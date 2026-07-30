<!-- Movies View -->
<div x-show="currentTab === 'movies'" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full">
    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Movie Library</h2>
            <p class="text-xs text-white/50 mono">Manage and curate media catalog assets</p>
        </div>
        <button @click="openAddMovieModal()" class="relative px-6 py-3 overflow-hidden rounded-xl group hover:scale-105 active:scale-95 transition-all duration-300 shadow-xl shadow-red-500/20">
            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-600 via-red-500 to-red-800 opacity-80 group-hover:opacity-100 transition-opacity"></span>
            <span class="absolute -inset-1 w-full h-full bg-gradient-to-r from-red-500 via-red-400 to-red-600 blur-xl opacity-30 group-hover:opacity-60 transition-opacity animate-pulse"></span>
            <div class="relative flex items-center gap-2 text-white font-bold text-sm tracking-wide">
                <span class="material-symbols-outlined text-[18px]">add</span> Add Movie
            </div>
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8 pb-10">
        <template x-for="movie in movies" :key="movie.id">
            <div class="movie-card-container stagger-item">
                <div class="glass-card movie-card rounded-2xl h-80 relative group cursor-pointer border border-white/10 hover:border-red-500/50 transition-all duration-300 shadow-lg hover:shadow-[0_0_30px_rgba(239,68,68,0.3)] overflow-hidden" 
                     x-data="{ isHovered: false }" @mouseenter="isHovered = true" @mouseleave="isHovered = false"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100">
             
                     <!-- Glowing orb effect behind poster -->
                     <div class="absolute -top-10 -left-10 w-32 h-32 bg-red-500/30 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                     <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-purple-500/30 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                     
                     <img :src="movie.img || 'https://via.placeholder.com/300x450/050505/ffffff?text=No+Poster'" alt="Poster" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 opacity-80 group-hover:opacity-100">
                     <div class="absolute inset-0 bg-gradient-to-t from-[#030305] via-[#030305]/60 to-transparent opacity-90 group-hover:opacity-70 transition-opacity"></div>
                    
                     <!-- Action buttons on card hover -->
                     <div class="absolute top-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-[-10px] group-hover:translate-y-0 z-20">
                         <button @click.stop="openEditMovieModal(movie)" class="w-8 h-8 rounded-full bg-black/60 border border-white/20 backdrop-blur-md flex items-center justify-center hover:bg-white text-white hover:text-black transition-colors hover:scale-110" title="Edit">
                             <span class="material-symbols-outlined text-[14px]">edit</span>
                         </button>
                         <button @click.stop="deleteMovie(movie.id)" class="w-8 h-8 rounded-full bg-black/60 border border-white/20 backdrop-blur-md flex items-center justify-center hover:bg-red-600 text-white transition-colors hover:scale-110" title="Delete">
                             <span class="material-symbols-outlined text-[14px]">delete</span>
                         </button>
                     </div>
                    
                     <div class="absolute bottom-0 left-0 right-0 p-5 transform transition-transform duration-300 translate-y-2 group-hover:translate-y-0">
                         <div class="flex items-center gap-2 mb-2 flex-wrap">
                             <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white/10 text-white border border-white/10 mono" x-text="movie.year || 'N/A'"></span>
                             <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 truncate max-w-[110px]" x-text="movie.genre || 'N/A'"></span>
                             <div class="flex items-center gap-1 text-yellow-400 text-[10px] font-bold bg-yellow-500/10 px-2 py-0.5 rounded border border-yellow-500/20">
                                 <span class="material-symbols-outlined text-[12px]">star</span>
                                 <span x-text="movie.rating || '0'"></span>
                             </div>
                         </div>
                         <h4 class="font-bold text-lg text-white leading-tight group-hover:text-red-400 transition-colors drop-shadow-md truncate" x-text="movie.title"></h4>
                     </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Movie Modal -->
    <div x-show="movieModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="glass-card rounded-2xl p-8 max-w-2xl w-full relative max-h-[90vh] overflow-y-auto" @click.away="movieModalOpen = false">
            <button @click="movieModalOpen = false" class="absolute top-4 right-4 text-white/40 hover:text-white transition-colors z-10">
                <span class="material-symbols-outlined">close</span>
            </button>
            <h3 class="text-xl font-bold text-white mb-6" x-text="editingMovie ? 'Edit Movie' : 'Add New Movie'"></h3>
            
            <div x-data="{ movieTab: 'details' }">
                <div class="flex border-b border-white/10 mb-6">
                    <button class="px-4 py-2 font-semibold text-sm transition-colors border-b-2" :class="movieTab === 'details' ? 'border-red-500 text-white' : 'border-transparent text-white/40 hover:text-white'" @click="movieTab = 'details'">Details & Media</button>
                    <button x-show="editingMovie" class="px-4 py-2 font-semibold text-sm transition-colors border-b-2" :class="movieTab === 'comments' ? 'border-red-500 text-white' : 'border-transparent text-white/40 hover:text-white'" @click="movieTab = 'comments'">Comments</button>
                </div>

                <!-- Details Tab -->
                <div x-show="movieTab === 'details'" class="space-y-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Image Upload Placeholder -->
                        <div class="w-full md:w-1/3">
                            <input type="file" x-ref="moviePosterInput" class="hidden" accept="image/*" @change="handleFileUpload($event, val => newMovie.img = val)">
                            <div class="aspect-[2/3] rounded-xl border-2 border-dashed border-white/20 bg-white/5 hover:bg-white/10 hover:border-red-500/50 transition-all flex flex-col items-center justify-center cursor-pointer relative overflow-hidden group" @click="$refs.moviePosterInput.click()">
                                <template x-if="newMovie.img">
                                    <img :src="newMovie.img" class="absolute inset-0 w-full h-full object-cover">
                                </template>
                                <template x-if="!newMovie.img">
                                    <div class="text-center p-4">
                                        <span class="material-symbols-outlined text-4xl text-white/40 mb-2 group-hover:text-red-400 transition-colors">add_photo_alternate</span>
                                        <p class="text-xs text-white/60 font-medium uppercase tracking-wide">Upload Poster</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="w-full md:w-2/3 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Title</label>
                                <input type="text" x-model="newMovie.title" placeholder="e.g. Blade Runner 2049" class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Year</label>
                                    <input type="text" x-model="newMovie.year" class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Rating</label>
                                    <input type="text" x-model="newMovie.rating" class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors">
                                </div>
                            </div>

                            <!-- Interactive Genre Selection -->
                            <div>
                                <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Genres Tag Selection</label>
                                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto p-3 bg-[#030305]/80 border border-white/10 rounded-xl">
                                    <template x-for="genre in allGenres" :key="genre">
                                        <button type="button" @click="toggleGenre(genre)" 
                                                :class="(newMovie.genres || []).includes(genre) ? 'bg-red-500/20 text-red-400 border-red-500/50' : 'bg-white/5 text-white/50 border-white/10 hover:text-white'" 
                                                class="px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors flex items-center gap-1">
                                            <span x-text="genre"></span>
                                            <span class="material-symbols-outlined text-[12px]" x-text="(newMovie.genres || []).includes(genre) ? 'check' : 'add'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Description</label>
                                <textarea x-model="newMovie.description" rows="3" class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors resize-none"></textarea>
                            </div>
                           <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Video URL</label>
                            <div class="flex items-center bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 focus-within:border-red-500/50 transition-colors">
                                <span class="material-symbols-outlined text-white/40 mr-2 text-[18px]">smart_display</span>
                                <input type="text" x-model="newMovie.video_url" placeholder="https://youtube.com/..." class="w-full bg-transparent text-white focus:outline-none">
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-white/10">
                        <button @click="movieModalOpen = false" class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-semibold transition-colors">Cancel</button>
                        <button @click="saveMovie()" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white rounded-xl text-sm font-bold transition-colors">Save Movie</button>
                    </div>
                </div>

                <!-- Comments Tab -->
                <div x-show="movieTab === 'comments'" class="space-y-4" style="display: none;">
                    <template x-if="newMovie.comments && newMovie.comments.length > 0">
                        <div class="space-y-3">
                            <template x-for="comment in newMovie.comments" :key="comment.id">
                                <div class="bg-white/5 border border-white/10 rounded-xl p-4 flex gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex-shrink-0 flex items-center justify-center font-bold text-sm" x-text="comment.user.charAt(0)"></div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-1">
                                            <h5 class="font-bold text-sm text-white" x-text="comment.user"></h5>
                                            <span class="text-[10px] text-white/40 mono" x-text="comment.date"></span>
                                        </div>
                                        <p class="text-sm text-white/70" x-text="comment.text"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!newMovie.comments || newMovie.comments.length === 0">
                        <div class="text-center py-8">
                            <span class="material-symbols-outlined text-4xl text-white/20 mb-2">forum</span>
                            <p class="text-sm text-white/40">No comments logged for this title.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>