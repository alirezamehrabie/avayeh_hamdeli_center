<!-- بخش اعتماد: جداکننده مدرن و مینیمال بخش‌ها با ارکان اعتماد مرکز -->
<section
    x-data="{
        visible: false,
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.visible = true;
                return;
            }
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        this.visible = true;
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.15 });
            observer.observe(this.$el);
        }
    }"
    class="relative border-y border-slate-100/90 bg-gradient-to-b from-slate-50/50 via-white to-slate-50/30 py-6 sm:py-8"
    aria-label="ارکان اعتماد و شفافیت"
>
    <div class="mx-auto max-w-5xl px-4 sm:px-6">
        @php
            $trusts = [
                [
                    'icon' => 'bi-shield-check',
                    'title' => 'شفافیت و امانتداری',
                    'desc' => 'ثبت مستند و گزارش دقیق تمامی خدمات و حمایت‌ها',
                    'color' => '#1572A1',
                    'rgb' => '21, 114, 161',
                ],
                [
                    'icon' => 'bi-heart-fill',
                    'title' => 'همراهی با مهربانی',
                    'desc' => 'مراقبت تخصصی با پاسداشت کرامت و امید کودکان',
                    'color' => '#A4184B',
                    'rgb' => '164, 24, 75',
                ],
                [
                    'icon' => 'bi-person-check-fill',
                    'title' => 'تیم متخصص و مددکاران',
                    'desc' => 'پایش مستمر و نیازسنجی توسط مددکاران دلسوز مرکز',
                    'color' => '#5964AE',
                    'rgb' => '89, 100, 174',
                ],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 divide-y divide-slate-100/90 sm:gap-5 md:grid-cols-3 md:gap-6 md:divide-y-0 md:divide-x md:divide-x-reverse lg:gap-8">
            @foreach($trusts as $index => $trust)
                <div
                    class="group flex items-center gap-3.5 pt-3.5 first:pt-0 md:pt-0 {{ $index > 0 ? 'md:pe-6 lg:pe-8' : '' }} transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] hover:-translate-y-0.5"
                    :class="visible ? 'opacity-100 translate-x-0 scale-100' : 'opacity-0 translate-x-8 scale-[0.98] pointer-events-none'"
                    style="transition-delay: {{ $index * 130 }}ms;"
                >
                    <!-- کانتینر آیکون اسکوئیرکل مینیمال بدون سایه با پالت سازمانی -->
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ring-1 ring-black/[0.04] transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:scale-105 group-hover:-rotate-3 sm:h-12 sm:w-12"
                        style="background: linear-gradient(135deg, rgba({{ $trust['rgb'] }}, 0.12) 0%, rgba({{ $trust['rgb'] }}, 0.04) 100%); color: {{ $trust['color'] }};"
                    >
                        <i class="bi {{ $trust['icon'] }} text-base sm:text-lg" aria-hidden="true"></i>
                    </span>

                    <!-- متن و عنوان رکن اعتماد -->
                    <div class="min-w-0 flex-1">
                        <h3 class="text-xs font-extrabold leading-5 text-slate-800 transition-colors duration-200 group-hover:text-slate-900 sm:text-sm">
                            {{ $trust['title'] }}
                        </h3>
                        <p class="mt-0.5 text-[11px] font-medium leading-relaxed text-slate-400 group-hover:text-slate-500 sm:text-xs">
                            {{ $trust['desc'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>