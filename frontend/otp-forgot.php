<?php // otp-forgot.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP - Nexus</title>
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nexus-dark': '#050505',
                        'nexus-card': 'rgba(255, 255, 255, 0.05)',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <link rel="stylesheet" href="style.css">
    
    <style>
        .ultimate-reveal { opacity: 0; }
    </style>



</head>
<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" x-data="otpForm()">
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>

<!-- Ambient background animation elements -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none" id="particles-container">
        <div id="blob1" class="absolute top-[30%] left-[-20%] w-[500px] h-[500px] bg-yellow-600 rounded-full blur-[140px] opacity-20"></div>
        <div id="blob2" class="absolute top-[-10%] right-[-10%] w-[400px] h-[400px] bg-red-600 rounded-full blur-[160px] opacity-20"></div>
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
                    <span class="material-symbols-outlined text-white relative z-10">key</span>
                </div>
                <span class="nexus-text text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-red-500 pr-2">VERIFY</span>
            </h1>
            <p class="text-white/50 mt-3 text-sm font-light tracking-wide">Enter the reset code sent to your email.</p>
        </div>

        <!-- Card -->
        <div class="glass-card rounded-2xl p-8 ultimate-reveal border-red-500/20 shadow-[0_0_50px_rgba(239,68,68,0.1)]" id="glass-card">
            
            

            <form action="../backend/otp-forgot_backend.php?action=verify_otp_forgot" method="POST" class="space-y-6" id="otpForm">
                
                <!-- OTP Inputs -->
                <input type="hidden" name="otp" x-model="otpCode">
                
                <div class="flex justify-between gap-2 gs-stagger" id="otp-inputs">
                    <template x-for="i in 6" :key="i">
                        <input 
                            type="text" 
                            maxlength="1" 
                            class="w-12 h-14 bg-white/5 border border-white/10 rounded-xl text-center text-2xl font-bold text-white focus:border-yellow-500 focus:bg-white/10 outline-none transition-all duration-200"
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
                    class="w-full bg-gradient-to-r from-red-600 to-yellow-600 text-white font-black uppercase tracking-widest text-xs py-4 px-4 rounded-xl hover:shadow-[0_0_20px_rgba(239,68,68,0.5)] transition-all duration-300 mt-4 gs-stagger relative overflow-hidden group flex justify-center items-center gap-2 transform origin-center"
                >
                    <span class="relative z-10" id="btnText">Confirm Reset</span>
                    <span class="material-symbols-outlined relative z-10 text-[18px] group-hover:translate-x-1 transition-transform duration-500" id="btnIcon">check_circle</span>
                    <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-0 transition-transform duration-500 ease-out z-0"></div>
                </button>
                
            </form>

            <div class="text-center gs-stagger mt-6 flex flex-col gap-2">
                <p class="text-gray-400 text-sm">
                    Didn't receive the code? 
                    <a href="../backend/resend_otp.php" class="text-white font-medium hover:text-yellow-500 transition-colors ml-1 inline-flex items-center gap-1 group">
                        Resend <span class="material-symbols-outlined text-[14px] group-hover:rotate-180 transition-transform duration-500">refresh</span>
                    </a>
                </p>
                <p class="text-gray-500 text-xs">
                    <a href="forgot-password.php" class="hover:text-white transition-colors">Change Email</a>
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-6 gs-stagger text-[10px] uppercase tracking-widest text-white/20 flex justify-between px-4 gs-footer">
            <span>VERIFICATION REQUIRED.</span>
            <span class="flex items-center gap-2">Nexus Protocol <div class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.8)]"></div></span>
        </div>
    </main>

    <script src="animations.js"></script>
    <script>
        function otpForm() {
            return {
                otpCode: '',
                inputs: [],
                init() {
                    this.$nextTick(() => {
                        this.inputs = Array.from(this.$el.querySelectorAll('#otp-inputs input'));
                        if(this.inputs.length > 0) this.inputs[0].focus();
                    });
                },
                updateHiddenInput() {
                    this.otpCode = this.inputs.map(input => input.value).join('');
                },
                handleInput(e, index) {
                    const val = e.target.value;
                    if (/[^0-9]/.test(val)) {
                        e.target.value = val.replace(/[^0-9]/g, '');
                        return;
                    }
                    if (val) {
                        gsap.fromTo(e.target, { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(2)" });
                        if (index < 5) this.inputs[index + 1].focus();
                    }
                    this.updateHiddenInput();
                },
                handleKeyDown(e, index) {
                    if (e.key === 'Backspace') {
                        if (!e.target.value && index > 0) {
                            this.inputs[index - 1].focus();
                            this.inputs[index - 1].value = '';
                        }
                        this.updateHiddenInput();
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        this.inputs[index - 1].focus();
                    } else if (e.key === 'ArrowRight' && index < 5) {
                        this.inputs[index + 1].focus();
                    }
                },
                handlePaste(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                    if (!pastedData) return;
                    pastedData.split('').forEach((char, i) => {
                        if (i < 6) {
                            this.inputs[i].value = char;
                            gsap.fromTo(this.inputs[i], { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(2)", delay: i * 0.05 });
                        }
                    });
                    const focusIndex = Math.min(pastedData.length, 5);
                    this.inputs[focusIndex].focus();
                    this.updateHiddenInput();
                }
            }
        }

        // Ultimate entrance animation
        document.addEventListener('DOMContentLoaded', () => {
            const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
            tl.fromTo("#branding", 
                { y: -50, opacity: 0, scale: 0.9 },
                { y: 0, opacity: 1, scale: 1, duration: 1.5, ease: "elastic.out(1, 0.5)" },
                0
            )
            .fromTo("#logo-box",
                { rotation: -180, borderRadius: "50%", scale: 0 },
                { rotation: 0, borderRadius: "0.75rem", scale: 1, duration: 1.5, ease: "expo.inOut" },
                "-=1.2"
            )
            .fromTo("#glass-card",
                { y: 50, opacity: 0, rotationX: -15, scale: 0.95 },
                { y: 0, opacity: 1, rotationX: 0, scale: 1, duration: 1.2, transformPerspective: 1000 },
                "-=1"
            )
            .fromTo("#otp-inputs input",
                { y: 20, opacity: 0, scale: 0.5 },
                { y: 0, opacity: 1, scale: 1, duration: 0.8, stagger: 0.05, ease: "back.out(1.5)" },
                "-=0.8"
            )
            .fromTo(".gs-stagger",
                { y: 20, opacity: 0 },
                { y: 0, opacity: 1, duration: 0.8, stagger: 0.1 },
                "-=0.6"
            )
            .fromTo(".gs-footer",
                { opacity: 0 },
                { opacity: 1, duration: 1 },
                "-=0.5"
            );

            // Ultimate submit button interaction
            const submitBtn = document.getElementById('submitBtn');
            if(submitBtn) {
                submitBtn.addEventListener('mousedown', () => {
                    gsap.to(submitBtn, { scale: 0.95, duration: 0.1, ease: 'power2.inOut' });
                });
                submitBtn.addEventListener('mouseup', () => {
                    gsap.to(submitBtn, { scale: 1, duration: 0.5, ease: 'elastic.out(1, 0.3)' });
                });
            }
        });
    </script>



</body>
</html>
