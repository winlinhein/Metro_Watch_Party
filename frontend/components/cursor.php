<?php
// frontend/components/cursor.php
?>
<div id="cursor-glow"></div>
<style>
/* Advanced Cursor Follower */
body { cursor: none; }
a, button, input, textarea, select, .cursor-pointer, .top-nav-item, [x-ref="progressBar"], .gs-movie-card { cursor: none !important; }

#cursor-glow {
    position: fixed;
    top: 0;
    left: 0;
    width: 30vw;
    height: 30vw;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239,68,68,0.1) 0%, rgba(79,70,229,0.05) 30%, transparent 70%);
    pointer-events: none;
    z-index: 0;
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
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    box-sizing: border-box;
    transform: translate(-50%, -50%);
    transition: width 0.2s, height 0.2s, background-color 0.2s;
}
</style>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Prevent duplicate cursors
        if(document.querySelectorAll('#cursor-glow').length > 1) {
            document.querySelectorAll('#cursor-glow')[1].remove();
        }
        
        const cursor = document.getElementById('cursor-glow');
        let innerCursor = document.querySelector('.inner-cursor');
        if (!innerCursor) {
            innerCursor = document.createElement('div');
            innerCursor.classList.add('inner-cursor');
            document.body.appendChild(innerCursor);
        }

        if(typeof gsap !== 'undefined') {
            // Start invisible — we don't know the real cursor position yet, so
            // showing anything now would show it in the wrong place (top-left,
            // per the CSS default) until the first mousemove ever fires.
            gsap.set(cursor, { xPercent: -50, yPercent: -50, opacity: 0 });
            gsap.set(innerCursor, { xPercent: -50, yPercent: -50, opacity: 0 });

            let mouseX = window.innerWidth / 2;
            let mouseY = window.innerHeight / 2;
            let hasPositioned = false;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;

                if (!hasPositioned) {
                    // First real mouse position we've seen this page load:
                    // snap both cursors straight there (no tween) and reveal
                    // them in the same tick, so there's nothing to see travel
                    // in from the corner.
                    hasPositioned = true;
                    gsap.set(cursor, { x: mouseX, y: mouseY, opacity: 1 });
                    gsap.set(innerCursor, { x: mouseX, y: mouseY, opacity: 1 });
                } else {
                    gsap.set(innerCursor, {
                        x: mouseX,
                        y: mouseY
                    });
                }
            });

            gsap.ticker.add(() => {
                if (!hasPositioned) return;
                gsap.to(cursor, {
                    duration: 0.5,
                    x: mouseX,
                    y: mouseY,
                    ease: 'power2.out'
                });
            });

            window.initInteractiveElements = () => {
                const interactiveElements = document.querySelectorAll('button, a, input, textarea, select, .cursor-pointer, .top-nav-item, [x-ref="progressBar"], .gs-movie-card, .menu-item');
                interactiveElements.forEach(elem => {
                    if (!elem.hasAttribute('data-cursor-bound')) {
                        elem.setAttribute('data-cursor-bound', 'true');
                        elem.addEventListener('mouseenter', () => {
                            gsap.to(innerCursor, { scale: 4, backgroundColor: 'transparent', border: '1px solid rgba(239, 68, 68, 0.8)', duration: 0.2 });
                        });
                        elem.addEventListener('mouseleave', () => {
                            gsap.to(innerCursor, { scale: 1, backgroundColor: '#ef4444', border: 'none', duration: 0.2 });
                        });
                    }
                });
            };
            
            // Re-init interactive elements when new ones are added
            const observer = new MutationObserver((mutations) => {
                window.initInteractiveElements();
            });
            observer.observe(document.body, { childList: true, subtree: true });
            
            window.initInteractiveElements();
        }
    });
</script>
