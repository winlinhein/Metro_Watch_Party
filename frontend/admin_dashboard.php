<?php
session_start();

// Block access if not authenticated OR if the user is not an admin
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) || 
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: login.php?error=" . urlencode("Access denied. Admin privileges required."));
    exit();
}

$userName  = $_SESSION['user_name']  ?? 'Agent';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole  = $_SESSION['user_role']  ?? 'user';
require_once __DIR__ . '/../conn.php';

try {
    // Fetch Movies with description, aggregated rating, trailer, and genre list
    $sql = "
        SELECT 
            m.movie_id AS id,
            m.title,
            m.description,
            m.poster AS img,
            video_url,
            m.view_count,
            YEAR(m.created_at) AS year,
            COALESCE(ROUND(AVG(r.rating), 1), 0) AS rating,
            COUNT(DISTINCT c.comment_id) AS total_comments,
            COALESCE(GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR ', '), 'N/A') AS genre
        FROM movies m
        LEFT JOIN movie_rating r ON m.movie_id = r.movie_id
        LEFT JOIN movie_comments c ON m.movie_id = c.movie_id
        LEFT JOIN movie_and_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        GROUP BY m.movie_id
        ORDER BY m.created_at DESC
    ";

    $stmt = $conn->query($sql);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all available genres for tag selection
    $genresStmt = $conn->query("SELECT genre_name FROM genres ORDER BY genre_name ASC");
    $allGenres = $genresStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - Admin Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    
    <style>
        body { 
            font-family: 'Space Grotesk', sans-serif; 
            background-color: #030305; 
            color: #ffffff; 
            overflow: hidden; 
            cursor: default;
        }
        .mono { font-family: 'JetBrains Mono', monospace; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.015);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-right: 1px solid rgba(255, 255, 255, 0.04);
        }
        .glass-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        .glass-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }
        
        .nav-item {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        .nav-item.active {
            background: rgba(239, 68, 68, 0.08);
            border-left: 3px solid #ef4444;
            color: #fff;
            box-shadow: inset 20px 0 30px -20px rgba(239,68,68,0.2);
        }
        .nav-item::after {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0; width: 3px;
            background: #ef4444;
            transform: scaleY(0);
            transition: transform 0.3s ease;
            transform-origin: bottom;
        }
        .nav-item:hover:not(.active)::after {
            transform: scaleY(1);
        }
        .nav-item:not(.active):hover {
            background: rgba(255,255,255,0.03);
            color: #fff;
            transform: translateX(4px);
        }
        .nav-item.active .icon { color: #ef4444; text-shadow: 0 0 10px rgba(239,68,68,0.5); }
        
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(239,68,68,0.5); }
        
        /* Cursor Follower */
        

        /* Movie Card 3D Effect */
        .movie-card-container {
            perspective: 1000px;
        }
        .movie-card {
            transform-style: preserve-3d;
            transition: transform 0.1s;
        }
        
        .chart-bar {
            transform-origin: bottom;
            transform: scaleY(0);
        }

        /* Ambient Background */
        .bg-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(at 0% 0%, rgba(239, 68, 68, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.08) 0px, transparent 50%);
            z-index: -2;
        }
        .noise {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)"/%3E%3C/svg%3E');
            opacity: 0.03;
            pointer-events: none;
            z-index: -1;
        }
    </style>



</head>
<body class="h-screen w-screen flex relative selection:bg-red-500/30" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <?php include __DIR__ . '/components/cursor.php'; ?>
<div id="barba-container" class="flex h-full w-full" data-barba="container" data-barba-namespace="admin_dashboard" x-data="adminDashboard()" x-init="initDashboard()">


<div class="bg-mesh"></div>
    <div class="noise"></div>

    <!-- Sidebar -->
    <aside class="sidebar w-64 h-full glass-panel flex flex-col relative z-20 shrink-0">
        <div class="p-8 flex items-center gap-4 sidebar-brand">
            <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] relative overflow-hidden group cursor-pointer">
                <div class="absolute inset-0 bg-white/20 scale-0 group-hover:scale-100 transition-transform rounded-xl rounded-full opacity-0 group-hover:opacity-100 duration-300"></div>
                <span class="material-symbols-outlined text-white font-bold relative z-10 text-[20px]">dashboard_customize</span>
            </div>
            <div>
                <span class="text-xl font-bold tracking-tighter uppercase block leading-none">NEXUS</span>
                <span class="text-[10px] text-white/50 tracking-widest uppercase font-semibold">Command Center</span>
            </div>
        </div>
        
        <nav class="flex-1 px-4 mt-4 space-y-1 overflow-y-auto">
            <template x-for="item in navItems" :key="item.id">
                <a href="#" @click.prevent="switchTab(item.id)" 
                   :class="{'active': currentTab === item.id}"
                   class="nav-item flex items-center gap-4 py-3 px-4 rounded-xl text-white/50 font-medium cursor-pointer gs-nav-item">
                    <span class="material-symbols-outlined icon text-[20px]" x-text="item.icon"></span>
                    <span class="text-sm" x-text="item.label"></span>
                </a>
            </template>
        </nav>
        
        <div class="p-6">
            <button onclick="handleLogout()" 
                    class="flex items-center gap-3 py-2 px-4 text-white/50 hover:text-red-400 transition-colors rounded-xl hover:bg-red-500/10 gs-nav-item w-full text-left cursor-pointer">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span class="text-sm font-medium">Terminate Session</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative z-10 bg-[#030305]/50">
        
        <!-- Header -->
        <header class="header h-24 flex items-center justify-between px-10 shrink-0 border-b border-white/5 backdrop-blur-md relative z-50">
            <div class="flex items-center gap-4 bg-white/[0.03] border border-white/10 rounded-2xl px-5 py-3 w-[400px] focus-within:border-red-500/50 focus-within:bg-white/[0.05] transition-all duration-300 shadow-inner gs-header-item group">
                <span class="material-symbols-outlined text-white/40 group-focus-within:text-red-400 transition-colors">search</span>
                <input type="text" placeholder="Search databases..." class="bg-transparent border-none outline-none text-white text-sm w-full placeholder-white/30 font-medium">
                <div class="px-2 py-0.5 rounded bg-white/10 text-[10px] text-white/50 mono border border-white/5">⌘K</div>
            </div>
            
            <div class="flex items-center gap-6 relative">
                <!-- Notifications -->
                <div class="gs-header-item" @click.away="notificationsOpen = false">
                    <button @click="notificationsOpen = !notificationsOpen" class="relative w-10 h-10 rounded-full bg-white/5 hover:bg-white/15 border border-white/5 hover:border-white/20 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-[0_0_20px_rgba(255,255,255,0.15)] group">
                        <span class="material-symbols-outlined text-white/70 group-hover:text-white transition-all duration-300 group-hover:rotate-12 group-hover:scale-110">notifications</span>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full shadow-[0_0_8px_#ef4444]" x-show="unreadNotifications > 0">
                            <span class="absolute inset-0 rounded-full bg-red-500 opacity-0 group-hover:opacity-100 group-hover:animate-ping"></span>
                        </span>
                    </button>
                
                    <?php include __DIR__ . '/components/notifications.php'; ?>
                </div>
                
                <!-- Profile -->
                <div class="flex items-center gap-4 pl-6 border-l border-white/10 cursor-pointer group gs-header-item">
                    <div class="text-right hidden md:block">
                        <!-- Dynamically updates header text when userName changes in Alpine -->
                        <p class="text-sm font-bold text-white group-hover:text-red-400 transition-colors tracking-wide" 
                        x-text="userName">
                            <?php echo htmlspecialchars($userName); ?>
                        </p>
                        <p class="text-xs text-white/40 mono uppercase"><?php echo htmlspecialchars($userRole); ?></p>
                    </div>
                    <div class="relative w-12 h-12">
                        <!-- Dynamically regenerates avatar initials when userName changes -->
                        <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(userName) + '&background=ef4444&color=fff&bold=true'"
                            :alt="userName"
                            src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=ef4444&color=fff&bold=true" 
                            alt="<?= htmlspecialchars($userName) ?>" 
                            class="w-full h-full rounded-full border border-white/20 group-hover:border-red-500/50 group-hover:shadow-[0_0_15px_rgba(239,68,68,0.3)] transition-all duration-300 relative z-10">
                        
                        <template x-if="selectedBorder">
                            <img :src="selectedBorder" class="absolute inset-0 w-full h-full object-cover z-20 pointer-events-none scale-[1.3] drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] mix-blend-screen opacity-90">
                        </template>
                        <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-[#030305] rounded-full z-30"></div>
                    </div>
                </div>

                <!-- Dropdown -->
                
            </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-10 tab-content relative scroll-smooth" >
            <?php include __DIR__ . '/views/dashboard.php'; ?>
            <?php include __DIR__ . '/views/movies.php'; ?>
            <?php include __DIR__ . '/views/users.php'; ?>
            <?php include __DIR__ . '/views/sessions.php'; ?>
            <?php include __DIR__ . '/views/reports.php'; ?>
            <?php include __DIR__ . '/views/profile.php'; ?>
            <?php include __DIR__ . '/views/shop.php'; ?>
        </div>
    </main>

    <script>
        function adminDashboard() {
            return {
                currentTab: 'dashboard',
                isTabAnimating: false,
                sidebarOpen: true,
                notificationsOpen: false,
                notifications: [
                    {
                        id: 1, 
                        read: false,
                        gradientFrom: 'from-red-500/10',
                        bgClass: 'bg-red-500/10',
                        borderClass: 'border-red-500/20',
                        hoverBgClass: 'group-hover:bg-red-500/20',
                        hoverBorderClass: 'group-hover:border-red-500/50',
                        hoverShadowClass: 'group-hover:shadow-[0_0_15px_rgba(239,68,68,0.2)]',
                        icon: 'movie',
                        iconColorClass: 'text-red-400',
                        hoverIconColorClass: 'text-red-400',
                        message: 'New asset ingested: <span class="text-white font-bold group-hover:text-red-400 transition-colors">Dune: Part Two</span>',
                        time: '2m ago',
                        indicatorClass: 'bg-red-500 shadow-[0_0_8px_#ef4444]'
                    },
                    {
                        id: 2, 
                        read: false,
                        gradientFrom: 'from-indigo-500/10',
                        bgClass: 'bg-indigo-500/10',
                        borderClass: 'border-indigo-500/20',
                        hoverBgClass: 'group-hover:bg-indigo-500/20',
                        hoverBorderClass: 'group-hover:border-indigo-500/50',
                        hoverShadowClass: 'group-hover:shadow-[0_0_15px_rgba(99,102,241,0.2)]',
                        icon: 'trending_up',
                        iconColorClass: 'text-indigo-400',
                        hoverIconColorClass: 'text-indigo-400',
                        message: 'Traffic spike detected in <span class="font-bold text-white group-hover:text-indigo-400 transition-colors">US-East</span> sector',
                        time: '12m ago',
                        indicatorClass: 'bg-indigo-500 shadow-[0_0_8px_#6366f1]'
                    },
                    {
                        id: 3, 
                        read: true,
                        gradientFrom: 'from-green-500/10',
                        bgClass: 'bg-white/5',
                        borderClass: 'border-white/10',
                        hoverBgClass: 'group-hover:bg-green-500/20',
                        hoverBorderClass: 'group-hover:border-green-500/50',
                        hoverShadowClass: 'group-hover:shadow-[0_0_15px_rgba(34,197,94,0.2)]',
                        icon: 'security_update_good',
                        iconColorClass: 'text-white/50',
                        hoverIconColorClass: 'group-hover:text-green-400',
                        message: 'System defense matrix updated successfully.',
                        time: '1h ago',
                        indicatorClass: ''
                    }
                ],
                get unreadNotifications() {
                    return this.notifications.filter(n => !n.read).length;
                },
                markAllRead() {
                    this.notifications.forEach(n => n.read = true);
                },
                navItems: [
                    { id: 'dashboard', label: 'Overview', icon: 'monitoring' },
                    { id: 'movies', label: 'Movies', icon: 'movie' },
                    { id: 'rooms', label: 'Sessions', icon: 'satellite_alt' },
                    { id: 'users', label: 'Directory', icon: 'shield_person' },
                    { id: 'reports', label: 'Reports Analysis', icon: 'report' },
                    { id: 'shop', label: 'Shop', icon: 'storefront' },
                    { id: 'profile', label: 'Profile', icon: 'person' }
                ],
                avatarModalOpen: false,
                selectedAvatar: 'https://ui-avatars.com/api/?name=SA&background=050505&color=ef4444&bold=true',
                presetAvatars: [

                    'https://ui-avatars.com/api/?name=SA&background=050505&color=ef4444&bold=true',
                    'https://ui-avatars.com/api/?name=01&background=random&color=fff&bold=true',
                    'https://ui-avatars.com/api/?name=MK&background=random&color=fff&bold=true',
                    'https://ui-avatars.com/api/?name=X&background=random&color=fff&bold=true',
                    'https://ui-avatars.com/api/?name=V&background=random&color=fff&bold=true',
                    'https://ui-avatars.com/api/?name=Z&background=random&color=fff&bold=true'
                ],
                selectedBorder: "/frontend/assets/borders/Angel's wing(Dark).gif",
                borders: [
                    { id: 'none', url: '' },
                    { id: 'angel', url: "/frontend/assets/borders/Angel's wing(Dark).gif" },
                    { id: 'encom', url: '/frontend/assets/borders/Encom grid.gif' },
                    { id: 'glitch', url: '/frontend/assets/borders/Glitch.gif' },
                    { id: 'hallu', url: '/frontend/assets/borders/Hallunication.gif' },
                    { id: 'pandoran', url: '/frontend/assets/borders/Pandoran sea.gif' },
                    { id: 'satiru', url: "/frontend/assets/borders/Satiru's unlimited void.gif" },
                    { id: 'spray', url: '/frontend/assets/borders/Spray doodle.gif' },
                    { id: 'sukuna', url: "/frontend/assets/borders/Sukuna's slashes.gif" },
                    { id: 'anomaly', url: '/frontend/assets/borders/The anomaly.gif' }
                ],
                stats: [
                    { label: 'Active Users', value: '12.4k', change: '+12%', icon: 'group' },
                    { label: 'Live Streams', value: '342', change: '+5%', icon: 'rss_feed' },
                    { label: 'Media Assets', value: '8,921', change: '+2%', icon: 'dns' },
                    { label: 'System Load', value: '24%', change: 'Stable', icon: 'memory' }
                ],
                // Dynamic Movie Data
                allGenres: <?= json_encode($allGenres ?? []); ?>,
                movies: <?= json_encode($movies ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                movieModalOpen: false,
                editingMovie: null,
                newMovie: { title: '', year: '', rating: 0, genres: [], img: '', description: '', video_url: '', comments: [] },

                openAddMovieModal() {
                    this.editingMovie = null;
                    this.newMovie = {
                        title: '',
                        year: new Date().getFullYear(),
                        rating: 0,
                        genres: [],
                        img: '',
                        description: '',
                        video_url: '',
                        comments: []
                    };
                    this.movieModalOpen = true;
                },

                openEditMovieModal(movie) {
                    this.editingMovie = movie;
                    const genreList = (movie.genre && movie.genre !== 'N/A') 
                        ? movie.genre.split(',').map(g => g.trim()) 
                        : [];

                    this.newMovie = {
                        title: movie.title || '',
                        description: movie.description || '',
                        year: movie.year || new Date().getFullYear(),
                        rating: movie.rating || 0,
                        genres: genreList,
                        img: movie.img || '',
                        video_url: movie.video_url || '',
                        comments: movie.comments || []
                    };
                    this.movieModalOpen = true;
                }, // Added missing comma here
                toggleGenre(genreName) {
                    if (!Array.isArray(this.newMovie.genres)) {
                        this.newMovie.genres = [];
                    }
                    if (this.newMovie.genres.includes(genreName)) {
                        this.newMovie.genres = this.newMovie.genres.filter(g => g !== genreName);
                    } else {
                        this.newMovie.genres.push(genreName);
                    }
                },
                handleFileUpload(event, callback) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => callback(e.target.result);
                        reader.readAsDataURL(file);
                    }
                },
                async saveMovie() {
                    if (!this.newMovie.title.trim()) {
                        alert('Please enter a movie title');
                        return;
                    }

                    // Include the ID so the backend can distinguish between ADD and EDIT
                    const payload = {
                        id: this.editingMovie ? this.editingMovie.id : null,
                        ...this.newMovie,
                        genre: this.newMovie.genres.join(', ')
                    };

                    try {
                        const response = await fetch('../backend/save_movie.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (result.success) {
                            if (this.editingMovie) {
                                const index = this.movies.findIndex(m => m.id === this.editingMovie.id);
                                if (index !== -1) {
                                    this.movies[index] = { 
                                        ...this.movies[index], 
                                        ...this.newMovie,
                                        img: result.movie?.poster || this.newMovie.img,
                                        genre: payload.genre || 'N/A'
                                    };
                                }
                            } else {
                            this.movies.unshift({
                                id: result.movie?.id || Date.now(),
                                title: this.newMovie.title,
                                description: this.newMovie.description || '',
                                img: result.movie?.poster || this.newMovie.img,
                                video_url: this.newMovie.video_url || '',
                                year: this.newMovie.year || new Date().getFullYear(),
                                rating: this.newMovie.rating || 0,
                                view_count: 0,
                                total_comments: 0,
                                genre: payload.genre || 'N/A'
                            });
                        }
                            this.movieModalOpen = false;
                        } else {
                            alert(result.message || 'Failed to save movie');
                        }
                    } catch (err) {
                        console.error('Error saving movie:', err);
                        alert('An unexpected error occurred while saving.');
                    }
                },
                async deleteMovie(id) {
                    if (!confirm('Are you sure you want to delete this media asset?')) return;
                    try {
                        await fetch(`../backend/delete_movie.php?id=${id}`, { method: 'DELETE' });
                    } catch (e) {
                        console.error(e);
                    }
                    this.movies = this.movies.filter(m => m.id !== id);
                },
                
                reportStats: {
                    total: 142,
                    pending: 28,
                    read: 114
                },
                reportsList: [
                    { id: 'REP-001', type: 'Bug Report', status: 'Pending', date: '2026-07-19', user: 'Alex Johnson', priority: 'High', excerpt: 'Video playback stuttering on Matrix...' },
                    { id: 'REP-002', type: 'Content Issue', status: 'Pending', date: '2026-07-18', user: 'Sarah Connor', priority: 'Medium', excerpt: 'Missing subtitles for Dune part 2...' },
                    { id: 'REP-003', type: 'User Report', status: 'Read', date: '2026-07-17', user: 'Kenji Murakami', priority: 'Low', excerpt: 'User LP-331 spamming in chat...' },
                    { id: 'REP-004', type: 'Billing', status: 'Read', date: '2026-07-15', user: 'Lisa Palmer', priority: 'High', excerpt: 'Double charged for premium sub...' },
                    { id: 'REP-005', type: 'Bug Report', status: 'Read', date: '2026-07-14', user: 'David Wilson', priority: 'Medium', excerpt: 'App crashes when switching tabs...' }
                ],
                
                selectedRoom: null,
                roomModalOpen: false,
                mockRoomUsers: [],
                viewRoom(room) {
                    this.selectedRoom = room;
                    this.mockRoomUsers = Array.from({ length: Math.min(room.users, 18) }, (_, i) => ({
                        id: i,
                        name: 'Viewer_' + (Math.floor(Math.random() * 9000) + 1000),
                        avatar: 'https://ui-avatars.com/api/?name=V' + i + '&background=random&color=fff&bold=true'
                    }));
                    // Ensure host is first
                    if (this.mockRoomUsers.length > 0) {
                        this.mockRoomUsers[0].name = room.host;
                        this.mockRoomUsers[0].isHost = true;
                        this.mockRoomUsers[0].avatar = 'https://ui-avatars.com/api/?name=' + room.host.substring(0, 2) + '&background=050505&color=ef4444&bold=true';
                    }
                    this.roomModalOpen = true;
                },
                disbandRoom(roomId) {
                    gsap.to('#room-card-' + roomId, {
                        scale: 0.8,
                        opacity: 0,
                        duration: 0.4,
                        ease: 'power2.in',
                        onComplete: () => {
                            this.rooms = this.rooms.filter(r => r.id !== roomId);
                            if (this.selectedRoom && this.selectedRoom.id === roomId) {
                                this.roomModalOpen = false;
                            }
                        }
                    });
                },
                rooms: [
                    { id: 1, name: 'Sci-Fi Watch Party', host: 'AX-992', users: 45 },
                    { id: 2, name: 'Horror Night 💀', host: 'SC-102', users: 120 },
                    { id: 3, name: 'Anime Marathon', host: 'KM-404', users: 89 },
                    { id: 4, name: 'Nolan Fans', host: 'DW-777', users: 210 },
                    { id: 5, name: 'Classic 80s', host: 'LP-331', users: 34 }
                ],
                usersList: [
                    { id: 1, name: 'Alex Johnson', email: 'alex@nexus.net', status: 'Online', role: 'Premium' },
                    { id: 2, name: 'Sarah Connor', email: 'sarah@skynet.com', status: 'Offline', role: 'Standard' },
                    { id: 3, name: 'Kenji Murakami', email: 'kenji@cyber.jp', status: 'Online', role: 'Moderator' },
                    { id: 4, name: 'David Wilson', email: 'david@corp.org', status: 'Online', role: 'Standard' },
                    { id: 5, name: 'Lisa Palmer', email: 'lisa@nexus.net', status: 'Offline', role: 'Premium' },
                ],
                //(used for header and profile card preview)
                userName: <?= json_encode($userName); ?>,
                userEmail: <?= json_encode($userEmail); ?>,

                //(bound to input fields only)
                profileForm: {
                    name: <?= json_encode($userName); ?>,
                    email: <?= json_encode($userEmail); ?>
                },

                profileFormLoading: false,
                passwordForm: {
                    current_password: '',
                    new_password: '',
                    confirm_password: ''
                },
                // Inside return object of function adminDashboard()
                profileFeedback: {
                    message: '',
                    isError: false
                },
                profileLoading: false,

                // Validation Methods Inside Alpine Scope
                validateProfileInfo() {
                    let errors = [];
                    const name = (this.profileForm.name || '').trim();
                    const email = (this.profileForm.email || '').trim();

                    const namePattern = /^[\p{L}\s'-]+$/u;
                    if (name.length === 0) {
                        errors.push('Display Name is required.');
                    } else if (name.length < 2 || name.length > 50) {
                        errors.push('Display Name must be between 2 and 50 characters.');
                    } else if (!namePattern.test(name)) {
                        errors.push('Display Name contains invalid characters.');
                    }

                    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (email.length === 0) {
                        errors.push('Email address is required.');
                    } else if (!emailPattern.test(email)) {
                        errors.push('Please enter a valid email address.');
                    }

                    if (errors.length > 0) {
                        this.profileFeedback.isError = true;
                        this.profileFeedback.message = errors.join(' | ');
                        return false;
                    }
                    return true;
                },

                validatePasswordForm() {
                    let errors = [];
                    const currentPassword = this.passwordForm.current_password || '';
                    const newPassword = this.passwordForm.new_password || '';
                    const confirmPassword = this.passwordForm.confirm_password || '';

                    if (currentPassword.length === 0) {
                        errors.push('Current password is required.');
                    }

                    if (newPassword.length === 0) {
                        errors.push('New password is required.');
                    } else {
                        let passwordCriteria = [];
                        if (newPassword.length < 8) passwordCriteria.push("8+ characters");
                        if (!/[A-Z]/.test(newPassword)) passwordCriteria.push("1 uppercase letter");
                        if (!/[a-z]/.test(newPassword)) passwordCriteria.push("1 lowercase letter");
                        if (!/[0-9]/.test(newPassword)) passwordCriteria.push("1 number");
                        if (!/[!@#$%^&*(),.?":{}|<>_\-]/.test(newPassword)) passwordCriteria.push("1 special character");

                        if (passwordCriteria.length > 0) {
                            errors.push('New password missing: ' + passwordCriteria.join(', '));
                        }
                    }

                    if (newPassword !== confirmPassword) {
                        errors.push('New password and confirmation password do not match.');
                    }

                    if (errors.length > 0) {
                        this.profileFeedback.isError = true;
                        this.profileFeedback.message = errors.join(' | ');
                        return false;
                    }
                    return true;
                },

                async saveProfileInfo() {
                    // Call the method attached to Alpine component state
                    if (!this.validateProfileInfo()) return;

                    this.profileLoading = true;
                    this.profileFeedback.message = '';

                    try {
                        const response = await fetch('../backend/update_profile.php', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                action: 'update_info',
                                name: this.profileForm.name,
                                email: this.profileForm.email
                            })
                        });

                        const result = await response.json();
                        this.profileFeedback.isError = !result.success;
                        this.profileFeedback.message = result.message;

                        if (result.success && result.user) {
                            this.userName = result.user.name;
                            this.userEmail = result.user.email;
                        }
                    } catch (err) {
                        this.profileFeedback.isError = true;
                        this.profileFeedback.message = 'Network error while updating profile.';
                    } finally {
                        this.profileLoading = false;
                    }
                },

                async changePassword() {
                    // Call the method attached to Alpine component state
                    if (!this.validatePasswordForm()) return;

                    this.profileLoading = true;
                    this.profileFeedback.message = '';

                    try {
                        const response = await fetch('../backend/update_profile.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'change_password',
                                ...this.passwordForm
                            })
                        });

                        const result = await response.json();
                        this.profileFeedback.isError = !result.success;
                        this.profileFeedback.message = result.message;

                        if (result.success) {
                            this.passwordForm.current_password = '';
                            this.passwordForm.new_password = '';
                            this.passwordForm.confirm_password = '';
                        }
                    } catch (err) {
                        this.profileFeedback.isError = true;
                        this.profileFeedback.message = 'Network error while updating password.';
                    } finally {
                        this.profileLoading = false;
                    }
                },

                // Add these properties to your adminDashboard() return object
                deleteModalOpen: false,
                deletePassword: '',
                deleteLoading: false,

                async deleteAccount() {
                    if (!this.deletePassword) {
                        alert('Please enter your password to confirm deletion.');
                        return;
                    }

                    this.deleteLoading = true;

                    try {
                        const response = await fetch('../backend/delete_account.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ password: this.deletePassword })
                        });

                        const result = await response.json();

                        if (result.success) {
                            window.location.href = result.redirect || 'login.php';
                        } else {
                            alert(result.message || 'Failed to delete account.');
                        }
                    } catch (err) {
                        alert('A network error occurred.');
                    } finally {
                        this.deleteLoading = false;
                    }
                },
                
                switchTab(tab) {
                    // Ignore clicks on the active tab, and ignore rapid double-clicks
                    // while a transition is already in flight (this used to let GSAP
                    // overwrite the in-flight tween and silently drop onComplete,
                    // which is why the panel could get stuck showing stale content).
                    if (this.currentTab === tab || this.isTabAnimating) return;
                    this.isTabAnimating = true;

                    const tabContent = document.querySelector('.tab-content');
                    const currentPanel = tabContent ? tabContent.querySelector(`:scope > [data-tab-panel="${this.currentTab}"]`) : null;

                    const showNextPanel = () => {
                        this.currentTab = tab;

                        const nextPanel = tabContent ? tabContent.querySelector(`:scope > [data-tab-panel="${tab}"]`) : null;
                        if (!nextPanel) { this.isTabAnimating = false; return; }

                        // Wipe any leftover inline styles from a previous visit before
                        // animating in, so panels can never get stuck mid-transition.
                        gsap.set(nextPanel, { clearProps: "all" });
                        nextPanel.style.display = "block";

                        gsap.fromTo(nextPanel,
                            { opacity: 0, y: 24, scale: 0.98 },
                            {
                                opacity: 1, y: 0, scale: 1, duration: 0.35, ease: "power2.out",
                                overwrite: "auto",
                                onComplete: () => {
                                    gsap.set(nextPanel, { clearProps: "all" });
                                    this.isTabAnimating = false;
                                }
                            }
                        );

                        gsap.fromTo(nextPanel.querySelectorAll(".stagger-item"),
                            { opacity: 0, y: 50, scale: 0.95 },
                            { opacity: 1, y: 0, scale: 1, duration: 0.4, stagger: 0.04, ease: "power2.out", clearProps: "all" }
                        );

                        // Re-animate the traffic chart whenever the dashboard is (re)entered
                        if (tab === 'dashboard') {
                            gsap.fromTo(nextPanel.querySelectorAll(".chart-bar"),
                                { scaleY: 0 },
                                { scaleY: 1, duration: 0.6, stagger: 0.03, ease: "elastic.out(1, 0.8)", delay: 0.15 }
                            );
                        }
                    };

                    if (!currentPanel) {
                        // Nothing visible to animate out (first paint) — just show the new tab.
                        this.$nextTick(showNextPanel);
                        return;
                    }

                    gsap.to(currentPanel, {
                        opacity: 0, y: -16, scale: 0.98, duration: 0.25, ease: "power2.inOut",
                        overwrite: "auto",
                        onComplete: () => {
                            gsap.set(currentPanel, { clearProps: "all" });
                            currentPanel.style.display = "none";
                            this.$nextTick(showNextPanel);
                        }
                    });
                },
                
                initDashboard() {
                    gsap.config({ nullTargetWarn: false });

                    // Movie Card 3D Hover
                    this.$nextTick(() => {
                        document.querySelectorAll('.movie-card-container').forEach(container => {
                            const card = container.querySelector('.movie-card');
                            if (!card) return;
                            container.addEventListener('mousemove', (e) => {
                                const rect = container.getBoundingClientRect();
                                const x = e.clientX - rect.left;
                                const y = e.clientY - rect.top;
                                const centerX = rect.width / 2;
                                const centerY = rect.height / 2;
                                const rotateX = ((y - centerY) / centerY) * -10;
                                const rotateY = ((x - centerX) / centerX) * 10;

                                gsap.to(card, {
                                    rotateX: rotateX,
                                    rotateY: rotateY,
                                    duration: 0.5,
                                    ease: "power2.out"
                                });
                            });
                            container.addEventListener('mouseleave', () => {
                                gsap.to(card, {
                                    rotateX: 0,
                                    rotateY: 0,
                                    duration: 0.8,
                                    ease: "elastic.out(1, 0.5)"
                                });
                            });
                        });

                        setTimeout(() => {
                            // Initial load animation sequence — scoped to the tab that's
                            // actually visible on first paint so hidden panels' items
                            // aren't silently pre-animated in the background.
                            const initialPanel = document.querySelector(`.tab-content > [data-tab-panel="${this.currentTab}"]`);
                            const tl = gsap.timeline();
                            
                            tl.fromTo(".sidebar-brand", 
                                { y: -20, opacity: 0 }, 
                                { y: 0, opacity: 1, duration: 0.4, ease: "power2.out", clearProps: "all" }
                              )
                              .fromTo(".gs-nav-item", 
                                { x: -30, opacity: 0 }, 
                                { x: 0, opacity: 1, stagger: 0.04, duration: 0.4, ease: "power2.out", clearProps: "all" }, 
                                "-=0.4"
                              )
                              .fromTo(".gs-header-item", 
                                { y: -20, opacity: 0 }, 
                                { y: 0, opacity: 1, stagger: 0.04, duration: 0.4, ease: "power2.out", clearProps: "all" }, 
                                "-=0.4"
                              )
                              .fromTo(initialPanel ? initialPanel.querySelectorAll(".stagger-item") : ".stagger-item", 
                                  { opacity: 0, y: 50, scale: 0.95 }, 
                                  { opacity: 1, y: 0, scale: 1, stagger: 0.04, duration: 0.4, ease: "power2.out", clearProps: "all" }, 
                                  "-=0.2"
                              );
        
                            gsap.fromTo(initialPanel ? initialPanel.querySelectorAll(".chart-bar") : ".chart-bar", 
                                { scaleY: 0 }, 
                                { scaleY: 1, duration: 0.6, stagger: 0.03, ease: "elastic.out(1, 0.8)", delay: 0.15 }
                            );
                        }, 50);
                    });
                }
            }
        }

         async function handleLogout() {
            const btn = document.getElementById('logoutBtn');

            // Show the loader immediately on click, before the network
            // request even starts, instead of only after logout.php responds.
            if (typeof window.showPageLoader === 'function') {
                window.showPageLoader();
            }

            try {
                // Optional UI Feedback (disable button during request)
                if (btn) btn.style.opacity = '0.5';

                const response = await fetch('../backend/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to login page
                    window.location.href = data.redirect;
                } else {
                    console.error('Logout failed');
                    if (btn) btn.style.opacity = '1';
                    if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
                }
            } catch (error) {
                console.error('Error during sign out:', error);
                if (btn) btn.style.opacity = '1';
                if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
            }
        }
    </script>
    

    


    </div>



    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js"></script>
    <script>
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') { gsap.registerPlugin(ScrollTrigger); }
        function initAnimations(container = document) {
            if (typeof gsap === 'undefined') return;
            const q = gsap.utils.selector(container);
            const tl = gsap.timeline();
            
            const heroTitleWords = q('.gs-word');
            if(heroTitleWords.length > 0) {
                gsap.set(heroTitleWords, {opacity: 0, y: 40});
                
                tl.fromTo(q('.gs-hero-content .gs-reveal'), 
                    { opacity: 0, y: 30 },
                    { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: 'power3.out' }
                )
                .to(heroTitleWords,
                    { opacity: 1, y: 0, duration: 0.8, stagger: 0.05, ease: 'back.out(1.7)' },
                    "-=0.6"
                )
                .fromTo(q('.gs-hero-visual'),
                    { opacity: 0, scale: 0.9, x: 50 },
                    { opacity: 1, scale: 1, x: 0, duration: 1, ease: 'power3.out' },
                    "-=0.8"
                );
            }

            q('.gs-reveal-up').forEach(elem => {
                gsap.fromTo(elem,
                    { opacity: 0, y: 50 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.8, 
                        ease: 'power3.out',
                        scrollTrigger: {
                            trigger: elem,
                            start: "top 85%",
                            toggleActions: "play none none reverse"
                        }
                    }
                );
            });

            q('.gs-movie-card').forEach((elem, i) => {
                gsap.fromTo(elem,
                    { opacity: 0, scale: 0.8, y: 40 },
                    {
                        opacity: 1, 
                        scale: 1, 
                        y: 0, 
                        duration: 0.6, 
                        delay: (i % 4) * 0.1,
                        ease: 'back.out(1.4)',
                        scrollTrigger: {
                            trigger: elem.parentElement,
                            start: "top 80%"
                        }
                    }
                );
            });

            q('.gs-step').forEach((elem, i) => {
                gsap.fromTo(elem,
                    { opacity: 0, y: 40 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.6,
                        ease: 'power2.out',
                        scrollTrigger: {
                            trigger: elem.parentElement,
                            start: "top 80%"
                        }
                    }
                );
            });
        }

        // Initialize animations on first load
        initAnimations();

    </script>
    <script src="/js/barba_setup.js"></script>

</body>
</html>
