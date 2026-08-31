<?php // register.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Nexus</title>
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
    
    <style>
        [x-cloak] { display: none !important; }
        .ultimate-reveal { opacity: 0; }
        .success-checkmark { stroke-dasharray: 50; stroke-dashoffset: 50; }
    </style>



    <script src="../js/nexus_scripts.js?v=1788155000"></script>
</head>

<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>
<div id="barba-container" data-barba="container" data-barba-namespace="register" x-data="{ showPassword: false }">
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
        <div class="text-center mb-8 ultimate-reveal" id="branding">
            <h1 class="text-4xl font-black tracking-tighter uppercase italic flex items-center justify-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-600 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(79,70,229,0.4)] relative overflow-hidden" id="logo-box">
                    <svg class="w-6 h-6 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <span class="nexus-text text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-red-500 pr-2">JOIN NEXUS</span>
            </h1>
            <p class="text-white/50 mt-3 text-sm font-light tracking-wide">Become part of the universe.</p>
        </div>

        <!-- Register Card -->
        <div class="glass-card rounded-2xl p-8 ultimate-reveal border-indigo-500/20 shadow-[0_0_50px_rgba(79,70,229,0.1)]" id="glass-card">
            
            

            <form action="../backend/register_backend.php?action=register" method="POST" class="space-y-5" id="registerForm" onsubmit="return validateRegistrationForm();" novalidate>
                
                <!-- Name Field -->
                <div class="floating-label-group gs-stagger">
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        placeholder="Full Name" 
                        class="input-field w-full pl-12 pr-4 pt-7 pb-3 rounded-xl text-white outline-none focus:ring-0 peer focus:border-indigo-500"
                    >
                    <label for="name" class="floating-label peer-focus:text-indigo-400">Full Name</label>
                    <span class="material-symbols-outlined absolute left-4 top-[55%] -translate-y-1/2 text-white/30 pointer-events-none icon-transition peer-focus:text-indigo-500">person</span>
                </div>

                <!-- Email Field -->
                <div class="floating-label-group gs-stagger">
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        placeholder="Email" 
                        class="input-field w-full pl-12 pr-4 pt-7 pb-3 rounded-xl text-white outline-none focus:ring-0 peer focus:border-indigo-500"
                    >
                    <label for="email" class="floating-label peer-focus:text-indigo-400">Email Address</label>
                    <span class="material-symbols-outlined absolute left-4 top-[55%] -translate-y-1/2 text-white/30 pointer-events-none icon-transition peer-focus:text-indigo-500">mail</span>
                </div>

                <!-- Password Field -->
                <div class="floating-label-group gs-stagger relative">
                    <input 
                        :type="showPassword ? 'text' : 'password'" 
                        name="password" 
                        id="password" 
                        placeholder="Password" 
                        class="input-field w-full pl-12 pr-12 pt-7 pb-3 rounded-xl text-white outline-none focus:ring-0 peer focus:border-indigo-500"
                    >
                    <label for="password" class="floating-label peer-focus:text-indigo-400">Password</label>
                    <span class="material-symbols-outlined absolute left-4 top-[55%] -translate-y-1/2 text-white/30 pointer-events-none icon-transition peer-focus:text-indigo-500">lock</span>
                    
                    <button 
                        type="button" 
                        @click="showPassword = !showPassword" 
                        class="absolute right-3 top-[55%] -translate-y-1/2 text-gray-400 hover:text-white transition-colors p-1"
                    >
                        <span x-show="!showPassword" class="material-symbols-outlined text-[20px]">visibility</span>
                        <span x-show="showPassword" x-cloak class="material-symbols-outlined text-[20px]">visibility_off</span>
                    </button>
                </div>

                <!-- Terms -->
                <div class="flex items-center text-xs text-gray-400 gs-stagger">
                    <label class="flex items-start gap-2 cursor-pointer group">
                        <div class="relative flex items-center mt-0.5">
                            <input type="checkbox" name="terms" id="terms" class="peer sr-only">
                            <div class="w-4 h-4 border border-gray-500 rounded bg-transparent peer-checked:bg-indigo-600 peer-checked:border-indigo-600 transition-all flex items-center justify-center">
                                <svg viewBox="0 0 14 10" fill="none" class="w-2.5 h-2.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity">
                                    <path d="M1 5L4.5 8.5L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="success-checkmark"/>
                                </svg>
                            </div>
                        </div>
                        <span class="group-hover:text-gray-300 transition-colors leading-tight">I agree to the <a href="#" class="text-indigo-400 hover:underline">Terms of Service</a> and <a href="#" class="text-indigo-400 hover:underline">Privacy Policy</a>.</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button 
                    type="button" 
                    id="submitBtn"
                    onclick="handleOTPNavigation(event)"
                    class="w-full bg-gradient-to-r from-indigo-600 to-red-600 text-white font-black uppercase tracking-widest text-xs py-4 px-4 rounded-xl hover:shadow-[0_0_20px_rgba(79,70,229,0.5)] transition-all duration-300 mt-2 gs-stagger relative overflow-hidden group flex justify-center items-center gap-2 transform origin-center"
                >
                    <span class="relative z-10" id="btnText">Create Account</span>
                    <span class="material-symbols-outlined relative z-10 text-[18px] group-hover:rotate-45 transition-transform duration-500" id="btnIcon">add_circle</span>
                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500 ease-out z-0"></div>
                </button>
                
            </form>

            <!-- Login link -->
            <div class="text-center gs-stagger mt-6">
                <p class="text-gray-400 text-sm">
                    Already have an account? 
                    <a href="login.php" class="text-white font-medium hover:text-indigo-500 transition-colors ml-1 inline-flex items-center gap-1 group">
                        Log In <span class="material-symbols-outlined text-[14px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
                    </a>
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-6 gs-stagger text-[10px] uppercase tracking-widest text-white/20 flex justify-between px-4 gs-footer">
            <span>SECURE. ENCRYPTED.</span>
            <span class="flex items-center gap-2">Nexus Protocol <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse shadow-[0_0_8px_rgba(79,70,229,0.8)]"></div></span>
        </div>
    </main>

    <!-- Error Modal Popup -->
    <div id="errorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
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