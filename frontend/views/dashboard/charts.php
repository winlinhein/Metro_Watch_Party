<!-- Charts Area -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 pb-10">
    <div class="xl:col-span-2 glass-card allow-overflow rounded-2xl p-8 stagger-item relative">
        <div class="relative z-10 flex flex-wrap justify-between items-center gap-4 mb-8">
            <div>
                <h3 class="text-lg font-bold tracking-wide" x-text="chartMode === 'logins' ? 'Login Activity' : 'Revenue'"></h3>
                <p class="text-[11px] text-white/40 uppercase tracking-widest mono mt-1" x-text="chartMode === 'logins' ? 'Sessions signed in' : 'Successful payments'"></p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative flex items-center bg-black/40 p-1 rounded-xl border border-white/10 overflow-hidden" data-chart-toggle>
                    <div class="chart-mode-pill absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-lg bg-gradient-to-r from-red-600/80 to-indigo-500/80 shadow-[0_0_18px_rgba(239,68,68,0.35)] pointer-events-none"
                         :class="chartMode === 'logins' ? 'is-logins' : ''"></div>
                    <button type="button"
                            @click="setAdminChartMode('revenue')"
                            class="relative z-10 min-w-[92px] px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-lg transition-colors"
                            :class="chartMode === 'revenue' ? 'text-white' : 'text-white/45 hover:text-white'">
                        Revenue
                    </button>
                    <button type="button"
                            @click="setAdminChartMode('logins')"
                            class="relative z-10 min-w-[92px] px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-lg transition-colors"
                            :class="chartMode === 'logins' ? 'text-white' : 'text-white/45 hover:text-white'">
                        Logins
                    </button>
                </div>
                <select x-model="chartRange"
                        @change="onAdminChartRangeChange()"
                        class="bg-[#0a0a0f] border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white outline-none cursor-pointer focus:border-red-500/50 transition-colors">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>
            </div>
        </div>

        <div class="relative h-64 admin-chart-stage"
             @mouseleave="hideChartTooltip()">
            <div x-show="chartTooltip.show"
                 x-cloak
                 class="chart-float-tooltip pointer-events-none absolute z-40 min-w-[92px] px-3 py-2 rounded-xl bg-black/90 border border-white/20 shadow-[0_12px_30px_rgba(0,0,0,0.45)] backdrop-blur-md text-center"
                 :style="`left: ${chartTooltip.x}px; top: ${chartTooltip.y}px;`">
                <p class="text-[10px] uppercase tracking-widest text-white/50 font-mono" x-text="chartTooltip.title"></p>
                <p class="text-sm font-bold text-white mt-0.5" x-text="chartTooltip.value"></p>
            </div>

            <div x-show="chartMode !== 'logins'"
                 x-cloak
                 class="absolute inset-0 flex items-end gap-2 sm:gap-3 border-l-2 border-b-2 border-white/5 pb-6 pl-3 overflow-hidden"
                 id="traffic-chart">
                <template x-for="(data, index) in currentChartSeries" :key="'rev-' + chartRange + '-' + index">
                    <div class="flex-1 min-w-0 h-full flex flex-col cursor-pointer"
                         @mousemove="hoverRevenuePoint($event, data, index)"
                         @mouseenter="hoverRevenuePoint($event, data, index)">
                        <div class="flex-1 min-h-0 w-full flex items-end overflow-hidden">
                            <div class="relative w-full chart-bar rounded-t-md bg-gradient-to-t from-red-600/70 via-fuchsia-500/50 to-indigo-400/90 shadow-[0_0_18px_rgba(239,68,68,0.18)]"
                                 :class="chartHoverIndex === index ? 'brightness-125' : ''"
                                 :style="`height: ${data.height}%`">
                                <div class="chart-bar-shine absolute inset-x-0 h-1/2 bg-gradient-to-b from-white/40 to-transparent pointer-events-none"></div>
                            </div>
                        </div>
                        <span class="mt-2 shrink-0 text-[9px] uppercase tracking-widest text-white/35 font-mono" x-text="data.label"></span>
                    </div>
                </template>
            </div>

            <div x-show="chartMode === 'logins'"
                 x-cloak
                 class="absolute inset-0 login-chart-wrap"
                 id="login-chart"
                 @mousemove="hoverLoginChart($event)">
                <svg class="w-full h-full overflow-visible" viewBox="0 0 640 220" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="loginAreaFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#22d3ee" stop-opacity="0.35"></stop>
                            <stop offset="100%" stop-color="#6366f1" stop-opacity="0.02"></stop>
                        </linearGradient>
                        <linearGradient id="loginStroke" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#22d3ee"></stop>
                            <stop offset="50%" stop-color="#818cf8"></stop>
                            <stop offset="100%" stop-color="#f43f5e"></stop>
                        </linearGradient>
                        <filter id="loginGlow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="3.5" result="blur"/>
                            <feMerge>
                                <feMergeNode in="blur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    <line class="login-grid-line" :x1="loginChartGeometry.padX" :x2="loginChartGeometry.w - loginChartGeometry.padX" :y1="loginChartGeometry.grid[0]" :y2="loginChartGeometry.grid[0]" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4 6"></line>
                    <line class="login-grid-line" :x1="loginChartGeometry.padX" :x2="loginChartGeometry.w - loginChartGeometry.padX" :y1="loginChartGeometry.grid[1]" :y2="loginChartGeometry.grid[1]" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4 6"></line>
                    <line class="login-grid-line" :x1="loginChartGeometry.padX" :x2="loginChartGeometry.w - loginChartGeometry.padX" :y1="loginChartGeometry.grid[2]" :y2="loginChartGeometry.grid[2]" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4 6"></line>
                    <line class="login-grid-line" :x1="loginChartGeometry.padX" :x2="loginChartGeometry.w - loginChartGeometry.padX" :y1="loginChartGeometry.grid[3]" :y2="loginChartGeometry.grid[3]" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4 6"></line>
                    <line class="login-grid-line" :x1="loginChartGeometry.padX" :x2="loginChartGeometry.w - loginChartGeometry.padX" :y1="loginChartGeometry.grid[4]" :y2="loginChartGeometry.grid[4]" stroke="rgba(255,255,255,0.12)" stroke-width="1" stroke-dasharray="4 6"></line>
                    <path class="login-area-path"
                          :d="loginChartGeometry.area"
                          fill="url(#loginAreaFill)"></path>
                    <path class="login-line-path"
                          :d="loginChartGeometry.line"
                          fill="none"
                          stroke="url(#loginStroke)"
                          stroke-width="3"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          filter="url(#loginGlow)"></path>
                    <line class="login-scan-line"
                          :x1="loginChartGeometry.padX"
                          :x2="loginChartGeometry.w - loginChartGeometry.padX"
                          y1="110"
                          y2="110"
                          stroke="rgba(34,211,238,0.55)"
                          stroke-width="1.5"></line>
                    <line class="login-hover-guide"
                          x-show="chartHoverIndex >= 0 && loginChartGeometry.points[chartHoverIndex]"
                          :x1="(loginChartGeometry.points[chartHoverIndex] || {}).x || 0"
                          :x2="(loginChartGeometry.points[chartHoverIndex] || {}).x || 0"
                          :y1="loginChartGeometry.grid[0]"
                          :y2="loginChartGeometry.grid[4]"
                          stroke="rgba(34,211,238,0.45)"
                          stroke-width="1"
                          stroke-dasharray="3 5"></line>
                    <template x-for="(point, pi) in loginChartGeometry.points" :key="'d-' + pi">
                        <g class="login-dot" :class="chartHoverIndex === pi ? 'is-hover' : ''" :transform="`translate(${point.x} ${point.y})`">
                            <circle r="14" fill="transparent"></circle>
                            <circle :r="chartHoverIndex === pi ? 11 : 7" :fill="chartHoverIndex === pi ? 'rgba(34,211,238,0.32)' : 'rgba(34,211,238,0.18)'"></circle>
                            <circle :r="chartHoverIndex === pi ? 5 : 3.4" fill="#e0f2fe" stroke="#22d3ee" stroke-width="1.5"></circle>
                        </g>
                    </template>
                </svg>
                <div class="absolute inset-x-0 bottom-0 flex justify-between px-7 text-[9px] uppercase tracking-widest text-white/35 font-mono pointer-events-none">
                    <template x-for="(data, index) in currentChartSeries" :key="'lbl-' + index">
                        <span x-show="chartRange === '7' || index % 5 === 0 || index === currentChartSeries.length - 1" x-text="data.label"></span>
                    </template>
                </div>
            </div>
        </div>
    </div>
    
    <div class="glass-card rounded-2xl p-8 stagger-item relative overflow-hidden">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold tracking-wide">Active Sessions</h3>
            <span class="text-[10px] font-bold uppercase tracking-widest mono px-2 py-1 rounded-md border"
                  :class="(rooms && rooms.length) ? 'text-emerald-400 border-emerald-500/20 bg-emerald-500/10' : 'text-white/40 border-white/10 bg-white/5'"
                  x-text="(rooms && rooms.length) ? (rooms.length + ' live') : 'idle'"></span>
        </div>
        <div class="space-y-5" x-show="rooms && rooms.length" data-session-list>
            <template x-for="room in rooms.slice(0, 5)" :key="room.id">
                <div class="admin-session-row flex items-center gap-4 group cursor-pointer" @click="viewRoom(room)">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-white/5 group-hover:border-indigo-500/30 flex items-center justify-center shrink-0 text-indigo-400 group-hover:text-white transition-all group-hover:shadow-[0_0_15px_rgba(99,102,241,0.2)]">
                        <span class="material-symbols-outlined text-[20px]">play_circle</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-white truncate group-hover:text-indigo-300 transition-colors" x-text="room.movie_title || room.name"></h4>
                        <p class="text-xs text-white/40 truncate mono mt-0.5" x-text="'Host: ' + room.host"></p>
                    </div>
                    <div class="text-xs font-bold text-green-400 flex items-center gap-1.5 bg-green-500/10 px-2 py-1 rounded-md border border-green-500/20">
                        <span class="material-symbols-outlined text-[14px]">group</span>
                        <span x-text="room.users"></span>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="!rooms || rooms.length === 0"
             x-cloak
             data-session-empty
             class="admin-empty-sessions flex flex-col items-center justify-center text-center min-h-[220px] px-4">
            <div class="relative w-20 h-20 mb-5 flex items-center justify-center">
                <span class="empty-orbit absolute inset-0 rounded-full border border-dashed border-white/15"></span>
                <span class="empty-orbit empty-orbit-slow absolute inset-2 rounded-full border border-cyan-400/20"></span>
                <span class="empty-icon relative z-10 material-symbols-outlined text-4xl text-white/35">sensors_off</span>
            </div>
            <h4 class="empty-title text-lg font-bold text-white mb-1">Nothing is active</h4>
            <p class="text-sm text-white/40 max-w-[220px]">No live watch parties right now. New rooms will appear here the moment someone starts one.</p>
        </div>
    </div>
</div>
