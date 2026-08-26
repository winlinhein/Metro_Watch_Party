<div x-show="showPremiumModal"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-8"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-8"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
     style="display: none;">

    <!-- Premium Activation Container -->
    <div class="relative w-full max-w-4xl mx-auto min-h-[600px] flex items-center justify-center p-6"
         @click.outside="if(!isActivating) showPremiumModal = false">

        <!-- Close Button -->
        <button @click="showPremiumModal = false"
                class="absolute top-4 right-4 z-[60] w-10 h-10 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full flex items-center justify-center text-white/70 hover:text-white transition-all backdrop-blur-md">
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
                    <!-- Feature items unchanged -->
                </ul>

                <button @click="activatePremium()" :disabled="isActivating"
                        class="premium-btn relative group inline-flex items-center justify-center gap-3 bg-white text-black px-10 py-4 rounded-2xl font-black text-lg tracking-wider uppercase transition-all duration-300 hover:scale-105 hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-indigo-500 opacity-0 group-hover:opacity-10 transition-opacity duration-500"></div>
                    <span class="relative z-10">Activate Premium Plan</span>
                    <span class="material-symbols-outlined relative z-10">bolt</span>
                </button>
            </div>

            <!-- Loading State -->
            <div class="premium-loader absolute inset-0 flex flex-col items-center justify-center opacity-0 scale-0 pointer-events-none">
                <!-- spinner unchanged -->
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
                    <button @click="showPremiumModal = false"
                            class="px-8 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold tracking-wider transition-all duration-300 hover:shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                        Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>