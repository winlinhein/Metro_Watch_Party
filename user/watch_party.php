<?php
session_start();
// Ensure user is logged in, redirect if not...
if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php?error=" . urlencode("Access denied."));
    exit();
}

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Agent';
$userEmail = $_SESSION['user_email'] ?? '';
$userAvatar = '';
$userBorder = '';
try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../profile_media_helper.php';
    $media = getUserProfileMedia($conn, (int)$userId);
    $userAvatar = $media['avatar_url'] ?? '';
    $userBorder = $media['border_preview'] ?? '';
} catch (Throwable $e) {
    error_log('watch_party profile media: ' . $e->getMessage());
}
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/../frontend/components/head_boot.php'; ?>
    <title>Nexus - Watch Party</title>
    
    <script>
        window.CURRENT_USER_ID = <?php echo json_encode($userId); ?>;
        window.USER_NAME = <?php echo json_encode($userName); ?>;
        window.USER_AVATAR = <?php echo json_encode($userAvatar); ?>;
        window.USER_BORDER = <?php echo json_encode($userBorder); ?>;
        window.PUSHER_KEY = 'f4b5637ef4b8952b6eb8';
        window.PUSHER_CLUSTER = 'ap1';
        window.NEXUS_SIGNALING_URL = window.NEXUS_SIGNALING_URL || (
            (location.port && location.port !== '3000')
                ? (location.protocol + '//' + location.hostname + ':3000')
                : ''
        );
    </script>
    
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" crossorigin="anonymous" onerror="window.gsap=window.gsap||{to:()=>({to:()=>({}),fromTo:()=>({})}),fromTo:()=>({}),from:()=>({}),set:()=>{},timeline:()=>({to:()=>({}),fromTo:()=>({}),add:()=>({}),set:()=>({})}),config:()=>{},killTweensOf:()=>{}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" crossorigin="anonymous" onerror="if(window.gsap)window.gsap.ScrollTrigger=window.gsap.ScrollTrigger||{create:()=>{},refresh:()=>{},kill:()=>{}}"></script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #050508;
            color: #ffffff;
            overflow: hidden;
        }
        .mono {
            font-family: 'Space Grotesk', monospace;
        }
        .bg-mesh {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image: 
                radial-gradient(at 0% 0%, rgba(239, 68, 68, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
            filter: blur(80px);
            pointer-events: none;
        }
        .noise {
            position: fixed;
            inset: 0;
            z-index: 1;
            background-image: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)"/%3E%3C/svg%3E');
            opacity: 0.015;
            pointer-events: none;
        }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        /* Video Container Hover Controls */
        .video-controls-overlay {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .video-container:hover .video-controls-overlay {
            opacity: 1;
        }
    </style>


   <!-- 1. Third-Party Libraries First -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
<script>
    if (typeof io !== 'function') {
        document.write('<script src="' + (window.NEXUS_SIGNALING_URL || (location.protocol + '//' + location.hostname + ':3000')) + '/socket.io/socket.io.js"><\/script>');
    }
    if (typeof io !== 'function') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.7.5/dist/socket.io.min.js"><\/script>');
    }
</script>
<script src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js" crossorigin="anonymous"></script>

<!-- 2. Your Custom Scripts Last -->
<script src="watch_party.js?v=<?php echo time(); ?>"></script>
</head>
<body class="h-screen w-screen flex relative selection:bg-red-500/30" data-barba="wrapper">
    <?php include __DIR__ . '/../frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/../frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/../frontend/components/toast.php'; ?>
<div id="barba-container" class="flex w-full h-full" data-barba="container" data-barba-namespace="watch_party" x-data="watchParty()" >


<div class="bg-mesh"></div>
    <div class="noise"></div>
    

    <!-- Sidebar / Server List (Discord style) -->
    <div class="w-20 shrink-0 h-full bg-[#030305]/90 backdrop-blur-xl border-r border-white/5 flex flex-col items-center py-6 gap-4 z-20 relative">
        <button type="button" @click="leaveRoom()" class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.3)] hover:scale-105 hover:rounded-[12px] transition-all duration-300 cursor-pointer" title="Leave room">
            <span class="material-symbols-outlined text-white font-bold">arrow_back</span>
        </button>
        <div class="w-8 h-[2px] bg-white/10 rounded-full my-2"></div>
        <div class="flex-1 w-full flex flex-col items-center gap-4 overflow-y-auto custom-scrollbar py-2 px-1">
            <template x-for="user in participants" :key="user.peerId || user.socketId || user.id">
                <div class="w-12 h-12 rounded-[24px] hover:rounded-[16px] flex items-center justify-center transition-all duration-300 relative group overflow-visible"
                     :title="user.name">
                    <div class="absolute inset-0 z-0 overflow-hidden rounded-[inherit] scale-[1.05] bg-white/5 border border-white/10">
                        <img :src="user.avatar" class="absolute inset-0 h-full w-full object-cover" alt="">
                    </div>
                    <template x-if="user.border">
                        <img :src="user.border" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                    </template>
                    <div class="absolute left-0 w-1 bg-white rounded-r-full transition-all duration-300 h-0 group-hover:h-6 top-1/2 -translate-y-1/2 z-20"></div>
                </div>
            </template>
            <button type="button" @click="showInviteMenu = true" class="w-12 h-12 rounded-[24px] bg-white/5 hover:bg-emerald-500 hover:text-white text-emerald-400 hover:rounded-[16px] flex items-center justify-center transition-all duration-300 relative group shadow-[0_0_15px_rgba(16,185,129,0.1)] hover:shadow-[0_0_20px_rgba(16,185,129,0.4)]" title="Invite a friend">
                <span class="material-symbols-outlined">add</span>
            </button>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col h-full relative z-10 overflow-hidden bg-[#0a0a0f]/50">
        
        <!-- Header -->
        <header class="h-16 border-b border-white/5 flex items-center justify-between px-6 shrink-0 bg-[#050508]/80 backdrop-blur-md relative z-30">
            <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-red-500 text-[28px]">movie</span>
                <div>
                    <h1 class="font-bold text-lg leading-tight" x-text="roomName"></h1>
                    <p class="text-xs text-white/50 mono flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span x-text="participants.length + ' online'"></span>
                        <span class="text-white/30">·</span>
                        <span x-text="liveStatus"></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
               <button type="button" @click="openReportRoomModal()" class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 rounded-xl text-sm font-bold flex items-center gap-2 text-red-400 hover:text-red-300 transition-colors" title="Report this room">
                    <span class="material-symbols-outlined text-[18px]">flag</span>
                    Report
               </button>
               <div class="relative">
    <button @click="showInviteMenu = !showInviteMenu; if (showInviteMenu) refreshOnlineStatus()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors">
        <span class="material-symbols-outlined text-[18px]">person_add</span>
        Invite
    </button>

    <!-- Dropdown Menu -->
    <div x-show="showInviteMenu" @click.away="showInviteMenu = false" class="absolute right-0 top-full mt-2 w-64 bg-[#050508]/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl overflow-hidden z-50 p-2" x-transition>
        <div class="text-xs font-bold text-white/50 px-2 pb-2 mb-2 border-b border-white/5 uppercase tracking-wider">Your Friends</div>
        
        <div class="max-h-60 overflow-y-auto custom-scrollbar flex flex-col gap-1">
            <template x-for="friend in friends" :key="friend.user_id">
                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition-colors group">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="relative shrink-0 w-9 h-9 overflow-visible">
                            <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] border border-white/10">
                                <img :src="resolveAvatarUrl(friend.avatar_url, friend.user_name)" class="absolute inset-0 h-full w-full object-cover" alt="">
                            </div>
                            <template x-if="friend.border_preview">
                                <img :src="friend.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                            </template>
                            <span class="absolute -bottom-0.5 -right-0.5 z-20 h-2 w-2 rounded-full"
                                  :class="isUserOnline(friend)
                                    ? 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.85)] ring-1 ring-[#07070b]'
                                    : 'bg-gray-500 ring-1 ring-[#07070b]'"
                                  :title="isUserOnline(friend) ? 'Online' : 'Offline'"></span>
                        </div>
                        <span class="text-sm font-medium truncate" x-text="friend.user_name"></span>
                    </div>
                    <button @click="inviteFriend(friend.user_id, friend.user_name)" class="text-emerald-400 hover:text-white hover:bg-emerald-500 p-1.5 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                        <span class="material-symbols-outlined text-[16px]">send</span>
                    </button>
                </div>
            </template>
            <div x-show="friends.length === 0" class="text-sm text-white/40 text-center py-4">
                No friends found.
            </div>
        </div>
    </div>
</div>
            </div>
        </header>

        <!-- Content Area -->
        <div id="content-area" class="flex-1 flex overflow-hidden relative">

            <div class="absolute inset-0 z-50 bg-[#050508]/90 backdrop-blur-md flex flex-col items-center justify-center gap-4"
                 x-show="isConnecting || isLeaving"
                 x-transition.opacity
                 x-cloak>
                <div class="w-14 h-14 border-4 border-red-500/25 border-t-red-500 rounded-full animate-spin"></div>
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-white/80" x-text="connectionHint"></p>
                <p class="text-xs text-white/40" x-show="isConnecting && !isLeaving">Finding people in this room…</p>
            </div>
            
            <!-- Main Movie Player Background -->
            <div class="absolute inset-0 bg-black overflow-hidden group video-container z-0" @mousemove="showControls = true; clearTimeout(controlsTimeout); controlsTimeout = setTimeout(() => { if (isPlaying) showControls = false }, 2500)" @mouseleave="if (isPlaying) showControls = false">
                    
                    <template x-if="videoUrl && isYouTubeUrl(videoUrl)">
                        <iframe class="w-full h-full bg-black" :src="getYouTubeWatchEmbed(videoUrl)" title="Watch party movie" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    </template>
                    <template x-if="videoUrl && !isYouTubeUrl(videoUrl)">
                        <video id="main-player" class="w-full h-full object-contain bg-black cursor-pointer" x-ref="videoPlayer" @click="togglePlay" @timeupdate="updateProgress" @ended="isPlaying = false" :src="videoUrl" playsinline preload="metadata" poster="https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=1600&h=900"></video>
                    </template>
                    
                    <template x-if="!videoUrl">
                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-[#050508] overflow-hidden group/empty cursor-pointer" @click="showMovieModal = true">
                            <div class="absolute inset-0 bg-gradient-to-tr from-red-500/10 to-transparent opacity-0 group-hover/empty:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                            
                            <div class="w-32 h-32 rounded-full border border-red-500/30 flex items-center justify-center relative mb-6 group-hover/empty:scale-110 transition-transform duration-500 ease-out shadow-[0_0_50px_rgba(239,68,68,0.2)]">
                                <div class="absolute inset-0 rounded-full border-t border-red-500 animate-spin" style="animation-duration: 3s;"></div>
                                <div class="absolute inset-2 rounded-full border-b border-white/20 animate-spin" style="animation-duration: 2s; animation-direction: reverse;"></div>
                                <span class="material-symbols-outlined text-[48px] text-red-400 group-hover/empty:text-red-300 transition-colors">movie</span>
                                
                                <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shadow-lg transform group-hover/empty:rotate-90 transition-transform duration-300">
                                    <span class="material-symbols-outlined text-white text-[20px]">add</span>
                                </div>
                            </div>
                            
                            <h2 class="text-2xl font-black text-white tracking-widest uppercase mb-2">Select a Movie</h2>
                            <p class="text-white/50 text-sm max-w-sm text-center">Choose a movie from our library to start the watch party and sync playback with your friends.</p>
                        </div>
                    </template>
                    
                    <!-- Loading State overlay -->
                    <div class="absolute inset-0 bg-black/80 flex items-center justify-center z-10 transition-opacity duration-500" x-show="isLoading || movieSwitching" x-transition.opacity>
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 border-4 border-red-500/30 border-t-red-500 rounded-full animate-spin"></div>
                            <p class="text-xs font-bold uppercase tracking-widest text-white/60" x-show="movieSwitching">Switching movie…</p>
                        </div>
                    </div>

                    <!-- Giant Play Button Overlay (when paused) -->
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10 transition-opacity duration-300 cursor-pointer" x-show="videoUrl && !isYouTubeUrl(videoUrl) && !isPlaying && !isLoading" @click="togglePlay" x-transition.opacity>
                        <div class="w-24 h-24 bg-red-500/90 rounded-full flex items-center justify-center shadow-[0_0_40px_rgba(239,68,68,0.6)] backdrop-blur-md transform transition-transform hover:scale-110">
                            <span class="material-symbols-outlined text-[48px] text-white ml-2">play_arrow</span>
                        </div>
                    </div>

                    <!-- Player Controls Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex flex-col justify-end p-6 z-20 transition-opacity duration-500 pointer-events-none" :class="(showControls && videoUrl && !isYouTubeUrl(videoUrl)) ? 'opacity-100' : 'opacity-0'" x-show="videoUrl && !isYouTubeUrl(videoUrl)">
                        
                        <!-- Progress Bar -->
                        <div class="w-full h-1.5 bg-white/20 rounded-full mb-6 cursor-pointer relative group/progress pointer-events-auto" @click="seek" x-ref="progressBar">
                            <!-- Buffer Bar -->
                            <div class="absolute left-0 top-0 h-full bg-white/30 rounded-full transition-all duration-300" :style="`width: ${bufferPercent}%`"></div>
                            <!-- Current Progress -->
                            <div class="absolute left-0 top-0 h-full bg-gradient-to-r from-red-600 to-red-400 rounded-full shadow-[0_0_15px_rgba(239,68,68,0.8)] transition-all duration-100 ease-linear" :style="`width: ${progressPercent}%`"></div>
                            <!-- Scrubber -->
                            <div class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-white rounded-full shadow-[0_0_10px_rgba(255,255,255,0.8)] scale-0 group-hover/progress:scale-100 transition-transform duration-200" :style="`left: calc(${progressPercent}% - 8px)`"></div>
                            <!-- Tooltip (time hover) -->
                        </div>

                        <!-- Controls -->
                        <div class="flex items-center justify-between pointer-events-auto">
                            <div class="flex items-center gap-5">
                                <button class="text-white hover:text-red-400 transition-colors transform hover:scale-110" @click="togglePlay">
                                    <span class="material-symbols-outlined text-[36px]" x-text="isPlaying ? 'pause' : 'play_arrow'">play_arrow</span>
                                </button>
                                
                                <button class="text-white hover:text-white/70 transition-colors transform hover:scale-110">
                                    <span class="material-symbols-outlined text-[28px]">skip_next</span>
                                </button>
                                
                                <div class="flex items-center gap-3 group/volume">
                                    <button class="text-white hover:text-red-400 transition-colors" @click="toggleMute">
                                        <span class="material-symbols-outlined text-[24px]" x-text="volume === 0 ? 'volume_off' : (volume < 0.5 ? 'volume_down' : 'volume_up')">volume_up</span>
                                    </button>
                                    <div class="w-0 overflow-hidden group-hover/volume:w-24 transition-all duration-300 flex items-center">
                                        <input type="range" min="0" max="1" step="0.05" x-model="volume" @input="updateVolume" class="w-full h-1 bg-white/30 rounded-lg appearance-none cursor-pointer accent-red-500">
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-1.5 ml-2">
                                    <span class="text-xs text-white/90 font-mono tracking-wider" x-text="formatTime(currentTime)">00:00</span>
                                    <span class="text-xs text-white/50">/</span>
                                    <span class="text-xs text-white/50 font-mono tracking-wider" x-text="formatTime(duration)">00:00</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-5">
                                <button class="text-white hover:text-red-400 transition-colors relative group/cc">
                                    <span class="material-symbols-outlined text-[24px]">closed_caption</span>
                                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full scale-0 group-hover/cc:scale-100 transition-transform"></div>
                                </button>
                                <button class="text-white hover:text-red-400 transition-colors transform hover:rotate-90 duration-300">
                                    <span class="material-symbols-outlined text-[24px]">settings</span>
                                </button>
                                <button class="text-white hover:text-red-400 transition-colors transform hover:scale-110" @click="toggleFullscreen">
                                    <span class="material-symbols-outlined text-[28px]" x-text="isFullscreen ? 'fullscreen_exit' : 'fullscreen'">fullscreen</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Overlays Container -->
            <div class="absolute inset-0 pointer-events-none z-10 gs-stage">
                
                <!-- Left Overlay: Participants (Vertical) -->
                <div class="absolute left-6 top-6 bottom-24 w-40 flex flex-col gap-3 pointer-events-none">

                    <!-- Participants Header & Toggle -->
                    <div class="flex items-center justify-between pointer-events-auto bg-black/20 backdrop-blur-sm px-3 py-2 rounded-xl border border-white/5 shadow-lg">
                        <span class="text-white/80 text-[10px] font-bold uppercase tracking-wider">Members (<span x-text="participants.length"></span>)</span>
                        <button @click="showParticipants = !showParticipants" class="text-white/50 hover:text-white transition-colors bg-white/5 hover:bg-white/10 rounded-lg p-0.5">
                            <span class="material-symbols-outlined text-[18px] transition-transform duration-300" :class="showParticipants ? 'rotate-180' : ''">keyboard_arrow_down</span>
                        </button>
                    </div>

                    <!-- Video Grid (Participants) -->
                    <div class="flex flex-col gap-3 origin-top pointer-events-auto overflow-y-auto custom-scrollbar pr-1 pb-4" x-show="showParticipants" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-y-90" x-transition:enter-end="opacity-100 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-y-100" x-transition:leave-end="opacity-0 scale-y-90">
                        <template x-for="user in participants" :key="user.peerId || user.socketId || user.id">
                            <div class="participant-card w-full aspect-video hover:scale-105 transition-transform duration-300 bg-[#0a0a0f] rounded-xl border border-white/10 overflow-hidden relative group shadow-lg shrink-0">
                                <template x-if="user.stream && user.isSelf">
                                    <video x-show="user.videoOn !== false" x-effect="$el.srcObject = user.stream; $el.muted = true; $el.volume = 0; $el.defaultMuted = true; $el.play && $el.play().catch(()=>{});" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"></video>
                                </template>
                                <template x-if="user.stream && !user.isSelf">
                                    <video x-show="user.videoOn !== false" x-effect="$el.srcObject = user.stream; $el.muted = !!user.muted; $el.play && $el.play().catch(()=>{});" autoplay playsinline class="absolute inset-0 w-full h-full object-cover"></video>
                                </template>
                                <div x-show="!user.stream || user.videoOn === false" class="absolute inset-0 z-[5] flex items-center justify-center bg-[#0a0a0f]">
                                    <div class="relative w-14 h-14 overflow-visible">
                                        <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] border border-white/10">
                                            <img :src="user.avatar" class="absolute inset-0 h-full w-full object-cover" alt="">
                                        </div>
                                        <template x-if="user.border">
                                            <img :src="user.border" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                                        </template>
                                    </div>
                                </div>
                                <div class="absolute bottom-1 left-1 z-20 bg-black/60 backdrop-blur px-1.5 py-0.5 rounded text-[9px] font-bold text-white flex items-center gap-1 border border-white/10">
                                    <div class="relative w-4 h-4 overflow-visible shrink-0">
                                        <div class="absolute inset-0 z-0 overflow-hidden rounded-full">
                                            <img :src="user.avatar" class="absolute inset-0 h-full w-full object-cover" alt="">
                                        </div>
                                        <template x-if="user.border">
                                            <img :src="user.border" class="absolute inset-0 z-10 h-full w-full scale-[1.5] object-contain pointer-events-none" alt="">
                                        </template>
                                    </div>
                                    <span class="truncate max-w-[52px]" x-text="user.name"></span>
                                    <span x-show="user.isHost || (user.isSelf && isHost)" class="text-[8px] text-amber-400 uppercase tracking-wider">Host</span>
                                    <span class="material-symbols-outlined text-[10px]" :class="user.muted ? 'text-red-500' : 'text-green-500'" x-text="user.muted ? 'mic_off' : 'mic'"></span>
                                    <span class="material-symbols-outlined text-[10px]" :class="user.videoOn === false ? 'text-red-500' : 'text-green-500'" x-text="user.videoOn === false ? 'videocam_off' : 'videocam'"></span>
                                    <span x-show="user.chatBanned" class="material-symbols-outlined text-[10px] text-red-400">comments_disabled</span>
                                </div>
                                <div x-show="isHost && !user.isSelf" class="absolute top-1 right-1 z-30 grid grid-cols-2 gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" @click.stop="hostMuteMember(user)" class="w-7 h-7 rounded-lg bg-black/70 border border-white/15 text-white hover:bg-red-500 hover:border-red-400 flex items-center justify-center" :title="user.muted ? 'Unmute member' : 'Mute member'">
                                        <span class="material-symbols-outlined text-[15px]" x-text="user.muted ? 'mic_off' : 'mic'"></span>
                                    </button>
                                    <button type="button" @click.stop="hostVideoMember(user)" class="w-7 h-7 rounded-lg bg-black/70 border border-white/15 text-white hover:bg-red-500 hover:border-red-400 flex items-center justify-center" :title="user.videoOn === false ? 'Turn camera on' : 'Turn camera off'">
                                        <span class="material-symbols-outlined text-[15px]" x-text="user.videoOn === false ? 'videocam_off' : 'videocam'"></span>
                                    </button>
                                    <button type="button" @click.stop="hostBanChat(user)" class="w-7 h-7 rounded-lg bg-black/70 border border-white/15 text-white hover:bg-red-500 hover:border-red-400 flex items-center justify-center" :title="user.chatBanned ? 'Allow chat' : 'Ban from chat'">
                                        <span class="material-symbols-outlined text-[15px]" x-text="user.chatBanned ? 'comments_disabled' : 'chat'"></span>
                                    </button>
                                    <button type="button" @click.stop="kickMember(user)" class="w-7 h-7 rounded-lg bg-black/70 border border-white/15 text-white hover:bg-red-600 hover:border-red-400 flex items-center justify-center" title="Remove from room">
                                        <span class="material-symbols-outlined text-[15px]">person_remove</span>
                                    </button>
                                </div>
                                <div class="absolute inset-0 border-[1.5px] border-emerald-500 rounded-xl opacity-0 transition-opacity pointer-events-none z-10" :class="{'opacity-100': user.speaking}"></div>
                            </div>
                        </template>
                    </div>
                
                </div> <!-- End Left Overlay -->
            </div> <!-- End Overlays Container -->

            <!-- Chat Toggle Button (Visible when chat is closed) -->
            <button @click="showChat = true" 
                    class="absolute right-0 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-l-xl p-2 z-30 transition-all shadow-lg backdrop-blur-md border border-r-0 border-white/10 pointer-events-auto"
                    x-show="!showChat"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0">
                <span class="material-symbols-outlined">chevron_left</span>
                <span x-show="isHost && currentJoinRequest"
                      class="absolute top-1 left-1 w-2 h-2 rounded-full bg-indigo-400 shadow-[0_0_8px_#818cf8] animate-pulse"></span>
            </button>

            <!-- Right: Chat & Activities -->
            <div class="absolute right-0 top-0 bottom-0 w-[340px] shrink-0 border-l border-white/10 bg-[#030305]/40 backdrop-blur-md flex flex-col z-20 gs-chat shadow-2xl pointer-events-auto"
                 x-show="showChat"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="translate-x-full opacity-0">
                <!-- Chat Header -->
                <div class="h-14 border-b border-white/5 flex items-center justify-between px-4 gap-2 text-sm font-bold text-white/90">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">chat</span> Room Chat
                        <span x-show="isHost && currentJoinRequest"
                              class="px-1.5 py-0.5 rounded bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-[9px] font-black uppercase tracking-wider">
                            Request
                        </span>
                    </div>
                    <button @click="showChat = false" class="text-white/50 hover:text-white transition-colors bg-white/5 hover:bg-white/10 rounded-lg p-1 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                </div>
                
                <!-- Messages -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-4 flex flex-col gap-4" id="chat-container">
                    <template x-for="(msg, i) in messages" :key="msg.id || msg.request_id || i">
                        <div class="flex gap-3 chat-msg-item py-1">
                            <div class="relative w-8 h-8 shrink-0 overflow-visible mt-0.5">
                                <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] border border-white/10">
                                    <img :src="msg.avatar" class="absolute inset-0 h-full w-full object-cover" alt="">
                                </div>
                                <template x-if="msg.border">
                                    <img :src="msg.border" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline gap-2 mb-0.5">
                                    <span class="text-xs font-bold" :class="msg.isSelf ? 'text-red-400' : 'text-white'" x-text="msg.name"></span>
                                    <span class="text-[9px] text-white/40 mono" x-text="msg.time"></span>
                                </div>
                                <template x-if="msg.type === 'join_request'">
                                    <div class="rounded-xl border border-indigo-500/25 bg-indigo-500/10 px-3 py-2.5 mt-1">
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-300 mb-1">Join request</p>
                                        <p class="text-sm text-white/80 leading-relaxed" x-text="msg.text || 'wants to join the watch party.'"></p>
                                        <div class="flex gap-2 mt-3" x-show="isHost && (msg.request_status || 'pending') === 'pending'">
                                            <button type="button"
                                                    @click="respondJoinRequest('decline', msg)"
                                                    class="flex-1 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 text-[10px] font-black uppercase tracking-wider">
                                                Decline
                                            </button>
                                            <button type="button"
                                                    @click="respondJoinRequest('accept', msg)"
                                                    class="flex-1 py-1.5 rounded-lg bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-black uppercase tracking-wider">
                                                Accept
                                            </button>
                                        </div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider mt-2 text-emerald-400"
                                           x-show="msg.request_status === 'accepted'">Accepted</p>
                                        <p class="text-[11px] font-bold uppercase tracking-wider mt-2 text-white/35"
                                           x-show="msg.request_status === 'declined'">Declined</p>
                                    </div>
                                </template>
                                <template x-if="msg.type !== 'join_request'">
                                    <p class="text-sm text-white/70 leading-relaxed" x-text="msg.text"></p>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Chat Input -->
                <div class="p-4 border-t border-white/5 bg-transparent">
                    <div class="bg-white/5 border border-white/10 rounded-xl p-1 flex items-center focus-within:border-white/20 transition-colors">
                        <button class="w-8 h-8 flex items-center justify-center text-white/40 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        </button>
                        <input type="text" x-model="newMessage" @keydown.enter="sendMessage" :disabled="chatBanned" :placeholder="chatBanned ? 'The host banned you from chat' : 'Message room...'" class="flex-1 bg-transparent border-none outline-none text-sm text-white px-2 placeholder-white/30 disabled:opacity-50">
                        <button class="w-8 h-8 flex items-center justify-center text-white/40 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[18px]">mood</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Controls (Voice/Video toggles) -->
        <div class="h-20 border-t border-white/5 bg-[#050508]/90 backdrop-blur-xl flex items-center justify-center gap-4 px-6 relative z-30 gs-controls">
            <button @click="toggleMic($event)" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shadow-lg border" :class="isMuted ? 'bg-red-500/10 border-red-500/20 text-red-500 hover:bg-red-500/20' : 'bg-white/10 border-white/10 text-white hover:bg-white/20'" :title="forcedMuted ? 'The host muted your microphone' : (isMuted ? 'Unmute' : 'Mute')">
                <span class="material-symbols-outlined" x-text="isMuted ? 'mic_off' : 'mic'"></span>
            </button>
            <button @click="toggleVideo($event)" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shadow-lg border" :class="!isVideoOn ? 'bg-red-500/10 border-red-500/20 text-red-500 hover:bg-red-500/20' : 'bg-white/10 border-white/10 text-white hover:bg-white/20'" :title="forcedVideoOff ? 'The host turned off your camera' : (isVideoOn ? 'Turn camera off' : 'Turn camera on')">
                <span class="material-symbols-outlined" x-text="!isVideoOn ? 'videocam_off' : 'videocam'"></span>
            </button>
            <button @click="showMovieModal = true" class="w-12 h-12 rounded-xl bg-indigo-500/10 border-indigo-500/20 text-indigo-400 hover:bg-indigo-500/20 flex items-center justify-center transition-all duration-300 shadow-lg border">
                <span class="material-symbols-outlined">movie</span>
            </button>
            
            <div class="w-px h-8 bg-white/10 mx-2"></div>
            
            <button type="button" @click="leaveRoom()" class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.3)] hover:scale-105 hover:rounded-[12px] transition-all duration-300 cursor-pointer">
    <span class="material-symbols-outlined text-white font-bold">arrow_back</span>
</button>
        </div>
    </div>

    
    <script>
        document.addEventListener('fullscreenchange', () => {
            const cursorGlow = document.getElementById('cursor-glow');
            const innerCursor = document.querySelector('.inner-cursor');
            const contentArea = document.getElementById('content-area');
            
            if (document.fullscreenElement === contentArea) {
                // Move cursors to content area so they show in fullscreen
                if (cursorGlow) contentArea.appendChild(cursorGlow);
                if (innerCursor) contentArea.appendChild(innerCursor);
            } else {
                // Move them back to body
                if (cursorGlow) document.body.appendChild(cursorGlow);
                if (innerCursor) document.body.appendChild(innerCursor);
            }
        });
    </script>
    
    <!-- Movie Selection Modal -->
    <div x-show="showMovieModal" class="fixed inset-0 z-[100] flex flex-col justify-end pointer-events-auto" style="display: none;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-show="showMovieModal" x-transition.opacity @click="showMovieModal = false"></div>
        
        <div class="relative w-full h-[70vh] bg-[#050508] border-t border-white/10 rounded-t-3xl flex flex-col shadow-2xl z-10 overflow-hidden" 
             x-show="showMovieModal" 
             x-transition:enter="transition ease-out duration-300 transform" 
             x-transition:enter-start="translate-y-full" 
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
             
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between p-6 border-b border-white/5 gap-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2 shrink-0">
                    <span class="material-symbols-outlined text-red-500">movie</span>
                    Choose Movie to Play
                </h3>
                
                <div class="flex items-center gap-3 flex-1 justify-end">
                    <!-- Search Bar -->
                    <div class="relative w-full max-w-xs">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-[18px]">search</span>
                        <input type="text" x-model="movieSearchQuery" placeholder="Search movies..." class="w-full bg-[#030305]/50 border border-white/10 rounded-xl py-2 pl-10 pr-4 text-sm text-white placeholder-white/40 focus:outline-none focus:border-red-500/50 transition-colors">
                    </div>

                    <!-- Filter Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="h-[38px] px-4 bg-[#030305]/50 border border-white/10 rounded-xl text-sm text-white flex items-center gap-2 hover:bg-white/5 transition-colors whitespace-nowrap">
                            <span class="material-symbols-outlined text-[16px] text-red-400">filter_list</span>
                            <span x-text="movieFilter === 'all' ? 'All Genres' : movieFilter"></span>
                            <span class="material-symbols-outlined text-[16px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                        </button>
                        
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             class="absolute right-0 top-full mt-2 w-48 bg-[#0a0a0f] border border-white/10 rounded-xl shadow-xl overflow-hidden z-50">
                             
                            <div class="p-1 flex flex-col">
                                <button @click="movieFilter = 'all'; open = false" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors" :class="movieFilter === 'all' ? 'bg-red-500/10 text-red-400' : 'text-white/70 hover:bg-white/5 hover:text-white'">
                                    <span class="material-symbols-outlined text-[16px]">category</span>
                                    All Genres
                                </button>
                                <button @click="movieFilter = 'Action'; open = false" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors" :class="movieFilter === 'Action' ? 'bg-red-500/10 text-red-400' : 'text-white/70 hover:bg-white/5 hover:text-white'">
                                    <span class="material-symbols-outlined text-[16px]">sports_martial_arts</span>
                                    Action
                                </button>
                                <button @click="movieFilter = 'Sci-Fi'; open = false" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors" :class="movieFilter === 'Sci-Fi' ? 'bg-red-500/10 text-red-400' : 'text-white/70 hover:bg-white/5 hover:text-white'">
                                    <span class="material-symbols-outlined text-[16px]">rocket_launch</span>
                                    Sci-Fi
                                </button>
                                <button @click="movieFilter = 'Horror'; open = false" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors" :class="movieFilter === 'Horror' ? 'bg-red-500/10 text-red-400' : 'text-white/70 hover:bg-white/5 hover:text-white'">
                                    <span class="material-symbols-outlined text-[16px]">psychology_alt</span>
                                    Horror
                                </button>
                                <button @click="movieFilter = 'Comedy'; open = false" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors" :class="movieFilter === 'Comedy' ? 'bg-red-500/10 text-red-400' : 'text-white/70 hover:bg-white/5 hover:text-white'">
                                    <span class="material-symbols-outlined text-[16px]">theater_comedy</span>
                                    Comedy
                                </button>
                            </div>
                        </div>
                    </div>

                    <button @click="showMovieModal = false" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 text-white flex items-center justify-center transition-colors shrink-0">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <!-- Movie Grid -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <template x-for="movie in filteredMovies" :key="movie.id">
                        <div class="group relative aspect-[2/3] rounded-2xl overflow-hidden cursor-pointer bg-white/5" 
                             @click="selectMovie(movie)"
                             @mouseenter="hoveredMovieId = movie.id"
                             @mouseleave="hoveredMovieId = null">
                             
                            <!-- Poster Image -->
                            <img :src="movie.img || movie.cover_image" 
                                 loading="lazy" decoding="async" 
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                 :class="hoveredMovieId === movie.id && (movie.trailer || movie.actual_video_url) ? 'opacity-0' : 'opacity-100'">
                                 
                            <!-- Video Preview (Trailer) -->
                            <template x-if="hoveredMovieId === movie.id">
                                <div class="absolute inset-0 z-0 bg-black pointer-events-none overflow-hidden">
                                    <template x-if="movie.trailer && isYouTubeUrl(movie.trailer)">
                                        <iframe :src="getYouTubeEmbedUrl(movie.trailer, true)" 
                                                class="w-full h-full object-cover scale-150" 
                                                frameborder="0" allow="autoplay; encrypted-media"></iframe>
                                    </template>
                                    <template x-if="movie.actual_video_url && !isYouTubeUrl(movie.trailer)">
                                        <video :src="movie.actual_video_url" class="w-full h-full object-cover" autoplay muted loop playsinline></video>
                                    </template>
                                </div>
                            </template>

                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex flex-col justify-end p-4 z-10 pointer-events-none transition-opacity duration-300"
                                 :class="hoveredMovieId === movie.id && (movie.trailer || movie.actual_video_url) ? 'opacity-0' : 'opacity-100'">
                                <h4 class="text-white font-bold text-sm truncate" x-text="movie.title"></h4>
                                <p class="text-white/50 text-[10px]" x-text="movie.year || (movie.duration ? movie.duration + 'm' : '')"></p>
                            </div>
                            
                            <div x-show="!(hoveredMovieId === movie.id && (movie.trailer || movie.actual_video_url))" class="absolute inset-0 bg-red-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showInviteSentModal"
         class="fixed inset-0 z-[200] flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm"
             @click="showInviteSentModal = false"
             x-show="showInviteSentModal"
             x-transition.opacity></div>
        <div class="relative w-full max-w-sm bg-[#0a0a0f] border border-emerald-500/30 rounded-2xl p-6 shadow-[0_0_60px_rgba(16,185,129,0.2)] overflow-hidden"
             x-show="showInviteSentModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-transparent to-indigo-500/10 pointer-events-none"></div>
            <div class="relative z-10 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center shadow-[0_0_30px_rgba(16,185,129,0.35)]">
                    <span class="material-symbols-outlined text-emerald-400 text-3xl">mark_email_read</span>
                </div>
                <h3 class="text-xl font-black text-white tracking-tight mb-1">Invite sent</h3>
                <p class="text-sm text-white/55 mb-6">
                    <span class="text-white font-semibold" x-text="inviteSentName"></span>
                    will see it in their notifications.
                </p>
                <button type="button"
                        @click="showInviteSentModal = false"
                        class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black uppercase tracking-wider text-xs transition-colors">
                    Got it
                </button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/report_room_modal.php'; ?>
</div>

    <?php include __DIR__ . '/../frontend/components/barba_scripts.php'; ?>
   


</body>
</html>
