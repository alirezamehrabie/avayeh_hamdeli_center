@php
    $slides = [
        [
            'image' => 'images/landing/slide-1.jpg',
            'alt' => 'کودکان تحت پوشش در مسیر آموزش و یادگیری',
        ],
        [
            'image' => 'images/landing/slide-2.jpg',
            'alt' => 'تغذیه سالم و بسته‌های غذایی کودکان',
        ],
        [
            'image' => 'images/landing/slide-3.jpg',
            'alt' => 'حمایت و پناه از کودکان بی‌سرپرست',
        ],
        [
            'image' => 'images/landing/slide-4.jpg',
            'alt' => 'بازی و شادی کودکان در مرکز',
        ],
    ];
@endphp

<!-- بنر اسلایدی تصاویر مرکز -->
<section
    id="slider"
    class="bg-white px-3 pb-2 pt-20 sm:px-6 sm:pt-24"
    aria-label="بنر تصاویر مرکز نیکوکاری کودکان"
    aria-roledescription="کاروسل"
    x-data="{
        active: 0,
        count: {{ count($slides) }},
        hovered: false,
        timer: null,
        duration: 5000,
        swipeX: null,
        reduced: false,
        get playing() { return !this.reduced; },
        init() {
            this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.start();
            document.addEventListener('visibilitychange', () => {
                document.hidden ? this.stop() : this.start();
            });
        },
        start() {
            this.stop();
            if (!this.playing) return;
            this.timer = setInterval(() => {
                if (!this.hovered) this.next();
            }, this.duration);
        },
        stop() {
            if (this.timer) clearInterval(this.timer);
            this.timer = null;
        },
        go(index) {
            this.active = (index + this.count) % this.count;
            this.start();
        },
        next() { this.go(this.active + 1); },
        prev() { this.go(this.active - 1); },
        onPointerDown(event) { this.swipeX = event.clientX; },
        onPointerUp(event) {
            if (this.swipeX === null) return;
            const delta = event.clientX - this.swipeX;
            this.swipeX = null;
            if (Math.abs(delta) < 40) return;
            // کشیدن به چپ یعنی اسلاید بعدی (در چیدمان RTL)
            delta < 0 ? this.next() : this.prev();
        },
    }"
    @mouseenter="hovered = true"
    @mouseleave="hovered = false"
    @pointerdown="onPointerDown($event)"
    @pointerup="onPointerUp($event)"
    @pointercancel="swipeX = null"
    @keydown.left.prevent="next()"
    @keydown.right.prevent="prev()"
    tabindex="0"
>
    <div class="mx-auto max-w-7xl overflow-hidden rounded-2xl ring-1 ring-slate-900/5 sm:rounded-3xl">
        <div class="relative h-44 w-full touch-pan-y select-none sm:h-[320px] lg:h-[400px]">
            @foreach($slides as $index => $slide)
                <div
                    class="absolute inset-0 transition-opacity duration-700 ease-out motion-reduce:transition-none"
                    :class="active === {{ $index }} ? 'opacity-100' : 'opacity-0'"
                    role="group"
                    aria-roledescription="اسلاید"
                    aria-label="{{ $slide['alt'] }}"
                    :aria-hidden="active === {{ $index }} ? 'false' : 'true'"
                >
                    <img
                        src="{{ asset($slide['image']) }}"
                        alt="{{ $slide['alt'] }}"
                        class="h-full w-full object-cover"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                        fetchpriority="{{ $index === 0 ? 'high' : 'low' }}"
                        decoding="async"
                        draggable="false"
                    >
                </div>
            @endforeach

            <!-- دکمه‌های پیمایش (در RTL: چپ = بعدی) -->
            <button
                type="button"
                @click="next()"
                aria-label="اسلاید بعدی"
                class="absolute left-2 top-1/2 z-30 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-black/25 text-white backdrop-blur-sm transition hover:bg-black/45 sm:left-4 sm:inline-flex sm:h-11 sm:w-11"
            >
                <i class="bi bi-chevron-left text-lg" aria-hidden="true"></i>
            </button>
            <button
                type="button"
                @click="prev()"
                aria-label="اسلاید قبلی"
                class="absolute right-2 top-1/2 z-30 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/40 bg-black/25 text-white backdrop-blur-sm transition hover:bg-black/45 sm:right-4 sm:inline-flex sm:h-11 sm:w-11"
            >
                <i class="bi bi-chevron-right text-lg" aria-hidden="true"></i>
            </button>

            <!-- نشانگرها -->
            <div class="absolute inset-x-0 bottom-1 z-30 flex items-center justify-center gap-0.5 sm:bottom-2 sm:gap-1">
                @foreach($slides as $index => $slide)
                    <button
                        type="button"
                        @click="go({{ $index }})"
                        aria-label="نمایش اسلاید {{ $index + 1 }}"
                        :aria-current="active === {{ $index }} ? 'true' : 'false'"
                        class="flex h-7 w-5 items-center justify-center sm:h-8 sm:w-6"
                    >
                        <span
                            class="h-1.5 rounded-full transition-all duration-500 sm:h-2"
                            :class="active === {{ $index }} ? 'w-3 bg-white/95 sm:w-5' : 'w-1.5 bg-white/45 hover:bg-white/75'"
                            aria-hidden="true"
                        ></span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>
