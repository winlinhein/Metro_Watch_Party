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
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrambleTextPlugin.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollToPlugin.min.js" crossorigin="anonymous"></script>
    <script>if (window.gsap) gsap.config({ nullTargetWarn: false });</script>

    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #030305;
            color: #ffffff;
            overflow-x: hidden;
            cursor: none;
        }

        .mono { font-family: 'JetBrains Mono', monospace; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
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
            z-index: 2;
        }

        .home-scanlines {
            pointer-events: none;
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                to bottom,
                transparent 0px,
                transparent 2px,
                rgba(0,0,0,0.12) 3px
            );
            opacity: 0.18;
            z-index: 2;
        }

        .glass-nav {
            background: rgba(3, 3, 5, 0.25);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid transparent;
            transition: background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }
        .glass-nav.is-scrolled {
            background: rgba(3, 3, 5, 0.78);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 12px 40px rgba(0,0,0,0.35);
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);
            border: 1px solid rgba(255,255,255,0.07);
            position: relative;
            overflow: hidden;
        }
        .glass-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.14), transparent);
        }

        .animated-border {
            position: relative;
            background: rgba(8,8,12,0.85);
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
            opacity: 0.7;
        }
        .animated-border:hover::before { opacity: 1; }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .text-glow {
            text-shadow: 0 0 30px rgba(239, 68, 68, 0.5);
        }

        .home-hero-title {
            overflow: hidden;
        }
        .home-char {
            display: inline-block;
            will-change: transform;
        }

        .home-featured-img {
            object-position: center center;
        }

        .home-cinema-frame {
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow:
                0 40px 80px -20px rgba(0,0,0,0.8),
                0 0 0 1px rgba(255,255,255,0.08),
                0 0 80px rgba(239,68,68,0.12);
            will-change: transform;
        }

        .home-play {
            will-change: transform;
        }
        .home-play-pulse {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            border: 1px solid rgba(239,68,68,0.55);
            will-change: transform, opacity;
        }

        [x-cloak] { display: none !important; }

        @keyframes homeMarquee {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }
        .home-marquee-track {
            animation: homeMarquee 45s linear infinite;
            will-change: transform;
        }
        .home-marquee:hover .home-marquee-track {
            animation-play-state: paused;
        }

        .home-row-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .home-row-scroll::-webkit-scrollbar { display: none; }

        .home-movie-card { will-change: transform; }
        .home-feature-card { will-change: transform; }

        .home-feature-visual {
            height: 220px;
            position: relative;
            overflow: hidden;
        }

        .home-protocol-marquee {
            font-size: clamp(4rem, 12vw, 10rem);
            font-weight: 700;
            letter-spacing: -0.06em;
            color: transparent;
            -webkit-text-stroke: 1px rgba(255,255,255,0.06);
            white-space: nowrap;
            user-select: none;
            will-change: transform;
        }

        .home-cta-band {
            background:
                radial-gradient(ellipse at 20% 50%, rgba(239,68,68,0.18), transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(79,70,229,0.16), transparent 50%),
                #030305;
        }

        .home-bento {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 1.1rem;
        }
        .home-bento-wide { grid-column: 1 / -1; }
        @media (max-width: 900px) {
            .home-bento { grid-template-columns: 1fr; }
        }

        .home-room-shell {
            background: linear-gradient(180deg, rgba(12,12,16,0.95), rgba(6,6,10,0.98));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1.4rem;
            overflow: hidden;
            box-shadow: 0 30px 80px -24px rgba(0,0,0,0.85), 0 0 0 1px rgba(255,255,255,0.04);
        }

        .home-sync-bar {
            height: 3px;
            background: linear-gradient(90deg, #ef4444, #4f46e5, #10b981);
            transform-origin: left center;
            will-change: transform;
        }

        .home-demo-step {
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.07);
            transition: border-color 0.3s ease, background 0.3s ease;
            will-change: transform;
        }
        .home-demo-step.is-active {
            border-color: rgba(239,68,68,0.45);
            background: linear-gradient(135deg, rgba(239,68,68,0.12), rgba(79,70,229,0.08));
        }

        .home-use-card { will-change: transform; }
        .home-bento-card { will-change: transform; }
        .home-plan-card { will-change: transform; }
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
        .home-plan-featured::before {
            background: linear-gradient(90deg, transparent, rgba(167,139,250,0.45), rgba(239,68,68,0.25), transparent);
        }

        .home-contact {
            position: relative;
            background:
                radial-gradient(ellipse at 12% 20%, rgba(239,68,68,0.16), transparent 42%),
                radial-gradient(ellipse at 88% 80%, rgba(79,70,229,0.18), transparent 46%),
                #030305;
        }
        .home-contact-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 78%);
        }
        .home-contact-card {
            background: rgba(8,8,12,0.72);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            box-shadow:
                0 40px 90px -30px rgba(0,0,0,0.9),
                inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .home-field {
            position: relative;
        }
        .home-field input,
        .home-field select,
        .home-field textarea {
            width: 100%;
            background: rgba(0,0,0,0.45);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.9rem;
            color: #fff;
            padding: 1.35rem 1rem 0.7rem;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
        }
        .home-field textarea { min-height: 132px; resize: none; }
        .home-field label {
            position: absolute;
            left: 1rem;
            top: 1.05rem;
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.38);
            pointer-events: none;
            transition: transform 0.28s cubic-bezier(0.4,0,0.2,1), color 0.28s ease;
            font-family: 'JetBrains Mono', monospace;
        }
        .home-field input:focus,
        .home-field select:focus,
        .home-field textarea:focus {
            border-color: rgba(239,68,68,0.55);
            background: rgba(0,0,0,0.65);
            box-shadow: 0 0 0 4px rgba(239,68,68,0.12), 0 0 28px rgba(79,70,229,0.12);
        }
        .home-field input:focus ~ label,
        .home-field select:focus ~ label,
        .home-field textarea:focus ~ label,
        .home-field input:not(:placeholder-shown) ~ label,
        .home-field select:not([value=""]) ~ label,
        .home-field textarea:not(:placeholder-shown) ~ label,
        .home-field.is-filled label {
            transform: translateY(-0.7rem) scale(0.86);
            color: #f87171;
        }
        .home-field input::placeholder,
        .home-field textarea::placeholder { color: transparent; }
        .home-honeypot {
            position: absolute;
            left: -9999px;
            opacity: 0;
            height: 0;
            width: 0;
            overflow: hidden;
        }
        .home-success {
            background: radial-gradient(circle at 50% 30%, rgba(16,185,129,0.18), transparent 55%);
        }
        .home-submit-shine {
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,0.18) 50%, transparent 80%);
            transform: translateX(-120%);
            pointer-events: none;
        }

        .hide-mobile-cursor body { cursor: none; }

        @media (prefers-reduced-motion: reduce) {
            .animated-border::before,
            .home-protocol-marquee,
            .home-play-pulse,
            .home-marquee-track {
                animation: none !important;
            }
        }
    </style>

    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js" crossorigin="anonymous"></script>
    <script src="/js/nexus_scripts.js?v=1788150001"></script>
</head>
<body data-barba="wrapper">
    <?php include __DIR__ . '/frontend/components/page_loader.php'; ?>
    <?php include __DIR__ . '/frontend/components/cursor.php'; ?>
    <?php include __DIR__ . '/frontend/components/toast.php'; ?>
<div id="barba-container" data-barba="container" data-barba-namespace="index" x-data="nexusHome()">

    <div class="bg-mesh"></div>
    <div class="noise"></div>

    <!-- Navigation -->
    <nav class="home-nav fixed top-0 left-0 right-0 z-50 glass-nav" :class="navScrolled && 'is-scrolled'">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group cursor-pointer">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.4)] group-hover:scale-110 transition-transform duration-500">
                    <span class="material-symbols-outlined text-white">movie</span>
                </div>
                <span class="text-xl font-bold tracking-tight group-hover:text-red-400 transition-colors">NEXUS</span>
            </a>

            <div class="hidden md:flex items-center gap-2">
                <a href="#features" class="text-sm font-bold text-white/70 hover:text-white px-4 py-2.5 rounded-xl hover:bg-white/5 transition-all duration-300 cursor-pointer flex items-center gap-2 group">
                    <span class="material-symbols-outlined text-[18px] group-hover:text-red-400 transition-colors">bolt</span>Features
                </a>
                <a href="#movies" class="text-sm font-bold text-white/70 hover:text-white px-4 py-2.5 rounded-xl hover:bg-white/5 transition-all duration-300 cursor-pointer flex items-center gap-2 group">
                    <span class="material-symbols-outlined text-[18px] group-hover:text-indigo-400 transition-colors">theaters</span>Movies
                </a>
                <a href="#how-it-works" class="text-sm font-bold text-white/70 hover:text-white px-4 py-2.5 rounded-xl hover:bg-white/5 transition-all duration-300 cursor-pointer flex items-center gap-2 group">
                    <span class="material-symbols-outlined text-[18px] group-hover:text-emerald-400 transition-colors">integration_instructions</span>How it works
                </a>
                <a href="#premium" class="text-sm font-bold text-white/70 hover:text-white px-4 py-2.5 rounded-xl hover:bg-white/5 transition-all duration-300 cursor-pointer flex items-center gap-2 group">
                    <span class="material-symbols-outlined text-[18px] group-hover:text-fuchsia-400 transition-colors">workspace_premium</span>Premium
                </a>
                <a href="#contact" class="text-sm font-bold text-white/70 hover:text-white px-4 py-2.5 rounded-xl hover:bg-white/5 transition-all duration-300 cursor-pointer flex items-center gap-2 group">
                    <span class="material-symbols-outlined text-[18px] group-hover:text-amber-400 transition-colors">mail</span>Contact
                </a>
            </div>

            <div class="hidden md:flex items-center gap-3">
                <a href="frontend/login.php" class="text-sm font-bold text-white/70 hover:text-white px-5 py-2.5 rounded-full hover:bg-white/5 border border-transparent hover:border-white/10 transition-all duration-300 cursor-pointer">Sign In</a>
                <a href="frontend/register.php" class="home-magnetic animated-border px-6 py-2.5 text-sm font-bold text-white flex items-center gap-2 cursor-pointer">
                    Get Started <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>

            <button class="md:hidden text-white/70 hover:text-white cursor-pointer" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Toggle menu">
                <span class="material-symbols-outlined text-3xl" x-text="mobileMenuOpen ? 'close' : 'menu'">menu</span>
            </button>
        </div>

        <div class="md:hidden absolute top-20 left-0 w-full glass-nav is-scrolled border-t border-white/5"
             x-show="mobileMenuOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4">
            <div class="p-6 flex flex-col gap-3">
                <a href="#features" @click="mobileMenuOpen = false" class="text-sm font-bold text-white/80 px-5 py-3 rounded-xl bg-white/5 border border-white/10">Features</a>
                <a href="#movies" @click="mobileMenuOpen = false" class="text-sm font-bold text-white/80 px-5 py-3 rounded-xl bg-white/5 border border-white/10">Movies</a>
                <a href="#how-it-works" @click="mobileMenuOpen = false" class="text-sm font-bold text-white/80 px-5 py-3 rounded-xl bg-white/5 border border-white/10">How it works</a>
                <a href="#premium" @click="mobileMenuOpen = false" class="text-sm font-bold text-white/80 px-5 py-3 rounded-xl bg-white/5 border border-white/10">Premium</a>
                <a href="#contact" @click="mobileMenuOpen = false" class="text-sm font-bold text-white/80 px-5 py-3 rounded-xl bg-white/5 border border-white/10">Contact</a>
                <div class="h-px w-full bg-white/10 my-1"></div>
                <a href="frontend/login.php" class="text-lg font-medium text-white/80">Sign In</a>
                <a href="frontend/register.php" class="text-lg font-bold text-red-500">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero: HBO Max / Netflix cinematic + Apple TV+ rounded frame -->
    <section class="home-hero relative min-h-screen flex items-end md:items-center pt-24 pb-28 overflow-hidden">
        <div class="absolute inset-0 -z-20 overflow-hidden">
            <img class="home-hero-bg-img home-featured-img absolute inset-0 w-full h-full object-cover scale-110"
                 :src="currentRoom.img"
                 src="/frontend/assets/home/dune-live.jpg"
                 :alt="currentRoom.title"
                 alt="Dune">
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#030305] via-[#030305]/75 to-[#030305]/30 -z-10"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#030305] via-[#030305]/40 to-[#030305]/30 -z-10"></div>
        <div class="home-scanlines -z-10"></div>
        <div class="absolute top-1/4 right-1/4 w-[480px] h-[480px] bg-red-600/15 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-1/4 left-1/5 w-[360px] h-[360px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 w-full relative z-10 grid lg:grid-cols-12 gap-10 items-center">
            <div class="home-hero-copy lg:col-span-6">
                <div class="home-live-badge inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 mb-6">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    <span class="home-live-text text-xs font-bold tracking-widest text-white/70 mono uppercase">Nexus Protocol Active</span>
                </div>

                <h1 class="home-hero-title text-5xl md:text-7xl font-bold tracking-tighter leading-[1.08] mb-6">
                    <span class="home-hero-line block overflow-hidden">
                        <span class="gs-word inline-block">Watch</span>
                        <span class="gs-word inline-block">together.</span>
                    </span>
                    <span class="home-hero-accent gs-word inline-block text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-indigo-500 text-glow">Stay in sync.</span>
                </h1>

                <p class="home-hero-lead text-lg text-white/60 mb-8 max-w-xl font-light leading-relaxed">
                    Create a room, invite your people, and stream the same frame at the same millisecond — with live chat, video, and zero lag.
                </p>

                <div class="home-hero-actions flex flex-wrap items-center gap-4">
                    <a href="backend/guest_login.php" class="home-magnetic animated-border px-8 py-4 font-bold text-white flex items-center gap-3 group cursor-pointer">
                        <span class="material-symbols-outlined">play_arrow</span>
                        Launch Nexus
                    </a>
                    <a href="#features" class="home-magnetic px-8 py-4 rounded-full bg-white/10 hover:bg-white/15 border border-white/15 font-medium text-white transition-colors duration-300 flex items-center gap-2 cursor-pointer backdrop-blur-md">
                        <span class="material-symbols-outlined text-red-400">info</span>
                        More Info
                    </a>
                </div>

                <div class="home-social-proof mt-10 flex items-center gap-5">
                    <div class="flex -space-x-3">
                        <img src="https://ui-avatars.com/api/?name=U1&background=ef4444&color=fff" alt="" class="w-10 h-10 rounded-full border-2 border-[#030305]">
                        <img src="https://ui-avatars.com/api/?name=U2&background=4f46e5&color=fff" alt="" class="w-10 h-10 rounded-full border-2 border-[#030305]">
                        <img src="https://ui-avatars.com/api/?name=U3&background=10b981&color=fff" alt="" class="w-10 h-10 rounded-full border-2 border-[#030305]">
                        <div class="w-10 h-10 rounded-full border-2 border-[#030305] bg-white/10 flex items-center justify-center text-xs font-bold">+2k</div>
                    </div>
                    <div>
                        <div class="text-sm text-white/80 font-medium">Live rooms right now</div>
                        <div class="text-xs text-white/40 mono" x-text="currentRoom.viewers + ' in ' + currentRoom.title">24 in Dune</div>
                    </div>
                </div>
            </div>

            <div class="home-visual relative lg:col-span-6 flex items-center justify-center min-h-[420px]">
                <div class="relative w-full max-w-md z-10">
                    <div class="home-cinema-frame relative">
                        <div class="w-full aspect-video relative">
                            <img class="home-featured-img w-full h-full object-cover"
                                 :src="currentRoom.img"
                                 src="/frontend/assets/home/dune-live.jpg"
                                 :alt="currentRoom.title"
                                 alt="Dune">
                            <div class="absolute inset-0 bg-black/35"></div>
                            <div class="home-scanlines"></div>
                            <a href="backend/guest_login.php" class="home-play absolute inset-0 flex items-center justify-center cursor-pointer group">
                                <span class="relative w-16 h-16 flex items-center justify-center">
                                    <span class="home-play-pulse"></span>
                                    <span class="home-play-pulse" style="animation-delay: 0.6s;"></span>
                                    <span class="w-16 h-16 rounded-full bg-red-500 text-white flex items-center justify-center shadow-[0_0_40px_rgba(239,68,68,0.55)] group-hover:scale-110 transition-transform">
                                        <span class="material-symbols-outlined text-3xl ml-0.5">play_arrow</span>
                                    </span>
                                </span>
                            </a>
                            <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between">
                                <div>
                                    <h4 class="home-featured-title font-bold text-lg leading-tight" x-text="currentRoom.title">Dune</h4>
                                    <p class="home-featured-host text-[11px] text-white/60 mono" x-text="'Hosted by ' + currentRoom.host">Hosted by Alex</p>
                                </div>
                                <div class="home-featured-tag px-2 py-1 rounded text-[10px] mono font-bold text-red-400 border border-red-500/30 bg-black/50" x-text="currentRoom.tag">LIVE</div>
                            </div>
                        </div>
                        <div class="p-4 bg-black/70 backdrop-blur-xl flex items-center justify-between border-t border-white/5">
                            <div class="flex items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Host&background=ef4444&color=fff" alt="" class="w-9 h-9 rounded-full">
                                <div>
                                    <p class="text-xs font-bold">Watch party</p>
                                    <p class="home-featured-viewers text-[10px] text-white/50 mono" x-text="currentRoom.viewers + ' watching'">24 watching</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded border border-emerald-400/20">
                                <span class="material-symbols-outlined text-[14px]">graphic_eq</span>
                                <span class="text-[10px] font-bold">SYNCED</span>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-4 -left-2 sm:-left-8 w-[230px] md:w-64 z-20 home-float-card">
                        <div class="glass-card rounded-2xl p-3 md:p-4 bg-black/80 backdrop-blur-xl">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-symbols-outlined text-indigo-400 text-[18px]">forum</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-white/70 mono">Room Chat</span>
                            </div>
                            <div class="space-y-2">
                                <div class="home-chat-line flex gap-2">
                                    <img src="https://ui-avatars.com/api/?name=A&background=4f46e5&color=fff" alt="" class="w-6 h-6 rounded-full">
                                    <div class="bg-white/5 rounded-lg rounded-tl-none p-2 text-[11px] text-white/80">This scene is insane.</div>
                                </div>
                                <div class="home-chat-line flex gap-2">
                                    <img src="https://ui-avatars.com/api/?name=B&background=ef4444&color=fff" alt="" class="w-6 h-6 rounded-full">
                                    <div class="bg-indigo-500/20 border border-indigo-500/30 rounded-lg rounded-tl-none p-2 text-[11px] text-white/90">Wait for the drop.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute bottom-8 left-0 right-0 z-10 flex justify-center gap-2">
            <template x-for="(room, i) in featuredRooms" :key="room.title">
                <button type="button"
                        class="h-1.5 rounded-full transition-all duration-500 cursor-pointer"
                        :class="featuredIndex === i ? 'w-8 bg-red-500' : 'w-3 bg-white/25 hover:bg-white/50'"
                        @click="setFeatured(i)"
                        :aria-label="'Show ' + room.title"></button>
            </template>
        </div>
    </section>

    <!-- Poster marquee: HBO Max vertical panels / Netflix poster wall -->
    <section class="home-marquee relative -mt-8 mb-8 overflow-hidden" aria-hidden="true">
        <div class="pointer-events-none absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-[#030305] to-transparent z-10"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-[#030305] to-transparent z-10"></div>
        <div class="home-marquee-track flex gap-4 w-max py-2">
            <template x-for="(movie, i) in marqueeMovies" :key="'m'+i+movie.title">
                <div class="w-28 md:w-36 aspect-[2/3] rounded-xl overflow-hidden border border-white/10 shrink-0">
                    <img :src="movie.img" :alt="movie.title" class="w-full h-full object-cover" loading="lazy">
                </div>
            </template>
        </div>
    </section>

    <!-- Stats strip -->
    <section class="relative py-8 border-y border-white/5">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-3 gap-4 text-center">
            <div class="home-reveal">
                <div class="text-2xl md:text-4xl font-bold tracking-tight"><span data-count="2400">0</span>+</div>
                <div class="text-[10px] md:text-xs text-white/40 mono uppercase tracking-widest mt-1">Watching now</div>
            </div>
            <div class="home-reveal">
                <div class="text-2xl md:text-4xl font-bold tracking-tight"><span data-count="128">0</span></div>
                <div class="text-[10px] md:text-xs text-white/40 mono uppercase tracking-widest mt-1">Live rooms</div>
            </div>
            <div class="home-reveal">
                <div class="text-2xl md:text-4xl font-bold tracking-tight">0ms</div>
                <div class="text-[10px] md:text-xs text-white/40 mono uppercase tracking-widest mt-1">Sync target</div>
            </div>
        </div>
    </section>

    <!-- Features: TIDAL-style visual cards -->
    <section id="features" class="py-28 relative overflow-hidden">
        <div class="home-protocol-marquee absolute top-8 left-0 opacity-80 pointer-events-none">
            PROTOCOL&nbsp;&nbsp;CAPABILITIES&nbsp;&nbsp;PROTOCOL&nbsp;&nbsp;CAPABILITIES
        </div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="home-reveal mb-14 max-w-2xl">
                <h2 class="text-sm font-bold text-red-500 tracking-widest uppercase mb-3 mono">Protocol Capabilities</h2>
                <h3 class="text-3xl md:text-5xl font-bold tracking-tight">Built for watching together</h3>
            </div>

            <div class="grid md:grid-cols-3 gap-5">
                <article class="home-feature-card glass-card rounded-3xl flex flex-col group">
                    <div class="home-feature-visual bg-gradient-to-br from-red-600/40 via-red-950/40 to-black">
                        <div class="absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 30% 40%, rgba(239,68,68,0.5), transparent 55%);"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-7xl text-red-300/90">sync</span>
                        </div>
                        <span class="absolute bottom-4 left-5 text-[10px] mono tracking-[0.25em] text-red-200/80">SYNC ENGINE</span>
                    </div>
                    <div class="p-7 flex flex-col flex-1">
                        <h4 class="text-xl font-bold mb-3">Perfect Sync</h4>
                        <p class="text-sm text-white/50 leading-relaxed mb-6 flex-1">Everyone sees the same frame at the same time — millisecond playback lock across the room.</p>
                        <a href="#how-it-works" class="text-sm font-bold text-white/80 hover:text-red-400 transition-colors cursor-pointer">Learn more →</a>
                    </div>
                </article>

                <article class="home-feature-card glass-card rounded-3xl flex flex-col group">
                    <div class="home-feature-visual bg-gradient-to-br from-indigo-600/40 via-indigo-950/50 to-black">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-7xl text-indigo-300/90">record_voice_over</span>
                        </div>
                        <span class="absolute bottom-4 left-5 text-[10px] mono tracking-[0.25em] text-indigo-200/80">SPATIAL COMMS</span>
                    </div>
                    <div class="p-7 flex flex-col flex-1">
                        <h4 class="text-xl font-bold mb-3">Spatial Audio & Video</h4>
                        <p class="text-sm text-white/50 leading-relaxed mb-6 flex-1">Voice and video that duck when dialogue hits, so you never miss a line or a reaction.</p>
                        <a href="#how-it-works" class="text-sm font-bold text-white/80 hover:text-indigo-400 transition-colors cursor-pointer">Learn more →</a>
                    </div>
                </article>

                <article class="home-feature-card glass-card rounded-3xl flex flex-col group">
                    <div class="home-feature-visual bg-gradient-to-br from-emerald-600/30 via-emerald-950/50 to-black">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-7xl text-emerald-300/90">security</span>
                        </div>
                        <span class="absolute bottom-4 left-5 text-[10px] mono tracking-[0.25em] text-emerald-200/80">SECURE UPLINK</span>
                    </div>
                    <div class="p-7 flex flex-col flex-1">
                        <h4 class="text-xl font-bold mb-3">Secure Rooms</h4>
                        <p class="text-sm text-white/50 leading-relaxed mb-6 flex-1">Invite-only links, host controls, and instant moderation. Your party stays yours.</p>
                        <a href="frontend/register.php" class="text-sm font-bold text-white/80 hover:text-emerald-400 transition-colors cursor-pointer">Get started →</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Trending: Netflix-style horizontal row -->
    <section id="movies" class="py-20 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="home-reveal flex items-end justify-between mb-8 gap-4">
                <div>
                    <h2 class="text-sm font-bold text-red-500 tracking-widest uppercase mb-2 mono">Trending Now</h2>
                    <h3 class="text-3xl md:text-4xl font-bold tracking-tight">Popular Watch Parties</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="w-10 h-10 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 flex items-center justify-center cursor-pointer" @click="scrollRow(-1)" aria-label="Previous">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <button type="button" class="w-10 h-10 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 flex items-center justify-center cursor-pointer" @click="scrollRow(1)" aria-label="Next">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6">
            <div x-ref="movieRow" class="home-row-scroll flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory">
                <template x-for="(movie, i) in movies" :key="movie.title + i">
                    <a href="backend/guest_login.php"
                       class="home-movie-card glass-card rounded-2xl overflow-hidden group cursor-pointer snap-start shrink-0 w-[42vw] sm:w-44 md:w-52"
                       @mouseenter="hoverMovie($el, true)"
                       @mouseleave="hoverMovie($el, false)">
                        <div class="aspect-[2/3] relative overflow-hidden">
                            <img :src="movie.img" :alt="movie.title" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <span class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-2xl ml-0.5">play_arrow</span>
                                </span>
                            </div>
                            <div class="absolute top-3 left-3 text-[9px] font-bold tracking-widest uppercase bg-red-600 px-1.5 py-0.5 rounded">NEXUS</div>
                            <div class="absolute bottom-3 left-3 right-3">
                                <span class="text-[10px] font-bold text-red-400 tracking-widest uppercase" x-text="movie.genre">Sci-Fi</span>
                                <h4 class="font-bold text-base leading-tight" x-text="movie.title"></h4>
                                <p class="text-[10px] text-white/50 mono mt-0.5" x-text="(movie.viewers || 12) + ' in room'">12 in room</p>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </section>

    <!-- Product showcase: inside a room -->
    <section id="showcase" class="py-24 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="home-reveal mb-12 max-w-3xl">
                <h2 class="text-sm font-bold text-red-500 tracking-widest uppercase mb-3 mono">Inside a room</h2>
                <h3 class="text-3xl md:text-5xl font-bold tracking-tight mb-4">The same theatre. Different couches.</h3>
                <p class="text-white/50 text-lg leading-relaxed">A Nexus room is a shared player, a live chat, and a lock on the same millisecond. Hosts control play, guests react, and nobody waits for “wait, where are you?”</p>
            </div>

            <div class="home-room-shell home-bento-card relative">
                <div class="flex items-center justify-between px-4 md:px-5 py-3 border-b border-white/8 bg-black/40">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        <span class="text-xs font-bold tracking-widest mono text-white/70">NEXUS ROOM · LIVE</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline text-[10px] mono text-white/40" x-text="currentRoom.title">Dune</span>
                        <span class="text-[10px] font-bold text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 px-2 py-1 rounded">SYNCED</span>
                    </div>
                </div>
                <div class="grid lg:grid-cols-12">
                    <div class="lg:col-span-8 relative">
                        <div class="aspect-video relative overflow-hidden">
                            <img class="home-featured-img w-full h-full object-cover" :src="currentRoom.img" :alt="currentRoom.title" alt="Dune">
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="w-14 h-14 rounded-full bg-red-500/90 flex items-center justify-center shadow-[0_0_40px_rgba(239,68,68,0.5)]">
                                    <span class="material-symbols-outlined text-3xl ml-0.5">play_arrow</span>
                                </span>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 p-4">
                                <div class="h-1.5 rounded-full bg-white/15 overflow-hidden mb-3">
                                    <div class="home-sync-bar h-full w-2/5 rounded-full"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] mono text-white/50">
                                    <span>01:14:22</span>
                                    <span class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-[16px]">volume_up</span>
                                        <span class="material-symbols-outlined text-[16px]">fullscreen</span>
                                    </span>
                                    <span>02:46:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-4 border-t lg:border-t-0 lg:border-l border-white/8 bg-black/50 p-4 flex flex-col min-h-[280px]">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-indigo-400 text-[18px]">forum</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-white/70 mono">Room Chat</span>
                            <span class="ml-auto text-[10px] text-white/30 mono" x-text="currentRoom.viewers + ' watching'">24 watching</span>
                        </div>
                        <div class="space-y-3 flex-1">
                            <div class="home-chat-line flex gap-2">
                                <img src="https://ui-avatars.com/api/?name=Alex&background=ef4444&color=fff" alt="" class="w-7 h-7 rounded-full">
                                <div>
                                    <p class="text-[10px] text-red-400 font-bold">Alex · host</p>
                                    <p class="text-xs text-white/70 bg-white/5 rounded-xl rounded-tl-none px-3 py-2 mt-1">Everyone locked. Hitting play in 3…</p>
                                </div>
                            </div>
                            <div class="home-chat-line flex gap-2">
                                <img src="https://ui-avatars.com/api/?name=Maya&background=4f46e5&color=fff" alt="" class="w-7 h-7 rounded-full">
                                <div>
                                    <p class="text-[10px] text-indigo-300 font-bold">Maya</p>
                                    <p class="text-xs text-white/70 bg-white/5 rounded-xl rounded-tl-none px-3 py-2 mt-1">I am at the same frame. Let’s go.</p>
                                </div>
                            </div>
                            <div class="home-chat-line flex gap-2">
                                <img src="https://ui-avatars.com/api/?name=Ken&background=10b981&color=fff" alt="" class="w-7 h-7 rounded-full">
                                <div>
                                    <p class="text-[10px] text-emerald-300 font-bold">Kenji</p>
                                    <p class="text-xs text-white/70 bg-indigo-500/15 border border-indigo-500/20 rounded-xl rounded-tl-none px-3 py-2 mt-1">Wait for the drop — 01:14:30.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-3 py-2">
                            <span class="text-xs text-white/30">Say something…</span>
                            <span class="material-symbols-outlined text-[16px] text-white/30 ml-auto">send</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bento capabilities -->
    <section class="pb-16 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="home-reveal mb-10">
                <h2 class="text-sm font-bold text-indigo-400 tracking-widest uppercase mb-2 mono">What you actually get</h2>
                <h3 class="text-3xl md:text-4xl font-bold tracking-tight">Every surface of a night in.</h3>
            </div>
            <div class="home-bento">
                <article class="home-bento-card glass-card rounded-3xl p-6 md:p-8">
                    <span class="text-[10px] mono tracking-[0.2em] text-red-400">01 · SYNC ENGINE</span>
                    <h4 class="text-2xl font-bold mt-3 mb-3">Millisecond lock</h4>
                    <p class="text-sm text-white/50 leading-relaxed mb-6">Pause, seek, or reconnect and the room catches up. Playback is a shared timeline — not four people guessing.</p>
                    <div class="rounded-2xl bg-black/50 border border-white/8 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] mono text-white/40">OFFSET</span>
                            <span class="text-[10px] mono text-emerald-400">±0ms</span>
                        </div>
                        <div class="h-10 flex items-end gap-1">
                            <span class="flex-1 bg-red-500/70 rounded-sm h-3"></span>
                            <span class="flex-1 bg-red-500/80 rounded-sm h-6"></span>
                            <span class="flex-1 bg-indigo-500/80 rounded-sm h-8"></span>
                            <span class="flex-1 bg-indigo-400 rounded-sm h-10"></span>
                            <span class="flex-1 bg-emerald-400 rounded-sm h-7"></span>
                            <span class="flex-1 bg-emerald-500/80 rounded-sm h-5"></span>
                            <span class="flex-1 bg-white/20 rounded-sm h-4"></span>
                        </div>
                    </div>
                </article>
                <article class="home-bento-card glass-card rounded-3xl p-6 md:p-8">
                    <span class="text-[10px] mono tracking-[0.2em] text-indigo-400">02 · UPLINK</span>
                    <h4 class="text-2xl font-bold mt-3 mb-3">Invite in one link</h4>
                    <p class="text-sm text-white/50 leading-relaxed mb-6">Share a private room URL. Friends join as guests or signed-in members. Hosts can rotate the invite any time.</p>
                    <div class="rounded-2xl bg-black/50 border border-white/8 px-4 py-3 flex items-center gap-3">
                        <span class="material-symbols-outlined text-indigo-400">link</span>
                        <span class="text-xs mono text-white/60 truncate">nexus.app/room/arrakis-204</span>
                        <span class="ml-auto text-[10px] font-bold text-white/80 bg-white/10 px-2 py-1 rounded">COPY</span>
                    </div>
                </article>
                <article class="home-bento-card glass-card rounded-3xl p-6">
                    <span class="text-[10px] mono tracking-[0.2em] text-emerald-400">03 · HOST</span>
                    <h4 class="text-xl font-bold mt-3 mb-2">Host controls</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Mute, kick, and lock play so the night stays yours.</p>
                </article>
                <article class="home-bento-card glass-card rounded-3xl p-6">
                    <span class="text-[10px] mono tracking-[0.2em] text-amber-400">04 · LIBRARY</span>
                    <h4 class="text-xl font-bold mt-3 mb-2">Watchlist &amp; catalog</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Save titles, pick from the library, and launch a room around them.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- How it works: interactive sequence -->
    <section id="how-it-works" class="home-steps py-28 relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="home-reveal mb-12 md:flex md:items-end md:justify-between gap-8">
                <div>
                    <h2 class="text-sm font-bold text-indigo-400 tracking-widest uppercase mb-2 mono">Deployment Sequence</h2>
                    <h3 class="text-3xl md:text-5xl font-bold tracking-tight">Four steps to first light</h3>
                </div>
                <p class="text-white/45 max-w-md mt-4 md:mt-0">Click a step to preview the flow. This is the same path from guest drop-in to a locked, chatting room.</p>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-5 space-y-3 relative">
                    <div class="home-steps-line hidden lg:block absolute top-8 bottom-8 left-[27px] w-px bg-gradient-to-b from-red-500 via-indigo-500 to-emerald-400 origin-top z-0"></div>

                    <button type="button" class="home-step home-demo-step relative z-10 w-full text-left rounded-2xl p-5 bg-[#030305]" :class="demoStep === 1 && 'is-active'" @click="setDemoStep(1)">
                        <div class="flex gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0">
                                <span class="text-xl font-bold mono text-red-400">01</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Connect</h4>
                                <p class="text-sm text-white/50 leading-relaxed">Sign in, or drop in as a guest. The protocol boots instantly — no install, no plugin.</p>
                            </div>
                        </div>
                    </button>
                    <button type="button" class="home-step home-demo-step relative z-10 w-full text-left rounded-2xl p-5 bg-[#030305]" :class="demoStep === 2 && 'is-active'" @click="setDemoStep(2)">
                        <div class="flex gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0">
                                <span class="text-xl font-bold mono text-indigo-400">02</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Select</h4>
                                <p class="text-sm text-white/50 leading-relaxed">Pick a title from the library or your watchlist. That film becomes the room’s source.</p>
                            </div>
                        </div>
                    </button>
                    <button type="button" class="home-step home-demo-step relative z-10 w-full text-left rounded-2xl p-5 bg-[#030305]" :class="demoStep === 3 && 'is-active'" @click="setDemoStep(3)">
                        <div class="flex gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                <span class="text-xl font-bold mono text-emerald-400">03</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Uplink</h4>
                                <p class="text-sm text-white/50 leading-relaxed">Spin a private room and fire the invite to your crew. They land in the same lobby.</p>
                            </div>
                        </div>
                    </button>
                    <button type="button" class="home-step home-demo-step relative z-10 w-full text-left rounded-2xl p-5 bg-[#030305]" :class="demoStep === 4 && 'is-active'" @click="setDemoStep(4)">
                        <div class="flex gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/15 flex items-center justify-center shrink-0">
                                <span class="text-xl font-bold mono text-white">04</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Engage</h4>
                                <p class="text-sm text-white/50 leading-relaxed">Hit play. Chat, react, and stay locked on the same frame until the credits.</p>
                            </div>
                        </div>
                    </button>
                </div>

                <div class="lg:col-span-7">
                    <div class="home-room-shell home-demo-visual p-5 md:p-7 min-h-[340px]">
                        <div x-show="demoStep === 1" x-cloak class="home-demo-panel space-y-4">
                            <p class="text-[10px] mono tracking-[0.25em] text-red-400">GATE · AUTH</p>
                            <h4 class="text-2xl font-bold">Drop in as guest or sign in</h4>
                            <p class="text-sm text-white/50">Guests get a room seat immediately. Signed-in hosts unlock invite rotation, kick, and shop rewards.</p>
                            <div class="grid sm:grid-cols-2 gap-3 pt-2">
                                <a href="backend/guest_login.php" class="rounded-2xl border border-white/10 bg-white/5 p-4 hover:border-red-500/40 transition-colors">
                                    <span class="material-symbols-outlined text-red-400">bolt</span>
                                    <p class="font-bold mt-2">Guest uplink</p>
                                    <p class="text-xs text-white/40 mt-1">No account required</p>
                                </a>
                                <a href="frontend/register.php" class="rounded-2xl border border-white/10 bg-white/5 p-4 hover:border-indigo-500/40 transition-colors">
                                    <span class="material-symbols-outlined text-indigo-400">person_add</span>
                                    <p class="font-bold mt-2">Create identity</p>
                                    <p class="text-xs text-white/40 mt-1">Keep friends &amp; watchlist</p>
                                </a>
                            </div>
                        </div>
                        <div x-show="demoStep === 2" x-cloak class="home-demo-panel space-y-4">
                            <p class="text-[10px] mono tracking-[0.25em] text-indigo-400">LIBRARY · SELECT</p>
                            <h4 class="text-2xl font-bold">Choose the night’s title</h4>
                            <p class="text-sm text-white/50">Browse the catalog or a saved watchlist. The chosen film becomes the room’s playback source.</p>
                            <div class="flex gap-3 overflow-hidden pt-2">
                                <template x-for="movie in movies.slice(0,4)" :key="'demo'+movie.title">
                                    <div class="w-24 shrink-0 aspect-[2/3] rounded-xl overflow-hidden border border-white/10">
                                        <img :src="movie.img" :alt="movie.title" class="w-full h-full object-cover">
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div x-show="demoStep === 3" x-cloak class="home-demo-panel space-y-4">
                            <p class="text-[10px] mono tracking-[0.25em] text-emerald-400">ROOM · UPLINK</p>
                            <h4 class="text-2xl font-bold">Private lobby, public vibe</h4>
                            <p class="text-sm text-white/50">The room is invite-only. Copy the link, ping friends, and wait until the SYNCED badge lights up.</p>
                            <div class="flex -space-x-3 pt-2">
                                <img src="https://ui-avatars.com/api/?name=A&background=ef4444&color=fff" class="w-11 h-11 rounded-full border-2 border-[#0a0a0e]" alt="">
                                <img src="https://ui-avatars.com/api/?name=M&background=4f46e5&color=fff" class="w-11 h-11 rounded-full border-2 border-[#0a0a0e]" alt="">
                                <img src="https://ui-avatars.com/api/?name=K&background=10b981&color=fff" class="w-11 h-11 rounded-full border-2 border-[#0a0a0e]" alt="">
                                <div class="w-11 h-11 rounded-full border-2 border-[#0a0a0e] bg-white/10 flex items-center justify-center text-xs font-bold">+8</div>
                            </div>
                            <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">Invite sent · waiting for 2 more</div>
                        </div>
                        <div x-show="demoStep === 4" x-cloak class="home-demo-panel space-y-4">
                            <p class="text-[10px] mono tracking-[0.25em] text-white/50">PLAYBACK · ENGAGE</p>
                            <h4 class="text-2xl font-bold">One playhead. Many reactions.</h4>
                            <p class="text-sm text-white/50">The host hits play. Chat stays beside the frame. If someone lags, Nexus pulls them back onto the timeline.</p>
                            <div class="rounded-2xl border border-white/10 overflow-hidden">
                                <div class="aspect-video relative">
                                    <img class="home-featured-img w-full h-full object-cover" :src="currentRoom.img" :alt="currentRoom.title" alt="Dune">
                                    <div class="absolute inset-0 bg-black/35"></div>
                                    <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between">
                                        <span class="text-xs font-bold" x-text="currentRoom.title">Dune</span>
                                        <span class="text-[10px] font-bold text-emerald-400">SYNCED</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Use cases -->
    <section class="pb-24">
        <div class="max-w-7xl mx-auto px-6">
            <div class="home-reveal mb-10">
                <h2 class="text-sm font-bold text-white/40 tracking-widest uppercase mb-2 mono">Who it’s for</h2>
                <h3 class="text-3xl md:text-4xl font-bold tracking-tight">Built for the nights that used to be “maybe later.”</h3>
            </div>
            <div class="grid md:grid-cols-3 gap-5">
                <article class="home-use-card glass-card rounded-3xl p-7">
                    <span class="material-symbols-outlined text-red-400 text-3xl">favorite</span>
                    <h4 class="text-xl font-bold mt-4 mb-2">Long-distance nights</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Same film, same jokes, same gasp — even when the couches are continents apart.</p>
                </article>
                <article class="home-use-card glass-card rounded-3xl p-7">
                    <span class="material-symbols-outlined text-indigo-400 text-3xl">groups</span>
                    <h4 class="text-xl font-bold mt-4 mb-2">Crew watch parties</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Premiere a title with friends, keep chat on the side, and never lose the plot to lag.</p>
                </article>
                <article class="home-use-card glass-card rounded-3xl p-7">
                    <span class="material-symbols-outlined text-emerald-400 text-3xl">school</span>
                    <h4 class="text-xl font-bold mt-4 mb-2">Classrooms &amp; clubs</h4>
                    <p class="text-sm text-white/50 leading-relaxed">Host a screening with invite-only access, then pause together for discussion.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Premium plans -->
    <section id="premium" class="home-plans pb-28 relative overflow-hidden">
        <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[640px] h-[640px] bg-indigo-600/15 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute right-1/4 bottom-0 w-[420px] h-[420px] bg-fuchsia-600/12 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="home-reveal text-center mb-14 max-w-2xl mx-auto">
                <h2 class="text-sm font-bold text-indigo-400 tracking-widest uppercase mb-3 mono">Uplink Tiers</h2>
                <h3 class="text-3xl md:text-5xl font-bold tracking-tight mb-4">Stay free. Or go Premium.</h3>
                <p class="text-white/50 leading-relaxed">Guest rooms are open to everyone. Premium unlocks the full protocol — cosmetics, unlimited hosting, and a badge that says you run the night.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto items-stretch">
                <article class="home-plan-card glass-card rounded-[1.75rem] p-7 md:p-8 flex flex-col">
                    <p class="text-[10px] mono tracking-[0.25em] text-white/40 uppercase mb-4">Signal</p>
                    <h4 class="text-2xl font-bold mb-1">Free</h4>
                    <p class="text-sm text-white/45 mb-6">Drop in, watch, chat. No card required.</p>
                    <div class="flex items-end gap-1 mb-8">
                        <span class="text-5xl font-black tracking-tighter">$0</span>
                        <span class="text-sm text-white/40 mb-2 mono">/ forever</span>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1 text-sm text-white/70">
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Guest or account join</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Millisecond sync playback</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Live room chat</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Invite-only rooms</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-white/25 text-[18px] mt-0.5">check</span><span class="text-white/35">Profile cosmetics locked</span></li>
                    </ul>
                    <a href="backend/guest_login.php" class="home-magnetic w-full rounded-2xl border border-white/15 bg-white/5 hover:bg-white/10 py-3.5 text-center font-bold cursor-pointer transition-colors">Start for free</a>
                </article>

                <article class="home-plan-card home-plan-featured glass-card rounded-[1.75rem] p-7 md:p-8 flex flex-col relative overflow-hidden">
                    <div class="absolute top-5 right-5 text-[10px] font-bold tracking-[0.2em] uppercase px-3 py-1 rounded-full bg-gradient-to-r from-indigo-500/30 to-fuchsia-500/30 border border-indigo-400/40 text-indigo-200">Most chosen</div>
                    <p class="text-[10px] mono tracking-[0.25em] text-indigo-300 uppercase mb-4 inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">stars</span>Nexus Premium
                    </p>
                    <h4 class="text-2xl font-bold mb-1 text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-fuchsia-300 to-indigo-300">Ascend</h4>
                    <p class="text-sm text-white/50 mb-6">Ultimate fidelity, unlimited hosting, and a profile that stands out.</p>
                    <div class="flex items-end gap-2 mb-8">
                        <span class="text-5xl font-black tracking-tighter">$4.99</span>
                        <span class="text-sm text-white/45 mb-2 mono">/ month</span>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1 text-sm text-white/80">
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Everything in Free</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Exclusive profile cosmetics &amp; borders</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Unlimited watch-party hosting</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Premium badge on your identity</li>
                        <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Priority uplink — no protocol limits</li>
                    </ul>
                    <a href="frontend/register.php" class="home-magnetic w-full rounded-2xl bg-white text-black hover:shadow-[0_0_40px_rgba(255,255,255,0.28)] py-3.5 text-center font-black tracking-wide uppercase cursor-pointer inline-flex items-center justify-center gap-2">
                        Unlock Premium <span class="material-symbols-outlined text-[18px]">bolt</span>
                    </a>
                    <p class="text-[11px] text-white/35 text-center mt-4">Billed monthly · cancel any time · 30-day cycle</p>
                </article>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="pb-20">
        <div class="max-w-3xl mx-auto px-6">
            <div class="home-reveal text-center mb-10">
                <h2 class="text-sm font-bold text-white/40 tracking-widest uppercase mb-2 mono">Signal Check</h2>
                <h3 class="text-3xl font-bold tracking-tight">Questions, answered</h3>
            </div>
            <div class="space-y-3">
                <div class="glass-card rounded-2xl px-5 py-4 cursor-pointer" @click="toggleFaq(1)">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-bold">Do we all need the same account?</h4>
                        <span class="material-symbols-outlined text-white/40" x-text="faqOpen === 1 ? 'expand_less' : 'expand_more'">expand_more</span>
                    </div>
                    <div x-show="faqOpen === 1" x-collapse>
                        <p class="text-sm text-white/50 mt-3 leading-relaxed">No. Guests can join a room from a link. Hosts get extra controls once signed in — invites, kick, and shop rewards.</p>
                    </div>
                </div>
                <div class="glass-card rounded-2xl px-5 py-4 cursor-pointer" @click="toggleFaq(2)">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-bold">Will playback drift?</h4>
                        <span class="material-symbols-outlined text-white/40" x-text="faqOpen === 2 ? 'expand_less' : 'expand_more'">expand_more</span>
                    </div>
                    <div x-show="faqOpen === 2" x-collapse>
                        <p class="text-sm text-white/50 mt-3 leading-relaxed">Nexus re-syncs continuously so pause, seek, and reconnect stay aligned across clients. The target offset is 0ms.</p>
                    </div>
                </div>
                <div class="glass-card rounded-2xl px-5 py-4 cursor-pointer" @click="toggleFaq(3)">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-bold">Is chat private?</h4>
                        <span class="material-symbols-outlined text-white/40" x-text="faqOpen === 3 ? 'expand_less' : 'expand_more'">expand_more</span>
                    </div>
                    <div x-show="faqOpen === 3" x-collapse>
                        <p class="text-sm text-white/50 mt-3 leading-relaxed">Rooms are invite-only. Hosts can mute, kick, and rotate the invite at any time. Chat never leaves the room.</p>
                    </div>
                </div>
                <div class="glass-card rounded-2xl px-5 py-4 cursor-pointer" @click="toggleFaq(4)">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-bold">Do I need to install anything?</h4>
                        <span class="material-symbols-outlined text-white/40" x-text="faqOpen === 4 ? 'expand_less' : 'expand_more'">expand_more</span>
                    </div>
                    <div x-show="faqOpen === 4" x-collapse>
                        <p class="text-sm text-white/50 mt-3 leading-relaxed">No. Nexus runs in the browser. Open a link, join the room, and the player + chat load together.</p>
                    </div>
                </div>
                <div class="glass-card rounded-2xl px-5 py-4 cursor-pointer" @click="toggleFaq(5)">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-bold">Can I host without a full account?</h4>
                        <span class="material-symbols-outlined text-white/40" x-text="faqOpen === 5 ? 'expand_less' : 'expand_more'">expand_more</span>
                    </div>
                    <div x-show="faqOpen === 5" x-collapse>
                        <p class="text-sm text-white/50 mt-3 leading-relaxed">Guest login lets you enter quickly. Creating an account keeps your watchlist, friends, and shop items between sessions.</p>
                    </div>
                </div>
                <div class="glass-card rounded-2xl px-5 py-4 cursor-pointer" @click="toggleFaq(6)">
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="font-bold">What does Premium include?</h4>
                        <span class="material-symbols-outlined text-white/40" x-text="faqOpen === 6 ? 'expand_less' : 'expand_more'">expand_more</span>
                    </div>
                    <div x-show="faqOpen === 6" x-collapse>
                        <p class="text-sm text-white/50 mt-3 leading-relaxed">Premium is $4.99 a month. You keep every Free feature, plus exclusive cosmetics, unlimited hosting, a Premium badge, and no protocol caps. Activate it from your dashboard after you sign in.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA band -->
    <section class="home-cta-band relative py-24 overflow-hidden">
        <div class="max-w-4xl mx-auto px-6 text-center home-reveal relative z-10">
            <p class="text-xs mono tracking-[0.3em] text-red-400 mb-4 uppercase">Ready when you are</p>
            <h3 class="text-4xl md:text-6xl font-bold tracking-tighter mb-6">Press play with your people.</h3>
            <p class="text-white/50 mb-10 max-w-xl mx-auto">Open a room in seconds. No installs. Just a link, a film, and a crew on the same timeline.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="frontend/register.php" class="home-magnetic animated-border px-8 py-4 font-bold text-white inline-flex items-center gap-2 cursor-pointer">
                    Create your room <span class="material-symbols-outlined">rocket_launch</span>
                </a>
                <a href="frontend/login.php" class="px-8 py-4 rounded-full border border-white/15 bg-white/5 font-medium cursor-pointer hover:bg-white/10 transition-colors">Sign in</a>
            </div>
        </div>
    </section>

    <footer id="contact" class="home-contact border-t border-white/10 pt-20 pb-8 relative overflow-hidden">
        <div class="home-contact-grid absolute inset-0 pointer-events-none opacity-60"></div>
        <div class="absolute -top-24 left-1/4 w-80 h-80 bg-red-600/20 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/5 w-72 h-72 bg-indigo-600/20 rounded-full blur-[110px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start mb-16">
                <div class="lg:col-span-5 home-contact-copy">
                    <p class="text-[10px] mono tracking-[0.3em] text-red-400 uppercase mb-4">Open channel</p>
                    <h3 class="text-4xl md:text-6xl font-bold tracking-tighter leading-[1.05] mb-5">Get in touch.</h3>
                    <p class="text-white/50 leading-relaxed mb-10 max-w-md">Questions, partnerships, bugs, or just a signal from the dark. Drop a message and the crew will reply.</p>

                    <div class="space-y-5">
                        <div class="flex items-start justify-between gap-4 border-b border-white/8 pb-4">
                            <span class="text-[10px] mono tracking-widest text-white/35 uppercase">General</span>
                            <a href="mailto:hello@nexus.watch" class="text-sm text-white hover:text-red-400 transition-colors">hello@nexus.watch</a>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-white/8 pb-4">
                            <span class="text-[10px] mono tracking-widest text-white/35 uppercase">Support</span>
                            <a href="mailto:support@nexus.watch" class="text-sm text-white hover:text-indigo-400 transition-colors">support@nexus.watch</a>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-white/8 pb-4">
                            <span class="text-[10px] mono tracking-widest text-white/35 uppercase">Response</span>
                            <span class="text-sm text-white/70">Within one business day</span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <div class="home-contact-card home-contact-form-wrap relative rounded-[1.75rem] p-6 md:p-8 overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-red-500/60 to-indigo-500/50"></div>

                        <form class="home-contact-form relative"
                              x-show="contactStatus !== 'sent'"
                              @submit.prevent="sendContact()"
                              novalidate>
                            <input type="text" name="website" tabindex="-1" autocomplete="off" class="home-honeypot" x-model="contact.website">

                            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                                <div class="home-field" :class="contact.name && 'is-filled'">
                                    <input type="text" name="name" x-model="contact.name" placeholder="Name" required maxlength="80">
                                    <label>Name</label>
                                </div>
                                <div class="home-field" :class="contact.email && 'is-filled'">
                                    <input type="email" name="email" x-model="contact.email" placeholder="Email" required maxlength="120">
                                    <label>Email</label>
                                </div>
                            </div>
                            <div class="home-field mb-4" :class="contact.topic && 'is-filled'">
                                <select name="topic" x-model="contact.topic">
                                    <option value="general">General enquiry</option>
                                    <option value="support">Support</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="feedback">Feedback</option>
                                    <option value="bug">Bug report</option>
                                </select>
                                <label>Topic</label>
                            </div>
                            <div class="home-field mb-3" :class="contact.message && 'is-filled'">
                                <textarea name="message" x-model="contact.message" placeholder="Message" required maxlength="2000"></textarea>
                                <label>Message</label>
                                <span class="absolute bottom-3 right-3 text-[10px] mono text-white/30" x-text="(contact.message || '').length + '/2000'">0/2000</span>
                            </div>
                            <p class="text-xs text-red-400 mb-4 min-h-[1rem]" x-show="contactError" x-text="contactError"></p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <button type="submit"
                                        class="home-magnetic home-submit relative overflow-hidden animated-border px-8 py-3.5 font-bold text-white inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
                                        :disabled="contactStatus === 'sending'">
                                    <span class="home-submit-shine"></span>
                                    <span class="home-submit-label" x-text="contactStatus === 'sending' ? 'Transmitting…' : 'Send signal'"></span>
                                    <span class="material-symbols-outlined text-[18px]" x-show="contactStatus !== 'sending'">arrow_outward</span>
                                </button>
                                <p class="text-[11px] text-white/35 leading-relaxed">By sending you agree we can reply to this address. We never sell signals.</p>
                            </div>
                        </form>

                        <div class="home-success relative py-16 text-center" x-show="contactStatus === 'sent'" x-cloak>
                            <div class="home-success-mark w-16 h-16 rounded-full bg-emerald-500/15 border border-emerald-400/40 mx-auto flex items-center justify-center mb-6">
                                <span class="material-symbols-outlined text-emerald-400 text-4xl">check</span>
                            </div>
                            <h4 class="home-success-title text-3xl font-bold mb-3">Signal received.</h4>
                            <p class="text-white/50 max-w-sm mx-auto">Thanks — we’ll reach back shortly. Until then, the protocol is live.</p>
                            <button type="button" class="mt-8 text-sm font-bold text-white/70 hover:text-white" @click="resetContact()">Send another</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-10">
                <a href="index.php" class="flex items-center gap-3 cursor-pointer group">
                    <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-white text-[18px]">movie</span>
                    </div>
                    <span class="text-lg font-bold tracking-tight group-hover:text-red-400 transition-colors">NEXUS</span>
                </a>
                <div class="flex flex-wrap items-center justify-center gap-6">
                    <a href="#features" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">Features</a>
                    <a href="#showcase" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">Showcase</a>
                    <a href="#premium" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">Premium</a>
                    <a href="#how-it-works" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">How it works</a>
                    <a href="frontend/login.php" class="text-sm text-white/50 hover:text-white transition-colors cursor-pointer">Sign In</a>
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

    </div>

    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
    <script src="/js/barba_setup.js?v=5"></script>
</body>
</html>
