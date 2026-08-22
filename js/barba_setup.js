// Barba.js Initialization
if (typeof barba !== 'undefined') {
    barba.init({
        prevent: ({ el }) => {
            if (el.hasAttribute('data-barba-prevent')) return true;
            if (el.getAttribute('href') && el.getAttribute('href').startsWith('#')) return true;
            if (el.href && el.href.includes('backend/')) return true;
            return false;
        },
        transitions: [{
            name: 'opacity-transition',
            leave(data) {
                // Stop any playing videos in the old container
                const oldVideos = data.current.container.querySelectorAll('video');
                oldVideos.forEach(v => {
                    v.pause();
                    v.removeAttribute('src');
                    v.load();
                    v.remove();
                });

                // Kill all ScrollTriggers before leaving to prevent memory leaks and conflicts
                if (typeof ScrollTrigger !== 'undefined') {
                    ScrollTrigger.getAll().forEach(t => t.kill());
                }
                
                // Reset custom cursor state if present
                const innerCursor = document.querySelector('.inner-cursor');
                if (innerCursor && typeof gsap !== 'undefined') {
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
                // Alpine v3 automatically detects new elements via MutationObserver when data.next.container is inserted.
                // We do not manually call Alpine.start() or Alpine.initTree() to avoid duplicate errors.

                // Start entering animation
                gsap.from(data.next.container, {
                    opacity: 0,
                    duration: 0.3
                });
                
                // Update body classes safely
                if (data.next.html) {
                    const parser = new DOMParser();
                    const htmlDoc = parser.parseFromString(data.next.html, 'text/html');
                    
                    if (htmlDoc.title) {
                        document.title = htmlDoc.title;
                    }

                    if (htmlDoc.body.className) {
                        let newClass = htmlDoc.body.className;
                        newClass = newClass.replace(/is-loading/g, '').trim();
                        document.body.className = newClass;
                    }

                    // Swap inline styles
                    const oldStyles = document.head.querySelectorAll('style');
                    oldStyles.forEach(s => s.remove());
                    htmlDoc.head.querySelectorAll('style').forEach(newStyle => {
                        const style = document.createElement('style');
                        style.innerHTML = newStyle.innerHTML;
                        document.head.appendChild(style);
                    });

                    // Swap external stylesheets
                    const newLinkHrefs = Array.from(htmlDoc.head.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href);
                    document.head.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
                        if (!newLinkHrefs.includes(link.href)) {
                            link.remove();
                        }
                    });
                    
                    const currentLinks = Array.from(document.head.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href);
                    htmlDoc.head.querySelectorAll('link[rel="stylesheet"]').forEach(newLink => {
                        if (newLink.href && !currentLinks.includes(newLink.href)) {
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = newLink.href;
                            document.head.appendChild(link);
                        }
                    });

                    // Swap external scripts dynamically
                    const currentScripts = Array.from(document.querySelectorAll('script')).map(s => s.src).filter(Boolean);
                    htmlDoc.querySelectorAll('script').forEach(newScript => {
                        if (newScript.src && !currentScripts.includes(newScript.src)) {
                            const script = document.createElement('script');
                            script.src = newScript.src;
                            script.type = newScript.type || 'text/javascript';
                            document.body.appendChild(script);
                        }
                    });
                }

                // Re-bind HTMX strictly as requested
                if (typeof htmx !== 'undefined') {
                    htmx.process(data.next.container);
                }
                
                // Re-bind cursor interactivity
                if (typeof window.initInteractiveElements === 'function') {
                    window.initInteractiveElements();
                }
                
                // Re-initialize GSAP scoped strictly to the new container
                if (typeof initAnimations === 'function') {
                    initAnimations(data.next.container);
                }
                
                if (typeof initLocalAnimations === 'function') {
                    initLocalAnimations(data.next.container);
                }

                // Check for URL parameters (error/success messages) after Barba transition
                setTimeout(() => {
                    const urlParams = new URLSearchParams(window.location.search);
                    const phpError = urlParams.get('error');
                    const phpSuccess = urlParams.get('success');
                    if (phpError && typeof window.showToast === 'function') {
                        window.showToast(decodeURIComponent(phpError), 'error');
                        window.history.replaceState({}, document.title, window.location.pathname);
                    } else if (phpSuccess && typeof window.showToast === 'function') {
                        window.showToast(decodeURIComponent(phpSuccess), 'success');
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                }, 100);
                
                // Hide loader after enter
                if (typeof window.hidePageLoader === 'function') {
                    window.hidePageLoader();
                }
            }
        }]
    });
}


document.addEventListener('DOMContentLoaded', () => {
    if (typeof initAnimations === 'function') {
        initAnimations(document);
    }
    if (typeof initLocalAnimations === 'function') {
        initLocalAnimations(document);
    }
});
if(typeof gsap !== 'undefined') gsap.config({nullTargetWarn: false});

// Global Form Submission Interceptor for Barba.js
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (form && form.tagName === 'FORM') {
        if (e.defaultPrevented) return;
        if (form.hasAttribute('data-barba-prevent')) return;
        if (typeof barba === 'undefined' || !barba.go) return;
        
        e.preventDefault();
        const formData = new FormData(form);
        const action = form.getAttribute('action') || window.location.href;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        
        if (typeof window.showPageLoader === 'function') window.showPageLoader();
        
        try {
            let fetchOpts = { method, redirect: 'follow', credentials: 'same-origin' };
            let finalAction = action;
            if (method === 'POST') {
                fetchOpts.body = formData;
            } else {
                const params = new URLSearchParams(formData).toString();
                finalAction += (finalAction.includes('?') ? '&' : '?') + params;
            }
            
            const response = await fetch(finalAction, fetchOpts);
            const finalUrl = response.url;
            
            // Re-hide loader is handled by Barba's enter hook
            barba.go(finalUrl);
        } catch(err) {
            console.error('Form submission error:', err);
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
        }
    }
});


// Global Link Interceptor for backend/ links
document.addEventListener('click', async (e) => {
    const link = e.target.closest('a');
    if (link && link.href && link.href.includes('backend/')) {
        if (e.defaultPrevented) return;
        if (link.hasAttribute('data-barba-prevent')) return;
        if (typeof barba === 'undefined' || !barba.go) return;
        
        e.preventDefault();
        
        if (typeof window.showPageLoader === 'function') window.showPageLoader();
        
        try {
            const response = await fetch(link.href, { redirect: 'follow', credentials: 'same-origin' });
            barba.go(response.url);
        } catch(err) {
            console.error('Link fetch error:', err);
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
        }
    }
});
