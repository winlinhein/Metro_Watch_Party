<!-- Reports View -->
<div x-data="{ viewModalOpen: false, selectedReport: null, viewReport(report) { this.selectedReport = report; this.viewModalOpen = true; }, resolveReport() { if(this.selectedReport) { this.selectedReport.status = 'Read'; this.viewModalOpen = false; } } }" data-tab-panel="reports" style="display: none;" class="absolute inset-0 p-10 w-full min-h-full">
    <div class="flex items-center justify-between mb-10 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Reports Analysis</h2>
            
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
            <h3 class="text-3xl font-bold text-white" x-text="reportStats.total"></h3>
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
            <h3 class="text-3xl font-bold text-white" x-text="reportStats.pending"></h3>
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
            <h3 class="text-3xl font-bold text-white" x-text="reportStats.read"></h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-green-500/10 text-green-400 flex items-center justify-center border border-green-500/20">
            <span class="material-symbols-outlined text-2xl">task_alt</span>
        </div>
    </div>
</div>
    </div>

    <!-- Reports Table -->
    <div class="glass-card rounded-2xl overflow-hidden stagger-item">
        <div class="p-6 border-b border-white/5 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white">Recent Reports</h3>
            <div class="flex gap-2">
                <select class="bg-[#030305] border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white outline-none cursor-pointer focus:border-red-500/50 transition-colors">
                    <option class="bg-[#030305] text-white" value="all">All Status</option>
                    <option class="bg-[#030305] text-white" value="pending">Pending</option>
                    <option class="bg-[#030305] text-white" value="read">Read</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-white/10 text-white/40 text-xs uppercase tracking-wider">
                        <th class="p-4 font-semibold">ID / Date</th>
                        <th class="p-4 font-semibold">User</th>
                        <th class="p-4 font-semibold">Type</th>
                        <th class="p-4 font-semibold">Excerpt</th>
                        <th class="p-4 font-semibold text-center">Status</th>
                        <th class="p-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    <template x-for="report in reportsList" :key="report.id">
                        <tr class="hover:bg-white/10 border-b border-white/5 hover:border-white/10 transition-colors group cursor-pointer">
                            <td class="p-4">
                                <div class="font-bold text-white mono mb-0.5" x-text="report.id"></div>
                                <div class="text-xs text-white/40" x-text="report.date"></div>
                            </td>
                            <td class="p-4 font-medium text-white/70" x-text="report.user"></td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded border text-xs font-bold" 
                                      :class="{
                                          'bg-red-500/10 text-red-400 border-red-500/20': report.priority === 'High',
                                          'bg-yellow-500/10 text-yellow-400 border-yellow-500/20': report.priority === 'Medium',
                                          'bg-blue-500/10 text-blue-400 border-blue-500/20': report.priority === 'Low'
                                      }" x-text="report.type"></span>
                            </td>
                            <td class="p-4 text-white/60 truncate max-w-[200px]" x-text="report.excerpt"></td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="report.status === 'Pending' ? 'bg-orange-500/20 text-orange-400 border border-orange-500/30' : 'bg-green-500/20 text-green-400 border border-green-500/30'"
                                      x-text="report.status"></span>
                            </td>
                            <td class="p-4 text-right">
                                <button @click="viewReport(report)" class="p-2 hover:bg-white/10 rounded-lg text-white/40 hover:text-white transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>


    <!-- View Report Modal -->
    <div x-show="viewModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="viewModalOpen = false" x-show="viewModalOpen" x-transition.opacity></div>
        
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
                     <div class="flex justify-between items-start mb-6">
                         <div class="flex items-center gap-4">
                             <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.3)]">
                                 <span class="material-symbols-outlined text-2xl">receipt_long</span>
                             </div>
                             <div>
                                 <h3 class="text-xl font-bold text-white">Report Details</h3>
                                 <p class="text-sm text-blue-400/60" x-text="selectedReport.id"></p>
                             </div>
                         </div>
                         <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                               :class="selectedReport.status === 'Pending' ? 'bg-orange-500/20 text-orange-400 border border-orange-500/30' : 'bg-green-500/20 text-green-400 border border-green-500/30'"
                               x-text="selectedReport.status"></span>
                     </div>
                     
                     <div class="space-y-4 mb-6 text-sm">
                         <div class="bg-[#030305] border border-white/10 rounded-xl p-4">
                             <p class="text-white/40 text-xs uppercase tracking-wider mb-1 font-bold">Reporter</p>
                             <div class="flex items-center gap-3">
                                 <img :src="`https://ui-avatars.com/api/?name=${selectedReport.user}&background=random&color=fff&bold=true`" class="w-8 h-8 rounded-lg">
                                 <span class="text-white font-medium" x-text="selectedReport.user"></span>
                             </div>
                         </div>
                         <div class="bg-[#030305] border border-white/10 rounded-xl p-4">
                             <p class="text-white/40 text-xs uppercase tracking-wider mb-1 font-bold">Issue Type</p>
                             <span class="text-white" x-text="selectedReport.type"></span>
                         </div>
                         <div class="bg-[#030305] border border-white/10 rounded-xl p-4">
                             <p class="text-white/40 text-xs uppercase tracking-wider mb-2 font-bold">Description</p>
                             <p class="text-white/80 leading-relaxed" x-text="selectedReport.excerpt + ' (Detailed logs attached in full view. User experienced issue on device X9.)'"></p>
                         </div>
                     </div>
                     
                     <div class="flex gap-3 justify-end">
                         <button @click="viewModalOpen = false" class="px-5 py-2.5 rounded-xl border border-white/10 text-white/70 hover:bg-white/5 hover:text-white transition-colors text-sm font-bold">
                             Close
                         </button>
                         <button x-show="selectedReport.status === 'Pending'" @click="resolveReport()" class="px-5 py-2.5 rounded-xl bg-green-500 hover:bg-green-600 text-white transition-all text-sm font-bold shadow-[0_0_15px_rgba(34,197,94,0.4)] hover:shadow-[0_0_25px_rgba(34,197,94,0.6)] flex items-center gap-2">
                             <span class="material-symbols-outlined text-[18px]">check_circle</span> Mark Resolved
                         </button>
                     </div>
                 </div>
             </template>
        </div>
    </div>
</div>