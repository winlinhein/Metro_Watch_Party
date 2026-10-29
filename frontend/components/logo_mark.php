<?php
// Shared brand mark ("Nexus Pulse"): play triangle with orbital sync arcs.
// Colors follow the active UI theme via --nx-glow / --nx-glow-2 / --nx-card-rgb.
$nexus_logo_id = 'nxlg' . substr(md5(uniqid('', true) . mt_rand()), 0, 8);
$nexus_logo_class = $nexus_logo_class ?? 'w-10 h-10';
?>
<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" aria-hidden="true"
     style="filter: drop-shadow(0 0 8px rgb(var(--nx-glow, 239 68 68) / 0.35));"
     class="<?= htmlspecialchars($nexus_logo_class, ENT_QUOTES) ?>">
    <defs>
        <linearGradient id="<?= $nexus_logo_id ?>g" x1="44" y1="44" x2="4" y2="4" gradientUnits="userSpaceOnUse">
            <stop offset="0" style="stop-color: rgb(var(--nx-glow-2, 79 70 229));"/>
            <stop offset="1" style="stop-color: rgb(var(--nx-glow, 239 68 68));"/>
        </linearGradient>
    </defs>
    <rect x="1.5" y="1.5" width="45" height="45" rx="12.5" style="fill: rgb(var(--nx-card-rgb, 10 10 15));"/>
    <rect x="1.5" y="1.5" width="45" height="45" rx="12.5" stroke="url(#<?= $nexus_logo_id ?>g)" stroke-width="3"/>
    <path d="M20 16.2 34 24 20 31.8Z" fill="url(#<?= $nexus_logo_id ?>g)"/>
    <path d="M13 35a15.5 15.5 0 0 1 0-22" stroke="#ffffff" stroke-opacity="0.9" stroke-width="3.5" stroke-linecap="round"/>
    <path d="M35 13a15.5 15.5 0 0 1 0 22" stroke="#ffffff" stroke-opacity="0.4" stroke-width="3.5" stroke-linecap="round"/>
</svg>
