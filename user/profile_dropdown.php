<!-- User Profile Context Dropdown Menu -->
<div x-show="activeDropdown !== null" 
     class="fixed z-[1000] w-48 bg-[#0a0a0f]/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-[0_15px_40px_rgba(0,0,0,0.8)] p-1.5 flex flex-col gap-1 cursor-default"
     :style="`top: ${dropdownY}px; left: ${dropdownX}px; display: none;`"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
     x-transition:leave-end="opacity-0 scale-95 translate-y-1"
     @click.outside="closeDropdown()"
     @click.stop>
     
    <!-- Header info inside dropdown -->
    <div class="px-2 pb-2 pt-1 border-b border-white/5 mb-1 flex items-center gap-2">
        <template x-if="selectedProfileUser">
            <div class="relative w-6 h-6 shrink-0 overflow-visible" style="width: 1.5rem; height: 1.5rem;">
                <div class="absolute inset-0 z-0 overflow-hidden rounded-md scale-[1.05]">
                    <img :src="resolveAvatarUrl(selectedProfileUser?.avatar_url, selectedProfileUser?.user_name || 'User')" class="absolute inset-0 h-full w-full object-cover">
                </div>
                <template x-if="selectedProfileUser?.border_preview">
                    <img :src="selectedProfileUser.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                </template>
            </div>
        </template>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate" x-text="selectedProfileUser?.user_name || 'User'"></p>
            <p class="text-[9px] font-mono text-white/40 truncate">ID: <span x-text="selectedProfileUser?.user_id || selectedProfileUser?.friend_id"></span></p>
        </div>
    </div>

    <!-- Conditional Actions -->
    <template x-if="selectedProfileUser && getFriendStatus(selectedProfileUser) === 'friend'">
        <button @click="unfriendUser()" class="w-full text-left px-2.5 py-2 text-xs text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-lg flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-[16px]">person_remove</span> Unfriend
        </button>
    </template>
    
    <button @click="openReportModal()" class="w-full text-left px-2.5 py-2 text-xs text-white/60 hover:bg-white/10 hover:text-white rounded-lg flex items-center gap-2 transition-colors">
        <span class="material-symbols-outlined text-[16px]">flag</span> Report User
    </button>
</div>
