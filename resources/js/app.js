import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('ui', {
    hasShadow: false,
    unreadCount: 0,
    latestUnreadNotificationId: 0,
    messageUnreadCount: 0,
});

Alpine.data('appNavbar', ({ unreadCount = 0, latestUnreadNotificationId = 0, messageUnreadCount = 0, refreshUrl = '' } = {}) => ({
    mobileOpen: false,
    refreshTimer: null,
    audioContext: null,
    soundUnlocked: false,
    latestUnreadNotificationId: Number(latestUnreadNotificationId) || 0,
    lastPlayedNotificationId: Number(latestUnreadNotificationId) || 0,
    init() {
        this.setUiState({
            hasShadow: false,
            unreadCount,
            latestUnreadNotificationId,
            messageUnreadCount,
        });

        let storedSoundNotificationId = 0;
        try {
            storedSoundNotificationId = Number(window.localStorage.getItem('app:last-notification-sound-id') || 0) || 0;
        } catch (error) {
            storedSoundNotificationId = 0;
        }

        this.lastPlayedNotificationId = Math.max(
            storedSoundNotificationId,
            Number(latestUnreadNotificationId) || 0
        );

        const unlockSound = () => {
            this.soundUnlocked = true;
            this.ensureAudioContext();
            if (this.audioContext?.state === 'suspended') {
                this.audioContext.resume().catch(() => {});
            }
        };

        window.addEventListener('pointerdown', unlockSound, { once: true, passive: true });
        window.addEventListener('keydown', unlockSound, { once: true });

        this.handleScroll();
        window.addEventListener('scroll', () => this.handleScroll(), { passive: true });
        window.addEventListener('mobile-sidebar-changed', (event) => {
            this.mobileOpen = !!(event?.detail?.open);
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.mobileOpen = false;
            }
        });
        this.refreshCounts();
        this.refreshTimer = window.setInterval(() => this.refreshCounts(), 30000);
    },
    setUiState(nextState = {}) {
        const store = Alpine.store('ui');
        store.hasShadow = Boolean(nextState.hasShadow ?? store.hasShadow);
        store.unreadCount = Number(nextState.unreadCount ?? store.unreadCount) || 0;
        store.latestUnreadNotificationId = Number(nextState.latestUnreadNotificationId ?? store.latestUnreadNotificationId) || 0;
        store.messageUnreadCount = Number(nextState.messageUnreadCount ?? store.messageUnreadCount) || 0;
    },
    handleScroll() {
        this.setUiState({ hasShadow: window.scrollY > 4 });
    },
    async refreshCounts() {
        if (!refreshUrl) {
            return;
        }

        try {
            const response = await fetch(refreshUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const html = await response.text();
            const notificationMatch = html.match(/data-notification-unread="(\d+)"/);
            const latestNotificationMatch = html.match(/data-notification-latest-id="(\d+)"/);
            const messageMatch = html.match(/data-message-unread="(\d+)"/);

            if (notificationMatch && notificationMatch[1] !== undefined) {
                const nextUnreadCount = Number(notificationMatch[1]) || 0;
                const nextLatestId = latestNotificationMatch && latestNotificationMatch[1] !== undefined
                    ? Number(latestNotificationMatch[1]) || 0
                    : this.latestUnreadNotificationId;

                if (
                    nextUnreadCount > 0
                    && nextLatestId > this.latestUnreadNotificationId
                    && nextLatestId > this.lastPlayedNotificationId
                ) {
                    this.playNotificationSound(nextLatestId);
                }

                this.latestUnreadNotificationId = Math.max(this.latestUnreadNotificationId, nextLatestId);
                this.setUiState({
                    unreadCount: nextUnreadCount,
                    latestUnreadNotificationId: this.latestUnreadNotificationId,
                });
            }

            if (messageMatch && messageMatch[1] !== undefined) {
                this.setUiState({ messageUnreadCount: Number(messageMatch[1]) });
            }
        } catch (error) {
            this.setUiState();
        }
    },
    ensureAudioContext() {
        if (this.audioContext) {
            return this.audioContext;
        }

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return null;
        }

        this.audioContext = new AudioContextClass();
        return this.audioContext;
    },
    playNotificationSound(notificationId) {
        try {
            const context = this.ensureAudioContext();
            if (!context || (context.state === 'suspended' && !this.soundUnlocked)) {
                return;
            }

            if (context.state === 'suspended') {
                context.resume().catch(() => {});
                if (context.state === 'suspended') {
                    return;
                }
            }

            const now = context.currentTime;
            const gain = context.createGain();
            const firstTone = context.createOscillator();
            const secondTone = context.createOscillator();

            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.045, now + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.42);
            gain.connect(context.destination);

            firstTone.type = 'sine';
            firstTone.frequency.setValueAtTime(740, now);
            firstTone.connect(gain);
            firstTone.start(now);
            firstTone.stop(now + 0.16);

            secondTone.type = 'sine';
            secondTone.frequency.setValueAtTime(988, now + 0.17);
            secondTone.connect(gain);
            secondTone.start(now + 0.17);
            secondTone.stop(now + 0.38);

            this.lastPlayedNotificationId = Number(notificationId) || this.lastPlayedNotificationId;
            try {
                window.localStorage.setItem('app:last-notification-sound-id', String(this.lastPlayedNotificationId));
            } catch (error) {
                // Storage can be disabled in private contexts; sound dedupe still works in memory.
            }
        } catch (error) {
            // Browsers can block audio until user interaction; notification UI still updates normally.
        }
    },
}));

const createPortalShell = () => ({
    navigating: false,
    loadingLabel: 'Chargement en cours...',
    init() {
        const isManagedLink = (link) => {
            if (!link) {
                return false;
            }

            const href = link.getAttribute('href') || '';

            if (href === '' || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                return false;
            }

            if (
                link.hasAttribute('download')
                || link.dataset.noLoading === 'true'
                || href.includes('/attachments/')
                || href.includes('/receipts/')
                || href.includes('/download')
            ) {
                return false;
            }

            if (link.target && link.target !== '_self') {
                return false;
            }

            return true;
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('[data-portal-shell] a[href], [data-student-shell] a[href]');
            if (!isManagedLink(link) || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            this.loadingLabel = link.dataset.loadingLabel || 'Chargement de la page...';
            this.navigating = true;
        });

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('[data-portal-shell] form, [data-student-shell] form');
            if (!form || event.defaultPrevented) {
                return;
            }

            this.loadingLabel = form.dataset.loadingLabel || 'Traitement en cours...';
            this.navigating = true;
        });

        window.addEventListener('pageshow', () => {
            this.navigating = false;
        });
    },
});

Alpine.data('studentPortalShell', createPortalShell);
Alpine.data('portalShell', createPortalShell);

async function initNativeAndroidPushRegistration() {
    const cap = window.Capacitor;
    if (!cap || typeof cap.isNativePlatform !== 'function' || !cap.isNativePlatform()) {
        return;
    }

    const plugin = cap?.Plugins?.PushNotifications;
    if (!plugin) {
        console.warn('[Push] PushNotifications plugin unavailable. Run: npm i @capacitor/push-notifications && npx cap sync android');
        return;
    }

    if (window.__myeduPushInitDone) {
        return;
    }
    window.__myeduPushInitDone = true;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const persistToken = async (token) => {
        if (!token) {
            return;
        }

        try {
            const response = await fetch('/api/device-tokens', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                credentials: 'include',
                body: JSON.stringify({
                    token,
                    platform: 'android',
                }),
            });

            if (!response.ok) {
                console.warn('[Push] Token save failed with status', response.status);
            }
        } catch (error) {
            console.warn('[Push] Token save error', error);
        }
    };

    plugin.addListener('registration', async (token) => {
        const value = token?.value || '';
        console.log('[Push][Android] FCM token:', value);
        await persistToken(value);
    });

    plugin.addListener('registrationError', (error) => {
        console.error('[Push] Registration error:', error);
    });

    plugin.addListener('pushNotificationReceived', (notification) => {
        console.log('[Push] Notification received:', notification);
    });

    plugin.addListener('pushNotificationActionPerformed', (action) => {
        console.log('[Push] Notification action:', action);
    });

    try {
        const permissionStatus = await plugin.checkPermissions();
        let receive = permissionStatus?.receive || 'prompt';
        if (receive !== 'granted') {
            const requested = await plugin.requestPermissions();
            receive = requested?.receive || 'denied';
        }

        if (receive === 'granted') {
            await plugin.register();
        } else {
            console.warn('[Push] Notifications permission not granted:', receive);
        }
    } catch (error) {
        console.error('[Push] Unable to initialize registration:', error);
    }
}

Alpine.start();
initNativeAndroidPushRegistration();
