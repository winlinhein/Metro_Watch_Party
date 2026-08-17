function watchParty() {
    return {
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
        participants: [], // Holds everyone's streams
        messages: [],
        newMessage: '',
        
        // --- Dynamic Room State ---
        roomId: new URLSearchParams(window.location.search).get('room_id'),
        roomName: 'Loading Room...',
        videoUrl: '',

        friends: [],
        showInviteMenu: false,

        async init() {
            // Fetch friends right when the room loads
            await this.fetchRoomDetails();
            await this.fetchFriends();
            
            this.$refs.videoPlayer.onloadedmetadata = () => {
                this.duration = this.$refs.videoPlayer.duration;
            };
            await this.startLocalMedia();
            this.connectSignaling();
        },

        async fetchRoomDetails() {
            // We will add the PHP fetch logic here in the next step!
            console.log("Fetching data for room:", this.roomId);
        },

        async fetchFriends() {
            try {
                // Call your existing backend endpoint
                const res = await fetch('user_backend/get_friends.php');
                const data = await res.json();
                if (data.friends) {
                    this.friends = data.friends;
                }
            } catch (e) {
                console.error("Error fetching friends:", e);
            }
        },

        inviteFriend(friendId) {
            // Extract room ID from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const roomId = urlParams.get('room_id');
            
            // Use dynamic user name from PHP
            this.socket.emit('send-lobby-invite', {
                targetUserId: friendId,
                hostName: window.CURRENT_USER_NAME, 
                roomId: roomId
            });
            
            this.showInviteMenu = false;
            
            // Optional: Trigger your custom toast here instead of an alert!
            alert("Invite sent!"); 
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
                    name: 'You',
                    stream: this.localStream,
                    muted: false,
                    speaking: false,
                    isSelf: true // Mutes your own video element so you don't hear an echo
                });
            } catch (e) {
                console.error("Camera/Mic access denied or unavailable.", e);
            }
        },

       connectSignaling() {
            this.socket = io('http://localhost:3000'); 

            this.socket.on('connect', () => {
                console.log("Connected to signaling server with ID:", this.socket.id);
                
                // Use the dynamic ID passed from PHP
                const myUserId = window.CURRENT_USER_ID; 
                
                if (myUserId) {
                    this.socket.emit('register-user', myUserId);
                }
            });

            this.socket.on('receive-invite', (data) => {
                // If they happen to receive an invite while ALREADY in a room
                window.dispatchEvent(new CustomEvent('incoming-party-invite', { detail: data }));
            });
        },

        // ==========================================
        // VIDEO PLAYER CONTROLS
        // ==========================================
        togglePlay() {
            this.isPlaying = !this.isPlaying;
            if (this.isPlaying) {
                this.$refs.videoPlayer.play();
                // TODO: Send WebSocket message to peers: { action: "play", time: this.currentTime }
            } else {
                this.$refs.videoPlayer.pause();
                // TODO: Send WebSocket message to peers: { action: "pause", time: this.currentTime }
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
            // TODO: Send WebSocket message to peers: { action: "seek", time: this.$refs.videoPlayer.currentTime }
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
            
            // Temporarily log the message until we connect the backend socket.
            console.log("Preparing to send:", this.newMessage);
            
            this.newMessage = '';
            
            this.$nextTick(() => {
                const container = document.getElementById('chat-container');
                container.scrollTop = container.scrollHeight;
            });
        },

        toggleMic() {
            this.isMuted = !this.isMuted;
            if(this.localStream) {
                // Disable/Enable the actual audio track
                this.localStream.getAudioTracks()[0].enabled = !this.isMuted;
            }
        },

        toggleVideo() {
            this.isVideoOn = !this.isVideoOn;
            if(this.localStream) {
                // Disable/Enable the actual video track
                this.localStream.getVideoTracks()[0].enabled = this.isVideoOn;
            }
        }
    }
}