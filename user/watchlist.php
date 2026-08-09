<!-- Watchlist Tab -->
<div class="flex-1 overflow-y-auto p-10 tab-content relative scroll-smooth" 
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
            <div class="flex items-center gap-3">
                <div class="flex gap-2 p-1.5 bg-white/[0.03] rounded-2xl border border-white/10 shadow-inner">
                    <button class="px-5 py-2.5 rounded-xl bg-white/10 text-white text-[11px] font-bold uppercase tracking-wider shadow-lg">All</button>
                    <button class="px-5 py-2.5 rounded-xl hover:bg-white/5 text-white/50 hover:text-white transition-all text-[11px] font-bold uppercase tracking-wider">Movies</button>
                    <button class="px-5 py-2.5 rounded-xl hover:bg-white/5 text-white/50 hover:text-white transition-all text-[11px] font-bold uppercase tracking-wider">Shows</button>
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