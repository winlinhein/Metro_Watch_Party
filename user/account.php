<div class="absolute inset-0 w-full h-full overflow-y-auto p-10 pb-32 tab-content scroll-smooth custom-scrollbar" 
     x-show="currentTab === 'account'" style="display: none;">
    <!-- Account Settings Logic -->
    <div class="max-w-[1400px] mx-auto space-y-8 pb-24">
        
        <!-- Header -->
        <div class="flex items-center gap-4 mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-red-500 flex items-center justify-center shadow-[0_0_20px_rgba(239,68,68,0.3)]">
                <span class="material-symbols-outlined text-3xl text-white">person</span>
            </div>
            <div>
                <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-white/50 uppercase tracking-widest">Account Details</h1>
                <p class="text-white/50 text-sm mt-1">Manage your identity, security, and appearance</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Basic Info Form -->
            <div class="bg-[#0a0a0f] border border-white/10 rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 blur-3xl rounded-full translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-500">badge</span>
                    Profile Information
                </h2>
                <form @submit.prevent="updateAccountInfo()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">Username</label>
                        <input type="text" x-model="accountForm.username" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500/50 outline-none transition-colors" placeholder="Enter new username">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">Email</label>
                        <input type="email" x-model="accountForm.email" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500/50 outline-none transition-colors" placeholder="Enter new email">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold transition-all hover:border-red-500/50 shadow-md">Save Changes</button>
                </form>
            </div>

            <!-- Security Form -->
            <div class="bg-[#0a0a0f] border border-white/10 rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 blur-3xl rounded-full translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500">lock</span>
                    Security
                </h2>
                <form @submit.prevent="updatePassword()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">Current Password</label>
                        <input type="password" x-model="passwordForm.current" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-indigo-500/50 outline-none transition-colors" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">New Password</label>
                        <input type="password" x-model="passwordForm.new" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-indigo-500/50 outline-none transition-colors" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-white/50 uppercase tracking-wider mb-2">Confirm New Password</label>
                        <input type="password" x-model="passwordForm.confirm" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-indigo-500/50 outline-none transition-colors" placeholder="••••••••">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 text-indigo-400 font-bold transition-all hover:border-indigo-500 shadow-md">Update Password</button>
                </form>
            </div>
        </div>

        <!-- Appearance (Borders, etc.) -->
        <div class="bg-[#0a0a0f] border border-white/10 rounded-2xl p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 blur-3xl rounded-full translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">palette</span>
                Appearance & Borders
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <template x-for="border in availableBorders" :key="border.id">
                    <button @click="setActiveBorder(border.id)" 
                            class="relative p-4 rounded-xl border flex flex-col items-center justify-center gap-3 transition-all duration-300 transform"
                            :class="[
                                !border.owned ? 'bg-black/60 border-white/5 opacity-50 cursor-not-allowed hover:bg-black/60' : 'hover:-translate-y-1',
                                activeBorderId === border.id ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.2)]' : 'bg-black/40 border-white/5 text-white/50 hover:bg-white/5 hover:border-white/20'
                            ]">
                        
                        <div class="relative">
                            <img :src="border.preview" class="w-12 h-12 rounded-full object-cover border-2" :class="activeBorderId === border.id ? 'border-emerald-500' : 'border-white/10'">
                            <!-- Lock overlay for unowned -->
                            <div x-show="!border.owned" class="absolute inset-0 bg-black/60 rounded-full flex items-center justify-center backdrop-blur-[1px]">
                                <span class="material-symbols-outlined text-[16px] text-white/70">lock</span>
                            </div>
                        </div>

                        <span class="text-xs font-bold uppercase tracking-wider" x-text="border.name"></span>
                        
                        <!-- Checkmark for active -->
                        <div x-show="activeBorderId === border.id" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-emerald-500 text-black flex items-center justify-center shadow-lg">
                            <span class="material-symbols-outlined text-[14px] font-bold">check</span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
