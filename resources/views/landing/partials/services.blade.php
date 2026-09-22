@php
    // کارت‌های فعال از دیتابیس (کش‌شده) خوانده می‌شوند؛ در نبود داده، نمونه‌های پیش‌فرض نمایش داده می‌شوند.
    $serviceRows = \App\Support\Landing\LandingContent::serviceRows();
@endphp

<!-- بخش خدمات: دو رگال کارت عمودی -->
<section
    id="services"
    class="landing-section bg-white pb-12 pt-2 sm:pb-16 sm:pt-3"
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
        <div class="flex items-center justify-center gap-2 sm:gap-3" data-reveal>
            <span class="h-px w-8 bg-gradient-to-l from-transparent to-[#1572A1]/50 sm:w-16" aria-hidden="true"></span>
            <p class="text-sm font-bold text-[#1572A1] sm:text-base">خدمات ما</p>
            <span class="h-px w-8 bg-gradient-to-r from-transparent to-[#1572A1]/50 sm:w-16" aria-hidden="true"></span>
        </div>

        <div class="mt-2 space-y-2.5 sm:mt-9 sm:space-y-3" data-reveal>
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
    </div>
</section>
