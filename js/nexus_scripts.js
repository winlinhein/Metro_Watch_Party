function userDashboard() {
    return {
        // Navigation & Tab State
        currentTab: 'dashboard',
        isNavOpen: false,
        
        // Drawer Panels & Modals State
        showFriendsPanel: false,
        showQuestsPanel: false,
        questActiveTab: 'daily',
        showInviteModal: false,
        movieModalOpen: false,
        editingMovie: false,
        banModalOpen: false,
        viewModalOpen: false,
        roomModalOpen: false,
        modalOpen: false,
        modalMode: 'add',

        // Form & Data Objects
        userToBan: null,
        banReason: '',
        banNotes: '',
        selectedReport: null,
        selectedRoom: null,
        formData: { name: '', price: 0, rarity: 'Common', image: '' },
        newMovie: { title: '', genre: '', year: '', rating: '', description: '', trailer: '', img: '', comments: [] },
        shopItems: [],
        movies: [],
        async fetchMovies() { 
            try { 
                const response = await fetch("/backend/movies_api.php"); 
                const text = await response.text(); 
                try { 
                    const data = JSON.parse(text); 
                    if (response.ok) { 
                        this.movies = data; 
                    } 
                } catch(e) {} 
            } catch(e) {} 
        }, 
        init() { 
            this.fetchMovies(); 
        },

        // Quests Data
        quests: {
            daily: [
                { id: 1, title: 'Watch a Movie', desc: 'Watch any movie for at least 30 minutes', points: 50, completed: true },
                { id: 2, title: 'Host a Watch Party', desc: 'Invite at least 1 friend to a party', points: 100, completed: false },
                { id: 3, title: 'Chat Master', desc: 'Send 10 messages in party chat', points: 30, completed: false }
            ],
            weekly: [
                { id: 4, title: 'Movie Marathon', desc: 'Watch 3 movies this week', points: 300, completed: false },
                { id: 5, title: 'Social Butterfly', desc: 'Host 3 watch parties', points: 500, completed: false },
                { id: 6, title: 'Genre Explorer', desc: 'Watch movies from 3 different genres', points: 250, completed: true }
            ],
            monthly: [
                { id: 7, title: 'Cinephile', desc: 'Watch 15 movies this month', points: 1500, completed: false },
                { id: 8, title: 'Party Animal', desc: 'Host 10 watch parties', points: 2000, completed: false },
                { id: 9, title: 'Community Pillar', desc: 'Add 5 new friends', points: 1000, completed: false }
            ]
        },

        // Friends & User Search State
        friends: [],
        searchQuery: '',
        searchResults: [],
        friendSearchQuery: '',
        searchTimeout: null,

        // Navigation Items
        navItems: [
            { id: 'dashboard', label: 'Command Center', icon: 'dashboard', module: 'MODULE_1' },
            { id: 'watchlist', label: 'Watchlist', icon: 'bookmark', module: 'MODULE_2' },
            { id: 'movies', label: 'Movies', icon: 'movie', module: 'MODULE_3' },
            { id: 'history', label: 'Watch History', icon: 'history_toggle_off', module: 'MODULE_4' },
            { id: 'settings', label: 'System Preferences', icon: 'settings', module: 'MODULE_5' }
        ],

        // Command Center Metrics
        stats: [
            { label: 'Total Watch Time', value: 124, suffix: 'H', icon: 'timer', colorClass: 'bg-red-500/10 text-red-500 border border-red-500/20 group-hover:bg-red-500/20 group-hover:shadow-[0_0_20px_rgba(239,68,68,0.3)]', trendClass: 'text-green-400 border-green-400/20', trend: '+12%', desc: 'vs last week' },
            { label: 'Sessions Hosted', value: 28, suffix: '', icon: 'cell_tower', colorClass: 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 group-hover:bg-indigo-500/20 group-hover:shadow-[0_0_20px_rgba(79,70,229,0.3)]', trendClass: 'text-green-400 border-green-400/20', trend: '+3', desc: 'new this week' },
            { label: 'Friends', value: 0, suffix: '', icon: 'group', colorClass: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 group-hover:bg-emerald-500/20 group-hover:shadow-[0_0_20px_rgba(16,185,129,0.3)]', trendClass: 'text-emerald-400 border-emerald-400/20', trend: 'Online', desc: 'active', action: 'showFriendsPanel = true' },
            { label: 'Quests', value: 1250, suffix: ' PTS', icon: 'stars', colorClass: 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 group-hover:bg-yellow-500/20 group-hover:shadow-[0_0_20px_rgba(234,179,8,0.3)]', trendClass: 'text-yellow-400 border-yellow-400/20', trend: 'Available', desc: 'Daily quests', action: 'showQuestsPanel = true' }
        ],

        // Watch Party Sessions, Watchlist & Activity Feed
        upcomingParties: [
            { title: "Dune: Part Two", time: "TODAY 20:00", genre: "SCI-FI", host: "You", members: 8, img: "https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=400&h=200" },
            { title: "Interstellar", time: "TMRW 21:00", genre: "SCI-FI", host: "Sarah J.", members: 12, img: "https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?auto=format&fit=crop&q=80&w=400&h=200" },
            { title: "Cyberpunk Edgerunners", time: "FRI 22:00", genre: "ANIME", host: "David W.", members: 15, img: "https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&q=80&w=400&h=200" }
        ],
        watchlist: [
            { title: "Blade Runner 2049", year: "2017", genre: "SCI-FI", rating: "98%", status: "Next Up", img: "https://images.unsplash.com/photo-1618193132172-e1c6b6531398?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "The Matrix", year: "1999", genre: "ACTION", rating: "99%", status: "Pending", img: "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Arrival", year: "2016", genre: "SCI-FI", rating: "95%", status: "Pending", img: "https://images.unsplash.com/photo-1518331647614-7a1f04cd34cb?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Akira", year: "1988", genre: "ANIME", rating: "96%", status: "Pending", img: "https://images.unsplash.com/photo-1554629947-334ff61d85dc?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Ex Machina", year: "2014", genre: "THRILLER", rating: "92%", status: "Pending", img: "https://images.unsplash.com/photo-1535378273068-9bb67d5beacd?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Tron: Legacy", year: "2010", genre: "ACTION", rating: "88%", status: "Pending", img: "https://images.unsplash.com/photo-1510511459019-5efa7ae67376?auto=format&fit=crop&q=80&w=600&h=900" }
        ],
        activityFeed: [
            { text: '<span class="font-bold text-white">Sarah Connor</span> joined your network', time: '2 MINS AGO', dotColor: 'bg-indigo-500' },
            { text: 'Protocol initialized: <span class="font-bold text-red-400">Cyberpunk Edgerunners</span>', time: '1 HOUR AGO', dotColor: 'bg-red-500' },
            { text: 'Achievement unlocked: <span class="font-bold text-yellow-400">Night Owl V2</span>', time: 'YESTERDAY', dotColor: 'bg-yellow-500' },
            { text: 'System diagnostic completed. Connection stable.', time: '2 DAYS AGO', dotColor: 'bg-white/20' }
        ],
        networkTraffic: [
            { day: 'Mon', reqs: 1250, height: 40 },
            { day: 'Tue', reqs: 3400, height: 75 },
            { day: 'Wed', reqs: 2100, height: 55 },
            { day: 'Thu', reqs: 4800, height: 90 },
            { day: 'Fri', reqs: 3100, height: 65 },
            { day: 'Sat', reqs: 5500, height: 100 },
            { day: 'Sun', reqs: 4200, height: 85 },
            { day: 'Mon', reqs: 2500, height: 60 },
            { day: 'Tue', reqs: 3800, height: 80 },
            { day: 'Wed', reqs: 1900, height: 50 },
            { day: 'Thu', reqs: 4100, height: 82 },
            { day: 'Fri', reqs: 2900, height: 62 },
            { day: 'Sat', reqs: 5100, height: 95 },
            { day: 'Sun', reqs: 4600, height: 88 }
        ],

        // Keep stat card for Friends synchronized dynamically
        updateFriendsCount() {
            const friendStat = this.stats.find(s => s.label === 'Friends');
            if (friendStat) {
                friendStat.value = this.friends ? this.friends.length : 0;
            }
        },
        addFriend(friendId) {
            this.sendFriendRequest(friendId);
        },

       fetchFriends() {
            fetch('/user_backend/get_friends.php')
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    
                    if (!response.ok) {
                        // Fallback to response status text if data.error is missing
                        throw new Error(data.error || `HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    return data;
                })
                .then((data) => {
                    if (Array.isArray(data)) {
                        this.friends = data;
                        this.updateFriendsCount(); // Synchronize dashboard Friends stat card
                    }
                })
                .catch((error) => {
                    console.error('Failed to fetch friends:', error.message || error);
                });
        },

        getFriendStatus(user) {
            // 1. Return direct status from backend API if available ('friend', 'pending', 'none')
            const status = (user.friend_status || user.status || '').toLowerCase();
            if (['friend', 'pending', 'none'].includes(status)) {
                return status;
            }

            // 2. Fallback check against loaded friends list
            const isFriend = this.friends.some(f => (f.user_id || f.id) === user.user_id);
            if (isFriend) return 'friend';

            // 3. Fallback check for pending flag
            if (user.is_pending) return 'pending';

            return 'none';
        },

        sendFriendRequest(friendId) {
            fetch('/user_backend/add_friend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ friend_id: friendId })
            })
            .then(async res => {
                const text = await res.text();
                let data;
                
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // Strips HTML tags from PHP error output so you can see the exact cause
                    const cleanText = text.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                    throw new Error(cleanText || 'Server outputted non-JSON response.');
                }

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Failed to send friend request.');
                }

                return data;
            })
            .then(data => {
                alert(data.message);
                if (typeof this.searchUsers === 'function') {
                    this.searchUsers(); // Refresh search view
                }
            })
            .catch(err => {
                console.error('Error sending friend request:', err);
                alert(`Request Failed: ${err.message}`);
            });
        },
        searchUsers(query = null) {
            const searchTerm = (query !== null ? query : this.searchQuery) || '';
            fetch(`/user_backend/search_users.php?q=${encodeURIComponent(searchTerm.trim())}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (Array.isArray(data)) {
                        this.searchResults = data;
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        },

        get filteredFriends() {
            if (!this.friendSearchQuery) return this.friends;
            return this.friends.filter(f => 
                (f.user_name || f.username || '').toLowerCase().includes(this.friendSearchQuery.toLowerCase())
            );
        },

        // Tab Navigation
        switchTab(tabId) {
            if (this.currentTab === tabId) return;
            const oldTab = this.currentTab;
            this.currentTab = tabId;
            const oldPanel = document.querySelector(`[data-tab-panel="${oldTab}"]`);
            const newPanel = document.querySelector(`[data-tab-panel="${tabId}"]`);
            if (oldPanel && newPanel && typeof window.gsap !== 'undefined') {
                window.gsap.to(oldPanel, { opacity: 0, scale: 0.95, duration: 0.2, onComplete: () => {
                    oldPanel.style.display = 'none';
                    newPanel.style.display = 'block';
                    window.gsap.fromTo(newPanel, { opacity: 0, scale: 1.05 }, { opacity: 1, scale: 1, duration: 0.3, ease: "power2.out" });
                }});
            } else if (oldPanel && newPanel) {
                oldPanel.style.display = 'none';
                newPanel.style.display = 'block';
            }
        },

        // Navigation Drawer
        openNav() {
            if (this.isNavOpen) return;
            this.isNavOpen = true;
            
            const tl = gsap.timeline();
            this.$root.querySelector('#side-panel').style.pointerEvents = 'auto';
            this.$root.querySelector('#nav-overlay').style.pointerEvents = 'auto';
            
            tl.to('#nav-overlay', { opacity: 1, duration: 0.15, ease: "power2.out" }, 0);
            tl.to('#side-panel', { x: 0, duration: 0.5, ease: "expo.out" }, 0);
            tl.fromTo('.side-nav-item', 
                { x: 30, opacity: 0, scale: 0.9, rotationX: -15 },
                { x: 0, opacity: 1, scale: 1, rotationX: 0, stagger: 0.05, duration: 0.6, ease: "back.out(1.5)" },
                0.2
            );
            tl.fromTo('.side-panel-stagger',
                { opacity: 0, x: -20, scale: 0.95 },
                { opacity: 1, x: 0, scale: 1, stagger: 0.05, duration: 0.5, ease: "back.out(1.2)" },
                0.1
            );
        },
        closeNav() {
            if (!this.isNavOpen) return;
            this.isNavOpen = false;
            
            const tl = gsap.timeline({
                onComplete: () => {
                    this.$root.querySelector('#side-panel').style.pointerEvents = 'none';
                    this.$root.querySelector('#nav-overlay').style.pointerEvents = 'none';
                }
            });
            
            tl.to('.side-nav-item', {
                x: -30, opacity: 0, scale: 0.9, rotationX: 15, stagger: 0.02, duration: 0.3, ease: "power3.in"
            }, 0);
            tl.to('.side-panel-stagger', {
                opacity: 0, x: -20, scale: 0.95, duration: 0.2, ease: "power3.in"
            }, 0);
            tl.to('#side-panel', { x: '-100%', duration: 0.4, ease: "expo.inOut" }, 0.1);
            tl.to('#nav-overlay', { opacity: 0, duration: 0.3, ease: "power2.in" }, 0.2);
        },

        // Modal and Shared Helper Methods
        openEditMovieModal(movie) {
            this.editingMovie = true;
            this.newMovie = { ...movie, comments: movie.comments || [] };
            this.movieModalOpen = true;
        },
        openBanModal(user) {
            this.userToBan = user;
            this.banReason = '';
            this.banModalOpen = true;
        },
        confirmBan() {
            if (this.userToBan && this.banReason) {
                this.userToBan.status = 'Banned';
                this.banModalOpen = false;
            }
        },
        viewReport(report) {
            this.selectedReport = report;
            this.viewModalOpen = true;
        },
        resolveReport() {
            if (this.selectedReport) {
                this.selectedReport.status = 'Resolved';
                this.viewModalOpen = false;
            }
        },
        openAddMovieModal() {
            this.editingMovie = false;
            this.newMovie = { title: '', genre: '', year: '', rating: '', description: '', trailer: '', img: '', comments: [] };
            this.movieModalOpen = true;
        },
        saveMovie() {
            this.movieModalOpen = false;
        },
        viewRoom(room) {
            this.selectedRoom = room;
            this.roomModalOpen = true;
        },
        disbandRoom(roomId) {
            this.roomModalOpen = false;
        },
        openModal(mode, item = null) {
            this.modalMode = mode;
            this.formData = item ? { ...item } : { name: '', price: 0, rarity: 'Common', image: '' };
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
        },
        deleteItem(id) {
            this.shopItems = this.shopItems.filter(i => i.id !== id);
        },
        saveItem() {
            this.modalOpen = false;
        },
        handleFileUpload(event, callback) {
            const file = event.target.files ? event.target.files[0] : null;
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (callback && typeof callback === 'function') {
                        callback(e.target.result);
                    } else if (this.formData) {
                        this.formData.image = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        },

        // Life cycle initialization
        initDashboard() {
            gsap.config({ nullTargetWarn: false });
            this.$watch('friends', () => {
                this.updateFriendsCount();
            });
            // Initial data fetch
            this.fetchFriends();
            this.searchUsers();

           // Debounced watcher: reset to default users when empty, search when >= 2 chars
            this.$watch('searchQuery', (query) => {
                clearTimeout(this.searchTimeout);
                const trimmed = (query || '').trim();

                // If input is completely erased, reload default users from PHP
                if (trimmed === '') {
                    this.searchUsers();
                    return;
                }

                // Don't trigger API call for a single character
                if (trimmed.length < 2) {
                    return;
                }

                // Debounce search requests for 2+ characters
                this.searchTimeout = setTimeout(() => {
                    this.searchUsers();
                }, 300);
            });

            // Auto-trigger initial search when invite modal opens
            this.$watch('showInviteModal', (isOpen) => {
                if (isOpen) {
                    this.searchUsers(this.searchQuery);
                }
            });
            
            // Escape key listener for navigation drawer
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isNavOpen) {
                    this.closeNav();
                }
            });
            
            // Watchers for Quests UI animations
            this.$watch('showQuestsPanel', value => {
                if(value) {
                    this.$nextTick(() => {
                        gsap.fromTo('.quest-item', 
                            { x: 100, opacity: 0, scale: 0.8, rotationY: 45 },
                            { x: 0, opacity: 1, scale: 1, rotationY: 0, duration: 0.8, stagger: 0.1, ease: 'elastic.out(1, 0.75)' }
                        );
                        gsap.fromTo('.quest-header',
                            { y: -50, opacity: 0 },
                            { y: 0, opacity: 1, duration: 0.6, ease: 'back.out(1.7)' }
                        );
                    });
                }
            });

            this.$watch('questActiveTab', value => {
                this.$nextTick(() => {
                    gsap.fromTo('.quest-item', 
                        { x: 50, opacity: 0, scale: 0.95 },
                        { x: 0, opacity: 1, scale: 1, duration: 0.5, stagger: 0.05, ease: 'power3.out' }
                    );
                });
            });

            // Animated Number Counters
            this.$nextTick(() => {
                const counters = this.$root.querySelectorAll('.stat-counter');
                counters.forEach(counter => {
                    const target = parseFloat(counter.getAttribute('data-target'));
                    const obj = { val: 0 };
                    
                    gsap.to(obj, {
                        val: target,
                        duration: 2.5,
                        ease: "power3.out",
                        delay: 0.8,
                        onUpdate: () => {
                            counter.innerText = Math.floor(obj.val);
                        }
                    });
                });
            });

            // Intro Animations
            const tl = gsap.timeline();
            tl.fromTo(".gs-header-item", 
                { y: -40, opacity: 0, scale: 0.95 }, 
                { y: 0, opacity: 1, scale: 1, stagger: 0.1, duration: 0.8, ease: "back.out(1.5)" }, 
                0.2
            )
            .fromTo(".stagger-item", 
                { opacity: 0, y: 80, rotationY: 15, scale: 0.9 }, 
                { opacity: 1, y: 0, rotationY: 0, scale: 1, stagger: 0.1, duration: 0.9, ease: "back.out(1.2)" }, 
                "-=0.6"
            );

            // Split text animation for welcome header
            const welcomeText = this.$root.querySelector('.welcome-text');
            if (welcomeText) {
                const text = welcomeText.innerText;
                welcomeText.innerHTML = '';
                [...text].forEach(char => {
                    const span = document.createElement('span');
                    span.innerText = char;
                    span.style.opacity = '0';
                    span.style.display = 'inline-block';
                    if (char === ' ') span.innerHTML = '&nbsp;';
                    welcomeText.appendChild(span);
                });
                
                gsap.fromTo(welcomeText.querySelectorAll('span'), 
                    { opacity: 0, y: 30, rotationX: 90 },
                    {
                        opacity: 1,
                        y: 0,
                        rotationX: 0,
                        stagger: 0.04,
                        duration: 0.7,
                        ease: "back.out(2)",
                        delay: 0.5
                    }
                );
            }

            // Continuous pulse micro-animation for activity feed items
            this.$nextTick(() => {
                gsap.to('.activity-item .dot-pulse', {
                    scale: 1.8,
                    opacity: 0,
                    repeat: -1,
                    duration: 1.5,
                    ease: "power2.out",
                    stagger: 0.3
                });
            });

            // Random glitch effect on dashboard stat numbers periodically
            setInterval(() => {
                const stats = this.$root.querySelectorAll('.stat-counter');
                if (stats.length > 0) {
                    const randomStat = stats[Math.floor(Math.random() * stats.length)];
                    gsap.to(randomStat, {
                        x: () => Math.random() * 8 - 4,
                        y: () => Math.random() * 8 - 4,
                        duration: 0.05,
                        yoyo: true,
                        repeat: 5,
                        onComplete: () => {
                            gsap.set(randomStat, {x:0, y:0});
                        }
                    });
                }
            }, 6000);
        }
    };
}


function watchParty() {
    return {
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
            try {
                console.log("WATCH PARTY INIT CALLED");
                if (typeof gsap === 'undefined') return;
                gsap.config({ nullTargetWarn: false });

                this.$nextTick(() => {
                    try {
                        const tl = gsap.timeline({ onComplete: () => console.log('GSAP TIMELINE COMPLETED!') });
                        
                        // 1. Stage and Video Container entrance
                        const stage = this.$el.querySelector('.gs-stage');
                        if (stage) {
                            tl.fromTo(stage, 
                                { opacity: 0, scale: 0.95 },
                                { opacity: 1, scale: 1, duration: 1, ease: 'power3.out' }
                            );
                        }
                        
                        const video = this.$el.querySelector('.video-container');
                        if (video) {
                            tl.fromTo(video, 
                                { opacity: 0, scale: 0.7, rotationX: 20, rotationY: 15, z: -500 },
                                { opacity: 1, scale: 1, rotationX: 0, rotationY: 0, z: 0, duration: 1.5, ease: 'elastic.out(1, 0.75)', transformPerspective: 1000 },
                                "-=0.5"
                            );
                        }

                        // 2. Participants Grid staggered entrance
                        const participants = this.$el.querySelectorAll('.participant-card');
                        if (participants.length > 0) {
                            tl.fromTo(participants, 
                                { opacity: 0, y: 50, scale: 0.8 },
                                { opacity: 1, y: 0, scale: 1, duration: 0.8, stagger: 0.15, ease: 'back.out(1.7)' },
                                "-=1.0"
                            );
                        }

                        // 3. Chat Panel sliding in with 3D flip
                        const chat = this.$el.querySelector('.gs-chat');
                        if (chat) {
                            tl.fromTo(chat, 
                                { opacity: 0, x: 100, rotationY: 45 },
                                { opacity: 1, x: 0, rotationY: 0, duration: 1.2, ease: 'power4.out', transformPerspective: 1000 },
                                "-=1.2"
                            );
                        }

                        // 4. Bottom Controls rising up
                        const controls = this.$el.querySelector('.gs-controls');
                        if (controls) {
                            tl.fromTo(controls, 
                                { opacity: 0, y: 50 },
                                { opacity: 1, y: 0, duration: 1, ease: 'power3.out' },
                                "-=1"
                            );
                        }

                        if (video) {
                            // Ambient breathing effect for the video container
                            gsap.to(video, {
                                boxShadow: '0 30px 60px rgba(239,68,68,0.3)',
                                duration: 2,
                                repeat: -1,
                                yoyo: true,
                                ease: 'sine.inOut'
                            });
                        }

                        this.scrollToBottom();
                    } catch (e) {
                        console.error("GSAP timeline error in watchParty:", e);
                        // Fallback to visible
                        const elements = this.$el.querySelectorAll('.gs-stage, .video-container, .gs-chat, .gs-controls, .participant-card');
                        elements.forEach(el => { el.style.opacity = '1'; el.style.transform = 'none'; });
                    }
                });

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
            } catch(e) { console.error("Init Error", e); }
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
                        gsap.fromTo(this.$el.querySelector('.video-container'), 
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
                gsap.to(this.$el.querySelector('.video-container'), { scale: 0.98, duration: 0.3, ease: 'power2.out' });
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
        toggleMic(event) {
            this.isMuted = !this.isMuted;
            this.participants[0].muted = this.isMuted;
            
            // Animation for toggle
            if (event && event.currentTarget) {
                const btn = event.currentTarget;
                gsap.fromTo(btn, { scale: 0.8 }, { scale: 1, duration: 0.4, ease: 'back.out(2)' });
            }
        },
        toggleVideo(event) {
            this.isVideoOn = !this.isVideoOn;
            
            if (event && event.currentTarget) {
                const btn = event.currentTarget;
                gsap.fromTo(btn, { scale: 0.8 }, { scale: 1, duration: 0.4, ease: 'back.out(2)' });
            }
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
                const msgs = this.$el.querySelectorAll('.chat-msg-item');
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
            const container = this.$el.querySelector('#chat-container');
            if (container) {
                gsap.to(container, {
                    scrollTop: container.scrollHeight,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        }
    }}


// Ensure URL parameters are checked globally
setTimeout(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const phpError = urlParams.get('error');
        const phpSuccess = urlParams.get('success');
        if (phpError && typeof window.showToast === 'function') {
            window.showToast(decodeURIComponent(phpError), 'error');
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        } else if (phpSuccess && typeof window.showToast === 'function') {
            window.showToast(decodeURIComponent(phpSuccess), 'success');
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        } else if (phpSuccess && typeof window.showErrorModal === 'function') {
            // For now, reuse error modal to show success, or if there's a success modal, use it.
            // Let's just use showErrorModal but maybe it's styled as an error. 
            // The original logic didn't seem to have a success modal. Let's just use it.
            window.showErrorModal([decodeURIComponent(phpSuccess)]);
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }, 100);


window.initAnimations = function(container = document) {
    const termsCheck = container.querySelector('#terms');
    const checkmark = container.querySelector('.success-checkmark');
    if (termsCheck && checkmark) {
        termsCheck.addEventListener('change', (e) => {
            if (e.target.checked) {
                if (typeof gsap !== 'undefined') {
                    gsap.fromTo(checkmark, 
                        { strokeDashoffset: 50 },
                        { strokeDashoffset: 0, duration: 0.6, ease: "power2.out" }
                    );
                }
            }
        });
    }

    // Generate Posters
    const posterWall = container.querySelector('#poster-wall-container');
    if (posterWall) {
        let html = '';
        for (let i = 0; i < 20; i++) {
            const dir = i % 2 === 0 ? 'up' : 'down';
            const duration = 50 + (Math.random() * 30);
            let posters = '';
            for (let j = 0; j < 30; j++) {
                posters += `
                    <div class="poster">
                        <span class="material-symbols-outlined text-white/20 text-6xl">movie</span>
                    </div>
                `;
            }
            html += `
                <div class="poster-col ${dir}" style="animation-duration: ${duration}s;">
                    ${posters}
                </div>
            `;
        }
        posterWall.innerHTML = html;
    }

    // Particle Generation
    const particlesContainer = container.querySelector('#particles-container');
    if (particlesContainer) {
        const numParticles = 40;
        
        for (let i = 0; i < numParticles; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // Randomize properties
            const size = Math.random() * 3 + 1;
            const opacity = Math.random() * 0.5 + 0.1;
            const x = Math.random() * 100;
            const y = Math.random() * 100;
            
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.opacity = opacity;
            particle.style.left = `${x}%`;
            particle.style.top = `${y}%`;
            
            // Randomize animation
            const animDuration = Math.random() * 20 + 10;
            const animDelay = Math.random() * 5;
            const yOffset = (Math.random() * 100) - 50;
            
            // Set custom properties for the animation
            particle.style.setProperty('--y-end', `${yOffset}vh`);
            
            // Add inline animation since CSS keyframes might be complex to inject dynamically here
            // Just use GSAP if it's available
            if (typeof gsap !== 'undefined') {
                gsap.to(particle, {
                    y: `${yOffset}vh`,
                    x: `${(Math.random() * 50) - 25}vw`,
                    opacity: 0,
                    duration: animDuration,
                    delay: animDelay,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            }
            
            particlesContainer.appendChild(particle);
        }
    }

    if (typeof gsap !== 'undefined') {
        const tl = gsap.timeline();
        
        // Initial Sequence
        tl.to(container.querySelectorAll('.ultimate-reveal'), 
            { opacity: 1, duration: 0.1 }
        )
        .fromTo(container.querySelectorAll('#logo-box'), 
            { scale: 0, rotation: -45, opacity: 0 },
            { scale: 1, rotation: 0, opacity: 1, duration: 0.8, ease: 'back.out(1.5)' }
        )
        .fromTo(container.querySelectorAll('#branding h1'), 
            { x: -30, opacity: 0 },
            { x: 0, opacity: 1, duration: 0.6, ease: 'power2.out' },
            "-=0.4"
        )
        .fromTo(container.querySelectorAll('#branding p'), 
             { y: 20, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.5, ease: 'power2.out' },
            "-=0.4"
        )
        .fromTo(container.querySelectorAll('#otp-inputs input'),
            { y: 20, opacity: 0, scale: 0.5 },
            { y: 0, opacity: 1, scale: 1, duration: 0.8, stagger: 0.05, ease: "back.out(1.5)" },
            "-=0.4"
        )
        .fromTo(container.querySelectorAll('.gs-stagger'), 
            { opacity: 0, y: 30 },
            { opacity: 1, y: 0, duration: 0.6, stagger: 0.08, ease: 'back.out(1.2)' },
            "-=0.8"
        )
        .fromTo(container.querySelectorAll('.gs-footer'), 
            { opacity: 0, y: 10 },
            { opacity: 1, y: 0, duration: 0.5 },
            "-=0.4"
        );
    }

    // Smooth Floating & Parallax Effect
    const card = container.querySelector('#glass-card');
    const mainContainer = container.querySelector('#main-container');
    
    if (mainContainer && card) {
        mainContainer.addEventListener('mouseenter', () => {
            gsap.to(card, {
                y: -10,
                scale: 1.02,
                boxShadow: '0 40px 80px -12px rgba(220, 38, 38, 0.2), inset 0 1px 0 rgba(255,255,255,0.2)',
                borderColor: 'rgba(255, 255, 255, 0.2)',
                duration: 0.6,
                ease: 'power3.out'
            });
        });
        
        mainContainer.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Subtle content shift (parallax)
            gsap.to(container.querySelectorAll('.glass-card > *'), {
                x: (x - rect.width / 2) * 0.03,
                y: (y - rect.height / 2) * 0.03,
                duration: 0.5,
                ease: 'power2.out'
            });
        });
        
        mainContainer.addEventListener('mouseleave', () => {
            gsap.to(card, {
                y: 0,
                scale: 1,
                boxShadow: '0 30px 60px -12px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255,255,255,0.1)',
                borderColor: 'rgba(255, 255, 255, 0.08)',
                duration: 0.8,
                ease: 'elastic.out(1, 0.5)'
            });
            
            gsap.to(container.querySelectorAll('.glass-card > *'), {
                x: 0,
                y: 0,
                duration: 0.8,
                ease: 'elastic.out(1, 0.5)'
            });
        });
    }

    // Input field focus animations
    const inputFields = container.querySelectorAll('.input-field');
    inputFields.forEach(input => {
        const label = input.nextElementSibling;
        const icon = input.nextElementSibling ? input.nextElementSibling.nextElementSibling : null;
        
        input.addEventListener('focus', () => {
            gsap.to(input, {
                borderColor: 'rgba(239, 68, 68, 0.8)',
                boxShadow: '0 0 25px rgba(239, 68, 68, 0.2)',
                duration: 0.3,
                ease: 'power2.out'
            });
            if(icon) {
                gsap.to(icon, {
                    scale: 1.2,
                    color: 'rgba(239, 68, 68, 1)',
                    duration: 0.3,
                    ease: 'back.out(2)'
                });
            }
        });
        
        input.addEventListener('blur', () => {
            gsap.to(input, {
                borderColor: 'rgba(255, 255, 255, 0.1)',
                boxShadow: 'none',
                duration: 0.3,
                ease: 'power2.out'
            });
            if(icon) {
                gsap.to(icon, {
                    scale: 1,
                    color: 'rgba(255, 255, 255, 0.3)',
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        });
    });

    // Social button hover animations
    const socialBtns = container.querySelectorAll('.grid.grid-cols-2 button');
    socialBtns.forEach(btn => {
        const icon = btn.querySelector('svg');
        btn.addEventListener('mouseenter', () => {
            gsap.to(btn, {
                y: -3,
                scale: 1.02,
                duration: 0.3,
                ease: 'power2.out'
            });
            if(icon) {
                gsap.to(icon, {
                    rotation: 15,
                    scale: 1.2,
                    duration: 0.3,
                    ease: 'back.out(2)'
                });
            }
        });
        
        btn.addEventListener('mouseleave', () => {
            gsap.to(btn, {
                y: 0,
                scale: 1,
                duration: 0.3,
                ease: 'power2.out'
            });
            if (icon) {
                gsap.to(icon, {
                    rotation: 0,
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        });
    });

    // Button hover effect
    const submitBtn = container.querySelector('#submitBtn');
    const ripple = container.querySelector('#btnRipple');
    const btnIcon = submitBtn ? submitBtn.querySelector('span.material-symbols-outlined') : null;
    
    // Check if it's the register page to avoid some button conflicts? 
    // Just wrap in try/catch or if
    if (submitBtn) {
        submitBtn.addEventListener('mouseenter', (e) => {
            if (ripple) gsap.to(ripple, { scale: 1.5, opacity: 1, duration: 0.4, ease: 'power2.out' });
            if (btnIcon) gsap.to(btnIcon, { x: 5, duration: 0.3, ease: 'back.out(2)' });
        });
        
        submitBtn.addEventListener('mouseleave', () => {
            if (ripple) gsap.to(ripple, { scale: 0, opacity: 0, duration: 0.4 });
            if (btnIcon) gsap.to(btnIcon, { x: 0, duration: 0.3, ease: 'power2.out' });
        });
        
        // Button click animation
        submitBtn.addEventListener('mousedown', () => {
            gsap.to(submitBtn, { scale: 0.95, duration: 0.1, ease: 'power2.inOut' });
        });
        
        submitBtn.addEventListener('mouseup', () => {
            gsap.to(submitBtn, { scale: 1, duration: 0.4, ease: 'elastic.out(1, 0.3)' });
        });
    }

    // Next-Level Magnetic Back Button
    const backBtn = container.querySelector('.gs-back-btn');
    const backHit = container.querySelector('.gs-back-hit');
    const backRing = container.querySelector('.gs-back-ring');
    const backIcon = container.querySelector('.gs-back-icon');
    
    if (backBtn && backHit) {
        // Initial entrance
        gsap.fromTo(backBtn, 
             { x: -50, opacity: 0, scale: 0 }, 
             { x: 0, opacity: 1, scale: 1, duration: 1.5, ease: "elastic.out(1, 0.4)", delay: 0.3 }
        );

        let hoverTween = gsap.to(backRing, { rotation: 360, duration: 4, repeat: -1, ease: "linear", paused: true });

        backHit.addEventListener("mousemove", (e) => {
            const rect = backHit.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            // Move the button itself
            gsap.to(backBtn, {
                x: x * 0.4,
                y: y * 0.4,
                scale: 1.1,
                duration: 0.4,
                ease: "power3.out",
                boxShadow: "0 10px 30px rgba(239, 68, 68, 0.3)",
                borderColor: "rgba(239, 68, 68, 0.5)"
            });
            
            // Move icon slightly more for parallax
            if (backIcon) {
                gsap.to(backIcon, {
                    x: x * 0.3,
                    y: y * 0.3,
                    color: "#fff",
                    duration: 0.3,
                    ease: "power2.out"
                });
            }
            
            if (backRing) {
                gsap.to(backRing, { opacity: 1, duration: 0.3 });
                hoverTween.play();
            }
        });

        backHit.addEventListener("mouseleave", () => {
            gsap.to(backBtn, {
                x: 0,
                y: 0,
                scale: 1,
                duration: 0.8,
                ease: "elastic.out(1, 0.4)",
                boxShadow: "0 0 0 transparent",
                borderColor: "rgba(255, 255, 255, 0.1)"
            });
            
            if (backIcon) {
                gsap.to(backIcon, {
                    x: 0,
                    y: 0,
                    color: "rgba(255, 255, 255, 0.6)",
                    duration: 0.8,
                    ease: "elastic.out(1, 0.4)"
                });
            }
            
            if (backRing) {
                gsap.to(backRing, { opacity: 0, duration: 0.5, onComplete: () => hoverTween.pause() });
            }
        });
        
        backBtn.addEventListener("mousedown", () => {
            gsap.to(backBtn, { scale: 0.9, duration: 0.15, ease: "power2.inOut" });
            if (backIcon) gsap.to(backIcon, { scale: 0.8, duration: 0.15 });
        });

        backBtn.addEventListener("mouseup", () => {
            gsap.to(backBtn, { scale: 1.1, duration: 0.4, ease: "elastic.out(1, 0.4)" });
            if (backIcon) gsap.to(backIcon, { scale: 1, duration: 0.4 });
        });
    }
}

window.showErrorModal = function(errors) {
    const modal = document.getElementById('errorModal');
    const card = document.getElementById('errorModalCard');
    const list = document.getElementById('errorList');
    if (!modal) return;
    list.innerHTML = errors.map(err => `
        <li class="flex items-start gap-2">
            <span class="material-symbols-outlined text-[16px] text-red-400 mt-0.5">error</span>
            <span>${err}</span>
        </li>
    `).join('');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95');
    }, 10);
};

window.closeErrorModal = function() {
    const modal = document.getElementById('errorModal');
    const card = document.getElementById('errorModalCard');
    if (!modal) return;
    modal.classList.add('opacity-0');
    card.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
};

window.closeErrorModalOnBackdrop = function(event) {
    if (event.target.id === 'errorModal') {
        window.closeErrorModal();
    }
};

window.validateLoginForm = function() {
    let errors = [];
    let email = document.getElementById('email').value.trim();
    let password = document.getElementById('password').value;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.length === 0) {
        errors.push('Email address is required.');
    } else if (!emailPattern.test(email)) {
        errors.push('Please enter a valid email address.');
    }
    if (password.length === 0) {
        errors.push('Password is required.');
    }
    if (errors.length > 0) {
        window.showErrorModal(errors);
        return false;
    }
    if (typeof window.showPageLoader === 'function') window.showPageLoader();
    return true;
};

window.validateRegistrationForm = function() {
    let errors = [];
    let name = document.getElementById('name').value;
    let email = document.getElementById('email').value;
    let password = document.getElementById('password').value;
    let terms = document.getElementById('terms').checked;
    const namePattern = /^[a-zA-Z\s]+$/;
    if (name.trim().length === 0) {
        errors.push('Full Name is required.');
    } else if (!namePattern.test(name)) {
        errors.push('Full Name can only contain letters and spaces.');
    }
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/;
    if (email.trim().length === 0) {
        errors.push('Email address is required.');
    } else if (!emailPattern.test(email)) {
        errors.push('Email address format is invalid.');
    }
    let password_criteria = [];
    if (password.length === 0) {
        errors.push('Password is required.');
    } else {
        if (password.length < 8) password_criteria.push("8+ characters");
        if (!/[A-Z]/.test(password)) password_criteria.push("1 uppercase letter");
        if (!/[a-z]/.test(password)) password_criteria.push("1 lowercase letter");
        if (!/[0-9]/.test(password)) password_criteria.push("1 number");
        if (!/[!@#$%^&*(),.?\":{}|]/.test(password)) password_criteria.push("1 special character");
        if (password_criteria.length > 0) {
            errors.push('Password missing: ' + password_criteria.join(', '));
        }
    }
    if (!terms) {
        errors.push('You must accept the Terms of Service.');
    }
    if (errors.length > 0) {
        window.showErrorModal(errors);
        return false;
    }
    if (typeof window.showPageLoader === 'function') window.showPageLoader();
    return true;
};

window.validateProfileForm = function(profileObj = null) {
    let errors = [];

    // Fallback between Alpine reactive object or DOM element IDs
    let name = profileObj ? (profileObj.user_name || '').trim() : (document.getElementById('profile_name') ? document.getElementById('profile_name').value.trim() : '');
    let email = profileObj ? (profileObj.email || '').trim() : (document.getElementById('profile_email') ? document.getElementById('profile_email').value.trim() : '');
    let currentPassword = profileObj ? (profileObj.current_password || '') : (document.getElementById('current_password') ? document.getElementById('current_password').value : '');
    let newPassword = profileObj ? (profileObj.new_password || '') : (document.getElementById('new_password') ? document.getElementById('new_password').value : '');
    let confirmPassword = profileObj ? (profileObj.confirm_password || '') : (document.getElementById('confirm_password') ? document.getElementById('confirm_password').value : '');

    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/;

    // 1. Full Name Check
    if (name.length === 0) {
        errors.push('Full name is required.');
    } else if (name.length < 2) {
        errors.push('Full name must be at least 2 characters long.');
    }

    // 2. Email Address Check
    if (email.length === 0) {
        errors.push('Email address is required.');
    } else if (!emailPattern.test(email)) {
        errors.push('Please enter a valid email address.');
    }

    // 3. Password Validation
    if (newPassword.length > 0) {
        if (currentPassword.length === 0) {
            errors.push('Current password is required to set a new password.');
        }

        if (newPassword !== confirmPassword) {
            errors.push('New password and confirm password do not match.');
        }

        let password_criteria = [];
        if (newPassword.length < 8) password_criteria.push("8+ characters");
        if (!/[A-Z]/.test(newPassword)) password_criteria.push("1 uppercase letter");
        if (!/[a-z]/.test(newPassword)) password_criteria.push("1 lowercase letter");
        if (!/[0-9]/.test(newPassword)) password_criteria.push("1 number");
        if (!/[!@#$%^&*(),.?":{}|]/.test(newPassword)) password_criteria.push("1 special character");

        if (password_criteria.length > 0) {
            errors.push('New password missing: ' + password_criteria.join(', '));
        }
    }

    return errors; 
};

window.validateForgotPasswordForm = function() {
    let errors = [];
    let email = document.getElementById('email').value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.length === 0) {
        errors.push('Email address is required.');
    } else if (!emailPattern.test(email)) {
        errors.push('Please enter a valid email address.');
    }
    if (errors.length > 0) {
        window.showErrorModal(errors);
        return false;
    }
    if (typeof window.showPageLoader === 'function') window.showPageLoader();
    return true;
};

window.validateOtpForm = function() {
    let errors = [];
    let codeInputs = document.querySelectorAll('.otp-input');
    let fullCode = Array.from(codeInputs).map(i => i.value).join('');
    
    if (fullCode.length !== 6) {
        errors.push('Please enter the full 6-digit verification code.');
    }
    if (errors.length > 0) {
        window.showErrorModal(errors);
        return false;
    }
    if (typeof window.showPageLoader === 'function') window.showPageLoader();
    return true;
};

window.handleOTPNavigation = function(event) {
    if (event) event.preventDefault();
    if (window.validateRegistrationForm()) {
        document.getElementById('registerForm').submit();
    }
};

window.moveToNext = function(current, event) {
    const inputs = document.querySelectorAll('.otp-input');
    const index = Array.from(inputs).indexOf(current);
    if (current.value.length === 1 && index < inputs.length - 1) {
        inputs[index + 1].focus();
    }
    if (event.key === 'Backspace' && index > 0 && current.value === '') {
        inputs[index - 1].focus();
    }
    document.getElementById('full_otp').value = Array.from(inputs).map(i => i.value).join('');
};



window.otpForm = function() {
    return {
        otpCode: '',
        inputs: [],
        init() {
            this.$nextTick(() => {
                this.inputs = Array.from(this.$el.querySelectorAll('#otp-inputs input'));
                if(this.inputs.length > 0) this.inputs[0].focus();
            });
        },
        updateHiddenInput() {
            this.otpCode = this.inputs.map(input => input.value).join('');
        },
        handleInput(e, index) {
            const val = e.target.value;
            if (/[^0-9]/.test(val)) {
                e.target.value = val.replace(/[^0-9]/g, '');
                return;
            }
            if (val) {
                if (typeof gsap !== 'undefined') {
                    gsap.fromTo(e.target, { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(2)" });
                }
                if (index < 5) {
                    this.inputs[index + 1].focus();
                }
            }
            this.updateHiddenInput();
        },
        handleKeyDown(e, index) {
            if (e.key === 'Backspace') {
                if (!e.target.value && index > 0) {
                    this.inputs[index - 1].focus();
                    this.inputs[index - 1].value = '';
                }
                this.updateHiddenInput();
            } else if (e.key === 'ArrowLeft' && index > 0) {
                this.inputs[index - 1].focus();
            } else if (e.key === 'ArrowRight' && index < 5) {
                this.inputs[index + 1].focus();
            }
        },
        handlePaste(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            if (!pastedData) return;
            pastedData.split('').forEach((char, i) => {
                if (i < 6) {
                    this.inputs[i].value = char;
                    if (typeof gsap !== 'undefined') {
                        gsap.fromTo(this.inputs[i], { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(2)", delay: i * 0.05 });
                    }
                }
            });
            const focusIndex = Math.min(pastedData.length, 5);
            if(this.inputs[focusIndex]) this.inputs[focusIndex].focus();
            this.updateHiddenInput();
        }
    }
};

window.handleLogout = async function() {
    try {
        const response = await fetch('/backend/logout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            if (typeof barba !== 'undefined') {
                barba.go(data.redirect);
            } else {
                window.location.href = data.redirect;
            }
        } else {
            console.error('Logout failed');
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
        }
    } catch (err) {
        console.error('Logout error:', err);
        if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
    }
};

function adminDashboard(userData = {}) {
    return {
        init() {
            // Initial GSAP animations are now handled globally in admin_animations.js via Barba hooks
        },

        currentTab: 'dashboard',
        isNavOpen: false,
        notificationsOpen: false,
        unreadNotifications: 3,
        navItems: [
            { id: 'dashboard', label: 'Overview', icon: 'dashboard' },
            { id: 'users', label: 'Users', icon: 'group' },
            { id: 'movies', label: 'Movies', icon: 'movie' },
            { id: 'sessions', label: 'Watch Parties', icon: 'live_tv' },
            { id: 'shop', label: 'Avatar Shop', icon: 'storefront' },
            { id: 'reports', label: 'Reports', icon: 'flag' },
            { id: 'profile', label: 'Profile', icon: 'person' }
        ],
        notifications: [
            { id: 1, text: 'New user registered', time: '5m ago' },
            { id: 2, text: 'Server CPU high', time: '1h ago' }
        ],
        stats: [
            { label: 'Total Users', value: '12,450', change: '+12%', icon: 'group' },
            { label: 'Active Sessions', value: '342', change: '+5%', icon: 'live_tv' },
            { label: 'Revenue', value: '$45,231', change: '+23%', icon: 'payments' },
            { label: 'Server Load', value: '42%', change: '-2%', icon: 'memory' }
        ],

       // Add Loading & Error states
        isLoading: false,
        errorMessage: '',

        // Users
        searchQuery: '',
        roleFilter: 'All',
        banModalOpen: false,
        userToBan: null,
        banReason: '',
        banNotes: '',
        
        // CHANGED: Renamed from usersList to users to match filteredUsers getter
        users: [],

        get filteredUsers() { 
            return (this.users || []).filter(u => 
                (this.roleFilter === 'All' || u.role === this.roleFilter) && 
                (u.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                 u.email.toLowerCase().includes(this.searchQuery.toLowerCase()))
            ); 
        },

        // ADDED: Fetch users from the PHP backend API
        async fetchUsers() {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                // Assuming standard routing to match movies_api.php
                const response = await fetch('/backend/users_api.php');
                const text = await response.text();
                
                try {
                    const data = JSON.parse(text);
                    if (response.ok) {
                        this.users = data;
                    } else {
                        this.errorMessage = data.error || 'Failed to load user directory.';
                    }
                } catch(e) {
                    this.errorMessage = 'Invalid JSON response from server.';
                    console.error('Raw response:', text);
                }
            } catch (err) {
                this.errorMessage = 'Network error fetching users.';
                console.error(err);
            } finally {
                this.isLoading = false;
            }
        },

        // Movies
        movies: [],
        async fetchMovies() { 
            try { 
                const response = await fetch("/backend/movies_api.php"); 
                const text = await response.text(); 
                try { 
                    const data = JSON.parse(text); 
                    if (response.ok) { 
                        this.movies = data; 
                    } 
                } catch(e) {} 
            } catch(e) {} 
        }, 
        init() { 
            this.fetchMovies(); 
        },
        availableGenres: [], // Holds list from genres table
        movieModalOpen: false,
        editingMovie: false,
        movieTab: 'details',
        newMovie: {
            id: null,
            title: '',
            description: '',
            img: '',       // Maps to `poster`
            trailer: '',   // Maps to `video_url`
            duration: '',  // Maps to `duration` in minutes
            genre_ids: []  // Array of genre_id integers
        },
        async fetchMovies() {
            try {
                const response = await fetch('/backend/movies_api.php');
                const text = await response.text(); // Read as raw text first
                
                try {
                    const data = JSON.parse(text); // Try parsing as JSON
                    if (response.ok) {
                        this.movies = data;
                    } else {
                        console.error('Movies API Error:', data.error);
                    }
                } catch (jsonErr) {
                    console.error('PHP returned raw non-JSON text:', text);
                }
            } catch (err) {
                console.error('Network error fetching movies:', err);
            }
        },

        async fetchGenres() {
            try {
                const response = await fetch('/backend/genres_api.php');
                const text = await response.text(); // Read as raw text first
                
                try {
                    const data = JSON.parse(text); // Try parsing as JSON
                    if (response.ok) {
                        this.availableGenres = data;
                    } else {
                        console.error('Genres API Error:', data.error);
                    }
                } catch (jsonErr) {
                    console.error('PHP returned raw non-JSON text:', text);
                }
            } catch (err) {
                console.error('Network error fetching genres:', err);
            }
        },

        // Sessions
        roomModalOpen: false,
        selectedRoom: null,
        rooms: [
            { id: 1, name: 'Sci-Fi Night', host: 'Alice', users: 5 },
            { id: 2, name: 'Horror Marathon', host: 'Bob', users: 12 },
            { id: 3, name: 'Anime Watch Party', host: 'Charlie', users: 8 },
            { id: 4, name: 'Classic Movies', host: 'Diana', users: 3 },
            { id: 5, name: 'Comedy Hour', host: 'Eve', users: 15 }
        ],
        mockRoomUsers: [{ id: 1, name: 'Alice', isHost: true, avatar: '' }, { id: 2, name: 'Charlie', isHost: false, avatar: '' } ], mockRoomUsers2: [
            { name: 'Alice', isHost: true },
            { name: 'Charlie', isHost: false }
        ],

        // Reports
        viewModalOpen: false,
        selectedReport: null,
        reportStats: { total: 10, pending: 2, read: 8 },
        reportsList: [
            { id: 101, user: 'Bob', type: 'Bug', status: 'Pending', excerpt: 'Video player stuttering', date: '2023-10-01' }
        ],

        // Shop       
        modalOpen: false,
        modalMode: 'add',
        formData: { name: '', price: 0, rarity: 'Common', image: '' },
        shopItems: [
            { id: 1, name: 'Gold Border', price: 500, rarity: 'Legendary' }
        ],
        selectedAvatar: null,
        selectedBorder: null,
        avatarModalOpen: false,
        borders: [],

        // Profile
        currentTab: 'dashboard',
        avatarModalOpen: false,
        selectedBorder: null,
        deleteAccountModalOpen: false,
        deleteAccountPassword: '',
        deleteAccountError: '',
        
        // Generates dynamic avatar based on user's name
        selectedAvatar: `https://ui-avatars.com/api/?name=${encodeURIComponent(userData.user_name || 'Admin')}&background=ef4444&color=fff&bold=true`,

        notification: {
            show: false,
            type: 'error',
            message: ''
        },

        showNotification(message, type = 'error') {
            this.notification.message = message;
            this.notification.type = type;
            this.notification.show = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // 1. DISPLAY STATE (Used by UI headers, sidebars, and avatar badges)
        displayName: userData.user_name || 'Admin',
        displayEmail: userData.email || '',

        // 2. FORM STATE (Bound to form input fields via x-model)
        profile: {
            user_name: userData.user_name || '',
            email: userData.email || '',
            current_password: '',
            new_password: '',
            confirm_password: ''
        },

        async saveProfile() {
            this.notification.show = false;

            // Run validation
            const errors = typeof window.validateProfileForm === 'function' 
                ? window.validateProfileForm(this.profile) 
                : [];

            if (errors.length > 0) {
                this.showNotification(errors, 'error');
                return;
            }

            try {
                const response = await fetch('/backend/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.profile)
                });
                const data = await response.json();

                if (data.success) {
                    // COMMIT FORM STATE TO DISPLAY STATE ONLY ON SUCCESS
                    this.displayName = this.profile.user_name;
                    this.displayEmail = this.profile.email;

                    // Clear password fields
                    this.profile.current_password = '';
                    this.profile.new_password = '';
                    this.profile.confirm_password = '';

                    this.showNotification('Profile updated successfully!', 'success');
                } else {
                    this.showNotification(data.error || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                this.showNotification('Network error updating profile.', 'error');
            }
        },
        async confirmDeleteAccount() {
            // Reset previous errors
            this.deleteAccountError = '';
            this.notification.show = false;

            // 1. Client-side check: No password entered
            if (!this.deleteAccountPassword.trim()) {
                const msg = 'Please enter your password to confirm account deletion.';
                this.deleteAccountError = msg;
                this.showNotification(msg, 'error');
                return;
            }

            try {
                const response = await fetch('/backend/delete_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: this.deleteAccountPassword })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/login.php?account_deleted=1';
                } else {
                    // 2. Server-side check: Incorrect password / session error
                    const errorMsg = data.message || data.error || 'Failed to delete account.';
                    this.deleteAccountError = errorMsg; // Displays inside modal
                    this.showNotification(errorMsg, 'error'); // Displays in top page banner
                }
            } catch (err) {
                const networkError = 'Network error processing account deletion.';
                this.deleteAccountError = networkError;
                this.showNotification(networkError, 'error');
            }
        },
    
        switchTab(tabId) {
            if (this.currentTab === tabId) return;
            const oldTab = this.currentTab;
            this.currentTab = tabId;
            const oldPanel = document.querySelector(`[data-tab-panel="${oldTab}"]`);
            const newPanel = document.querySelector(`[data-tab-panel="${tabId}"]`);
            
            if (oldPanel && newPanel && typeof window.gsap !== 'undefined') {
                // Outro animation for old panel
                window.gsap.to(oldPanel, {
                    opacity: 0,
                    y: -30,
                    scale: 0.95,
                    filter: "blur(10px)",
                    duration: 0.4,
                    ease: "power3.in",
                    onComplete: () => {
                        oldPanel.style.display = 'none';
                        newPanel.style.display = 'block';
                        
                        // Set initial state for new panel to avoid flicker
                        window.gsap.set(newPanel, { opacity: 0, y: 50, scale: 0.95, rotationX: 15, filter: "blur(15px)", transformPerspective: 1000 });
                        
                        // Intro animation for new panel
                        window.gsap.to(newPanel, {
                            opacity: 1, 
                            y: 0, 
                            scale: 1, 
                            rotationX: 0, 
                            filter: "blur(0px)", 
                            duration: 0.8, 
                            ease: "expo.out"
                        });
                        
                        // Re-trigger internal staggered items (like charts, stats, tables, forms, etc)
                        const staggers = newPanel.querySelectorAll('.gs-stat-card, .gs-table-row, .stagger-item, tbody tr, .card, .glass-card, .movie-card-container');
                        if (staggers.length > 0) {
                            window.gsap.fromTo(staggers,
                                { opacity: 0, y: 40, scale: 0.9, rotationX: -15, transformPerspective: 1000 },
                                { opacity: 1, y: 0, scale: 1, rotationX: 0, duration: 0.8, stagger: 0.05, ease: "back.out(1.5)", delay: 0.1 }
                            );
                        }
                        
                        // Re-trigger chart bars specifically
                        const chartBars = newPanel.querySelectorAll('.chart-bar');
                        if (chartBars.length > 0) {
                            window.gsap.fromTo(chartBars,
                                { scaleY: 0, transformOrigin: 'bottom' },
                                { scaleY: 1, duration: 1, stagger: 0.05, ease: 'power3.out', delay: 0.4 }
                            );
                        }
                    }
                });
            } else if (oldPanel && newPanel) {
                oldPanel.style.display = 'none';
                newPanel.style.display = 'block';
            }
        },
        // Toggle genre string or object ID in newMovie.genres
        toggleGenre(genre) {
            if (!Array.isArray(this.newMovie.genres)) {
                this.newMovie.genres = [];
            }
            
            const val = typeof genre === 'object' ? (genre.name || genre.id) : genre;
            const index = this.newMovie.genres.indexOf(val);
            
            if (index > -1) {
                this.newMovie.genres.splice(index, 1);
            } else {
                this.newMovie.genres.push(val);
            }
            
            // Automatically update single string field for list card display
            this.newMovie.genre = this.newMovie.genres.join(', ');
        },
        // Helper to check selection status
        isGenreSelected(genre) {
            if (!this.newMovie || !Array.isArray(this.newMovie.genres)) return false;
            const val = typeof genre === 'object' ? (genre.name || genre.id) : genre;
            return this.newMovie.genres.includes(val);
        },
              
        openEditMovieModal(movie) {
            this.editingMovie = true;
            this.movieTab = 'details';
            this.newMovie = JSON.parse(JSON.stringify(movie));
            
            // Convert string like "Action, Sci-Fi" into array ['Action', 'Sci-Fi'] if needed
            if (!Array.isArray(this.newMovie.genres)) {
                if (typeof this.newMovie.genre === 'string' && this.newMovie.genre.trim() !== '') {
                    this.newMovie.genres = this.newMovie.genre.split(',').map(g => g.trim());
                } else {
                    this.newMovie.genres = [];
                }
            }
            this.movieModalOpen = true;
        },
        openBanModal(user) {
            this.userToBan = user;
            this.banReason = '';
            this.banModalOpen = true;
        },
        confirmBan() {
            if (this.userToBan && this.banReason) {
                this.userToBan.status = 'Banned';
                this.banModalOpen = false;
            }
        },
        viewReport(report) {
            this.selectedReport = report;
            this.viewModalOpen = true;
        },
        resolveReport() {
            if (this.selectedReport) {
                this.selectedReport.status = 'Resolved';
                this.viewModalOpen = false;
            }
        },
        // Open modal for adding
        openAddMovieModal() {
            this.editingMovie = false;
            this.movieTab = 'details';
            this.newMovie = {
                title: '',
                year: '',
                rating: 0,
                genres: [],
                genre: '',
                description: '',
                video_url: '',
                img: ''
            };
            this.movieModalOpen = true;
        },
        // Preferred FormData approach
        async saveMovie() {
            const formData = new FormData();
            formData.append('title', this.newMovie.title);
            formData.append('duration', this.newMovie.duration);
            formData.append('genre_ids', JSON.stringify(this.newMovie.genre_ids));
            if (this.$refs.moviePosterInput.files[0]) {
                formData.append('poster', this.$refs.moviePosterInput.files[0]);
            }
            
            await fetch('/backend/movies_api.php', { method: 'POST', body: formData });
        },

        viewRoom(room) {
            this.selectedRoom = room;
            this.roomModalOpen = true;
        },
        disbandRoom(roomId) {
            this.roomModalOpen = false;
        },
        openModal(mode, item = null) {
            this.modalMode = mode;
            this.formData = item ? { ...item } : { name: '', price: 0, rarity: 'Common', image: '' };
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
        },
        deleteItem(id) {
            this.shopItems = this.shopItems.filter(i => i.id !== id);
        },
        saveItem() {
            this.modalOpen = false;
        },
        handleFileUpload(event, callback) {
            const file = event.target.files ? event.target.files[0] : null;
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (callback && typeof callback === 'function') {
                        callback(e.target.result);
                    } else if (this.formData) {
                        this.formData.image = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        },
        networkTraffic: [
            { day: 'Mon', reqs: 1250, height: 40 },
            { day: 'Tue', reqs: 3400, height: 75 },
            { day: 'Wed', reqs: 2100, height: 55 },
            { day: 'Thu', reqs: 4800, height: 90 },
            { day: 'Fri', reqs: 3100, height: 65 },
            { day: 'Sat', reqs: 5500, height: 100 },
            { day: 'Sun', reqs: 4200, height: 85 },
            { day: 'Mon', reqs: 2500, height: 60 },
            { day: 'Tue', reqs: 3800, height: 80 },
            { day: 'Wed', reqs: 1900, height: 50 },
            { day: 'Thu', reqs: 4100, height: 82 },
            { day: 'Fri', reqs: 2900, height: 62 },
            { day: 'Sat', reqs: 5100, height: 95 },
            { day: 'Sun', reqs: 4600, height: 88 }
        ],

         initDashboard() {
            this.fetchMovies();
            this.fetchGenres();
            this.fetchUsers();
        }
    };
}

window.shopPage = function() {
    return {
        modalOpen: false,
        modalMode: 'add',
        formData: { name: '', price: 0, rarity: 'Common', image: '' },
        shopItems: [
            { id: 1, name: 'Gold Border', price: 500, rarity: 'Legendary', image: '' }
        ],
        openModal(mode, item = null) {
            this.modalMode = mode;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
        }
    };
};
