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
         <p class="text-xs text-white/50 mb-4">Please provide a reason for reporting <span class="font-bold text-white" x-text="selectedProfileUser?.user_name"></span>.</p>
         
         <textarea x-model="reportReason" placeholder="Describe reason in detail..." rows="4" class="w-full bg-black/60 border border-white/10 rounded-xl p-3 text-sm text-white placeholder-white/30 focus:border-red-500/50 outline-none resize-none mb-4"></textarea>
         
         <div class="flex gap-3">
             <button @click="closeReportModal()" class="flex-1 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/70 font-semibold transition-colors">Cancel</button>
             <button @click="reportUser()" class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white shadow-[0_0_15px_rgba(239,68,68,0.4)] font-semibold disabled:opacity-50 transition-colors" :disabled="!reportReason.trim()">Submit Report</button>
         </div>
    </div>
</div>
