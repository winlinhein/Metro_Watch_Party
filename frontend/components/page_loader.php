<script src="/js/home_page.js?v=7"></script>
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
    if(!document.body.classList.contains('is-loading')) {
        document.body.classList.add('is-loading');
    }

    window.showPageLoader = function(onComplete) {
        const loader = document.getElementById('nexus-page-loader');
        const panels = document.querySelectorAll('.loader-panel');
        const content = document.querySelector('.loader-content');
        
        if (!loader) {
            if (onComplete) onComplete();
            return;
        }

        loader.style.display = 'flex';
        loader.style.pointerEvents = 'auto';
        document.body.classList.add('is-loading');

        if (typeof gsap !== 'undefined') {
            const tl = gsap.timeline({ onComplete: onComplete });
            tl.to(panels, {
                scaleY: 1,
                duration: 0.5,
                ease: "expo.inOut",
                stagger: 0.1
            })
            .to(content, {
                opacity: 1,
                scale: 1,
                filter: 'blur(0px)',
                duration: 0.4,
                ease: "power2.out"
            }, "-=0.2");
            return tl;
        } else {
            loader.style.opacity = '1';
            if(onComplete) onComplete();
        }
    };

    window.hidePageLoader = function(onComplete) {
        const loader = document.getElementById('nexus-page-loader');
        const panels = document.querySelectorAll('.loader-panel');
        const content = document.querySelector('.loader-content');
        
        if (!loader) {
            if (onComplete) onComplete();
            return;
        }

        if (typeof gsap !== 'undefined') {
            gsap.config({ nullTargetWarn: false });
            const enterTl = gsap.timeline({
                onComplete: () => {
                    document.body.classList.remove('is-loading');
                    loader.style.pointerEvents = 'none';
                    if (onComplete) onComplete();
                }
            });
            
            enterTl.to(content, {
                opacity: 0,
                scale: 1.1,
                filter: 'blur(10px)',
                duration: 0.5,
                ease: "power2.inOut",
                delay: 0.2
            })
            .to(panels, {
                scaleY: 0,
                duration: 0.8,
                ease: "expo.inOut",
                stagger: 0.1
            }, "-=0.3");
            return enterTl;
        } else {
            loader.style.display = 'none';
            document.body.classList.remove('is-loading');
            if(onComplete) onComplete();
        }
    };

    // Initial page load
    document.addEventListener('DOMContentLoaded', () => {
        // Just hide it initially since it's visible by default in HTML
        window.hidePageLoader();
    });
</script>
