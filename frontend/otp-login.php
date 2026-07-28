<?php // otp-login.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Nexus</title>
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nexus-dark': '#050505',
                        'nexus-card': 'rgba(255, 255, 255, 0.05)',
                        'nexus-accent': '#dc2626', /* red-600 */
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
        .otp-input { text-align: center; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.5rem; }
        .otp-input:focus { border-color: #dc2626; outline: none; }
    </style>



</head>
<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" data-barba="wrapper">
    <?php include __DIR__ . '/components/cursor.php'; ?>
    <?php include __DIR__ . '/components/toast.php'; ?>
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
            
            

            <form action="../backend/otp-login_backend.php?action=verify_otp_login" method="POST" class="space-y-6" id="otpForm">
                
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

            <div class="text-center gs-stagger mt-6 flex flex-col gap-2">
                <p class="text-gray-400 text-sm">
                    Didn't receive the code? 
                    <a href="../backend/resend_otp.php" class="text-white font-medium hover:text-red-500 transition-colors ml-1 inline-flex items-center gap-1 group">
                        Resend <span class="material-symbols-outlined text-[14px] group-hover:rotate-180 transition-transform duration-500">refresh</span>
                    </a>
                </p>
                <p class="text-gray-500 text-xs">
                    <a href="login.php" class="hover:text-white transition-colors">Back to Login</a>
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-6 gs-stagger text-[10px] uppercase tracking-widest text-white/20 flex justify-between px-4 gs-footer">
            <span>2FA REQUIRED.</span>
            <span class="flex items-center gap-2">Nexus Protocol <div class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(220,38,38,0.8)]"></div></span>
        </div>
    </main>

    <script src="/frontend/animations.js"></script>
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
                    // Allow only numbers
                    if (/[^0-9]/.test(val)) {
                        e.target.value = val.replace(/[^0-9]/g, '');
                        return;
                    }
                    
                    if (val) {
                        // animate the input
                        if (typeof gsap !== 'undefined') {
                            gsap.fromTo(e.target, { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(2)" });
                        }
                        // move to next
                        if (index < 5) {
                            this.inputs[index + 1].focus();
                        }
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
                            if (typeof gsap !== 'undefined') {
                                gsap.fromTo(this.inputs[i], { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(2)", delay: i * 0.05 });
                            }
                        }
                    });
                    
                    const focusIndex = Math.min(pastedData.length, 5);
                    if(this.inputs[focusIndex]) this.inputs[focusIndex].focus();
                    this.updateHiddenInput();
                }
            }
        }

        // Ultimate entrance animation & opacity fallback safety
        setTimeout(() => {
            if (typeof gsap !== 'undefined') {
                const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
                tl.fromTo("#branding", 
                    { y: -50, opacity: 0, scale: 0.9 },
                    { y: 0, opacity: 1, scale: 1, duration: 1.5, ease: "elastic.out(1, 0.5)" },
                    0
                )
                .fromTo("#logo-box",
                    { rotation: 180, borderRadius: "50%", scale: 0 },
                    { rotation: 0, borderRadius: "0.75rem", scale: 1, duration: 1.5, ease: "expo.inOut" },
                    "-=1.2"
                )
                .fromTo("#glass-card",
                    { y: 50, opacity: 0, rotationY: 15, scale: 0.95 },
                    { y: 0, opacity: 1, rotationY: 0, scale: 1, duration: 1.2, transformPerspective: 1000 },
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

                const submitBtn = document.getElementById('submitBtn');
                if(submitBtn) {
                    submitBtn.addEventListener('mousedown', () => {
                        gsap.to(submitBtn, { scale: 0.95, duration: 0.1, ease: 'power2.inOut' });
                    });
                    submitBtn.addEventListener('mouseup', () => {
                        gsap.to(submitBtn, { scale: 1, duration: 0.5, ease: 'elastic.out(1, 0.3)' });
                    });
                }
            } else {
                // Safety fallback if GSAP or animations.js is blocked
                document.querySelectorAll('.ultimate-reveal').forEach(el => el.style.opacity = '1');
            }
        });
    </script>




    


    </div>



    <script src="https://unpkg.com/@barba/core@2.9.7/dist/barba.umd.js"></script>
    <script>
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') { gsap.registerPlugin(ScrollTrigger); }
        function initAnimations(container = document) {
            if (typeof gsap === 'undefined') return;
            const q = gsap.utils.selector(container);
            const tl = gsap.timeline();
            
            const heroTitleWords = q('.gs-word');
            if(heroTitleWords.length > 0) {
                gsap.set(heroTitleWords, {opacity: 0, y: 40});
                
                tl.fromTo(q('.gs-hero-content .gs-reveal'), 
                    { opacity: 0, y: 30 },
                    { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: 'power3.out' }
                )
                .to(heroTitleWords,
                    { opacity: 1, y: 0, duration: 0.8, stagger: 0.05, ease: 'back.out(1.7)' },
                    "-=0.6"
                )
                .fromTo(q('.gs-hero-visual'),
                    { opacity: 0, scale: 0.9, x: 50 },
                    { opacity: 1, scale: 1, x: 0, duration: 1, ease: 'power3.out' },
                    "-=0.8"
                );
            }

            q('.gs-reveal-up').forEach(elem => {
                gsap.fromTo(elem,
                    { opacity: 0, y: 50 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.8, 
                        ease: 'power3.out',
                        scrollTrigger: {
                            trigger: elem,
                            start: "top 85%",
                            toggleActions: "play none none reverse"
                        }
                    }
                );
            });

            q('.gs-movie-card').forEach((elem, i) => {
                gsap.fromTo(elem,
                    { opacity: 0, scale: 0.8, y: 40 },
                    {
                        opacity: 1, 
                        scale: 1, 
                        y: 0, 
                        duration: 0.6, 
                        delay: (i % 4) * 0.1,
                        ease: 'back.out(1.4)',
                        scrollTrigger: {
                            trigger: elem.parentElement,
                            start: "top 80%"
                        }
                    }
                );
            });

            q('.gs-step').forEach((elem, i) => {
                gsap.fromTo(elem,
                    { opacity: 0, y: 40 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.6,
                        ease: 'power2.out',
                        scrollTrigger: {
                            trigger: elem.parentElement,
                            start: "top 80%"
                        }
                    }
                );
            });
        }

        // Initialize animations on first load
        initAnimations();

    </script>
    <script src="/js/barba_setup.js"></script>

</body>
</html>