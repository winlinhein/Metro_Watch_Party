<!-- Notifications Dropdown -->
<div x-show="notificationsOpen" 
     x-transition:enter="transition ease-out duration-300" 
     x-transition:enter-start="opacity-0 translate-y-4 scale-95" 
     x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
     x-transition:leave="transition ease-in duration-200" 
     x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
     x-transition:leave-end="opacity-0 translate-y-4 scale-95" 
     class="absolute right-0 top-14 mt-4 w-96 bg-[#0a0a0c] border border-white/10 rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.9)] z-50 transform origin-top-right overflow-hidden flex flex-col">
    <div class="flex justify-between items-center p-5 border-b border-white/5 bg-[#0a0a0c] relative z-10">
        <h3 class="text-white font-semibold tracking-wide flex items-center gap-2">
            Alerts 
            <span class="bg-white/10 text-white/50 text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="notifications.length + ' Total'"></span>
        </h3>
        <button type="button" @click.stop="clearAllNotifications()" x-show="notifications.length > 0" class="text-xs text-white/40 hover:text-red-400 transition-colors flex items-center gap-1 group">
            <span class="material-symbols-outlined text-[14px] group-hover:rotate-12 transition-transform">delete_sweep</span>
            Clear All
        </button>
    </div>
    
    <!-- Render Notifications Dynamically -->
    <div class="flex-1 overflow-y-auto max-h-[400px] p-2 space-y-1 bg-[#0a0a0c]">
        <template x-for="notif in notifications" :key="notif.id">
            <div class="flex gap-4 p-3 rounded-xl hover:bg-white/[0.04] transition-all duration-300 group relative overflow-hidden"
                 :class="Number(notif.is_read) === 0 ? 'bg-red-500/10' : 'opacity-80 hover:opacity-100'">
                <div class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity" :class="notif.gradientFrom"></div>
                <div class="relative w-10 h-10 shrink-0 overflow-visible" style="width: 2.5rem; height: 2.5rem;">
                    <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] flex items-center justify-center border transition-all group-hover:scale-110"
                         :class="notif.avatar_url ? 'border-white/10 bg-white/5' : [notif.bgClass || 'bg-white/5', notif.borderClass || 'border-white/10']">
                        <img x-show="notif.avatar_url" :src="resolveAvatarUrl ? resolveAvatarUrl(notif.avatar_url, notif.sender_name || 'User') : notif.avatar_url" class="absolute inset-0 h-full w-full object-cover" alt="">
                        <span x-show="!notif.avatar_url" class="material-symbols-outlined text-[18px] transition-colors" :class="[notif.iconColorClass || 'text-white/70']" x-text="notif.icon || 'notifications'"></span>
                    </div>
                    <template x-if="notif.border_preview">
                        <img :src="notif.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                    </template>
                </div>
                <div class="relative z-10 flex-1 min-w-0">
                    <p class="text-sm leading-snug transition-colors" :class="Number(notif.is_read) === 1 ? 'text-white/60 group-hover:text-white' : 'text-white/80 group-hover:text-white'">
                        <span class="font-bold text-white" x-show="notif.sender_name" x-text="notif.sender_name"></span>
                        <span x-text="(notif.sender_name ? ' ' : '') + (notif.message || '')"></span>
                    </p>
                    <span class="text-white/30 text-[10px] mono mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">schedule</span>
                        <span x-text="notif.time || notif.created_at"></span>
                    </span>
                </div>
                <div class="relative z-10 flex flex-col items-center gap-2 shrink-0">
                    <span x-show="Number(notif.is_read) === 0" class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_#ef4444]"></span>
                    <button type="button"
                            @click.stop="deleteNotification(notif.id)"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-white/30 hover:text-red-400 hover:bg-red-500/15 border border-transparent hover:border-red-500/30 transition-all"
                            title="Delete notification">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                    </button>
                </div>
            </div>
        </template>
        <div x-show="notifications.length === 0" class="py-10 text-center text-xs text-white/40">
            No notifications yet.
        </div>
    </div>
</div>
