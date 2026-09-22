<!-- بخش درباره ما: بازطراحی مدرن، مینیمال با پس‌زمینه اختصاصی روشن و تفکیک‌شده -->
<section id="about" class="landing-section relative overflow-hidden border-t border-slate-100/90 bg-white py-14 sm:py-20 lg:py-24" aria-labelledby="about-title">
    <!-- پس‌زمینه آمبینت نرم و لطیف با انکسار نوری بسیار ملایم -->
    <div class="pointer-events-none absolute -left-20 top-1/4 h-80 w-80 rounded-full bg-[#36A9DF]/5 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-20 bottom-1/4 h-80 w-80 rounded-full bg-[#A4184B]/4 blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6">
        <!-- عنوان اصلی بخش -->
        <div data-reveal class="mx-auto max-w-2xl text-center">
            <x-landing.section-title id="about-title" heading="درباره ما" />
        </div>

        <!-- محتوای اصلی دو ستونه -->
        <div class="mt-10 grid grid-cols-1 items-center gap-10 sm:mt-14 lg:grid-cols-12 lg:gap-14">
            <!-- ستون محتوا و ارکان مأموریت -->
            <div class="order-1 lg:order-2 lg:col-span-7">
                <div data-reveal class="inline-flex items-center gap-2 rounded-full border border-[#1572A1]/15 bg-slate-50/90 px-3.5 py-1 text-xs font-bold text-[#1572A1] shadow-xs">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#1572A1]" aria-hidden="true"></span>
                    خانه امن کودکان نیازمند و آسیب‌دیده
                </div>

                <h3 data-reveal class="mt-4 text-2xl font-black leading-snug text-slate-900 sm:text-3xl lg:text-[2.1rem] lg:leading-tight">
                    جایی که در آن
                    <span class="bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] bg-clip-text text-transparent">
                        کودکی هیچ بچه‌ای
                    </span>
                    از دست نمی‌رود
                </h3>

                <div data-reveal class="mt-4 space-y-3 text-sm leading-7 text-slate-600 text-pretty sm:text-base sm:leading-8">
                    <p>
                        «آوای همدلی» خانه‌ی محلیِ کودکانِ بی‌سرپرست و بدسرپرست است؛ کانون امنی که باور دارد هر کودک فارغ از هر شرایط و پیشینه‌ای، شایسته‌ی برخورداری از آموزشی شایسته، تغذیه‌ای سالم، پوشاکی آراسته و از همه مهم‌تر، احترامی بی‌قیدوشرط است.
                    </p>
                    <p class="text-slate-500">
                        رویکرد ما امداد مقطعی نیست؛ ما با همراهی متخصصان مددکاری و پشتیبانی خیرین گرانقدر، در تمام مراحل رشد در کنار این فرزندان می‌ایستیم تا با کشف استعداد و تقویت مهارت‌های فردی، با عزت‌نفس بر روی پای خود بایستند.
                    </p>
                </div>

                <!-- ۴ ستون اساسی مأموریت به صورت کارت‌های مینیمال با انیمیشن Fade تک‌تک آیتم‌ها -->
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
                            'color' => 'text-[#5964AE]',
                            'bg' => 'bg-[#5964AE]/10',
                        ],
                        [
                            'icon' => 'bi-house-heart-fill',
                            'title' => 'سرپناه و امنیت عاطفی',
                            'desc' => 'فضایی امن و آرام برای کودکی کردن',
                            'color' => 'text-[#A4184B]',
                            'bg' => 'bg-[#A4184B]/10',
                        ],
                    ];
                @endphp

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3.5">
                    @foreach($aboutPillars as $index => $pillar)
                        <div
                            x-data="{
                                itemVisible: false,
                                init() {
                                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                                        this.itemVisible = true;
                                        return;
                                    }
                                    const obs = new IntersectionObserver((entries) => {
                                        if (entries[0].isIntersecting) {
                                            this.itemVisible = true;
                                            obs.disconnect();
                                        }
                                    }, { threshold: 0.15, rootMargin: '0px 0px -20px 0px' });
                                    obs.observe(this.$el);
                                }
                            }"
                            class="group relative flex items-start gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-3.5 shadow-xs transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md hover:shadow-slate-900/5"
                            :class="itemVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-9 pointer-events-none'"
                            style="transition-delay: {{ $index * 130 }}ms;"
                        >
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

                <!-- دکمه‌های اقدام سریع -->
                <div data-reveal class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a
                        href="#contact"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#1572A1]/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#5964AE]/30 active:translate-y-0"
                    >
                        <i class="bi bi-heart-fill text-xs text-rose-200" aria-hidden="true"></i>
                        <span>همراهی و حمایت از کودکان</span>
                    </a>
                    <a
                        href="#services"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50/90 px-5 py-3 text-sm font-bold text-slate-700 backdrop-blur-sm transition-all duration-200 hover:border-slate-300 hover:bg-white hover:text-[#1572A1]"
                    >
                        <span>مشاهده خدمات مرکز</span>
                        <i class="bi bi-arrow-left text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- ستون تصویر: قاب مدرن، مینیمال و ژورنالی (Sculptural Minimal Gallery Frame) -->
            <div class="order-2 lg:order-1 lg:col-span-5" data-reveal>
                <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                    <!-- هاله نوری ملایم و گرم در پس‌زمینه (Soft Ambient Glow) -->
                    <div class="pointer-events-none absolute -inset-3 rounded-[3rem] bg-gradient-to-tr from-[#36A9DF]/15 via-[#5964AE]/10 to-[#A4184B]/10 blur-2xl" aria-hidden="true"></div>

                    <!-- قاب گالری مینیمال با لبه‌های پیوسته سوپربیضی و عمق ملایم -->
                    <div class="group relative overflow-hidden rounded-[2.5rem] border border-slate-200/80 bg-white p-2.5 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.08),0_1px_3px_rgba(0,0,0,0.05)] sm:p-3 transition-shadow duration-500 hover:shadow-[0_25px_60px_-10px_rgba(21,114,161,0.12)]">
                        <!-- کانتینر تصویر با نسبت ۴:۳ و انحنای کامل -->
                        <div class="relative aspect-[4/3] w-full overflow-hidden rounded-[2rem] bg-slate-100">
                            <img
                                src="{{ asset('images/landing/about-image.webp') }}"
                                alt="همراهی و آموزش کودکان در مرکز آوای همدلی"
                                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                                loading="lazy"
                            >

                            <!-- خط مویین صیقلی لبه داخلی تصویر -->
                            <div class="pointer-events-none absolute inset-0 rounded-[2rem] ring-1 ring-inset ring-black/5" aria-hidden="true"></div>

                            <!-- انعکاس نوری لطیف بالایی -->
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-white/20 via-transparent to-transparent" aria-hidden="true"></div>
                        </div>
                    </div>

                    <!-- پیل شناور مینیمال و شکیل در لبه کادر -->
                    <div class="absolute -bottom-3.5 right-6 sm:-bottom-4 sm:right-8 z-10 flex items-center gap-2.5 rounded-full border border-slate-200/90 bg-white/95 px-4 py-2 shadow-lg shadow-slate-900/6 backdrop-blur-md transition-transform duration-300 hover:translate-y-[-2px]">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-50 text-[#A4184B]">
                            <i class="bi bi-heart-fill text-xs" aria-hidden="true"></i>
                        </span>
                        <span class="text-xs font-bold text-slate-800">پایش فردی و مددکاری تخصصی</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
