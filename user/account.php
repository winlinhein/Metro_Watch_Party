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
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Live Preview Section -->
                <div class="shrink-0 w-full md:w-64 bg-[#050508]/60 border border-white/5 rounded-2xl p-6 flex flex-col items-center justify-center relative shadow-inner h-fit">
                    <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] absolute top-4 left-0 w-full text-center">Profile Preview</h3>
                    
                    <div class="relative w-24 h-24 mt-6 mb-4 overflow-visible shrink-0" style="width: 6rem; height: 6rem;">
                        <div class="absolute inset-0 z-0 overflow-hidden rounded-full bg-black shadow-lg scale-[1.18]"
                             :class="activeBorderId === 0 ? 'ring-2 ring-white/10' : ''">
                            <img :src="selectedAvatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(savedProfile.username || 'User') + '&background=ef4444&color=fff&bold=true'" class="absolute inset-0 h-full w-full object-cover" style="object-fit: cover;">
                        </div>
                        <template x-if="Number(activeBorderId) !== 0 && activeBorderPreview">
                            <img :src="activeBorderPreview" class="absolute inset-0 z-10 h-full w-full object-contain pointer-events-none scale-[1.38]">
                        </template>
                    </div>
                    
                    <div class="text-center mt-2 z-20">
                        <p class="text-white font-bold tracking-wide text-lg" x-text="savedProfile.username || 'User'"></p>
                        <p class="text-emerald-400 text-[10px] font-bold uppercase tracking-wider mt-1" x-text="availableBorders.find(b => b.id === activeBorderId)?.name || 'None'"></p>
                    </div>
                    
                    <button type="button" @click="$refs.avatarInput.click()" class="mt-5 w-full py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-bold text-white transition-colors flex items-center justify-center gap-2 z-20 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">add_a_photo</span>
                        Change Picture
                    </button>
                    <input type="file" x-ref="avatarInput" @change="uploadAvatar" accept="image/*" class="hidden">

                    <!-- Remove Photo Button (only when custom avatar exists) -->
                    <button type="button" x-show="hasCustomAvatar" @click="removeAvatar()" 
                            class="mt-2 w-full py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 rounded-xl text-xs font-bold text-red-400 transition-colors flex items-center justify-center gap-2 z-20 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        Remove Photo
                    </button>
                </div>

                <!-- Border Selection Grid -->
                <div class="flex-1">
                    <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
                        <template x-for="border in availableBorders" :key="border.id">
                            <button @click="border.owned ? setActiveBorder(border.id) : null" 
                                    class="relative p-4 rounded-xl border flex flex-col items-center justify-center gap-3 transition-all duration-300 transform"
                                    :class="[
                                        !border.owned ? 'bg-black/60 border-white/5 opacity-50 cursor-not-allowed hover:bg-black/60' : 'hover:-translate-y-1',
                                        activeBorderId === border.id ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.2)]' : 'bg-black/40 border-white/5 text-white/50 hover:bg-white/5 hover:border-white/20'
                                    ]">
                                
                                <div class="relative w-12 h-12 overflow-visible shrink-0" style="width: 3rem; height: 3rem;">
                                    <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.18]" :class="border.id === 0 ? (activeBorderId === border.id ? 'ring-2 ring-emerald-500' : 'ring-2 ring-white/10') : ''">
                                        <img :src="selectedAvatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(savedProfile.username || 'User') + '&background=ef4444&color=fff&bold=true'" class="absolute inset-0 h-full w-full object-cover" style="object-fit: cover;">
                                    </div>
                                    <template x-if="border.id !== 0">
                                        <img :src="border.preview" class="absolute inset-0 z-10 h-full w-full object-contain scale-[1.38] pointer-events-none">
                                    </template>
                                    <div x-show="!border.owned" class="absolute inset-0 z-20 flex scale-[1.18] items-center justify-center rounded-full bg-black/60 backdrop-blur-[1px]">
                                        <span class="material-symbols-outlined text-[16px] text-white/70">lock</span>
                                    </div>
                                </div>

                                <span class="text-xs font-bold uppercase tracking-wider" x-text="border.name"></span>
                                
                                <!-- Checkmark for active -->
                                <div x-show="activeBorderId === border.id" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-emerald-500 text-black flex items-center justify-center shadow-lg z-30">
                                    <span class="material-symbols-outlined text-[14px] font-bold">check</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Danger Zone -->
    <div class="mt-12 pt-8 border-t border-white/10">
        <h4 class="text-xs font-extrabold text-red-400 uppercase tracking-widest mb-3">Danger Zone</h4>
        
        <div class="p-6 rounded-2xl bg-red-500/[0.04] border border-red-500/20 backdrop-blur-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h5 class="text-sm font-bold text-white">Delete Account</h5>
                <p class="text-xs text-white/40">Permanently remove your account, profile details, and watchlist history. This action cannot be undone.</p>
            </div>
            
            <button @click="openDeleteAccountModal()" 
                    class="px-5 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-xs font-bold text-red-400 transition-all hover:scale-105 active:scale-95 flex-shrink-0 flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                <span>Delete Account</span>
            </button>
        </div>
    </div>
</div>

<!-- Account Deletion Confirmation Modal (unchanged) -->
<div x-show="deleteAccountModalOpen" 
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" 
     style="display: none;" 
     x-transition.opacity>
    
    <div class="w-full max-w-md bg-[#0c0c12] border border-red-500/30 rounded-3xl shadow-2xl overflow-hidden p-6 space-y-5 relative"
         @click.away="closeDeleteAccountModal()">
        
        <!-- Modal Header -->
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 flex-shrink-0">
                <span class="material-symbols-outlined text-[22px]">warning</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-white leading-tight">Delete Account?</h3>
                <p class="text-xs text-white/40 mt-0.5">Please confirm your current password to proceed.</p>
            </div>
        </div>

        <!-- Error Banner -->
        <div x-show="deleteAccountError"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-3.5 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-semibold flex items-center gap-2.5"
             style="display: none;">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <span x-text="deleteAccountError"></span>
        </div>

        <!-- Password Input -->
        <div class="space-y-2">
            <label class="block text-[11px] font-extrabold text-white/40 uppercase tracking-widest">Confirm Password</label>
            <input type="password" 
                   x-model="deleteAccountPassword" 
                   @input="deleteAccountError = ''"
                   placeholder="Enter your password" 
                   @keydown.enter="confirmDeleteAccount()"
                   class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/60 focus:ring-2 focus:ring-red-500/20 transition-all placeholder:text-white/20">
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-white/5">
            <button @click="closeDeleteAccountModal()" 
                    class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition-colors">
                Cancel
            </button>
            <button @click="confirmDeleteAccount()" 
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-xs font-bold text-white shadow-lg shadow-red-600/30 transition-all hover:scale-105 active:scale-95">
                Permanently Delete
            </button>
        </div>
    </div>
</div>