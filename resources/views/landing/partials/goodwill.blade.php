@php
    // پیام‌های حالِ خوبِ خیرین؛ تا پیش از راه‌اندازی مدیریت محتوا، آیتم‌های پیش‌فرض رندر می‌شوند.
    $icons = [
        'heart' => '<path d="M12 20.7C6.1 16.9 2.8 13.5 2.8 9.8 2.8 7 5 4.8 7.8 4.8c1.7 0 3.3.8 4.2 2.1.9-1.3 2.5-2.1 4.2-2.1 2.8 0 5 2.2 5 5 0 3.7-3.3 7.1-9.2 10.9Z"/>',
        'smile' => '<circle cx="12" cy="12" r="8.5"/><path d="M7.9 13.9a4.6 4.6 0 0 0 8.2 0"/><path d="M9.3 9.3v1M14.7 9.3v1"/>',
        'sunrise' => '<path d="M12 3.2v2.5M5.1 6.2l1.7 1.7M18.9 6.2l-1.7 1.7M2.6 16.9h3.3M18.1 16.9h3.3"/><path d="M8.2 16.9a3.8 3.8 0 0 1 7.6 0"/>',
        'sparkle' => '<path d="m10.4 3.6 1.4 4.6 4.6 1.4-4.6 1.4-1.4 4.6-1.4-4.6-4.6-1.4 4.6-1.4Z"/><path d="m17.6 13.4.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9Z"/>',
        'candle' => '<path d="M12 3.1c1.8 2 2.7 3.4 2.7 4.6a2.7 2.7 0 1 1-5.4 0c0-1.2.9-2.6 2.7-4.6Z"/><rect x="9.2" y="11.6" width="5.6" height="9.6" rx="1.5"/>',
    ];

    $goodwillItems = [
        [
            'message' => 'هر واریز که می‌کنم دلم سبک می‌شود؛ این حالِ خوب را هیچ‌جا نمی‌توان خرید.',
            'name' => 'زهرا ک.',
            'role' => 'از همراهان همیشگی',
            'icon' => 'heart',
            'color' => '#D4205F',
        ],
        [
            'message' => 'صبح‌ها با امید بیشتری بیدار می‌شوم؛ می‌دانم کودکی به‌خاطرِ من فردا را قشنگ‌تر می‌بیند.',
            'name' => 'علی م.',
            'role' => 'خیر',
            'icon' => 'smile',
            'color' => '#1572A1',
        ],
        [
            'message' => 'گزارش‌های شفافِ هر ماه دلم را گرم می‌کند؛ انگار خودم سرِ سفره‌شان نشسته‌ام.',
            'name' => 'مریم س.',
            'role' => 'حامی ماهانه',
            'icon' => 'sunrise',
            'color' => '#36A9DF',
        ],
        [
            'message' => 'سهمی از مهریه‌مان را وقف کودکان کردیم؛ از آن روز آرامش خانۀمان چند برابر شده است.',
            'name' => 'حسین و فاطمۀ ر.',
            'role' => 'زوجِ خیر',
            'icon' => 'sparkle',
            'color' => '#5964AE',
        ],
        [
            'message' => 'ماهانه به نیت مادرمان کمک می‌کنیم؛ هر بار که نامش در دعای کودکان می‌آید، دلمان روشن می‌شود.',
            'name' => 'خانوادۀ تهرانی',
            'role' => 'خیرین',
            'icon' => 'candle',
            'color' => '#A4184B',
        ],
    ];
@endphp

<!-- بخش حال خوش همدلی: روایت حالِ خوبِ خیرین در یک رگال افقی -->
<section id="goodwill" class="landing-section bg-white pb-14 pt-10 sm:pb-16 sm:pt-12" aria-labelledby="goodwill-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div data-reveal>
            <x-landing.section-title id="goodwill-title" heading="حال خوشِ همدلی" />
        </div>

        <div
            class="mt-6 sm:mt-9"
            data-reveal
            x-data="{
                hovered: false,
                userActive: false,
                idleTimer: null,
                timer: null,
                motionOk: true,
                thumb: 100,
                offset: 0,
                init() {
                    this.motionOk = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    const rail = this.$refs.rail;

                    rail.addEventListener('pointerenter', () => { this.hovered = true; });
                    rail.addEventListener('pointerleave', () => { this.hovered = false; });
                    // لمس یا اسکرول دستی، حرکت خودکار را برای چند ثانیه متوقف می‌کند
                    const wake = () => {
                        this.userActive = true;
                        clearTimeout(this.idleTimer);
                        this.idleTimer = setTimeout(() => { this.userActive = false; }, 2000);
                    };
                    rail.addEventListener('pointerdown', wake);
                    rail.addEventListener('wheel', wake, { passive: true });

                    this.measure();
                    window.addEventListener('resize', () => this.measure());

                    if (! this.motionOk) return;
                    setTimeout(() => this.startTicker(), 100);
                    document.addEventListener('visibilitychange', () => {
                        if (! document.hidden && this.motionOk) this.startTicker();
                    });
                },
                measure() {
                    const rail = this.$refs.rail;
                    const max = rail.scrollWidth - rail.clientWidth;
                    if (max <= 1) { this.thumb = 100; this.offset = 0; return; }
                    this.thumb = Math.max(10, (rail.clientWidth / rail.scrollWidth) * 100);
                    this.offset = (Math.abs(rail.scrollLeft) / max) * (100 - this.thumb);
                },
                startTicker() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => this.advance(), 4500);
                },
                advance() {
                    if (this.hovered || this.userActive || document.hidden) return;
                    const rail = this.$refs.rail;
                    const max = rail.scrollWidth - rail.clientWidth;
                    if (max <= 4) return;

                    const isRtl = getComputedStyle(rail).direction === 'rtl';
                    const first = rail.firstElementChild;
                    const gap = parseFloat(getComputedStyle(rail).columnGap) || 0;
                    const step = first ? first.getBoundingClientRect().width + gap : 280;
                    // در RTL مقدار scrollLeft منفی است؛ پیشرفت را همیشه مثبت می‌خوانیم
                    const progress = Math.abs(rail.scrollLeft);
                    const next = progress + step >= max ? 0 : progress + step;

                    rail.scrollTo({ left: isRtl ? -next : next, behavior: 'smooth' });
                },
            }"
        >
            <ul
                x-ref="rail"
                tabindex="0"
                aria-label="پیام‌های خیرین"
                @scroll="measure()"
                class="flex cursor-grab snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth px-0.5 pb-1 [scrollbar-width:none] [mask-image:linear-gradient(to_right,transparent,#000_4%,#000_96%,transparent)] active:cursor-grabbing [&::-webkit-scrollbar]:hidden sm:gap-4"
            >
                @foreach($goodwillItems as $item)
                    <li class="w-[82%] shrink-0 snap-center sm:w-[48%] lg:w-[32%]">
                        <article
                            class="relative flex h-full flex-col overflow-hidden rounded-2xl bg-white p-4 shadow-[0_2px_10px_rgba(56,83,140,0.06)] ring-1 ring-slate-100 transition duration-500 ease-out hover:-translate-y-0.5 hover:shadow-[0_6px_18px_rgba(89,100,174,0.12)] sm:rounded-3xl sm:p-6"
                        >
                            <span
                                class="absolute left-4 top-2 select-none text-4xl font-black leading-none opacity-[0.07] sm:left-5 sm:top-3 sm:text-5xl"
                                style="color: {{ $item['color'] }}"
                                aria-hidden="true"
                            >❝</span>
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl sm:h-11 sm:w-11 sm:rounded-2xl"
                                style="background-color: {{ $item['color'] }}14; color: {{ $item['color'] }}"
                                aria-hidden="true"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-5 w-5 sm:h-6 sm:w-6"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >{!! $icons[$item['icon']] !!}</svg>
                            </span>
                            <p class="relative mt-3 flex-1 text-xs leading-5 text-slate-700 sm:mt-4 sm:text-sm sm:leading-7">
                                «{{ $item['message'] }}»
                            </p>
                            <footer class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3 text-[11px] sm:mt-5 sm:pt-4 sm:text-xs">
                                <span class="font-black text-slate-900">{{ $item['name'] }}</span>
                                <span class="text-slate-300" aria-hidden="true">•</span>
                                <span class="text-slate-500">{{ $item['role'] }}</span>
                            </footer>
                        </article>
                    </li>
                @endforeach
            </ul>
            <div class="mx-auto mt-4 flex h-[3px] w-16 overflow-hidden rounded-full bg-slate-200/80" aria-hidden="true">
                <div
                    class="h-full rounded-full bg-[#38538C]/50 transition-[margin,width] duration-300 ease-out"
                    :style="`width: ${thumb}%; margin-inline-start: ${offset}%`"
                ></div>
            </div>
        </div>
    </div>
</section>
