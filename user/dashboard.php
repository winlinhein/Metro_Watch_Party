<?php
session_start();

// Block access if not authenticated OR if the user's role is not 'user'
$allowedRoles = ['user', 'guest'];

if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    empty($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], $allowedRoles, true)
) {
    header("Location: ../frontend/login.php?error=" . urlencode("Access denied."));
    exit();
}

$userName  = $_SESSION['user_name']  ?? 'Agent';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole  = $_SESSION['user_role']  ?? 'user';
$userId = $_SESSION['user_id'] ?? null;

// Boot avatar/border from DB so header renders instantly (no waiting on JS fetch)
$avatarUrl = '';
$borderPreview = '';
$activeBorderId = 0;
$isPremium = false;
$premiumExpiresAt = '';
if (!empty($userId) && $userRole !== 'guest') {
    try {
        require_once __DIR__ . '/../conn.php';
        require_once __DIR__ . '/../profile_media_helper.php';
        require_once __DIR__ . '/../premium_status_helper.php';
        $uid = (int)$userId;
        $avatarStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ? LIMIT 1");
        $avatarStmt->execute([$uid]);
        $avatarUrl = normalizeAvatarUrl((string)($avatarStmt->fetchColumn() ?: ''));
        $activeBorderId = getActiveBorderId($conn, $uid);
        $borderPreview = borderPreviewForId($conn, $activeBorderId);
        $premium = resolveUserPremium($conn, $uid);
        $isPremium = (bool)$premium['is_premium'];
        $premiumExpiresAt = (string)($premium['premium_expires_at'] ?? '');
    } catch (Throwable $e) {
        error_log('dashboard boot profile media: ' . $e->getMessage());
    }
}

$nexusUserBoot = [
    'username' => $userName,
    'email' => $userEmail,
    'role' => $userRole,
    'isGuest' => $userRole === 'guest',
    'avatar_url' => $avatarUrl,
    'active_border_id' => $activeBorderId,
    'border_preview' => $borderPreview,
    'is_premium' => $isPremium,
    'premium_expires_at' => $premiumExpiresAt,
];
session_write_close();
?>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://js.stripe.com/v3/"></script>
<script>
    // This safely passes the logged-in user's ID from your backend session to JS
    window.CURRENT_USER_ID = <?php echo json_encode($userId); ?>;
    window.NEXUS_USER = <?php echo json_encode($nexusUserBoot, JSON_UNESCAPED_SLASHES); ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - User Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nexus: {
                            dark: '#030305',
                            red: '#ef4444',
                            indigo: '#4f46e5'
                        }
                    },
                    fontFamily: {
                        sans: ['Space Grotesk', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <?php if ($avatarUrl !== ''): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($borderPreview !== ''): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($borderPreview, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    
    <!-- Alpine.js & GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" crossorigin="anonymous" onerror="window.gsap=window.gsap||{to:()=>({to:()=>({}),fromTo:()=>({})}),fromTo:()=>({}),from:()=>({}),set:()=>{},timeline:()=>({to:()=>({}),fromTo:()=>({}),add:()=>({}),set:()=>({})}),config:()=>{},killTweensOf:()=>{}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" crossorigin="anonymous" onerror="if(window.gsap)window.gsap.ScrollTrigger=window.gsap.ScrollTrigger||{create:()=>{},refresh:()=>{},kill:()=>{}}"></script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.1/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    
    <style>
        body { 
            font-family: 'Space Grotesk', sans-serif; 
            background-color: #030305; 
            color: #ffffff; 
            overflow: hidden; 
            cursor: none;
        }

        [x-cloak] { display: none !important; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.015);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.04);
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
        
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(239,68,68,0.5); }

        .hover-glow {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hover-glow:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px -10px rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }
        
        .icon-bounce {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .group:hover .icon-bounce {
            transform: scale(1.2) translateY(-4px);
        }

        .bg-mesh {
            position: fixed;
            top: -20%; left: -20%; right: -20%; bottom: -20%;
            background: 
                radial-gradient(at 20% 20%, rgba(239, 68, 68, 0.08) 0px, transparent 40%),
                radial-gradient(at 80% 80%, rgba(79, 70, 229, 0.08) 0px, transparent 40%);
            z-index: -2;
            will-change: transform;
        }
        
        .noise {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="4" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)"/%3E%3C/svg%3E');
            opacity: 0.04;
            pointer-events: none;
            z-index: -1;
        }
        
        .animated-gradient-border {
            position: relative;
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.01));
        }
        .animated-gradient-border::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(45deg, transparent, rgba(239, 68, 68, 0.6), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            background-size: 200% 200%;
            animation: borderGlow 6s linear infinite;
        }
        @keyframes borderGlow {
            0% { background-position: 0% 0%; }
            50% { background-position: 100% 100%; }
            100% { background-position: 0% 0%; }
        }
        
        :root {
            --plyr-color-main: #ef4444; /* Nexus Red */
        }

        @keyframes nexus-stat-spin {
            to { transform: rotate(360deg); }
        }
        @keyframes nexus-stat-spin-rev {
            to { transform: rotate(-360deg); }
        }
        .stat-loader-orbit-outer {
            animation: nexus-stat-spin 1.5s linear infinite;
        }
        .stat-loader-orbit-inner {
            animation: nexus-stat-spin-rev 1s linear infinite;
        }
        .dash-row-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .dash-row-scroll::-webkit-scrollbar { display: none; }
        .home-plan-featured {
            background:
                linear-gradient(180deg, rgba(79,70,229,0.16) 0%, rgba(8,8,12,0.92) 42%),
                rgba(8,8,12,0.85);
            border: 1px solid rgba(167,139,250,0.4);
            box-shadow:
                0 30px 80px -24px rgba(79,70,229,0.45),
                0 0 60px rgba(217,70,239,0.12),
                inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .home-plan-current {
            border-color: rgba(16,185,129,0.45) !important;
            box-shadow: 0 0 0 1px rgba(16,185,129,0.2), 0 20px 50px -24px rgba(16,185,129,0.25);
        }
    </style>

    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="../js/nexus_scripts.js?v=1789042200"></script>
</head>
<body class="h-screen w-screen flex flex-col relative selection:bg-red-500/30" data-barba="wrapper">
    <?php include __DIR__ . '/../frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/../frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/../frontend/components/toast.php'; ?>

<div id="barba-container" class="flex w-full h-full" data-barba="container" data-barba-namespace="dashboard" x-data="userDashboard()" x-init="init()" data-current-user-id="<?php echo htmlspecialchars($userId ?? '', ENT_QUOTES, 'UTF-8'); ?>"
     data-nexus-user="<?php echo htmlspecialchars(json_encode($nexusUserBoot, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">

    <div class="bg-mesh"></div>
    <div class="noise"></div>

    <!-- Side Navigation Drawer -->
    <div id="nav-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] opacity-0 pointer-events-none" @click="closeNav()"></div>
    <div id="side-panel" class="fixed top-0 left-0 w-full md:w-[320px] h-screen bg-[#050508]/95 backdrop-blur-3xl border-r border-white/10 z-[100] flex flex-col pointer-events-none -translate-x-full will-change-transform">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
        <div class="p-6 flex justify-between items-center border-b border-white/5 relative z-10 shrink-0">
            <a href="/index.php" data-barba-prevent class="flex items-center gap-4 group cursor-pointer pointer-events-auto">
                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] relative overflow-hidden icon-bounce">
                    <span class="material-symbols-outlined text-white font-bold relative z-10 text-[20px]">dashboard_customize</span>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tighter uppercase block leading-none group-hover:text-red-400 transition-colors">NEXUS</span>
                    <span class="text-[10px] text-white/50 tracking-widest uppercase font-semibold">Menu</span>
                </div>
            </a>
            <button @click="closeNav()" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-all duration-300 pointer-events-auto">
                <span class="material-symbols-outlined text-white/70 text-[18px]">close</span>
            </button>
        </div>

        <div class="flex-1 flex flex-col p-6 relative z-10 overflow-y-auto">
            <nav class="flex-1 flex flex-col gap-2">
                <template x-for="item in navItems" :key="item.id">
                    <a href="#" @click.prevent="switchTab(item.id); closeNav()" 
                       :class="{'bg-red-500/10 border-red-500/30 text-white shadow-[0_0_20px_rgba(239,68,68,0.1)]': currentTab === item.id, 'bg-white/[0.02] border-white/5 text-white/50': currentTab !== item.id}"
                       class="flex items-center gap-3 p-3 rounded-xl border hover:bg-white/[0.05] hover:text-white transition-all duration-300 cursor-pointer group pointer-events-auto">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center group-hover:bg-red-500/20 group-hover:text-red-400 transition-colors shrink-0"
                             :class="{'bg-red-500/20 text-red-400': currentTab === item.id}">
                            <span class="material-symbols-outlined text-[18px]" x-text="item.icon"></span>
                        </div>
                        <span class="text-sm font-bold tracking-wider block truncate" x-text="item.label"></span>
                    </a>
                </template>
            </nav>
            <div class="mt-auto pt-6 pointer-events-auto">
                <button @click="window.handleLogout()" class="w-full flex items-center gap-3 p-3 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-red-500/10 hover:border-red-500/30 text-white/50 hover:text-red-400 transition-all duration-300 group">
                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center group-hover:bg-red-500/20 transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                    </div>
                    <span class="text-sm font-bold tracking-wider block">Logout</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Quests Drawer -->
    <div x-show="showQuestsPanel" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90]" x-transition.opacity @click="showQuestsPanel = false" style="display: none;"></div>
    <div class="fixed top-0 right-0 w-full md:w-[400px] h-screen bg-[#050508]/95 backdrop-blur-3xl border-l border-white/10 z-[100] flex flex-col shadow-[0_0_50px_rgba(0,0,0,0.5)] transition-transform duration-500" :class="showQuestsPanel ? 'translate-x-0' : 'translate-x-full'">
       <!-- Quests Drawer Content for Logged-in Users -->
        <template x-if="!isGuest">
            <div class="flex flex-col flex-1 min-h-0">
                <!-- Header -->
                <div class="quest-header p-6 border-b border-white/5 relative z-10 shrink-0">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center">
                                <span class="material-symbols-outlined text-yellow-400">stars</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white tracking-tight">Quests</h2>
                                <p class="text-xs text-white/50 mono"><span x-text="questPointsAvailable"></span> PTS AVAILABLE</p>
                            </div>
                        </div>
                        <button @click="showQuestsPanel = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/50 hover:text-white">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-2 bg-black/40 p-1 rounded-xl border border-white/5">
                        <button @click="questActiveTab = 'daily'" class="flex-1 py-1.5 text-xs font-bold rounded-lg uppercase" :class="questActiveTab === 'daily' ? 'bg-yellow-500/20 text-yellow-400' : 'text-white/40'">Daily</button>
                        <button @click="questActiveTab = 'weekly'" class="flex-1 py-1.5 text-xs font-bold rounded-lg uppercase" :class="questActiveTab === 'weekly' ? 'bg-yellow-500/20 text-yellow-400' : 'text-white/40'">Weekly</button>
                        <button @click="questActiveTab = 'monthly'" class="flex-1 py-1.5 text-xs font-bold rounded-lg uppercase" :class="questActiveTab === 'monthly' ? 'bg-yellow-500/20 text-yellow-400' : 'text-white/40'">Monthly</button>
                    </div>
                </div>
                
                <!-- Quest List -->
                <div class="flex-1 overflow-y-auto p-6 relative z-10 space-y-4">
                   <template x-for="quest in (quests[questActiveTab] || [])" :key="quest.id">
                        <div class="quest-item bg-white/5 border border-white/10 rounded-xl p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="text-sm font-bold text-white" x-text="quest.title"></h4>
                                    <p class="text-[11px] text-white/50" x-text="quest.desc"></p>
                                </div>
                                <span class="material-symbols-outlined text-[18px]" 
                                    :class="quest.completed ? 'text-green-400' : 'text-white/30'" 
                                    x-text="quest.completed ? 'check_circle' : 'hourglass_empty'"></span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-bold text-white/60 mono">
                                        Progress: <span x-text="quest.progress + '/' + quest.target"></span>
                                    </span>
                                    <span class="text-[10px] font-bold text-yellow-400 mono" x-text="quest.points + ' PTS'"></span>
                                </div>
                                <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                        :class="quest.completed ? 'bg-green-500' : 'bg-yellow-500'"
                                        :style="`width: ${quest.target > 0 ? Math.min(100, (quest.progress / quest.target) * 100) : 0}%`"></div>
                                </div>
                            </div>

                            <!-- Claim Button -->
                            <div class="flex justify-end mt-3">
                                <button @click="claimQuest(quest.id)" 
                                        :disabled="!quest.completed || quest.claimed || claimingQuestId === quest.id"
                                        class="text-[10px] uppercase font-bold tracking-wider px-3 py-1.5 rounded-lg transition-all"
                                        :class="quest.claimed 
                                            ? 'bg-green-500/20 text-green-400 border border-green-500/30 cursor-default' 
                                            : (quest.completed 
                                                ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/30 cursor-pointer' 
                                                : 'bg-white/5 text-white/30 border border-white/10 cursor-default')"
                                        x-text="quest.claimed ? 'Claimed' : (claimingQuestId === quest.id ? 'Claiming...' : (quest.completed ? 'Claim' : 'In Progress'))">
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="!(quests[questActiveTab] || []).length" class="py-12 text-center">
                        <span class="material-symbols-outlined text-white/25 text-[28px]">task_alt</span>
                        <p class="text-[11px] text-white/40 mt-2">No quests in this cycle yet.</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Guest Login Prompt -->
        <template x-if="isGuest">
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-3xl text-yellow-400">stars</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Login to View Quests</h3>
                <p class="text-sm text-white/50 mb-6">Complete quests and earn points.</p>
                <a href="../frontend/login.php" class="px-6 py-3 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-xl transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">login</span>
                    Login / Register
                </a>
            </div>
        </template>
    </div>

    <!-- Upgraded Friends Drawer -->
    <div x-show="showFriendsPanel" 
        class="fixed inset-0 bg-black/70 backdrop-blur-md z-[90] transition-opacity duration-300 ease-out" 
        x-transition.opacity
        @click="if (!showChatPanel) showFriendsPanel = false" 
        :class="showChatPanel ? 'pointer-events-none' : ''"
        style="display: none;"></div>

    <div class="fixed top-0 right-0 w-full md:w-[320px] h-screen bg-[#07070b]/95 backdrop-blur-2xl border-l border-white/10 z-[100] flex flex-col shadow-[0_0_60px_rgba(0,0,0,0.8)] transition-transform duration-300 ease-out" 
        :class="showFriendsPanel ? 'translate-x-0' : 'translate-x-full'">
        
        <!-- Friends Drawer Content for Logged-in Users -->
        <template x-if="!isGuest">
            <div class="flex flex-col flex-1 min-h-0">
                <!-- Header -->
                <div class="p-4 border-b border-white/5 relative z-10 shrink-0 bg-gradient-to-b from-white/[0.02] to-transparent">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.1)] shrink-0">
                                <span class="material-symbols-outlined text-emerald-400 text-[18px]">group</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold uppercase tracking-wider text-white">Friends</h2>
                                <p class="text-[10px] text-emerald-400/90 uppercase tracking-widest font-mono mt-0.5 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span x-text="onlineFriendsCount"></span> Online
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="showInviteModal = true" class="w-8 h-8 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 hover:border-emerald-500/40 transition-all duration-200 flex items-center justify-center group" title="Search Users">
                                <span class="material-symbols-outlined text-[16px] group-hover:scale-110 transition-transform">person_add</span>
                            </button>
                            <button @click="showFriendsPanel = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-white/40 hover:text-white border border-white/5 transition-all duration-200 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Friends Sub-Tabs -->
                    <div class="flex items-center gap-2 bg-black/40 p-1 rounded-xl border border-white/5 mb-3">
                        <button @click="friendsTab = 'connected'" 
                                class="flex-1 py-1.5 text-[11px] font-bold rounded-lg uppercase transition-all" 
                                :class="friendsTab === 'connected' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-white/40 hover:text-white'">
                            Connected (<span x-text="friends.length"></span>)
                        </button>
                        <button @click="friendsTab = 'pending'" 
                                class="flex-1 py-1.5 text-[11px] font-bold rounded-lg uppercase transition-all relative" 
                                :class="friendsTab === 'pending' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-white/40 hover:text-white'">
                            Requests (<span x-text="pendingRequests.length"></span>)
                            <template x-if="pendingRequests.length > 0">
                                <span class="w-2 h-2 rounded-full bg-red-500 absolute top-1 right-1 animate-pulse"></span>
                            </template>
                        </button>
                    </div>
                    
                    <!-- Filter Input -->
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-[16px]">search</span>
                        <input type="text" 
                            x-model="friendSearchQuery" 
                            placeholder="Filter users..." 
                            class="w-full bg-white/[0.03] border border-white/10 focus:border-emerald-500/50 rounded-xl py-2 pl-9 pr-3 text-[11px] text-white placeholder-white/30 outline-none">
                    </div>
                </div>
                
                <!-- Tab 1: Connected Friends List -->
                <div x-show="friendsTab === 'connected'" class="flex-1 overflow-y-auto p-3 space-y-2.5 custom-scrollbar relative z-10">
                    <template x-for="friend in filteredFriends" :key="friend.user_id">
                        <div class="group relative bg-gradient-to-br from-white/[0.04] to-white/[0.01] hover:from-white/[0.07] hover:to-emerald-500/[0.04] border border-white/10 hover:border-emerald-500/30 rounded-xl p-3 transition-all duration-300">
                            <div class="flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1 cursor-pointer hover:opacity-80" @click.stop="toggleDropdown(friend, $event)">
                                    <div class="relative shrink-0 cursor-pointer hover:scale-105 transition-transform w-9 h-9 overflow-visible" style="width: 2.25rem; height: 2.25rem;" @click.stop="toggleDropdown(friend, $event)">
                                        <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.15] border border-emerald-500/30 shadow-md">
                                            <img :src="resolveAvatarUrl(friend.avatar_url, friend.user_name)"
                                                class="absolute inset-0 h-full w-full object-cover">
                                        </div>
                                        <template x-if="friend.border_preview">
                                            <img :src="friend.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                                        </template>
                                        <span class="absolute -bottom-0.5 -right-0.5 z-20 h-2 w-2 rounded-full"
                                              :class="isUserOnline(friend)
                                                ? 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.85)] ring-1 ring-[#07070b]'
                                                : 'bg-gray-500 ring-1 ring-[#07070b]'"
                                              :title="isUserOnline(friend) ? 'Online' : 'Offline'"></span>
                                    </div>
                                    <div class="min-w-0 flex-1 cursor-pointer" @click.stop="toggleDropdown(friend, $event)">
                                        <h4 class="text-xs font-semibold text-white/90 truncate" x-text="friend.user_name"></h4>
                                    </div>
                                </div>
                                
                                <button @click="openChat(friend)" 
                                    :class="friend.unread_count > 0 ? 'bg-emerald-500/20 border-emerald-500/50 shadow-lg shadow-emerald-500/10' : 'bg-emerald-500/10 hover:bg-emerald-500/20 border-emerald-500/20 hover:border-emerald-500/40'"
                                    class="shrink-0 flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-emerald-400 text-[10px] font-semibold uppercase tracking-wider border transition-all relative">
                                    
                                    <span class="material-symbols-outlined text-[13px]">chat</span>
                                    <span>Chat</span>

                                    <template x-if="friend.unread_count > 0">
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-extrabold leading-none text-white bg-red-500 rounded-full animate-pulse shadow-sm"
                                            x-text="friend.unread_count > 99 ? '99+' : friend.unread_count">
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredFriends.length === 0" class="py-10 text-center">
                        <span class="material-symbols-outlined text-white/30 text-[24px]">group_off</span>
                        <p class="text-[11px] text-white/40 mt-1">No friends found</p>
                    </div>
                </div>

                <!-- Tab 2: Pending Incoming Friend Requests -->
                <div x-show="friendsTab === 'pending'" class="flex-1 overflow-y-auto p-3 space-y-2.5 custom-scrollbar relative z-10" style="display: none;">
                    <template x-for="req in pendingRequests" :key="req.user_id">
                        <div class="bg-gradient-to-br from-white/[0.04] to-white/[0.01] border border-yellow-500/30 rounded-xl p-3 transition-all">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1 cursor-pointer hover:opacity-80" @click.stop="toggleDropdown(req, $event)">
                                    <div class="relative w-8 h-8 shrink-0 overflow-visible" style="width: 2rem; height: 2rem;">
                                        <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.15] border border-yellow-500/30">
                                            <img :src="resolveAvatarUrl(req.avatar_url, req.user_name)"
                                                class="absolute inset-0 h-full w-full object-cover">
                                        </div>
                                        <template x-if="req.border_preview">
                                            <img :src="req.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                                        </template>
                                        <span class="absolute -bottom-0.5 -right-0.5 z-20 h-2 w-2 rounded-full"
                                              :class="isUserOnline(req)
                                                ? 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.85)] ring-1 ring-[#07070b]'
                                                : 'bg-gray-500 ring-1 ring-[#07070b]'"
                                              :title="isUserOnline(req) ? 'Online' : 'Offline'"></span>
                                    </div>
                                    <div class="min-w-0 flex-1 cursor-pointer" @click.stop="toggleDropdown(req, $event)">
                                        <h4 class="text-xs font-semibold text-white truncate" x-text="req.user_name"></h4>
                                        <p class="text-[9px] text-yellow-400 uppercase font-mono tracking-wider">Added you</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 mt-2">
                                <button @click="respondToFriendRequest(req.user_id, 'accept')" 
                                        class="flex-1 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">person_add</span>
                                    <span>Add Back</span>
                                </button>
                                <button @click="respondToFriendRequest(req.user_id, 'decline')" 
                                        class="py-1.5 px-3 rounded-lg bg-white/5 hover:bg-white/10 text-white/40 hover:text-white border border-white/10 text-[10px] font-bold uppercase transition-all">
                                    Decline
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="pendingRequests.length === 0" class="py-10 text-center">
                        <span class="material-symbols-outlined text-white/30 text-[24px]">inbox</span>
                        <p class="text-[11px] text-white/40 mt-1">No pending requests</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Guest Login Prompt -->
        <template x-if="isGuest">
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-3xl text-emerald-400">group</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Login to View Friends</h3>
                <p class="text-sm text-white/50 mb-6">Connect with friends and watch together.</p>
                <a href="../frontend/login.php" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-black font-bold rounded-xl transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">login</span>
                    Login / Register
                </a>
            </div>
        </template>
    </div>
    <!-- Live User Search Modal -->
    <div x-show="showInviteModal" class="fixed inset-0 z-[110] flex items-center justify-center" style="display: none;">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-md" x-show="showInviteModal" x-transition.opacity @click="showInviteModal = false"></div>
        <div class="relative w-[90%] max-w-[480px] bg-[#050508]/95 backdrop-blur-3xl border border-white/10 rounded-3xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden z-10"
             x-show="showInviteModal"
             x-transition:enter="transition-all duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            
            <div class="p-6 border-b border-white/5 shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-400 text-[20px]">person_search</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold uppercase leading-none">User Search</h2>
                            <p class="text-[10px] text-white/40 uppercase tracking-widest mono mt-1">Find & add friends</p>
                        </div>
                    </div>
                    <button @click="showInviteModal = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-white/50 hover:text-white flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/40 text-[18px]">search</span>
                    <input type="text" x-model="searchQuery" placeholder="Type username or email..." class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-white placeholder-white/30 focus:border-emerald-500/50 outline-none">
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mono mb-2 px-2">
                    <span x-text="searchQuery.trim() === '' ? 'Suggested Users' : 'Search Results'"></span>
                </p>
                
                <!-- Search User Item Template -->
                <template x-for="user in searchResults" :key="user.user_id">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/[0.03] border border-white/10 hover:border-emerald-500/30 transition-all">
                        
                        <!-- User Info -->
                        <div class="flex items-center gap-3 min-w-0 flex-1 cursor-pointer hover:opacity-80" @click.stop="toggleDropdown(user, $event)">
                            <div class="relative w-9 h-9 shrink-0 overflow-visible" style="width: 2.25rem; height: 2.25rem;">
                                <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.15] border border-emerald-500/30">
                                    <img :src="resolveAvatarUrl(user.avatar_url, user.user_name)"
                                        class="absolute inset-0 h-full w-full object-cover">
                                </div>
                                <template x-if="user.border_preview">
                                    <img :src="user.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                                </template>
                                <span class="absolute -bottom-0.5 -right-0.5 z-20 h-2 w-2 rounded-full"
                                      :class="isUserOnline(user)
                                        ? 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.85)] ring-1 ring-[#07070b]'
                                        : 'bg-gray-500 ring-1 ring-[#07070b]'"
                                      :title="isUserOnline(user) ? 'Online' : 'Offline'"></span>
                            </div>
                            <div class="min-w-0 flex-1 cursor-pointer" @click.stop="toggleDropdown(user, $event)">
                                <h4 class="text-xs font-bold text-white truncate" x-text="user.user_name"></h4>
                                <p class="text-[10px] text-white/40 truncate" x-text="user.email"></p>
                            </div>
                        </div>

                        <!-- Conditional Action Buttons -->
                        <div class="shrink-0 ml-2">
                            
                            <!-- STATE 1: Already Friends -->
                            <template x-if="getFriendStatus(user) === 'friend'">
                                <span class="px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                    Friends
                                </span>
                            </template>

                            <!-- STATE 2: Incoming Request -> SHOW "ADD BACK" -->
                            <template x-if="getFriendStatus(user) === 'incoming_pending'">
                                <button @click="respondToFriendRequest(user.user_id, 'accept')" 
                                        class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-black font-extrabold text-[10px] uppercase tracking-wider shadow-[0_0_15px_rgba(16,185,129,0.4)] transition-all flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">person_add</span>
                                    Add Back
                                </button>
                            </template>

                            <!-- STATE 3: Outgoing Request -> SHOW "PENDING" -->
                            <template x-if="getFriendStatus(user) === 'outgoing_pending'">
                                <span class="px-3 py-1.5 rounded-lg bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                                    Pending
                                </span>
                            </template>

                            <!-- STATE 4: Not Friends -> SHOW "ADD FRIEND" -->
                            <template x-if="getFriendStatus(user) === 'none'">
                                <button @click="sendFriendRequest(user.user_id)" 
                                        class="px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 text-emerald-400 font-bold text-[10px] uppercase tracking-wider transition-all flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">person_add</span>
                                    Add Friend
                                </button>
                            </template>

                        </div>
                    </div>
                </template>

                <div x-show="searchResults.length === 0" class="p-6 text-center text-xs text-white/40">
                    No users matching "<span x-text="searchQuery"></span>" found.
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative z-10 w-full">
        
        <header class="h-24 flex items-center justify-between px-10 shrink-0 border-b border-white/5 backdrop-blur-md relative z-50">
            <div class="flex items-center gap-6">
                <button @click="openNav()" class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] icon-bounce">
                    <span class="material-symbols-outlined text-white font-bold text-[24px]">menu</span>
                </button>
                
                <div class="h-8 w-[1px] bg-white/10 hidden md:block"></div>

                <a href="/index.php" data-barba-prevent class="hidden md:flex items-center gap-3 group">
                    <span class="text-xl font-bold tracking-tighter uppercase group-hover:text-red-400 transition-colors">NEXUS</span>
                </a>
            </div>
            
            <div class="flex items-center gap-6">
                <?php include 'user_notification.php'; ?>

                <button @click="showFriendsPanel = true" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/15 border border-white/5 flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-white/70">group</span>
                </button>

                <!-- Profile Menu -->
                <?php if ($userRole !== 'guest'): ?>
                <div class="relative z-[60]" x-data="{ showProfileMenu: false }" @click.outside="showProfileMenu = false">
                    <div @click="showProfileMenu = !showProfileMenu" class="flex items-center gap-3 p-2 bg-[#050508]/40 border border-white/5 rounded-xl cursor-pointer hover:bg-white/[0.05]">
                        <div class="relative w-10 h-10 overflow-visible shrink-0" style="width: 2.5rem; height: 2.5rem;">
                            <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.18]<?php echo $activeBorderId > 0 ? '' : ' ring-2 ring-red-500/50'; ?>" :class="Number(activeBorderId) === 0 ? 'ring-2 ring-red-500/50' : ''">
                                <img src="<?php echo htmlspecialchars($avatarUrl !== '' ? $avatarUrl : ('https://ui-avatars.com/api/?name=' . rawurlencode($userName) . '&background=ef4444&color=fff&bold=true'), ENT_QUOTES, 'UTF-8'); ?>"
                                     :src="selectedAvatar || getAvatarUrl(savedProfile.username, 'ef4444')" class="absolute inset-0 h-full w-full object-cover" style="object-fit: cover;" alt="" decoding="sync" fetchpriority="high">
                            </div>
                            <?php if ($activeBorderId > 0 && $borderPreview !== ''): ?>
                            <img src="<?php echo htmlspecialchars($borderPreview, ENT_QUOTES, 'UTF-8'); ?>"
                                 :src="activeBorderPreview || '<?php echo htmlspecialchars($borderPreview, ENT_QUOTES, 'UTF-8'); ?>'"
                                 class="absolute inset-0 z-10 h-full w-full scale-[1.38] object-contain pointer-events-none"
                                 alt=""
                                 decoding="sync"
                                 fetchpriority="high"
                                 :class="Number(activeBorderId) === 0 ? 'invisible' : ''">
                            <?php else: ?>
                            <img x-show="Number(activeBorderId) !== 0 && !!activeBorderPreview"
                                 :src="activeBorderPreview"
                                 class="absolute inset-0 z-10 h-full w-full scale-[1.38] object-contain pointer-events-none"
                                 style="display: none;"
                                 alt="">
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:block min-w-0 pr-1">
                            <p class="text-sm font-bold text-white truncate" x-text="savedProfile.username"><?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-[9px] text-red-400 uppercase tracking-widest mono font-bold bg-red-500/10 px-2 py-0.5 rounded border border-red-500/20 inline-block mt-0.5"><?php echo htmlspecialchars($userRole); ?></p>
                        </div>
                    </div>
                    
                    <div class="absolute right-0 top-full mt-2 w-48 bg-[#050508] border border-white/10 rounded-xl shadow-2xl p-2 z-50" x-show="showProfileMenu" style="display: none;">
                        <button @click="currentTab = 'account'; showProfileMenu = false" class="w-full text-left p-2 hover:bg-white/10 rounded-lg text-xs font-semibold text-white/70 hover:text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">manage_accounts</span> Account
                        </button>
                        <button @click="showPremiumModal = true; showProfileMenu = false" class="w-full text-left p-2 hover:bg-white/10 rounded-lg text-xs font-semibold text-white/70 hover:text-white flex items-center justify-between gap-2 mt-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">workspace_premium</span> Manage Plan
                            </div>
                            <span x-show="isPremium" class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold uppercase border border-emerald-500/30">Active</span>
                            <span x-show="!isPremium" class="text-[9px] px-1.5 py-0.5 rounded bg-white/10 text-white/40 font-bold uppercase border border-white/10">Inactive</span>
                        </button>
                    </div>
                </div>
                <?php else: ?>

                <!-- Login button for guests -->
                <a href="../frontend/login.php" 
                class="relative z-[60] flex items-center gap-3 p-2 bg-[#050508]/40 border border-white/5 rounded-xl hover:bg-white/[0.05] transition-all">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white">login</span>
                    </div>
                    <span class="text-sm font-bold text-white">Login</span>
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Tabs Container (Relative wrapper for Absolute children) -->
        <div class="relative flex-1 overflow-hidden h-full">
            <!-- Content -->
            <div class="absolute inset-0 w-full h-full overflow-y-auto p-10 scroll-smooth custom-scrollbar" x-show="currentTab === 'dashboard'">
                <div class="max-w-[1400px] mx-auto space-y-8">

                <?php include __DIR__ . '/../frontend/components/trending_movies.php'; ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <template x-for="(stat, index) in stats" :key="index">
                        <div class="glass-card rounded-2xl p-6 cursor-pointer hover-glow" @click="if(stat.label === 'Friends') showFriendsPanel = true; if(stat.label === 'Quests') showQuestsPanel = true">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-white/50 text-xs uppercase tracking-widest mono mb-2" x-text="stat.label"></p>
                                    <div class="relative min-h-[40px] flex items-end">
                                        <!-- Loading Spinner -->
                                        <template x-if="statsLoading">
                                            <div class="h-10 relative overflow-hidden flex items-center py-1">
                                                <div class="relative w-9 h-9 flex items-center justify-center">
                                                    <!-- Ambient Glow -->
                                                    <div class="absolute inset-0 bg-indigo-500/20 rounded-full blur-md animate-pulse"></div>
                                                    <!-- Outer Orbit Ring -->
                                                    <div class="stat-loader-orbit-outer absolute inset-0 border-[2px] border-white/5 border-t-indigo-500 border-r-indigo-500 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.2)]"></div>
                                                    <!-- Inner Counter-Orbit Ring -->
                                                    <div class="stat-loader-orbit-inner absolute inset-1.5 border-[2px] border-white/5 border-b-red-500 border-l-red-500 rounded-full shadow-[0_0_10px_rgba(239,68,68,0.2)]"></div>
                                                    <!-- Core Energy Dot -->
                                                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-white shadow-[0_0_8px_#fff] rotate-45 animate-ping" style="animation-duration: 1.5s;"></div>
                                                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-white shadow-[0_0_8px_#fff] rotate-45"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <!-- Actual Value -->
                                        <template x-if="!statsLoading">
                                            <h3 class="text-4xl font-bold text-white tracking-tight flex items-end gap-1">
                                                <span class="font-mono" x-text="isGuest ? '0' : stat.value"></span>
                                                <span class="text-lg text-white/50 mb-1" x-text="stat.suffix" x-show="stat.suffix"></span>
                                            </h3>
                                        </template>
                                    </div>
                                </div>
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="stat.colorClass">
                                    <span class="material-symbols-outlined text-[24px]" x-text="stat.icon"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-4">
                                <span class="text-[11px] px-2 py-1 rounded bg-white/5 mono border border-white/10" :class="stat.trendClass" x-text="stat.trend"></span>
                                <span class="text-xs text-white/40" x-text="stat.desc"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <!-- Active Directives / Stream Rooms -->
                    <div class="xl:col-span-2 space-y-6 flex flex-col">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold tracking-wide uppercase flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full"
                                      :class="friendRooms.length ? 'bg-red-500 shadow-[0_0_10px_#ef4444]' : 'bg-white/20'"></span>
                                Active Directives (Stream Rooms)
                            </h2>
                            <button type="button" @click="fetchFriendRooms()" class="text-xs text-red-400 hover:text-white uppercase tracking-widest font-bold mono">Refresh</button>
                        </div>
                        
                        <div class="space-y-4" x-show="friendRooms.length > 0">
                            <template x-for="party in friendRooms" :key="party.room_id">
                                <div class="glass-card hover-glow animated-gradient-border rounded-2xl p-5 flex flex-col sm:flex-row gap-6 items-center group">
                                    <div class="w-full sm:w-48 h-32 rounded-xl overflow-hidden relative shrink-0">
                                        <img :src="party.img" class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500" alt="Cover">
                                        <div class="absolute bottom-2 left-2 z-20 flex items-center gap-1 bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                            <span class="text-[10px] font-bold mono" x-text="party.time || 'LIVE'"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 w-full min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-red-500/30 text-red-400 bg-red-500/10" x-text="party.genre || 'ORIGINAL'"></span>
                                        </div>
                                        <h3 class="text-2xl font-bold text-white mb-2 truncate group-hover:text-red-400 transition-colors" x-text="party.title || 'Original'"></h3>
                                        <p class="text-sm text-white/50 mb-4 flex items-center gap-2">
                                            Hosted by
                                            <span class="relative inline-flex w-6 h-6 overflow-visible shrink-0">
                                                <span class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] border border-white/10">
                                                    <img :src="resolveAvatarUrl(party.host_avatar, party.host)" class="absolute inset-0 h-full w-full object-cover" alt="">
                                                </span>
                                                <template x-if="party.host_border">
                                                    <img :src="party.host_border" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                                                </template>
                                            </span>
                                            <span class="text-white font-medium" x-text="party.host"></span>
                                        </p>
                                        
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs text-white/60 mono" x-text="(party.members || 0) + ' Members Active'"></span>
                                            <button type="button"
                                                    @click="party.in_room || party.request_status === 'accepted' ? enterFriendRoom(party) : requestJoinRoom(party)"
                                                    :disabled="joiningRoomId === party.room_id"
                                                    class="px-5 py-2 border text-white font-bold tracking-wide rounded-xl transition-all flex items-center gap-2 disabled:opacity-60"
                                                    :class="party.request_status === 'pending'
                                                        ? 'bg-white/5 border-white/10 text-white/70'
                                                        : 'bg-white/5 hover:bg-white/10 border-white/10'">
                                                <span x-text="joiningRoomId === party.room_id
                                                    ? 'Sending…'
                                                    : (party.in_room || party.request_status === 'accepted' ? 'Enter Room' : (party.request_status === 'pending' ? 'Requested' : 'Request to Join'))"></span>
                                                <span class="material-symbols-outlined text-[18px]" x-text="party.request_status === 'pending' ? 'hourglass_top' : 'arrow_forward'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="glass-card rounded-2xl flex-1 min-h-[280px] p-8 flex flex-col items-center justify-center text-center relative overflow-hidden"
                             x-show="friendRooms.length === 0"
                             x-cloak>
                            <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 via-transparent to-indigo-500/5 pointer-events-none"></div>
                            <div class="absolute inset-6 rounded-xl border border-dashed border-white/10 pointer-events-none"></div>

                            <div class="relative z-10 w-20 h-20 rounded-full border border-white/10 flex items-center justify-center mb-5 shadow-[0_0_40px_rgba(239,68,68,0.08)]">
                                <div class="absolute inset-0 rounded-full border-t border-white/20 animate-spin" style="animation-duration: 6s;"></div>
                                <span class="material-symbols-outlined text-[32px] text-white/35">sensors_off</span>
                            </div>

                            <p class="relative z-10 text-[10px] font-bold tracking-[0.28em] uppercase text-white/40 mono mb-2">Signal offline</p>
                            <h3 class="relative z-10 text-2xl font-black tracking-wide uppercase text-white mb-2">Nobody is hosting</h3>
                            <p class="relative z-10 text-sm text-white/45 max-w-sm">None of your friends have a live watch party right now.</p>
                        </div>
                    </div>

                    <!-- Premium Plan Upgrade Card -->
                    <div class="space-y-6">
                        <div class="group relative rounded-[2rem] p-8 bg-[#0a0a0f] border overflow-hidden transition-all duration-700"
                             :class="isPremium ? 'border-indigo-400/40 hover:shadow-[0_0_50px_rgba(99,102,241,0.2)]' : 'border-white/10 hover:border-indigo-400/30'">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/12 via-fuchsia-500/8 to-transparent opacity-70"></div>
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/20 rounded-full blur-[60px]"></div>
                            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-fuchsia-500/10 rounded-full blur-[80px]"></div>

                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-fuchsia-500 p-[1px]">
                                            <div class="w-full h-full bg-[#0a0a0f] rounded-xl flex items-center justify-center">
                                                <span class="material-symbols-outlined text-indigo-300 text-2xl">workspace_premium</span>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-fuchsia-300 to-indigo-300 uppercase">Premium</h3>
                                            <p class="text-[10px] font-mono text-indigo-300/70 tracking-[0.2em] uppercase" x-text="isPremium ? 'Ascend · Active' : 'Uplink tiers'">Uplink tiers</p>
                                        </div>
                                    </div>
                                    <span x-show="isPremium" class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded-full text-emerald-400 text-[10px] font-bold uppercase tracking-widest">Active</span>
                                    <span x-show="!isPremium" class="px-3 py-1 bg-white/10 border border-white/10 rounded-full text-white/40 text-[10px] font-bold uppercase tracking-widest">Free</span>
                                </div>

                                <div x-show="isPremium" class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 mb-6">
                                    <p class="text-[10px] mono tracking-widest uppercase text-emerald-300/80 mb-1">Your current plan</p>
                                    <p class="text-sm font-bold text-white" x-text="premiumEndsLabel">Ends —</p>
                                    <p class="text-xl font-black tracking-tight text-emerald-300 mono mt-1" x-text="premiumCountdown">--</p>
                                </div>

                                <div class="space-y-4 mb-8" x-show="!isPremium">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-fuchsia-400">blur_on</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">Exclusive cosmetics</p>
                                            <p class="text-[10px] text-white/50 font-mono">Animated profile borders</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-indigo-400">group</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">Unlimited hosting</p>
                                            <p class="text-[10px] text-white/50 font-mono">No protocol caps</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[16px] text-emerald-400">verified</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">Premium badge</p>
                                            <p class="text-[10px] text-white/50 font-mono">Shown on your identity</p>
                                        </div>
                                    </div>
                                </div>

                                <button type="button"
                                        class="mt-2 w-full relative overflow-hidden rounded-xl bg-white text-black py-3 font-black uppercase tracking-widest text-xs inline-flex items-center justify-center gap-2 hover:shadow-[0_0_30px_rgba(255,255,255,0.25)]"
                                        @click="isGuest ? window.location.href='/frontend/register.php' : showPremiumModal = true">
                                    <span x-text="isGuest ? 'Start for free' : (isPremium ? 'Your current plan' : 'Manage plan')"></span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Watchlist Section Included -->
        <?php include "user_movies.php"; ?>
        <?php include "user_premium.php"; ?>
        <?php if ($userRole !== 'guest'): ?>
            <?php include "watchlist.php"; ?>
            <?php include "user_shop.php"; ?>
            <?php include "account.php"; ?>
        <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../frontend/components/host_party_fab.php'; ?>

    <?php include "user_chat.php"; ?>
    <?php include "profile_dropdown.php"; ?>
    <?php include "report_user_modal.php"; ?>
    <?php include "report_item_modal.php"; ?>

</div>

<script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
<script src="../js/barba_setup.js?v=9"></script>

</body>
</html>

