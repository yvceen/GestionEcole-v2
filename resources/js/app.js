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

function initMyEduDatePickers() {
    const monthNames = [
        'Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre',
    ];
    const dayNames = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'];

    const pad = (value) => String(value).padStart(2, '0');
    const toIsoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const toIsoMonth = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}`;
    const parseIsoDate = (value) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) {
            return null;
        }

        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(year, month - 1, day);

        return Number.isNaN(date.getTime()) ? null : date;
    };
    const parseIsoMonth = (value) => {
        if (!/^\d{4}-\d{2}$/.test(value || '')) {
            return null;
        }

        const [year, month] = value.split('-').map(Number);
        const date = new Date(year, month - 1, 1);

        return Number.isNaN(date.getTime()) ? null : date;
    };
    const parseIsoDateTime = (value) => {
        if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(value || '')) {
            return null;
        }

        const [datePart, timePart] = value.split('T');
        const date = parseIsoDate(datePart);

        return date ? { date, time: (timePart || '08:00').slice(0, 5) } : null;
    };
    const formatDisplay = (value, mode = 'date') => {
        if (mode === 'month') {
            const date = parseIsoMonth(value);
            return date ? `${monthNames[date.getMonth()]} ${date.getFullYear()}` : '';
        }

        if (mode === 'datetime-local') {
            const parsed = parseIsoDateTime(value);
            return parsed ? `${pad(parsed.date.getDate())}/${pad(parsed.date.getMonth() + 1)}/${parsed.date.getFullYear()} ${parsed.time}` : '';
        }

        const date = parseIsoDate(value);
        return date ? `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}` : '';
    };

    const closeAll = (except = null) => {
        document.querySelectorAll('.myedu-date-picker.is-open').forEach((picker) => {
            if (picker !== except) {
                picker.classList.remove('is-open');
            }
        });
    };

    const buildPicker = (input) => {
        if (input.dataset.myeduDateReady === 'true') {
            return;
        }

        input.dataset.myeduDateReady = 'true';

        const mode = input.type;
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = input.name;
        hidden.value = input.value || '';
        hidden.dataset.myeduDateValue = 'true';

        const originalRequired = input.required;
        input.required = false;
        input.name = '';
        input.type = 'text';
        input.readOnly = true;
        input.autocomplete = 'off';
        input.inputMode = 'none';
        input.value = formatDisplay(hidden.value, mode);
        input.classList.add('myedu-date-display');
        input.placeholder = input.placeholder || (mode === 'month' ? 'Choisir un mois' : (mode === 'datetime-local' ? 'Choisir date et heure' : 'Choisir une date'));

        const wrapper = document.createElement('div');
        wrapper.className = 'myedu-date-picker';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        wrapper.appendChild(hidden);

        const icon = document.createElement('button');
        icon.type = 'button';
        icon.className = 'myedu-date-icon';
        icon.setAttribute('aria-label', 'Ouvrir le calendrier');
        icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4" y="5" width="16" height="15" rx="3"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg>';
        wrapper.appendChild(icon);

        const panel = document.createElement('div');
        panel.className = 'myedu-date-panel';
        wrapper.appendChild(panel);

        let visibleDate = mode === 'month'
            ? (parseIsoMonth(hidden.value) || new Date())
            : ((mode === 'datetime-local' ? parseIsoDateTime(hidden.value)?.date : parseIsoDate(hidden.value)) || new Date());
        const positionPanel = () => {
            if (!wrapper.classList.contains('is-open')) {
                return;
            }

            const rect = input.getBoundingClientRect();
            const panelWidth = Math.min(352, window.innerWidth - 32);
            const panelHeight = Math.min(panel.scrollHeight || 390, window.innerHeight - 32);
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            const left = Math.min(
                Math.max(16, rect.left),
                Math.max(16, window.innerWidth - panelWidth - 16)
            );
            let top = rect.bottom + 10;
            let openUp = false;

            if (top + panelHeight > window.innerHeight - 16) {
                if (spaceAbove >= Math.min(panelHeight, 260) || spaceAbove > spaceBelow) {
                    top = Math.max(16, rect.top - panelHeight - 10);
                    openUp = true;
                } else {
                    top = Math.max(16, window.innerHeight - panelHeight - 16);
                }
            }

            panel.style.width = `${panelWidth}px`;
            panel.style.maxHeight = `${Math.max(260, window.innerHeight - 32)}px`;
            panel.style.left = `${left}px`;
            panel.style.top = `${top}px`;
            panel.classList.toggle('opens-up', openUp);
        };

        const selectedTime = () => {
            const parsed = parseIsoDateTime(hidden.value);
            const timeInput = panel.querySelector('[data-time-input]');

            return timeInput?.value || parsed?.time || '08:00';
        };

        const syncDisplay = () => {
            input.value = formatDisplay(hidden.value, mode);
            input.dispatchEvent(new Event('change', { bubbles: true }));
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const setValue = (date, close = true) => {
            if (mode === 'month') {
                hidden.value = toIsoMonth(date);
            } else if (mode === 'datetime-local') {
                hidden.value = `${toIsoDate(date)}T${selectedTime()}`;
            } else {
                hidden.value = toIsoDate(date);
            }

            syncDisplay();

            if (close) {
                wrapper.classList.remove('is-open');
            }
        };

        const render = () => {
            const year = visibleDate.getFullYear();
            const month = visibleDate.getMonth();
            const selected = mode === 'month'
                ? parseIsoMonth(hidden.value)
                : (mode === 'datetime-local' ? parseIsoDateTime(hidden.value)?.date : parseIsoDate(hidden.value));
            const todayIso = toIsoDate(new Date());
            const firstDay = new Date(year, month, 1);
            const startOffset = (firstDay.getDay() + 6) % 7;
            const gridStart = new Date(year, month, 1 - startOffset);

            if (mode === 'month') {
                const selectedMonth = selected ? toIsoMonth(selected) : '';
                const currentMonth = toIsoMonth(new Date());
                const months = monthNames.map((name, index) => {
                    const monthDate = new Date(year, index, 1);
                    const iso = toIsoMonth(monthDate);

                    return `
                        <button type="button" class="myedu-month-choice${iso === selectedMonth ? ' is-selected' : ''}${iso === currentMonth ? ' is-today' : ''}" data-month="${iso}">
                            ${name.slice(0, 3)}
                        </button>
                    `;
                }).join('');

                panel.innerHTML = `
                    <div class="myedu-date-head">
                        <button type="button" class="myedu-date-nav" data-year-move="-1" aria-label="Annee precedente">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                        </button>
                        <div>
                            <p>Mois</p>
                            <strong>${year}</strong>
                        </div>
                        <button type="button" class="myedu-date-nav" data-year-move="1" aria-label="Annee suivante">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"></path></svg>
                        </button>
                    </div>
                    <div class="myedu-month-grid">${months}</div>
                    <div class="myedu-date-actions">
                        <button type="button" data-action="clear">Vider</button>
                        <button type="button" data-action="today">Ce mois</button>
                    </div>
                `;
                return;
            }

            const days = Array.from({ length: 42 }, (_, index) => {
                const day = new Date(gridStart);
                day.setDate(gridStart.getDate() + index);
                const iso = toIsoDate(day);
                const isSelected = selected && iso === toIsoDate(selected);
                const isToday = iso === todayIso;
                const isMuted = day.getMonth() !== month;

                return `
                    <button type="button" class="myedu-date-day${isSelected ? ' is-selected' : ''}${isToday ? ' is-today' : ''}${isMuted ? ' is-muted' : ''}" data-date="${iso}">
                        ${day.getDate()}
                    </button>
                `;
            }).join('');

            panel.innerHTML = `
                <div class="myedu-date-head">
                    <button type="button" class="myedu-date-nav" data-move="-1" aria-label="Mois precedent">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                    </button>
                    <div>
                        <p>${monthNames[month]}</p>
                        <strong>${year}</strong>
                    </div>
                    <button type="button" class="myedu-date-nav" data-move="1" aria-label="Mois suivant">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"></path></svg>
                    </button>
                </div>
                <div class="myedu-date-weekdays">
                    ${dayNames.map((day) => `<span>${day}</span>`).join('')}
                </div>
                <div class="myedu-date-grid">${days}</div>
                ${mode === 'datetime-local' ? `
                    <label class="myedu-time-field">
                        <span>Heure</span>
                        <input type="time" data-time-input value="${parseIsoDateTime(hidden.value)?.time || '08:00'}">
                    </label>
                ` : ''}
                <div class="myedu-date-actions">
                    <button type="button" data-action="clear">Vider</button>
                    <button type="button" data-action="today">Aujourd'hui</button>
                    ${mode === 'datetime-local' ? '<button type="button" data-action="done">Valider</button>' : ''}
                </div>
            `;
        };

        const open = () => {
            closeAll(wrapper);
            const currentValue = mode === 'month'
                ? parseIsoMonth(hidden.value)
                : (mode === 'datetime-local' ? parseIsoDateTime(hidden.value)?.date : parseIsoDate(hidden.value));
            if (currentValue) {
                visibleDate = new Date(currentValue.getFullYear(), currentValue.getMonth(), 1);
            }
            wrapper.classList.add('is-open');
            render();
            positionPanel();
        };

        input.addEventListener('click', open);
        icon.addEventListener('click', open);

        panel.addEventListener('click', (event) => {
            const moveButton = event.target.closest('[data-move]');
            if (moveButton) {
                visibleDate = new Date(visibleDate.getFullYear(), visibleDate.getMonth() + Number(moveButton.dataset.move), 1);
                render();
                positionPanel();
                return;
            }

            const yearMoveButton = event.target.closest('[data-year-move]');
            if (yearMoveButton) {
                visibleDate = new Date(visibleDate.getFullYear() + Number(yearMoveButton.dataset.yearMove), visibleDate.getMonth(), 1);
                render();
                positionPanel();
                return;
            }

            const monthButton = event.target.closest('[data-month]');
            if (monthButton) {
                const monthDate = parseIsoMonth(monthButton.dataset.month);
                if (monthDate) {
                    setValue(monthDate);
                }
                return;
            }

            const dayButton = event.target.closest('[data-date]');
            if (dayButton) {
                const date = parseIsoDate(dayButton.dataset.date);
                if (date) {
                    setValue(date, mode !== 'datetime-local');
                }
                return;
            }

            const timeInput = event.target.closest('[data-time-input]');
            if (timeInput && hidden.value) {
                const parsed = parseIsoDateTime(hidden.value);
                const date = parsed?.date || parseIsoDate(hidden.value.slice(0, 10)) || new Date();
                setValue(date, false);
                return;
            }

            const action = event.target.closest('[data-action]')?.dataset.action;
            if (action === 'clear') {
                hidden.value = '';
                input.value = '';
                wrapper.classList.remove('is-open');
                syncDisplay();
            }
            if (action === 'today') {
                setValue(new Date());
            }
            if (action === 'done') {
                wrapper.classList.remove('is-open');
            }
        });

        panel.addEventListener('change', (event) => {
            const timeInput = event.target.closest('[data-time-input]');
            if (!timeInput || mode !== 'datetime-local') {
                return;
            }

            const parsed = parseIsoDateTime(hidden.value);
            setValue(parsed?.date || new Date(), false);
        });

        input.form?.addEventListener('submit', (event) => {
            if (originalRequired && !hidden.value) {
                event.preventDefault();
                closeAll(wrapper);
                wrapper.classList.add('is-open');
                input.focus();
                render();
                positionPanel();
            }
        });

        window.addEventListener('resize', positionPanel, { passive: true });
        window.addEventListener('scroll', positionPanel, { passive: true });
    };

    document.querySelectorAll('input[type="date"]:not([data-native-date]), input[type="month"]:not([data-native-date]), input[type="datetime-local"]:not([data-native-date])').forEach(buildPicker);

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.myedu-date-picker')) {
            closeAll();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
}

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
        await persistToken(value);
    });

    plugin.addListener('registrationError', (error) => {
        console.error('[Push] Registration error:', error);
    });

    plugin.addListener('pushNotificationReceived', (notification) => {
    });

    plugin.addListener('pushNotificationActionPerformed', (action) => {
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMyEduDatePickers);
} else {
    initMyEduDatePickers();
}

Alpine.start();
initNativeAndroidPushRegistration();
