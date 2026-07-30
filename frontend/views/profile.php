<!-- Profile View -->
<div data-tab-panel="profile" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full">
    
    <!-- Header Action Bar -->
    <div class="flex items-center justify-between mb-8 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Profile & Identity</h2>
            <p class="text-xs text-white/40 mono uppercase">Agent Parameters & Security Credentials</p>
        </div>
        
        <button @click="saveProfileInfo()" 
                :disabled="profileLoading"
                class="relative px-6 py-3 overflow-hidden rounded-xl group hover:scale-105 active:scale-95 transition-all duration-300 shadow-xl shadow-red-500/20 disabled:opacity-50">
            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-600 via-red-500 to-red-800 opacity-80 group-hover:opacity-100 transition-opacity"></span>
            <span class="absolute -inset-1 w-full h-full bg-gradient-to-r from-red-500 via-red-400 to-red-600 blur-xl opacity-30 group-hover:opacity-60 transition-opacity animate-pulse"></span>
            <div class="relative flex items-center gap-2 text-white font-bold text-sm tracking-wide">
                <span class="material-symbols-outlined text-[18px]" x-text="profileLoading ? 'hourglass_top' : 'save'">save</span>
                <span x-text="profileLoading ? 'Saving...' : 'Save Changes'">Save Changes</span>
            </div>
        </button>
    </div>

    <!-- Alert Banner -->
    <template x-if="profileFeedback.message">
        <div class="mb-8 p-4 rounded-xl text-sm font-semibold flex items-center justify-between transition-all stagger-item"
             :class="profileFeedback.isError ? 'bg-red-500/10 border border-red-500/30 text-red-400' : 'bg-green-500/10 border border-green-500/30 text-green-400'">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[20px]" x-text="profileFeedback.isError ? 'error' : 'check_circle'"></span>
                <span x-text="profileFeedback.message"></span>
            </div>
            <button @click="profileFeedback.message = ''" class="hover:opacity-75">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    </template>
    
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 pb-10">
        
        <!-- Left Column: Avatar Card -->
        <div class="xl:col-span-1 space-y-6">
            <div class="glass-card rounded-2xl p-8 stagger-item flex flex-col items-center relative overflow-hidden group hover:border-red-500/30 transition-colors duration-300 shadow-xl">
                <div class="absolute inset-0 bg-gradient-to-b from-red-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10 w-full flex flex-col items-center">
                    
                    <div class="relative w-32 h-32 mb-6 group cursor-pointer mt-4" @click="avatarModalOpen = true">
                        <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(userName) + '&background=ef4444&color=fff&bold=true'" 
                             :alt="userName"
                             src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=ef4444&color=fff&bold=true" 
                             alt="<?= htmlspecialchars($userName) ?>" 
                             class="w-full h-full rounded-full object-cover border-4 border-red-500/50 group-hover:border-red-500 transition-colors z-10 relative shadow-2xl">
                        
                        <!-- Dynamic Border Overlay Demo -->
                        <template x-if="selectedBorder">
                            <img :src="selectedBorder" class="absolute inset-0 w-full h-full object-cover z-20 pointer-events-none scale-[1.3] drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] mix-blend-screen opacity-90">
                        </template>
                        
                        <div class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-30">
                            <span class="material-symbols-outlined text-white">photo_camera</span>
                        </div>
                    </div>
                    
                    <!-- Only updates display when Save is clicked -->
                    <h3 class="text-xl font-bold text-white mb-1" x-text="userName"><?= htmlspecialchars($userName); ?></h3>
                    <p class="text-white/40 text-sm mb-4 mono" x-text="userEmail"><?= htmlspecialchars($userEmail); ?></p>
                    
                    <?php if (!empty($user['updated_at'])): ?>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-[11px] text-white/50 mono mb-6">
                            <span class="material-symbols-outlined text-[14px] text-red-400">schedule</span>
                            Updated: <?= date('M d, Y H:i', strtotime($user['updated_at'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <button @click="avatarModalOpen = true" 
                            class="w-full py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-semibold transition-colors flex justify-center items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">image</span> Change Frame
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Forms -->
        <div class="xl:col-span-2 space-y-6">
            
            <!-- Personal Information Form -->
            <div class="glass-card rounded-2xl p-8 stagger-item">
                <h3 class="text-lg font-bold text-white mb-6">Personal Information</h3>
                <form @submit.prevent="saveProfileInfo()" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Display Name</label>
                            <input type="text" x-model="profileForm.name" required
                                   class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" x-model="profileForm.email" required
                                   class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors text-sm">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="profileLoading" 
                                class="px-5 py-2.5 bg-white/10 hover:bg-white/20 border border-white/10 text-white rounded-xl text-xs font-bold transition-all">
                            Save Personal Details
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Security Access Key Form -->
            <div class="glass-card rounded-2xl p-8 stagger-item">
                <h3 class="text-lg font-bold text-white mb-6">Security Access Key</h3>
                <form @submit.prevent="changePassword()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Current Password</label>
                        <input type="password" x-model="passwordForm.current_password" required
                               class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors text-sm">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">New Password</label>
                            <input type="password" x-model="passwordForm.new_password" required minlength="8"
                                   class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" x-model="passwordForm.confirm_password" required minlength="8"
                                   class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors text-sm">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="profileLoading"
                                class="px-5 py-2.5 bg-indigo-600/80 hover:bg-indigo-600 border border-indigo-500/30 text-white rounded-xl text-xs font-bold transition-all">
                            Update Access Key
                        </button>
                    </div>
                </form>
            </div>
            
           <!-- Danger Zone -->
            <div class="glass-card rounded-2xl p-8 stagger-item border-red-500/20 bg-red-500/5">
                <h3 class="text-lg font-bold text-red-400 mb-2">Danger Zone</h3>
                <p class="text-white/40 text-sm mb-6">Irreversible actions regarding your account status.</p>
                <div class="flex items-center justify-between border border-red-500/10 bg-[#030305]/50 p-4 rounded-xl">
                    <div>
                        <h4 class="text-sm font-bold text-white mb-1">Delete Account</h4>
                        <p class="text-xs text-white/40">Permanently remove your data from the Nexus ecosystem.</p>
                    </div>
                    <button type="button" 
                            @click="deleteModalOpen = true" 
                            class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-lg text-xs font-bold transition-colors">
                        Delete Account
                    </button>
                </div>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <div x-show="deleteModalOpen" 
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
                style="display: none;" 
                x-transition>
                <div class="glass-card rounded-2xl p-8 max-w-md w-full relative border-red-500/30" @click.away="deleteModalOpen = false">
                    <button @click="deleteModalOpen = false" class="absolute top-4 right-4 text-white/40 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                    
                    <div class="flex items-center gap-3 mb-4 text-red-400">
                        <span class="material-symbols-outlined text-3xl">warning</span>
                        <h3 class="text-xl font-bold text-white">Confirm Account Deletion</h3>
                    </div>
                    
                    <p class="text-white/60 text-sm mb-6">
                        This action <strong class="text-red-400">cannot be undone</strong>. Please enter your current password to verify ownership before proceeding.
                    </p>

                    <form @submit.prevent="deleteAccount()" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Current Password</label>
                            <input type="password" 
                                x-model="deletePassword" 
                                required 
                                placeholder="Enter your password"
                                class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors text-sm">
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" 
                                    @click="deleteModalOpen = false" 
                                    class="px-4 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-bold text-white transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    :disabled="deleteLoading"
                                    class="px-4 py-2.5 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-red-600/30 disabled:opacity-50">
                                <span x-text="deleteLoading ? 'Deleting...' : 'Permanently Delete'">Permanently Delete</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Frame Modal -->
<div x-show="avatarModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;" x-transition>
    <div class="glass-card rounded-2xl p-8 max-w-md w-full relative max-h-[90vh] overflow-y-auto" @click.away="avatarModalOpen = false">
        <button @click="avatarModalOpen = false" class="absolute top-4 right-4 text-white/40 hover:text-white transition-colors z-10">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <h3 class="text-xl font-bold text-white mb-6">Select Border</h3>
        
        <div class="grid grid-cols-4 gap-3">
            <template x-for="border in borders" :key="border.id">
                <button @click="selectedBorder = border.url; avatarModalOpen = false" 
                        :class="selectedBorder === border.url ? 'border-red-500 bg-red-500/10' : 'border-white/10 bg-white/5 hover:border-white/30'"
                        class="w-full aspect-square rounded-xl border transition-all flex items-center justify-center overflow-hidden relative group">
                    <template x-if="!border.url">
                        <span class="material-symbols-outlined text-white/20 text-2xl group-hover:text-white/40 transition-colors">block</span>
                    </template>
                    <template x-if="border.url">
                        <img :src="border.url" class="absolute inset-0 w-full h-full object-cover">
                    </template>
                    
                    <template x-if="selectedBorder === border.url">
                        <div class="absolute inset-0 border-2 border-red-500 rounded-xl"></div>
                    </template>
                </button>
            </template>
        </div>
        
        <div class="mt-8 flex justify-end">
            <button @click="avatarModalOpen = false" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-semibold transition-colors">
                Done
            </button>
        </div>
    </div>
</div>

