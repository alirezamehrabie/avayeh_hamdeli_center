<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * هدرهای امنیتی پایه برای همه‌ی پاسخ‌های HTTP.
     *
     * عمداً مینیمال نگه داشته شده تا چیزی در محیط Deploy نشکند:
     *  - SAMEORIGIN به‌جای DENY تا پیش‌نمایش داخلی صفحات/فایل‌های خود اپ مختل نشود.
     *  - HSTS بدون includeSubDomains تا زیردامنه‌های HTTP آسیب نبینند.
     *      (مرورگرها HSTS را کش می‌کنند؛ حذف آسان آن بعداً ممکن نیست، لذا از
     *      مقدار استاندارد یک‌ساله استفاده شده چون سایت کاملاً HTTPS است.)
     *  - CSP عمداً اضافه نشده (نیاز به بازبینی دقیق اسکریپت‌های inline دارد).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        return $response;
    }
}
