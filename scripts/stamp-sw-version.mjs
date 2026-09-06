/**
 * پس از هر `npm run build` (بلافاصله بعد از generate-precache-manifest.mjs)
 * اجرا می‌شود و یک kill switch خودکار برای کش سرویس‌ورکر می‌سازد:
 *
 * هش محتوای ورودی‌هایی که بدون هاشِ نام‌فایل از کش سرو می‌شوند — منطق خودِ
 * sw.js، استاتیکِ بدون‌هاش (images/sounds/css)، صفحات پوسته (offline/loading)
 * و فهرست پیش‌کش build — محاسبه و در `const VERSION` فایل public/sw.js
 * نوشته می‌شود. این‌طور با هر دیپلویِ دارای تغییرِ مؤثر، بایت‌های sw.js عوض
 * می‌شود؛ کروم (حین ناوبری یا پول ۱۰ دقیقه‌ای update()) تفاوت را می‌بیند،
 * SW تازه install و در activate کش‌های نسخه‌ی قبل را پاک می‌کند — بدون هیچ
 * bump دستی.
 *
 * قطعی (deterministic) است: build دوباره‌ی همان سورس، VERSION را عوض نمی‌کند
 * تا چرخشِ بی‌دلیل کش روی دستگاه کاربران رخ ندهد.
 *
 * ⚠ مقدار VERSION در sw.js را دستی ویرایش نکنید؛ این اسکریپت بازنویسی‌اش
 *   می‌کند. اگر خط منظمِ VERSION پیدا نشود، build با خطا متوقف می‌شود تا
 *   kill switch بی‌صدا از کار نیفتد.
 */
import { createHash } from 'node:crypto';
import { existsSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const swPath = join(root, 'public', 'sw.js');
const VERSION_PATTERN = /^const VERSION = '([^']*)';\r?$/m;

// فایل‌ها و پوشه‌هایی که از STATIC_CACHE/SHELL_CACHE/BUILD_CACHE سرو می‌شوند
// و خودشان مکانیزم بیات‌شدن ندارند؛ هر تغییر در این‌ها باید VERSION را عوض کند.
const HASHED_FILES = [
    'public/offline.html',
    'public/loading.html',
    'public/precache-manifest.json',
];
const HASHED_DIRS = ['public/images', 'public/sounds', 'public/css'];

const listFiles = (dirAbs, baseRel) => {
    if (!existsSync(dirAbs)) {
        return [];
    }

    const files = [];
    for (const entry of readdirSync(dirAbs, { withFileTypes: true })) {
        const abs = join(dirAbs, entry.name);
        const rel = `${baseRel}/${entry.name}`;
        if (entry.isDirectory()) {
            files.push(...listFiles(abs, rel));
        } else if (entry.isFile()) {
            files.push(rel);
        }
    }

    return files;
};

let swSource;
try {
    swSource = readFileSync(swPath, 'utf8');
} catch {
    console.error('[sw-version] public/sw.js not found — cannot stamp VERSION.');
    process.exit(1);
}

const match = swSource.match(VERSION_PATTERN);
if (!match) {
    console.error("[sw-version] `const VERSION = '...';` line not found in public/sw.js — refusing to guess.");
    process.exit(1);
}

const hash = createHash('sha256');

// خط VERSION پیش از هش‌کردن خنثی می‌شود تا مقدار قبلی روی نتیجه بازخورد
// نکند و اسکریپت idempotent بماند.
hash.update(`sw\u0000${swSource.replace(VERSION_PATTERN, "const VERSION = '';")}`);

const addFile = (rel) => {
    const abs = join(root, rel);
    if (existsSync(abs)) {
        hash.update(`${rel}\u0000`);
        hash.update(readFileSync(abs));
    } else {
        // نبودِ فایل هم علامت‌گذاری می‌شود تا ظاهر/غیب‌شدنش هش را عوض کند.
        hash.update(`${rel}\u0000<absent>`);
    }
};

for (const rel of HASHED_FILES) {
    addFile(rel);
}

for (const dirRel of HASHED_DIRS) {
    listFiles(join(root, dirRel), dirRel)
        .sort()
        .forEach(addFile);
}

const version = `v${hash.digest('hex').slice(0, 8)}`;

if (match[1] === version) {
    console.log(`[sw-version] VERSION unchanged (${version})`);
    process.exit(0);
}

const hadCR = match[0].endsWith('\r');
const stamped = swSource.replace(VERSION_PATTERN, `const VERSION = '${version}';${hadCR ? '\r' : ''}`);
writeFileSync(swPath, stamped, 'utf8');
console.log(`[sw-version] VERSION ${match[1]} → ${version}`);
