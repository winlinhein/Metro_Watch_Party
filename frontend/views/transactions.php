<!-- Transaction History View -->
<div data-tab-panel="transactions" style="display: none; padding-top: 7.5rem;" class="absolute inset-0 px-10 pb-10 w-full h-full overflow-y-auto">

    <div class="flex items-center justify-between mb-6 stagger-item">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight mb-1">Transaction History</h2>
            <p class="text-white/40 text-sm">Premium payments and billing activity across the network.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 stagger-item">
        <div class="glass-card rounded-2xl p-5 border border-white/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-1">Total Volume</p>
            <h3 class="text-2xl font-bold text-white mono" x-text="txnSummary.volume"></h3>
        </div>
        <div class="glass-card rounded-2xl p-5 border border-white/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-1">Successful</p>
            <h3 class="text-2xl font-bold text-emerald-400 mono" x-text="txnSummary.success"></h3>
        </div>
        <div class="glass-card rounded-2xl p-5 border border-white/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-1">Pending</p>
            <h3 class="text-2xl font-bold text-yellow-400 mono" x-text="txnSummary.pending"></h3>
        </div>
        <div class="glass-card rounded-2xl p-5 border border-white/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-1">Failed / Refunded</p>
            <h3 class="text-2xl font-bold text-red-400 mono" x-text="txnSummary.issues"></h3>
        </div>
    </div>

    <div class="glass-card rounded-2xl stagger-item border-white/10 relative min-h-[400px]">
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-white/10 bg-white/5">
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">Transaction</th>
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">User</th>
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">Plan</th>
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">Gateway</th>
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">Amount</th>
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">Status</th>
                        <th class="p-5 text-xs font-bold text-white/50 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="txn in pagedTransactions" :key="txn.id">
                        <tr class="border-b border-white/5 hover:bg-white/[0.03] transition-colors group">
                            <td class="p-5">
                                <span class="font-bold text-white text-sm mono block" x-text="txn.id"></span>
                                <span class="text-[10px] text-white/30 mono" x-text="txn.gateway_txn_id"></span>
                            </td>
                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-9 h-9 overflow-visible shrink-0" style="width: 2.25rem; height: 2.25rem;">
                                        <div class="absolute inset-0 z-0 overflow-hidden rounded-full scale-[1.18] bg-white/5">
                                            <img
                                                :src="txn.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(txn.user_name)}&background=ef4444&color=fff&bold=true`"
                                                class="absolute inset-0 h-full w-full object-cover"
                                                alt=""
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <span class="font-bold text-white text-sm block" x-text="txn.user_name"></span>
                                        <span class="text-[10px] text-white/30" x-text="txn.email"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5">
                                <span class="text-white/80 text-sm font-medium" x-text="txn.plan"></span>
                            </td>
                            <td class="p-5">
                                <span class="text-white/60 text-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-indigo-400">payments</span>
                                    <span x-text="txn.gateway"></span>
                                </span>
                            </td>
                            <td class="p-5 text-white font-bold mono" x-text="txn.amount"></td>
                            <td class="p-5">
                                <span
                                    class="px-3 py-1 rounded-md text-xs font-bold border"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400 border-emerald-500/20': txn.status === 'Success',
                                        'bg-yellow-500/10 text-yellow-400 border-yellow-500/20': txn.status === 'Pending',
                                        'bg-red-500/10 text-red-400 border-red-500/20': txn.status === 'Failed',
                                        'bg-white/5 text-white/50 border-white/10': txn.status === 'Refunded'
                                    }"
                                    x-text="txn.status"
                                ></span>
                            </td>
                            <td class="p-5 text-white/50 text-sm mono" x-text="txn.date"></td>
                        </tr>
                    </template>

                    <tr x-show="filteredTransactions.length === 0" style="display: none;">
                        <td colspan="7" class="p-10 text-center text-white/40">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">receipt_long</span>
                                <p>No transactions found matching your filters.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php $pagerKey = 'transactions'; include __DIR__ . '/../components/admin_pagination.php'; ?>
    </div>
</div>
