// Mika's QA-infowallet
// Author: Mika Fekadu
// Website: https://www.mikafekadu.com
// Email: i@mikafekadu.com
// Copyright (c) 2025 Mika Fekadu. All rights reserved.

// viewinfo.js (Concise Refactor with Slower Animations & Microinteractions)
document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const HOLD_DURATION = 2000; // Duration in ms for hold-to-copy
    const DEFAULT_ICON = 'assets/icons/mika_logo.svg'; // Fallback icon path
    const QR_CODE_SIZE = 220; // Pixels for QR code dimensions
    const DATA_FILE_PATH = 'data.json'; // Path to your data file (used in fetch)

    // --- Animation Durations (Adjust for speed) ---
    const VIEW_TRANSITION_DURATION = 0.8; // Was 0.45 - Slower main view fade
    const CARD_STAGGER_DURATION = 0.9; // Was 0.6 - Slower card entrance
    const CARD_STAGGER_AMOUNT = 0.8; // Was 0.6 - Adjust stagger timing
    const MICRO_INTERACTION_DURATION = 0.2; // For hover, focus, clicks
    const BUTTON_CLICK_DURATION = 0.1; // Quick feedback for button clicks
    const INITIAL_FADE_DURATION = 1.0; // Used for initial app fade

    // --- DOM References ---
    const body = document.body;
    const themeToggle = document.getElementById('theme-toggle');
    const mainContainer = document.querySelector('.main-container'); // Used in initialization
    const categorySwiperView = document.getElementById('category-swiper-view');
    const categorySwiperWrapper = document.getElementById('category-swiper-wrapper');
    const cardListViewsContainer = document.getElementById('card-list-views-container'); // Used for card list views

    // --- State Variables ---
    let jsonData = null;
    let categorySwiper = null;
    let holdTimeout = null;
    let holdGsapTweenGlow = null;
    let holdGsapTweenIndicator = null;
    let currentVisibleCardList = null;
    let isTransitioning = false;

    // --- Define SVG Icons (Ensure paths are correct) ---
    const ICONS = {
        copy: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375c-3.181 0-5.904-.84-8.25-2.25M10.5 18.75v-4.875c0-.621.504-1.125 1.125-1.125h3.375c.621 0 1.125.504 1.125 1.125V18.75m-7.5 2.25h9.75" /></svg>`,
        qr: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 14.625a1.125 1.125 0 011.125-1.125h4.5a1.125 1.125 0 011.125 1.125v4.5a1.125 1.125 0 01-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" /></svg>`,
        share: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" /></svg>`,
        back: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>`,
        addNote: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>`
    };

    // --- GSAP Plugin Registration ---
    gsap.registerPlugin(ScrollTrigger);

    // Ensure Font Awesome is included
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const fa = document.createElement('link');
        fa.rel = 'stylesheet';
        fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';
        fa.crossOrigin = 'anonymous';
        document.head.appendChild(fa);
    }

    // Ensure Granim.js is included
    if (!document.querySelector('script[src*="granim"]')) {
        const granimScript = document.createElement('script');
        granimScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/granim/2.0.0/granim.min.js';
        granimScript.crossOrigin = 'anonymous';
        document.head.appendChild(granimScript);
    }

    // --- Theme Management ---
    const applyTheme = (theme) => {
        body.classList.remove('light-mode', 'dark-mode');
        body.classList.add(`${theme}-mode`);
        localStorage.setItem('theme', theme);
        themeToggle?.querySelectorAll('.light-icon, .dark-icon').forEach(icon =>
            icon.classList.toggle('hidden', icon.classList.contains(theme === 'dark' ? 'light-icon' : 'dark-icon'))
        );
        ScrollTrigger.refresh();
        console.log(`ViewInfo: Theme applied - ${theme}`);
    };

    const setupThemeToggle = () => { // Used for theme toggle setup
        if (!themeToggle) return;
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
        const currentTheme = localStorage.getItem('theme') || (prefersDarkScheme.matches ? 'dark' : 'light');

        themeToggle.addEventListener('click', () => {
            if (isTransitioning) return;
            const currentIcon = themeToggle.querySelector('svg:not(.hidden)');
            const nextTheme = body.classList.contains('light-mode') ? 'dark' : 'light';

            if (currentIcon) {
                gsap.to(currentIcon, {
                    rotate: 90,
                    scale: 0.8,
                    opacity: 0,
                    duration: MICRO_INTERACTION_DURATION / 2,
                    ease: 'power1.in',
                    onComplete: () => {
                        applyTheme(nextTheme);
                        const newIcon = themeToggle.querySelector('svg:not(.hidden)');
                        if (newIcon) {
                            gsap.fromTo(newIcon,
                                { rotate: -90, scale: 0.8, opacity: 0 },
                                { rotate: 0, scale: 1, opacity: 1, duration: MICRO_INTERACTION_DURATION, ease: 'power1.out' }
                            );
                        }
                    }
                });
            } else {
                 applyTheme(nextTheme);
            }
        });

        prefersDarkScheme.addEventListener('change', e => {
            if (!localStorage.getItem('theme')) applyTheme(e.matches ? 'dark' : 'light');
        });

        applyTheme(currentTheme);
    };

    // --- Utility Functions ---
    const hexToRgb = hex => {
        if (!hex || typeof hex !== 'string') return null;
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? { r: parseInt(result[1], 16), g: parseInt(result[2], 16), b: parseInt(result[3], 16) } : null;
    };

    const getContrastType = hexColor => {
        try {
            if (!chroma.valid(hexColor)) return 'light';
            return chroma(hexColor).luminance() > 0.5 ? 'dark' : 'light';
        } catch { return 'light'; }
    };

    const getContrastColor = (bgColor) => {
        try {
            if (!chroma.valid(bgColor)) return '#222';
            return chroma(bgColor).luminance() > 0.5 ? '#222' : '#fff';
        } catch {
            return '#222';
        }
    };

    const adjustColor = (hex, percent) => {
        const rgb = hexToRgb(hex);
        if (!rgb) return hex;
        let { r, g, b } = rgb;
        const amount = Math.floor(255 * (percent / 100));
        r = Math.max(0, Math.min(255, r + amount));
        g = Math.max(0, Math.min(255, g + amount));
        b = Math.max(0, Math.min(255, b + amount));
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    };

    
    const mk_adjustColor = (constastVal,baseColor) => {
        if (constastVal == "light") {
            return chroma(baseColor).brighten(2);
        }
        else{
            return chroma(baseColor).darken(2);
        }
    };

    // Add this new function instead:
const getGradientStopColor = (baseColor) => {
    try {
        if (!chroma.valid(baseColor)) return baseColor;
        
        const contrastType = getContrastType(baseColor);
        // For light colors, darken for gradient stop
        if (contrastType === 'light') {
            return chroma(baseColor).darken(1.2).hex();
        } 
        // For dark colors, brighten for gradient stop
        else {
            return chroma(baseColor).brighten(1.2).hex();
        }
    } catch (error) {
        console.error("Error adjusting color with chroma:", error);
        return baseColor;
    }
};

    const showToast = (message = 'Copied!', icon = 'success') => {
        if (typeof Swal === 'undefined') return console.warn("SweetAlert2 not loaded. Fallback:", message);
        try {
            Swal.fire({
                toast: true, position: 'bottom-end', icon: icon, title: escapeHtml(message),
                showConfirmButton: false, timer: 2000, timerProgressBar: true,
                customClass: { popup: 'info-hub-toast' },
                background: getComputedStyle(body).getPropertyValue('--mika_card_bg').trim() || '#ffffff',
                color: getComputedStyle(body).getPropertyValue('--mika_text_primary').trim() || '#333333',
                didOpen: toast => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        } catch (e) { console.error("Error showing toast:", e); }
    };

    const escapeHtml = unsafe => String(unsafe ?? '')
        .replace(/&/g, "&")
        .replace(/</g, "<")
        .replace(/>/g, ">")
        .replace(/"/g, "\"")
        .replace(/'/g, "'");

    const isValidUrl = string => typeof string === 'string' && (string.startsWith('http:') || string.startsWith('https:') || string.startsWith('mailto:') || string.startsWith('tel:'));

    // Utility to wrap each letter in a span for animation
    function wrapLetters(text) {
        return text.split('').map((char, i) => {
            const cls = char === ' ' ? 'letter space' : 'letter';
            return `<span class="${cls}">${char}</span>`;
        }).join('');
    }

    // Utility to check NFC support
    function isNfcSupported() {
        return ('NDEFReader' in window || 'NDEFWriter' in window);
    }

    // --- NFC Sharing Logic ---
    const handleNfcShare = async (value, buttonElement) => {
        if (!value) return;

        if (!isNfcSupported()) {
            showToast('NFC is not supported on this device.', 'error');
            return;
        }

        const buttonRect = buttonElement.getBoundingClientRect();

        // Create NFC modal
        let nfcModal = document.getElementById('nfc-share-modal');
        if (nfcModal) {
            nfcModal.remove(); // Remove existing modal to prevent duplicates
        }
        nfcModal = document.createElement('div');
        nfcModal.id = 'nfc-share-modal';
        document.body.appendChild(nfcModal);

        // Create or get overlay
        let modalOverlay = document.getElementById('nfc-modal-overlay');
        if (!modalOverlay) {
            modalOverlay = document.createElement('div');
            modalOverlay.id = 'nfc-modal-overlay';
            modalOverlay.style.position = 'fixed';
            modalOverlay.style.top = '0';
            modalOverlay.style.left = '0';
            modalOverlay.style.width = '100vw';
            modalOverlay.style.height = '100vh';
            modalOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modalOverlay.style.backdropFilter = 'blur(10px)';
            modalOverlay.style.zIndex = '10000';
            modalOverlay.style.opacity = '0';
            document.body.appendChild(modalOverlay);
        }

        // Style NFC modal
        nfcModal.style.position = 'fixed';
        nfcModal.style.zIndex = '10001';
        nfcModal.style.backgroundColor = '#1a1a1a';
        nfcModal.style.color = '#ffffff';
        nfcModal.style.borderRadius = '0';
        nfcModal.style.boxShadow = '0 8px 32px rgba(0,0,0,0.2)';
        nfcModal.style.overflow = 'hidden';

        // Set initial position from button
        nfcModal.style.top = `${buttonRect.top}px`;
        nfcModal.style.left = `${buttonRect.left}px`;
        nfcModal.style.width = `${buttonRect.width}px`;
        nfcModal.style.height = `${buttonRect.height}px`;
        nfcModal.style.display = 'block';

        // Prepare modal content with steps
        nfcModal.innerHTML = `
            <div class="nfc-modal-content" style="opacity: 0; height: 100%; display: flex; flex-direction: column;">
                <div class="modal-close-row" style="display: flex; justify-content: flex-end; padding: 2rem;">
                    <button class="nfc-modal-close" style="background: none; border: none; color: inherit; font-size: 72px; cursor: pointer; padding: 8px; line-height: 0.5;">×</button>
                </div>
                <div class="nfc-modal-body" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; gap: 3rem;">
                    <div class="nfc-status-icon" style="font-size: 72px; margin-bottom: 2rem;">📱</div>
                    <h2 class="nfc-status-title fittext" style="font-size: 2.5rem; margin: 0 0 2rem 0;">Ready to Share</h2>
                    <p class="nfc-status-text fittext" style="font-size: 1.5rem; margin: 0;">Bring your device close to another NFC-enabled device to share.</p>
                    <div class="nfc-animation" style="width: 300px; height: 300px; position: relative; margin: 3rem 0;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <div style="width: 150px; height: 150px; border: 4px solid #fff; border-radius: 50%; opacity: 0.2;"></div>
                            <div style="width: 120px; height: 120px; border: 4px solid #fff; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.4;"></div>
                            <div style="width: 90px; height: 90px; border: 4px solid #fff; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.6;"></div>
                        </div>
                    </div>
                    <div class="qr-code-fallback" style="display: none; flex-direction: column; align-items: center; gap: 1rem; margin-top: 2rem;">
                        <p>Or scan this QR code:</p>
                        <div id="nfc-modal-qrcode"></div>
                    </div>
                </div>
            </div>
        `;

        // Animate overlay and modal
        gsap.to(modalOverlay, {
            opacity: 1,
            duration: 0.3,
            ease: 'power2.out'
        });

        // Get final dimensions for full screen
        const finalWidth = window.innerWidth;
        const finalHeight = window.innerHeight;

        // Create timeline for modal animation
        const tl = gsap.timeline();
        
        tl.to(nfcModal, {
            top: 0,
            left: 0,
            width: finalWidth,
            height: finalHeight,
            duration: 0.4,
            ease: 'power2.inOut'
        })
        .to(nfcModal.querySelector('.nfc-modal-content'), {
            opacity: 1,
            duration: 0.3,
            ease: 'power2.out'
        });

        // Animate NFC rings
        gsap.to(nfcModal.querySelectorAll('.nfc-animation div div'), {
            scale: 1.5,
            opacity: 0,
            duration: 1.5,
            stagger: 0.2,
            repeat: -1,
            ease: 'power1.out'
        });

        // Setup close handlers
        const closeNfcModal = () => {
            const tl = gsap.timeline({
                onComplete: () => {
                    nfcModal.remove();
                    modalOverlay.remove();
                }
            });

            tl.to(nfcModal.querySelector('.nfc-modal-content'), {
                opacity: 0,
                duration: 0.2
            })
            .to(modalOverlay, {
                opacity: 0,
                duration: 0.2
            }, '<')
            .to(nfcModal, {
                top: buttonRect.top,
                left: buttonRect.left,
                width: buttonRect.width,
                height: buttonRect.height,
                duration: 0.3,
                ease: 'power2.inOut'
            });
        };

        nfcModal.querySelector('.nfc-modal-close').addEventListener('click', closeNfcModal);
        modalOverlay.addEventListener('click', closeNfcModal);

        try {
            const ndef = new NDEFReader();
            await ndef.write({ records: [{ recordType: "text", data: value }] });
            showToast('Data shared via NFC!', 'success');
            setTimeout(closeNfcModal, 2000);
        } catch (error) {
            console.error('NFC sharing failed:', error);
            showToast(`NFC failed: ${error.message}`, 'error');
            // Show QR code fallback
            const qrFallback = nfcModal.querySelector('.qr-code-fallback');
            if (qrFallback) {
                qrFallback.style.display = 'flex';
                new QRCode(document.getElementById('nfc-modal-qrcode'), {
                    text: value,
                    width: 128,
                    height: 128
                });
            }
        }
    };

    // --- Double Click Helper ---
    function setupDoubleClick(card, handler) {
        let clickTimeout = null;
        card.addEventListener('click', (e) => {
            if (clickTimeout) {
                clearTimeout(clickTimeout);
                clickTimeout = null;
                handler(card, e);
            } else {
                clickTimeout = setTimeout(() => {
                    clickTimeout = null;
                }, 250);
            }
        });
    }

    // --- GSAP & Animation Functions ---

    const animateButtonClick = (button) => {
        gsap.to(button, {
            scale: 0.9,
            duration: BUTTON_CLICK_DURATION,
            yoyo: true,
            repeat: 1,
            ease: 'power1.inOut'
        });
    };

    const animateViewChange = (outgoingView, incomingView, onCompleteCallback, cardGrid = null) => {
        if (!outgoingView || !incomingView || isTransitioning) return;
        isTransitioning = true;
        console.log(`ViewInfo: Animating out: ${outgoingView.id}, Animating in: ${incomingView.id}`);

        gsap.set(incomingView, { opacity: 0, visibility: 'hidden', display: 'none' });

        const tl = gsap.timeline({
            defaults: { ease: 'power2.inOut' },
            onComplete: () => {
                gsap.set(outgoingView, { display: 'none', visibility: 'hidden', opacity: 1 });
                gsap.set(incomingView, { display: 'block', visibility: 'visible', opacity: 1 });
                onCompleteCallback?.();
                ScrollTrigger.refresh();
                isTransitioning = false;
                console.log(`ViewInfo: Transition complete. Showing: ${incomingView.id}`);
            }
        });

        tl.to(outgoingView, {
            opacity: 0,
            duration: VIEW_TRANSITION_DURATION
        })
        .set(incomingView, {
            display: 'block',
            visibility: 'visible'
        }, `>-${VIEW_TRANSITION_DURATION * 0.2}`)
        .to(incomingView, {
            opacity: 1,
            duration: VIEW_TRANSITION_DURATION
        });

        if (cardGrid) {
            const cards = cardGrid.querySelectorAll('.item-card');
            if (cards.length > 0) {
                gsap.set(cards, {
                    opacity: 0,
                    y: 50,
                    rotationX: -20,
                    transformOrigin: "center bottom"
                });
                tl.to(cards, {
                    opacity: 1,
                    y: 0,
                    rotationX: 0,
                    duration: CARD_STAGGER_DURATION,
                    stagger: {
                        amount: Math.min(CARD_STAGGER_AMOUNT, cards.length * 0.08),
                        from: "start",
                        ease: "power2.out"
                    },
                    ease: 'power2.out'
                }, `-=${VIEW_TRANSITION_DURATION * 0.5}`);
            }
        }

        console.log('Animating view change:', { outgoingView, incomingView });
        console.log('Current visible card list:', currentVisibleCardList);
        console.log('Card grid visibility check:', cardGrid);

        if (!cardGrid) {
            console.warn('Card grid not found for the selected category.');
        } else {
            console.log('Card grid found. Ensuring visibility.');
        }

        return tl;
    };

    // --- 3D Tilt and Shine Effect for Grid View Cards ---
    function setupCardTiltEffect() {
        document.querySelectorAll('.card-grid .item-card').forEach(card => {
            // Add shine element if not present
            if (!card.querySelector('.shine')) {
                const shine = document.createElement('div');
                shine.className = 'shine';
                card.appendChild(shine);
            }
            const shine = card.querySelector('.shine');
            let mouseOnComponent = false;
            card.addEventListener('mousemove', (e) => {
                mouseOnComponent = true;
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const deltaX = x - centerX;
                const deltaY = y - centerY;
                const percentX = deltaX / centerX;
                const percentY = deltaY / centerY;
                const rotateX = percentY * 12; // max 12deg
                const rotateY = percentX * -12; // max 12deg
                card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                card.classList.add('tilted');
                // Shine effect
                const angle = Math.atan2(deltaY, deltaX) * 180 / Math.PI - 90;
                const shinePos = (percentX + 1) * 50;
                shine.style.background = `linear-gradient(${angle}deg, rgba(255,255,255,${0.35 + percentY * 0.15}) 0%, rgba(255,255,255,0.05) 80%)`;
                shine.style.opacity = '1';
            });
            card.addEventListener('mouseleave', () => {
                mouseOnComponent = false;
                card.style.transform = '';
                card.classList.remove('tilted');
                shine.style.opacity = '0';
            });
            card.addEventListener('mouseenter', () => {
                mouseOnComponent = true;
            });
        });
    }

    // --- Card Interaction Functions ---
    const handleCopyAction = (value, cardElement) => {
        if (!value || !cardElement) return;
        // Remove progress bar after copy
        const progress = cardElement.querySelector('.hold-progress-bar');
        if (progress) progress.style.width = '0%';
        navigator.clipboard.writeText(value)
            .then(() => {
                showToast('Copied!');
                gsap.to(cardElement, {
                    scale: 1.02,
                    yoyo: true,
                    repeat: 1,
                    duration: 0.2,
                    ease: 'power1.inOut'
                });
            })
            .catch(err => {
                console.error('ViewInfo: Failed to copy:', err);
                showToast('Failed to copy!', 'error');
            });
    };

    const releaseHold = (card, clearActionTimeout = true) => {
        if (!card?.classList.contains('is-holding')) return;

        card.style.userSelect = '';
        card.classList.remove('is-holding');
        if (clearActionTimeout) clearTimeout(holdTimeout);

        holdGsapTweenGlow?.kill();
        holdGsapTweenIndicator?.kill();
        holdGsapTweenGlow = holdGsapTweenIndicator = null;

        // Animate progress bar back to 0 and remove it after animation
        const progress = card.querySelector('.hold-progress-bar');
        if (progress) {
            gsap.to(progress, { width: '0%', duration: 0.2, ease: 'power1.inOut', onComplete: () => progress.remove() });
        }

        const baseShadow = card.style.boxShadow.split(',')[0] || '0 5px 15px var(--mika_shadow_color)';
        const currentGlow = card.dataset.currentGlow || '0 0 0px 0px transparent';
        gsap.to(card, {
            boxShadow: `${baseShadow}, ${currentGlow}`,
            duration: 0.3,
            ease: 'power1.out'
        });
        gsap.to(card, { '--before-width': '0%', duration: 0.1 });
    };

    const startHold = (event) => {
        const card = event.currentTarget;
        if (isTransitioning || card.classList.contains('static-content-card') || !card.dataset.copyValue) return;

        event.preventDefault();
        card.style.userSelect = 'none';

        clearTimeout(holdTimeout);
        holdGsapTweenGlow?.kill();
        holdGsapTweenIndicator?.kill();
        card.classList.add('is-holding');

        // Add or reset progress bar
        let progress = card.querySelector('.hold-progress-bar');
        if (!progress) {
            progress = document.createElement('div');
            progress.className = 'hold-progress-bar';
            card.appendChild(progress);
        }
        gsap.set(progress, { width: '0%' });
        gsap.to(progress, { width: '100%', duration: HOLD_DURATION / 1000, ease: 'linear' });

        const baseShadow = card.style.boxShadow.split(',')[0] || '0 5px 15px var(--mika_shadow_color)';
        const orangeColor = getComputedStyle(body).getPropertyValue('--mika_warning').trim() || '#FF9800';
        const orangeGlow = `0 0 25px 10px ${orangeColor}99`;
        holdGsapTweenGlow = gsap.to(card, {
            boxShadow: `${baseShadow}, ${orangeGlow}`,
            duration: 0.5,
            ease: 'power2.out'
        });

        gsap.set(card, { '--before-width': '0%' });
        holdGsapTweenIndicator = gsap.to(card, {
            '--before-width': '100%',
            duration: HOLD_DURATION / 1000,
            ease: 'linear'
        });

        holdTimeout = setTimeout(() => {
            handleCopyAction(card.dataset.copyValue, card);
            releaseHold(card, false);
        }, HOLD_DURATION);
    };

    const handleCardDoubleClick = (card) => {
        if (!card) return;
        
        const value = card?.dataset?.copyValue;
        if (!value) { showToast('No value to display.', 'error'); return; }

        // Get card details
        const cardRect = card.getBoundingClientRect();
        const cardName = card.querySelector('.card-name')?.textContent || '';
        const cardDesc = card.dataset.description || '';
        const cardValue = value;
        const logoSrc = card.querySelector('.card-logo img')?.getAttribute('src') || DEFAULT_ICON;
        const cardBgColor = card.style.getPropertyValue('--card-bg-color') || '#ff5001';
        const cardTextColor = card.style.getPropertyValue('--card-text-color') || 'inherit';
        const nfcSupported = isNfcSupported();

        // Create or get modal
        let qrModal = document.getElementById('qr-code-modal');
        if (qrModal) {
            qrModal.remove(); // Remove existing modal to prevent duplicates
        }
        qrModal = document.createElement('div');
        qrModal.id = 'qr-code-modal';
        qrModal.className = 'qr-code-modal';
        document.body.appendChild(qrModal);

        // Create modal overlay with blur effect
        let modalOverlay = document.getElementById('modal-overlay');
        if (!modalOverlay) {
            modalOverlay = document.createElement('div');
            modalOverlay.id = 'modal-overlay';
            modalOverlay.style.position = 'fixed';
            modalOverlay.style.top = '0';
            modalOverlay.style.left = '0';
            modalOverlay.style.width = '100vw';
            modalOverlay.style.height = '100vh';
            modalOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modalOverlay.style.backdropFilter = 'blur(10px)';
            modalOverlay.style.zIndex = '9998';
            modalOverlay.style.opacity = '0';
            document.body.appendChild(modalOverlay);
        }

        // Position modal initially at card position
        qrModal.style.position = 'fixed';
        qrModal.style.zIndex = '9999';
        qrModal.style.backgroundColor = cardBgColor;
        qrModal.style.color = cardTextColor;
        qrModal.style.borderRadius = '0';
        qrModal.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.39)';
        qrModal.style.overflow = 'auto';

        // Set initial position and size to match the card
        qrModal.style.top = `${cardRect.top}px`;
        qrModal.style.left = `${cardRect.left}px`;
        qrModal.style.width = `${cardRect.width}px`;
        qrModal.style.height = `${cardRect.height}px`;
        qrModal.style.display = 'block';

        // Get contrasting colors for title and gradient
        const contrastColor = getContrastColor(cardBgColor);
        const darkerShade = adjustColor(cardBgColor, -20);
        const lighterShade = adjustColor(cardBgColor, 20);
        const baseColor = card.style.getPropertyValue('--card-bg-color').trim();
         const contrastType = getContrastType(cardBgColor);
  const mk_txt_color= mk_adjustColor(contrastType, cardBgColor);

        // Create unique gradient ID for this modal
        const gradientId = `modal-gradient-${Date.now()}`;

        // Prepare modal content
        const titleBgColor = adjustColor(cardBgColor, contrastColor === '#ffffff' ? 20 : -20);
        const modalContent = `
            <div class="modal-content" style="opacity: 0; height: 100%; display: flex; flex-direction: column; position: relative; overflow: hidden;">
                <canvas id="${gradientId}" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; z-index: 0;"></canvas>
                <div style="position: relative; z-index: 1; height: 100%; display: flex; flex-direction: column; width: 100%;">
                    <div class="modal-header-row" style="display: flex; justify-content: space-between; align-items: center; padding: 2rem 1rem; width: 100%;">
                        <img src="${logoSrc}" alt="" class="modal-logo modal-logo-img" loading="lazy" />
                        <button class="modal-close" style="background: none; border: none; color: inherit; cursor: pointer; padding: 8px; line-height: 0.5; align-self: first baseline;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="50" height="50" class="main-grid-item-icon" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                <line x1="18" x2="6" y1="6" y2="18" />
                                <line x1="6" x2="18" y1="6" y2="18" />
                            </svg>
                        </button>
                    </div>
                    <div class="modal-title-section" style="background: ${titleBgColor}; margin: 0; width: 100vw; margin-left: -50vw; left: 50%; position: relative;">
                        <h2 class="modal-title" style="font-size: 2rem; margin: 0; color: ${mk_txt_color}; padding: 1rem 2rem;  margin: 0 auto;">${escapeHtml(cardName)}</h2>
                    </div>
                    <div class="modal-btm" style="width: 370px; max-width: 370px; margin: 0 auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1rem;">
                        <p class="modal-description" style="font-size: 1.5rem; line-height: 1.3; margin: 0; opacity: 0.9; color: ${mk_txt_color}; ">${escapeHtml(cardDesc)}</p>
                        <div class="qr-section" style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                            <div id="qrcode-container" style="background: white; padding: 1rem; border-radius: 12px;"></div>
                        </div>
                        <div class="value-section" style="word-break: break-all; font-size: 0.5rem; text-align: center; cursor: pointer;" title="Click to copy">
                            ${escapeHtml(cardValue)}
                        </div>
                        ${nfcSupported ? `
                            <button class="nfc-share-btn" style="background: rgba(255,255,255,0.2); color: inherit; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: -1rem; position: relative; z-index: 1;" onclick="handleNfcShare('${escapeHtml(cardValue)}', this)">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12C2 6.48 6.48 2 12 2C17.52 2 22 6.48 22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12Z"></path>
                                    <path d="M8 12C8 9.79 9.79 8 12 8C14.21 8 16 9.79 16 12C16 14.21 14.21 16 12 16C9.79 16 8 14.21 8 12Z"></path>
                                    <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                                </svg>
                                Share via NFC
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>`;

        qrModal.innerHTML = modalContent;

        // Initialize Granim
        new Granim({
            element: `#${gradientId}`,
            direction: 'diagonal', 
            opacity: [1, 1],
            states: {
                "default-state": {
                    gradients: [
                        [cardBgColor, darkerShade],
                        [lighterShade, cardBgColor],
                        [darkerShade, cardBgColor]
                    ],
                    transitionSpeed: 8000
                }
            }
        });

        // Create a timeline for the animation
        const tl = gsap.timeline({
            onComplete: () => {
                // Generate QR code after animation
                const qrContainer = qrModal.querySelector('#qrcode-container');
                if (qrContainer) {
                    new QRCode(qrContainer, {
                        text: value,
                        width: 200,
                        height: 200,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            }
        });

        // Animate overlay
        gsap.to(modalOverlay, {
            opacity: 1,
            duration: 0.3,
            ease: 'power2.out'
        });

        // Get final modal dimensions - now full screen
        const finalWidth = window.innerWidth;
        const finalHeight = window.innerHeight;
        const finalLeft = 0;
        const finalTop = 0;

        // Animate modal
        tl.to(qrModal, {
            top: finalTop,
            left: finalLeft,
            width: finalWidth,
            height: finalHeight,
            duration: 0.4,
            ease: 'power2.inOut'
        })
        .to(qrModal.querySelector('.modal-content'), {
            opacity: 1,
            duration: 0.3,
            ease: 'power2.out'
        });

        // Setup close functionality
        const closeModal = () => {
            const tl = gsap.timeline({
                onComplete: () => {
                    qrModal.style.display = 'none';
                    qrModal.querySelector('#qrcode-container').innerHTML = '';
                }
            });

            tl.to(qrModal.querySelector('.modal-content'), {
                opacity: 0,
                duration: 0.2,
                ease: 'power2.in'
            })
            .to(qrModal, {
                top: cardRect.top,
                left: cardRect.left,
                width: cardRect.width,
                height: cardRect.height,
                duration: 0.3,
                ease: 'power2.inOut'
            }, '-=0.1')
            .to(modalOverlay, {
                opacity: 0,
                duration: 0.3,
                ease: 'power2.in'
            }, '-=0.2');
        };

        // Add event listeners
        qrModal.querySelector('.modal-close').onclick = closeModal;
        modalOverlay.onclick = closeModal;

        // Handle escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);

        // Handle NFC share button if present
        const nfcButton = qrModal.querySelector('.nfc-share-btn');
        if (nfcButton) {
            nfcButton.onclick = () => handleNfcShare(value, card);
        }

        // Copy functionality
        const valueSection = qrModal.querySelector('.value-section');
        if (valueSection) {
            valueSection.style.cursor = 'pointer';
            valueSection.title = 'Click to copy';
            valueSection.onclick = () => {
                navigator.clipboard.writeText(value)
                    .then(() => showToast('Copied to clipboard!', 'success'))
                    .catch(() => showToast('Failed to copy', 'error'));
            };
        }

        // Initialize FitText after content is added
        if (window.jQuery && window.jQuery.fn.fitText) {
            $('.fittext').fitText();
        }
    };

    // --- Event Listeners Setup ---
    let attachCardEventListeners = (viewContainer) => {
        viewContainer.querySelectorAll('.item-card').forEach(card => {
            // Double click handler
            card.addEventListener('dblclick', (e) => {
                e.preventDefault();
                handleCardDoubleClick(card);
            });

            // Touch double tap for mobile
            let lastTap = 0;
            card.addEventListener('touchend', (e) => {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;
                if (tapLength < 500 && tapLength > 0) {
                    e.preventDefault();
                    handleCardDoubleClick(card);
                }
                lastTap = currentTime;
            });

            let baseGlow = card.dataset.baseGlow || '0 0 0px 0px transparent';
            card.dataset.currentGlow = baseGlow;

            // Hover Animation
            card.addEventListener('pointerenter', () => {
                gsap.to(card, {
                    y: -8,
                    scale: 1.03,
                    boxShadow: '0 12px 30px rgba(0, 0, 0, 0.2), 0 0 20px 5px var(--card-glow-color-rgba)',
                    duration: 0.3,
                    ease: 'power1.out'
                });
            });

            card.addEventListener('pointerleave', () => {
                gsap.to(card, {
                    y: 0,
                    scale: 1,
                    boxShadow: '0 6px 18px rgba(0, 0, 0, 0.1), 0 0 10px 2px var(--card-glow-color-rgba)',
                    duration: 0.3,
                    ease: 'power1.inOut'
                });
            });

            // Focus Animation
            card.addEventListener('focusin', () => {
                gsap.to(card, {
                    boxShadow: '0 12px 30px rgba(0, 0, 0, 0.2), 0 0 25px 8px var(--card-glow-color-rgba)',
                    duration: 0.3,
                    ease: 'power1.out'
                });
            });

            card.addEventListener('focusout', () => {
                gsap.to(card, {
                    boxShadow: '0 6px 18px rgba(0, 0, 0), 0 0 1px 2px var(--card-glow-color-rgba)',
                    duration: 0.3,
                    ease: 'power1.inOut'
                });
            });

            // Click Animation
            card.addEventListener('pointerdown', () => {
                gsap.to(card, {
                    scale: 0.97,
                    duration: 0.1,
                    ease: 'power1.inOut',
                    yoyo: true,
                    repeat: 1
                });
            });

            // Ripple Effect on Click
            card.addEventListener('click', (event) => {
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                const rect = card.getBoundingClientRect();
                ripple.style.left = `${event.clientX - rect.left}px`;
                ripple.style.top = `${event.clientY - rect.top}px`;
                card.appendChild(ripple);

                gsap.fromTo(ripple, {
                    scale: 0,
                    opacity: 0.5
                }, {
                    scale: 4,
                    opacity: 0,
                    duration: 0.6,
                    ease: 'power1.out',
                    onComplete: () => ripple.remove()
                });
            });

            // Entrance Animation
            gsap.fromTo(card, {
                opacity: 0,
                y: 50
            }, {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'power2.out',
                stagger: 0.1
            });

            card.querySelectorAll('.card-action-button').forEach(button => {
                button.addEventListener('click', e => {
                    e.stopPropagation();
                    const action = e.currentTarget.dataset.action;
                    if (action === 'copy') handleCopyAction(card.dataset.copyValue, card);
                });
                button.addEventListener('keydown', e => { if (e.key === ' ') e.stopPropagation(); });
            });

            // NFC button logic
            const nfcBtn = card.querySelector('.nfc-action-button');
            if (nfcBtn) {
                nfcBtn.onclick = (e) => {
                    e.stopPropagation();
                    handleNfcShare(card.dataset.copyValue, card);
                };
            }

            if (card.dataset.copyValue && !card.classList.contains('static-content-card')) {
                ['pointerdown', 'touchstart'].forEach(evtType => card.addEventListener(evtType, startHold, { passive: false }));
                ['pointerup', 'pointerleave', 'pointercancel', 'touchend', 'touchcancel'].forEach(evtType =>
                    card.addEventListener(evtType, () => releaseHold(card))
                );
                card.addEventListener('keydown', e => {
                    if ((e.key === 'Enter' || e.key === ' ') && !e.target.closest('button')) {
                        e.preventDefault();
                        handleCopyAction(card.dataset.copyValue, card);
                    }
                });
            } else {
                card.addEventListener('pointerdown', () => releaseHold(card));
            }
        });
    };

    const origAttachCardEventListeners = attachCardEventListeners;
    attachCardEventListeners = function(viewContainer) {
        origAttachCardEventListeners(viewContainer);
        // Also apply to stack view
        const stackCards = viewContainer.querySelectorAll('.stack-swiper .item-card');
        stackCards.forEach(card => {
            // Double click
            card.addEventListener('dblclick', (e) => {
                e.preventDefault();
                handleCardDoubleClick(card);
            });

            // Touch double tap for mobile
            let lastTap = 0;
            card.addEventListener('touchend', (e) => {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;
                if (tapLength < 500 && tapLength > 0) {
                    e.preventDefault();
                    handleCardDoubleClick(card);
                }
                lastTap = currentTime;
            });

            // Hold to copy
            if (card.dataset.copyValue && !card.classList.contains('static-content-card')) {
                ['pointerdown', 'touchstart'].forEach(evtType => card.addEventListener(evtType, startHold, { passive: false }));
                ['pointerup', 'pointerleave', 'pointercancel', 'touchend', 'touchcancel'].forEach(evtType =>
                    card.addEventListener(evtType, () => releaseHold(card))
                );
                card.addEventListener('keydown', e => {
                    if ((e.key === 'Enter' || e.key === ' ') && !e.target.closest('button')) {
                        e.preventDefault();
                        handleCopyAction(card.dataset.copyValue, card);
                    }
                });
            } else {
                card.addEventListener('pointerdown', () => releaseHold(card));
            }
        });
        // Only apply to grid view
        if (viewContainer.querySelector('.card-grid')) {
            setupCardTiltEffect();
            // Enhanced entrance animation
            const cards = viewContainer.querySelectorAll('.card-grid .item-card');
            gsap.set(cards, { opacity: 0, y: 60, scale: 0.92, rotationY: 12 });
            gsap.to(cards, {
                opacity: 1,
                y: 0,
                scale: 1,
                rotationY: 0,
                duration: 1.2,
                ease: 'elastic.out(1, 0.55)',
                stagger: { amount: Math.min(0.7, cards.length * 0.08), from: 'start' }
            });
        }
    };

    const attachGlobalEventListeners = () => {
        body.addEventListener('click', event => {
            const button = event.target.closest('button[data-action="show-swiper"]');
            if (button && !isTransitioning) {
                animateButtonClick(button);
                showCategorySwiper(button.closest('.card-list-container'));
            }
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && currentVisibleCardList && !isTransitioning) {
                 showCategorySwiper(currentVisibleCardList);
            }
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('.view-items-button');
            if (button) {
                const categoryId = button.getAttribute('data-category-id');
                if (categoryId) {
                    handleCategorySelect(categoryId);
                }
            }
        });
    };

    // --- View Navigation ---
    const handleCategorySelect = (categoryId) => {
        if (isTransitioning) return;
        console.log(`ViewInfo: Category selected: ${categoryId}`);
        const cardListView = document.getElementById(`card-list-${categoryId}`);
        if (!cardListView) {
            console.error(`ViewInfo: cardListView not found for category ID: ${categoryId}`);
            return;
        }

        const cardGrid = cardListView.querySelector('.card-grid');
        if (!cardGrid) {
            console.error(`ViewInfo: cardGrid not found in cardListView for category ID: ${categoryId}`);
            return;
        }

        const outgoingView = categorySwiperView.style.display !== 'none' ? categorySwiperView : currentVisibleCardList;
        if (!outgoingView) {
            console.warn(`ViewInfo: Cannot determine outgoing view for category select.`);
            // Hide all card list views
            document.querySelectorAll('.card-list-container').forEach(v => v.classList.remove('visible'));
            categorySwiperView.classList.remove('visible');
            // Show the selected card list view
            cardListView.classList.add('visible');
            currentVisibleCardList = cardListView;
            isTransitioning = false;
            return;
        }

        // Hide all card list views
        document.querySelectorAll('.card-list-container').forEach(v => v.classList.remove('visible'));
        categorySwiperView.classList.remove('visible');
        // Show the selected card list view
        cardListView.classList.add('visible');
        currentVisibleCardList = cardListView;
        animateViewChange(outgoingView, cardListView, () => {
            const focusTarget = cardListView.querySelector('.back-button') || cardListView.querySelector('.category-dropdown');
            focusTarget?.focus({ preventScroll: true });
            if (cardGrid) {
                gsap.set(cardGrid, { opacity: 0, visibility: 'visible', display: 'grid' });
                gsap.to(cardGrid, { opacity: 1, duration: 2, ease: 'power1.out' });
            }
        }, cardGrid);
    };

    const showCategorySwiper = (outgoingCardListView) => {
        if (isTransitioning || !outgoingCardListView || !categorySwiperView) return;
        const categoryId = outgoingCardListView.dataset.categoryId;
        console.log(`ViewInfo: Going back from category: ${categoryId}`);
        const slideIndex = jsonData?.categories.findIndex(cat => cat.id === categoryId) ?? -1;
        // Hide all card list views
        document.querySelectorAll('.card-list-container').forEach(v => v.classList.remove('visible'));
        // Show the swiper view
        categorySwiperView.classList.add('visible');
        animateViewChange(outgoingCardListView, categorySwiperView, () => {
            if (categorySwiper && slideIndex !== -1) {
                requestAnimationFrame(() => {
                    categorySwiper.slideTo(slideIndex, 0);
                    categorySwiper.update();
                    const targetSlide = categorySwiper.slides[slideIndex];
                    targetSlide?.focus({ preventScroll: true });
                });
            }
            currentVisibleCardList = null;
            outgoingCardListView.scrollTop = 0;
        });
    };

    const createLightRays = (container) => {
        const raysContainer = document.createElement('div');
        raysContainer.className = 'light-rays-container';

        const rayCount = 12;
        for (let i = 0; i < rayCount; i++) {
            const ray = document.createElement('div');
            ray.className = 'light-ray';
            ray.style.transform = `rotate(${ (360 / rayCount) * i }deg)`;
            raysContainer.appendChild(ray);
        }
        container.prepend(raysContainer);

        // Animation
        gsap.to(raysContainer, {
            rotation: 360,
            duration: 200,
            ease: 'none',
            repeat: -1
        });

        gsap.to(raysContainer.children, {
            opacity: 0.5,
            duration: 1 ,
            stagger: {
                each: 0.5,
                from: 'random',
                repeat: -1,
                yoyo: true
            },
            ease: 'power1.inOut'
        });

        // Mouse interaction
        container.addEventListener('mousemove', (e) => {
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const xc = rect.width / 2;
            const yc = rect.height / 2;
            const dx = x - xc;
            const dy = y - yc;

            gsap.to(raysContainer, {
                x: dx * 0.05,
                y: dy * 0.05,
                duration: 1,
                ease: 'power2.out'
            });
        });
    };

    // --- Initialization Logic ---
    const setupCategorySwiper = async () => {
        if (!categorySwiperWrapper || !jsonData?.categories) return;

        const slides = jsonData.categories.map((category, index) => {
            const gradientId = `granim-gradient-${index}`;
            const categoryLabel = category.name || category.title || category.id || 'Category';
            return `
                <div class="swiper-slide category-slide" data-category-id="${category.id}" role="button" tabindex="0" aria-label="Select category: ${escapeHtml(categoryLabel)}">
                    <canvas id="${gradientId}" class="granim-canvas"></canvas>
                    <div class="slide-content">
                        <h2 class="category-title fittext">${escapeHtml(categoryLabel)}</h2>
                        <button class="view-items-button" data-category-id="${category.id}">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        </button>
                    </div>
                </div>`;
        });

        categorySwiperWrapper.innerHTML = slides.join('');

        document.querySelectorAll('.category-slide').forEach(slide => {
            createLightRays(slide);
        });

        const predefinedColors = ['#001E14', '#002846', '#140A1E', '#1E141E', '#001414', '#1E0A1E', '#003246', '#0A2828', '#1D0D19', '#0A0A14', '#00141E', '#000032', '#140A1E', '#0A0A1E'];

        const fetchShadesFromPredefinedColors = async (color) => {
            try {
                const response = await fetch(`https://www.thecolorapi.com/scheme?hex=${color.replace('#', '')}&mode=monochrome&count=5`);
                if (!response.ok) throw new Error('Failed to fetch shades from the API');
                const data = await response.json();

                if (!data.colors || !Array.isArray(data.colors)) {
                    throw new Error('Unexpected API response structure: colors array is missing');
                }

                return data.colors.map(color => color.hex.value);
            } catch (error) {
                console.error('Error fetching shades:', error);
                return [color];
            }
        };

        const initializeGranimWithPredefinedColors = async (gradientId, index) => {
            const baseColor = predefinedColors[Math.floor(Math.random() * predefinedColors.length)];
            const shades = await fetchShadesFromPredefinedColors(baseColor);

            const granimInstance = new Granim({
                element: `#${gradientId}`,
                name: `granim-gradient-${index}`,
                direction: 'diagonal',
                isPausedWhenNotInView: true,
                colorType: 'hex',
                states: {
                    "default-state": {
                        gradients: [
                            [shades[0], shades[1]],
                            [shades[2], shades[3]],
                            [shades[1], shades[4]]
                        ],
                        transitionSpeed: 10000
                    }
                }
            });

            const slideElement = document.querySelector(`#${gradientId}`).closest('.swiper-slide');
            if (slideElement) {
                slideElement.addEventListener('mouseenter', () => granimInstance.play());
                slideElement.addEventListener('mouseleave', () => granimInstance.pause());
            }

            const categoryTitle = slideElement.querySelector('.category-title');
            if (categoryTitle) {
                categoryTitle.addEventListener('click', () => {
                    const categoryId = slideElement.getAttribute('data-category-id');
                    if (categoryId) {
                        handleCategorySelect(categoryId);
                    }
                });
            }

            slideElement.addEventListener('click', (event) => {
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                const rect = slideElement.getBoundingClientRect();
                ripple.style.left = `${event.clientX - rect.left}px`;
                ripple.style.top = `${event.clientY - rect.top}px`;
                slideElement.appendChild(ripple);

                gsap.fromTo(ripple, {
                    scale: 0,
                    opacity: 0.5
                }, {
                    scale: 4,
                    opacity: 0,
                    duration: 0.6,
                    ease: 'power1.out',
                    onComplete: () => ripple.remove()
                });

                const categoryId = slideElement.getAttribute('data-category-id');
                if (categoryId) {
                    handleCategorySelect(categoryId);
                }
            });
        };

        jsonData.categories.forEach((category, index) => {
            const gradientId = `granim-gradient-${index}`;
            initializeGranimWithPredefinedColors(gradientId, index);
        });

        if (typeof Swiper !== 'undefined') {
            categorySwiper = new Swiper('.category-swiper', {
                direction: 'horizontal',
                loop: true,
                grabCursor: true,
                spaceBetween: 20,
                slidesPerView: 1,
                centeredSlides: true,
                effect: 'slide',
                speed: 500,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                navigation: false,
                pagination: false,
                keyboard: false
            });
        } else {
            console.error('ViewInfo: Swiper library not loaded.');
        }
    };

    const createAndPopulateCardListViews = async () => {
        if (!cardListViewsContainer || !jsonData?.categories) return;
        cardListViewsContainer.innerHTML = '';

        const viewPromises = jsonData.categories.map(async category => {
            const view = document.createElement('div');
            view.id = `card-list-${category.id}`;
            view.className = 'card-list-container hide-scrollbar';
            view.dataset.categoryId = category.id;
            view.style.display = 'none';
            view.style.visibility = 'hidden';
            const categoryLabel = category.name || category.title || category.id || 'Category';
            view.setAttribute('aria-label', `Items in category: ${escapeHtml(categoryLabel)}`);
            view.setAttribute('role', 'region');

            const cardsHTML = createItemCardsHTML(category.items || []);
            view.innerHTML = `
                <div class="card-list-header xs:pt-8" style="display:flex;align-items:center;justify-content:space-between;position:relative;">
                    <div style="display:flex;align-items:center;">
                        <button class="back-button" data-action="show-swiper" aria-label="Go back to categories">${ICONS.back}</button>
                        <button class="refresh-button" title="Refresh" aria-label="Refresh" style="background:none;border:none;color:var(--mika_primary);font-size:1rem;cursor:pointer;padding:0.5rem;line-height:1;display:inline-flex;align-items:center;justify-content:center;transition:color 0.2s,transform 0.2s; border-radius:50%;">
                          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M4.93 4.93a10 10 0 1 1-1.32 2.06"/><path d="M4 4v5h5"/></svg>
                        </button>
                    </div>
                    <div class="header-center" style="flex:1;display:flex;align-items:center;justify-content:center;min-width:0;">
                        <select class="category-dropdown" style="background-color: var(--mika_card_bg); color: var(--mika_text_primary); font-size: 1.5rem; border: none; padding: 0.5rem; border-radius: 8px; outline: none; pointer-events:auto; min-width:180px; max-width:950%; width:90%; text-align:center;">
                            ${jsonData.categories.map(cat => `<option value="${cat.id}" ${cat.id === category.id ? 'selected' : ''}>${escapeHtml(cat.name || cat.title || cat.id)}</option>`).join('')}
                        </select>
                    </div>
                    <div style="display:flex;align-items:center;z-index:2;">
                        <button class="stack-view-toggle" title="Toggle Stack View" aria-label="Toggle Stack View" style="background:none;border:none;color:var(--mika_primary);font-size:1rem;cursor:pointer; padding:0.5rem;line-height:1;display:inline-flex;align-items:center;justify-content:center;transition:color 0.2s,transform 0.2s; border-radius:50%;">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="7" width="18" height="2" rx="1"/><rect x="3" y="11" width="18" height="2" rx="1"/><rect x="3" y="15" width="18" height="2" rx="1"/></svg>
                        </button>
                        <a href="admin.php" class="add-note-button" title="Add Note" style="background:none;border:none;color:var(--mika_primary);font-size:1.75rem;cursor:pointer;padding:0.5rem;line-height:1;display:inline-flex;align-items:center;justify-content:center;transition:color 0.2s,transform 0.2s; border-radius:50%;text-decoration:none;z-index:2;" tabindex="0">${ICONS.addNote}</a>
                    </div>
                </div>
                <div class="card-grid">${cardsHTML}</div>`;

            // Add stack view markup after the card-grid
            view.innerHTML += `
                <div class="card-stack-view stack-effect" style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">
                  <div class="swiper stack-swiper" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
                    <div class="swiper-wrapper">
                                            ${(category.items || []).map(item => {
                                                const baseColor = item.color || '#ff5001';
                                                const contrastColor = getContrastColor(baseColor);
                                                const contrastType = getContrastType(baseColor);
                                                const textColorVar = `var(--mika_text_${contrastType === 'light' ? 'light' : 'dark'}_base)`;
                                                const mk_txt_color= mk_adjustColor(contrastType, baseColor);
                                                const gradientStop = adjustColor(baseColor, contrastType === 'light' ? -18 : 18);
                                                const backgroundStyle = `linear-gradient(135deg, ${baseColor} 0%, ${gradientStop} 100%)`;
                                                const logoSrc = item.icon || item.image || DEFAULT_ICON;
                                                const copyValue = String(item.value ?? item.url ?? item.text ?? item.number ?? item.code ?? '');
                                                const itemTitle = item.title || item.name || item.label || copyValue || '';
                                                const rgb = hexToRgb(baseColor);
                                                const nfcIconColor = getContrastColor(baseColor);
                                                const nfcSupported = isNfcSupported();
                                                const truncate = (str, n) => str && str.length > n ? str.slice(0, n) + '...' : str;
                                                const fullDesc = item.description || '';
                                                const descTrunc = truncate(fullDesc, 40);
    const valueTrunc = truncate(copyValue, 50);
                                                return `
                                                    <div class="swiper-slide stack-card" style="display:flex;align-items:center;justify-content:center;">
                                                        <div class="item-card elegant-card stack-tall-card"
                                                            style="--card-bg-color: ${baseColor}; --card-bg-gradient-stop: ${gradientStop}; background: ${backgroundStyle}; color: ${textColorVar}; --card-text-color: ${textColorVar}; --card-base-color-rgb: ${rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '128,128,128'}; --mika-glow-opacity: 0; min-width:clamp(260px,40vw,400px); max-width:80vw; display:flex;flex-direction:column;align-items:center;justify-content:flex-start;"
                                                            ${copyValue ? `data-copy-value="${escapeHtml(copyValue)}"` : ''}
                                                            data-description="${escapeHtml(item.description || '')}">
                                                            <div class="card-logo" aria-hidden="true" style="width:100%;max-width:150px;max-height:30vh;margin:0 auto 1.2rem auto;display:flex;align-items:center;justify-content:center;">
                                                                <img src="${escapeHtml(logoSrc)}" alt="" loading="lazy" style="width:100%;max-width:150px;max-height:30vh;object-fit:contain;border-radius:0; background:none; border:none; box-shadow:none;" onerror="this.src='${DEFAULT_ICON}'; this.onerror=null;">
                                                            </div>
                                                            <div class="card-text-block" style="width:100%;text-align:center;">
                                                                <h3 class="card-name" style="color:${mk_txt_color};">${escapeHtml(itemTitle)}</h3>
                                                                <p class="card-description card-text-p mkx" title="${escapeHtml(fullDesc)}" style="color: ${mk_txt_color};">${escapeHtml(descTrunc)}</p>
                                                            </div>
                                                            <div class="card-value-field" style="margin-top:1.2rem; color: ${mk_txt_color};">${escapeHtml(copyValue)}</div>
                                                            ${nfcSupported ? `
                                                                <button class="card-action-button nfc-action-button mt-2" title="Share via NFC" aria-label="NFC Share" style="background:none;border:none;outline:none;display:flex;align-items:center;justify-content:center;padding:0.1rem 0.5rem;cursor:pointer; margin-top:1rem;">
                                                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M2 12C2 6.48 6.48 2 12 2C17.52 2 22 6.48 22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12Z" stroke="${nfcIconColor}" stroke-width="2"/>
                                                                        <path d="M8 12C8 9.79 9.79 8 12 8C14.21 8 16 9.79 16 12C16 14.21 14.21 16 12 16C9.79 16 8 14.21 8 12Z" stroke="${nfcIconColor}" stroke-width="2"/>
                                                                        <circle cx="12" cy="12" r="1.5" fill="${nfcIconColor}"/>
                                                                    </svg>
                                                                </button>
                                                            ` : ''}
                                                        </div>
                                                    </div>`;
                                            }).join('')}
                    </div>
                  </div>
                </div>`;

            cardListViewsContainer.appendChild(view);
            attachCardEventListeners(view);

            // Stack view toggle logic
            const stackToggleBtn = view.querySelector('.stack-view-toggle');
            const cardGrid = view.querySelector('.card-grid');
            const cardStackView = view.querySelector('.card-stack-view');
            let stackSwiper = null;
            stackToggleBtn.addEventListener('click', () => {
              if (cardGrid.style.display !== 'none') {
                cardGrid.style.display = 'none';
                cardStackView.style.display = 'block';
                if (!stackSwiper && typeof Swiper !== 'undefined') {
                  stackSwiper = new Swiper(cardStackView.querySelector('.stack-swiper'), {
                    effect: 'cards',
                    grabCursor: true,
                    cardsEffect: { perSlideOffset: 8, perSlideRotate: 2, rotate: true, slideShadows: true },
                    navigation: false,
                    pagination: false,
                  });
                } else if (stackSwiper) {
                  stackSwiper.update();
                }
              } else {
                cardGrid.style.display = 'grid';
                cardStackView.style.display = 'none';
              }
            });

            // Add event listener for category dropdown change
            setTimeout(() => {
                const categoryDropdown = view.querySelector('.category-dropdown');
                if (categoryDropdown) {
                    categoryDropdown.addEventListener('change', (event) => {
                        const selectedCategoryId = event.target.value;
                        handleCategorySelect(selectedCategoryId);
                    });
                }

                const refreshBtn = view.querySelector('.refresh-button');
                if (refreshBtn) {
                    refreshBtn.style.display = 'inline-flex';
                    refreshBtn.addEventListener('click', () => {
                        if ('caches' in window) {
                            caches.keys().then(function(names) {
                                for (let name of names) caches.delete(name);
                            });
                        }
                        window.location.reload(true);
                    });
                }
            }, 0);
        });

        await Promise.all(viewPromises);
    };

    const initializeApp = async () => {
        try {
            console.log("ViewInfo: Initializing...");
            setupThemeToggle();

            if (!categorySwiperView || !cardListViewsContainer || !mainContainer) {
                console.log("ViewInfo: Essential containers missing. Assuming minimal view (e.g., login). Performing basic fade-in.");
                const containerToShow = mainContainer || body.children[0];
                if (containerToShow) gsap.to(containerToShow, { opacity: 1, duration: INITIAL_FADE_DURATION, ease: 'power1.out' });
                return;
            }

            gsap.set(mainContainer, { opacity: 0 });

            // --- Offline First Approach ---
            let isDataFromCache = false;
            try {
                const cachedData = localStorage.getItem('qaInfoWalletData');
                if (cachedData) {
                    jsonData = JSON.parse(cachedData);
                    if (jsonData && Array.isArray(jsonData.categories) && jsonData.categories.length > 0) {
                        console.log("ViewInfo: Data loaded from localStorage cache.");
                        isDataFromCache = true;
                        await buildUI();
                    }
                }
            } catch (e) {
                console.error("ViewInfo: Error reading from localStorage", e);
            }

            // --- Fetch from Network ---
            try {
                console.log("ViewInfo: Fetching data from network...");
                const response = await fetch(`${DATA_FILE_PATH}?t=${new Date().getTime()}`); // Bust cache
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const freshData = await response.json();

                if (!freshData || !Array.isArray(freshData.categories) || freshData.categories.length === 0) {
                    throw new Error("Fetched data invalid or missing 'categories' array.");
                }

                // --- Sync and Update ---
                const cachedDataString = JSON.stringify(jsonData);
                const freshDataString = JSON.stringify(freshData);

                if (cachedDataString !== freshDataString) {
                    console.log("ViewInfo: Data has changed. Updating UI and cache.");
                    jsonData = freshData;
                    localStorage.setItem('qaInfoWalletData', freshDataString);
                    await buildUI(); // Rebuild UI with fresh data
                    showToast('Data updated!', 'success');
                } else {
                    console.log("ViewInfo: Data is up to date.");
                    if (!isDataFromCache) {
                        await buildUI(); // If not already built from cache, build it now
                    }
                }
            } catch (error) {
                console.error("ViewInfo: FAILED TO FETCH FROM NETWORK:", error);
                if (!isDataFromCache) {
                    // If fetch fails and we have no cached data, show an error
                    throw new Error("Application could not be loaded. Please check your network connection.");
                } else {
                    // If fetch fails but we have cached data, inform the user they are offline
                    showToast('You are offline. Showing cached data.', 'info');
                }
            }

            // --- Final Fade-in ---
            console.log("ViewInfo: Initial App Fade-in...");
            gsap.to(mainContainer, {
                opacity: 1,
                duration: INITIAL_FADE_DURATION,
                ease: 'power1.out',
                onComplete: () => console.log("ViewInfo: Initialization and fade-in complete.")
            });

        } catch (error) {
            console.error("ViewInfo: FAILED TO INITIALIZE APP:", error);
            if (mainContainer) {
                mainContainer.innerHTML = `<div class="error-message" style="padding: 20px; color: red; text-align: center;">Error loading application data: ${escapeHtml(error.message)}. Please check the console or data file.</div>`;
                gsap.to(mainContainer, { opacity: 1, duration: 0.5 });
            }
        }
    };

    const buildUI = async () => {
        console.log("ViewInfo: Building UI...");
        await setupCategorySwiper();
        await createAndPopulateCardListViews();
        attachGlobalEventListeners();

        gsap.set(categorySwiperView, { display: 'block', visibility: 'visible', opacity: 1 });
        if (jsonData && jsonData.categories) {
            jsonData.categories.forEach(category => {
                const cardList = document.getElementById(`card-list-${category.id}`);
                if (cardList) gsap.set(cardList, { display: 'none', visibility: 'hidden', opacity: 0 });
            });
        }
        console.log("ViewInfo: UI build complete.");
    };

    // const createItemCardsHTML = (items) => {
    //     if (!Array.isArray(items)) {
    //         console.error("ViewInfo: createItemCardsHTML received non-array:", items);
    //         return '';
    //     }
    //     const nfcSupported = isNfcSupported();
    //     const truncate = (str, n) => str && str.length > n ? str.slice(0, n) + '...' : str;
    //     return items.map(item => {
    //         const baseColor = item.color || '#ff5001';
    //         const contrastColor = getContrastColor(baseColor);
    //         const contrastType = getContrastType(baseColor);
    //         const textColorVar = `var(--mika_text_${contrastType === 'light' ? 'light' : 'dark'}_base)`;
    //         const gradientStop = adjustColor(baseColor, contrastType === 'light' ? -18 : 18);
    //         const backgroundStyle = `linear-gradient(135deg, ${baseColor} 0%, ${gradientStop} 100%)`;
    //         const logoSrc = item.icon || item.image || DEFAULT_ICON;
    //         const copyValue = String(item.value ?? item.url ?? item.text ?? item.number ?? item.code ?? item.link ?? '');
    //         const itemTitle = item.title || item.name || item.label || copyValue || '';
    //         const rgb = hexToRgb(baseColor);
    //         const nfcIconColor = getContrastColor(baseColor);
    //         // Store full description but display truncated version
    //         const fullDesc = item.description || '';
    //         const descTrunc = truncate(fullDesc, 30);
    //         const valueTrunc = truncate(copyValue, 40);
    //         return `
    //             <div class="item-card elegant-card"
    //                  style="--card-bg-color: ${baseColor}; --card-bg-gradient-stop: ${gradientStop}; background: ${backgroundStyle}; color: ${textColorVar}; --card-text-color: ${textColorVar}; --card-base-color-rgb: ${rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '128,128,128'}; --mika-glow-opacity: 0;"
    //                  ${copyValue ? `data-copy-value="${escapeHtml(copyValue)}"` : ''}
    //                  data-description="${escapeHtml(fullDesc)}"
    //                 tabindex="0" aria-label="${escapeHtml(itemTitle)}: ${escapeHtml(fullDesc)}">
    //                 <div class="card-top-content card-grid-layout" style="display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 1.2rem;">
    //                     <div class="card-text-block" style="min-width:0;">
    //                         <h3 class="card-name" style="color: ${contrastColor};">${escapeHtml(itemTitle)}</h3>
    //                         <p class="card-description card-text-p" title="${escapeHtml(fullDesc)}" style="color: ${contrastColor};">${escapeHtml(descTrunc)}</p>
    //                     </div>
    //                     <div class="card-logo" aria-hidden="true" style="margin-left:0; margin-right:0; background:none; border:none; box-shadow:none; padding:0;">
    //                         <img src="${escapeHtml(logoSrc)}" alt="" loading="lazy" style="width:56px;height:56px;object-fit:contain;border-radius:12px; background:none; border:none; box-shadow:none;" onerror="this.src='${DEFAULT_ICON}'; this.onerror=null;">
    //                     </div>
    //                 </div>
    //                 <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.7rem;">
    //                     <button class="card-action-button nfc-action-button" title="Share via NFC" aria-label="NFC Share" style="background:none;border:none;outline:none;display:flex;align-items:center;justify-content:center;padding:0.5rem;cursor:pointer;" ${nfcSupported ? '' : 'disabled'}>
    //                         <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    //                             <path d="M2 12C2 6.48 6.48 2 12 2C17.52 2 22 6.48 22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12Z" stroke="${nfcIconColor}" stroke-width="2"/>
    //                             <path d="M8 12C8 9.79 9.79 8 12 8C14.21 8 16 9.79 16 12C16 14.21 14.21 16 12 16C9.79 16 8 14.21 8 12Z" stroke="${nfcIconColor}" stroke-width="2"/>
    //                             <circle cx="12" cy="12" r="1.5" fill="${nfcIconColor}"/>
    //                         </svg>
    //                     </button>
    //                 </div>
    //                 <div class="card-value-field" style="color: ${contrastColor};">${escapeHtml(valueTrunc)}</div>
    //             </div>`;
    //     }).join('');
    // };

    // In createItemCardsHTML function, update the card generation logic:
const createItemCardsHTML = (items) => {
  if (!Array.isArray(items)) {
    console.error("ViewInfo: createItemCardsHTML received non-array:", items);
    return '';
  }
  
  const nfcSupported = isNfcSupported();
  const truncate = (str, n) => str && str.length > n ? str.slice(0, n) + '...' : str;
  
  return items.map(item => {
    const baseColor = item.color || '#ff5001'; // Default gray if no color
    const contrastColor = getContrastColor(baseColor);
    
    // Calculate gradient stop based on contrast type
    const contrastType = getContrastType(baseColor);
    const gradientStop = adjustColor(baseColor, contrastType === 'light' ? -18 : 18);
    const backgroundStyle = `linear-gradient(135deg, ${baseColor} 0%, ${gradientStop} 100%)`;
     const mk_txt_color= mk_adjustColor(contrastType, baseColor);
    const logoSrc = item.icon || item.image || DEFAULT_ICON;
    const copyValue = String(item.value ?? item.url ?? item.text ?? item.number ?? item.code ?? item.link ?? '');
    const itemTitle = item.title || item.name || item.label || copyValue || '';
    const rgb = hexToRgb(baseColor);
    
    // Store full description but display truncated version
    const fullDesc = item.description || '';
    const descTrunc = truncate(fullDesc, 40);
    const valueTrunc = truncate(copyValue, 50);
    
    return `
      <div class="item-card elegant-card"
           style="--card-bg-color: ${baseColor}; --card-bg-gradient-stop: ${gradientStop}; 
                  background: ${backgroundStyle}; color: ${contrastColor};
                  --card-base-color-rgb: ${rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '128,128,128'}; 
                  --mika-glow-opacity: 0;"
           ${copyValue ? `data-copy-value="${escapeHtml(copyValue)}"` : ''}
           data-description="${escapeHtml(fullDesc)}"
          tabindex="0" aria-label="${escapeHtml(itemTitle)}: ${escapeHtml(fullDesc)}">
        <div class="card-top-content card-grid-layout" style="display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 1.2rem;">
            <div class="card-text-block" style="min-width:0;">
                <h3 class="card-name" style="color: ${mk_txt_color};">${escapeHtml(itemTitle)}</h3>
               <p class="card-description card-text-p mkx" title="${escapeHtml(fullDesc)}" style="color: ${mk_txt_color};">${escapeHtml(descTrunc)}</p>
            </div>
            <div class="card-logo" aria-hidden="true" style="margin-left:0; margin-right:0; background:none; border:none; box-shadow:none; padding:0;">
                <img src="${escapeHtml(logoSrc)}" alt="" loading="lazy" style="width:80px;height:80px;object-fit:contain;border-radius:12px; background:none; border:none; box-shadow:none;" onerror="this.src='${DEFAULT_ICON}'; this.onerror=null;">
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.7rem;">
            <button class="card-action-button nfc-action-button" title="Share via NFC" aria-label="NFC Share" style="background:none;border:none;outline:none;display:flex;align-items:center;justify-content:center;padding:0.5rem;cursor:pointer; color: ${contrastColor};" ${nfcSupported ? '' : 'disabled'}>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 12C2 6.48 6.48 2 12 2C17.52 2 22 6.48 22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12Z" stroke="${mk_txt_color}" stroke-width="2"/>
                    <path d="M8 12C8 9.79 9.79 8 12 8C14.21 8 16 9.79 16 12C16 14.21 14.21 16 12 16C9.79 16 8 14.21 8 12Z" stroke="${mk_txt_color}" stroke-width="2"/>
                    <circle cx="12" cy="12" r="1.5" fill="${mk_txt_color}"/>
                </svg>
            </button>
        </div>
        <div class="card-value-field" style="color: ${mk_txt_color};">${escapeHtml(valueTrunc)}</div>
      </div>`;
  }).join('');
};

    const initCustomCursor = () => {
        magicMouse();
    };

    initializeApp();

}); // End DOMContentLoaded