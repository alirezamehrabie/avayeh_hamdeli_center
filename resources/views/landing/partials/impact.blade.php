<!-- بخش اعداد/اثر -->
<section id="impact" class="landing-section relative overflow-hidden bg-[linear-gradient(145deg,#38538C_0%,#5964AE_55%,#A4184B_140%)] py-16 sm:py-24" aria-labelledby="impact-title">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_80%_0%,rgba(54,169,223,0.25),transparent_50%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_100%,rgba(212,32,95,0.22),transparent_50%)]" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6">
        <div class="text-center text-white" data-reveal>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold ring-1 ring-white/25">
                <i class="bi bi-graph-up" aria-hidden="true"></i>
                عددهای امید
            </span>
            <h2 id="impact-title" class="mt-4 text-2xl font-black sm:text-3xl lg:text-4xl">
                اثر ما با هم، عدد می‌شود
            </h2>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-white/85 sm:text-base">
                آمار واقعی از تلاش و همراهی شما؛ عددها تلاش‌مند و صادق منتشر می‌شوند.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
            @php
                $stats = [
                    ['counter' => 200, 'suffix' => '+', 'label' => 'کودک تحت پوشش'],
                    ['counter' => 1200, 'suffix' => '+', 'label' => 'وعده‌ی غذایی', 'suffixLabel' => null],
                    ['counter' => 340, 'suffix' => '+', 'label' => 'بسته‌ی پوشاک'],
                    ['counter' => 80, 'suffix' => '+', 'label' => 'همراه و حامی'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div class="text-center">
                    <span
                        data-counter
                        data-target="{{ $stat['counter'] }}"
                        data-suffix="{{ $stat['suffix'] }}"
                        class="block text-3xl font-black text-white sm:text-4xl lg:text-5xl"
                    >۰</span>
                    <span class="mt-2 block text-sm font-semibold text-white/85">{{ $stat['label'] }}</span>
                </div>
            @endforeach
        </div>

        <p class="mt-10 text-center text-xs leading-6 text-white/70">
            * عددها نمونه و برای نمایش طراحی شده‌اند؛ پیش از انتشار با داده‌های واقعی مرکز به‌روزرسانی می‌شوند.
        </p>
    </div>
</section>