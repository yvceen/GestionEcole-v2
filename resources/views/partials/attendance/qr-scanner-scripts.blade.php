@once
    @push('head')
        <style>
            [data-qr-reader] {
                position: relative;
                min-height: 18rem;
                border-radius: 1.75rem;
                overflow: hidden;
                background:
                    radial-gradient(circle at top, rgba(14, 165, 233, 0.22), transparent 42%),
                    linear-gradient(180deg, #0f172a 0%, #020617 100%);
            }

            [data-qr-reader] video,
            [data-qr-reader] canvas {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover;
                background: #020617;
            }

            [data-qr-reader] > div {
                border: 0 !important;
                padding: 0 !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
            window.createAttendanceQrScanner = function (config = {}) {
                return {
                    scanCode: config.initialCode || '',
                    result: config.initialResult || null,
                    busy: false,
                    scanning: false,
                    isProcessing: false,
                    scannerLocked: false,
                    scanner: null,
                    audioContext: null,
                    cameraError: '',
                    records: config.records || [],
                    autoStart: config.autoStart !== false,

                    initController() {
                        window.addEventListener('pointerdown', () => this.unlockAudio(), { once: true, passive: true });
                        window.addEventListener('keydown', () => this.unlockAudio(), { once: true });
                        window.addEventListener('pagehide', () => this.stopScanner({ preserveLock: true }));
                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) {
                                this.stopScanner({ preserveLock: true });
                            }
                        });

                        this.$nextTick(() => {
                            if (this.autoStart && this.supportsCamera()) {
                                this.startScanner();
                            }
                        });
                    },

                    supportsCamera() {
                        return !!window.Html5Qrcode && !!navigator.mediaDevices?.getUserMedia;
                    },

                    isLikelyWebView() {
                        const ua = navigator.userAgent || '';

                        return !!window.Capacitor
                            || /\bwv\b/i.test(ua)
                            || /(iPhone|iPod|iPad)(?!.*Safari)/i.test(ua)
                            || /WebView/i.test(ua);
                    },

                    unlockAudio() {
                        if (typeof window.AudioContext === 'undefined' && typeof window.webkitAudioContext === 'undefined') {
                            return;
                        }

                        if (!this.audioContext) {
                            const Context = window.AudioContext || window.webkitAudioContext;
                            this.audioContext = new Context();
                        }

                        if (this.audioContext?.state === 'suspended') {
                            this.audioContext.resume().catch(() => {});
                        }
                    },

                    playTone(frequency, duration, offset = 0, gain = 0.07, type = 'sine') {
                        if (!this.audioContext) {
                            return;
                        }

                        const startAt = this.audioContext.currentTime + offset;
                        const oscillator = this.audioContext.createOscillator();
                        const amplifier = this.audioContext.createGain();

                        oscillator.type = type;
                        oscillator.frequency.value = frequency;
                        amplifier.gain.setValueAtTime(gain, startAt);
                        amplifier.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

                        oscillator.connect(amplifier);
                        amplifier.connect(this.audioContext.destination);
                        oscillator.start(startAt);
                        oscillator.stop(startAt + duration);
                    },

                    playPresentSound() {
                        this.unlockAudio();
                        this.playTone(988, 0.12, 0, 0.085, 'triangle');
                        this.playTone(1318, 0.16, 0.1, 0.082, 'triangle');
                    },

                    playLateSound() {
                        this.unlockAudio();
                        this.playTone(740, 0.11, 0, 0.08, 'sawtooth');
                        this.playTone(659, 0.12, 0.09, 0.075, 'sawtooth');
                    },

                    playErrorSound() {
                        this.unlockAudio();
                        this.playTone(392, 0.13, 0, 0.09, 'square');
                        this.playTone(262, 0.19, 0.1, 0.085, 'square');
                    },

                    resultVariant() {
                        if (!this.result) return 'neutral';
                        if (this.result.variant) return this.result.variant;
                        if (this.result.status === 'late') return 'late';
                        if (this.result.status === 'present') return 'present';
                        return 'error';
                    },

                    resultCardClass() {
                        return {
                            'border-emerald-200 bg-emerald-50 text-emerald-950': this.resultVariant() === 'present',
                            'border-amber-200 bg-amber-50 text-amber-950': this.resultVariant() === 'late',
                            'border-rose-200 bg-rose-50 text-rose-950': this.resultVariant() === 'error',
                            'border-slate-200 bg-slate-50 text-slate-900': this.resultVariant() === 'neutral',
                        };
                    },

                    resultBadgeClass() {
                        return {
                            'bg-emerald-600 text-white': this.resultVariant() === 'present',
                            'bg-amber-500 text-white': this.resultVariant() === 'late',
                            'bg-rose-600 text-white': this.resultVariant() === 'error',
                            'bg-slate-700 text-white': this.resultVariant() === 'neutral',
                        };
                    },

                    resultLabel() {
                        if (this.resultVariant() === 'present') return 'Present';
                        if (this.resultVariant() === 'late') return 'En retard';
                        if (this.resultVariant() === 'error') return 'Erreur';
                        return 'Scan';
                    },

                    ensureReaderId() {
                        if (!this.$refs.reader.id) {
                            this.$refs.reader.id = `attendance-qr-reader-${Math.random().toString(36).slice(2, 10)}`;
                        }

                        return this.$refs.reader.id;
                    },

                    preferredCameraId(cameras = []) {
                        if (!Array.isArray(cameras) || cameras.length === 0) {
                            return null;
                        }

                        const preferred = cameras.find((camera) => /back|rear|environment/i.test(camera.label || ''));

                        return (preferred || cameras[cameras.length - 1] || cameras[0])?.id || null;
                    },

                    qrBoxSize(width, height) {
                        const edge = Math.floor(Math.min(width, height) * 0.72);

                        return {
                            width: Math.max(180, Math.min(edge, 320)),
                            height: Math.max(180, Math.min(edge, 320)),
                        };
                    },

                    scannerConfig() {
                        return {
                            fps: 10,
                            qrbox: (width, height) => this.qrBoxSize(width, height),
                            aspectRatio: 1.333334,
                            disableFlip: false,
                            experimentalFeatures: {
                                useBarCodeDetectorIfSupported: true,
                            },
                        };
                    },

                    setReaderPreviewAttributes() {
                        const video = this.$refs.reader?.querySelector('video');
                        if (!video) {
                            return;
                        }

                        video.setAttribute('playsinline', 'true');
                        video.setAttribute('webkit-playsinline', 'true');
                        video.setAttribute('autoplay', 'true');
                        video.setAttribute('muted', 'true');
                        video.setAttribute('disablepictureinpicture', 'true');
                        video.muted = true;
                        video.autoplay = true;
                        video.playsInline = true;

                        if (video.style) {
                            video.style.objectFit = 'cover';
                            video.style.width = '100%';
                            video.style.height = '100%';
                            video.style.backgroundColor = '#020617';
                        }

                        video.play().catch(() => {});
                    },

                    cameraFailureMessage(error) {
                        const rawMessage = String(error?.message || error || '');
                        const lower = rawMessage.toLowerCase();

                        if (lower.includes('permission') || lower.includes('notallowed') || lower.includes('denied')) {
                            return this.isLikelyWebView()
                                ? 'La camera a ete refusee dans l application. Autorisez la camera puis relancez le scan.'
                                : 'La camera a ete refusee. Autorisez l acces puis relancez le scan.';
                        }

                        if (lower.includes('notfound') || lower.includes('overconstrained')) {
                            return 'Aucune camera compatible n a ete detectee. Utilisez la saisie manuelle si besoin.';
                        }

                        if (!this.supportsCamera()) {
                            return 'Le scan camera n est pas disponible sur cet appareil. Utilisez la saisie manuelle du code.';
                        }

                        return this.isLikelyWebView()
                            ? 'La camera ne demarre pas correctement dans l application. Utilisez la saisie manuelle ou relancez le scan.'
                            : 'La camera ne demarre pas correctement sur cet appareil. Utilisez la saisie manuelle ou relancez le scan.';
                    },

                    async startWithSource(source) {
                        this.scanner = new Html5Qrcode(this.ensureReaderId());

                        await this.scanner.start(
                            source,
                            this.scannerConfig(),
                            (decodedText) => this.handleDecodedText(decodedText),
                            () => {}
                        );

                        this.scanning = true;
                        this.cameraError = '';

                        this.$nextTick(() => {
                            this.setReaderPreviewAttributes();
                        });
                    },

                    async startScanner() {
                        this.unlockAudio();

                        if (this.scanning || this.isProcessing) {
                            return;
                        }

                        if (!this.supportsCamera()) {
                            this.cameraError = this.cameraFailureMessage('unsupported');
                            this.setErrorResult(this.cameraError);
                            return;
                        }

                        this.scanCode = '';
                        this.cameraError = '';
                        this.scannerLocked = false;

                        await this.stopScanner({ preserveLock: false });

                        try {
                            await this.startWithSource({ facingMode: { ideal: 'environment' } });
                        } catch (primaryError) {
                            try {
                                const cameras = await Html5Qrcode.getCameras();
                                const cameraId = this.preferredCameraId(cameras);

                                if (!cameraId) {
                                    throw primaryError;
                                }

                                await this.stopScanner({ preserveLock: false });
                                await this.startWithSource(cameraId);
                            } catch (fallbackError) {
                                await this.stopScanner({ preserveLock: false });
                                this.cameraError = this.cameraFailureMessage(fallbackError || primaryError);
                                this.setErrorResult(this.cameraError);
                            }
                        }
                    },

                    async stopScanner({ preserveLock = true } = {}) {
                        this.scanning = false;

                        const video = this.$refs.reader?.querySelector('video');
                        const scanner = this.scanner;
                        this.scanner = null;

                        if (!preserveLock) {
                            this.scannerLocked = false;
                        }

                        if (!scanner) {
                            return;
                        }

                        try {
                            await scanner.stop();
                        } catch (error) {}

                        try {
                            await scanner.clear();
                        } catch (error) {}

                        if (video?.srcObject) {
                            try {
                                video.srcObject.getTracks().forEach((track) => track.stop());
                            } catch (error) {}
                            video.srcObject = null;
                        }
                    },

                    async handleDecodedText(decodedText) {
                        const value = String(decodedText || '').trim();
                        if (!value || this.isProcessing || this.scannerLocked) {
                            return;
                        }

                        this.isProcessing = true;
                        this.scannerLocked = true;
                        this.scanCode = value;

                        await this.stopScanner({ preserveLock: true });
                        await this.submitCode({ fromScan: true });
                    },

                    async restartScanner() {
                        this.isProcessing = false;
                        this.scannerLocked = false;
                        this.cameraError = '';
                        this.scanCode = '';

                        await this.stopScanner({ preserveLock: false });
                        await this.startScanner();
                    },

                    async submitCode({ fromScan = false } = {}) {
                        const value = String(this.scanCode || '').trim();

                        if (!value || this.busy) {
                            this.isProcessing = false;
                            return;
                        }

                        this.unlockAudio();
                        this.busy = true;
                        this.scannerLocked = true;

                        if (!fromScan && this.scanning) {
                            await this.stopScanner({ preserveLock: true });
                        }

                        try {
                            const body = typeof config.buildRequestBody === 'function'
                                ? config.buildRequestBody.call(this, value)
                                : { [config.requestKey || 'code']: value };

                            const response = await fetch(config.endpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': config.csrfToken,
                                },
                                body: JSON.stringify(body),
                            });

                            const payload = await response.json();
                            if (!response.ok || payload.success === false) {
                                throw new Error(payload.message || 'Scan impossible.');
                            }

                            if (typeof config.onSuccess === 'function') {
                                config.onSuccess.call(this, payload);
                            } else {
                                this.result = payload;
                            }
                        } catch (error) {
                            this.setErrorResult(error?.message || 'Scan impossible.');
                        } finally {
                            this.busy = false;
                            this.isProcessing = false;
                        }
                    },

                    setErrorResult(message) {
                        if (typeof config.onError === 'function') {
                            config.onError.call(this, message);
                            return;
                        }

                        this.result = {
                            variant: 'error',
                            status: 'error',
                            student_name: 'Scan impossible',
                            message,
                        };
                    },
                };
            };
        </script>
    @endpush
@endonce
