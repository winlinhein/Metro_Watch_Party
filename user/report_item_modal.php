<!-- Report Reply Modal -->
<div x-show="showReportItemModal" 
     class="fixed inset-0 z-[1010] flex items-center justify-center p-4"
     style="display: none;"
     id="report-item-modal-wrapper">
     
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" 
         x-show="showReportItemModal"
         x-transition.opacity
         @click="closeReportItemModal()"></div>

    <!-- Modal Content -->
    <div class="relative w-full max-w-sm bg-[#0a0a0f] border border-red-500/30 rounded-2xl shadow-2xl p-6"
         id="report-item-modal-content"
         x-show="showReportItemModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         @click.stop>
         
        <!-- Glitch Overlay for Animation -->
        <div id="item-modal-glitch" class="absolute inset-0 bg-red-600/20 mix-blend-overlay rounded-2xl opacity-0 pointer-events-none"></div>

        <h3 class="text-lg font-bold text-red-500 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">warning</span> <span x-text="reportItemType === 'comment' ? 'Report Comment' : 'Report Reply'"></span>
        </h3>
        <p class="text-xs text-white/50 mb-4">You are reporting a <span x-text="reportItemType"></span>. Please specify why.</p>
         
        <!-- Predefined Reasons (Tags) -->
        <div class="space-y-3 mb-5">
            <p class="text-sm text-gray-400">Select reasons:</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="reason in availableReasons" :key="'item-reason-'+reason.reason_id">
                    <label
                        class="relative group cursor-pointer flex items-center justify-center px-3 py-1.5 rounded-lg border transition-all duration-200"
                        :class="selectedItemReasonIds.includes(String(reason.reason_id)) || selectedItemReasonIds.includes(Number(reason.reason_id))
                            ? 'bg-red-500/20 border-red-500 text-red-400 shadow-[0_0_10px_rgba(239,68,68,0.2)]'
                            : 'bg-white/5 border-white/10 text-gray-300 hover:bg-white/10'"
                    >
                        <input
                            type="checkbox"
                            :value="reason.reason_id"
                            x-model="selectedItemReasonIds"
                            class="hidden"
                        >
                        <span class="text-xs font-medium" x-text="reason.reason_title"></span>
                    </label>
                </template>
            </div>
        </div>

        <!-- Custom Textarea -->
        <div class="mb-4">
            <p class="text-sm text-gray-400 mb-2">Or write a custom description:</p>
            <textarea x-model="reportItemDescription" placeholder="Describe the violation..." rows="3"
                       class="w-full bg-black/60 border border-white/10 rounded-xl p-3 text-sm text-white placeholder-white/30 focus:border-red-500/50 outline-none resize-none transition-colors"></textarea>
        </div>
         
        <div class="flex gap-3 mt-4">
            <button @click="closeReportItemModal()" id="cancel-item-report-btn" class="flex-1 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/70 font-semibold transition-colors">Cancel</button>
            <button @click="submitItemReport()" 
                     id="submit-item-report-btn"
                     class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white shadow-[0_0_15px_rgba(220,38,38,0.5)] font-bold disabled:opacity-50 transition-all transform hover:scale-105 active:scale-95"
                     :disabled="selectedItemReasonIds.length === 0 && !reportItemDescription.trim()">
                Obliterate
            </button>
        </div>
    </div>
</div>
