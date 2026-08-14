<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - Watch Movies Together</title>
    
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
    
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />
    
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.1/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" crossorigin="anonymous"></script>
    
    

    <style>
        body { 
            font-family: 'Space Grotesk', sans-serif;
            background-color: #030305;
            color: #ffffff;
            overflow-x: hidden;
            cursor: none;
        }

        

        .mono { font-family: 'JetBrains Mono', monospace; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(239,68,68,0.5); }

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
            inset: 0;
            background: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="4" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)"/%3E%3C/svg%3E');
            opacity: 0.04;
            pointer-events: none;
            z-index: -1;
        }

        .glass-nav {
            background: rgba(3, 3, 5, 0.6);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .glass-card:hover {
            transform: translateY(-8px);
            border-color: rgba(239, 68, 68, 0.3);
            box-shadow: 0 20px 40px -10px rgba(239, 68, 68, 0.15);
        }

        .glass-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }

        .animated-border {
            position: relative;
            background: rgba(255,255,255,0.05);
            border-radius: 9999px;
            z-index: 1;
        }
        .animated-border::before {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: 9999px;
            background: linear-gradient(45deg, #ef4444, #4f46e5, #ef4444);
            z-index: -1;
            background-size: 200% 200%;
            animation: gradientMove 3s ease infinite;
            transition: opacity 0.3s;
            opacity: 0.5;
        }
        .animated-border:hover::before {
            opacity: 1;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        
        
        

        .grid-pattern {
            position: absolute;
            inset: 0;
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            mask-image: linear-gradient(to bottom, black 40%, transparent);
            -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent);
            z-index: -1;
        }

        .text-glow {
            text-shadow: 0 0 30px rgba(239, 68, 68, 0.5);
        }

        .floating-element {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(1deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
    </style>



    <script src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js" crossorigin="anonymous"></script>
    <script src="/js/nexus_scripts.js?v=5"></script>
</head>
<body data-barba="wrapper">
    <?php include __DIR__ . '/frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/frontend/components/toast.php'; ?>
<div id="barba-container" data-barba="container" data-barba-namespace="index" x-data="{ mobileMenuOpen: false }">


<div class="bg-mesh"></div>
    <div class="noise"></div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="#" class="flex items-center gap-3 group cursor-pointer">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] group-hover:scale-110 transition-transform duration-500">
                    <span class="material-symbols-outlined text-white">movie</span>
                </div>
                <span class="text-xl font-bold tracking-tight group-hover:text-red-400 transition-colors">NEXUS</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-4">
                <a href="#features" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer shadow-[0_0_15px_rgba(0,0,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.05)] flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-red-400 transition-colors">bolt</span>Features</a>
                <a href="#movies" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer shadow-[0_0_15px_rgba(0,0,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.05)] flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-indigo-400 transition-colors">theaters</span>Movies</a>
                <a href="learning.php" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer shadow-[0_0_15px_rgba(0,0,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.05)] flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-emerald-400 transition-colors">integration_instructions</span>How it works</a>
            </div>

            <!-- Auth Buttons -->
            <div class="hidden md:flex items-center gap-4">
                <a href="user/dashboard.php" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-transparent hover:bg-white/5 border border-transparent hover:border-white/10 transition-all duration-300 cursor-pointer flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-blue-400 transition-colors">login</span>Sign In</a>
                <a href="user/dashboard.php" class="animated-border px-6 py-2.5 text-sm font-bold text-white flex items-center gap-2 cursor-pointer">
                    Get Started <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>

            <!-- Mobile Toggle -->
            <button class="md:hidden text-white/70 hover:text-white cursor-pointer" @click="mobileMenuOpen = !mobileMenuOpen">
                <span class="material-symbols-outlined text-3xl" x-text="mobileMenuOpen ? 'close' : 'menu'">menu</span>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden absolute top-20 left-0 w-full glass-nav border-t border-white/5" 
             x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4">
            <div class="p-6 flex flex-col gap-4">
                <a href="#features" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer shadow-[0_0_15px_rgba(0,0,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.05)] flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-red-400 transition-colors">bolt</span>Features</a>
                <a href="#movies" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer shadow-[0_0_15px_rgba(0,0,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.05)] flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-indigo-400 transition-colors">theaters</span>Movies</a>
                <a href="learning.php" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer shadow-[0_0_15px_rgba(0,0,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.05)] flex items-center gap-2 group"><span class="material-symbols-outlined text-[18px] group-hover:text-emerald-400 transition-colors">integration_instructions</span>How it works</a>
                <div class="h-px w-full bg-white/10 my-2"></div>
                <a href="user/dashboard.php" class="text-lg font-medium text-white/80 hover:text-red-400 transition-colors cursor-pointer">Sign In</a>
                <a href="user/dashboard.php" class="text-lg font-bold text-red-500 hover:text-red-400 transition-colors cursor-pointer">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center pt-20 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center opacity-100 -z-20" style="background-image: url('4181333.jpg');"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-[#030305]/40 via-[#030305]/70 to-[#030305] -z-10"></div>
        
        
        <!-- Abstract glowing orbs -->
        <div class="absolute top-1/4 right-1/4 w-[500px] h-[500px] bg-red-600/20 rounded-full blur-[120px] -z-10 animate-pulse pointer-events-none"></div>
        <div class="absolute bottom-1/4 left-1/4 w-[400px] h-[400px] bg-indigo-600/20 rounded-full blur-[100px] -z-10 pointer-events-none" style="animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite reverse;"></div>

        <div class="max-w-7xl mx-auto px-6 w-full relative z-10 grid lg:grid-cols-2 gap-12 items-center">
            <div class="gs-hero-content">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 mb-6 gs-reveal">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    <span class="text-xs font-bold tracking-widest text-white/70 mono uppercase">Nexus Protocol Active</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-bold tracking-tighter leading-[1.1] mb-6 hero-text">
                    <span class="inline-block gs-word">Watch</span> <span class="inline-block gs-word">Movies</span> <br>
                    <span class="inline-block gs-word"><span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-indigo-500 text-glow">Together.</span></span><br>
                    <span class="inline-block gs-word">Anywhere.</span>
                </h1>
                
                <p class="text-lg text-white/60 mb-10 max-w-xl gs-reveal font-light leading-relaxed">
                    Create a watch room, invite your friends, and enjoy synchronized streaming with real-time video chat, messaging, and zero latency.
                </p>
                
                <div class="flex flex-wrap items-center gap-4 gs-reveal">
                    <a href="user/dashboard.php" class="animated-border px-8 py-4 font-bold text-white flex items-center gap-3 group cursor-pointer">
                        Launch Nexus
                        <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">rocket_launch</span>
                    </a>
                    <a href="#features" class="px-8 py-4 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 font-medium text-white transition-all duration-300 flex items-center gap-2 group cursor-pointer">
                        <span class="material-symbols-outlined group-hover:rotate-45 transition-transform duration-300 text-red-400">explore</span>
                        Explore Features
                    </a>
                </div>

                <div class="mt-12 flex items-center gap-6 gs-reveal">
                    <div class="flex -space-x-3">
                        <img src="https://ui-avatars.com/api/?name=U1&background=random&color=fff" class="w-10 h-10 rounded-full border-2 border-[#030305]">
                        <img src="https://ui-avatars.com/api/?name=U2&background=random&color=fff" class="w-10 h-10 rounded-full border-2 border-[#030305]">
                        <img src="https://ui-avatars.com/api/?name=U3&background=random&color=fff" class="w-10 h-10 rounded-full border-2 border-[#030305]">
                        <div class="w-10 h-10 rounded-full border-2 border-[#030305] bg-white/10 flex items-center justify-center text-xs font-bold">+2k</div>
                    </div>
                    <div class="text-sm text-white/50 mono">Active users right now</div>
                </div>
            </div>
            
            <div class="relative lg:h-[600px] flex items-center justify-center gs-hero-visual">
                <!-- Floating UI Cards -->
                
                <div class="relative z-10 w-full max-w-md floating-element" style="animation-delay: -1s;">
                    <div class="glass-card rounded-2xl p-4 shadow-[0_20px_50px_rgba(239,68,68,0.2)]">
                        <div class="w-full aspect-video rounded-xl overflow-hidden relative mb-4">
                            <img src="https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=600" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-2xl ml-1">play_arrow</span>
                                </div>
                            </div>
                            <div class="absolute bottom-2 right-2 bg-black/60 backdrop-blur px-2 py-1 rounded text-[10px] mono text-red-400 border border-red-500/20">LIVE</div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Host&background=ef4444&color=fff" class="w-10 h-10 rounded-full">
                                <div>
                                    <h4 class="text-sm font-bold">Dune: Part Two Watch Party</h4>
                                    <p class="text-[10px] text-white/50 mono">Hosted by Alex</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded border border-emerald-400/20">
                                <span class="material-symbols-outlined text-[14px]">graphic_eq</span>
                                <span class="text-[10px] font-bold">SYNCED</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-4 left-2 sm:left-4 md:bottom-8 md:-left-4 w-[240px] md:w-64 floating-element z-20" style="animation-delay: -3s;">
                    <div class="glass-card rounded-2xl p-3 md:p-4 bg-black/80 backdrop-blur-xl border border-white/10 shadow-2xl">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="material-symbols-outlined text-indigo-400">forum</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-white/70">Room Chat</span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex gap-2">
                                <img src="https://ui-avatars.com/api/?name=A&background=random" class="w-6 h-6 rounded-full">
                                <div class="bg-white/5 rounded-lg rounded-tl-none p-2 text-[11px] text-white/80">This scene is incredible! 🤯</div>
                            </div>
                            <div class="flex gap-2">
                                <img src="https://ui-avatars.com/api/?name=B&background=random" class="w-6 h-6 rounded-full">
                                <div class="bg-indigo-500/20 border border-indigo-500/30 rounded-lg rounded-tl-none p-2 text-[11px] text-white/90">The visual effects are insane.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 gs-reveal-up">
                <h2 class="text-sm font-bold text-red-500 tracking-widest uppercase mb-2 mono">Protocol Capabilities</h2>
                <h3 class="text-3xl md:text-5xl font-bold tracking-tight">Next-Gen Viewing Experience</h3>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="glass-card rounded-3xl p-8 gs-reveal-up group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500/20 to-red-600/5 border border-red-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                        <span class="material-symbols-outlined text-3xl text-red-400">sync</span>
                    </div>
                    <h4 class="text-xl font-bold mb-3 tracking-wide">Perfect Sync</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Advanced playback synchronization ensures everyone sees the exact same frame at the exact same time, down to the millisecond.</p>
                </div>
                <!-- Feature 2 -->
                <div class="glass-card rounded-3xl p-8 gs-reveal-up group" style="transition-delay: 0.1s;">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-indigo-600/5 border border-indigo-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                        <span class="material-symbols-outlined text-3xl text-indigo-400">record_voice_over</span>
                    </div>
                    <h4 class="text-xl font-bold mb-3 tracking-wide">Spatial Audio & Video</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Integrated high-fidelity voice and video chat that automatically ducks when characters are speaking so you never miss a line.</p>
                </div>
                <!-- Feature 3 -->
                <div class="glass-card rounded-3xl p-8 gs-reveal-up group" style="transition-delay: 0.2s;">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/5 border border-emerald-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                        <span class="material-symbols-outlined text-3xl text-emerald-400">security</span>
                    </div>
                    <h4 class="text-xl font-bold mb-3 tracking-wide">Secure Rooms</h4>
                    <p class="text-sm text-white/50 leading-relaxed">End-to-end encrypted watch parties with granular host controls, invite-only links, and instant moderation capabilities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Section -->
    <section id="movies" class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-red-900/5 transform -skew-y-2"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex items-end justify-between mb-12 gs-reveal-up">
                <div>
                    <h2 class="text-sm font-bold text-red-500 tracking-widest uppercase mb-2 mono">Trending Now</h2>
                    <h3 class="text-3xl md:text-4xl font-bold tracking-tight">Popular Watch Parties</h3>
                </div>
                <a href="#" class="text-sm text-white/50 hover:text-white font-medium flex items-center gap-1 transition-colors cursor-pointer">
                    View All <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <!-- Movie Cards -->
                <template x-data="{ 
                    movies: [
                        { title: 'Interstellar', img: 'https://image.tmdb.org/t/p/w500/qNBAXBIQlnOThrVvA6mA2B5ggV6.jpg', genre: 'Sci-Fi' },
                        { title: 'The Batman', img: 'https://image.tmdb.org/t/p/w500/8UlWHLMpgZm9bx6QYh0NFoq67TZ.jpg', genre: 'Action' },
                        { title: 'Avengers', img: 'https://image.tmdb.org/t/p/w500/74xTEgt7R36Fpooo50r9T25onhq.jpg', genre: 'Action' },
                        { title: 'Joker', img: 'https://image.tmdb.org/t/p/w500/9Gtg2DzBhmYamXBS1hKAhiwbBKS.jpg', genre: 'Drama' }
                    ]
                }" x-for="(movie, i) in movies" :key="i">
                    <div class="glass-card rounded-2xl overflow-hidden group cursor-pointer gs-movie-card">
                        <div class="aspect-[2/3] relative overflow-hidden">
                            <img :src="movie.img" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-80"></div>
                            
                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-sm">
                                <div class="w-14 h-14 rounded-full bg-red-500 text-white flex items-center justify-center transform scale-50 group-hover:scale-100 transition-transform duration-500 delay-100">
                                    <span class="material-symbols-outlined text-3xl ml-1">play_arrow</span>
                                </div>
                            </div>

                            <div class="absolute bottom-4 left-4 right-4 z-10 pointer-events-none">
                                <span class="text-[10px] font-bold text-red-400 tracking-widest uppercase border border-red-500/30 px-2 py-0.5 rounded bg-red-500/10 mb-2 inline-block" x-text="movie.genre"></span>
                                <h4 class="font-bold text-lg leading-tight tracking-tight text-white" x-text="movie.title"></h4>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-24 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 gs-reveal-up">
                <h2 class="text-sm font-bold text-indigo-500 tracking-widest uppercase mb-2 mono">Deployment Sequence</h2>
                <h3 class="text-3xl md:text-5xl font-bold tracking-tight">How It Works</h3>
            </div>

            <div class="grid md:grid-cols-4 gap-6 relative">
                <!-- Connecting Line (Desktop) -->
                <div class="hidden md:block absolute top-1/2 left-[10%] right-[10%] h-[1px] bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-y-1/2 z-0"></div>

                <!-- Steps -->
                <div class="gs-step relative z-10">
                    <div class="glass-card rounded-3xl p-6 text-center h-full group bg-[#030305]">
                        <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mx-auto flex items-center justify-center mb-6 group-hover:bg-red-500/10 group-hover:border-red-500/30 transition-colors duration-300">
                            <span class="text-2xl font-bold font-mono text-white/50 group-hover:text-red-400 transition-colors">01</span>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Connect</h4>
                        <p class="text-xs text-white/50 leading-relaxed">Sign in to the Nexus protocol network instantly.</p>
                    </div>
                </div>
                
                <div class="gs-step relative z-10" style="transition-delay: 0.1s;">
                    <div class="glass-card rounded-3xl p-6 text-center h-full group bg-[#030305]">
                        <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mx-auto flex items-center justify-center mb-6 group-hover:bg-indigo-500/10 group-hover:border-indigo-500/30 transition-colors duration-300">
                            <span class="text-2xl font-bold font-mono text-white/50 group-hover:text-indigo-400 transition-colors">02</span>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Select Target</h4>
                        <p class="text-xs text-white/50 leading-relaxed">Choose your media source from the vast library.</p>
                    </div>
                </div>

                <div class="gs-step relative z-10" style="transition-delay: 0.2s;">
                    <div class="glass-card rounded-3xl p-6 text-center h-full group bg-[#030305]">
                        <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mx-auto flex items-center justify-center mb-6 group-hover:bg-emerald-500/10 group-hover:border-emerald-500/30 transition-colors duration-300">
                            <span class="text-2xl font-bold font-mono text-white/50 group-hover:text-emerald-400 transition-colors">03</span>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Establish Uplink</h4>
                        <p class="text-xs text-white/50 leading-relaxed">Generate a secure room and send invites to friends.</p>
                    </div>
                </div>

                <div class="gs-step relative z-10" style="transition-delay: 0.3s;">
                    <div class="glass-card rounded-3xl p-6 text-center h-full group bg-[#030305]">
                        <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mx-auto flex items-center justify-center mb-6 group-hover:bg-white/20 group-hover:border-white/40 transition-colors duration-300">
                            <span class="text-2xl font-bold font-mono text-white/50 group-hover:text-white transition-colors">04</span>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Engage</h4>
                        <p class="text-xs text-white/50 leading-relaxed">Experience synchronized viewing with live comms.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    
    <footer class="border-t border-white/10 bg-[#030305] pt-16 pb-8 relative overflow-hidden">
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full h-px bg-gradient-to-r from-transparent via-red-500 to-transparent opacity-20"></div>
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12">
                <div class="flex items-center gap-3 cursor-pointer group">
                    <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-[18px]">movie</span>
                    </div>
                    <span class="text-lg font-bold tracking-tight group-hover:text-red-400 transition-colors">NEXUS</span>
                </div>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">Twitter</a>
                    <a href="#" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">Discord</a>
                    <a href="#" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">GitHub</a>
                </div>
            </div>
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 border-t border-white/5 pt-8">
                <p class="text-xs text-white/40 mono">© 2026 Nexus Protocol. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <a href="#" class="text-xs text-white/40 hover:text-white transition-colors cursor-pointer">Privacy Policy</a>
                    <a href="#" class="text-xs text-white/40 hover:text-white transition-colors cursor-pointer">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    
    
    

    </div>



    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
    
        <script src="/js/barba_setup.js?v=4"></script>

</body>
</html>