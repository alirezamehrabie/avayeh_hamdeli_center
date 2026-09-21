<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="مجلۀ همدلی مرکز نیکوکاری آوای همدلی؛ اخبار، گزارش‌ها، رسانه و نوشته‌های کودکان.">
    <title>{{ $title ?? 'مجلۀ همدلی | آوای همدلی' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-sm.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')

    <style>
        body { font-family: 'iransans', 'Vazir', Tahoma, sans-serif; }
        html { overflow-x: clip; }
        [x-cloak] { display: none !important; }
        .group:hover .mag-section-title { color: var(--mag-accent, #1572A1); }
        [data-reveal] { opacity: 0; transform: translateY(18px); }
        [data-reveal].reveal-visible {
            opacity: 1;
            transform: none;
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; }
            .scroll-progress-fill { transition: none !important; }
        }
    </style>
</head>
<body
    x-data="{
        initReveal() {
            const els = document.querySelectorAll('[data-reveal]');
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                els.forEach((el) => el.classList.add('reveal-visible'));
                return;
            }
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('reveal-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            els.forEach((el) => io.observe(el));
        },
    }"
    x-init="initReveal()"
    class="min-h-screen bg-white text-slate-700 antialiased"
>
    @yield('content')
</body>
</html>
