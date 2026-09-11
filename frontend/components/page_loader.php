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
    body.is-loading {
        overflow: hidden !important;
    }
    @keyframes nexus-spin {
        to { transform: rotate(360deg); }
    }
    @keyframes nexus-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .45; }
    }
    @keyframes nexus-ping {
        75%, 100% { transform: scale(2); opacity: 0; }
    }
    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    #nexus-page-loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #030305;
        pointer-events: auto;
    }
    #nexus-page-loader .loader-panel {
        position: absolute;
        inset: 0;
        background: #030305;
        transform-origin: bottom center;
        z-index: 1;
    }
    #nexus-page-loader .loader-content {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #fff;
    }
    #nexus-page-loader .loader-content > .relative.w-32 {
        position: relative;
        width: 8rem;
        height: 8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2rem;
    }
    #nexus-page-loader .animate-spin {
        position: absolute;
        border-radius: 9999px;
        border-style: solid;
        border-width: 0;
        animation: nexus-spin 1s linear infinite;
    }
    #nexus-page-loader .border-t-2 {
        inset: 0;
        border-top-width: 2px;
        border-color: #ef4444;
        animation-duration: 1s;
    }
    #nexus-page-loader .border-r-2 {
        inset: 0.5rem;
        border-right-width: 2px;
        border-color: #6366f1;
        animation-duration: 1.5s;
        animation-direction: reverse;
    }
    #nexus-page-loader .border-b-2 {
        inset: 1rem;
        border-bottom-width: 2px;
        border-color: #10b981;
        animation-duration: 2s;
    }
    #nexus-page-loader .w-12.h-12 {
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        background: linear-gradient(to top right, #6366f1, #dc2626);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 30px rgba(239, 68, 68, 0.5);
        position: relative;
        z-index: 20;
        color: #fff;
    }
    #nexus-page-loader .bg-red-500\/20,
    #nexus-page-loader .mix-blend-screen {
        position: absolute;
        inset: 0;
        background: rgba(239, 68, 68, 0.2);
        filter: blur(40px);
        border-radius: 9999px;
        mix-blend-mode: screen;
        z-index: -1;
        animation: nexus-pulse 2s ease-in-out infinite;
    }
    #nexus-page-loader .animate-pulse {
        animation: nexus-pulse 2s ease-in-out infinite;
    }
    #nexus-page-loader h2 {
        font-size: 1.875rem;
        font-weight: 900;
        letter-spacing: 0.3em;
        text-transform: uppercase;
        color: transparent;
        background-image: linear-gradient(to right, #ef4444, #ffffff, #6366f1);
        -webkit-background-clip: text;
        background-clip: text;
        filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.3));
    }
    #nexus-page-loader .w-56 {
        width: 14rem;
        height: 3px;
        margin-top: 2rem;
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
        background: rgba(255, 255, 255, 0.1);
    }
    #nexus-page-loader .loader-progress {
        height: 100%;
        width: 100%;
        border-radius: 9999px;
        background-image: linear-gradient(to right, #ef4444, #6366f1, #ef4444);
        background-size: 200% auto;
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.8);
        animation: gradientMove 3s linear infinite;
    }
    #nexus-page-loader .mt-4 {
        margin-top: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #nexus-page-loader .animate-ping {
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 9999px;
        background: #10b981;
        animation: nexus-ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    #nexus-page-loader .loader-status {
        font-size: 10px;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.2em;
    }
</style>

<script>
    if (document.body && !document.body.classList.contains('is-loading')) {
        document.body.classList.add('is-loading');
    }

    window.showPageLoader = function(onComplete) {
        const loader = document.getElementById('nexus-page-loader');
        const panels = document.querySelectorAll('#nexus-page-loader .loader-panel');
        const content = document.querySelector('#nexus-page-loader .loader-content');
        
        if (!loader) {
            if (onComplete) onComplete();
            return;
        }

        loader.style.display = 'flex';
        loader.style.opacity = '1';
        loader.style.pointerEvents = 'auto';
        document.body.classList.add('is-loading');

        if (typeof gsap !== 'undefined' && panels.length) {
            gsap.config({ nullTargetWarn: false });
            gsap.set(panels, { scaleY: 1 });
            if (content) gsap.set(content, { opacity: 1, scale: 1, filter: 'blur(0px)' });
            const tl = gsap.timeline({ onComplete: onComplete });
            tl.fromTo(panels, { scaleY: 0 }, {
                scaleY: 1,
                duration: 0.5,
                ease: "expo.inOut",
                stagger: 0.1
            });
            if (content) {
                tl.to(content, {
                    opacity: 1,
                    scale: 1,
                    filter: 'blur(0px)',
                    duration: 0.4,
                    ease: "power2.out"
                }, "-=0.2");
            }
            return tl;
        }

        if (onComplete) onComplete();
    };

    window.hidePageLoader = function(onComplete) {
        const loader = document.getElementById('nexus-page-loader');
        const panels = document.querySelectorAll('#nexus-page-loader .loader-panel');
        const content = document.querySelector('#nexus-page-loader .loader-content');
        
        if (!loader) {
            if (onComplete) onComplete();
            return;
        }

        const finish = () => {
            document.body.classList.remove('is-loading');
            loader.style.pointerEvents = 'none';
            loader.style.display = 'none';
            if (onComplete) onComplete();
        };

        if (typeof gsap !== 'undefined') {
            gsap.config({ nullTargetWarn: false });
            const enterTl = gsap.timeline({ onComplete: finish });
            if (content) {
                enterTl.to(content, {
                    opacity: 0,
                    scale: 1.1,
                    filter: 'blur(10px)',
                    duration: 0.5,
                    ease: "power2.inOut",
                    delay: 0.2
                });
            }
            if (panels.length) {
                enterTl.to(panels, {
                    scaleY: 0,
                    duration: 0.8,
                    ease: "expo.inOut",
                    stagger: 0.1
                }, content ? "-=0.3" : 0);
            }
            if (!content && !panels.length) finish();
            return enterTl;
        }

        finish();
    };

    // Initial page load
    document.addEventListener('DOMContentLoaded', () => {
        // Just hide it initially since it's visible by default in HTML
        window.hidePageLoader();
        setTimeout(() => {
            const loader = document.getElementById('nexus-page-loader');
            if (!loader) return;
            document.body.classList.remove('is-loading');
            loader.style.pointerEvents = 'none';
            if (loader.style.display !== 'none' && loader.getAttribute('data-force-hidden') !== '1') {
                const stillVisible = window.getComputedStyle(loader).opacity !== '0';
                if (stillVisible) {
                    loader.style.display = 'none';
                }
            }
        }, 4000);
    });
</script>
