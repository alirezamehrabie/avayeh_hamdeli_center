@php
    // هدر مشترک صفحات زیرمجموعۀ مجله — همان ساختار هدر مجله، بدون ناوبری بخش‌ها
    $subBackHref = $backHref ?? route('magazine.index');
    $subBackLabel = $backLabel ?? 'بازگشت به مجلۀ همدلی';
    $subPageTitle = $pageTitle ?? 'مجلۀ همدلی';
@endphp

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
        <!-- گروه راست (در RTL): بازگشت + برند -->
        <div class="flex min-w-0 items-center gap-1.5 sm:gap-2.5">
            <a
                href="{{ $subBackHref }}"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:h-11 sm:w-11"
                aria-label="{{ $subBackLabel }}"
            >
                <svg viewBox="0 0 24 24" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5.4"/><path d="m11.4 5.6-6 6.4 6 6.4"/>
                </svg>
            </a>

            <a href="{{ route('landing.preview') }}" class="flex min-w-0 items-center gap-2 sm:gap-2.5" aria-label="آوای همدلی">
                <img
                    src="{{ asset('images/logo-sm.png') }}"
                    alt="لوگوی آوای همدلی"
                    class="h-8 w-8 shrink-0 rounded-lg object-cover shadow-sm sm:h-10 sm:w-10 sm:rounded-xl"
                >
                <span class="min-w-0">
                    <span class="block truncate text-xs font-black leading-5 text-slate-900 sm:text-base sm:leading-6">آوای همدلی</span>
                    <span class="block truncate text-[9px] leading-3.5 text-slate-500 sm:text-[11px] sm:leading-4">{{ $subPageTitle }}</span>
                </span>
            </a>
        </div>

        <!-- اقدامات -->
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <a
                href="{{ route('landing.preview') }}#services"
                class="inline-flex min-h-10 items-center gap-1.5 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:min-h-0 sm:gap-2 sm:px-4 sm:py-2.5 sm:text-sm"
            >
                صفحۀ اصلی
            </a>
        </div>
    </div>

    <!-- نوار پیشرفت اسکرول با گرادیان برند -->
    <div class="pointer-events-none absolute inset-x-0 top-16 h-[2px]" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/5"></div>
        <div
            class="scroll-progress-fill absolute inset-0 bg-[linear-gradient(to_left,#4d56a3_0%,#1572A1_55%,#A4184B_100%)]"
            :style="`clip-path: inset(0 0 0 ${((1 - progress) * 100).toFixed(2)}%); transition: clip-path 150ms ease-out;`"
        ></div>
    </div>
</header>
