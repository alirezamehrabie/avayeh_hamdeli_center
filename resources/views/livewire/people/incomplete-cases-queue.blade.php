@php
    $summary = $this->summary;
    $reasonLabels = [
        'missing_guardian' => 'بدون سرپرست',
        'missing_social_worker' => 'بدون مددکار',
        'missing_national_id' => 'بدون کد ملی',
        'missing_contact_path' => 'بدون مسیر تماس',
        'missing_residence' => 'بدون سکونت',
        'missing_support_coverage' => 'بدون پوشش حمایتی',
        'missing_needs_level' => 'بدون سطح نیاز',
    ];
@endphp

<div class="space-y-6" dir="rtl">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-[0.16em] text-slate-400">Management Queue</p>
                <h1 class="mt-1 text-xl font-bold text-slate-800">صف پرونده‌های ناقص</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    این بخش پرونده‌هایی را نشان می‌دهد که برای تکمیل اطلاعات پایه نیاز به پیگیری دارند. در این مرحله فقط نمایش و پایش انجام می‌شود.
                </p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <span class="block text-xs font-semibold text-amber-700">تعداد پرونده‌های ناقص</span>
                <span class="mt-1 block text-2xl font-black">{{ number_format($summary['incomplete_count']) }}</span>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
            @foreach($reasonLabels as $reasonKey => $label)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <p class="text-[11px] font-semibold text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-lg font-bold text-slate-800">
                        {{ number_format($summary['reason_counts'][$reasonKey] ?? 0) }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-800">فهرست پرونده‌های نیازمند تکمیل</h2>
            <p class="mt-1 text-sm text-slate-500">پرونده‌ها بر اساس جدیدترین ثبت مرتب شده‌اند.</p>
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
                @endphp

                <article class="px-5 py-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-800 sm:text-base">
                                    {{ $personName !== '' ? $personName : 'مددجوی بدون نام' }}
                                </h3>
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">
                                    {{ $person->person_code ?: 'بدون کد' }}
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
                                    {{ optional($person->created_at)->format('Y-m-d H:i') ?: '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="xl:max-w-xl">
                            <p class="mb-2 text-xs font-semibold text-slate-500">موارد ناقص</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($reasons as $reason)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-semibold text-amber-800">
                                        {{ $reason }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-emerald-700">در حال حاضر پرونده ناقصی در صف دیده نمی‌شود.</p>
                    <p class="mt-2 text-xs text-slate-500">با تکمیل‌نشدن اطلاعات پایه، پرونده‌ها در این بخش ظاهر خواهند شد.</p>
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
