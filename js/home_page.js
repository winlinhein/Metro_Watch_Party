(function () {
    'use strict';

    let homeCtx = null;
    let featuredTween = null;

    const FALLBACK_MOVIES = [
        { title: 'Interstellar', img: 'https://image.tmdb.org/t/p/w500/gEU2QniLT6KCq6urGY8JiXQAALt.jpg', genre: 'Sci-Fi', viewers: 42 },
        { title: 'Dune: Part Two', img: 'https://image.tmdb.org/t/p/w500/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg', genre: 'Sci-Fi', viewers: 36 },
        { title: 'The Batman', img: 'https://image.tmdb.org/t/p/w500/74xTEgt7R36Fpooo50r9T25onhq.jpg', genre: 'Action', viewers: 28 },
        { title: 'Joker', img: 'https://image.tmdb.org/t/p/w500/udDclJoHjfjb8Ekgsd4FDteOkCU.jpg', genre: 'Drama', viewers: 19 },
        { title: 'Inception', img: 'https://image.tmdb.org/t/p/w500/oYuLEt3zNKs9x4kKviJVFPQKqg8.jpg', genre: 'Thriller', viewers: 31 },
        { title: 'Parasite', img: 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', genre: 'Thriller', viewers: 22 },
        { title: 'Spider-Man', img: 'https://image.tmdb.org/t/p/w500/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg', genre: 'Action', viewers: 47 },
        { title: 'Your Name', img: 'https://image.tmdb.org/t/p/w500/q719jXXEzOoYaps6babgKnOH1r.jpg', genre: 'Anime', viewers: 15 }
    ];

    const FEATURED_ROOMS = [
        {
            title: 'Dune',
            host: 'Alex',
            viewers: 24,
            tag: 'LIVE',
            line: 'Arrakis. Dune. Desert planet.',
            img: '/frontend/assets/home/dune-live.jpg'
        },
        {
            title: 'Interstellar',
            host: 'Maya',
            viewers: 18,
            tag: 'LIVE',
            line: 'We used to look up at the sky and wonder.',
            img: '/user_backend/get_poster.php?id=11'
        },
        {
            title: 'Inception',
            host: 'Kenji',
            viewers: 31,
            tag: 'SYNCED',
            line: 'We need to go deeper.',
            img: '/user_backend/get_poster.php?id=10'
        }
    ];

    function movieStillFromList(list, needle) {
        const n = String(needle || '').toLowerCase();
        return (list || []).find((m) => String(m.title || '').toLowerCase().includes(n));
    }

    function roomsFromCatalog(list) {
        return [
            { needle: 'dune', i: 0 },
            { needle: 'interstellar', i: 1 },
            { needle: 'inception', i: 2 }
        ].map(({ needle, i }) => {
            const base = FEATURED_ROOMS[i];
            const m = movieStillFromList(list, needle);
            if (!m) return base;
            // Keep curated stills for featured rooms; only sync the catalog title.
            return Object.assign({}, base, {
                title: m.title || base.title
            });
        });
    }

    function gsapReady() {
        return typeof gsap !== 'undefined';
    }

    function registerPlugins() {
        if (!gsapReady()) return;
        const plugins = [];
        if (typeof ScrollTrigger !== 'undefined') plugins.push(ScrollTrigger);
        if (typeof SplitText !== 'undefined') plugins.push(SplitText);
        if (typeof ScrambleTextPlugin !== 'undefined') plugins.push(ScrambleTextPlugin);
        if (typeof ScrollToPlugin !== 'undefined') plugins.push(ScrollToPlugin);
        if (plugins.length) gsap.registerPlugin.apply(gsap, plugins);
    }

    window.destroyHomePage = function destroyHomePage() {
        if (window._nexusHomeFeatTimer) {
            clearInterval(window._nexusHomeFeatTimer);
            window._nexusHomeFeatTimer = null;
        }
        if (featuredTween) {
            featuredTween.kill();
            featuredTween = null;
        }
        if (homeCtx) {
            homeCtx.revert();
            homeCtx = null;
        }
        const live = document.querySelector('[data-barba-namespace="index"]');
        if (live) delete live.dataset.homeInit;
        if (typeof ScrollTrigger !== 'undefined') {
            ScrollTrigger.getAll().forEach((t) => {
                const id = t.vars && t.vars.id;
                if (id && String(id).indexOf('home-') === 0) t.kill();
            });
        }
    };

    window.initHomePage = function initHomePage(container) {
        if (!container || !container.querySelector || !container.querySelector('.home-hero')) return;
        if (!gsapReady()) return;
        if (container.dataset.homeInit === '1') return;
        container.dataset.homeInit = '1';
        if (homeCtx) {
            homeCtx.revert();
            homeCtx = null;
        }

        registerPlugins();
        gsap.config({ nullTargetWarn: false });

        homeCtx = gsap.context(() => {
            const mm = gsap.matchMedia();

            mm.add(
                {
                    isDesktop: '(min-width: 800px)',
                    reduceMotion: '(prefers-reduced-motion: reduce)'
                },
                (context) => {
                    const { isDesktop, reduceMotion } = context.conditions;
                    const d = reduceMotion ? 0 : 1;

                    const heroBg = container.querySelector('.home-hero-bg-img');
                    const heroCopy = container.querySelector('.home-hero-copy');
                    const title = container.querySelector('.home-hero-title');
                    const visual = container.querySelector('.home-visual');
                    const nav = container.querySelector('.home-nav');

                    gsap.set([heroCopy, visual].filter(Boolean), { autoAlpha: reduceMotion ? 1 : 0 });

                    const intro = gsap.timeline({ defaults: { ease: 'power3.out' } });

                    if (nav) {
                        intro.from(nav, { y: -24, autoAlpha: 0, duration: 0.6 * d }, 0);
                    }

                    intro.to(heroCopy, { autoAlpha: 1, duration: 0.2 * d }, 0.15);

                    const badge = container.querySelector('.home-live-badge');
                    if (badge) {
                        intro.from(badge, { y: 16, autoAlpha: 0, duration: 0.5 * d }, 0.2);
                        const badgeText = badge.querySelector('.home-live-text');
                        if (badgeText && typeof ScrambleTextPlugin !== 'undefined' && !reduceMotion) {
                            intro.to(badgeText, {
                                duration: 0.9,
                                scrambleText: { text: badgeText.textContent, chars: '01NEXUS', speed: 0.4 }
                            }, 0.25);
                        }
                    }

                    if (title) {
                        const line = container.querySelector('.home-hero-line');
                        const accent = container.querySelector('.home-hero-accent');
                        if (typeof SplitText !== 'undefined' && !reduceMotion && line) {
                            const split = SplitText.create(line, {
                                type: 'chars, words',
                                charsClass: 'home-char',
                                aria: 'auto'
                            });
                            gsap.set(split.chars, { yPercent: 120, autoAlpha: 0 });
                            intro.to(split.chars, {
                                yPercent: 0,
                                autoAlpha: 1,
                                stagger: 0.018,
                                duration: 0.9,
                                ease: 'power4.out'
                            }, 0.28);
                        } else {
                            const words = title.querySelectorAll('.gs-word');
                            intro.from(words.length ? words : title, {
                                y: 36,
                                autoAlpha: 0,
                                stagger: 0.08,
                                duration: 0.8 * d
                            }, 0.28);
                        }
                        if (accent && typeof SplitText !== 'undefined') {
                            intro.from(accent, {
                                y: 28,
                                autoAlpha: 0,
                                duration: 0.7 * d,
                                ease: 'power3.out'
                            }, 0.55);
                        }
                    }

                    intro.from(container.querySelectorAll('.home-hero-lead, .home-hero-actions, .home-social-proof'), {
                        y: 24,
                        autoAlpha: 0,
                        stagger: 0.1,
                        duration: 0.7 * d
                    }, 0.55);

                    if (visual) {
                        intro.fromTo(visual, { autoAlpha: 0, y: 40, scale: 0.96 }, {
                            autoAlpha: 1,
                            y: 0,
                            scale: 1,
                            duration: 0.9 * d,
                            ease: 'power3.out'
                        }, 0.4);
                    }

                    intro.from(container.querySelectorAll('.home-float-card'), {
                        y: 30,
                        autoAlpha: 0,
                        stagger: 0.12,
                        duration: 0.7 * d
                    }, 0.7);

                    const chatLines = container.querySelectorAll('.home-chat-line');
                    if (chatLines.length) {
                        intro.from(chatLines, {
                            x: -18,
                            autoAlpha: 0,
                            stagger: 0.22,
                            duration: 0.45 * d
                        }, 1.0);
                    }

                    if (heroBg && !reduceMotion) {
                        gsap.fromTo(heroBg, { scale: 1.08 }, {
                            scale: 1.18,
                            duration: 22,
                            ease: 'none',
                            repeat: -1,
                            yoyo: true
                        });
                        gsap.to(heroBg, {
                            yPercent: 12,
                            ease: 'none',
                            scrollTrigger: {
                                id: 'home-hero-parallax',
                                trigger: container.querySelector('.home-hero'),
                                start: 'top top',
                                end: 'bottom top',
                                scrub: true
                            }
                        });
                    }

                    const playPulse = container.querySelectorAll('.home-play-pulse');
                    if (playPulse.length && !reduceMotion) {
                        gsap.to(playPulse, {
                            scale: 1.55,
                            autoAlpha: 0,
                            duration: 1.8,
                            repeat: -1,
                            ease: 'power1.out',
                            stagger: 0.4
                        });
                    }

                    gsap.set(container.querySelectorAll('.home-feature-card'), { y: 56, autoAlpha: 0 });
                    if (typeof ScrollTrigger !== 'undefined') {
                        ScrollTrigger.batch(container.querySelectorAll('.home-feature-card'), {
                            start: 'top 88%',
                            interval: 0.12,
                            batchMax: 3,
                            onEnter: (batch) => gsap.to(batch, {
                                y: 0,
                                autoAlpha: 1,
                                stagger: 0.12,
                                duration: reduceMotion ? 0 : 0.85,
                                ease: 'power3.out',
                                overwrite: true
                            })
                        });
                    }

                    gsap.set(container.querySelectorAll('.home-movie-card'), { y: 40, autoAlpha: 0 });
                    if (typeof ScrollTrigger !== 'undefined') {
                        ScrollTrigger.batch(container.querySelectorAll('.home-movie-card'), {
                            start: 'top 90%',
                            interval: 0.08,
                            batchMax: 8,
                            onEnter: (batch) => gsap.to(batch, {
                                y: 0,
                                autoAlpha: 1,
                                stagger: 0.06,
                                duration: reduceMotion ? 0 : 0.7,
                                ease: 'power3.out',
                                overwrite: true
                            })
                        });
                    }

                    gsap.set(container.querySelectorAll('.home-step, .home-bento-card, .home-use-card, .home-plan-card'), { y: 40, autoAlpha: 0 });
                    if (typeof ScrollTrigger !== 'undefined') {
                        ScrollTrigger.batch(container.querySelectorAll('.home-step, .home-bento-card, .home-use-card, .home-plan-card'), {
                            start: 'top 88%',
                            interval: 0.1,
                            batchMax: 6,
                            onEnter: (batch) => gsap.to(batch, {
                                y: 0,
                                autoAlpha: 1,
                                stagger: 0.08,
                                duration: reduceMotion ? 0 : 0.75,
                                ease: 'power3.out',
                                overwrite: true
                            })
                        });
                    }

                    const line = container.querySelector('.home-steps-line');
                    if (line && typeof ScrollTrigger !== 'undefined') {
                        gsap.set(line, { scaleY: 0, transformOrigin: 'top center' });
                        gsap.to(line, {
                            scaleY: 1,
                            ease: 'none',
                            scrollTrigger: {
                                id: 'home-steps-line',
                                trigger: container.querySelector('.home-steps'),
                                start: 'top 70%',
                                end: 'bottom 55%',
                                scrub: 1
                            }
                        });
                    }

                    const syncBar = container.querySelector('.home-sync-bar');
                    if (syncBar && typeof ScrollTrigger !== 'undefined') {
                        gsap.fromTo(syncBar, { scaleX: 0.18 }, {
                            scaleX: 0.72,
                            ease: 'none',
                            scrollTrigger: {
                                id: 'home-sync-bar',
                                trigger: container.querySelector('#showcase'),
                                start: 'top 70%',
                                end: 'bottom 40%',
                                scrub: 1
                            }
                        });
                    }

                    const contactCard = container.querySelector('.home-contact-form-wrap');
                    if (contactCard) {
                        gsap.from(contactCard, {
                            y: 48,
                            autoAlpha: 0,
                            duration: 0.9 * d,
                            ease: 'power3.out',
                            scrollTrigger: {
                                id: 'home-contact-card',
                                trigger: contactCard,
                                start: 'top 88%'
                            }
                        });
                    }

                    const contactFields = container.querySelectorAll('.home-contact-form .home-field');
                    if (contactFields.length) {
                        gsap.from(contactFields, {
                            y: 18,
                            autoAlpha: 0,
                            stagger: 0.08,
                            duration: 0.55 * d,
                            ease: 'power3.out',
                            scrollTrigger: {
                                id: 'home-contact-fields',
                                trigger: container.querySelector('.home-contact-form'),
                                start: 'top 90%'
                            }
                        });
                    }

                    const shine = container.querySelector('.home-submit-shine');
                    if (shine && !reduceMotion) {
                        gsap.to(shine, {
                            xPercent: 220,
                            duration: 1.8,
                            ease: 'power2.inOut',
                            repeat: -1,
                            repeatDelay: 2.4
                        });
                    }

                    if (isDesktop && !reduceMotion) {
                        container.querySelectorAll('.home-bento-card, .home-use-card, .home-plan-card').forEach((card) => {
                            card.addEventListener('mouseenter', () => {
                                gsap.to(card, { y: -6, duration: 0.4, ease: 'power3.out', overwrite: 'auto' });
                            });
                            card.addEventListener('mouseleave', () => {
                                gsap.to(card, { y: 0, duration: 0.45, ease: 'power3.out', overwrite: 'auto' });
                            });
                        });
                    }

                    const reveals = container.querySelectorAll('.home-reveal');
                    reveals.forEach((el) => {
                        gsap.from(el, {
                            y: 32,
                            autoAlpha: 0,
                            duration: 0.8 * d,
                            scrollTrigger: {
                                trigger: el,
                                start: 'top 88%'
                            }
                        });
                    });

                    container.querySelectorAll('[data-count]').forEach((el) => {
                        const end = parseFloat(el.getAttribute('data-count')) || 0;
                        const obj = { val: 0 };
                        gsap.to(obj, {
                            val: end,
                            duration: reduceMotion ? 0 : 1.6,
                            ease: 'power2.out',
                            scrollTrigger: {
                                id: 'home-count-' + (el.getAttribute('data-count') || ''),
                                trigger: el,
                                start: 'top 90%'
                            },
                            onUpdate: () => {
                                el.textContent = Math.floor(obj.val).toLocaleString();
                            }
                        });
                    });

                    if (isDesktop && !reduceMotion) {
                        container.querySelectorAll('.home-magnetic').forEach((btn) => {
                            const xTo = gsap.quickTo(btn, 'x', { duration: 0.4, ease: 'power3' });
                            const yTo = gsap.quickTo(btn, 'y', { duration: 0.4, ease: 'power3' });
                            btn.addEventListener('mousemove', (e) => {
                                const r = btn.getBoundingClientRect();
                                xTo((e.clientX - r.left - r.width / 2) * 0.22);
                                yTo((e.clientY - r.top - r.height / 2) * 0.22);
                            });
                            btn.addEventListener('mouseleave', () => {
                                xTo(0);
                                yTo(0);
                            });
                        });

                        container.querySelectorAll('.home-feature-card').forEach((card) => {
                            card.addEventListener('mouseenter', () => {
                                gsap.to(card, { y: -8, duration: 0.45, ease: 'power3.out', overwrite: 'auto' });
                            });
                            card.addEventListener('mouseleave', () => {
                                gsap.to(card, { y: 0, duration: 0.5, ease: 'power3.out', overwrite: 'auto' });
                            });
                        });
                    }

                    const protocol = container.querySelector('.home-protocol-marquee');
                    if (protocol && !reduceMotion) {
                        gsap.to(protocol, {
                            xPercent: -20,
                            ease: 'none',
                            scrollTrigger: {
                                id: 'home-protocol',
                                trigger: container.querySelector('#features'),
                                start: 'top bottom',
                                end: 'bottom top',
                                scrub: 1
                            }
                        });
                    }

                    if (typeof ScrollTrigger !== 'undefined') {
                        requestAnimationFrame(() => ScrollTrigger.refresh());
                    }
                }
            );
        }, container);

        container.dataset.homeInit = '1';
    };

    window.crossfadeFeatured = function crossfadeFeatured(container, room) {
        if (!container || !room || !gsapReady()) return;
        const imgs = container.querySelectorAll('.home-featured-img');
        const title = container.querySelector('.home-featured-title');
        const host = container.querySelector('.home-featured-host');
        const viewers = container.querySelector('.home-featured-viewers');
        const tag = container.querySelector('.home-featured-tag');
        const fadeTargets = [...imgs, title, host].filter(Boolean);

        if (featuredTween) featuredTween.kill();
        featuredTween = gsap.timeline();
        featuredTween.to(fadeTargets, {
            autoAlpha: 0.35,
            duration: 0.25,
            ease: 'power2.in'
        });
        featuredTween.add(() => {
            imgs.forEach((img) => { img.src = room.img; });
            if (title) title.textContent = room.title;
            if (host) host.textContent = 'Hosted by ' + room.host;
            if (viewers) viewers.textContent = room.viewers + ' watching';
            if (tag) tag.textContent = room.tag;
        });
        featuredTween.to(fadeTargets, {
            autoAlpha: 1,
            duration: 0.4,
            ease: 'power2.out'
        });
    };

    window.nexusHome = function nexusHome() {
        return {
            mobileMenuOpen: false,
            navScrolled: false,
            featuredIndex: 0,
            featuredRooms: FEATURED_ROOMS,
            movies: FALLBACK_MOVIES.slice(),
            hoveredMovie: null,
            faqOpen: null,
            demoStep: 1,
            contact: { name: '', email: '', topic: 'general', message: '', website: '' },
            contactStatus: 'idle',
            contactError: '',
            _featTimer: null,
            _scrollBound: null,

            get currentRoom() {
                return this.featuredRooms[this.featuredIndex] || this.featuredRooms[0];
            },

            get marqueeMovies() {
                return this.movies.concat(this.movies);
            },

            init() {
                this._scrollBound = () => {
                    this.navScrolled = window.scrollY > 24;
                };
                window.addEventListener('scroll', this._scrollBound, { passive: true });
                this._scrollBound();
                this.loadMovies();
                this.startFeatured();

                this.$watch('featuredIndex', (i) => {
                    const room = this.featuredRooms[i];
                    if (room) window.crossfadeFeatured(this.$root, room);
                });

                this.$nextTick(() => {
                    if (typeof window.initHomePage === 'function') {
                        window.initHomePage(this.$root);
                    }
                });
            },

            destroy() {
                window.removeEventListener('scroll', this._scrollBound);
                this.stopFeatured();
                if (typeof window.destroyHomePage === 'function') {
                    window.destroyHomePage();
                }
            },

            startFeatured() {
                this.stopFeatured();
                this._featTimer = setInterval(() => {
                    this.featuredIndex = (this.featuredIndex + 1) % this.featuredRooms.length;
                }, 6500);
                window._nexusHomeFeatTimer = this._featTimer;
            },

            stopFeatured() {
                if (this._featTimer) {
                    clearInterval(this._featTimer);
                    this._featTimer = null;
                }
                if (window._nexusHomeFeatTimer) {
                    clearInterval(window._nexusHomeFeatTimer);
                    window._nexusHomeFeatTimer = null;
                }
            },

            setFeatured(i) {
                this.featuredIndex = i;
                this.startFeatured();
            },

            async loadMovies() {
                const urls = [
                    '/user_backend/movies_api.php',
                    'user_backend/movies_api.php'
                ];
                for (const url of urls) {
                    try {
                        const res = await fetch(url);
                        if (!res.ok) continue;
                        const data = await res.json();
                        const list = Array.isArray(data) ? data : (data && data.movies);
                        if (list && list.length) {
                            this.movies = list.slice(0, 12).map((m) => ({
                                title: m.title || 'Untitled',
                                img: m.img || m.cover_image || '',
                                genre: Array.isArray(m.genres) ? (m.genres[0] || 'Film') : (m.genre || 'Film'),
                                viewers: Math.max(3, Math.floor(Math.random() * 48))
                            }));
                            this.featuredRooms = roomsFromCatalog(list);
                            this.$nextTick(() => {
                                if (typeof ScrollTrigger !== 'undefined') ScrollTrigger.refresh();
                                window.crossfadeFeatured(this.$root, this.currentRoom);
                            });
                            return;
                        }
                    } catch (e) { /* try next */ }
                }
            },

            scrollRow(dir) {
                const row = this.$refs.movieRow;
                if (!row) return;
                const amount = Math.min(row.clientWidth * 0.8, 720) * dir;
                if (gsapReady() && typeof ScrollToPlugin !== 'undefined') {
                    gsap.to(row, { duration: 0.7, ease: 'power3.out', scrollTo: { x: row.scrollLeft + amount } });
                } else {
                    row.scrollBy({ left: amount, behavior: 'smooth' });
                }
            },

            hoverMovie(card, enter) {
                if (typeof gsap === 'undefined') return;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                gsap.to(card, {
                    scale: enter ? 1.06 : 1,
                    y: enter ? -6 : 0,
                    duration: 0.4,
                    ease: 'power3.out',
                    overwrite: 'auto'
                });
            },

            toggleFaq(id) {
                this.faqOpen = this.faqOpen === id ? null : id;
            },

            setDemoStep(step) {
                this.demoStep = step;
                this.$nextTick(() => {
                    const panel = this.$root.querySelector('.home-demo-panel:not([style*="display: none"])')
                        || this.$root.querySelector('.home-demo-visual');
                    if (!panel || typeof gsap === 'undefined') return;
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                    gsap.fromTo(panel, { autoAlpha: 0.35, y: 12 }, {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.45,
                        ease: 'power3.out',
                        overwrite: 'auto'
                    });
                });
            },

            resetContact() {
                this.contact = { name: '', email: '', topic: 'general', message: '', website: '' };
                this.contactStatus = 'idle';
                this.contactError = '';
            },

            async sendContact() {
                if (this.contactStatus === 'sending') return;
                this.contactError = '';
                if (!this.contact.name || this.contact.name.trim().length < 2) {
                    this.contactError = 'Please enter your name.';
                    return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.contact.email || '')) {
                    this.contactError = 'Please enter a valid email.';
                    return;
                }
                if (!(this.contact.message || '').trim() || this.contact.message.trim().length < 10) {
                    this.contactError = 'Please write a slightly longer message.';
                    return;
                }

                this.contactStatus = 'sending';
                try {
                    const res = await fetch('/backend/contact.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.contact)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        this.contactStatus = 'idle';
                        this.contactError = data.message || 'Could not send right now.';
                        if (window.showToast) window.showToast(this.contactError, 'error');
                        return;
                    }
                    this.contactStatus = 'sent';
                    this.$nextTick(() => {
                        const mark = this.$root.querySelector('.home-success-mark');
                        const title = this.$root.querySelector('.home-success-title');
                        if (typeof gsap !== 'undefined' && mark) {
                            const tl = gsap.timeline();
                            tl.from(mark, { scale: 0.4, autoAlpha: 0, duration: 0.55, ease: 'back.out(1.8)' });
                            if (title) tl.from(title, { y: 16, autoAlpha: 0, duration: 0.45, ease: 'power3.out' }, '-=0.2');
                        }
                    });
                    if (window.showToast) window.showToast(data.message || 'Signal received.', 'success');
                } catch (e) {
                    this.contactStatus = 'idle';
                    this.contactError = 'Could not send right now. Try again in a moment.';
                    if (window.showToast) window.showToast(this.contactError, 'error');
                }
            }
        };
    };

    document.addEventListener('alpine:init', () => {
        if (window.Alpine && typeof Alpine.data === 'function') {
            Alpine.data('nexusHome', window.nexusHome);
        }
    });
})();
