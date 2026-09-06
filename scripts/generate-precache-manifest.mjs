/**
 * پس از هر `npm run build` اجرا می‌شود و فهرست دارایی‌های build فعلی را در
 * public/precache-manifest.json می‌نویسد تا Service Worker هنگام install
 * همان‌ها را پیش‌کش کند (App Shell). با هر build بازنویسی می‌شود؛
 * عمداً در gitignore است چون خروجی build است.
 */
import { readdirSync, writeFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const assetsDir = join(root, 'public', 'build', 'assets');

if (!existsSync(assetsDir)) {
    console.warn('[precache] public/build/assets not found — skipping precache manifest.');
    process.exit(0);
}

const urls = readdirSync(assetsDir)
    .filter((file) => !file.endsWith('.map'))
    .sort()
    .map((file) => `/build/assets/${file}`);

const target = join(root, 'public', 'precache-manifest.json');
writeFileSync(target, `${JSON.stringify(urls, null, 2)}\n`, 'utf8');
console.log(`[precache] wrote ${urls.length} asset URLs to public/precache-manifest.json`);
