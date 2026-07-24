function userDashboard() {
    return {
        currentTab: 'dashboard',
        isNavOpen: false,
        showFriendsPanel: false,
        showInviteModal: false,
        friends: [
            { name: 'Sarah Connor', status: 'Online', avatar: 'https://ui-avatars.com/api/?name=Sarah+Connor&background=ec4899&color=fff', activity: 'Watching Dune: Part Two' },
            { name: 'John Doe', status: 'Away', avatar: 'https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff', activity: 'In menus' },
            { name: 'Jane Smith', status: 'Offline', avatar: 'https://ui-avatars.com/api/?name=Jane+Smith&background=f59e0b&color=fff', activity: 'Last seen 2h ago' }
        ],
        openNav() {
            if (this.isNavOpen) return;
            this.isNavOpen = true;
            
            const tl = gsap.timeline();
            
            // Enable pointers
            document.getElementById('side-panel').style.pointerEvents = 'auto';
            document.getElementById('nav-overlay').style.pointerEvents = 'auto';
            
            // Overlay fade
            tl.to('#nav-overlay', { opacity: 1, duration: 0.15, ease: "power2.out" }, 0);
            
            // Panel slide in
            tl.to('#side-panel', {
                x: 0,
                duration: 0.5,
                ease: "expo.out"
            }, 0);
            
            // Stagger nav items
            tl.fromTo('.side-nav-item', 
                { x: 30, opacity: 0, scale: 0.9, rotationX: -15 },
                { x: 0, opacity: 1, scale: 1, rotationX: 0, stagger: 0.05, duration: 0.6, ease: "back.out(1.5)" },
                0.2
            );
            
            // Stagger other panel elements
            tl.fromTo('.side-panel-stagger',
                { opacity: 0, x: -20, scale: 0.95 },
                { opacity: 1, x: 0, scale: 1, stagger: 0.05, duration: 0.5, ease: "back.out(1.2)" },
                0.1
            );
        },
        closeNav() {
            if (!this.isNavOpen) return;
            this.isNavOpen = false;
            
            const tl = gsap.timeline({
                onComplete: () => {
                    document.getElementById('side-panel').style.pointerEvents = 'none';
                    document.getElementById('nav-overlay').style.pointerEvents = 'none';
                }
            });
            
            // Fade out elements quickly
            tl.to('.side-nav-item', {
                x: -30, opacity: 0, scale: 0.9, rotationX: 15, stagger: 0.02, duration: 0.3, ease: "power3.in"
            }, 0);
            
            tl.to('.side-panel-stagger', {
                opacity: 0, x: -20, scale: 0.95, duration: 0.2, ease: "power3.in"
            }, 0);
            
            // Hide panel
            tl.to('#side-panel', {
                x: '-100%',
                duration: 0.4,
                ease: "expo.inOut"
            }, 0.1);
            
            // Hide overlay
            tl.to('#nav-overlay', { opacity: 0, duration: 0.3, ease: "power2.in" }, 0.2);
        },
        navItems: [
            { id: 'dashboard', label: 'Command Center', icon: 'dashboard', module: 'MODULE_1' },
            { id: 'watchlist', label: 'Watchlist', icon: 'bookmark', module: 'MODULE_2' },
            { id: 'friends', label: 'Network (Friends)', icon: 'hub', module: 'MODULE_3' },
            { id: 'history', label: 'Watch History', icon: 'history_toggle_off', module: 'MODULE_4' },
            { id: 'settings', label: 'System Preferences', icon: 'settings', module: 'MODULE_5' }
        ],
        stats: [
            { label: 'Total Watch Time', value: 124, suffix: 'H', icon: 'timer', colorClass: 'bg-red-500/10 text-red-500 border border-red-500/20 group-hover:bg-red-500/20 group-hover:shadow-[0_0_20px_rgba(239,68,68,0.3)]', trendClass: 'text-green-400 border-green-400/20', trend: '+12%', desc: 'vs last week' },
            { label: 'Sessions Hosted', value: 28, suffix: '', icon: 'cell_tower', colorClass: 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 group-hover:bg-indigo-500/20 group-hover:shadow-[0_0_20px_rgba(79,70,229,0.3)]', trendClass: 'text-green-400 border-green-400/20', trend: '+3', desc: 'new this week' },
            { label: 'Friends', value: 9, suffix: '', icon: 'group', colorClass: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 group-hover:bg-emerald-500/20 group-hover:shadow-[0_0_20px_rgba(16,185,129,0.3)]', trendClass: 'text-emerald-400 border-emerald-400/20', trend: 'Online', desc: 'active', action: 'showFriendsPanel = true' },
            { label: 'Achievements', value: 12, suffix: '', icon: 'military_tech', colorClass: 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 group-hover:bg-yellow-500/20 group-hover:shadow-[0_0_20px_rgba(234,179,8,0.3)]', trendClass: 'text-yellow-400 border-yellow-400/20', trend: 'New', desc: 'Night Owl badge' }
        ],
        upcomingParties: [
            { title: "Dune: Part Two", time: "TODAY 20:00", genre: "SCI-FI", host: "You", members: 8, img: "https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=400&h=200" },
            { title: "Interstellar", time: "TMRW 21:00", genre: "SCI-FI", host: "Sarah J.", members: 12, img: "https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?auto=format&fit=crop&q=80&w=400&h=200" },
            { title: "Cyberpunk Edgerunners", time: "FRI 22:00", genre: "ANIME", host: "David W.", members: 15, img: "https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&q=80&w=400&h=200" }
        ],
        watchlist: [
            { title: "Blade Runner 2049", year: "2017", genre: "SCI-FI", rating: "98%", status: "Next Up", img: "https://images.unsplash.com/photo-1618193132172-e1c6b6531398?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "The Matrix", year: "1999", genre: "ACTION", rating: "99%", status: "Pending", img: "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Arrival", year: "2016", genre: "SCI-FI", rating: "95%", status: "Pending", img: "https://images.unsplash.com/photo-1518331647614-7a1f04cd34cb?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Akira", year: "1988", genre: "ANIME", rating: "96%", status: "Pending", img: "https://images.unsplash.com/photo-1554629947-334ff61d85dc?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Ex Machina", year: "2014", genre: "THRILLER", rating: "92%", status: "Pending", img: "https://images.unsplash.com/photo-1535378273068-9bb67d5beacd?auto=format&fit=crop&q=80&w=600&h=900" },
            { title: "Tron: Legacy", year: "2010", genre: "ACTION", rating: "88%", status: "Pending", img: "https://images.unsplash.com/photo-1510511459019-5efa7ae67376?auto=format&fit=crop&q=80&w=600&h=900" }
        ],
        activityFeed: [
            { text: '<span class="font-bold text-white">Sarah Connor</span> joined your network', time: '2 MINS AGO', dotColor: 'bg-indigo-500' },
            { text: 'Protocol initialized: <span class="font-bold text-red-400">Cyberpunk Edgerunners</span>', time: '1 HOUR AGO', dotColor: 'bg-red-500' },
            { text: 'Achievement unlocked: <span class="font-bold text-yellow-400">Night Owl V2</span>', time: 'YESTERDAY', dotColor: 'bg-yellow-500' },
            { text: 'System diagnostic completed. Connection stable.', time: '2 DAYS AGO', dotColor: 'bg-white/20' }
        ],
        initDashboard() {
            gsap.registerPlugin();
            
            // Escape key to close nav
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isNavOpen) {
                    this.closeNav();
                }
            });
            
            // 1. Advanced Cursor Follower
            const cursor = document.getElementById('cursor-glow');
            const innerCursor = document.createElement('div');
            innerCursor.classList.add('inner-cursor');
            document.body.appendChild(innerCursor);
            
            gsap.set(cursor, { xPercent: -50, yPercent: -50 });
            gsap.set(innerCursor, { xPercent: -50, yPercent: -50 });
            
            let mouseX = window.innerWidth / 2;
            let mouseY = window.innerHeight / 2;
            
            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
                
                // Parallax background mesh
                gsap.to('.bg-mesh', {
                    x: (e.clientX / window.innerWidth - 0.5) * 60,
                    y: (e.clientY / window.innerHeight - 0.5) * 60,
                    ease: "power2.out",
                    duration: 1.5
                });
            });
            
            gsap.ticker.add(() => {
                // Smooth follower for outer glow
                gsap.to(cursor, {
                    x: mouseX,
                    y: mouseY,
                    duration: 0.8,
                    ease: "power3.out"
                });
                // Instant follower for inner dot
                gsap.set(innerCursor, {
                    x: mouseX,
                    y: mouseY
                });
            });

            // Interactive Elements Cursor Effect
            const interactiveElements = document.querySelectorAll('button, a, .cursor-pointer, .top-nav-item');
            interactiveElements.forEach(elem => {
                elem.addEventListener('mouseenter', () => {
                    gsap.to(innerCursor, { scale: 4, backgroundColor: 'transparent', border: '1px solid rgba(239, 68, 68, 0.8)', duration: 0.2 });
                });
                elem.addEventListener('mouseleave', () => {
                    gsap.to(innerCursor, { scale: 1, backgroundColor: '#ef4444', border: 'none', duration: 0.2 });
                });
            });

            this.$nextTick(() => {
                // 3. Animated Number Counters
                const counters = document.querySelectorAll('.stat-counter');
                counters.forEach(counter => {
                    const target = parseFloat(counter.getAttribute('data-target'));
                    const obj = { val: 0 };
                    
                    gsap.to(obj, {
                        val: target,
                        duration: 2.5,
                        ease: "power3.out",
                        delay: 0.8, // Wait for intro sequence
                        onUpdate: () => {
                            counter.innerText = Math.floor(obj.val);
                        }
                    });
                });
            });

            // Epic Intro Sequence
            const tl = gsap.timeline();
            
            // Header items drop in
            tl.fromTo(".gs-header-item", 
                { y: -40, opacity: 0, scale: 0.95 }, 
                { y: 0, opacity: 1, scale: 1, stagger: 0.1, duration: 0.8, ease: "back.out(1.5)" }, 
                0.2
            )
            // Staggered grid cards entry
            .fromTo(".stagger-item", 
                { opacity: 0, y: 80, rotationY: 15, scale: 0.9 }, 
                { opacity: 1, y: 0, rotationY: 0, scale: 1, stagger: 0.1, duration: 0.9, ease: "back.out(1.2)" }, 
                "-=0.6"
            );

            // Split text animation for welcome
            const welcomeText = document.querySelector('.welcome-text');
            if (welcomeText) {
                const text = welcomeText.innerText;
                welcomeText.innerHTML = '';
                [...text].forEach(char => {
                    const span = document.createElement('span');
                    span.innerText = char;
                    span.style.opacity = '0';
                    span.style.display = 'inline-block';
                    if (char === ' ') span.innerHTML = '&nbsp;';
                    welcomeText.appendChild(span);
                });
                
                gsap.fromTo(welcomeText.querySelectorAll('span'), 
                    { opacity: 0, y: 30, rotationX: 90 },
                    {
                        opacity: 1,
                        y: 0,
                        rotationX: 0,
                        stagger: 0.04,
                        duration: 0.7,
                        ease: "back.out(2)",
                        delay: 0.5
                    }
                );
            }

            // Continuous Micro-Animations
            
            // Pulsing dots in activity feed
            this.$nextTick(() => {
                gsap.to('.activity-item .dot-pulse', {
                    scale: 1.8,
                    opacity: 0,
                    repeat: -1,
                    duration: 1.5,
                    ease: "power2.out",
                    stagger: 0.3
                });
            });

            // Random glitch effect on stat numbers periodically
            setInterval(() => {
                const stats = document.querySelectorAll('.stat-counter');
                const randomStat = stats[Math.floor(Math.random() * stats.length)];
                gsap.to(randomStat, {
                    x: () => Math.random() * 8 - 4,
                    y: () => Math.random() * 8 - 4,
                    duration: 0.05,
                    yoyo: true,
                    repeat: 5,
                    onComplete: () => {
                        gsap.set(randomStat, {x:0, y:0});
                    }
                });
            }, 6000);
        }
    }
}
