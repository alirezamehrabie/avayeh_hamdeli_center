import './bootstrap';
import 'bootstrap/dist/js/bootstrap.min.js';
import * as bootstrap from 'bootstrap';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker';
import { attendanceResultBanner, createAttendanceResultBannerState } from './attendance-result-banner';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

let qrScannerDependencies = null;

const loadQrScannerDependencies = async () => {
    if (!qrScannerDependencies) {
        qrScannerDependencies = Promise.all([
            import('html5-qrcode'),
            import('./qr-scanner-enhancer'),
        ]).then(([html5QrCode, scannerEnhancer]) => ({
            Html5Qrcode: html5QrCode.Html5Qrcode,
            Html5QrcodeSupportedFormats: html5QrCode.Html5QrcodeSupportedFormats,
            createEnhancedQrScanner: scannerEnhancer.createEnhancedQrScanner,
        })).catch((error) => {
            qrScannerDependencies = null;
            throw error;
        });
    }

    return qrScannerDependencies;
};

Alpine.data('rialAmountInput', (model) => ({
    model,
    formatted: '',
    init() {
        this.formatted = this.format(this.model);

        this.$watch('model', (value) => {
            this.formatted = this.format(value);
        });
    },
    normalize(value) {
        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
        const arabicDigits = '٠١٢٣٤٥٦٧٨٩';

        return String(value ?? '')
            .replace(/[۰-۹]/g, digit => persianDigits.indexOf(digit))
            .replace(/[٠-٩]/g, digit => arabicDigits.indexOf(digit))
            .replace(/\D/g, '');
    },
    format(value) {
        const digits = this.normalize(value);

        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },
    formatInput() {
        const digits = this.normalize(this.formatted);

        this.formatted = this.format(digits);
        this.model = digits;
    },
}));

Alpine.data('pressHoldPreview', ({ delay = 260 } = {}) => ({
    previewOpen: false,
    holdTimer: null,
    previewWasShown: false,
    beginPreview() {
        this.previewWasShown = false;
        window.clearTimeout(this.holdTimer);
        this.holdTimer = window.setTimeout(() => {
            this.previewOpen = true;
            this.previewWasShown = true;
        }, delay);
    },
    endPreview() {
        window.clearTimeout(this.holdTimer);
        this.holdTimer = null;
        this.previewOpen = false;
    },
    handleCardClick(event) {
        if (!this.previewWasShown) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        this.previewWasShown = false;
    },
}));

const normalizeLocaleDigits = (value) => {
    const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
    const arabicDigits = '٠١٢٣٤٥٦٧٨٩';

    return String(value ?? '')
        .replace(/[۰-۹]/g, digit => persianDigits.indexOf(digit))
        .replace(/[٠-٩]/g, digit => arabicDigits.indexOf(digit));
};

const normalizeJalaliDateTimeValue = (value) => normalizeLocaleDigits(value)
    .replace(/[‌‏ ]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

Alpine.data('jalaliDateTimeField', (model) => ({
    model,
    draft: '',
    committedValue: '',
    confirmedDuringSession: false,
    init() {
        this.draft = this.model ?? '';
        this.committedValue = this.model ?? '';

        this.$watch('model', (value) => {
            const normalizedValue = normalizeJalaliDateTimeValue(value ?? '');

            if (normalizedValue === this.committedValue && normalizedValue === this.draft) {
                return;
            }

            this.draft = normalizedValue;
            this.committedValue = normalizedValue;

            if (this.$refs.input) {
                this.$refs.input.value = normalizedValue;
            }
        });
    },
    handlePickerOpen() {
        this.confirmedDuringSession = false;
        this.committedValue = normalizeJalaliDateTimeValue(this.model ?? '');
        this.draft = normalizeJalaliDateTimeValue(this.$refs.input?.value ?? this.draft ?? '');
    },
    handlePickerClose() {
        if (this.confirmedDuringSession) {
            this.confirmedDuringSession = false;
            return;
        }

        this.draft = this.committedValue;

        if (this.$refs.input) {
            this.$refs.input.value = this.committedValue;
        }
    },
    syncFromInput() {
        const normalizedValue = normalizeJalaliDateTimeValue(this.$refs.input?.value ?? this.draft ?? '');

        this.draft = normalizedValue;
        this.committedValue = normalizedValue;
        this.model = normalizedValue;

        if (this.$refs.input) {
            this.$refs.input.value = normalizedValue;
        }
    },
    confirm() {
        this.confirmedDuringSession = true;
        this.syncFromInput();
    },
}));

Alpine.data('idCardScanner', ({
    resolveScan,
    successSoundUrl = '',
    activityName = '',
    enableResultBanner = true,
    autoStart = true,
    autoResumeAfterError = true,
    autoResumeAfterSuccess = true,
}) => ({
    ...attendanceResultBanner(),
    cameras: [],
    selectedDeviceId: '',
    html5QrCode: null,
    scannerElementId: '',
    enhancedScanner: null,
    fallbackTimer: null,
    cameraCapabilities: {},
    cameraSettings: {},
    torchEnabled: false,
    zoomLevel: 1,
    cameraActive: false,
    startingCamera: false,
    scanning: false,
    resolvingScan: false,
    status: 'initializing',
    message: 'در حال آماده‌سازی دوربین...',
    Html5Qrcode: null,
    Html5QrcodeSupportedFormats: null,
    createEnhancedQrScanner: null,
    lastDecodedText: '',
    lastDecodedAt: 0,
    lastNativeDecodeAt: 0,
    fallbackEnabledAt: 0,
    resumeAfterSuccessTimer: null,
    successBannerTimer: null,
    nextScanShortcutActive: false,
    nextScanFlashTimer: null,
    audioContext: null,
    scanSuccessSoundUrl: successSoundUrl,
    scanSuccessAudio: null,
    activityName,
    enableResultBanner,
    autoResumeAfterError,
    autoResumeAfterSuccess,
    successBanner: createAttendanceResultBannerState(),
    async init() {
        this.prepareSuccessSound();
        this.ensurePrimaryViewportVisible();

        if (!('mediaDevices' in navigator) || !('getUserMedia' in navigator.mediaDevices)) {
            this.setStatus('unsupported', 'دسترسی به دوربین در این مرورگر یا دستگاه در دسترس نیست.');
            return;
        }

        try {
            const dependencies = await loadQrScannerDependencies();

            this.Html5Qrcode = dependencies.Html5Qrcode;
            this.Html5QrcodeSupportedFormats = dependencies.Html5QrcodeSupportedFormats;
            this.createEnhancedQrScanner = dependencies.createEnhancedQrScanner;

            this.scannerElementId = this.$refs.scanner.id;
            this.enhancedScanner = this.createEnhancedQrScanner();
            this.html5QrCode = new this.Html5Qrcode(this.scannerElementId, {
                verbose: false,
                formatsToSupport: [this.Html5QrcodeSupportedFormats.QR_CODE],
                useBarCodeDetectorIfSupported: true,
            });

            if (autoStart) {
                await this.ensureCameraPermission();
                await this.loadCameras();
                await this.startCamera();
            } else {
                this.setStatus('ready', 'دوربین آماده فعال‌سازی است.');
            }
        } catch (error) {
            this.setStatus('camera_denied', 'فعال‌سازی دوربین انجام نشد. دسترسی مرورگر به دوربین را بررسی کنید.');
        }
    },
    async ensureCameraPermission() {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        });

        stream.getTracks().forEach((track) => track.stop());
    },
    async loadCameras() {
        const devices = await this.Html5Qrcode.getCameras();
        this.cameras = devices
            .map((device, index) => ({
                id: device.id,
                label: device.label || `دوربین ${index + 1}`,
            }))
            .sort((left, right) => this.cameraScore(right) - this.cameraScore(left));

        if (!this.selectedDeviceId && this.cameras.length) {
            this.selectedDeviceId = this.cameras[0].id;
        }
    },
    cameraScore(camera) {
        const label = (camera?.label || '').toLowerCase();
        let score = 0;

        if (/(back|rear|environment|world|external|usb)/.test(label)) {
            score += 10;
        }

        if (/(front|user|face)/.test(label)) {
            score -= 10;
        }

        return score;
    },
    async startCamera() {
        if (this.startingCamera) {
            return;
        }

        try {
            this.startingCamera = true;
            this.setStatus('initializing', 'در حال فعال‌سازی دوربین...');
            await this.stopCamera();
            this.$refs.scanner.innerHTML = '';
            await this.loadCameras();

            const preferredCamera = this.resolvePreferredCamera();
            const started = await this.tryStartScanner(preferredCamera);

            if (!started) {
                throw new Error('No available camera could be started.');
            }

            this.stabilizePreview();
            await this.waitForPreview();
            this.ensurePrimaryViewportVisible();
            this.optimizeRunningCamera();
            this.cameraActive = true;
            this.scanning = true;
            this.resolvingScan = false;
            this.lastNativeDecodeAt = 0;
            this.fallbackEnabledAt = Date.now() + 2200;
            this.startFallbackLoop();
            this.setStatus('scanning', 'اسکن زنده فعال است. QR را مقابل دوربین نگه دارید.');
        } catch (error) {
            const message = error?.message || '';
            this.setStatus(
                'camera_denied',
                message !== ''
                    ? `فعال‌سازی دوربین یا اسکنر انجام نشد: ${message}`
                    : 'مرورگر اجازه دسترسی به دوربین را نداد یا دوربین در دسترس نیست.'
            );
        } finally {
            this.startingCamera = false;
        }
    },
    async switchCamera() {
        if (!this.selectedDeviceId) {
            return;
        }

        await this.startCamera();
    },
    async cycleCamera() {
        if (this.cameras.length < 2) {
            return;
        }

        const currentIndex = this.cameras.findIndex((camera) => camera.id === this.selectedDeviceId);
        const nextIndex = currentIndex >= 0 ? (currentIndex + 1) % this.cameras.length : 0;
        this.selectedDeviceId = this.cameras[nextIndex].id;
        await this.switchCamera();
    },
    pauseFromWire() {
        this.scanning = false;
        this.stopFallbackLoop();
        this.setStatus('paused', 'اسکن پس از شناسایی موفق متوقف شد.');
    },
    resumeFromWire() {
        this.resumeScan();
    },
    // Keyboard shortcut (Ctrl + Enter) mirror of the "اسکن نفر بعدی" button.
    // Runs the exact same server logic (resumeScanning) and flashes the button
    // so the operator gets immediate visual confirmation the shortcut fired.
    triggerNextScanShortcut() {
        if (this.resolvingScan) {
            return;
        }

        this.nextScanShortcutActive = true;
        if (this.nextScanFlashTimer) {
            clearTimeout(this.nextScanFlashTimer);
        }
        this.nextScanFlashTimer = setTimeout(() => {
            this.nextScanShortcutActive = false;
        }, 260);

        this.$wire.resumeScanning();
    },
    resumeScan({ clearLastDecoded = true } = {}) {
        if (!this.cameraActive) {
            this.startCamera();
            return;
        }

        if (clearLastDecoded) {
            this.lastDecodedText = '';
            this.lastDecodedAt = 0;
        }

        this.resolvingScan = false;
        this.scanning = true;
        this.lastNativeDecodeAt = 0;
        this.fallbackEnabledAt = Date.now() + 1200;
        this.startFallbackLoop();

        try {
            this.html5QrCode?.resume();
            this.stabilizePreview();
        } catch (error) {
            this.startCamera();
            return;
        }

        this.setStatus('scanning', 'اسکن دوباره فعال شد. QR را مقابل دوربین نگه دارید.');
    },
    async stopCamera() {
        this.scanning = false;
        this.resolvingScan = false;
        this.stopFallbackLoop();
        this.torchEnabled = false;
        this.zoomLevel = 1;

        if (this.html5QrCode && (this.cameraActive || this.html5QrCode.isScanning)) {
            try {
                await this.html5QrCode.stop();
            } catch (error) {
                // Ignore stop errors; the next start attempt should still proceed.
            }
        }

        this.cameraActive = false;
    },
    resolvePreferredCamera() {
        if (this.selectedDeviceId && this.cameras.some((camera) => camera.id === this.selectedDeviceId)) {
            return this.selectedDeviceId;
        }

        if (this.cameras.length) {
            this.selectedDeviceId = this.cameras[0].id;
            return this.selectedDeviceId;
        }

        this.selectedDeviceId = '';

        return null;
    },
    cameraScanConfig() {
        return {
            fps: 18,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const boxSize = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.68);

                return {
                    width: Math.min(viewfinderWidth, Math.max(280, boxSize)),
                    height: Math.min(viewfinderHeight, Math.max(280, boxSize)),
                };
            },
            disableFlip: false,
        };
    },
    async handleDecode(decodedText, source = 'native') {
        if (!this.scanning || this.resolvingScan) {
            return;
        }

        if (source === 'native') {
            this.lastNativeDecodeAt = Date.now();
        }

        const value = (decodedText || '').trim();
        const now = Date.now();

        if (!value) {
            return;
        }

        if (value === this.lastDecodedText && (now - this.lastDecodedAt) <= 2500) {
            return;
        }

        this.lastDecodedText = value;
        this.lastDecodedAt = now;
        this.resolvingScan = true;
        this.scanning = false;
        this.html5QrCode?.pause(false);
        this.setStatus('paused', 'QR شناسایی شد. در حال دریافت اطلاعات...');
        try {
            const response = await resolveScan(value, this);

            if (response?.result) {
                const feedbackVariant = response?.result?.code === 'duplicate' ? 'warning' : 'success';

                if (response?.ok) {
                    this.vibrateOnSuccess();
                    this.playFeedbackSound(feedbackVariant);
                }

                if (this.enableResultBanner) {
                    this.showResultBanner(response.result, response?.message);
                }

                if (response?.ok) {
                    if (this.autoResumeAfterSuccess) {
                        this.scheduleResumeAfterSuccess();
                    } else {
                        // Stay paused so the operator can act on the result (e.g. assign categories)
                        // before deliberately scanning the next subject.
                        this.resolvingScan = false;
                    }
                } else if (this.autoResumeAfterError) {
                    this.scheduleResumeAfterSuccess();
                } else {
                    this.resolvingScan = false;
                }
            }

            this.setStatus(
                response?.ok ? 'paused' : (response?.status || 'scan_error'),
                response?.message || (response?.ok ? 'اطلاعات شناسایی شد.' : 'دریافت اطلاعات انجام نشد.')
            );
        } catch (error) {
            if (this.enableResultBanner) {
                this.showResultBanner({
                    ok: false,
                    code: 'processing_failed',
                    message: 'خطا در پردازش کد',
                });
            }
            this.scheduleResumeAfterSuccess();
            this.resolvingScan = false;
            this.setStatus('scan_error', 'دریافت اطلاعات QR انجام نشد. دوباره تلاش کنید.');
        }
    },
    scheduleResumeAfterSuccess() {
        if (this.resumeAfterSuccessTimer) {
            window.clearTimeout(this.resumeAfterSuccessTimer);
        }

        this.resumeAfterSuccessTimer = window.setTimeout(() => {
            this.resumeAfterSuccessTimer = null;
            this.resumeScan({ clearLastDecoded: false });
        }, 0);
    },
    prepareSuccessSound() {
        if (typeof Audio === 'undefined' || !this.scanSuccessSoundUrl) {
            return;
        }

        this.scanSuccessAudio = new Audio(this.scanSuccessSoundUrl);
        this.scanSuccessAudio.preload = 'auto';
    },
    playFeedbackSound(variant = 'success') {
        if (variant === 'success' && this.playCustomSuccessSound()) {
            return;
        }

        this.playGeneratedFeedbackSound(variant);
    },
    playCustomSuccessSound() {
        if (!this.scanSuccessAudio) {
            return false;
        }

        try {
            this.scanSuccessAudio.pause();
            this.scanSuccessAudio.currentTime = 0;

            const playback = this.scanSuccessAudio.play();

            if (playback && typeof playback.catch === 'function') {
                playback.catch(() => this.playGeneratedFeedbackSound('success'));
            }

            return true;
        } catch (error) {
            return false;
        }
    },
    playGeneratedFeedbackSound(variant = 'success') {
        if (typeof window === 'undefined' || !('AudioContext' in window || 'webkitAudioContext' in window)) {
            return;
        }

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        this.audioContext = this.audioContext || new AudioContextClass();

        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        const now = this.audioContext.currentTime;

        if (variant === 'warning') {
            this.playWarningChime(now);
            return;
        }

        this.playScannerChirp(now);
    },
    playScannerChirp(startTime) {
        const output = this.audioContext.createGain();
        output.gain.setValueAtTime(0.82, startTime);
        output.gain.exponentialRampToValueAtTime(0.001, startTime + 0.19);
        output.connect(this.audioContext.destination);

        this.playTone({
            type: 'triangle',
            frequency: 1320,
            startTime,
            duration: 0.155,
            volume: 0.15,
            attack: 0.001,
            releasePadding: 0.02,
            frequencyEnd: 3150,
            destination: output,
        });

        this.playTone({
            type: 'square',
            frequency: 2350,
            startTime: startTime + 0.012,
            duration: 0.078,
            volume: 0.052,
            attack: 0.001,
            releasePadding: 0.012,
            frequencyEnd: 3850,
            destination: output,
        });

        this.playTone({
            type: 'triangle',
            frequency: 3050,
            startTime: startTime + 0.088,
            duration: 0.066,
            volume: 0.04,
            attack: 0.001,
            releasePadding: 0.014,
            frequencyEnd: 2500,
            destination: output,
        });
    },
    playWarningChime(startTime) {
        this.playTone({
            type: 'sine',
            frequency: 720,
            startTime,
            duration: 0.075,
            volume: 0.028,
            attack: 0.01,
            releasePadding: 0.02,
            frequencyEnd: 660,
        });

        this.playTone({
            type: 'sine',
            frequency: 620,
            startTime: startTime + 0.085,
            duration: 0.085,
            volume: 0.024,
            attack: 0.012,
            releasePadding: 0.024,
            frequencyEnd: 560,
        });
    },
    playTone({
        frequency,
        startTime,
        duration,
        volume,
        type = 'sine',
        attack = 0.008,
        releasePadding = 0.02,
        frequencyEnd = null,
        destination = null,
    }) {
        const oscillator = this.audioContext.createOscillator();
        const gain = this.audioContext.createGain();

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, startTime);
        if (frequencyEnd !== null) {
            oscillator.frequency.exponentialRampToValueAtTime(frequencyEnd, startTime + duration);
        }
        gain.gain.setValueAtTime(0.001, startTime);
        gain.gain.exponentialRampToValueAtTime(volume, startTime + attack);
        gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

        oscillator.connect(gain);
        gain.connect(destination || this.audioContext.destination);
        oscillator.start(startTime);
        oscillator.stop(startTime + duration + releasePadding);
    },
    vibrateOnSuccess() {
        if (typeof navigator === 'undefined' || typeof navigator.vibrate !== 'function') {
            return;
        }

        navigator.vibrate([60, 20, 60]);
    },
    startFallbackLoop() {
        this.stopFallbackLoop();

        this.fallbackTimer = window.setInterval(async () => {
            if (!this.scanning || this.resolvingScan || !this.enhancedScanner) {
                return;
            }

            if (Date.now() < this.fallbackEnabledAt) {
                return;
            }

            if (this.lastNativeDecodeAt && Date.now() - this.lastNativeDecodeAt < 1800) {
                return;
            }

            const video = this.currentVideoElement();

            if (!video) {
                return;
            }

            const decoded = await this.enhancedScanner.decodeVideo(video);

            if (decoded) {
                await this.handleDecode(decoded, 'fallback');
            }
        }, 900);
    },
    stopFallbackLoop() {
        if (this.fallbackTimer) {
            window.clearInterval(this.fallbackTimer);
            this.fallbackTimer = null;
        }
    },
    currentVideoElement() {
        return this.$refs.scanner?.querySelector('video') || null;
    },
    async tryStartScanner(preferredCamera) {
        const startAttempts = [];

        if (preferredCamera) {
            startAttempts.push(preferredCamera);
            startAttempts.push({
                deviceId: { exact: preferredCamera },
                width: { ideal: 1920, min: 640 },
                height: { ideal: 1080, min: 480 },
                frameRate: { ideal: 30, min: 15 },
            });
        }

        startAttempts.push({
            facingMode: 'environment',
            width: { ideal: 1920, min: 640 },
            height: { ideal: 1080, min: 480 },
            frameRate: { ideal: 30, min: 15 },
        });
        startAttempts.push({
            facingMode: 'environment',
            width: { ideal: 1280, min: 640 },
            height: { ideal: 720, min: 480 },
            frameRate: { ideal: 24, min: 12 },
        });

        for (const camera of this.cameras) {
            if (camera.id !== preferredCamera) {
                startAttempts.push(camera.id);
            }
        }

        for (const attempt of startAttempts) {
            try {
                await this.html5QrCode.start(
                    attempt,
                    this.cameraScanConfig(),
                    (decodedText) => this.handleDecode(decodedText),
                    () => {}
                );

                if (typeof attempt === 'string') {
                    this.selectedDeviceId = attempt;
                }

                return true;
            } catch (error) {
                this.$refs.scanner.innerHTML = '';
            }
        }

        return false;
    },
    stabilizePreview() {
        requestAnimationFrame(() => {
            const scanner = this.$refs.scanner;

            if (!scanner) {
                return;
            }

            scanner.querySelectorAll('video, canvas').forEach((element) => {
                element.style.width = '100%';
                element.style.height = '100%';
                element.style.objectFit = 'cover';
            });
        });
    },
    ensurePrimaryViewportVisible() {
        const root = this.$root;

        if (!root) {
            return;
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                root.scrollIntoView({
                    block: 'start',
                    inline: 'nearest',
                    behavior: 'instant',
                });
            });
        });
    },
    async waitForPreview() {
        const scanner = this.$refs.scanner;

        for (let attempt = 0; attempt < 20; attempt++) {
            const video = scanner?.querySelector('video');

            if (video && video.srcObject) {
                return;
            }

            await new Promise((resolve) => setTimeout(resolve, 100));
        }

        throw new Error('Camera preview was not attached.');
    },
    async optimizeRunningCamera() {
        requestAnimationFrame(async () => {
            if (!this.html5QrCode?.isScanning) {
                return;
            }

            try {
                this.cameraCapabilities = this.html5QrCode.getRunningTrackCapabilities?.() || {};
                this.cameraSettings = this.html5QrCode.getRunningTrackSettings?.() || {};

                const constraints = {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    frameRate: { ideal: 30, min: 15 },
                    advanced: [],
                };

                const advanced = constraints.advanced;
                const capabilities = this.cameraCapabilities;

                if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
                    advanced.push({ focusMode: 'continuous' });
                }

                if (Array.isArray(capabilities.exposureMode) && capabilities.exposureMode.includes('continuous')) {
                    advanced.push({ exposureMode: 'continuous' });
                }

                if (capabilities.zoom?.max && capabilities.zoom.max > 1) {
                    const zoom = Math.min(capabilities.zoom.max, Math.max(capabilities.zoom.min || 1, 2));
                    advanced.push({ zoom });
                }

                await this.html5QrCode.applyVideoConstraints(constraints);
                this.cameraSettings = this.html5QrCode.getRunningTrackSettings?.() || {};
                this.torchEnabled = Boolean(this.cameraSettings.torch);
                this.zoomLevel = Number(this.cameraSettings.zoom || 1);
            } catch (error) {
                // Some webcams do not expose focus/exposure constraints; scanning can continue.
            }
        });
    },
    supportsTorch() {
        return Boolean(this.cameraCapabilities?.torch);
    },
    supportsZoom() {
        return Boolean(this.cameraCapabilities?.zoom?.max && this.cameraCapabilities.zoom.max > 1);
    },
    zoomMin() {
        return Number(this.cameraCapabilities?.zoom?.min || 1);
    },
    zoomMax() {
        return Number(this.cameraCapabilities?.zoom?.max || 1);
    },
    zoomStep() {
        return Number(this.cameraCapabilities?.zoom?.step || 0.1);
    },
    async applyCameraAdvancedConstraints(advancedConstraints) {
        if (!this.html5QrCode?.isScanning) {
            return;
        }

        try {
            await this.html5QrCode.applyVideoConstraints({
                advanced: [advancedConstraints],
            });
            this.cameraSettings = this.html5QrCode.getRunningTrackSettings?.() || {};
            this.torchEnabled = Boolean(this.cameraSettings.torch);
            this.zoomLevel = Number(this.cameraSettings.zoom || this.zoomLevel || 1);
        } catch (error) {
            this.cameraSettings = this.html5QrCode.getRunningTrackSettings?.() || this.cameraSettings;
        }
    },
    async toggleTorch() {
        if (!this.supportsTorch()) {
            return;
        }

        await this.applyCameraAdvancedConstraints({ torch: !this.torchEnabled });
    },
    async applyZoom() {
        if (!this.supportsZoom()) {
            return;
        }

        const zoom = Math.min(this.zoomMax(), Math.max(this.zoomMin(), Number(this.zoomLevel || this.zoomMin())));
        this.zoomLevel = zoom;
        await this.applyCameraAdvancedConstraints({ zoom });
    },
    setStatus(status, message = '') {
        this.status = status;

        if (message !== '') {
            this.message = message;
        }
    },
    statusLabel() {
        return {
            initializing: 'در حال آماده‌سازی',
            ready: 'آماده',
            scanning: 'در حال اسکن',
            paused: 'متوقف',
            camera_denied: 'خطای دوربین',
            unsupported: 'پشتیبانی نمی‌شود',
            scan_error: 'خطای اسکن',
        }[this.status] || this.status;
    },
    destroy() {
        if (this.resumeAfterSuccessTimer) {
            window.clearTimeout(this.resumeAfterSuccessTimer);
            this.resumeAfterSuccessTimer = null;
        }

        this.clearResultBannerTimer();

        this.stopFallbackLoop();
        this.stopCamera();
    },
}));

const initializeJalaliDateTimePickers = () => {
    if (!window.jalaliDatepicker) {
        return;
    }

    const options = {
        time: true,
        hasSecond: false,
        autoHide: true,
        hideAfterChange: false,
        showTodayBtn: true,
        showEmptyBtn: true,
        showCloseBtn: true,
        showSelectTimeBtnAlways: false,
        autoReadOnlyInput: false,
        useDropDownYears: true,
        persianDigits: true,
        separatorChars: {
            date: '/',
            between: ' ',
            time: ':',
        },
    };

    if (window.__jalaliDatepickerInitialized) {
        window.jalaliDatepicker.updateOptions(options);
        return;
    }

    window.jalaliDatepicker.startWatch(options);
    window.__jalaliDatepickerInitialized = true;

    document.querySelectorAll('input[data-jdp]').forEach((input) => {
        if (input.hasAttribute('data-jdp-readonly')) {
            input.setAttribute('readonly', 'readonly');
            input.readOnly = true;
            input.setAttribute('inputmode', 'none');
            return;
        }

        input.removeAttribute('readonly');
        input.readOnly = false;
    });
};

const dispatchJalaliPickerEvent = (input, eventName) => {
    if (!input) {
        return;
    }

    input.dispatchEvent(new CustomEvent(eventName, {
        bubbles: true,
    }));
};

const ensureJalaliConfirmButton = () => {
    const container = document.querySelector('.jdp-container');
    const footer = container?.querySelector('.jdp-footer');

    if (!footer || footer.querySelector('[data-jdp-confirm-btn]')) {
        return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-primary btn-sm ms-2';
    button.textContent = 'تأیید';
    button.setAttribute('data-jdp-confirm-btn', 'true');
    button.addEventListener('click', () => {
        const activeInput = window.__jdpActiveInput;

        dispatchJalaliPickerEvent(activeInput, 'jalali-picker-confirm');
        window.jalaliDatepicker.hide();
    });

    footer.insertBefore(button, footer.firstChild);
};

const patchJalaliDatepickerLifecycle = () => {
    if (!window.jalaliDatepicker || window.__jalaliDatepickerPatched) {
        return;
    }

    const originalShow = window.jalaliDatepicker.show.bind(window.jalaliDatepicker);
    const originalHide = window.jalaliDatepicker.hide.bind(window.jalaliDatepicker);

    window.jalaliDatepicker.show = (input) => {
        window.__jdpActiveInput = input;
        dispatchJalaliPickerEvent(input, 'jalali-picker-open');
        originalShow(input);
        requestAnimationFrame(() => {
            ensureJalaliConfirmButton();
        });
    };

    window.jalaliDatepicker.hide = () => {
        const activeInput = window.__jdpActiveInput;

        originalHide();
        dispatchJalaliPickerEvent(activeInput, 'jalali-picker-close');
        window.__jdpActiveInput = null;
    };

    document.addEventListener('jdp:change', () => {
        requestAnimationFrame(() => {
            ensureJalaliConfirmButton();
        });
    });

    window.__jalaliDatepickerPatched = true;
};

document.addEventListener('livewire:init', () => {
    patchJalaliDatepickerLifecycle();
    initializeJalaliDateTimePickers();

    Livewire.hook('morph.updated', () => {
        patchJalaliDatepickerLifecycle();
        initializeJalaliDateTimePickers();
    });
});

Livewire.start();
