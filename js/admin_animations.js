function initLocalAnimations(container = document) {
    if (typeof gsap === 'undefined') return;

    if (typeof ScrollTrigger !== 'undefined') {
        ScrollTrigger.getAll().forEach(t => t.kill());
    }

    // High performance config
    gsap.config({ force3D: true });

    const tl = gsap.timeline({
        defaults: { ease: "expo.out", duration: 1.2 }
    });

    // 1. Sidebar Panel Entry - glassmorphic slide with blur
    gsap.set(container.querySelectorAll('.sidebar'), { x: -250, opacity: 0, filter: "blur(10px)" });
    tl.to(container.querySelectorAll('.sidebar'), { 
        x: 0, 
        opacity: 1, 
        filter: "blur(0px)",
        duration: 1.5,
        ease: "power4.out"
    });

    // 2. Sidebar Brand Logo pop
    gsap.set(container.querySelectorAll('.sidebar-brand *'), { opacity: 0, y: 15, scale: 0.9 });
    tl.to(container.querySelectorAll('.sidebar-brand *'), { 
        opacity: 1, 
        y: 0, 
        scale: 1,
        duration: 0.8, 
        stagger: 0.1, 
        ease: "back.out(2)" 
    }, "-=1.2");

    // 3. Navigation Items staggered slide-up with subtle skew
    gsap.set(container.querySelectorAll('.gs-nav-item'), { opacity: 0, x: -30, skewX: -5 });
    tl.to(container.querySelectorAll('.gs-nav-item'), { 
        opacity: 1, 
        x: 0, 
        skewX: 0,
        duration: 0.8, 
        stagger: 0.05, 
        ease: "power3.out" 
    }, "-=1.0");

    // 4. Header elements drop in
    gsap.set(container.querySelectorAll('.gs-header-item'), { opacity: 0, y: -40, scale: 0.95 });
    tl.to(container.querySelectorAll('.gs-header-item'), { 
        opacity: 1, 
        y: 0, 
        scale: 1,
        duration: 1, 
        stagger: 0.08, 
        ease: "elastic.out(1, 0.7)" 
    }, "-=0.8");

    // 5. Main Content Area 3D tilt reveal
    gsap.set(container.querySelectorAll('.tab-content'), { opacity: 0, y: 60, rotationX: 15, transformPerspective: 1000 });
    tl.to(container.querySelectorAll('.tab-content'), { 
        opacity: 1, 
        y: 0, 
        rotationX: 0,
        duration: 1.5, 
        ease: "power4.out" 
    }, "-=1.0");

    // 6. Stats Cards & Inner Staggered Items (for the initial active tab)
    const staggers = container.querySelectorAll('.gs-stat-card, .gs-table-row, .stagger-item, tbody tr, .card, .glass-card, .movie-card-container');
    if (staggers.length) {
        gsap.set(staggers, { opacity: 0, y: 40, scale: 0.9, rotationX: -15, transformPerspective: 1000 });
        tl.to(staggers, {
            opacity: 1,
            y: 0,
            scale: 1,
            rotationX: 0,
            duration: 0.8,
            stagger: 0.05,
            ease: "back.out(1.5)"
        }, "-=1.2");
    }

    // Chart bars initially
    const chartBars = container.querySelectorAll('.chart-bar');
    if (chartBars.length) {
        gsap.set(chartBars, { scaleY: 0, transformOrigin: 'bottom' });
        tl.to(chartBars, {
            scaleY: 1,
            duration: 1,
            stagger: 0.05,
            ease: "power3.out"
        }, "-=0.8");
    }

    // 7. Ambient breathing for active gradients
    gsap.to(container.querySelectorAll('.icon-bounce'), {
        y: -3,
        duration: 1.5,
        yoyo: true,
        repeat: -1,
        ease: "sine.inOut"
    });
}

