<!-- Users View -->
<div data-tab-panel="users" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full overflow-y-auto">

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">User Directory</h2>
        </div>

        <div class="flex gap-4 items-center">

            <div class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 flex items-center gap-2 focus-within:border-blue-500/50 transition-colors">
                <span class="material-symbols-outlined text-white/40 text-[18px]">search</span>

                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Search users..."
                    class="bg-transparent border-none outline-none text-white text-sm w-48 placeholder-white/30 font-medium"
                >
            </div>

            <select
                x-model="roleFilter"
                class="bg-[#030305] border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none cursor-pointer focus:border-blue-500/50 transition-colors"
            >
                <option class="bg-[#030305] text-white" value="All">All Roles</option>
                <option class="bg-[#030305] text-white" value="User">User / Standard</option>
                <option class="bg-[#030305] text-white" value="Premium">Premium</option>
                <option class="bg-[#030305] text-white" value="Admin">Admin</option>
                <option class="bg-[#030305] text-white" value="Moderator">Moderator</option>
            </select>

        </div>
    </div>

    <div class="flex gap-4 mb-6 border-b border-white/10 pb-3 stagger-item">

        <button
            @click="userCategoryTab = 'Users'"
            class="px-4 py-2 text-sm font-bold transition-all rounded-lg"
            :class="userCategoryTab === 'Users'
                ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30'
                : 'text-white/50 hover:text-white'"
        >
            Standard Users
        </button>

        <button
            @click="userCategoryTab = 'Moderators'"
            class="px-4 py-2 text-sm font-bold transition-all rounded-lg"
            :class="userCategoryTab === 'Moderators'
                ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30'
                : 'text-white/50 hover:text-white'"
        >
            Moderators & Admins
        </button>

    </div>

    <!-- Error Alert -->
    <div
        x-show="errorMessage"
        style="display: none;"
        class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm"
        x-text="errorMessage"
    ></div>

    <div class="glass-card rounded-2xl stagger-item border-white/10 relative min-h-[400px]">

        <!-- Loading Overlay -->
        <div
            x-show="isLoading"
            class="absolute inset-0 bg-[#030305]/70 backdrop-blur-sm z-20 flex items-center justify-center"
        >
            <div class="flex items-center gap-3 text-white/70">
                <span class="material-symbols-outlined animate-spin">sync</span>
                <span>Fetching directory...</span>
            </div>
        </div>

        <div class="w-full">

            <table class="w-full text-left border-collapse whitespace-nowrap">

                <thead>
                    <tr class="border-b border-white/10 bg-white/5">

                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">
                            Identity
                        </th>

                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">
                            Contact
                        </th>

                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">
                            Status
                        </th>

                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">
                            Clearance
                        </th>

                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">
                            Points
                        </th>

                    </tr>
                </thead>

                <tbody>

                    <template
                        x-for="(user, index) in filteredUsers"
                        :key="user.id"
                    >

                        <tr
                            class="border-b border-white/5 hover:bg-white/[0.03] transition-colors group user-row cursor-pointer"
                            x-data="{
                                dropdownOpen: false,
                                mouseX: 0,
                                mouseY: 0
                            }"
                            @click="
                                dropdownOpen = !dropdownOpen;
                                mouseX = $event.clientX + 220 < window.innerWidth
                                    ? $event.clientX + 15
                                    : $event.clientX - 205;
                                mouseY = $event.clientY + 180 < window.innerHeight
                                    ? $event.clientY + 15
                                    : $event.clientY - 165;
                            "
                            @click.away="dropdownOpen = false"
                        >

                            <td class="p-5 flex items-center gap-4">

                                <div class="relative">
                                    <div class="relative w-10 h-10 overflow-visible" style="width: 2.5rem; height: 2.5rem;">
                                        <div class="absolute inset-0 z-0 overflow-hidden rounded-xl scale-[1.05]">
                                            <img
                                                :src="(resolveAvatarUrl ? resolveAvatarUrl(user.avatar_url, user.name) : user.avatar_url) || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&color=fff&bold=true`"
                                                @error="$el.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&color=fff&bold=true`"
                                                class="w-full h-full object-cover border border-white/10 group-hover:border-white/30 transition-colors"
                                            >
                                        </div>
                                        <template x-if="user.border_preview">
                                            <img :src="user.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.45] object-contain pointer-events-none">
                                        </template>
                                    </div>

                                    <div
                                        class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-[#030305] transition-colors z-20"
                                        :class="
                                            user.status === 'Active'
                                                ? 'bg-green-500 group-hover:shadow-[0_0_8px_#22c55e]'
                                                : (
                                                    user.status === 'Banned'
                                                        ? 'bg-red-500'
                                                        : 'bg-yellow-500'
                                                )
                                        "
                                    ></div>
                                </div>

                                <div>

                                    <span
                                        class="font-bold text-white group-hover:text-blue-400 transition-colors block"
                                        x-text="user.name"
                                    ></span>

                                    <span
                                        class="text-[10px] text-white/30 mono"
                                        x-text="'ID: ' + user.id.toString().padStart(6, '0')"
                                    ></span>

                                </div>

                            </td>

                            <td
                                class="p-5 text-white/60 text-sm mono"
                                x-text="user.email"
                            ></td>

                            <td class="p-5">

                                <span
                                    class="px-3 py-1 rounded-md text-xs font-bold border"
                                    :class="{
                                        'bg-green-500/10 text-green-400 border-green-500/20':
                                            user.status === 'Active',

                                        'bg-red-500/10 text-red-400 border-red-500/20':
                                            user.status === 'Banned',

                                        'bg-yellow-500/10 text-yellow-400 border-yellow-500/20':
                                            user.status === 'Pending'
                                    }"
                                    x-text="user.status"
                                ></span>

                            </td>

                            <td class="p-5">

                                <span class="text-white/80 text-sm font-medium flex items-center gap-2">

                                    <span
                                        class="material-symbols-outlined text-[16px]"
                                        :class="
                                            user.role === 'Premium'
                                                ? 'text-indigo-400'
                                                : (
                                                    user.role === 'Admin'
                                                        ? 'text-orange-400'
                                                        : 'text-white/30'
                                                )
                                        "
                                        x-text="
                                            user.role === 'Premium'
                                                ? 'star'
                                                : (
                                                    user.role === 'Admin'
                                                        ? 'security'
                                                        : 'person'
                                                )
                                        "
                                    ></span>

                                    <span x-text="user.role"></span>

                                </span>

                            </td>

                            <td class="p-5 text-white/80 font-bold mono relative">

                                <span x-text="user.points"></span>

                                <template x-teleport="body">

                                    <div
                                        x-show="dropdownOpen"
                                        style="display: none;"
                                        @click.stop
                                        :style="`top: ${mouseY}px; left: ${mouseX}px;`"
                                        class="fixed w-48 bg-[#0a0a0f] border border-white/10 rounded-xl shadow-[0_0_30px_rgba(0,0,0,0.8)] z-[9999] overflow-hidden flex flex-col"
                                        x-transition.opacity
                                    >

                                        <!-- Standard/Premium users -->
                                        <template x-if="user.role !== 'Moderator' && user.role !== 'Admin'">

                                            <button
                                                @click="promoteModerator(user); dropdownOpen = false;"
                                                class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-500/10 text-white hover:text-indigo-400 text-sm font-bold border-b border-white/5 transition-colors text-left w-full"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">
                                                    verified_user
                                                </span>
                                                Promote to Moderator
                                            </button>

                                        </template>

                                        <!-- Admin -->
                                        <template x-if="user.role === 'Admin'">

                                            <button
                                                @click="demoteAdmin(user); dropdownOpen = false;"
                                                class="flex items-center gap-3 px-4 py-3 hover:bg-orange-500/10 text-white hover:text-orange-400 text-sm font-bold border-b border-white/5 transition-colors text-left w-full"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">
                                                    remove_moderator
                                                </span>
                                                Demote Admin
                                            </button>

                                        </template>

                                        <!-- Moderator -->
                                        <template x-if="user.role === 'Moderator'">

                                            <button
                                                @click="demoteModerator(user); dropdownOpen = false;"
                                                class="flex items-center gap-3 px-4 py-3 hover:bg-orange-500/10 text-white hover:text-orange-400 text-sm font-bold border-b border-white/5 transition-colors text-left w-full"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">
                                                    person_remove
                                                </span>
                                                Demote Moderator
                                            </button>

                                        </template>

                                        <!-- Only non-elevated users can be suspended -->
                                        <template x-if="user.role !== 'Moderator' && user.role !== 'Admin'">

                                            <button
                                                @click="openBanModal(user); dropdownOpen = false;"
                                                :disabled="user.status === 'Banned'"
                                                class="flex items-center gap-3 px-4 py-3 hover:bg-red-500/10 text-white hover:text-red-400 text-sm font-bold transition-colors text-left w-full disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">
                                                    block
                                                </span>
                                                Suspend User
                                            </button>

                                        </template>

                                    </div>

                                </template>

                            </td>

                        </tr>

                    </template>

                    <tr
                        x-show="!isLoading && filteredUsers.length === 0"
                        style="display: none;"
                    >
                        <td
                            colspan="5"
                            class="p-10 text-center text-white/40"
                        >
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">
                                    search_off
                                </span>
                                <p>No users found matching your filters.</p>
                            </div>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

    <!-- Ban User Modal -->
    <div
        x-show="banModalOpen"
        style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center"
        x-transition.opacity
    >

        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            @click="banModalOpen = false"
        ></div>

        <div
            class="relative bg-[#0a0a0f] border border-red-500/30 rounded-2xl p-8 max-w-md w-full shadow-[0_0_40px_rgba(239,68,68,0.15)]"
            x-show="banModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        >

            <div class="flex items-center gap-4 mb-6">

                <div class="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-500 shadow-[0_0_15px_rgba(239,68,68,0.3)]">
                    <span class="material-symbols-outlined text-2xl">
                        gavel
                    </span>
                </div>

                <div>

                    <h3 class="text-xl font-bold text-white">
                        Suspend Directive
                    </h3>

                    <p class="text-sm text-red-400">
                        Initiating ban sequence
                    </p>

                </div>

            </div>

            <template x-if="userToBan">

                <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-6 flex items-center gap-4">

                    <div class="relative w-10 h-10 overflow-visible shrink-0" style="width: 2.5rem; height: 2.5rem;">
                        <div class="absolute inset-0 z-0 overflow-hidden rounded-lg">
                            <img
                                :src="(resolveAvatarUrl ? resolveAvatarUrl(userToBan.avatar_url, userToBan.name) : userToBan.avatar_url) || `https://ui-avatars.com/api/?name=${encodeURIComponent(userToBan.name)}&background=random&color=fff&bold=true`"
                                @error="$el.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userToBan.name)}&background=random&color=fff&bold=true`"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        <template x-if="userToBan.border_preview">
                            <img :src="userToBan.border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                        </template>
                    </div>

                    <div>

                        <p
                            class="text-white font-bold text-sm"
                            x-text="userToBan.name"
                        ></p>

                        <p
                            class="text-white/40 text-xs mono"
                            x-text="userToBan.email"
                        ></p>

                    </div>

                </div>

            </template>

            <div class="mb-6">

                <label class="block text-xs font-bold text-white/50 uppercase tracking-wider mb-2">
                    Reason for Suspension
                </label>

                <textarea
                    x-model="banNotes"
                    class="w-full bg-[#030305] border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-red-500/50 transition-colors h-24 resize-none"
                    placeholder="Provide evidence or internal notes..."
                ></textarea>

            </div>

            <div class="flex gap-3 justify-end">

                <button
                    @click="banModalOpen = false"
                    class="px-5 py-2.5 rounded-xl border border-white/10 text-white/70 hover:bg-white/5 hover:text-white transition-colors text-sm font-bold"
                >
                    Cancel
                </button>

                <button
                    @click="confirmBan()"
                    :disabled="!banNotes"
                    class="px-5 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white transition-all text-sm font-bold shadow-[0_0_15px_rgba(239,68,68,0.4)] hover:shadow-[0_0_25px_rgba(239,68,68,0.6)] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Execute Ban
                </button>

            </div>

        </div>

    </div>

</div>