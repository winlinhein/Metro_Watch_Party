<?php
session_start();

// Block access if not authenticated OR if the user is not an admin
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) 
) {
    header("Location: ../frontend/login.php?error=" . urlencode("Access denied. Admin privileges required."));
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
    <title>Nexus - User Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    
    <!-- Alpine.js & GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
        
        
        
        
        

        /* Hover Effects */
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

        /* Ambient Background */
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



</head>
<body x-data="userDashboard()" x-init="initDashboard()" class="h-screen w-screen flex flex-col relative selection:bg-red-500/30">
    <?php include __DIR__ . '/../frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/../frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/../frontend/components/toast.php'; ?>

<div class="bg-mesh"></div>
    <div class="noise"></div>

    <!-- Side Navigation Drawer -->
    <div id="nav-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] opacity-0 pointer-events-none" @click="closeNav()"></div>
    <div id="side-panel" class="fixed top-0 left-0 w-full md:w-[320px] h-screen bg-[#050508]/95 backdrop-blur-3xl border-r border-white/10 z-[100] flex flex-col pointer-events-none -translate-x-full">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-red-500/5 to-transparent pointer-events-none opacity-50"></div>
        
        <div class="p-6 flex justify-between items-center border-b border-white/5 relative z-10 shrink-0">
            <div class="flex items-center gap-4 side-panel-stagger group cursor-pointer">
                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] relative overflow-hidden icon-bounce">
                    <div class="absolute inset-0 bg-white/20 scale-0 group-hover:scale-100 transition-transform duration-300 rounded-xl opacity-0 group-hover:opacity-100"></div>
                    <span class="material-symbols-outlined text-white font-bold relative z-10 text-[20px]">dashboard_customize</span>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tighter uppercase block leading-none">NEXUS</span>
                    <span class="text-[10px] text-white/50 tracking-widest uppercase font-semibold">Menu</span>
                </div>
            </div>
            <button @click="closeNav()" class="relative w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-all duration-300 group cursor-pointer pointer-events-auto side-panel-stagger icon-bounce">
                <span class="material-symbols-outlined text-white/70 group-hover:text-white group-hover:rotate-90 transition-all duration-500 text-[18px]">close</span>
            </button>
        </div>

        <div class="flex-1 flex flex-col p-6 relative z-10 overflow-y-auto">
            
            <!-- Nav Items -->
            <nav class="flex-1 flex flex-col gap-2">
                <template x-for="item in navItems" :key="item.id">
                    <a href="#" @click.prevent="currentTab = item.id; closeNav()" 
                       :class="{'bg-red-500/10 border-red-500/30 text-white shadow-[0_0_20px_rgba(239,68,68,0.1)]': currentTab === item.id, 'bg-white/[0.02] border-white/5 text-white/50': currentTab !== item.id}"
                       class="side-nav-item flex items-center gap-3 p-3 rounded-xl border hover:bg-white/[0.05] hover:text-white transition-all duration-300 cursor-pointer group pointer-events-auto">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center group-hover:bg-red-500/20 group-hover:text-red-400 transition-colors relative overflow-hidden shrink-0"
                             :class="{'bg-red-500/20 text-red-400': currentTab === item.id}">
                            <span class="material-symbols-outlined icon text-[18px] group-hover:scale-110 transition-transform duration-300 relative z-10" x-text="item.icon"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-sm font-bold tracking-wider block truncate" x-text="item.label"></span>
                        </div>
                    </a>
                </template>
            </nav>
            
            <div class="mt-6 flex gap-2 side-panel-stagger">
                <button class="flex-1 py-2.5 bg-white/5 rounded-xl text-[10px] font-bold uppercase tracking-widest text-white/70 hover:text-white hover:bg-white/10 border border-white/5 transition-colors flex items-center justify-center gap-2 pointer-events-auto">
                    <span class="material-symbols-outlined text-[16px]">settings</span> Settings
                </button>
                <button id="logoutBtn" 
                        onclick="handleLogout()"
                        class="flex-1 py-2.5 bg-red-500/10 rounded-xl text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-300 hover:bg-red-500/20 border border-red-500/20 transition-colors flex items-center justify-center gap-2 pointer-events-auto">
                    <span class="material-symbols-outlined text-[16px]">logout</span> Sign Out
                </button>
            </div>
        </div>
    </div>

    
    <!-- Quests Drawer -->
    <div x-show="showQuestsPanel" 
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90]" 
         x-transition.opacity 
         @click="showQuestsPanel = false"
         style="display: none;"></div>
         
    <div class="fixed top-0 right-0 w-full md:w-[400px] h-screen bg-[#050508]/95 backdrop-blur-3xl border-l border-white/10 z-[100] flex flex-col shadow-[0_0_50px_rgba(0,0,0,0.5)] transition-transform duration-500 ease-[cubic-bezier(0.19,1,0.22,1)]"
         :class="showQuestsPanel ? 'translate-x-0' : 'translate-x-full'">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
        <div class="absolute top-0 right-0 w-full h-1/2 bg-gradient-to-b from-yellow-500/5 to-transparent pointer-events-none opacity-50"></div>
        
        <div class="p-6 border-b border-white/5 relative z-10 shrink-0 quest-header">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center shadow-[0_0_15px_rgba(234,179,8,0.2)]">
                        <span class="material-symbols-outlined text-yellow-400">stars</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white tracking-tight">Quests</h2>
                        <p class="text-xs text-white/50 mono"><span x-text="stats[3].value"></span> PTS AVAILABLE</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="showQuestsPanel = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/50 hover:text-white transition-all duration-300 hover:rotate-90" title="Close">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="flex items-center gap-2 bg-black/40 p-1 rounded-xl border border-white/5">
                <button @click="questActiveTab = 'daily'" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all duration-300 uppercase tracking-widest" :class="questActiveTab === 'daily' ? 'bg-yellow-500/20 text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.2)]' : 'text-white/40 hover:text-white/80'">Daily</button>
                <button @click="questActiveTab = 'weekly'" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all duration-300 uppercase tracking-widest" :class="questActiveTab === 'weekly' ? 'bg-yellow-500/20 text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.2)]' : 'text-white/40 hover:text-white/80'">Weekly</button>
                <button @click="questActiveTab = 'monthly'" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all duration-300 uppercase tracking-widest" :class="questActiveTab === 'monthly' ? 'bg-yellow-500/20 text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.2)]' : 'text-white/40 hover:text-white/80'">Monthly</button>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6 relative z-10 custom-scrollbar">
            <div class="space-y-4">
                <template x-for="(quest, index) in quests[questActiveTab]" :key="quest.id">
                    <div class="group relative bg-white/5 border border-white/10 rounded-xl p-4 overflow-hidden transition-all duration-300 hover:bg-white/[0.07] hover:border-white/20 quest-item">
                        <div class="absolute inset-0 bg-gradient-to-r from-yellow-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                        
                        <div class="flex justify-between items-start mb-2 relative z-10">
                            <div>
                                <h4 class="text-sm font-bold text-white mb-1 group-hover:text-yellow-400 transition-colors" x-text="quest.title"></h4>
                                <p class="text-[11px] text-white/50 leading-relaxed" x-text="quest.desc"></p>
                            </div>
                            <div class="flex-shrink-0 ml-4 flex items-center justify-center w-8 h-8 rounded-full" :class="quest.completed ? 'bg-green-500/20 text-green-400 border border-green-500/30 shadow-[0_0_15px_rgba(34,197,94,0.3)]' : 'bg-white/5 text-white/40 border border-white/10'">
                                <span class="material-symbols-outlined text-[16px]" x-text="quest.completed ? 'check' : 'hourglass_empty'"></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mt-4 relative z-10">
                            <div class="flex items-center gap-1.5 px-2 py-1 bg-yellow-500/10 rounded border border-yellow-500/20 shadow-[0_0_10px_rgba(234,179,8,0.1)]">
                                <span class="material-symbols-outlined text-[14px] text-yellow-400">toll</span>
                                <span class="text-[10px] font-bold text-yellow-400 mono tracking-widest" x-text="quest.points + ' PTS'"></span>
                            </div>
                            
                            <button class="text-[10px] uppercase tracking-widest font-bold transition-all" :class="quest.completed ? 'text-green-400 cursor-default' : 'text-yellow-400 hover:text-yellow-300 hover:shadow-[0_0_10px_rgba(234,179,8,0.5)]'" x-text="quest.completed ? 'Claimed' : 'Claim'"></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Friends Drawer -->
    <div x-show="showFriendsPanel" 
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90]" 
         x-transition.opacity 
         @click="showFriendsPanel = false"
         style="display: none;"></div>
         
    <div class="fixed top-0 right-0 w-full md:w-[360px] h-screen bg-[#050508]/95 backdrop-blur-3xl border-l border-white/10 z-[100] flex flex-col shadow-[0_0_50px_rgba(0,0,0,0.5)] transition-transform duration-500 ease-[cubic-bezier(0.19,1,0.22,1)]"
         :class="showFriendsPanel ? 'translate-x-0' : 'translate-x-full'">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
        <div class="absolute top-0 right-0 w-full h-1/2 bg-gradient-to-b from-emerald-500/5 to-transparent pointer-events-none opacity-50"></div>
        
        <div class="p-6 border-b border-white/5 relative z-10 shrink-0 quest-header">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4 group cursor-pointer">
                    <div class="w-10 h-10 bg-gradient-to-tr from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(16,185,129,0.3)] relative overflow-hidden">
                        <div class="absolute inset-0 bg-white/20 scale-0 group-hover:scale-100 transition-transform duration-300 rounded-xl opacity-0 group-hover:opacity-100"></div>
                        <span class="material-symbols-outlined text-white font-bold relative z-10 text-[20px]">group</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold tracking-tighter uppercase block leading-none">Friends</h2>
                        <p class="text-[10px] text-emerald-400 uppercase tracking-widest mono font-semibold mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>9 Online</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="showInviteModal = true" class="w-8 h-8 rounded-lg bg-emerald-500/10 hover:bg-emerald-500 flex items-center justify-center text-emerald-400 hover:text-white transition-all duration-300 shadow-[0_0_15px_rgba(16,185,129,0.2)] hover:shadow-[0_0_20px_rgba(16,185,129,0.5)]" title="Invite Friends">
                        <span class="material-symbols-outlined text-[18px]">person_add</span>
                    </button>
                    <button @click="showFriendsPanel = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/50 hover:text-white transition-all duration-300 hover:rotate-90" title="Close">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
            </div>
            
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-emerald-400 transition-colors text-[18px]">search</span>
                <input type="text" placeholder="Search network..." class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-white placeholder-white/30 focus:border-emerald-500/50 focus:bg-white/[0.05] transition-all outline-none">
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 relative z-10 space-y-2">
            <template x-for="(friend, i) in friends" :key="i">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.01] hover:bg-white/[0.04] transition-all duration-300 cursor-pointer group border border-transparent hover:border-white/10 hover:shadow-[0_4px_20px_rgba(0,0,0,0.2)]">
                    <div class="relative shrink-0">
                        <img :src="friend.avatar" class="w-11 h-11 rounded-full border border-white/10 group-hover:border-emerald-500/40 transition-colors shadow-lg">
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-[#050508]"
                             :class="{'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]': friend.status === 'Online', 'bg-yellow-500 shadow-[0_0_8px_rgba(234,179,8,0.5)]': friend.status === 'Away', 'bg-white/20': friend.status === 'Offline'}"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-bold text-white/90 truncate group-hover:text-emerald-400 transition-colors" x-text="friend.name"></span>
                            <span class="text-[9px] uppercase tracking-wider text-white/40 font-mono font-bold" x-text="friend.status"></span>
                        </div>
                        <p class="text-xs text-white/50 truncate" x-text="friend.activity"></p>
                    </div>
                    <button class="w-8 h-8 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-white text-white/40 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100 shrink-0 shadow-lg hover:shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                        <span class="material-symbols-outlined text-[16px]">chat</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Invite Modal -->
    <div x-show="showInviteModal" 
         class="fixed inset-0 z-[110] flex items-center justify-center pointer-events-none" 
         style="display: none;">
         
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md pointer-events-auto transition-opacity duration-300" 
             x-show="showInviteModal" 
             x-transition.opacity 
             @click="showInviteModal = false"></div>
             
        <!-- Modal Content -->
        <div class="relative w-[90%] max-w-[480px] bg-[#050508]/95 backdrop-blur-3xl border border-white/10 rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.8)] pointer-events-auto flex flex-col max-h-[85vh] overflow-hidden"
             x-show="showInviteModal"
             x-transition:enter="transition-all duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition-all duration-300 ease-in"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">
            
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 pointer-events-none"></div>
            
            <!-- Header -->
            <div class="p-6 border-b border-white/5 relative z-10 shrink-0 quest-header">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-tr from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(16,185,129,0.3)]">
                            <span class="material-symbols-outlined text-white font-bold text-[20px]">person_add</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold tracking-tighter uppercase block leading-none">Add Friends</h2>
                            <p class="text-[10px] text-white/50 uppercase tracking-widest mono font-semibold mt-1">Global Directory</p>
                        </div>
                    </div>
                    <button @click="showInviteModal = false" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/50 hover:text-white transition-all duration-300 hover:rotate-90">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-emerald-400 transition-colors text-[18px]">search</span>
                    <input type="text" placeholder="Search by username or ID..." class="w-full bg-white/[0.03] border border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-white placeholder-white/30 focus:border-emerald-500/50 focus:bg-white/[0.05] transition-all outline-none">
                </div>
            </div>
            
            <!-- Search Results / Suggestions -->
            <div class="flex-1 overflow-y-auto p-4 relative z-10 space-y-2">
                <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mono mb-3 px-2">Suggested Operatives</p>
                <template x-for="i in 5" :key="i">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.01] hover:bg-white/[0.04] transition-all duration-300 group border border-transparent hover:border-white/10">
                        <img :src="`https://ui-avatars.com/api/?name=Member+${i}&background=random&color=fff`" class="w-11 h-11 rounded-full border border-white/10 group-hover:border-emerald-500/40 transition-colors">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-white/90 group-hover:text-white transition-colors truncate" x-text="`User_${Math.floor(Math.random() * 9000 + 1000)}`"></h4>
                            <p class="text-xs text-white/40 truncate">Mutuals: 3</p>
                        </div>
                        <button class="px-4 py-2 rounded-lg bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white text-[11px] font-bold uppercase tracking-wider transition-all duration-300 flex items-center gap-1 group/btn shadow-[0_0_15px_rgba(16,185,129,0)] hover:shadow-[0_0_20px_rgba(16,185,129,0.3)] shrink-0">
                            <span>Add</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative z-10 w-full">
        
        <!-- Header -->
        <header class="header h-24 flex items-center justify-between px-10 shrink-0 border-b border-white/5 backdrop-blur-md relative z-50">
            <div class="gs-header-item flex items-center gap-6">
                <!-- Menu Toggle Button -->
                <button @click="openNav()" class="relative w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-red-600 hover:opacity-90 flex items-center justify-center transition-all duration-300 group cursor-pointer shadow-[0_0_20px_rgba(239,68,68,0.4)] icon-bounce">
                    <div class="absolute inset-0 bg-white/20 group-hover:scale-110 transition-transform duration-300 rounded-xl"></div>
                    <span class="material-symbols-outlined text-white font-bold relative z-10 text-[24px]">menu</span>
                </button>
                
                <div class="h-8 w-[1px] bg-white/10 hidden md:block"></div>
                
                <div class="hidden md:block">
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight welcome-text">Welcome back, <?php echo htmlspecialchars($userName); ?></h1>
                    </div>
                    <p class="text-xs text-white/40 mono mt-1">NEXUS PROTOCOL ACTIVE</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <button class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/15 border border-white/5 hover:border-white/20 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-[0_0_20px_rgba(255,255,255,0.15)] group gs-header-item">
                    <span class="material-symbols-outlined text-white/70 group-hover:text-white transition-all duration-300 group-hover:rotate-12">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full shadow-[0_0_8px_#ef4444] animate-pulse"></span>
                </button>

                <!-- Profile Header / Trigger -->
                <div class="relative gs-header-item z-[60] sm:min-w-[240px]" x-data="{ showProfileMenu: false }" @click.outside="showProfileMenu = false">
                    <div @click="showProfileMenu = !showProfileMenu" 
                         class="flex items-center justify-between p-2 bg-[#050508]/40 border cursor-pointer group transition-all duration-300 pointer-events-auto relative z-[61] w-full"
                         :class="showProfileMenu ? 'border-white/10 rounded-t-xl rounded-b-none border-b-transparent shadow-[0_-10px_40px_rgba(0,0,0,0.5)]' : 'border-white/5 hover:bg-white/[0.05] rounded-xl'">
                        <div class="flex items-center gap-3">
                            <div class="relative shrink-0">
                               <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=ef4444&color=fff&bold=true" 
                                    alt="<?= htmlspecialchars($userName) ?>" 
                                    class="w-10 h-10 rounded-full border-2 border-red-500/50 shadow-[0_0_15px_rgba(239,68,68,0.3)] relative z-10 transition-transform duration-500 group-hover:scale-105">
                            </div>
                            <div class="hidden sm:block min-w-0 pr-1">
                                <p class="text-sm font-bold text-white group-hover:text-red-400 transition-colors truncate"><?php echo htmlspecialchars($userName); ?></p>
                                <p class="text-[9px] text-red-400 uppercase tracking-widest mono font-bold bg-red-500/10 px-2 py-0.5 rounded border border-red-500/20 inline-block mt-0.5"><?php echo htmlspecialchars($userRole); ?></p>
                            </div>
                        </div>
                        <div class="hidden sm:flex w-8 h-8 rounded-lg bg-white/5 items-center justify-center group-hover:bg-white/10 transition-colors mr-1">
                            <span class="material-symbols-outlined text-white/50 group-hover:text-white transition-transform duration-500 text-[18px]" :class="{'rotate-90': showProfileMenu}">chevron_right</span>
                        </div>
                    </div>
                    
                    <!-- Profile Submenu -->
                    <div class="absolute right-0 top-full mt-[-1px] w-full min-w-[240px] bg-[#050508] border border-white/10 border-t-0 rounded-b-xl shadow-[0_20px_40px_rgba(0,0,0,0.8)] transition-all duration-300 transform origin-top z-50 pointer-events-auto overflow-hidden"
                         x-show="showProfileMenu"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-y-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-y-95"
                         style="display: none;">
                        <div class="p-2 flex flex-col gap-1">
                            <button class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-white/10 transition-colors text-white/70 hover:text-white group text-left relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-red-500/0 via-red-500/10 to-transparent -translate-x-full group-hover:translate-x-0 transition-transform duration-500 ease-out"></div>
                                <span class="material-symbols-outlined text-[16px] group-hover:text-red-400 transition-colors group-hover:scale-110 relative z-10">badge</span>
                                <span class="text-xs font-semibold tracking-wider group-hover:translate-x-1 transition-transform relative z-10">Change Name</span>
                            </button>
                            <button class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-white/10 transition-colors text-white/70 hover:text-white group text-left relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-red-500/0 via-red-500/10 to-transparent -translate-x-full group-hover:translate-x-0 transition-transform duration-500 ease-out"></div>
                                <span class="material-symbols-outlined text-[16px] group-hover:text-red-400 transition-colors group-hover:scale-110 relative z-10">border_outer</span>
                                <span class="text-xs font-semibold tracking-wider group-hover:translate-x-1 transition-transform relative z-10">Change Borders</span>
                            </button>
                            <button class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-white/10 transition-colors text-white/70 hover:text-white group text-left relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-red-500/0 via-red-500/10 to-transparent -translate-x-full group-hover:translate-x-0 transition-transform duration-500 ease-out"></div>
                                <span class="material-symbols-outlined text-[16px] group-hover:text-red-400 transition-colors group-hover:scale-110 relative z-10">manage_accounts</span>
                                <span class="text-xs font-semibold tracking-wider group-hover:translate-x-1 transition-transform relative z-10">Account Settings</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="flex-1 overflow-y-auto p-10 tab-content relative scroll-smooth" 
             x-show="currentTab === 'dashboard'"
             x-transition:enter="transition-all duration-500 delay-300 cubic-bezier(0.34, 1.56, 0.64, 1)"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition-all duration-300 ease-in"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-8 absolute w-full"
             >
            <div class="max-w-[1400px] mx-auto space-y-8">
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <template x-for="(stat, index) in stats" :key="index">
                        <div class="glass-card rounded-2xl p-6 stagger-item group cursor-pointer hover-glow" @click="if(stat.label === 'Friends') showFriendsPanel = true; if(stat.label === 'Quests') showQuestsPanel = true">
                            <div class="w-full h-full">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <p class="text-white/50 text-xs uppercase tracking-widest mono mb-2" x-text="stat.label"></p>
                                        <h3 class="text-4xl font-bold text-white tracking-tight flex items-end gap-1">
                                            <span class="stat-counter font-mono tracking-tighter" :data-target="stat.value">0</span>
                                            <span class="text-lg text-white/50 mb-1" x-text="stat.suffix" x-show="stat.suffix"></span>
                                        </h3>
                                    </div>
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-500" :class="stat.colorClass">
                                        <span class="material-symbols-outlined text-[24px] icon-bounce" x-text="stat.icon"></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-4">
                                    <span class="text-[11px] px-2 py-1 rounded bg-white/5 mono border border-white/10" :class="stat.trendClass" x-text="stat.trend"></span>
                                    <span class="text-xs text-white/40" x-text="stat.desc"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Main Layout -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    
                    <!-- Upcoming Parties -->
                    <div class="xl:col-span-2 space-y-6">
                        <div class="flex items-center justify-between stagger-item">
                            <h2 class="text-xl font-bold tracking-wide uppercase flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_10px_#ef4444]"></span>
                                Active Directives (Parties)
                            </h2>
                            <button class="text-xs text-red-400 hover:text-white uppercase tracking-widest font-bold mono transition-colors">View All</button>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="(party, index) in upcomingParties" :key="index">
                                <div class="glass-card hover-glow animated-gradient-border rounded-2xl p-5 flex flex-col sm:flex-row gap-6 items-center stagger-item group cursor-pointer">
                                    <!-- Poster -->
                                    <div class="w-full sm:w-48 h-32 rounded-xl overflow-hidden relative shrink-0">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10 group-hover:opacity-0 transition-opacity duration-300"></div>
                                        <img :src="party.img" class="w-full h-full object-cover group-hover:scale-110 transition-all duration-700" alt="Cover">
                                        <div class="absolute bottom-2 left-2 z-20 flex items-center gap-1 bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10">
                                            <span class="material-symbols-outlined text-red-500 text-[12px]">schedule</span>
                                            <span class="text-[10px] font-bold mono" x-text="party.time"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Details -->
                                    <div class="flex-1 w-full min-w-0 relative z-10">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-red-500/30 text-red-400 bg-red-500/10" x-text="party.genre"></span>
                                        </div>
                                        <h3 class="text-2xl font-bold text-white mb-2 truncate group-hover:text-red-400 transition-colors tracking-tight" x-text="party.title"></h3>
                                        <div class="flex items-center gap-2 text-sm text-white/50 mb-4">
                                            <span class="material-symbols-outlined text-[16px]">account_circle</span>
                                            <span>Hosted by <span class="text-white font-medium" x-text="party.host"></span></span>
                                        </div>
                                        
                                        <!-- Avatars -->
                                        <div class="flex items-center justify-between">
                                            <div class="flex -space-x-3 icon-bounce">
                                                <template x-for="i in 3">
                                                    <img :src="'https://ui-avatars.com/api/?name=U'+i+'&background=random&color=fff&bold=true'" class="inline-block h-8 w-8 rounded-full ring-2 ring-[#030305] shadow-lg">
                                                </template>
                                                <div class="h-8 w-8 rounded-full ring-2 ring-[#030305] bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-[10px] font-bold mono text-white shadow-lg" x-text="'+' + (party.members - 3)"></div>
                                            </div>
                                            
                                            <button class="px-6 py-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold tracking-wide rounded-xl transition-all hover:scale-105 hover:shadow-[0_0_15px_rgba(255,255,255,0.1)] flex items-center gap-2">
                                                Enter Room
                                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Right Sidebar Activity -->
                    <div class="space-y-6">
                        <!-- Premium Card -->
                        <div class="glass-card hover-glow rounded-2xl p-8 bg-gradient-to-br from-indigo-500/20 to-red-600/20 stagger-item border border-white/10 relative overflow-hidden group cursor-pointer">
                            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20"></div>
                            <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-500/30 rounded-full blur-[50px] group-hover:scale-150 transition-transform duration-700"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="material-symbols-outlined text-red-400 text-3xl icon-bounce">workspace_premium</span>
                                    <h3 class="text-xl font-bold tracking-wider">PRO PLAN</h3>
                                </div>
                                <p class="text-sm text-white/70 mb-6 leading-relaxed">Your premium access is active. Host up to 100 viewers per session with 4K streaming.</p>
                                
                                <div class="w-full bg-black/40 rounded-full h-1.5 mb-2 overflow-hidden border border-white/10">
                                    <div class="bg-gradient-to-r from-indigo-500 to-red-500 h-1.5 rounded-full w-[75%] relative overflow-hidden">
                                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                    </div>
                                </div>
                                <div class="flex justify-between text-[10px] text-white/50 mono font-bold">
                                    <span>75% STORAGE USED</span>
                                    <span class="text-red-400 hover:text-red-300 transition-colors">UPGRADE</span>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Feed -->
                        <div class="glass-panel rounded-2xl p-6 stagger-item">
                            <h2 class="text-lg font-bold tracking-wide uppercase mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-white/50">history</span>
                                Transmission Log
                            </h2>
                            
                            <div class="relative pl-4 space-y-6 before:absolute before:inset-y-0 before:left-[11px] before:w-[1px] before:bg-gradient-to-b before:from-red-500/50 before:via-white/10 before:to-transparent">
                                <template x-for="(item, index) in activityFeed" :key="index">
                                    <div class="relative activity-item group cursor-default">
                                        <div class="absolute -left-[27px] top-1 w-3 h-3">
                                            <span class="absolute inset-0 rounded-full dot-pulse opacity-50" :class="item.dotColor"></span>
                                            <span class="absolute inset-0 rounded-full ring-4 ring-[#030305] transition-transform duration-300 group-hover:scale-125" :class="item.dotColor"></span>
                                        </div>
                                        <p class="text-sm text-white/80 group-hover:text-white transition-colors" x-html="item.text"></p>
                                        <p class="text-[10px] text-white/40 mono mt-1 font-semibold tracking-wider" x-text="item.time"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <?php include "watchlist.php"; ?>
    </main>

    <?php include __DIR__ . '/../frontend/components/host_party_fab.php'; ?>

    <script src="user_animations.js"></script>
    <script>
        async function handleLogout() {
            const btn = document.getElementById('logoutBtn');
            
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
                }
            } catch (error) {
                console.error('Error during sign out:', error);
                if (btn) btn.style.opacity = '1';
            }
        }
    </script>

</body>
</html>
