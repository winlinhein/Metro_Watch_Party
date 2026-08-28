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
     <div x-ref="chatPanel" class="chat-panel-container h-full w-full bg-[#050508]/90 backdrop-blur-2xl border-l border-white/10 flex flex-col pointer-events-auto shadow-[-20px_0_50px_rgba(0,0,0,0.5)] relative overflow-hidden">
        
        <!-- Animated Background Glows -->
        <div class="absolute -top-32 -right-32 w-64 h-64 bg-emerald-500/10 rounded-full blur-[80px]"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-emerald-500/10 rounded-full blur-[80px]"></div>
        
        <!-- Chat Header -->
        <div class="h-20 shrink-0 border-b border-white/10 flex items-center justify-between px-6 bg-white/[0.02] relative z-10">
            <template x-if="activeChatFriend">
                <div class="flex items-center gap-4 cursor-pointer hover:opacity-80 transition-opacity" @click.stop="toggleDropdown(activeChatFriend, $event)">
                    <div class="relative">
                        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(activeChatFriend?.user_name || 'User')}&background=10b981&color=fff`"
                             class="w-10 h-10 rounded-full border-2 border-emerald-500/30 shadow-[0_0_15px_rgba(16,185,129,0.2)]">
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-[#050508] rounded-full"></span>
                    </div>
                    <div>
                        <h3 class="font-bold text-white tracking-wide" x-text="activeChatFriend?.user_name || 'User'"></h3>
                        <p class="text-[10px] font-mono text-emerald-400 uppercase tracking-widest flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-emerald-400 animate-ping"></span>
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
        <div class="chat-messages-container flex-1 overflow-y-auto custom-scrollbar p-6 flex flex-col gap-6 relative z-10">
            <template x-for="msg in chatMessages" :key="msg.id">
                <div class="chat-message-item flex flex-col" :class="msg.sender === 'me' ? 'items-end' : 'items-start'">
                    <div class="flex items-end gap-2 max-w-[85%]" :class="msg.sender === 'me' ? 'flex-row-reverse' : 'flex-row'">
                        <template x-if="msg.sender !== 'me'">
                            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(activeChatFriend?.user_name || 'User')}&background=10b981&color=fff`" class="w-6 h-6 rounded-full opacity-70">
                        </template>
                        <div class="p-2 rounded-2xl relative group overflow-hidden" 
                            :class="msg.sender === 'me' 
                                ? 'bg-gradient-to-br from-emerald-600 to-emerald-700 text-white rounded-br-sm shadow-[0_10px_20px_rgba(16,185,129,0.2)]' 
                                : 'bg-white/10 text-white/90 rounded-bl-sm border border-white/5'">
                            <!-- Text message -->
                            <template x-if="msg.message_type === 'text' || !msg.message_type">
                                <p class="text-sm leading-relaxed px-2 py-1" x-text="msg.text || msg.message_text"></p>
                            </template>
                            <!-- Image message -->
                            <template x-if="msg.message_type === 'image'">
                                <img :src="msg.image_url" class="max-w-full max-h-64 rounded-lg cursor-pointer hover:opacity-90" @click="window.open(msg.image_url, '_blank')" />
                            </template>
                        </div>
                    </div>
                    <span class="text-[9px] text-white/30 font-mono mt-1 px-8" x-text="msg.time"></span>
                </div>
            </template>
        </div>
        
        <!-- Chat Input Area -->
        <div class="shrink-0 p-4 border-t border-white/10 bg-black/20 backdrop-blur-md relative z-10">
            <!-- Image preview -->
            <div x-show="selectedImagePreview" class="mb-2 relative inline-block">
                <img :src="selectedImagePreview" class="h-20 w-20 object-cover rounded-lg border border-white/20" />
                <button @click="clearSelectedImage()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full text-white text-xs">✕</button>
            </div>

            <form @submit.prevent="sendMessage()" class="relative flex items-center group">
                <!-- Image button -->
                <button type="button" @click="$refs.imageInput.click()" class="absolute left-2 w-10 h-10 rounded-xl text-white/50 hover:text-emerald-400 hover:bg-white/5 flex items-center justify-center transition-all z-10">
                    <span class="material-symbols-outlined text-[20px]">image</span>
                </button>
                <input type="file" x-ref="imageInput" accept="image/*" class="hidden" @change="handleImageSelect($event)" />

                <input type="text" x-model="chatInput" placeholder="Transmit secure message..." 
                    class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-14 pr-14 text-sm text-white placeholder-white/30 outline-none focus:border-emerald-500/50 focus:bg-white/10 transition-all shadow-inner">
                <button type="submit" 
                        class="absolute right-2 w-10 h-10 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.4)] transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="!chatInput.trim() && !selectedImagePreview">
                    <span class="material-symbols-outlined text-[18px] translate-x-0.5">send</span>
                </button>
            </form>
        </div>
     </div>
</div>
