<?php
require_once __DIR__ . '/components/session_boot.php';

// Block access if not authenticated OR if the user's role is not admin or moderator
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) || 
    !in_array($_SESSION['user_role'], ['admin', 'moderator'])
) {
    header("Location: login.php?error=" . urlencode("Access denied. Admin or Moderator privileges required."));
    exit();
}

$userName  = $_SESSION['user_name']  ?? 'Agent';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole  = $_SESSION['user_role']  ?? 'user';
$userId    = (int)($_SESSION['user_id'] ?? 0);

$avatarUrl = '';
$borderPreview = '';
$activeBorderId = 0;
if ($userId > 0) {
    try {
        require_once __DIR__ . '/../conn.php';
        require_once __DIR__ . '/../profile_media_helper.php';
        $media = getUserProfileMedia($conn, $userId);
        $avatarUrl = $media['avatar_url'] ?? '';
        $borderPreview = $media['border_preview'] ?? '';
        $activeBorderId = (int)($media['border_id'] ?? 0);
    } catch (Throwable $e) {
        error_log('admin dashboard boot profile media: ' . $e->getMessage());
    }
}

$bootAvatarSrc = $avatarUrl !== ''
    ? $avatarUrl
    : ('https://ui-avatars.com/api/?name=' . rawurlencode($userName) . '&background=ef4444&color=fff&bold=true');

session_write_close();
?>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    window.CURRENT_USER_ID = <?= json_encode($userId) ?>;
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/components/head_boot.php'; ?>
    <title>Nexus - Admin Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrambleTextPlugin.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/CustomEase.min.js" crossorigin="anonymous"></script>
    <script>if (window.gsap) gsap.config({ nullTargetWarn: false });</script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/teleport@3.14.1/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    
    <style>
        html, body { color-scheme: dark; }
        body { 
            font-family: 'Space Grotesk', sans-serif; 
            background-color: #030305; 
            color: #ffffff; 
            overflow: hidden;
        }
        select {
            color-scheme: dark;
            background-color: #0a0a0f;
            color: #f5f5f5;
        }
        select option,
        select optgroup {
            background-color: #0a0a0f;
            color: #f5f5f5;
        }
        .mono { font-family: 'JetBrains Mono', monospace; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.015);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-right: 1px solid rgba(255, 255, 255, 0.04);
        }
        .admin-main {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        .admin-main > .header {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 6rem;
            z-index: 50;
            background: rgba(3, 3, 5, 0.28);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }
        .admin-main > .tab-content {
            position: absolute !important;
            inset: 0 !important;
            overflow: hidden;
            z-index: 0;
        }
        .admin-main [data-tab-panel] {
            padding-top: 7.5rem !important;
        }
        .glass-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        .glass-card.allow-overflow {
            overflow: visible;
        }
        .glass-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }
        
        .nav-item {
            transition: color 0.35s cubic-bezier(0.16, 1, 0.3, 1), background 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .nav-item.active {
            color: #fff;
            background: transparent;
        }
        .nav-item:not(.active):hover {
            color: #fff;
        }
        .nav-item.active .icon { color: #ef4444; text-shadow: 0 0 10px rgba(239,68,68,0.5); }
        [data-admin-nav] { position: relative; }
        .admin-nav-indicator {
            position: absolute;
            left: 1rem;
            right: 1rem;
            top: 0;
            height: 3rem;
            border-radius: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border-left: 3px solid #ef4444;
            box-shadow: inset 24px 0 32px -20px rgba(239,68,68,0.28), 0 0 24px rgba(239,68,68,0.08);
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            will-change: transform;
        }
        .glass-card,
        .gs-stat-card,
        .movie-card-container {
            --mx: 50%;
            --my: 50%;
        }
        .glass-card::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(420px circle at var(--mx) var(--my), rgba(255,255,255,0.07), transparent 42%);
            opacity: 0;
            transition: opacity 0.35s ease;
            z-index: 2;
        }
        .glass-card:hover::after { opacity: 1; }
        .admin-cursor {
            position: fixed;
            width: 280px;
            height: 280px;
            margin: -140px 0 0 -140px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 30;
            background: radial-gradient(circle, rgba(239,68,68,0.14), transparent 68%);
            mix-blend-mode: screen;
            opacity: 0;
            will-change: transform;
        }
        .ambient-orb {
            position: fixed;
            width: 42vw;
            height: 42vw;
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
            filter: blur(80px);
            opacity: 0.45;
            will-change: transform;
        }
        .ambient-orb-a {
            top: -12%;
            left: -8%;
            background: rgba(239, 68, 68, 0.16);
        }
        .ambient-orb-b {
            right: -10%;
            bottom: -16%;
            background: rgba(79, 70, 229, 0.16);
        }
        @media (prefers-reduced-motion: reduce) {
            .admin-cursor,
            .ambient-orb,
            .admin-nav-indicator { display: none !important; }
            .chart-bar { transform: none !important; }
            .nav-item, .glass-card, .movie-card-container { transition: none !important; }
        }
        
        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(239,68,68,0.5); }

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

        :root {
            --plyr-color-main: #ef4444; /* Nexus Red */
        }
        .dash-row-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .dash-row-scroll::-webkit-scrollbar { display: none; }
    </style>



    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="../js/nexus_scripts.js?v=1789044701"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js" crossorigin="anonymous"></script>
</head>
<body class="h-screen w-screen flex relative selection:bg-red-500/30" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>
<div id="barba-container" 
     class="flex h-full w-full" 
     data-barba="container" 
     data-barba-namespace="admin_dashboard" 
     x-data="adminDashboard(<?= htmlspecialchars(json_encode([
         'user_name' => $userName,
         'email' => $userEmail,
         'avatar_url' => $avatarUrl,
         'border_preview' => $borderPreview,
         'active_border_id' => (int)$activeBorderId,
     ], JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>)"
     @view-comment="handleViewComment($event.detail)"
     x-init="initDashboard()">


<div class="bg-mesh"></div>
    <div class="noise"></div>
    <div class="ambient-orb ambient-orb-a" aria-hidden="true"></div>
    <div class="ambient-orb ambient-orb-b" aria-hidden="true"></div>
    <div class="admin-cursor" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside class="sidebar w-64 h-full glass-panel flex flex-col relative z-20 shrink-0">
        <a href="/index.php" data-barba-prevent class="p-8 flex items-center gap-4 sidebar-brand group">
            <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] relative overflow-hidden cursor-pointer">
                <div class="absolute inset-0 bg-white/20 scale-0 group-hover:scale-100 transition-transform rounded-xl rounded-full opacity-0 group-hover:opacity-100 duration-300"></div>
                <span class="material-symbols-outlined text-white font-bold relative z-10 text-[20px]">dashboard_customize</span>
            </div>
            <div>
                <span class="text-xl font-bold tracking-tighter uppercase block leading-none group-hover:text-red-400 transition-colors">NEXUS</span>
                <span class="text-[10px] text-white/50 tracking-widest uppercase font-semibold">Command Center</span>
            </div>
        </a>
        
        <nav class="flex-1 px-4 mt-4 space-y-1 overflow-y-auto" data-admin-nav>
            <div class="admin-nav-indicator" aria-hidden="true"></div>
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
            <button type="button" onclick="handleLogout()"
                    class="flex items-center gap-3 py-2 px-4 text-white/50 hover:text-red-400 transition-colors rounded-xl hover:bg-red-500/10 gs-nav-item w-full text-left cursor-pointer">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span class="text-sm font-medium">Terminate Session</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main flex-1 z-10 bg-[#030305]/50">
        
        <!-- Header -->
        <header class="header h-24 flex items-center justify-between px-10 border-b border-white/5">
            <div class="flex items-center gap-3">
            <div class="flex items-center gap-3 bg-white/[0.03] border border-white/10 rounded-2xl px-5 py-3 w-[400px] focus-within:border-red-500/50 focus-within:bg-white/[0.05] transition-all duration-300 shadow-inner gs-header-item group">
                <span class="material-symbols-outlined text-white/40 group-focus-within:text-red-400 transition-colors">search</span>
                <input type="text"
                       x-model="searchQuery"
                       @input="onAdminSearch()"
                       :placeholder="adminSearchPlaceholder"
                       class="bg-transparent border-none outline-none text-white text-sm w-full placeholder-white/30 font-medium">
                <button type="button"
                        x-show="(searchQuery || '').trim()"
                        @click="searchQuery = ''; onAdminSearch()"
                        class="text-white/30 hover:text-white transition-colors"
                        style="display: none;"
                        title="Clear search">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
            </div>
            
            <div class="flex items-center gap-6 relative">
                <!-- Notifications -->
                <div class="gs-header-item" @click.away="notificationsOpen = false">
                    <button @click="toggleNotificationPanel()" class="relative w-10 h-10 rounded-full bg-white/5 hover:bg-white/15 border border-white/5 hover:border-white/20 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-[0_0_20px_rgba(255,255,255,0.15)] group">
                        <span class="material-symbols-outlined text-white/70 group-hover:text-white transition-all duration-300 group-hover:rotate-12 group-hover:scale-110">notifications</span>
                        <template x-if="unreadNotifications > 0">
                            <span class="absolute -top-0.5 -right-0.5 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 border border-white/50 text-[9px] font-bold text-white items-center justify-center" x-text="unreadNotifications"></span>
                            </span>
                        </template>
                    </button>
                
                    <?php include __DIR__ . '/components/notifications.php'; ?>
                </div>
                
                <!-- Top Bar Profile Header -->
                <div @click="switchTab('profile')" class="flex items-center gap-4 pl-6 border-l border-white/10 cursor-pointer group gs-header-item">
                    <div class="text-right hidden md:block">
                        <!-- 1. Dynamically displays the confirmed name from saveProfile() -->
                        <p class="text-sm font-bold text-white group-hover:text-red-400 transition-colors tracking-wide" x-text="displayName">
                            <?php echo htmlspecialchars($userName); ?>
                        </p>
                        <p class="text-xs text-white/40 mono uppercase">
                            <?php echo htmlspecialchars($userRole); ?>
                        </p>
                    </div>
                    <div class="relative w-12 h-12 overflow-visible shrink-0" style="width: 3rem; height: 3rem;">
                        <div class="absolute inset-0 z-10 overflow-hidden rounded-full scale-[1.18] group-hover:shadow-[0_0_15px_rgba(239,68,68,0.3)] transition-all duration-300" :class="selectedBorder ? '' : 'ring-1 ring-white/20 group-hover:ring-red-500/50'">
                            <img :src="selectedAvatar" 
                                :alt="displayName"
                                src="<?= htmlspecialchars($bootAvatarSrc, ENT_QUOTES, 'UTF-8') ?>" 
                                class="absolute inset-0 h-full w-full object-cover"
                                style="object-fit: cover;">
                        </div>
                        <?php if ($borderPreview !== ''): ?>
                        <img src="<?= htmlspecialchars($borderPreview, ENT_QUOTES, 'UTF-8') ?>"
                             :src="selectedBorder || '<?= htmlspecialchars($borderPreview, ENT_QUOTES, 'UTF-8') ?>'"
                             class="absolute inset-0 z-20 h-full w-full object-contain pointer-events-none scale-[1.38] drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] mix-blend-screen opacity-90"
                             alt=""
                             decoding="sync"
                             fetchpriority="high"
                             :class="selectedBorder ? '' : 'invisible'">
                        <?php else: ?>
                        <img x-show="!!selectedBorder"
                             :src="selectedBorder"
                             class="absolute inset-0 z-20 h-full w-full object-contain pointer-events-none scale-[1.38] drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] mix-blend-screen opacity-90"
                             style="display: none;"
                             alt="">
                        <?php endif; ?>
                        <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-[#030305] rounded-full z-30"></div>
                    </div>
                </div>

                <!-- Dropdown -->
                
            </div>
        </header>

        <!-- Content Area sits below the frosted top bar in normal flow -->
        <div class="tab-content">
            <?php include __DIR__ . '/views/dashboard.php'; ?>
            <?php include __DIR__ . '/views/movies.php'; ?>
            <?php include __DIR__ . '/views/users.php'; ?>
            <?php include __DIR__ . '/views/sessions.php'; ?>
            <?php include __DIR__ . '/views/reports.php'; ?>
            <?php include __DIR__ . '/views/profile.php'; ?>
            <?php include __DIR__ . '/views/shop.php'; ?>
            <?php include __DIR__ . '/views/transactions.php'; ?>
        </div>
    </main>

    <script src="../js/admin_animations.js?v=3"></script>
    <?php include __DIR__ . '/components/barba_scripts.php'; ?>
    
</body>
</html>
