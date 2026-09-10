<!-- Fixed positioning to keep it floating at the bottom right -->
<div class="fixed bottom-8 right-8 z-[100]" x-show="!showMovieDetailModal" x-transition>
    <button @click="createParty()"
            style="cursor: none !important"
            class="group relative flex items-center justify-center gap-2 bg-nexus-red hover:bg-red-400 text-white px-6 py-4 rounded-full font-bold shadow-[0_0_20px_rgba(239,68,68,0.4)] hover:shadow-[0_0_40px_rgba(239,68,68,0.6)] transition-all duration-300 hover:-translate-y-1 overflow-hidden">
        <span class="material-symbols-outlined text-[24px] group-hover:rotate-90 transition-transform duration-300">add</span>
        <span class="tracking-wide">Host Party</span>
        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out rounded-full pointer-events-none"></div>
    </button>
    <div class="absolute inset-0 bg-red-500 rounded-full animate-ping opacity-20 -z-10 pointer-events-none" style="animation-duration: 2s;"></div>
</div>
