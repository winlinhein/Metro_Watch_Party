<!-- Reports View -->
<div data-tab-panel="reports" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full overflow-y-auto">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Reports Analysis</h2>
            <p class="text-white/40 text-sm">Review and resolve user and room infractions.</p>
        </div>
        <button class="relative px-6 py-3 overflow-hidden rounded-xl group hover:scale-105 active:scale-95 transition-all duration-300 shadow-xl shadow-red-500/20">
            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-600 via-red-500 to-red-800 opacity-80 group-hover:opacity-100 transition-opacity"></span>
            <span class="absolute -inset-1 w-full h-full bg-gradient-to-r from-red-500 via-red-400 to-red-600 blur-xl opacity-30 group-hover:opacity-60 transition-opacity animate-pulse"></span>
            <div class="relative flex items-center gap-2 text-white font-bold text-sm tracking-wide">
                <span class="material-symbols-outlined text-[18px]">download</span> Export Data
            </div>
        </button>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 stagger-item">
        <div class="glass-card rounded-2xl p-6 border border-white/10 relative overflow-hidden group hover:border-blue-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/20 hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.8)]"></div>
            <div class="relative z-10 w-full flex items-center justify-between">
                <div>
                    <p class="text-white/40 text-xs font-bold uppercase tracking-wider mb-1">Total Reports</p>
                    <h3 class="text-3xl font-bold text-white" x-text="reportStats?.total || 0"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
                    <span class="material-symbols-outlined text-2xl">assessment</span>
                </div>
            </div>
        </div>
        
        <div class="glass-card rounded-2xl p-6 border border-white/10 relative overflow-hidden group hover:border-orange-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-orange-500/20 hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-orange-500 shadow-[0_0_10px_rgba(249,115,22,0.8)]"></div>
            <div class="relative z-10 w-full flex items-center justify-between">
                <div>
                    <p class="text-white/40 text-xs font-bold uppercase tracking-wider mb-1">Pending Reports</p>
                    <h3 class="text-3xl font-bold text-white" x-text="reportStats?.pending || 0"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center border border-orange-500/20">
                    <span class="material-symbols-outlined text-2xl">pending_actions</span>
                </div>
            </div>
        </div>
        
        <div class="glass-card rounded-2xl p-6 border border-white/10 relative overflow-hidden group hover:border-green-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-green-500/20 hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.8)]"></div>
            <div class="relative z-10 w-full flex items-center justify-between">
                <div>
                    <p class="text-white/40 text-xs font-bold uppercase tracking-wider mb-1">Read / Resolved</p>
                    <h3 class="text-3xl font-bold text-white" x-text="reportStats?.read || 0"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-500/10 text-green-400 flex items-center justify-center border border-green-500/20">
                    <span class="material-symbols-outlined text-2xl">task_alt</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="glass-card rounded-2xl overflow-hidden stagger-item">
        <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
            <h3 class="text-lg font-bold text-white">Recent Reports</h3>
            <div class="flex gap-2 relative">
                <select x-model="filterStatus" class="appearance-none bg-black/40 border border-white/10 rounded-xl pl-4 pr-10 py-2 text-sm text-white outline-none cursor-pointer focus:border-red-500/50 transition-colors shadow-inner">
                    <option class="bg-[#030305] text-white" value="all">All Status</option>
                    <option class="bg-[#030305] text-white" value="pending">Pending</option>
                    <option class="bg-[#030305] text-white" value="read">Read</option>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-2.5 text-white/40 pointer-events-none text-[18px]">expand_more</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/20 border-b border-white/10 text-white/40 text-xs uppercase tracking-wider">
                        <th class="p-5 font-semibold">ID / Date</th>
                        <th class="p-5 font-semibold">Reporter</th>
                        <th class="p-5 font-semibold">Type</th>
                        <th class="p-5 font-semibold">Reported Target</th>
                        <th class="p-5 font-semibold text-center">Status</th>
                        <th class="p-5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    <template x-for="report in filteredReports" :key="report.id">
                        <tr x-show="filterStatus === 'all' || filterStatus === report.status.toLowerCase()" class="hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <td class="p-5">
                                <div class="font-bold text-white mono mb-0.5" x-text="report.id"></div>
                                <div class="text-xs text-white/40" x-text="report.date"></div>
                            </td>
                            <td class="p-5 font-medium text-white/70" x-text="report.user"></td>
                            <td class="p-5">
                                <span class="px-3 py-1 rounded-full border text-[11px] font-bold tracking-wide" 
                                      :class="{
                                          'bg-red-500/10 text-red-400 border-red-500/20 shadow-[0_0_10px_rgba(239,68,68,0.15)]': report.type === 'High',
                                          'bg-yellow-500/10 text-yellow-400 border-yellow-500/20 shadow-[0_0_10px_rgba(234,179,8,0.15)]': report.type === 'Medium',
                                          'bg-blue-500/10 text-blue-400 border-blue-500/20 shadow-[0_0_10px_rgba(59,130,246,0.15)]': report.type === 'Low' || !['High','Medium'].includes(report.type)
                                      }" x-text="report.type || 'Standard'"></span>
                            </td>
                            <td class="p-5 text-white/60 truncate max-w-[200px]" x-text="report.excerpt"></td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="report.status === 'Pending' ? 'bg-orange-500/20 text-orange-400 border border-orange-500/30' : 'bg-green-500/20 text-green-400 border border-green-500/30'"
                                      x-text="report.status"></span>
                            </td>
                            <td class="p-5 text-right">
                                <button @click="viewReport(report)" class="p-2 bg-white/5 border border-white/10 hover:bg-white/10 hover:border-white/20 rounded-xl text-white/60 hover:text-white transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Empty State -->
                    <tr x-show="!filteredReports || filteredReports.length === 0">
                        <td colspan="6" class="p-10 text-center text-white/40">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50">inbox</span>
                            <p>No reports found matching your criteria.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Report Modal (Teleported to body) -->
    <template x-teleport="body">
        <div x-show="viewModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="viewModalOpen = false" x-show="viewModalOpen" x-transition.opacity></div>
            
            <!-- Modal Content -->
            <div class="relative bg-[#0a0a0f] border border-white/10 rounded-2xl p-8 max-w-lg w-full shadow-2xl"
                x-show="viewModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                
                <template x-if="selectedReport">
                    <div>
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-400 shadow-[0_0_15px_rgba(239,68,68,0.3)]">
                                    <span class="material-symbols-outlined text-2xl">gavel</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">Incident Report</h3>
                                    <p class="text-sm text-red-400/60 uppercase mono" x-text="'ID: ' + selectedReport.id"></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4 mb-6 text-sm">
                            
                            <!-- Reporter & Target Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-[#030305] border border-white/10 rounded-xl p-4">
                                    <p class="text-white/40 text-[10px] uppercase tracking-wider mb-1 font-bold">Filed By</p>
                                    <span class="text-white font-medium" x-text="selectedReport.user"></span>
                                </div>
                                <div class="bg-red-500/5 border border-red-500/10 rounded-xl p-4">
                                    <p class="text-red-400/60 text-[10px] uppercase tracking-wider mb-2 font-bold">Reported Target</p>
                                    <div class="flex items-center gap-2.5">
                                        <div class="relative w-8 h-8 shrink-0 overflow-visible" style="width: 2rem; height: 2rem;">
                                            <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.15] bg-red-500/20">
                                                <img x-show="selectedReport.reported_avatar_url" :src="selectedReport.reported_avatar_url" class="absolute inset-0 h-full w-full object-cover" alt="">
                                                <div x-show="!selectedReport.reported_avatar_url" class="absolute inset-0 flex items-center justify-center text-[11px] font-bold text-red-100 uppercase"
                                                     x-text="(selectedReport.reported_user || 'U').charAt(0)"></div>
                                            </div>
                                            <template x-if="selectedReport.reported_border_preview">
                                                <img :src="selectedReport.reported_border_preview" class="absolute inset-0 z-10 h-full w-full scale-[1.4] object-contain pointer-events-none" alt="">
                                            </template>
                                        </div>
                                        <span class="text-red-100 font-medium" x-text="selectedReport.reported_user || 'Unknown'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Reported Comment / Reply -->
                            <div x-show="reportCommentDetails" class="bg-indigo-500/5 border border-indigo-500/10 rounded-xl p-4">
                                <p class="text-indigo-400/60 text-[10px] uppercase tracking-wider mb-3 font-bold">
                                    Reported Comment <span x-text="reportCommentDetails.parent_id ? '(Reply)' : ''"></span>
                                </p>
                                <!-- No loading spinner needed -->
                                <template x-if="reportCommentDetails">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-xs text-white uppercase"
                                                x-text="reportCommentDetails.user_name ? reportCommentDetails.user_name.charAt(0) : 'U'"></div>
                                            <span class="text-white font-medium text-sm" x-text="reportCommentDetails.user_name"></span>
                                            <span class="text-white/40 text-xs" x-text="new Date(reportCommentDetails.created_at).toLocaleString()"></span>
                                        </div>
                                        <p class="text-white/90 text-sm leading-relaxed" x-text="reportCommentDetails.comment_text"></p>
                                        <div class="flex items-center gap-2 text-xs text-white/40">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">favorite</span>
                                                <span x-text="reportCommentDetails.likes_count || 0"></span>
                                            </span>
                                            <span x-show="reportCommentDetails.parent_id" class="text-indigo-400/60">
                                                Reply to comment #<span x-text="reportCommentDetails.parent_id"></span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Dynamic Reason Tags -->
                            <div class="bg-[#030305] border border-white/10 rounded-xl p-4">
                                <p class="text-white/40 text-[10px] uppercase tracking-wider mb-3 font-bold">Violations</p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="reason in (selectedReport.reason ? selectedReport.reason.split(',') : ['Policy Violation'])">
                                        <span class="px-2.5 py-1 bg-white/5 border border-white/10 text-white/80 text-[11px] rounded flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            <span x-text="reason.trim()"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Description / Notes -->
                            <div class="bg-[#030305] border border-white/10 rounded-xl p-4">
                                <p class="text-white/40 text-[10px] uppercase tracking-wider mb-2 font-bold">Details & Evidence</p>
                                <p class="text-white/80 leading-relaxed text-xs" x-text="selectedReport.description || 'No additional details provided.'"></p>
                            </div>
                        </div>
                        
                        <!-- Footer Buttons -->
                        <div class="flex gap-3 justify-end">
                            <button x-show="selectedReport.reported_comment_id && selectedReport.reported_movie_id" @click="viewModalOpen = false; handleViewComment({movie_id: selectedReport.reported_movie_id, comment_id: selectedReport.reported_comment_id})" class="px-5 py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white transition-all text-sm font-bold shadow-[0_0_15px_rgba(99,102,241,0.4)] flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">visibility</span> View Comment
                            </button>
                            <button @click="viewModalOpen = false" class="px-5 py-2.5 rounded-xl border border-white/10 text-white/70 hover:bg-white/5 hover:text-white transition-colors text-sm font-bold">
                                Dismiss
                            </button>
                            <button x-show="selectedReport.status === 'Pending'" @click="resolveReport()" class="px-5 py-2.5 rounded-xl bg-green-500 hover:bg-green-600 text-white transition-all text-sm font-bold shadow-[0_0_15px_rgba(34,197,94,0.4)] flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span> Mark Resolved
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
</div>