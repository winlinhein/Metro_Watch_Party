<!-- Notifications Button & Panel Component -->
<div class="relative z-[60]" x-data>
    <button @click="showNotifications = !showNotifications; if(showNotifications) { fetchNotifications(); markNotificationsAsRead(); }" 
            class="relative w-10 h-10 rounded-xl bg-gradient-to-br from-red-500/20 to-purple-600/20 hover:from-red-500/40 hover:to-purple-600/40 border border-red-500/30 flex items-center justify-center transition-all duration-300 group shadow-[0_0_15px_rgba(239,68,68,0.3)] hover:shadow-[0_0_30px_rgba(239,68,68,0.6)] hover:scale-110 active:scale-95">
        <span class="absolute inset-0 rounded-xl bg-white/5 group-hover:bg-transparent transition-colors"></span>
        <span class="material-symbols-outlined text-white/90 group-hover:text-white transition-colors relative z-10 group-hover:animate-bounce">notifications</span>
        
        <!-- Animated Notification Badge Indicator -->
        <template x-if="unreadNotifCount > 0">
            <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 border border-white/50 text-[9px] font-bold text-white items-center justify-center" x-text="unreadNotifCount"></span>
            </span>
        </template>
    </button>

    <!-- Notifications Dropdown Panel -->
    <div x-show="showNotifications"
         @click.outside="showNotifications = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="absolute right-0 top-full mt-4 w-[380px] bg-[#050508]/95 backdrop-blur-3xl border border-red-500/30 rounded-2xl shadow-[0_20px_60px_-15px_rgba(239,68,68,0.4)] p-4 z-50 overflow-hidden">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 border-b border-white/10 pb-3 gap-2 sm:gap-0">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-500 text-[20px] animate-pulse">notifications_active</span>
                    Notifications
                </h3>
                <span class="px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-[9px] text-white/50 uppercase font-mono tracking-widest" x-text="notifications.length + ' Total'"></span>
            </div>
            
            <button @click="clearAllNotifications()" x-show="notifications.length > 0" class="group relative overflow-hidden px-3 py-1.5 rounded-lg border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 transition-all duration-300">
                <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
                <div class="flex items-center gap-1.5 relative z-10">
                    <span class="material-symbols-outlined text-[14px] text-red-400 group-hover:rotate-12 transition-transform duration-300">delete_sweep</span>
                    <span class="text-[10px] font-bold text-red-400 uppercase tracking-widest">Clear All</span>
                </div>
            </button>
        </div>

        <div class="space-y-3 max-h-[320px] overflow-y-auto custom-scrollbar pr-1">
            <template x-for="notif in notifications" :key="notif.id">
                <div class="p-3 rounded-xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/5 transition-all flex gap-3 items-start">
                    
                    <!-- Icon based on type -->
                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                         :class="{
                             'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': notif.type === 'friend_request' || notif.type === 'friend_accepted',
                             'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30': notif.type === 'party_invite',
                             'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': notif.type === 'quest'
                         }">
                        <span class="material-symbols-outlined text-[18px]" 
                              x-text="notif.type === 'friend_request' ? 'person_add' : (notif.type === 'friend_accepted' ? 'how_to_reg' : 'notifications')"></span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-white/90 leading-snug">
                            <span class="font-bold text-white" x-text="notif.sender_name"></span>
                            <span x-text="' ' + notif.message"></span>
                        </p>
                        
                        <!-- Action buttons for incoming friend requests -->
                        <template x-if="notif.type === 'friend_request'">
                            <div class="flex items-center gap-2 mt-2">
                                <button @click="respondToFriendRequest(notif.sender_id, 'accept')" 
                                        class="px-3 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase transition-all">
                                    Add Back
                                </button>
                                <button @click="respondToFriendRequest(notif.sender_id, 'decline')" 
                                        class="px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white/50 hover:text-white text-[10px] font-bold uppercase transition-all">
                                    Decline
                                </button>
                            </div>
                        </template>

                        <p class="text-[9px] text-white/40 uppercase tracking-widest mt-1.5 font-mono" x-text="notif.created_at"></p>
                    </div>
                </div>
            </template>

            <div x-show="notifications.length === 0" class="py-8 text-center text-xs text-white/40">
                No notifications yet.
            </div>
        </div>
    </div>
</div>