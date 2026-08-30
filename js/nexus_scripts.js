(function () {
    if (typeof window !== 'undefined' && typeof window.gsapSafe === 'undefined') {
        const noopTween = { to: () => noopTween, fromTo: () => noopTween, play: () => {}, pause: () => {}, kill: () => {}, add: () => noopTween, set: () => noopTween };
        const safe = {
            to: () => noopTween,
            fromTo: () => noopTween,
            from: () => noopTween,
            set: () => {},
            timeline: () => noopTween,
            config: () => {},
            killTweensOf: () => {},
            del: () => {},
            setProperty: () => {},
            getProperty: () => null,
            utils: {},
            plugins: {}
        };
        Object.defineProperty(window, 'gsapSafe', {
            configurable: true,
            get() { return typeof gsap !== 'undefined' ? gsap : safe; }
        });
    }
})();

function userDashboard() {
    return {
        // Navigation & Tab State
        currentTab: 'dashboard',
        isNavOpen: false,
        returnToWatchlistAfterClose: false,
        
        // Chat State (existing)
        showChatPanel: false,
        activeChatFriend: null,
        chatMessages: [],
        chatInput: '',
        selectedImageFile: null,
        selectedImagePreview: null,
        
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
        showPremiumModal: false,
        friendsTab: 'connected',
        movieModalOpen: false,
        localLikedComments: new Set(JSON.parse(localStorage.getItem('nexus_liked_comments') || '[]')),
        editingMovie: false,
        viewModalOpen: false,
        roomModalOpen: false,
        modalOpen: false,
        modalMode: 'add',

        // Form & Data Objects
        selectedReport: null,
        selectedRoom: null,

         // Shop & Points
        formData: { name: '', price: 0, rarity: 'Common', image: '' },      
        userPoints: 0,
        userInventory: [],
        shopItems: [],
        showConfirmModal: false,  
        selectedItem: null,        

        // Movie State & Modals
        movies: [],
        movieSearchQuery: '',
        selectedMovie: null,
        viewRecorded: false,        // reset each time a new movie detail is opened
        viewThresholdSeconds: 10,   // minimum watch time before counting a view 
        newRating: 0,
        hoveredRating: 0,
        commentText: '',
        isSubmittingReview: false,
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

       // Account State
        accountForm: { },
        passwordForm: { current: '', new: '', confirm: '' },
        activeBorderId: 0,
        availableBorders: [],
        hasCustomAvatar: false,
        selectedAvatar: '',   

        resolveShopImage(image) {
            if (!image) return '';
            if (/^(https?:)?\/\//i.test(image) || image.startsWith('/') || image.startsWith('data:') || image.startsWith('blob:')) {
                return image;
            }
            return '/uploads/shop/' + image;
        },

        buildAvailableBorders() {
            const borderItems = this.shopItems.filter(item => item.category === 'border');
            const ownedIds = new Set(this.userInventory);
            this.availableBorders = [
                { id: 0, name: 'None', preview: '', owned: true },
                ...borderItems.map(item => ({
                    id: item.id,
                    name: item.name,
                    preview: item.image,
                    owned: ownedIds.has(item.id)
                }))
            ];
        },

       async fetchUserProfile() {
            try {
                const res = await fetch('/user_backend/get_user_profile.php');
                const data = await res.json();
                if (data.success) {
                    this.userPoints = data.points;
                    this.userInventory = data.inventory;
                    this.activeBorderId = data.active_border_id;
                    if (data.avatar_url) {
                        this.selectedAvatar = data.avatar_url;
                        this.savedProfile.avatar_url = data.avatar_url;
                        this.hasCustomAvatar = true;
                    } else {
                        // Fallback to generated avatar
                        this.selectedAvatar = this.getAvatarUrl(this.savedProfile.username);
                        this.savedProfile.avatar_url = '';
                        this.hasCustomAvatar = false;
                    }
                    this.buildAvailableBorders();
                    
                    // Force reset if active border is not owned
                    const currentBorder = this.availableBorders.find(b => b.id === this.activeBorderId);
                    if (this.activeBorderId !== 0 && (!currentBorder || !currentBorder.owned)) {
                        this.activeBorderId = 0;
                        // Optionally persist the reset to backend
                        fetch('/user_backend/update_active_border.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ border_id: 0 })
                        }).catch(() => {});
                    }
                }
            } catch (e) {
                console.error('Failed to fetch profile:', e);
            }
        },

        async setActiveBorder(borderId) {
            const border = this.availableBorders.find(b => b.id === borderId);
            if (!border || !border.owned) {
                if (window.showToast) window.showToast('You do not own this border', 'error');
                return;
            }

            // Optimistic UI
            this.activeBorderId = borderId;
            if (window.showToast) window.showToast(`${border.name} border applied!`, 'success');

            // Persist to backend
            try {
                await fetch('/user_backend/update_active_border.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ border_id: borderId })
                });
            } catch (e) {
                console.error('Failed to save border:', e);
            }
        },

       async uploadAvatar(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const res = await fetch('/user_backend/upload_avatar.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedAvatar = data.avatar_url;
                    this.savedProfile.avatar_url = data.avatar_url;
                    this.hasCustomAvatar = true;   // <-- add this
                    if (window.showToast) window.showToast('Profile picture updated!', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Upload failed', 'error');
                }
            } catch (e) {
                console.error('Avatar upload error:', e);
                if (window.showToast) window.showToast('Network error', 'error');
            }
        },

        async removeAvatar() {
            try {
                const res = await fetch('/user_backend/remove_avatar.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    // Fall back to generated avatar
                    this.selectedAvatar = this.getAvatarUrl(this.savedProfile.username);
                    this.savedProfile.avatar_url = '';
                    this.hasCustomAvatar = false;
                    if (window.showToast) window.showToast('Profile picture removed', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to remove avatar', 'error');
                }
            } catch (e) {
                console.error('Remove avatar error:', e);
                if (window.showToast) window.showToast('Network error', 'error');
            }
        },

        // --- Report Item Modal State ---
        showReportItemModal: false,
        selectedItemIdToReport: null,
        reportItemType: 'reply', // 'reply' or 'comment'
        reportItemDescription: '',
        selectedItemReasonIds: [],

        //Premium Plan
        isPremium: false,
        isActivating: false,
        justPaid: false,

        get isGuest() {
            return window.NEXUS_USER?.isGuest === true;
        },

        requireLogin() {
            if (this.isGuest) {
                if (window.showToast) window.showToast('Please login to continue', 'info');
                return false;
            }
            return true;
        },

        

        async activatePremium() {
            if (this.isActivating) return;
            this.isActivating = true;

            if (typeof gsap !== 'undefined') {
                const tl = gsap.timeline();
                tl.to('.premium-btn', { scale: 0.9, duration: 0.2 })
                .to('.premium-btn', { scale: 1.1, duration: 0.1, yoyo: true, repeat: 3 })
                .to('.premium-btn', { opacity: 0, scale: 0, duration: 0.4, ease: 'back.in(1.5)' });
                tl.to('.premium-features', { opacity: 0, y: -20, duration: 0.3, stagger: 0.1 }, '-=0.4');
                tl.to('.premium-card', { boxShadow: '0 0 100px rgba(99,102,241,0)', duration: 0.5 }, '-=0.5');
                tl.fromTo('.premium-loader', { scale: 0, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.5, ease: 'elastic.out(1, 0.5)' });
            }

            try {
                const res = await fetch('/user_backend/create_checkout_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'premium' })
                });
                const data = await res.json();
                if (!data.id) throw new Error(data.error || 'Failed to create session');

                const stripe = Stripe('pk_test_51U7dOOQ4txrxX3UyKFl8Esnat3ahKw22hUWtA1HpDKSozJXz9UBofzTjLNreSIOlt8sN6WM4gkS8PCw2k7fuqhUO00CcN5mWd8');
                const { error } = await stripe.redirectToCheckout({ sessionId: data.id });
                if (error) throw error;
            } catch (err) {
                console.error('Payment error:', err);
                if (window.showToast) window.showToast('Payment failed to start.', 'error');
                if (typeof gsap !== 'undefined') {
                    gsap.to('.premium-loader', { opacity: 0, scale: 0, duration: 0.3 });
                    gsap.to('.premium-btn', { opacity: 1, scale: 1, duration: 0.3, delay: 0.3 });
                    gsap.to('.premium-features', { opacity: 1, y: 0, duration: 0.3, stagger: 0.1, delay: 0.3 });
                }
            } finally {
                this.isActivating = false;
            }
        },

        initPremium() {
            if (typeof gsap === 'undefined') return;
            if (this.isPremium) {
                gsap.set('.premium-success-icon', { opacity: 1, scale: 1, rotation: 0 });
                gsap.set('.premium-welcome-text', { opacity: 1, y: 0 });
                return;
            }
            gsap.fromTo('.premium-badge', { y: -20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: 'elastic.out(1, 0.5)', delay: 0.2 });
            gsap.fromTo('.premium-title', { y: 20, opacity: 0, scale: 0.9 }, { y: 0, opacity: 1, scale: 1, duration: 0.8, ease: 'power3.out', delay: 0.3 });
            gsap.fromTo('.premium-desc', { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: 'power3.out', delay: 0.5 });
            gsap.fromTo('.premium-features li', { x: -30, opacity: 0, scale: 0.9 }, { x: 0, opacity: 1, scale: 1, duration: 0.6, stagger: 0.1, ease: 'back.out(1.2)', delay: 0.7 });
            gsap.fromTo('.premium-btn', { scale: 0, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.8, ease: 'elastic.out(1, 0.4)', delay: 1.2 });
        },

        async checkPaymentStatus() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('payment') === 'success') {
                const sessionId = params.get('session_id');
                if (sessionId) {
                    try {
                        const res = await fetch(`/user_backend/verify_payment.php?session_id=${sessionId}`);
                        const data = await res.json();
                        if (data.success) {
                            this.justPaid = true;   // mark that payment was just completed
                            if (window.showToast) window.showToast('Payment successful! Premium activated.', 'success');
                        } else {
                            if (window.showToast) window.showToast(data.message || 'Payment verification failed.', 'error');
                        }
                    } catch (e) {
                        console.error('Verification error:', e);
                    }
                }
                history.replaceState({}, document.title, window.location.pathname);
            } else if (params.get('payment') === 'cancelled') {
                if (window.showToast) window.showToast('Payment cancelled.', 'info');
                history.replaceState({}, document.title, window.location.pathname);
            }
        },

       async fetchPremiumStatus() {
            try {
                const res = await fetch('/user_backend/get_premium_status.php');
                const data = await res.json();
                if (data.success) {
                    this.isPremium = data.is_premium;
                    localStorage.setItem('nexus_premium', data.is_premium ? 'true' : 'false');
                    if (window.NEXUS_USER) window.NEXUS_USER.is_premium = data.is_premium;
                }
            } catch (e) {
                console.error('Premium status check failed', e);
            }
        },

        getAvatarUrl(name, background = 'ef4444') {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'User')}&background=${background}&color=fff&bold=true`;
        },

       // Live filtered movies getter
        get filteredMovies() {
            if (!this.movieSearchQuery.trim()) return this.movies;
            const query = this.movieSearchQuery.toLowerCase();
            return this.movies.filter(movie => {
                const titleMatch = movie.title && movie.title.toLowerCase().includes(query);
                let genres = movie.genres;
                if (typeof genres === 'string') genres = genres.split(',').map(s => s.trim()).filter(Boolean);
                const genreMatch = Array.isArray(genres) && genres.some(g => (g || '').toLowerCase().includes(query));
                return titleMatch || genreMatch;
            });
        },

        // Normalize movies returned by the API: ensure genres=array, add year/genre helpers
        _normalizeMovie(m) {
            if (!m || typeof m !== 'object') return m;
            let genres = m.genres;
            if (typeof genres === 'string') genres = genres.split(',').map(s => s.trim()).filter(Boolean);
            if (!Array.isArray(genres)) genres = [];
            const year = m.year || (m.created_at ? new Date(m.created_at).getFullYear() : 2024);
            return {
                ...m,
                genres,
                year,
                genre: m.genre || genres[0] || 'Movie'
            };
        },

        // API Fetching
        async fetchMovies() {
            try {
                const response = await fetch('/user_backend/movies_api.php');
                if (response.status === 401) {
                    if (this.isGuest) {
                        if (window.showToast) window.showToast('Unable to load movies. Please try again.', 'error');
                        return;
                    }
                    window.location.href = '/frontend/login.php';
                    return;
                }
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                const data = await response.json();

                let rawMovies = [];
                if (Array.isArray(data)) {
                    rawMovies = data;
                } else if (data && typeof data === 'object' && data.success !== undefined) {
                    if (data.success && Array.isArray(data.movies)) {
                        rawMovies = data.movies;
                    } else {
                        throw new Error(data.message || 'Failed to load movies');
                    }
                } else {
                    throw new Error('Invalid response format');
                }

                this.movies = rawMovies.map(m => this._normalizeMovie(m));
                this.syncWatchlistState();
            } catch (e) {
                console.error("Failed to load movies from database:", e);
                this.movieError = "Failed to load movies. Please try again.";
                this.movies = [];
            }
        },

       async recordView(movieId) {
            if (!movieId || this.viewRecorded) return;

            try {
                const res = await fetch('/user_backend/record_view.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ movie_id: movieId })
                });
                const data = await res.json();
                if (data.success) {
                    // Update modal
                    if (this.selectedMovie && this.getMovieId(this.selectedMovie) === movieId) {
                        this.selectedMovie.view_count = data.view_count;
                    }
                    // Update main movies array
                    const movieInList = this.movies.find(m => this.getMovieId(m) === movieId);
                    if (movieInList) {
                        movieInList.view_count = data.view_count;
                    }
                }
                // Always mark as recorded to prevent repeated calls
                this.viewRecorded = true;
            } catch (e) {
                console.error('Failed to record view:', e);
                this.viewRecorded = true; // prevent repeated attempts on network error
            }
        },

        // Detect if URL is from YouTube
        isYouTubeUrl(url) {
            if (!url) return false;
            return url.includes('youtube.com') || url.includes('youtu.be');
        },

        // --- Account Methods ---
        // Initialized synchronously from NEXUS_USER so the correct name is
        // shown on the very first render, without waiting for init().
        savedProfile: {
            username: '...',   
            email: ''
        },
        deleteAccountModalOpen: false,
        deleteAccountPassword: '',
        deleteAccountError: '',

        async updateAccountInfo() {
            if (!this.accountForm.username || !this.accountForm.email) {
                if (window.showToast) window.showToast('Please fill all fields.', 'error');
                return;
            }

            try {
                const res = await fetch('/user_backend/update_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: this.accountForm.username,
                        email: this.accountForm.email
                    })
                });

                const data = await res.json();
               if (data.success) {
                    // Update the saved profile (committed values)
                    this.savedProfile = {
                        username: this.accountForm.username,
                        email: this.accountForm.email
                    };

                    // Optional: update global
                    if (window.NEXUS_USER) {
                        window.NEXUS_USER.username = this.savedProfile.username;
                        window.NEXUS_USER.email = this.savedProfile.email;
                    }

                    if (window.showToast) window.showToast(data.message || 'Profile updated!', 'success');
                }
            } catch (e) {
                console.error('Update profile error:', e);
                if (window.showToast) window.showToast('Network error. Please try again.', 'error');
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

                const data = await res.json();
                if (data.success) {
                    this.passwordForm = { current: '', new: '', confirm: '' };
                    if (window.showToast) window.showToast(data.message || 'Password updated!', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Update failed.', 'error');
                }
            } catch (e) {
                console.error('Update password error:', e);
                if (window.showToast) window.showToast('Network error. Please try again.', 'error');
            }
        },

        openDeleteAccountModal() {
            this.deleteAccountModalOpen = true;
            this.deleteAccountPassword = '';
            this.deleteAccountError = '';
        },

        closeDeleteAccountModal() {
            this.deleteAccountModalOpen = false;
            this.deleteAccountPassword = '';
            this.deleteAccountError = '';
        },

        async confirmDeleteAccount() {
            if (!this.deleteAccountPassword) {
                this.deleteAccountError = 'Please enter your password.';
                return;
            }

            try {
                const res = await fetch('/user_backend/delete_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: this.deleteAccountPassword })
                });

                const data = await res.json();

                if (data.success) {
                    if (window.showToast) window.showToast(data.message || 'Account deleted.', 'success');
                    // Redirect to login page after short delay
                    setTimeout(() => {
                        window.location.href = '/frontend/login.php';
                    }, 1500);
                } else {
                    this.deleteAccountError = data.message || 'Failed to delete account.';
                }
            } catch (e) {
                console.error('Delete account error:', e);
                this.deleteAccountError = 'Network error. Please try again.';
            }
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
            this.viewRecorded = false; 
            this.newRating = movie?.user_rating ? parseInt(movie.user_rating, 10) : 0;
            this.hoveredRating = 0;
            this.commentText = '';
            this.isSubmittingReview = false;
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
        statsLoading: true, stats: [
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
            this.statsLoading = true;
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
            } finally {
                this.statsLoading = false;
            }
        },

        // Fetch Friends & Incoming Pending Requests
        async fetchFriends(retries = 1) {
            try {
                const response = await fetch('/user_backend/get_friends.php');
                if (response.status === 401) {
                    if (this.isGuest) {
                        if (window.showToast) window.showToast('Unable to load movies. Please try again.', 'error');
                        return;
                    }
                    window.location.href = '/frontend/login.php';
                    return;
                }
                if (!response.ok) throw new Error('Failed to fetch friends');
                
                const data = await response.json();
                
                if (data && !data.error) {
                    this.friends = data.friends || [];
                    this.pendingRequests = data.pending_requests || [];
                    this.updateFriendsCount();
                    this.initAllChatSubscriptions();
                }
            } catch (err) {
                if (err.name === 'AbortError') return;
                if (retries > 0) {
                    setTimeout(() => this.fetchFriends(retries - 1), 1000);
                    return;
                }
                console.warn('Could not fetch friends (transient network error):', err.message);
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
            if (this.isGuest && ['watchlist', 'account', 'shop'].includes(tabId)) {
                window.location.href = '/frontend/login.php';
                return;
            }

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
            
            const sidePanel = this.$root.querySelector('#side-panel');
            const navOverlay = this.$root.querySelector('#nav-overlay');
            if (sidePanel) sidePanel.style.pointerEvents = 'auto';
            if (navOverlay) navOverlay.style.pointerEvents = 'auto';
            
            if (typeof gsap === 'undefined') return;
            
            const tl = gsap.timeline();
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
            
            const sidePanel = this.$root.querySelector('#side-panel');
            const navOverlay = this.$root.querySelector('#nav-overlay');
            
            if (typeof gsap === 'undefined') {
                if (sidePanel) sidePanel.style.pointerEvents = 'none';
                if (navOverlay) navOverlay.style.pointerEvents = 'none';
                return;
            }
            
            const tl = gsap.timeline({
                onComplete: () => {
                    if (sidePanel) sidePanel.style.pointerEvents = 'none';
                    if (navOverlay) navOverlay.style.pointerEvents = 'none';
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
            if (typeof Pusher === 'undefined' || !currentUserId || !targetFriendId) {
                console.warn('Chat subscription skipped: missing Pusher or IDs');
                return;
            }

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            const minId = Math.min(currentUserId, targetFriendId);
            const maxId = Math.max(currentUserId, targetFriendId);
            const channelName = `chat-${minId}-${maxId}`;

            if (this.activeSubscriptions.has(channelName)) {
                console.log(`Already subscribed to ${channelName}`);
                return;
            }
            this.activeSubscriptions.add(channelName);

            const channel = this.pusherClient.subscribe(channelName);
            console.log(`Subscribed to ${channelName}`);


            channel.bind('new_message', (data) => {
                const senderId = Number(data.sender_id);
                if (senderId === Number(window.CURRENT_USER_ID)) return;

                const activeFriendId = Number(this.activeChatFriend?.user_id || this.activeChatFriend?.friend_id || this.activeChatFriend?.id);
                const isCurrentActiveChat = this.showChatPanel && activeFriendId === senderId;

                if (isCurrentActiveChat) {
                    this.chatMessages = [...this.chatMessages, {
                        id: data.message_id || data.id || 'live-' + Date.now(),
                        sender: 'them',
                        text: data.message_text || '',
                        message_type: data.message_type || 'text',
                        image_url: data.image_url || null,
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

            const historyPromise = this.fetchChatHistory(friendId);
            fetch('/user_backend/mark_as_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sender_id: friendId })
            }).catch(() => {});
            await historyPromise;
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
            // Fallback to the currently selected dropdown user
            const targetUser = user || this.selectedProfileUser;
            
            if (!targetUser) return;
            
            this.selectedProfileUser = targetUser;
            this.reportTab = 'select'; 
            this.selectedReasonIds = []; 
            this.reportDescription = ''; 
            this.showReportModal = true;
            this.activeDropdown = null; // Close option dropdown when modal opens
        },

        closeReportModal() {
            this.showReportModal = false;
            setTimeout(() => {
                this.selectedProfileUser = null;
                this.selectedReasonIds = [];
                this.reportDescription = '';
            }, 300);
        },

        openReportItemModal(id, type) {
            console.log('Opening item report modal:', { id, type }); // optional debug
            this.selectedItemIdToReport = id;
            this.reportItemType = type;
            this.reportItemDescription = '';
            this.selectedItemReasonIds = [];
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
                
                // Reset modal animations
                const modalEl = document.getElementById('report-item-modal-content');
                if (modalEl && window.gsap) gsap.set(modalEl, { clearProps: "all" });
            }, 300);
        },

      async submitItemReport() {
        if (!this.selectedItemIdToReport) return;

        const id = this.selectedItemIdToReport;
        const type = this.reportItemType;

        // Animate modal close (unchanged)
        const modalEl = document.getElementById('report-item-modal-content');
        if (modalEl && window.gsap) {
            gsap.to(modalEl, { scale: 0.8, opacity: 0, duration: 0.3, ease: "back.in(1.5)", delay: 0.1 });
        }

        try {
            const res = await fetch('/user_backend/submit_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reported_item_id: id,
                    item_type: type,
                    reason_ids: this.selectedItemReasonIds,
                    description: this.reportItemDescription
                })
            });

            const data = await res.json().catch(() => ({ success: false, message: 'Invalid server response' }));

            if (data.success) {
                this.closeReportItemModal();
                if (window.showToast) window.showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} reported.`, 'success');
            } else {
                this.closeReportItemModal();
                if (window.showToast) window.showToast(data.message || 'Report failed.', 'error');
            }
        } catch (e) {
            console.error("Report item failed:", e);
            this.closeReportItemModal();
            if (window.showToast) window.showToast('Network error. Please try again.', 'error');
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
                        id: msg.message_id,
                        sender: Number(msg.sender_id) === Number(window.CURRENT_USER_ID) ? 'me' : 'them',
                        text: msg.message_text,
                        message_type: msg.message_type || 'text',
                        image_url: msg.image_url || null,
                        time: this.formatTime(msg.time || msg.created_at),
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
            if ((!this.chatInput.trim() && !this.selectedImageFile) || !this.activeChatFriend) return;
            
            const receiverId = Number(this.activeChatFriend.user_id || this.activeChatFriend.friend_id || this.activeChatFriend.id);
            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('message', this.chatInput.trim());
            if (this.selectedImageFile) {
                formData.append('image', this.selectedImageFile);
            }
            
            // Optimistic UI for text
            if (this.chatInput.trim()) {
                this.chatMessages.push({
                    id: 'local-' + Date.now(),
                    sender: 'me',
                    text: this.chatInput.trim(),
                    time: this.formatTime(new Date()),
                    is_read: 0,
                    message_type: 'text',
                    image_url: null
                });
            }
            // Optimistic UI for image (using local preview)
            if (this.selectedImagePreview) {
                this.chatMessages.push({
                    id: 'temp-img-' + Date.now(),
                    sender: 'me',
                    message_type: 'image',
                    image_url: this.selectedImagePreview,
                    time: this.formatTime(new Date()),
                    is_temp: true
                });
            }
            
            this.chatInput = '';
            this.clearSelectedImage();
            
            try {
                const res = await fetch('/user_backend/send_chat.php', {
                    method: 'POST',
                    body: formData   // do NOT set Content-Type header
                });
                
                const data = await res.json();
                if (data.success && data.data.message_type === 'image') {
                    // Replace temp image message with real URL
                    const tempMsg = this.chatMessages.find(m => m.id.startsWith('temp-img-'));
                    if (tempMsg) {
                        tempMsg.image_url = data.data.image_url;
                        tempMsg.is_temp = false;
                    }
                } else if (!data.success) {
                    console.error('Send failed:', data.message);
                    // Optionally remove the optimistic message or show error
                }
            } catch (e) {
                console.error('Network error:', e);
            }
        },

        handleImageSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                alert('Invalid image type');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image too large (max 5MB)');
                return;
            }
            
            this.selectedImageFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.selectedImagePreview = e.target.result;
            };
            reader.readAsDataURL(file);
            event.target.value = '';
        },

        clearSelectedImage() {
            this.selectedImageFile = null;
            this.selectedImagePreview = null;
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
        async disbandRoom(roomId) {
            if (!roomId) return;
            if (!confirm('Are you sure you want to disband this room? This cannot be undone.')) return;
            try {
                const res = await fetch(`/user_backend/leave_room.php?room_id=${roomId}`, { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    this.rooms = this.rooms.filter(r => r.id !== roomId);
                    this.roomModalOpen = false;
                    if (window.showToast) window.showToast('Room disbanded successfully.', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to disband room.', 'error');
                }
            } catch (e) {
                console.error('disbandRoom error:', e);
                if (window.showToast) window.showToast('Network error while disbanding room.', 'error');
            }
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

        // Fetch existing comments
        async fetchMovieComments(movieId) {
            try {
                const res = await fetch(`/user_backend/get_comments.php?movie_id=${movieId}`);
                const data = await res.json();
                if (data.success) {
                    const applyLikes = (comments) => {
                        comments.forEach(c => {
                            if (this.localLikedComments.has(Number(c.id)) || Number(c.is_liked) === 1) {
                                c.is_liked = true;
                            } else {
                                c.is_liked = false;
                            }
                            if (c.replies) applyLikes(c.replies);
                        });
                    };
                    applyLikes(data.comments);
                    this.selectedMovie.comments = data.comments;
                }
            } catch (e) { 
                console.error("Failed to load comments:", e); 
            }
        },

        async toggleLike(commentId) {
            if (!this.selectedMovie) return;

            // 1. Find the comment in the local Alpine array
            const comment = this.selectedMovie.comments.find(c => Number(c.id) === Number(commentId));
            if (!comment) return;

            // 2. Instantly update UI for the user (Optimistic UI)
            comment.is_liked = !comment.is_liked;
            comment.likes_count += comment.is_liked ? 1 : -1;
            
            if (comment.is_liked) {
                this.localLikedComments.add(Number(commentId));
            } else {
                this.localLikedComments.delete(Number(commentId));
            }
            localStorage.setItem('nexus_liked_comments', JSON.stringify([...this.localLikedComments]));
            
            // Force Alpine to re-render
            this.selectedMovie.comments = [...this.selectedMovie.comments];

            // 3. Send the like to your backend
            try {
                await fetch('/user_backend/like_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ comment_id: commentId })
                });
            } catch (e) {
                console.error("Failed to like comment:", e);
                // If the database fails, revert the like button visually
                comment.is_liked = !comment.is_liked;
                comment.likes_count += comment.is_liked ? 1 : -1;
                this.selectedMovie.comments = [...this.selectedMovie.comments];
            }
        },

        async handleReviewSubmission() {
            if (this.newRating === 0 && this.commentText.trim() === '') {
                return window.showToast ? window.showToast('Please select a rating or write a review', 'error') : alert('Please select a rating or write a review');
            }
            this.isSubmittingReview = true;
            const success = await this.submitReview(this.newRating, this.commentText);
            if (success) {
                this.commentText = '';
                if (window.showToast) window.showToast('Review posted successfully!', 'success');
            } else {
                if (window.showToast) window.showToast('Error posting review.', 'error');
            }
            this.isSubmittingReview = false;
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

        // Like/Unlike Comment Method
        async likeComment(commentId) {
            // Optimistic UI update
            const findComment = (list) => {
                for (let c of list) {
                    if (Number(c.id) === Number(commentId)) return c;
                    if (c.replies) {
                        const found = findComment(c.replies);
                        if (found) return found;
                    }
                }
                return null;
            };

            const targetComment = findComment(this.selectedMovie.comments || []);
            if (targetComment) {
                targetComment.is_liked = !targetComment.is_liked;
                targetComment.likes_count += targetComment.is_liked ? 1 : -1;
                
                if (targetComment.is_liked) {
                    this.localLikedComments.add(Number(commentId));
                } else {
                    this.localLikedComments.delete(Number(commentId));
                }
                localStorage.setItem('nexus_liked_comments', JSON.stringify([...this.localLikedComments]));
                
                this.selectedMovie.comments = [...this.selectedMovie.comments];
            }

            try {
                await fetch('/user_backend/like_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ comment_id: commentId })
                });
            } catch (e) {
                console.error("Like error:", e);
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

        async fetchShopItems() {
            try {
                const res = await fetch('/user_backend/get_shop_items.php');
                const data = await res.json();
                if (data.success) {
                    this.shopItems = data.items.map(item => ({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        rarity: item.rarity,
                        image: this.resolveShopImage(item.image),
                        category: item.category
                    }));
                }
            } catch (e) {
                console.error('Failed to fetch shop items:', e);
            }
        },

        async purchaseItem(itemId) {
            try {
                const res = await fetch('/user_backend/purchase_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId })
                });
                const data = await res.json();
                if (data.success) {
                    this.userPoints = data.new_points;
                    this.userInventory.push(itemId);
                    this.showConfirmModal = false;   // close modal
                    if (window.showToast) window.showToast('Purchase successful!', 'success');
                    this.fetchUserProfile();
                } else {
                    if (window.showToast) window.showToast(data.message || 'Purchase failed', 'error');
                }
            } catch (e) {
                console.error('Purchase error:', e);
                if (window.showToast) window.showToast('Network error', 'error');
            }
        },

        async fetchWatchlist() {
            try {
                const response = await fetch("/user_backend/get_watchlist.php");
                const data = await response.json();
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

                const currentComments = this.selectedMovie.comments || [];
                // Push onto the array
                this.selectedMovie.comments = [data, ...currentComments];
            });

            // 3. Live Reply Update
           channel.bind('new_reply', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                // Build the new comments array
                const updatedComments = this.selectedMovie.comments.map(comment => {
                    if (Number(comment.id) === Number(data.parent_id)) {
                        const currentReplies = comment.replies || [];
                        const replyExists = currentReplies.some(r => Number(r.id) === Number(data.id));

                        if (!replyExists) {
                            return { ...comment, replies: [...currentReplies, data] };
                        }
                    }
                    return comment;
                });

                // OVERWRITE the root object
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
                            return true;
                        }
                        if (list[i].replies && findAndSetLikes(list[i].replies)) {
                            return true;
                        }
                    }
                    return false;
                };

                // Create a shallow copy of comments to force reactivity
                const newComments = [...(this.selectedMovie.comments || [])];
                if (findAndSetLikes(newComments)) {
                    this.selectedMovie.comments = newComments;
                }
            });

            // 5. Live View Count Update
            channel.bind('view_count_updated', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                // Update modal
                this.selectedMovie.view_count = data.view_count;

                // Update main movies array
                const movieInList = this.movies.find(m => Number(this.getMovieId(m)) === Number(data.movie_id));
                if (movieInList) {
                    movieInList.view_count = data.view_count;
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

         handleProfileChanged(data) {
            // Update current user if it's them (e.g., another tab)
            if (window.CURRENT_USER_ID && Number(data.user_id) === Number(window.CURRENT_USER_ID)) {
                if (data.avatar_url) this.selectedAvatar = data.avatar_url;
                if (data.border_id !== undefined) this.activeBorderId = data.border_id;
            }

            // Update comments (if any)
            if (this.selectedMovie && this.selectedMovie.comments) {
                this.selectedMovie.comments = this.selectedMovie.comments.map(comment => {
                    if (Number(comment.user_id) === Number(data.user_id)) {
                        comment.avatar_url = data.avatar_url || comment.avatar_url;
                        comment.border_preview = data.border_preview || comment.border_preview;
                    }
                    return comment;
                });
            }

            // Update friends list
            this.friends = this.friends.map(friend => {
                if (Number(friend.user_id) === Number(data.user_id)) {
                    friend.avatar_url = data.avatar_url || friend.avatar_url;
                    friend.border_preview = data.border_preview || friend.border_preview;
                }
                return friend;
            });

            // Update searchResults
            this.searchResults = this.searchResults.map(user => {
                if (Number(user.user_id) === Number(data.user_id)) {
                    user.avatar_url = data.avatar_url || user.avatar_url;
                    user.border_preview = data.border_preview || user.border_preview;
                }
                return user;
            });
        },

        // Initialize Pusher Connection
        initPusher() {
            // Only run if Pusher library is available
            if (typeof Pusher === 'undefined') return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            this.pusherClient.connection.bind('connected', () => {
                console.log('Pusher connected');
            });
            this.pusherClient.connection.bind('error', (err) => {
                console.error('Pusher connection error:', err);
            });

            // ---- PUBLIC CHANNELS (always subscribe) ----

            // Shop live updates
            const shopChannel = this.pusherClient.subscribe('shop-updates');
            shopChannel.bind('shop_changed', () => {
                this.fetchShopItems();
            });

            // Movie live updates
            const movieChannel = this.pusherClient.subscribe('movie-updates');
            movieChannel.bind('movie_changed', (data) => {
                if (data.action === 'delete') {
                    const movieId = Number(data.movie_id);
                    this.movies = this.movies.filter(m => Number(m.id || m.movie_id) !== movieId);
                } else {
                    this.fetchMovies();
                }
            });

            //Border and Avater
            const profileChannel = this.pusherClient.subscribe('profile-updates');
            profileChannel.bind('profile_changed', (data) => {
                this.handleProfileChanged(data);
            });

            // ---- USER-SPECIFIC CHANNEL (only if logged in) ----
            if (!window.CURRENT_USER_ID) return;

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
                this.searchResults = this.searchResults.map(user => {
                    if (Number(user.user_id) === removedId) {
                        return { ...user, friend_status: null, requester_id: null };
                    }
                    return user;
                });
                if (window.showToast) {
                    window.showToast('A friendship was removed.', 'info');
                }
            });

            // Watchlist updates
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

            // Friend events
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
                            { user_id: data.sender_id, user_name: data.sender_name },
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
                            { user_id: acceptorId, user_name: acceptorName, unread_count: 0 }
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
                this.fetchNotifications();
            });
        },

        async init() { 
             // 1. Read user data from data attributes (works even on Barba transitions)
            const root = this.$root;

            if (root.dataset.currentUserId) {
                window.CURRENT_USER_ID = Number(root.dataset.currentUserId) || null;
            } else {
                window.CURRENT_USER_ID = null;
            }

            if (root.dataset.nexusUser) {
                try {
                    window.NEXUS_USER = JSON.parse(root.dataset.nexusUser);
                } catch (e) {
                    console.error('Failed to parse NEXUS_USER from data attribute', e);
                    window.NEXUS_USER = { isGuest: true, username: 'Guest', email: '' };
                }
            } else {
                // Fallback (should not normally happen)
                window.NEXUS_USER = { isGuest: true, username: 'Guest', email: '' };
            }

            // 2. Update savedProfile and accountForm based on NEXUS_USER
            if (!window.NEXUS_USER.isGuest) {
                this.savedProfile = {
                    username: window.NEXUS_USER.username || 'CurrentUser',
                    email: window.NEXUS_USER.email || ''
                };
                this.accountForm = { ...this.savedProfile };
            } else {
                this.savedProfile = { username: 'Guest', email: '' };
                this.accountForm = { ...this.savedProfile };
            }

            const savedBorder = localStorage.getItem('activeBorder');
            if (savedBorder) this.activeBorderId = parseInt(savedBorder, 10);

            if (typeof gsap !== 'undefined') gsap.config({ nullTargetWarn: false });

            this.initPusher();  
            
            if (this.isGuest) {
                this.statsLoading = false;
                this.stats = this.stats.map(stat => ({ ...stat, value: '0' }));
                this.friends = [];
                this.pendingRequests = [];
                this.quests = { daily: [], weekly: [], monthly: [] };
                this.watchlist = [];
                this.notifications = [];
                this.unreadNotifCount = 0;
                await this.fetchMovies();
                await this.fetchShopItems();
            } else {
                await Promise.all([
                    this.fetchMovies(),
                    this.fetchWatchlist(),
                    this.fetchFriends(),
                    this.fetchNotifications(),
                    this.loadMissions(),
                    this.fetchReasons(),
                    this.fetchShopItems(),             
                    this.fetchUserProfile(),         
                    (async () => {
                        await this.checkPaymentStatus();
                        await this.fetchPremiumStatus();
                    })()
                ]);
                this.searchUsers();
                this.buildAvailableBorders(); 

                if (this.justPaid) {
                    this.showPremiumModal = true;
                    this.justPaid = false;
                }
            }

            // Watchers remain the same, but guard quests watchers to avoid GSAP errors
            this.$watch('showPremiumModal', value => {
                if (value && !this.isGuest) {
                    this.$nextTick(() => this.initPremium());
                }
            });

            this.$watch('friends', () => {
                if (!this.isGuest) this.updateFriendsCount();
            });

            // Search watcher – allow searching even for guests (backend returns public data)
            // but no actions will be possible.
            this.$watch('searchQuery', (query) => {
                clearTimeout(this.searchTimeout);
                const trimmed = (query || '').trim();
                if (trimmed === '') { this.searchUsers(); return; }
                if (trimmed.length < 2) return;
                this.searchTimeout = setTimeout(() => this.searchUsers(), 300);
            });

            this.$watch('showInviteModal', (isOpen) => {
                if (isOpen && !this.isGuest) this.searchUsers(this.searchQuery);
            });

            if (!this.isGuest) {
                this.$watch('showQuestsPanel', value => {
                    if (value && typeof gsap !== 'undefined') {
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
                    if (typeof gsap !== 'undefined') {
                        this.$nextTick(() => {
                            gsap.fromTo('.quest-item',
                                { x: 50, opacity: 0, scale: 0.95 },
                                { x: 0, opacity: 1, scale: 1, duration: 0.5, stagger: 0.05, ease: 'power3.out' }
                            );
                        });
                    }
                });
            }
            // 5. Global Event Listeners
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isNavOpen) this.closeNav();
            });

            // 6. Initial GSAP UI Animations 
            // Uses a single $nextTick to ensure Alpine has finished rendering the HTML
            this.$nextTick(() => {
                if (typeof gsap !== 'undefined') {
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
                }
            });

            // 7. Interval Animations
            // Random glitch effect on dashboard stat numbers periodically
            if (typeof gsap !== 'undefined') {
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
        }
    };
}

function watchParty() {
    return {
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
                    autoplay: '1', 
                    mute: '1',     
                    controls: '0',                 
                    loop: '1',
                    playlist: videoId,             
                    modestbranding: '1',
                    rel: '0',
                    showinfo: '0',
                    iv_load_policy: '3',
                    fs: '0'
                });
                return `https://www.youtube.com/embed/${videoId}?${params.toString()}`;
            }
            return url;
        },

        // --- 1. UI State ---
        showChat: true,
        showParticipants: true,
        showControls: false,
        controlsTimeout: null,
        isLoading: false,
        movieSearchQuery: '',
        movieFilter: 'all',
        hoveredMovieId: null,
        
        get filteredMovies() {
            let result = this.allMovies || [];
            
            // Search filter
            if (this.movieSearchQuery) {
                const q = this.movieSearchQuery.toLowerCase();
                result = result.filter(m => m.title.toLowerCase().includes(q));
            }
            
            // Genre filter
            if (this.movieFilter !== 'all') {
                result = result.filter(m => {
                    if (Array.isArray(m.genres)) {
                        return m.genres.some(g => g.toLowerCase() === this.movieFilter.toLowerCase());
                    } else if (m.genre) {
                        return m.genre.toLowerCase().includes(this.movieFilter.toLowerCase());
                    }
                    return false;
                });
            }
            
            return result;
        },

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
        showMovieModal: false,
        allMovies: [],
        friends: [],
        showInviteMenu: false,

        async init() { 
            const savedBorder = localStorage.getItem('activeBorder');
            if (savedBorder) {
                this.activeBorderId = parseInt(savedBorder, 10);
            }
            this.fetchRoomDetails();
            this.fetchFriends();
            this.startLocalMedia();
            this.connectSignaling();
            this.fetchMovies();
        },

        async fetchRoomDetails() {
            // We will add the PHP fetch logic here in the next step!
            console.log("Fetching data for room:", this.roomId);
        },

        async fetchMovies() {
            try {
                const res = await fetch('/user_backend/movies_api.php');
                if (res.status === 401) {
                    window.location.href = '/frontend/login.php';
                    return;
                }
                const data = await res.json();
                if (Array.isArray(data)) {
                    this.allMovies = data;
                }
            } catch (e) {
                console.error("Error fetching movies:", e);
            }
        },

        selectMovie(movie) {
            // Use actual_video_url or trailer depending on what's available
            this.videoUrl = movie.actual_video_url || movie.trailer || 'https://upload.wikimedia.org/wikipedia/commons/transcoded/8/88/Big_Buck_Bunny_alt.webm/Big_Buck_Bunny_alt.webm.720p.vp9.webm';
            this.showMovieModal = false;
            
            // Add a slight delay to allow x-if template to render the video player
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

        async fetchFriends(retries = 1) {
            try {
                const res = await fetch('/user_backend/get_friends.php');
                if (res.status === 401) {
                    window.location.href = '/frontend/login.php';
                    return;
                }
                if (!res.ok) throw new Error('Failed to fetch friends');
                const data = await res.json();
                if (data.friends) {
                    this.friends = data.friends;
                }
            } catch (e) {
                if (e.name === 'AbortError') return;
                if (retries > 0) {
                    setTimeout(() => this.fetchFriends(retries - 1), 1000);
                    return;
                }
                console.warn("Could not fetch friends (transient network error):", e.message);
            }
        },

        inviteFriend(friendId) {
            // Extract room ID from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const roomId = urlParams.get('room_id');
            
            // Use dynamic user name from PHP
            this.socket.emit('send-lobby-invite', {
                targetUserId: friendId,
                hostName: window.USER_NAME || 'Someone', 
                roomId: roomId
            });
            
            this.showInviteMenu = false;
            
            // Optional: Trigger your custom toast here instead of an alert!
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
                    name: 'You',
                    stream: this.localStream,
                    muted: this.isMuted,
                    videoOn: this.isVideoOn,
                    speaking: false,
                    isSelf: true // Mutes your own video element so you don't hear an echo
                });
            } catch (e) {
                console.error("Camera/Mic access denied or unavailable.", e);
            }
        },

       connectSignaling() {
            this.socket = io(); 

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
            const localParticipant = this.participants.find(p => p.id === 'local');
            if (localParticipant) {
                localParticipant.muted = this.isMuted;
            }
            // we should also notify other peers here but for UI we update local participant
            if (this.socket) {
                 this.socket.emit('toggle-mic', this.isMuted);
            }
        },

        toggleVideo() {
            this.isVideoOn = !this.isVideoOn;
            if(this.localStream) {
                // Disable/Enable the actual video track
                this.localStream.getVideoTracks()[0].enabled = this.isVideoOn;
            }
            const localParticipant = this.participants.find(p => p.id === 'local');
            if (localParticipant) {
                localParticipant.videoOn = this.isVideoOn;
            }
            // we should also notify other peers here but for UI we update local participant
            if (this.socket) {
                 this.socket.emit('toggle-video', this.isVideoOn);
            }
        }
    }
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

    // Generate Posters with actual movie images
    const posterWall = container.querySelector('#poster-wall-container');
    if (posterWall && !posterWall.dataset.initialized) {
        posterWall.dataset.initialized = '1';

        const POSTER_IMAGES = [
            '3 Idiots.jpg', 'A Brighter Summer Day.jpg', 'Deadpool & Wolverine.jpg',
            'Deep Water.jpg', 'Doctor Strange in the Multiverse of Madness.jpg', 'Dune.jpg',
            'Forrest Gump.jpg', 'Grave of the Fireflies.jpg', 'Heartstopper Forever.jpg',
            'Inception.jpg', 'Interstellar.jpg', 'KPop Demon Hunters.jpg',
            'Minions & Monsters.jpg', 'Modern Times.jpg', "Now You See Me Now You Don't.jpg",
            'Obsession.jpg', 'Once We Were Us.jpg', 'Parasite.jpg',
            'Reservoir Dogs.jpg', 'Spider-Man Brand New Day.jpg', 'Supergirl.jpg',
            'Swapped.jpg', 'The Lady.jpg', 'The Mandalorian and Grogu.jpg',
            'The Mask.jpg', 'The Odyssey.jpg', 'The Salt of the Earth.jpg',
            'The Shawshank Redemption.jpg', 'Warfare.jpg', 'World War Z.jpg', 'Your Name.jpg'
        ];

        // Detect path prefix based on where we are (frontend pages vs root)
        const isInFrontend = window.location.pathname.includes('/frontend/');
        const POSTER_BASE = isInFrontend ? 'Movies poster/' : 'frontend/Movies poster/';

        // Fisher-Yates shuffle helper
        function shuffleArray(arr) {
            const a = [...arr];
            for (let i = a.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [a[i], a[j]] = [a[j], a[i]];
            }
            return a;
        }

        let html = '';
        for (let i = 0; i < 8; i++) {
            const dir = i % 2 === 0 ? 'up' : 'down';
            const duration = 120 + (i * 15);
            const shuffled = shuffleArray(POSTER_IMAGES);
            // Duplicate for seamless loop
            const doubled = [...shuffled, ...shuffled];
            let posters = '';
            doubled.forEach(filename => {
                const src = POSTER_BASE + encodeURIComponent(filename);
                posters += `<div class="poster"><img src="${src}" alt="" class="poster-img" loading="eager" decoding="async" onerror="this.parentElement.style.display='none'"></div>`;
            });
            html += `<div class="poster-col ${dir}" style="animation-duration:${duration}s;">${posters}</div>`;
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

    if (typeof gsap !== 'undefined') {
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

    const inputFields = container.querySelectorAll('.input-field');
    inputFields.forEach(input => {
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

    const submitBtn = container.querySelector('#submitBtn');
    const ripple = container.querySelector('#btnRipple');
    const btnIcon = submitBtn ? submitBtn.querySelector('span.material-symbols-outlined') : null;
    
    if (submitBtn) {
        submitBtn.addEventListener('mouseenter', (e) => {
            if (ripple) gsap.to(ripple, { scale: 1.5, opacity: 1, duration: 0.4, ease: 'power2.out' });
            if (btnIcon) gsap.to(btnIcon, { x: 5, duration: 0.3, ease: 'back.out(2)' });
        });
        
        submitBtn.addEventListener('mouseleave', () => {
            if (ripple) gsap.to(ripple, { scale: 0, opacity: 0, duration: 0.4 });
            if (btnIcon) gsap.to(btnIcon, { x: 0, duration: 0.3, ease: 'power2.out' });
        });
        
        submitBtn.addEventListener('mousedown', () => {
            gsap.to(submitBtn, { scale: 0.95, duration: 0.1, ease: 'power2.inOut' });
        });
        
        submitBtn.addEventListener('mouseup', () => {
            gsap.to(submitBtn, { scale: 1, duration: 0.4, ease: 'elastic.out(1, 0.3)' });
        });
    }

    const backBtn = container.querySelector('.gs-back-btn');
    const backHit = container.querySelector('.gs-back-hit');
    const backRing = container.querySelector('.gs-back-ring');
    const backIcon = container.querySelector('.gs-back-icon');
    
    if (backBtn && backHit) {
        gsap.fromTo(backBtn, 
             { x: -50, opacity: 0, scale: 0 }, 
             { x: 0, opacity: 1, scale: 1, duration: 1.5, ease: "elastic.out(1, 0.4)", delay: 0.3 }
        );

        let hoverTween = gsap.to(backRing, { rotation: 360, duration: 4, repeat: -1, ease: "linear", paused: true });

        backHit.addEventListener("mousemove", (e) => {
            const rect = backHit.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            gsap.to(backBtn, {
                x: x * 0.4,
                y: y * 0.4,
                scale: 1.1,
                duration: 0.4,
                ease: "power3.out",
                boxShadow: "0 10px 30px rgba(239, 68, 68, 0.3)",
                borderColor: "rgba(239, 68, 68, 0.5)"
            });
            
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
            if (this.$refs.resendIcon && typeof gsap !== 'undefined') {
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
        // Merged the duplicate init() logic here so both GSAP and fetchMovies() run properly

        resolveShopImage(image) {
            if (!image) return '';
            if (/^(https?:)?\/\//i.test(image) || image.startsWith('/') || image.startsWith('data:') || image.startsWith('blob:')) {
                return image;
            }
            return '/uploads/shop/' + image;
        },

        //comments
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
            
            // First pass: create a map of all comments by ID
            flatComments.forEach(comment => {
                map[comment.id] = { ...comment, replies: [] };
            });
            
            // Second pass: assign children to parents or roots
            flatComments.forEach(comment => {
                const parentId = comment.parent_id;
                if (parentId && map[parentId]) {
                    map[parentId].replies.push(map[comment.id]);
                } else {
                    roots.push(map[comment.id]);
                }
            });
            
            // Optional: sort roots by newest first (already sorted by backend)
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
                    // Remove from both arrays (modal + global list)
                    this.movieComments = this.movieComments.filter(c => c.id !== commentId && c.parent_id !== commentId);
                    this.comments = this.comments.filter(c => c.id !== commentId && c.parent_id !== commentId);
                    this.showToast('Comment deleted', 'success');
                } else {
                    this.showToast(data.error || 'Delete failed', 'error');
                }
            } catch (e) {
                console.error(e);
                this.showToast('Network error', 'error');
            }
        },

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

                const rawText = await response.text(); // Read raw text first
                
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
        // Clear notification badge
       markAllRead() {
            if (this.unreadNotifCount === 0) return;

            fetch('/user_backend/mark_notifications_read.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.unreadNotifCount = 0;
                        // Force Alpine to re-render by mapping to a completely new array
                        this.notifications = this.notifications.map(n => ({ ...n, is_read: 1 }));
                    }
                })
                .catch(err => console.error("Error marking read:", err));
        },
        statsLoading: true,
        stats: [
            { label: "Total Users", value: "0", change: "0%", icon: "group" },
            { label: "Active Sessions", value: "0", change: "0%", icon: "live_tv" },
            { label: "Revenue", value: "$0", change: "0%", icon: "payments" },
            { label: "Server Load", value: "0%", change: "0%", icon: "memory" }
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
        availableGenres: [], // Holds list from genres table
        movieModalOpen: false,
        editingMovie: false,
        movieTab: 'details',
        posterPreview: null,
        newMovie: {
            id: null,
            title: '',
            description: '',
            img_file: null, // Holds the actual file object for backend upload
            trailer: '',   
            duration: '',  
            genre_ids: []  
        },

        // --- Initialization ---
        async init() { 
            const savedBorder = localStorage.getItem('activeBorder');
            if (savedBorder) {
                this.activeBorderId = parseInt(savedBorder, 10);
            }
            await this.fetchMovies();
            await this.fetchGenres();
        },

        // --- Fetch API Methods ---
       async fetchMovies() {
            this.isLoading = true;
            try {
                const response = await fetch('/backend/movies_api.php');
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
                } catch(e) {
                    console.error("Invalid JSON response for genres:", text);
                }
            } catch (err) {
                console.error("Network error fetching genres:", err);
            }
        },

        // --- Movie Management & Upload Logic ---

        // Safely preview an image without saving a bad blob: link to the DB
        handlePosterSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.newMovie.img_file = file;
            
            // Clean up previous blob to prevent memory leaks
            if (this.posterPreview && this.posterPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.posterPreview);
            }
            // Generate a local preview for the admin
            this.posterPreview = URL.createObjectURL(file);
        },

        openAddMovieModal() {
            this.editingMovie = false;
            this.errorMessage = '';
            this.posterPreview = null;
            this.newMovie = { 
                id: null, 
                title: '', 
                description: '', 
                img_file: null, 
                trailer: '', 
                duration: '', 
                genre_ids: [] 
            };
            this.movieTab = 'details';   
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

                // Use the file property that handleFileUpload sets
                if (this.newMovie.posterFile) {
                    formData.append('poster_image', this.newMovie.posterFile);
                } else if (this.newMovie.img_file) {
                    formData.append('poster_image', this.newMovie.img_file);
                }

                const response = await fetch('/backend/movies_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    // If the server returns the updated movie object, use it
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
                        // Force reactivity
                        this.movies = [...this.movies];
                    } else {
                        // Fallback: fetchMovies with cache-busting
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

                const response = await fetch('/backend/movies_api.php', {
                    method: 'POST',
                    body: formData
                });
                
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
        // Sessions
        roomModalOpen: false,
        selectedRoom: null,
        rooms: [],

        mockRoomUsers: [{ id: 1, name: 'Alice', isHost: true, avatar: '' }, { id: 2, name: 'Charlie', isHost: false, avatar: '' } ], mockRoomUsers2: [
            { name: 'Alice', isHost: true },
            { name: 'Charlie', isHost: false }
        ],

        async fetchRooms() {
            try {
                const res = await fetch('/user_backend/get_rooms.php');
                const data = await res.json();
                if (data.success) {
                    this.rooms = data.rooms;
                }
            } catch (e) {
                console.error('fetchRooms error:', e);
            }
        },

        // Reports
        viewModalOpen: false,
        selectedReport: null,
        reportsList: [],
        reportStats: { total: 0, pending: 0, read: 0 },
        filterStatus: 'all',
        reportCommentDetails: null,
        loadingReportComment: false,
        commentsCache: {},

        preloadReportComments() {
            const movieIds = [...new Set(
                this.reportsList
                    .filter(r => r.reported_movie_id && r.reported_comment_id)
                    .map(r => r.reported_movie_id)
            )];

            movieIds.forEach(async (movieId) => {
                if (this.commentsCache[movieId]) return;
                try {
                    const res = await fetch(`/backend/comments_api.php?movie_id=${movieId}`);
                    const data = await res.json();
                    if (data.success) {
                        this.commentsCache[movieId] = data.comments;
                    }
                } catch (e) {
                    console.error('Failed to preload comments for movie', movieId, e);
                }
            });
        },

        viewReport(report) {
            this.selectedReport = report;
            this.viewModalOpen = true;
            this.reportCommentDetails = null;

            // Mark as read if pending
            if (report.status === 'Pending') {
                try {
                    fetch('/backend/update_report_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ report_id: report.id, status: 'Read' })
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            report.status = 'Read';
                            this.updateReportStats();
                        }
                    });
                } catch (e) {
                    console.error("Failed to mark report as read:", e);
                }
            }

            // Load comment details from cache
            if (report.reported_comment_id && report.reported_movie_id) {
                const movieComments = this.commentsCache[report.reported_movie_id];
                if (movieComments) {
                    const found = movieComments.find(c => c.id == report.reported_comment_id);
                    if (found) this.reportCommentDetails = found;
                } else {
                    this.fetchReportComment(report);
                }
            }
        },

        async fetchReportComment(report) {
            this.loadingReportComment = true;
            this.reportCommentDetails = null;
            try {
                const movieId = report.reported_movie_id;
                const commentId = report.reported_comment_id;
                const res = await fetch(`/backend/comments_api.php?movie_id=${movieId}`);
                const data = await res.json();
                if (data.success) {
                    this.commentsCache[movieId] = data.comments;
                    const found = data.comments.find(c => c.id == commentId);
                    if (found) this.reportCommentDetails = found;
                }
            } catch (e) {
                console.error('Failed to fetch reported comment:', e);
            } finally {
                this.loadingReportComment = false;
            }
        },

        // Optionally update resolveReport if needed (it's fine as is)

        async fetchReports() {
            try {
                const response = await fetch('/backend/get_reports.php');
                const data = await response.json();
                
                if (data.success) {
                    this.reportsList = data.reports;
                    this.updateReportStats();
                    this.preloadReportComments();
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

        // UPDATED: Sends a request to the backend to mark the report as "Resolved"
        async resolveReport() {
            if (!this.selectedReport) return;

            try {
                const res = await fetch('/backend/update_report_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: this.selectedReport.id, status: 'Read' })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    this.selectedReport.status = 'Read';
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
            if (this.filterStatus === 'all') {
                return this.reportsList;
            }
            return this.reportsList.filter(report => report.status.toLowerCase() === this.filterStatus);
        },
        
        highlightCommentId: null,
        returnToReportsAfterMovieModal: false,

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
            this.movieTab = 'comments';
            await this.fetchMovieCommentsForAdmin(movieId);
            
            this.highlightCommentId = commentId;
            
            // Expand replies if the highlighted comment is a reply
            for (let c of this.nestedMovieComments) {
                if (c.replies && c.replies.some(r => r.id == commentId)) {
                    c.show_replies = true;
                }
            }

            // Scroll to the comment
            setTimeout(() => {
                const el = document.getElementById('admin-comment-' + commentId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 400);
        },

        // Shop       
        modalOpen: false,
        modalMode: 'add',
        formData: { name: '', price: 0, rarity: 'Common', image: '' },
        shopItems: [],
        borders: [],
        shopImageFile: null,   // holds File object for shop item image

        async fetchShopItems() {
            try {
                const res = await fetch('/backend/shop_items_api.php?action=list');
                const data = await res.json();
                if (data.success) {
                    this.shopItems = data.items.map(item => ({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        rarity: item.rarity,
                        image: this.resolveShopImage(item.image),
                        category: item.category
                    }));
                } else {
                    this.showToast(data.error || 'Failed to load shop items', 'error');
                }
            } catch (e) {
                console.error('Fetch shop items error:', e);
                this.showToast('Network error loading shop items', 'error');
            }
        },

        handleShopImageSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.shopImageFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.formData.image = e.target.result;  // base64 preview
            };
            reader.readAsDataURL(file);
        },

        async saveItem() {
            if (!this.formData.name || this.formData.price <= 0) {
                this.showToast('Name and positive price required', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', this.modalMode === 'add' ? 'create' : 'update');
            formData.append('name', this.formData.name);
            formData.append('price', this.formData.price);
            formData.append('rarity', this.formData.rarity);
            formData.append('category', this.formData.category || 'border');
            if (this.modalMode === 'edit' && this.formData.id) {
                formData.append('id', this.formData.id);
            }
            if (this.shopImageFile) {
                formData.append('image', this.shopImageFile);
            }

            try {
                const res = await fetch('/backend/shop_items_api.php', {
                    method: 'POST',
                    body: formData   // do NOT set Content-Type header
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(this.modalMode === 'add' ? 'Item added' : 'Item updated', 'success');
                    this.modalOpen = false;
                    await this.fetchShopItems();
                } else {
                    this.showToast(data.error || 'Save failed', 'error');
                }
            } catch (e) {
                console.error('Save item error:', e);
                this.showToast('Network error saving item', 'error');
            }
        },

        async deleteItem(id) {
            if (!confirm('Are you sure you want to delete this item?')) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const res = await fetch('/backend/shop_items_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast('Item deleted', 'success');
                    await this.fetchShopItems();
                } else {
                    this.showToast(data.error || 'Delete failed', 'error');
                }
            } catch (e) {
                console.error('Delete item error:', e);
                this.showToast('Network error deleting item', 'error');
            }
        },

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
                    window.location.href = '../frontend/login.php?account_deleted=1';
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
        // Toggle genre ID in newMovie.genre_ids
        toggleGenre(genre) {
            // Ensure genre_ids array exists
            if (!Array.isArray(this.newMovie.genre_ids)) {
                this.newMovie.genre_ids = [];
            }

            // Extract the ID if genre is an object, otherwise treat the input as the ID
            const val = typeof genre === 'object' ? genre.id : genre;

            const index = this.newMovie.genre_ids.indexOf(val);

            if (index > -1) {
                this.newMovie.genre_ids.splice(index, 1);
            } else {
                this.newMovie.genre_ids.push(val);
            }

            // Optional: update a display string if needed, but not required for saving
            // this.newMovie.genre = this.newMovie.genre_ids.join(', ');
        },

        // Helper to check selection status by ID
        isGenreSelected(genre) {
            if (!this.newMovie || !Array.isArray(this.newMovie.genre_ids)) return false;
            const val = typeof genre === 'object' ? genre.id : genre;
            return this.newMovie.genre_ids.includes(val);
        },
        openEditMovieModal(movie) {
            this.editingMovie = true;
            this.movieTab = 'details';
            this.newMovie = JSON.parse(JSON.stringify(movie));

            // Convert string genre to array if needed
            if (!Array.isArray(this.newMovie.genre_ids)) {
                if (typeof this.newMovie.genre_ids === 'string' && this.newMovie.genre_ids.trim() !== '') {
                    // Convert "1,2,3" → [1,2,3]
                    this.newMovie.genre_ids = this.newMovie.genre_ids
                        .split(',')
                        .map(id => parseInt(id.trim(), 10))
                        .filter(id => !isNaN(id));
                } else {
                    this.newMovie.genre_ids = [];
                }
            }

            // ✅ Set the poster preview URL to whatever field the backend uses
            this.newMovie.img = movie.img || movie.poster_url || movie.poster || movie.image || '';
            this.posterPreview = this.newMovie.img; // optional, if you still use posterPreview elsewhere

             this.movieModalOpen = true;

            // Fetch comments for this movie
            this.fetchMovieCommentsForAdmin(this.newMovie.id);
        },
        openBanModal(user) {
            this.userToBan = user;
            this.banReason = '';
            this.banNotes = '';
            this.banModalOpen = true;
        },

        // Use the deployed user_action.php location, with a safe fallback
        // for installations where it lives under /backend.
        async postUserAction(payload) {
            const endpoints = [
                '/user_backend/user_action.php',
                '/backend/user_action.php'
            ];

            let lastError = null;

            for (const endpoint of endpoints) {
                try {
                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
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
                        console.error(
                            `Non-JSON response from ${endpoint}:`,
                            raw
                        );
                        throw new Error('Invalid JSON response from server');
                    }

                    return data;

                } catch (err) {
                    lastError = err;

                    if (
                        err?.message ===
                        'Invalid JSON response from server'
                    ) {
                        throw err;
                    }
                }
            }

            throw (
                lastError ||
                new Error('user_action.php endpoint was not found.')
            );
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

                    window.showToast(
                        data.message || 'User suspended',
                        'success'
                    );
                } else {
                    window.showToast(
                        data.error || 'Failed to ban user',
                        'error'
                    );
                }

            } catch (e) {
                console.error('Ban error:', e);

                window.showToast(
                    e.message || 'Network error',
                    'error'
                );

            } finally {
                this.banModalOpen = false;
            }
        },

        async promoteModerator(user) {
            try {
                const data = await this.postUserAction({
                    action: 'promote_moderator',
                    id: user.id
                });

                if (data.success) {
                    user.role = 'Moderator';
                    user.role_id = 3;

                    this.users = [...this.users];

                    window.showToast(
                        data.message || 'User promoted',
                        'success'
                    );

                } else {
                    window.showToast(
                        data.error || 'Failed to promote user',
                        'error'
                    );
                }

            } catch (e) {
                console.error('Promote error:', e);

                window.showToast(
                    e.message || 'Network error',
                    'error'
                );
            }
        },

        async demoteModerator(user) {
            try {
                const data = await this.postUserAction({
                    action: 'demote_moderator',
                    id: user.id
                });

                if (data.success) {
                    user.role = 'User';
                    user.role_id = 2;

                    this.users = [...this.users];

                    window.showToast(
                        data.message || 'Moderator demoted',
                        'success'
                    );

                } else {
                    window.showToast(
                        data.error || 'Failed to demote user',
                        'error'
                    );
                }

            } catch (e) {
                console.error('Demote moderator error:', e);

                window.showToast(
                    e.message || 'Network error',
                    'error'
                );
            }
        },

        async demoteAdmin(user) {
            try {
                const data = await this.postUserAction({
                    action: 'demote_admin',
                    id: user.id
                });

                if (data.success) {
                    user.role = 'Moderator';
                    user.role_id = 3;

                    this.users = [...this.users];

                    window.showToast(
                        data.message || 'Admin demoted to Moderator',
                        'success'
                    );

                } else {
                    window.showToast(
                        data.error || 'Failed to demote admin',
                        'error'
                    );
                }

            } catch (e) {
                console.error('Demote admin error:', e);

                window.showToast(
                    e.message || 'Network error',
                    'error'
                );
            }
        },
    
        viewRoom(room) {
            this.selectedRoom = room;
            this.roomModalOpen = true;
        },
        async disbandRoom(roomId) {
            if (!roomId) return;
            if (!confirm('Are you sure you want to disband this room? This cannot be undone.')) return;
            try {
                const res = await fetch(`/user_backend/leave_room.php?room_id=${roomId}`, { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    this.rooms = this.rooms.filter(r => r.id !== roomId);
                    this.roomModalOpen = false;
                    if (window.showToast) window.showToast('Room disbanded successfully.', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to disband room.', 'error');
                }
            } catch (e) {
                console.error('disbandRoom error:', e);
                if (window.showToast) window.showToast('Network error while disbanding room.', 'error');
            }
        },

        openModal(mode, item = null) {
            this.modalMode = mode;
            if (item) {
                this.formData = {
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    rarity: item.rarity,
                    image: item.image || '',
                    category: item.category || 'border'
                };
            } else {
                this.formData = { name: '', price: 0, rarity: 'Common', image: '', category: 'border' };
            }
            this.shopImageFile = null;
            this.modalOpen = true;
        },
        closeModal() {
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
                this.newMovie.posterFile = compressedFile;   // store for upload
                const reader = new FileReader();
                reader.onload = (e) => callback(e.target.result); // set newMovie.img for preview
                reader.readAsDataURL(compressedFile);
            } catch (err) {
                // fallback to original
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

        // Detect if URL is from YouTube
        isYouTubeUrl(url) {
            if (!url) return false;
            return url.includes('youtube.com') || url.includes('youtu.be');
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
                    autoplay: isHover ? '1' : '0', // Autoplay must be 1 for hover
                    mute: isHover ? '1' : '0',     // Browser policy requires mute for auto-play on hover
                    controls: '0',                 // Always hide controls
                    loop: '1',
                    playlist: videoId,             // Required for looping
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
            this.fetchRooms();
            this.fetchShopItems(); 
            this.initPusher();
            this.fetchComments();

            
            this.$watch('movieModalOpen', (isOpen) => {
                if (!isOpen) {
                    this.currentMovieId = null;
                    this.highlightCommentId = null;
                    if (this.returnToReportsAfterMovieModal) {
                        this.switchTab('reports');
                        this.returnToReportsAfterMovieModal = false;
                    }
                }
            });
        },

        handleProfileChanged(data) {
            // Update users list (admin user management)
            this.users = this.users.map(user => {
                const userId = user.id || user.user_id;
                if (Number(userId) === Number(data.user_id)) {
                    user.avatar_url = data.avatar_url || user.avatar_url;
                    user.border_preview = data.border_preview || user.border_preview;
                }
                return user;
            });

            // Update comments if viewing
            if (this.comments) {
                this.comments = this.comments.map(comment => {
                    if (Number(comment.user_id) === Number(data.user_id)) {
                        comment.avatar_url = data.avatar_url || comment.avatar_url;
                        comment.border_preview = data.border_preview || comment.border_preview;
                    }
                    return comment;
                });
            }

            // Update selected report if it shows the user
            if (this.selectedReport && Number(this.selectedReport.reported_user_id) === Number(data.user_id)) {
                this.selectedReport.reported_avatar_url = data.avatar_url || this.selectedReport.reported_avatar_url;
                this.selectedReport.reported_border_preview = data.border_preview || this.selectedReport.reported_border_preview;
            }
        },

       initPusher() {
            // Only run if Pusher library is available
            if (typeof Pusher === 'undefined') return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            // ---- PUBLIC CHANNELS (always subscribe) ----

            // Admin comments channel
            const adminCommentsChannel = this.pusherClient.subscribe('admin-comments');

            adminCommentsChannel.bind('new_comment', (data) => {
                this.comments.unshift({
                    id: data.id,
                    movie_id: data.movie_id,
                    user_name: data.user_name,
                    movie_title: data.movie_title,
                    comment_text: data.comment_text,
                    created_at: data.created_at,
                    likes_count: data.likes_count || 0,
                    parent_id: null
                });
            });

            adminCommentsChannel.bind('new_reply', () => {
                this.fetchComments();
            });

            adminCommentsChannel.bind('comment_liked', (data) => {
                const comment = this.comments.find(c => c.id == data.comment_id);
                if (comment) comment.likes_count = data.likes_count;
            });

            // Shop updates channel
            const shopChannel = this.pusherClient.subscribe('shop-updates');

            shopChannel.bind('shop_changed', (data) => {
                if (data.action === 'create') {
                    if (!this.shopItems.some(item => item.id === data.item.id)) {
                        this.shopItems.push(data.item);
                    }
                } else if (data.action === 'update') {
                    const index = this.shopItems.findIndex(item => item.id === data.item.id);
                    if (index !== -1) {
                        this.shopItems.splice(index, 1, data.item);
                    } else {
                        this.shopItems.push(data.item);
                    }
                } else if (data.action === 'delete') {
                    this.shopItems = this.shopItems.filter(item => item.id !== data.item_id);
                }

                // Ensure Alpine reactivity
                this.shopItems = [...this.shopItems];
            });

            // Movie updates channel (optional, but recommended for live movie changes)
            const movieChannel = this.pusherClient.subscribe('movie-updates');
            movieChannel.bind('movie_changed', (data) => {
                if (data.action === 'delete') {
                    const movieId = Number(data.movie_id);
                    this.movies = this.movies.filter(m => Number(m.id || m.movie_id) !== movieId);
                } else {
                    this.fetchMovies();
                }
            });

            const profileChannel = this.pusherClient.subscribe('profile-updates');
            profileChannel.bind('profile_changed', (data) => {
                this.handleProfileChanged(data);
            });

            // ---- USER-SPECIFIC CHANNEL (only if logged in) ----
            if (!window.CURRENT_USER_ID) return;

            const userChannel = this.pusherClient.subscribe(`user-${window.CURRENT_USER_ID}`);

            userChannel.bind('force_logout', (data) => {
                alert(data.message || 'Your account has been banned.');
                window.location.href = '/backend/logout.php';
            });

            // You can add other personal events (e.g., notifications) here if needed
        }
    };
}

window.createParty = async function(movieId = null) {
    try {
        const formData = new FormData();
        formData.append('action', 'create_room');
        if (movieId) {
            formData.append('movie_id', movieId);
        }
        
        const pathsToTry = [
            '/user_backend/create_room.php', // Standard relative path
            '/user_backend/create_room.php',   // Workspace root absolute path
            'user_backend/create_room.php'     // Current directory path
        ];
        
        let data = null;
        let lastError = null;
        let successfulPath = null;
        
        // Robust fetch: try multiple path variations to handle different VSCode PHP Server setups
        for (const p of pathsToTry) {
            try {
                const res = await fetch(p, { method: 'POST', body: formData });
                const text = await res.text();
                
                // If it returns an HTML document (like a 404 page), it's the wrong path
                if (text.trim().toLowerCase().startsWith('<!doctype') || text.trim().toLowerCase().startsWith('<html')) {
                    throw new Error(`Path ${p} returned HTML (likely 404)`);
                }
                
                data = JSON.parse(text);
                successfulPath = p;
                break; // Successfully found and parsed JSON
            } catch (e) {
                lastError = e;
                console.warn(`[Nexus] Path fallback: ${p} failed.`);
            }
        }
        
        if (!data) {
            throw new Error(`All path resolutions failed. Is the server running from the correct root folder? Last error: ${lastError.message}`);
        }
        
        if (data.success) {
            console.log(`Room created successfully using path ${successfulPath}! Code: ${data.room_code}`);
            if (typeof barba !== 'undefined' && barba.go) {
                barba.go(`watch_party.php?room_id=${data.room_id}`);
            } else {
                window.location.href = `watch_party.php?room_id=${data.room_id}`;
            }
        } else {
            console.error("Room creation failed:", data.error);
            if (typeof window.showToast === 'function') {
                window.showToast(data.error, 'error');
            }
        }
    } catch (error) {
        console.error("Error creating room:", error);
        if (typeof window.showToast === 'function') {
            window.showToast("Failed to connect to server. Please check your localhost setup.", 'error');
        }
    }
};
