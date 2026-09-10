<?php
// Force the OS pointer off. Do not use data-URI cursors — Chrome on Windows
// treats them as invalid and then ignores the entire `cursor` declaration.
?>
<style id="nexus-cursor-boot">
html, body, *, *::before, *::after {
    cursor: none !important;
}
#nexus-cursor-dot {
    position: fixed;
    top: 0;
    left: 0;
    width: 8px;
    height: 8px;
    margin: 0;
    padding: 0;
    background-color: #ef4444;
    border-radius: 50%;
    pointer-events: none;
    z-index: 2147483647;
    box-sizing: border-box;
    will-change: left, top, transform;
    margin-left: -4px;
    margin-top: -4px;
}
#nexus-cursor-dot.is-hidden { opacity: 0; }
</style>
<script>
(function () {
    document.documentElement.setAttribute('data-nexus-cursor', '');

    window.__nexusPointer = window.__nexusPointer || null;
    try {
        var saved = sessionStorage.getItem('nexusPointer');
        if (saved) window.__nexusPointer = JSON.parse(saved);
    } catch (e) {}

    function lockSheet() {
        var s = document.getElementById('nexus-cursor-lock');
        if (!s) {
            s = document.createElement('style');
            s.id = 'nexus-cursor-lock';
            s.textContent = 'html,body,*,*::before,*::after{cursor:none!important}';
        }
        if (s.parentNode !== document.documentElement || document.documentElement.lastChild !== s) {
            document.documentElement.appendChild(s);
        }
    }

    function hideNative(el) {
        if (!el || el.nodeType !== 1) return;
        if (el.id === 'nexus-cursor-dot' || el.id === 'cursor-glow' || el.id === 'nexus-cursor-lock') return;
        el.style.setProperty('cursor', 'none', 'important');
    }

    window.nexusLockNativeCursor = function (root) {
        document.documentElement.setAttribute('data-nexus-cursor', '');
        lockSheet();
        hideNative(document.documentElement);
        hideNative(document.body);
        var scope = root && root.querySelectorAll ? root : document;
        try {
            var nodes = scope.querySelectorAll('a, button, input, textarea, select, label, summary, iframe, video, canvas, [role="button"], .cursor-pointer, .cursor-default, .cursor-not-allowed, .home-magnetic');
            for (var i = 0; i < nodes.length; i++) hideNative(nodes[i]);
        } catch (err) {}
    };

    document.addEventListener('pointerover', function (e) { hideNative(e.target); }, true);
    document.addEventListener('mouseover', function (e) { hideNative(e.target); }, true);

    lockSheet();
    if (document.head) {
        new MutationObserver(lockSheet).observe(document.documentElement, { childList: true });
    }

    function ensureDot() {
        var dot = document.getElementById('nexus-cursor-dot');
        if (dot) return dot;
        dot = document.createElement('div');
        dot.id = 'nexus-cursor-dot';
        dot.className = 'inner-cursor';
        (document.body || document.documentElement).appendChild(dot);
        return dot;
    }

    function placeDot(x, y) {
        if (window.__nexusCursorGsapReady) return;
        var dot = ensureDot();
        if (typeof x !== 'number' || typeof y !== 'number') return;
        dot.style.left = x + 'px';
        dot.style.top = y + 'px';
        dot.classList.remove('is-hidden');
    }

    var boot = ensureDot();
    var p = window.__nexusPointer;
    if (p && typeof p.x === 'number') {
        boot.style.left = p.x + 'px';
        boot.style.top = p.y + 'px';
        boot.classList.remove('is-hidden');
    }

    document.addEventListener('mousemove', function (e) {
        window.__nexusPointer = { x: e.clientX, y: e.clientY };
        hideNative(e.target);
        placeDot(e.clientX, e.clientY);
        try {
            sessionStorage.setItem('nexusPointer', JSON.stringify(window.__nexusPointer));
        } catch (err) {}
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.nexusLockNativeCursor(); });
    } else {
        window.nexusLockNativeCursor();
    }

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
