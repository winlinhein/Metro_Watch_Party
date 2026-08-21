<style>
@keyframes insane-shimmer {
    0% { transform: translateX(-100%) skewX(-15deg); }
    100% { transform: translateX(200%) skewX(-15deg); }
}
@keyframes insane-glitch {
    0% { clip-path: inset(10% 0 80% 0); transform: translate(-2px, 2px); filter: hue-rotate(90deg); }
    20% { clip-path: inset(80% 0 5% 0); transform: translate(2px, -2px); filter: hue-rotate(-90deg); }
    40% { clip-path: inset(40% 0 30% 0); transform: translate(-2px, -2px); filter: hue-rotate(180deg); }
    60% { clip-path: inset(90% 0 2% 0); transform: translate(2px, 2px); filter: hue-rotate(0deg); }
    80% { clip-path: inset(20% 0 60% 0); transform: translate(-1px, 1px); filter: hue-rotate(45deg); }
    100% { clip-path: inset(10% 0 80% 0); transform: translate(1px, -1px); filter: hue-rotate(-45deg); }
}
</style>

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
            
            <div class="min-h-[40px] mb-2 relative overflow-hidden flex items-center">
                <!-- Next-Level Loading Spinner (Only when loading) -->
                <template x-if="statsLoading">
                    <div class="w-full flex items-center h-full relative py-1">
                        <div class="relative w-9 h-9 flex items-center justify-center">
                            <!-- Ambient Glow -->
                            <div class="absolute inset-0 bg-indigo-500/20 rounded-full blur-md animate-pulse"></div>
                            <!-- Outer Orbit Ring -->
                            <div class="absolute inset-0 border-[2px] border-white/5 border-t-indigo-500 border-r-indigo-500 rounded-full animate-[spin_1.5s_linear_infinite] shadow-[0_0_10px_rgba(99,102,241,0.2)]"></div>
                            <!-- Inner Counter-Orbit Ring -->
                            <div class="absolute inset-1.5 border-[2px] border-white/5 border-b-red-500 border-l-red-500 rounded-full animate-[spin_1s_linear_infinite_reverse] shadow-[0_0_10px_rgba(239,68,68,0.2)]"></div>
                            <!-- Core Energy Dot -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-white shadow-[0_0_8px_#fff] rotate-45 animate-ping" style="animation-duration: 1.5s;"></div>
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-white shadow-[0_0_8px_#fff] rotate-45"></div>
                        </div>
                    </div>
                </template>
                
                <!-- Actual Stats Number (Only when loaded) -->
                <template x-if="!statsLoading">
                    <h3 class="text-4xl font-bold text-white tracking-tighter" x-text="stat.value"></h3>
                </template>
            </div>
            
            <p class="text-white/40 text-sm font-medium uppercase tracking-wider" x-text="stat.label"></p>
        </div>
    </template>
</div>
