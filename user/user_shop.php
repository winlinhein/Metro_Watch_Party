<!-- Shop Tab -->
<div class="absolute inset-0 w-full h-full overflow-y-auto p-5 md:p-6 tab-content scroll-smooth custom-scrollbar" 
     x-show="currentTab === 'shop'"
     x-transition:enter="transition-all duration-500 delay-300 cubic-bezier(0.34, 1.56, 0.64, 1)"
     x-transition:enter-start="opacity-0 translate-y-8"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition-all duration-300 ease-in"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-8 absolute w-full"
     style="display: none;">
    <div class="max-w-[1400px] mx-auto space-y-5 pb-16">
        
        <!-- Header & Points -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-4 relative z-10">
            <div>
                <h2 class="text-2xl md:text-3xl font-black tracking-tighter text-white flex items-center gap-3 mb-1.5 uppercase drop-shadow-[0_0_15px_rgba(255,255,255,0.3)]">
                    <div class="w-10 h-10 bg-gradient-to-tr from-violet-600 to-fuchsia-600 rounded-xl flex items-center justify-center shadow-[0_0_40px_rgba(139,92,246,0.5)] border border-white/20">
                        <span class="material-symbols-outlined text-[22px] text-white">storefront</span>
                    </div>
                    Point Shop
                </h2>
                <p class="text-white/50 text-xs max-w-xl font-medium tracking-wide">Redeem your hard-earned points for exclusive profile cosmetics, avatars, and special badges to stand out in the network.</p>
            </div>

            <div class="flex items-center gap-3 bg-black/40 backdrop-blur-xl border border-white/10 rounded-xl p-3 shadow-xl">
                <div class="w-9 h-9 rounded-lg bg-yellow-500/20 border border-yellow-500/30 flex items-center justify-center">
                    <span class="material-symbols-outlined text-yellow-400 text-xl">toll</span>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest text-white/50 font-bold mb-0.5">Your Balance</p>
                    <p class="text-xl font-black text-yellow-400 tracking-tighter" x-ref="pointsDisplay">
                        <span x-text="userPoints.toLocaleString()"></span> 
                        <span class="text-xs font-bold text-yellow-500/50">PTS</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Items Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 relative z-10"
             x-transition:enter="transition-all duration-500"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <template x-for="item in shopItems" :key="item.id">
                <div class="group relative bg-[#050508] rounded-2xl border border-white/[0.05] p-3.5 hover:border-white/20 transition-all duration-500 hover:-translate-y-1 shadow-xl overflow-hidden cursor-pointer"
                     @click="!userInventory.includes(item.id) ? (selectedItem = item, showConfirmModal = true) : null">
                    
                    <!-- Background Glow (generic) -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 opacity-0 group-hover:opacity-20 transition-opacity duration-700 pointer-events-none"></div>
                    
                    <!-- Visual Asset -->
                    <div class="w-full aspect-square rounded-xl mb-3 relative bg-black/50 border border-white/5 flex items-center justify-center"
                         :class="item.category === 'border' ? '' : 'overflow-hidden'">
                        <!-- Animated border preview (ring around an avatar) -->
                        <template x-if="item.category === 'border' && item.image">
                            <div class="absolute inset-0">
                                <!-- Width + padding-bottom both % of parent WIDTH, so the hole stays 1:1 -->
                                <div class="absolute left-1/2 top-1/2 w-[64%] pb-[64%] -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-full bg-[#111116] shadow-inner">
                                    <img :src="selectedAvatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(savedProfile.username || 'User') + '&background=ef4444&color=fff&bold=true'" class="absolute inset-0 h-full w-full object-cover" style="object-fit: cover;">
                                </div>
                                <img :src="item.image" :alt="item.name" class="absolute left-1/2 top-1/2 w-[80%] -translate-x-1/2 -translate-y-1/2 object-contain pointer-events-none mix-blend-screen drop-shadow-[0_0_18px_rgba(255,255,255,0.18)] group-hover:scale-110 transition-transform duration-700" style="aspect-ratio: 1 / 1; height: auto;">
                            </div>
                        </template>
                        <!-- Image (avatars / other categories) -->
                        <template x-if="item.category !== 'border' && item.image">
                            <img :src="item.image" :alt="item.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        </template>
                        <!-- No image fallback -->
                        <template x-if="!item.image">
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br opacity-80">
                                <span class="material-symbols-outlined text-[48px] text-white/50" x-text="item.category === 'badge' ? 'workspace_premium' : 'account_circle'"></span>
                            </div>
                        </template>
                        
                        <!-- Purchased Overlay -->
                        <div x-show="userInventory.includes(item.id)" class="absolute inset-0 bg-black/80 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-500 flex items-center justify-center mb-1 shadow-[0_0_30px_rgba(16,185,129,0.5)]">
                                <span class="material-symbols-outlined text-xl text-emerald-400">check_circle</span>
                            </div>
                            <span class="text-emerald-400 font-bold uppercase tracking-widest text-[10px]">Owned</span>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex items-end justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-white mb-0.5 truncate" x-text="item.name"></h3>
                            <span class="text-[10px] font-mono uppercase text-white/40 tracking-wider" x-text="item.category"></span>
                        </div>
                        <div class="flex items-center gap-1 bg-black/60 px-2 py-1 rounded-md border border-white/10 group-hover:border-yellow-500/50 transition-colors shrink-0"
                             x-show="!userInventory.includes(item.id)">
                            <span class="material-symbols-outlined text-yellow-500 text-sm">toll</span>
                            <span class="text-yellow-400 font-bold text-xs" x-text="item.price"></span>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="shopItems.length === 0" class="py-20 flex flex-col items-center justify-center text-center opacity-50 relative z-10">
            <span class="material-symbols-outlined text-6xl text-white/20 mb-4 animate-pulse">inventory_2</span>
            <p class="text-lg font-bold text-white uppercase tracking-widest">No Items Available</p>
            <p class="text-sm text-white/60 mt-2">Check back later for new stock.</p>
        </div>

    </div>

    <!-- Confirm Purchase Modal -->
    <div x-show="showConfirmModal" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         style="display: none;">
        <!-- Backdrop -->
        <div x-show="showConfirmModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-black/80 backdrop-blur-sm"
             @click="showConfirmModal = false"></div>
        
        <!-- Modal Content -->
        <div x-show="showConfirmModal"
             x-transition:enter="transition ease-out duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
             x-transition:enter-start="opacity-0 scale-90 translate-y-8"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-[#0a0a0f] border border-white/10 p-8 rounded-[2rem] max-w-sm w-full shadow-2xl overflow-hidden">
            
            <div class="absolute inset-0 bg-gradient-to-b from-white/5 to-transparent pointer-events-none"></div>

            <template x-if="selectedItem">
                <div class="relative z-10 text-center">
                    <!-- Preview Icon/Image -->
                    <div class="w-24 h-24 mx-auto mb-6 flex items-center justify-center"
                         :class="selectedItem.category === 'border' ? '' : 'rounded-2xl bg-black/50 border border-white/10 overflow-hidden'">
                        <template x-if="selectedItem.category === 'border' && selectedItem.image">
                            <div class="relative h-full w-full">
                                <div class="absolute left-1/2 top-1/2 w-[64%] pb-[64%] -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-full bg-[#111116]">
                                    <img :src="selectedAvatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(savedProfile.username || 'User') + '&background=ef4444&color=fff&bold=true'" class="absolute inset-0 h-full w-full object-cover" style="object-fit: cover;">
                                </div>
                                <img :src="selectedItem.image" class="absolute left-1/2 top-1/2 w-[80%] -translate-x-1/2 -translate-y-1/2 object-contain pointer-events-none mix-blend-screen" style="aspect-ratio: 1 / 1; height: auto;">
                            </div>
                        </template>
                        <template x-if="selectedItem.category !== 'border' && selectedItem.image">
                            <img :src="selectedItem.image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!selectedItem.image">
                            <span class="material-symbols-outlined text-[48px] text-white/50">workspace_premium</span>
                        </template>
                    </div>

                    <h3 class="text-2xl font-black text-white mb-2" x-text="'Buy ' + selectedItem.name + '?'"></h3>
                    <p class="text-white/60 text-sm mb-8">This will deduct <strong class="text-yellow-400 font-mono" x-text="selectedItem.price + ' PTS'"></strong> from your balance.</p>

                    <div class="flex gap-3">
                        <button @click="showConfirmModal = false" class="flex-1 py-3 rounded-xl font-bold uppercase tracking-wider text-xs text-white/60 bg-white/5 hover:bg-white/10 transition-colors">
                            Cancel
                        </button>
                        <button @click="purchaseItem(selectedItem.id)" 
                                class="flex-1 py-3 rounded-xl font-black uppercase tracking-wider text-xs shadow-lg transition-all transform hover:-translate-y-1"
                                :class="userPoints >= selectedItem.price ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-indigo-500/25 hover:shadow-indigo-500/40' : 'bg-red-500/20 text-red-500 border border-red-500/30 cursor-not-allowed'">
                            <span x-text="userPoints >= selectedItem.price ? 'Confirm' : 'Insufficient'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Background Ambient Glow -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" x-show="currentTab === 'shop'">
        <div class="absolute top-[20%] left-[10%] w-[500px] h-[500px] rounded-full bg-violet-600/10 blur-[120px]"></div>
        <div class="absolute bottom-[20%] right-[10%] w-[600px] h-[600px] rounded-full bg-fuchsia-600/10 blur-[150px]"></div>
    </div>

</div>