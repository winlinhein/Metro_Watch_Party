<!-- Charts Area -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 pb-10">
    <div class="xl:col-span-2 glass-card rounded-2xl p-8 stagger-item">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-lg font-bold tracking-wide">Network Traffic</h3>
            <select class="bg-[#030305] border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white outline-none cursor-pointer focus:border-red-500/50 transition-colors">
                <option class="bg-[#030305] text-white">Last 7 Days</option>
                <option class="bg-[#030305] text-white">Last 30 Days</option>
            </select>
        </div>
        <!-- Animated CSS Chart -->
        <div class="h-64 flex items-end gap-3 border-l-2 border-b-2 border-white/5 pb-2 pl-3 relative" id="traffic-chart">
                        <template x-for="(data, index) in networkTraffic" :key="index">
                <div class="flex-1 bg-gradient-to-t from-red-600/50 to-indigo-500/80 rounded-t-sm relative group cursor-pointer hover:brightness-125 transition-all chart-bar" :style="`height: ${data.height}%`">
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-white/10 backdrop-blur-md text-xs py-1.5 px-3 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10 border border-white/20 text-white font-bold shadow-xl translate-y-2 group-hover:translate-y-0 duration-200">
                        <span x-text="data.reqs"></span> reqs
                    </div>
                </div>
            </template>
        </div>
    </div>
    
    <div class="glass-card rounded-2xl p-8 stagger-item">
        <h3 class="text-lg font-bold tracking-wide mb-6">Active Sessions</h3>
        <div class="space-y-5">
            <template x-for="room in rooms.slice(0, 5)" :key="room.id">
                <div class="flex items-center gap-4 group cursor-pointer">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-white/5 group-hover:border-indigo-500/30 flex items-center justify-center shrink-0 text-indigo-400 group-hover:text-white transition-all group-hover:shadow-[0_0_15px_rgba(99,102,241,0.2)]">
                        <span class="material-symbols-outlined text-[20px]">play_circle</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-white truncate group-hover:text-indigo-300 transition-colors" x-text="room.name"></h4>
                        <p class="text-xs text-white/40 truncate mono mt-0.5" x-text="'ID: ' + room.host"></p>
                    </div>
                    <div class="text-xs font-bold text-green-400 flex items-center gap-1.5 bg-green-500/10 px-2 py-1 rounded-md border border-green-500/20">
                        <span class="material-symbols-outlined text-[14px]">group</span>
                        <span x-text="room.users"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
