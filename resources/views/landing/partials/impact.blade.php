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
<section id="impact" class="landing-section px-3 pb-8 pt-2 sm:px-6 sm:pb-14 sm:pt-10" aria-labelledby="impact-title">
    <div class="relative mx-auto max-w-5xl overflow-hidden rounded-[1.75rem] bg-white/60 px-3 py-6 shadow-[0_10px_40px_rgba(56,83,140,0.10)] ring-1 ring-white/70 backdrop-blur-2xl sm:px-8 sm:py-9">
        <span class="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full bg-[#36A9DF]/25 blur-3xl" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-20 -left-14 h-52 w-52 rounded-full bg-[#A4184B]/15 blur-3xl" aria-hidden="true"></span>
        <span class="pointer-events-none absolute left-1/2 top-1/3 h-40 w-72 -translate-x-1/2 rounded-full bg-[#5964AE]/15 blur-3xl" aria-hidden="true"></span>

        <div class="relative z-10 text-center" data-reveal>
            <h2 id="impact-title" class="inline-flex items-center gap-1.5 rounded-full bg-[#1572A1]/8 px-3 py-1 text-[11px] font-bold text-[#1572A1] ring-1 ring-inset ring-[#1572A1]/15 sm:px-4 sm:py-1.5 sm:text-sm">
                <i class="bi bi-graph-up" aria-hidden="true"></i>
                آمار همدلی
            </h2>
        </div>

        <div class="relative z-10 mt-4 grid grid-cols-3 gap-2 sm:mt-7 sm:gap-4">
            @php
                $stats = [
                    ['value' => $coveredMembers, 'label' => 'تحت پوشش', 'caption' => 'کل اعضای مرکز', 'color' => '#1572A1'],
                    ['value' => $households, 'label' => 'خانوار', 'caption' => 'سرپرستان خانوار', 'color' => '#38538C'],
                    ['value' => $students, 'label' => 'محصل', 'caption' => 'در حال تحصیل', 'color' => '#36A9DF'],
                    ['value' => $birthdaysThisMonth, 'label' => 'متولدین '.$currentMonthName, 'caption' => 'زادروز این ماه', 'color' => '#A4184B'],
                    ['value' => $serviceDeliveries, 'label' => 'خدمات', 'caption' => 'میانگین '.$toFa(number_format($avgServicesPerHousehold, 1)).'', 'color' => '#5964AE'],
                    ['value' => $activeSocialWorkers, 'label' => 'مددکار فعال', 'caption' => 'فعال در مرکز', 'color' => '#D4205F'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div class="rounded-2xl bg-white/70 px-1 py-3 text-center shadow-[0_2px_10px_rgba(56,83,140,0.06)] ring-1 ring-white/70 backdrop-blur-md transition duration-300 hover:bg-white/90 sm:px-3 sm:py-5">
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
</section>
