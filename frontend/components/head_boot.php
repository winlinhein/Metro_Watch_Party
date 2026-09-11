<?php
// Shared boot: SPA navigation helper.
?>
<script>
(function () {
    window.nexusNavigate = function (url, opts) {
        if (!url) return;
        var hard = opts && opts.hard;
        var href = String(url);
        var isWatch = href.indexOf('watch_party.php') !== -1;
        var isBackend = href.indexOf('/backend/') !== -1 && href.indexOf('guest_login.php') === -1;
        if (!hard && !isWatch && !isBackend && window.barba && typeof barba.go === 'function') {
            if (barba.transitions && barba.transitions.isRunning) return;
            barba.go(href);
            return;
        }
        window.location.href = href;
    };
})();
</script>
