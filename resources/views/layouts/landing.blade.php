<!DOCTYPE html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="مرکز نیکوکاری تخصصی کودکان آوای همدلی؛ همراهی، آموزش، تغذیه و امید برای کودکان در نیاز.">
    <title>{{ $title ?? 'آوای همدلی | مرکز نیکوکاری تخصصی کودکان' }}</title>

    <meta property="og:type" content="website">
    <meta property="og:title" content="آوای همدلی | مرکز نیکوکاری تخصصی کودکان">
    <meta property="og:description" content="همراه ما باش تا امید را به کودکان در نیاز هدیه دهیم.">
    <meta property="og:image" content="{{ asset('images/logo-sm.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('images/logo-sm.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body { font-family: 'iransans', 'Vazir', Tahoma, sans-serif; }
        /* clip (not hidden) so no scroll container is created and fixed/sticky keep working.
           html is the sole scroll container here: the global body scrollbar gutter would
           otherwise reserve a strip that stops full-bleed sections short of the edge. */
        html { overflow-x: clip; }
        body { overflow-y: visible; scrollbar-gutter: auto; }
        .landing-section { scroll-margin-top: 5rem; }
        [x-cloak] { display: none !important; }
        [data-reveal] { opacity: 0; transform: translateY(18px); }
        [data-reveal].reveal-visible {
            opacity: 1;
            transform: none;
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; }
        }
    </style>
</head>
<body
    x-data="{
        mobileNavOpen: false,
        floatingBarVisible: false,
        revealHeights: new Set(),
        init() {
            this.initFloatingBar();
            this.initReveal();
            this.initCounter();
        },
        initFloatingBar() {
            const heroEnd = document.getElementById('hero')?.getBoundingClientRect().bottom ?? 600;
            const footer = document.getElementById('site-footer');
            const onScroll = () => {
                if (!footer) return;
                const footerTop = footer.getBoundingClientRect().top;
                const pastHero = window.scrollY > heroEnd;
                this.floatingBarVisible = pastHero && footerTop > window.innerHeight;
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        },
        initReveal() {
            const els = document.querySelectorAll('[data-reveal]');
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('reveal-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            els.forEach((el) => {
                if (prefersReduced) { el.classList.add('reveal-visible'); return; }
                io.observe(el);
            });
        },
        initCounter() {
            const counters = document.querySelectorAll('[data-counter]');
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    io.unobserve(el);
                    if (prefersReduced) { el.textContent = el.dataset.target; return; }
                    const target = parseFloat(el.dataset.target);
                    const suffix = el.dataset.suffix ?? '';
                    const dur = 1400;
                    const start = performance.now();
                    const tick = (now) => {
                        const p = Math.min((now - start) / dur, 1);
                        const eased = 1 - Math.pow(1 - p, 3);
                        el.textContent = Math.round(target * eased).toLocaleString('fa-IR') + suffix;
                        if (p < 1) requestAnimationFrame(tick);
                    };
                    requestAnimationFrame(tick);
                });
            }, { threshold: 0.5 });
            counters.forEach((c) => io.observe(c));
        }
    }"
    class="min-h-screen bg-white text-slate-700 antialiased"
>
    @yield('content')

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>