function userDashboard() {
    return {
        // Navigation & Tab State
        currentTab: 'dashboard',
        isNavOpen: false,
        returnToWatchlistAfterClose: false,
        
        // Chat State
        showChatPanel: false,
        activeChatFriend: null,
        chatMessages: [],
        chatInput: '',
        
        // Drawer Panels & Modals State
        showFriendsPanel: false,
        activeDropdown: null,
        dropdownX: 0,
        dropdownY: 0,
        reportReason: "",

        showQuestsPanel: false,
        questActiveTab: 'daily',
        showInviteModal: false,
        showNotifications: false,
        friendsTab: 'connected',
        movieModalOpen: false,
        editingMovie: false,
        viewModalOpen: false,
        roomModalOpen: false,
        modalOpen: false,
        modalMode: 'add',

        // Form & Data Objects
        selectedReport: null,
        selectedRoom: null,
        formData: { name: '', price: 0, rarity: 'Common', image: '' },
        shopItems: [],

        // Movie State & Modals
        movies: [],
        movieSearchQuery: '',
        selectedMovie: null,
        showMovieDetailModal: false,
        replyingToCommentId: null,
        activeMovieChannel: null,
        replyInputText: '',

        // Pusher Client
        pusherClient: null,

        // Add channel reference to Alpine state
        activeChatChannel: null,

        // --- Shared State ---
        selectedProfileUser: null, 
        
        // --- Report Modal State ---
        showReportModal: false,
        reportTab: 'select', 
        availableReasons: [],
        selectedReasonIds: [],
        reportDescription: '',
        reportItemPreview: '',

        // --- Account State ---
        displayName: window.USER_NAME || 'CurrentUser',
        displayEmail: window.USER_EMAIL || 'user@example.com',
        accountForm: { 
            username: window.USER_NAME || 'CurrentUser', 
            email: window.USER_EMAIL || 'user@example.com' 
        },
        passwordForm: { current: '', new: '', confirm: '' },
        activeBorderId: 1,
        availableBorders: [
            { id: 1, name: 'None', preview: 'https://via.placeholder.com/150/000000/FFFFFF/?text=None', owned: true },
            { id: 2, name: 'Encom Grid', preview: '/frontend/assets/borders/Encom%20grid.gif', owned: true },
            { id: 3, name: 'Glitch', preview: '/frontend/assets/borders/Glitch.gif', owned: false },
            { id: 4, name: 'Hallucination', preview: '/frontend/assets/borders/Hallunication.gif', owned: false },
            { id: 5, name: 'Spray Doodle', preview: '/frontend/assets/borders/Spray%20doodle.gif', owned: false },
            { id: 6, name: 'Sukuna Slashes', preview: '/frontend/assets/borders/Sukuna\'s%20slashes.gif', owned: true }
        ],

        // --- Report Item Modal State ---
        showReportItemModal: false,
        selectedItemIdToReport: null,
        reportItemType: 'reply', // 'reply' or 'comment'
        reportItemDescription: '',
        selectedItemReasonIds: [],

       // Live filtered movies getter
        get filteredMovies() {
            if (!this.movieSearchQuery.trim()) return this.movies;
            const query = this.movieSearchQuery.toLowerCase();
            return this.movies.filter(movie => 
                (movie.title && movie.title.toLowerCase().includes(query)) ||
                (Array.isArray(movie.genres) && movie.genres.some(g => g.toLowerCase().includes(query)))
            );
        },

        // API Fetching
        async fetchMovies() { 
            try { 
                const response = await fetch(`/user_backend/movies_api.php?t=${Date.now()}`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json(); 
                console.log("Fetched movies:", data)
                this.movies = data;
            } catch(e) {
                console.error("Failed to load movies from database:", e);
                // Optional: Set an error message visible in the UI
                this.movieError = "Failed to load movies. Please try again.";
            } 
        },

        // Detect if URL is from YouTube
        isYouTubeUrl(url) {
            if (!url) return false;
            return url.includes('youtube.com') || url.includes('youtu.be');
        },

        // --- Account Methods ---
        deleteAccountModalOpen: false,
        deleteAccountPassword: '',
        deleteAccountError: '',

       async updateAccountInfo() {
            if (!this.accountForm.username.trim() || !this.accountForm.email.trim()) {
                if (window.showToast) window.showToast('Username and email are required.', 'error');
                return;
            }

            try {
                const res = await fetch('/user_backend/update_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: this.accountForm.username.trim(),
                        email: this.accountForm.email.trim()
                        // No active_border_id here
                    })
                });

                const rawText = await res.text();
                let data;
                try { data = JSON.parse(rawText); } catch (e) { throw new Error('Invalid server response'); }

                if (data.success) {
                    this.displayName = this.accountForm.username;
                    this.displayEmail = this.accountForm.email;
                    window.USER_NAME = this.accountForm.username;
                    window.USER_EMAIL = this.accountForm.email;
                    if (window.showToast) window.showToast('Profile updated successfully!', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to update profile.', 'error');
                }
            } catch (e) {
                console.error(e);
                if (window.showToast) window.showToast('Network error updating profile.', 'error');
            }
        },

        async updatePassword() {
            if (!this.passwordForm.current || !this.passwordForm.new || !this.passwordForm.confirm) {
                if (window.showToast) window.showToast('Please fill all password fields.', 'error');
                return;
            }
            if (this.passwordForm.new !== this.passwordForm.confirm) {
                if (window.showToast) window.showToast('New passwords do not match.', 'error');
                return;
            }

            try {
                const res = await fetch('/user_backend/update_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: this.passwordForm.current,
                        new_password: this.passwordForm.new
                    })
                });

                const rawText = await res.text();
                let data;
                try { data = JSON.parse(rawText); } catch (e) { throw new Error('Invalid server response'); }

                if (data.success) {
                    this.passwordForm = { current: '', new: '', confirm: '' };
                    if (window.showToast) window.showToast('Password updated successfully!', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to update password.', 'error');
                }
            } catch (e) {
                console.error(e);
                if (window.showToast) window.showToast('Network error updating password.', 'error');
            }
        },

        async confirmDeleteAccount() {
            this.deleteAccountError = '';
            if (!this.deleteAccountPassword.trim()) {
                this.deleteAccountError = 'Please enter your password to confirm account deletion.';
                return;
            }

            try {
                const res = await fetch('/user_backend/delete_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: this.deleteAccountPassword })
                });

                const rawText = await res.text();
                let data;
                try { data = JSON.parse(rawText); } catch (e) { throw new Error('Invalid server response'); }

                if (data.success) {
                    window.location.href = '/frontend/login.php?account_deleted=1';
                } else {
                    this.deleteAccountError = data.message || 'Failed to delete account.';
                }
            } catch (e) {
                console.error(e);
                this.deleteAccountError = 'Network error processing account deletion.';
            }
        },

        setActiveBorder(borderId) {
            const border = this.availableBorders.find(b => b.id === borderId);
            if (!border) return;
            if (!border.owned) {
                if (window.showToast) window.showToast('You do not own this border.', 'error');
                return;
            }
            this.activeBorderId = borderId;
            localStorage.setItem('activeBorder', borderId);
            if (window.showToast) window.showToast(`${border.name} border applied!`, 'success');
        },

        // Convert any standard YouTube link into a clean Embed URL
        getYouTubeEmbedUrl(url, isHover = false) {
            if (!url) return '';
            
            // Extract Video ID
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
            
            if (match && match[2].length === 11) {
                const videoId = match[2];
                
                // Query parameters for YouTube Embed
                const params = new URLSearchParams({
                    autoplay: '1',
                    mute: isHover ? '1' : '0',            // Browser policy requires mute for auto-play on hover
                    controls: '0',                        // Always hide controls
                    loop: '1',
                    playlist: videoId,                    // Required for looping
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

        // Modal triggers
       async toggleWatchlist(movie) {
            if (!movie) return;

            movie.inWatchlist = !movie.inWatchlist;
            const movieId = movie.id || movie.movie_id;
            if (!movieId) return;

            // Optimistic local update
            if (movie.inWatchlist) {
                if (!this.watchlist.find(w => (w.id || w.movie_id) === movieId)) {
                    this.watchlist = [{
                        ...movie,
                        year: movie.created_at ? new Date(movie.created_at).getFullYear() : "2024",
                        genre: movie.genres && movie.genres.length > 0 ? movie.genres[0] : (movie.genre || "Movie"),
                        rating: movie.rating ? movie.rating + " / 5" : "N/A",
                        status: "Next Up",
                        img: movie.img || movie.cover_image || "https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster"
                    }, ...this.watchlist];
                }
                if (window.showToast) window.showToast('Added to watchlist', 'success');
            } else {
                this.watchlist = this.watchlist.filter(w => (w.id || w.movie_id) !== movieId);
                if (window.showToast) window.showToast('Removed from watchlist', 'info');
            }

            // Persist to backend
            try {
                const response = await fetch('/user_backend/toggle_watchlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ movie_id: movieId })
                });
                const data = await response.json();
                if (!data.success && window.showToast) {
                    window.showToast(data.message || 'Failed to update watchlist', 'error');
                    movie.inWatchlist = !movie.inWatchlist;  // revert
                    await this.fetchWatchlist();
                }
            } catch (e) {
                console.error('Watchlist save error:', e);
                movie.inWatchlist = !movie.inWatchlist;
                await this.fetchWatchlist();
            }
        },

        async openMovieDetail(movie) {
            this.selectedMovie = movie;
            this.showMovieDetailModal = true;
            
            // Extract the reliable ID
            const movieId = this.getMovieId(movie);
            
            if (movieId) {
                // 1. Fetch historical comments from the database (does not block modal opening)
                this.fetchMovieComments(movieId);
                
                // 2. Subscribe to the real-time Pusher channel
                this.subscribeToLiveMovieEvents(movieId);
                
            }
        },

       closeMovieDetail() {
            if (this.selectedMovie) {
                const movieId = this.getMovieId(this.selectedMovie);
                if (movieId) {
                    this.unsubscribeFromLiveMovieEvents(movieId);
                }
            }

            this.showMovieDetailModal = false;

            // If opened from watchlist, switch back to watchlist tab
            if (this.returnToWatchlistAfterClose) {
                this.switchTab('watchlist');
                this.returnToWatchlistAfterClose = false;
            }

            setTimeout(() => {
                this.selectedMovie = null;
            }, 300);
        },

        // Quests Data
       quests: {
            daily: [],
            weekly: [],
            monthly: []
        },

        //noti
        async deleteNotification(notificationId) {
            try {
                const res = await fetch('/user_backend/delete_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notificationId })
                });
                const data = await res.json();
                if (data.success) {
                    this.notifications = this.notifications.filter(n => n.id !== notificationId);
                    this.unreadNotifCount = this.notifications.filter(n => Number(n.is_read) === 0).length;
                }
            } catch (e) {
                console.error('Failed to delete notification:', e);
            }
        },

        // Friends & User Search State
        

        // Data Lists
        friends: [],
        pendingRequests: [],
        notifications: [],
        searchResults: [],
        unreadNotifCount: 0,
        
        searchQuery: '',
        friendSearchQuery: '',
        searchTimeout: null,
        pollingInterval: null,

        // Navigation Items
        navItems: [
            { id: 'dashboard', label: 'Command Center', icon: 'dashboard', module: 'MODULE_1' },
            { id: 'watchlist', label: 'Watchlist', icon: 'bookmark', module: 'MODULE_2' },
            { id: 'movies', label: 'Movies', icon: 'movie', module: 'MODULE_3' },
            { id: 'shop', label: 'Point Shop', icon: 'storefront', module: 'MODULE_4' },
            { id: 'account', label: 'Account', icon: 'person', module: 'MODULE_5' }
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
        watchlist: [],
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

        async loadMissions() {
            try {
                const response = await fetch('/user_backend/mission.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update Total Points in stats array
                    this.stats[3].value = data.totalPoints;
                    
                    // Populate daily, weekly, and monthly quests dynamically
                    ['daily', 'weekly', 'monthly'].forEach(type => {
                        this.quests[type] = (data.quests[type] || []).map(q => ({
                            id: q.mission_id,
                            title: q.title,
                            desc: `Reward: ${q.points_reward} Points`, // Fixed backticks
                            points: q.points_reward,
                            completed: Number(q.completed) === 1
                        }));
                    });
                }
            } catch (err) {
                console.error('Failed to fetch missions:', err);
            }
        },

        // Fetch Friends & Incoming Pending Requests
        async fetchFriends() {
            try {
                const response = await fetch('/user_backend/get_friends.php');
                if (!response.ok) throw new Error('Failed to fetch friends');
                
                const data = await response.json();
                
                if (data && !data.error) {
                    this.friends = data.friends || [];
                    this.pendingRequests = data.pending_requests || [];
                    this.updateFriendsCount();
                }
            } catch (err) {
                console.error('Fetch friends error:', err);
            }
        },

        // Fetch Notifications
        async fetchNotifications() {
            try {
                const response = await fetch('/user_backend/get_notifications.php');
                if (!response.ok) return;

                const data = await response.json();
                if (data.success && Array.isArray(data.notifications)) {
                    this.notifications = data.notifications;
                    this.unreadNotifCount = this.notifications.filter(n => Number(n.is_read) === 0).length;
                }
            } catch (err) {
                console.error('Notification error:', err);
            }
        },

        async clearAllNotifications() {
            try {
                const res = await fetch('/user_backend/clear_notifications.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    this.notifications = [];
                    this.unreadNotifCount = 0;
                    window.showToast('All notifications cleared.', 'success');
                }
            } catch (err) {
                console.error('Failed to clear notifications:', err);
                // Even if it fails server-side or mock, we can clear locally for UX if we want.
                this.notifications = [];
                this.unreadNotifCount = 0;
            }
        },

        respondToFriendRequest(userId, action) {
            const targetUserId = Number(userId);

            fetch('/user_backend/respond_friend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sender_id: targetUserId, action: action })
            })
            .then(async res => {
                // Fix 1: Read raw text first to prevent silent JSON syntax crashes 
                // caused by backend warnings or extra whitespace
                const rawText = await res.text();
                try {
                    return JSON.parse(rawText);
                } catch (err) {
                    console.error("respond_friend.php returned invalid JSON:", rawText);
                    throw new Error("Invalid JSON response from server");
                }
            })
            .then(data => {
                if (data && data.success) {
                    if (window.showToast) window.showToast(`Friend request ${action}ed!`, 'success');

                    // Fix 2: Find the user details regardless of which page/panel the accept was clicked from
                    let acceptedUser = this.pendingRequests.find(req => Number(req.user_id) === targetUserId);
                    
                    // If not found in pending, check notifications
                    if (!acceptedUser) {
                        const notifUser = this.notifications.find(n => Number(n.sender_id) === targetUserId);
                        if (notifUser) acceptedUser = { user_id: notifUser.sender_id, user_name: notifUser.sender_name };
                    }
                    
                    // If still not found, check search results
                    if (!acceptedUser) {
                        const searchUser = this.searchResults.find(u => Number(u.user_id) === targetUserId);
                        if (searchUser) acceptedUser = { user_id: searchUser.user_id, user_name: searchUser.user_name || searchUser.name };
                    }
                    
                    // Delete friend request notifications for this user (accept/decline)
                    const notifIdsToDelete = this.notifications
                        .filter(n => n.type === 'friend_request' && Number(n.sender_id) === targetUserId)
                        .map(n => n.id);

                    notifIdsToDelete.forEach(id => {
                        fetch('/user_backend/delete_notification.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ notification_id: id })
                        });
                    });
                    // Synchronize State Arrays (Immediately remove the requests/notifications)
                    this.pendingRequests = this.pendingRequests.filter(req => Number(req.user_id) !== targetUserId);
                    this.notifications = this.notifications.filter(notif => Number(notif.sender_id) !== targetUserId);
                    this.unreadNotifCount = this.notifications.filter(n => Number(n.is_read) === 0).length;

                    // Update Search Results UI instantly
                    const userIndex = this.searchResults.findIndex(u => Number(u.user_id) === targetUserId);
                    if (userIndex !== -1) {
                        this.searchResults[userIndex].friend_status = action === 'accept' ? 'accepted' : null;
                        this.searchResults = [...this.searchResults]; // Trigger Alpine reactivity
                    }

                    if (action === 'accept') {
                        // Optimistically add to local friends list to update the dashboard counter and menus instantly
                        if (acceptedUser && !this.friends.some(f => Number(f.user_id) === targetUserId)) {
                            this.friends = [...this.friends, {
                                user_id: acceptedUser.user_id,
                                user_name: acceptedUser.user_name || "New Friend",
                                unread_count: 0
                            }];
                            this.updateFriendsCount();
                        }
                        
                        // Fetch fresh data in the background to ensure perfect database sync
                        this.fetchFriends().then(() => {
                            this.initAllChatSubscriptions();
                        });
                    }
                } else {
                    console.error("Action failed:", data);
                    if (window.showToast) window.showToast(data.message || 'Action failed', 'error');
                }
            })
            .catch(error => console.error("Fetch/Network Error:", error));
        },

        sendFriendRequest(userId) {
            fetch('/user_backend/add_friend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ friend_id: userId })
            })
            .then(async res => {
                const rawText = await res.text(); 
                try {
                    return JSON.parse(rawText);
                } catch (err) {
                    console.error("Backend returned HTML instead of JSON. Raw response:", rawText);
                    throw new Error("Invalid JSON response from server");
                }
            })
            .then(data => {
                if (data && data.success) {
                    if (window.showToast) window.showToast('Friend request sent!', 'success');
                    
                    // SYNCHRONIZE STATE
                    const userIndex = this.searchResults.findIndex(u => u.user_id === userId);
                    if (userIndex !== -1) {
                        this.searchResults[userIndex].friend_status = 'pending';
                        this.searchResults[userIndex].requester_id = window.CURRENT_USER_ID; 
                        
                        // 🔥 FIX: Force Alpine to recognize the array changed
                        this.searchResults = [...this.searchResults];
                    }
                } else {
                    console.error("Request failed:", data);
                }
            })
            .catch(error => console.error("Fetch/Network Error:", error));
        },

        // Search Users
        searchUsers(query = null) {
            const searchTerm = (query !== null ? query : this.searchQuery) || '';
            
            fetch(`/user_backend/search_users.php?q=${encodeURIComponent(searchTerm.trim())}`)
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) {
                        // Throws error containing backend response message
                        throw new Error(data.error || `HTTP error! Status: ${res.status}`);
                    }
                    return data;
                })
                .then(data => {
                    if (Array.isArray(data)) {
                        this.searchResults = data;
                    } else {
                        console.warn('Unexpected response format:', data);
                        this.searchResults = [];
                    }
                })
                .catch(err => {
                    console.error('Search error:', err.message);
                    this.searchResults = [];
                });
        },

        getFriendStatus(user) {
            const userId = Number(user.user_id);

            // 1. Already connected as friends
            if (user.friend_status === 'accepted' || this.friends.some(f => Number(f.user_id) === userId)) {
                return 'friend';
            }

            // 2. Incoming request: other user sent YOU the request
            const isIncoming = this.pendingRequests.some(r => Number(r.user_id) === userId) ||
                            (user.friend_status === 'pending' && Number(user.requester_id) === userId);
            if (isIncoming) {
                return 'incoming_pending';
            }

            // 3. Outgoing request: YOU sent them the request
            if (user.friend_status === 'pending') {
                return 'outgoing_pending';
            }

            // 4. No existing relationship
            return 'none';
        },

        // Update Dashboard Stat Card
        updateFriendsCount() {
            const friendStat = this.stats.find(s => s.label === 'Friends');
            if (friendStat) {
                friendStat.value = this.friends ? this.friends.length : 0;
            }
        },

        get filteredFriends() {
            if (!this.friendSearchQuery) return this.friends;
            return this.friends.filter(f => 
                (f.user_name || '').toLowerCase().includes(this.friendSearchQuery.toLowerCase())
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
        
        // 1. Time Formatting Helper (Uniform Time Display)
        formatTime(timeInput) {
            if (!timeInput) return '';
            
            if (typeof timeInput === 'string' && /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]\s?(AM|PM)?$/i.test(timeInput.trim())) {
                return timeInput.trim();
            }

            let parsableTime = timeInput;
            if (typeof timeInput === 'string' && timeInput.includes(' ') && !timeInput.includes('T')) {
                parsableTime = timeInput.replace(' ', 'T') + 'Z'; 
            }

            const date = new Date(parsableTime);
            if (isNaN(date.getTime())) return timeInput;

            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        // Add activeSubscriptions to your state object at the top:
        activeSubscriptions: new Set(),

        clearFriendUnread(friendId) {
            const id = Number(friendId);
            this.friends = this.friends.map(f =>
                Number(f.user_id || f.friend_id || f.id) === id
                    ? { ...f, unread_count: 0 }
                    : f
            );
        },

        incrementFriendUnread(friendId) {
            const id = Number(friendId);
            let friendName = null;
            this.friends = this.friends.map(f => {
                if (Number(f.user_id || f.friend_id || f.id) === id) {
                    friendName = f.user_name;
                    return { ...f, unread_count: (Number(f.unread_count) || 0) + 1 };
                }
                return f;
            });
            return friendName;
        },

        // Reusable Channel Subscription Helper
        subscribeToChatChannel(friendId) {
            const targetFriendId = Number(friendId);
            const currentUserId = Number(window.CURRENT_USER_ID);

            if (typeof Pusher === 'undefined' || !currentUserId || !targetFriendId) return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            const minId = Math.min(currentUserId, targetFriendId);
            const maxId = Math.max(currentUserId, targetFriendId);
            const channelName = `chat-${minId}-${maxId}`;

            if (this.activeSubscriptions.has(channelName)) return;
            this.activeSubscriptions.add(channelName);

            const channel = this.pusherClient.subscribe(channelName);

            channel.bind('new_message', (data) => {
                const senderId = Number(data.sender_id);
                if (senderId === Number(window.CURRENT_USER_ID)) return;

                const activeFriendId = Number(this.activeChatFriend?.user_id || this.activeChatFriend?.friend_id || this.activeChatFriend?.id);
                const isCurrentActiveChat = this.showChatPanel && activeFriendId === senderId;

                if (isCurrentActiveChat) {
                    this.chatMessages = [...this.chatMessages, {
                        id: data.message_id || data.id || 'live-' + Date.now(),
                        sender: 'them',
                        text: data.message_text,
                        time: this.formatTime(data.time)
                    }];
                    this.scrollToBottom();

                    fetch('/user_backend/mark_as_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ sender_id: senderId })
                    });
                } else {
                    const friendName = this.incrementFriendUnread(senderId);
                    if (friendName && typeof window.showToast === 'function') {
                        window.showToast(`New message from ${friendName}`, 'info');
                    }
                }
            });

            channel.bind('messages_read', (data) => {
                const readerId = Number(data.reader_id);
                const senderId = Number(data.sender_id);
                const currentUserId = Number(window.CURRENT_USER_ID);

                // Receiver opened chat on another tab — sync unread badge here
                if (readerId === currentUserId && senderId) {
                    this.clearFriendUnread(senderId);
                }

                // Sender sees read receipts in the active chat
                const activeFriendId = Number(this.activeChatFriend?.user_id || this.activeChatFriend?.friend_id || this.activeChatFriend?.id);
                if (readerId === activeFriendId) {
                    this.chatMessages = this.chatMessages.map(msg =>
                        msg.sender === 'me' ? { ...msg, is_read: 1 } : msg
                    );
                }
            });
        },

        initAllChatSubscriptions() {
            this.friends.forEach(friend => {
                const friendId = friend.user_id || friend.friend_id || friend.id;
                if (friendId) this.subscribeToChatChannel(friendId);
            });
        },

        async openChat(friend) {
            const friendId = Number(friend.user_id || friend.friend_id || friend.id);
            if (!friendId) return;

            this.activeChatFriend = { ...friend, user_id: friendId, unread_count: 0 };
            this.chatMessages = [];
            this.showChatPanel = true;
            this.clearFriendUnread(friendId);

            // Ensure live subscription is active for this friend immediately
            this.subscribeToChatChannel(friendId);

            this.$nextTick(() => {
                if (typeof gsap !== 'undefined') {
                    gsap.fromTo(this.$refs.chatPanel, 
                        { x: '100%', opacity: 0 }, 
                        { x: '0%', opacity: 1, duration: 0.35, ease: 'power2.out' }
                    );
                }
            });

            await fetch('/user_backend/mark_as_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sender_id: friendId })
            });

            await this.fetchChatHistory(friendId);
        },

        
        toggleDropdown(user, event) {
            if (!user) return;
            const id = user.user_id || user.friend_id || user.id;
            if (this.activeDropdown === id) {
                this.closeDropdown();
            } else {
                this.activeDropdown = id;
                this.selectedProfileUser = user;
                this.reportReason = "";
                const rect = event.currentTarget.getBoundingClientRect();
                let x = rect.left;
                let y = rect.bottom + 8;
                if (x + 200 > window.innerWidth) x = window.innerWidth - 210;
                if (y + 150 > window.innerHeight) y = Math.max(10, rect.top - 180);
                this.dropdownX = x;
                this.dropdownY = y;
            }
        },
        closeDropdown() {
            this.activeDropdown = null;
            setTimeout(() => { 
                if (!this.showReportModal) {
                    this.selectedProfileUser = null; 
                    this.reportReason = ""; 
                }
            }, 200);
        },
        
        //report and unfriend
       // --- Fetch Predefined Reasons ---
        fetchReasons() {
            fetch('/user_backend/get_reasons.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.availableReasons = data.reasons;
                    }
                })
                .catch(err => console.error("Failed to fetch reasons", err));
        },

        // --- Report User Logic ---
        openReportModal(user) {
            if (!user) return;
            this.selectedProfileUser = user;
            this.showReportModal = true;
            this.selectedReasonIds = [];
            this.reportDescription = '';

            // Ensure reasons are loaded
            if (this.availableReasons.length === 0) {
                this.fetchReasons();
            }

            // Prevent dropdown timeout from clearing selectedProfileUser
            this.activeDropdown = null;
        },

        closeReportModal() {
            this.showReportModal = false;
            this.selectedReasonIds = [];
            this.reportDescription = '';

            // Clear selected user after modal is fully hidden
            setTimeout(() => {
                if (!this.showReportModal) {
                    this.selectedProfileUser = null;
                }
            }, 200);
        },

        openReportItemModal(id, type, previewText = '') {
            this.selectedItemIdToReport = id;
            this.reportItemType = type;
            this.reportItemDescription = '';
            this.selectedItemReasonIds = [];
            this.reportItemPreview = previewText;
            this.showReportItemModal = true;
            if (this.availableReasons.length === 0) {
                this.fetchReasons();
            }
        },

        closeReportItemModal() {
            this.showReportItemModal = false;
            setTimeout(() => {
                this.selectedItemIdToReport = null;
                this.reportItemDescription = '';
                this.selectedItemReasonIds = [];
                this.reportItemPreview = '';
                const modalEl = document.getElementById('report-item-modal-content');
                if (modalEl && window.gsap) gsap.set(modalEl, { clearProps: "all" });
            }, 300);
        },

        async submitItemReport() {
            if (!this.selectedItemIdToReport) return;
            const id = this.selectedItemIdToReport;
            const type = this.reportItemType;

            // Prevent double submission
            if (this.isSubmittingReport) return;
            this.isSubmittingReport = true;

            try {
                const res = await fetch('/user_backend/submit_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reported_item_id: id,
                        item_type: type,
                        reason_ids: this.selectedItemReasonIds.map(rid => Number(rid)),
                        description: this.reportItemDescription,
                        preview: this.reportItemPreview
                    })
                });

                const rawText = await res.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (e) {
                    console.error("Server returned non-JSON response:", rawText);
                    throw new Error("Invalid response from server");
                }

                if (data.success) {
                    // Only now animate and close
                    const modalEl = document.getElementById('report-item-modal-content');
                    const glitchEl = document.getElementById('item-modal-glitch');
                    if (modalEl && window.gsap) {
                        if (glitchEl) {
                            gsap.to(glitchEl, { opacity: 1, duration: 0.05, yoyo: true, repeat: 5 });
                        }
                        gsap.to(modalEl, { 
                            scale: 0.8, 
                            opacity: 0, 
                            duration: 0.3, 
                            ease: "back.in(1.5)",
                            onComplete: () => {
                                this.closeReportItemModal();
                                if (window.showToast) window.showToast('Report submitted. Our moderators will review it.', 'success');
                            }
                        });
                    } else {
                        this.closeReportItemModal();
                        if (window.showToast) window.showToast('Report submitted. Our moderators will review it.', 'success');
                    }
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to submit report.', 'error');
                }
            } catch (e) {
                console.error("Report item failed:", e);
                if (window.showToast) window.showToast('Failed to submit report. Please try again.', 'error');
            } finally {
                this.isSubmittingReport = false;
            }
        },

        submitReport() {
            if (!this.selectedProfileUser) return;
            if (this.selectedReasonIds.length === 0 && !this.reportDescription.trim()) return;

            const targetId = Number(
                this.selectedProfileUser.user_id || 
                this.selectedProfileUser.friend_id || 
                this.selectedProfileUser.id
            );

            fetch('/user_backend/submit_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    reported_id: targetId,
                    type: 'user', 
                    description: this.reportDescription, 
                    reason_ids: this.selectedReasonIds 
                })
            })
            .then(async res => {
                const text = await res.text(); // Read the raw response first
                try {
                    return JSON.parse(text); // Try to parse it as JSON
                } catch (e) {
                    // If it fails, print the raw PHP error to the console!
                    console.error("Backend returned HTML instead of JSON. Raw response:\n", text);
                    throw new Error("Invalid JSON response");
                }
            })
            .then(data => {
                if (data.success) {
                    this.closeReportModal();
                    if (window.showToast) window.showToast('Report submitted successfully.', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Report failed.', 'error');
                }
            })
            .catch(err => console.error("Report error:", err));
        },

        // --- Unfriend Logic ---
        unfriendUser(user) {
            // Fallback to the currently selected dropdown user
            const targetUser = user || this.selectedProfileUser;
            
            if (!targetUser) return;
            
            const targetId = Number(targetUser.user_id || targetUser.friend_id || targetUser.id);
            const userName = targetUser.user_name || targetUser.name || 'this user';

            if (!confirm(`Are you sure you want to unfriend ${userName}?`)) return;

            fetch('/user_backend/unfriend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    friend_id: targetId
                })
            })
            .then(async res => {
                const rawText = await res.text();

                console.log('UNFRIEND HTTP STATUS:', res.status);
                console.log('UNFRIEND RAW RESPONSE:', rawText);

                try {
                    return JSON.parse(rawText);
                } catch (e) {
                    throw new Error(
                        'unfriend.php returned invalid JSON:\n' + rawText
                    );
                }
            })
            .then(data => {
                if (data.success) {
                    if (window.showToast) {
                        window.showToast(
                            'User removed from friends.',
                            'success'
                        );
                    }

                    this.friends = this.friends.filter(
                        f => Number(f.user_id || f.friend_id || f.id) !== targetId
                    );

                    this.updateFriendsCount();
                    this.closeDropdown();

                } else {
                    if (window.showToast) {
                        window.showToast(
                            data.message || 'Failed to unfriend user.',
                            'error'
                        );
                    }
                }
            })
            .catch(err => {
                console.error('Unfriend error:', err);
            });
        },

        // 4. Updated closeChat Method
        closeChat() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this.$refs.chatPanel, {
                    x: '100%',
                    opacity: 0,
                    duration: 0.3,
                    ease: 'power2.in',
                    onComplete: () => {
                        this.showChatPanel = false;
                        this.activeChatFriend = null;
                        // gsap.set(".chat-panel-container", { clearProps: "all" });
                    }
                });
            } else {
                this.showChatPanel = false;
                this.activeChatFriend = null;
            }
        },

        // 5. Updated fetchChatHistory Method
        async fetchChatHistory(friendId) {
            if (!friendId) return;

            try {
                const res = await fetch(`/user_backend/get_chat_history.php?friend_id=${friendId}`);
                const rawText = await res.text();

                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (jsonErr) {
                    console.error("Non-JSON output returned from server:", rawText);
                    return;
                }

                if (data.success) {
                    this.chatMessages = data.messages.map(msg => ({
                        id: msg.id || msg.message_id || 'db-' + Math.random(), // ADDED: ID mapping
                        sender: Number(msg.sender_id) === Number(window.CURRENT_USER_ID) ? 'me' : 'them',
                        text: msg.message_text,
                        time: this.formatTime(msg.time),
                        is_read: msg.is_read
                    }));
                    this.scrollToBottom();
                } else {
                    console.error("Backend error loading chats:", data.message);
                }
            } catch (e) {
                console.error("Network error loading chat history:", e);
            }
        },

        // 6. Updated sendMessage Method
        async sendMessage() {
            if (!this.chatInput.trim() || !this.activeChatFriend) return;

            const messageText = this.chatInput.trim();
            this.chatInput = '';
            
            const messageObj = {
                id: 'local-' + Date.now(), 
                sender: 'me',
                text: messageText,
                time: this.formatTime(new Date()),
                is_read: 0
            };

            // ADDED: A unique ID so Alpine.js renders it instantly
            this.chatMessages = [...this.chatMessages, messageObj];
            this.scrollToBottom();

            try {
                await fetch('/user_backend/send_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        receiver_id: Number(this.activeChatFriend.user_id || this.activeChatFriend.friend_id || this.activeChatFriend.id),
                        message: messageText
                    })
                });
            } catch (e) {
                console.error("Failed to send message:", e);
            }
        },

        // Auto-scroll utility
        scrollToBottom() {
            this.$nextTick(() => {
                const container = document.querySelector('.chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        openAddMovieModal() {
            this.editingMovie = false;
            this.newMovie = { title: '', genre: '', year: '', rating: '', description: '', trailer: '', img: '', comments: [] };
            this.movieModalOpen = true;
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


        //movie
        getMovieId(movie) {
            if (!movie) return null;
            return movie.id || movie.movie_id;
        },

        //comments
        likingComments: new Set(),

        // Fetch existing comments
        async fetchMovieComments(movieId) {
            try {
                const res = await fetch(`/user_backend/get_comments.php?movie_id=${movieId}`);
                const data = await res.json();
                if (data.success) {
                    const normaliseComment = (c) => ({
                        ...c,
                        is_liked: Boolean(Number(c.is_liked)),
                        likes_count: Number(c.likes_count) || 0,
                        replies: c.replies ? c.replies.map(normaliseComment) : []
                    });

                    this.selectedMovie.comments = data.comments.map(normaliseComment);
                }
            } catch (e) {
                console.error("Failed to load comments:", e);
            }
        },


        // Unified Process Live Review
        async processLiveReview(rating, commentText) {
            if (!this.selectedMovie) return false;
            const movieId = this.getMovieId(this.selectedMovie);

            try {
                // 1. Submit Rating
                if (rating > 0) {
                    const ratingRes = await fetch('/user_backend/rate_movie.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ movie_id: movieId, rating: rating })
                    });
                    
                    if (!ratingRes.ok) {
                        throw new Error(`Rating HTTP error: ${ratingRes.status}`);
                    }
                    
                    const ratingData = await ratingRes.json();
                    
                    if (!ratingData.success) {
                        throw new Error(ratingData.message || 'Rating submission failed');
                    }

                    // Ensure the rating is saved as an integer
                    this.selectedMovie.user_rating = parseInt(rating, 10);
                    
                    // Force Alpine to recognize the deep object change
                    this.selectedMovie = { ...this.selectedMovie }; 
                    
                    // Sync the personal user_rating back to the main movies array
                    const movieIndex = this.movies.findIndex(m => this.getMovieId(m) === movieId);
                    if (movieIndex > -1) {
                        this.movies[movieIndex].user_rating = this.selectedMovie.user_rating;
                    }

                    // REMOVED: this.updateStarDisplay(this.selectedMovie.user_rating);
                }

                // 2. Submit Comment (keep existing logic)
                if (commentText.trim().length > 0) {
                    const commentRes = await fetch('/user_backend/post_comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ movie_id: movieId, comment: commentText })
                    });
                    if (!commentRes.ok) {
                        throw new Error(`Comment HTTP error: ${commentRes.status}`);
                    }
                    const commentData = await commentRes.json();
                    if (!commentData.success) {
                        throw new Error(commentData.message || 'Comment submission failed');
                    }
                    await this.fetchMovieComments(movieId);
                }

                // If we reach here, both succeeded
                return true;
            } catch (e) {
                console.error("Failed to post review:", e);
                if (window.showToast) {
                    window.showToast(e.message || 'Error posting review.', 'error');
                }
                return false;
            }
        },

       // Reply to a Comment Method
        async postReply(parentCommentId) {
            if (!this.replyInputText.trim()) return;

            const replyText = this.replyInputText.trim();
            this.replyInputText = '';
            this.replyingToCommentId = null;

            try {
                const res = await fetch('/user_backend/post_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        movie_id: this.selectedMovie.id,
                        parent_id: parentCommentId,
                        comment: replyText
                    })
                });
                
                // Read the raw text first before forcing it to be JSON
                const rawText = await res.text(); 
                
                try {
                    const data = JSON.parse(rawText); // Try to parse it
                    if (!data.success && window.showToast) {
                        window.showToast(data.message || 'Failed to post reply', 'error');
                    } else {
                        await this.fetchMovieComments(this.selectedMovie.id);
                    }
                } catch (parseError) {
                    console.error("Server returned non-JSON response:", rawText);
                    if (window.showToast) window.showToast("Server error occurred.", "error");
                }
                
            } catch (e) {
                console.error("Reply error:", e);
            }
        },

        async submitReply(parentId, replyText) {
            if (!replyText || !replyText.trim() || !this.selectedMovie) return false;
            const movieId = this.getMovieId(this.selectedMovie);

            try {
                const res = await fetch('/user_backend/post_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        movie_id: movieId,
                        parent_id: parentId,
                        comment: replyText
                    })
                });

                const data = await res.json();
                return data.success;
            } catch (e) {
                console.error("Failed to post reply:", e);
                return false;
            }
        },

        async submitReview(rating, commentText) {
            return await this.processLiveReview(rating, commentText);
        },

        async toggleLike(commentId) {
            if (!this.selectedMovie) return; // Guard for null at start
            const commentIdNum = Number(commentId);
            if (this.likingComments.has(commentIdNum)) return; // Already in progress

            // Recursive find (works for top-level and replies)
            const findComment = (list) => {
                for (let c of list) {
                    if (Number(c.id) === commentIdNum) return c;
                    if (c.replies) {
                        const found = findComment(c.replies);
                        if (found) return found;
                    }
                }
                return null;
            };

            const comment = findComment(this.selectedMovie.comments || []);
            if (!comment) return;

            this.likingComments.add(commentIdNum);
            const action = comment.is_liked ? 'unlike' : 'like';

            // Optimistic UI update
            comment.is_liked = !comment.is_liked;
            comment.likes_count = (Number(comment.likes_count) || 0) + (comment.is_liked ? 1 : -1);
            this.selectedMovie.comments = [...this.selectedMovie.comments];

            try {
                const res = await fetch('/user_backend/like_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ comment_id: commentIdNum, action })
                });

                const data = await res.json();

                // Re-check after await (modal may have been closed)
                if (!this.selectedMovie) return;
                const currentComment = findComment(this.selectedMovie.comments || []);
                if (!currentComment) return;

                if (!data.success) {
                    // Revert on server error
                    currentComment.is_liked = !currentComment.is_liked;
                    currentComment.likes_count = (Number(currentComment.likes_count) || 0) + (currentComment.is_liked ? 1 : -1);
                } else {
                    // Overwrite with server values
                    if (data.likes_count !== undefined) currentComment.likes_count = Number(data.likes_count);
                    if (data.is_liked !== undefined) currentComment.is_liked = Boolean(data.is_liked);
                }
                this.selectedMovie.comments = [...this.selectedMovie.comments];
            } catch (e) {
                console.error("Like failed:", e);
                // Revert if still possible
                if (this.selectedMovie) {
                    const currentComment = findComment(this.selectedMovie.comments || []);
                    if (currentComment) {
                        currentComment.is_liked = !currentComment.is_liked;
                        currentComment.likes_count = (Number(currentComment.likes_count) || 0) + (currentComment.is_liked ? 1 : -1);
                        this.selectedMovie.comments = [...this.selectedMovie.comments];
                    }
                }
            } finally {
                this.likingComments.delete(commentIdNum);
            }
        },

        async fetchWatchlist() {
            try {
                const response = await fetch("/user_backend/get_watchlist.php");
                let raw = await response.text();
                console.log("Raw watchlist response:", raw);
                
                // Remove leading backslash if present
                if (raw.startsWith('\\')) {
                    raw = raw.substring(1);
                }
                
                const data = JSON.parse(raw);
                if (data.success) {
                    this.watchlist = data.watchlist || [];
                    this.watchlist = [...this.watchlist];
                    this.syncWatchlistState();
                } else {
                    console.error("Watchlist fetch failed:", data.message);
                }
            } catch (e) {
                console.error("Failed to fetch watchlist:", e);
            }
        },

        syncWatchlistState() {
            if (!this.movies.length || !this.watchlist.length) return;
            const watchlistIds = new Set(this.watchlist.map(w => Number(w.id)));
            
            this.movies.forEach(movie => {
                const id = Number(this.getMovieId(movie));
                movie.inWatchlist = watchlistIds.has(id);
            });
        },
        openWatchlistMovie(item) {
            // Set the flag so we return to watchlist after closing the modal
            this.returnToWatchlistAfterClose = true;

            this.switchTab('movies');

            const movieId = item.id || item.movie_id;
            const fullMovie = this.movies.find(m => (m.id || m.movie_id) === movieId) || item;

            setTimeout(() => {
                this.openMovieDetail(fullMovie);
            }, 100);
        },
         // Real-Time Listener with Alpine Reactivity Fixes
        subscribeToLiveMovieEvents(movieId) {
            if (typeof Pusher === 'undefined') return;

            // Initialize Pusher client once if not active
            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            const channelName = `movie-${movieId}`;

            // Clean up previous movie channel before subscribing to a new one
            if (this.activeMovieChannel) {
                this.pusherClient.unsubscribe(this.activeMovieChannel.name);
                this.activeMovieChannel = null;
            }

            // Subscribe to new channel
            const channel = this.pusherClient.subscribe(channelName);
            this.activeMovieChannel = channel;

            // 1. Live Ratings Update
            channel.bind('rating_updated', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                // Directly update properties on the reactive proxy instead of reassigning the whole object
                this.selectedMovie.rating = data.avg_rating;
                this.selectedMovie.rating_count = data.rating_count;

                // Sync main movies array
                const movieInList = this.movies.find(m => Number(this.getMovieId(m)) === Number(data.movie_id));
                if (movieInList) {
                    movieInList.rating = data.avg_rating;
                    movieInList.rating_count = data.rating_count;
                }
            });

            // 2. Live Comment Update
           channel.bind('new_comment', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                const normaliseComment = (c) => ({
                    id: c.id || c.comment_id || 'live-' + Date.now(),
                    user_name: c.user_name || 'Anonymous',
                    comment_text: c.comment_text || c.content || '',
                    // Add this line – the template uses `comment.comment`
                    comment: c.comment_text || c.content || c.comment || '',
                    created_at: c.created_at || new Date().toISOString(),
                    likes_count: Number(c.likes_count) || 0,
                    is_liked: Boolean(Number(c.is_liked)),
                    rating: c.rating || null,
                    replies: c.replies || [],
                    parent_id: c.parent_id || null,
                    border_preview: c.border_preview || null
                });

                const newComment = normaliseComment(data);

                const existing = (this.selectedMovie.comments || []).some(c => Number(c.id) === Number(newComment.id));
                if (existing) return;

                this.selectedMovie.comments = [newComment, ...(this.selectedMovie.comments || [])];
                this.selectedMovie = { ...this.selectedMovie };
            });

            // 3. Live Reply Update
          channel.bind('new_reply', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                const normaliseReply = (r) => ({
                    id: r.id || r.comment_id || 'live-' + Date.now(),
                    user_name: r.user_name || 'Anonymous',
                    comment_text: r.comment_text || r.content || '',
                    // Add this line
                    comment: r.comment_text || r.content || r.comment || '',
                    created_at: r.created_at || new Date().toISOString(),
                    likes_count: Number(r.likes_count) || 0,
                    is_liked: Boolean(Number(r.is_liked)),
                    replies: [],
                    parent_id: r.parent_id || null,
                    border_preview: r.border_preview || null
                });

                const newReply = normaliseReply(data);

                const updatedComments = this.selectedMovie.comments.map(comment => {
                    if (Number(comment.id) === Number(data.parent_id)) {
                        const currentReplies = comment.replies || [];
                        const replyExists = currentReplies.some(r => Number(r.id) === Number(newReply.id));
                        if (!replyExists) {
                            return { ...comment, replies: [...currentReplies, newReply] };
                        }
                    }
                    return comment;
                });

                this.selectedMovie = {
                    ...this.selectedMovie,
                    comments: updatedComments
                };
            });

            // 4. Live Like Update
           channel.bind('comment_liked', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                const findAndSetLikes = (list) => {
                    if (!list) return false;
                    for (let i = 0; i < list.length; i++) {
                        if (Number(list[i].id) === Number(data.comment_id)) {
                            list[i].likes_count = Number(data.likes_count);
                            if (data.user_id !== undefined && Number(data.user_id) === Number(window.CURRENT_USER_ID)) {
                                list[i].is_liked = Boolean(data.is_liked);
                            }
                            return true;
                        }
                        if (list[i].replies && findAndSetLikes(list[i].replies)) {
                            return true;
                        }
                    }
                    return false;
                };

                const newComments = [...(this.selectedMovie.comments || [])];
                if (findAndSetLikes(newComments)) {
                    this.selectedMovie.comments = newComments;
                }
            });
        },

        unsubscribeFromLiveMovieEvents(movieId) {
            if (this.pusherClient && movieId) {
                this.pusherClient.unsubscribe(`movie-${movieId}`);
                this.activeMovieChannel = null;
            }
        },

        markNotificationsAsRead() {
            if (this.unreadNotifCount === 0) return;

            fetch('/user_backend/mark_notifications_read.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.unreadNotifCount = 0;
                        this.notifications.forEach(n => n.is_read = 1);
                    }
                });
        },

        clearAllNotifications() {
            fetch('/user_backend/clear_notifications.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.notifications = [];
                        this.unreadNotifCount = 0;
                    }
                });
        },

        // Initialize Pusher Connection
        initPusher() {
             if (typeof Pusher === 'undefined') {
                console.error('Pusher library not loaded');
                return;
            }
            if (!window.CURRENT_USER_ID || typeof Pusher === 'undefined') return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }   

            const channel = this.pusherClient.subscribe(`user-${window.CURRENT_USER_ID}`);

            channel.bind('notifications_read', () => {
                this.unreadNotifCount = 0;
                this.notifications = this.notifications.map(n => ({ ...n, is_read: 1 }));
            });

            channel.bind('notifications_cleared', () => {
                this.notifications = [];
                this.unreadNotifCount = 0;
            });

            // Real-time unfriend
            channel.bind('friend-removed', (data) => {
                const removedId = Number(data.friend_id);

                this.friends = this.friends.filter(
                    f => Number(f.user_id || f.friend_id || f.id) !== removedId
                );

                this.updateFriendsCount();

                // Also update search results immediately
                this.searchResults = this.searchResults.map(user => {
                    if (Number(user.user_id) === removedId) {
                        return {
                            ...user,
                            friend_status: null,
                            requester_id: null
                        };
                    }
                    return user;
                });

                if (window.showToast) {
                    window.showToast('A friendship was removed.', 'info');
                }
            });

            channel.bind('watchlist-updated', (data) => {
                const movieId = data.movie_id;
                const action = data.action;

                const catalogItem = this.movies.find(m => Number(this.getMovieId(m)) === Number(movieId));
                if (catalogItem) {
                    catalogItem.inWatchlist = (action === 'added');
                }

                if (action === 'removed') {
                    this.watchlist = this.watchlist.filter(w => Number(w.id) !== Number(movieId));
                } else if (action === 'added') {
                    this.fetchWatchlist();
                }
            });

            channel.bind('friend_event', (data) => {
                this.notifications = [
                    {
                        id: Date.now(),
                        type: data.type,
                        sender_id: data.sender_id,
                        sender_name: data.sender_name,
                        message: data.message,
                        created_at: data.created_at,
                        is_read: 0
                    },
                    ...this.notifications
                ];

                this.unreadNotifCount++;

                if (data.type === 'friend_request') {
                    const alreadyExists = this.pendingRequests.some(r => r.user_id == data.sender_id);
                    if (!alreadyExists) {
                        this.pendingRequests = [
                            {
                                user_id: data.sender_id,
                                user_name: data.sender_name
                            }, 
                            ...this.pendingRequests
                        ];
                    }
                }

                if (data.type === 'friend_accepted') {
                    const acceptorId = Number(data.sender_id);
                    const acceptorName = data.sender_name;

                    const friendExists = this.friends.some(f => Number(f.user_id) === acceptorId);
                    if (!friendExists) {
                        this.friends = [
                            ...this.friends,
                            {
                                user_id: acceptorId,
                                user_name: acceptorName,
                                unread_count: 0
                            }
                        ];
                        this.subscribeToChatChannel(acceptorId);
                    }

                    const userIndex = this.searchResults.findIndex(u => Number(u.user_id) === acceptorId);
                    if (userIndex !== -1) {
                        this.searchResults[userIndex].friend_status = 'accepted';
                        this.searchResults = [...this.searchResults];
                    }

                    if (window.showToast) window.showToast(`${acceptorName} accepted your request!`, 'success');
                } else if (typeof window.showToast === 'function') {
                    const toastType = data.type === 'friend_rejected' ? 'error' : 'success';
                    window.showToast(`${data.sender_name} ${data.message}`, toastType);
                }

                this.fetchFriends();
                this.searchUsers();
                this.fetchNotifications();
            });

            // Subscribe to movie update events
            const movieChannel = this.pusherClient.subscribe('movie-updates');

            movieChannel.bind('movie_changed', (data) => {
                // Refresh the movie list when any change occurs
                if (data.action === 'delete') {
                    const movieId = Number(data.movie_id);
                    this.movies = this.movies.filter(m => Number(m.id || m.movie_id) !== movieId);
                } else {
                    // For create/update, simply re-fetch the full list
                    this.fetchMovies();
                }
            });

            // Notify when someone replies to my comment
            channel.bind('comment_replied', (data) => {
                if (window.showToast) {
                    window.showToast(`${data.sender_name} replied to your comment: "${data.reply_text}"`, 'info');
                }
                // Optionally also add to notifications array
                this.notifications.unshift({
                    id: Date.now(),
                    type: 'comment_reply',
                    sender_id: null,
                    sender_name: data.sender_name,
                    message: `replied to your comment`,
                    created_at: data.created_at,
                    is_read: 0
                });
                this.unreadNotifCount++;
            });

            // Notify when someone likes my comment
            channel.bind('comment_liked_notification', (data) => {
                if (window.showToast) {
                    window.showToast(`${data.sender_name} liked your comment`, 'info');
                }
                this.notifications.unshift({
                    id: Date.now(),
                    type: 'comment_like',
                    sender_id: null,
                    sender_name: data.sender_name,
                    message: `liked your comment`,
                    created_at: data.created_at,
                    is_read: 0
                });
                this.unreadNotifCount++;
            });
        },

        async init() { 
            // Restore saved border
            const savedBorder = localStorage.getItem('activeBorder');
            if (savedBorder) {
                this.activeBorderId = parseInt(savedBorder, 10);
            }

            // --- Ensure CURRENT_USER_ID is set (critical for real-time) ---
            if (!window.CURRENT_USER_ID) {
                try {
                    const res = await fetch('/user_backend/get_current_user.php');
                    const data = await res.json();
                    if (data.user_id) {
                        window.CURRENT_USER_ID = data.user_id;
                    } else {
                        console.warn('No user ID returned from endpoint.');
                    }
                } catch (e) {
                    console.error('Failed to fetch current user ID', e);
                }
            }

            // --- Dynamically load Pusher if not already present ---
            if (typeof Pusher === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('Failed to load Pusher'));
                    document.head.appendChild(script);
                }).catch(err => {
                    console.error(err);
                });
            }
            // 1. Core Config
            if (typeof gsap !== 'undefined') gsap.config({ nullTargetWarn: false });

            // 2. Initial Data Fetches
            this.fetchReasons();
            await this.fetchMovies(); 
            await this.fetchWatchlist();
            this.fetchFriends();
            this.searchUsers();
            this.loadMissions();
            this.fetchNotifications();

            // 3. Real-Time Connections – only start if we have a valid user ID and Pusher is loaded
            if (window.CURRENT_USER_ID && typeof Pusher !== 'undefined') {
                this.initPusher();
                this.initAllChatSubscriptions();
            } else {
                console.warn('Real-time features disabled: missing user ID or Pusher');
            }

            // 4. Watchers & Interactions
            this.$watch('friends', () => this.updateFriendsCount());
            
            // Debounce search watcher
            this.$watch('searchQuery', (query) => {
                clearTimeout(this.searchTimeout);
                const trimmed = (query || '').trim();
                if (trimmed === '') { this.searchUsers(); return; }
                if (trimmed.length < 2) return;
                this.searchTimeout = setTimeout(() => this.searchUsers(), 300);
            });

            // Auto-trigger initial search when invite modal opens
            this.$watch('showInviteModal', (isOpen) => {
                if (isOpen) this.searchUsers(this.searchQuery);
            });

            // Watchers for Quests UI animations (Runs GSAP only when panel is toggled)
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

            // 5. Global Event Listeners
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isNavOpen) this.closeNav();
            });

            // 6. Initial GSAP UI Animations 
            // Uses a single $nextTick to ensure Alpine has finished rendering the HTML
            this.$nextTick(() => {
                
                // A. Animated Number Counters
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
                
                // B. Intro Animations
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

                // C. Split text animation for welcome header
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
                        { opacity: 1, y: 0, rotationX: 0, stagger: 0.04, duration: 0.7, ease: "back.out(2)", delay: 0.5 }
                    );
                }

                // D. Continuous pulse micro-animation for activity feed items
                gsap.to('.activity-item .dot-pulse', {
                    scale: 1.8,
                    opacity: 0,
                    repeat: -1,
                    duration: 1.5,
                    ease: "power2.out",
                    stagger: 0.3
                });
            });

            // 7. Interval Animations
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
                            gsap.set(randomStat, {x: 0, y: 0});
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
                container.scrollTop = container.scrollHeight;
            }
        }
    };
}


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
        timeLeft: 180, // 3 minutes in seconds
        timerInterval: null,
        isResending: false,
        init() {
            this.$nextTick(() => {
                this.inputs = Array.from(this.$el.querySelectorAll('#otp-inputs input'));
                if(this.inputs.length > 0) this.inputs[0].focus();
                this.startTimer();
            });
        },
        startTimer() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timeLeft = 180;
            this.timerInterval = setInterval(() => {
                if (this.timeLeft > 0) {
                    this.timeLeft--;
                } else {
                    clearInterval(this.timerInterval);
                }
            }, 1000);
        },
        get formattedTime() {
            const m = Math.floor(this.timeLeft / 60);
            const s = this.timeLeft % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },
        async resendOTP(url) {
            if (this.timeLeft > 0 || this.isResending) return;
            this.isResending = true;
            
            // GSAP Animation for resend button
            if (this.$refs.resendIcon) {
                gsap.to(this.$refs.resendIcon, { rotation: "+=360", duration: 1, ease: "power2.inOut" });
            }

            try {
                const res = await fetch(url, { method: 'POST' });
                // We're ignoring the response content to keep it simple, just reset timer
                if (res.ok) {
                    this.startTimer();
                    // trigger a toast if available
                    if (window.showToast) window.showToast('OTP Resent Successfully!', 'success');
                }
            } catch (e) {
                console.error(e);
                if (window.showToast) window.showToast('Failed to resend OTP', 'error');
            } finally {
                this.isResending = false;
            }
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
        currentMovieId: null,
        modalMode: 'add',
        formData: { name: '', price: '', rarity: '', image: null },
        comments: [],
        commentsLoading: false,
        commentError: '',
        commentFilter: '',
        movieComments: [],
        loadingMovieComments: false,

        buildCommentTree(flatComments) {
            if (!flatComments || !flatComments.length) return [];
            const map = {};
            const roots = [];
            flatComments.forEach(comment => {
                map[comment.id] = { ...comment, replies: [] };
            });
            flatComments.forEach(comment => {
                const parentId = comment.parent_id;
                if (parentId && map[parentId]) {
                    map[parentId].replies.push(map[comment.id]);
                } else {
                    roots.push(map[comment.id]);
                }
            });
            return roots;
        },

        get filteredComments() {
            if (!this.commentFilter.trim()) return this.nestedComments;
            const q = this.commentFilter.toLowerCase();
            const filterTree = (nodes) => {
                return nodes.filter(node => {
                    const nodeMatch = 
                        (node.user_name && node.user_name.toLowerCase().includes(q)) ||
                        (node.movie_title && node.movie_title.toLowerCase().includes(q)) ||
                        (node.comment_text && node.comment_text.toLowerCase().includes(q));
                    const childMatch = node.replies && node.replies.length > 0 ? filterTree(node.replies).length > 0 : false;
                    if (nodeMatch || childMatch) {
                        node.replies = node.replies ? filterTree(node.replies) : [];
                        return true;
                    }
                    return false;
                });
            };
            return filterTree(this.nestedComments);
        },

        get nestedComments() {
            return this.buildCommentTree(this.comments);
        },

        get nestedMovieComments() {
            return this.buildCommentTree(this.movieComments);
        },

        async fetchComments() {
            this.commentsLoading = true;
            this.commentError = '';
            try {
                const res = await fetch('/backend/comments_api.php');
                const data = await res.json();
                if (data.success) {
                    this.comments = data.comments;
                } else {
                    this.commentError = data.error || 'Failed to load comments';
                }
            } catch (e) {
                console.error(e);
                this.commentError = 'Network error loading comments';
            } finally {
                this.commentsLoading = false;
            }
        },

        async fetchMovieCommentsForAdmin(movieId) {
            this.loadingMovieComments = true;
            try {
                const res = await fetch(`/backend/comments_api.php?movie_id=${movieId}`);
                const data = await res.json();
                if (data.success) {
                    this.movieComments = data.comments;
                }
            } catch (e) {
                console.error('Failed to load movie comments:', e);
            } finally {
                this.loadingMovieComments = false;
            }
        },

        async deleteComment(commentId) {
            if (!confirm('Delete this comment and its replies?')) return;
            try {
                const res = await fetch('/backend/comments_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', comment_id: commentId })
                });
                const data = await res.json();
                if (data.success) {
                    // Simpler: refetch both lists to ensure nested replies are removed
                    await this.fetchComments();
                    if (this.currentMovieId) {
                        await this.fetchMovieCommentsForAdmin(this.currentMovieId);
                    }
                    this.showToast('Comment deleted', 'success');
                } else {
                    this.showToast(data.error || 'Delete failed', 'error');
                }
            } catch (e) {
                console.error(e);
                this.showToast('Network error', 'error');
            }
        },

        currentTab: 'dashboard',
        isNavOpen: false,
        notificationsOpen: false,
        unreadNotifications: 0,
        navItems: [
            { id: 'dashboard', label: 'Overview', icon: 'dashboard' },
            { id: 'users', label: 'Users', icon: 'group' },
            { id: 'movies', label: 'Movies', icon: 'movie' },
            { id: 'sessions', label: 'Watch Parties', icon: 'live_tv' },
            { id: 'shop', label: 'Avatar Shop', icon: 'storefront' },
            { id: 'reports', label: 'Reports', icon: 'flag' },
            { id: 'profile', label: 'Profile', icon: 'person' },
            { id: 'comments', label: 'Comments', icon: 'comment' }
        ],
        notifications: [],

        async fetchNotifications() {
            try {
                const response = await fetch('/user_backend/get_notifications.php');
                if (!response.ok) return;
                const rawText = await response.text();
                try {
                    const data = JSON.parse(rawText);
                    if (data.success && Array.isArray(data.notifications)) {
                        this.notifications = data.notifications;
                        this.unreadNotifCount = this.notifications.filter(n => Number(n.is_read) === 0).length;
                    }
                } catch (jsonErr) {
                    console.error('Notification JSON Parse Error. Raw response:', rawText);
                }
            } catch (err) {
                console.error('Notification network error:', err);
            }
        },

        markAllRead() {
            if (this.unreadNotifCount === 0) return;
            fetch('/user_backend/mark_notifications_read.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.unreadNotifCount = 0;
                        this.notifications = this.notifications.map(n => ({ ...n, is_read: 1 }));
                    }
                })
                .catch(err => console.error("Error marking read:", err));
        },

        stats: [],
        statsLoading: false,
        isLoading: false,
        errorMessage: '',
        searchQuery: '',
        roleFilter: 'All',
        banModalOpen: false,
        userToBan: null,
        banReason: '',
        banNotes: '',
        users: [],
        userCategoryTab: 'Users',

        get filteredUsers() {
            return (this.users || []).filter(u => {
                const searchMatch = u.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                    u.email.toLowerCase().includes(this.searchQuery.toLowerCase());
                const roleMatch = this.roleFilter === 'All' || u.role === this.roleFilter;
                const tabMatch = this.userCategoryTab === 'Moderators' 
                                  ? (u.role === 'Moderator' || u.role === 'Admin') 
                                  : (u.role !== 'Moderator' && u.role !== 'Admin');
                return searchMatch && roleMatch && tabMatch;
            });
        },

        async fetchStats() {
            this.statsLoading = true;
            try {
                const response = await fetch('/backend/dashboard_stats_api.php');
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (response.ok && Array.isArray(data)) {
                        this.stats = data;
                    } else {
                        console.error('Stats API Error:', data.error || 'Failed to load dashboard stats.');
                    }
                } catch (e) {
                    console.error('Invalid JSON response from stats API:', text);
                }
            } catch (err) {
                console.error('Network error fetching dashboard stats:', err);
            } finally {
                this.statsLoading = false;
            }
        },

        async fetchUsers() {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const response = await fetch('/backend/users_api.php');
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (response.ok) {
                        this.users = data;
                    } else {
                        this.errorMessage = data.error || 'Failed to load user directory.';
                    }
                } catch (e) {
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

        movies: [],
        availableGenres: [],
        movieModalOpen: false,
        editingMovie: false,
        movieTab: 'details',
        posterPreview: null,
        newMovie: {
            id: null,
            title: '',
            description: '',
            img_file: null,
            trailer: '',
            duration: '',
            genre_ids: []
        },

        async init() {
            await this.fetchMovies();
            await this.fetchGenres();
        },

        async fetchMovies() {
            this.isLoading = true;
            try {
                const response = await fetch(`/backend/movies_api.php?t=${Date.now()}`);
                const text = await response.text();
                const data = JSON.parse(text);
                if (response.ok) {
                    this.movies = data;
                }
            } catch (err) {
                console.error('Network error fetching movies:', err);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchGenres() {
            try {
                const response = await fetch('/backend/genres_api.php');
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (response.ok) {
                        this.availableGenres = data;
                    } else {
                        console.error("Failed to load genres:", data.error);
                    }
                } catch (e) {
                    console.error("Invalid JSON response for genres:", text);
                }
            } catch (err) {
                console.error("Network error fetching genres:", err);
            }
        },

        handlePosterSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.newMovie.img_file = file;
            if (this.posterPreview && this.posterPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.posterPreview);
            }
            this.posterPreview = URL.createObjectURL(file);
        },

        openAddMovieModal() {
            this.editingMovie = false;
            this.errorMessage = '';
            this.posterPreview = null;
            this.newMovie = { id: null, title: '', description: '', img_file: null, trailer: '', duration: '', genre_ids: [] };
            this.movieModalOpen = true;
        },

        async saveMovie() {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const formData = new FormData();
                formData.append('title', this.newMovie.title);
                formData.append('description', this.newMovie.description);
                formData.append('trailer', this.newMovie.trailer);
                formData.append('actual_video_url', this.newMovie.actual_video_url || '');
                formData.append('duration', this.newMovie.duration || '');
                formData.append('genre_ids', JSON.stringify(this.newMovie.genre_ids || []));
                if (this.editingMovie) {
                    formData.append('id', this.newMovie.id);
                    formData.append('action', 'update');
                } else {
                    formData.append('action', 'create');
                }
                if (this.newMovie.posterFile) {
                    formData.append('poster_image', this.newMovie.posterFile);
                } else if (this.newMovie.img_file) {
                    formData.append('poster_image', this.newMovie.img_file);
                }
                const response = await fetch('/backend/movies_api.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    if (data.movie) {
                        if (this.editingMovie) {
                            const index = this.movies.findIndex(m => (m.id || m.movie_id) === (data.movie.id || data.movie.movie_id));
                            if (index !== -1) {
                                this.movies.splice(index, 1, data.movie);
                            } else {
                                this.movies.push(data.movie);
                            }
                        } else {
                            this.movies.push(data.movie);
                        }
                        this.movies = [...this.movies];
                    } else {
                        await this.fetchMovies();
                    }
                    this.movieModalOpen = false;
                    this.showToast(this.editingMovie ? 'Movie updated successfully!' : 'Movie added successfully!', 'success');
                } else {
                    this.errorMessage = data.error || 'Failed to save movie.';
                }
            } catch (err) {
                console.error("Error saving movie:", err);
                this.errorMessage = 'Network error occurred while saving.';
            } finally {
                this.isLoading = false;
            }
        },

        async deleteMovie(id) {
            if (!confirm("Are you sure you want to delete this movie? This cannot be undone.")) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch('/backend/movies_api.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    await this.fetchMovies();
                    this.showToast('Movie deleted successfully.', 'success');
                } else {
                    alert(data.error || 'Failed to delete movie.');
                }
            } catch (err) {
                console.error("Error deleting movie:", err);
            }
        },

        showToast(msg, type = 'info') {
            if (window.showToast) {
                window.showToast(msg, type);
            } else {
                alert(msg);
            }
        },

        roomModalOpen: false,
        selectedRoom: null,
        rooms: [
            { id: 1, name: 'Sci-Fi Night', host: 'Alice', users: 5 },
            { id: 2, name: 'Horror Marathon', host: 'Bob', users: 12 },
            { id: 3, name: 'Anime Watch Party', host: 'Charlie', users: 8 },
            { id: 4, name: 'Classic Movies', host: 'Diana', users: 3 },
            { id: 5, name: 'Comedy Hour', host: 'Eve', users: 15 }
        ],
        mockRoomUsers: [{ id: 1, name: 'Alice', isHost: true, avatar: '' }, { id: 2, name: 'Charlie', isHost: false, avatar: '' }],
        mockRoomUsers2: [{ name: 'Alice', isHost: true }, { name: 'Charlie', isHost: false }],

        viewModalOpen: false,
        selectedReport: null,
        reportsList: [],
        reportStats: { total: 0, pending: 0, read: 0 },
        filterStatus: 'all',
        highlightCommentId: null,
        returnToReportsAfterMovieModal: false,

        async fetchReports() {
            try {
                const response = await fetch('/backend/get_reports.php');
                const data = await response.json();
                if (data.success) {
                    this.reportsList = data.reports;
                    this.updateReportStats();
                }
            } catch (error) {
                console.error("Error fetching reports:", error);
            }
        },

        updateReportStats() {
            this.reportStats.total = this.reportsList.length;
            this.reportStats.pending = this.reportsList.filter(r => r.status === 'Pending').length;
            this.reportStats.read = this.reportsList.filter(r => r.status === 'Read').length;
        },

        async viewReport(report) {
            this.selectedReport = report;
            this.viewModalOpen = true;
            if (report.status === 'Pending') {
                try {
                    const res = await fetch('/backend/update_report_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ report_id: report.id, status: 'Read' })
                    });
                    const data = await res.json();
                    if (data.success) {
                        report.status = 'Read';
                        this.updateReportStats();
                    }
                } catch (e) {
                    console.error("Failed to mark report as read:", e);
                }
            }
        },

        async resolveReport() {
            if (!this.selectedReport) return;
            try {
                const res = await fetch('/backend/update_report_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: this.selectedReport.id, status: 'Resolved' })
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedReport.status = 'Resolved';
                    this.viewModalOpen = false;
                    this.updateReportStats();
                    if (window.showToast) window.showToast('Report marked as resolved.', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to resolve report.', 'error');
                }
            } catch (e) {
                console.error("Failed to resolve report:", e);
                if (window.showToast) window.showToast('Network error while resolving report.', 'error');
            }
        },

        get filteredReports() {
            if (this.filterStatus === 'all') return this.reportsList;
            return this.reportsList.filter(report => report.status.toLowerCase() === this.filterStatus);
        },

        async handleViewComment(detail) {
            const movieId = detail.movie_id;
            const commentId = detail.comment_id;
            if (!movieId) return;
            this.returnToReportsAfterMovieModal = true;
            this.switchTab('movies');
            if (!this.movies.length) {
                await this.fetchMovies();
            }
            const movie = this.movies.find(m => (m.id || m.movie_id) == movieId);
            if (!movie) {
                if (window.showToast) window.showToast('Movie not found.', 'error');
                this.returnToReportsAfterMovieModal = false;
                this.currentMovieId = null;
                return;
            }
            this.openEditMovieModal(movie);
            await this.fetchMovieCommentsForAdmin(movieId);
            window.dispatchEvent(new CustomEvent('admin-show-comment', {
                detail: { commentId: commentId }
            }));
        },

        modalOpen: false,
        formData: { name: '', price: 0, rarity: 'Common', image: '' },
        shopItems: [{ id: 1, name: 'Gold Border', price: 500, rarity: 'Legendary' }],
        selectedAvatar: null,
        selectedBorder: null,
        avatarModalOpen: false,
        borders: [],
        deleteAccountModalOpen: false,
        deleteAccountPassword: '',
        deleteAccountError: '',
        selectedAvatar: `https://ui-avatars.com/api/?name=${encodeURIComponent(userData.user_name || 'Admin')}&background=ef4444&color=fff&bold=true`,
        notification: { show: false, type: 'error', message: '' },

        showNotification(message, type = 'error') {
            this.notification.message = message;
            this.notification.type = type;
            this.notification.show = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        displayName: userData.user_name || 'Admin',
        displayEmail: userData.email || '',
        profile: {
            user_name: userData.user_name || '',
            email: userData.email || '',
            current_password: '',
            new_password: '',
            confirm_password: ''
        },

        async saveProfile() {
            this.notification.show = false;
            const errors = typeof window.validateProfileForm === 'function' ? window.validateProfileForm(this.profile) : [];
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
                    this.displayName = this.profile.user_name;
                    this.displayEmail = this.profile.email;
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
            this.deleteAccountError = '';
            this.notification.show = false;
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
                    const errorMsg = data.message || data.error || 'Failed to delete account.';
                    this.deleteAccountError = errorMsg;
                    this.showNotification(errorMsg, 'error');
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
                        window.gsap.set(newPanel, { opacity: 0, y: 50, scale: 0.95, rotationX: 15, filter: "blur(15px)", transformPerspective: 1000 });
                        window.gsap.to(newPanel, { opacity: 1, y: 0, scale: 1, rotationX: 0, filter: "blur(0px)", duration: 0.8, ease: "expo.out" });
                        const staggers = newPanel.querySelectorAll('.gs-stat-card, .gs-table-row, .stagger-item, tbody tr, .card, .glass-card, .movie-card-container');
                        if (staggers.length > 0) {
                            window.gsap.fromTo(staggers,
                                { opacity: 0, y: 40, scale: 0.9, rotationX: -15, transformPerspective: 1000 },
                                { opacity: 1, y: 0, scale: 1, rotationX: 0, duration: 0.8, stagger: 0.05, ease: "back.out(1.5)", delay: 0.1 }
                            );
                        }
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

        toggleGenre(genre) {
            if (!Array.isArray(this.newMovie.genre_ids)) {
                this.newMovie.genre_ids = [];
            }
            const val = typeof genre === 'object' ? genre.id : genre;
            const index = this.newMovie.genre_ids.indexOf(val);
            if (index > -1) {
                this.newMovie.genre_ids.splice(index, 1);
            } else {
                this.newMovie.genre_ids.push(val);
            }
        },

        isGenreSelected(genre) {
            if (!this.newMovie || !Array.isArray(this.newMovie.genre_ids)) return false;
            const val = typeof genre === 'object' ? genre.id : genre;
            return this.newMovie.genre_ids.includes(val);
        },

        openEditMovieModal(movie) {
            this.editingMovie = true;
            this.movieTab = 'details';
            this.newMovie = JSON.parse(JSON.stringify(movie));
            this.currentMovieId = movie.id || movie.movie_id;

            if (!Array.isArray(this.newMovie.genre_ids)) {
                if (typeof this.newMovie.genre_ids === 'string' && this.newMovie.genre_ids.trim() !== '') {
                    this.newMovie.genre_ids = this.newMovie.genre_ids
                        .split(',')
                        .map(id => parseInt(id.trim(), 10))
                        .filter(id => !isNaN(id));
                } else {
                    this.newMovie.genre_ids = [];
                }
            }
            this.newMovie.img = movie.img || movie.poster_url || movie.poster || movie.image || '';
            this.posterPreview = this.newMovie.img;
            this.movieModalOpen = true;
            this.fetchMovieCommentsForAdmin(this.newMovie.id);
        },

        openBanModal(user) {
            this.userToBan = user;
            this.banReason = '';
            this.banNotes = '';
            this.banModalOpen = true;
        },

        async postUserAction(payload) {
            const endpoints = ['/user_backend/user_action.php', '/backend/user_action.php'];
            let lastError = null;
            for (const endpoint of endpoints) {
                try {
                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const raw = await res.text();
                    if (res.status === 404) {
                        console.warn(`User action endpoint not found: ${endpoint}`);
                        continue;
                    }
                    let data;
                    try {
                        data = JSON.parse(raw);
                    } catch (parseError) {
                        console.error(`Non-JSON response from ${endpoint}:`, raw);
                        throw new Error('Invalid JSON response from server');
                    }
                    return data;
                } catch (err) {
                    lastError = err;
                    if (err?.message === 'Invalid JSON response from server') {
                        throw err;
                    }
                }
            }
            throw (lastError || new Error('user_action.php endpoint was not found.'));
        },

        async confirmBan() {
            if (!this.userToBan || !this.banReason) return;
            try {
                const data = await this.postUserAction({
                    action: 'ban',
                    id: this.userToBan.id,
                    reason: this.banReason,
                    notes: this.banNotes
                });
                if (data.success) {
                    this.userToBan.status = 'Banned';
                    window.showToast(data.message || 'User suspended', 'success');
                } else {
                    window.showToast(data.error || 'Failed to ban user', 'error');
                }
            } catch (e) {
                console.error('Ban error:', e);
                window.showToast(e.message || 'Network error', 'error');
            } finally {
                this.banModalOpen = false;
            }
        },

        async promoteModerator(user) {
            try {
                const data = await this.postUserAction({ action: 'promote_moderator', id: user.id });
                if (data.success) {
                    user.role = 'Moderator';
                    user.role_id = 3;
                    this.users = [...this.users];
                    window.showToast(data.message || 'User promoted', 'success');
                } else {
                    window.showToast(data.error || 'Failed to promote user', 'error');
                }
            } catch (e) {
                console.error('Promote error:', e);
                window.showToast(e.message || 'Network error', 'error');
            }
        },

        async demoteModerator(user) {
            try {
                const data = await this.postUserAction({ action: 'demote_moderator', id: user.id });
                if (data.success) {
                    user.role = 'User';
                    user.role_id = 2;
                    this.users = [...this.users];
                    window.showToast(data.message || 'Moderator demoted', 'success');
                } else {
                    window.showToast(data.error || 'Failed to demote user', 'error');
                }
            } catch (e) {
                console.error('Demote moderator error:', e);
                window.showToast(e.message || 'Network error', 'error');
            }
        },

        async demoteAdmin(user) {
            try {
                const data = await this.postUserAction({ action: 'demote_admin', id: user.id });
                if (data.success) {
                    user.role = 'Moderator';
                    user.role_id = 3;
                    this.users = [...this.users];
                    window.showToast(data.message || 'Admin demoted to Moderator', 'success');
                } else {
                    window.showToast(data.error || 'Failed to demote admin', 'error');
                }
            } catch (e) {
                console.error('Demote admin error:', e);
                window.showToast(e.message || 'Network error', 'error');
            }
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

        async compressImage(file, maxWidth = 800, quality = 0.8) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth) {
                            height = height * (maxWidth / width);
                            width = maxWidth;
                        }
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            resolve(new File([blob], file.name.replace(/\.[^/.]+$/, '.jpg'), { type: 'image/jpeg' }));
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = reject;
                    img.src = e.target.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        async handleFileUpload(event, callback) {
            const file = event.target.files[0];
            if (!file) return;
            try {
                const compressedFile = await this.compressImage(file);
                this.newMovie.posterFile = compressedFile;
                const reader = new FileReader();
                reader.onload = (e) => callback(e.target.result);
                reader.readAsDataURL(compressedFile);
            } catch (err) {
                this.newMovie.posterFile = file;
                const reader = new FileReader();
                reader.onload = (e) => callback(e.target.result);
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

        isYouTubeUrl(url) {
            if (!url) return false;
            return url.includes('youtube.com') || url.includes('youtu.be');
        },

        getYouTubeEmbedUrl(url, isHover = false) {
            if (!url) return '';
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
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

        initDashboard() {
            this.fetchReports();
            this.fetchStats();
            this.fetchMovies();
            this.fetchGenres();
            this.fetchUsers();
            this.fetchNotifications();
            this.initPusher();
            this.fetchComments();
            this.$watch('movieModalOpen', (isOpen) => {
                if (!isOpen) {
                    this.currentMovieId = null;
                    if (this.returnToReportsAfterMovieModal) {
                        this.switchTab('reports');
                        this.returnToReportsAfterMovieModal = false;
                    }
                }
            });
        },

        initPusher() {
            if (!window.CURRENT_USER_ID || typeof Pusher === 'undefined') return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            // User-specific channel
            const userChannel = this.pusherClient.subscribe(`user-${window.CURRENT_USER_ID}`);
            userChannel.bind('force_logout', (data) => {
                alert(data.message || 'Your account has been banned.');
                window.location.href = '/backend/logout.php';
            });

            // Admin comments channel
            const adminCommentsChannel = this.pusherClient.subscribe('admin-comments');

            // Helper to update likes in a flat array
            const updateLikeInArray = (arr, commentId, likesCount, isLiked) => {
                const comment = arr.find(c => Number(c.id) === Number(commentId));
                if (comment) {
                    comment.likes_count = likesCount;
                    comment.is_liked = isLiked;
                    return true;
                }
                return false;
            };

            // 1. New comment
            adminCommentsChannel.bind('new_comment', (data) => {
                const newComment = {
                    id: data.id,
                    movie_id: data.movie_id,
                    user_name: data.user_name,
                    movie_title: data.movie_title,
                    comment_text: data.comment_text,
                    created_at: data.created_at,
                    likes_count: data.likes_count || 0,
                    is_liked: false,
                    parent_id: null,
                    replies: []
                };

                // Add to global flat list
                this.comments.unshift(newComment);
                this.comments = [...this.comments];

                // If the comment belongs to currently open movie, add to movieComments
                if (this.currentMovieId && Number(data.movie_id) === Number(this.currentMovieId)) {
                    this.movieComments.unshift(newComment);
                    this.movieComments = [...this.movieComments];
                }
            });

            // 2. New reply
            adminCommentsChannel.bind('new_reply', (data) => {
                const newReply = {
                    id: data.id,
                    movie_id: data.movie_id,
                    parent_id: data.parent_id,
                    user_name: data.user_name,
                    comment_text: data.comment_text,
                    created_at: data.created_at,
                    likes_count: data.likes_count || 0,
                    is_liked: false,
                    replies: []
                };

                // Add to global flat list
                this.comments.unshift(newReply);
                this.comments = [...this.comments];

                // Add to movie-specific list if matching
                if (this.currentMovieId && Number(data.movie_id) === Number(this.currentMovieId)) {
                    this.movieComments.unshift(newReply);
                    this.movieComments = [...this.movieComments];
                }
            });

            // 3. Comment liked/unliked
            adminCommentsChannel.bind('comment_liked', (data) => {
                const { comment_id, likes_count, is_liked } = data;

                // Update global flat list
                updateLikeInArray(this.comments, comment_id, likes_count, is_liked);
                this.comments = [...this.comments];

                // Update movie-specific flat list if movie open
                if (this.currentMovieId) {
                    updateLikeInArray(this.movieComments, comment_id, likes_count, is_liked);
                    this.movieComments = [...this.movieComments];
                }
            });

            // Admin moderation channel
            const adminModerationChannel = this.pusherClient.subscribe('admin-moderation-channel');
            adminModerationChannel.bind('new-report-event', (data) => {
                if (data && data.report) {
                    this.reportsList.unshift(data.report);
                    this.updateReportStats();
                }
                if (data.notification) {
                    this.notifications.unshift(data.notification);
                    this.unreadNotifications = (this.unreadNotifications || 0) + 1;
                }
                if (window.showToast) window.showToast('New report submitted!', 'info');
            });
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