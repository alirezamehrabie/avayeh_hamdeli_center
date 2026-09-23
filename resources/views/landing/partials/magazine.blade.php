@php
    $magazineUrl = Route::has('magazine.index') ? route('magazine.index') : '/magazine';
    $coverImage = asset('images/landing/magazine-imagecard.webp');
    $khatamPattern = asset('images/landing/pattern-khatam-white.svg');
@endphp

<!-- بخش مجلۀ همدلی: کارت ادیتوریال مدرن با تصویر شاخص -->
<section id="magazine" class="landing-section bg-white pb-8 pt-4 sm:pb-16 sm:pt-6" aria-labelledby="magazine-title">
    <div class="mx-auto max-w-6xl px-3 sm:px-6">
        <div data-reveal>
            <x-landing.section-title id="magazine-title" heading="مجلۀ همدلی" />
        </div>

        <a
            href="{{ $magazineUrl }}"
            class="group relative mt-6 block overflow-hidden rounded-2xl bg-gradient-to-br from-[#1b3a6b] via-[#1572A1] to-[#2083b0] shadow-[0_12px_36px_rgba(21,114,161,0.28)] transition-all duration-500 ease-out hover:-translate-y-1 hover:shadow-[0_20px_48px_rgba(21,114,161,0.38)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1572A1] sm:mt-8 sm:rounded-[2rem]"
            aria-labelledby="magazine-card-title"
        >
            <!-- بافت محو اسلیمی/خاتم در پس‌زمینه برای هویت اصیل نشریه -->
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.04] mix-blend-overlay transition-opacity duration-500 group-hover:opacity-[0.07]"
                style="background-image: url('{{ $khatamPattern }}'); background-repeat: repeat; background-size: 80px;"
                aria-hidden="true"
            ></div>

            <!-- هاله نور دکوراتیو در گرادیان -->
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-indigo-500/25 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10 flex flex-col-reverse gap-6 p-5 sm:p-7 md:flex-row md:items-center md:justify-between md:gap-8 lg:p-9">
                <!-- ستون محتوا و دعوت به اقدام -->
                <div class="flex flex-1 flex-col items-start text-white">
                    <!-- بج نسخه آنلاین نشریه -->
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white/95 backdrop-blur-md ring-1 ring-inset ring-white/25">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>
                        <span>نسخۀ آنلاین نشریه</span>
                        <span class="h-3 w-px bg-white/30" aria-hidden="true"></span>
                        <span class="text-white/80 font-normal">آوای همدلی</span>
                    </div>

                    <!-- عنوان نشریه -->
                    <h3 id="magazine-card-title" class="mt-3.5 text-xl font-black leading-tight text-white sm:text-2xl lg:text-3xl">
                        مجلۀ همدلی
                    </h3>

                    <!-- توضیحات -->
                    <p class="mt-2 text-xs leading-6 text-white/90 sm:text-sm sm:leading-7 lg:max-w-xl">
                        روایت‌ها، گزارش‌های اختصاصی، قصه‌ها و دست‌نوشته‌های صمیمانۀ کودکان در نشریۀ مرکز نیکوکاری آوای همدلی
                    </p>

                    <!-- برچسب بخش‌های مجله -->
                    <div class="mt-4 flex flex-wrap items-center gap-1.5 pointer-events-none" aria-label="بخش‌های مجله">
                        <span class="text-[11px] font-bold text-white/80 sm:text-xs">شامل:</span>
                        <span class="inline-flex items-center rounded-lg bg-white/15 px-2.5 py-1 text-[11px] font-medium leading-4 text-white ring-1 ring-inset ring-white/20 sm:text-xs">اخبار</span>
                        <span class="inline-flex items-center rounded-lg bg-white/15 px-2.5 py-1 text-[11px] font-medium leading-4 text-white ring-1 ring-inset ring-white/20 sm:text-xs">گزارشات</span>
                        <span class="inline-flex items-center rounded-lg bg-white/15 px-2.5 py-1 text-[11px] font-medium leading-4 text-white ring-1 ring-inset ring-white/20 sm:text-xs">رسانه</span>
                        <span class="inline-flex items-center rounded-lg bg-white/15 px-2.5 py-1 text-[11px] font-medium leading-4 text-white ring-1 ring-inset ring-white/20 sm:text-xs">مقالات</span>
                    </div>

                    <!-- دکمه CTA ورود به مجله -->
                    <div class="mt-5 sm:mt-6">
                        <span class="inline-flex items-center gap-2.5 rounded-xl bg-white px-5 py-2.5 text-xs font-black text-[#1572A1] shadow-lg shadow-black/10 transition-all duration-300 group-hover:bg-slate-50 group-hover:shadow-xl sm:px-6 sm:py-3 sm:text-sm">
                            <span>ورود به مجله</span>
                            <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 transition-transform duration-300 group-hover:-translate-x-1.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 12H5.4"/>
                                <path d="m11.4 5.6-6 6.4 6 6.4"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- ستون تصویر شاخص / کاور مجله با افکت عمق -->
                <div class="w-full md:w-5/12 lg:w-4/12 shrink-0">
                    <div class="relative mx-auto max-w-sm overflow-hidden rounded-2xl bg-slate-900/40 p-1.5 ring-1 ring-white/25 shadow-2xl transition-all duration-500 group-hover:scale-[1.02] group-hover:ring-white/40 sm:rounded-3xl sm:p-2">
                        <div class="relative aspect-[16/10] sm:aspect-[4/3] w-full overflow-hidden rounded-[0.9rem] sm:rounded-[1.3rem]">
                            <img
                                src="{{ $coverImage }}"
                                alt="تصویر مجلۀ همدلی"
                                loading="lazy"
                                width="800"
                                height="500"
                                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                            >
                            <!-- سایه‌روشن مجله‌ای روی تصویر -->
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-black/10"></div>
                            <div class="pointer-events-none absolute bottom-2.5 right-3 left-3 flex items-center justify-between text-white drop-shadow-md sm:bottom-3 sm:right-4 sm:left-4">
                                <span class="text-[11px] font-black sm:text-xs">مرکز آوای همدلی</span>
                                <span class="rounded bg-black/40 px-2 py-0.5 text-[10px] font-medium backdrop-blur-sm sm:text-[11px]">ویژه‌نامه</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</section>
