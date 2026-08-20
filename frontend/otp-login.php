<?php // otp-login.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Nexus</title>
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" crossorigin="anonymous"></script>
    <script>if(window.gsap) gsap.config({nullTargetWarn: false});</script>

    <link rel="stylesheet" href="/frontend/style.css">
    
    <style>
        .ultimate-reveal { opacity: 0; }
        .otp-input { text-align: center; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.5rem; }
        .otp-input:focus { border-color: #dc2626; outline: none; }
    </style>

    <script src="/js/nexus_scripts.js?v=5"></script>
</head>
<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>

    <!-- Trigger toast if backend error exists -->
    <?php if (isset($_GET['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast(<?php echo json_encode($_GET['error']); ?>, 'error');
            }
        });
    </script>
    <?php endif; ?>

<div id="barba-container" data-barba="container" data-barba-namespace="otp-login" x-data="otpForm()">

    <!-- Ambient background animation elements -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none" id="particles-container">
        <div id="blob1" class="absolute top-[20%] left-[-10%] w-[500px] h-[500px] bg-red-600 rounded-full blur-[140px] opacity-20"></div>
        <div id="blob2" class="absolute bottom-[-20%] right-[-10%] w-[600px] h-[600px] bg-indigo-600 rounded-full blur-[160px] opacity-20"></div>
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
                <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-red-600 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(220,38,38,0.4)] relative overflow-hidden" id="logo-box">
                    <span class="material-symbols-outlined text-white relative z-10">verified_user</span>
                </div>
                <span class="nexus-text text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-indigo-400 pr-2">SECURE</span>
            </h1>
            <p class="text-white/50 mt-3 text-sm font-light tracking-wide">Enter the 6-digit code sent to your device.</p>
        </div>

        <!-- Card -->
        <div class="glass-card rounded-2xl p-8 ultimate-reveal border-red-500/20 shadow-[0_0_50px_rgba(220,38,38,0.1)]" id="glass-card">
            
            <form action="../backend/otp-login_backend.php" method="POST" class="space-y-6" id="otpForm" onsubmit="window.showPageLoader && window.showPageLoader();">
                
                <!-- OTP Inputs (Hidden actual input, visible separate inputs) -->
                <input type="hidden" name="otp" x-model="otpCode">
                
                <div class="flex justify-between gap-2 gs-stagger" id="otp-inputs">
                    <template x-for="i in 6" :key="i">
                        <input 
                            type="text" 
                            maxlength="1" 
                            class="w-12 h-14 bg-white/5 border border-white/10 rounded-xl text-center text-2xl font-bold text-white focus:border-red-500 focus:bg-white/10 outline-none transition-all duration-200"
                            @input="handleInput($event, i - 1)"
                            @keydown="handleKeyDown($event, i - 1)"
                            @paste="handlePaste($event)"
                        >
                    </template>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full bg-gradient-to-r from-red-600 to-indigo-600 text-white font-black uppercase tracking-widest text-xs py-4 px-4 rounded-xl hover:shadow-[0_0_20px_rgba(220,38,38,0.5)] transition-all duration-300 mt-4 gs-stagger relative overflow-hidden group flex justify-center items-center gap-2 transform origin-center"
                >
                    <span class="relative z-10" id="btnText">Verify Identity</span>
                    <span class="material-symbols-outlined relative z-10 text-[18px] group-hover:translate-x-1 transition-transform duration-500" id="btnIcon">arrow_forward</span>
                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500 ease-out z-0"></div>
                </button>
                
            </form>

            <div class="text-center gs-stagger mt-8 flex flex-col gap-4">
                
                <!-- Visual Timer -->
                <div class="relative w-full max-w-[200px] mx-auto h-1.5 bg-white/5 rounded-full overflow-hidden shadow-[inset_0_1px_3px_rgba(0,0,0,0.5)]">
                    <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-red-500 to-indigo-500 rounded-full transition-all duration-1000 ease-linear shadow-[0_0_10px_rgba(220,38,38,0.5)]"
                         :style="`width: ${(timeLeft / 180) * 100}%`"></div>
                </div>

                <div class="flex items-center justify-center gap-3">
                    <p class="text-gray-400 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-indigo-400" x-show="timeLeft > 0">timer</span>
                        <span class="font-mono text-white tracking-widest" x-show="timeLeft > 0" x-text="formattedTime"></span>
                        <span x-show="timeLeft === 0" class="text-red-400 font-bold uppercase tracking-wider text-xs animate-pulse">Expired</span>
                    </p>
                    
                    <span class="w-1 h-1 rounded-full bg-white/20"></span>

                    <button 
                        @click.prevent="resendOTP('../backend/resend_otp.php')" 
                        :disabled="timeLeft > 0 || isResending"
                        class="text-sm font-medium transition-all duration-300 inline-flex items-center gap-1.5 group relative"
                        :class="timeLeft > 0 ? 'text-white/20 cursor-not-allowed' : 'text-white hover:text-red-400 cursor-pointer'"
                    >
                        Resend
                        <span x-ref="resendIcon" class="material-symbols-outlined text-[14px]">refresh</span>
                        <div x-show="timeLeft === 0" class="absolute -bottom-1 left-0 w-full h-[1px] bg-gradient-to-r from-red-500 to-transparent origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                    </button>
                </div>

                <p class="text-gray-500 text-xs mt-2">
                    <a href="login.php" class="hover:text-white transition-colors">Back to Login</a>
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-6 gs-stagger text-[10px] uppercase tracking-widest text-white/20 flex justify-between px-4 gs-footer">
            <span>2FA REQUIRED.</span>
            <span class="flex items-center gap-2">Nexus Protocol <div class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(220,38,38,0.8)]"></div></span>
        </div>
    </main>

</div>

<script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js" crossorigin="anonymous"></script>
<script src="/js/barba_setup.js?v=4"></script>

</body>
</html>