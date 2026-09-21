@extends('layouts.magazine')

@section('content')
    @php
        // بنر صفحۀ مجله — فعلاً هاردکد؛ پس از راه‌اندازی مدیریت لندینگ جای همین خط عوض می‌شود.
        $bannerUrl = asset('images/landing/single-pages/banner-magazine.webp');

        // نقطه ورود هر بخش؛ پس از راه‌اندازی صفحۀ هر بخش، آدرس‌ها عوض می‌شوند.
        $sections = [
            [
                'title' => 'اخبار',
                'subtitle' => 'تازه‌های مرکز',
                'href' => '#',
                'color' => '#1572A1',
                'icon' => 'news',
            ],
            [
                'title' => 'گزارشات',
                'subtitle' => 'روایت اقدام‌ها',
                'href' => '#',
                'color' => '#5964AE',
                'icon' => 'report',
            ],
            [
                'title' => 'رسانه',
                'subtitle' => 'تصویر و صدا',
                'href' => '#',
                'color' => '#36A9DF',
                'icon' => 'media',
            ],
            [
                'title' => 'مقالات',
                'subtitle' => 'نوشته‌های کودکان',
                'href' => '#',
                'color' => '#A4184B',
                'icon' => 'article',
            ],
            [
                'title' => 'ارتباط با خدا',
                'subtitle' => 'روایت دل‌ها',
                'href' => '#',
                'color' => '#0e7a55',
                'icon' => 'spiritual',
            ],
            [
                'title' => 'پخش زنده',
                'subtitle' => 'رویدادها زنده',
                'href' => '#',
                'color' => '#D4205F',
                'icon' => 'live',
            ],
        ];

        $icons = [
            'news' => '<path d="M4.5 5.6h15.4v12.8a2 2 0 0 0 2-2V7.8"/><path d="M4.5 5.6a1.6 1.6 0 0 0-1.6 1.6v9.4a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2V8.9"/><path d="M5.7 9.4h11M5.7 12.6h11M5.7 15.8h6.6"/>',
            'report' => '<path d="M6.2 3.4h8.2l4 4v12.2a1.4 1.4 0 0 1-1.4 1.4H6.2a1.4 1.4 0 0 1-1.4-1.4V4.8a1.4 1.4 0 0 1 1.4-1.4Z"/><path d="M14.2 3.6v4h4"/><path d="M8.5 17.2v-3.6M12 17.2v-6.4M15.5 17.2v-2.2"/>',
            'media' => '<rect x="3.4" y="5.6" width="17.2" height="11.4" rx="2"/><path d="M8.6 19.8h6.8"/><path d="m10.6 9 4.2 2.3-4.2 2.3V9Z"/>',
            'article' => '<path d="M18.4 3.9a2.1 2.1 0 0 1 3 3L9.6 18.6l-4.7 1.3a.5.5 0 0 1-.6-.6l1.3-4.7L18.4 3.9Z"/><path d="m16.4 5.9 3 3"/>',
            'spiritual' => '<path d="M12 20.4c-4.4-2.7-7.8-5.9-7.8-9.6a4.3 4.3 0 0 1 7.8-2.4 4.3 4.3 0 0 1 7.8 2.4c0 3.7-3.4 6.9-7.8 9.6Z"/><path d="M12 3.4v1.7M5.3 5.6 6.6 7M18.7 5.6 17.4 7"/>',
            'live' => '<circle cx="12" cy="12" r="2.1"/><path d="M8.1 8.1a5.6 5.6 0 0 0 0 7.8M15.9 8.1a5.6 5.6 0 0 1 0 7.8"/><path d="M5.3 5.3a9.6 9.6 0 0 0 0 13.4M18.7 5.3a9.6 9.6 0 0 1 0 13.4"/>',
        ];
    @endphp

    <!-- هدر چسبان برند، هم‌خانوادۀ هدر لندینگ -->
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
                    href="{{ route('landing.preview') }}"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:h-11 sm:w-11"
                    aria-label="بازگشت به صفحۀ اصلی"
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
                        <span class="block truncate text-[9px] leading-3.5 text-slate-500 sm:text-[11px] sm:leading-4">مجلۀ همدلی</span>
                    </span>
                </a>
            </div>

            <!-- ناوبری دسکتاپ: پرش به بخش‌ها -->
            <nav class="hidden items-center gap-1 xl:flex" aria-label="ناوبری بخش‌ها">
                @foreach($sections as $section)
                    <a
                        href="#mag-{{ $section['icon'] }}"
                        class="rounded-lg px-2.5 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-[#5964AE]"
                    >{{ $section['title'] }}</a>
                @endforeach
            </nav>

            <!-- اقدامات -->
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <a
                    href="#magazine-top"
                    class="hidden items-center gap-2 rounded-xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_60%,#A4184B_135%)] px-5 py-2.5 text-sm font-extrabold text-white shadow-md shadow-[#5964AE]/20 transition hover:shadow-lg hover:shadow-[#1572A1]/25 active:translate-y-px sm:inline-flex"
                >
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    بالاترین نقطه
                </a>
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

    <main id="magazine-top" class="bg-white pb-14 pt-16 sm:pb-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <!-- عنوان بخش، هم‌سبک تیترهای لندینگ -->
            <div class="flex items-center justify-center gap-2 pt-6 sm:gap-3 sm:pt-10" data-reveal>
                <span class="h-px w-8 bg-gradient-to-l from-transparent to-[#1572A1]/50 sm:w-16" aria-hidden="true"></span>
                <h1 class="text-sm font-bold text-[#1572A1] sm:text-base">مجلۀ همدلی</h1>
                <span class="h-px w-8 bg-gradient-to-r from-transparent to-[#5964AE]/50 sm:w-16" aria-hidden="true"></span>
            </div>
            <p class="mt-2 text-center text-[11px] leading-5 text-slate-500 sm:mt-3 sm:text-sm sm:leading-6" data-reveal>
                روایت‌ها، گزارش‌ها و نوشته‌های کودکان در نشریۀ مرکز آوای همدلی
            </p>

            <!-- بنر افقی -->
            <figure class="relative mt-4 sm:mt-8" data-reveal>
                <div class="overflow-hidden rounded-2xl bg-slate-100 shadow-[0_1px_3px_rgba(15,23,42,0.05)] sm:rounded-[1.75rem]">
                    <img
                        src="{{ $bannerUrl }}"
                        alt="بنر مجلۀ همدلی"
                        width="1536"
                        height="1024"
                        decoding="async"
                        fetchpriority="high"
                        class="aspect-[3/2] h-auto w-full object-cover sm:aspect-[16/9]"
                    >
                    <figcaption class="pointer-events-none absolute inset-x-0 bottom-0 rounded-b-2xl bg-[linear-gradient(to_top,rgba(15,23,42,0.55),transparent)] px-4 pb-3 pt-10 sm:rounded-b-[1.75rem] sm:px-6 sm:pb-4 sm:pt-14">
                        <span class="text-xs font-black leading-6 text-white drop-shadow-sm sm:text-sm sm:leading-7">مجلۀ همدلی</span>
                        <span class="mr-2 hidden text-[11px] font-bold text-white/75 sm:mr-3 sm:inline">نشریۀ مرکز آوای همدلی</span>
                    </figcaption>
                </div>
            </figure>

            <!-- بخش‌های مجله: موبایل ردیف افقی کم‌ارتفاع ۲×۳، دسکتاپ کارت عمودی ۳×۲ -->
            <div class="mt-4 grid grid-cols-2 gap-2.5 sm:mt-9 sm:grid-cols-3 sm:gap-4">
                @foreach($sections as $section)
                    <a
                        id="mag-{{ $section['icon'] }}"
                        href="{{ $section['href'] }}"
                        class="group relative flex flex-row items-center gap-2.5 overflow-hidden rounded-2xl border border-slate-100 bg-white py-2.5 pr-3 pl-2 shadow-[0_1px_3px_rgba(15,23,42,0.05)] transition duration-500 ease-out hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1572A1] scroll-mt-20 sm:scroll-mt-24 sm:flex-col sm:items-stretch sm:gap-0 sm:rounded-[1.5rem] sm:p-5"
                        style="--mag-accent: {{ $section['color'] }};"
                        data-reveal
                    >
                        <!-- خط رنگی زیر کارت با رنگ همان بخش -->
                        <span class="absolute inset-x-0 bottom-0 h-[3px] origin-right scale-x-0 transition-transform duration-500 ease-out group-hover:scale-x-100" style="background-color: {{ $section['color'] }};" aria-hidden="true"></span>

                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[0.95rem] text-white shadow-sm transition duration-500 ease-out group-hover:-rotate-3 group-hover:scale-105 sm:h-14 sm:w-14 sm:rounded-[1.3rem] sm:self-start"
                            style="background-color: {{ $section['color'] }};"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5 sm:h-[1.7rem] sm:w-[1.7rem]" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                {!! $icons[$section['icon']] !!}
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1 sm:mt-4 sm:shrink-0">
                            <span class="mag-section-title block truncate text-[13px] font-extrabold leading-6 text-slate-900 transition-colors duration-300 sm:text-base sm:leading-7">{{ $section['title'] }}</span>
                            <span class="block truncate text-[10px] leading-5 text-slate-400 sm:mt-1 sm:text-xs sm:leading-6 sm:text-slate-500">{{ $section['subtitle'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </main>
@endsection
