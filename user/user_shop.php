        <!-- Shop Tab -->
        <div class="flex-1 overflow-y-auto p-6 md:p-10 tab-content relative scroll-smooth" 
             x-show="currentTab === 'shop'"
             x-transition:enter="transition-all duration-500 delay-300 cubic-bezier(0.34, 1.56, 0.64, 1)"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition-all duration-300 ease-in"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-8 absolute w-full"
             style="display: none;"
             x-data="{
                points: 1250,
                activeCategory: 'avatars',
                showConfirmModal: false,
                selectedItem: null,
                inventory: [],
                categories: [
                    { id: 'avatars', name: 'Premium Avatars', icon: 'account_circle' },
                    { id: 'borders', name: 'Profile Borders', icon: 'crop_square' },
                    { id: 'badges', name: 'Special Badges', icon: 'workspace_premium' }
                ],
                items: [
                    { id: 'a1', type: 'avatars', name: 'Cyberpunk Neon', price: 300, img: 'https://images.unsplash.com/photo-1535295972055-1c762f4483e5?w=200&h=200&fit=crop', color: 'from-fuchsia-500 to-cyan-500' },
                    { id: 'a2', type: 'avatars', name: 'Cosmic Entity', price: 500, img: 'https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?w=200&h=200&fit=crop', color: 'from-indigo-500 to-purple-500' },
                    { id: 'a3', type: 'avatars', name: 'Mecha Samurai', price: 800, img: 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=200&h=200&fit=crop', color: 'from-red-500 to-orange-500' },
                    { id: 'b1', type: 'borders', name: 'Glitch Effect', price: 400, img: '/frontend/assets/borders/Glitch.gif', color: 'from-green-400 to-emerald-600', isGif: true },
                    { id: 'b2', type: 'borders', name: 'Hallucination', price: 600, img: '/frontend/assets/borders/Hallunication.gif', color: 'from-pink-500 to-rose-500', isGif: true },
                    { id: 'b3', type: 'borders', name: 'Sukuna Slashes', price: 1000, img: '/frontend/assets/borders/Sukuna\'s slashes.gif', color: 'from-red-600 to-red-900', isGif: true },
                    { id: 'c1', type: 'badges', name: 'Early Adopter', price: 200, icon: 'military_tech', color: 'from-yellow-400 to-amber-600' },
                    { id: 'c2', type: 'badges', name: 'Cinephile', price: 450, icon: 'movie_filter', color: 'from-blue-400 to-indigo-600' },
                    { id: 'c3', type: 'badges', name: 'Party Host MVP', price: 750, icon: 'celebration', color: 'from-fuchsia-500 to-pink-500' }
                ],
                get filteredItems() {
                    return this.items.filter(i => i.type === this.activeCategory);
                },
                buyItem() {
                    if(!this.selectedItem) return;
                    if(this.points >= this.selectedItem.price) {
                        this.points -= this.selectedItem.price;
                        this.inventory.push(this.selectedItem.id);
                        if(window.showToast) window.showToast('Successfully purchased ' + this.selectedItem.name + '!', 'success');
                        
                        // Confetti Effect or GSAP animation
                        gsap.fromTo(this.$refs.pointsDisplay, 
                            { scale: 1.5, color: '#4ade80' }, 
                            { scale: 1, color: '#eab308', duration: 0.8, ease: 'back.out(1.7)' }
                        );
                    } else {
                        if(window.showToast) window.showToast('Not enough points!', 'error');
                        
                        gsap.fromTo(this.$refs.pointsDisplay,
                            { x: -10 },
                            { x: 10, yoyo: true, repeat: 5, duration: 0.05, clearProps: 'x' }
                        );
                    }
                    this.showConfirmModal = false;
                }
             }">
            <div class="max-w-[1400px] mx-auto space-y-8 pb-20">
                
                <!-- Header & Points -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 relative z-10">
                    <div>
                        <h2 class="text-4xl font-black tracking-tighter text-white flex items-center gap-4 mb-2 uppercase drop-shadow-[0_0_15px_rgba(255,255,255,0.3)]">
                            <div class="w-14 h-14 bg-gradient-to-tr from-violet-600 to-fuchsia-600 rounded-2xl flex items-center justify-center shadow-[0_0_40px_rgba(139,92,246,0.5)] border border-white/20">
                                <span class="material-symbols-outlined text-[32px] text-white">storefront</span>
                            </div>
                            Point Shop
                        </h2>
                        <p class="text-white/50 text-sm max-w-xl font-medium tracking-wide">Redeem your hard-earned points for exclusive profile cosmetics, avatars, and special badges to stand out in the network.</p>
                    </div>

                    <div class="flex items-center gap-4 bg-black/40 backdrop-blur-xl border border-white/10 rounded-2xl p-4 shadow-xl">
                        <div class="w-12 h-12 rounded-xl bg-yellow-500/20 border border-yellow-500/30 flex items-center justify-center">
                            <span class="material-symbols-outlined text-yellow-400 text-3xl">toll</span>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest text-white/50 font-bold mb-1">Your Balance</p>
                            <p class="text-3xl font-black text-yellow-400 tracking-tighter" x-ref="pointsDisplay">
                                <span x-text="points.toLocaleString()"></span> 
                                <span class="text-sm font-bold text-yellow-500/50">PTS</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="flex gap-4 overflow-x-auto custom-scrollbar pb-4 relative z-10">
                    <template x-for="category in categories" :key="category.id">
                        <button @click="activeCategory = category.id"
                                class="flex items-center gap-3 px-6 py-4 rounded-2xl font-bold text-sm tracking-wide transition-all duration-500 border whitespace-nowrap"
                                :class="activeCategory === category.id 
                                    ? 'bg-white text-black border-white shadow-[0_0_30px_rgba(255,255,255,0.3)] scale-105' 
                                    : 'bg-[#0a0a0f]/80 text-white/60 border-white/5 hover:bg-white/5 hover:text-white hover:border-white/20'">
                            <span class="material-symbols-outlined text-xl" x-text="category.icon"></span>
                            <span x-text="category.name"></span>
                        </button>
                    </template>
                </div>

                <!-- Items Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 relative z-10"
                     x-transition:enter="transition-all duration-500"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <template x-for="item in filteredItems" :key="item.id">
                        <div class="group relative bg-[#050508] rounded-[2rem] border border-white/[0.05] p-6 hover:border-white/20 transition-all duration-500 hover:-translate-y-2 shadow-2xl overflow-hidden cursor-pointer"
                             @click="!inventory.includes(item.id) ? (selectedItem = item, showConfirmModal = true) : null">
                            
                            <!-- Background Glow -->
                            <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-10 transition-opacity duration-700 pointer-events-none" :class="item.color"></div>
                            
                            <!-- Visual Asset -->
                            <div class="w-full aspect-square rounded-2xl mb-6 relative overflow-hidden bg-black/50 border border-white/5 flex items-center justify-center">
                                <!-- Type: Avatars or Borders with Images -->
                                <template x-if="item.img">
                                    <img :src="item.img" :alt="item.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" :class="item.type === 'borders' ? 'object-contain p-4' : ''">
                                </template>
                                <!-- Type: Badges with Icons -->
                                <template x-if="item.icon">
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br opacity-80 group-hover:opacity-100 transition-opacity duration-500" :class="item.color">
                                        <span class="material-symbols-outlined text-[80px] text-white drop-shadow-[0_0_20px_rgba(255,255,255,0.5)]" x-text="item.icon" style="font-variation-settings: 'FILL' 1;"></span>
                                    </div>
                                </template>
                                
                                <!-- Purchased Overlay -->
                                <div x-show="inventory.includes(item.id)" class="absolute inset-0 bg-black/80 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                                    <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500 flex items-center justify-center mb-2 shadow-[0_0_30px_rgba(16,185,129,0.5)]">
                                        <span class="material-symbols-outlined text-3xl text-emerald-400">check_circle</span>
                                    </div>
                                    <span class="text-emerald-400 font-bold uppercase tracking-widest text-xs">Owned</span>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-bold text-white mb-1" x-text="item.name"></h3>
                                    <span class="text-xs font-mono uppercase text-white/40 tracking-wider" x-text="categories.find(c => c.id === item.type).name"></span>
                                </div>
                                <div class="flex items-center gap-1.5 bg-black/60 px-3 py-1.5 rounded-lg border border-white/10 group-hover:border-yellow-500/50 transition-colors"
                                     x-show="!inventory.includes(item.id)">
                                    <span class="material-symbols-outlined text-yellow-500 text-lg">toll</span>
                                    <span class="text-yellow-400 font-bold" x-text="item.price"></span>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="filteredItems.length === 0" class="py-20 flex flex-col items-center justify-center text-center opacity-50 relative z-10">
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
                            <div class="w-24 h-24 mx-auto rounded-2xl bg-black/50 border border-white/10 mb-6 flex items-center justify-center overflow-hidden">
                                <template x-if="selectedItem.img">
                                    <img :src="selectedItem.img" class="w-full h-full object-cover">
                                </template>
                                <template x-if="selectedItem.icon">
                                    <span class="material-symbols-outlined text-[48px] text-white" x-text="selectedItem.icon"></span>
                                </template>
                            </div>

                            <h3 class="text-2xl font-black text-white mb-2" x-text="'Buy ' + selectedItem.name + '?'"></h3>
                            <p class="text-white/60 text-sm mb-8">This will deduct <strong class="text-yellow-400 font-mono" x-text="selectedItem.price + ' PTS'"></strong> from your balance.</p>

                            <div class="flex gap-3">
                                <button @click="showConfirmModal = false" class="flex-1 py-3 rounded-xl font-bold uppercase tracking-wider text-xs text-white/60 bg-white/5 hover:bg-white/10 transition-colors">
                                    Cancel
                                </button>
                                <button @click="buyItem()" 
                                        class="flex-1 py-3 rounded-xl font-black uppercase tracking-wider text-xs shadow-lg transition-all transform hover:-translate-y-1"
                                        :class="points >= selectedItem.price ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-indigo-500/25 hover:shadow-indigo-500/40' : 'bg-red-500/20 text-red-500 border border-red-500/30 cursor-not-allowed'">
                                    <span x-text="points >= selectedItem.price ? 'Confirm' : 'Insufficient'"></span>
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
