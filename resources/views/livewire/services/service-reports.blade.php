<div
    x-data="{
        categoriesOpen: false,
        categories: [],
        categoryTitle: '',
        workersOpen: false,
        workersSummary: null,
        openWorkers(payload) {
            this.workersSummary = payload;
            this.workersOpen = true;
        },
    }"
    class="space-y-6"
>
    @php
        $statusBadgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'approved' => 'bg-yellow-100 text-yellow-700',
            'in_distribution' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-green-100 text-green-700',
        ];

        $hasServiceFilters = trim($search ?? '') !== ''
            || $selectedServiceName !== 'all'
            || $selectedCategory !== 'all'
            || $selectedStatus !== 'all'
            || $selectedType !== 'all'
            || ($selectedSocialWorker ?? 'all') !== 'all'
            || trim($serviceDateFrom ?? '') !== ''
            || trim($serviceDateTo ?? '') !== '';
    @endphp

    @if(! $selectedService && ! $deliveryChannel)
        {{-- Landing: pick a delivery method before entering the services report --}}
        <div id="service-report-channel-picker" class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
            {{-- Header --}}
            <div class="bg-gradient-to-l from-violet-600 via-indigo-600 to-sky-600 px-4 py-5 text-white sm:px-6 sm:py-6">
                <h1 class="text-xl font-extrabold sm:text-2xl">گزارش خدمات</h1>
                <p class="mt-1.5 max-w-3xl text-xs text-indigo-50/90 sm:text-sm">
                    برای شروع، روش تحویل مورد نظر را انتخاب کنید. سپس فقط خدمات همان روش در گزارش نمایش داده می‌شوند.
                </p>
            </div>

            {{-- Method cards --}}
            <div class="grid grid-cols-1 gap-4 p-4 sm:p-6 md:grid-cols-3">
                @foreach($deliveryChannelCards as $card)
                    <button
                        type="button"
                        wire:key="channel-card-{{ $card['channel'] }}"
                        wire:click="selectDeliveryChannel('{{ $card['channel'] }}')"
                        class="group relative flex flex-col items-stretch overflow-hidden rounded-2xl border bg-white p-5 pr-6 text-right shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 {{ $card['classes'] }}"
                    >
                        <span class="absolute inset-y-0 right-0 w-1 {{ $card['accent'] }}"></span>
                        <span class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $card['iconClasses'] }}">
                            @if($card['icon'] === 'home')
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12 11.2 3.05a1.13 1.13 0 0 1 1.6 0L21.75 12M4.5 9.75v10.13c0 .62.5 1.12 1.13 1.12H9.75v-4.88c0-.62.5-1.12 1.13-1.12h2.24c.63 0 1.13.5 1.13 1.12v4.88h4.13c.62 0 1.12-.5 1.12-1.13V9.75"/></svg>
                            @elseif($card['icon'] === 'station')
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/></svg>
                            @else
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            @endif
                        </span>

                        <span class="text-base font-extrabold text-slate-900">{{ $card['label'] }}</span>
                        <span class="mt-1.5 block text-xs leading-6 text-slate-500">{{ $card['description'] }}</span>

                        <span class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-500">
                                <svg class="h-4 w-4 {{ $card['textClasses'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                {{ $card['count'] }} خدمت
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs font-black transition-transform group-hover:translate-x-1 {{ $card['textClasses'] }}">
                                مشاهده گزارش
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5 15.75 12l-7.5 7.5"/></svg>
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @elseif(! $selectedService)
        <div id="service-report-list" class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
            {{-- Header --}}
            <div class="bg-gradient-to-l from-violet-600 via-indigo-600 to-sky-600 px-4 py-4 text-white sm:px-6 sm:py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-extrabold sm:text-2xl">گزارش خدمات</h1>
                            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-2.5 py-0.5 text-xs font-semibold backdrop-blur">
                    {{ $services->total() }} خدمت
                </span>
                            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-indigo-700 shadow-sm">
                    {{ $deliveryChannelLabel }}
                </span>
                        </div>
                        <p class="mt-1.5 max-w-3xl text-xs text-indigo-50/90 sm:text-sm">
                            فهرست خدمات را سریع مرور کنید، جستجو بزنید و مستقیم وارد تحویل‌های هر خدمت شوید.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="backToChannelSelection"
                        class="inline-flex w-fit shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        تغییر روش تحویل
                    </button>
                </div>
            </div>

            {{-- Search Bar + Quick Filters --}}
            <div class="border-b border-slate-200 px-4 py-3 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    {{-- Search Input --}}
                    <div class="relative w-full sm:max-w-md">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="جستجوی سراسری"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                        >
                    </div>

                    {{-- Quick Filters --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Service Name Filter --}}
                        <select wire:model.live="selectedServiceName" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <option value="all">همه خدمات</option>
                            @foreach($serviceNames as $sname)
                                <option value="{{ $sname }}">{{ $sname }}</option>
                            @endforeach
                        </select>


                        {{-- Category Filter --}}
                        <select wire:model.live="selectedCategory" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <option value="all">همه دسته‌ها</option>
                            @foreach($categoryOptions as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>

                        {{-- Status Filter --}}
                        <select wire:model.live="selectedStatus" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <option value="all">همه وضعیت‌ها</option>
                            <option value="completed">تکمیل شده</option>
                            <option value="in_distribution">در حال توزیع</option>
                            <option value="approved">تأیید شده</option>
                            <option value="draft">پیش نویس</option>
                        </select>

                        {{-- Type Filter --}}
                        <select wire:model.live="selectedType" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <option value="all">همه انواع</option>
                            @foreach($typeDisplayOptions as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>

                        {{-- Social Worker Filter --}}
                        <select wire:model.live="selectedSocialWorker" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            <option value="all">همه مددکاران</option>
                            @foreach($socialWorkerOptions ?? [] as $workerOption)
                                <option value="{{ $workerOption['id'] }}">{{ $workerOption['name'] }}</option>
                            @endforeach
                        </select>

                        {{-- Creation Date Range Filter (تاریخ ثبت) --}}
                        <div x-data="jalaliDateTimeField($wire.entangle('serviceDateFrom').live)" class="w-full sm:w-36">
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
                                placeholder="از تاریخ ثبت"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            >
                        </div>

                        <div x-data="jalaliDateTimeField($wire.entangle('serviceDateTo').live)" class="w-full sm:w-36">
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
                                placeholder="تا تاریخ ثبت"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            >
                        </div>

                        @if($hasServiceFilters)
                            <button
                                type="button"
                                wire:click="clearServiceFilters"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-500 outline-none transition hover:bg-slate-50 focus:ring-2 focus:ring-indigo-100"
                            >
                                پاک کردن فیلترها
                            </button>
                        @endif

                    </div>
                </div>
            </div>

        {{-- Automatic layout: table on wide containers, cards on narrow ones (see .report-cq in app.css) --}}
        <div class="report-cq">
            {{-- List View (wide containers) --}}
            <div class="rpt-lg-table px-4 pb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="px-3 py-3 text-right font-semibold">خدمت</th>
                            <th class="px-3 py-3 text-right font-semibold">دسته‌بندی</th>
                            <th class="px-3 py-3 text-right font-semibold">نوع</th>
                            <th class="px-3 py-3 text-right font-semibold">توضیحات</th>
                            <th class="px-3 py-3 text-center font-semibold">تحویل خدمات</th>
                            <th class="px-3 py-3 text-right font-semibold">اپراتور</th>
                            <th class="px-3 py-3 text-center font-semibold">وضعیت</th>
                            <th class="px-3 py-3 text-right font-semibold">تاریخ</th>
                            <th class="px-3 py-3 text-center font-semibold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($services as $service)
                        @php
                            $creatorName = trim(implode(' ', array_filter([
                                $service->creator?->first_name,
                                $service->creator?->last_name,
                            ]))) ?: ($service->creator?->name ?: '-');
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-3 py-3 text-right font-semibold text-slate-900">{{ $service->serviceName?->name ?: '-' }}</td>
                            <td class="px-3 py-3 text-right">
                                @if($service->categories->count() > 0)
                                    @include('livewire.services.partials.service-categories-trigger', [
                                        'service' => $service,
                                        'unitOptions' => $unitOptions,
                                    ])
                                @else
                                    <span class="text-slate-400 text-xs">بدون دسته‌بندی</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <x-service-type-badge :type="$service->service_type" />
                            </td>
                            <td class="px-3 py-3 text-center">
                                <x-description-popover :text="$service->description" />
                            </td>
                            <td class="px-3 py-3 text-center">
                                @include('livewire.services.partials.delivery-summary-trigger', [
                                    'service' => $service,
                                    'unitOptions' => $unitOptions,
                                    'label' => 'وضعیت',
                                    'minimal' => true,
                                    'compact' => true,
                                ])
                            </td>
                            <td class="px-3 py-3 text-right text-slate-600 text-xs">{{ $creatorName }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusBadgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $statusOptions[$service->status] ?? $service->status }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right text-slate-500 text-xs">{{ $jalaliDateTime($service->created_at) }}</td>
                            <td class="px-3 py-3 text-center">
                                <div class="inline-flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="openService({{ $service->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-indigo-700"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        جزئیات
                                    </button>
                                    @if($deliveryChannel === \App\Models\Service::DELIVERY_CHANNEL_GATE)
                                        <a
                                            href="{{ route('admin.gate-technical-report', ['service' => $service->id]) }}"
                                            class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            گزارش فنی
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                {{ $hasServiceFilters ? 'موردی برای فیلترهای فعلی پیدا نشد.' : 'هنوز خدمتی تعریف نشده است.' }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Card View (narrow containers) --}}
            <div class="rpt-lg-cards grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                @forelse($services as $service)
                    @php
                        $creatorName = trim(implode(' ', array_filter([
                            $service->creator?->first_name,
                            $service->creator?->last_name,
                        ]))) ?: ($service->creator?->name ?: '-');
                    @endphp
                    <div class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-4 text-right shadow-sm transition-all duration-200 hover:border-indigo-200 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusBadgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $statusOptions[$service->status] ?? $service->status }}
                            </span>
                            <x-service-type-badge :type="$service->service_type" />
                        </div>

                        <div class="mt-3 min-h-[54px] rounded-xl bg-slate-50 px-3 py-3">
                            <p class="text-sm font-black text-slate-900 leading-5">
                                {{ $service->serviceName?->name ?: '-' }}
                            </p>
                            @if($service->categories->count() > 0)
                                @include('livewire.services.partials.service-categories-trigger', [
                                    'service' => $service,
                                    'unitOptions' => $unitOptions,
                                    'variant' => 'chip-white',
                                    'label' => $service->categories->count().' دسته‌بندی',
                                ])
                            @else
                                <p class="mt-1 text-xs text-slate-400">بدون دسته‌بندی</p>
                            @endif
                        </div>

                        <div class="mt-3 flex-1 space-y-3">
                            <div>
                                <p class="text-[11px] font-semibold text-slate-500">توضیحات</p>
                                <p class="mt-1 line-clamp-3 text-xs leading-5 text-slate-600">
                                    {{ $service->description ?: 'بدون توضیحات' }}
                                </p>
                            </div>

                            <div class="grid gap-2 rounded-xl bg-slate-50 px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] text-slate-500">تحویل خدمات</span>
                                    @include('livewire.services.partials.delivery-summary-trigger', [
                                        'service' => $service,
                                        'unitOptions' => $unitOptions,
                                        'label' => 'وضعیت',
                                        'minimal' => true,
                                        'compact' => true,
                                    ])
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] text-slate-500">اپراتور توزیع</span>
                                    <span class="text-xs font-bold text-slate-800 text-left">{{ $creatorName }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] text-slate-500">ایجاد شده</span>
                                    <span class="text-xs font-bold text-slate-800 text-left">{{ $jalaliDateTime($service->created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="openService({{ $service->id }})"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                            >
                                لیست تحویل
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            @if($deliveryChannel === \App\Models\Service::DELIVERY_CHANNEL_GATE)
                                <a
                                    href="{{ route('admin.gate-technical-report', ['service' => $service->id]) }}"
                                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100"
                                >
                                    گزارش فنی
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-slate-500">
                        {{ $hasServiceFilters ? 'موردی برای فیلترهای فعلی پیدا نشد.' : 'هنوز خدمتی تعریف نشده است.' }}
                    </div>
                @endforelse
            </div>
        </div>

        @if($services->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
                {{ $services->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#service-report-list']) }}
            </div>
        @endif
        </div>
    @else
        <div class="space-y-6">
            <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-l from-slate-900 via-indigo-900 to-sky-800 px-6 py-6 text-white">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <button
                                type="button"
                                wire:click="backToServices"
                                class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                بازگشت به فهرست خدمات
                            </button>
                            <h1 class="mt-3 text-2xl font-extrabold">{{ $selectedService->serviceName?->name ?: 'خدمت بدون نام' }}</h1>
                            <p class="mt-2 text-sm text-slate-200">
                                {{ $selectedService->code }}
                                @if($selectedService->categories->count() > 0)
                                    ·
                                    @include('livewire.services.partials.service-categories-trigger', [
                                        'service' => $selectedService,
                                        'unitOptions' => $unitOptions,
                                        'variant' => 'header',
                                        'label' => $selectedService->categories->count().' دسته‌بندی',
                                    ])
                                @else
                                    · بدون دسته‌بندی
                                @endif
                            </p>
                        </div>

                        <div
                            class="flex flex-col items-stretch gap-3 sm:items-end"
                            x-data="{ displaySettingsOpen: false }"
                            @keydown.escape.window="displaySettingsOpen = false"
                        >
                            <div class="flex items-stretch gap-3">
                                <button
                                    type="button"
                                    wire:click="exportToExcel"
                                    wire:loading.attr="disabled"
                                    wire:target="exportToExcel"
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-500/90 px-4 py-2 text-xs font-bold text-white ring-1 ring-emerald-300/40 backdrop-blur transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60 sm:flex-none"
                                >
                                    <svg wire:loading.remove wire:target="exportToExcel" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                    </svg>
                                    <svg wire:loading wire:target="exportToExcel" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="exportToExcel">خروجی اکسل</span>
                                    <span wire:loading wire:target="exportToExcel">در حال آماده‌سازی…</span>
                                </button>

                                <button
                                    type="button"
                                    @click="displaySettingsOpen = true"
                                    :aria-expanded="displaySettingsOpen ? 'true' : 'false'"
                                    aria-haspopup="dialog"
                                    title="تنظیمات نمایش"
                                    aria-label="تنظیمات نمایش"
                                    class="inline-flex shrink-0 items-center justify-center rounded-xl border border-white/15 bg-white/10 p-2 text-slate-200 backdrop-blur transition hover:bg-white/20 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>

                            {{-- «تنظیمات نمایش»: quick modal. Each future setting gets one more @if-style row in the body below. --}}
                            <template x-teleport="body">
                                <div
                                    x-show="displaySettingsOpen"
                                    x-cloak
                                    x-transition.opacity
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 backdrop-blur-md"
                                    style="display: none;"
                                >
                                    <div
                                        x-show="displaySettingsOpen"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        @click.outside="displaySettingsOpen = false"
                                        role="dialog"
                                        aria-modal="true"
                                        aria-label="تنظیمات نمایش رکوردهای تحویل"
                                        class="w-full max-w-sm overflow-hidden rounded-[24px] bg-white text-right text-slate-800 shadow-2xl ring-1 ring-slate-900/5"
                                    >
                                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                                            <div class="flex min-w-0 items-center gap-2.5">
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </span>
                                                <div class="min-w-0">
                                                    <h3 class="truncate text-sm font-black text-slate-900">تنظیمات نمایش</h3>
                                                    <p class="mt-0.5 text-[11px] text-slate-500">نحوهٔ نمایش رکوردهای تحویل</p>
                                                </div>
                                            </div>
                                            <button type="button" @click="displaySettingsOpen = false" class="shrink-0 rounded-full border border-slate-200 bg-white p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="بستن">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="space-y-4 px-5 py-4">
                                            <div>
                                                <p class="text-xs font-black text-slate-700">نمایش دسته‌بندی رکوردهای تحویل</p>

                                                <div class="mt-2 grid grid-cols-1 gap-2">
                                                    <button
                                                        type="button"
                                                        wire:click="setDeliveryDisplayMode('categorized')"
                                                        @click="displaySettingsOpen = false"
                                                        class="flex items-center justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-right text-xs font-bold transition {{ $deliveryDisplayMode === 'categorized' ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
                                                    >
                                                        <span>
                                                            <span class="block">با نمایش دسته‌بندی</span>
                                                            <span class="mt-0.5 block text-[11px] font-medium text-slate-400">رکوردها داخل آکاردئون هر دسته‌بندی</span>
                                                        </span>
                                                        @if($deliveryDisplayMode === 'categorized')
                                                            <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @endif
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="setDeliveryDisplayMode('compact')"
                                                        @click="displaySettingsOpen = false"
                                                        class="flex items-center justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-right text-xs font-bold transition {{ $deliveryDisplayMode === 'compact' ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
                                                    >
                                                        <span>
                                                            <span class="block">بدون نمایش دسته‌بندی</span>
                                                            <span class="mt-0.5 block text-[11px] font-medium text-slate-400">فقط سرتیتر گیرنده‌ها — نمایش ساده‌تر</span>
                                                        </span>
                                                        @if($deliveryDisplayMode === 'compact')
                                                            <svg class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-1 gap-3">
                            <div
                                x-data="{ deliveredOpen: false }"
                                @keydown.escape.window="deliveredOpen = false"
                            >
                                <button
                                    type="button"
                                    @click="deliveredOpen = true"
                                    :aria-expanded="deliveredOpen ? 'true' : 'false'"
                                    aria-haspopup="dialog"
                                    aria-label="مشاهده جزئیات تحویل"
                                    class="group flex w-full items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-right backdrop-blur transition hover:border-emerald-300/40 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-emerald-300/50"
                                >
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/30 transition group-hover:bg-emerald-400/30">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[10px] text-slate-300">تحویل شده</span>
                                        <span class="mt-0.5 block text-sm font-bold text-white">مشاهده جزئیات تحویل</span>
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:-translate-x-0.5 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-show="deliveredOpen"
                                        x-cloak
                                        x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 backdrop-blur-md"
                                        style="display: none;"
                                    >
                                        <div
                                            x-show="deliveredOpen"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            @click.outside="deliveredOpen = false"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-label="جزئیات تحویل بر اساس دسته‌بندی"
                                            class="flex max-h-[82vh] w-full max-w-md flex-col overflow-hidden rounded-[28px] bg-white text-right text-slate-800 shadow-2xl ring-1 ring-slate-900/5"
                                        >
                                            {{-- Header --}}
                                            <div class="relative overflow-hidden bg-gradient-to-l from-indigo-600 via-indigo-600 to-violet-600 px-5 py-5 text-white">
                                                <div class="absolute -left-6 -top-8 h-24 w-24 rounded-full bg-white/10"></div>
                                                <div class="absolute -bottom-10 right-10 h-20 w-20 rounded-full bg-white/10"></div>
                                                <div class="relative flex items-start justify-between gap-3">
                                                    <div class="flex min-w-0 items-center gap-3">
                                                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-6 0h.01M12 16h3m-6 0h.01"/>
                                                            </svg>
                                                        </span>
                                                        <div class="min-w-0">
                                                            <p class="text-[11px] font-medium text-indigo-100">تحویل بر اساس دسته‌بندی</p>
                                                            <h3 class="mt-0.5 truncate text-lg font-black">{{ $selectedService->serviceName?->name ?: 'خدمت' }}</h3>
                                                        </div>
                                                    </div>
                                                    <button type="button" @click="deliveredOpen = false" class="shrink-0 rounded-full bg-white/10 p-2 text-white/80 ring-1 ring-white/20 transition hover:bg-white/20 hover:text-white" aria-label="بستن">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Body --}}
                                            <div class="flex-1 space-y-2.5 overflow-y-auto bg-slate-50/60 px-4 py-4">
                                                @forelse($this->deliveredCategoryBreakdown as $index => $row)
                                                    <div class="group flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3.5 py-3 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition hover:border-indigo-200 hover:shadow-md">
                                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xs font-black text-indigo-600 transition group-hover:bg-indigo-100">
                                                            {{ $index + 1 }}
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-sm font-bold text-slate-800">{{ $row['category'] }}</p>
                                                            @if(($row['remaining'] ?? null) !== null)
                                                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $row['remaining'].(($row['unitLabel'] ?? '-') !== '-' ? ' '.$row['unitLabel'] : '').' باقی‌مانده از '.$row['categoryTotal'] }}</p>
                                                            @endif
                                                        </div>
                                                        <span class="shrink-0 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-black text-indigo-700">
                                                            {{ $row['total'] }}
                                                            <span class="font-bold text-indigo-400">{{ $row['unitLabel'] }}</span>
                                                        </span>
                                                    </div>
                                                @empty
                                                    <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center">
                                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                                            </svg>
                                                        </span>
                                                        <p class="text-sm font-medium text-slate-500">{{ (trim($deliverySearch ?? "") !== "" || $selectedDeliveryEntryType !== 'all' || $selectedCoverageSocialWorker !== 'all' || $deliveryDateFrom !== '' || $deliveryDateTo !== '') ? "موردی برای فیلترهای فعلی پیدا نشد." : "هنوز هیچ تحویلی برای این خدمت ثبت نشده است." }}</p>
                                                    </div>
                                                @endforelse
                                            </div>

                                            {{-- Footer summary --}}
                                            @if($this->deliveredCategoryBreakdown->isNotEmpty())
                                                <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-white px-5 py-3">
                                                    <div class="flex min-w-0 items-center gap-2">
                                                        <span class="text-xs font-medium text-slate-500">مجموع دسته‌بندی‌ها</span>
                                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-900 px-3 py-1 text-xs font-black text-white">
                                                            {{ $this->deliveredCategoryBreakdown->count() }} دسته‌بندی
                                                        </span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        wire:click="exportCategoryBreakdownToExcel"
                                                        wire:loading.attr="disabled"
                                                        wire:target="exportCategoryBreakdownToExcel"
                                                        class="inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl bg-emerald-500/90 px-3 py-1.5 text-[11px] font-bold text-white ring-1 ring-emerald-300/40 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        <svg wire:loading.remove wire:target="exportCategoryBreakdownToExcel" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                                        </svg>
                                                        <svg wire:loading wire:target="exportCategoryBreakdownToExcel" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                        <span wire:loading.remove wire:target="exportCategoryBreakdownToExcel">خروجی اکسل</span>
                                                        <span wire:loading wire:target="exportCategoryBreakdownToExcel">در حال آماده‌سازی…</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>

                @if(session('error'))
                    <div class="border-b border-rose-100 bg-rose-50 px-6 py-3 text-xs font-semibold text-rose-700">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-slate-200 px-6 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">وضعیت:</span>
                        <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800">
                            {{ $statusOptions[$selectedService->status] ?? $selectedService->status }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">منطقه:</span>
                        <span class="text-xs font-bold text-slate-800">{{ $selectedService->district?->name ?: 'بدون منطقه' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">نوع:</span>
                        <x-service-type-badge :type="$selectedService->service_type" />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">تعداد تحویل:</span>
                        <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800">
                            {{ number_format($this->deliveryRecipientCount) }} نفر
                        </span>
                    </div>
                </div>

                <div class="px-6 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative w-full sm:max-w-sm">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="deliverySearch"
                                placeholder="جستجو در تحویل‌ها (نام مددجو، کد مددجو، کد ملی)"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                            >
                        </div>

                        <select wire:model.live="selectedDeliveryEntryType" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 sm:w-56">
                            <option value="all">انواع ثبت</option>
                            <option value="manual">ثبت دستی</option>
                            <option value="individual">شخصی (مددجو)</option>
                            <option value="guardian">خانوادگی (سرپرست)</option>
                        </select>

                        <div
                            x-data="{
                                open: false,
                                query: '',
                                workers: @js($coverageSocialWorkerOptions),
                                normalizeSearchText(value) {
                                    let text = String(value ?? '');

                                    text = text
                                        .replace(/[يى]/g, 'ی')
                                        .replace(/ك/g, 'ک')
                                        .replace(/[ۀة]/g, 'ه')
                                        .replace(/[آأإٱ]/g, 'ا')
                                        .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
                                        .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
                                    text = text.replace(/[\u200C\u200D\uFEFF\u00A0]/g, ' ');

                                    return text.toLowerCase().replace(/\s+/g, ' ').trim();
                                },
                                get filtered() {
                                    const query = this.normalizeSearchText(this.query);

                                    if (! query) {
                                        return this.workers.slice(0, 50);
                                    }

                                    const terms = query.split(' ');

                                    return this.workers
                                        .filter((worker) => {
                                            const haystack = this.normalizeSearchText(worker.name).replace(/ /g, '');

                                            return terms.every((term) => haystack.includes(term.replace(/ /g, '')));
                                        })
                                        .slice(0, 50);
                                },
                                choose(id) {
                                    this.$wire.set('selectedCoverageSocialWorker', id === null ? 'all' : String(id));
                                    this.open = false;
                                    this.query = '';
                                },
                            }"
                            class="relative w-full sm:w-64"
                        >
                            <button
                                type="button"
                                aria-label="مددکار اجتماعی"
                                @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
                                class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            >
                                <span class="truncate">{{ data_get(collect($coverageSocialWorkerOptions)->firstWhere('id', (int) $selectedCoverageSocialWorker), 'name', 'همه مددکاران اجتماعی') }}</span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-show="open"
                                x-cloak
                                x-transition
                                @click.outside="open = false"
                                class="absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                            >
                                <input
                                    x-ref="search"
                                    type="text"
                                    x-model="query"
                                    placeholder="جستجوی مددکار اجتماعی…"
                                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                >

                                <div class="mt-1 max-h-56 space-y-0.5 overflow-y-auto">
                                    <button type="button" @click="choose(null)" class="w-full rounded-lg px-3 py-2 text-right text-sm font-bold text-slate-600 transition hover:bg-slate-50">همه مددکاران اجتماعی</button>
                                    <template x-for="worker in filtered" :key="worker.id">
                                        <button type="button" @click="choose(worker.id)" x-text="worker.name" class="w-full truncate rounded-lg px-3 py-2 text-right text-sm text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"></button>
                                    </template>
                                    <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-slate-400">مددکاری با این جستجو یافت نشد.</p>
                                </div>
                            </div>
                        </div>

                        <div x-data="jalaliDateTimeField($wire.entangle('deliveryDateFrom').live)" class="w-full sm:w-40">
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
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            >
                        </div>

                        <div x-data="jalaliDateTimeField($wire.entangle('deliveryDateTo').live)" class="w-full sm:w-40">
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
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            >
                        </div>

                        <div class="flex items-center gap-2 sm:mr-auto">
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ number_format($deliveryGroups->total()) }} نفر یافت شد</span>
                            </span>
                        </div>

                        @if(trim($deliverySearch ?? '') !== '' || $selectedDeliveryEntryType !== 'all' || $selectedCoverageSocialWorker !== 'all' || $deliveryDateFrom !== '' || $deliveryDateTo !== '')
                            <button
                                type="button"
                                wire:click="clearDeliveryFilters"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-500 outline-none transition hover:bg-slate-50 focus:ring-2 focus:ring-indigo-100"
                            >
                                پاک کردن فیلترها
                            </button>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 bg-slate-50/70 px-4 py-4 sm:px-6" id="delivery-groups-list">
                    @forelse($deliveryGroups as $group)
                        @php
                            $typeBadge = match ($group->recipientType) {
                                'شخصی' => 'bg-indigo-100 text-indigo-700 ring-indigo-200/60',
                                'خانوادگی' => 'bg-emerald-100 text-emerald-700 ring-emerald-200/60',
                                default => 'bg-amber-100 text-amber-700 ring-amber-200/60',
                            };
                            $guardianLabel = $group->person?->guardian?->full_name
                                ?: $group->guardian?->full_name;

                            // Recipient's covering social worker (guardian-assigned),
                            // already eager-loaded with the delivery rows.
                            $coverageWorkerName = $group->person?->guardian?->socialWorker?->full_name
                                ?: $group->guardian?->socialWorker?->full_name;

                            $hasReceipt = $group->deliveries->count() > 0;
                            $relationLabel = $group->guardian
                                ? ($group->guardian->guardian_code ? 'کد خانوار: '.$group->guardian->guardian_code : null)
                                : ($guardianLabel ? 'سرپرست مرتبط: '.$guardianLabel : null);

                            $receiptPayload = [
                                'recipientName' => $group->recipientName ?: '-',
                                'recipientType' => $group->recipientType,
                                'nationalId' => $group->recipientNationalId ?: '-',
                                'recipientCode' => $group->person
                                    ? ($group->person->person_code ?: '-')
                                    : ($group->guardian ? ($group->guardian->guardian_code ?: '-') : null),
                                'recipientCodeLabel' => $group->guardian ? 'کد خانوار' : 'کد مددجو',
                                'relation' => $relationLabel,
                                'mobile' => $group->mobile ?: null,
                                'serviceName' => $selectedService->serviceName?->name ?: 'خدمت',
                                'serviceCode' => $selectedService->code,
                                'date' => $group->receiptDate,
                                'items' => $group->receiptItems,
                                'unitTotals' => $group->unitTotals,
                            ];
                        @endphp

                        <section
                            x-data="deliveryReceipt(@js($receiptPayload))"
                            @keydown.escape.window="receiptOpen = false"
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-sm shadow-sm ring-1 ring-slate-950/[0.02]"
                        >
                            <div class="border-b border-slate-200 bg-gradient-to-l from-white via-slate-50 to-slate-100 px-4 py-4 sm:px-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                                            <h3 class="text-base font-extrabold leading-6 text-slate-950 sm:text-lg">{{ $group->recipientName ?: '-' }}</h3>
                                            @if($group->person?->person_code)
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                                    <span class="font-medium text-slate-400">کد مددجو:</span>
                                                    {{ $group->person->person_code }}
                                                </span>
                                            @elseif($group->guardian?->guardian_code)
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                                    <span class="font-medium text-slate-400">کد خانوار:</span>
                                                    {{ $group->guardian->guardian_code }}
                                                </span>
                                            @endif
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $typeBadge }}">{{ $group->recipientType }}</span>
                                            @if($hasReceipt)
                                                <button
                                                    type="button"
                                                    @click="receiptOpen = true"
                                                    :aria-expanded="receiptOpen ? 'true' : 'false'"
                                                    aria-haspopup="dialog"
                                                    title="مشاهده رسید تحویل"
                                                    aria-label="مشاهده رسید تحویل"
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px] font-bold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                                >
                                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <span>رسید تحویل</span>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 font-medium">کد ملی: {{ $group->recipientNationalId ?: '-' }}</span>
                                            @if(!$group->guardian && $guardianLabel)
                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 font-medium">سرپرست: {{ $guardianLabel }}</span>
                                            @endif
                                            @if($group->mobile)
                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 font-medium">موبایل: {{ $group->mobile }}</span>
                                            @endif
                                            @if($coverageWorkerName)
                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 font-medium">مددکار: {{ $coverageWorkerName }}</span>
                                            @endif
                                            <span class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-1 font-bold text-indigo-700">{{ $group->deliveries->count() }} دسته‌بندی</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs sm:min-w-72">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center shadow-[0_1px_0_rgba(15,23,42,0.03)]">
                                            <p class="text-slate-500">جمع مقدار</p>
                                            <div class="mt-1 flex flex-wrap items-center justify-center gap-1">
                                                @forelse($group->unitTotals as $unitTotal)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                                        <span class="text-slate-500">{{ $unitTotal['label'] }}:</span>
                                                        <span class="text-slate-900">{{ $unitTotal['total'] }}</span>
                                                    </span>
                                                @empty
                                                    <span class="font-extrabold text-slate-900">-</span>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2 text-center shadow-[0_1px_0_rgba(15,23,42,0.03)]">
                                            <p class="text-emerald-700/80">جمع ارزش</p>
                                            <p class="mt-1 font-extrabold text-emerald-700">{{ number_format($group->totalValue) }} ریال</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Body follows the «تنظیمات نمایش» modal: categorized = one accordion per category (compact renders the section header only). --}}
                            @if($deliveryDisplayMode === 'categorized')
                                <div class="divide-y divide-slate-100">
                                    @foreach($group->categorySections as $section)
                                        <div x-data="{ categoryOpen: false }">
                                            <button
                                                type="button"
                                                @click="categoryOpen = !categoryOpen"
                                                :aria-expanded="categoryOpen ? 'true' : 'false'"
                                                class="flex w-full items-center justify-between gap-3 bg-white px-4 py-3 text-right transition hover:bg-slate-50 focus:outline-none focus-visible:bg-slate-50 sm:px-5"
                                            >
                                                <div class="flex min-w-0 items-center gap-2.5">
                                                    <span class="h-2 w-2 shrink-0 rounded-full bg-indigo-400"></span>
                                                    <span class="truncate text-sm font-extrabold text-slate-900">{{ $section['category'] }}</span>
                                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $section['recordCount'] }} رکورد</span>
                                                </div>
                                                <div class="flex shrink-0 items-center gap-3 text-xs">
                                                    <span class="font-bold text-slate-800">{{ $section['quantity'] }} <span class="font-medium text-slate-400">{{ $section['unitLabel'] }}</span></span>
                                                    <span class="hidden text-slate-400 sm:inline">{{ $section['date'] }}</span>
                                                    <svg class="h-4 w-4 text-slate-400 transition-transform" :class="categoryOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </button>
                                            <div x-show="categoryOpen" x-collapse x-cloak>
                                                {{-- Automatic layout: table on wide containers, cards on narrow ones --}}
                                                <div class="report-cq border-t border-slate-100">
                                                    <div class="rpt-md-table overflow-x-auto">
                                                        <table class="w-full min-w-[760px] text-sm">
                                                            <thead>
                                                            <tr class="border-b border-slate-200 bg-white text-slate-500">
                                                                <th class="px-4 py-3 text-right text-xs font-bold">دسته‌بندی</th>
                                                                <th class="px-4 py-3 text-center text-xs font-bold">مقدار تحویل</th>
                                                                <th class="px-4 py-3 text-center text-xs font-bold">ارزش تحویل</th>
                                                                <th class="px-4 py-3 text-center text-xs font-bold">مددکار</th>
                                                                <th class="px-4 py-3 text-center text-xs font-bold">تاریخ تحویل</th>
                                                                <th class="w-16 px-4 py-3 text-center text-xs font-bold">عملیات</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100">
                                                            @foreach($section['deliveries'] as $delivery)
                                                                @php
                                                                    $socialWorkerName = $delivery->display_social_worker_name ?: '—';
                                                                    $createdDate = $jalaliDateTime($delivery->created_at) ?: '—';
                                                                    $deliveredDate = $delivery->delivered_at
                                                                        ? \App\Helpers\Morilog\Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d')
                                                                        : '—';
                                                                    $updatedDate = $delivery->updated_at && $delivery->created_at
                                                                        && $delivery->updated_at->ne($delivery->created_at)
                                                                            ? $jalaliDateTime($delivery->updated_at)
                                                                            : null;
                                                                    $operatorName = trim((string) ($delivery->creator?->full_name ?? $delivery->creator?->name ?? '')) ?: null;
                                                                    $updaterName = trim((string) ($delivery->updater?->full_name ?? $delivery->updater?->name ?? '')) ?: null;
                                                                    $deliveryUnitKey = $delivery->serviceCategory?->unit;
                                                                    $deliveryUnitLabel = $deliveryUnitKey
                                                                        ? ($unitOptions[$deliveryUnitKey] ?? $deliveryUnitKey)
                                                                        : '-';
                                                                @endphp
                                                                <tr class="align-top transition hover:bg-slate-50/80">
                                                                    <td class="px-4 py-4 text-slate-700">
                                                                        <p class="font-bold text-slate-900">{{ $delivery->serviceCategory?->name ?: '-' }}</p>
                                                                    </td>
                                                                    <td class="px-4 py-4 text-center font-bold text-slate-800">
                                                                        {{ \App\Models\Service::formatQuantityForUnit($delivery->delivered_quantity, $deliveryUnitKey) }}
                                                                        {{ $deliveryUnitLabel }}
                                                                    </td>
                                                                    <td class="px-4 py-4 text-center font-bold text-emerald-600">
                                                                        {{ number_format($delivery->delivered_total_value) }} ریال
                                                                    </td>
                                                                    <td class="px-4 py-4 text-center">
                                                                        @include('livewire.services.partials.social-worker-popover', [
                                                                            'socialWorkerName' => $socialWorkerName,
                                                                            'deliveredDate' => $deliveredDate,
                                                                            'createdDate' => $createdDate,
                                                                            'updatedDate' => $updatedDate,
                                                                            'operatorName' => $operatorName,
                                                                            'updaterName' => $updaterName,
                                                                        ])
                                                                    </td>
                                                                    <td class="px-4 py-4 text-center text-slate-700">
                                                                        {{ str_replace(' ', ' - ', $jalaliDateTime($delivery->created_at)) ?: '-' }}
                                                                    </td>
                                                                    <td class="px-4 py-4 text-center">
                                                                        @include('livewire.services.partials.delivery-actions', ['delivery' => $delivery])
                                                                    </td>
                                                                </tr>
                                                                @if($delivery->notes)
                                                                    <tr class="bg-slate-50/80">
                                                                        <td colspan="6" class="px-4 pb-3 pt-2 text-xs text-slate-600">
                                                                            <span class="font-bold text-slate-700">توضیحات:</span>
                                                                            {{ $delivery->notes }}
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <div class="rpt-md-cards space-y-3 px-3 py-3 sm:px-4">
                                                        @foreach($section['deliveries'] as $delivery)
                                                            @php
                                                                $socialWorkerName = $delivery->display_social_worker_name ?: '—';
                                                                $createdDate = $jalaliDateTime($delivery->created_at) ?: '—';
                                                                $deliveredDate = $delivery->delivered_at
                                                                    ? \App\Helpers\Morilog\Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d')
                                                                    : '—';
                                                                $updatedDate = $delivery->updated_at && $delivery->created_at
                                                                    && $delivery->updated_at->ne($delivery->created_at)
                                                                        ? $jalaliDateTime($delivery->updated_at)
                                                                        : null;
                                                                $operatorName = trim((string) ($delivery->creator?->full_name ?? $delivery->creator?->name ?? '')) ?: null;
                                                                $updaterName = trim((string) ($delivery->updater?->full_name ?? $delivery->updater?->name ?? '')) ?: null;
                                                                $deliveryUnitKey = $delivery->serviceCategory?->unit;
                                                                $deliveryUnitLabel = $deliveryUnitKey
                                                                    ? ($unitOptions[$deliveryUnitKey] ?? $deliveryUnitKey)
                                                                    : '-';
                                                            @endphp
                                                            <article class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <p class="min-w-0 flex-1 truncate text-sm font-bold text-slate-900">{{ $delivery->serviceCategory?->name ?: '-' }}</p>
                                                                    @include('livewire.services.partials.delivery-actions', ['delivery' => $delivery, 'size' => 'md'])
                                                                </div>

                                                                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                                        <dt class="text-slate-400">مقدار تحویل</dt>
                                                                        <dd class="mt-0.5 font-bold text-slate-800">
                                                                            {{ \App\Models\Service::formatQuantityForUnit($delivery->delivered_quantity, $deliveryUnitKey) }}
                                                                            {{ $deliveryUnitLabel }}
                                                                        </dd>
                                                                    </div>
                                                                    <div class="rounded-xl bg-emerald-50/70 px-3 py-2">
                                                                        <dt class="text-emerald-700/70">ارزش تحویل</dt>
                                                                        <dd class="mt-0.5 font-bold text-emerald-700">{{ number_format($delivery->delivered_total_value) }} ریال</dd>
                                                                    </div>
                                                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                                        <dt class="text-slate-400">تاریخ تحویل</dt>
                                                                        <dd class="mt-0.5 font-bold text-slate-800">{{ str_replace(' ', ' - ', $jalaliDateTime($delivery->created_at)) ?: '-' }}</dd>
                                                                    </div>
                                                                    <div class="flex items-center rounded-xl bg-slate-50 px-3 py-2">
                                                                        @include('livewire.services.partials.social-worker-popover', [
                                                                            'socialWorkerName' => $socialWorkerName,
                                                                            'deliveredDate' => $deliveredDate,
                                                                            'createdDate' => $createdDate,
                                                                            'updatedDate' => $updatedDate,
                                                                            'operatorName' => $operatorName,
                                                                            'updaterName' => $updaterName,
                                                                        ])
                                                                    </div>
                                                                </dl>

                                                                @if($delivery->notes)
                                                                    <p class="mt-2 rounded-xl bg-slate-50/80 px-3 py-2 text-xs text-slate-600">
                                                                        <span class="font-bold text-slate-700">توضیحات:</span>
                                                                        {{ $delivery->notes }}
                                                                    </p>
                                                                @endif
                                                            </article>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($hasReceipt)
                                <template x-teleport="body">
                                    <div
                                        x-show="receiptOpen"
                                        x-cloak
                                        x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-6 backdrop-blur-sm"
                                        style="display: none;"
                                    >
                                        <div
                                            @click.outside="receiptOpen = false"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-label="رسید تحویل خدمت"
                                            class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white text-right text-slate-800 shadow-2xl"
                                        >
                                            {{-- Header --}}
                                            <div class="flex items-start justify-between gap-3 border-b border-dashed border-slate-300 bg-slate-50 px-5 py-4">
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-600">رسید تحویل خدمت</p>
                                                    <h3 class="mt-1 truncate text-lg font-black text-slate-900">{{ $receiptPayload['serviceName'] }}</h3>
                                                    <p class="mt-0.5 text-xs text-slate-500">کد خدمت: {{ $receiptPayload['serviceCode'] ?: '-' }}</p>
                                                </div>
                                                <div class="flex shrink-0 items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="downloadImage()"
                                                        :disabled="downloading"
                                                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                                        title="دانلود تصویر رسید"
                                                        aria-label="دانلود تصویر رسید"
                                                    >
                                                        <svg x-show="!downloading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                                        </svg>
                                                        <svg x-show="downloading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                        <span x-text="downloading ? 'در حال ساخت…' : 'دانلود تصویر'"></span>
                                                    </button>
                                                    <button type="button" @click="receiptOpen = false" class="rounded-full border border-slate-200 bg-white p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="بستن">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <p x-show="downloadError" x-cloak class="border-b border-rose-100 bg-rose-50 px-5 py-2 text-xs font-semibold text-rose-700" x-text="downloadError"></p>

                                            <div class="flex-1 overflow-y-auto px-5 py-4">
                                                {{-- Recipient info --}}
                                                <div class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-xs">
                                                    <div>
                                                        <p class="text-slate-400">نوع گیرنده</p>
                                                        <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['recipientType'] }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-400">نام گیرنده</p>
                                                        <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['recipientName'] }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-400">کد ملی</p>
                                                        <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['nationalId'] }}</p>
                                                    </div>
                                                    @if($receiptPayload['recipientCode'] !== null)
                                                        <div>
                                                            <p class="text-slate-400">{{ $receiptPayload['recipientCodeLabel'] }}</p>
                                                            <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['recipientCode'] }}</p>
                                                        </div>
                                                    @endif
                                                    @if($receiptPayload['relation'])
                                                        <div class="col-span-2">
                                                            <p class="text-slate-400">اطلاعات مرتبط</p>
                                                            <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['relation'] }}</p>
                                                        </div>
                                                    @endif
                                                    @if($receiptPayload['mobile'])
                                                        <div>
                                                            <p class="text-slate-400">موبایل</p>
                                                            <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['mobile'] }}</p>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <p class="text-slate-400">تاریخ تحویل</p>
                                                        <p class="mt-0.5 font-bold text-slate-800">{{ $receiptPayload['date'] }}</p>
                                                    </div>
                                                </div>

                                                {{-- Delivered items (receipt/invoice style) --}}
                                                <div class="mt-4">
                                                    <p class="mb-2 text-xs font-black text-slate-700">اقلام تحویل‌شده</p>
                                                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                                                        <table class="w-full text-xs">
                                                            <thead>
                                                                <tr class="bg-slate-100 text-slate-500">
                                                                    <th class="px-3 py-2 text-right font-bold">دسته‌بندی</th>
                                                                    <th class="px-3 py-2 text-center font-bold">مقدار</th>
                                                                    <th class="px-3 py-2 text-center font-bold">تاریخ</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100">
                                                                @foreach($receiptPayload['items'] as $item)
                                                                    <tr class="bg-white">
                                                                        <td class="px-3 py-2 text-right font-semibold text-slate-800">
                                                                            {{ $item['category'] }}
                                                                            @if(($item['recordCount'] ?? 1) > 1)
                                                                                <span class="mr-1 text-[10px] font-medium text-slate-400">({{ $item['recordCount'] }} رکورد)</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="px-3 py-2 text-center font-bold text-slate-900">
                                                                            {{ $item['quantity'] }} {{ $item['unitLabel'] }}
                                                                        </td>
                                                                        <td class="px-3 py-2 text-center text-slate-500">{{ $item['date'] }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Totals --}}
                                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3">
                                                    <span class="text-xs font-bold text-slate-500">جمع مقدار</span>
                                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                                        @forelse($group->unitTotals as $unitTotal)
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                                                <span class="text-slate-500">{{ $unitTotal['label'] }}:</span>
                                                                <span class="text-slate-900">{{ $unitTotal['total'] }}</span>
                                                            </span>
                                                        @empty
                                                            <span class="font-bold text-slate-900">-</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="border-t border-slate-200 px-5 py-3 text-left">
                                                <button type="button" @click="receiptOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100">
                                                    بستن
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            @endif
                        </section>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-slate-500">
                            {{ (trim($deliverySearch ?? "") !== "" || $selectedDeliveryEntryType !== 'all' || $selectedCoverageSocialWorker !== 'all' || $deliveryDateFrom !== '' || $deliveryDateTo !== '') ? "موردی برای فیلترهای فعلی پیدا نشد." : "هنوز هیچ تحویلی برای این خدمت ثبت نشده است." }}
                        </div>
                    @endforelse

                    @if($deliveryGroups->hasPages())
                        <div class="pt-2">
                            {{ $deliveryGroups->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#delivery-groups-list']) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($showEditDeliveryModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-6 backdrop-blur-sm" wire:click.self="closeEditDeliveryModal">
                <div class="w-full max-w-3xl rounded-[28px] bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">ویرایش تحویل خدمت</h2>
                            <p class="mt-1 text-xs text-slate-500">اصلاح مشخصات ثبت، مقدار تحویل و توضیحات در همین صفحه.</p>
                        </div>
                        <button type="button" wire:click="closeEditDeliveryModal" class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="بستن">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveDeliveryEdits" class="space-y-5 px-6 py-5">
                        @php
                            $editingDelivery = $this->editingDelivery;
                            $isManualEditing = $editingDelivery && ! $editingDelivery->person && ! $editingDelivery->guardian;
                        @endphp

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">نام گیرنده</label>
                                <input
                                    type="text"
                                    wire:model.defer="editRecipientName"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                >
                                @error('editRecipientName') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-[11px] text-slate-500">این نام روی خود رکورد تحویل ذخیره می‌شود و در صورت نیاز می‌تواند با پرونده اصلی متفاوت باشد.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">کد ملی گیرنده</label>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <input
                                        type="text"
                                        wire:model.defer="editNationalId"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                    >
                                    <button type="button" wire:click="connectDeliveryRecipient" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">اتصال</button>
                                </div>
                                @error('editNationalId') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-[11px] text-slate-500">در صورت تغییر کد ملی یا نیاز به اصلاح اتصال، با دکمه اتصال پرونده مناسب پیدا و دوباره به این تحویل وصل می‌شود.</p>
                                @if($editConnectMessage !== '')
                                    <p class="mt-2 rounded-xl px-3 py-2 text-xs font-medium {{ $editConnectMessageType === 'success' ? 'bg-emerald-50 text-emerald-700' : ($editConnectMessageType === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700') }}">{{ $editConnectMessage }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">موبایل</label>
                                <input
                                    type="text"
                                    wire:model.defer="editMobile"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                >
                                @error('editMobile') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">دسته‌بندی خدمت</label>
                                <select
                                    wire:model.defer="editServiceCategoryId"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                >
                                    <option value="">انتخاب دسته‌بندی</option>
                                    @foreach($selectedService?->categories?->sortBy('sort_id') ?? [] as $category)
                                        @php($remainingStock = $selectedService?->remainingStockForCategory((int) $category->id) ?? 0)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }} - موجودی: {{ number_format($remainingStock, 2) }} {{ $unitOptions[$category->unit] ?? $category->unit }} - ارزش واحد: {{ number_format((int) $category->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('editServiceCategoryId') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">مقدار تحویل</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model.defer="editDeliveredQuantity"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                >
                                @error('editDeliveredQuantity') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">تاریخ تحویل</label>
                                <input
                                    type="text"
                                    dir="ltr"
                                    wire:model.defer="editDeliveredAt"
                                    placeholder="1405/03/16"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                                >
                                @error('editDeliveredAt') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-bold text-slate-700">نوع ثبت</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    @if($editingDelivery?->person)
                                        فردی
                                    @elseif($editingDelivery?->guardian)
                                        سرپرست خانوار
                                    @else
                                        ثبت دستی
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">توضیحات</label>
                            <textarea
                                wire:model.defer="editNotes"
                                rows="4"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                            ></textarea>
                            @error('editNotes') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                            <button type="button" wire:click="closeEditDeliveryModal" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">انصراف</button>
                            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700">ذخیره تغییرات</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

    @include('livewire.services.partials.delivery-summary-modal')

    {{-- Categories Modal --}}
    <template x-teleport="body">
    <div
        x-show="categoriesOpen"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="categoriesOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-6 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.outside="categoriesOpen = false"
            role="dialog"
            aria-modal="true"
            aria-label="دسته‌بندی‌های خدمت"
            class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white text-right text-slate-800 shadow-2xl"
        >
            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 border-b border-dashed border-slate-300 bg-slate-50 px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h10M4 17h7"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-600">دسته‌بندی‌های خدمت</p>
                        <h3 class="mt-0.5 truncate text-lg font-black text-slate-900" x-text="categoryTitle"></h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            <span x-text="categories.length"></span> دسته‌بندی
                        </p>
                    </div>
                </div>
                <button type="button" @click="categoriesOpen = false" class="shrink-0 rounded-full border border-slate-200 bg-white p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="بستن">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                <template x-if="categories.length">
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="grid grid-cols-[1fr_auto] gap-3 bg-slate-100 px-4 py-2.5 text-[11px] font-bold text-slate-500">
                            <span>نام دسته</span>
                            <span class="text-left">مقدار / ارزش واحد</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="(category, index) in categories" :key="`${category.name}-${index}`">
                                <div class="grid grid-cols-[1fr_auto] items-center gap-3 px-4 py-3 transition odd:bg-white even:bg-slate-50/70 hover:bg-indigo-50/40">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-[11px] font-black text-indigo-600" x-text="index + 1"></span>
                                        <p class="truncate text-sm font-bold text-slate-800" x-text="category.name"></p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                            <span class="text-slate-900" x-text="category.quantity"></span>
                                            <span class="text-slate-400" x-text="category.unit"></span>
                                        </span>
                                        <template x-if="category.value">
                                            <span class="text-[11px] font-semibold text-emerald-600">
                                                <span x-text="category.value"></span> ریال
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="!categories.length">
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        دسته‌بندی‌ای برای این خدمت ثبت نشده است.
                    </div>
                </template>
            </div>

            <div class="border-t border-slate-200 px-5 py-3 text-left">
                <button type="button" @click="categoriesOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100">
                    بستن
                </button>
            </div>
        </div>
    </div>
    </template>
</div>
