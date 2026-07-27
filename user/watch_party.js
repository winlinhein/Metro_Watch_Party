document.addEventListener('alpine:init', () => {
    Alpine.data('watchParty', () => ({
        isMuted: false,
        isVideoOn: true,
        newMessage: '',
        showChat: true,
        showParticipants: true,
        
        // Video Player State
        isPlaying: false,
        isLoading: true,
        showControls: true,
        progressPercent: 0,
        bufferPercent: 0,
        currentTime: 0,
        duration: 0,
        volume: 1,
        controlsTimeout: null,
        isFullscreen: false,

        participants: [
            { name: 'Alex M.', cam: 'https://ui-avatars.com/api/?name=Alex+M&background=3b82f6&color=fff', muted: false, speaking: true },
            { name: 'Sarah C.', cam: 'https://ui-avatars.com/api/?name=Sarah+C&background=ec4899&color=fff', muted: true, speaking: false },
            { name: 'John D.', cam: 'https://ui-avatars.com/api/?name=John+D&background=10b981&color=fff', muted: false, speaking: false },
            { name: 'Jane S.', cam: 'https://ui-avatars.com/api/?name=Jane+S&background=f59e0b&color=fff', muted: true, speaking: false },
        ],
        messages: [
            { name: 'System', text: 'Welcome to the watch party! 🎉', time: '20:00', avatar: 'https://ui-avatars.com/api/?name=S&background=ef4444&color=fff', isSelf: false },
            { name: 'Sarah C.', text: 'This movie is so good! 🍿', time: '20:02', avatar: 'https://ui-avatars.com/api/?name=Sarah+C&background=ec4899&color=fff', isSelf: false },
        ],
        init() {
            // INSANE GSAP Animations Setup
            const tl = gsap.timeline();

            // 1. Stage and Video Container entrance
            tl.fromTo('.gs-stage', 
                { opacity: 0, scale: 0.95 },
                { opacity: 1, scale: 1, duration: 1, ease: 'power3.out' }
            )
            .fromTo('.video-container', 
                { 
                    opacity: 0, 
                    scale: 0.7, 
                    rotationX: 20, 
                    rotationY: 15, 
                    z: -500 
                },
                { 
                    opacity: 1, 
                    scale: 1, 
                    rotationX: 0, 
                    rotationY: 0, 
                    z: 0,
                    duration: 1.5, 
                    ease: 'elastic.out(1, 0.75)',
                    transformPerspective: 1000
                },
                "-=0.5"
            )
            // 2. Participants Grid staggered entrance
            .fromTo('.participant-card', 
                { opacity: 0, y: 50, scale: 0.8 },
                { 
                    opacity: 1, 
                    y: 0, 
                    scale: 1, 
                    duration: 0.8, 
                    stagger: 0.15,
                    ease: 'back.out(1.7)'
                },
                "-=1.0"
            )
            // 3. Chat Panel sliding in with 3D flip
            .fromTo('.gs-chat', 
                { opacity: 0, x: 100, rotationY: 45 },
                { 
                    opacity: 1, 
                    x: 0, 
                    rotationY: 0,
                    duration: 1.2, 
                    ease: 'power4.out',
                    transformPerspective: 1000
                },
                "-=1.2"
            )
            // 4. Bottom Controls rising up
            .fromTo('.gs-controls', 
                { opacity: 0, y: 50 },
                { opacity: 1, y: 0, duration: 1, ease: 'power3.out' },
                "-=1"
            );

            // Ambient breathing effect for the video container
            gsap.to('.video-container', {
                boxShadow: '0 30px 60px rgba(239,68,68,0.3)',
                duration: 2,
                repeat: -1,
                yoyo: true,
                ease: 'sine.inOut'
            });

            this.scrollToBottom();

            
            // Video player setup
            this.$nextTick(() => {
                const video = this.$refs.videoPlayer;
                if (video) {
                    const handleLoaded = () => {
                        this.isLoading = false;
                        this.duration = video.duration;
                        this.togglePlay(); // auto play
                    };

                    if (video.readyState >= 2) {
                        handleLoaded();
                    } else {
                        video.addEventListener('loadeddata', handleLoaded);
                    }
                    
                    video.addEventListener('progress', () => {
                        if (video.buffered.length > 0) {
                            this.bufferPercent = (video.buffered.end(video.buffered.length - 1) / video.duration) * 100;
                        }
                    });

                    video.addEventListener('waiting', () => {
                        this.isLoading = true;
                    });

                    video.addEventListener('playing', () => {
                        this.isLoading = false;
                    });

                    video.addEventListener('error', () => {
                        this.isLoading = false;
                        console.error("Video failed to load.");
                    });
                }
                
                document.addEventListener('fullscreenchange', () => {
                    this.isFullscreen = !!document.fullscreenElement;
                });
            });
        },
        togglePlay() {
            const video = this.$refs.videoPlayer;
            if (video.paused) {
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        this.isPlaying = true;
                        this.controlsTimeout = setTimeout(() => { this.showControls = false; }, 2500);
                        
                        // Fun animation on play
                        gsap.fromTo('.video-container', 
                            { scale: 0.98 }, 
                            { scale: 1, duration: 0.5, ease: 'elastic.out(1, 0.5)' }
                        );
                    }).catch(error => {
                        console.log("Autoplay prevented:", error);
                        this.isPlaying = false;
                        this.showControls = true;
                    });
                } else {
                    this.isPlaying = true;
                    this.controlsTimeout = setTimeout(() => { this.showControls = false; }, 2500);
                }
            } else {
                video.pause();
                this.isPlaying = false;
                this.showControls = true;
                
                // Fun animation on pause
                gsap.to('.video-container', { scale: 0.98, duration: 0.3, ease: 'power2.out' });
            }
        },
        updateProgress() {
            const video = this.$refs.videoPlayer;
            this.currentTime = video.currentTime;
            if (this.duration > 0) {
                this.progressPercent = (this.currentTime / this.duration) * 100;
            }
        },
        seek(e) {
            const rect = this.$refs.progressBar.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            const video = this.$refs.videoPlayer;
            video.currentTime = pos * this.duration;
            this.updateProgress();
            
            // Interaction feedback
            gsap.fromTo(this.$refs.progressBar, 
                { scaleY: 1.5 }, 
                { scaleY: 1, duration: 0.3, ease: 'bounce.out' }
            );
        },
        toggleMute() {
            const video = this.$refs.videoPlayer;
            video.muted = !video.muted;
            if (video.muted) {
                this.volume = 0;
            } else {
                this.volume = 1;
            }
        },
        updateVolume(e) {
            const video = this.$refs.videoPlayer;
            video.volume = this.volume;
            video.muted = this.volume === 0;
        },
        formatTime(seconds) {
            if (isNaN(seconds)) return "00:00";
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            if (h > 0) {
                return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
            return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        },
        toggleFullscreen() {
            const container = document.getElementById('content-area');
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        },
        toggleMic() {
            this.isMuted = !this.isMuted;
            this.participants[0].muted = this.isMuted;
            
            // Animation for toggle
            const btn = event.currentTarget;
            gsap.fromTo(btn, { scale: 0.8 }, { scale: 1, duration: 0.4, ease: 'back.out(2)' });
        },
        toggleVideo() {
            this.isVideoOn = !this.isVideoOn;
            
            const btn = event.currentTarget;
            gsap.fromTo(btn, { scale: 0.8 }, { scale: 1, duration: 0.4, ease: 'back.out(2)' });
        },
        sendMessage() {
            if (this.newMessage.trim() === '') return;
            
            const now = new Date();
            const time = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
            
            this.messages.push({
                name: 'Alex M. (You)',
                text: this.newMessage,
                time: time,
                avatar: 'https://ui-avatars.com/api/?name=Alex+M&background=3b82f6&color=fff',
                isSelf: true
            });
            
            this.newMessage = '';
            
            this.$nextTick(() => {
                this.scrollToBottom();
                
                // Animate newest message
                const msgs = document.querySelectorAll('.chat-msg-item');
                const lastMsg = msgs[msgs.length - 1];
                if (lastMsg) {
                    gsap.fromTo(lastMsg, 
                        { opacity: 0, x: 20, scale: 0.9 }, 
                        { opacity: 1, x: 0, scale: 1, duration: 0.4, ease: 'back.out(1.5)' }
                    );
                }
            });
        },
        scrollToBottom() {
            const container = document.getElementById('chat-container');
            if (container) {
                gsap.to(container, {
                    scrollTop: container.scrollHeight,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        }
    }));
});
