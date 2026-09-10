<?php
// frontend/components/cursor.php
?>
<div id="cursor-glow"></div>
<style>
html, body, *, *::before, *::after,
a, button, input, textarea, select, label, summary,
iframe, video, canvas, [role="button"],
.cursor-pointer, .cursor-default, .cursor-not-allowed, .home-magnetic,
.top-nav-item, [x-ref="progressBar"], .gs-movie-card, .menu-item,
:fullscreen, :fullscreen *,
:-webkit-full-screen, :-webkit-full-screen *,
.plyr, .plyr *,
.plyr--fullscreen-active, .plyr--fullscreen-active * {
    cursor: none !important;
}

/* Fix for Plyr jumping to the top during load in aspect-video containers */
.aspect-video .plyr {
    height: 100%;
    width: 100%;
}

#cursor-glow {
    position: fixed;
    top: 0;
    left: 0;
    width: 30vw;
    height: 30vw;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239,68,68,0.1) 0%, rgba(79,70,229,0.05) 30%, transparent 70%);
    pointer-events: none;
    z-index: 999998;
    transform: translate(-50%, -50%);
    mix-blend-mode: screen;
}
.inner-cursor {
    position: fixed;
    top: 0;
    left: 0;
    width: 8px;
    height: 8px;
    background-color: #ef4444;
    border: 2.5px solid transparent;
    border-radius: 50%;
    pointer-events: none;
    z-index: 2147483647;
    box-sizing: border-box;
}
</style>


<script>
    const handleFullscreenChange = () => {
        const cursorGlow = document.getElementById('cursor-glow');
        const innerCursor = document.querySelector('.inner-cursor');
        const fsElement = document.fullscreenElement || document.webkitFullscreenElement;

        if (fsElement) {
            if (cursorGlow) fsElement.appendChild(cursorGlow);
            if (innerCursor) fsElement.appendChild(innerCursor);
        } else {
            if (cursorGlow) document.body.appendChild(cursorGlow);
            if (innerCursor) document.body.appendChild(innerCursor);
        }
    };
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);

    const bootNexusCursor = () => {
        if (window.__nexusCursorInit) return;
        window.__nexusCursorInit = true;

        if (document.documentElement.classList.contains('nexus-touch-cursor')) return;
        document.documentElement.setAttribute('data-nexus-cursor', '');
        if (typeof window.nexusLockNativeCursor === 'function') window.nexusLockNativeCursor();

        if(document.querySelectorAll('#cursor-glow').length > 1) {
            document.querySelectorAll('#cursor-glow')[1].remove();
        }

        const cursor = document.getElementById('cursor-glow');
        let innerCursor = document.getElementById('nexus-cursor-dot') || document.querySelector('.inner-cursor');
        if (!innerCursor) {
            innerCursor = document.createElement('div');
            innerCursor.id = 'nexus-cursor-dot';
            innerCursor.classList.add('inner-cursor');
            document.body.appendChild(innerCursor);
        } else {
            innerCursor.classList.add('inner-cursor');
        }

        const extraRing = document.getElementById('nexus-cursor-ring');
        if (extraRing) extraRing.remove();

        if(typeof gsap !== 'undefined') {
            const saved = window.__nexusPointer;
            let mouseX = saved && typeof saved.x === 'number' ? saved.x : window.innerWidth / 2;
            let mouseY = saved && typeof saved.y === 'number' ? saved.y : window.innerHeight / 2;
            const hasSaved = !!(saved && typeof saved.x === 'number');
            let hasPositioned = hasSaved;

            innerCursor.style.left = '0px';
            innerCursor.style.top = '0px';
            innerCursor.style.marginLeft = '0px';
            innerCursor.style.marginTop = '0px';
            innerCursor.classList.remove('is-hidden');
            window.__nexusCursorGsapReady = true;

            if (cursor) {
                gsap.set(cursor, { xPercent: -50, yPercent: -50, x: mouseX, y: mouseY, opacity: hasSaved ? 1 : 0 });
            }
            gsap.set(innerCursor, {
                xPercent: -50,
                yPercent: -50,
                x: mouseX,
                y: mouseY,
                opacity: 1,
                width: 8,
                height: 8,
                backgroundColor: '#ef4444',
                borderColor: 'transparent',
                borderWidth: 2.5
            });

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;

                if (!hasPositioned) {
                    hasPositioned = true;
                    if (cursor) gsap.set(cursor, { x: mouseX, y: mouseY, opacity: 1 });
                    gsap.set(innerCursor, { x: mouseX, y: mouseY, opacity: 1 });
                } else {
                    gsap.set(innerCursor, { x: mouseX, y: mouseY });
                }
            });

            gsap.ticker.add(() => {
                if (!hasPositioned || !cursor) return;
                gsap.to(cursor, {
                    duration: 0.5,
                    x: mouseX,
                    y: mouseY,
                    ease: 'power2.out',
                    overwrite: 'auto'
                });
            });

            window.initInteractiveElements = () => {
                if (typeof window.nexusLockNativeCursor === 'function') {
                    window.nexusLockNativeCursor();
                }
                const interactiveElements = document.querySelectorAll('button, a, input, textarea, select, label, [role="button"], .cursor-pointer, .top-nav-item, [x-ref="progressBar"], .gs-movie-card, .menu-item');
                interactiveElements.forEach(elem => {
                    if (!elem.hasAttribute('data-cursor-bound')) {
                        elem.setAttribute('data-cursor-bound', 'true');
                        elem.addEventListener('mouseenter', () => {
                            gsap.to(innerCursor, {
                                width: 32,
                                height: 32,
                                backgroundColor: 'transparent',
                                borderColor: 'rgba(239, 68, 68, 0.8)',
                                borderWidth: 2.5,
                                duration: 0.2,
                                overwrite: 'auto'
                            });
                        });
                        elem.addEventListener('mouseleave', () => {
                            gsap.to(innerCursor, {
                                width: 8,
                                height: 8,
                                backgroundColor: '#ef4444',
                                borderColor: 'transparent',
                                duration: 0.2,
                                overwrite: 'auto'
                            });
                        });
                    }
                });
            };

            const observer = new MutationObserver(() => {
                window.initInteractiveElements();
            });
            observer.observe(document.body, { childList: true, subtree: true });

            window.initInteractiveElements();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootNexusCursor);
    } else {
        bootNexusCursor();
    }
</script>
