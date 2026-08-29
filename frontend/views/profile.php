<!-- Profile View -->
<div data-tab-panel="profile" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full overflow-y-auto">
    <!-- Inline Notification Banner -->
    <div x-show="notification.show" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4"
        :class="notification.type === 'error' ? 'bg-red-500/10 border-red-500/30 text-red-300' : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'"
        class="mb-6 p-4 rounded-2xl border backdrop-blur-md flex items-start justify-between gap-4 shadow-lg"
        style="display: none;">
        
        <div class="flex items-start gap-3">
            <!-- Icon -->
            <span class="material-symbols-outlined text-xl mt-0.5" 
                x-text="notification.type === 'error' ? 'error' : 'check_circle'"></span>
            
            <!-- Message Container (Handles single string or error array) -->
            <div class="text-xs font-medium space-y-1">
                <template x-if="Array.isArray(notification.message)">
                    <ul class="list-disc list-inside space-y-1">
                        <template x-for="msg in notification.message" :key="msg">
                            <li x-text="msg"></li>
                        </template>
                    </ul>
                </template>
                <template x-if="!Array.isArray(notification.message)">
                    <p x-text="notification.message"></p>
                </template>
            </div>
        </div>

        <!-- Dismiss Button -->
        <button @click="notification.show = false" class="text-white/40 hover:text-white transition-colors">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    </div>

    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Profile & Identity</h2>
        </div>
        
        <!-- Original Button Restored -->
        <button @click="saveProfile()" class="relative px-6 py-3 overflow-hidden rounded-xl group hover:scale-105 active:scale-95 transition-all duration-300 shadow-xl shadow-red-500/20">
            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-600 via-red-500 to-red-800 opacity-80 group-hover:opacity-100 transition-opacity"></span>
            <span class="absolute -inset-1 w-full h-full bg-gradient-to-r from-red-500 via-red-400 to-red-600 blur-xl opacity-30 group-hover:opacity-60 transition-opacity animate-pulse"></span>
            <div class="relative flex items-center gap-2 text-white font-bold text-sm tracking-wide">
                <span class="material-symbols-outlined text-[18px]">save</span> Save Changes
            </div>
        </button>
    </div>
    
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 pb-10">
        <!-- Left Column: Avatar & Borders -->
        <div class="xl:col-span-1 space-y-6">
            <div class="glass-card rounded-2xl p-8 stagger-item flex flex-col items-center relative overflow-hidden group hover:border-red-500/30 transition-colors duration-300 shadow-xl">
                <div class="absolute inset-0 bg-gradient-to-b from-red-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10 w-full flex flex-col items-center">
                    
                    <div class="relative w-32 h-32 mb-6 group cursor-pointer mt-4" @click="avatarModalOpen = true">
                        <img :src="selectedAvatar" class="w-full h-full rounded-full object-cover border-4 border-red-500/50 group-hover:border-red-500 transition-colors z-10 relative shadow-2xl">
                        
                        <!-- Dynamic Border -->
                        <template x-if="selectedBorder">
                            <img :src="selectedBorder" class="absolute inset-0 w-full h-full object-cover z-20 pointer-events-none scale-[1.3] drop-shadow-[0_0_15px_rgba(255,255,255,0.2)] mix-blend-screen opacity-90">
                        </template>
                        
                        <div class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-30">
                            <span class="material-symbols-outlined text-white">photo_camera</span>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-bold text-white mb-1" 
                        x-text="displayName">
                    </h3>

                    <p class="text-white/40 text-sm mb-6 mono" 
                    x-text="displayEmail">
                    </p>
                    <button @click="avatarModalOpen = true" class="w-full py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-semibold transition-colors flex justify-center items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">image</span> Change Border
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Info & Password -->
        <div class="xl:col-span-2 space-y-6">
            <div class="glass-card rounded-2xl p-8 stagger-item">
                <h3 class="text-lg font-bold text-white mb-6">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Full Name</label>
                       <input type="text" 
                            id="profile_name" 
                            x-model="profile.user_name" 
                            class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-sm text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Email Address</label>
                      <input type="email" 
                            id="profile_email" 
                            x-model="profile.email" 
                            class="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-sm text-white">
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-8 stagger-item">
                <h3 class="text-lg font-bold text-white mb-6">Change Password</h3>
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Current Password</label>
                        <input type="password" 
                            id="current_password"
                            x-model="profile.current_password" 
                            class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">New Password</label>
                            <input type="password" 
                                id="new_password"
                                x-model="profile.new_password" 
                                class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-white/40 uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" 
                                id="confirm_password"
                                x-model="profile.confirm_password" 
                                class="w-full bg-[#030305]/80 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500/50 transition-colors">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone Area -->
            <div class="mt-12 pt-8 border-t border-white/10">
                <h4 class="text-xs font-extrabold text-red-400 uppercase tracking-widest mb-3">Danger Zone</h4>
                
                <div class="p-6 rounded-2xl bg-red-500/[0.04] border border-red-500/20 backdrop-blur-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h5 class="text-sm font-bold text-white">Delete Account</h5>
                        <p class="text-xs text-white/40">Permanently remove your account, profile details, and watchlist history. This action cannot be undone.</p>
                    </div>
                    
                    <button @click="deleteAccountModalOpen = true; deleteAccountPassword = ''" 
                            class="px-5 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-xs font-bold text-red-400 transition-all hover:scale-105 active:scale-95 flex-shrink-0 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                        <span>Delete Account</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Avatar/Border Modal -->
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
                    
                    <!-- Selected Indicator -->
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

<!-- Account Deletion Confirmation Modal -->
<template x-teleport="body">
    <div x-show="deleteAccountModalOpen" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" 
         style="display: none;" 
         x-transition.opacity>
        
        <div class="w-full max-w-md bg-[#0c0c12] border border-red-500/30 rounded-3xl shadow-2xl overflow-hidden p-6 space-y-5 relative"
             @click.away="deleteAccountModalOpen = false">
            
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

            <!-- Modal Inline Error Banner -->
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
                <button @click="deleteAccountModalOpen = false; deleteAccountError = '';" 
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
</template>