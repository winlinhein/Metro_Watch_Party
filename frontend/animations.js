document.addEventListener('DOMContentLoaded', () => {
    // Generate Posters
    const posterWall = document.getElementById('poster-wall-container');
    if (posterWall) {
        let html = '';
        for (let i = 0; i < 20; i++) {
            const dir = i % 2 === 0 ? 'up' : 'down';
            const duration = 50 + (Math.random() * 30);
            let posters = '';
            for (let j = 0; j < 30; j++) {
                posters += `
                    <div class="poster">
                        <span class="material-symbols-outlined text-white/20 text-6xl">movie</span>
                    </div>
                `;
            }
            html += `
                <div class="poster-col ${dir}" style="animation-duration: ${duration}s;">
                    ${posters}
                </div>
            `;
        }
        posterWall.innerHTML = html;
    }

    // Particle Generation
    const particlesContainer = document.getElementById('particles-container');
    const numParticles = 40;
    
    for (let i = 0; i < numParticles; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Randomize properties
        const size = Math.random() * 3 + 1;
        const opacity = Math.random() * 0.5 + 0.1;
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.opacity = opacity;
        particle.style.left = `${x}%`;
        particle.style.top = `${y}%`;
        
        particlesContainer.appendChild(particle);
        
        // Animate each particle independently
        gsap.to(particle, {
            y: `-=${Math.random() * 100 + 50}`,
            x: `+=${Math.random() * 40 - 20}`,
            rotation: Math.random() * 360,
            opacity: 0,
            duration: Math.random() * 10 + 5,
            repeat: -1,
            ease: 'none',
            delay: Math.random() * -10
        });
    }
    
    // Main Timeline
    const tl = gsap.timeline({ defaults: { ease: 'power4.out' } });

    // Interactive particles
    const particles = document.querySelectorAll('.particle');
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX;
        const mouseY = e.clientY;
        
        particles.forEach(particle => {
            const rect = particle.getBoundingClientRect();
            const partX = rect.left + rect.width / 2;
            const partY = rect.top + rect.height / 2;
            
            const distX = mouseX - partX;
            const distY = mouseY - partY;
            const distance = Math.sqrt(distX * distX + distY * distY);
            
            if (distance < 150) {
                gsap.to(particle, {
                    x: `-=${distX * 0.2}`,
                    y: `-=${distY * 0.2}`,
                    duration: 0.5,
                    ease: 'power2.out'
                });
            }
        });
    });

    // Animate background blobs with complex motion
    gsap.to('#blob1', {
        x: '15vw',
        y: '15vh',
        scale: 1.2,
        duration: 25,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut'
    });
    
    gsap.to('#blob2', {
        x: '-15vw',
        y: '-15vh',
        scale: 1.3,
        duration: 28,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut'
    });
    
    // Split text animation for NEXUS (Index page only)
    const nexusText = document.querySelector('.nexus-text');
    const isRegisterPage = document.querySelector('.ultimate-reveal') !== null;
    
    if (nexusText && !isRegisterPage) {
        const chars = nexusText.textContent.split('');
        nexusText.innerHTML = '';
        chars.forEach(char => {
            const span = document.createElement('span');
            span.textContent = char;
            span.style.display = 'inline-block';
            nexusText.appendChild(span);
        });

        // Advanced Reveal sequence
        tl.from('.nexus-text span', {
            y: 50,
            opacity: 0,
            rotationX: -90,
            stagger: 0.05,
            duration: 1,
            ease: 'back.out(1.7)'
        })
        .from('.gs-reveal > p, .gs-reveal > .w-10', {
            y: 20,
            opacity: 0,
            duration: 0.8,
            stagger: 0.1
        }, "-=0.6")
        .fromTo('#glass-card', 
            { y: 60, opacity: 0, rotationX: 10, scale: 0.95 },
            { y: 0, opacity: 1, rotationX: 0, scale: 1, duration: 1.2, ease: 'expo.out' },
            "-=0.6"
        )
        .fromTo('.gs-stagger',
            { y: 20, opacity: 0, x: -10 },
            { y: 0, opacity: 1, x: 0, duration: 0.6, stagger: 0.08, ease: 'back.out(1.2)' },
            "-=0.8"
        )
        .fromTo('.gs-footer', 
            { opacity: 0, y: 10 },
            { opacity: 1, y: 0, duration: 0.5 },
            "-=0.4"
        );
    }

    // Smooth Floating & Parallax Effect
    const card = document.getElementById('glass-card');
    const container = document.getElementById('main-container');
    
    if (container && card) {
        container.addEventListener('mouseenter', () => {
            gsap.to(card, {
                y: -10,
                scale: 1.02,
                boxShadow: '0 40px 80px -12px rgba(220, 38, 38, 0.2), inset 0 1px 0 rgba(255,255,255,0.2)',
                borderColor: 'rgba(255, 255, 255, 0.2)',
                duration: 0.6,
                ease: 'power3.out'
            });
        });
        
        container.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Subtle content shift (parallax)
            gsap.to('.glass-card > *', {
                x: (x - rect.width / 2) * 0.03,
                y: (y - rect.height / 2) * 0.03,
                duration: 0.5,
                ease: 'power2.out'
            });
        });
        
        container.addEventListener('mouseleave', () => {
            gsap.to(card, {
                y: 0,
                scale: 1,
                boxShadow: '0 30px 60px -12px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255,255,255,0.1)',
                borderColor: 'rgba(255, 255, 255, 0.08)',
                duration: 0.8,
                ease: 'elastic.out(1, 0.5)'
            });
            
            gsap.to('.glass-card > *', {
                x: 0,
                y: 0,
                duration: 0.8,
                ease: 'elastic.out(1, 0.5)'
            });
        });
    }

    // Input field focus animations
    const inputFields = document.querySelectorAll('.input-field');
    inputFields.forEach(input => {
        const label = input.nextElementSibling;
        const icon = input.nextElementSibling.nextElementSibling;
        
        input.addEventListener('focus', () => {
            gsap.to(input, {
                borderColor: 'rgba(239, 68, 68, 0.8)',
                boxShadow: '0 0 25px rgba(239, 68, 68, 0.2)',
                duration: 0.3,
                ease: 'power2.out'
            });
            if(icon) {
                gsap.to(icon, {
                    scale: 1.2,
                    color: 'rgba(239, 68, 68, 1)',
                    duration: 0.3,
                    ease: 'back.out(2)'
                });
            }
        });
        
        input.addEventListener('blur', () => {
            gsap.to(input, {
                borderColor: 'rgba(255, 255, 255, 0.1)',
                boxShadow: 'none',
                duration: 0.3,
                ease: 'power2.out'
            });
            if(icon) {
                gsap.to(icon, {
                    scale: 1,
                    color: 'rgba(255, 255, 255, 0.3)',
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        });
    });

    // Social button hover animations
    const socialBtns = document.querySelectorAll('.grid.grid-cols-2 button');
    socialBtns.forEach(btn => {
        const icon = btn.querySelector('svg');
        btn.addEventListener('mouseenter', () => {
            gsap.to(btn, {
                y: -3,
                scale: 1.02,
                duration: 0.3,
                ease: 'power2.out'
            });
            gsap.to(icon, {
                rotation: 15,
                scale: 1.2,
                duration: 0.3,
                ease: 'back.out(2)'
            });
        });
        
        btn.addEventListener('mouseleave', () => {
            gsap.to(btn, {
                y: 0,
                scale: 1,
                duration: 0.3,
                ease: 'power2.out'
            });
            gsap.to(icon, {
                rotation: 0,
                scale: 1,
                duration: 0.3,
                ease: 'power2.out'
            });
        });
    });

    // Button hover effect
    const submitBtn = document.getElementById('submitBtn');
    const ripple = document.getElementById('btnRipple');
    const btnIcon = submitBtn ? submitBtn.querySelector('span.material-symbols-outlined') : null;

    if (submitBtn && !isRegisterPage) {
        submitBtn.addEventListener('mouseenter', (e) => {
            if (ripple) gsap.to(ripple, { scale: 1.5, opacity: 1, duration: 0.4, ease: 'power2.out' });
            if (btnIcon) gsap.to(btnIcon, { x: 5, duration: 0.3, ease: 'back.out(2)' });
        });
        
        submitBtn.addEventListener('mouseleave', () => {
            if (ripple) gsap.to(ripple, { scale: 0, opacity: 0, duration: 0.4 });
            if (btnIcon) gsap.to(btnIcon, { x: 0, duration: 0.3, ease: 'power2.out' });
        });
        
        // Button click animation
        submitBtn.addEventListener('mousedown', () => {
            gsap.to(submitBtn, { scale: 0.95, duration: 0.1, ease: 'power2.inOut' });
        });
        
        submitBtn.addEventListener('mouseup', () => {
            gsap.to(submitBtn, { scale: 1, duration: 0.4, ease: 'elastic.out(1, 0.3)' });
        });
    }
    // Next-Level Magnetic Back Button
    const backBtn = document.querySelector(".gs-back-btn");
    const backHit = document.querySelector(".gs-back-hit");
    const backRing = document.querySelector(".gs-back-ring");
    const backIcon = document.querySelector(".gs-back-icon");
    
    if (backBtn && backHit) {
        // Initial entrance
        gsap.fromTo(backBtn, 
            { x: -50, opacity: 0, scale: 0 }, 
            { x: 0, opacity: 1, scale: 1, duration: 1.5, ease: "elastic.out(1, 0.4)", delay: 0.3 }
        );

        let hoverTween = gsap.to(backRing, { rotation: 360, duration: 4, repeat: -1, ease: "linear", paused: true });

        backHit.addEventListener("mousemove", (e) => {
            const rect = backHit.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            // Move the button itself
            gsap.to(backBtn, {
                x: x * 0.4,
                y: y * 0.4,
                scale: 1.1,
                duration: 0.4,
                ease: "power3.out",
                boxShadow: "0 10px 30px rgba(239, 68, 68, 0.3)",
                borderColor: "rgba(239, 68, 68, 0.5)"
            });
            
            // Move icon slightly more for parallax
            if (backIcon) {
                gsap.to(backIcon, {
                    x: x * 0.3,
                    y: y * 0.3,
                    color: "#fff",
                    duration: 0.3,
                    ease: "power2.out"
                });
            }
            
            if (backRing) {
                gsap.to(backRing, { opacity: 1, duration: 0.3 });
                hoverTween.play();
            }
        });

        backHit.addEventListener("mouseleave", () => {
            gsap.to(backBtn, {
                x: 0,
                y: 0,
                scale: 1,
                duration: 0.8,
                ease: "elastic.out(1, 0.4)",
                boxShadow: "0 0 0 transparent",
                borderColor: "rgba(255, 255, 255, 0.1)"
            });
            
            if (backIcon) {
                gsap.to(backIcon, {
                    x: 0,
                    y: 0,
                    color: "rgba(255, 255, 255, 0.6)",
                    duration: 0.8,
                    ease: "elastic.out(1, 0.4)"
                });
            }
            
            if (backRing) {
                gsap.to(backRing, { opacity: 0, duration: 0.5, onComplete: () => hoverTween.pause() });
            }
        });
        
        backBtn.addEventListener("mousedown", () => {
            gsap.to(backBtn, { scale: 0.9, duration: 0.15, ease: "power2.inOut" });
            if (backIcon) gsap.to(backIcon, { scale: 0.8, duration: 0.15 });
        });

        backBtn.addEventListener("mouseup", () => {
            gsap.to(backBtn, { scale: 1.1, duration: 0.4, ease: "elastic.out(1, 0.4)" });
            if (backIcon) gsap.to(backIcon, { scale: 1, duration: 0.4 });
        });
    }
});
