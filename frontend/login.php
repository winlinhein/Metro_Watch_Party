<?php // index.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nexus</title>
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js" crossorigin="anonymous"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" crossorigin="anonymous" onerror="window.gsap=window.gsap||{to:()=>({to:()=>({}),fromTo:()=>({})}),fromTo:()=>({}),from:()=>({}),set:()=>{},timeline:()=>({to:()=>({}),fromTo:()=>({}),add:()=>({}),set:()=>({})}),config:()=>{},killTweensOf:()=>{}}"></script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>

    <link rel="stylesheet" href="style.css">



    <script src="../js/nexus_scripts.js?v=1788150001"></script>
</head>
<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>
<div id="barba-container" data-barba="container" data-barba-namespace="login" x-data="{ showPassword: false }">


<!-- Floating Back Button -->
    <a href="../index.php" class="fixed top-8 left-8 sm:top-12 sm:left-12 z-50 flex items-center justify-center w-14 h-14 rounded-full bg-black/40 border border-white/10 backdrop-blur-xl gs-back-btn overflow-visible" id="floating-back">
        <!-- Magnetic hit area -->
        <div class="absolute -inset-6 bg-transparent rounded-full gs-back-hit"></div>
        <!-- Rotating ring -->
        <svg class="absolute inset-[-4px] w-[calc(100%+8px)] h-[calc(100%+8px)] pointer-events-none opacity-0 gs-back-ring" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="48" fill="none" stroke="url(#redGrad)" stroke-width="2" stroke-dasharray="100 200" stroke-linecap="round"></circle>
            <defs>
                <linearGradient id="redGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ef4444" />
                    <stop offset="100%" stop-color="#7f1d1d" />
                </linearGradient>
            </defs>
        </svg>
        <span class="material-symbols-outlined text-white/60 relative z-10 gs-back-icon text-[20px]">arrow_back</span>
    </a>

    <!-- Ambient background animation elements -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none" id="particles-container">
        <div id="blob1" class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-red-600 rounded-full blur-[160px] opacity-20"></div>
        <div id="blob2" class="absolute bottom-[-15%] right-[-10%] w-[500px] h-[500px] bg-indigo-600 rounded-full blur-[140px] opacity-30"></div>
    </div>

    <!-- Movie Poster Wall -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none flex items-center justify-center opacity-60 mask-radial">
        <div class="flex flex-row gap-6 transform -rotate-[15deg] scale-[1.5]" id="poster-wall-container">
            <!-- Populated by JS -->
        </div>
    </div>
    <script>
    (function() {
        var POSTER_IMAGES = [
            '3 Idiots.jpg','A Brighter Summer Day.jpg','Deadpool & Wolverine.jpg',
            'Deep Water.jpg','Doctor Strange in the Multiverse of Madness.jpg','Dune.jpg',
            'Forrest Gump.jpg','Grave of the Fireflies.jpg','Heartstopper Forever.jpg',
            'Inception.jpg','Interstellar.jpg','KPop Demon Hunters.jpg',
            'Minions & Monsters.jpg','Modern Times.jpg',"Now You See Me Now You Don't.jpg",
            'Obsession.jpg','Once We Were Us.jpg','Parasite.jpg',
            'Reservoir Dogs.jpg','Spider-Man Brand New Day.jpg','Supergirl.jpg',
            'Swapped.jpg','The Lady.jpg','The Mandalorian and Grogu.jpg',
            'The Mask.jpg','The Odyssey.jpg','The Salt of the Earth.jpg',
            'The Shawshank Redemption.jpg','Warfare.jpg','World War Z.jpg','Your Name.jpg'
        ];
        var BASE = 'Movies poster/';
        function shuffle(arr) {
            var a = arr.slice();
            for (var i = a.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var t = a[i]; a[i] = a[j]; a[j] = t;
            }
            return a;
        }
        var wall = document.getElementById('poster-wall-container');
        if (wall) {
            wall.dataset.initialized = '1';
            var html = '';
            for (var i = 0; i < 8; i++) {
                var dir = i % 2 === 0 ? 'up' : 'down';
                var dur = 120 + (i * 15);
                var imgs = shuffle(POSTER_IMAGES).concat(shuffle(POSTER_IMAGES));
                var posters = '';
                imgs.forEach(function(f) {
                    posters += '<div class="poster"><img src="' + BASE + encodeURIComponent(f) + '" alt="" class="poster-img" loading="eager" decoding="async" onerror="this.parentElement.style.display=\'none\'"></div>';
                });
                html += '<div class="poster-col ' + dir + '" style="animation-duration:' + dur + 's">' + posters + '</div>';
            }
            wall.innerHTML = html;
        }
    })();
    </script>

    <!-- Main Container -->
    <main class="w-full max-w-md p-6 z-10" id="main-container">
        
        <!-- Logo / Branding -->
        <div class="text-center mb-10 gs-reveal">
            <h1 class="text-5xl font-black tracking-tighter uppercase italic flex items-center justify-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-tr from-red-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(220,38,38,0.4)] relative overflow-hidden">
                    <svg class="w-6 h-6 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="nexus-text pr-2">NEXUS</span>
            </h1>
            <p class="text-white/50 mt-3 text-sm font-light tracking-wide">Enter the void. Connect your universe.</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-2xl p-8 gs-reveal" id="glass-card">
            
            <!-- Success Message Banner -->
            

            <!-- Error Message Banner -->
            

            <!-- Form with novalidate so custom JS modal handles all validation -->
            <form action="../backend/login_backend.php" method="POST" class="space-y-6" id="loginForm" onsubmit="return validateLoginForm()" novalidate>
                
                <!-- Email Field -->
                <div class="floating-label-group gs-stagger">
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        placeholder="Email" 
                        required 
                        class="input-field w-full pl-12 pr-4 pt-7 pb-3 rounded-xl text-white outline-none focus:ring-0 peer"
                    >
                    <label for="email" class="floating-label">Email or Phone</label>
                    <span class="material-symbols-outlined absolute left-4 top-[55%] -translate-y-1/2 text-white/30 pointer-events-none icon-transition peer-focus:text-red-500">mail</span>
                </div>

                <!-- Password Field -->
                <div class="floating-label-group gs-stagger relative">
                    <input 
                        :type="showPassword ? 'text' : 'password'" 
                        name="password" 
                        id="password" 
                        placeholder="Password" 
                        required 
                        class="input-field w-full pl-12 pr-12 pt-7 pb-3 rounded-xl text-white outline-none focus:ring-0 peer"
                    >
                    <label for="password" class="floating-label">Password</label>
                    <span class="material-symbols-outlined absolute left-4 top-[55%] -translate-y-1/2 text-white/30 pointer-events-none icon-transition peer-focus:text-red-500">lock</span>
                    
                    <button 
                        type="button" 
                        @click="showPassword = !showPassword" 
                        class="absolute right-3 top-[55%] -translate-y-1/2 text-gray-400 hover:text-white transition-colors p-1"
                    >
                        <span x-show="!showPassword" class="material-symbols-outlined text-[20px]">visibility</span>
                        <span x-show="showPassword" x-cloak class="material-symbols-outlined text-[20px]">visibility_off</span>
                    </button>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-sm text-gray-400 gs-stagger">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="remember" class="peer sr-only">
                            <div class="w-4 h-4 border border-gray-500 rounded bg-transparent peer-checked:bg-red-600 peer-checked:border-red-600 transition-all"></div>
                            <span class="material-symbols-outlined absolute left-[1px] top-[1px] text-[14px] text-white opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                        </div>
                        <span class="group-hover:text-gray-300 transition-colors">Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="hover:text-red-400 hover:underline transition-colors flex items-center gap-1">
                        Forgot password? <span class="material-symbols-outlined text-[14px]">key</span>
                    </a>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full bg-white text-black font-black uppercase tracking-widest text-xs py-4 px-4 rounded-xl hover:bg-red-600 hover:text-white transition-all duration-300 mt-4 gs-stagger relative overflow-hidden group flex justify-center items-center gap-2"
                >
                    <span class="relative z-10">Log In</span>
                    <span class="material-symbols-outlined relative z-10 text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    <!-- Hover ripple effect -->
                    <div class="absolute inset-0 h-full w-full bg-black/10 scale-0 rounded-xl opacity-0 transition-transform duration-500 origin-center" id="btnRipple"></div>
                </button>
                
            </form>

            <!-- Divider -->
            <div class="relative my-8 gs-stagger">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-white/10"></div></div>
                <div class="relative flex justify-center text-[10px] uppercase tracking-widest"><span class="bg-[#111] px-4 text-white/40 rounded flex items-center gap-2"><span class="material-symbols-outlined text-[14px]">hub</span> Connect via</span></div>
            </div>

            <!-- Social Logins -->
            <div class="flex justify-center w-full gs-stagger mb-6">
                
                <button class="flex items-center justify-center gap-2 bg-white/5 border border-white/10 py-3 rounded-xl hover:bg-red-600/20 hover:border-red-500/50 transition-all group relative overflow-hidden w-full">
                    <svg class="w-4 h-4 text-red-500 group-hover:scale-110 transition-transform relative z-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-white/70 group-hover:text-white relative z-10">Google</span>
                </button>
            </div>

            <!-- Create account link -->
            <div class="text-center gs-stagger">
                <p class="text-gray-400 text-sm">
                    Don't have an account? 
                    <a href="register.php" class="text-white font-medium hover:text-red-500 transition-colors ml-1 inline-flex items-center gap-1 group">
                        Create Account <span class="material-symbols-outlined text-[14px] group-hover:-translate-y-0.5 group-hover:translate-x-0.5 transition-transform">open_in_new</span>
                    </a>
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-8 gs-stagger text-[10px] uppercase tracking-widest text-white/20 flex justify-between px-4 gs-footer">
            <span>V.4.2.0-STABLE</span>
            <span class="flex items-center gap-2">Ready <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.8)]"></div></span>
        </div>
    </main>

    <!-- Error Modal Popup -->
    <div id="errorModal" onclick="closeErrorModalOnBackdrop(event)" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
        <div id="errorModalCard" class="w-full max-w-sm glass-card p-6 rounded-2xl border border-red-500/30 bg-[#0a0a0c] text-center shadow-[0_0_50px_rgba(220,38,38,0.25)] transform scale-95 transition-transform duration-300">
            <div class="w-12 h-12 bg-red-600/15 text-red-500 rounded-full flex items-center justify-center mx-auto mb-3 border border-red-500/30">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <h3 class="text-lg font-bold text-white uppercase tracking-wider mb-2">Notice</h3>
            
            <!-- Error Bullet List -->
            <ul id="errorList" class="text-xs text-red-300 space-y-2 my-4 text-left bg-red-950/20 p-3.5 rounded-xl border border-red-900/40">
                <!-- Injected via JS -->
            </ul>

            <button type="button" onclick="closeErrorModal()" class="w-full bg-gradient-to-r from-red-600 to-indigo-600 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl hover:shadow-[0_0_20px_rgba(220,38,38,0.4)] transition-all">
                Dismiss
            </button>
        </div>
    </div><!-- Error Modal Popup -->
    <div id="errorModal" onclick="closeErrorModalOnBackdrop(event)" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
        <div id="errorModalCard" class="w-full max-w-sm glass-card p-6 rounded-2xl border border-red-500/30 bg-[#0a0a0c] text-center shadow-[0_0_50px_rgba(220,38,38,0.25)] transform scale-95 transition-transform duration-300">
            <div class="w-12 h-12 bg-red-600/15 text-red-500 rounded-full flex items-center justify-center mx-auto mb-3 border border-red-500/30">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <h3 class="text-lg font-bold text-white uppercase tracking-wider mb-2">Notice</h3>
            
            <!-- Error Bullet List -->
            <ul id="errorList" class="text-xs text-red-300 space-y-2 my-4 text-left bg-red-950/20 p-3.5 rounded-xl border border-red-900/40">
                <!-- Injected via JS -->
            </ul>

            <button type="button" onclick="closeErrorModal()" class="w-full bg-gradient-to-r from-red-600 to-indigo-600 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl hover:shadow-[0_0_20px_rgba(220,38,38,0.4)] transition-all">
                Dismiss
            </button>
        </div>
    </div>

    <!-- GSAP Animations & Interactions -->
    
    




    


    </div>



    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
    
    <script src="../js/barba_setup.js?v=4"></script>

</body>
</html>