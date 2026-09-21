@php
    use App\Helpers\Morilog\Jalalian;
    use App\Models\Education;
    use App\Models\Guardian;
    use App\Models\Person;
    use App\Models\ServiceDelivery;
    use App\Models\SocialWorker;

    try {
        $currentJalaliMonth = (int) Jalalian::now()->getMonth();
    } catch (\Throwable) {
        $currentJalaliMonth = (int) jdate('n');
    }
    $currentMonthName = Person::$months[$currentJalaliMonth] ?? '';

    $coveredMembers = (int) Guardian::query()->sum('children_in_house');
    $households = Guardian::query()->count();
    $students = Education::query()->where('is_studying', true)->count();
    $birthdaysThisMonth = Person::query()->birthdayThisMonth()->count();
    $serviceDeliveries = ServiceDelivery::query()->count();
    $avgServicesPerHousehold = $households > 0 ? $serviceDeliveries / $households : 0.0;
    $activeSocialWorkers = SocialWorker::query()->count();

    $toFa = fn (string|int|float $value): string => strtr(
        (string) $value,
        ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', '.' => '٫']
    );
@endphp

<!-- بخش آمار همدلی: شمارش‌های زنده و واقعی مرکز -->
<section id="impact" class="landing-section px-3 py-8 sm:px-6 sm:py-14" aria-labelledby="impact-title">
    <div class="mx-auto max-w-5xl rounded-[1.75rem] bg-[#F4F6FB] px-3 py-7 ring-1 ring-slate-100 sm:px-8 sm:py-10">
        <div class="text-center text-slate-900" data-reveal>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#1572A1]/8 px-3 py-1 text-[11px] font-bold text-[#1572A1] ring-1 ring-inset ring-[#1572A1]/15 sm:text-xs">
                <i class="bi bi-graph-up" aria-hidden="true"></i>
                آمار همدلی
            </span>
            <h2 id="impact-title" class="mt-2 text-base font-black sm:mt-3 sm:text-2xl">
                تصویر زندۀ مرکز، در یک نگاه
            </h2>
        </div>

        <div class="mt-5 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-4">
            @php
                $stats = [
                    ['value' => $coveredMembers, 'label' => 'تحت پوشش', 'caption' => 'کل اعضای مرکز'],
                    ['value' => $households, 'label' => 'خانوار', 'caption' => 'سرپرستان خانوار'],
                    ['value' => $students, 'label' => 'محصل', 'caption' => 'در حال تحصیل'],
                    ['value' => $birthdaysThisMonth, 'label' => 'متولدین '.$currentMonthName, 'caption' => 'زادروز این ماه'],
                    ['value' => $serviceDeliveries, 'label' => 'خدمات', 'caption' => 'میانگین '.$toFa(number_format($avgServicesPerHousehold, 1)).''],
                    ['value' => $activeSocialWorkers, 'label' => 'مددکار فعال', 'caption' => 'فعال در مرکز'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div class="rounded-2xl bg-white px-1 py-3 text-center shadow-[0_1px_3px_rgba(15,23,42,0.05)] sm:px-3 sm:py-5">
                    <span
                        data-counter
                        data-target="{{ $stat['value'] }}"
                        class="block text-lg font-black leading-7 text-[#38538C] sm:text-3xl sm:leading-10"
                    >۰</span>
                    <span class="mt-0.5 block text-[10px] font-bold leading-4 text-slate-700 sm:mt-1.5 sm:text-sm">{{ $stat['label'] }}</span>
                    <span class="mt-0.5 block text-[9px] leading-4 text-slate-400 sm:mt-1 sm:text-[11px] sm:leading-5">{{ $stat['caption'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
