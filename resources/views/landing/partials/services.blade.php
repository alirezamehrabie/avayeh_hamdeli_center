@php
    // کارت‌های فعال از دیتابیس (کش‌شده) خوانده می‌شوند؛ در نبود داده، نمونه‌های پیش‌فرض نمایش داده می‌شوند.
    $serviceRows = \App\Support\Landing\LandingContent::serviceRows();
@endphp

<!-- بخش خدمات: دو رگال کارت عمودی -->
<section
    id="services"
    class="landing-section bg-white pb-8 pt-4 sm:pb-8 sm:pt-14"
    aria-label="خدمات مرکز آوای همدلی"
    x-data="{
        rails: [],
        kickoff: null,
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
                    this.idleTimer = setTimeout(() => { this.userActive = false; }, 2000);
                };
                rail.addEventListener('pointerdown', wake);
                rail.addEventListener('wheel', wake, { passive: true });
            });

            if (! this.motionOk) return;
            // اولین حرکت 0.8 ثانیه پس از لود صفحه انجام می‌شود
            this.kickoff = setTimeout(() => this.startTicker(), 100);
            document.addEventListener('visibilitychange', () => {
                if (! document.hidden && this.motionOk) this.startTicker();
            });
        },
        firstRail() {
            return this.rails[0];
        },
        startTicker() {
            clearInterval(this.timer);
            this.timer = setInterval(() => this.advance(), 4000);
        },
        advance() {
            if (this.hovered || this.userActive || document.hidden) return;
            const isRtl = getComputedStyle(this.firstRail()).direction === 'rtl';

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
        <x-landing.section-title data-reveal heading="خدمات ما" />

        <div class="mt-6 space-y-2.5 sm:mt-9 sm:space-y-3" data-reveal>
            @foreach($serviceRows as $row)
                <div
                    x-data="{
                        thumb: 100,
                        offset: 0,
                        measure(rail) {
                            const max = rail.scrollWidth - rail.clientWidth;
                            if (max <= 1) { this.thumb = 100; this.offset = 0; return; }
                            this.thumb = Math.max(10, (rail.clientWidth / rail.scrollWidth) * 100);
                            this.offset = (Math.abs(rail.scrollLeft) / max) * (100 - this.thumb);
                        },
                    }"
                    x-init="$nextTick(() => {
                        measure($refs.rail);
                        $watch('$el.offsetHeight', () => measure($refs.rail));
                        const onResize = () => measure($refs.rail);
                        window.addEventListener('resize', onResize);
                        $cleanup(() => window.removeEventListener('resize', onResize));
                    })"
                >
                    <ul
                        data-rail
                        x-ref="rail"
                        tabindex="0"
                        aria-label="{{ $row['label'] }}"
                        @scroll="measure($el)"
                        class="flex cursor-grab snap-x snap-mandatory gap-2 overflow-x-auto scroll-smooth px-0.5 pb-1 [scrollbar-width:none] [mask-image:linear-gradient(to_right,transparent,#000_4%,#000_96%,transparent)] active:cursor-grabbing [&::-webkit-scrollbar]:hidden sm:gap-2.5 lg:gap-4"
                    >
                        @foreach($row['items'] as $service)
                            <li class="w-[30%] max-w-[110px] shrink-0 snap-center sm:w-[21%] lg:w-[150px] lg:max-w-[150px]">
                                <article
                                    class="group flex h-full flex-col items-center justify-center gap-1.5 rounded-2xl bg-white p-1.5 shadow-[0_1px_3px_rgba(15,23,42,0.05)] transition duration-500 ease-out hover:-translate-y-0.5 hover:shadow-[0_4px_14px_rgba(89,100,174,0.10)] sm:gap-2 sm:p-2"
                                >
                                    <div class="aspect-square w-full overflow-hidden rounded-[1.75rem] bg-slate-50 sm:rounded-[2.5rem]">
                                        <img
                                            src="{{ $service['image'] }}"
                                            alt=""
                                            aria-hidden="true"
                                            loading="lazy"
                                            decoding="async"
                                            class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.05]"
                                        >
                                    </div>
                                    <h3 class="w-full truncate px-1 pb-1 text-center text-[11px] font-bold leading-5 text-slate-900 sm:text-xs" title="{{ $service['title'] }}">
                                        {{ $service['title'] }}
                                    </h3>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mx-auto mt-1.5 flex h-[3px] w-16 overflow-hidden rounded-full bg-slate-200/80" aria-hidden="true">
                        <div
                            class="h-full rounded-full bg-[#1572A1]/50 transition-[margin,width] duration-300 ease-out"
                            :style="`width: ${thumb}%; margin-inline-start: ${offset}%`"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- دکمه مینیمال مشاهده همه خدمات --}}
        <div class="mt-6 flex justify-center sm:mt-8" data-reveal>
            <a
                href="{{ route('landing.services') }}"
                class="group inline-flex items-center gap-2.5 rounded-full border border-slate-200/90 bg-white/90 px-5 py-2.5 text-xs font-semibold text-slate-700 shadow-[0_1px_2px_rgba(15,23,42,0.04)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#1572A1]/40 hover:bg-slate-50/80 hover:text-[#1572A1] hover:shadow-[0_4px_12px_rgba(21,114,161,0.12)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1572A1] active:translate-y-0 active:scale-[0.98] sm:px-6 sm:py-3 sm:text-sm"
                aria-label="مشاهده همه خدمات (View All Services)"
            >
                <span class="flex items-center gap-1.5">
                    <span>مشاهده همه خدمات</span>
                    <span class="text-[10px] font-normal text-slate-400 transition-colors group-hover:text-[#1572A1]/70 sm:text-xs">/ راه های مهربانی</span>
                </span>
                <svg class="h-4 w-4 transition-transform duration-300 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
                </svg>
            </a>
        </div>
    </div>
</section>
