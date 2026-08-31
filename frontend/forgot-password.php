<?php // forgot-password.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Nexus</title>
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

    <link rel="stylesheet" href="style.css">
    
    <style>
        .ultimate-reveal { opacity: 0; }
    </style>



    <script src="../js/nexus_scripts.js?v=1788150001"></script>
</head>
<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" data-barba="wrapper">
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>
<div id="barba-container" data-barba="container" data-barba-namespace="forgot-password">


<!-- Ambient background animation elements -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none" id="particles-container">
        <div id="blob1" class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-red-600 rounded-full blur-[160px] opacity-20"></div>
        <div id="blob2" class="absolute bottom-[-15%] right-[-10%] w-[500px] h-[500px] bg-yellow-600 rounded-full blur-[140px] opacity-30"></div>
    </div>

    <!-- Movie Poster Wall -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none flex items-center justify-center opacity-60 mask-radial">
        <div class="flex flex-row gap-6 transform -rotate-[15deg] scale-[1.5]" id="poster-wall-container">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- Main Container -->
    <main class="w-full max-w-md p-6 z-10" id="main-container">
        
        <!-- Logo / Branding -->
        <div class="text-center mb-8 ultimate-reveal" id="branding">
            <h1 class="text-4xl font-black tracking-tighter uppercase italic flex items-center justify-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(239,68,68,0.4)] relative overflow-hidden" id="logo-box">
                    <svg class="w-6 h-6 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <span class="nexus-text text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-red-500 pr-2">RECOVER</span>
            </h1>
            <p class="text-white/50 mt-3 text-sm font-light tracking-wide">Enter your email to reset your password.</p>
        </div>

        <!-- Recovery Card -->
        <div class="glass-card rounded-2xl p-8 ultimate-reveal border-red-500/20 shadow-[0_0_50px_rgba(239,68,68,0.1)]" id="glass-card">
            
            <!-- Success Banner (Inline) -->
            <?php if (isset($_GET['message'])): ?>
            <div class="bg-green-500/10 border border-green-500/50 text-green-400 p-3 rounded-lg mb-6 text-sm flex items-center gap-2 gs-error">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                <span><?= htmlspecialchars(urldecode($_GET['message'])) ?></span>
            </div>
            <?php endif; ?>

            <!-- Form attached to JS validation with novalidate -->
            <form action="../backend/forgot-password_backend.php?action=forgot_password" method="POST" class="space-y-6" id="recoveryForm" onsubmit="return validateRecoveryForm()" novalidate>
                
                <!-- Email Field -->
                <div class="floating-label-group gs-stagger">
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        placeholder="Email" 
                        required 
                        class="input-field w-full pl-12 pr-4 pt-7 pb-3 rounded-xl text-white outline-none focus:ring-0 peer focus:border-red-500"
                    >
                    <label for="email" class="floating-label peer-focus:text-red-400">Email Address</label>
                    <span class="material-symbols-outlined absolute left-4 top-[55%] -translate-y-1/2 text-white/30 pointer-events-none icon-transition peer-focus:text-red-500">mail</span>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full bg-gradient-to-r from-red-600 to-yellow-600 text-white font-black uppercase tracking-widest text-xs py-4 px-4 rounded-xl hover:shadow-[0_0_20px_rgba(239,68,68,0.5)] transition-all duration-300 mt-2 gs-stagger relative overflow-hidden group flex justify-center items-center gap-2 transform origin-center"
                >
                    <span class="relative z-10" id="btnText">Send Reset Link</span>
                    <span class="material-symbols-outlined relative z-10 text-[18px] group-hover:translate-x-1 transition-transform duration-500" id="btnIcon">send</span>
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-0 transition-transform duration-500 ease-out z-0"></div>
                </button>
                
            </form>

            <!-- Back to Login link -->
            <div class="text-center gs-stagger mt-6">
                <p class="text-gray-400 text-sm">
                    <a href="login.php" class="text-white font-medium hover:text-red-500 transition-colors ml-1 inline-flex items-center gap-1 group">
                        <span class="material-symbols-outlined text-[14px] group-hover:-translate-x-1 transition-transform">arrow_back</span> Back to Login
                    </a>
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-6 gs-stagger text-[10px] uppercase tracking-widest text-white/20 flex justify-between px-4 gs-footer">
            <span>SECURE. ENCRYPTED.</span>
            <span class="flex items-center gap-2">Nexus Protocol <div class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.8)]"></div></span>
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
    </div>

    <!-- GSAP Animations & Interactions -->
    
    




    


    </div>



    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
    
    <script src="../js/barba_setup.js?v=4"></script>

</body>
</html>