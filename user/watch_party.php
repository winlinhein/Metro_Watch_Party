<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - Watch Party</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
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



</head>
<body x-data="watchParty()" x-init="init()" class="h-screen w-screen flex relative selection:bg-red-500/30">
    <?php include __DIR__ . '/../frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/../frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/../frontend/components/toast.php'; ?>

<div class="bg-mesh"></div>
    <div class="noise"></div>
    

    <!-- Sidebar / Server List (Discord style) -->
    <div class="w-20 shrink-0 h-full bg-[#030305]/90 backdrop-blur-xl border-r border-white/5 flex flex-col items-center py-6 gap-4 z-20 relative">
        <a href="dashboard.php" class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.3)] hover:scale-105 hover:rounded-[12px] transition-all duration-300 cursor-pointer">
            <span class="material-symbols-outlined text-white font-bold">arrow_back</span>
        </a>
        <div class="w-8 h-[2px] bg-white/10 rounded-full my-2"></div>
        <div class="flex-1 w-full flex flex-col items-center gap-3 overflow-y-auto custom-scrollbar">
            <!-- Mock other parties/servers -->
            <template x-for="i in 3">
                <div class="w-12 h-12 rounded-[24px] bg-white/5 hover:bg-white/10 hover:rounded-[16px] flex items-center justify-center transition-all duration-300 cursor-pointer relative group">
                    <img :src="`https://ui-avatars.com/api/?name=U${i}&background=random&color=fff`" class="w-full h-full object-cover rounded-[inherit]">
                    <div class="absolute left-0 w-1 bg-white rounded-r-full transition-all duration-300 h-0 group-hover:h-6 top-1/2 -translate-y-1/2"></div>
                </div>
            </template>
            <div class="w-12 h-12 rounded-[24px] bg-white/5 hover:bg-emerald-500 hover:text-white text-emerald-400 hover:rounded-[16px] flex items-center justify-center transition-all duration-300 cursor-pointer relative group shadow-[0_0_15px_rgba(16,185,129,0.1)] hover:shadow-[0_0_20px_rgba(16,185,129,0.4)]">
                <span class="material-symbols-outlined">add</span>
            </div>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col h-full relative z-10 overflow-hidden bg-[#0a0a0f]/50">
        
        <!-- Header -->
        <header class="h-16 border-b border-white/5 flex items-center justify-between px-6 shrink-0 bg-[#050508]/80 backdrop-blur-md relative z-30">
            <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-red-500 text-[28px]">movie</span>
                <div>
                    <h1 class="font-bold text-lg leading-tight">Dune: Part Two Watch Party</h1>
                    <p class="text-xs text-white/50 mono flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span x-text="participants.length + ' online'"></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <!-- Invite Button removed -->
            </div>
        </header>

        <!-- Content Area -->
        <div id="content-area" class="flex-1 flex overflow-hidden relative">
            
            <!-- Main Movie Player Background -->
            <div class="absolute inset-0 bg-black overflow-hidden group video-container z-0" @mousemove="showControls = true; clearTimeout(controlsTimeout); controlsTimeout = setTimeout(() => { if (isPlaying) showControls = false }, 2500)" @mouseleave="if (isPlaying) showControls = false">
                    
                    <video id="main-player" class="w-full h-full object-contain bg-black cursor-pointer" x-ref="videoPlayer" @click="togglePlay" @timeupdate="updateProgress" @ended="isPlaying = false" src="https://upload.wikimedia.org/wikipedia/commons/transcoded/8/88/Big_Buck_Bunny_alt.webm/Big_Buck_Bunny_alt.webm.720p.vp9.webm" playsinline preload="metadata" poster="https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=1600&h=900"></video>
                    
                    <!-- Loading State overlay -->
                    <div class="absolute inset-0 bg-black/80 flex items-center justify-center z-10 transition-opacity duration-500" x-show="isLoading" x-transition.opacity>
                        <div class="w-12 h-12 border-4 border-red-500/30 border-t-red-500 rounded-full animate-spin"></div>
                    </div>

                    <!-- Giant Play Button Overlay (when paused) -->
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10 transition-opacity duration-300 cursor-pointer" x-show="!isPlaying && !isLoading" @click="togglePlay" x-transition.opacity>
                        <div class="w-24 h-24 bg-red-500/90 rounded-full flex items-center justify-center shadow-[0_0_40px_rgba(239,68,68,0.6)] backdrop-blur-md transform transition-transform hover:scale-110">
                            <span class="material-symbols-outlined text-[48px] text-white ml-2">play_arrow</span>
                        </div>
                    </div>

                    <!-- Player Controls Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex flex-col justify-end p-6 z-20 transition-opacity duration-500 pointer-events-none" :class="showControls ? 'opacity-100' : 'opacity-0'">
                        
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
                        <template x-for="(user, index) in participants" :key="index">
                            <div class="participant-card w-full aspect-video hover:scale-105 transition-transform duration-300 bg-white/5 rounded-xl border border-white/10 overflow-hidden relative group shadow-lg shrink-0">
                                <img :src="user.cam" alt="User Cam" class="w-full h-full object-cover">
                                <div class="absolute bottom-1 left-1 bg-black/60 backdrop-blur px-1.5 py-0.5 rounded text-[9px] font-bold text-white flex items-center gap-1 border border-white/10">
                                    <span class="truncate max-w-[60px]" x-text="user.name"></span>
                                    <span class="material-symbols-outlined text-[10px] text-red-500" x-show="user.muted">mic_off</span>
                                </div>
                                <!-- Speaking indicator -->
                                <div class="absolute inset-0 border-[1.5px] border-emerald-500 rounded-xl opacity-0 transition-opacity" :class="{'opacity-100': user.speaking}"></div>
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
                    </div>
                    <button @click="showChat = false" class="text-white/50 hover:text-white transition-colors bg-white/5 hover:bg-white/10 rounded-lg p-1 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                </div>
                
                <!-- Messages -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-4 flex flex-col gap-4" id="chat-container">
                    <template x-for="(msg, i) in messages" :key="i">
                        <div class="flex gap-3 chat-msg-item">
                            <img :src="msg.avatar" class="w-8 h-8 rounded-full border border-white/10 shrink-0">
                            <div>
                                <div class="flex items-baseline gap-2 mb-0.5">
                                    <span class="text-xs font-bold" :class="msg.isSelf ? 'text-red-400' : 'text-white'" x-text="msg.name"></span>
                                    <span class="text-[9px] text-white/40 mono" x-text="msg.time"></span>
                                </div>
                                <p class="text-sm text-white/70 leading-relaxed" x-text="msg.text"></p>
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
                        <input type="text" x-model="newMessage" @keydown.enter="sendMessage" placeholder="Message room..." class="flex-1 bg-transparent border-none outline-none text-sm text-white px-2 placeholder-white/30">
                        <button class="w-8 h-8 flex items-center justify-center text-white/40 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[18px]">mood</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Controls (Voice/Video toggles) -->
        <div class="h-20 border-t border-white/5 bg-[#050508]/90 backdrop-blur-xl flex items-center justify-center gap-4 px-6 relative z-30 gs-controls">
            <button @click="toggleMic($event)" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shadow-lg border" :class="isMuted ? 'bg-red-500/10 border-red-500/20 text-red-500 hover:bg-red-500/20' : 'bg-white/10 border-white/10 text-white hover:bg-white/20'">
                <span class="material-symbols-outlined" x-text="isMuted ? 'mic_off' : 'mic'"></span>
            </button>
            <button @click="toggleVideo($event)" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shadow-lg border" :class="!isVideoOn ? 'bg-red-500/10 border-red-500/20 text-red-500 hover:bg-red-500/20' : 'bg-white/10 border-white/10 text-white hover:bg-white/20'">
                <span class="material-symbols-outlined" x-text="!isVideoOn ? 'videocam_off' : 'videocam'"></span>
            </button>
            <button class="w-12 h-12 rounded-xl bg-white/10 border-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-all duration-300 shadow-lg border">
                <span class="material-symbols-outlined">present_to_all</span>
            </button>
            
            <div class="w-px h-8 bg-white/10 mx-2"></div>
            
            <a href="dashboard.php" class="px-6 py-3 rounded-xl bg-red-500 hover:bg-red-600 text-white font-bold text-sm transition-all duration-300 shadow-[0_0_20px_rgba(239,68,68,0.3)] hover:shadow-[0_0_30px_rgba(239,68,68,0.5)] flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">call_end</span> Leave Party
            </a>
        </div>
    </div>

    <script src="watch_party.js"></script>

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




</body>
</html>
