<div class="space-y-6" dir="rtl">
    @php
        $statusBadgeClasses = [
            'pending' => 'bg-slate-100 text-slate-700',
            'delivered' => 'bg-amber-100 text-amber-700',
            'finalized' => 'bg-emerald-100 text-emerald-700',
        ];
        $statusLabels = [
            'pending' => 'در انتظار تحویل',
            'delivered' => 'تحویل شده',
            'finalized' => 'خروج نهایی',
        ];
        $tabs = [
            'entry' => ['label' => 'گیت ورود', 'color' => 'sky'],
            'delivery' => ['label' => 'گیت تحویل', 'color' => 'amber'],
            'exit' => ['label' => 'گیت خروج', 'color' => 'emerald'],
        ];
    @endphp

    @if(! $selectedService)
        {{-- Service picker --}}
        <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-l from-violet-600 via-indigo-600 to-sky-600 px-4 py-4 text-white sm:px-6 sm:py-5">
                <h1 class="text-xl font-extrabold sm:text-2xl">گزارش گیت‌های توزیع</h1>
                <p class="mt-1.5 max-w-3xl text-xs text-indigo-50/90 sm:text-sm">
                    ابتدا خدمت مورد نظر را انتخاب کنید تا گزارش ورود، تحویل و خروج آن نمایش داده شود.
                </p>
            </div>

            <div class="border-b border-slate-200 px-4 py-3 sm:px-6">
                <div class="relative w-full sm:max-w-md">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="serviceSearch"
                        placeholder="جستجوی خدمت"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                @forelse($gateServices as $service)
                    <button
                        type="button"
                        wire:click="selectService({{ $service->id }})"
                        class="group relative text-right overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    >
                        <p class="text-xs font-semibold text-indigo-600">{{ $service->code }}</p>
                        <h2 class="mt-1 text-sm font-bold text-slate-900">{{ $service->serviceName?->name ?: $service->name }}</h2>
                        <p class="mt-2 text-xs text-slate-500">{{ $service->categories_count }} دسته‌بندی</p>
                    </button>
                @empty
                    <div class="col-span-full py-12 text-center text-sm text-slate-500">
                        خدمتی با پشتیبانی گیت توزیع یافت نشد.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        {{-- Header --}}
        <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-l from-violet-600 via-indigo-600 to-sky-600 px-4 py-4 text-white sm:px-6 sm:py-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <button type="button" wire:click="backToServices" class="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-indigo-100 hover:text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            بازگشت به فهرست خدمات
                        </button>
                        <h1 class="text-xl font-extrabold sm:text-2xl">{{ $selectedService->serviceName?->name ?: $selectedService->name }}</h1>
                        <p class="mt-1 text-xs text-indigo-50/90 sm:text-sm">{{ $selectedService->code }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="exportToExcel"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2 text-xs font-bold text-white ring-1 ring-white/25 backdrop-blur transition hover:bg-white/25"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                        </svg>
                        خروجی اکسل
                    </button>
                </div>
            </div>

            @if(session('error'))
                <div class="border-b border-rose-100 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700 sm:px-6">
                    {{ session('error') }}
                </div>
            @endif

            {{-- KPI stat cards --}}
            <div class="grid grid-cols-2 gap-3 border-b border-slate-200 p-4 sm:grid-cols-4 sm:p-6">
                <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-3">
                    <p class="text-[11px] font-semibold text-sky-600">کل ورودی‌ها</p>
                    <p class="mt-1 text-xl font-black text-sky-900">{{ number_format($stats['entered']) }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-3">
                    <p class="text-[11px] font-semibold text-amber-600">تحویل شده (منتظر خروج)</p>
                    <p class="mt-1 text-xl font-black text-amber-900">{{ number_format($stats['delivered']) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                    <p class="text-[11px] font-semibold text-emerald-600">خروج نهایی</p>
                    <p class="mt-1 text-xl font-black text-emerald-900">{{ number_format($stats['exited']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-600">ارزش کل توزیع‌شده</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($stats['total_value']) }} <span class="text-xs font-bold">ریال</span></p>
                </div>
            </div>

            {{-- Tab switcher --}}
            <div class="flex flex-wrap gap-2 border-b border-slate-200 p-4 sm:px-6">
                @foreach($tabs as $key => $tab)
                    @php
                        $isActive = $activeTab === $key;
                        $count = match($key) {
                            'entry' => $stats['entered'],
                            'delivery' => $stats['delivered'],
                            'exit' => $stats['exited'],
                        };
                        $activeClasses = [
                            'sky' => 'bg-sky-600 text-white shadow-sm',
                            'amber' => 'bg-amber-600 text-white shadow-sm',
                            'emerald' => 'bg-emerald-600 text-white shadow-sm',
                        ][$tab['color']];
                    @endphp
                    <button
                        type="button"
                        wire:click="setActiveTab('{{ $key }}')"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition {{ $isActive ? $activeClasses : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    >
                        {{ $tab['label'] }}
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $isActive ? 'bg-white/25' : 'bg-white text-slate-500' }}">{{ number_format($count) }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Filters --}}
            <div class="border-b border-slate-200 p-4 sm:px-6">
                <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_9rem_9rem_9rem_9rem_auto] sm:items-center">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="جستجو نام، کد ملی یا موبایل"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                    >

                    <select wire:model.live="selectedCategory" class="h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                        <option value="all">همه دسته‌ها</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    @if($activeTab === 'entry')
                        <select wire:model.live="selectedStatus" class="h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <option value="all">همه وضعیت‌ها</option>
                            <option value="pending">در انتظار تحویل</option>
                            <option value="delivered">تحویل شده</option>
                            <option value="finalized">خروج نهایی</option>
                        </select>
                    @else
                        <div></div>
                    @endif

                    <div x-data="jalaliDateTimeField($wire.entangle('dateFrom').live)">
                        <input
                            type="text"
                            x-ref="input"
                            x-model="draft"
                            x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:jalali-picker-open="handlePickerOpen()"
                            x-on:jalali-picker-close="handlePickerClose()"
                            x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            readonly
                            inputmode="none"
                            autocomplete="off"
                            data-jdp-readonly
                            data-jdp
                            data-jdp-only-date
                            placeholder="از تاریخ"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                    </div>

                    <div x-data="jalaliDateTimeField($wire.entangle('dateTo').live)">
                        <input
                            type="text"
                            x-ref="input"
                            x-model="draft"
                            x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:jalali-picker-open="handlePickerOpen()"
                            x-on:jalali-picker-close="handlePickerClose()"
                            x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            readonly
                            inputmode="none"
                            autocomplete="off"
                            data-jdp-readonly
                            data-jdp
                            data-jdp-only-date
                            placeholder="تا تاریخ"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                    </div>

                    @if($search !== '' || $selectedCategory !== 'all' || $selectedStatus !== 'all' || $dateFrom !== '' || $dateTo !== '')
                        <button type="button" wire:click="clearFilters" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50">
                            پاک کردن
                        </button>
                    @endif
                </div>
            </div>

            {{-- Results --}}
            @if($activeTab === 'entry')
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="px-4 py-4 text-right font-bold">گیرنده</th>
                            <th class="px-4 py-4 text-center font-bold">نوع</th>
                            <th class="px-4 py-4 text-right font-bold">دسته‌بندی/قلم</th>
                            <th class="px-4 py-4 text-center font-bold">وضعیت</th>
                            <th class="px-4 py-4 text-right font-bold">اپراتور ورود</th>
                            <th class="px-4 py-4 text-right font-bold">زمان ورود</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($results as $assignment)
                            @php
                                $isGuardian = (bool) $assignment->guardian_id;
                                $code = $isGuardian ? $assignment->guardian?->guardian_code : $assignment->person?->person_code;
                                $extraFields = $isGuardian
                                    ? ($entryFieldsByGuardian[$assignment->guardian_id] ?? [])
                                    : ($entryFieldsByPerson[$assignment->person_id] ?? []);
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-slate-700">
                                    <p class="font-bold text-slate-900">{{ $assignment->recipient_name }}</p>
                                    <div class="mt-1 space-y-0.5 text-xs text-slate-500">
                                        @if($code)<p>کد: {{ $code }}</p>@endif
                                        @if($assignment->national_id)<p>کد ملی: {{ $assignment->national_id }}</p>@endif
                                        @if($assignment->mobile)<p>موبایل: {{ $assignment->mobile }}</p>@endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $isGuardian ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ $isGuardian ? 'خانوادگی' : 'شخصی' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-slate-700">{{ $assignment->serviceCategory?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusBadgeClasses[$assignment->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $statusLabels[$assignment->status] ?? $assignment->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-slate-600 text-xs">{{ $assignment->creator?->full_name ?: $assignment->creator?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-right text-slate-500 text-xs">
                                    {{ $assignment->assigned_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($assignment->assigned_at)->format('Y/m/d H:i') : '-' }}
                                </td>
                            </tr>
                            @if(! empty($extraFields))
                                <tr class="bg-slate-50/70">
                                    <td colspan="6" class="px-4 pb-3 pt-1 text-xs text-slate-600">
                                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                                            @foreach($extraFields as $field)
                                                <span><span class="font-bold text-slate-700">{{ $field['label'] }}:</span> {{ $field['value'] }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">هنوز رکوردی برای گیت ورود این خدمت ثبت نشده است.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="divide-y divide-slate-100 sm:hidden">
                    @forelse($results as $assignment)
                        @php $isGuardian = (bool) $assignment->guardian_id; @endphp
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <p class="font-bold text-slate-900">{{ $assignment->recipient_name }}</p>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $statusBadgeClasses[$assignment->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $statusLabels[$assignment->status] ?? $assignment->status }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $assignment->serviceCategory?->name ?: '-' }} &middot; {{ $isGuardian ? 'خانوادگی' : 'شخصی' }}</p>
                            <p class="mt-2 text-[11px] text-slate-500">اپراتور: {{ $assignment->creator?->full_name ?: $assignment->creator?->name ?: '-' }}</p>
                            <p class="text-[11px] text-slate-500">{{ $assignment->assigned_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($assignment->assigned_at)->format('Y/m/d H:i') : '-' }}</p>
                        </div>
                    @empty
                        <div class="py-12 text-center text-sm text-slate-500">هنوز رکوردی برای گیت ورود این خدمت ثبت نشده است.</div>
                    @endforelse
                </div>
            @endif

            @if($activeTab === 'delivery')
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="px-4 py-4 text-right font-bold">گیرنده</th>
                            <th class="px-4 py-4 text-right font-bold">دسته‌بندی/قلم</th>
                            <th class="px-4 py-4 text-center font-bold">وضعیت فعلی</th>
                            <th class="px-4 py-4 text-right font-bold">اپراتور تحویل</th>
                            <th class="px-4 py-4 text-right font-bold">زمان تحویل</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($results as $assignment)
                            @php $isGuardian = (bool) $assignment->guardian_id; @endphp
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-slate-700">
                                    <p class="font-bold text-slate-900">{{ $assignment->recipient_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $isGuardian ? 'خانوادگی' : 'شخصی' }} @if($assignment->national_id) &middot; کد ملی: {{ $assignment->national_id }} @endif</p>
                                </td>
                                <td class="px-4 py-4 text-right text-slate-700">{{ $assignment->serviceCategory?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusBadgeClasses[$assignment->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $statusLabels[$assignment->status] ?? $assignment->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-slate-600 text-xs">{{ $assignment->deliveredBy?->full_name ?: $assignment->deliveredBy?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-right text-slate-500 text-xs">
                                    {{ $assignment->delivered_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($assignment->delivered_at)->format('Y/m/d H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-slate-500">هنوز رکوردی برای گیت تحویل این خدمت ثبت نشده است.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 sm:hidden">
                    @forelse($results as $assignment)
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <p class="font-bold text-slate-900">{{ $assignment->recipient_name }}</p>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $statusBadgeClasses[$assignment->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $statusLabels[$assignment->status] ?? $assignment->status }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $assignment->serviceCategory?->name ?: '-' }}</p>
                            <p class="mt-2 text-[11px] text-slate-500">اپراتور: {{ $assignment->deliveredBy?->full_name ?: $assignment->deliveredBy?->name ?: '-' }}</p>
                            <p class="text-[11px] text-slate-500">{{ $assignment->delivered_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($assignment->delivered_at)->format('Y/m/d H:i') : '-' }}</p>
                        </div>
                    @empty
                        <div class="py-12 text-center text-sm text-slate-500">هنوز رکوردی برای گیت تحویل این خدمت ثبت نشده است.</div>
                    @endforelse
                </div>
            @endif

            @if($activeTab === 'exit')
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="px-4 py-4 text-right font-bold">گیرنده</th>
                            <th class="px-4 py-4 text-right font-bold">دسته‌بندی/قلم</th>
                            <th class="px-4 py-4 text-center font-bold">مقدار</th>
                            <th class="px-4 py-4 text-center font-bold">ارزش تحویل</th>
                            <th class="px-4 py-4 text-right font-bold">اپراتور خروج</th>
                            <th class="px-4 py-4 text-right font-bold">زمان خروج</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($results as $delivery)
                            @php $isGuardian = (bool) $delivery->guardian_id; @endphp
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-slate-700">
                                    <p class="font-bold text-slate-900">{{ $delivery->recipient_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $isGuardian ? 'خانوادگی' : 'شخصی' }} @if($delivery->national_id) &middot; کد ملی: {{ $delivery->national_id }} @endif</p>
                                </td>
                                <td class="px-4 py-4 text-right text-slate-700">{{ $delivery->serviceCategory?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-center font-bold text-slate-800">{{ number_format((float) $delivery->delivered_quantity, 2) }}</td>
                                <td class="px-4 py-4 text-center font-bold text-emerald-600">{{ number_format($delivery->delivered_total_value) }} ریال</td>
                                <td class="px-4 py-4 text-right text-slate-600 text-xs">{{ $delivery->creator?->full_name ?: $delivery->creator?->name ?: '-' }}</td>
                                <td class="px-4 py-4 text-right text-slate-500 text-xs">
                                    {{ $delivery->delivered_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">هنوز رکوردی برای گیت خروج این خدمت ثبت نشده است.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 sm:hidden">
                    @forelse($results as $delivery)
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <p class="font-bold text-slate-900">{{ $delivery->recipient_name }}</p>
                                <span class="text-xs font-bold text-emerald-600">{{ number_format($delivery->delivered_total_value) }} ریال</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $delivery->serviceCategory?->name ?: '-' }} &middot; {{ number_format((float) $delivery->delivered_quantity, 2) }}</p>
                            <p class="mt-2 text-[11px] text-slate-500">اپراتور: {{ $delivery->creator?->full_name ?: $delivery->creator?->name ?: '-' }}</p>
                            <p class="text-[11px] text-slate-500">{{ $delivery->delivered_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d') : '-' }}</p>
                        </div>
                    @empty
                        <div class="py-12 text-center text-sm text-slate-500">هنوز رکوردی برای گیت خروج این خدمت ثبت نشده است.</div>
                    @endforelse
                </div>
            @endif

            @if($results && $results->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $results->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
