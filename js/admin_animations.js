(function () {
    'use strict';

    let adminCtx = null;
    let tabGen = 0;
    let tabTween = null;
    let reduceMotion = false;
    let bootedRoot = null;
    let introPlayed = false;

    const STAGGER_SEL = '.gs-stat-card, .gs-table-row, .stagger-item, tbody tr, .card, .glass-card, .movie-card-container';
    const TAB_KEYS = {
        movies: 'movies',
        users: 'users',
        rooms: 'sessions',
        shop: 'shop',
        reports: 'reports',
        transactions: 'transactions'
    };

    function gsapReady() {
        return typeof gsap !== 'undefined' && typeof gsap.timeline === 'function';
    }

    function registerPlugins() {
        if (!gsapReady()) return;
        const plugins = [];
        if (typeof ScrollTrigger !== 'undefined') plugins.push(ScrollTrigger);
        if (typeof SplitText !== 'undefined') plugins.push(SplitText);
        if (typeof ScrambleTextPlugin !== 'undefined') plugins.push(ScrambleTextPlugin);
        if (typeof CustomEase !== 'undefined') plugins.push(CustomEase);
        if (plugins.length) gsap.registerPlugin.apply(gsap, plugins);
        if (typeof CustomEase !== 'undefined') {
            try { CustomEase.create('nexusOut', 'M0,0 C0.16,1 0.3,1 1,1'); } catch (e) {}
        }
    }

    function easeOut() {
        return typeof CustomEase !== 'undefined' ? 'nexusOut' : 'expo.out';
    }

    function prefersReduced() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function breatheActiveIcon(root) {
        if (!gsapReady() || !root) return;
        qa(root, '.nav-item .icon').forEach((icon) => gsap.killTweensOf(icon, 'y'));
        const icon = q(root, '.nav-item.active .icon');
        if (!icon || reduceMotion) return;
        gsap.set(icon, { y: 0 });
        gsap.to(icon, {
            y: -2,
            duration: 1.6,
            yoyo: true,
            repeat: -1,
            ease: 'sine.inOut'
        });
    }

    function whenNavReady(root, callback) {
        let tries = 0;
        const tick = () => {
            if (qa(root, '.gs-nav-item').length || tries > 24) {
                callback();
                return;
            }
            tries += 1;
            setTimeout(tick, 50);
        };
        tick();
    }

    function q(root, sel) {
        return root && root.querySelector ? root.querySelector(sel) : null;
    }

    function qa(root, sel) {
        return root && root.querySelectorAll ? Array.from(root.querySelectorAll(sel)) : [];
    }

    function visibleItems(panel, limit) {
        const items = qa(panel, STAGGER_SEL).filter((el) => {
            if (!el || el.closest('[style*="display: none"]')) return false;
            const style = window.getComputedStyle(el);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });
        return items.slice(0, limit || 18);
    }

    function killTabTweens(targets) {
        if (!gsapReady()) return;
        gsap.killTweensOf(targets);
        if (tabTween) {
            tabTween.kill();
            tabTween = null;
        }
    }

    function syncNav(root, immediate) {
        if (!gsapReady() || !root) return;
        const nav = q(root, '[data-admin-nav]');
        const indicator = q(root, '.admin-nav-indicator');
        const active = nav && nav.querySelector('.nav-item.active');
        if (!nav || !indicator || !active) return;

        const navRect = nav.getBoundingClientRect();
        const r = active.getBoundingClientRect();
        const y = r.top - navRect.top + nav.scrollTop;

        gsap.to(indicator, {
            y,
            height: r.height,
            autoAlpha: 1,
            duration: reduceMotion || immediate ? 0 : 0.45,
            ease: easeOut(),
            overwrite: 'auto'
        });
        breatheActiveIcon(root);
    }

    function splitTitle(el) {
        if (!el || reduceMotion || typeof SplitText === 'undefined') {
            if (!el || !gsapReady()) return null;
            return gsap.fromTo(el, { y: 10, autoAlpha: 0 }, {
                y: 0,
                autoAlpha: 1,
                duration: 0.45,
                ease: easeOut()
            });
        }
        try {
            const split = SplitText.create(el, { type: 'chars,words', aria: 'auto' });
            return gsap.from(split.chars, {
                yPercent: 120,
                autoAlpha: 0,
                duration: 0.55,
                stagger: 0.018,
                ease: easeOut(),
                onComplete: () => {
                    try { split.revert(); } catch (e) {}
                }
            });
        } catch (e) {
            return null;
        }
    }

    function animateChartBars(panel, position) {
        const bars = qa(panel, '.chart-bar');
        if (!bars.length || !gsapReady()) return;
        gsap.set(bars, { scaleY: reduceMotion ? 1 : 0, transformOrigin: 'bottom' });
        if (reduceMotion) return;
        const tween = gsap.to(bars, {
            scaleY: 1,
            duration: 0.9,
            stagger: 0.045,
            ease: 'power3.out'
        });
        if (tabTween && position != null) tabTween.add(tween, position);
        return tween;
    }

    function enterPanelContent(panel) {
        if (!panel || !gsapReady()) return;
        const items = visibleItems(panel, 18);
        const title = panel.querySelector('h2');

        if (reduceMotion) {
            gsap.set([panel, items, title].filter(Boolean), { clearProps: 'transform,filter,opacity' });
            gsap.set(panel, { autoAlpha: 1 });
            return;
        }

        if (title) splitTitle(title);

        if (items.length) {
            gsap.fromTo(items, {
                y: 28,
                autoAlpha: 0
            }, {
                y: 0,
                autoAlpha: 1,
                duration: 0.62,
                stagger: { each: 0.045, from: 'start' },
                ease: easeOut(),
                delay: 0.04,
                overwrite: 'auto',
                clearProps: 'transform'
            });
        }

        animateChartBars(panel);
    }

    function transitionTabs(oldPanel, newPanel, dir) {
        if (!oldPanel || !newPanel) return;
        if (!gsapReady()) {
            oldPanel.style.display = 'none';
            newPanel.style.display = 'block';
            return;
        }

        const gen = ++tabGen;
        const direction = dir >= 0 ? 1 : -1;
        killTabTweens([oldPanel, newPanel]);
        gsap.set(newPanel, { filter: 'blur(0px)' });

        if (reduceMotion) {
            oldPanel.style.display = 'none';
            gsap.set(oldPanel, { autoAlpha: 1, x: 0, y: 0 });
            newPanel.style.display = 'block';
            gsap.set(newPanel, { autoAlpha: 1, x: 0, y: 0, clearProps: 'transform,filter' });
            return;
        }

        tabTween = gsap.timeline({
            defaults: { ease: 'power3.inOut' },
            onComplete: () => { if (gen === tabGen) tabTween = null; }
        });

        tabTween.to(oldPanel, {
            autoAlpha: 0,
            x: -36 * direction,
            duration: 0.28,
            ease: 'power3.in'
        });

        tabTween.add(() => {
            if (gen !== tabGen) return;
            oldPanel.style.display = 'none';
            gsap.set(oldPanel, { x: 0, y: 0, autoAlpha: 1, clearProps: 'filter,transform' });
            newPanel.style.display = 'block';
            gsap.set(newPanel, { autoAlpha: 0, x: 42 * direction, y: 0 });
        });

        tabTween.to(newPanel, {
            autoAlpha: 1,
            x: 0,
            duration: 0.55,
            ease: easeOut(),
            clearProps: 'transform',
            onStart: () => {
                if (gen !== tabGen) return;
                enterPanelContent(newPanel);
            }
        });
    }

    function pulseList(root, key) {
        if (!gsapReady() || reduceMotion || !root) return;
        const tab = TAB_KEYS[key] || key;
        const panel = q(root, `[data-tab-panel="${tab}"]`);
        if (!panel || panel.style.display === 'none') return;
        const items = visibleItems(panel, 16);
        if (!items.length) return;
        gsap.fromTo(items, {
            y: 16,
            autoAlpha: 0.35
        }, {
            y: 0,
            autoAlpha: 1,
            duration: 0.42,
            stagger: 0.03,
            ease: easeOut(),
            overwrite: 'auto',
            clearProps: 'transform'
        });
    }

    function bindPointerMotion(root, ctxRoot) {
        const main = q(ctxRoot, '.admin-main') || ctxRoot;
        const cursor = q(ctxRoot, '.admin-cursor');
        if (!main || !gsapReady()) return;

        if (cursor && !reduceMotion) {
            const xTo = gsap.quickTo(cursor, 'x', { duration: 0.55, ease: 'power3.out' });
            const yTo = gsap.quickTo(cursor, 'y', { duration: 0.55, ease: 'power3.out' });
            gsap.set(cursor, { x: window.innerWidth * 0.6, y: 120, autoAlpha: 0 });

            const onMove = (e) => {
                xTo(e.clientX);
                yTo(e.clientY);
            };
            main.addEventListener('mousemove', onMove);
            main.addEventListener('mouseenter', () => gsap.to(cursor, { autoAlpha: 1, duration: 0.35 }));
            main.addEventListener('mouseleave', () => gsap.to(cursor, { autoAlpha: 0, duration: 0.35 }));
        }

        const shineMove = (e) => {
            const card = e.target.closest('.glass-card, .movie-card-container, .gs-stat-card');
            if (!card || !main.contains(card)) return;
            const r = card.getBoundingClientRect();
            const mx = ((e.clientX - r.left) / r.width) * 100;
            const my = ((e.clientY - r.top) / r.height) * 100;
            card.style.setProperty('--mx', mx + '%');
            card.style.setProperty('--my', my + '%');

            if (card.classList.contains('movie-card-container') && !reduceMotion) {
                const dx = gsap.utils.clamp(-1, 1, (e.clientX - r.left) / r.width * 2 - 1);
                const dy = gsap.utils.clamp(-1, 1, (e.clientY - r.top) / r.height * 2 - 1);
                gsap.to(card, {
                    rotationY: dx * 6,
                    rotationX: -dy * 5,
                    transformPerspective: 900,
                    duration: 0.4,
                    ease: 'power3.out',
                    overwrite: 'auto'
                });
            }
        };

        const shineLeave = (e) => {
            const card = e.target.closest('.glass-card, .movie-card-container, .gs-stat-card');
            if (!card) return;
            if (card.classList.contains('movie-card-container') && !reduceMotion) {
                gsap.to(card, { rotationX: 0, rotationY: 0, duration: 0.5, ease: easeOut(), overwrite: 'auto' });
            }
        };

        main.addEventListener('pointermove', shineMove);
        main.addEventListener('pointerleave', shineLeave, true);

        const nav = q(ctxRoot, '[data-admin-nav]');
        if (nav && !reduceMotion) {
            nav.addEventListener('click', (e) => {
                const item = e.target.closest('.nav-item');
                if (!item) return;
                gsap.fromTo(item, { scale: 0.97 }, { scale: 1, duration: 0.45, ease: 'back.out(2)', overwrite: 'auto' });
            });
            nav.addEventListener('mouseover', (e) => {
                const item = e.target.closest('.nav-item');
                if (!item || item.classList.contains('active')) return;
                gsap.to(item, { x: 6, duration: 0.35, ease: easeOut(), overwrite: 'auto' });
                const icon = item.querySelector('.icon');
                if (icon) gsap.to(icon, { scale: 1.12, duration: 0.35, ease: 'back.out(2)', overwrite: 'auto' });
            });
            nav.addEventListener('mouseout', (e) => {
                const item = e.target.closest('.nav-item');
                if (!item) return;
                const next = e.relatedTarget && item.contains(e.relatedTarget);
                if (next) return;
                gsap.to(item, { x: 0, duration: 0.4, ease: easeOut(), overwrite: 'auto' });
                const icon = item.querySelector('.icon');
                if (icon) gsap.to(icon, { scale: 1, duration: 0.35, ease: easeOut(), overwrite: 'auto' });
            });
        }

        const search = q(ctxRoot, '.gs-header-item');
        if (search) {
            const input = search.querySelector('input');
            if (input) {
                input.addEventListener('focus', () => {
                    gsap.to(search, { scale: 1.015, duration: 0.35, ease: easeOut(), overwrite: 'auto' });
                });
                input.addEventListener('blur', () => {
                    gsap.to(search, { scale: 1, duration: 0.4, ease: easeOut(), overwrite: 'auto' });
                });
            }
        }
    }

    function playAmbient(ctxRoot) {
        if (!gsapReady() || reduceMotion) return;
        const a = q(ctxRoot, '.ambient-orb-a');
        const b = q(ctxRoot, '.ambient-orb-b');
        if (a) {
            gsap.to(a, {
                x: 80,
                y: 50,
                scale: 1.15,
                duration: 14,
                yoyo: true,
                repeat: -1,
                ease: 'sine.inOut'
            });
        }
        if (b) {
            gsap.to(b, {
                x: -60,
                y: -40,
                scale: 1.2,
                duration: 18,
                yoyo: true,
                repeat: -1,
                ease: 'sine.inOut'
            });
        }

        qa(ctxRoot, '.nav-item.active .icon').forEach((icon) => {
            gsap.to(icon, {
                y: -2,
                duration: 1.6,
                yoyo: true,
                repeat: -1,
                ease: 'sine.inOut'
            });
        });
    }

    function playIntro(root) {
        if (!gsapReady() || introPlayed) return;
        introPlayed = true;

        const sidebar = q(root, '.sidebar');
        const brand = qa(root, '.sidebar-brand > *');
        const brandTitle = q(root, '.sidebar-brand .text-xl');
        const navItems = qa(root, '.gs-nav-item');
        const headerItems = qa(root, '.gs-header-item');
        const tabContent = q(root, '.tab-content');
        const indicator = q(root, '.admin-nav-indicator');
        const dashboard = q(root, '[data-tab-panel="dashboard"]') || q(root, '[data-tab-panel]');
        const d = reduceMotion ? 0 : 1;

        gsap.set(sidebar, { x: reduceMotion ? 0 : -80, autoAlpha: reduceMotion ? 1 : 0 });
        gsap.set(brand, { y: reduceMotion ? 0 : 12, autoAlpha: reduceMotion ? 1 : 0 });
        gsap.set(navItems, { x: reduceMotion ? 0 : -18, autoAlpha: reduceMotion ? 1 : 0 });
        gsap.set(headerItems, { y: reduceMotion ? 0 : -18, autoAlpha: reduceMotion ? 1 : 0 });
        gsap.set(tabContent, { y: reduceMotion ? 0 : 18, autoAlpha: reduceMotion ? 1 : 0 });
        if (indicator) gsap.set(indicator, { autoAlpha: 0 });

        const tl = gsap.timeline({
            defaults: { ease: easeOut() },
            delay: reduceMotion ? 0 : 0.12
        });

        tl.to(sidebar, { x: 0, autoAlpha: 1, duration: 0.85 * d || 0.01 }, 0);
        tl.to(brand, { y: 0, autoAlpha: 1, duration: 0.5 * d || 0.01, stagger: 0.08 }, 0.15);

        if (brandTitle && typeof ScrambleTextPlugin !== 'undefined' && !reduceMotion) {
            tl.to(brandTitle, {
                duration: 0.9,
                scrambleText: { text: 'NEXUS', chars: 'upperCase', speed: 0.45 }
            }, 0.18);
        }

        tl.to(navItems, {
            x: 0,
            autoAlpha: 1,
            duration: 0.5 * d || 0.01,
            stagger: { each: 0.045, from: 'start' }
        }, 0.28);

        tl.add(() => syncNav(root, reduceMotion), 0.42);

        tl.to(headerItems, {
            y: 0,
            autoAlpha: 1,
            duration: 0.55 * d || 0.01,
            stagger: 0.07
        }, 0.32);

        tl.to(tabContent, {
            y: 0,
            autoAlpha: 1,
            duration: 0.7 * d || 0.01,
            clearProps: 'transform'
        }, 0.28);

        if (dashboard && !reduceMotion) {
            const items = visibleItems(dashboard, 18);
            if (items.length) {
                gsap.set(items, { y: 32, autoAlpha: 0 });
                tl.to(items, {
                    y: 0,
                    autoAlpha: 1,
                    duration: 0.65,
                    stagger: { each: 0.05, from: 'start' },
                    ease: easeOut(),
                    clearProps: 'transform'
                }, 0.45);
            }
            const title = dashboard.querySelector('h2');
            if (title) tl.add(splitTitle(title), 0.42);
            tl.add(() => animateChartBars(dashboard), 0.7);
        }

        tl.add(() => playAmbient(root), 0.9);
    }

    function init(container) {
        if (!gsapReady()) return;
        const root = container && container.querySelector ? container : document;
        const scope = q(root, '[data-barba-namespace="admin_dashboard"]') || (root.querySelector && root.querySelector('.sidebar') ? root : document);

        if (!q(scope, '.sidebar')) return;
        if (bootedRoot === scope && adminCtx) {
            requestAnimationFrame(() => syncNav(scope, true));
            return;
        }

        registerPlugins();
        gsap.config({ nullTargetWarn: false });
        reduceMotion = prefersReduced();

        if (adminCtx) {
            try { adminCtx.revert(); } catch (e) {}
            adminCtx = null;
        }

        bootedRoot = scope;
        introPlayed = false;

        adminCtx = gsap.context(() => {
            const mm = gsap.matchMedia();
            mm.add('(prefers-reduced-motion: reduce)', () => {
                reduceMotion = true;
            });
            mm.add('(prefers-reduced-motion: no-preference)', () => {
                reduceMotion = false;
            });

            bindPointerMotion(scope, scope);
            whenNavReady(scope, () => playIntro(scope));

            const nav = q(scope, '[data-admin-nav]');
            if (nav) {
                nav.addEventListener('scroll', () => syncNav(scope, true), { passive: true });
            }
        }, scope);

        requestAnimationFrame(() => syncNav(scope, true));
        setTimeout(() => syncNav(scope, false), 420);
    }

    function destroy() {
        tabGen += 1;
        killTabTweens([]);
        if (adminCtx) {
            try { adminCtx.revert(); } catch (e) {}
            adminCtx = null;
        }
        bootedRoot = null;
        introPlayed = false;
    }

    window.NexusAdminMotion = {
        init,
        destroy,
        transitionTabs,
        syncNav: (immediate) => syncNav(bootedRoot || document, immediate),
        pulseList: (key) => pulseList(bootedRoot || document, key),
        enterPanel: enterPanelContent
    };

    window.initLocalAnimations = function initLocalAnimations(container) {
        const start = () => window.NexusAdminMotion.init(container || document);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start, { once: true });
            return;
        }
        if (window.Alpine) {
            requestAnimationFrame(() => setTimeout(start, 40));
            return;
        }
        document.addEventListener('alpine:initialized', start, { once: true });
        setTimeout(start, 160);
    };
})();
