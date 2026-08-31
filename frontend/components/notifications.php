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
            <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-show="unreadNotifications > 0" x-text="unreadNotifications + ' NEW'"></span>
        </h3>
        <button @click="markAllRead()" class="text-xs text-white/40 hover:text-red-400 transition-colors flex items-center gap-1 group">
            <span class="material-symbols-outlined text-[14px] group-hover:rotate-90 transition-transform">clear_all</span>
            Clear
        </button>
    </div>
    
    <!-- Render Notifications Dynamically -->
    <div class="flex-1 overflow-y-auto max-h-[400px] p-2 space-y-1 bg-[#0a0a0c]">
        <template x-for="notification in notifications" :key="notification.id">
            <div @click="notification.is_read = 1; notification.read = true" class="flex gap-4 p-3 rounded-xl hover:bg-white/[0.04] transition-all duration-300 cursor-pointer group relative overflow-hidden" :class="{'opacity-70 hover:opacity-100': Number(notification.is_read) === 1 || notification.read}">
                <div class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity" :class="notification.gradientFrom"></div>
                <div class="relative w-10 h-10 shrink-0 overflow-visible" style="width: 2.5rem; height: 2.5rem;">
                    <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] flex items-center justify-center border transition-all group-hover:scale-110"
                         :class="notification.avatar_url ? 'border-white/10 bg-white/5' : [notification.bgClass || 'bg-white/5', notification.borderClass || 'border-white/10']">
                        <img x-show="notification.avatar_url" :src="resolveAvatarUrl ? resolveAvatarUrl(notification.avatar_url, notification.sender_name || 'User') : notification.avatar_url" class="absolute inset-0 h-full w-full object-cover" alt="">
                        <span x-show="!notification.avatar_url" class="material-symbols-outlined text-[18px] transition-colors" :class="[notification.iconColorClass || 'text-white/70']" x-text="notification.icon || 'notifications'"></span>
                    </div>
                    <template x-if="notification.border_preview">
                        <img :src="notification.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                    </template>
                </div>
                <div class="relative z-10 flex-1">
                    <p class="text-sm leading-snug transition-colors" :class="(Number(notification.is_read) === 1 || notification.read) ? 'text-white/60 group-hover:text-white' : 'text-white/80 group-hover:text-white'">
                        <span class="font-bold text-white" x-show="notification.sender_name" x-text="notification.sender_name"></span>
                        <span x-text="(notification.sender_name ? ' ' : '') + (notification.message || '')"></span>
                    </p>
                    <span class="text-white/30 text-[10px] mono mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">schedule</span>
                        <span x-text="notification.time || notification.created_at"></span>
                    </span>
                </div>
                <template x-if="Number(notification.is_read) === 0 && !notification.read">
                    <div class="w-2 h-2 rounded-full mt-1 bg-red-500" :class="notification.indicatorClass"></div>
                </template>
            </div>
        </template>
    </div>
    
    <div class="p-3 bg-white/[0.02] border-t border-white/5 relative z-10">
        <button class="w-full py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/70 hover:text-white text-xs font-bold uppercase tracking-wider transition-all border border-transparent hover:border-white/10 flex items-center justify-center gap-2 group">
            View All Events
            <span class="material-symbols-outlined text-[14px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
        </button>
    </div>
</div>
