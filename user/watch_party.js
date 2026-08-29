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
        roomId: new URLSearchParams(window.location.search).get('room_id'), //[cite: 6]
        roomName: 'Loading Room...', //[cite: 6]
        videoUrl: '', //[cite: 6]
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
            this.connectSignaling();

            // Camera/Mic requires a user gesture to avoid NotAllowedError when the page
            // is first loaded via a Barba.js AJAX transition (no real navigation event).
            // We attempt immediately, but if it fails we wait for the first user click
            // and retry — so a manual refresh is never needed.
            const mediaStarted = await this.startLocalMedia();
            if (!mediaStarted) {
                const retryOnGesture = async () => {
                    document.removeEventListener('click', retryOnGesture);
                    await this.startLocalMedia();
                };
                document.addEventListener('click', retryOnGesture, { once: true });
            }
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
                    window.location.href = 'dashboard.php'; // Redirect if ended or missing
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
            const roomId = urlParams.get('room_id') || urlParams.get('id');
            
            const query = roomCode 
                ? `room_code=${encodeURIComponent(roomCode)}` 
                : `room_id=${encodeURIComponent(roomId)}`;

            if (this.isHost) {
                const confirmEnd = confirm("Leaving as host will end this watch party for everyone. Continue?");
                if (!confirmEnd) return;
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
                // Cleanup local media
                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => track.stop());
                }

                // Disconnect socket if any
                if (this.socket) {
                    this.socket.disconnect();
                }

                // Smart redirect
                const referrer = document.referrer;
                const currentOrigin = window.location.origin;

                if (referrer && referrer.startsWith(currentOrigin) && referrer !== window.location.href) {
                    // Go back to the page the user came from (same origin)
                    window.location.href = referrer;
                } else if (window.history.length > 1) {
                    // Fallback to history back if no valid referrer
                    window.history.back();
                } else {
                    // Final fallback
                    window.location.href = 'dashboard.php';
                }
            }
        },

        async fetchFriends() {
            try {
                const res = await fetch('../user_backend/get_friends.php'); //[cite: 6]
                const data = await res.json(); //[cite: 6]
                if (data.friends) { //[cite: 6]
                    this.friends = data.friends; //[cite: 6]
                }
            } catch (e) {
                console.error("Error fetching friends:", e); //[cite: 6]
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

        inviteFriend(friendId) { //[cite: 6]
            const urlParams = new URLSearchParams(window.location.search); //[cite: 6]
            const roomId = urlParams.get('room_id'); //[cite: 6]

            if (!this.socket || !this.socket.connected) { //[cite: 6]
                alert("Cannot send invite — signaling server is offline. Please ensure 'npm run dev' is running.");
                return;
            }
            
            this.socket.emit('send-lobby-invite', { //[cite: 6]
                targetUserId: friendId, //[cite: 6]
                hostName: window.CURRENT_USER_NAME,  //[cite: 6]
                roomId: roomId //[cite: 6]
            });
            
            this.showInviteMenu = false; //[cite: 6]
            alert("Invite sent!");  //[cite: 6]
        },

        // ==========================================
        // WEBRTC & LOCAL MEDIA[cite: 6]
        // ==========================================
        async startLocalMedia() { //[cite: 6]
            // Remove any existing local participant entry before (re-)attempting
            this.participants = this.participants.filter(p => p.id !== 'local');

            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true }); //[cite: 6]
                
                this.participants.push({ //[cite: 6]
                    id: 'local', //[cite: 6]
                    name: 'You', //[cite: 6]
                    stream: this.localStream, //[cite: 6]
                    muted: false, //[cite: 6]
                    speaking: false, //[cite: 6]
                    isSelf: true //[cite: 6]
                });
                return true; // Signal success to init()
            } catch (e) {
                console.warn("Camera/Mic access denied or unavailable — will retry on next click.", e); //[cite: 6]
                // Add a placeholder tile so the UI doesn't appear broken
                this.participants.push({
                    id: 'local',
                    name: 'You (no camera)',
                    stream: null,
                    muted: true,
                    speaking: false,
                    isSelf: true,
                    noCamera: true
                });
                return false; // Signal failure so init() can set up a retry
            }
        },

        connectSignaling() { //[cite: 6]
            // Attempt to connect; if the signaling server is offline the app keeps
            // running — video/chat features just won't be real-time until it starts.
            this.socket = io('http://localhost:3000', {
                reconnectionAttempts: 5,        // Stop after 5 retries (no infinite flood)
                reconnectionDelay: 3000,         // Wait 3 s between retries
                timeout: 5000                    // Fail fast if server unreachable
            });

            this.socket.on('connect', () => { //[cite: 6]
                console.log("Connected to signaling server with ID:", this.socket.id); //[cite: 6]
                const myUserId = window.CURRENT_USER_ID;  //[cite: 6]
                
                if (myUserId) { //[cite: 6]
                    this.socket.emit('register-user', myUserId); //[cite: 6]
                }
            });

            this.socket.on('connect_error', (err) => {
                console.warn("[Socket] Signaling server unreachable — real-time features paused.", err.message);
            });

            this.socket.on('reconnect_failed', () => {
                console.warn("[Socket] Could not reconnect after 5 attempts. Is the server running? (npm run dev)");
            });

            this.socket.on('receive-invite', (data) => { //[cite: 6]
                window.dispatchEvent(new CustomEvent('incoming-party-invite', { detail: data })); //[cite: 6]
            });
        },

        // ==========================================
        // VIDEO PLAYER CONTROLS[cite: 6]
        // ==========================================
        togglePlay() { //[cite: 6]
            this.isPlaying = !this.isPlaying; //[cite: 6]
            if (this.isPlaying) { //[cite: 6]
                this.$refs.videoPlayer.play(); //[cite: 6]
            } else {
                this.$refs.videoPlayer.pause(); //[cite: 6]
            }
        },

        updateProgress() { //[cite: 6]
            this.currentTime = this.$refs.videoPlayer.currentTime; //[cite: 6]
            this.progressPercent = (this.currentTime / this.duration) * 100; //[cite: 6]
            
            if (this.$refs.videoPlayer.buffered.length > 0) { //[cite: 6]
                this.bufferPercent = (this.$refs.videoPlayer.buffered.end(0) / this.duration) * 100; //[cite: 6]
            }
        },

        seek(e) { //[cite: 6]
            const rect = this.$refs.progressBar.getBoundingClientRect(); //[cite: 6]
            const pos = (e.clientX - rect.left) / rect.width; //[cite: 6]
            this.$refs.videoPlayer.currentTime = pos * this.duration; //[cite: 6]
        },

        updateVolume() { //[cite: 6]
            this.$refs.videoPlayer.volume = this.volume; //[cite: 6]
        },

        toggleMute() { //[cite: 6]
            this.volume = this.volume === 0 ? 1 : 0; //[cite: 6]
            this.updateVolume(); //[cite: 6]
        },

        toggleFullscreen() { //[cite: 6]
            const contentArea = document.getElementById('content-area'); //[cite: 6]
            if (!document.fullscreenElement) { //[cite: 6]
                contentArea.requestFullscreen().catch(err => console.log(err)); //[cite: 6]
                this.isFullscreen = true; //[cite: 6]
            } else {
                document.exitFullscreen(); //[cite: 6]
                this.isFullscreen = false; //[cite: 6]
            }
        },

        formatTime(seconds) { //[cite: 6]
            if(isNaN(seconds)) return "00:00"; //[cite: 6]
            const m = Math.floor(seconds / 60).toString().padStart(2, '0'); //[cite: 6]
            const s = Math.floor(seconds % 60).toString().padStart(2, '0'); //[cite: 6]
            return `${m}:${s}`; //[cite: 6]
        },

        // ==========================================
        // CHAT & BOTTOM TOGGLES[cite: 6]
        // ==========================================
        sendMessage() { //[cite: 6]
            if (this.newMessage.trim() === '') return; //[cite: 6]
            console.log("Preparing to send:", this.newMessage); //[cite: 6]
            this.newMessage = ''; //[cite: 6]
            
            this.$nextTick(() => { //[cite: 6]
                const container = document.getElementById('chat-container'); //[cite: 6]
                container.scrollTop = container.scrollHeight; //[cite: 6]
            });
        },

        toggleMic() { //[cite: 6]
            this.isMuted = !this.isMuted; //[cite: 6]
            if(this.localStream) { //[cite: 6]
                this.localStream.getAudioTracks()[0].enabled = !this.isMuted; //[cite: 6]
            }
        },

        toggleVideo() { //[cite: 6]
            this.isVideoOn = !this.isVideoOn; //[cite: 6]
            if(this.localStream) { //[cite: 6]
                this.localStream.getVideoTracks()[0].enabled = this.isVideoOn; //[cite: 6]
            }
        }
    };
}
