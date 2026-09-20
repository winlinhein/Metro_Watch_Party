<?php
$fetchLoaderShow = $fetchLoaderShow ?? 'true';
$fetchLoaderLabel = $fetchLoaderLabel ?? 'Loading';
$fetchLoaderClass = $fetchLoaderClass ?? 'w-full py-16';
?>
<style>
@keyframes nexus-fetch-spin { to { transform: rotate(360deg); } }
@keyframes nexus-fetch-spin-rev { to { transform: rotate(-360deg); } }
.nexus-fetch-orbit-outer { animation: nexus-fetch-spin 1.4s linear infinite; }
.nexus-fetch-orbit-inner { animation: nexus-fetch-spin-rev 1s linear infinite; }
</style>
<div x-show="<?php echo htmlspecialchars($fetchLoaderShow, ENT_QUOTES, 'UTF-8'); ?>"
     x-cloak
     class="<?php echo htmlspecialchars($fetchLoaderClass, ENT_QUOTES, 'UTF-8'); ?> flex flex-col items-center justify-center text-center">
    <div class="relative w-14 h-14 flex items-center justify-center mb-4">
        <div class="absolute inset-0 bg-indigo-500/20 rounded-full blur-md animate-pulse"></div>
        <div class="nexus-fetch-orbit-outer absolute inset-0 border-[2px] border-white/5 border-t-indigo-500 border-r-indigo-500 rounded-full"></div>
        <div class="nexus-fetch-orbit-inner absolute inset-1.5 border-[2px] border-white/5 border-b-red-500 border-l-red-500 rounded-full"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-white shadow-[0_0_8px_#fff] rotate-45"></div>
    </div>
    <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-white/45 mono"><?php echo htmlspecialchars($fetchLoaderLabel, ENT_QUOTES, 'UTF-8'); ?></p>
</div>
