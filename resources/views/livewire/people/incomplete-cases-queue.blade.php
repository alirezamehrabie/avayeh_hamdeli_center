@php
    $summary = $this->summary;
    $reasonLabels = $this->reasonOptions();
    $severityLabels = $this->severityOptions();
    $requirementTypeLabels = [
        'required' => 'الزامی',
        'conditional' => 'مشروط',
        'management' => 'مدیریتی',
    ];
    $fieldPathLabels = [
        'people.first_name' => 'نام مددجو',
        'people.last_name' => 'نام خانوادگی مددجو',
        'people.national_id' => 'کد ملی مددجو',
        'people.shenasnameh_serial' => 'سریال شناسنامه',
        'people.shenasnameh_series_number' => 'شماره سری شناسنامه',
        'people.shenasnameh_series_letter' => 'حرف سری شناسنامه',
        'people.birth_day' => 'روز تولد',
        'people.birth_month' => 'ماه تولد',
        'people.birth_year' => 'سال تولد',
        'people.father_name' => 'نام پدر',
        'people.father_national_id' => 'کد ملی پدر',
        'people.mother_national_id' => 'کد ملی مادر',
        'people.phone_number' => 'شماره همراه مددجو',
        'people.gender' => 'جنسیت',
        'people.role' => 'نقش مددجو',
        'people.sadaat_status' => 'وضعیت سادات',
        'people.sadaat_relation_id' => 'نسبت سادات',
        'harm_type_person.harm_type_id' => 'آسیب‌های ثبت‌شده',
        'people.has_disability' => 'وضعیت معلولیت',
        'people.disability_type_id' => 'نوع معلولیت',
        'people.disability_description' => 'شرح معلولیت',
        'people.profile_photo' => 'عکس مددجو',
        'people.photo_id_card' => 'تصویر کارت ملی',
        'people.photo_birth_certificate' => 'تصویر شناسنامه',
        'educations.is_studying' => 'وضعیت تحصیل',
        'educations.reason_for_not_studying' => 'دلیل عدم تحصیل',
        'educations.education_degree' => 'مقطع یا درجه تحصیلی',
        'family_statuses.guardian_relation_type_id' => 'نسبت با سرپرست',
        'people.client_case_history' => 'شرح وضعیت و تاریخچه پرونده',
        'family_statuses.has_parent_disability' => 'وضعیت معلولیت والدین',
        'family_statuses.parent_disability_description' => 'شرح معلولیت والدین',
        'guardians.national_code' => 'کد ملی سرپرست',
        'guardians.first_name' => 'نام سرپرست',
        'guardians.last_name' => 'نام خانوادگی سرپرست',
        'guardians.social_worker_id' => 'مددکار مسئول',
        'guardians.insurance_status' => 'وضعیت بیمه سرپرست',
        'guardians.insurance_type_id' => 'نوع بیمه سرپرست',
        'guardians.guardian_phone_number' => 'شماره همراه سرپرست',
        'residences.residence_status_id' => 'وضعیت سکونت',
        'residences.district_id' => 'ناحیه سکونت',
        'residences.address' => 'نشانی',
        'contacts.landline_phone' => 'تلفن ثابت',
        'contacts.trusted_person_phone' => 'شماره فرد مورد اعتماد',
        'support_coverages.support_organization_id' => 'سازمان حمایتی',
        'support_coverages.description' => 'شرح پوشش حمایتی',
        'support_coverages.other_organization_name' => 'نام نهاد حمایتی آزاد',
        'support_coverages.support_card_image' => 'تصویر کارت حمایتی',
        'needs_levels.need_level_id' => 'سطح نیازمندی',
    ];
    $reasonCards = collect($reasonLabels)
        ->reject(fn ($label, $key) => $key === 'all')
        ->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'count' => $summary['reason_counts'][$key] ?? 0,
        ])
        ->sortByDesc('count')
        ->values();
@endphp

<div class="space-y-6" dir="rtl">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-[0.16em] text-slate-400">Management Queue</p>
                <h1 class="mt-1 text-xl font-bold text-slate-800">صف پرونده‌های ناقص</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    این بخش تمام فیلدهای الزامی، مشروط و مدیریتی فرم کامل ثبت‌نام مددجو را پایش می‌کند تا نقص هر پرونده
                    به‌صورت دقیق، قابل دسته‌بندی و قابل اولویت‌بندی دیده شود.
                </p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <span class="block text-xs font-semibold text-amber-700">تعداد پرونده‌های ناقص</span>
                <span class="mt-1 block text-2xl font-black">{{ number_format($summary['incomplete_count']) }}</span>
                @if($selectedReason !== 'all' || $selectedSeverity !== 'all')
                    <span class="mt-1 block text-xs text-amber-700">
                        نتیجه فیلتر فعلی: {{ number_format($summary['filtered_count']) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-700">پرتکرارترین نقص‌های فرم</h2>
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        پاک کردن فیلترها
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($reasonCards->take(12) as $reasonCard)
                        <button
                            type="button"
                            wire:click="selectReason('{{ $reasonCard['key'] }}')"
                            class="rounded-2xl border px-3 py-3 text-right transition {{ $selectedReason === $reasonCard['key'] ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white' }}"
                        >
                            <p class="text-[11px] font-semibold leading-5 text-slate-500">{{ $reasonCard['label'] }}</p>
                            <p class="mt-2 text-lg font-bold text-slate-800">
                                {{ number_format($reasonCard['count']) }}
                            </p>
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="mb-3 text-sm font-semibold text-slate-700">تفکیک بر اساس شدت</h2>
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-1">
                    @foreach($summary['severity_counts'] as $severityKey => $count)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <p class="text-[11px] font-semibold text-slate-500">{{ $severityLabels[$severityKey] }}</p>
                            <p class="mt-2 text-lg font-bold text-slate-800">{{ number_format($count) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div>
                <label for="incomplete-reason-filter" class="mb-2 block text-xs font-semibold text-slate-500">فیلتر فیلد ناقص</label>
                <select
                    id="incomplete-reason-filter"
                    wire:model.live="selectedReason"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                >
                    @foreach($reasonLabels as $reasonKey => $label)
                        <option value="{{ $reasonKey }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="incomplete-severity-filter" class="mb-2 block text-xs font-semibold text-slate-500">فیلتر شدت پرونده</label>
                <select
                    id="incomplete-severity-filter"
                    wire:model.live="selectedSeverity"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                >
                    @foreach($severityLabels as $severityKey => $label)
                        <option value="{{ $severityKey }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold text-slate-500">وضعیت فیلتر فعلی</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">
                    {{ $reasonLabels[$selectedReason] ?? 'همه موارد' }} / {{ $severityLabels[$selectedSeverity] ?? 'همه شدت‌ها' }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    {{ number_format($summary['filtered_count']) }} پرونده در لیست فعلی دیده می‌شود.
                </p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">فهرست پرونده‌های نیازمند تکمیل</h2>
                    <p class="mt-1 text-sm text-slate-500">پرونده‌ها بر اساس جدیدترین ثبت مرتب شده‌اند و هر نقص با فیلد دقیق فرم نمایش داده می‌شود.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ number_format($summary['filtered_count']) }} نتیجه
                </span>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($this->incompletePeople as $person)
                @php
                    $personName = trim((string) ($person->full_name ?: $person->first_name.' '.$person->last_name));
                    $guardianName = $person->guardian
                        ? trim((string) ($person->guardian->first_name.' '.$person->guardian->last_name))
                        : null;
                    $socialWorkerName = $person->guardian?->socialWorker
                        ? trim((string) ($person->guardian->socialWorker->first_name.' '.$person->guardian->socialWorker->last_name))
                        : null;
                    $reasons = $this->reasonsFor($person);
                    $severity = $this->severityFor($person);
                    $reasonsBySection = $reasons->groupBy('section');
                @endphp

                <article class="px-5 py-4">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-800 sm:text-base">
                                        {{ $personName !== '' ? $personName : 'مددجوی بدون نام' }}
                                    </h3>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">
                                        {{ $person->person_code ?: 'بدون کد' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $severity['classes'] }}">
                                        شدت {{ $severity['label'] }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                        {{ number_format($reasons->count()) }} نقص ثبت‌شده
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-slate-500 sm:grid-cols-2 xl:grid-cols-4">
                                    <div>
                                        <span class="font-semibold text-slate-600">کد ملی:</span>
                                        {{ $person->national_id ?: 'ثبت نشده' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold text-slate-600">سرپرست:</span>
                                        {{ $guardianName ?: 'ثبت نشده' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold text-slate-600">مددکار:</span>
                                        {{ $socialWorkerName ?: 'ثبت نشده' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold text-slate-600">تاریخ ثبت:</span>
                                        {{ $person->created_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($person->created_at)->format('Y/m/d H:i') : '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="xl:max-w-sm">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-500">جمع‌بندی نقص‌ها</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach($reasons->groupBy('severity') as $severityKey => $severityReasons)
                                            <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                                                {{ $severityLabels[$severityKey] ?? $severityKey }}: {{ number_format($severityReasons->count()) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="mb-3 text-xs font-semibold text-slate-500">فهرست فیلدهای ناقص</p>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-3">
                                <div class="space-y-2 text-xs text-slate-700">
                                    @foreach($reasonsBySection as $sectionLabel => $sectionReasons)
                                        <p class="leading-6">
                                            <span class="font-bold text-slate-800">{{ $sectionLabel }}:</span>
                                            {{ $sectionReasons
                                                ->map(fn ($reason) => $fieldPathLabels[$reason['field']] ?? $reason['label'])
                                                ->implode('، ') }}
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="$dispatch('open-dashboard-section', { section: 'beneficiary-case-file', id: {{ $person->id }} })"
                                class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                مشاهده پرونده مددجو
                            </button>

                            @can('people-edit')
                                <button
                                    type="button"
                                    wire:click="$dispatch('open-dashboard-section', { section: 'person-edit', id: {{ $person->id }} })"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                                >
                                    ویرایش اطلاعات
                                </button>
                            @endcan
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-emerald-700">در حال حاضر پرونده ناقصی در صف دیده نمی‌شود.</p>
                    <p class="mt-2 text-xs text-slate-500">با ناقص شدن فیلدهای فرم کامل ثبت‌نام، پرونده‌ها در این بخش ظاهر خواهند شد.</p>
                </div>
            @endforelse
        </div>

        @if($this->incompletePeople->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $this->incompletePeople->links() }}
            </div>
        @endif
    </section>
</div>
