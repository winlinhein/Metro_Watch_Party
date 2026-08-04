<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
    <template x-for="stat in stats" :key="stat.label">
        <div class="glass-card p-6 rounded-2xl stagger-item gs-stat-card group hover:border-white/20 transition-colors cursor-default">
            <div class="flex justify-between items-start mb-6">
                <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/5 flex items-center justify-center text-white/50 group-hover:text-white group-hover:bg-white/10 transition-all duration-300 group-hover:shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                    <span class="material-symbols-outlined" x-text="stat.icon"></span>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-green-500/10 text-green-400 border border-green-500/20" x-text="stat.change"></span>
            </div>
            <h3 class="text-4xl font-bold text-white mb-2 tracking-tighter" x-text="stat.value"></h3>
            <p class="text-white/40 text-sm font-medium uppercase tracking-wider" x-text="stat.label"></p>
        </div>
    </template>
</div>
