<!-- هدر ثابت و موبایل‌فرست -->
<header
    x-cloak
    x-data="{
        scrolled: false,
        progress: 0,
        updateScroll() {
            this.scrolled = window.scrollY > 8;
            const max = document.documentElement.scrollHeight - window.innerHeight;
            this.progress = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
        }
    }"
    x-init="updateScroll()"
    @scroll.window.passive="updateScroll()"
    @resize.window.passive="updateScroll()"
    class="fixed inset-x-0 top-0 z-40 transition-all duration-300"
    :class="scrolled ? 'bg-white/90 shadow-sm backdrop-blur-md' : 'bg-white/60 backdrop-blur-sm'"
>
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-3 sm:gap-4 sm:px-6">
        <!-- گروه راست (لبه آغازین RTL): برند -->
        <div class="flex min-w-0 items-center gap-1.5 sm:gap-2">
            <!-- برند -->
            <a href="#top" class="flex min-w-0 items-center gap-2 sm:gap-2.5" aria-label="آوای همدلی">
                <img
                    src="{{ asset('images/logo-sm.png') }}"
                    alt="لوگوی آوای همدلی"
                    class="h-8 w-8 shrink-0 rounded-lg object-cover shadow-sm sm:h-10 sm:w-10 sm:rounded-xl"
                >
                <span class="min-w-0">
                    <span class="block truncate text-xs font-black leading-5 text-slate-900 sm:text-base sm:leading-6">آوای همدلی</span>
                    <span class="block truncate text-[9px] leading-3.5 text-slate-500 sm:text-[11px] sm:leading-4">مرکز نیکوکاری تخصصی کودکان</span>
                </span>
            </a>
        </div>

        <!-- ناوبری دسکتاپ -->
        <nav class="hidden items-center gap-1 lg:flex" aria-label="ناوبری اصلی">
            @foreach($navLinks as $link)
                <a
                    href="{{ $link['href'] }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-[#5964AE]"
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <!-- اقدامات و منو (لبه انتهایی RTL) -->
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <a
                href="#contact"
                class="hidden items-center gap-2 rounded-xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_60%,#A4184B_135%)] px-5 py-2.5 text-sm font-extrabold text-white shadow-md shadow-[#5964AE]/20 transition hover:shadow-lg hover:shadow-[#1572A1]/25 active:translate-y-px sm:inline-flex"
            >
                <i class="bi bi-heart-fill" aria-hidden="true"></i>
                همدلی کنید
            </a>
            <a
                href="{{ $loginEntryUrl }}"
                class="inline-flex min-h-10 items-center gap-1.5 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:min-h-0 sm:gap-2 sm:px-4 sm:py-2.5 sm:text-sm"
            >
                <i class="bi bi-box-arrow-in-left" aria-hidden="true"></i>
                {{ $loginEntryLabel }}
            </a>
            <!-- دکمه همبرگر: در گوشه انتهایی (چپ در RTL) و هم‌سو با لبه ورود کشو -->
            <button
                type="button"
                @click="mobileNavOpen ? closeMobileNav() : openMobileNav()"
                :aria-expanded="mobileNavOpen.toString()"
                aria-controls="mobile-nav"
                aria-label="باز و بسته کردن منو"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:h-11 sm:w-11 lg:hidden"
            >
                <svg x-show="!mobileNavOpen" class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <svg x-show="mobileNavOpen" x-cloak class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- نوار پیشرفت اسکرول: با پر شدن صفحه، از راست (RTL) گرادیان برند را آشکار می‌کند -->
    <div class="pointer-events-none absolute inset-x-0 top-16 h-[2px]" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/5"></div>
        <div
            class="scroll-progress-fill absolute inset-0 bg-[linear-gradient(to_left,#4d56a3_0%,#1572A1_55%,#A4184B_100%)]"
            :style="`clip-path: inset(0 0 0 ${((1 - progress) * 100).toFixed(2)}%); transition: clip-path 150ms ease-out;`"
        ></div>
    </div>
</header>