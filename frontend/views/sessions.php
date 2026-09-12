<!-- Sessions View (Rooms) -->
<div data-tab-panel="sessions" style="display: none; padding-top: 7.5rem;" class="absolute inset-0 px-10 pb-10 w-full min-h-full overflow-y-auto">
    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Active Sessions</h2>
            <p class="text-white/40 text-sm">Live watch parties update in real time.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-green-500/10 border border-green-500/20 px-3 py-1.5 rounded-full">
                <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                <span class="text-xs font-bold text-green-400 uppercase tracking-wider" x-text="(rooms?.length || 0) + ' Live'"></span>
            </div>
            <button type="button" @click="fetchRooms()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 hover:text-white text-xs font-bold uppercase tracking-wider transition-colors">
                Refresh
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 pb-10">
        <template x-for="room in pagedRooms" :key="room.id">
            <div :id="'room-card-' + room.id" class="glass-card rounded-2xl overflow-hidden relative group border border-white/5 hover:border-indigo-500/30 transition-all stagger-item">
                <div class="relative h-40 overflow-hidden bg-[#0a0a12]">
                    <img x-show="room.movie_poster" :src="room.movie_poster" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="">
                    <div x-show="!room.movie_poster" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-indigo-500/10 to-purple-500/10">
                        <span class="material-symbols-outlined text-5xl text-white/20">movie</span>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-[#030305] via-black/40 to-transparent"></div>
                    <div class="absolute top-4 left-4 flex items-center gap-2 bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full border border-white/10">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div>
                        <span class="text-[10px] font-bold text-green-400 uppercase tracking-wider">Live</span>
                    </div>
                    <div class="absolute bottom-3 left-4 right-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-white/50 mb-0.5">Now watching</p>
                        <h3 class="text-lg font-bold text-white truncate" x-text="room.movie_title || 'No movie selected'"></h3>
                    </div>
                </div>

                <div class="relative z-10 p-5">
                    <p class="text-sm text-white/50 mb-4 flex items-center gap-2 min-w-0">
                        <span class="text-white/30 mono text-xs" x-text="room.name"></span>
                        <span class="text-white/20">·</span>
                        Hosted by
                        <span class="relative inline-flex w-6 h-6 overflow-visible shrink-0">
                            <span class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] border border-white/10">
                                <img :src="resolveAvatarUrl(room.host_avatar_url, room.host)" class="absolute inset-0 h-full w-full object-cover" alt="">
                            </span>
                            <template x-if="room.host_border_preview">
                                <img :src="room.host_border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                            </template>
                        </span>
                        <span class="text-white font-medium truncate" x-text="room.host"></span>
                    </p>

                    <div class="flex items-center justify-between pt-4 border-t border-white/10">
                        <div class="flex items-center">
                            <template x-for="(user, index) in (room.participants || []).slice(0, 5)" :key="(user.user_id || user.id) + '-' + index">
                                <div class="relative w-8 h-8 overflow-visible shrink-0" :class="index > 0 ? '-ml-2' : ''" :title="user.name">
                                    <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] bg-white/10 border border-[#030305]">
                                        <img :src="resolveAvatarUrl(user.avatar_url, user.name)" class="absolute inset-0 h-full w-full object-cover" alt="">
                                    </div>
                                    <template x-if="user.border_preview">
                                        <img :src="user.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                                    </template>
                                </div>
                            </template>
                            <div x-show="(room.users || 0) > 5" class="relative -ml-2 w-8 h-8 rounded-full bg-white/10 border border-[#030305] flex items-center justify-center text-[10px] font-bold text-white/70" x-text="'+' + ((room.users || 0) - 5)"></div>
                            <span class="ml-3 text-xs text-white/50" x-text="(room.users || 0) + ' online'"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="viewRoom(room)" class="w-10 h-10 rounded-full bg-indigo-500/20 hover:bg-indigo-500/40 border border-indigo-500/30 flex items-center justify-center text-indigo-300 hover:text-white transition-colors" title="View Users">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </button>
                            <button @click="disbandRoom(room.id)" class="w-10 h-10 rounded-full bg-red-500/20 hover:bg-red-500/40 border border-red-500/30 flex items-center justify-center text-red-400 hover:text-white transition-colors" title="Force close room">
                                <span class="material-symbols-outlined text-[18px]">cancel</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="adminPage('rooms').total === 0">
            <div class="col-span-full glass-card rounded-2xl p-16 flex flex-col items-center justify-center text-center border border-white/5">
                <span class="material-symbols-outlined text-6xl text-white/20 mb-4">satellite_alt</span>
                <h3 class="text-xl font-bold text-white mb-2" x-text="(searchQuery || '').trim() ? 'No matching sessions' : 'No Active Sessions'"></h3>
                <p class="text-white/40 max-w-sm" x-text="(searchQuery || '').trim() ? 'Try a different search in the header.' : 'There are currently no active watch parties or rooms. Wait for users to create new sessions.'"></p>
            </div>
        </template>
    </div>
    <?php $pagerKey = 'rooms'; include __DIR__ . '/../components/admin_pagination.php'; ?>

    <!-- Room Details Modal -->
    <div x-show="roomModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 backdrop-blur-none" x-transition:enter-end="opacity-100 backdrop-blur-sm" x-transition:leave="transition ease-in duration-700" x-transition:leave-start="opacity-100 backdrop-blur-sm" x-transition:leave-end="opacity-0 backdrop-blur-none">
        <div class="glass-card rounded-2xl p-8 max-w-4xl w-full relative max-h-[85vh] overflow-hidden flex flex-col border border-white/10 shadow-2xl" @click.away="roomModalOpen = false"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-700" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95">
            
            <button @click="roomModalOpen = false" class="absolute top-6 right-6 w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-colors z-10 border border-white/10">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
            
            <div class="flex items-center gap-4 mb-8 pr-12">
                <div class="w-16 h-20 rounded-xl overflow-hidden bg-gradient-to-br from-indigo-600 to-purple-600 shrink-0 border border-white/10">
                    <img x-show="selectedRoom?.movie_poster" :src="selectedRoom?.movie_poster" class="w-full h-full object-cover" alt="">
                    <div x-show="!selectedRoom?.movie_poster" class="w-full h-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-3xl text-white">live_tv</span>
                    </div>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-2xl font-bold text-white tracking-tight truncate" x-text="selectedRoom?.movie_title || selectedRoom?.name"></h3>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30 flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div> Live</span>
                    </div>
                    <p class="text-white/40 text-sm mono">
                        <span x-text="selectedRoom?.name"></span> &bull;
                        Host: <span class="text-white/70" x-text="selectedRoom?.host"></span> &bull;
                        <span class="text-indigo-400" x-text="(selectedRoom?.users || 0) + ' online'"></span>
                    </p>
                </div>
            </div>
            
            <div class="flex justify-between items-end border-b border-white/10 pb-4 mb-6">
                <h4 class="text-lg font-bold text-white">Active Participants</h4>
                <p class="text-xs text-white/40 uppercase tracking-wider font-semibold">Live members</p>
            </div>
            
            <div class="overflow-y-auto flex-1 pr-2 -mr-2 space-y-3 custom-scrollbar">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(user, index) in (selectedRoom?.participants || [])" :key="user.user_id || user.id || index">
                        <div class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 flex items-center gap-4 transition-colors group">
                            <div class="relative w-10 h-10 overflow-visible shrink-0">
                                <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.1] bg-white/10">
                                    <img :src="resolveAvatarUrl(user.avatar_url, user.name)" class="absolute inset-0 h-full w-full object-cover" alt="">
                                </div>
                                <template x-if="user.border_preview">
                                    <img :src="user.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none" alt="">
                                </template>
                                <template x-if="user.isHost">
                                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-[#030305] flex items-center justify-center z-20" title="Host">
                                        <span class="material-symbols-outlined text-[10px] text-white">star</span>
                                    </div>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h5 class="text-sm font-bold text-white truncate" x-text="user.name"></h5>
                                <p class="text-[10px] text-white/40 font-mono" x-text="user.isHost ? 'Session Host' : 'Viewer'"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="!selectedRoom?.participants || selectedRoom.participants.length === 0" class="col-span-full text-center text-white/40 py-8">
                        <span class="material-symbols-outlined text-3xl mb-2 opacity-50">group_off</span>
                        <p>No members are currently connected.</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 pt-6 border-t border-white/10 flex justify-between items-center">
                <button @click="disbandRoom(selectedRoom?.id)" class="px-5 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-xl text-sm font-bold transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">warning</span> Force Close Room
                </button>
                <button @click="roomModalOpen = false" class="px-6 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-sm font-bold transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
