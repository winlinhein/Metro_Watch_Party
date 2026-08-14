<!-- Report User Modal -->
<div x-show="showReportModal" 
     class="fixed inset-0 z-[1010] flex items-center justify-center p-4"
     style="display: none;">
     
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" 
         x-show="showReportModal"
         x-transition.opacity
         @click="closeReportModal()"></div>

    <!-- Modal Content -->
    <div class="relative w-full max-w-sm bg-[#0a0a0f] border border-white/10 rounded-2xl shadow-2xl p-6"
         x-show="showReportModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         @click.stop>
         
         <h3 class="text-lg font-bold text-white mb-2">Report User</h3>
         <p class="text-xs text-white/50 mb-4">Reporting <span class="font-bold text-white" x-text="selectedProfileUser?.user_name"></span>.</p>
         
         <!-- Predefined Reasons (Tags) -->
        <div class="space-y-3 mb-5">
            <p class="text-sm text-gray-400">Select reasons (optional):</p>

            <!-- Tags Container -->
            <div class="flex flex-wrap gap-2">
                <!-- Loop through available reasons -->
                <template x-for="reason in availableReasons" :key="reason.reason_id">
                    
                    <!-- Clickable Label Tag -->
                    <label
                        class="relative group cursor-pointer flex items-center justify-center px-3 py-1.5 rounded-lg border transition-all duration-200"
                        :class="selectedReasonIds.includes(String(reason.reason_id)) || selectedReasonIds.includes(Number(reason.reason_id))
                            ? 'bg-red-500/20 border-red-500 text-red-400 shadow-[0_0_10px_rgba(239,68,68,0.2)]'
                            : 'bg-white/5 border-white/10 text-gray-300 hover:bg-white/10'"
                    >
                        <input
                            type="checkbox"
                            :value="reason.reason_id"
                            x-model="selectedReasonIds"
                            class="hidden"
                        >

                        <!-- Show the Reason Title -->
                        <span class="text-xs font-medium" x-text="reason.reason_title"></span>

                        <!-- Hover Tooltip for Description -->
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-max max-w-[200px] p-2 bg-[#050508] border border-white/10 text-[10px] text-gray-300 rounded shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 pointer-events-none text-center">
                            <span x-text="reason.reason_description"></span>
                            
                            <!-- Little triangle pointing down for the tooltip -->
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-white/10"></div>
                        </div>
                    </label>
                </template>
            </div>
        </div>

         <!-- Custom Textarea -->
         <div class="mb-4">
             <p class="text-sm text-gray-400 mb-2">Or write a custom description:</p>
             <textarea x-model="reportDescription" placeholder="Describe reason in detail..." rows="3" 
                       class="w-full bg-black/60 border border-white/10 rounded-xl p-3 text-sm text-white placeholder-white/30 focus:border-red-500/50 outline-none resize-none"></textarea>
         </div>
         
         <div class="flex gap-3 mt-4">
             <button @click="closeReportModal()" class="flex-1 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/70 font-semibold transition-colors">Cancel</button>
             
             <!-- Submit button logic -->
             <button @click="submitReport()" 
                     class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white shadow-[0_0_15px_rgba(239,68,68,0.4)] font-semibold disabled:opacity-50 transition-colors" 
                     :disabled="selectedReasonIds.length === 0 && !reportDescription.trim()">
                 Submit
             </button>
         </div>
    </div>
</div>