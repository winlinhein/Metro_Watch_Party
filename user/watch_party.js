function watchParty() {
    const peerConnections = {};
    const pendingCandidates = {};

    return {

        showMovieModal: false,
        movieSearchQuery: '',
        movieFilter: 'all',
        hoveredMovieId: null,
        allMovies: [],
        // --- 1. UI State ---
        showChat: true,
        showParticipants: true,
        showControls: false,
        controlsTimeout: null,
        isLoading: false,

        // --- 2. Video Player State ---
        isPlaying: false,
        volume: 1,
        currentTime: 0,
        duration: 0,
        progressPercent: 0,
        bufferPercent: 0,
        isFullscreen: false,

        // --- 3. WebRTC & Media State ---
        isMuted: false,
        isVideoOn: true,
        localStream: null,
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' }
        ],
        participants: [],
        messages: [],
        newMessage: '',
        
        // --- Dynamic Room State ---
        roomId: new URLSearchParams(window.location.search).get('room_id'),
        roomName: 'Loading Room...',
        videoUrl: '',
        isHost: false,
        currentMovie: null,

        friends: [],
        showInviteMenu: false,
        showInviteSentModal: false,
        inviteSentName: '',
        socket: null,
        pusherClient: null,
        roomChannel: null,
        applyingRemotePlayback: false,
        liveStatus: 'Connecting…',
        peerId: (window.crypto && crypto.randomUUID)
            ? crypto.randomUUID()
            : ('peer-' + Date.now() + '-' + Math.random().toString(16).slice(2)),
        roomSyncTimer: null,

        async init() {
            window.addEventListener('beforeunload', () => {
                if (!this.roomId) return;
                const qs = `room_id=${encodeURIComponent(this.roomId)}&peer_id=${encodeURIComponent(this.peerId)}`;
                navigator.sendBeacon(`../user_backend/leave_room.php?${qs}`);
            });
            document.addEventListener('click', () => {
                document.querySelectorAll('video').forEach((el) => {
                    el.play().catch(() => {});
                });
            }, { once: true });

            this.fetchFriends();
            this.fetchMovies();
            await this.startLocalMedia();
            await this.fetchRoomDetails();
            await this.connectSignaling();
            await this.announcePresence();
            this.startRoomSync();
        },

        // Fetch room metadata & enforce host permissions / room active status
        async fetchRoomDetails({ quiet = false } = {}) {
            if (!this.roomId) return;
            if (!quiet) this.isLoading = true;

            try {
                const res = await fetch(`../user_backend/get_room_details.php?room_id=${encodeURIComponent(this.roomId)}`);
                const result = await res.json();

                if (!result.success) {
                    if (quiet) return;
                    alert(result.message || "Unable to join room.");
                    window.location.href = 'dashboard.php';
                    return;
                }

                const { room, movie } = result.data;

                // 1. Identify Host Status
                this.isHost = (parseInt(room.host_id) === parseInt(window.CURRENT_USER_ID));
                this.roomName = `Room #${room.room_code}`;
                if (room.room_id) {
                    this.roomId = String(room.room_id);
                }

                // 2. Set Movie / Media Source if assigned
                if (movie) {
                    this.applyMovie(movie);
                }

            } catch (e) {
                console.error("Error fetching room details:", e);
            } finally {
                if (!quiet) this.isLoading = false;
            }
        },

        // Triggered when user explicitly clicks a "Leave Room" button
        async leaveRoom() {
            const urlParams = new URLSearchParams(window.location.search);
            const roomCode = urlParams.get('room_code');
            const roomId   = urlParams.get('room_id') || urlParams.get('id');

            const query = roomCode
                ? `room_code=${encodeURIComponent(roomCode)}`
                : `room_id=${encodeURIComponent(roomId)}`;

            if (this.isHost) {
                const confirmEnd = confirm("Leaving as host will end this watch party for everyone. Continue?");
                if (!confirmEnd) return;
            }

            this.teardownMediaAndPeers();

            try {
                const res = await fetch(`../user_backend/leave_room.php?${query}`, {
                    method: 'POST'
                });
                const data = await res.json();
                console.log("Leave response:", data);
            } catch (e) {
                console.error("Error leaving room:", e);
            } finally {
                // Always navigate away — even if the API call failed
                window.location.href = 'dashboard.php';
            }
        },

        async fetchFriends() {
            try {
                const res = await fetch('../user_backend/get_friends.php');
                const data = await res.json();
                if (data.friends) {
                    this.friends = data.friends;
                }
            } catch (e) {
                console.error("Error fetching friends:", e);
            }
        },

        get filteredMovies() {
            const query = (this.movieSearchQuery || '').toLowerCase();
            return (this.allMovies || []).filter(movie => {
                const genres = Array.isArray(movie.genres) ? movie.genres : String(movie.genre || '').split(',').map(s => s.trim());
                const genreOk = this.movieFilter === 'all' || genres.includes(this.movieFilter);
                const titleOk = !query || (movie.title && movie.title.toLowerCase().includes(query));
                return genreOk && titleOk;
            });
        },

        async fetchMovies() {
            try {
                const res = await fetch('/user_backend/movies_api.php');
                const data = await res.json();
                this.allMovies = Array.isArray(data) ? data : [];
            } catch (e) {
                console.error("Error fetching movies:", e);
            }
        },

        async selectMovie(movie) {
            if (!movie) return;
            this.applyMovie(movie);

            const movieId = movie.id || movie.movie_id;
            if (this.roomId && movieId) {
                try {
                    const form = new FormData();
                    form.append('room_id', this.roomId);
                    form.append('movie_id', movieId);
                    await fetch('../user_backend/set_room_movie.php', {
                        method: 'POST',
                        body: form
                    });
                } catch (e) {
                    console.error('Failed to persist shared movie:', e);
                }
            }

            if (this.socket && this.socket.connected) {
                this.socket.emit('movie-changed', {
                    movieId,
                    videoUrl: this.videoUrl,
                    title: movie.title || '',
                    movie
                });
            }
        },

        isYouTubeUrl(url) {
            if (!url) return false;
            return url.includes('youtube.com') || url.includes('youtu.be');
        },

        getYouTubeEmbedUrl(url, isHover = false) {
            if (!url) return '';
            const match = url.match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/);
            if (match && match[2].length === 11) {
                const videoId = match[2];
                const params = new URLSearchParams({
                    autoplay: isHover ? '1' : '0',
                    mute: isHover ? '1' : '0',
                    controls: '0',
                    loop: '1',
                    playlist: videoId,
                    modestbranding: '1',
                    rel: '0',
                    showinfo: '0',
                    iv_load_policy: '3',
                    enablejsapi: '1',
                    disablekb: '1'
                });
                return `https://www.youtube.com/embed/${videoId}?${params.toString()}`;
            }
            return url;
        },

        getYouTubeWatchEmbed(url) {
            if (!url) return '';
            const match = url.match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/);
            if (match && match[2].length === 11) {
                const videoId = match[2];
                const params = new URLSearchParams({
                    autoplay: '1',
                    mute: '0',
                    controls: '1',
                    rel: '0',
                    modestbranding: '1',
                    playsinline: '1'
                });
                return `https://www.youtube.com/embed/${videoId}?${params.toString()}`;
            }
            return url;
        },

        movieStreamUrl(movie) {
            if (!movie) return '';
            return movie.actual_video_url || movie.stream_url || movie.trailer || movie.video_url || '';
        },

        applyMovie(movie) {
            this.currentMovie = movie || null;
            const nextUrl = this.movieStreamUrl(movie);
            if (nextUrl && nextUrl === this.videoUrl) return;
            this.videoUrl = nextUrl;
            this.showMovieModal = false;
            this.isPlaying = !!(this.videoUrl && !this.isYouTubeUrl(this.videoUrl));
            if (this.videoUrl && !this.isYouTubeUrl(this.videoUrl)) {
                this.$nextTick(() => {
                    if (this.$refs.videoPlayer) {
                        this.$refs.videoPlayer.onloadedmetadata = () => {
                            this.duration = this.$refs.videoPlayer.duration;
                        };
                        this.$refs.videoPlayer.play().catch(() => {});
                    }
                });
            }
        },

        async inviteFriend(friendId, friendName = 'friend') {
            const urlParams = new URLSearchParams(window.location.search);
            const roomId = urlParams.get('room_id') || this.roomId;
            const targetId = Number(friendId);

            if (!roomId || !targetId) {
                if (window.showToast) window.showToast("Missing room or friend.", "error");
                return;
            }

            this.showInviteMenu = false;

            try {
                const form = new FormData();
                form.append('target_user_id', targetId);
                form.append('room_id', roomId);
                const res = await fetch('../user_backend/send_party_invite.php', {
                    method: 'POST',
                    body: form
                });
                const data = await res.json();
                if (!data.success) {
                    if (window.showToast) window.showToast(data.message || 'Invite failed.', 'error');
                    return;
                }
            } catch (e) {
                console.error(e);
                if (window.showToast) window.showToast('Network error sending invite.', 'error');
                return;
            }

            // Live socket delivery while friend is online
            if (this.socket && this.socket.connected) {
                this.socket.emit('send-lobby-invite', {
                    targetUserId: targetId,
                    hostName: window.USER_NAME || window.CURRENT_USER_NAME || 'Someone',
                    hostId: Number(window.CURRENT_USER_ID) || null,
                    roomId: Number(roomId),
                    room_id: Number(roomId),
                    sender_id: Number(window.CURRENT_USER_ID) || null,
                    sender_name: window.USER_NAME || 'Someone',
                    message: 'invited you to a watch party.'
                });
            }

            this.inviteSentName = friendName || 'friend';
            this.showInviteSentModal = true;
        },

        // ==========================================
        // WEBRTC & LOCAL MEDIA
        // ==========================================
        localPreviewStream() {
            if (!this.localStream) return null;
            const videoTracks = this.localStream.getVideoTracks();
            // Preview must never include the mic track — that is what causes self-echo.
            return videoTracks.length ? new MediaStream(videoTracks) : null;
        },

        resolveAvatarUrl(url, name = 'User') {
            if (!url) {
                return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'User')}&background=ef4444&color=fff&bold=true`;
            }
            if (/^(https?:)?\/\//i.test(url) || url.startsWith('data:') || url.startsWith('blob:')) return url;
            if (url.startsWith('/user_backend/media.php') || url.startsWith('/uploads/') || url.startsWith('/')) return url;
            return '/uploads/avatars/' + String(url).replace(/^\/+/, '');
        },

        selfAvatar() {
            return this.resolveAvatarUrl(window.USER_AVATAR || '', window.USER_NAME || 'You');
        },

        selfBorder() {
            return window.USER_BORDER || '';
        },

        withPeerMedia(peer) {
            const name = peer.name || peer.userName || 'User';
            const uid = Number(peer.userId || (peer.isSelf ? window.CURRENT_USER_ID : peer.id));
            let avatar = peer.avatar || '';
            let border = peer.border || peer.border_preview || '';
            if (!avatar) {
                if (peer.avatar_url) {
                    avatar = this.resolveAvatarUrl(peer.avatar_url, name);
                } else if (peer.isSelf || uid === Number(window.CURRENT_USER_ID)) {
                    avatar = this.selfAvatar();
                    border = border || this.selfBorder();
                } else {
                    const friend = (this.friends || []).find(f => Number(f.user_id) === uid);
                    if (friend) {
                        avatar = this.resolveAvatarUrl(friend.avatar_url, friend.user_name);
                        border = border || friend.border_preview || '';
                    } else {
                        avatar = this.resolveAvatarUrl('', name);
                    }
                }
            }
            return { ...peer, name, avatar, border };
        },

        mapRoomPeer(peer) {
            return {
                peerId: peer.peer_id || peer.peerId,
                userId: peer.user_id || peer.userId,
                userName: peer.user_name || peer.userName,
                avatar_url: peer.avatar_url,
                border_preview: peer.border_preview
            };
        },

        upsertParticipant(peer) {
            peer = this.withPeerMedia(peer);
            const idx = this.participants.findIndex(p => {
                if (p.isSelf && peer.isSelf) return true;
                if (peer.peerId && p.peerId && p.peerId === peer.peerId) return true;
                if (peer.socketId && p.socketId && p.socketId === peer.socketId) return true;
                if (p.id && peer.id && String(p.id) === String(peer.id) && !p.isSelf && !peer.isSelf) return true;
                return false;
            });
            if (idx >= 0) {
                const prev = this.participants[idx];
                this.participants.splice(idx, 1, {
                    ...prev,
                    ...peer,
                    stream: peer.stream || prev.stream,
                    avatar: peer.avatar || prev.avatar,
                    border: peer.border || prev.border
                });
            } else {
                this.participants.push(peer);
            }
            this.participants = [...this.participants];
        },

        teardownMediaAndPeers() {
            Object.keys(peerConnections).forEach(id => {
                try { peerConnections[id].close(); } catch (e) { /* ignore */ }
                delete peerConnections[id];
            });
            Object.keys(pendingCandidates).forEach(id => { delete pendingCandidates[id]; });

            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
                this.localStream = null;
            }

            if (this.socket) {
                this.socket.disconnect();
                this.socket = null;
            }
        },

        async startLocalMedia() {
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });

                this.upsertParticipant({
                    id: 'local',
                    socketId: 'local',
                    peerId: this.peerId,
                    name: window.USER_NAME || 'You',
                    avatar: this.selfAvatar(),
                    border: this.selfBorder(),
                    stream: this.localPreviewStream(),
                    muted: this.isMuted,
                    videoOn: this.isVideoOn,
                    speaking: false,
                    isSelf: true
                });

            } catch (e) {
                console.error("Camera/Mic access denied or unavailable.", e);

                let msg = "Camera/mic unavailable.";
                if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
                    msg = "Camera/mic permission denied. Please allow access in your browser settings.";
                } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
                    msg = "No camera or microphone found on this device.";
                } else if (e.name === 'NotReadableError' || e.name === 'TrackStartError') {
                    msg = "Camera/mic is in use by another application.";
                } else if (e.name === 'OverconstrainedError') {
                    msg = "Camera/mic constraints could not be satisfied.";
                }

                if (window.showToast) {
                    window.showToast(msg, 'error');
                } else {
                    console.warn(msg);
                }

                this.upsertParticipant({
                    id: 'local',
                    socketId: 'local',
                    peerId: this.peerId,
                    name: window.USER_NAME || 'You',
                    avatar: this.selfAvatar(),
                    border: this.selfBorder(),
                    stream: null,
                    muted: true,
                    videoOn: false,
                    speaking: false,
                    isSelf: true
                });
            }
        },

        normalizePeer(data) {
            if (data && typeof data === 'object') {
                const peerId = data.peerId || data.fromPeerId || data.socketId || data.fromSocketId || null;
                return {
                    peerId,
                    socketId: data.socketId || data.fromSocketId || peerId,
                    userId: data.userId || data.fromUserId,
                    userName: data.userName || data.name || 'Guest',
                    avatar_url: data.avatar_url,
                    border_preview: data.border_preview
                };
            }
            return { peerId: null, socketId: null, userId: data, userName: 'Guest' };
        },

        peerKey(peer) {
            return peer?.peerId || peer?.socketId || null;
        },

        waitForIce(pc) {
            if (!pc || pc.iceGatheringState === 'complete') return Promise.resolve();
            return new Promise((resolve) => {
                const finish = () => {
                    pc.removeEventListener('icegatheringstatechange', onState);
                    resolve();
                };
                const onState = () => {
                    if (pc.iceGatheringState === 'complete') finish();
                };
                pc.addEventListener('icegatheringstatechange', onState);
                setTimeout(finish, 2500);
            });
        },

        createPeerConnection(peerKey, peerMeta = {}) {
            if (!peerKey || peerKey === this.peerId || peerKey === this.socket?.id) return null;
            if (peerConnections[peerKey]) return peerConnections[peerKey];

            const pc = new RTCPeerConnection({
                iceServers: this.iceServers,
                iceCandidatePoolSize: 4
            });
            peerConnections[peerKey] = pc;

            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    pc.addTrack(track, this.localStream);
                });
            }

            pc.onicecandidate = (event) => {
                if (!event.candidate) return;
                this.signal('ice-candidate', {
                    targetPeerId: peerKey,
                    targetSocketId: peerMeta.socketId || peerKey,
                    candidate: event.candidate,
                    fromPeerId: this.peerId,
                    fromSocketId: this.socket?.id
                });
            };

            pc.ontrack = (event) => {
                const stream = event.streams[0] || new MediaStream([event.track]);
                this.upsertParticipant({
                    id: peerMeta.userId || peerKey,
                    peerId: peerKey,
                    socketId: peerMeta.socketId || peerKey,
                    userId: peerMeta.userId,
                    name: peerMeta.userName || 'Guest',
                    avatar_url: peerMeta.avatar_url,
                    border_preview: peerMeta.border_preview,
                    stream,
                    muted: false,
                    videoOn: true,
                    speaking: false,
                    isSelf: false
                });
            };

            pc.onconnectionstatechange = () => {
                if (pc.connectionState === 'failed' || pc.connectionState === 'closed') {
                    this.removePeer({ peerId: peerKey, socketId: peerKey });
                }
            };

            return pc;
        },

        async callPeer(peer) {
            const normalized = this.normalizePeer(peer);
            const key = this.peerKey(normalized);
            if (!key || key === this.peerId || key === this.socket?.id) return;

            this.upsertParticipant({
                id: normalized.userId || key,
                peerId: key,
                socketId: normalized.socketId || key,
                userId: normalized.userId,
                name: normalized.userName || 'Guest',
                avatar_url: normalized.avatar_url,
                border_preview: normalized.border_preview,
                stream: null,
                muted: false,
                videoOn: true,
                speaking: false,
                isSelf: false
            });

            const pc = this.createPeerConnection(key, normalized);
            if (!pc || pc.localDescription) return;

            try {
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                await this.waitForIce(pc);
                this.signal('offer', {
                    targetPeerId: key,
                    targetSocketId: normalized.socketId || key,
                    sdp: pc.localDescription,
                    fromPeerId: this.peerId,
                    fromSocketId: this.socket?.id,
                    userId: Number(window.CURRENT_USER_ID) || null,
                    userName: window.USER_NAME || 'You',
                    avatar_url: window.USER_AVATAR || '',
                    border_preview: window.USER_BORDER || ''
                });
            } catch (e) {
                console.error('Failed to create offer for', key, e);
            }
        },

        async handleOffer(data) {
            const from = this.normalizePeer(data);
            const fromKey = this.peerKey(from);
            if (!fromKey || fromKey === this.peerId) return;
            if (data.targetPeerId && data.targetPeerId !== this.peerId && data.targetSocketId !== this.socket?.id) return;

            this.upsertParticipant({
                id: from.userId || fromKey,
                peerId: fromKey,
                socketId: from.socketId || fromKey,
                userId: from.userId,
                name: from.userName || 'Guest',
                avatar_url: from.avatar_url,
                border_preview: from.border_preview,
                stream: null,
                muted: false,
                videoOn: true,
                speaking: false,
                isSelf: false
            });

            const pc = this.createPeerConnection(fromKey, from);
            if (!pc) return;

            try {
                if (pc.signalingState === 'have-local-offer') {
                    try { await pc.setLocalDescription({ type: 'rollback' }); } catch (e) { /* ignore */ }
                }
                if (pc.signalingState !== 'stable' && pc.currentRemoteDescription) return;
                await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
                await this.flushPendingCandidates(fromKey);
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                await this.waitForIce(pc);
                this.signal('answer', {
                    targetPeerId: fromKey,
                    targetSocketId: from.socketId || fromKey,
                    sdp: pc.localDescription,
                    fromPeerId: this.peerId,
                    fromSocketId: this.socket?.id,
                    userId: Number(window.CURRENT_USER_ID) || null,
                    userName: window.USER_NAME || 'You',
                    avatar_url: window.USER_AVATAR || '',
                    border_preview: window.USER_BORDER || ''
                });
            } catch (e) {
                console.error('Failed to handle offer from', fromKey, e);
            }
        },

        async handleAnswer(data) {
            const from = this.normalizePeer(data);
            const fromKey = this.peerKey(from);
            if (data.targetPeerId && data.targetPeerId !== this.peerId && data.targetSocketId !== this.socket?.id) return;
            const pc = peerConnections[fromKey];
            if (!pc) return;
            try {
                if (!pc.currentRemoteDescription) {
                    await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
                    await this.flushPendingCandidates(fromKey);
                }
            } catch (e) {
                console.error('Failed to handle answer from', fromKey, e);
            }
        },

        async handleIceCandidate(data) {
            const from = this.normalizePeer(data);
            const fromKey = this.peerKey(from);
            if (!fromKey || !data.candidate) return;
            if (data.targetPeerId && data.targetPeerId !== this.peerId && data.targetSocketId !== this.socket?.id) return;
            const pc = peerConnections[fromKey];
            if (pc && pc.remoteDescription) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
                } catch (e) {
                    console.warn('Failed to add ICE candidate', e);
                }
                return;
            }
            if (!pendingCandidates[fromKey]) pendingCandidates[fromKey] = [];
            pendingCandidates[fromKey].push(data.candidate);
        },

        async flushPendingCandidates(peerKey) {
            const pc = peerConnections[peerKey];
            const queued = pendingCandidates[peerKey] || [];
            pendingCandidates[peerKey] = [];
            if (!pc) return;
            for (const candidate of queued) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (e) {
                    console.warn('Failed to flush ICE candidate', e);
                }
            }
        },

        removePeer(payload) {
            const peer = this.normalizePeer(payload);
            const key = this.peerKey(peer);
            if (key && peerConnections[key]) {
                try { peerConnections[key].close(); } catch (e) { /* ignore */ }
                delete peerConnections[key];
            }
            this.participants = this.participants.filter(p => {
                if (p.isSelf) return true;
                if (key && (p.peerId === key || p.socketId === key)) return false;
                if (!key && peer.userId && String(p.id) === String(peer.userId)) return false;
                return true;
            });
        },

        signal(event, data) {
            if (this.socket && this.socket.connected && (event === 'offer' || event === 'answer' || event === 'ice-candidate' || event === 'send_message' || event === 'toggle-mic' || event === 'toggle-video' || event === 'movie-changed' || event === 'playback-sync')) {
                this.socket.emit(event, data);
            }
            this.sendPusherSignal(event, data);
        },

        sendPusherSignal(event, data) {
            if (!this.roomId) return;
            const mapped = event === 'send_message' ? 'new_message' : event;
            const allowed = {
                offer: true,
                answer: true,
                'ice-candidate': true,
                'peer-leave': true,
                new_message: true,
                'playback-sync': true,
                'toggle-mic': true,
                'toggle-video': true
            };
            if (!allowed[mapped]) return;
            const form = new FormData();
            form.append('room_id', this.roomId);
            form.append('event', mapped);
            form.append('payload', JSON.stringify({
                ...data,
                fromPeerId: this.peerId
            }));
            fetch('../user_backend/room_signal.php', { method: 'POST', body: form }).catch(() => {});
        },

        onRoomEvent(event, data) {
            if (!data) return;
            if (data.fromPeerId && data.fromPeerId === this.peerId) return;
            if (data.fromSocketId && this.socket?.id && data.fromSocketId === this.socket.id) return;
            if (event === 'offer' || event === 'answer') {
                const key = event + ':' + (data.fromPeerId || data.fromSocketId || '') + ':' + String(data.sdp && data.sdp.sdp || '').slice(0, 64);
                this._seenSignals = this._seenSignals || {};
                if (this._seenSignals[key]) return;
                this._seenSignals[key] = true;
            }

            if (event === 'peer-join' || event === 'user-connected') {
                const peer = this.normalizePeer(data);
                const key = this.peerKey(peer);
                if (!key || key === this.peerId) return;
                this.upsertParticipant({
                    id: peer.userId || key,
                    peerId: key,
                    socketId: peer.socketId || key,
                    userId: peer.userId,
                    name: peer.userName || 'Guest',
                    avatar_url: data.avatar_url,
                    border_preview: data.border_preview,
                    stream: null,
                    muted: false,
                    videoOn: true,
                    speaking: false,
                    isSelf: false
                });
                this.createPeerConnection(key, peer);
                if (String(this.peerId) > String(key)) this.callPeer(peer);
                return;
            }
            if (event === 'peer-leave' || event === 'user-disconnected') {
                this.removePeer(data);
                return;
            }
            if (event === 'existing-users') {
                (data || []).forEach(peer => this.callPeer(peer));
                return;
            }
            if (event === 'offer') return this.handleOffer(data);
            if (event === 'answer') return this.handleAnswer(data);
            if (event === 'ice-candidate') return this.handleIceCandidate(data);
            if (event === 'new_message') {
                if (data.senderId && Number(data.senderId) === Number(window.CURRENT_USER_ID)) return;
                const mk = 'msg:' + (data.senderId || '') + ':' + (data.text || '') + ':' + (data.time || '');
                this._seenSignals = this._seenSignals || {};
                if (this._seenSignals[mk]) return;
                this._seenSignals[mk] = true;
                this.messages.push({ ...data, isSelf: false });
                this.$nextTick(() => {
                    const container = document.getElementById('chat-container');
                    if (container) container.scrollTop = container.scrollHeight;
                });
                return;
            }
            if (event === 'movie-changed') {
                const movie = data.movie || { actual_video_url: data.videoUrl, title: data.title };
                if (!movie.actual_video_url && data.videoUrl) movie.actual_video_url = data.videoUrl;
                this.applyMovie(movie);
                return;
            }
            if (event === 'playback-sync') {
                this.applyRemotePlayback(data);
                return;
            }
            if (event === 'toggle-mic' || event === 'peer-mic-changed') {
                const p = this.participants.find(x => x.peerId === data.fromPeerId || x.socketId === data.socketId || String(x.id) === String(data.userId));
                if (p && !p.isSelf) {
                    p.muted = data.isMuted;
                    this.participants = [...this.participants];
                }
                return;
            }
            if (event === 'toggle-video' || event === 'peer-video-changed') {
                const p = this.participants.find(x => x.peerId === data.fromPeerId || x.socketId === data.socketId || String(x.id) === String(data.userId));
                if (p && !p.isSelf) {
                    p.videoOn = data.isVideoOn;
                    this.participants = [...this.participants];
                }
            }
        },

        bindPusherRoom() {
            if (typeof Pusher === 'undefined' || !this.roomId) return false;
            if (!this.pusherClient) {
                this.pusherClient = new Pusher(window.PUSHER_KEY || 'f4b5637ef4b8952b6eb8', {
                    cluster: window.PUSHER_CLUSTER || 'ap1',
                    encrypted: true
                });
            }
            const channelName = `watch-party-${this.roomId}`;
            this.roomChannel = this.pusherClient.subscribe(channelName);
            [
                'peer-join', 'peer-leave', 'offer', 'answer', 'ice-candidate',
                'new_message', 'movie-changed', 'playback-sync', 'toggle-mic', 'toggle-video'
            ].forEach(event => {
                this.roomChannel.bind(event, (data) => this.onRoomEvent(event, data));
            });
            this.liveStatus = 'Live';
            return true;
        },

        async announcePresence() {
            if (!this.roomId || !this.peerId) return;
            try {
                const form = new FormData();
                form.append('room_id', this.roomId);
                form.append('peer_id', this.peerId);
                const res = await fetch('../user_backend/join_room.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success && Array.isArray(data.peers)) {
                    data.peers.forEach(peer => this.callPeer(this.mapRoomPeer(peer)));
                }
            } catch (e) {
                console.error('Failed to announce presence', e);
            }
        },

        startRoomSync() {
            if (this.roomSyncTimer) clearInterval(this.roomSyncTimer);
            this.roomSyncTimer = setInterval(() => {
                this.fetchRoomDetails({ quiet: true });
                if (!this.roomId || !this.peerId) return;
                const form = new FormData();
                form.append('room_id', this.roomId);
                form.append('peer_id', this.peerId);
                form.append('heartbeat', '1');
                fetch('../user_backend/join_room.php', { method: 'POST', body: form })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success || !Array.isArray(data.peers)) return;
                        data.peers.forEach(peer => {
                            const key = peer.peer_id || peer.peerId;
                            if (!key) return;
                            const pc = peerConnections[key];
                            if (pc && (pc.localDescription || pc.remoteDescription)) return;
                            this.callPeer(this.mapRoomPeer(peer));
                        });
                    })
                    .catch(() => {});
            }, 4000);
        },

        ensureSocketIo() {
            if (typeof io === 'function') return Promise.resolve(io);

            const signalingBase = window.NEXUS_SIGNALING_URL
                || ((location.port && location.port !== '3000')
                    ? `${location.protocol}//${location.hostname}:3000`
                    : `${location.protocol}//${location.host}`);

            const urls = [
                `${signalingBase}/socket.io/socket.io.js`,
                'https://cdn.jsdelivr.net/npm/socket.io-client@4.7.5/dist/socket.io.min.js',
                'https://cdn.socket.io/4.7.5/socket.io.min.js'
            ];

            return new Promise((resolve) => {
                const tryLoad = (index) => {
                    if (typeof io === 'function') return resolve(io);
                    if (index >= urls.length) return resolve(null);
                    const script = document.createElement('script');
                    script.src = urls[index];
                    script.async = true;
                    script.onload = () => resolve(typeof io === 'function' ? io : null);
                    script.onerror = () => tryLoad(index + 1);
                    document.head.appendChild(script);
                };
                tryLoad(0);
            });
        },

        async connectSignaling() {
            const pusherOk = this.bindPusherRoom();
            if (!pusherOk && window.showToast) {
                window.showToast('Live sync is limited. Check your connection.', 'error');
            }

            const ioClient = await this.ensureSocketIo();
            if (!ioClient) {
                if (!pusherOk && window.showToast) {
                    window.showToast('Could not start live room connection.', 'error');
                }
                return;
            }

            const signalingUrl = window.NEXUS_SIGNALING_URL
                || (location.port && location.port !== '3000'
                    ? `${location.protocol}//${location.hostname}:3000`
                    : undefined);
            this.socket = signalingUrl
                ? ioClient(signalingUrl, { transports: ['websocket', 'polling'] })
                : ioClient({ transports: ['websocket', 'polling'] });

            this.socket.on('connect', () => {
                console.log('Connected to signaling server with ID:', this.socket.id);
                const myUserId = Number(window.CURRENT_USER_ID);
                const myName = window.USER_NAME || 'You';
                if (myUserId) this.socket.emit('register-user', myUserId);
                if (this.roomId) this.socket.emit('join-room', String(this.roomId), myUserId, myName);
                if (this.liveStatus !== 'Live') this.liveStatus = 'Live';
            });

            this.socket.on('connect_error', (err) => {
                console.warn('Signaling server connection error:', err.message);
                if (!this.roomChannel) this.liveStatus = 'Connecting…';
            });

            this.socket.on('receive-invite', (data) => {
                window.dispatchEvent(new CustomEvent('incoming-party-invite', { detail: data }));
            });

            this.socket.on('existing-users', (users) => this.onRoomEvent('existing-users', users));
            this.socket.on('user-connected', (data) => this.onRoomEvent('user-connected', data));
            this.socket.on('user-disconnected', (data) => this.onRoomEvent('user-disconnected', data));
            this.socket.on('offer', (data) => this.onRoomEvent('offer', data));
            this.socket.on('answer', (data) => this.onRoomEvent('answer', data));
            this.socket.on('ice-candidate', (data) => this.onRoomEvent('ice-candidate', data));
            this.socket.on('peer-mic-changed', (data) => this.onRoomEvent('peer-mic-changed', data));
            this.socket.on('peer-video-changed', (data) => this.onRoomEvent('peer-video-changed', data));
            this.socket.on('new_message', (data) => this.onRoomEvent('new_message', data));
            this.socket.on('movie-changed', (data) => this.onRoomEvent('movie-changed', data));
            this.socket.on('playback-sync', (data) => this.onRoomEvent('playback-sync', data));
        },

        applyRemotePlayback(data) {
            const player = this.$refs.videoPlayer;
            if (!player) return;
            this.applyingRemotePlayback = true;
            this.isPlaying = !!data.isPlaying;
            if (typeof data.currentTime === 'number' && Math.abs((player.currentTime || 0) - data.currentTime) > 1.25) {
                player.currentTime = data.currentTime;
            }
            if (this.isPlaying) {
                player.play().catch(() => {});
            } else {
                player.pause();
            }
            this.$nextTick(() => {
                this.applyingRemotePlayback = false;
            });
        },

        emitPlaybackSync() {
            if (this.applyingRemotePlayback) return;
            const player = this.$refs.videoPlayer;
            this.signal('playback-sync', {
                isPlaying: this.isPlaying,
                currentTime: player ? player.currentTime : (this.currentTime || 0)
            });
        },

        // ==========================================
        // VIDEO PLAYER CONTROLS
        // ==========================================
        togglePlay() {
            if (!this.$refs.videoPlayer) return;
            this.isPlaying = !this.isPlaying;
            if (this.isPlaying) {
                this.$refs.videoPlayer.play();
            } else {
                this.$refs.videoPlayer.pause();
            }
            this.emitPlaybackSync();
        },

        updateProgress() {
            if (!this.$refs.videoPlayer) return;
            this.currentTime = this.$refs.videoPlayer.currentTime;
            this.progressPercent = this.duration ? (this.currentTime / this.duration) * 100 : 0;
            
            if (this.$refs.videoPlayer.buffered.length > 0) {
                this.bufferPercent = (this.$refs.videoPlayer.buffered.end(0) / this.duration) * 100;
            }
        },

        seek(e) {
            if (!this.$refs.videoPlayer || !this.$refs.progressBar) return;
            const rect = this.$refs.progressBar.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            this.$refs.videoPlayer.currentTime = pos * this.duration;
            this.emitPlaybackSync();
        },

        updateVolume() {
            this.$refs.videoPlayer.volume = this.volume;
        },

        toggleMute() {
            this.volume = this.volume === 0 ? 1 : 0;
            this.updateVolume();
        },

        toggleFullscreen() {
            const contentArea = document.getElementById('content-area');
            if (!document.fullscreenElement) {
                contentArea.requestFullscreen().catch(err => console.log(err));
                this.isFullscreen = true;
            } else {
                document.exitFullscreen();
                this.isFullscreen = false;
            }
        },

        formatTime(seconds) {
            if(isNaN(seconds)) return "00:00";
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        // ==========================================
        // CHAT & BOTTOM TOGGLES
        // ==========================================
        sendMessage() {
            if (this.newMessage.trim() === '') return;
            
            const msg = {
                name: window.USER_NAME || 'You',
                text: this.newMessage.trim(),
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                avatar: this.selfAvatar(),
                isSelf: true,
                senderId: Number(window.CURRENT_USER_ID) || null
            };

            this.messages.push(msg);

            if (this.roomId) {
                this.signal('send_message', {
                    room: this.roomId,
                    ...msg
                });
            }

            this.newMessage = '';
            
            this.$nextTick(() => {
                const container = document.getElementById('chat-container');
                if (container) container.scrollTop = container.scrollHeight;
            });
        },

        toggleMic() {
            this.isMuted = !this.isMuted;

            // Guard: stream may be null if getUserMedia was denied
            if (this.localStream) {
                const audioTracks = this.localStream.getAudioTracks();
                if (audioTracks.length > 0) {
                    audioTracks[0].enabled = !this.isMuted;
                }
            }

            const localParticipant = this.participants.find(p => p.id === 'local');
            if (localParticipant) {
                localParticipant.muted = this.isMuted;
                this.participants = [...this.participants];
            }

            this.signal('toggle-mic', { isMuted: this.isMuted, socketId: this.socket?.id, userId: Number(window.CURRENT_USER_ID) || null });
        },

        toggleVideo() {
            this.isVideoOn = !this.isVideoOn;

            // Guard: stream may be null if getUserMedia was denied
            if (this.localStream) {
                const videoTracks = this.localStream.getVideoTracks();
                if (videoTracks.length > 0) {
                    videoTracks[0].enabled = this.isVideoOn;
                }
            }

            const localParticipant = this.participants.find(p => p.id === 'local');
            if (localParticipant) {
                localParticipant.videoOn = this.isVideoOn;
                this.participants = [...this.participants];
            }

            this.signal('toggle-video', { isVideoOn: this.isVideoOn, socketId: this.socket?.id, userId: Number(window.CURRENT_USER_ID) || null });
        }
    }
}