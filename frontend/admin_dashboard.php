<?php
session_start();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - Admin Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/teleport@3.14.1/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
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

        :root {
            --plyr-color-main: #ef4444; /* Nexus Red */
        }
    </style>



    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="../js/nexus_scripts.js?v=1787387210"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js" crossorigin="anonymous"></script>
</head>
<body class="h-screen w-screen flex relative selection:bg-red-500/30" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>
<div id="barba-container" 
     class="flex h-full w-full" 
     data-barba="container" 
     data-barba-namespace="admin_dashboard" 
     x-data="adminDashboard({ 
         user_name: '<?= htmlspecialchars($userName, ENT_QUOTES) ?>', 
         email: '<?= htmlspecialchars($userEmail, ENT_QUOTES) ?>' 
     })" 
     @view-comment="handleViewComment($event.detail)"
     x-init="initDashboard()">


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
                <div class="px-2 py-0.5 rounded bg-white/10 text-[10px] text-white/50 mono border border-white/5">😘</div>
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
                    <div class="relative w-12 h-12">
                        <!-- 2. Dynamically updates avatar image & alt text -->
                        <img :src="selectedAvatar" 
                            :alt="displayName"
                            src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=ef4444&color=fff&bold=true" 
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

    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
    <script src="../js/admin_animations.js?v=1"></script>
    <script src="../js/barba_setup.js?v=4"></script>
    
</body>
</html>
