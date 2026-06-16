import './bootstrap';
import 'bootstrap/dist/js/bootstrap.min.js';
import * as bootstrap from 'bootstrap';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

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

Alpine.data('idCardScanner', ({ resolveScan }) => ({
    cameras: [],
    selectedDeviceId: '',
    html5QrCode: null,
    scannerElementId: '',
    cameraActive: false,
    startingCamera: false,
    scanning: false,
    resolvingScan: false,
    status: 'initializing',
    message: 'در حال آماده‌سازی دوربین...',
    lastDecodedText: '',
    lastDecodedAt: 0,
    async init() {
        if (!('mediaDevices' in navigator) || !('getUserMedia' in navigator.mediaDevices)) {
            this.setStatus('unsupported', 'دسترسی به دوربین در این مرورگر یا دستگاه در دسترس نیست.');
            return;
        }

        try {
            await this.ensureCameraPermission();
            await this.loadCameras();
            this.scannerElementId = this.$refs.scanner.id;
            this.html5QrCode = new Html5Qrcode(this.scannerElementId, {
                verbose: false,
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                useBarCodeDetectorIfSupported: false,
            });
            await this.startCamera();
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
        const devices = await Html5Qrcode.getCameras();
        this.cameras = devices.map((device, index) => ({
            id: device.id,
            label: device.label || `دوربین ${index + 1}`,
        }));

        if (!this.selectedDeviceId && this.cameras.length) {
            this.selectedDeviceId = this.cameras[0].id;
        }
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
            this.optimizeRunningCamera();
            this.cameraActive = true;
            this.scanning = true;
            this.resolvingScan = false;
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
    pauseFromWire() {
        this.scanning = false;
        this.setStatus('paused', 'اسکن پس از شناسایی موفق متوقف شد.');
    },
    resumeFromWire() {
        this.resumeScan();
    },
    resumeScan() {
        if (!this.cameraActive) {
            this.startCamera();
            return;
        }

        this.lastDecodedText = '';
        this.lastDecodedAt = 0;
        this.resolvingScan = false;
        this.scanning = true;

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
            fps: 30,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const boxSize = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.92);

                return {
                    width: Math.min(viewfinderWidth, Math.max(320, boxSize)),
                    height: Math.min(viewfinderHeight, Math.max(320, boxSize)),
                };
            },
            disableFlip: false,
        };
    },
    async handleDecode(decodedText) {
        if (!this.scanning || this.resolvingScan) {
            return;
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
            const response = await resolveScan(value);

            this.setStatus(
                response?.ok ? 'paused' : (response?.status || 'scan_error'),
                response?.message || (response?.ok ? 'اطلاعات شناسایی شد.' : 'دریافت اطلاعات انجام نشد.')
            );
        } catch (error) {
            this.resolvingScan = false;
            this.setStatus('scan_error', 'دریافت اطلاعات QR انجام نشد. دوباره تلاش کنید.');
        }
    },
    async tryStartScanner(preferredCamera) {
        const startAttempts = [];

        if (preferredCamera) {
            startAttempts.push(preferredCamera);
        }

        startAttempts.push({
            facingMode: 'environment',
            width: { ideal: 1280 },
            height: { ideal: 720 },
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
                await this.html5QrCode.applyVideoConstraints({
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    frameRate: { ideal: 30, min: 15 },
                    advanced: [
                        { focusMode: 'continuous' },
                        { exposureMode: 'continuous' },
                    ],
                });
            } catch (error) {
                // Some webcams do not expose focus/exposure constraints; scanning can continue.
            }
        });
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
        this.stopCamera();
    },
}));

Livewire.start();
