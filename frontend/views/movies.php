<!-- Movies View Container -->
<div data-tab-panel="movies"  style="display: none;" class="relative w-full min-h-full p-8 lg:p-12 overflow-y-auto">
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10 stagger-item">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-3xl font-black text-white tracking-tight">Movie Library</h2>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20" x-text="movies ? movies.length + ' titles' : '0 titles'"></span>
            </div>
            <p class="text-xs text-white/40 font-mono">Manage, edit, and organize movie catalog items</p>
        </div>
        <button @click="openAddMovieModal()" class="relative group px-6 py-3.5 rounded-2xl overflow-hidden font-bold text-sm text-white transition-all duration-300 hover:scale-105 active:scale-95 shadow-lg shadow-red-600/25">
            <div class="absolute inset-0 bg-gradient-to-r from-red-600 via-rose-600 to-red-700 group-hover:opacity-90 transition-opacity"></div>
            <div class="relative flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span>Add New Movie</span>
            </div>
        </button>
    </div>

    <!-- Movie Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 pb-12">
        <template x-for="movie in movies" :key="movie.id">
            <div class="movie-card-container stagger-item">
                
                <!-- 1. ADD x-data, @mouseenter, and @mouseleave HERE -->
                <div x-data="{ isHovered: false }" 
                    @mouseenter="isHovered = true" 
                    @mouseleave="isHovered = false"
                    class="group relative rounded-2xl bg-[#08080c] border border-white/[0.08] hover:border-red-500/40 transition-all duration-300 hover:-translate-y-1.5 shadow-xl hover:shadow-[0_12px_30px_rgba(239,68,68,0.15)] overflow-hidden">
                    
                    <div class="aspect-[2/3] w-full relative overflow-hidden bg-white/5">
                        <img :src="movie.img || movie.cover_image || 'https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster'" alt="Poster" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        
                        <!-- 2. INSERT THE TRAILER IFRAME HERE -->
                        <template x-if="isHovered && movie.trailer && isYouTubeUrl(movie.trailer)">
                            <div x-data="{}"
                                 x-init="let p; $nextTick(() => { const iframe = $el.querySelector('iframe'); if (iframe) iframe.src = getYouTubeEmbedUrl(movie.trailer, true); p = new Plyr($el.querySelector('.plyr-target'), { autoplay: true, muted: true, controls: [], clickToPlay: false, youtube: { noCookie: false, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1, disablekb: 1 } }); }); return () => { try { if (p) p.destroy(); } catch(e) {} }"
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

                        <div class="absolute inset-0 bg-gradient-to-t from-[#08080c] via-[#08080c]/40 to-transparent opacity-90 group-hover:opacity-75 transition-opacity duration-300 z-10"></div>
                        
                        <!-- Rating Badge on Poster -->
                        <div class="absolute top-3 left-3 px-2.5 py-1 rounded-xl bg-black/60 backdrop-blur-md border border-white/10 flex items-center gap-1.5 text-xs font-bold text-yellow-400 z-20">
                            <span class="material-symbols-outlined text-[14px]">star</span>
                            <span x-text="movie.rating ? movie.rating : '0.0'"></span>
                        </div>

                        <!-- Hover Actions -->
                        <div class="absolute top-3 right-3 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-[-8px] group-hover:translate-y-0 z-20">
                            <button @click.stop="openEditMovieModal(movie)" class="w-8 h-8 rounded-xl bg-black/70 border border-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                            </button>
                            <button @click.stop="deleteMovie(movie.id)" class="w-8 h-8 rounded-xl bg-black/70 border border-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-red-600 transition-colors" title="Delete">
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Card Details -->
                    <div class="p-4 space-y-2 relative z-20">
                        <h4 class="font-bold text-base text-white group-hover:text-red-400 transition-colors truncate" x-text="movie.title"></h4>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/[0.06] text-white/70 border border-white/5 font-mono" x-text="movie.year || movie.duration || 'N/A'"></span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 truncate max-w-[100px]" x-text="movie.genre || 'N/A'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Redesigned Modal (Teleported to body) -->
    <template x-teleport="body">
        <div x-show="movieModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-black/85 backdrop-blur-md" style="display: none;" x-transition.opacity>
            <div class="w-full max-w-2xl bg-[#0c0c12] border border-white/10 rounded-3xl shadow-2xl overflow-hidden relative max-h-[90vh] flex flex-col" @click.away="movieModalOpen = false">
                
                <!-- Modal Header Bar -->
                <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500">
                            <span class="material-symbols-outlined" x-text="editingMovie ? 'movie_edit' : 'video_call'"></span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white leading-none mb-1" x-text="editingMovie ? 'Edit Movie Details' : 'Create New Movie'"></h3>
                            <p class="text-xs text-white/40">Fill in title details, genres, media and metadata</p>
                        </div>
                    </div>
                    <button @click="movieModalOpen = false" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/50 hover:text-white flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div x-data="{ movieTab: 'details' }" class="p-8 overflow-y-auto flex-1 space-y-6">
                    
                    <!-- Segmented Tab Controls -->
                    <div class="grid grid-cols-2 p-1.5 bg-black/40 border border-white/10 rounded-2xl max-w-xs">
                        <button class="py-2 px-4 text-xs font-bold rounded-xl transition-all duration-200" :class="movieTab === 'details' ? 'bg-red-600 text-white shadow-md' : 'text-white/40 hover:text-white'" @click="movieTab = 'details'">Details & Media</button>
                        <button x-show="editingMovie" class="py-2 px-4 text-xs font-bold rounded-xl transition-all duration-200" :class="movieTab === 'comments' ? 'bg-red-600 text-white shadow-md' : 'text-white/40 hover:text-white'" @click="movieTab = 'comments'">Comments</button>
                    </div>

                    <!-- Details Tab Content -->
                    <div x-show="movieTab === 'details'" class="space-y-6">
                        <div class="flex flex-col md:flex-row gap-6">
                            
                            <!-- Modern Poster Box -->
                            <div class="w-full md:w-1/3 flex-shrink-0">
                                <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Poster Media</label>
                                <input type="file" x-ref="moviePosterInput" class="hidden" accept="image/*" @change="handleFileUpload($event, val => newMovie.img = val)">
                                <div class="aspect-[2/3] rounded-2xl border-2 border-dashed border-white/15 bg-white/[0.02] hover:bg-white/[0.05] hover:border-red-500/50 transition-all duration-300 flex flex-col items-center justify-center cursor-pointer relative overflow-hidden group shadow-inner" @click="$refs.moviePosterInput.click()">
                                    <template x-if="newMovie.img">
                                        <div class="w-full h-full relative">
                                            <img :src="newMovie.img" class="w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-xs font-semibold">
                                                <span class="material-symbols-outlined text-2xl mb-1">flip_camera_ios</span>
                                                <span>Change Poster</span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!newMovie.img">
                                        <div class="text-center p-4">
                                            <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 group-hover:text-red-400 transition-all">
                                                <span class="material-symbols-outlined text-2xl text-white/40 group-hover:text-red-400">add_photo_alternate</span>
                                            </div>
                                            <p class="text-xs font-bold text-white/70 mb-0.5">Upload Poster</p>
                                            <p class="text-[10px] text-white/30">PNG, JPG or WEBP</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Inputs Container -->
                            <div class="w-full md:w-2/3 space-y-4">
                                <div>
                                    <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Movie Title</label>
                                    <input type="text" x-model="newMovie.title" placeholder="e.g. Blade Runner 2049" class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/60 focus:ring-2 focus:ring-red-500/20 transition-all placeholder:text-white/20">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Duration (minutes)</label>
                                        <input type="number" x-model="newMovie.duration" placeholder="e.g. 120" class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/60 focus:ring-2 focus:ring-red-500/20 transition-all placeholder:text-white/20">
                                    </div>

                                    <!-- Locked Rating Badge -->
                                    <div>
                                        <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Rating (User Reviews)</label>
                                        <div class="flex items-center justify-between bg-white/[0.02] border border-white/10 rounded-2xl px-4 py-3 select-none">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-yellow-400 text-[18px]">star</span>
                                                <span class="text-sm font-bold text-white" x-text="newMovie.rating ? newMovie.rating + ' / 10' : '0.0'"></span>
                                            </div>
                                            <span class="material-symbols-outlined text-white/20 text-[16px]" title="Calculated automatically from audience ratings">lock</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Genre Tags Picker -->
                                <div>
                                    <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Assign Genres</label>
                                    <div class="flex flex-wrap gap-2 max-h-36 overflow-y-auto p-3 bg-white/[0.02] border border-white/10 rounded-2xl">
                                        <template x-for="genre in (availableGenres && availableGenres.length ? availableGenres : (typeof allGenres !== 'undefined' && allGenres && allGenres.length ? allGenres : ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Thriller', 'Romance', 'Animation', 'Adventure']))" :key="typeof genre === 'object' ? (genre.id || genre.name) : genre">
                                            <button type="button" 
                                                    @click="toggleGenre(genre)" 
                                                    :class="isGenreSelected(genre) 
                                                        ? 'bg-red-500/20 text-red-400 border-red-500/60 shadow-[0_0_12px_rgba(239,68,68,0.25)]' 
                                                        : 'bg-white/5 text-white/50 border-white/10 hover:text-white hover:bg-white/10'" 
                                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all duration-200 flex items-center gap-1.5 cursor-pointer select-none">
                                                <span x-text="typeof genre === 'object' ? genre.name : genre"></span>
                                                <span class="material-symbols-outlined text-[13px]" x-text="isGenreSelected(genre) ? 'check' : 'add'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Synopsis</label>
                                    <textarea x-model="newMovie.description" rows="3" placeholder="Brief summary of the storyline..." class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/60 focus:ring-2 focus:ring-red-500/20 transition-all resize-none placeholder:text-white/20"></textarea>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Trailer URL</label>
                                    <div class="flex items-center bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 focus-within:border-red-500/60 focus-within:ring-2 focus-within:ring-red-500/20 transition-all">
                                        <span class="material-symbols-outlined text-white/30 mr-2.5 text-[18px]">movie</span>
                                        <input type="text" x-model="newMovie.trailer" placeholder="https://youtube.com/watch?v=..." class="w-full bg-transparent text-sm text-white focus:outline-none placeholder:text-white/20">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest mb-2">Actual Movie Video URL</label>
                                    <div class="flex items-center bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 focus-within:border-indigo-500/60 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all">
                                        <span class="material-symbols-outlined text-white/30 mr-2.5 text-[18px]">smart_display</span>
                                        <input type="text" x-model="newMovie.actual_video_url" placeholder="https://mp4-server.com/movie.mp4" class="w-full bg-transparent text-sm text-white focus:outline-none placeholder:text-white/20">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-white/5">
                            <button @click="movieModalOpen = false" class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition-colors">Cancel</button>
                            <button @click="saveMovie()" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-xs font-bold text-white shadow-lg shadow-red-600/30 transition-all hover:scale-105 active:scale-95">Save Changes</button>
                        </div>
                    </div>

                    <!-- Comments Tab Content -->
                    <div x-show="movieTab === 'comments'" class="space-y-4" style="display: none;">
                        <!-- Loading -->
                        <div x-show="loadingMovieComments" class="text-white/50 text-sm">Loading comments...</div>

                        <!-- Nested Comments List -->
                        <template x-if="!loadingMovieComments && nestedMovieComments.length > 0">
                            <div class="space-y-4">
                                <template x-for="comment in nestedMovieComments" :key="comment.id">
                                    <!-- Parent Comment -->
                                    <div class="rounded-2xl p-4 transition-all duration-700 border" :class="highlightCommentId == comment.id ? 'bg-red-500/10 border-red-500/50 shadow-[0_0_20px_rgba(239,68,68,0.2)]' : 'bg-white/[0.03] border-white/10'" :id="'admin-comment-' + comment.id">
                                        <!-- Header -->
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="relative w-8 h-8 shrink-0 overflow-visible" style="width: 2rem; height: 2rem;">
                                                    <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.18] bg-gradient-to-tr from-red-500 to-indigo-600">
                                                        <img x-show="comment.avatar_url" :src="resolveAvatarUrl ? resolveAvatarUrl(comment.avatar_url, comment.user_name || 'User') : comment.avatar_url" @error="$el.style.display='none'" class="absolute inset-0 h-full w-full object-cover" alt="">
                                                        <div x-show="!comment.avatar_url" class="absolute inset-0 flex items-center justify-center font-bold text-xs text-white uppercase"
                                                            x-text="comment.user_name ? comment.user_name.charAt(0) : 'U'"></div>
                                                    </div>
                                                    <template x-if="comment.border_preview">
                                                        <img :src="comment.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.38] object-contain pointer-events-none" alt="">
                                                    </template>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-xs text-white" x-text="comment.user_name"></p>
                                                    <p class="text-[10px] text-white/40 font-mono" x-text="new Date(comment.created_at).toLocaleString()"></p>
                                                </div>
                                            </div>
                                            <button @click="deleteComment(comment.id)"
                                                    class="text-red-400 hover:text-red-300 text-xs font-bold">Delete</button>
                                        </div>
                                        
                                        <!-- Comment Text -->
                                        <p class="text-xs text-white/80 leading-relaxed" x-text="comment.comment_text"></p>
                                        
                                        <!-- Likes & Reply Toggle -->
                                        <div class="flex items-center gap-3 mt-2 text-[11px] text-white/50">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">favorite</span>
                                                <span x-text="comment.likes_count || 0"></span>
                                            </span>
                                            
                                            <!-- Replies Toggle -->
                                            <template x-if="comment.replies && comment.replies.length > 0">
                                                <button @click="comment.show_replies = comment.show_replies === undefined ? false : !comment.show_replies"
                                                        class="flex items-center gap-1 text-indigo-400 hover:text-indigo-300">
                                                    <span class="material-symbols-outlined text-[14px]"
                                                        :class="comment.show_replies !== false ? 'rotate-180' : ''">keyboard_arrow_down</span>
                                                    <span x-text="comment.show_replies !== false ? 'Hide replies' : 'Show replies (' + comment.replies.length + ')'"></span>
                                                </button>
                                            </template>
                                        </div>
                                        
                                        <!-- Nested Replies -->
                                        <div x-show="comment.show_replies !== false && comment.replies && comment.replies.length > 0"
                                            x-transition:enter="transition-all ease-out duration-300"
                                            x-transition:enter-start="opacity-0 -translate-y-2"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="pl-4 mt-3 border-l-2 border-indigo-500/30 space-y-2">
                                            <template x-for="reply in comment.replies" :key="reply.id">
                                                <div class="rounded-lg p-3 transition-all duration-700 border" :class="highlightCommentId == reply.id ? 'bg-red-500/10 border-red-500/50 shadow-[0_0_20px_rgba(239,68,68,0.2)]' : 'bg-black/30 border-white/5'" :id="'admin-comment-' + reply.id">
                                                    <div class="flex justify-between items-start mb-1">
                                                        <div class="flex items-center gap-2">
                                                            <div class="relative w-6 h-6 shrink-0 overflow-visible" style="width: 1.5rem; height: 1.5rem;">
                                                                <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.18] bg-gradient-to-tr from-red-500 to-indigo-600">
                                                                    <img x-show="reply.avatar_url" :src="resolveAvatarUrl ? resolveAvatarUrl(reply.avatar_url, reply.user_name || 'User') : reply.avatar_url" @error="$el.style.display='none'" class="absolute inset-0 h-full w-full object-cover" alt="">
                                                                    <div x-show="!reply.avatar_url" class="absolute inset-0 flex items-center justify-center font-bold text-[10px] text-white uppercase"
                                                                        x-text="reply.user_name ? reply.user_name.charAt(0) : 'U'"></div>
                                                                </div>
                                                                <template x-if="reply.border_preview">
                                                                    <img :src="reply.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.38] object-contain pointer-events-none" alt="">
                                                                </template>
                                                            </div>
                                                            <span class="text-[11px] font-bold text-white/90" x-text="reply.user_name"></span>
                                                        </div>
                                                        <button @click="deleteComment(reply.id)"
                                                                class="text-red-400 hover:text-red-300 text-[10px] font-bold">Delete</button>
                                                    </div>
                                                    <p class="text-[11px] text-white/70" x-text="reply.comment_text"></p>
                                                    <div class="flex items-center gap-2 mt-1 text-[10px] text-white/40">
                                                        <span class="flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-[12px]">favorite</span>
                                                            <span x-text="reply.likes_count || 0"></span>
                                                        </span>
                                                        <span x-text="new Date(reply.created_at).toLocaleString()"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!loadingMovieComments && nestedMovieComments.length === 0">
                            <div class="text-center py-12">
                                <span class="material-symbols-outlined text-4xl text-white/15 mb-2">forum</span>
                                <p class="text-xs text-white/40">No comments for this movie yet.</p>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>
