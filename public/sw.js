/**
 * Service Worker — آوای همدلی (مرحله ۱: PWA قابل‌نصب، Online-first)
 *
 * اصول این نسخه:
 *  - فقط درخواست‌های GET هم‌مبدأ کش می‌شوند؛ هر درخواست دارای state
 *    (POST/PUT/DELETE/PATCH و /livewire/*) هرگز کش نمی‌شود.
 *  - پاسخ‌های HTML/ناوبری و مسیرهای احراز هویت هرگز کش نمی‌شوند
 *    (جلوگیری از سرو شدن صفحه‌ی بیات و نشت داده روی دستگاه مشترک).
 *  - تنها دارایی‌های استاتیک (build هش‌شده، تصاویر، صداها، فونت‌ها) کش می‌شوند.
 *
 * ساختار برای مراحل بعد (Hybrid/Offline):
 *  - استراتژی‌ها به‌صورت تابع‌های مستقل تعریف شده‌اند؛ برای افزودن
 *    Offline Queue / IndexedDB / Background Sync فقط به dispatch انتهای فایل
 *    یک شاخه‌ی جدید اضافه کنید و ثابت‌های کش را باافزایش VERSION تغییر دهید.
 *
 * ⚠ Kill switch: پس از هر دیپلوی که فایل‌های استاتیکِ بدون هاش
 * (images/sounds/css) یا منطق این فایل تغییر کرده، VERSION را افزایش دهید
 * تا کش‌های قدیمی در دستگاه کاربران پاک شود.
 */

const VERSION = 'v1';
const BUILD_CACHE = `avaayeh-build-${VERSION}`;      // دارایی‌های هش‌شده‌ی Vite (تغییرناپذیر)
const STATIC_CACHE = `avaayeh-static-${VERSION}`;    // تصاویر/صداها/css استاتیک
const SHELL_CACHE = `avaayeh-shell-${VERSION}`;      // پوسته‌ی آفلاین (offline.html + آیکون‌ها)

// فایل‌های پوسته که در زمان install کش می‌شوند (برای صفحه‌ی آفلاین و نصب)
const CORE_ASSETS = [
    '/offline.html',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
    '/images/pwa/maskable-icon-512.png',
];

// پیشوند مسیرهایی که هرگز نباید کش شوند (دارای state / احراز هویت / داده‌ی کاربر)
const NEVER_CACHE_PREFIXES = [
    '/livewire/',
    '/login',
    '/logout',
    '/password',
    '/register',
    '/qr/',
    '/admin/people/case-file/attachments/',
    '/storage/',
    '/uploads/',
];

// پیشوند دارایی‌های استاتیکِ قابل‌کش
const BUILD_PREFIXES = ['/build/'];
const STATIC_PREFIXES = ['/images/', '/sounds/', '/css/'];

const isSameOriginGet = (request) => {
    if (request.method !== 'GET') {
        return false;
    }
    try {
        return new URL(request.url).origin === self.location.origin;
    } catch (e) {
        return false;
    }
};

const pathOf = (request) => new URL(request.url).pathname;

const hasPrefix = (pathname, prefixes) => prefixes.some((prefix) => pathname.startsWith(prefix));

const isNeverCached = (request) => hasPrefix(pathOf(request), NEVER_CACHE_PREFIXES);
const isBuildAsset = (request) => hasPrefix(pathOf(request), BUILD_PREFIXES);
const isStaticAsset = (request) => hasPrefix(pathOf(request), STATIC_PREFIXES);
const isNavigation = (request) => request.mode === 'navigate';

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);
        // تک‌تک اضافه می‌شوند تا نبودِ یک فایل، نصب را شکست ندهد
        await Promise.all(CORE_ASSETS.map(async (asset) => {
            try {
                await cache.add(new Request(asset, { cache: 'reload' }));
            } catch (e) {
                console.warn('[sw] core asset unavailable:', asset, e);
            }
        }));
        // مرحله ۱: بلافاصله فعال شود تا دیپلوی‌های بعدی گیر نکنند
        self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keep = new Set([BUILD_CACHE, STATIC_CACHE, SHELL_CACHE]);
        const names = await caches.keys();
        await Promise.all(
            names.filter((name) => !keep.has(name)).map((name) => caches.delete(name))
        );
        await self.clients.claim();
    })());
});

/**
 * استراتژی Cache-First برای دارایی‌های استاتیک.
 * دارایی‌های /build/ به‌دلیل هش در نام، تغییرناپذیرند؛ بقیه با افزایش VERSION
 * یا تغییر نام فایل در دیپلوی بعدی به‌روز می‌شوند.
 */
async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) {
        return cached;
    }

    const response = await fetch(request);
    if (response.ok && (response.type === 'basic' || response.type === 'default')) {
        cache.put(request, response.clone());
    }
    return response;
}

/**
 * ناوبری: همیشه شبکه (online-first). فقط در صورت قطعی شبکه،
 * صفحه‌ی آفلاین استاتیک سرو می‌شود — هرگز HTML بیات از کش.
 */
async function networkFirstNavigation(request) {
    try {
        return await fetch(request);
    } catch (e) {
        const offline = await caches.match('/offline.html', { cacheName: SHELL_CACHE });
        if (offline) {
            return offline;
        }
        return new Response('اتصال به اینترنت برقرار نیست.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // فقط GET هم‌مبدأ؛ بقیه (از جمله POST/PUT/DELETE و کراس‌اورجین) دست‌نخورده رد می‌شوند
    if (!isSameOriginGet(request)) {
        return;
    }

    // درخواست‌های دارای Range (پخش تدریجی صدا) کش نمی‌شوند
    if (request.headers.has('range')) {
        return;
    }

    if (isNeverCached(request)) {
        return; // شبکه‌ی عادی، بدون دخالت کش
    }

    if (isNavigation(request)) {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isBuildAsset(request)) {
        event.respondWith(cacheFirst(request, BUILD_CACHE));
        return;
    }

    if (isStaticAsset(request)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // بقیه‌ی GETها (صفحات HTML، JSON و…) هرگز کش نمی‌شوند
});

// ── نقطه‌ی اتصال مراحل بعد ──────────────────────────────────────────────
// رویدادهای sync (Background Sync) و پیام‌های IndexedDB/Offline Queue در
// مراحل بعد همین‌جا و با همان الگوی VERSION افزوده می‌شوند.
// ────────────────────────────────────────────────────────────────────────
