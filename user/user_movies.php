<!-- User Movies View Container -->
<div x-show="currentTab === 'movies'" style="display: none;" class="absolute inset-0 w-full h-full p-8 lg:p-12 pb-24 overflow-y-auto custom-scrollbar">
    
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
        <template x-for="movie in filteredMovies" :key="movie.id || movie.movie_id">
            <div class="movie-card-container stagger-item">
                <div @click="openMovieDetail(movie)" class="group cursor-pointer relative rounded-2xl bg-[#050508] border border-white/[0.05] hover:border-indigo-500/40 transition-all duration-500 hover:-translate-y-2 shadow-2xl hover:shadow-[0_20px_40px_rgba(99,102,241,0.2)] overflow-hidden" x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false">
                    
                    <!-- Poster Image & Trailer Container -->
                    <div class="aspect-[2/3] w-full relative overflow-hidden bg-[#050508]">
                        <img :src="movie.img || movie.cover_image || 'https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster'" alt="Poster" loading="lazy" decoding="async" class="w-full h-full object-cover transition-all duration-700 absolute inset-0 z-10 group-hover:scale-110" :class="hovered && (movie.trailer || movie.video_url) ? 'opacity-0' : 'opacity-100'">
                        
                        <div class="absolute inset-0 z-0 transition-opacity duration-500 overflow-hidden pointer-events-none" :class="hovered ? 'opacity-100' : 'opacity-0'">
                            <template x-if="hovered && (movie.trailer || movie.video_url)">
                                <div x-data="{}"
                                     x-init="let p; $nextTick(() => { const iframe = $el.querySelector('iframe'); if(iframe) iframe.src = getYouTubeEmbedUrl(movie.trailer || movie.video_url, true); p = new Plyr($el.querySelector('.plyr-target'), { autoplay: true, muted: true, controls: [], clickToPlay: false, youtube: { noCookie: false, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1, disablekb: 1 } }); }); return () => { try { if (p) p.destroy(); } catch(e) {} }"
                                     class="absolute top-1/2 left-1/2 w-[350%] -translate-x-1/2 -translate-y-1/2 pointer-events-none z-0">
                                    <div class="plyr__video-embed w-full plyr-target">
                                        <iframe
                                             class="w-full h-full pointer-events-none"
                                             frameborder="0"
                                             allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                             allowfullscreen>
                                        </iframe>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="absolute inset-0 bg-gradient-to-t from-[#050508] via-[#050508]/20 to-transparent opacity-90 group-hover:opacity-100 transition-opacity duration-500 z-20 pointer-events-none"></div>

                        <!-- Rating Badge -->
                        <div class="absolute top-4 left-4 px-3 py-1.5 rounded-xl bg-black/60 backdrop-blur-md border border-yellow-500/30 flex items-center gap-1.5 text-xs font-black text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.3)] z-30">
                            <span class="material-symbols-outlined text-[16px]">star</span>
                            <span x-text="movie.rating ? movie.rating : '0.0'"></span>
                        </div>

                        <!-- Watchlist Button Overlay -->
                        <button @click.stop="isGuest ? window.location.href='../frontend/login.php' : toggleWatchlist(movie)" 
                                class="absolute top-4 right-4 z-40 group/watchlist w-10 h-10 flex items-center justify-center rounded-xl backdrop-blur-xl border transition-all duration-500 overflow-hidden transform-gpu"
                                :class="movie.inWatchlist ? 'bg-indigo-500/20 border-indigo-400/50 shadow-[0_0_20px_rgba(99,102,241,0.5)]' : 'bg-black/40 border-white/10 hover:border-indigo-500/50 hover:bg-black/60'">
                            <span class="material-symbols-outlined relative z-10 transition-all duration-500 transform group-hover/watchlist:scale-110"
                                :class="movie.inWatchlist ? 'text-indigo-400' : 'text-white/60 group-hover/watchlist:text-white'"
                                :style="movie.inWatchlist ? 'font-variation-settings: \'FILL\' 1;' : ''"
                                x-text="movie.inWatchlist ? 'bookmark_added' : 'bookmark_add'"></span>
                        </button>

                        <div x-show="!(hovered && (movie.trailer || movie.video_url))"
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-110"
                             class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30 scale-90 group-hover:scale-100 pointer-events-none">
                            <div class="w-16 h-16 rounded-full bg-indigo-500/90 backdrop-blur-sm flex items-center justify-center text-white shadow-[0_0_30px_rgba(99,102,241,0.8)]">
                                <span class="material-symbols-outlined text-[32px] ml-1">play_arrow</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Info -->
                    <div class="relative p-5 z-30 border-t border-white/[0.02]">
                        <h3 class="font-black text-lg text-white mb-1.5 truncate group-hover:text-indigo-400 transition-colors duration-300" x-text="movie.title"></h3>
                        <div class="flex items-center justify-between">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="(genre, idx) in (movie.genres && movie.genres.length ? movie.genres : ['Movie'])" :key="idx">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-indigo-500/20 text-indigo-300 bg-indigo-500/10" x-text="genre"></span>
                                </template>
                            </div>
                            <span class="text-[11px] text-white/40 font-bold mono" x-text="movie.created_at ? new Date(movie.created_at).getFullYear() : '2024'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="filteredMovies.length === 0" class="col-span-full py-20 text-center">
            <span class="material-symbols-outlined text-white/20 text-6xl mb-3">movie_off</span>
            <p class="text-sm font-bold text-white/40">No movies found matching your query.</p>
        </div>
    </div>

   
</div>

 <!-- Movie Detail & Playback Modal -->
    <div x-show="showMovieDetailModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-xl" @click="closeMovieDetail()"></div>
        
        <div class="relative w-full max-w-4xl bg-[#08080d] border border-white/10 rounded-3xl overflow-hidden shadow-2xl z-10 flex flex-col max-h-[90vh]" x-show="showMovieDetailModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            
            <template x-if="selectedMovie">
                <div class="flex flex-col h-full overflow-y-auto custom-scrollbar">
                    
                    <div class="relative aspect-video w-full bg-black">
                        <template x-if="selectedMovie?.actual_video_url && isYouTubeUrl(selectedMovie?.actual_video_url)">
                            <div x-data="{}"
                                x-init="let p; $nextTick(() => { const iframe = $el.querySelector('iframe'); if(iframe) iframe.src = getYouTubeEmbedUrl(selectedMovie?.actual_video_url, false); p = new Plyr($el.querySelector('.plyr-target'), { autoplay: true, controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen'], youtube: { noCookie: false, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1 } }); }); return () => { try { if (p) p.destroy(); } catch(e) {} }"
                                class="absolute inset-0 w-full h-full">
                                <div class="plyr__video-embed w-full h-full plyr-target">
                                    <iframe
                                        class="w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            </div>
                        </template>

                       <!-- Non-YouTube direct video file -->
                        <template x-if="selectedMovie?.actual_video_url && !isYouTubeUrl(selectedMovie?.actual_video_url)">
                            <div x-data="{ movieUrl: selectedMovie?.actual_video_url }"
                                x-init="let player;
                                        $nextTick(() => {
                                            const video = $el.querySelector('video');
                                            if (video && movieUrl) {
                                                video.src = movieUrl;
                                                player = new Plyr(video, {
                                                    autoplay: false,   // or true, but browsers may block
                                                    controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen']
                                                });
                                            }
                                        });
                                        $cleanup(() => { if (player) player.destroy(); });"
                                class="absolute inset-0 w-full h-full">
                                <video class="w-full h-full object-contain"></video>
                            </div>
                        </template>

                        <template x-if="!selectedMovie?.actual_video_url">
                            <div class="absolute inset-0 flex items-center justify-center text-white/50 bg-black">
                                <div class="text-center">
                                    <span class="material-symbols-outlined text-5xl mb-3">videocam_off</span>
                                    <p class="text-sm">Full movie not available for this title</p>
                                </div>
                            </div>
                        </template>

                        <button @click="closeMovieDetail()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-black/60 border border-white/20 text-white flex items-center justify-center hover:bg-white/20 transition-all z-20">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-6">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-3xl font-black text-white" x-text="selectedMovie?.title"></h2>
                                    
                                    <!-- NEW: Watchlist Button inside Modal -->
                                    <button @click="isGuest ? window.location.href='../frontend/login.php' : toggleWatchlist(selectedMovie)" 
                                            class="px-3 py-1.5 rounded-xl border flex items-center gap-1.5 text-xs font-bold transition-all duration-300"
                                            :class="selectedMovie?.inWatchlist ? 'bg-indigo-500/20 text-indigo-400 border-indigo-500/40 shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-white/5 text-white/70 hover:text-white border-white/10 hover:border-indigo-500/40'">
                                        <span class="material-symbols-outlined text-[16px]"
                                            :style="selectedMovie?.inWatchlist ? 'font-variation-settings: \'FILL\' 1;' : ''"
                                            x-text="selectedMovie?.inWatchlist ? 'bookmark_added' : 'bookmark_add'"></span>
                                        <span x-text="selectedMovie?.inWatchlist ? 'Saved' : 'Add to Watchlist'"></span>
                                    </button>
                                    
                                    <!-- Host Party Button inside Modal -->
                                    <button @click="isGuest ? window.location.href='../frontend/login.php' : createParty(selectedMovie?.id || selectedMovie?.movie_id)"
                                            class="px-3 py-1.5 rounded-xl border border-red-500/40 bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white flex items-center gap-1.5 text-xs font-bold transition-all duration-300 shadow-[0_0_15px_rgba(239,68,68,0.1)] hover:shadow-[0_0_25px_rgba(239,68,68,0.4)]">
                                        <span class="material-symbols-outlined text-[16px]">celebration</span>
                                        <span>Host Party</span>
                                    </button>
                                </div>

                                <div class="flex items-center gap-3 mt-2">
                                    <!-- Global Rating -->
                                    <span class="text-xs font-bold text-yellow-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">star</span>
                                        <span x-text="selectedMovie?.rating || '0.0'"></span>
                                    </span>

                                    <!-- User Rating & Stats -->
                                    <template x-if="selectedMovie && selectedMovie?.user_rating > 0">
                                        <span class="text-xs font-bold text-indigo-400 flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-500/10 border border-indigo-500/20">
                                            <span class="material-symbols-outlined text-[14px]">star_half</span>
                                            <span>Your Rating: <span x-text="selectedMovie?.user_rating"></span>/5</span>
                                        </span>
                                    </template>

                                    <span class="text-xs text-white/40 font-mono" x-text="selectedMovie?.duration ? selectedMovie?.duration + ' mins' : ''"></span>
                                    <span class="text-xs text-white/40 font-mono" x-text="(selectedMovie?.view_count || 0) + ' views'"></span>
                                </div>
                            </div>

                            <!-- Genres -->
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="g in (selectedMovie?.genres || [])" :key="g">
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20" x-text="g"></span>
                                </template>
                            </div>
                        </div>

                        <p class="text-sm text-white/70 leading-relaxed" x-text="selectedMovie?.description || 'No description available for this movie.'"></p>

                        <!-- Rating and Comment Section -->
                        <div class="pt-8 mt-6 border-t border-white/10">
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-widest text-white mb-6 flex items-center gap-3">
                                    <span class="material-symbols-outlined text-indigo-400 text-[24px]">reviews</span>
                                    Ratings & Reviews
                                    <span class="px-2 py-0.5 rounded-full bg-white/5 text-white/50 text-[10px]" x-text="selectedMovie?.comments ? selectedMovie?.comments.length : 0"></span>
                                </h3>

                                <template x-if="!isGuest">
                                    <!-- Submit Review Form -->
                                    <div class="bg-gradient-to-br from-[#0a0a0f] to-[#050508] p-5 rounded-2xl border border-white/5 shadow-inner mb-8 relative overflow-hidden group">
                                        <div class="relative z-10">
                                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
                                                <div class="flex gap-1">
                                                    <template x-for="i in 5">
                                                        <button @mouseenter="hoveredRating = i" 
                                                                @mouseleave="hoveredRating = 0" 
                                                                @click="newRating = i" 
                                                                class="transition-all duration-300 transform hover:scale-125 focus:outline-none"
                                                                :class="(hoveredRating >= i || newRating >= i) ? 'text-yellow-400 drop-shadow-[0_0_10px_rgba(234,179,8,0.5)]' : 'text-white/20'">
                                                            <span class="material-symbols-outlined text-[28px]" :style="(hoveredRating >= i || newRating >= i) ? 'font-variation-settings: \'FILL\' 1;' : ''">star</span>
                                                        </button>
                                                    </template>
                                                </div>
                                                <span class="text-xs font-bold uppercase tracking-wider transition-colors duration-300" 
                                                    :class="newRating > 0 ? 'text-yellow-400' : 'text-white/30'"
                                                    x-text="newRating === 0 ? 'Select Rating' : newRating + ' out of 5 stars'"></span>
                                            </div>
                                            
                                            <div class="relative">
                                                <textarea x-model="commentText" 
                                                        class="w-full bg-black/40 border border-white/10 rounded-xl p-4 pb-14 text-sm text-white placeholder-white/30 focus:border-indigo-500/50 outline-none resize-none transition-all duration-300 custom-scrollbar"
                                                        rows="3" 
                                                        placeholder="Share your thoughts about this title..."></textarea>
                                                        
                                                <div class="absolute bottom-3 right-3">
                                                    <button @click="handleReviewSubmission()" 
                                                            :disabled="isSubmittingReview || (newRating === 0 && commentText.trim() === '')"
                                                            class="px-5 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all duration-300 flex items-center gap-2"
                                                            :class="(isSubmittingReview || (newRating === 0 && commentText.trim() === '')) ? 'bg-white/5 text-white/30 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]'">
                                                        <span x-show="!isSubmittingReview" class="flex items-center gap-2">
                                                            Post Review <span class="material-symbols-outlined text-[16px]">send</span>
                                                        </span>
                                                        <span x-show="isSubmittingReview" class="flex items-center gap-2">
                                                            Posting <span class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="isGuest">
                                    <div class="bg-gradient-to-br from-[#0a0a0f] to-[#050508] p-5 rounded-2xl border border-white/5 shadow-inner mb-8 relative overflow-hidden group">
                                        <div class="flex flex-col items-center justify-center py-8 text-center">
                                            <span class="material-symbols-outlined text-5xl text-white/20 mb-3">lock</span>
                                            <h4 class="text-lg font-bold text-white/80 mb-2">Login to Rate & Comment</h4>
                                            <p class="text-sm text-white/50 mb-4">Share your thoughts and ratings with the community.</p>
                                            <a href="../frontend/login.php" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition-all hover:shadow-[0_0_20px_rgba(99,102,241,0.4)]">
                                                Login / Register
                                            </a>
                                        </div>
                                    </div>
                                </template>
                                
                                <!-- Comments List -->
                                <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar relative">
                                    <template x-for="comment in (selectedMovie?.comments || [])" :key="String(comment.id || comment.comment_id)">
                                        <div :id="'comment-' + (comment.id || comment.comment_id)" class="p-4 bg-white/5 rounded-xl border border-white/5 mb-3 transition-all duration-300 group">
                                            <div class="flex justify-between items-start mb-1">
                                                <div class="flex items-center gap-2.5">
                                                    <!-- Comment Profile Avatar & Border -->
                                                    <div class="relative w-8 h-8 flex items-center justify-center shrink-0">
                                                        <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(comment.user_name || 'User') + '&background=ef4444&color=fff&bold=true'" class="w-8 h-8 rounded-full border border-red-500/50 absolute z-0">
                                                        <template x-if="comment.border_preview || (comment.user_name === '<?php echo htmlspecialchars($userName); ?>' && activeBorderId !== 1)">
                                                            <img :src="comment.border_preview || availableBorders.find(b => b.id === activeBorderId)?.preview" class="absolute inset-0 w-11 h-11 max-w-none -ml-1.5 -mt-1.5 pointer-events-none object-contain z-10">
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-bold text-white block leading-none" x-text="comment.user_name || 'User'"></span>
                                                        <template x-if="comment.rating">
                                                            <div class="inline-flex items-center gap-0.5 bg-black/40 px-1.5 py-0.5 rounded border border-white/5 mt-1">
                                                                <span class="font-bold text-yellow-400 text-[9px]" x-text="comment.rating"></span>
                                                                <span class="material-symbols-outlined text-[10px] text-yellow-400" style="font-variation-settings: 'FILL' 1;">star</span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-white/40" x-text="comment.created_at || ''"></span>
                                                    <button @click="isGuest ? window.location.href='../frontend/login.php' : openReportItemModal(comment.comment_id || comment.id, 'comment')" class="opacity-0 group-hover:opacity-100 text-white/30 hover:text-red-500 transition-all focus:opacity-100" title="Report Comment">
                                                        <span class="material-symbols-outlined text-[16px]">flag</span>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <p class="text-xs text-white/80 my-2" x-text="comment.comment || comment.content || ''"></p>

                                            <!-- Like & Reply Buttons -->
                                            <div class="flex items-center gap-4 mt-2 pt-2 border-t border-white/5 text-[11px]">
                                                <button @click="isGuest ? window.location.href='../frontend/login.php' : likeComment(comment.id || comment.comment_id)"  class="flex items-center gap-1 text-white/50 hover:text-red-400 transition-colors">
                                                    <span class="material-symbols-outlined text-[14px]" :class="comment.is_liked ? 'text-red-500' : ''" :style="comment.is_liked ? 'font-variation-settings: \'FILL\' 1;' : ''">favorite</span>
                                                    <span x-text="comment.likes_count || 0"></span>
                                                </button>
                                                <button @click="isGuest ? window.location.href='../frontend/login.php' : replyingToCommentId = replyingToCommentId === (comment.id || comment.comment_id) ? null : (comment.id || comment.comment_id)"  class="text-white/50 hover:text-white transition-colors">Reply</button>
                                                
                                                <template x-if="comment.replies && comment.replies.length > 0">
                                                    <button @click="comment.show_replies = comment.show_replies === undefined ? false : !comment.show_replies" 
                                                            class="ml-auto group relative flex items-center gap-2 px-2.5 py-1 rounded-full bg-white/5 hover:bg-white/10 border border-white/5 hover:border-indigo-500/30 transition-all duration-300 overflow-hidden">
                                                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                                        <div class="relative z-10 flex items-center gap-1.5">
                                                            <span class="material-symbols-outlined text-[14px] text-indigo-400 transition-all duration-500 ease-out"
                                                                  :class="comment.show_replies !== false ? 'rotate-180 text-purple-400 drop-shadow-[0_0_8px_rgba(168,85,247,0.5)]' : 'rotate-0'">
                                                                keyboard_arrow_down
                                                            </span>
                                                            <span class="text-[9px] font-bold tracking-widest uppercase transition-colors duration-300"
                                                                  :class="comment.show_replies !== false ? 'text-purple-300' : 'text-white/70 group-hover:text-white'"
                                                                  x-text="comment.show_replies !== false ? 'Hide' : 'Replies'"></span>
                                                            <div x-show="comment.show_replies === false" 
                                                                  x-transition:enter="transition ease-out duration-300"
                                                                  x-transition:enter-start="opacity-0 scale-50"
                                                                  x-transition:enter-end="opacity-100 scale-100"
                                                                  class="flex items-center justify-center min-w-[16px] h-[16px] rounded-full bg-indigo-500/20 text-indigo-300 text-[9px] font-black"
                                                                  x-text="comment.replies.length"></div>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>

                                            <!-- Reply Input -->
                                            <div x-show="replyingToCommentId === (comment.id || comment.comment_id)" 
                                                 x-transition:enter="transition-all ease-out duration-300"
                                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                 class="mt-3 flex gap-2">
                                                <input type="text" x-model="replyInputText" placeholder="Write a reply..." class="flex-1 bg-black/40 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white outline-none focus:border-indigo-500/50 transition-colors duration-300">
                                                <button @click="postReply(comment.id || comment.comment_id)" class="px-3 py-1.5 bg-indigo-600/80 hover:bg-indigo-500 border border-indigo-500/50 rounded-lg text-xs font-bold text-white transition-all duration-300 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)]">Send</button>
                                            </div>

                                            <!-- Nested Replies -->
                                            <div x-show="comment.show_replies !== false && comment.replies && comment.replies.length > 0" 
                                                 x-transition:enter="transition-all ease-out duration-400"
                                                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95 blur-sm"
                                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100 blur-0"
                                                 x-transition:leave="transition-all ease-in duration-300"
                                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100 blur-0"
                                                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95 blur-sm"
                                                 class="pl-4 mt-3 border-l-2 border-indigo-500/30 space-y-2 relative origin-top">
                                                <template x-for="reply in comment.replies" :key="reply.id || reply.comment_id">
                                                    <div :id="'reply-' + (reply.id || reply.comment_id)" 
                                                        class="p-2.5 bg-black/30 rounded-lg border border-white/5 relative group"
                                                        x-init="console.log('Reply object:', reply)">
                                                        <div class="flex justify-between items-start mb-1">
                                                            <div class="flex items-center gap-2">
                                                                <!-- Reply Profile Avatar & Border -->
                                                                <div class="relative w-6 h-6 flex items-center justify-center shrink-0">
                                                                    <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(reply.user_name || 'User') + '&background=ef4444&color=fff&bold=true'" class="w-6 h-6 rounded-full border border-red-500/50 absolute z-0">
                                                                    <template x-if="reply.border_preview || (reply.user_name === '<?php echo htmlspecialchars($userName); ?>' && activeBorderId !== 1)">
                                                                        <img :src="reply.border_preview || availableBorders.find(b => b.id === activeBorderId)?.preview" class="absolute inset-0 w-8 h-8 max-w-none -ml-1 -mt-1 pointer-events-none object-contain z-10">
                                                                    </template>
                                                                </div>
                                                                <span class="text-[11px] font-bold text-white/90" x-text="reply.user_name || 'User'"></span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[9px] text-white/30" x-text="reply.created_at || ''"></span>
                                                                <button @click="isGuest ? window.location.href='../frontend/login.php' : openReportItemModal(reply.comment_id || reply.id || reply.reply_id, 'reply')" class="opacity-0 group-hover:opacity-100 text-white/30 hover:text-red-500 transition-all focus:opacity-100" title="Report Reply">
                                                                    <span class="material-symbols-outlined text-[14px]">flag</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <p class="text-[11px] text-white/70" x-text="reply.comment || reply.content || ''"></p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <div x-show="!selectedMovie?.comments || selectedMovie?.comments.length === 0" class="py-12 flex flex-col items-center justify-center text-center opacity-50">
                                        <span class="material-symbols-outlined text-6xl text-white/20 mb-4 animate-pulse">forum</span>
                                        <p class="text-sm font-bold text-white uppercase tracking-widest">No reviews yet</p>
                                        <p class="text-xs text-white/60 mt-2">Be the first to share your thoughts on this title.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>