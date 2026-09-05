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
            { urls: 'stun:stun1.l.google.com:19302' }
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

        async init() {
            // Bind page unload listener to end room if host closes window/tab
            window.addEventListener('beforeunload', () => {
                if (this.isHost && this.roomId) {
                    navigator.sendBeacon(`../user_backend/leave_room.php?room_id=${encodeURIComponent(this.roomId)}`);
                }
            });

            this.fetchFriends();
            this.fetchMovies();
            await this.startLocalMedia();
            await this.fetchRoomDetails();
            this.connectSignaling();
        },

        // Fetch room metadata & enforce host permissions / room active status
        async fetchRoomDetails() {
            if (!this.roomId) return;
            this.isLoading = true;

            try {
                const res = await fetch(`../user_backend/get_room_details.php?room_id=${encodeURIComponent(this.roomId)}`);
                const result = await res.json();

                if (!result.success) {
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
                    this.currentMovie = movie;
                    this.videoUrl = movie.stream_url || '';
                    if (this.$refs.videoPlayer && this.videoUrl) {
                        this.$refs.videoPlayer.src = this.videoUrl;
                    }
                }

            } catch (e) {
                console.error("Error fetching room details:", e);
            } finally {
                this.isLoading = false;
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

        selectMovie(movie) {
            this.videoUrl = movie.actual_video_url || movie.trailer || movie.video_url || '';
            this.currentMovie = movie;
            this.showMovieModal = false;
            setTimeout(() => {
                if (this.$refs.videoPlayer) {
                    this.$refs.videoPlayer.onloadedmetadata = () => {
                        this.duration = this.$refs.videoPlayer.duration;
                    };
                    this.isPlaying = true;
                    this.$refs.videoPlayer.play();
                }
            }, 100);
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

        upsertParticipant(peer) {
            const idx = this.participants.findIndex(p =>
                (peer.socketId && p.socketId === peer.socketId) ||
                (p.id && peer.id && String(p.id) === String(peer.id) && !p.isSelf)
            );
            if (idx >= 0) {
                const prev = this.participants[idx];
                this.participants.splice(idx, 1, {
                    ...prev,
                    ...peer,
                    stream: peer.stream || prev.stream
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
                    name: window.USER_NAME || 'You',
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
                    name: window.USER_NAME || 'You',
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
                return {
                    socketId: data.socketId || data.fromSocketId || null,
                    userId: data.userId,
                    userName: data.userName || data.name || 'Guest'
                };
            }
            return { socketId: null, userId: data, userName: 'Guest' };
        },

        createPeerConnection(peerSocketId, peerMeta = {}) {
            if (!peerSocketId || peerSocketId === this.socket?.id) return null;
            if (peerConnections[peerSocketId]) return peerConnections[peerSocketId];

            const pc = new RTCPeerConnection({ iceServers: this.iceServers });
            peerConnections[peerSocketId] = pc;

            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    pc.addTrack(track, this.localStream);
                });
            }

            pc.onicecandidate = (event) => {
                if (event.candidate && this.socket) {
                    this.socket.emit('ice-candidate', {
                        targetSocketId: peerSocketId,
                        candidate: event.candidate,
                        fromSocketId: this.socket.id
                    });
                }
            };

            pc.ontrack = (event) => {
                const stream = event.streams[0] || new MediaStream([event.track]);
                this.upsertParticipant({
                    id: peerMeta.userId || peerSocketId,
                    socketId: peerSocketId,
                    name: peerMeta.userName || 'Guest',
                    stream,
                    muted: false,
                    videoOn: true,
                    speaking: false,
                    isSelf: false
                });
            };

            pc.onconnectionstatechange = () => {
                if (pc.connectionState === 'failed' || pc.connectionState === 'closed' || pc.connectionState === 'disconnected') {
                    if (pc.connectionState !== 'disconnected') {
                        this.removePeer({ socketId: peerSocketId });
                    }
                }
            };

            return pc;
        },

        async callPeer(peer) {
            const { socketId, userId, userName } = this.normalizePeer(peer);
            if (!socketId || !this.socket || socketId === this.socket.id) return;

            this.upsertParticipant({
                id: userId || socketId,
                socketId,
                name: userName || 'Guest',
                stream: null,
                muted: false,
                videoOn: true,
                speaking: false,
                isSelf: false
            });

            const pc = this.createPeerConnection(socketId, { userId, userName });
            if (!pc || pc.localDescription) return;

            try {
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                this.socket.emit('offer', {
                    targetSocketId: socketId,
                    sdp: pc.localDescription,
                    fromSocketId: this.socket.id,
                    userId: Number(window.CURRENT_USER_ID) || null,
                    userName: window.USER_NAME || 'You'
                });
            } catch (e) {
                console.error('Failed to create offer for', socketId, e);
            }
        },

        async handleOffer(data) {
            const fromSocketId = data.fromSocketId;
            if (!fromSocketId || fromSocketId === this.socket?.id) return;

            this.upsertParticipant({
                id: data.userId || fromSocketId,
                socketId: fromSocketId,
                name: data.userName || 'Guest',
                stream: null,
                muted: false,
                videoOn: true,
                speaking: false,
                isSelf: false
            });

            const pc = this.createPeerConnection(fromSocketId, {
                userId: data.userId,
                userName: data.userName
            });
            if (!pc) return;

            try {
                await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
                await this.flushPendingCandidates(fromSocketId);
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                this.socket.emit('answer', {
                    targetSocketId: fromSocketId,
                    sdp: pc.localDescription,
                    fromSocketId: this.socket.id,
                    userId: Number(window.CURRENT_USER_ID) || null,
                    userName: window.USER_NAME || 'You'
                });
            } catch (e) {
                console.error('Failed to handle offer from', fromSocketId, e);
            }
        },

        async handleAnswer(data) {
            const fromSocketId = data.fromSocketId;
            const pc = peerConnections[fromSocketId];
            if (!pc) return;
            try {
                if (!pc.currentRemoteDescription) {
                    await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
                    await this.flushPendingCandidates(fromSocketId);
                }
            } catch (e) {
                console.error('Failed to handle answer from', fromSocketId, e);
            }
        },

        async handleIceCandidate(data) {
            const fromSocketId = data.fromSocketId;
            if (!fromSocketId || !data.candidate) return;
            const pc = peerConnections[fromSocketId];
            if (pc && pc.remoteDescription) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
                } catch (e) {
                    console.warn('Failed to add ICE candidate', e);
                }
                return;
            }
            if (!pendingCandidates[fromSocketId]) pendingCandidates[fromSocketId] = [];
            pendingCandidates[fromSocketId].push(data.candidate);
        },

        async flushPendingCandidates(socketId) {
            const pc = peerConnections[socketId];
            const queued = pendingCandidates[socketId] || [];
            pendingCandidates[socketId] = [];
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
            const socketId = peer.socketId;
            if (socketId && peerConnections[socketId]) {
                try { peerConnections[socketId].close(); } catch (e) { /* ignore */ }
                delete peerConnections[socketId];
            }
            this.participants = this.participants.filter(p => {
                if (p.isSelf) return true;
                if (socketId && p.socketId === socketId) return false;
                if (!socketId && peer.userId && String(p.id) === String(peer.userId)) return false;
                return true;
            });
        },

        connectSignaling() {
            // PHP pages (e.g. :9000) don't serve Socket.io — use Node signaling on :3000 locally.
            // Override with window.NEXUS_SIGNALING_URL when needed.
            const signalingUrl = window.NEXUS_SIGNALING_URL
                || (location.port && location.port !== '3000'
                    ? `${location.protocol}//${location.hostname}:3000`
                    : undefined);
            this.socket = signalingUrl ? io(signalingUrl) : io();

            this.socket.on('connect', () => {
                console.log("Connected to signaling server with ID:", this.socket.id);
                const myUserId = Number(window.CURRENT_USER_ID);
                const myName = window.USER_NAME || 'You';

                if (myUserId) {
                    this.socket.emit('register-user', myUserId);
                }

                if (this.roomId) {
                    this.socket.emit('join-room', String(this.roomId), myUserId, myName);
                }
            });

            this.socket.on('connect_error', (err) => {
                console.warn("Signaling server connection error:", err.message);
                if (window.showToast) {
                    window.showToast("Could not connect to signaling server. Real-time features may be limited.", 'error');
                }
            });

            this.socket.on('receive-invite', (data) => {
                window.dispatchEvent(new CustomEvent('incoming-party-invite', {
                    detail: {
                        hostName: data.hostName,
                        sender_name: data.hostName || data.sender_name,
                        sender_id: data.hostId || data.sender_id,
                        roomId: data.roomId || data.room_id,
                        room_id: data.roomId || data.room_id,
                        message: data.message || 'invited you to a watch party.'
                    }
                }));
            });

            this.socket.on('existing-users', (users) => {
                (users || []).forEach(peer => this.callPeer(peer));
            });

            this.socket.on('user-connected', (data) => {
                const peer = this.normalizePeer(data);
                console.log("Peer joined room:", peer);
                this.upsertParticipant({
                    id: peer.userId || peer.socketId,
                    socketId: peer.socketId,
                    name: peer.userName || 'Guest',
                    stream: null,
                    muted: false,
                    videoOn: true,
                    speaking: false,
                    isSelf: false
                });
                this.createPeerConnection(peer.socketId, peer);
            });

            this.socket.on('user-disconnected', (data) => {
                console.log("Peer left room:", data);
                this.removePeer(data);
            });

            this.socket.on('offer', (data) => this.handleOffer(data));
            this.socket.on('answer', (data) => this.handleAnswer(data));
            this.socket.on('ice-candidate', (data) => this.handleIceCandidate(data));

            this.socket.on('peer-mic-changed', ({ userId, socketId, isMuted }) => {
                const p = this.participants.find(x => x.socketId === socketId || String(x.id) === String(userId));
                if (p && !p.isSelf) {
                    p.muted = isMuted;
                    this.participants = [...this.participants];
                }
            });

            this.socket.on('peer-video-changed', ({ userId, socketId, isVideoOn }) => {
                const p = this.participants.find(x => x.socketId === socketId || String(x.id) === String(userId));
                if (p && !p.isSelf) {
                    p.videoOn = isVideoOn;
                    this.participants = [...this.participants];
                }
            });

            this.socket.on('new_message', (msg) => {
                if (msg.senderId && Number(msg.senderId) === Number(window.CURRENT_USER_ID)) return;
                this.messages.push({ ...msg, isSelf: false });
                this.$nextTick(() => {
                    const container = document.getElementById('chat-container');
                    if (container) container.scrollTop = container.scrollHeight;
                });
            });
        },

        // ==========================================
        // VIDEO PLAYER CONTROLS
        // ==========================================
        togglePlay() {
            this.isPlaying = !this.isPlaying;
            if (this.isPlaying) {
                this.$refs.videoPlayer.play();
            } else {
                this.$refs.videoPlayer.pause();
            }
        },

        updateProgress() {
            this.currentTime = this.$refs.videoPlayer.currentTime;
            this.progressPercent = (this.currentTime / this.duration) * 100;
            
            if (this.$refs.videoPlayer.buffered.length > 0) {
                this.bufferPercent = (this.$refs.videoPlayer.buffered.end(0) / this.duration) * 100;
            }
        },

        seek(e) {
            const rect = this.$refs.progressBar.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            this.$refs.videoPlayer.currentTime = pos * this.duration;
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
                avatar: `https://ui-avatars.com/api/?name=${encodeURIComponent(window.USER_NAME || 'You')}&background=random&color=fff`,
                isSelf: true,
                senderId: Number(window.CURRENT_USER_ID) || null
            };

            this.messages.push(msg);

            if (this.socket && this.socket.connected && this.roomId) {
                this.socket.emit('send_message', {
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

            if (this.socket && this.socket.connected) {
                this.socket.emit('toggle-mic', this.isMuted);
            }
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

            if (this.socket && this.socket.connected) {
                this.socket.emit('toggle-video', this.isVideoOn);
            }
        }
    }
}