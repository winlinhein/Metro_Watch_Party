<div x-show="showPremiumModal"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
     style="display: none;">

    <div class="relative w-full max-w-4xl mx-auto my-8"
         @click.outside="if(!isActivating) showPremiumModal = false">

        <button type="button" @click="showPremiumModal = false"
                class="absolute -top-2 right-0 z-[60] w-10 h-10 bg-white/10 hover:bg-white/20 border border-white/20 rounded-full flex items-center justify-center text-white/70 hover:text-white transition-all backdrop-blur-md">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[520px] h-[520px] bg-indigo-600/20 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute right-1/4 bottom-0 w-[320px] h-[320px] bg-fuchsia-600/15 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 text-center mb-8 pt-6">
            <h2 class="text-sm font-bold text-indigo-400 tracking-widest uppercase mb-2 mono">Uplink Tiers</h2>
            <h3 class="text-2xl md:text-4xl font-bold tracking-tight mb-3">Stay free. Or go Premium.</h3>
            <p class="text-white/50 leading-relaxed text-sm max-w-xl mx-auto">Guest rooms are open to everyone. Premium unlocks cosmetics, unlimited hosting, and a badge that says you run the night.</p>
        </div>

        <div class="relative z-10 grid md:grid-cols-2 gap-4 items-stretch">
            <article class="home-plan-card glass-card rounded-2xl p-5 md:p-6 flex flex-col bg-[#0a0a0f]/80"
                     :class="!isPremium && 'home-plan-current'">
                <p class="text-[10px] mono tracking-[0.25em] text-white/40 uppercase mb-3">Signal</p>
                <h4 class="text-xl font-bold mb-1">Free</h4>
                <p class="text-sm text-white/45 mb-4">Drop in, watch, chat. No card required.</p>
                <div class="flex items-end gap-1 mb-5">
                    <span class="text-4xl font-black tracking-tighter">$0</span>
                    <span class="text-sm text-white/40 mb-1.5 mono">/ forever</span>
                </div>
                <ul class="space-y-2.5 mb-5 flex-1 text-sm text-white/70">
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Guest or account join</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Millisecond sync playback</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Live room chat</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Invite-only rooms</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-emerald-400 text-[18px] mt-0.5">check</span>Host rooms for up to 5 people</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-white/25 text-[18px] mt-0.5">check</span><span class="text-white/35">Premium movies, borders, and bonus points locked</span></li>
                </ul>
                <a x-show="isGuest" href="/frontend/register.php" class="w-full rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 py-3 text-center font-bold cursor-pointer transition-colors">Start for free</a>
                <div x-show="!isGuest && !isPremium" class="w-full rounded-xl border border-emerald-400/30 bg-emerald-500/10 py-3 text-center font-bold text-emerald-300">Your current plan</div>
                <div x-show="!isGuest && isPremium" class="w-full rounded-xl border border-white/10 bg-white/5 py-3 text-center font-bold text-white/40">Included in Premium</div>
            </article>

            <article class="home-plan-card home-plan-featured glass-card rounded-2xl p-5 md:p-6 flex flex-col relative overflow-hidden bg-[#0a0a0f]/80"
                     :class="isPremium && 'home-plan-current'">
                <div class="absolute top-4 right-4 text-[9px] font-bold tracking-[0.2em] uppercase px-2.5 py-1 rounded-full bg-gradient-to-r from-indigo-500/30 to-fuchsia-500/30 border border-indigo-400/40 text-indigo-200"
                     x-text="isPremium ? 'Active' : 'Most chosen'">Most chosen</div>
                <p class="text-[10px] mono tracking-[0.25em] text-indigo-300 uppercase mb-3 inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">stars</span>Nexus Premium
                </p>
                <h4 class="text-xl font-bold mb-1 text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-fuchsia-300 to-indigo-300">Ascend</h4>
                <p class="text-sm text-white/50 mb-4">Ultimate fidelity, unlimited hosting, and a profile that stands out.</p>
                <div class="flex items-end gap-2 mb-5">
                    <span class="text-4xl font-black tracking-tighter">$4.99</span>
                    <span class="text-sm text-white/45 mb-1.5 mono">/ month</span>
                </div>
                <ul class="space-y-2.5 mb-5 flex-1 text-sm text-white/80">
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Everything in Free</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Extra premium movies in the catalog</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Premium borders and exclusive cosmetics</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Double mission points</li>
                    <li class="flex items-start gap-3"><span class="material-symbols-outlined text-fuchsia-400 text-[18px] mt-0.5">check</span>Host rooms for up to 30 people</li>
                </ul>

                <div x-show="isPremium" class="rounded-xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 mb-4 text-center">
                    <p class="text-[10px] mono tracking-widest uppercase text-emerald-300/80 mb-1">Renews / ends</p>
                    <p class="text-sm font-bold text-white" x-text="premiumEndsLabel">Ends —</p>
                    <p class="text-lg font-black tracking-tight text-emerald-300 mono mt-1" x-text="premiumCountdown">--</p>
                </div>

                <a x-show="isGuest" href="/frontend/register.php" class="w-full rounded-xl bg-white text-black hover:shadow-[0_0_40px_rgba(255,255,255,0.28)] py-3 text-center font-black tracking-wide uppercase cursor-pointer inline-flex items-center justify-center gap-2">
                    Unlock Premium <span class="material-symbols-outlined text-[18px]">bolt</span>
                </a>
                <button x-show="!isGuest && !isPremium" type="button" @click="activatePremium()" :disabled="isActivating"
                        class="premium-btn w-full rounded-xl bg-white text-black hover:shadow-[0_0_40px_rgba(255,255,255,0.28)] py-3 text-center font-black tracking-wide uppercase cursor-pointer inline-flex items-center justify-center gap-2 disabled:opacity-60">
                    <span x-text="isActivating ? 'Redirecting…' : 'Unlock Premium'"></span>
                    <span class="material-symbols-outlined text-[18px]">bolt</span>
                </button>
                <div x-show="!isGuest && isPremium" class="w-full rounded-xl border border-indigo-400/30 bg-indigo-500/10 py-3 text-center font-bold text-indigo-200">Your current plan</div>
                <p class="text-[11px] text-white/35 text-center mt-3" x-show="!isPremium">Billed monthly · cancel any time · 30-day cycle</p>
            </article>
        </div>
    </div>
</div>
