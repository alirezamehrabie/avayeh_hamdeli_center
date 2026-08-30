@php
    $slides = [
        [
            'image' => 'images/landing/slide-1.svg',
            'alt' => 'کودکان تحت پوشش در مسیر آموزش و یادگیری',
            'badge' => 'آموزش و یادگیری',
            'caption' => 'کلاس، کتاب و آینده‌ای روشن برای هر کودک',
        ],
        [
            'image' => 'images/landing/slide-2.svg',
            'alt' => 'تغذیه سالم و بسته‌های غذایی کودکان',
            'badge' => 'تغذیه سالم',
            'caption' => 'هر وعده‌ی گرم، یک قدم به سلامت نزدیک‌تر',
        ],
        [
            'image' => 'images/landing/slide-3.svg',
            'alt' => 'حمایت و پناه از کودکان بی‌سرپرست',
            'badge' => 'پناه و حمایت',
            'caption' => 'دست‌هایی که کودکان را تنها نمی‌گذارند',
        ],
        [
            'image' => 'images/landing/slide-4.svg',
            'alt' => 'بازی و شادی کودکان در مرکز',
            'badge' => 'بازی و شادی',
            'caption' => 'کودکی، حقِ بازی کردن و خندیدن است',
        ],
    ];
@endphp

<!-- اسلایدشو تصاویر مرکز -->
<section
    id="slider"
    class="relative bg-slate-900"
    aria-label="اسلایدشو تصاویر مرکز نیکوکاری کودکان"
    aria-roledescription="کاروسل"
    x-data="{
        active: 0,
        count: {{ count($slides) }},
        hovered: false,
        timer: null,
        duration: 6000,
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
    }"
    @mouseenter="hovered = true"
    @mouseleave="hovered = false"
    @keydown.left.prevent="next()"
    @keydown.right.prevent="prev()"
>
    <div class="relative h-[78svh] min-h-[460px] w-full overflow-hidden sm:h-[82svh] lg:h-[88svh]">
        <!-- تصاویر -->
        @foreach($slides as $index => $slide)
            <div
                class="absolute inset-0 transition-all duration-[1200ms] ease-out motion-reduce:transition-none"
                :class="active === {{ $index }} ? 'opacity-100 scale-100' : 'opacity-0 scale-105'"
                :aria-hidden="active === {{ $index }} ? 'false' : 'true'"
            >
                <img
                    src="{{ asset($slide['image']) }}"
                    alt="{{ $slide['alt'] }}"
                    class="h-full w-full object-cover"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    fetchpriority="{{ $index === 0 ? 'high' : 'low' }}"
                    draggable="false"
                >
            </div>
        @endforeach

        <!-- لایه‌های تیرگی برای خوانایی متن -->
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(8,20,38,0.55)_0%,rgba(8,20,38,0.28)_40%,rgba(8,20,38,0.80)_100%)]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_90%_at_78%_8%,rgba(21,114,161,0.30)_0%,transparent_55%),radial-gradient(110%_80%_at_8%_96%,rgba(164,24,75,0.36)_0%,transparent_60%)]" aria-hidden="true"></div>

        <!-- متن روی اسلاید -->
        <div class="absolute inset-0 z-20 flex items-center pt-16">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
                <div class="max-w-2xl text-center sm:text-right">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-md sm:text-sm">
                        <span class="relative flex h-2 w-2" aria-hidden="true">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#8ad4f2] opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-[#36A9DF]"></span>
                        </span>
                        @foreach($slides as $index => $slide)
                            <span x-show="active === {{ $index }}" x-cloak>{{ $slide['badge'] }}</span>
                        @endforeach
                    </span>

                    <h1 class="mt-5 text-4xl font-black leading-[1.25] text-white drop-shadow-[0_4px_24px_rgba(0,0,0,0.5)] sm:text-5xl lg:text-6xl">
                        آوای همدلی
                    </h1>

                    <p class="mt-4 text-lg font-extrabold leading-9 text-white drop-shadow-[0_2px_16px_rgba(0,0,0,0.55)] sm:text-2xl sm:leading-10">
                        حامی کودکان بی‌سرپرست و آسیب‌دیده
                    </p>

                    <p class="mt-4 min-h-14 text-sm font-medium leading-7 text-white/85 sm:text-base">
                        @foreach($slides as $index => $slide)
                            <span
                                x-show="active === {{ $index }}"
                                x-cloak
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="block"
                            >{{ $slide['caption'] }}</span>
                        @endforeach
                    </p>

                    <div class="mt-6 flex justify-center sm:mt-7 lg:justify-start">
                        <a
                            href="#about"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-white/35 bg-white/10 px-5 py-2.5 text-sm font-bold text-white backdrop-blur-md transition hover:bg-white/20 sm:min-h-14 sm:px-8 sm:py-4 sm:text-base"
                        >
                            درباره مرکز
                            <i class="bi bi-arrow-down" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- دکمه‌های پیمایش (در RTL: چپ = بعدی) -->
        <button
            type="button"
            @click="next()"
            aria-label="اسلاید بعدی"
            class="absolute left-3 top-1/2 z-30 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white backdrop-blur-md transition hover:bg-white/25 sm:left-6 sm:inline-flex"
        >
            <i class="bi bi-chevron-left text-xl" aria-hidden="true"></i>
        </button>
        <button
            type="button"
            @click="prev()"
            aria-label="اسلاید قبلی"
            class="absolute right-3 top-1/2 z-30 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white backdrop-blur-md transition hover:bg-white/25 sm:right-6 sm:inline-flex"
        >
            <i class="bi bi-chevron-right text-xl" aria-hidden="true"></i>
        </button>

        <!-- نشانگرها و کنترل پخش -->
        <div class="absolute bottom-3 left-0 right-0 z-30 flex items-center justify-center gap-1">
            @foreach($slides as $index => $slide)
                <button
                    type="button"
                    @click="go({{ $index }})"
                    aria-label="نمایش اسلاید {{ $index + 1 }}: {{ $slide['badge'] }}"
                    :aria-current="active === {{ $index }} ? 'true' : 'false'"
                    class="group flex h-11 w-8 items-center justify-center"
                >
                    <span
                        class="h-2.5 rounded-full transition-all duration-500"
                        :class="active === {{ $index }} ? 'w-12 bg-white' : 'w-2.5 bg-white/40 group-hover:bg-white/70'"
                        aria-hidden="true"
                    ></span>
                </button>
            @endforeach
        </div>

        <!-- نشان اعتماد شناور -->
        <div class="absolute bottom-6 left-6 z-30 hidden items-center gap-3 rounded-2xl border border-white/25 bg-white/10 px-4 py-3 text-white backdrop-blur-md lg:flex">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                <i class="bi bi-shield-check text-lg" aria-hidden="true"></i>
            </span>
            <span>
                <span class="block text-xs font-black">شفاف و امن</span>
                <span class="block text-[11px] text-white/75">هر کمک، پیگیری می‌شود</span>
            </span>
        </div>
    </div>
</section>
