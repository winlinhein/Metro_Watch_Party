function nexusHardNavigateUrl(url) {
    try {
        const path = new URL(String(url || ''), window.location.href).pathname.replace(/\/+$/, '') || '/';
        return (
            path === '/'
            || path === '/index.php'
            || path.endsWith('/index.php')
            || path.endsWith('/dashboard.php')
            || path.endsWith('/admin_dashboard.php')
            || path.endsWith('/watch_party.php')
            || path.endsWith('/login.php')
            || path.endsWith('/register.php')
            || path.endsWith('/otp-login.php')
            || path.endsWith('/otp-register.php')
            || path.endsWith('/otp-forgot.php')
            || path.endsWith('/forgot-password.php')
            || path.includes('/backend/')
        );
    } catch (e) {
        return false;
    }
}

// Barba.js Initialization
(function initNexusBarba() {
    if (typeof barba === 'undefined') {
        console.warn('[Nexus] Barba.js did not load; clicks will do a full page reload.');
        return;
    }
    if (window.__nexusBarbaInit) return;
    window.__nexusBarbaInit = true;

    function mergeHeadFromNextPage(html) {
        const parser = new DOMParser();
        const htmlDoc = parser.parseFromString(html, 'text/html');

        if (htmlDoc.title) {
            document.title = htmlDoc.title;
        }

        if (htmlDoc.body && htmlDoc.body.className) {
            document.body.className = htmlDoc.body.className.replace(/is-loading/g, '').trim();
        }

        // Keep existing <style> tags (Tailwind CDN + cursor boot). Only add new ones.
        const existingStyleText = new Set(
            Array.from(document.head.querySelectorAll('style')).map((s) => s.textContent)
        );
        htmlDoc.head.querySelectorAll('style').forEach((newStyle) => {
            if (newStyle.id === 'nexus-cursor-boot') return;
            if (existingStyleText.has(newStyle.textContent)) return;
            const style = document.createElement('style');
            if (newStyle.id) style.id = newStyle.id;
            style.textContent = newStyle.textContent;
            document.head.appendChild(style);
            existingStyleText.add(newStyle.textContent);
        });

        const currentLinks = new Set(
            Array.from(document.head.querySelectorAll('link[rel="stylesheet"]')).map((l) => l.href)
        );
        htmlDoc.head.querySelectorAll('link[rel="stylesheet"]').forEach((newLink) => {
            if (!newLink.href || currentLinks.has(newLink.href)) return;
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = newLink.href;
            document.head.appendChild(link);
            currentLinks.add(newLink.href);
        });

        // Do not re-inject the same JS file with a different cache-buster.
        const scriptKey = (src) => {
            try { return new URL(src, window.location.origin).pathname; } catch (e) { return src; }
        };
        const currentScripts = new Set(
            Array.from(document.querySelectorAll('script[src]')).map((s) => scriptKey(s.src))
        );
        htmlDoc.querySelectorAll('script[src]').forEach((newScript) => {
            if (!newScript.src) return;
            const key = scriptKey(newScript.src);
            if (!key || currentScripts.has(key)) return;
            if (/barba(\.umd)?\.js/i.test(key) || /barba_setup\.js/i.test(key) || /nexus_scripts\.js/i.test(key)) return;
            const script = document.createElement('script');
            script.src = newScript.src;
            script.type = newScript.type || 'text/javascript';
            document.body.appendChild(script);
            currentScripts.add(key);
        });
    }

    barba.init({
        // Render (especially after idle spin-down) can take well over Barba's 2s default.
        timeout: 30000,
        // Prefetch on hover stamps PHP session locks and races the click request.
        prefetchIgnore: true,
        cacheIgnore: false,
        // A second click while a slow fetch is in-flight would otherwise force a hard reload.
        preventRunning: true,
        prevent: ({ el, href }) => {
            if (el && el.hasAttribute('data-barba-prevent')) return true;
            if (el && el.getAttribute('href') && el.getAttribute('href').startsWith('#')) return true;
            if (el && el.href && el.href.includes('/backend/')) return true;
            const url = href || (el && el.href) || '';
            if (String(url).includes('watch_party.php')) return true;
            return nexusHardNavigateUrl(url);
        },
        views: [{
            namespace: 'index',
            afterEnter({ next }) {
                if (typeof window.initHomePage === 'function') {
                    requestAnimationFrame(() => window.initHomePage(next.container));
                }
            },
            beforeLeave() {
                if (typeof window.destroyHomePage === 'function') {
                    window.destroyHomePage();
                }
            }
        }],
        transitions: [{
            name: 'opacity-transition',
            leave(data) {
                const oldVideos = data.current.container.querySelectorAll('video');
                oldVideos.forEach((v) => {
                    v.pause();
                    v.removeAttribute('src');
                    v.load();
                    v.remove();
                });

                if (typeof window.destroyHomePage === 'function') {
                    window.destroyHomePage();
                }

                if (typeof ScrollTrigger !== 'undefined') {
                    ScrollTrigger.getAll().forEach((t) => t.kill());
                }

                const innerCursor = document.querySelector('.inner-cursor');
                if (innerCursor && typeof gsap !== 'undefined') {
                    gsap.to(innerCursor, { scale: 1, backgroundColor: '#ef4444', border: 'none', duration: 0.2 });
                }

                return new Promise((resolve) => {
                    if (typeof window.showPageLoader === 'function') {
                        window.showPageLoader(resolve);
                    } else if (typeof gsap !== 'undefined') {
                        gsap.to(data.current.container, {
                            opacity: 0,
                            duration: 0.3,
                            onComplete: resolve
                        });
                    } else {
                        resolve();
                    }
                });
            },
            enter(data) {
                if (typeof gsap !== 'undefined') {
                    if (data.next.namespace === 'index') {
                        gsap.from(data.next.container, {
                            opacity: 0,
                            y: 20,
                            duration: 0.55,
                            ease: 'power3.out'
                        });
                    } else {
                        gsap.from(data.next.container, {
                            opacity: 0,
                            duration: 0.3
                        });
                    }
                }

                if (data.next.html) {
                    mergeHeadFromNextPage(data.next.html);
                }

                if (typeof htmx !== 'undefined') {
                    htmx.process(data.next.container);
                }

                if (typeof window.initInteractiveElements === 'function') {
                    window.initInteractiveElements();
                }

                const ns = data.next && data.next.namespace;
                if (ns !== 'index' && typeof initAnimations === 'function') {
                    initAnimations(data.next.container);
                }

                if (ns !== 'index' && typeof initLocalAnimations === 'function') {
                    initLocalAnimations(data.next.container);
                }

                if (data.next.namespace === 'index' && typeof window.initHomePage === 'function') {
                    requestAnimationFrame(() => window.initHomePage(data.next.container));
                }

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

                if (typeof window.hidePageLoader === 'function') {
                    window.hidePageLoader();
                }
            }
        }]
    });

    barba.hooks.before((data) => {
        const nextUrl = data?.next?.url;
        const href = typeof nextUrl === 'string' ? nextUrl : (nextUrl?.href || '');
        if (href.includes('watch_party.php')) {
            window.location.assign(href);
            throw new Error('Hard navigation to watch party');
        }
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    const ns = document.querySelector('[data-barba-namespace]')?.getAttribute('data-barba-namespace');
    if (ns === 'index') {
        if (typeof gsap !== 'undefined') gsap.config({ nullTargetWarn: false });
        return;
    }
    if (typeof initAnimations === 'function') {
        initAnimations(document);
    }
    if (typeof initLocalAnimations === 'function') {
        initLocalAnimations(document);
    }
});
if (typeof gsap !== 'undefined') gsap.config({ nullTargetWarn: false });

document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (form && form.tagName === 'FORM') {
        if (e.defaultPrevented) return;
        if (form.hasAttribute('data-barba-prevent')) return;
        if (typeof barba === 'undefined' || !barba.go) return;
        const action = form.getAttribute('action') || window.location.href;
        if (nexusHardNavigateUrl(action)) return;

        e.preventDefault();
        const formData = new FormData(form);
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
            if (nexusHardNavigateUrl(finalUrl)) {
                window.location.assign(finalUrl);
                return;
            }

            barba.go(finalUrl);
        } catch (err) {
            console.error('Form submission error:', err);
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
        }
    }
});

document.addEventListener('click', async (e) => {
    const link = e.target.closest('a');
    if (link && link.href && link.href.includes('/backend/')) {
        if (e.defaultPrevented) return;
        if (link.hasAttribute('data-barba-prevent')) return;
        if (nexusHardNavigateUrl(link.href)) return;
        if (typeof barba === 'undefined' || !barba.go) return;

        e.preventDefault();

        if (typeof window.showPageLoader === 'function') window.showPageLoader();

        try {
            const response = await fetch(link.href, { redirect: 'follow', credentials: 'same-origin' });
            if (nexusHardNavigateUrl(response.url)) {
                window.location.assign(response.url);
                return;
            }
            barba.go(response.url);
        } catch (err) {
            console.error('Link fetch error:', err);
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
        }
    }
});
