<!DOCTYPE html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="مرکز نیکوکاری تخصصی کودکان آوای همدلی؛ همراهی، آموزش، تغذیه و امید برای کودکان در نیاز.">
    <title>{{ $title ?? 'آوای همدلی | مرکز نیکوکاری تخصصی کودکان' }}</title>

    <meta property="og:type" content="website">
    <meta property="og:title" content="آوای همدلی | مرکز نیکوکاری تخصصی کودکان">
    <meta property="og:description" content="همراه ما باش تا امید را به کودکان در نیاز هدیه دهیم.">
    <meta property="og:image" content="{{ asset('images/logo-sm.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('images/logo-sm.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')
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
            .scroll-progress-fill { transition: none !important; }
        }
    </style>
</head>
<body
    x-data="{
        mobileNavOpen: false,
        mobileNavHistoryPushed: false,
        mobileNavClosingFromPopstate: false,
        mobileNavPopstateHandler: null,
        mobileNavScrollLock: null,
        mobileNavEnableHistoryClose: false,
        floatingBarVisible: false,
        revealHeights: new Set(),
        init() {
            this.initMobileNav();
            this.initFloatingBar();
            this.initReveal();
            this.initCounter();
        },
        initMobileNav() {
            // دکمه Back گوشی فقط روی موبایل/لمسی history می‌خواند تا تاریخچه دسکتاپ آلوده نشود.
            this.mobileNavEnableHistoryClose = window.matchMedia('(max-width: 1023px), (pointer: coarse)').matches;
            window.addEventListener('resize', () => {
                if (this.mobileNavOpen && window.matchMedia('(min-width: 1024px)').matches) {
                    this.closeMobileNav();
                }
            });
        },
        openMobileNav() {
            if (this.mobileNavOpen) return;
            this.mobileNavOpen = true;
            this.lockMobileNavScroll();
            this.pushMobileNavHistory();
            this.$nextTick(() => this.$refs.mobileNavClose?.focus({ preventScroll: true }));
        },
        closeMobileNav(skipHistoryBack = false) {
            if (! this.mobileNavOpen) return;

            if (this.mobileNavHistoryPushed && ! this.mobileNavClosingFromPopstate && ! skipHistoryBack) {
                this.mobileNavHistoryPushed = false;
                try {
                    window.history.back();
                } catch (error) {
                    // کشو حتی اگر ورودی history قابل مصرف نباشد بسته می‌شود.
                }
            }

            this.mobileNavOpen = false;
            this.mobileNavClosingFromPopstate = false;
            this.teardownMobileNavHistory();
            this.unlockMobileNavScroll();
        },
        pushMobileNavHistory() {
            if (! this.mobileNavEnableHistoryClose || ! window.history?.pushState) return;

            window.history.pushState({ ...(window.history.state || {}), landingNav: true }, '', window.location.href);
            this.mobileNavHistoryPushed = true;

            this.mobileNavPopstateHandler = () => {
                if (! this.mobileNavHistoryPushed || ! this.mobileNavOpen) return;
                // فشردن Back: منو بسته می‌شود و خروج ناگهانی از صفحه گرفته نمی‌شود.
                this.mobileNavClosingFromPopstate = true;
                this.closeMobileNav(true);
            };

            window.addEventListener('popstate', this.mobileNavPopstateHandler);
        },
        teardownMobileNavHistory() {
            if (! this.mobileNavPopstateHandler) return;
            window.removeEventListener('popstate', this.mobileNavPopstateHandler);
            this.mobileNavPopstateHandler = null;
        },
        lockMobileNavScroll() {
            if (this.mobileNavScrollLock) return;
            // html تنها ظرف اسکرول این صفحه است، پس قفل روی همان اعمال می‌شود.
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            this.mobileNavScrollLock = {
                scrollY: window.scrollY,
                htmlOverflow: document.documentElement.style.overflow,
                bodyPaddingRight: document.body.style.paddingRight,
            };
            document.documentElement.style.overflow = 'hidden';
            if (scrollbarWidth > 0) {
                document.body.style.paddingRight = `${scrollbarWidth}px`;
            }
        },
        unlockMobileNavScroll() {
            if (! this.mobileNavScrollLock) return;
            const lock = this.mobileNavScrollLock;
            document.documentElement.style.overflow = lock.htmlOverflow;
            document.body.style.paddingRight = lock.bodyPaddingRight;
            window.scrollTo(0, lock.scrollY);
            this.mobileNavScrollLock = null;
        },
        initFloatingBar() {
            const footer = document.getElementById('site-footer');
            let pastTop = false;
            let footerIntersecting = false;

            const updateVisibility = () => {
                this.floatingBarVisible = pastTop && !footerIntersecting;
            };

            if (footer) {
                const footerObserver = new IntersectionObserver((entries) => {
                    footerIntersecting = entries[0].isIntersecting;
                    updateVisibility();
                }, { threshold: 0 });
                footerObserver.observe(footer);
            }

            let ticking = false;
            const onScroll = () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        const currentPastTop = window.scrollY > 400;
                        if (currentPastTop !== pastTop) {
                            pastTop = currentPastTop;
                            updateVisibility();
                        }
                        ticking = false;
                    });
                    ticking = true;
                }
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