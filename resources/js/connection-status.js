/**
 * تشخیص وضعیت اتصال (فاز ۲ PWA) — Online-first
 *
 * چرا فقط navigator.onLine کافی نیست: این پرچم فقط می‌گوید دستگاه به شبکه‌ی
 * محلی وصل است؛ در قطع واقعی اینترنت (DNS/روتر/WAN) همچنان true می‌ماند.
 * پس یک heartbeat روی مسیر سلامت Laravel (`/up`، بدون session و بدون auth)
 * زده می‌شود: هر پاسخ HTTP (حتی ۵۰۰/۵۰۳) یعنی سرور در دسترس است؛ فقط خطای
 * شبکه/timeout یعنی offline.
 *
 * وضعیت‌ها: online | offline | checking
 * رویدادها روی window:
 *   - connection:status    detail: { status, previous }
 *   - connection:restored  فقط هنگام گذار offline/checking → online
 * API: window.pwaConnection = { get status(), check() }
 *
 * در حالت online هر ۳۰ ثانیه و در حالت offline هر ۸ ثانیه چک می‌شود؛ با
 * برگشت اتصال، به Service Worker پیام REVALIDATE_STATIC فرستاده می‌شود تا
 * دارایی‌های استاتیکِ بدون‌هاش دوباره از سرور تازه گرفته شوند.
 */

const PING_URL = '/up';
const ONLINE_INTERVAL_MS = 30000;
const OFFLINE_RETRY_INTERVAL_MS = 8000;
const PING_TIMEOUT_MS = 5000;

let status = navigator.onLine ? 'checking' : 'offline';
let timer = null;
let probing = false;

const emit = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));

function setStatus(next) {
    if (status === next) {
        return;
    }

    const previous = status;
    status = next;
    emit('connection:status', { status: next, previous });

    if (next === 'online' && previous !== 'online') {
        emit('connection:restored');
        notifyServiceWorker();
    }
}

function notifyServiceWorker() {
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        try {
            navigator.serviceWorker.controller.postMessage({ type: 'REVALIDATE_STATIC' });
        } catch (e) {
            // خطای postMessage هرگز نباید UI اتصال را بشکند
        }
    }
}

async function ping() {
    try {
        const signal = typeof AbortSignal !== 'undefined' && AbortSignal.timeout
            ? AbortSignal.timeout(PING_TIMEOUT_MS)
            : undefined;
        // هر پاسخ HTTP (حتی non-2xx) یعنی اتصال برقرار است؛ فقط throw یعنی قطع
        await fetch(PING_URL, { cache: 'no-store', credentials: 'same-origin', signal });
        return true;
    } catch (e) {
        return false;
    }
}

function schedule() {
    clearTimeout(timer);
    const interval = status === 'online' ? ONLINE_INTERVAL_MS : OFFLINE_RETRY_INTERVAL_MS;
    timer = setTimeout(probe, interval);
}

async function probe() {
    if (probing) {
        return;
    }

    probing = true;
    try {
        const reachable = await ping();
        setStatus(reachable ? 'online' : 'offline');
        // پخش وضعیت در هر سنجش (نه فقط هنگام تغییر): اگر ایندیکیتور
        // رویداد گذار را به‌دلیل race با مقداردهی Alpine از دست بدهد،
        // در تیک بعدی خودبه‌خود همگام می‌شود.
        emit('connection:status', { status, previous: status });
    } finally {
        probing = false;
        schedule();
    }
}

window.addEventListener('offline', () => {
    setStatus('offline');
    schedule();
});

window.addEventListener('online', () => {
    // مرورگر می‌گوید شبکه برگشته؛ تا تأیید heartbeat زرد بمان
    setStatus('checking');
    probe();
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        probe();
    }
});

window.pwaConnection = {
    get status() {
        return status;
    },
    check: probe,
};

// اولین سنجش بلافاصله پس از load تا وضعیت واقعی (نه حدس onLine) مشخص شود
window.addEventListener('load', () => {
    probe();
});

export {};
