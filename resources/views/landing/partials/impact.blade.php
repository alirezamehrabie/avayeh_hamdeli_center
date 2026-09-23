@php
    use App\Helpers\Morilog\Jalalian;
    use App\Models\Education;
    use App\Models\Guardian;
    use App\Models\Person;
    use App\Models\ServiceDelivery;
    use App\Models\SocialWorker;
    use Illuminate\Support\Facades\Cache;

    // کوئری‌های تجمیعی فقط هر ۲ ساعت یک‌بار اجرا می‌شوند؛ بقیه درخواست‌ها
    // مستقیم از کش (بدون لمس دیتابیس) رندر می‌شوند تا لندینگ سبک بماند.
    $statsData = Cache::remember('landing.hamdeli-stats', now()->addHours(2), function () {
        try {
            $currentJalaliMonth = (int) Jalalian::now()->getMonth();
        } catch (\Throwable) {
            $currentJalaliMonth = (int) jdate('n');
        }

        $households = Guardian::query()->count();
        $serviceDeliveries = ServiceDelivery::query()->count();

        return [
            'coveredMembers' => (int) Guardian::query()->sum('children_in_house'),
            'households' => $households,
            'students' => Education::query()->where('is_studying', true)->count(),
            'birthdaysThisMonth' => Person::query()->birthdayThisMonth()->count(),
            'monthName' => Person::$months[$currentJalaliMonth] ?? '',
            'serviceDeliveries' => $serviceDeliveries,
            'avgServicesPerHousehold' => $households > 0 ? round($serviceDeliveries / $households, 1) : 0.0,
            'activeSocialWorkers' => SocialWorker::query()->count(),
        ];
    });

    $coveredMembers = $statsData['coveredMembers'];
    $households = $statsData['households'];
    $students = $statsData['students'];
    $birthdaysThisMonth = $statsData['birthdaysThisMonth'];
    $currentMonthName = $statsData['monthName'];
    $serviceDeliveries = $statsData['serviceDeliveries'];
    $avgServicesPerHousehold = $statsData['avgServicesPerHousehold'];
    $activeSocialWorkers = $statsData['activeSocialWorkers'];

    $toFa = fn (string|int|float $value): string => strtr(
        (string) $value,
        ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', '.' => '٫']
    );
@endphp

<!-- بخش آمار همدلی: شمارش‌های زنده و واقعی مرکز -->
<section id="impact" class="landing-section px-3 pb-4 pt-2 sm:px-6 sm:pb-14 sm:pt-10" aria-labelledby="impact-title">
    <div class="mx-auto max-w-5xl" data-reveal>
        <x-landing.section-title id="impact-title" heading="آمار همدلی" />

        <!-- نشانگر زنده شفافیت و آمار به‌روز مرکز -->
        <div class="mt-3 flex items-center justify-center sm:mt-3">
            <div class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-slate-50/80 px-2.5 py-0.5 text-[10px] font-medium text-slate-600 shadow-[inset_0_1px_2px_rgba(0,0,0,0.02)] backdrop-blur-sm sm:px-3 sm:py-1 sm:text-[11px]">
                <span class="relative flex h-1.5 w-1.5 shrink-0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                </span>
                <span>آمار شفاف و به‌روزرسانی لحظه‌ای سامانه</span>
            </div>
        </div>
    </div>

    <div class="mx-auto mt-3.5 max-w-5xl sm:mt-4" data-reveal>
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3.5 lg:gap-4.5">
            @php
                $stats = [
                    ['value' => $coveredMembers, 'label' => 'تحت پوشش', 'caption' => 'کل اعضای مرکز', 'color' => '#1572A1', 'rgb' => '21, 114, 161', 'icon' => 'bi-people-fill'],
                    ['value' => $households, 'label' => 'خانوار', 'caption' => 'سرپرستان خانوار', 'color' => '#38538C', 'rgb' => '56, 83, 140', 'icon' => 'bi-house-heart-fill'],
                    ['value' => $students, 'label' => 'محصل', 'caption' => 'در حال تحصیل', 'color' => '#0284c7', 'rgb' => '2, 132, 199', 'icon' => 'bi-mortarboard-fill'],
                    ['value' => $birthdaysThisMonth, 'label' => 'متولدین '.$currentMonthName, 'caption' => 'زادروز این ماه', 'color' => '#be185d', 'rgb' => '190, 24, 93', 'icon' => 'bi-gift-fill'],
                    ['value' => $serviceDeliveries, 'label' => 'خدمات', 'caption' => 'تحویل‌شده', 'color' => '#4f46e5', 'rgb' => '79, 70, 229', 'icon' => 'bi-box2-heart-fill'],
                    ['value' => $activeSocialWorkers, 'label' => 'مددکار فعال', 'caption' => 'فعال در مرکز', 'color' => '#0d9488', 'rgb' => '13, 148, 136', 'icon' => 'bi-person-heart'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div
                    class="group relative flex min-w-0 select-none flex-col items-center justify-between overflow-hidden rounded-2xl border border-slate-100/90 bg-white/95 px-3 py-3.5 text-center shadow-[0_1px_3px_rgba(15,23,42,0.03),0_4px_12px_rgba(56,83,140,0.03)] ring-1 ring-inset ring-white/80 backdrop-blur-sm transition-all duration-300 ease-out hover:border-slate-200 hover:shadow-[0_8px_20px_-6px_rgba(var(--stat-rgb),0.12)] sm:rounded-3xl sm:px-4 sm:py-5"
                    style="--stat-rgb: {{ $stat['rgb'] }};"
                >
                    <!-- هاله نوری محاطی (Ambient Radial Glow) بسیار ظریف هنگام هاور -->
                    <div
                        class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-500 ease-out group-hover:opacity-100"
                        style="background: radial-gradient(85% 70% at 50% 0%, rgba({{ $stat['rgb'] }}, 0.12) 0%, rgba({{ $stat['rgb'] }}, 0.02) 55%, transparent 100%);"
                        aria-hidden="true"
                    ></div>

                    <!-- پرتو نوری فوق‌العاده مدرن و مویین در لبه بالا (Hairline Top Specular Light) -->
                    <div
                        class="pointer-events-none absolute inset-x-3 top-0 h-[1.5px] rounded-full opacity-60 transition-all duration-300 group-hover:inset-x-1 group-hover:h-[2px] group-hover:opacity-100 sm:inset-x-4"
                        style="background: linear-gradient(90deg, transparent 0%, {{ $stat['color'] }} 50%, transparent 100%);"
                        aria-hidden="true"
                    ></div>

                    <!-- کانتینر آیکون اسکوئیرکل شناور و نرم بدون سایه با mb استاندارد -->
                    <div class="relative z-10 mb-2 sm:mb-2.5">
                        <span
                            class="relative flex h-8 w-8 items-center justify-center rounded-xl ring-1 ring-black/[0.04] transition-transform duration-300 ease-out group-hover:scale-105 sm:h-10 sm:w-10 sm:rounded-2xl"
                            style="background: linear-gradient(135deg, rgba({{ $stat['rgb'] }}, 0.14) 0%, rgba({{ $stat['rgb'] }}, 0.05) 100%); color: {{ $stat['color'] }};"
                        >
                            <i class="bi {{ $stat['icon'] }} text-xs transition-transform duration-300 sm:text-sm" aria-hidden="true"></i>
                        </span>
                    </div>

                    <!-- عدد شمارنده زنده مجسمه‌گون با ارقام جمع‌تر -->
                    <div class="relative z-10 flex items-baseline justify-center gap-0.5" dir="ltr" aria-label="{{ $toFa(number_format($stat['value'])) }} {{ $stat['label'] }}">
                        <span
                            data-counter
                            data-target="{{ $stat['value'] }}"
                            class="block text-lg font-bold leading-none tracking-[-0.05em] text-slate-800 tabular-nums min-[380px]:text-xl sm:text-2xl sm:tracking-[-0.06em] lg:text-3xl"
                        >{{ $toFa(number_format($stat['value'])) }}</span>
                        <span
                            class="text-[10px] font-bold sm:text-xs lg:text-sm"
                            style="color: {{ $stat['color'] }}"
                            aria-hidden="true"
                        >+</span>
                    </div>

                    <!-- عنوان اصلی و زیرعنوان ساختاریافته -->
                    <div class="relative z-10 mt-1.5 w-full min-w-0 px-0.5 sm:mt-2">
                        <span
                            class="block text-xs font-extrabold leading-5 text-slate-800 transition-colors duration-200 group-hover:text-slate-900 sm:text-xs lg:text-sm"
                            title="{{ $stat['label'] }}"
                        >
                            {{ $stat['label'] }}
                        </span>
                        <span
                            class="mt-0.5 block text-[11px] font-medium leading-4 text-slate-400 group-hover:text-slate-500 sm:text-xs"
                            title="{{ $stat['caption'] }}"
                        >
                            {{ $stat['caption'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>


    <!-- بنر سینمایی اثرگذاری و شعار محوری مرکز -->
    <figure class="relative mx-auto mt-8 max-w-5xl overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-900 shadow-xl shadow-slate-900/10 sm:mt-12 sm:rounded-3xl" data-reveal>
        <div class="relative aspect-[16/8] w-full overflow-hidden sm:aspect-[2.4/1]">
            <img
                src="{{ asset('images/landing/image-1.webp') }}"
                alt="یاری کودکان نیازمند در مرکز آوای همدلی"
                loading="lazy"
                decoding="async"
                width="1280"
                height="533"
                class="h-full w-full object-cover object-[center_35%] transition-transform duration-700 ease-out hover:scale-[1.02]"
            >

            <!-- لایه سایه‌روشن سینمایی برای خوانایی اعلای متن -->
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/45 to-slate-900/25" aria-hidden="true"></div>

            <!-- هاله نور دکوراتیو آبی برند -->
            <div class="pointer-events-none absolute -bottom-16 left-1/2 h-36 w-80 -translate-x-1/2 rounded-full bg-[#1572A1]/35 blur-3xl" aria-hidden="true"></div>

            <!-- محتوای شعار روی تصویر -->
            <figcaption class="absolute inset-0 flex flex-col items-center justify-end p-4 text-center sm:p-7 md:p-9">
                <!-- بج محتوایی بالای شعار -->
                <div class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-[10px] font-semibold text-white/95 backdrop-blur-md ring-1 ring-inset ring-white/20 sm:mb-3 sm:px-3 sm:py-1 sm:text-xs">
                    <svg viewBox="0 0 24 24" class="h-3 w-3 text-cyan-300 sm:h-3.5 sm:w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    </svg>
                    <span>آوای همدلی</span>
                </div>

                <!-- متن شعار انگیزشی با خوانایی بی‌نقص -->
                <blockquote class="max-w-2xl px-2">
                    <p class="text-sm font-black leading-relaxed text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.5)] sm:text-lg sm:leading-8 md:text-xl lg:text-2xl">
                        همراهی مهربان برای یاری کودکان نیازمند
                    </p>
                </blockquote>
            </figcaption>
        </div>
    </figure>
</section>

