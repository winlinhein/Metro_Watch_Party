<!-- User Movies View Container -->
<div x-show="currentTab === 'movies'" style="display: none;" class="relative w-full min-h-full p-8 lg:p-12 pb-24 overflow-y-auto">
    
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10 stagger-item">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-3xl font-black text-white tracking-tight drop-shadow-[0_0_10px_rgba(255,255,255,0.3)]">Movie Library</h2>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 shadow-[0_0_15px_rgba(99,102,241,0.2)]" x-text="(filteredMovies ? filteredMovies.length : 0) + ' titles'"></span>
            </div>
            <p class="text-xs text-white/50 font-mono tracking-widest uppercase mt-2">Explore the premium catalog</p>
        </div>
        
        <!-- Search & Filter Input -->
        <div class="flex items-center gap-3">
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-[18px] group-focus-within:text-indigo-400 transition-colors">search</span>
                <input type="text" x-model="movieSearchQuery" placeholder="Search titles or genres..." class="w-64 bg-white/[0.02] border border-white/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-white/30 outline-none focus:border-indigo-500/50 focus:bg-white/[0.04] transition-all duration-300 shadow-lg">
            </div>
        </div>
    </div>

    <!-- Dynamic Movie Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 lg:gap-8 pb-12">
        
        <template x-for="movie in filteredMovies" :key="movie.id">
            <div class="movie-card-container stagger-item">
                <div @click="openMovieDetail(movie)" class="group cursor-pointer relative rounded-2xl bg-[#050508] border border-white/[0.05] hover:border-indigo-500/40 transition-all duration-500 hover:-translate-y-2 shadow-2xl hover:shadow-[0_20px_40px_rgba(99,102,241,0.2)] overflow-hidden" x-data="{ hovered: false }" @mouseenter="hovered = true; if($refs.trailer) $refs.trailer.play().catch(e=>{})" @mouseleave="hovered = false; if($refs.trailer) { $refs.trailer.pause(); $refs.trailer.currentTime = 0; }">
                    
                    <!-- Poster Image & Trailer Container -->
                    <div class="aspect-[2/3] w-full relative overflow-hidden bg-[#050508]" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">
                        
                        <!-- Poster Image -->
                        <img :src="movie.img || movie.cover_image || 'https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster'" alt="Poster" class="w-full h-full object-cover transition-all duration-700 absolute inset-0 z-10 group-hover:scale-110" :class="hovered && (movie.trailer || movie.video_url) ? 'opacity-0' : 'opacity-100'">
                        
                        <!-- YouTube Trailer Preview on Hover -->
                        <div class="absolute inset-0 z-0 transition-opacity duration-500 overflow-hidden pointer-events-none" :class="hovered ? 'opacity-100' : 'opacity-0'">
                            <template x-if="hovered && (movie.trailer || movie.video_url)">
                                <iframe 
                                    :src="getYouTubeEmbedUrl(movie.trailer || movie.video_url, true)" 
                                    class="w-full h-[140%] -mt-[20%] scale-150 pointer-events-none" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </template>
                        </div>

                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-[#050508] via-[#050508]/20 to-transparent opacity-90 group-hover:opacity-100 transition-opacity duration-500 z-20 pointer-events-none"></div>

                        <!-- Rating Badge -->
                        <div class="absolute top-4 left-4 px-3 py-1.5 rounded-xl bg-black/60 backdrop-blur-md border border-yellow-500/30 flex items-center gap-1.5 text-xs font-black text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.3)] z-30">
                            <span class="material-symbols-outlined text-[16px]">star</span>
                            <span x-text="movie.rating ? movie.rating : '0.0'"></span>
                        </div>

                        <!-- Watchlist Button Overlay -->
                        <button @click.stop="toggleWatchlist(movie)" 
                                class="absolute top-4 right-4 z-40 group/watchlist w-10 h-10 flex items-center justify-center rounded-xl backdrop-blur-xl border transition-all duration-500 overflow-hidden transform-gpu"
                                :class="movie.inWatchlist ? 'bg-indigo-500/20 border-indigo-400/50 shadow-[0_0_20px_rgba(99,102,241,0.5)]' : 'bg-black/40 border-white/10 hover:border-indigo-500/50 hover:bg-black/60'">
                            <!-- Hover gradient effect -->
                            <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500/0 via-indigo-500/0 to-purple-500/0 opacity-0 group-hover/watchlist:opacity-100 group-hover/watchlist:from-indigo-500/20 group-hover/watchlist:to-purple-500/20 transition-all duration-500"></div>
                            
                            <!-- Icon -->
                            <span class="material-symbols-outlined relative z-10 transition-all duration-500 transform group-hover/watchlist:scale-110"
                                  :class="movie.inWatchlist ? 'text-indigo-400' : 'text-white/60 group-hover/watchlist:text-white'"
                                  :style="movie.inWatchlist ? 'font-variation-settings: \'FILL\' 1;' : ''"
                                  x-text="movie.inWatchlist ? 'bookmark_added' : 'bookmark_add'"></span>
                            
                            <!-- Ripple effect on click (could use GSAP if we wanted, but CSS is fine) -->
                            <div class="absolute inset-0 rounded-xl bg-indigo-400/30 opacity-0 scale-0 transition-all duration-500"
                                 :class="movie.inWatchlist ? 'animate-[ping_0.5s_ease-out_1]' : ''"></div>
                        </button>

                        <!-- Play Icon Overlay -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30 scale-90 group-hover:scale-100 pointer-events-none">
                            <div class="w-16 h-16 rounded-full bg-indigo-500/90 backdrop-blur-sm flex items-center justify-center text-white shadow-[0_0_30px_rgba(99,102,241,0.8)]">
                                <span class="material-symbols-outlined text-[32px] ml-1">play_arrow</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Info -->
                    <div class="relative p-5 z-30 border-t border-white/[0.02]">
                        <h3 class="font-black text-lg text-white mb-1.5 truncate group-hover:text-indigo-400 transition-colors duration-300 drop-shadow-[0_0_5px_rgba(255,255,255,0.2)]" x-text="movie.title"></h3>
                        <div class="flex items-center justify-between">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="(genre, idx) in (movie.genres.length ? movie.genres : ['Movie'])" :key="idx">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-indigo-500/20 text-indigo-300 bg-indigo-500/10" x-text="genre"></span>
                                </template>
                            </div>
                            <span class="text-[11px] text-white/40 font-bold mono" x-text="movie.created_at ? new Date(movie.created_at).getFullYear() : '2024'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="filteredMovies.length === 0" class="col-span-full py-20 text-center">
            <span class="material-symbols-outlined text-white/20 text-6xl mb-3">movie_off</span>
            <p class="text-sm font-bold text-white/40">No movies found matching your query.</p>
        </div>
    </div>

    <!-- Movie Detail & Playback Modal -->
    <div x-show="showMovieDetailModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-xl" @click="closeMovieDetail()"></div>
        
        <div class="relative w-full max-w-4xl bg-[#08080d] border border-white/10 rounded-3xl overflow-hidden shadow-2xl z-10 flex flex-col max-h-[90vh]" x-show="showMovieDetailModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            
            <template x-if="selectedMovie">
                <div class="flex flex-col h-full overflow-y-auto custom-scrollbar">
                    
                    <!-- Video Player Container in Modal -->
                    <div class="relative aspect-video w-full bg-black">
                        <!-- YouTube Embed Player -->
                        <template x-if="isYouTubeUrl(selectedMovie?.video_url || selectedMovie?.trailer)">
                            <iframe 
                                :src="getYouTubeEmbedUrl(selectedMovie?.video_url || selectedMovie?.trailer, false)" 
                                class="w-full h-full" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </template>

                        <!-- Standard HTML5 MP4 Player Fallback -->
                        <template x-if="!isYouTubeUrl(selectedMovie?.video_url || selectedMovie?.trailer)">
                            <video :src="selectedMovie?.video_url || selectedMovie?.trailer" controls class="w-full h-full object-contain"></video>
                        </template>

                        <!-- Close Button -->
                        <button @click="closeMovieDetail()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-black/60 border border-white/20 text-white flex items-center justify-center hover:bg-white/20 transition-all z-20">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <!-- Details & Comments -->
                    <div class="p-8 space-y-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-6">
                            <div>
                                <h2 class="text-3xl font-black text-white" x-text="selectedMovie.title"></h2>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="text-xs font-bold text-yellow-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">star</span>
                                        <span x-text="selectedMovie.rating"></span>
                                    </span>
                                    <span class="text-xs text-white/40 font-mono" x-text="selectedMovie.duration ? selectedMovie.duration + ' mins' : ''"></span>
                                    <span class="text-xs text-white/40 font-mono" x-text="selectedMovie.view_count + ' views'"></span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="g in selectedMovie.genres" :key="g">
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20" x-text="g"></span>
                                </template>
                            </div>
                        </div>

                        <p class="text-sm text-white/70 leading-relaxed" x-text="selectedMovie.description || 'No description available for this movie.'"></p>

                        <!-- Rating and Comment Section -->
                        <div class="pt-8 mt-6 border-t border-white/10" 
                             x-data="{ 
                                newRating: 0, 
                                hoveredRating: 0, 
                                commentText: '', 
                                isSubmitting: false,
                                submitReview() {
                                    if(this.newRating === 0) return window.showToast('Please select a rating', 'error');
                                    this.isSubmitting = true;
                                    // Simulate API call
                                    setTimeout(() => {
                                        if(!this.selectedMovie.comments) this.selectedMovie.comments = [];
                                        this.selectedMovie.comments.unshift({
                                            comment_id: Date.now(),
                                            user_id: 'You',
                                            comment_text: this.commentText,
                                            created_at: new Date().toISOString(),
                                            rating: this.newRating
                                        });
                                        this.newRating = 0;
                                        this.commentText = '';
                                        this.isSubmitting = false;
                                        window.showToast('Review posted successfully!', 'success');
                                    }, 800);
                                }
                             }">
                            
                            <h3 class="text-lg font-black uppercase tracking-widest text-white mb-6 flex items-center gap-3">
                                <span class="material-symbols-outlined text-indigo-400 text-[24px]">reviews</span>
                                Ratings & Reviews
                                <span class="px-2 py-0.5 rounded-full bg-white/5 text-white/50 text-[10px]" x-text="selectedMovie.comments ? selectedMovie.comments.length : 0"></span>
                            </h3>

                            <!-- Submit Form -->
                            <div class="bg-gradient-to-br from-[#0a0a0f] to-[#050508] p-5 rounded-2xl border border-white/5 shadow-inner mb-8 relative overflow-hidden group">
                                <div class="absolute inset-0 bg-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                                
                                <div class="relative z-10">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
                                        <div class="flex gap-1">
                                            <template x-for="i in 5">
                                                <button @mouseenter="hoveredRating = i" 
                                                        @mouseleave="hoveredRating = 0" 
                                                        @click="newRating = i" 
                                                        class="transition-all duration-300 transform hover:scale-125 focus:outline-none"
                                                        :class="(hoveredRating >= i || newRating >= i) ? 'text-yellow-400 drop-shadow-[0_0_10px_rgba(234,179,8,0.5)]' : 'text-white/20'">
                                                    <span class="material-symbols-outlined text-[28px]" :class="(hoveredRating >= i || newRating >= i) ? 'filled' : ''" style="font-variation-settings: 'FILL' 1;">star</span>
                                                </button>
                                            </template>
                                        </div>
                                        <span class="text-xs font-bold uppercase tracking-wider transition-colors duration-300" 
                                              :class="newRating > 0 ? 'text-yellow-400' : 'text-white/30'"
                                              x-text="newRating === 0 ? 'Select Rating' : newRating + ' out of 5 stars'"></span>
                                    </div>
                                    
                                    <div class="relative">
                                        <textarea x-model="commentText" 
                                                  class="w-full bg-black/40 border border-white/10 rounded-xl p-4 pb-14 text-sm text-white placeholder-white/30 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/50 outline-none resize-none transition-all duration-300 custom-scrollbar"
                                                  rows="3" 
                                                  placeholder="Share your thoughts about this title..."></textarea>
                                                  
                                        <div class="absolute bottom-3 right-3">
                                            <button @click="submitReview()" 
                                                    :disabled="isSubmitting || newRating === 0"
                                                    class="px-5 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all duration-300 flex items-center gap-2 overflow-hidden relative group/btn"
                                                    :class="(isSubmitting || newRating === 0) ? 'bg-white/5 text-white/30 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-[0_0_20px_rgba(99,102,241,0.4)] hover:shadow-[0_0_30px_rgba(99,102,241,0.6)] hover:-translate-y-0.5'">
                                                
                                                <span x-show="!isSubmitting" class="relative z-10 flex items-center gap-2">
                                                    Post Review <span class="material-symbols-outlined text-[16px]">send</span>
                                                </span>
                                                
                                                <span x-show="isSubmitting" class="relative z-10 flex items-center gap-2">
                                                    Posting <span class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments List -->
                            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar relative">
                                <template x-for="(comment, index) in selectedMovie.comments" :key="comment.comment_id || index">
                                    <div class="p-5 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-white/10 hover:bg-white/[0.04] transition-all duration-300 transform-gpu"
                                         x-data="{ show: false }" x-init="setTimeout(() => show = true, index * 50)"
                                         :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                                         style="transition-duration: 500ms;">
                                        
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-black uppercase shadow-inner">
                                                    <span x-text="(comment.user_name || comment.user_id || 'U').substring(0, 1)"></span>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-bold text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]" x-text="comment.user_name || 'User ' + (comment.user_id || '')"></span>
                                                        <span x-show="comment.user_id === 'You'" class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">You</span>
                                                    </div>
                                                    <span class="text-[10px] text-white/40 font-mono" x-text="new Date(comment.created_at).toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit'})"></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Rating Display -->
                                            <div class="flex items-center gap-0.5 bg-black/40 px-2 py-1 rounded-lg border border-white/5" x-show="comment.rating">
                                                <span class="font-bold text-yellow-400 text-xs mr-1" x-text="comment.rating"></span>
                                                <span class="material-symbols-outlined text-[14px] text-yellow-400" style="font-variation-settings: 'FILL' 1;">star</span>
                                            </div>
                                        </div>
                                        
                                        <p class="text-sm text-white/70 leading-relaxed ml-[52px]" x-text="comment.comment_text"></p>
                                    </div>
                                </template>

                                <div x-show="!selectedMovie.comments || selectedMovie.comments.length === 0" class="py-12 flex flex-col items-center justify-center text-center opacity-50">
                                    <span class="material-symbols-outlined text-6xl text-white/20 mb-4 animate-pulse">forum</span>
                                    <p class="text-sm font-bold text-white uppercase tracking-widest">No reviews yet</p>
                                    <p class="text-xs text-white/60 mt-2">Be the first to share your thoughts on this title.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>