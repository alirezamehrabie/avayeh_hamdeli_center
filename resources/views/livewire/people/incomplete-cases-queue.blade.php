@php
    $summary = $this->summary;
    $reasonLabels = $this->reasonOptions();
    $severityLabels = $this->severityOptions();
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
                    <h2 class="text-sm font-semibold text-slate-700">تفکیک بر اساس دلیل</h2>
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        پاک کردن فیلترها
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-4">
                    @foreach($reasonLabels as $reasonKey => $label)
                        @continue($reasonKey === 'all')
                        <button
                            type="button"
                            wire:click="selectReason('{{ $reasonKey }}')"
                            class="rounded-2xl border px-3 py-3 text-right transition {{ $selectedReason === $reasonKey ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white' }}"
                        >
                            <p class="text-[11px] font-semibold text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-lg font-bold text-slate-800">
                                {{ number_format($summary['reason_counts'][$reasonKey] ?? 0) }}
                            </p>
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="mb-3 text-sm font-semibold text-slate-700">تفکیک بر اساس شدت</h2>
                <div class="grid grid-cols-3 gap-3 xl:grid-cols-1">
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
                <label for="incomplete-reason-filter" class="mb-2 block text-xs font-semibold text-slate-500">فیلتر دلیل</label>
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
                <label for="incomplete-severity-filter" class="mb-2 block text-xs font-semibold text-slate-500">فیلتر شدت</label>
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
                    <p class="mt-1 text-sm text-slate-500">پرونده‌ها بر اساس جدیدترین ثبت مرتب شده‌اند.</p>
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
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $severity['classes'] }}">
                                    شدت {{ $severity['label'] }}
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
                            <div class="space-y-2">
                                @foreach($reasons as $reason)
                                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-[11px] text-amber-900">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold">{{ $reason['label'] }}</span>
                                            <code class="rounded bg-white/80 px-1.5 py-0.5 text-[10px] text-amber-800">{{ $reason['field'] }}</code>
                                        </div>
                                        <p class="mt-1 leading-5 text-amber-800">{{ $reason['basis'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
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
