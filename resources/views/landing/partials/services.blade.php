<!-- بخش خدمات -->
<section id="services" class="landing-section bg-white pb-16 sm:pb-24" aria-labelledby="services-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="text-center" data-reveal>
            <span class="inline-flex items-center gap-2 rounded-full bg-[#36A9DF]/10 px-4 py-1.5 text-xs font-bold text-[#1572A1]">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                خدمات ما
            </span>
            <h2 id="services-title" class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl lg:text-4xl">
                چه کارهایی انجام می‌دهیم؟
            </h2>
            <p class="mx-auto mt-3 max-w-xl text-base leading-7 text-slate-600">
                مجموعه‌ای از خدمات تخصصی برای پوشش نیازهای محرومیت‌زدایی کودکان.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $services = [
                    ['icon' => 'bi-egg-fried', 'title' => 'تغذیه سالم', 'desc' => 'وعده‌های غذایی مغذی و محرک رشد برای کودکان در نیاز.'],
                    ['icon' => 'bi-journal-bookmark-fill', 'title' => 'آموزش و سواد', 'desc' => 'حمایت آموزشی، لوازم تحریر و مسیر یادگیری برای آینده‌ی بهتر.'],
                    ['icon' => 'bi-handbag-fill', 'title' => 'لباس و پوشاک', 'desc' => 'تأمین لباس گرم و مناسبِ فصل برای آسایش و کرامت کودک.'],
                    ['icon' => 'bi-activity', 'title' => 'بازتوانی و مراقبت', 'desc' => 'خدمات تخصصی مددکاری، روانی و مراقبتی متناسب با هر کودک.'],
                ];
            @endphp
            @foreach($services as $service)
                <article
                    data-reveal
                    class="group flex flex-col rounded-3xl border border-slate-100 bg-[#f8fbff] p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-[#5964AE]/20 hover:shadow-lg hover:shadow-[#5964AE]/10"
                >
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-[#5964AE] shadow-sm ring-1 ring-slate-100 transition group-hover:bg-[#5964AE] group-hover:text-white">
                        <i class="{{ $service['icon'] }} text-2xl" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-5 text-base font-black text-slate-900">{{ $service['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $service['desc'] }}</p>
                </article>
            @endforeach
        </div>

        <p class="mt-12 text-center">
            <a
                href="#help"
                class="inline-flex items-center gap-2 rounded-2xl border-2 border-[#5964AE]/20 bg-[#5964AE]/5 px-8 py-4 text-base font-bold text-[#5964AE] transition hover:border-[#5964AE]/40 hover:bg-[#5964AE]/10"
            >
                حمایت از این خدمات
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </p>
    </div>
</section>