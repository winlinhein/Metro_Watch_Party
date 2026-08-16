<!-- Watchlist Tab -->
<div class="absolute inset-0 w-full h-full overflow-y-auto p-10 tab-content scroll-smooth custom-scrollbar" 
     x-show="currentTab === 'watchlist'"
     x-transition:enter="transition-all duration-500 delay-300 cubic-bezier(0.34, 1.56, 0.64, 1)"
     x-transition:enter-start="opacity-0 translate-y-8"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition-all duration-300 ease-in"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-8 absolute w-full"
     style="display: none;"
     >
    <div class="max-w-[1400px] mx-auto space-y-8">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
            <div>
                <h2 class="text-4xl font-bold tracking-tight text-white flex items-center gap-4 mb-2">
                    <div class="w-12 h-12 bg-gradient-to-tr from-red-500 to-rose-600 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(239,68,68,0.3)]">
                        <span class="material-symbols-outlined text-[28px] text-white">bookmark</span>
                    </div>
                    Your Watchlist
                </h2>
                <p class="text-white/40 mono text-sm uppercase tracking-widest pl-16">Queued for protocol initiation</p>
            </div>
            <div class="flex items-center gap-3" x-data="{ searchOpen: false, searchFocused: false }">
                <div class="relative group">
                    <!-- Animated Glow Behind -->
                    <div class="absolute inset-0 bg-gradient-to-r from-red-500 via-rose-500 to-purple-500 rounded-2xl blur-xl transition-all duration-700 opacity-0"
                         :class="searchOpen ? 'opacity-30 scale-105' : 'group-hover:opacity-20'"></div>
                    
                    <!-- Search Container -->
                    <div class="relative flex items-center bg-black/40 backdrop-blur-xl border transition-all duration-700 ease-[cubic-bezier(0.34,1.56,0.64,1)] rounded-2xl overflow-hidden"
                         :class="searchOpen ? 'w-80 border-red-500/50 shadow-[inset_0_0_20px_rgba(239,68,68,0.2)]' : 'w-14 border-white/10 hover:border-white/30'">
                        
                        <!-- Icon Button -->
                        <button @click="searchOpen = !searchOpen; if(searchOpen) $nextTick(() => $refs.searchInput.focus())" 
                                class="w-14 h-14 shrink-0 flex items-center justify-center transition-colors duration-300 relative z-10"
                                :class="searchOpen ? 'text-red-400' : 'text-white/50 group-hover:text-white'">
                            <!-- Search Icon (Fades out and down) -->
                            <span class="material-symbols-outlined absolute text-[24px] transition-all duration-500"
                                  :class="searchOpen ? 'opacity-0 scale-50 rotate-90 translate-y-4' : 'opacity-100 scale-100 rotate-0 translate-y-0'">search</span>
                            <!-- Close Icon (Fades in and up) -->
                            <span class="material-symbols-outlined absolute text-[24px] transition-all duration-500"
                                  :class="searchOpen ? 'opacity-100 scale-100 rotate-0 translate-y-0' : 'opacity-0 scale-50 -rotate-90 -translate-y-4'">close</span>
                        </button>

                        <!-- Input Field -->
                        <input x-ref="searchInput" 
                               type="text" 
                               @focus="searchFocused = true" 
                               @blur="searchFocused = false"
                               placeholder="INITIATE SEARCH..." 
                               class="w-full bg-transparent text-white text-[11px] font-bold uppercase tracking-widest outline-none placeholder-red-500/30 pr-4 transition-all duration-500"
                               :class="searchOpen ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8 pointer-events-none'">
                               
                        <!-- Scanning Line Effect -->
                        <div class="absolute inset-y-0 left-14 w-[1px] bg-red-500/80 shadow-[0_0_15px_#ef4444] transition-all duration-[1.5s] ease-in-out"
                             :class="searchFocused ? 'opacity-100 translate-x-[240px]' : 'opacity-0 translate-x-0'"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <template x-for="(item, index) in watchlist" :key="item.id || index">
                <div @click="openWatchlistMovie(item)"
                     class="relative group cursor-pointer perspective-container" 
                     :style="`animation-delay: ${index * 100}ms; perspective: 1000px;`">
                     <!-- Card -->
                    <div class="aspect-[2/3] w-full relative rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] transition-all duration-700 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:[transform:translateY(-16px)_rotateX(12deg)] group-hover:shadow-[0_40px_80px_rgba(239,68,68,0.2)] border border-white/5 group-hover:border-red-500/30">
                        <img :src="item.img" class="w-full h-full object-cover transition-all duration-700 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:scale-110 group-hover:brightness-50">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-[#050508] via-[#050508]/40 to-transparent opacity-80 group-hover:opacity-100 transition-opacity duration-500"></div>
                        
                        <!-- Floating Status Badge -->
                        <div class="absolute top-5 right-5 px-3 py-1.5 bg-black/40 backdrop-blur-md rounded-full border border-white/10 flex items-center gap-2 transition-transform duration-500 group-hover:-translate-y-2 group-hover:border-white/20">
                            <span class="w-1.5 h-1.5 rounded-full" :class="item.status === 'Next Up' ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.8)] animate-pulse' : 'bg-white/30'"></span>
                            <span class="text-[9px] font-bold text-white uppercase tracking-widest" x-text="item.status || 'Queued'"></span>
                        </div>
                        
                        <!-- Rating Badge -->
                        <div class="absolute top-5 left-5 px-2.5 py-1.5 bg-red-500/90 backdrop-blur-md rounded-lg border border-red-400/50 flex items-center gap-1 shadow-[0_10px_20px_rgba(239,68,68,0.4)] transform -translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 delay-100 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
                            <span class="material-symbols-outlined text-[14px] text-white">star</span>
                            <span class="text-[11px] font-bold text-white" x-text="item.rating"></span>
                        </div>

                        <!-- Content overlay -->
                        <div class="absolute inset-x-0 bottom-0 p-6 transform translate-y-8 group-hover:translate-y-0 transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2 py-1 bg-white/10 backdrop-blur-md rounded-md text-[9px] text-white/90 font-mono font-bold border border-white/10" x-text="item.year"></span>
                                <span class="px-2 py-1 bg-white/10 backdrop-blur-md rounded-md text-[9px] text-white/90 font-mono font-bold border border-white/10" x-text="item.genre"></span>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4 tracking-tight group-hover:text-red-400 transition-colors duration-300 drop-shadow-lg" x-text="item.title"></h3>
                            
                            <div class="flex gap-3 opacity-0 group-hover:opacity-100 transition-all duration-500 delay-150 transform translate-y-4 group-hover:translate-y-0">
                                <button @click.stop="openWatchlistMovie(item)" 
                                        class="flex-1 py-3 bg-red-500 hover:bg-red-600 rounded-xl text-white text-[11px] font-bold uppercase tracking-widest transition-all duration-300 shadow-[0_0_20px_rgba(239,68,68,0.4)] hover:shadow-[0_0_30px_rgba(239,68,68,0.6)] flex items-center justify-center gap-2 group/btn">
                                    <span class="material-symbols-outlined text-[18px] group-hover/btn:scale-125 transition-transform">play_arrow</span> Watch Now
                                </button>
                                <button @click.stop="openWatchlistMovie(item)" 
                                        class="w-12 h-12 bg-white/5 hover:bg-white/15 backdrop-blur-md rounded-xl text-white flex items-center justify-center transition-all duration-300 border border-white/10 hover:border-white/30 group/info">
                                    <span class="material-symbols-outlined text-[20px] group-hover/info:rotate-12 transition-transform">info</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Bottom spacing -->
        <div class="h-20 w-full opacity-50 flex items-center justify-center">
            <div class="w-1/2 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
        </div>
    </div>
</div>