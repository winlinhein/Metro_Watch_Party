<div x-show="$data.showGuestLoginModal"
     class="fixed inset-0 z-[140] flex items-center justify-center p-4"
     style="display: none;">
    <div x-show="$data.showGuestLoginModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-black/80 backdrop-blur-sm"
         @click="$data.showGuestLoginModal = false"></div>

    <div x-show="$data.showGuestLoginModal"
         x-transition:enter="transition ease-out duration-400"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-md bg-[#0a0a0f] border border-white/10 rounded-3xl p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-red-500/10 via-transparent to-indigo-500/10 pointer-events-none"></div>

        <button type="button"
                @click="$data.showGuestLoginModal = false"
                class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>

        <div class="relative z-10 text-center">
            <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_30px_rgba(239,68,68,0.35)]">
                <span class="material-symbols-outlined text-white text-[28px]">lock</span>
            </div>
            <h3 class="text-2xl font-black text-white mb-2">Login required</h3>
            <p class="text-sm text-white/55 mb-8" x-text="$data.guestLoginMessage || 'Please login to continue'">Please login to continue</p>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="../frontend/login.php"
                   class="flex-1 py-3 rounded-xl font-bold text-sm bg-white text-black hover:bg-red-500 hover:text-white transition-all inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">login</span>
                    Login
                </a>
                <a href="../frontend/register.php"
                   class="flex-1 py-3 rounded-xl font-bold text-sm bg-white/5 hover:bg-white/10 border border-white/10 text-white transition-all inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Register
                </a>
            </div>
        </div>
    </div>
</div>
