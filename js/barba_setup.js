// Barba.js Initialization
if (typeof barba !== 'undefined') { barba.init({
    prevent: ({ el }) => {
        if (el.hasAttribute('data-barba-prevent')) return true;
        if (el.getAttribute('href') && el.getAttribute('href').startsWith('#')) return true;
        if (el.href && el.href.includes('backend/')) return true;
        return false;
    },
    transitions: [{
        name: 'opacity-transition',
        beforeEnter(data) {
            data.next.container.setAttribute("x-ignore", "");
            // Using x-ignore instead of removing x-data
        },
        leave(data) {
            // Stop any playing videos in the old container
            const oldVideos = data.current.container.querySelectorAll('video');
            oldVideos.forEach(v => {
                v.pause();
                v.removeAttribute('src');
                v.load();
                        v.remove();
            });

            // Kill all ScrollTriggers before leaving
            if (typeof ScrollTrigger !== 'undefined') { ScrollTrigger.getAll().forEach(t => t.kill()); }
            
            // Reset custom cursor state
            const innerCursor = document.querySelector('.inner-cursor');
            if(innerCursor && typeof gsap !== 'undefined') {
                gsap.to(innerCursor, { scale: 1, backgroundColor: '#ef4444', border: 'none', duration: 0.2 });
            }
            
            return new Promise(resolve => {
                if (typeof window.showPageLoader === 'function') {
                    window.showPageLoader(resolve);
                } else {
                    gsap.to(data.current.container, {
                        opacity: 0,
                        duration: 0.3,
                        onComplete: resolve
                    });
                }
            });
        },
        enter(data) {
            // Start entering animation
            gsap.from(data.next.container, {
                opacity: 0,
                duration: 0.3
            });
            
            // Update body classes safely
            if (data.next.html) {
                const parser = new DOMParser();
                const htmlDoc = parser.parseFromString(data.next.html, 'text/html');
                if (htmlDoc.body.className) {
                    let newClass = htmlDoc.body.className;
                    newClass = newClass.replace(/is-loading/g, '').trim();
                    document.body.className = newClass;
                }
            }

            // Evaluate scripts in the new container safely
            let externalScriptsToLoad = 0;
            let initCalled = false;
            
            const checkAndInitAlpine = () => {
                if (externalScriptsToLoad > 0) return;
                if (initCalled) return;
                initCalled = true; console.log("checkAndInitAlpine running... typeof watchParty: " + typeof window.watchParty); if (data.next.container.hasAttribute("x-ignore")) { data.next.container.removeAttribute("x-ignore"); delete data.next.container._x_ignore; } if (typeof Alpine !== "undefined" && Alpine.initTree) { Alpine.initTree(data.next.container); }
                
                // Restore x-data AFTER all scripts (including external) have evaluated
                const tempElements = data.next.container.querySelectorAll('[data-x-data-temp]');
                tempElements.forEach(el => {
                    el.setAttribute('x-data', el.getAttribute('data-x-data-temp'));
                    el.removeAttribute('data-x-data-temp');
                });
                if (data.next.container.hasAttribute('data-x-data-temp')) {
                    data.next.container.setAttribute('x-data', data.next.container.getAttribute('data-x-data-temp'));
                    data.next.container.removeAttribute('data-x-data-temp');
                }
            };

            const scripts = data.next.container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                
                if (oldScript.src) {
                    externalScriptsToLoad++;
                    newScript.onload = () => {
                        externalScriptsToLoad--;
                        checkAndInitAlpine();
                    };
                    newScript.onerror = () => {
                        externalScriptsToLoad--;
                        checkAndInitAlpine();
                    };
                }
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
            
            // Fallback if no external scripts, or to trigger initial check
            setTimeout(checkAndInitAlpine, 10);

            // Re-bind HTMX
            if (typeof htmx !== 'undefined') {
                htmx.process(data.next.container);
            }
            
            // CRITICAL FIX: Re-bind cursor interactivity
            if (typeof window.initInteractiveElements === 'function') {
                window.initInteractiveElements();
            }
            
            // Re-initialize GSAP scoped to the new container
            if (typeof initAnimations === 'function') {
                initAnimations(data.next.container);
            }
            
            // Hide loader after enter
            if (typeof window.hidePageLoader === 'function') {
                window.hidePageLoader();
            }
        }
    }]
});
}
