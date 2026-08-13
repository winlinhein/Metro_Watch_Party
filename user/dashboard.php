<?php
session_start();

// Block access if not authenticated OR if the user is not an admin
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) 
) {
    header("Location: ../frontend/login.php?error=" . urlencode("Access denied. Please log in to view your dashboard."));
    exit();
}

$userName  = $_SESSION['user_name']  ?? 'Agent';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole  = $_SESSION['user_role']  ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

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
    <script>
        // Safely pass the PHP session user ID to JavaScript
        window.CURRENT_USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    
    <!-- Alpine.js & GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" crossorigin="anonymous"></script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    
    <style>
        body { 
            font-family: 'Space Grotesk', sans-serif; 
            background-color: #030305; 
            color: #ffffff; 
            overflow: hidden; 
            cursor: none;
        }

        .mono { font-family: 'JetBrains Mono', monospace; }
        
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
    </style>

    <script src="/js/nexus_scripts.js?v=6"></script>
</head>
<body class="h-screen w-screen flex flex-col relative selection:bg-red-500/30" data-barba="wrapper">
    <?php include __DIR__ . '/../frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/../frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/../frontend/components/toast.php'; ?>

<div id="barba-container" class="flex w-full h-full" data-barba="container" data-barba-namespace="dashboard" x-data="userDashboard()" x-init="init()">

    <div class="bg-mesh"></div>
    <div class="noise"></div>

    <!-- Side Navigation Drawer -->
    <div id="nav-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] opacity-0 pointer-events-none" @click="closeNav()"></div>
    <div id="side-panel" class="fixed top-0 left-0 w-full md:w-[320px] h-screen bg-[#050508]/95 backdrop-blur-3xl border-r border-white/10 z-[100] flex flex-col pointer-events-none -translate-x-full will-change-transform">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
        <div class="p-6 flex justify-between items-center border-b border-white/5 relative z-10 shrink-0">
            <div class="flex items-center gap-4 group cursor-pointer">
                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] relative overflow-hidden icon-bounce">
                    <span class="material-symbols-outlined text-white font-bold relative z-10 text-[20px]">dashboard_customize</span>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tighter uppercase block leading-none">NEXUS</span>
                    <span class="text-[10px] text-white/50 tracking-widest uppercase font-semibold">Menu</span>
                </div>
            </div>
            <button @click="closeNav()" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-all duration-300 pointer-events-auto">
                <span class="material-symbols-outlined text-white/70 text-[18px]">close</span>
            </button>
        </div>

        <div class="flex-1 flex flex-col p-6 relative z-10 overflow-y-auto">
            <nav class="flex-1 flex flex-col gap-2">
                <template x-for="item in navItems" :key="item.id">
                    <a href="#" @click.prevent="currentTab = item.id; closeNav()" 
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
        <div class="p-6 border-b border-white/5 relative z-10 shrink-0">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-yellow-400">stars</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white tracking-tight">Quests</h2>
                        <p class="text-xs text-white/50 mono"><span x-text="stats[3].value"></span> PTS AVAILABLE</p>
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
        
        <div class="flex-1 overflow-y-auto p-6 relative z-10 space-y-4">
            <template x-for="quest in quests[questActiveTab]" :key="quest.id">
                <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h4 class="text-sm font-bold text-white" x-text="quest.title"></h4>
                            <p class="text-[11px] text-white/50" x-text="quest.desc"></p>
                        </div>
                        <span class="material-symbols-outlined text-[18px]" :class="quest.completed ? 'text-green-400' : 'text-white/30'" x-text="quest.completed ? 'check_circle' : 'hourglass_empty'"></span>
                    </div>
                    <div class="flex justify-between items-center mt-3">
                        <span class="text-[10px] font-bold text-yellow-400 mono" x-text="quest.points + ' PTS'"></span>
                        <button class="text-[10px] uppercase font-bold text-yellow-400 hover:underline" x-text="quest.completed ? 'Claimed' : 'Claim'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Upgraded Friends Drawer -->
    <div x-show="showFriendsPanel" 
        class="fixed inset-0 bg-black/70 backdrop-blur-md z-[90] transition-opacity duration-300 ease-out" 
        x-transition.opacity
        @click="showFriendsPanel = false" 
        style="display: none;"></div>

    <div class="fixed top-0 right-0 w-full md:w-[320px] h-screen bg-[#07070b]/95 backdrop-blur-2xl border-l border-white/10 z-[100] flex flex-col shadow-[0_0_60px_rgba(0,0,0,0.8)] transition-transform duration-300 ease-out" 
        :class="showFriendsPanel ? 'translate-x-0' : 'translate-x-full'">
        
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
                            <span x-text="friends.length"></span> Active Friends
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

            <!-- Friends Sub-Tabs (Connected vs Pending Add Back) -->
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
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <div class="relative shrink-0">
                                <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(friend.user_name)}&background=10b981&color=fff`" 
                                    class="w-9 h-9 rounded-full border border-emerald-500/30 shadow-md object-cover">
                                <span class="absolute -bottom-0.5 -right-0.5 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 border-2 border-[#07070b]"></span>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-semibold text-white/90 truncate" x-text="friend.user_name"></h4>
                            </div>
                        </div>
                        
                        <button @click="openChat(friend)" 
                            :class="friend.unread_count > 0 ? 'bg-emerald-500/20 border-emerald-500/50 shadow-lg shadow-emerald-500/10' : 'bg-emerald-500/10 hover:bg-emerald-500/20 border-emerald-500/20 hover:border-emerald-500/40'"
                            class="shrink-0 flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-emerald-400 text-[10px] font-semibold uppercase tracking-wider border transition-all relative">
                            
                            <!-- Chat Icon & Label -->
                            <span class="material-symbols-outlined text-[13px]">chat</span>
                            <span>Chat</span>

                            <!-- Numerical Badge (If unread exists) -->
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

        <!-- Tab 2: Pending Incoming Friend Requests ("Added You") -->
        <div x-show="friendsTab === 'pending'" class="flex-1 overflow-y-auto p-3 space-y-2.5 custom-scrollbar relative z-10" style="display: none;">
            <template x-for="req in pendingRequests" :key="req.user_id">
                <div class="bg-gradient-to-br from-white/[0.04] to-white/[0.01] border border-yellow-500/30 rounded-xl p-3 transition-all">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(req.user_name)}&background=f59e0b&color=fff`" 
                                class="w-8 h-8 rounded-full border border-yellow-500/30 object-cover shrink-0">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-semibold text-white truncate" x-text="req.user_name"></h4>
                                <p class="text-[9px] text-yellow-400 uppercase font-mono tracking-wider">Added you</p>
                            </div>
                        </div>
                    </div>

                    <!-- Add Back / Decline Action Buttons -->
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
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.user_name)}&background=10b981&color=fff`" 
                                class="w-9 h-9 rounded-full border border-emerald-500/30 object-cover shrink-0">
                            <div class="min-w-0 flex-1">
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
                
                <div class="hidden md:block">
                    <h1 class="text-2xl font-bold tracking-tight">Welcome back, <?php echo htmlspecialchars($userName); ?></h1>
                    <p class="text-xs text-white/40 mono mt-1">NEXUS PROTOCOL ACTIVE</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <?php include 'user_notification.php'; ?>

                <button @click="showFriendsPanel = true" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/15 border border-white/5 flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-white/70">group</span>
                </button>

                <!-- Profile Menu -->
                <div class="relative z-[60]" x-data="{ showProfileMenu: false }" @click.outside="showProfileMenu = false">
                    <div @click="showProfileMenu = !showProfileMenu" class="flex items-center gap-3 p-2 bg-[#050508]/40 border border-white/5 rounded-xl cursor-pointer hover:bg-white/[0.05]">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=ef4444&color=fff&bold=true" class="w-10 h-10 rounded-full border-2 border-red-500/50">
                        <div class="hidden sm:block min-w-0 pr-1">
                            <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-[9px] text-red-400 uppercase tracking-widest mono font-bold bg-red-500/10 px-2 py-0.5 rounded border border-red-500/20 inline-block mt-0.5"><?php echo htmlspecialchars($userRole); ?></p>
                        </div>
                    </div>
                    
                    <div class="absolute right-0 top-full mt-2 w-48 bg-[#050508] border border-white/10 rounded-xl shadow-2xl p-2 z-50" x-show="showProfileMenu" style="display: none;">
                        <button class="w-full text-left p-2 hover:bg-white/10 rounded-lg text-xs font-semibold text-white/70 hover:text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">manage_accounts</span> Account
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-10 relative scroll-smooth" x-show="currentTab === 'dashboard'">
            <div class="max-w-[1400px] mx-auto space-y-8">
                
                <!-- Demo Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <template x-for="(stat, index) in stats" :key="index">
                        <div class="glass-card rounded-2xl p-6 cursor-pointer hover-glow" @click="if(stat.label === 'Friends') showFriendsPanel = true; if(stat.label === 'Quests') showQuestsPanel = true">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-white/50 text-xs uppercase tracking-widest mono mb-2" x-text="stat.label"></p>
                                    <h3 class="text-4xl font-bold text-white tracking-tight flex items-end gap-1">
                                        <span class="font-mono" x-text="stat.value"></span>
                                        <span class="text-lg text-white/50 mb-1" x-text="stat.suffix" x-show="stat.suffix"></span>
                                    </h3>
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
                    <!-- Demo Active Directives / Stream Rooms -->
                    <div class="xl:col-span-2 space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold tracking-wide uppercase flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_10px_#ef4444]"></span>
                                Active Directives (Stream Rooms)
                            </h2>
                            <button class="text-xs text-red-400 hover:text-white uppercase tracking-widest font-bold mono">View All</button>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="(party, index) in upcomingParties" :key="index">
                                <div class="glass-card hover-glow animated-gradient-border rounded-2xl p-5 flex flex-col sm:flex-row gap-6 items-center group cursor-pointer">
                                    <div class="w-full sm:w-48 h-32 rounded-xl overflow-hidden relative shrink-0">
                                        <img :src="party.img" class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500" alt="Cover">
                                        <div class="absolute bottom-2 left-2 z-20 flex items-center gap-1 bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10">
                                            <span class="material-symbols-outlined text-red-500 text-[12px]">schedule</span>
                                            <span class="text-[10px] font-bold mono" x-text="party.time"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 w-full min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-red-500/30 text-red-400 bg-red-500/10" x-text="party.genre"></span>
                                        </div>
                                        <h3 class="text-2xl font-bold text-white mb-2 truncate group-hover:text-red-400 transition-colors" x-text="party.title"></h3>
                                        <p class="text-sm text-white/50 mb-4">Hosted by <span class="text-white font-medium" x-text="party.host"></span></p>
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-white/60 mono" x-text="party.members + ' Members Active'"></span>
                                            <button class="px-5 py-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold tracking-wide rounded-xl transition-all flex items-center gap-2">
                                                Enter Room
                                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Premium Plan Upgrade Card -->
                    <div class="space-y-6">
                        <div class="group relative rounded-[2rem] p-8 bg-[#0a0a0f] border border-yellow-500/30 overflow-hidden hover:shadow-[0_0_50px_rgba(234,179,8,0.2)] transition-all duration-700">
                            <!-- Animated Background Effects -->
                            <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 via-orange-500/5 to-purple-600/10 opacity-50 group-hover:opacity-100 transition-opacity duration-700"></div>
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-yellow-500/20 rounded-full blur-[60px] group-hover:scale-150 transition-transform duration-1000"></div>
                            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-500/10 rounded-full blur-[80px] group-hover:scale-125 transition-transform duration-1000 delay-100"></div>
                            
                            <!-- Shimmer Sweep -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:animate-[shimmer_2s_infinite]"></div>

                            <div class="relative z-10">
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-400 to-orange-500 p-[1px] group-hover:scale-110 transition-transform duration-500">
                                            <div class="w-full h-full bg-[#0a0a0f] rounded-xl flex items-center justify-center">
                                                <span class="material-symbols-outlined text-transparent bg-clip-text bg-gradient-to-br from-yellow-400 to-orange-500 text-2xl group-hover:rotate-12 transition-transform duration-500">diamond</span>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500 uppercase">Premium</h3>
                                            <p class="text-[10px] font-mono text-yellow-500/70 tracking-[0.2em] uppercase">Membership</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 rounded-full text-yellow-400 text-[10px] font-bold uppercase tracking-widest animate-pulse">Active</span>
                                </div>

                                <!-- Benefits List -->
                                <div class="space-y-4 mb-8">
                                    <div class="flex items-center gap-3 transform group-hover:translate-x-2 transition-transform duration-500 delay-75">
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center shadow-[inset_0_0_10px_rgba(255,255,255,0.05)]">
                                            <span class="material-symbols-outlined text-[16px] text-emerald-400">group</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">Host Watch Parties</p>
                                            <p class="text-[10px] text-white/50 font-mono">Room capacity up to 4 people</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 transform group-hover:translate-x-2 transition-transform duration-500 delay-150">
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center shadow-[inset_0_0_10px_rgba(255,255,255,0.05)]">
                                            <span class="material-symbols-outlined text-[16px] text-purple-400">blur_on</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">Exclusive Cosmetics</p>
                                            <p class="text-[10px] text-white/50 font-mono">Unlock animated profile borders</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 transform group-hover:translate-x-2 transition-transform duration-500 delay-200">
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center shadow-[inset_0_0_10px_rgba(255,255,255,0.05)]">
                                            <span class="material-symbols-outlined text-[16px] text-orange-400">explore</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">Expanded Quests</p>
                                            <p class="text-[10px] text-white/50 font-mono">More daily & weekly missions</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <button class="mt-2 relative overflow-hidden rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 p-[1px] group/btn transition-all duration-300 hover:shadow-[0_0_20px_rgba(234,179,8,0.4)] hover:-translate-y-1">
                                    <div class="bg-[#0a0a0f] px-5 py-2.5 rounded-xl flex items-center gap-3 group-hover/btn:bg-transparent transition-colors duration-300">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-yellow-400 group-hover/btn:text-[#0a0a0f] transition-colors duration-300">Manage Plan</span>
                                        <span class="material-symbols-outlined text-[14px] text-yellow-400 group-hover/btn:text-[#0a0a0f] transition-colors duration-300 group-hover/btn:translate-x-1">arrow_forward</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Transmission Log -->
                        <div class="glass-panel rounded-2xl p-6">
                            <h2 class="text-lg font-bold tracking-wide uppercase mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-white/50">history</span>
                                Transmission Log
                            </h2>
                            <div class="relative pl-4 space-y-6 before:absolute before:inset-y-0 before:left-[11px] before:w-[1px] before:bg-white/10">
                                <template x-for="(item, index) in activityFeed" :key="index">
                                    <div class="relative">
                                        <p class="text-sm text-white/80" x-html="item.text"></p>
                                        <p class="text-[10px] text-white/40 mono mt-1 font-semibold" x-text="item.time"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Watchlist Section Included -->
        <?php include "watchlist.php"; ?>
        <?php include "user_movies.php"; ?>
        <?php include "user_shop.php"; ?>
    </main>

    <?php include __DIR__ . '/../frontend/components/host_party_fab.php'; ?>

    <?php include "user_chat.php"; ?>

</div>

<script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
<script src="/js/barba_setup.js?v=4"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="/js/nexus_scripts.js?v=6"></script>
</body>
</html>

