<?php // frontend/components/toast.php ?>
<div id="nexus-toast-container" class="fixed bottom-10 right-10 z-[9999] p-0 w-full max-w-[380px] pointer-events-none hidden" style="perspective: 1200px;">
    <div id="nexus-toast" class="pointer-events-auto relative bg-[#050505]/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_30px_60px_rgba(0,0,0,0.9)] overflow-hidden flex flex-col p-0 opacity-0 transform translate-y-[150px] rotate-x-[-30deg] rotate-y-[15deg] scale-90">
        
        <!-- Animated Background Glow -->
        <div class="absolute inset-0 opacity-20 bg-gradient-to-tr to-transparent pointer-events-none" id="toast-bg-glow"></div>
        
        <div class="relative flex items-start p-5 gap-4">
            <div class="relative flex-shrink-0 mt-1" id="toast-icon-container">
                <div id="toast-icon-wrapper" class="relative w-12 h-12 rounded-2xl border flex items-center justify-center overflow-hidden">
                    <div id="toast-icon-bg" class="absolute inset-0 animate-pulse"></div>
                    <span id="toast-icon" class="material-symbols-outlined text-[28px] relative z-10">error</span>
                </div>
            </div>
            
            <div class="relative flex-1 py-1">
                <div class="flex items-center justify-between mb-1.5 overflow-hidden">
                    <h4 class="text-white font-black text-[11px] uppercase tracking-[0.25em] flex items-center gap-2 m-0" id="toast-title">
                        System Error
                    </h4>
                </div>
                <div class="h-[1px] w-full bg-gradient-to-r to-transparent mb-2.5 origin-left" id="toast-divider"></div>
                <div class="overflow-hidden">
                    <p class="text-white/80 text-[13px] leading-relaxed font-medium m-0" id="toast-msg"></p>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar (Line Counter from below) -->
        <div class="relative h-[3px] w-full bg-white/5">
            <div class="absolute top-0 left-0 h-full origin-left w-full" id="nexus-toast-progress"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        let toastMessage = '';
        let toastType = '';
        
        if (urlParams.has('error')) {
            toastMessage = urlParams.get('error');
            toastType = 'error';
        } else if (urlParams.has('success') || urlParams.has('message')) {
            toastMessage = urlParams.get('success') || urlParams.get('message');
            toastType = 'success';
        }
        
        if (toastMessage) {
            const container = document.getElementById('nexus-toast-container');
            const toast = document.getElementById('nexus-toast');
            const bgGlow = document.getElementById('toast-bg-glow');
            const iconWrapper = document.getElementById('toast-icon-wrapper');
            const iconBg = document.getElementById('toast-icon-bg');
            const icon = document.getElementById('toast-icon');
            const title = document.getElementById('toast-title');
            const divider = document.getElementById('toast-divider');
            const msg = document.getElementById('toast-msg');
            const progressBar = document.getElementById('nexus-toast-progress');
            
            container.classList.remove('hidden');
            msg.textContent = toastMessage;
            
            if (toastType === 'error') {
                toast.classList.add('shadow-[0_15px_50px_rgba(239,68,68,0.2)]');
                bgGlow.classList.add('from-red-600/40', 'via-red-900/5');
                iconWrapper.classList.add('bg-red-500/10', 'border-red-500/40', 'shadow-[0_0_20px_rgba(239,68,68,0.5)]');
                iconBg.classList.add('bg-red-500/20');
                icon.classList.add('text-red-500', 'drop-shadow-[0_0_12px_rgba(239,68,68,1)]');
                icon.textContent = 'error';
                title.textContent = 'System Error';
                divider.classList.add('from-red-500/50');
                progressBar.classList.add('bg-red-500', 'shadow-[0_0_15px_rgba(239,68,68,1)]');
            } else {
                toast.classList.add('shadow-[0_15px_50px_rgba(34,197,94,0.2)]');
                bgGlow.classList.add('from-green-500/40', 'via-green-900/5');
                iconWrapper.classList.add('bg-green-500/10', 'border-green-500/40', 'shadow-[0_0_20px_rgba(34,197,94,0.5)]');
                iconBg.classList.add('bg-green-500/20');
                icon.classList.add('text-green-500', 'drop-shadow-[0_0_12px_rgba(34,197,94,1)]');
                icon.textContent = 'check_circle';
                title.textContent = 'Success';
                divider.classList.add('from-green-500/50');
                progressBar.classList.add('bg-green-500', 'shadow-[0_0_15px_rgba(34,197,94,1)]');
            }

            if (typeof gsap !== 'undefined') {
                const tl = gsap.timeline();
                
                // Initial states for intense entrance
                gsap.set(toast, { y: 100, opacity: 0, rotateX: 30, rotateY: -20, scale: 0.85, transformOrigin: "50% 100%" });
                gsap.set('#toast-icon-container', { scale: 0, rotation: -180 });
                gsap.set(title, { y: 20, opacity: 0 });
                
                gsap.set(divider, { scaleX: 0 });
                gsap.set(msg, { y: 20, opacity: 0 });
                
                // Insane 3D Entrance Sequence
                tl.to(toast, { 
                     y: 0, 
                     opacity: 1, 
                     rotateX: 0, 
                     rotateY: 0, 
                     scale: 1,
                    duration: 1.2, 
                     ease: "expo.out",
                    delay: 0.1
                })
                .to('#toast-icon-container', {
                    scale: 1,
                    rotation: 0,
                    duration: 0.8,
                    ease: "back.out(2.5)"
                }, "-=0.9")
                .to(title, {
                    y: 0,
                    opacity: 1,
                    duration: 0.6,
                    ease: "back.out(1.5)"
                }, "-=0.7")
                
                .to(divider, {
                    scaleX: 1,
                    duration: 0.8,
                    ease: "expo.out"
                }, "-=0.5")
                .to(msg, {
                    y: 0,
                    opacity: 1,
                    duration: 0.6,
                    ease: "power2.out"
                }, "-=0.5");

                // Continuous background glow animation
                gsap.to(bgGlow, {
                    opacity: 0.5,
                    duration: 2,
                    yoyo: true,
                    repeat: -1,
                    ease: "sine.inOut"
                });
                
                // Floating effect on the icon
                gsap.to('#toast-icon-container', {
                    y: -3,
                    duration: 1.5,
                    yoyo: true,
                    repeat: -1,
                    ease: "sine.inOut",
                    delay: 1.2
                });
                
                // Progress bar animation (countdown)
                const displayDuration = 6; // seconds
                
                gsap.fromTo(progressBar, 
                     { scaleX: 1 }, 
                     { scaleX: 0, duration: displayDuration, ease: "linear", delay: 1.2 }
                );

                // Auto dismiss animation
                const closeToast = () => {
                    const exitTl = gsap.timeline({
                        onComplete: () => {
                            if(container) container.remove();
                        }
                    });
                    
                    exitTl.to(msg, { y: 20, opacity: 0, duration: 0.3, ease: "power2.in" })
                          .to(divider, { scaleX: 0, duration: 0.3, ease: "power2.in" }, "-=0.2")
                          .to(title, { y: 20, opacity: 0, duration: 0.3, ease: "power2.in" }, "-=0.2")
                          .to('#toast-icon-container', { scale: 0.5, opacity: 0, duration: 0.3, ease: "back.in(2)" }, "-=0.2")
                          .to(toast, {
                              y: 80,
                              opacity: 0,
                              rotateX: 30,
                              scale: 0.9,
                              duration: 0.5,
                              ease: "power3.in"
                          }, "-=0.1");
                };

                // Auto dismiss after duration + entrance animations
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        closeToast();
                    }
                }, (displayDuration + 1.2) * 1000);
            }
        }
    });
</script>
