import './bootstrap';
import 'bootstrap/dist/js/bootstrap.min.js';
import * as bootstrap from 'bootstrap';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker';
import { attendanceResultBanner, createAttendanceResultBannerState } from './attendance-result-banner';
import { deliveryReceipt } from './delivery-receipt';
import './connection-status';
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

Alpine.data('labelEditor', (props) => ({
    ...props,
    scale: 4,
    dragging: null,
    dragStartX: 0,
    dragStartY: 0,
    dragStartQrX: 0,
    dragStartQrY: 0,
    dragStartTextX: 0,
    dragStartTextY: 0,
    qrPos: { x: 0, y: 0 },
    textPos: { x: 0, y: 0 },

    init() {
        this.syncPositionsFromProps();

        this.$watch('edgeMarginMm', () => this.syncPositionsFromProps());
        this.$watch('topMarginMm', () => this.syncPositionsFromProps());
        this.$watch('bottomMarginMm', () => this.syncPositionsFromProps());
        this.$watch('qrSizeDots', () => this.syncPositionsFromProps());
        this.$watch('qrTextGapMm', () => this.syncPositionsFromProps());
        this.$watch('layoutMode', () => this.syncPositionsFromProps());
        this.$watch('textBottomOffsetDots', () => this.syncPositionsFromProps());
    },

    dotsToMm(dots) {
        return dots * 25.4 / Math.max(1, this.dpi);
    },

    mmToPx(mm) {
        return mm * this.scale;
    },

    pxToMm(px) {
        return px / this.scale;
    },

    fontSizePx() {
        const pt = this.textFontSize * 72 / Math.max(1, this.dpi);
        return pt * this.scale * 0.35;
    },

    syncPositionsFromProps() {
        const qrSizeMm = this.dotsToMm(this.qrSizeDots);

        if (this.layoutMode === 'vertical') {
            this.qrPos.x = (this.labelWidthMm - qrSizeMm) / 2;
            this.qrPos.y = this.topMarginMm;
            this.textPos.x = this.labelWidthMm / 2;
            this.textPos.y = this.labelHeightMm - this.bottomMarginMm - this.dotsToMm(this.textBottomOffsetDots);
        } else {
            this.qrPos.x = this.edgeMarginMm;
            this.qrPos.y = this.topMarginMm;
            this.textPos.x = this.edgeMarginMm + qrSizeMm + this.qrTextGapMm;
            this.textPos.y = this.labelHeightMm / 2;
        }
    },

    clamp(val, min, max) {
        return Math.min(max, Math.max(min, val));
    },

    updateEdgeMarginFromQrX() {
        this.edgeMarginMm = Math.round(this.clamp(this.qrPos.x, 0, this.labelWidthMm / 2) * 10) / 10;
    },

    updateTopMarginFromQrY() {
        this.topMarginMm = Math.round(this.clamp(this.qrPos.y, 0, this.labelHeightMm / 2) * 10) / 10;
    },

    updateGapFromTextX() {
        const qrSizeMm = this.dotsToMm(this.qrSizeDots);
        const gap = this.textPos.x - this.edgeMarginMm - qrSizeMm;
        this.qrTextGapMm = Math.round(gap * 10) / 10;
    },

    updateBottomMarginFromTextY() {
        const bottom = this.labelHeightMm - this.textPos.y;
        this.bottomMarginMm = Math.round(bottom * 10) / 10;
    },

    qrStyle() {
        const sizeMm = this.dotsToMm(this.qrSizeDots);
        return `left: ${this.mmToPx(this.qrPos.x)}px; top: ${this.mmToPx(this.qrPos.y)}px; width: ${this.mmToPx(sizeMm)}px; height: ${this.mmToPx(sizeMm)}px;`;
    },

    textStyle() {
        const rawW = Math.max(20, this.labelWidthMm * 0.4);
        const rawH = Math.max(6, this.dotsToMm(this.textFontSize) * 1.5);
        const isLandscape = this.qrTextRotationDeg === 90 || this.qrTextRotationDeg === 270;
        const textW = isLandscape ? rawH : rawW;
        const textH = isLandscape ? rawW : rawH;
        return `left: ${this.mmToPx(this.textPos.x)}px; top: ${this.mmToPx(this.textPos.y)}px; width: ${this.mmToPx(textW)}px; height: ${this.mmToPx(textH)}px; transform: translate(-50%, -50%);`;
    },

    startDrag(element, event) {
        this.dragging = element;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.dragStartQrX = this.qrPos.x;
        this.dragStartQrY = this.qrPos.y;
        this.dragStartTextX = this.textPos.x;
        this.dragStartTextY = this.textPos.y;
        event.preventDefault();
    },

    onCanvasMouseDown(event) {
        // handled by element-level mousedown
    },

    onCanvasMouseMove(event) {
        if (!this.dragging) {
            return;
        }

        const dx = this.pxToMm(event.clientX - this.dragStartX);
        const dy = this.pxToMm(event.clientY - this.dragStartY);

        if (this.dragging === 'qr') {
            this.qrPos.x = Math.round(this.clamp(this.dragStartQrX + dx, 0, this.labelWidthMm) * 10) / 10;
            this.qrPos.y = Math.round(this.clamp(this.dragStartQrY + dy, 0, this.labelHeightMm) * 10) / 10;
            this.updateEdgeMarginFromQrX();
            this.updateTopMarginFromQrY();
        } else if (this.dragging === 'text') {
            this.textPos.x = Math.round((this.dragStartTextX + dx) * 10) / 10;
            this.textPos.y = Math.round((this.dragStartTextY + dy) * 10) / 10;
            this.updateGapFromTextX();
            this.updateBottomMarginFromTextY();
        }
    },

    onCanvasMouseUp() {
        this.dragging = null;
    },
}));

Alpine.data('deliveryReceipt', deliveryReceipt);

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

// Delivery Gate — eligible-items checklist.
// The server persists each toggle, but waiting for that round-trip before showing
// the tick makes selection feel sluggish. This component owns the *visual* state so a
// tap flips instantly (optimistic), fires the persist call in the background, and rolls
// back only if the server rejects it. The matching server method skipRender()s, so a
// toggle never re-renders the list or re-runs its queries.
//
// Marking an item delivered is instant, but *clearing* a mark first goes through the
// confirmation modal: a ticked item has already been handed over, so a stray tap by the
// next operator at the station must not be able to drop it back into the queue.
Alpine.data('deliveryItems', (initialDelivered = []) => ({
    delivered: new Set((initialDelivered || []).map(Number)),
    saving: new Set(),
    isDelivered(id) {
        return this.delivered.has(Number(id));
    },
    isSaving(id) {
        return this.saving.has(Number(id));
    },
    get deliveredCount() {
        return this.delivered.size;
    },
    toggle(id, label = '') {
        id = Number(id);
        if (this.saving.has(id)) {
            // A persist for this item is still in flight — ignore the extra tap so the
            // client can't drift out of step with the server's flip sequence.
            return;
        }

        if (this.delivered.has(id)) {
            this.confirmUndeliver(id, label);

            return;
        }

        this.persist(id, 'deliver');
    },
    confirmUndeliver(id, label = '') {
        const item = String(label ?? '').trim();

        window.dispatchEvent(new CustomEvent('open-notification-modal', {
            detail: {
                config: {
                    type: 'warning',
                    icon: 'warning',
                    title: 'برداشتن تأیید تحویل',
                    message: (item !== '' ? `قلم «${item}»` : 'این قلم')
                        + ' به‌عنوان تحویل‌شده ثبت شده است. با برداشتن علامت، تحویل ثبت‌شده لغو می‌شود و این قلم دوباره «در انتظار تحویل» قرار می‌گیرد.',
                    buttons: [
                        {
                            label: 'برداشتن علامت',
                            action: 'event',
                            event: 'delivery-gate-undeliver-confirmed',
                            payload: { id },
                            variant: 'danger',
                        },
                        {
                            label: 'انصراف',
                            action: 'close',
                            variant: 'secondary',
                        },
                    ],
                },
            },
        }));
    },
    undeliverConfirmed(id) {
        id = Number(id);

        if (this.saving.has(id) || !this.delivered.has(id)) {
            return;
        }

        this.persist(id, 'revert');
    },
    persist(id, intent) {
        const wasDelivered = this.delivered.has(id);

        // Optimistic flip (Sets are reassigned so Alpine picks up the change).
        this.apply(id, !wasDelivered);

        this.saving.add(id);
        this.saving = new Set(this.saving);

        Promise.resolve(this.$wire.toggleDelivered(id, intent))
            .then((delivered) => {
                // The server owns the row's real status and refuses a flip that contradicts
                // the tap's intent — e.g. another station delivered this item while the page
                // sat open — so realign with what it reports instead of the optimistic guess.
                if (typeof delivered === 'boolean') {
                    this.apply(id, delivered);
                }
            })
            .catch(() => {
                // Persist failed — restore the pre-tap state so the UI stays truthful.
                this.apply(id, wasDelivered);
            })
            .finally(() => {
                this.saving.delete(id);
                this.saving = new Set(this.saving);
            });
    },
    apply(id, delivered) {
        if (delivered) {
            this.delivered.add(id);
        } else {
            this.delivered.delete(id);
        }

        this.delivered = new Set(this.delivered);
    },
}));

// Entry Gate — service-categories checklist. Same optimistic pattern as deliveryItems:
// the tick flips on tap and the toggleCategory persist runs in the background (that
// server method skipRender()s), so a tap never waits for a re-render of the whole gate.
Alpine.data('entryGateCategories', (initialAssigned = []) => ({
    assigned: new Set((initialAssigned || []).map(Number)),
    saving: new Set(),
    isAssigned(id) {
        return this.assigned.has(Number(id));
    },
    isSaving(id) {
        return this.saving.has(Number(id));
    },
    get assignedCount() {
        return this.assigned.size;
    },
    toggle(id) {
        id = Number(id);
        if (this.saving.has(id)) {
            // A persist for this item is still in flight — ignore the extra tap so the
            // client can't drift out of step with the server's flip sequence.
            return;
        }

        const wasAssigned = this.assigned.has(id);

        // Optimistic flip (Sets are reassigned so Alpine picks up the change).
        if (wasAssigned) {
            this.assigned.delete(id);
        } else {
            this.assigned.add(id);
        }
        this.assigned = new Set(this.assigned);

        this.saving.add(id);
        this.saving = new Set(this.saving);

        Promise.resolve(this.$wire.toggleCategory(id))
            .catch(() => {
                // Persist failed — restore the pre-tap state so the UI stays truthful.
                if (wasAssigned) {
                    this.assigned.add(id);
                } else {
                    this.assigned.delete(id);
                }
                this.assigned = new Set(this.assigned);
            })
            .finally(() => {
                this.saving.delete(id);
                this.saving = new Set(this.saving);
            });
    },
}));

window.addEventListener('client-card-browser-print', (event) => {
    const { html, title } = event.detail || {};

    if (!html) {
        return;
    }

    const printWindow = window.open('', '_blank', 'width=900,height=700');

    if (!printWindow) {
        window.alert('مرورگر باز کردن پنجره چاپ را مسدود کرد.');
        return;
    }

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.document.title = title || 'Print';

    let printed = false;

    const triggerPrint = () => {
        if (printed) {
            return;
        }

        printed = true;
        printWindow.focus();
        printWindow.print();
    };

    printWindow.addEventListener('load', triggerPrint, { once: true });
    printWindow.addEventListener('afterprint', () => printWindow.close(), { once: true });
    window.setTimeout(triggerPrint, 600);
});

// Local Print Bridge (production mode).
// When the site runs on a shared host / main domain, no server-side code can
// reach the Windows printers installed on the operator's PC. Instead, a tiny
// loopback-only agent (print-bridge/) runs on each PC and exposes a local
// HTTP API. The *browser* talks to it, so printing keeps working through the
// public domain exactly like it did under localhost. See print-bridge/README.md.
const PRINT_BRIDGE_STORAGE_KEY = 'avaye.printBridge';
const PRINT_BRIDGE_DEFAULT_URL = 'http://127.0.0.1:9235';

const readPrintBridgeSettings = () => {
    let stored = {};

    try {
        stored = JSON.parse(window.localStorage.getItem(PRINT_BRIDGE_STORAGE_KEY) || '{}');
    } catch (error) {
        stored = {};
    }

    return {
        url: typeof stored.url === 'string' && stored.url.trim() !== '' ? stored.url.trim() : PRINT_BRIDGE_DEFAULT_URL,
        token: typeof stored.token === 'string' ? stored.token : '',
        printer: typeof stored.printer === 'string' ? stored.printer : '',
    };
};

const writePrintBridgeSettings = (patch) => {
    const next = { ...readPrintBridgeSettings(), ...patch };

    window.localStorage.setItem(PRINT_BRIDGE_STORAGE_KEY, JSON.stringify(next));

    return next;
};

const normalizePrintBridgeUrl = (value) => {
    let url = (value || '').trim();

    if (url === '') {
        url = PRINT_BRIDGE_DEFAULT_URL;
    }

    if (!/^https?:\/\//i.test(url)) {
        url = `http://${url}`;
    }

    return url.replace(/\/+$/, '');
};

// fetch + manual timeout so we fail fast while scanning for the agent.
const printBridgeFetch = (url, options = {}, timeoutMs = 2500) => {
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), timeoutMs);

    return fetch(url, { ...options, signal: controller.signal }).finally(() => {
        window.clearTimeout(timer);
    });
};

window.printBridge = {
    async check(baseUrl, token = '') {
        const response = await printBridgeFetch(
            `${normalizePrintBridgeUrl(baseUrl)}/ping`,
            token !== '' ? { headers: { 'X-Bridge-Token': token } } : {}
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    },
    async listPrinters(baseUrl, token = '') {
        const response = await printBridgeFetch(
            `${normalizePrintBridgeUrl(baseUrl)}/api/printers`,
            token !== '' ? { headers: { 'X-Bridge-Token': token } } : {},
            8000 // first enumeration after agent start can be slow (WMI)
        );

        const data = await response.json().catch(() => ({}));

        if (!response.ok || !data.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return Array.isArray(data.printers) ? data.printers : [];
    },
    async print({ baseUrl, token = '', printer = '', dataBase64, jobName = 'Avaye Client Card' }) {
        const response = await printBridgeFetch(
            `${normalizePrintBridgeUrl(baseUrl)}/api/print`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(token !== '' ? { 'X-Bridge-Token': token } : {}),
                },
                body: JSON.stringify({
                    data_base64: dataBase64,
                    printer,
                    job_name: jobName,
                }),
            },
            15000
        );

        const data = await response.json().catch(() => ({}));

        if (!response.ok || !data.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return data;
    },
};

window.addEventListener('client-card-bridge-print', async (event) => {
    const { payload } = event.detail || {};

    if (!payload) {
        return;
    }

    const settings = readPrintBridgeSettings();

    try {
        await window.printBridge.check(settings.url, settings.token);
        await window.printBridge.print({
            baseUrl: settings.url,
            token: settings.token,
            printer: settings.printer,
            dataBase64: payload,
        });
        window.alert('کار چاپ با موفقیت به پرینتر این رایانه ارسال شد.');
    } catch (error) {
        if (error instanceof TypeError || error.name === 'AbortError') {
            window.alert('برنامه «پل چاپ» روی این رایانه در دسترس نیست. لطفاً فایل start-bridge.bat در پوشه print-bridge را اجرا کنید و دوباره تلاش کنید.');
            return;
        }

        window.alert(`خطا در چاپ: ${error.message}`);
    }
});

Alpine.data('printBridgeSettings', () => ({
    status: 'checking', // checking | online | offline
    version: '',
    urlInput: readPrintBridgeSettings().url,
    tokenInput: readPrintBridgeSettings().token,
    printers: [],
    selectedPrinter: readPrintBridgeSettings().printer,
    scanning: false,
    scanError: '',

    async init() {
        await this.refreshStatus();
    },
    async refreshStatus() {
        this.status = 'checking';

        try {
            const info = await window.printBridge.check(this.urlInput, this.tokenInput.trim());
            this.status = 'online';
            this.version = info?.version ? String(info.version) : '';
        } catch (error) {
            this.status = 'offline';
            this.version = '';
        }
    },
    async scanPrinters() {
        this.scanning = true;
        this.scanError = '';

        try {
            await this.refreshStatus();

            if (this.status !== 'online') {
                throw Object.assign(new Error('offline'), { offline: true });
            }

            this.persistConnection();

            const printers = await window.printBridge.listPrinters(this.urlInput, this.tokenInput.trim());

            this.printers = printers;

            // Auto-pick the Windows default printer until the operator saves an explicit choice.
            if (!this.selectedPrinter) {
                const fallback = printers.find((p) => p.is_default) || printers[0];

                if (fallback?.name) {
                    this.selectedPrinter = fallback.name;
                    writePrintBridgeSettings({ printer: fallback.name });
                }
            }
        } catch (error) {
            this.scanError = error.offline
                ? 'اتصال به برنامه پل چاپ برقرار نشد. ابتدا آن را روی این رایانه اجرا کنید.'
                : `خطا در دریافت فهرست پرینترها: ${error.message}`;
        } finally {
            this.scanning = false;
        }
    },
    selectPrinter(name) {
        this.selectedPrinter = name;
        writePrintBridgeSettings({ printer: name });
    },
    persistConnection() {
        const next = writePrintBridgeSettings({
            url: normalizePrintBridgeUrl(this.urlInput),
            token: this.tokenInput.trim(),
        });

        this.urlInput = next.url;
    },
    testPrint() {
        if (this.status !== 'online') {
            window.alert('برنامه پل چاپ در دسترس نیست؛ ابتدا اتصال را بررسی کنید.');
            return;
        }

        this.$wire.printTestLabel();
    },
    statusText() {
        if (this.status === 'checking') {
            return 'در حال بررسی برنامه پل چاپ...';
        }

        if (this.status === 'online') {
            return this.version !== ''
                ? `برنامه پل چاپ فعال است (نسخه ${this.version})`
                : 'برنامه پل چاپ فعال است.';
        }

        return 'برنامه پل چاپ روی این رایانه در دسترس نیست.';
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

    // شبکه‌ی رویدادهای حالت بارگذاری سایدبار: اگر درخواست Livewire شکست بخورد،
    // رویداد حباب‌شونده‌ای روی window می‌فرستیم تا Dot Spinner و اسکلتون قفل نمانند.
    Livewire.hook('request', ({ fail }) => {
        fail(() => {
            window.dispatchEvent(new CustomEvent('sidebar-request-failed', { bubbles: true }));
            // فاز ۲ PWA: نشانگر اتصال با دیدن این رویداد در حالت آفلاین صریحاً
            // اعلام می‌کند عملیات انجام نشد — هیچ موفقیت جعلی گزارش نمی‌شود.
            window.dispatchEvent(new CustomEvent('pwa:livewire-failed'));
        });
    });

    Livewire.hook('morph.updated', () => {
        patchJalaliDatepickerLifecycle();
        initializeJalaliDateTimePickers();
    });
});

// ── PWA: نصب روی صفحه اصلی (گزینه «نسخه اندروید» در سایدبار) ────────────────
// کروم رویداد beforeinstallprompt را وقتی صادر می‌کند که شرایط نصب فراهم باشد
// (سرویس‌ورکر ثبت‌شده + منیفست معتبر + معیارهای تعامل، معمولاً اندروید/دسکتاپ).
// رویداد را زودگیر و نگه می‌داریم تا کاربر با دکمه‌ی «تأیید و نصب» در مودال،
// دیالوگ بومی نصب را با ژست کاربری (user gesture) ببیند.
window.__pwaDeferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.__pwaDeferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa:install-available'));
});

window.addEventListener('appinstalled', () => {
    window.__pwaDeferredInstallPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa:installed'));
});

window.__pwaIsStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: fullscreen)').matches
    || window.navigator.standalone === true;

// نتیجه: 'accepted' | 'dismissed' | 'installed' (از قبل نصب است) | 'unavailable'
window.__pwaInstall = async () => {
    if (window.__pwaIsStandalone()) {
        return 'installed';
    }

    const promptEvent = window.__pwaDeferredInstallPrompt;

    if (!promptEvent) {
        return 'unavailable';
    }

    promptEvent.prompt();
    const { outcome } = await promptEvent.userChoice;

    if (outcome === 'accepted') {
        window.__pwaDeferredInstallPrompt = null;
        return 'accepted';
    }

    return 'dismissed';
};

Alpine.data('androidInstallModal', () => ({
    open: false,
    state: 'confirm', // confirm | installing | success | manual | installed

    show() {
        if (window.__pwaIsStandalone()) {
            this.state = 'installed';
        } else if (window.__pwaDeferredInstallPrompt) {
            this.state = 'confirm';
        } else {
            this.state = 'manual';
        }

        this.open = true;
    },

    close() {
        this.open = false;
    },

    async install() {
        this.state = 'installing';

        const outcome = await window.__pwaInstall();

        if (outcome === 'accepted') {
            this.state = 'success';
        } else if (outcome === 'installed') {
            this.state = 'installed';
        } else if (outcome === 'dismissed') {
            this.state = 'confirm';
        } else {
            this.state = 'manual';
        }
    },
}));

Livewire.start();

// ── PWA: ثبت Service Worker (مرحله ۱ — فقط Online-first و قابل‌نصب) ──────────
// ثبت فقط در بیلد پروداکشن انجام می‌شود تا در حالت `npm run dev` (hot reload)
// هیچ دخالتی در سرو دارایی‌ها نداشته باشد. در زمینه‌ی ناامن هم ثبت نمی‌شود
// (تست محلی روی http://localhost معتبر است چون امن محسوب می‌شود).
// هر خطایی در ثبت، بی‌صدا بلعیده می‌شود تا رفتار فعلی برنامه هرگز نشکند.
if (import.meta.env.PROD && 'serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then((registration) => {
                // پنل PWA ممکن است روزها بدون ریلود کامل باز بماند و کروم sw.js را
                // فقط هنگام ناوبری کامل یا هر ۲۴ ساعت چک می‌کند؛ این به‌روزرسانی
                // زمان‌بندی‌شده (فقط وقتی تب نمایان است) دیپلوی تازه را حداکثر ظرف
                // ۱۰ دقیقه روی دستگاه می‌نشاند. update() فقط یک GET مقایسه‌ای است.
                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        registration.update().catch(() => {});
                    }
                }, 10 * 60 * 1000);
            })
            .catch((error) => {
                console.warn('[pwa] service worker registration failed:', error);
            });
    });
}
