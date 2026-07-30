<!-- Sessions View (Rooms) -->
<div data-tab-panel="rooms" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full">
    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Active Sessions</h2>
            
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 pb-10">
        <template x-for="room in rooms" :key="room.id">
            <div :id="'room-card-' + room.id" class="glass-card rounded-2xl p-6 relative group overflow-hidden border border-white/5 hover:border-indigo-500/30 transition-all stagger-item">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#030305] to-[#1a1a24] border border-white/10 flex items-center justify-center group-hover:border-indigo-500/50 transition-colors shadow-lg">
                            <span class="material-symbols-outlined text-3xl text-indigo-400 group-hover:text-indigo-300 drop-shadow-[0_0_10px_rgba(99,102,241,0.5)]">satellite_alt</span>
                        </div>
                        <div class="flex items-center gap-2 bg-green-500/10 border border-green-500/20 px-3 py-1 rounded-full">
                            <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                            <span class="text-xs font-bold text-green-400 uppercase tracking-wider">Live</span>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-bold text-white mb-1 group-hover:text-indigo-300 transition-colors" x-text="room.name"></h3>
                    <p class="text-sm text-white/40 mb-6 mono flex items-center gap-2">
                        <span class="material-symbols-outlined text-[14px]">account_circle</span>
                        Host: <span class="text-white/70" x-text="room.host"></span>
                    </p>
                    
                    <div class="flex items-center justify-between mt-auto pt-6 border-t border-white/10">
                        <div class="flex items-center gap-2 text-white/60">
                            <span class="material-symbols-outlined">group</span>
                            <span class="font-bold text-white" x-text="room.users"></span>
                            <span class="text-xs uppercase tracking-wider">Viewers</span>
                        </div>
                        
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0">
                            <button @click="viewRoom(room)" class="w-10 h-10 rounded-full bg-indigo-500/20 hover:bg-indigo-500/40 border border-indigo-500/30 flex items-center justify-center text-indigo-300 hover:text-white transition-colors" title="View Users">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </button>
                            <button @click="disbandRoom(room.id)" class="w-10 h-10 rounded-full bg-red-500/20 hover:bg-red-500/40 border border-red-500/30 flex items-center justify-center text-red-400 hover:text-white transition-colors" title="Disband Room">
                                <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="rooms.length === 0">
            <div class="col-span-full glass-card rounded-2xl p-16 flex flex-col items-center justify-center text-center border border-white/5">
                <span class="material-symbols-outlined text-6xl text-white/20 mb-4">satellite_alt</span>
                <h3 class="text-xl font-bold text-white mb-2">No Active Sessions</h3>
                <p class="text-white/40 max-w-sm">There are currently no active watch parties or rooms. Wait for users to create new sessions.</p>
            </div>
        </template>
    </div>

    <!-- Room Details Modal -->
    <div x-show="roomModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 backdrop-blur-none" x-transition:enter-end="opacity-100 backdrop-blur-sm" x-transition:leave="transition ease-in duration-700" x-transition:leave-start="opacity-100 backdrop-blur-sm" x-transition:leave-end="opacity-0 backdrop-blur-none">
        <div class="glass-card rounded-2xl p-8 max-w-4xl w-full relative max-h-[85vh] overflow-hidden flex flex-col border border-white/10 shadow-2xl" @click.away="roomModalOpen = false"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-700" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95">
            
            <button @click="roomModalOpen = false" class="absolute top-6 right-6 w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-colors z-10 border border-white/10">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
            
            <div class="flex items-center gap-4 mb-8 pr-12">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 shrink-0">
                    <span class="material-symbols-outlined text-3xl text-white">live_tv</span>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-2xl font-bold text-white tracking-tight" x-text="selectedRoom?.name"></h3>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30 flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div> Live</span>
                    </div>
                    <p class="text-white/40 text-sm mono">
                        Host: <span class="text-white/70" x-text="selectedRoom?.host"></span> &bull; 
                        <span class="text-indigo-400" x-text="selectedRoom?.users + ' Total Viewers'"></span>
                    </p>
                </div>
            </div>
            
            <div class="flex justify-between items-end border-b border-white/10 pb-4 mb-6">
                <h4 class="text-lg font-bold text-white">Active Participants</h4>
                <p class="text-xs text-white/40 uppercase tracking-wider font-semibold">Showing recent joins</p>
            </div>
            
            <div class="overflow-y-auto flex-1 pr-2 -mr-2 space-y-3 custom-scrollbar">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(user, index) in mockRoomUsers" :key="user.id">
                        <div class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 flex items-center gap-4 transition-colors group"
                             x-data="{ show: false }" x-init="setTimeout(() => show = true, index * 50)" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="relative">
                                <img :src="user.avatar" class="w-10 h-10 rounded-full object-cover border border-white/20 group-hover:border-white/40 transition-colors">
                                <template x-if="user.isHost">
                                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-[#030305] flex items-center justify-center" title="Host">
                                        <span class="material-symbols-outlined text-[10px] text-white">star</span>
                                    </div>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h5 class="text-sm font-bold text-white truncate" x-text="user.name"></h5>
                                <p class="text-[10px] text-white/40 font-mono" x-text="user.isHost ? 'Session Host' : 'Viewer'"></p>
                            </div>
                            <button class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-colors shrink-0">
                                <span class="material-symbols-outlined text-[16px]">more_vert</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            
            <div class="mt-8 pt-6 border-t border-white/10 flex justify-between items-center">
                <button @click="disbandRoom(selectedRoom?.id)" class="px-5 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-xl text-sm font-bold transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">warning</span> Disband Room
                </button>
                <button @click="roomModalOpen = false" class="px-6 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-sm font-bold transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
