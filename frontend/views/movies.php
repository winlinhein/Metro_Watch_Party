<!-- Movies View Container -->
<div data-tab-panel="movies"  style="display: none;" class="relative w-full min-h-full p-8 lg:p-12">
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
                        <img :src="movie.img || movie.cover_image || 'https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster'" alt="Poster" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        
                        <!-- 2. INSERT THE TRAILER IFRAME HERE -->
                        <template x-if="isHovered && movie.trailer && isYouTubeUrl(movie.trailer)">
                            <iframe 
                                class="absolute top-0 left-0 w-full h-full pointer-events-none z-0 object-cover" 
                                :src="getYouTubeEmbedUrl(movie.trailer, true)" 
                                frameborder="0" 
                                allow="autoplay; muted; encrypted-media">
                            </iframe>
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
                        <template x-if="newMovie.comments && newMovie.comments.length > 0">
                            <div class="space-y-3">
                                <template x-for="comment in newMovie.comments" :key="comment.id">
                                    <div class="bg-white/[0.03] border border-white/10 rounded-2xl p-4 flex gap-3.5">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-red-500 to-indigo-600 flex-shrink-0 flex items-center justify-center font-bold text-xs text-white uppercase" x-text="comment.user ? comment.user.charAt(0) : 'U'"></div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-center mb-1">
                                                <h5 class="font-bold text-xs text-white" x-text="comment.user"></h5>
                                                <span class="text-[10px] text-white/30 font-mono" x-text="comment.date"></span>
                                            </div>
                                            <p class="text-xs text-white/70 leading-relaxed" x-text="comment.text"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!newMovie.comments || newMovie.comments.length === 0">
                            <div class="text-center py-12">
                                <span class="material-symbols-outlined text-4xl text-white/15 mb-2">forum</span>
                                <p class="text-xs text-white/40">No audience comments recorded for this movie.</p>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>