<!-- Chat Modal UI (Animated via GSAP) -->
<div x-show="showChatPanel" 
     class="fixed inset-y-0 right-0 w-full sm:w-[400px] z-[999] flex flex-col pointer-events-none" 
     style="display: none;">
     
     <!-- Backdrop for mobile -->
     <div class="fixed inset-0 bg-black/60 backdrop-blur-sm sm:hidden pointer-events-auto" 
          x-show="showChatPanel" 
          x-transition.opacity 
          @click="closeChat()"></div>
          
     <!-- Chat Panel Container -->
     <div class="chat-panel-container h-full w-full bg-[#050508]/90 backdrop-blur-2xl border-l border-white/10 flex flex-col pointer-events-auto shadow-[-20px_0_50px_rgba(0,0,0,0.5)] relative overflow-hidden">
        
        <!-- Animated Background Glows -->
        <div class="absolute -top-32 -right-32 w-64 h-64 bg-rose-500/10 rounded-full blur-[80px]"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-red-500/10 rounded-full blur-[80px]"></div>
        
        <!-- Chat Header -->
        <div class="h-20 shrink-0 border-b border-white/10 flex items-center justify-between px-6 bg-white/[0.02] relative z-10">
            <template x-if="activeChatFriend">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(activeChatFriend?.user_name || 'User')}&background=ef4444&color=fff`"
                             class="w-10 h-10 rounded-full border-2 border-red-500/30 shadow-[0_0_15px_rgba(239,68,68,0.2)]">
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-red-500 border-2 border-[#050508] rounded-full"></span>
                    </div>
                    <div>
                        <h3 class="font-bold text-white tracking-wide" x-text="activeChatFriend?.user_name || 'User'"></h3>
                        <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-red-400 animate-ping"></span>
                            Secure Link Active
                        </p>
                    </div>
                </div>
            </template>
            <button @click="closeChat()" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-white/50 hover:text-white hover:rotate-90 transition-all duration-300">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <!-- Chat Messages -->
        <div class="chat-messages-container flex-1 overflow-y-auto p-4 space-y-3">
            <!-- Pass (msg, index) and use composite key with fallback -->
            <template x-for="(msg, index) in chatMessages" :key="(msg && msg.id) ? String(msg.id) : 'msg-' + index">
                <div class="flex w-full" :class="msg.sender === 'me' ? 'justify-end' : 'justify-start'">
                    
                    <div class="max-w-[75%] px-4 py-2 rounded-2xl text-sm shadow-sm"
                        :class="msg.sender === 'me' 
                                ? 'bg-blue-600 text-white rounded-tr-none' 
                                : 'bg-gray-100 text-gray-800 rounded-tl-none dark:bg-gray-700 dark:text-gray-100'">
                        
                        <p class="break-words" x-text="msg.text || ''"></p>
                        
                        <span class="text-[10px] block mt-1 opacity-75"
                            :class="msg.sender === 'me' ? 'text-right text-blue-100' : 'text-left text-gray-500 dark:text-gray-400'"
                            x-text="msg.time">
                        </span>
                    </div>

                </div>
            </template>
        </div>
        
        <!-- Chat Input Area -->
        <div class="shrink-0 p-4 border-t border-white/10 bg-black/20 backdrop-blur-md relative z-10">
            <!-- Keep only the submit.prevent on the form -->
            <form @submit.prevent="sendMessage()" class="relative flex items-center group">
                
                <!-- Removed the redundant @keydown.enter -->
                <input type="text" 
                    x-model="newMessageText" 
                    placeholder="Transmit secure message..." 
                    class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-5 pr-14 text-sm text-white placeholder-white/30 outline-none focus:border-red-500/50 focus:bg-white/10 transition-all shadow-inner">
                
                <!-- Removed x-model, changed :disabled to check newMessageText -->
                <button type="submit"
                        class="absolute right-2 w-10 h-10 rounded-xl bg-red-500 hover:bg-red-400 text-white flex items-center justify-center shadow-[0_0_15px_rgba(239,68,68,0.4)] transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="!newMessageText.trim()">
                    <span class="material-symbols-outlined text-[18px] translate-x-0.5">send</span>
                </button>
                
            </form>
        </div>
     </div>
</div>
