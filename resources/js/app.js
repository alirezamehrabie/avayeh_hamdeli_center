import './bootstrap';
import 'bootstrap/dist/js/bootstrap.min.js';
import * as bootstrap from 'bootstrap';
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

Alpine.data('idCardScanner', ({ resolveScan, updateStatus }) => ({
    cameras: [],
    selectedDeviceId: '',
    stream: null,
    detector: null,
    scanning: false,
    rafId: null,
    lastDecodedText: '',
    lastDecodedAt: 0,
    async init() {
        if (!('mediaDevices' in navigator) || !('getUserMedia' in navigator.mediaDevices)) {
            updateStatus('unsupported', 'دسترسی به دوربین در این مرورگر یا دستگاه در دسترس نیست.');
            return;
        }

        if (!('BarcodeDetector' in window)) {
            updateStatus('unsupported', 'تشخیص زنده QR در این مرورگر پشتیبانی نمی‌شود.');
            return;
        }

        try {
            const formats = await window.BarcodeDetector.getSupportedFormats();

            if (!formats.includes('qr_code')) {
                updateStatus('unsupported', 'مرورگر از تشخیص QR پشتیبانی نمی‌کند.');
                return;
            }

            this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            await this.loadCameras();
            updateStatus('ready', 'دوربین آماده است. برای شروع، دوربین را فعال کنید.');
        } catch (error) {
            updateStatus('unsupported', 'امکان آماده‌سازی اسکنر در این مرورگر وجود ندارد.');
        }
    },
    async loadCameras() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        this.cameras = devices
            .filter((device) => device.kind === 'videoinput')
            .map((device, index) => ({
                id: device.deviceId,
                label: device.label || `دوربین ${index + 1}`,
            }));

        if (!this.selectedDeviceId && this.cameras.length) {
            this.selectedDeviceId = this.cameras[0].id;
        }
    },
    async startCamera() {
        if (!this.detector) {
            return;
        }

        try {
            this.stopCamera();

            const constraints = this.selectedDeviceId
                ? { video: { deviceId: { exact: this.selectedDeviceId } }, audio: false }
                : { video: { facingMode: 'environment' }, audio: false };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.$refs.video.srcObject = this.stream;
            await this.$refs.video.play();
            await this.loadCameras();
            this.scanning = true;
            updateStatus('scanning', 'اسکن زنده فعال است. QR را مقابل دوربین نگه دارید.');
            this.scanFrame();
        } catch (error) {
            updateStatus('camera_denied', 'مرورگر اجازه دسترسی به دوربین را نداد یا دوربین در دسترس نیست.');
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
        updateStatus('paused', 'اسکن پس از شناسایی موفق متوقف شد.');
    },
    resumeFromWire() {
        this.resumeScan();
    },
    resumeScan() {
        if (!this.stream) {
            this.startCamera();
            return;
        }

        this.lastDecodedText = '';
        this.lastDecodedAt = 0;
        this.scanning = true;
        updateStatus('scanning', 'اسکن دوباره فعال شد. QR را مقابل دوربین نگه دارید.');
        this.scanFrame();
    },
    async scanFrame() {
        if (!this.scanning || !this.detector || !this.$refs.video) {
            return;
        }

        try {
            if (this.$refs.video.readyState >= 2) {
                const barcodes = await this.detector.detect(this.$refs.video);

                if (barcodes.length) {
                    const value = (barcodes[0].rawValue || '').trim();
                    const now = Date.now();

                    if (value && (value !== this.lastDecodedText || (now - this.lastDecodedAt) > 2500)) {
                        this.lastDecodedText = value;
                        this.lastDecodedAt = now;
                        this.scanning = false;
                        resolveScan(value);
                        return;
                    }
                }
            }
        } catch (error) {
            updateStatus('scan_error', 'خطا در خواندن تصویر دوربین رخ داد.');
        }

        this.rafId = window.requestAnimationFrame(() => this.scanFrame());
    },
    stopCamera() {
        this.scanning = false;

        if (this.rafId) {
            window.cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }
    },
    destroy() {
        this.stopCamera();
    },
}));

Livewire.start();
