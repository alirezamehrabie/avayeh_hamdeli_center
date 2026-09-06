/**
 * Service Worker — آوای همدلی (فاز ۲: Hybrid / Online-first PWA)
 *
 * تفکیک استراتژی‌ها:
 *  - Static Assets (build هش‌شده، تصاویر، صداها، css) → Cache First
 *  - صفحات عمومی/قابل‌کش (PUBLIC_PAGE_PREFIXES)      → Network First + fallback کش
 *    (فعلاً خالی است: کل HTML این پروژه احراز هویت‌شده و session-based است و
 *    کش‌کردنش هم صفحه‌ی بیات می‌دهد هم نشت داده روی دستگاه مشترک.)
 *  - ناوبری (Navigation)                              → Network Only + offline.html
 *  - Livewire و هر درخواست دارای state                → Network Only (دست‌نخورده)
 *  - اطلاعات حساس/session (auth، /up، پیوست‌ها)       → هرگز کش نمی‌شوند
 *
 * App Shell:
 *  - دارایی‌های build زمان install از /precache-manifest.json (خروجی postbuild
 *    Vite) پیش‌کش می‌شوند تا پوسته‌ی بصری (CSS/JS/فونت/آیکون) حتی در اولین
 *    بار اجرای آفلاین هم موجود باشد. HTML پوسته عمداً کش نمی‌شود چون
 *    خروجیِ Livewire‌ی هر کاربر متفاوت است.
 *
 * Recovery:
 *  - کلاینت پس از برگشت اینترنت پیام REVALIDATE_STATIC می‌فرستد؛ این SW
 *    کش دارایی‌های بدون‌هاش (تصویر/صدا/css) را خالی می‌کند تا نسخه‌ی تازه
 *    از سرور گرفته شود. دارایی‌های هش‌دار نیازی به revalidate ندارند چون
 *    HTML تازه همیشه هش درست را درخواست می‌کند.
 *
 * ساختار برای فاز ۳ (Offline Queue / IndexedDB / Background Sync):
 *  - یک شاخه‌ی جدید در dispatch انتهای fetch + یک cache/message handler
 *    مستقل؛ هیچ‌کدام از استراتژی‌های بالا بازنویسی نمی‌شوند.
 *
 * ⚠ Kill switch: با هر دیپلوی که فایل استاتیکِ بدون‌هاش یا منطق این فایل
 *   تغییر کرد، VERSION را افزایش دهید تا همه‌ی کش‌های قدیمی پاک شوند.
 */

const VERSION = 'v13';
const BUILD_CACHE = `avaayeh-build-${VERSION}`;      // دارایی‌های هش‌شده‌ی Vite (تغییرناپذیر)
const STATIC_CACHE = `avaayeh-static-${VERSION}`;    // تصاویر/صداها/css استاتیک (بدون هاش)
const SHELL_CACHE = `avaayeh-shell-${VERSION}`;      // پوسته‌ی آفلاین (offline.html + آیکون‌ها)
const PAGES_CACHE = `avaayeh-pages-${VERSION}`;      // صفحات عمومیِ network-first (فعلاً خالی)

const PRECACHE_MANIFEST_URL = '/precache-manifest.json';

// فایل‌های پوسته که در زمان install کش می‌شوند (برای صفحه‌ی آفلاین و نصب)
const CORE_ASSETS = [
    '/offline.html',
    '/loading.html',
    '/images/logo-sm.png',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
    '/images/pwa/maskable-icon-512.png',
];

// صفحه‌ی بارگذاری اولیه (start_url منیفست): استاتیک و بدون state است، پس
// ناوبری‌اش کش‌اول سرو می‌شود تا حتی آفلاین هم فوری بالا بیاید.
const LOADING_PATH = '/loading.html';

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

// مسیرهای دقیقِ هرگز-کش (نقطه‌ی سلامت اتصال؛ کش‌شدنش یعنی تشخیص آفلاین‌بودن اشتباه)
const NEVER_CACHE_EXACT = ['/up'];

// پیشوند صفحات عمومیِ قابل‌کش با استراتژی Network First.
// خالی بماند درست است: همه‌ی صفحات این پروژه پشت auth هستند. اگر در آینده
// صفحه‌ی عمومی (مثلاً لندینگ بدون لاگین) اضافه شد، پیشوندش را همین‌جا بگذارید.
const PUBLIC_PAGE_PREFIXES = [];

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

const isNeverCached = (request) => {
    const pathname = pathOf(request);
    return NEVER_CACHE_EXACT.includes(pathname) || hasPrefix(pathname, NEVER_CACHE_PREFIXES);
};
const isBuildAsset = (request) => hasPrefix(pathOf(request), BUILD_PREFIXES);
const isStaticAsset = (request) => hasPrefix(pathOf(request), STATIC_PREFIXES);
const isNavigation = (request) => request.mode === 'navigate';
const isPublicPage = (request) => hasPrefix(pathOf(request), PUBLIC_PAGE_PREFIXES);

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const shell = await caches.open(SHELL_CACHE);
        // تک‌تک اضافه می‌شوند تا نبودِ یک فایل، نصب را شکست ندهد
        await Promise.all(CORE_ASSETS.map(async (asset) => {
            try {
                await shell.add(new Request(asset, { cache: 'reload' }));
            } catch (e) {
                console.warn('[sw] core asset unavailable:', asset, e);
            }
        }));

        // App Shell: پیش‌کش دارایی‌های build فعلی (هش‌دار) از manifest تولیدشده در build
        try {
            const response = await fetch(PRECACHE_MANIFEST_URL, { cache: 'reload' });
            if (response.ok) {
                const urls = await response.json();
                const build = await caches.open(BUILD_CACHE);
                await Promise.all(urls.map(async (url) => {
                    try {
                        await build.add(new Request(url, { cache: 'reload' }));
                    } catch (e) {
                        console.warn('[sw] precache asset unavailable:', url, e);
                    }
                }));
            }
        } catch (e) {
            console.warn('[sw] precache manifest unavailable; falling back to runtime caching', e);
        }

        self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keep = new Set([BUILD_CACHE, STATIC_CACHE, SHELL_CACHE, PAGES_CACHE]);
        const names = await caches.keys();
        // پاک‌سازی کش نسخه‌های قدیمی (app-cache-v1 → v2 و…)
        await Promise.all(
            names.filter((name) => !keep.has(name)).map((name) => caches.delete(name))
        );
        await self.clients.claim();
    })());
});

/**
 * Cache First — برای دارایی‌های استاتیک.
 * /build/ به‌دلیل هش در نام تغییرناپذیر است؛ بقیه با VERSION یا REVALIDATE به‌روز می‌شوند.
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
 * عناصر <audio> (صداهای اسکنر) درخواست Range می‌فرستند؛ اگر نسخه‌ی کامل در کش
 * باشد همان را برمی‌گردانیم (پخش صدای کوتاه محلی با ۲۰۰ هم کار می‌کند) تا
 * اسکنر در حالت آفلاین بی‌صدا نشود. در غیر این صورت درخواست به شبکه می‌رود.
 */
async function cachedFullOrPassthrough(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) {
        return cached;
    }
    return fetch(request);
}

/**
 * Network First — فقط برای صفحات عمومیِ allowlist‌شده.
 * پاسخ موفق تازه همیشه کش می‌شود؛ فقط هنگام قطعی از کش سرو می‌شود.
 */
async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    try {
        const response = await fetch(request);
        if (response.ok && (response.type === 'basic' || response.type === 'default')) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (e) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }
        throw e;
    }
}

/**
 * ناوبری: همیشه شبکه (online-first). فقط در صورت قطعی، صفحه‌ی آفلاین
 * استاتیک سرو می‌شود — هرگز HTML بیات یا احراز هویت‌شده از کش.
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

    // فقط GET هم‌مبدأ؛ بقیه (POST/PUT/PATCH/DELETE، Livewire، کراس‌اورجین) رد می‌شوند
    if (!isSameOriginGet(request)) {
        return;
    }

    // ناوبری اولویت اول است: هرگز کش نمی‌شود ولی در قطعی، offline.html را
    // می‌گیرد — حتی برای مسیرهای never-cache مثل /login (وگرنه کاربر صفحه
    // خطای خام مرورگر می‌بیند). اگر بعد از این شاخه بیاید، never-cache
    // باعث می‌شد ناوبری‌های احراز هویت بدون fallback به شبکه بروند.
    // صفحه‌ی بارگذاری: استاتیک و بدون state → کش‌اول تا لحظه‌ی راه‌اندازی
    // آفلاین/کند هم فوری و برند سرو شود (خودش به / ریدایرکت می‌کند).
    if (isNavigation(request) && pathOf(request) === LOADING_PATH) {
        event.respondWith(cacheFirst(request, SHELL_CACHE));
        return;
    }

    if (isNavigation(request)) {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isNeverCached(request)) {
        return; // شبکه‌ی عادی، بدون دخالت کش
    }

    if (isBuildAsset(request)) {
        event.respondWith(cacheFirst(request, BUILD_CACHE));
        return;
    }

    if (isStaticAsset(request)) {
        if (request.headers.has('range')) {
            event.respondWith(cachedFullOrPassthrough(request, STATIC_CACHE));
            return;
        }
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    if (isPublicPage(request)) {
        event.respondWith(networkFirst(request, PAGES_CACHE));
        return;
    }

    // بقیه‌ی GETها (صفحات HTML پنل، JSON و…) هرگز کش نمی‌شوند
});

// پیام‌های کلاینت (از resources/js/connection-status.js)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'REVALIDATE_STATIC') {
        event.waitUntil((async () => {
            const cache = await caches.open(STATIC_CACHE);
            const keys = await cache.keys();
            await Promise.all(keys.map((request) => cache.delete(request)));
        })());
    }
});

// ── نقطه‌ی اتصال فاز ۳ ──────────────────────────────────────────────────
// Offline Queue / IndexedDB / Background Sync: یک handler برای event 'sync'
// و یک شاخه‌ی dispatch اینجا اضافه می‌شود؛ استراتژی‌های بالا دست‌نخورده می‌مانند.
// ────────────────────────────────────────────────────────────────────────
