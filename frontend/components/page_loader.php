<!-- Insane Page Loader -->
<div id="nexus-page-loader" class="fixed inset-0 z-[99999] pointer-events-auto flex items-center justify-center overflow-hidden">
    <!-- Animated background panels -->
    <div class="loader-panel absolute inset-0 bg-[#030305] origin-bottom scale-y-100" style="z-index: 1;"></div>
    
    <div class="loader-content relative z-10 flex flex-col items-center justify-center opacity-100 scale-100">
        <!-- Insane glowing orb loader -->
        <div class="relative w-32 h-32 flex items-center justify-center mb-8">
            <div class="absolute inset-0 rounded-full border-t-2 border-red-500 animate-spin" style="animation-duration: 1s;"></div>
            <div class="absolute inset-2 rounded-full border-r-2 border-indigo-500 animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
            <div class="absolute inset-4 rounded-full border-b-2 border-emerald-500 animate-spin" style="animation-duration: 2s;"></div>
            
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-red-600 flex items-center justify-center shadow-[0_0_30px_rgba(239,68,68,0.5)] relative z-20">
                <span class="material-symbols-outlined text-white text-2xl animate-pulse">movie</span>
            </div>
            
            <!-- Glow effect behind -->
            <div class="absolute inset-0 bg-red-500/20 blur-[40px] rounded-full mix-blend-screen -z-10 animate-pulse"></div>
        </div>
        
        <h2 class="text-3xl font-black tracking-[0.3em] text-transparent bg-clip-text bg-gradient-to-r from-red-500 via-white to-indigo-500 uppercase mono drop-shadow-[0_0_15px_rgba(255,255,255,0.3)]">Nexus</h2>
        
        <div class="w-56 h-[3px] bg-white/10 mt-8 rounded-full overflow-hidden relative">
            <div class="loader-progress h-full bg-gradient-to-r from-red-500 via-indigo-500 to-red-500 w-full rounded-full shadow-[0_0_15px_rgba(239,68,68,0.8)] bg-[length:200%_auto] animate-[gradientMove_3s_linear_infinite]"></div>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
            <p class="text-[10px] text-white/50 mono loader-status uppercase tracking-[0.2em]">Connection Established</p>
        </div>
    </div>
</div>

<style>
    /* Ensure body doesn't scroll while loading */
    body.is-loading {
        overflow: hidden !important;
    }
</style>

<script>
    // Add is-loading class immediately to prevent scrolling during load
    document.body.classList.add('is-loading');

    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('nexus-page-loader');
        const panels = document.querySelectorAll('.loader-panel');
        const content = document.querySelector('.loader-content');
        const progress = document.querySelector('.loader-progress');
        const statusText = document.querySelector('.loader-status');
        
        if (typeof gsap !== 'undefined') { gsap.config({ nullTargetWarn: false });
            // --- PAGE ENTER ANIMATION ---
            const enterTl = gsap.timeline({
                onComplete: () => {
                    document.body.classList.remove('is-loading');
                    loader.style.pointerEvents = 'none';
                }
            });
            
            // Keep it visible for a split second to feel the effect, then dismiss
            enterTl.to(content, {
                opacity: 0,
                scale: 1.1,
                filter: 'blur(10px)',
                duration: 0.5,
                ease: "power2.inOut",
                delay: 0.2 // slight delay for dramatic effect
            })
            .to(panels, {
                scaleY: 0,
                duration: 0.8,
                ease: "expo.inOut",
                stagger: 0.1
            }, "-=0.3");

            // --- PAGE EXIT INTERCEPTOR ---
            const links = document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"]):not([onclick])');
            
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    const targetUrl = link.getAttribute('href');
                    
                    // Only intercept if we have GSAP and it's not an anchor on same page
                    if (targetUrl && targetUrl !== '#' && !targetUrl.startsWith('#')) {
                        e.preventDefault();
                        
                        document.body.classList.add('is-loading');
                        loader.style.pointerEvents = 'auto'; // Block clicks
                        
                        const tl = gsap.timeline({
                            onComplete: () => {
                                window.location.href = targetUrl;
                            }
                        });
                        
                        // Setup origin for exit
                        gsap.set(panels, { transformOrigin: 'top', scaleY: 0 });
                        gsap.set(content, { opacity: 0, scale: 0.8, filter: 'blur(10px)' });
                        gsap.set(progress, { width: '0%' });
                        statusText.innerText = "Loading...";
                        
                        // Background scale up
                        tl.to(panels, {
                            scaleY: 1,
                            duration: 0.6,
                            ease: "expo.inOut",
                            stagger: 0.1
                        })
                        // Reveal content
                        .to(content, {
                            opacity: 1,
                            scale: 1,
                            filter: 'blur(0px)',
                            duration: 0.5,
                            ease: "back.out(1.5)"
                        }, "-=0.2")
                        // Fake progress bar loading
                        .to(progress, {
                            width: '100%',
                            duration: 0.8,
                            ease: "power2.inOut",
                            onComplete: () => {
                                statusText.innerText = "Connection Established";
                            }
                        });
                    }
                });
            });
        } else {
            // Fallback if GSAP is not loaded
            loader.style.display = 'none';
            document.body.classList.remove('is-loading');
        }
    });
</script>
