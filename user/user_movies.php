<!-- User Movies View Container -->
<div x-show="currentTab === 'movies'" style="display: none;" class="relative w-full min-h-full p-8 lg:p-12 pb-24 overflow-y-auto">
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10 stagger-item">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-3xl font-black text-white tracking-tight drop-shadow-[0_0_10px_rgba(255,255,255,0.3)]">Movie Library</h2>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 shadow-[0_0_15px_rgba(99,102,241,0.2)]" x-text="movies ? movies.length + ' titles' : '0 titles'"></span>
            </div>
            <p class="text-xs text-white/50 font-mono tracking-widest uppercase mt-2">Explore the premium catalog</p>
        </div>
        
        <!-- Search & Filter -->
        <div class="flex items-center gap-3">
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-[18px] group-focus-within:text-indigo-400 transition-colors">search</span>
                <input type="text" placeholder="Search titles..." class="w-64 bg-white/[0.02] border border-white/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-white/30 outline-none focus:border-indigo-500/50 focus:bg-white/[0.04] transition-all duration-300 shadow-lg">
            </div>
        </div>
    </div>

    <!-- Movie Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 lg:gap-8 pb-12">
        
        <!-- Placeholder Movie Card (Always visible for demo) -->
        <div class="movie-card-container stagger-item">
            <div @click="console.log('Viewing Placeholder Movie')" class="group cursor-pointer relative rounded-2xl bg-[#050508] border border-white/[0.05] hover:border-indigo-500/40 transition-all duration-500 hover:-translate-y-2 shadow-2xl hover:shadow-[0_20px_40px_rgba(99,102,241,0.2)] overflow-hidden" x-data="{ hovered: false }" @mouseenter="hovered = true; $refs.demoTrailer.play().catch(e=>{})" @mouseleave="hovered = false; $refs.demoTrailer.pause(); $refs.demoTrailer.currentTime = 0">
                
                <div class="aspect-[2/3] w-full relative overflow-hidden bg-[#050508]">
                    <!-- Poster Image -->
                    <img src="https://images.unsplash.com/photo-1536440136628-849c177e76a1?q=80&w=600&auto=format&fit=crop" alt="Poster" class="w-full h-full object-cover transition-all duration-700 absolute inset-0 z-10 group-hover:scale-110" :class="hovered ? 'opacity-0' : 'opacity-100'">
                    
                    <!-- Video Trailer on Hover -->
                    <div class="absolute inset-0 z-0 transition-opacity duration-500" :class="hovered ? 'opacity-100' : 'opacity-0'">
                        <video src="https://storage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4" loop muted playsinline class="w-full h-full object-cover scale-105" x-ref="demoTrailer"></video>
                    </div>
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-[#050508] via-[#050508]/20 to-transparent opacity-90 group-hover:opacity-100 transition-opacity duration-500 z-20 pointer-events-none"></div>
                    
                    <!-- Rating Badge on Poster -->
                    <div class="absolute top-4 left-4 px-3 py-1.5 rounded-xl bg-black/60 backdrop-blur-md border border-yellow-500/30 flex items-center gap-1.5 text-xs font-black text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.3)] z-30">
                        <span class="material-symbols-outlined text-[16px]">star</span>
                        <span>9.5</span>
                    </div>
                    
                    <!-- Watch Action Overlay -->
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30 scale-90 group-hover:scale-100 pointer-events-none">
                        <div class="w-16 h-16 rounded-full bg-indigo-500/90 backdrop-blur-sm flex items-center justify-center text-white shadow-[0_0_30px_rgba(99,102,241,0.8)]">
                            <span class="material-symbols-outlined text-[32px] ml-1">play_arrow</span>
                        </div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="relative p-5 z-30 border-t border-white/[0.02]">
                    <h3 class="font-black text-lg text-white mb-1.5 truncate group-hover:text-indigo-400 transition-colors duration-300 drop-shadow-[0_0_5px_rgba(255,255,255,0.2)]">Tears of Steel</h3>
                    <div class="flex items-center justify-between">
                        <div class="flex flex-wrap gap-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-indigo-500/20 text-indigo-300 bg-indigo-500/10">Sci-Fi</span>
                        </div>
                        <span class="text-[11px] text-white/40 font-bold mono">2012</span>
                    </div>
                </div>
            </div>
        </div>

        <template x-for="movie in movies" :key="movie.id">
            <div class="movie-card-container stagger-item">
                <div @click="console.log(`Viewing \${movie.title}`)" class="group cursor-pointer relative rounded-2xl bg-[#050508] border border-white/[0.05] hover:border-indigo-500/40 transition-all duration-500 hover:-translate-y-2 shadow-2xl hover:shadow-[0_20px_40px_rgba(99,102,241,0.2)] overflow-hidden" x-data="{ hovered: false }" @mouseenter="hovered = true; if($refs.trailer) $refs.trailer.play().catch(e=>{})" @mouseleave="hovered = false; if($refs.trailer) { $refs.trailer.pause(); $refs.trailer.currentTime = 0; }">
                    
                    <div class="aspect-[2/3] w-full relative overflow-hidden bg-[#050508]">
                        <!-- Poster Image -->
                        <img :src="movie.img || 'https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster'" alt="Poster" class="w-full h-full object-cover transition-all duration-700 absolute inset-0 z-10 group-hover:scale-110" :class="hovered ? 'opacity-0' : 'opacity-100'">
                        
                        <!-- Video Trailer on Hover -->
                        <template x-if="movie.trailer">
                            <div class="absolute inset-0 z-0 transition-opacity duration-500" :class="hovered ? 'opacity-100' : 'opacity-0'">
                                <video :src="movie.trailer" loop muted playsinline class="w-full h-full object-cover scale-105" x-ref="trailer"></video>
                            </div>
                        </template>
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-[#050508] via-[#050508]/20 to-transparent opacity-90 group-hover:opacity-100 transition-opacity duration-500 z-20 pointer-events-none"></div>
                        
                        <!-- Rating Badge on Poster -->
                        <div class="absolute top-4 left-4 px-3 py-1.5 rounded-xl bg-black/60 backdrop-blur-md border border-yellow-500/30 flex items-center gap-1.5 text-xs font-black text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.3)] z-30">
                            <span class="material-symbols-outlined text-[16px]">star</span>
                            <span x-text="movie.rating ? movie.rating : '0.0'"></span>
                        </div>
                        
                        <!-- Watch Action Overlay -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30 scale-90 group-hover:scale-100 pointer-events-none">
                            <div class="w-16 h-16 rounded-full bg-indigo-500/90 backdrop-blur-sm flex items-center justify-center text-white shadow-[0_0_30px_rgba(99,102,241,0.8)]">
                                <span class="material-symbols-outlined text-[32px] ml-1">play_arrow</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="relative p-5 z-30 border-t border-white/[0.02]">
                        <h3 class="font-black text-lg text-white mb-1.5 truncate group-hover:text-indigo-400 transition-colors duration-300 drop-shadow-[0_0_5px_rgba(255,255,255,0.2)]" x-text="movie.title"></h3>
                        <div class="flex items-center justify-between">
                            <div class="flex flex-wrap gap-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-indigo-500/20 text-indigo-300 bg-indigo-500/10">Movie</span>
                            </div>
                            <span class="text-[11px] text-white/40 font-bold mono" x-text="movie.year || '2024'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
