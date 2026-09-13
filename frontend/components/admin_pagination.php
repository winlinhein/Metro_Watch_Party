<?php
$pagerKey = $pagerKey ?? 'movies';
?>
<nav class="flex items-center justify-center gap-1.5 mt-8 pb-2"
     x-show="adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').showPager"
     x-cloak
     style="display: none;">
    <button type="button"
            @click="setAdminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>', adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').page - 1)"
            :disabled="adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').page <= 1"
            class="w-9 h-9 rounded-lg border border-white/10 bg-white/5 text-white/70 hover:bg-white/10 hover:text-white disabled:opacity-30 disabled:pointer-events-none flex items-center justify-center transition-colors">
        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
    </button>
    <template x-for="page in adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').pages" :key="'<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>-pg-'+page">
        <button type="button"
                @click="setAdminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>', page)"
                class="min-w-9 h-9 px-2 rounded-lg text-[11px] font-bold transition-colors"
                :class="page === adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').page
                    ? 'bg-red-500 text-white shadow-[0_0_12px_rgba(239,68,68,0.35)]'
                    : 'bg-white/5 border border-white/10 text-white/60 hover:text-white hover:bg-white/10'">
            <span x-text="page"></span>
        </button>
    </template>
    <button type="button"
            @click="setAdminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>', adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').page + 1)"
            :disabled="adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').page >= adminPage('<?= htmlspecialchars($pagerKey, ENT_QUOTES, 'UTF-8') ?>').pageCount"
            class="w-9 h-9 rounded-lg border border-white/10 bg-white/5 text-white/70 hover:bg-white/10 hover:text-white disabled:opacity-30 disabled:pointer-events-none flex items-center justify-center transition-colors">
        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
    </button>
</nav>
