@php
    // ردیف اول رگال خدمات
    $servicesRowOne = [
        ['image' => 'images/landing/services/01-hezine-tahsil.png', 'title' => 'هزینه تحصیل'],
        ['image' => 'images/landing/services/02-behdasht-darman.png', 'title' => 'بهداشت و درمان'],
        ['image' => 'images/landing/services/03-nan-mehrabani.png', 'title' => 'نان مهربانی'],
        ['image' => 'images/landing/services/04-sarparasti-ettaam.png', 'title' => 'سرپرستی ایتام'],
        ['image' => 'images/landing/services/05-pooshak.png', 'title' => 'پوشاک'],
        ['image' => 'images/landing/services/06-pack-arzaq.png', 'title' => 'پک ارزاق'],
    ];

    // ردیف دوم رگال خدمات
    $servicesRowTwo = [
        ['image' => 'images/landing/services/07-shir-khoshk.png', 'title' => 'شیر خشک'],
        ['image' => 'images/landing/services/08-sofreh-om-ol-banin.png', 'title' => 'سفره ام‌البنین (س)'],
        ['image' => 'images/landing/services/09-aqiqe.png', 'title' => 'عقیقه'],
        ['image' => 'images/landing/services/10-kala-daste-dom.png', 'title' => 'اهدای کالای دست دوم'],
        ['image' => 'images/landing/services/11-mashaghel-hamdeli.png', 'title' => 'مشاغل همدلی'],
        ['image' => 'images/landing/services/12-eftekharat-hamdeli.png', 'title' => 'افتخارات همدلی'],
    ];

    $serviceRows = [
        ['label' => 'رگال خدمات، ردیف یک', 'items' => $servicesRowOne],
        ['label' => 'رگال خدمات، ردیف دو', 'items' => $servicesRowTwo],
    ];
@endphp

<!-- بخش خدمات: دو رگال کارت عمودی -->
<section
    id="services"
    class="landing-section bg-white pb-12 pt-2 sm:pb-16 sm:pt-3"
    aria-label="خدمات مرکز آوای همدلی"
    x-data="{
        rails: [],
        timer: null,
        hovered: false,
        userActive: false,
        idleTimer: null,
        motionOk: true,
        init() {
            this.motionOk = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.rails = Array.from(this.$el.querySelectorAll('[data-rail]'));

            this.rails.forEach((rail) => {
                rail.addEventListener('pointerenter', () => { this.hovered = true; });
                rail.addEventListener('pointerleave', () => { this.hovered = false; });
                // لمس یا اسکرول دستی، حرکت خودکار را برای چند ثانیه متوقف می‌کند
                const wake = () => {
                    this.userActive = true;
                    clearTimeout(this.idleTimer);
                    this.idleTimer = setTimeout(() => { this.userActive = false; }, 4000);
                };
                rail.addEventListener('pointerdown', wake);
                rail.addEventListener('wheel', wake, { passive: true });
            });

            if (! this.motionOk) return;
            this.timer = setInterval(() => this.advance(), 4200);
            document.addEventListener('visibilitychange', () => {
                clearInterval(this.timer);
                if (document.hidden || ! this.motionOk) return;
                this.timer = setInterval(() => this.advance(), 4200);
            });
        },
        advance() {
            if (this.hovered || this.userActive || document.hidden) return;
            const isRtl = getComputedStyle(this.rails[0]).direction === 'rtl';

            this.rails.forEach((rail) => {
                const max = rail.scrollWidth - rail.clientWidth;
                if (max <= 4) return;

                const first = rail.firstElementChild;
                const gap = parseFloat(getComputedStyle(rail).columnGap) || 0;
                const step = first ? first.getBoundingClientRect().width + gap : 160;
                // در RTL مقدار scrollLeft منفی است؛ پیشرفت را همیشه مثبت می‌خوانیم
                const progress = Math.abs(rail.scrollLeft);
                const next = progress + step >= max ? 0 : progress + step;

                rail.scrollTo({ left: isRtl ? -next : next, behavior: 'smooth' });
            });
        },
    }"
>
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="text-center" data-reveal>
            <span class="inline-flex items-center gap-2 rounded-full bg-[#36A9DF]/10 px-3.5 py-1 text-[11px] font-bold text-[#1572A1]">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                خدمات ما
            </span>
        </div>

        <div class="mt-4 space-y-2.5 sm:mt-6 sm:space-y-3" data-reveal>
            @foreach($serviceRows as $row)
                <ul
                    data-rail
                    tabindex="0"
                    aria-label="{{ $row['label'] }}"
                    class="flex snap-x snap-mandatory gap-2 overflow-x-auto scroll-smooth px-0.5 pb-1 [scrollbar-width:none] [mask-image:linear-gradient(to_right,transparent,#000_4%,#000_96%,transparent)] [&::-webkit-scrollbar]:hidden sm:gap-2.5 lg:gap-4"
                >
                    @foreach($row['items'] as $service)
                        <li class="w-[30%] max-w-[110px] shrink-0 snap-center sm:w-[21%] lg:w-[150px] lg:max-w-[150px]">
                            <article
                                class="group flex h-full min-h-[126px] flex-col items-center justify-center gap-1.5 rounded-2xl bg-white p-1.5 shadow-[0_1px_3px_rgba(15,23,42,0.05)] transition duration-500 ease-out hover:-translate-y-0.5 hover:shadow-[0_4px_14px_rgba(89,100,174,0.10)] sm:min-h-[146px] sm:gap-2 sm:p-2"
                            >
                                <div class="w-full overflow-hidden rounded-[1.75rem] bg-slate-50 sm:rounded-[2.5rem]">
                                    <img
                                        src="{{ asset($service['image']) }}"
                                        alt=""
                                        aria-hidden="true"
                                        loading="lazy"
                                        decoding="async"
                                        class="h-[86px] w-full object-cover transition duration-700 ease-out group-hover:scale-[1.05] sm:h-[100px]"
                                    >
                                </div>
                                <h3 class="w-full truncate px-1 pb-1 text-center text-[11px] font-bold leading-5 text-slate-900 sm:text-xs" title="{{ $service['title'] }}">
                                    {{ $service['title'] }}
                                </h3>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>

        <p class="mt-6 text-center sm:mt-8">
            <a
                href="#help"
                class="inline-flex min-h-12 items-center gap-2 rounded-2xl border-2 border-[#5964AE]/20 bg-[#5964AE]/5 px-6 py-3 text-sm font-bold text-[#5964AE] transition hover:border-[#5964AE]/40 hover:bg-[#5964AE]/10 sm:min-h-14 sm:px-8 sm:py-4 sm:text-base"
            >
                حمایت از این خدمات
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </p>
    </div>
</section>
