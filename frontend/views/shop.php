<!-- Shop View -->
<div data-tab-panel="shop" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full overflow-y-auto">
    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Avatar Borders</h2>
            <p class="text-white/40 text-sm">Manage shop items, pricing, and availability.</p>
        </div>
        <button @click="openModal('add')" class="relative px-6 py-3 overflow-hidden rounded-xl group hover:scale-105 active:scale-95 transition-all duration-300 shadow-xl shadow-purple-500/20">
            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-purple-600 via-pink-500 to-red-500 opacity-80 group-hover:opacity-100 transition-opacity"></span>
            <span class="absolute -inset-1 w-full h-full bg-gradient-to-r from-purple-500 via-pink-400 to-red-500 blur-xl opacity-30 group-hover:opacity-60 transition-opacity animate-pulse"></span>
            <div class="relative flex items-center gap-2 text-white font-bold text-sm tracking-wide">
                <span class="material-symbols-outlined text-[18px]">add_circle</span> Add Item
            </div>
        </button>
    </div>

    <!-- Shop Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 pb-10">
        <template x-for="item in shopItems" :key="item.id">
            <div class="glass-card rounded-2xl relative group overflow-hidden border border-white/5 hover:border-purple-500/50 transition-all duration-500 stagger-item shadow-lg hover:shadow-[0_0_30px_rgba(168,85,247,0.2)]">
                <!-- Glowing orb effect -->
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-purple-500/20 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                
                <div class="p-8 flex flex-col items-center justify-center relative z-10 border-b border-white/5 bg-gradient-to-b from-white/[0.02] to-transparent">
                    <div class="relative w-32 h-32 group-hover:scale-110 transition-transform duration-500 ease-out">
                        <!-- Base Placeholder -->
                        <div class="absolute inset-2 rounded-full bg-[#111116] border border-white/5 shadow-inner flex items-center justify-center">
                            <span class="material-symbols-outlined text-white/10 text-3xl">person</span>
                        </div>
                        <!-- Border Image -->
                        <img :src="item.image" class="absolute inset-0 w-full h-full object-cover z-20 pointer-events-none scale-[1.3] mix-blend-screen drop-shadow-[0_0_15px_rgba(255,255,255,0.2)]">
                    </div>
                </div>

                <div class="p-6 relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-white group-hover:text-purple-400 transition-colors" x-text="item.name"></h3>
                            <div class="flex items-center gap-1 mt-1">
                                <span class="material-symbols-outlined text-[14px] text-yellow-400">monetization_on</span>
                                <span class="text-sm font-bold text-yellow-400" x-text="item.price.toLocaleString()"></span>
                                <span class="text-xs text-white/40 uppercase tracking-wider ml-1">Pts</span>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded bg-white/5 border border-white/10 text-[10px] font-bold text-white/50 uppercase tracking-wider" x-text="item.rarity"></span>
                    </div>

                    <div class="flex gap-2">
                        <button @click="openModal('edit', item)" class="flex-1 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-bold text-white transition-colors flex items-center justify-center gap-2 group/btn">
                            <span class="material-symbols-outlined text-[16px] group-hover/btn:text-blue-400 transition-colors">edit</span> Edit
                        </button>
                        <button @click="deleteItem(item.id)" class="py-2 px-3 bg-red-500/10 hover:bg-red-500 border border-red-500/20 hover:border-red-500 rounded-lg text-xs font-bold text-red-400 hover:text-white transition-all shadow-[0_0_10px_rgba(239,68,68,0.2)] hover:shadow-[0_0_20px_rgba(239,68,68,0.6)]">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="modalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()" x-show="modalOpen" x-transition.opacity></div>
        
        <!-- Modal Content -->
        <div class="relative bg-[#0a0a0f] border border-white/10 rounded-2xl p-8 max-w-md w-full shadow-2xl"
             x-show="modalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95">
             
             <div class="flex items-center gap-4 mb-6">
                 <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 shadow-[0_0_15px_rgba(168,85,247,0.3)]">
                     <span class="material-symbols-outlined text-2xl" x-text="modalMode === 'add' ? 'add_circle' : 'edit'"></span>
                 </div>
                 <div>
                     <h3 class="text-xl font-bold text-white" x-text="modalMode === 'add' ? 'Add Shop Item' : 'Edit Item'"></h3>
                     <p class="text-sm text-purple-400/60" x-text="modalMode === 'add' ? 'Create a new border' : 'Modify existing border'"></p>
                 </div>
             </div>
             
             <div class="space-y-4 mb-6">
                 <div>
                     <label class="block text-xs font-bold text-white/50 uppercase tracking-wider mb-2">Item Name</label>
                     <input type="text" x-model="formData.name" class="w-full bg-[#030305] border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-purple-500/50 transition-colors">
                 </div>
                 <div>
                     <label class="block text-xs font-bold text-white/50 uppercase tracking-wider mb-2">Price (Points)</label>
                     <input type="number" x-model.number="formData.price" class="w-full bg-[#030305] border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-purple-500/50 transition-colors">
                 </div>
                 <div>
                     <label class="block text-xs font-bold text-white/50 uppercase tracking-wider mb-2">Rarity</label>
                     <select x-model="formData.rarity" class="w-full bg-[#030305] border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-purple-500/50 transition-colors">
                         <option value="Common">Common</option>
                         <option value="Rare">Rare</option>
                         <option value="Epic">Epic</option>
                         <option value="Legendary">Legendary</option>
                     </select>
                 </div>
                 <div>
                     <label class="block text-xs font-bold text-white/50 uppercase tracking-wider mb-4 text-center">Border Image</label>
                     <div class="relative w-24 h-24 mx-auto cursor-pointer group" @click="$refs.fileInput.click()">
                        <input type="file" x-ref="fileInput" @change="handleShopImageSelect" accept="image/*" class="hidden">
                         
                         <div class="absolute inset-1 rounded-full bg-[#111116] border border-white/5 shadow-inner flex items-center justify-center">
                            <span class="material-symbols-outlined text-white/10 text-3xl">person</span>
                        </div>
                         <template x-if="!formData.image">
                             <div class="absolute inset-0 w-full h-full rounded-full bg-[#030305] border-2 border-dashed border-white/20 flex flex-col items-center justify-center scale-[1.3] transition-colors group-hover:border-purple-500/50">
                                 <span class="material-symbols-outlined text-white/40 text-2xl group-hover:text-purple-400 transition-colors">add_photo_alternate</span>
                             </div>
                         </template>
                         
                         <template x-if="formData.image">
                             <img :src="formData.image" class="absolute inset-0 w-full h-full object-cover z-20 pointer-events-none scale-[1.3] drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] mix-blend-screen">
                         </template>
                         
                         <template x-if="formData.image">
                             <div class="absolute inset-0 z-30 bg-black/60 rounded-full flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity scale-[1.3] border border-white/20">
                                 <span class="material-symbols-outlined text-white text-xl">upload</span>
                                 <span class="text-[8px] font-bold text-white uppercase mt-1 tracking-wider">Change</span>
                             </div>
                         </template>
                     </div>
                 </div>
             </div>
             
             <div class="flex gap-3 justify-end">
                 <button @click="closeModal()" class="px-5 py-2.5 rounded-xl border border-white/10 text-white/70 hover:bg-white/5 hover:text-white transition-colors text-sm font-bold">
                     Cancel
                 </button>
                 <button @click="saveItem()" class="px-5 py-2.5 rounded-xl bg-purple-500 hover:bg-purple-600 text-white transition-all text-sm font-bold shadow-[0_0_15px_rgba(168,85,247,0.4)] hover:shadow-[0_0_25px_rgba(168,85,247,0.6)]">
                     Save Item
                 </button>
             </div>
        </div>
    </div>
</div>


