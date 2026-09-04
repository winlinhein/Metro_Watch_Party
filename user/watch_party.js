function watchParty() {
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

        async init() {
            // Bind page unload listener to end room if host closes window/tab
            window.addEventListener('beforeunload', () => {
                if (this.isHost && this.roomId) {
                    navigator.sendBeacon(`../user_backend/leave_room.php?room_id=${encodeURIComponent(this.roomId)}`);
                }
            });

            this.fetchRoomDetails();
            this.fetchFriends();
            this.fetchMovies();
            this.startLocalMedia();
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

            // Stop local media tracks so camera/mic light turns off
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
                this.localStream = null;
            }

            // Disconnect from signaling server
            if (this.socket) {
                this.socket.disconnect();
                this.socket = null;
            }

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

        inviteFriend(friendId) {
            const urlParams = new URLSearchParams(window.location.search);
            const roomId = urlParams.get('room_id');
            
            if (!this.socket || !this.socket.connected) {
                if (window.showToast) window.showToast("Not connected to signaling server.", "error");
                return;
            }

            this.socket.emit('send-lobby-invite', {
                targetUserId: friendId,
                hostName: window.CURRENT_USER_NAME || window.USER_NAME || 'Someone',
                roomId: roomId
            });
            
            this.showInviteMenu = false;
            if (window.showToast) {
                window.showToast("Invite sent!", "success");
            } else {
                alert("Invite sent!");
            }
        },

        // ==========================================
        // WEBRTC & LOCAL MEDIA
        // ==========================================
        async startLocalMedia() {
            try {
                // Request camera and mic access
                this.localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });

                // Add yourself to the participants sidebar
                this.participants.push({
                    id: 'local',
                    name: window.USER_NAME || 'You',
                    stream: this.localStream,
                    muted: this.isMuted,
                    videoOn: this.isVideoOn,
                    speaking: false,
                    isSelf: true // Mutes your own video element so you don't hear an echo
                });

            } catch (e) {
                console.error("Camera/Mic access denied or unavailable.", e);

                // Determine a friendly error message
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

                // Show toast if available, otherwise console warn
                if (window.showToast) {
                    window.showToast(msg, 'error');
                } else {
                    console.warn(msg);
                }

                // Still add a placeholder participant so the UI slot appears
                this.participants.push({
                    id: 'local',
                    name: window.USER_NAME || 'You',
                    stream: null,
                    muted: true,
                    videoOn: false,
                    speaking: false,
                    isSelf: true
                });
            }
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
                const myUserId = window.CURRENT_USER_ID;

                if (myUserId) {
                    this.socket.emit('register-user', myUserId);
                }

                // Join the watch party room for WebRTC signaling
                if (this.roomId) {
                    this.socket.emit('join-room', this.roomId, myUserId);
                }
            });

            this.socket.on('connect_error', (err) => {
                console.warn("Signaling server connection error:", err.message);
                if (window.showToast) {
                    window.showToast("Could not connect to signaling server. Real-time features may be limited.", 'error');
                }
            });

            this.socket.on('receive-invite', (data) => {
                window.dispatchEvent(new CustomEvent('incoming-party-invite', { detail: data }));
            });

            // --- WebRTC peer events ---
            this.socket.on('user-connected', (userId) => {
                console.log("Peer joined room:", userId);
            });

            this.socket.on('user-disconnected', (userId) => {
                console.log("Peer left room:", userId);
                // Remove from participants list
                this.participants = this.participants.filter(p => p.id !== userId);
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
                isSelf: true
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
            }

            if (this.socket && this.socket.connected) {
                this.socket.emit('toggle-video', this.isVideoOn);
            }
        }
    }
}