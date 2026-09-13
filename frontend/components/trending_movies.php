<?php
$trendingClick = $trendingClick ?? 'openTrendingMovie(movie)';
?>
<section class="trending-now-block mb-8" x-show="trendingMovies.length" x-cloak>
    <div class="flex items-end justify-between mb-5 gap-4">
        <div>
            <h2 class="text-sm font-bold text-red-500 tracking-widest uppercase mb-1.5 mono">Trending Now</h2>
            <h3 class="text-2xl md:text-3xl font-bold tracking-tight">Most viewed titles</h3>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="w-10 h-10 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 flex items-center justify-center cursor-pointer" @click="scrollTrendingRow(-1)" aria-label="Previous trending">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <button type="button" class="w-10 h-10 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 flex items-center justify-center cursor-pointer" @click="scrollTrendingRow(1)" aria-label="Next trending">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
    </div>
    <div x-ref="trendingRow" class="dash-row-scroll flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
        <template x-for="(movie, i) in trendingMovies" :key="'trend-' + (movie.id || movie.movie_id || i)">
            <button type="button"
                    @click="<?php echo htmlspecialchars($trendingClick, ENT_QUOTES, 'UTF-8'); ?>"
                    class="home-movie-card glass-card rounded-xl overflow-hidden group cursor-pointer snap-start shrink-0 w-[38vw] sm:w-36 md:w-44 text-left">
                <div class="aspect-[2/3] relative overflow-hidden">
                    <img :src="movie.img || movie.cover_image" :alt="movie.title" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" @error="$el.src='/frontend/assets/home/dune-live.jpg'">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <span class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl ml-0.5">play_arrow</span>
                        </span>
                    </div>
                    <div class="absolute top-3 left-3 text-[11px] font-black tracking-tight bg-black/70 border border-white/10 px-2 py-0.5 rounded" x-text="'#' + (i + 1)"></div>
                    <div class="absolute bottom-3 left-3 right-3">
                        <span class="text-[10px] font-bold text-red-400 tracking-widest uppercase" x-text="(Array.isArray(movie.genres) ? (movie.genres[0] || movie.genre) : (movie.genre || 'Film'))">Sci-Fi</span>
                        <h4 class="font-bold text-base leading-tight truncate" x-text="movie.title"></h4>
                        <p class="text-[10px] text-white/50 mono mt-0.5" x-text="(movie.view_count || 0) + ' views'">0 views</p>
                    </div>
                </div>
            </button>
        </template>
    </div>
</section>
