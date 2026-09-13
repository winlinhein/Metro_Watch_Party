function nexusHardNavigateUrl(url) {
    try {
        const path = new URL(String(url || ''), window.location.href).pathname.replace(/\/+$/, '') || '/';
        if (path.endsWith('/guest_login.php')) return false;
        return (
            path.endsWith('/watch_party.php')
            || path.endsWith('/dashboard.php')
            || path.endsWith('/admin_dashboard.php')
            || path.includes('/backend/')
            || path.includes('/user_backend/')
        );
    } catch (e) {
        return false;
    }
}

function nexusScriptKey(src) {
    try { return new URL(src, window.location.origin).pathname; } catch (e) { return src; }
}

function nexusLoadScript(src, type) {
    return new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = src;
        if (type) script.type = type;
        script.onload = () => resolve();
        script.onerror = () => resolve();
        document.body.appendChild(script);
    });
}

function nexusApplyBootGlobals(html) {
    const parser = new DOMParser();
    const htmlDoc = parser.parseFromString(html, 'text/html');
    htmlDoc.querySelectorAll('script:not([src])').forEach((s) => {
        const text = s.textContent || '';
        if (!/NEXUS_USER|CURRENT_USER_ID/.test(text)) return;
        const run = document.createElement('script');
        run.textContent = text;
        document.head.appendChild(run);
        run.remove();
    });
}

function nexusRunContainerScripts(container) {
    if (!container) return;
    container.querySelectorAll('script').forEach((old) => {
        if (old.src) return;
        const script = document.createElement('script');
        script.textContent = old.textContent;
        old.replaceWith(script);
    });
}

function nexusPatchAlpineEvaluate() {
    if (typeof window.Alpine === 'undefined' || Alpine.__nexusSafeEval) return;
    Alpine.__nexusSafeEval = true;

    const originalEvaluate = Alpine.evaluate;
    if (typeof originalEvaluate === 'function') {
        Alpine.evaluate = function (el, expression, extras) {
            try {
                return originalEvaluate.call(this, el, expression, extras);
            } catch (err) {
                if (err && err.name === 'ReferenceError') return undefined;
                throw err;
            }
        };
    }

    const originalLater = Alpine.evaluateLater;
    if (typeof originalLater === 'function') {
        Alpine.evaluateLater = function (el, expression) {
            const run = originalLater.call(this, el, expression);
            return function (receiver, extras) {
                try {
                    run(receiver, extras);
                } catch (err) {
                    if (err && err.name === 'ReferenceError') {
                        if (typeof receiver === 'function') receiver(undefined);
                        return;
                    }
                    throw err;
                }
            };
        };
    }
}

function nexusStopAlpineNode(el) {
    if (!el) return;
    try {
        if (el._x_effects) {
            Array.from(el._x_effects).forEach((effect) => {
                try {
                    if (typeof effect.stop === 'function') effect.stop();
                } catch (e) {}
            });
            try { el._x_effects.clear(); } catch (e) {}
        }
    } catch (e) {}
    try {
        if (Array.isArray(el._x_cleanups)) el._x_cleanups.length = 0;
    } catch (e) {}
}

function nexusInitAlpine(container) {
    nexusPatchAlpineEvaluate();
    if (!container || typeof window.Alpine === 'undefined') return;
    if (container.dataset && container.dataset.nexusUser && !window.NEXUS_USER) {
        try { window.NEXUS_USER = JSON.parse(container.dataset.nexusUser); } catch (e) {}
    }
    try {
        if (typeof Alpine.destroyTree === 'function' && container._x_dataStack) {
            Alpine.destroyTree(container);
        }
    } catch (e) {}
    try {
        Alpine.initTree(container);
    } catch (e) {}
}

function nexusDestroyAlpine(container) {
    if (!container) return;
    const walk = (node) => {
        nexusStopAlpineNode(node);
        if (node.children) Array.from(node.children).forEach(walk);
    };
    try { walk(container); } catch (e) {}
    try { container.setAttribute('x-ignore', ''); } catch (e) {}
    if (typeof window.Alpine === 'undefined' || typeof Alpine.destroyTree !== 'function') return;
    try { Alpine.destroyTree(container); } catch (e) {}
}

const NEXUS_AUTH_NAMESPACES = {
    login: true,
    register: true,
    'otp-login': true,
    'otp-register': true,
    'otp-forgot': true,
    'forgot-password': true
};

function nexusClearInlineScrollLocks(el) {
    if (!el || !el.style) return;
    [
        'overflow', 'overflow-x', 'overflow-y', 'height', 'max-height',
        'position', 'top', 'left', 'right', 'bottom', 'width',
        'touch-action', 'perspective', 'min-height'
    ].forEach((prop) => el.style.removeProperty(prop));
}

function nexusRemoveIndexScrollReset() {
    document.documentElement.classList.remove('nexus-home');
    const reset = document.getElementById('nexus-index-scroll-reset');
    if (reset) reset.remove();
}

function nexusRestoreIndexScroll() {
    let reset = document.getElementById('nexus-index-scroll-reset');
    if (!reset) {
        reset = document.createElement('style');
        reset.id = 'nexus-index-scroll-reset';
        document.head.appendChild(reset);
    }
    // Scoped to html.nexus-home so this cannot collapse app shells like the dashboard.
    reset.textContent = [
        'html.nexus-home { overflow-x: hidden !important; overflow-y: auto !important; height: auto !important; }',
        'html.nexus-home body { overflow-x: hidden !important; overflow-y: visible !important; height: auto !important; min-height: 0 !important; perspective: none !important; position: static !important; }',
        'html.nexus-home body.is-loading { overflow: hidden !important; }'
    ].join('');

    document.documentElement.className = 'scroll-smooth nexus-home';
    document.body.className = 'overflow-x-hidden';
    nexusClearInlineScrollLocks(document.documentElement);
    nexusClearInlineScrollLocks(document.body);
    document.body.classList.remove('is-loading');
}

function nexusUnlockPageScroll(htmlDoc, namespace) {
    const ns = String(namespace || (htmlDoc && htmlDoc.querySelector && htmlDoc.querySelector('[data-barba-namespace]')?.getAttribute('data-barba-namespace')) || '');

    if (ns === 'index') {
        nexusRestoreIndexScroll();
        return;
    }

    nexusRemoveIndexScrollReset();

    const nextHtmlClass = htmlDoc && htmlDoc.documentElement
        ? String(htmlDoc.documentElement.className || '')
        : '';
    let nextBodyClass = htmlDoc && htmlDoc.body
        ? String(htmlDoc.body.className || '').replace(/\bis-loading\b/g, '').trim()
        : '';

    if (NEXUS_AUTH_NAMESPACES[ns]) {
        nextBodyClass = nextBodyClass.replace(/\boverflow-hidden\b/g, '').replace(/\s+/g, ' ').trim();
    }

    document.documentElement.className = nextHtmlClass;
    document.body.className = nextBodyClass;
    nexusClearInlineScrollLocks(document.documentElement);
    nexusClearInlineScrollLocks(document.body);
    document.body.classList.remove('is-loading');
}

async function mergeHeadFromNextPage(html) {
    const parser = new DOMParser();
    const htmlDoc = parser.parseFromString(html, 'text/html');
    const pendingScripts = [];

    if (htmlDoc.title) {
        document.title = htmlDoc.title;
    }

    const ns = htmlDoc && htmlDoc.querySelector
        ? (htmlDoc.querySelector('[data-barba-namespace]')?.getAttribute('data-barba-namespace') || '')
        : '';
    nexusUnlockPageScroll(htmlDoc, ns);

    const existingStyleText = new Set(
        Array.from(document.head.querySelectorAll('style')).map((s) => s.textContent)
    );
    htmlDoc.head.querySelectorAll('style').forEach((newStyle) => {
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

    const currentScripts = new Set(
        Array.from(document.querySelectorAll('script[src]')).map((s) => nexusScriptKey(s.src))
    );
    htmlDoc.querySelectorAll('script[src]').forEach((newScript) => {
        if (!newScript.src) return;
        const key = nexusScriptKey(newScript.src);
        if (!key || currentScripts.has(key)) return;
        if (/barba(\.umd)?\.js/i.test(key) || /barba_setup\.js/i.test(key)) return;
        pendingScripts.push(nexusLoadScript(newScript.src, newScript.type || 'text/javascript'));
        currentScripts.add(key);
    });

    nexusApplyBootGlobals(html);
    if (pendingScripts.length) {
        await Promise.all(pendingScripts);
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
    nexusPatchAlpineEvaluate();

    barba.init({
        timeout: 30000,
        prefetchIgnore: true,
        cacheIgnore: false,
        preventRunning: true,
        prevent: ({ el, href }) => {
            if (el && el.hasAttribute('data-barba-prevent')) return true;
            if (el && el.getAttribute('href') && el.getAttribute('href').startsWith('#')) return true;
            if (el && el.href && el.href.includes('/backend/') && el.href.indexOf('guest_login.php') === -1) return true;
            const url = href || (el && el.href) || '';
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
                if (window.NexusAdminMotion && typeof window.NexusAdminMotion.destroy === 'function') {
                    window.NexusAdminMotion.destroy();
                }
            }
        }],
        transitions: [{
            name: 'opacity-transition',
            leave(data) {
                nexusDestroyAlpine(data.current.container);

                try {
                    const oldVideos = data.current.container.querySelectorAll('video');
                    oldVideos.forEach((v) => {
                        v.pause();
                        v.removeAttribute('src');
                        v.load();
                        v.remove();
                    });
                } catch (e) {}

                if (typeof window.destroyHomePage === 'function') {
                    window.destroyHomePage();
                }
                if (window.NexusAdminMotion && typeof window.NexusAdminMotion.destroy === 'function') {
                    window.NexusAdminMotion.destroy();
                }

                if (typeof ScrollTrigger !== 'undefined') {
                    ScrollTrigger.getAll().forEach((t) => t.kill());
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
            async enter(data) {
                try {
                    const nextNs = data.next && data.next.namespace;
                    if (data.next && data.next.html) {
                        const preview = new DOMParser().parseFromString(data.next.html, 'text/html');
                        nexusUnlockPageScroll(preview, nextNs);
                    } else {
                        nexusUnlockPageScroll(null, nextNs);
                    }

                    if (typeof gsap !== 'undefined') {
                        gsap.fromTo(data.next.container, { opacity: 0 }, {
                            opacity: 1,
                            duration: nextNs === 'index' ? 0.55 : 0.3,
                            ease: 'power3.out',
                            clearProps: 'transform,opacity'
                        });
                    }

                    if (data.next.html) {
                        await mergeHeadFromNextPage(data.next.html);
                    }

                    nexusRunContainerScripts(data.next.container);
                    nexusInitAlpine(data.next.container);

                    if (typeof htmx !== 'undefined') {
                        htmx.process(data.next.container);
                    }

                    const ns = data.next && data.next.namespace;
                    if (ns !== 'index' && typeof initAnimations === 'function') {
                        initAnimations(data.next.container);
                    }

                    if (ns !== 'index' && typeof initLocalAnimations === 'function') {
                        initLocalAnimations(data.next.container);
                    }

                    if (nextNs === 'index') {
                        nexusRestoreIndexScroll();
                        if (typeof window.initHomePage === 'function') {
                            requestAnimationFrame(() => {
                                nexusRestoreIndexScroll();
                                window.initHomePage(data.next.container);
                                if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
                                    ScrollTrigger.refresh();
                                }
                            });
                        }
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
                } catch (err) {
                    console.error('[Nexus] Barba enter error:', err);
                }

                if (typeof window.hidePageLoader === 'function') {
                    window.hidePageLoader(() => {
                        if (data.next && data.next.namespace === 'index') {
                            nexusRestoreIndexScroll();
                            if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
                                ScrollTrigger.refresh();
                            }
                        }
                    });
                } else if (data.next && data.next.namespace === 'index') {
                    nexusRestoreIndexScroll();
                }
            }
        }]
    });

    barba.hooks.after((data) => {
        if (data?.next?.namespace === 'index') {
            nexusRestoreIndexScroll();
            window.scrollTo(0, 0);
            requestAnimationFrame(() => {
                nexusRestoreIndexScroll();
                if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
                    ScrollTrigger.refresh();
                }
            });
            return;
        }
        nexusRemoveIndexScrollReset();
    });

    barba.hooks.before((data) => {
        const nextUrl = data?.next?.url;
        const href = typeof nextUrl === 'string' ? nextUrl : (nextUrl?.href || '');
        if (nexusHardNavigateUrl(href)) {
            window.location.assign(href);
            throw new Error('Hard navigation');
        }
    });
})();

document.addEventListener('alpine:init', nexusPatchAlpineEvaluate);
nexusPatchAlpineEvaluate();

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

            if (barba.transitions && barba.transitions.isRunning) return;
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
            if (barba.transitions && barba.transitions.isRunning) return;
            barba.go(response.url);
        } catch (err) {
            console.error('Link fetch error:', err);
            if (typeof window.hidePageLoader === 'function') window.hidePageLoader();
        }
    }
});
