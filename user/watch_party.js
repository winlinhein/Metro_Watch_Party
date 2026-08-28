function watchParty() {
    return {

        showMovieModal: false,
        movieSearchQuery: '',
        movieFilter: 'all',
        filteredMovies: [],
        hoveredMovieId: null,
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

            // Fetch room details and friends on load[cite: 6]
            await this.fetchRoomDetails();
            await this.fetchFriends(); //[cite: 6]
            
            //this.$refs.videoPlayer.onloadedmetadata = () => { //[cite: 6]
               // this.duration = this.$refs.videoPlayer.duration; //[cite: 6]
          //  };
            await this.startLocalMedia(); //[cite: 6]
            this.connectSignaling(); //[cite: 6]
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
    // Extract whichever parameter is present in the current URL
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
        // Await the fetch so the browser doesn't navigate away early
       const res = await fetch(`../user_backend/leave_room.php?${query}`, {
    method: 'POST'
});
        const data = await res.json();
        console.log("Leave response:", data);
    } catch (e) {
        console.error("Error leaving room:", e);
    } finally {
        //window.location.href = 'dashboard.php';
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

        inviteFriend(friendId) { //[cite: 6]
            const urlParams = new URLSearchParams(window.location.search); //[cite: 6]
            const roomId = urlParams.get('room_id'); //[cite: 6]
            
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
            } catch (e) {
                console.error("Camera/Mic access denied or unavailable.", e); //[cite: 6]
            }
        },

        connectSignaling() { //[cite: 6]
            this.socket = io('http://localhost:3000');  //[cite: 6]

            this.socket.on('connect', () => { //[cite: 6]
                console.log("Connected to signaling server with ID:", this.socket.id); //[cite: 6]
                const myUserId = window.CURRENT_USER_ID;  //[cite: 6]
                
                if (myUserId) { //[cite: 6]
                    this.socket.emit('register-user', myUserId); //[cite: 6]
                }
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
    }
}