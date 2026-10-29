<?php
// Shared brand mark ("Nexus Pulse"): play triangle with orbital sync arcs.
$nexus_logo_id = 'nxlg' . substr(md5(uniqid('', true) . mt_rand()), 0, 8);
$nexus_logo_class = $nexus_logo_class ?? 'w-10 h-10';
?>
<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="<?= htmlspecialchars($nexus_logo_class, ENT_QUOTES) ?>">
    <defs>
        <linearGradient id="<?= $nexus_logo_id ?>g" x1="44" y1="44" x2="4" y2="4" gradientUnits="userSpaceOnUse">
            <stop stop-color="#4f46e5"/>
            <stop offset="1" stop-color="#ef4444"/>
        </linearGradient>
    </defs>
    <rect x="1" y="1" width="46" height="46" rx="13" fill="#0a0a12"/>
    <rect x="1" y="1" width="46" height="46" rx="13" stroke="url(#<?= $nexus_logo_id ?>g)" stroke-width="2"/>
    <path d="M20.5 16.8 33 24 20.5 31.2Z" fill="url(#<?= $nexus_logo_id ?>g)"/>
    <path d="M11.8 39.4a21.5 21.5 0 0 1 0-30.8" stroke="#ffffff" stroke-opacity="0.85" stroke-width="2.6" stroke-linecap="round"/>
    <path d="M36.2 8.6a21.5 21.5 0 0 1 0 30.8" stroke="#ffffff" stroke-opacity="0.35" stroke-width="2.6" stroke-linecap="round"/>
</svg>
