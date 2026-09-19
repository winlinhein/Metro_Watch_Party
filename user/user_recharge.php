<div x-show="showRechargeModal"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
     style="display: none;">

    <div class="relative w-full max-w-3xl mx-auto my-8"
         @click.outside="if(!isRecharging) showRechargeModal = false">

        <button type="button" @click="showRechargeModal = false"
                class="absolute -top-2 right-0 z-[60] w-10 h-10 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full flex items-center justify-center text-white/70 hover:text-white transition-all backdrop-blur-md">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[420px] h-[420px] bg-yellow-500/15 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute right-1/4 bottom-0 w-[260px] h-[260px] bg-amber-500/10 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 text-center mb-8 pt-6">
            <h2 class="text-sm font-bold text-yellow-400 tracking-widest uppercase mb-2 mono">Point Top-up</h2>
            <h3 class="text-2xl md:text-4xl font-bold tracking-tight mb-3">Recharge your balance</h3>
            <p class="text-white/50 leading-relaxed text-sm max-w-xl mx-auto">Buy points instantly and spend them in the shop on borders and cosmetics.</p>
            <p class="text-yellow-300/80 text-sm font-bold mt-3 mono">
                Current balance: <span x-text="Number(userPoints || 0).toLocaleString()"></span> PTS
            </p>
        </div>

        <div class="relative z-10 grid sm:grid-cols-2 gap-4">
            <template x-for="pack in pointPacks" :key="pack.id">
                <button type="button"
                        @click="selectedPointPack = pack.id"
                        class="glass-card rounded-2xl p-5 text-left transition-all border"
                        :class="selectedPointPack === pack.id
                            ? 'border-yellow-400/50 bg-yellow-500/10 shadow-[0_0_30px_rgba(234,179,8,0.12)]'
                            : 'border-white/10 hover:border-white/20'">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] mono tracking-[0.25em] uppercase text-white/40" x-text="pack.label"></p>
                        <span x-show="pack.best" class="text-[9px] font-bold tracking-[0.18em] uppercase px-2 py-1 rounded-full bg-yellow-500/20 border border-yellow-400/30 text-yellow-200">Best value</span>
                    </div>
                    <p class="text-3xl font-black tracking-tight text-white mb-1">
                        <span x-text="Number(pack.points).toLocaleString()"></span>
                        <span class="text-sm text-yellow-400 font-bold">PTS</span>
                    </p>
                    <p class="text-white/50 text-sm mono">$<span x-text="pack.price.toFixed(2)"></span></p>
                </button>
            </template>
        </div>

        <button type="button"
                @click="buyPointPack()"
                :disabled="isRecharging || !selectedPointPack"
                class="relative z-10 mt-6 w-full rounded-xl bg-yellow-400 hover:bg-yellow-300 text-black py-3.5 text-center font-black tracking-wide uppercase cursor-pointer inline-flex items-center justify-center gap-2 disabled:opacity-60">
            <span class="material-symbols-outlined text-[18px]">bolt</span>
            <span x-text="isRecharging ? 'Redirecting…' : 'Top up now'"></span>
        </button>
    </div>
</div>
