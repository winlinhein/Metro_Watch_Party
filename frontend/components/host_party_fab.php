<!-- Fixed positioning to keep it floating at the bottom right -->
<div class="fixed bottom-8 right-8 z-40"
     x-show="!showMovieDetailModal && !showFriendsPanel && !showQuestsPanel && !showChatPanel && !showInviteModal && !showRechargeModal"
     x-transition>
    <button @click="hasActiveRoom ? returnToRoom() : (isGuest ? requireLogin() : createParty())"
            class="nexus-fab group relative flex items-center justify-center gap-2 text-white px-6 py-4 rounded-full font-bold overflow-hidden"
            :class="hasActiveRoom
                ? 'bg-indigo-500 hover:bg-indigo-400 shadow-[0_0_20px_rgba(99,102,241,0.4)] hover:shadow-[0_0_40px_rgba(99,102,241,0.6)]'
                : 'bg-nexus-red hover:bg-red-400 shadow-[0_0_20px_rgba(239,68,68,0.4)] hover:shadow-[0_0_40px_rgba(239,68,68,0.6)]'">
        <span class="nexus-fab-orbit"></span>
        <span class="material-symbols-outlined text-[24px] relative z-10 transition-transform duration-500 group-hover:scale-110"
              :class="hasActiveRoom ? '' : 'group-hover:rotate-90'"
              x-text="hasActiveRoom ? 'arrow_back' : 'add'"></span>
        <span class="tracking-wide relative z-10" x-text="hasActiveRoom ? 'Back to room' : 'Host Party'"></span>
        <span class="nexus-fab-sheen"></span>
    </button>
    <div class="absolute inset-0 bg-red-500 rounded-full animate-ping opacity-20 -z-10 pointer-events-none" style="animation-duration: 2s;"></div>
</div>
