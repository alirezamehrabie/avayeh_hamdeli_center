<!-- بخش درباره ما: بازطراحی مدرن، مینیمال با پس‌زمینه اختصاصی روشن و تفکیک‌شده -->
<section id="about" class="landing-section relative overflow-hidden border-t border-slate-100/90 bg-white py-14 sm:py-20 lg:py-24" aria-labelledby="about-title">
    <!-- پس‌زمینه آمبینت نرم و لطیف با انکسار نوری بسیار ملایم -->
    <div class="pointer-events-none absolute -left-20 top-1/4 h-80 w-80 rounded-full bg-[#1572A1]/5 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-20 bottom-1/4 h-80 w-80 rounded-full bg-[#36A9DF]/5 blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6">
        <!-- عنوان اصلی بخش -->
        <div data-reveal class="mx-auto max-w-2xl text-center">
            <x-landing.section-title id="about-title" heading="درباره ما" />
        </div>

        <!-- محتوای اصلی دو ستونه (در موبایل تصویر اول، در دسکتاپ چیدمان متوازن) -->
        <div class="mt-8 grid grid-cols-1 items-center gap-8 sm:mt-12 lg:grid-cols-12 lg:gap-14">
            <!-- ستون تصویر: در موبایل اول، در دسکتاپ ستون کناری -->
            <div class="order-1 lg:order-1 lg:col-span-5" data-reveal>
                <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                    <!-- هاله نوری ملایم و گرم در پس‌زمینه -->
                    <div class="pointer-events-none absolute -inset-3 rounded-[3rem] bg-gradient-to-tr from-[#1572A1]/15 via-[#36A9DF]/10 to-[#38538C]/10 blur-2xl" aria-hidden="true"></div>

                    <!-- قاب گالری مینیمال با لبه‌های پیوسته سوپربیضی و عمق ملایم -->
                    <div class="group relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white p-2.5 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.08),0_1px_3px_rgba(0,0,0,0.05)] sm:rounded-[2.5rem] sm:p-3 transition-shadow duration-500 hover:shadow-[0_25px_60px_-10px_rgba(21,114,161,0.12)]">
                        <!-- کانتینر تصویر با نسبت ۴:۳ و انحنای کامل -->
                        <div class="relative aspect-[4/3] w-full overflow-hidden rounded-[1.5rem] sm:rounded-[2rem] bg-slate-100">
                            <img
                                src="{{ asset('images/landing/about-image.webp') }}"
                                alt="همراهی و آموزش کودکان در مرکز آوای همدلی"
                                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                                loading="lazy"
                                width="600"
                                height="450"
                            >

                            <!-- خط مویین صیقلی لبه داخلی تصویر -->
                            <div class="pointer-events-none absolute inset-0 rounded-[1.5rem] sm:rounded-[2rem] ring-1 ring-inset ring-black/5" aria-hidden="true"></div>

                            <!-- انعکاس نوری لطیف بالایی -->
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-white/20 via-transparent to-transparent" aria-hidden="true"></div>

                            <!-- پیل شناور لبه تصویر با پوزیشن امن داخلی -->
                            <div class="absolute bottom-3 right-3 sm:bottom-4 sm:right-4 z-10 flex items-center gap-2 rounded-full border border-white/30 bg-slate-950/65 px-3 py-1.5 text-white shadow-lg backdrop-blur-md">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#1572A1] text-white">
                                    <i class="bi bi-heart-fill text-[10px]" aria-hidden="true"></i>
                                </span>
                                <span class="text-[11px] font-bold sm:text-xs">پایش فردی و مددکاری تخصصی</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ستون محتوا و ارکان مأموریت -->
            <div class="order-2 lg:order-2 lg:col-span-7">
                <div data-reveal class="inline-flex items-center gap-2 rounded-full border border-[#1572A1]/15 bg-slate-50/90 px-3.5 py-1 text-xs font-bold text-[#1572A1] shadow-xs">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#1572A1]" aria-hidden="true"></span>
                    خانه امن کودکان نیازمند و آسیب‌دیده
                </div>

                <h3 data-reveal class="mt-3.5 text-2xl font-black leading-snug text-slate-900 sm:text-3xl lg:text-[2.1rem] lg:leading-tight">
                    جایی که در آن
                    <span class="bg-gradient-to-l from-[#1572A1] to-[#0284c7] bg-clip-text text-transparent">
                        کودکی هیچ بچه‌ای
                    </span>
                    از دست نمی‌رود
                </h3>

                <div data-reveal class="mt-3.5 text-sm leading-7 text-slate-600 sm:text-base sm:leading-8">
                    <p>
                        <strong class="font-bold text-slate-900">«آوای همدلی»</strong> کانون امن کودکان نیازمند و بدسرپرست است؛ کانون مهربانی که با همراهی متخصصان مددکاری و نیکوکاران، در تمام مراحل رشد در کنار فرزندان می‌ایستد تا با آموزش شایسته، تغذیه سالم و مهارت‌آموزی، با <span class="font-bold text-[#1572A1]">عزت‌نفس و امید</span> بر روی پای خود بایستند.
                    </p>
                </div>

                <!-- ۴ ستون اساسی مأموریت به صورت کارت‌های بهینه، سبک و بدون سربار JS -->
                @php
                    $aboutPillars = [
                        [
                            'icon' => 'bi-mortarboard-fill',
                            'title' => 'آموزش و کشف استعداد',
                            'desc' => 'تامین کتب، لوازم‌تحریر و بورسیه مهارت‌آموزی',
                            'color' => 'text-[#1572A1]',
                            'bg' => 'bg-[#1572A1]/10',
                        ],
                        [
                            'icon' => 'bi-shield-check',
                            'title' => 'تغذیه و سلامت بالینی',
                            'desc' => 'وعده‌های مقوی گرم و پایش منظم پزشکی',
                            'color' => 'text-emerald-600',
                            'bg' => 'bg-emerald-500/10',
                        ],
                        [
                            'icon' => 'bi-people-fill',
                            'title' => 'مددکاری و سلامت روان',
                            'desc' => 'مشاوره تخصصی، بازی‌درمانی و حمایت از خانواده',
                            'color' => 'text-[#0284c7]',
                            'bg' => 'bg-[#0284c7]/10',
                        ],
                        [
                            'icon' => 'bi-house-heart-fill',
                            'title' => 'سرپناه و امنیت عاطفی',
                            'desc' => 'فضایی امن و آرام برای کودکی کردن',
                            'color' => 'text-[#38538C]',
                            'bg' => 'bg-[#38538C]/10',
                        ],
                    ];
                @endphp

                <div data-reveal class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3.5">
                    @foreach($aboutPillars as $pillar)
                        <div class="group relative flex items-start gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-3.5 shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md hover:shadow-slate-900/5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $pillar['bg'] }} {{ $pillar['color'] }} transition-transform duration-300 group-hover:scale-105">
                                <i class="{{ $pillar['icon'] }} text-lg" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-bold text-slate-800 transition-colors group-hover:text-slate-950">{{ $pillar['title'] }}</h4>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $pillar['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- دکمه‌های اقدام سریع هماهنگ با جریان پیمایش صفحه -->
                <div data-reveal class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a
                        href="#contact"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-gradient-to-l from-[#1572A1] to-[#1e88ba] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#1572A1]/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#1572A1]/30 active:translate-y-0"
                    >
                        <i class="bi bi-heart-fill text-xs text-rose-200" aria-hidden="true"></i>
                        <span>همراهی و حمایت از کودکان</span>
                    </a>
                    <a
                        href="#stories"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50/90 px-5 py-3 text-sm font-bold text-slate-700 backdrop-blur-sm transition-all duration-200 hover:border-slate-300 hover:bg-white hover:text-[#1572A1]"
                    >
                        <span>قصه‌های امید کودکان</span>
                        <i class="bi bi-chat-heart text-xs text-[#1572A1]" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
