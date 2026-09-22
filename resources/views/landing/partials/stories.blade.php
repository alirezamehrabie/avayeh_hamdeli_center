<!-- بخش قصه‌های همدلی: طراحی بهینه موبایل‌فرست با اسکرول لمسی ارگونومیک -->
<section id="stories" class="landing-section relative overflow-hidden bg-[#f8fbff] py-10 sm:py-16" aria-labelledby="stories-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div data-reveal class="flex flex-col items-center justify-between gap-3 text-center sm:flex-row sm:text-right">
            <x-landing.section-title id="stories-title" heading="قصه‌های همدلی" />
        </div>

        <div
            x-data="{
                active: 0,
                rail: null,
                timer: null,
                hovered: false,
                userActive: false,
                idleTimer: null,
                motionOk: true,
                init() {
                    this.rail = this.$refs.rail;
                    if (!this.rail) return;

                    this.motionOk = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    // توقف موقت هنگام هاور ماوس
                    this.rail.addEventListener('pointerenter', () => { this.hovered = true; });
                    this.rail.addEventListener('pointerleave', () => { this.hovered = false; });

                    // وقفه موقت در اسکرول خودکار هنگام لمس یا اسکرول دستی کاربر در موبایل
                    const wake = () => {
                        this.userActive = true;
                        clearTimeout(this.idleTimer);
                        this.idleTimer = setTimeout(() => { this.userActive = false; }, 3500);
                    };
                    this.rail.addEventListener('pointerdown', wake, { passive: true });
                    this.rail.addEventListener('touchstart', wake, { passive: true });
                    this.rail.addEventListener('wheel', wake, { passive: true });

                    // رهگیری اسلاید فعال با IntersectionObserver کاملاً هماهنگ با اسکرول لمسی موبایل در RTL
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                const idx = parseInt(entry.target.dataset.index, 10);
                                if (!isNaN(idx)) this.active = idx;
                            }
                        });
                    }, { root: this.rail, threshold: 0.55 });

                    this.rail.querySelectorAll('.story-card').forEach((c) => io.observe(c));

                    // شروع تایمر ۵ ثانیه‌ای اسکرول خودکار و نرم
                    if (this.motionOk) {
                        this.startTimer();
                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) {
                                clearInterval(this.timer);
                            } else if (this.motionOk) {
                                this.startTimer();
                            }
                        });
                    }
                },
                startTimer() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        if (this.hovered || this.userActive || document.hidden) return;
                        this.scroll('next');
                    }, 5000);
                },
                scroll(direction) {
                    if (!this.rail) return;
                    const card = this.rail.querySelector('.story-card');
                    if (!card) return;
                    const gap = parseFloat(getComputedStyle(this.rail).columnGap) || 12;
                    const step = card.getBoundingClientRect().width + gap;
                    const isRtl = getComputedStyle(this.rail).direction === 'rtl';
                    const max = this.rail.scrollWidth - this.rail.clientWidth;
                    const current = Math.abs(this.rail.scrollLeft);

                    if (direction === 'next') {
                        const isAtEnd = current >= max - Math.max(10, step * 0.4);
                        if (isAtEnd) {
                            // بازگشت نرم به اولین کارت در پایان اسلایدر
                            this.rail.scrollTo({ left: 0, behavior: 'smooth' });
                            return;
                        }
                        const next = Math.min(current + step, max);
                        this.rail.scrollTo({ left: isRtl ? -next : next, behavior: 'smooth' });
                    } else {
                        const isAtStart = current <= 10;
                        if (isAtStart) {
                            this.rail.scrollTo({ left: isRtl ? -max : max, behavior: 'smooth' });
                            return;
                        }
                        const prev = Math.max(current - step, 0);
                        this.rail.scrollTo({ left: isRtl ? -prev : prev, behavior: 'smooth' });
                    }
                },
                scrollToIndex(idx) {
                    if (!this.rail) return;
                    const cards = this.rail.querySelectorAll('.story-card');
                    if (cards[idx]) {
                        cards[idx].scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
                    }
                },
                toFa(num) {
                    const f = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                    return String(num).replace(/[0-9]/g, (d) => f[d]);
                }
            }"
            class="mt-6 sm:mt-10"
        >
            @php
                $toFa = fn (int|string $n): string => strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
                $stories = [
                    [
                        'quote' => 'با رسیدن دفتر و کیف جدید، دیگر خجالت نکشیدم و با ذوق سر صف مدرسه ایستادم؛ حالا می‌خواهم مثل مربی‌ام معلم شوم.',
                        'name' => 'امیرعلی، ۱۰ ساله',
                        'role' => 'دانش‌آموز تحت پوشش',
                        'category' => 'آموزش و تحصیل',
                        'emoji' => '🎒',
                        'color' => '#1572A1',
                    ],
                    [
                        'quote' => 'بسته‌های غذایی ماهانه آرامش را به خانه‌مان آورد؛ دیگر دغدغه شام بچه‌ها را ندارم و هر روز پرانرژی بازی می‌کنند.',
                        'name' => 'مادر خانواده',
                        'role' => 'سرپرست خانوار',
                        'category' => 'معیشت و تغذیه',
                        'emoji' => '🍲',
                        'color' => '#38538C',
                    ],
                    [
                        'quote' => 'کاپشن گرم زمستانی که آمد، اولین بار بود در سرما با لبخند به مدرسه رفتم؛ این هدیه برایم پر از گرما و امید بود.',
                        'name' => 'زهرا، کلاس پنجم',
                        'role' => 'کودک تحت پوشش',
                        'category' => 'پوشاک و ملزومات',
                        'emoji' => '🧣',
                        'color' => '#D4205F',
                    ],
                    [
                        'quote' => 'حضور صبورانه مددکار مرکز مثل یک تکیه‌گاه محکم بود؛ راهنمایی‌هایشان مسیر درمان و زندگی فرزندم را نجات داد.',
                        'name' => 'پدر خانواده',
                        'role' => 'توانمندسازی خانواده',
                        'category' => 'مددکاری تخصصی',
                        'emoji' => '🌱',
                        'color' => '#5964AE',
                    ],
                    [
                        'quote' => 'در کلاس‌های نقاشی و تقویتی مرکز فهمیدم چقدر استعداد دارم؛ حالا برای اولین بار نمرات کارنامه‌ام همه خیلی‌خوب شده است.',
                        'name' => 'مهدی، ۱۲ ساله',
                        'role' => 'کودک تحت پوشش',
                        'category' => 'کشف استعداد',
                        'emoji' => '🎨',
                        'color' => '#36A9DF',
                    ],
                    [
                        'quote' => 'گزارش شفاف مرکز و دیدن لبخند واقعی این بچه‌ها، زیباترین حس همدلی را به من هدیه داد؛ همراهی با آنها برکت زندگی من است.',
                        'name' => 'فرهاد',
                        'role' => 'حامی ماهانه',
                        'category' => 'همراهی و نیکوکاری',
                        'emoji' => '💛',
                        'color' => '#A4184B',
                    ],
                    [
                        'quote' => 'جلسات مشاوره‌ای که مرکز برای پسرم تدارک دید، ترس و اضطراب را از او دور کرد؛ دوباره صدای خنده‌هایش در خانه پیچیده است.',
                        'name' => 'مادر سه فرزند',
                        'role' => 'سرپرست خانوار',
                        'category' => 'سلامت روان و امید',
                        'emoji' => '✨',
                        'color' => '#059669',
                    ],
                    [
                        'quote' => 'تا به حال هیچ‌کس برایم کیک نگرفته بود؛ در جشن تولد مرکز آرزو کردم همه بچه‌های دنیا همیشه مثل آن روز شاد باشند.',
                        'name' => 'فاطمه، ۸ ساله',
                        'role' => 'کودک تحت پوشش',
                        'category' => 'نشاط و انگیزه',
                        'emoji' => '🎂',
                        'color' => '#E11D48',
                    ],
                ];
            @endphp

            <!-- نوار کارت‌های کاروسل با افکت فید لبه‌های بسیار ملایم و طبیعی در مرز خارجی -->
            <div
                x-ref="rail"
                tabindex="0"
                aria-label="قصه‌های همدلی"
                role="region"
                aria-roledescription="کاروسل"
                class="mt-6 flex cursor-grab snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth py-2 px-1 [scrollbar-width:none] [-webkit-overflow-scrolling:touch] [overscroll-behavior-x:contain] [mask-image:linear-gradient(to_right,transparent_0%,#000_1.5%,#000_98.5%,transparent_100%)] [-webkit-mask-image:linear-gradient(to_right,transparent_0%,#000_1.5%,#000_98.5%,transparent_100%)] [&::-webkit-scrollbar]:hidden active:cursor-grabbing sm:mt-9 sm:gap-4.5"
            >
                @foreach($stories as $index => $story)
                    <article
                        data-index="{{ $index }}"
                        class="story-card group relative flex w-[82%] min-[420px]:w-[78%] sm:w-[48%] lg:w-[31.8%] shrink-0 snap-start flex-col justify-between overflow-hidden rounded-2xl border border-slate-100/90 bg-white p-4 shadow-[0_2px_8px_-3px_rgba(56,83,140,0.06)] transition-all duration-300 hover:-translate-y-1 hover:border-slate-200 hover:shadow-[0_12px_24px_-6px_rgba(56,83,140,0.12)] active:scale-[0.99] sm:rounded-3xl sm:p-5.5"
                    >
                        <!-- علامت گیومه شیک در پس‌زمینه کارت -->
                        <span class="pointer-events-none absolute left-3.5 top-2.5 select-none text-3xl font-serif text-slate-100/90 transition-colors duration-300 group-hover:text-slate-200/80 sm:left-5 sm:top-3.5 sm:text-5xl" aria-hidden="true">❝</span>

                        <!-- سربرگ کارت: آیکون اسکوئیرکل و بج دسته‌بندی -->
                        <div class="relative z-10 flex items-center justify-between gap-2">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-lg ring-1 ring-black/[0.04] transition-transform duration-300 group-hover:scale-105 sm:h-10.5 sm:w-10.5 sm:rounded-2xl sm:text-xl"
                                style="background-color: {{ $story['color'] }}12;"
                            >
                                {{ $story['emoji'] }}
                            </span>

                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[9.5px] font-bold sm:px-2.5 sm:text-[10px]"
                                style="background-color: {{ $story['color'] }}10; color: {{ $story['color'] }};"
                            >
                                {{ $story['category'] }}
                            </span>
                        </div>

                        <!-- متن قصه: با حداکثر ۳ خط و سه‌نقطه در صورت عبور (بهینه برای تمام اندازه‌های موبایل) -->
                        <blockquote
                            class="relative z-10 mt-3 text-[11.5px] font-medium leading-[1.65] text-slate-700 min-[380px]:text-xs sm:mt-3.5 sm:text-[13px] sm:leading-7"
                            style="display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; text-overflow: ellipsis; min-height: 3.75rem;"
                            title="{{ $story['quote'] }}"
                        >
                            «{{ $story['quote'] }}»
                        </blockquote>

                        <!-- پاورقی کارت: اطلاعات راوی و نقش با ارتفاع و تراز یکدست -->
                        <footer class="relative z-10 mt-3.5 flex items-center justify-between border-t border-slate-100/80 pt-2.5 text-xs sm:mt-4 sm:pt-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold sm:h-7 sm:w-7 sm:text-[11px]"
                                    style="background-color: {{ $story['color'] }}15; color: {{ $story['color'] }};"
                                    aria-hidden="true"
                                >
                                    {{ mb_substr($story['name'], 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <span class="block truncate text-[11.5px] font-black text-slate-900 sm:text-xs">{{ $story['name'] }}</span>
                                    <span class="block truncate text-[9.5px] font-medium text-slate-400 sm:text-[10px]">{{ $story['role'] }}</span>
                                </div>
                            </div>

                            <span class="text-[11px] text-slate-300 group-hover:text-slate-400" aria-hidden="true">
                                <i class="bi bi-chat-quote-fill"></i>
                            </span>
                        </footer>
                    </article>
                @endforeach
            </div>

            <!-- نوار کنترل مدرن، یکپارچه و بهینه‌شده برای موبایل (Integrated Mobile Navigation Dock) -->
            <div class="mt-5 flex items-center justify-center sm:mt-7">
                <div class="inline-flex items-center gap-1 rounded-full border border-slate-200/80 bg-white/95 p-1 shadow-[0_2px_10px_-2px_rgba(56,83,140,0.07)] ring-1 ring-black/[0.02] backdrop-blur-sm sm:gap-1.5 sm:p-1.5">
                    <!-- دکمه قبلی (راست در RTL) با طراحی ارگونومیک -->
                    <button
                        type="button"
                        @click="scroll('prev')"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-600 transition-all duration-200 hover:bg-[#5964AE] hover:text-white active:scale-90 sm:h-8.5 sm:w-8.5"
                        aria-label="داستان قبلی"
                    >
                        <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
                    </button>

                    <!-- نقاط پیمایش ظریف و لمسی -->
                    <div class="flex items-center gap-1 px-1 sm:gap-1.5 sm:px-2" role="tablist" aria-label="انتخاب داستان">
                        @foreach($stories as $index => $story)
                            <button
                                type="button"
                                @click="scrollToIndex({{ $index }})"
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="active === {{ $index }} ? 'w-4.5 bg-[#5964AE] sm:w-5.5' : 'w-1.5 bg-slate-200 hover:bg-slate-300'"
                                aria-label="رفتن به داستان {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>

                    <!-- نشانگر عدد داستان -->
                    <div class="border-s border-slate-200/80 pe-1 ps-2 text-[10.5px] font-bold text-slate-400 tabular-nums sm:text-[11px]">
                        <span x-text="toFa(active + 1)" class="font-black text-[#5964AE]"></span><span class="text-slate-300">/</span><span>{{ $toFa(count($stories)) }}</span>
                    </div>

                    <!-- دکمه بعدی (چپ در RTL) با طراحی ارگونومیک -->
                    <button
                        type="button"
                        @click="scroll('next')"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-600 transition-all duration-200 hover:bg-[#5964AE] hover:text-white active:scale-90 sm:h-8.5 sm:w-8.5"
                        aria-label="داستان بعدی"
                    >
                        <i class="bi bi-chevron-left text-xs" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
