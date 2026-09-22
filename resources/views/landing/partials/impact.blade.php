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
<section id="impact" class="landing-section px-3 pb-8 pt-2 sm:px-6 sm:pb-14 sm:pt-10" aria-labelledby="impact-title">
    <div class="mx-auto max-w-5xl" data-reveal>
        <x-landing.section-title id="impact-title" heading="آمار همدلی" />
    </div>

    <div class="mx-auto mt-4 max-w-5xl">
        <div class="grid grid-cols-3 gap-2 sm:gap-4">
            @php
                $stats = [
                    ['value' => $coveredMembers, 'label' => 'تحت پوشش', 'caption' => 'کل اعضای مرکز', 'color' => '#1572A1'],
                    ['value' => $households, 'label' => 'خانوار', 'caption' => 'سرپرستان خانوار', 'color' => '#38538C'],
                    ['value' => $students, 'label' => 'محصل', 'caption' => 'در حال تحصیل', 'color' => '#36A9DF'],
                    ['value' => $birthdaysThisMonth, 'label' => 'متولدین '.$currentMonthName, 'caption' => 'زادروز این ماه', 'color' => '#A4184B'],
                    ['value' => $serviceDeliveries, 'label' => 'خدمات', 'caption' => 'تحویل‌شده', 'color' => '#5964AE'],
                    ['value' => $activeSocialWorkers, 'label' => 'مددکار فعال', 'caption' => 'فعال در مرکز', 'color' => '#D4205F'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div class="rounded-2xl bg-white px-1 py-3 text-center shadow-[0_2px_10px_rgba(56,83,140,0.06)] ring-1 ring-slate-100 sm:px-3 sm:py-5">
                    <span class="flex items-baseline justify-center gap-0.5">
                        <span
                            data-counter
                            data-target="{{ $stat['value'] }}"
                            style="color: {{ $stat['color'] }}"
                            class="block text-lg font-black leading-7 sm:text-3xl sm:leading-10"
                        >۰</span>
                        <span style="color: {{ $stat['color'] }}; opacity: .4;" class="text-sm font-black sm:text-xl" aria-hidden="true">+</span>
                    </span>
                    <span class="mt-0.5 block text-[10px] font-bold leading-4 text-slate-600 sm:mt-1.5 sm:text-sm">{{ $stat['label'] }}</span>
                    <span class="mt-0.5 flex items-center justify-center gap-1 text-[9px] leading-4 text-slate-400 sm:mt-1 sm:text-[11px] sm:leading-5">
                        <span class="h-1 w-1 shrink-0 rounded-full sm:block" style="background-color: {{ $stat['color'] }}" aria-hidden="true"></span>
                        <span class="truncate">{{ $stat['caption'] }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- جداکننده بصری بین آمار و بخش بعدی -->
    <div class="relative mx-auto mt-8 max-w-5xl select-none overflow-hidden rounded-3xl sm:mt-12" data-reveal oncontextmenu="return false">
        <div
            class="h-32 w-full bg-cover bg-center sm:h-40 lg:h-56"
            role="img"
            aria-label=""
            style="background-image: url('{{ asset('images/landing/image-1.webp') }}');"
        ></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" aria-hidden="true"></div>
    </div>
</section>
