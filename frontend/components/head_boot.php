<?php
// Hide the OS cursor on first paint so a full reload never flashes the default pointer.
?>
<style id="nexus-cursor-boot">
html, body, a, button, input, textarea, select, label,
[role="button"], .cursor-pointer, .top-nav-item, .gs-movie-card, .menu-item {
    cursor: none !important;
}
</style>
<script>
(function () {
    window.__nexusPointer = window.__nexusPointer || null;
    try {
        var saved = sessionStorage.getItem('nexusPointer');
        if (saved) window.__nexusPointer = JSON.parse(saved);
    } catch (e) {}
    document.addEventListener('mousemove', function (e) {
        window.__nexusPointer = { x: e.clientX, y: e.clientY };
        try {
            sessionStorage.setItem('nexusPointer', JSON.stringify(window.__nexusPointer));
        } catch (err) {}
    }, { passive: true });

    window.nexusNavigate = function (url, opts) {
        if (!url) return;
        var hard = opts && opts.hard;
        var href = String(url);
        if (!hard && href.indexOf('watch_party.php') === -1 && window.barba && typeof barba.go === 'function') {
            barba.go(href);
            return;
        }
        window.location.href = href;
    };
})();
</script>
