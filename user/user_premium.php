<div x-show="showPremiumModal"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-8"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-8"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
     style="display: none;"
     x-data="{
        isPremium: localStorage.getItem('nexus_premium') === 'true',
        isActivating: false,
        checkPremiumStatus() {
            // Check status if needed
        },
        async activatePremium() {
            this.isActivating = true;
            
            // GSAP Insane Animation Sequence
            const tl = gsap.timeline();
            
            // 1. Shrink and pulse the button
            tl.to('.premium-btn', { scale: 0.9, duration: 0.2 })
              .to('.premium-btn', { scale: 1.1, duration: 0.1, yoyo: true, repeat: 3 })
              .to('.premium-btn', { opacity: 0, scale: 0, duration: 0.4, ease: 'back.in(1.5)' });

            // 2. Hide features and show a loading ring
            tl.to('.premium-features', { opacity: 0, y: -20, duration: 0.3, stagger: 0.1 }, '-=0.4');
            tl.to('.premium-card', { boxShadow: '0 0 100px rgba(99,102,241,0)', duration: 0.5 }, '-=0.5');
            tl.fromTo('.premium-loader', { scale: 0, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.5, ease: 'elastic.out(1, 0.5)' });

            // Call Backend
            try {
                const res = await fetch('/user_backend/activate_premium.php', { method: 'POST' });
                const data = await res.json();
                
                if (res.ok || data.success) {
                    this.isPremium = true;
                    localStorage.setItem('nexus_premium', 'true');
                    
                    // Success Animations!
                    tl.to('.premium-loader', { scale: 0, opacity: 0, duration: 0.3, ease: 'back.in(1.5)' })
                      .fromTo('.premium-success-burst', { scale: 0, opacity: 1 }, { scale: 5, opacity: 0, duration: 1, ease: 'power3.out' })
                      .fromTo('.premium-success-icon', { scale: 0, rotation: -180 }, { scale: 1, rotation: 0, duration: 0.8, ease: 'elastic.out(1, 0.3)' }, '-=0.8')
                      .to('.premium-card', { boxShadow: '0 0 120px rgba(99,102,241,0.6)', borderColor: 'rgba(99,102,241,0.8)', duration: 1 }, '-=0.5')
                      .fromTo('.premium-welcome-text', { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.5 }, '-=0.3');
                      
                    if (window.showToast) window.showToast('Nexus Premium Activated!', 'success');
                } else {
                    if (window.showToast) window.showToast(data.error || 'Failed to activate', 'error');
                    // Revert UI on fail
                    gsap.to('.premium-loader', { opacity: 0, scale: 0, duration: 0.3 });
                    gsap.to('.premium-btn', { opacity: 1, scale: 1, duration: 0.3, delay: 0.3 });
                    gsap.to('.premium-features', { opacity: 1, y: 0, duration: 0.3, stagger: 0.1, delay: 0.3 });
                }
            } catch (err) {
                if (window.showToast) window.showToast('Network error', 'error');
                gsap.to('.premium-loader', { opacity: 0, scale: 0, duration: 0.3 });
                gsap.to('.premium-btn', { opacity: 1, scale: 1, duration: 0.3, delay: 0.3 });
                gsap.to('.premium-features', { opacity: 1, y: 0, duration: 0.3, stagger: 0.1, delay: 0.3 });
            }
            this.isActivating = false;
        },
        initPremium() {
            if (this.isPremium) return;
            gsap.fromTo('.premium-badge', { y: -20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: 'elastic.out(1, 0.5)', delay: 0.2 });
            gsap.fromTo('.premium-title', { y: 20, opacity: 0, scale: 0.9 }, { y: 0, opacity: 1, scale: 1, duration: 0.8, ease: 'power3.out', delay: 0.3 });
            gsap.fromTo('.premium-desc', { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: 'power3.out', delay: 0.5 });
            gsap.fromTo('.premium-features li', { x: -30, opacity: 0, scale: 0.9 }, { x: 0, opacity: 1, scale: 1, duration: 0.6, stagger: 0.1, ease: 'back.out(1.2)', delay: 0.7 });
            gsap.fromTo('.premium-btn', { scale: 0, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.8, ease: 'elastic.out(1, 0.4)', delay: 1.2 });
            
            // Continuous blob animations
            gsap.to('.premium-blob-1', { x: 'random(-100, 100)', y: 'random(-50, 50)', rotation: 'random(-20, 20)', duration: 4, repeat: -1, yoyo: true, ease: 'sine.inOut' });
            gsap.to('.premium-blob-2', { x: 'random(-100, 100)', y: 'random(-50, 50)', rotation: 'random(-20, 20)', duration: 5, repeat: -1, yoyo: true, ease: 'sine.inOut' });
            gsap.to('.premium-blob-3', { x: 'random(-100, 100)', y: 'random(-50, 50)', duration: 6, repeat: -1, yoyo: true, ease: 'sine.inOut' });
        }
    }"
    x-init="
        $watch('showPremiumModal', value => {
            if (value) {
                initPremium();
            }
        })
    ">
    
    <!-- Premium Activation Container -->
    <div class="relative w-full max-w-4xl mx-auto min-h-[600px] flex items-center justify-center p-6" @click.outside="if(!isActivating) showPremiumModal = false">
        
        <!-- Close Button -->
        <button @click="showPremiumModal = false" class="absolute top-4 right-4 z-[60] w-10 h-10 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full flex items-center justify-center text-white/70 hover:text-white transition-all backdrop-blur-md">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
        
        <!-- Glowing background blobs -->
        <div class="premium-blob-1 absolute top-1/2 left-1/3 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[400px] md:w-[600px] md:h-[600px] bg-indigo-600/30 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="premium-blob-2 absolute top-1/2 right-1/3 translate-x-1/4 -translate-y-1/3 w-[250px] h-[350px] md:w-[500px] md:h-[500px] bg-fuchsia-600/30 rounded-full blur-[100px] pointer-events-none mix-blend-screen"></div>
        <div class="premium-blob-3 absolute bottom-0 left-1/2 -translate-x-1/2 w-[400px] h-[200px] bg-amber-500/20 rounded-full blur-[120px] pointer-events-none mix-blend-screen"></div>

        <!-- Glassmorphism Card -->
        <div class="premium-card relative z-10 w-full bg-[#0a0a0f]/60 backdrop-blur-3xl border border-indigo-500/20 rounded-[2.5rem] p-8 md:p-14 overflow-hidden shadow-[0_0_80px_rgba(79,70,229,0.15)] text-center transition-all duration-700">
            
            <div x-show="!isPremium">
                <div class="premium-badge inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-indigo-500/20 to-fuchsia-500/20 border border-indigo-500/30 text-indigo-300 text-sm font-bold tracking-widest uppercase mb-6 shadow-[0_0_15px_rgba(99,102,241,0.3)]">
                    <span class="material-symbols-outlined text-[16px]">stars</span>
                    Unlock Nexus Premium
                </div>
                
                <h1 class="premium-title text-5xl md:text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-fuchsia-400 to-indigo-400 mb-4 drop-shadow-lg">
                    Ascend Your Reality
                </h1>
                
                <p class="premium-desc text-white/60 text-lg max-w-xl mx-auto mb-10">
                    Experience movies in ultimate fidelity, unlock exclusive profile customization, host endless watch parties, and rule the Nexus without limits.
                </p>
                
                <ul class="premium-features grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto mb-12 text-left">
                    <li class="bg-white/5 border border-white/10 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-indigo-400">4k</span>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm">Ultra HD Streaming</h4>
                            <p class="text-white/50 text-xs">Uncompressed 4K visuals</p>
                        </div>
                    </li>
                    <li class="bg-white/5 border border-white/10 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-fuchsia-500/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-fuchsia-400">group_add</span>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm">Unlimited Parties</h4>
                            <p class="text-white/50 text-xs">Host rooms with up to 100 people</p>
                        </div>
                    </li>
                    <li class="bg-white/5 border border-white/10 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-amber-400">workspace_premium</span>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm">Exclusive Avatars</h4>
                            <p class="text-white/50 text-xs">Premium borders and badges</p>
                        </div>
                    </li>
                    <li class="bg-white/5 border border-white/10 rounded-xl p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-emerald-400">block</span>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm">Ad-Free Experience</h4>
                            <p class="text-white/50 text-xs">Zero interruptions forever</p>
                        </div>
                    </li>
                </ul>
                
                <button @click="activatePremium()" :disabled="isActivating" class="premium-btn relative group inline-flex items-center justify-center gap-3 bg-white text-black px-10 py-4 rounded-2xl font-black text-lg tracking-wider uppercase transition-all duration-300 hover:scale-105 hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-indigo-500 opacity-0 group-hover:opacity-10 transition-opacity duration-500"></div>
                    <span class="relative z-10">Activate Premium Plan</span>
                    <span class="material-symbols-outlined relative z-10">bolt</span>
                </button>
            </div>
            
            <!-- Loading State -->
            <div class="premium-loader absolute inset-0 flex flex-col items-center justify-center opacity-0 scale-0 pointer-events-none">
                <div class="relative w-24 h-24">
                    <svg class="animate-spin w-full h-full text-indigo-500/50" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="283" stroke-dashoffset="75"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-fuchsia-400 text-3xl animate-pulse">diamond</span>
                    </div>
                </div>
                <p class="mt-6 text-white/50 font-mono tracking-widest text-sm animate-pulse">PROCESSING ACTIVATION...</p>
            </div>

            <!-- Success State -->
            <div x-show="isPremium" class="absolute inset-0 flex flex-col items-center justify-center p-10 bg-[#0a0a0f]">
                <div class="relative">
                    <div class="premium-success-burst absolute inset-0 rounded-full border-[10px] border-indigo-500 opacity-0 pointer-events-none"></div>
                    <div class="premium-success-icon w-32 h-32 rounded-full bg-gradient-to-br from-indigo-500 to-fuchsia-500 flex items-center justify-center shadow-[0_0_80px_rgba(99,102,241,0.6)] mb-8 opacity-0">
                        <span class="material-symbols-outlined text-white text-6xl">verified</span>
                    </div>
                </div>
                <div class="premium-welcome-text opacity-0 text-center">
                    <h2 class="text-4xl font-black text-white mb-3">Welcome to Premium</h2>
                    <p class="text-indigo-300 text-lg mb-8">Your account has been upgraded successfully.</p>
                    <button @click="showPremiumModal = false" class="px-8 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold tracking-wider transition-all duration-300 hover:shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                        Close
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>
