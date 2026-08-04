<!-- Notifications Button -->
<div class="relative z-[60]" x-data="{ showNotifications: false }" @click.outside="showNotifications = false">
    <button @click="showNotifications = !showNotifications; if(showNotifications) { $nextTick(() => { typeof gsap !== 'undefined' && gsap.fromTo($refs.notifPanel, { opacity: 0, scale: 0.8, y: -20, rotationX: -15, transformPerspective: 1000 }, { opacity: 1, scale: 1, y: 0, rotationX: 0, duration: 0.6, ease: 'back.out(2)' }); typeof gsap !== 'undefined' && gsap.fromTo($refs.notifPanel.querySelectorAll('.notif-item'), { opacity: 0, x: 50 }, { opacity: 1, x: 0, duration: 0.5, stagger: 0.1, ease: 'power3.out', delay: 0.1 }) }) }" class="relative w-10 h-10 rounded-xl bg-gradient-to-br from-red-500/20 to-purple-600/20 hover:from-red-500/40 hover:to-purple-600/40 border border-red-500/30 flex items-center justify-center transition-all duration-300 group shadow-[0_0_15px_rgba(239,68,68,0.3)] hover:shadow-[0_0_30px_rgba(239,68,68,0.6)] hover:scale-110 active:scale-95">
        <span class="absolute inset-0 rounded-xl bg-white/5 group-hover:bg-transparent transition-colors"></span>
        <span class="material-symbols-outlined text-white/90 group-hover:text-white transition-colors relative z-10 group-hover:animate-bounce">notifications</span>
        
        <!-- Insane Glowing Ping -->
        <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 border border-white/50 shadow-[0_0_10px_#ef4444]"></span>
        </span>
    </button>

    <!-- Notifications Panel -->
    <div x-show="showNotifications"
         x-ref="notifPanel"
         class="absolute right-0 top-full mt-4 w-[380px] bg-[#050508]/95 backdrop-blur-3xl border border-red-500/30 rounded-2xl shadow-[0_20px_60px_-15px_rgba(239,68,68,0.4)] p-4 z-50 overflow-hidden" 
         style="display: none;">
        
        <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-white/[0.02] to-transparent pointer-events-none"></div>

        <div class="flex items-center justify-between mb-4 relative z-10 border-b border-white/10 pb-3">
            <h3 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2 drop-shadow-[0_0_5px_rgba(255,255,255,0.5)]">
                <span class="material-symbols-outlined text-red-500 text-[20px] animate-pulse">notifications_active</span>
                Notifications
            </h3>
            <button class="text-[10px] text-white/50 hover:text-red-400 uppercase font-black tracking-widest transition-colors hover:scale-105">Clear All</button>
        </div>

        <div class="space-y-3 max-h-[320px] overflow-y-auto overflow-x-hidden custom-scrollbar relative z-10 pr-2">
            <!-- Notification Item 1 -->
            <div class="notif-item group relative p-4 rounded-xl bg-gradient-to-r from-white/[0.03] to-white/[0.01] hover:from-red-500/10 hover:to-purple-500/10 border border-white/5 hover:border-red-500/30 transition-all duration-300 cursor-pointer flex gap-4 overflow-hidden shadow-lg">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-red-500 to-purple-500 scale-y-0 group-hover:scale-y-100 transition-transform duration-300 origin-top"></div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center shrink-0 shadow-[0_0_15px_rgba(16,185,129,0.3)] group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-emerald-400 text-[18px]">person_add</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-white/90 leading-snug tracking-wide"><span class="font-black text-white">Alice</span> accepted your friend request.</p>
                    <p class="text-[10px] text-white/40 uppercase tracking-widest mt-1.5 font-bold mono">2 mins ago</p>
                </div>
            </div>

            <!-- Notification Item 2 -->
            <div class="notif-item group relative p-4 rounded-xl bg-gradient-to-r from-white/[0.03] to-white/[0.01] hover:from-red-500/10 hover:to-purple-500/10 border border-white/5 hover:border-indigo-500/30 transition-all duration-300 cursor-pointer flex gap-4 overflow-hidden shadow-lg">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-indigo-500 to-blue-500 scale-y-0 group-hover:scale-y-100 transition-transform duration-300 origin-top"></div>
                <div class="w-10 h-10 rounded-full bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center shrink-0 shadow-[0_0_15px_rgba(99,102,241,0.3)] group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-indigo-400 text-[18px]">play_arrow</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-white/90 leading-snug tracking-wide"><span class="font-black text-white">Bob</span> invited you to a watch party for <span class="font-black text-indigo-400 drop-shadow-[0_0_5px_rgba(99,102,241,0.5)]">Inception</span>.</p>
                    <div class="flex gap-2 mt-3">
                        <button class="px-4 py-1.5 rounded-lg bg-indigo-500 text-white hover:bg-indigo-400 text-[10px] font-black uppercase transition-all shadow-[0_0_10px_rgba(99,102,241,0.4)] hover:shadow-[0_0_20px_rgba(99,102,241,0.8)] hover:scale-105 active:scale-95">Join</button>
                        <button class="px-4 py-1.5 rounded-lg bg-white/5 text-white/50 hover:bg-white/10 hover:text-white text-[10px] font-black uppercase transition-all border border-white/10 hover:border-white/30 hover:scale-105 active:scale-95">Decline</button>
                    </div>
                    <p class="text-[10px] text-white/40 uppercase tracking-widest mt-2 font-bold mono">1 hour ago</p>
                </div>
            </div>
            
            <!-- Notification Item 3 -->
            <div class="notif-item group relative p-4 rounded-xl bg-gradient-to-r from-white/[0.03] to-white/[0.01] hover:from-red-500/10 hover:to-purple-500/10 border border-white/5 hover:border-yellow-500/30 transition-all duration-300 cursor-pointer flex gap-4 overflow-hidden shadow-lg">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-yellow-500 to-orange-500 scale-y-0 group-hover:scale-y-100 transition-transform duration-300 origin-top"></div>
                <div class="w-10 h-10 rounded-full bg-yellow-500/20 border border-yellow-500/40 flex items-center justify-center shrink-0 shadow-[0_0_15px_rgba(234,179,8,0.3)] group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-yellow-400 text-[18px]">stars</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-white/90 leading-snug tracking-wide">You earned <span class="font-black text-yellow-400 drop-shadow-[0_0_5px_rgba(234,179,8,0.5)]">50 XP</span> from the daily quest.</p>
                    <p class="text-[10px] text-white/40 uppercase tracking-widest mt-1.5 font-bold mono">3 hours ago</p>
                </div>
            </div>
        </div>
    </div>
</div>
