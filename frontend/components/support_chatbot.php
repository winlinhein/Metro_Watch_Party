<div class="fixed bottom-[7.5rem] right-8 z-[110] flex flex-col items-end gap-3"
     x-data="nexusSupportChat()"
     x-cloak
     @keydown.escape.window="open && close()">

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-3 scale-95"
         class="w-[22rem] max-w-[calc(100vw-2rem)] h-[30rem] max-h-[74vh] flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#0c0c12] shadow-[0_24px_80px_-16px_rgba(0,0,0,0.9)]"
         style="display: none;">

        <div class="shrink-0 flex items-center justify-between gap-3 px-4 py-3 border-b border-white/10 bg-gradient-to-r from-indigo-500/15 to-red-600/15">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_18px_rgba(239,68,68,0.35)]">
                    <span class="material-symbols-outlined text-white text-[18px]">smart_toy</span>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold tracking-wide text-white truncate">Nex</p>
                    <p class="text-[10px] font-mono uppercase tracking-widest text-emerald-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Movie guide
                    </p>
                </div>
            </div>
            <button type="button"
                    @click="close()"
                    class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-white/50 hover:text-white hover:rotate-90 transition-all duration-300"
                    aria-label="Close chat">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <div x-ref="chatScroll" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 custom-scrollbar">
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex" :class="msg.from === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[90%] px-3 py-2 text-sm leading-relaxed rounded-2xl"
                         :class="msg.from === 'user'
                            ? 'bg-gradient-to-br from-red-500 to-red-600 text-white rounded-br-sm shadow-[0_8px_20px_rgba(239,68,68,0.2)]'
                            : 'bg-white/10 text-white/90 border border-white/10 rounded-bl-sm'">
                        <p class="whitespace-pre-wrap" x-text="msg.text"></p>
                        <template x-if="msg.movies && msg.movies.length">
                            <div class="mt-3 space-y-2">
                                <template x-for="movie in msg.movies" :key="movie.id || movie.title">
                                    <div class="flex gap-2 rounded-xl border border-white/10 bg-black/30 p-2">
                                        <img :src="movie.img || movie.cover_image"
                                             :alt="movie.title"
                                             class="w-12 h-[4.5rem] object-cover rounded-lg shrink-0 bg-white/5">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-white truncate" x-text="movie.title"></p>
                                            <p class="text-[10px] text-white/45 truncate" x-text="genreLine(movie)"></p>
                                            <p class="text-[10px] text-amber-300/90 mono" x-show="Number(movie.rating) > 0" x-text="'★ ' + movie.rating"></p>
                                            <div class="flex gap-1.5 mt-1.5">
                                                <button type="button"
                                                        @click="openMovie(movie)"
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/10 hover:bg-white/20 text-white">
                                                    Open
                                                </button>
                                                <button type="button"
                                                        @click="hostMovie(movie)"
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-600/80 hover:bg-red-500 text-white">
                                                    Host
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <div x-show="$data.typing" class="flex justify-start" style="display: none;">
                <div class="bg-white/10 border border-white/10 rounded-2xl rounded-bl-sm px-3 py-2 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay: 120ms;"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay: 240ms;"></span>
                </div>
            </div>
        </div>

        <div class="shrink-0 px-4 pb-2 flex flex-wrap gap-2">
            <template x-for="chip in suggestions" :key="chip">
                <button type="button"
                        @click="send(chip)"
                        class="text-[11px] font-semibold tracking-wide px-2.5 py-1 rounded-full border border-white/10 bg-white/[0.04] text-white/70 hover:text-white hover:border-red-500/40 hover:bg-red-500/10 transition-colors">
                    <span x-text="chip"></span>
                </button>
            </template>
        </div>

        <form class="shrink-0 p-3 border-t border-white/10 bg-black/40" @submit.prevent="send()">
            <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2 focus-within:border-red-500/40">
                <input type="text"
                       x-model="$data.input"
                       maxlength="400"
                       placeholder="Ask for a movie, mood, or how Nexus works..."
                       class="flex-1 bg-transparent text-sm text-white placeholder:text-white/30 outline-none">
                <button type="submit"
                        :disabled="!($data.input || '').trim() || $data.typing"
                        class="w-8 h-8 rounded-lg bg-nexus-red hover:bg-red-400 disabled:opacity-40 disabled:hover:bg-nexus-red flex items-center justify-center text-white transition-colors"
                        aria-label="Send message">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </div>
        </form>
    </div>

    <button type="button"
            @click="toggle()"
            class="relative group flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-tr from-indigo-500 to-red-600 text-white shadow-[0_0_24px_rgba(239,68,68,0.45)] hover:shadow-[0_0_40px_rgba(239,68,68,0.65)] hover:-translate-y-0.5 transition-all duration-300"
            :aria-expanded="open.toString()"
            aria-label="Open support chat">
        <span class="material-symbols-outlined text-[26px] transition-transform duration-300"
              :class="open ? 'rotate-90' : 'group-hover:scale-110'"
              x-text="open ? 'close' : 'smart_toy'"></span>
        <span x-show="$data.unread && !$data.open"
              class="absolute top-0.5 right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-[#030305]"
              style="display: none;"></span>
        <span class="absolute inset-0 rounded-full bg-red-500 animate-ping opacity-20 -z-10 pointer-events-none" style="animation-duration: 2s;"></span>
    </button>
</div>
