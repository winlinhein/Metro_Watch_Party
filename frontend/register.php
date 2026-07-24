<?php // register.php - Frontend view ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Nexus</title>
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nexus-dark': '#050505',
                        'nexus-card': 'rgba(255, 255, 255, 0.05)',
                        'nexus-accent': '#dc2626',
                        'nexus-danger': '#4f46e5',
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
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <link rel="stylesheet" href="style.css">
    
    <style>
        [x-cloak] { display: none !important; }
        .ultimate-reveal { opacity: 0; }
        .success-checkmark { stroke-dasharray: 50; stroke-dashoffset: 50; }
    </style>
</head>

<body class="bg-[#050505] text-white flex items-center justify-center font-sans antialiased relative overflow-hidden min-h-screen" x-data="{ showPassword: false }">

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
            
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-nexus-danger/10 border border-nexus-danger/50 text-nexus-danger p-3 rounded-lg mb-6 text-sm flex items-center gap-2 gs-error">
                <span class="material-symbols-outlined text-[20px]">error</span>
                <span><?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
            </div>
            <?php endif; ?>

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
    <script src="animations.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
            tl.fromTo("#branding", 
                { y: -50, opacity: 0, scale: 0.9 },
                { y: 0, opacity: 1, scale: 1, duration: 1.5, ease: "elastic.out(1, 0.5)" },
                0
            )
            .fromTo("#logo-box",
                { rotation: -180, borderRadius: "50%" },
                { rotation: 0, borderRadius: "0.75rem", duration: 1.5, ease: "expo.inOut" },
                "-=1.2"
            )
            .fromTo("#glass-card",
                { y: 100, opacity: 0, rotationY: -15, scale: 0.9 },
                { y: 0, opacity: 1, rotationY: 0, scale: 1, duration: 1.2, transformPerspective: 1000 },
                "-=1"
            )
            .fromTo(".gs-stagger",
                { x: 50, opacity: 0 },
                { x: 0, opacity: 1, duration: 0.8, stagger: 0.1 },
                "-=0.8"
            )
            .fromTo(".gs-footer",
                { y: 20, opacity: 0 },
                { y: 0, opacity: 1, duration: 1 },
                "-=0.5"
            );

            const termsCheck = document.getElementById('terms');
            const checkmark = document.querySelector('.success-checkmark');
            if (termsCheck && checkmark) {
                termsCheck.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        gsap.fromTo(checkmark, 
                            { strokeDashoffset: 50 },
                            { strokeDashoffset: 0, duration: 0.6, ease: "power2.out" }
                        );
                    }
                });
            }

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.addEventListener('mousedown', () => {
                    gsap.to(submitBtn, { scale: 0.92, duration: 0.1, ease: 'power2.inOut' });
                });
                submitBtn.addEventListener('mouseup', () => {
                    gsap.to(submitBtn, { scale: 1, duration: 0.5, ease: 'elastic.out(1, 0.3)' });
                });
            }
        });

        function validateRegistrationForm() {
            let errors = [];
            let name = document.getElementById('name').value;
            let email = document.getElementById('email').value;
            let password = document.getElementById('password').value;
            let terms = document.getElementById('terms').checked;

            // Name
            const namePattern = /^[a-zA-Z\s]+$/;
            if (name.trim().length === 0) {
                errors.push('Full Name is required.');
            } else if (!namePattern.test(name)) {
                errors.push('Full Name can only contain letters and spaces.');
            }

            // Email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.trim().length === 0) {
                errors.push('Email address is required.');
            } else if (!emailPattern.test(email)) {
                errors.push('Email address format is invalid.');
            }

            // Password
            let password_criteria = [];
            if (password.length === 0) {
                errors.push('Password is required.');
            } else {
                if (password.length < 8) password_criteria.push("8+ characters");
                if (!/[A-Z]/.test(password)) password_criteria.push("1 uppercase letter");
                if (!/[a-z]/.test(password)) password_criteria.push("1 lowercase letter");
                if (!/[0-9]/.test(password)) password_criteria.push("1 number");
                if (!/[!@#$%^&*(),.?\":{}|]/.test(password)) password_criteria.push("1 special character");

                if (password_criteria.length > 0) {
                    errors.push('Password missing: ' + password_criteria.join(', '));
                }
            }

            // Terms Checkbox
            if (!terms) {
                errors.push('You must accept the Terms of Service.');
            }

            // Show Modal if errors exist
            if (errors.length > 0) {
                showErrorModal(errors);
                return false;
            }

            return true;
        }

        function showErrorModal(errors) {
            const modal = document.getElementById('errorModal');
            const card = document.getElementById('errorModalCard');
            const list = document.getElementById('errorList');

            list.innerHTML = errors.map(err => `
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-[16px] text-red-400 mt-0.5">error</span>
                    <span>${err}</span>
                </li>
            `).join('');

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95');
            }, 10);
        }

        function closeErrorModal() {
            const modal = document.getElementById('errorModal');
            const card = document.getElementById('errorModalCard');

            modal.classList.add('opacity-0');
            card.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function handleOTPNavigation(event) {
            if (event) event.preventDefault();

            // 1. Validate inputs client-side
            if (validateRegistrationForm()) {
                // 2. Submit form to register_backend.php via POST
                document.getElementById('registerForm').submit();
            }
        }
    </script>
</body>
</html>