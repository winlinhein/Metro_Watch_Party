<?php // frontend/components/toast.php ?>
<style>
    #nexus-toast-container {
        --toast-accent: var(--nx-glow, 239 68 68);
    }
    #nexus-toast.nexus-toast-error {
        box-shadow: 0 15px 50px rgb(var(--toast-accent) / 0.22);
    }
    #nexus-toast.nexus-toast-error #toast-bg-glow {
        background-image: linear-gradient(to top right, rgb(var(--toast-accent) / 0.45), rgb(var(--toast-accent) / 0.08), transparent);
    }
    #nexus-toast.nexus-toast-error #toast-icon-wrapper {
        background: rgb(var(--toast-accent) / 0.12);
        border-color: rgb(var(--toast-accent) / 0.45);
        box-shadow: 0 0 20px rgb(var(--toast-accent) / 0.45);
    }
    #nexus-toast.nexus-toast-error #toast-icon-bg {
        background: rgb(var(--toast-accent) / 0.22);
    }
    #nexus-toast.nexus-toast-error #toast-icon {
        color: rgb(var(--toast-accent)) !important;
        background: none !important;
        -webkit-text-fill-color: rgb(var(--toast-accent));
        filter: drop-shadow(0 0 10px rgb(var(--toast-accent)));
    }
    #nexus-toast.nexus-toast-error #toast-divider {
        background-image: linear-gradient(to right, rgb(var(--toast-accent) / 0.75), transparent);
    }
    #nexus-toast.nexus-toast-error #nexus-toast-progress {
        background: rgb(var(--toast-accent));
        box-shadow: 0 0 15px rgb(var(--toast-accent));
    }
    #nexus-message-toasts {
        position: fixed;
        top: 5.25rem;
        right: 0;
        z-index: 9998;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        width: max-content;
        max-width: 100%;
        pointer-events: none;
    }
    .nexus-msg-toast {
        pointer-events: auto;
        width: 210px;
        max-width: 68vw;
        margin: 0;
        padding: 8px 14px 8px 12px;
        border: 1px solid rgb(var(--nx-glow, 239 68 68) / 0.4);
        border-right: 0;
        border-radius: 14px 0 0 14px;
        background: rgb(var(--nx-surface-rgb, 5 5 8) / 0.94);
        box-shadow: -10px 8px 24px rgba(0, 0, 0, 0.4);
        color: #fff;
        text-align: left;
        cursor: pointer;
        overflow: hidden;
    }
    .nexus-msg-toast-name {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
        color: rgb(var(--nx-glow, 239 68 68));
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nexus-msg-toast-text {
        margin-top: 2px;
        font-size: 12px;
        line-height: 1.3;
        color: rgba(255, 255, 255, 0.72);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
<div id="nexus-message-toasts" aria-live="polite"></div>
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
    window.showToast = function(toastMessage, toastType) {
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

            // Check that essential elements exist before manipulating classList
            if (!container || !toast) return;

            container.classList.remove('hidden');
            if (msg) msg.textContent = toastMessage;

            // Reset existing dynamic classes safely
            toast.className = "pointer-events-auto relative bg-[#050505]/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_30px_60px_rgba(0,0,0,0.9)] overflow-hidden flex flex-col p-0 opacity-0 transform translate-y-[150px] rotate-x-[-30deg] rotate-y-[15deg] scale-90";

            if (toastType === 'error') {
                toast.classList.add('nexus-toast-error');
                if (icon) icon.textContent = 'error';
                if (title) title.textContent = 'System Error';
            } else {
                toast.classList.add('shadow-[0_15px_50px_rgba(34,197,94,0.2)]');
                if (bgGlow) bgGlow.classList.add('from-green-500/40', 'via-green-900/5');
                if (iconWrapper) iconWrapper.classList.add('bg-green-500/10', 'border-green-500/40', 'shadow-[0_0_20px_rgba(34,197,94,0.5)]');
                if (iconBg) iconBg.classList.add('bg-green-500/20');
                if (icon) {
                    icon.classList.add('text-green-500', 'drop-shadow-[0_0_12px_rgba(34,197,94,1)]');
                    icon.textContent = 'check_circle';
                }
                if (title) title.textContent = 'Success';
                if (divider) divider.classList.add('from-green-500/50');
                if (progressBar) progressBar.classList.add('bg-green-500', 'shadow-[0_0_15px_rgba(34,197,94,1)]');
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
                            if (container) container.classList.add('hidden');
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
    };

    window.showMessageToast = function (payload) {
        const data = payload && typeof payload === 'object' ? payload : { text: payload };
        const name = String(data.name || 'New message').trim() || 'New message';
        const text = String(data.text || 'Sent a message').trim() || 'Sent a message';
        let stack = document.getElementById('nexus-message-toasts');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'nexus-message-toasts';
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }

        while (stack.children.length >= 3) {
            stack.removeChild(stack.firstElementChild);
        }

        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'nexus-msg-toast';
        const nameEl = document.createElement('div');
        nameEl.className = 'nexus-msg-toast-name';
        nameEl.textContent = name;
        const textEl = document.createElement('div');
        textEl.className = 'nexus-msg-toast-text';
        textEl.textContent = text;
        card.appendChild(nameEl);
        card.appendChild(textEl);
        stack.appendChild(card);

        let closed = false;
        const closeCard = () => {
            if (closed) return;
            closed = true;
            const removeCard = () => { if (card.parentNode) card.parentNode.removeChild(card); };
            if (typeof gsap !== 'undefined') {
                gsap.to(card, { x: '110%', opacity: 0, duration: 0.28, ease: 'power2.in', onComplete: removeCard });
            } else {
                removeCard();
            }
        };

        card.addEventListener('click', () => {
            if (typeof data.onClick === 'function') data.onClick();
            closeCard();
        });

        if (typeof gsap !== 'undefined') {
            gsap.fromTo(card, { x: '110%', opacity: 0 }, { x: 0, opacity: 1, duration: 0.38, ease: 'power3.out' });
        }
        setTimeout(closeCard, 4500);
    };
</script>
