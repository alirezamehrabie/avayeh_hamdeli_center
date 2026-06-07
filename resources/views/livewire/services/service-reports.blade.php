<div class="space-y-6">
    @php
        $statusBadgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'approved' => 'bg-yellow-100 text-yellow-700',
            'in_distribution' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-green-100 text-green-700',
        ];
    @endphp

    @if(! $selectedService)
        <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
            {{-- Header --}}
            <div class="bg-gradient-to-l from-violet-600 via-indigo-600 to-sky-600 px-4 py-4 text-white sm:px-6 sm:py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-extrabold sm:text-2xl">گزارش خدمات</h1>
                            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-2.5 py-0.5 text-xs font-semibold backdrop-blur">
                    {{ $services->count() }} خدمت
                </span>
                        </div>
                        <p class="mt-1.5 max-w-3xl text-xs text-indigo-50/90 sm:text-sm">
                            فهرست خدمات را سریع مرور کنید، جستجو بزنید و مستقیم وارد تحویل‌های هر خدمت شوید.
                        </p>
                    </div>
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
                            <option value="family">خانوادگی</option>
                            <option value="individual">شخصی</option>
                        </select>


                        {{-- View Toggle --}}
                        <div class="flex items-center rounded-lg border border-slate-200 bg-white p-0.5 text-xs">
                            <button
                                wire:click="$set('displayMode', 'list')"
                                class="{{ $displayMode === 'list' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700' }} flex items-center gap-1.5 rounded-md px-2.5 py-1.5 transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                لیست
                            </button>
                            <button
                                wire:click="$set('displayMode', 'card')"
                                class="{{ $displayMode === 'card' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700' }} flex items-center gap-1.5 rounded-md px-2.5 py-1.5 transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                </svg>
                                کارت
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        @if($displayMode === 'list')
            {{-- List View --}}
            <div class="overflow-x-auto px-4 pb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="px-3 py-3 text-center font-semibold">شناسه</th>
                            <th class="px-3 py-3 text-center font-semibold">وضعیت</th>
                            <th class="px-3 py-3 text-right font-semibold">خدمت</th>
                            <th class="px-3 py-3 text-right font-semibold">دسته‌بندی</th>
                            <th class="px-3 py-3 text-right font-semibold">نوع</th>
                            <th class="px-3 py-3 text-right font-semibold">توضیحات</th>
                            <th class="px-3 py-3 text-right font-semibold">اپراتور</th>
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
                            <td class="px-3 py-3 text-center text-slate-700 font-bold">{{ $service->id }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusBadgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $statusOptions[$service->status] ?? $service->status }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-900">{{ $service->serviceName?->name ?: '-' }}</td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ $service->serviceCategory?->name ?: 'بدون دسته‌بندی' }}</td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ $typeOptions[$service->service_type] ?? $service->service_type }}</td>
                            <td class="px-3 py-3 text-right text-slate-600 max-w-[200px] truncate">{{ $service->description ?: 'بدون توضیحات' }}</td>
                            <td class="px-3 py-3 text-right text-slate-600 text-xs">{{ $creatorName }}</td>
                            <td class="px-3 py-3 text-right text-slate-500 text-xs">{{ $jalaliDateTime($service->created_at) }}</td>
                            <td class="px-3 py-3 text-center">
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                {{ trim($search ?? '') !== '' ? 'موردی برای جستجوی فعلی پیدا نشد.' : 'هنوز خدمتی تعریف نشده است.' }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($displayMode === 'card')
            {{-- Card View --}}
            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
                @forelse($services as $service)
                    @php
                        $creatorName = trim(implode(' ', array_filter([
                            $service->creator?->first_name,
                            $service->creator?->last_name,
                        ]))) ?: ($service->creator?->name ?: '-');
                    @endphp
                    <div class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-4 text-right shadow-sm transition-all duration-200 hover:border-indigo-200 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold text-slate-500">شناسه خدمت</p>
                                <p class="mt-1 text-sm font-black text-slate-800">{{ $service->id }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusBadgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $statusOptions[$service->status] ?? $service->status }}
                            </span>
                        </div>

                        <div class="mt-3 min-h-[54px] rounded-xl bg-slate-50 px-3 py-3">
                            <p class="text-sm font-black text-slate-900 leading-5">
                                {{ $service->serviceName?->name ?: '-' }}
                                <span class="text-xs font-medium text-slate-500">
                                    / {{ $service->serviceCategory?->name ?: 'بدون دسته‌بندی' }}
                                </span>
                            </p>
                        </div>

                        <div class="mt-3 flex-1 space-y-3">
                            <div>
                                <p class="text-[11px] font-semibold text-slate-500">توضیحات</p>
                                <p class="mt-1 line-clamp-3 min-h-[54px] text-xs leading-5 text-slate-600">
                                    {{ $service->description ?: 'بدون توضیحات' }}
                                </p>
                            </div>

                            <div class="grid gap-2 rounded-xl bg-slate-50 px-3 py-3">
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

                        <button
                            type="button"
                            wire:click="openService({{ $service->id }})"
                            class="mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                        >
                            لیست تحویل
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-slate-500">
                        {{ trim($search ?? '') !== '' ? 'موردی برای جستجوی فعلی پیدا نشد.' : 'هنوز خدمتی تعریف نشده است.' }}
                    </div>
                @endforelse
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
                                {{ $selectedService->service_code }} · {{ $selectedService->serviceCategory?->name ?: 'بدون دسته‌بندی' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="text-[10px] text-slate-300">نوع خدمت</p>
                                <p class="mt-1 text-sm font-bold">{{ $typeOptions[$selectedService->service_type] ?? $selectedService->service_type }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="text-[10px] text-slate-300">کل مقدار</p>
                                <p class="mt-1 text-sm font-bold">
                                    {{ number_format((float) $selectedService->total_quantity, 2) }}
                                    {{ $unitOptions[$selectedService->service_unit] ?? $selectedService->service_unit }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="text-[10px] text-slate-300">تحویل شده</p>
                                <p class="mt-1 text-sm font-bold">{{ number_format((float) $selectedService->quantity_delivered, 2) }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="text-[10px] text-slate-300">تعداد تحویل‌ها</p>
                                <p class="mt-1 text-sm font-bold">{{ $selectedService->deliveries->count() }} مورد</p>
                            </div>
                        </div>
                    </div>
                </div>

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
                        <span class="text-xs font-bold text-slate-800">{{ $typeOptions[$selectedService->service_type] ?? $selectedService->service_type }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">واحد:</span>
                        <span class="text-xs font-bold text-slate-800">{{ $unitOptions[$selectedService->service_unit] ?? $selectedService->service_unit }}</span>
                    </div>
                </div>

                <div class="px-6 py-3">
                    <div class="relative w-full sm:max-w-sm">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="deliverySearch"
                            placeholder="جستجو در تحویل‌ها (نام، کد ملی، موبایل، مددکار)"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-slate-50 text-slate-600">
                            <th class="px-4 py-4 text-right font-bold">نام گیرنده</th>
                            <th class="px-4 py-4 text-center font-bold">نوع</th>
                            <th class="px-4 py-4 text-center font-bold">کد ملی</th>
                            <th class="px-4 py-4 text-center font-bold">مقدار تحویل</th>
                            <th class="px-4 py-4 text-center font-bold">ارزش تحویل</th>
                            <th class="px-4 py-4 text-center font-bold">مددکار</th>
                            <th class="px-4 py-4 text-center font-bold">تاریخ تحویل</th>
                            <th class="px-4 py-4 w-16 text-center font-bold">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($filteredDeliveries as $delivery)
                            @php
                                $recipientType = $delivery->person
                                    ? 'شخصی'
                                    : ($delivery->guardian ? 'خانوادگی' : 'ثبت دستی');
                                $typeBadge = match ($recipientType) {
                                    'شخصی' => 'bg-indigo-100 text-indigo-700',
                                    'خانوادگی' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                                $guardianLabel = $delivery->person?->guardian?->full_name
                                    ?: $delivery->guardian?->full_name;
                                $socialWorkerName = $delivery->socialWorker?->full_name ?: '-';
                                $creatorName = $delivery->creator?->full_name ?: $delivery->creator?->name ?: '-';
                                $socialWorkerInitial = $socialWorkerName !== '-' ? strtoupper(mb_substr($socialWorkerName, 0, 1, 'UTF-8')) : '-';
                                $creatorInitial = $creatorName !== '-' ? strtoupper(mb_substr($creatorName, 0, 1, 'UTF-8')) : '-';
                                $createdDate = $jalaliDateTime($delivery->created_at) ?: '-';
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-slate-700">
                                    <p class="font-bold text-slate-900">{{ $delivery->recipient_name }}</p>
                                    <div class="mt-2 space-y-1 text-xs text-slate-500">
                                        @if($delivery->person)
                                            <p>کد مددجو: {{ $delivery->person->person_code ?: '-' }}</p>
                                        @endif
                                        @if($delivery->guardian)
                                            <p>کد خانوار: {{ $delivery->guardian->guardian_code ?: '-' }}</p>
                                        @elseif($guardianLabel)
                                            <p>سرپرست مرتبط: {{ $guardianLabel }}</p>
                                        @endif
                                        @if($delivery->mobile)
                                            <p>موبایل: {{ $delivery->mobile }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center"><span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $typeBadge }}">{{ $recipientType }}</span></td>
                                <td class="px-4 py-4 text-center text-slate-700">{{ $delivery->recipient_national_id }}</td>
                                <td class="px-4 py-4 text-center font-bold text-slate-800">
                                    {{ number_format((float) $delivery->delivered_quantity, 2) }}
                                    {{ $unitOptions[$selectedService->service_unit] ?? $selectedService->service_unit }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-emerald-600">
                                    {{ number_format($delivery->delivered_total_value) }} ریال
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-3">
                                        {{-- Social Worker --}}
                                        <div class="group relative cursor-default" x-data>
                                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold ring-2 ring-white">
                                                {{ $socialWorkerInitial }}
                                            </div>
                                            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 hidden whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 shadow-lg group-hover:block rtl:translate-x-1/2 rtl:left-auto rtl:right-1/2 rtl:mr-2">
                                                <p class="font-semibold text-slate-900">مددکار: {{ $socialWorkerName }}</p>
                                                <p class="mt-0.5 text-slate-500">تاریخ ثبت: {{ $createdDate }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center text-slate-700">
                                    {{ str_replace(' ', ' - ', $jalaliDateTime($delivery->created_at)) ?: '-' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        {{-- Edit --}}
                                        <button
                                            type="button"
                                            wire:click="editDelivery({{ $delivery->id }})"
                                            class="rounded-lg border border-sky-200 bg-sky-50 p-1.5 text-sky-600 transition hover:bg-sky-100 hover:text-sky-700"
                                            title="ویرایش"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        {{-- Delete --}}
                                        <button
                                            type="button"
                                            wire:click="deleteDelivery({{ $delivery->id }})"
                                            onclick="return confirm('آیا از حذف این رکورد اطمینان دارید؟')"
                                            class="rounded-lg border border-rose-200 bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700"
                                            title="حذف"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        {{-- Print Receipt --}}
                                        <button
                                            type="button"
                                            wire:click="printReceipt({{ $delivery->id }})"
                                            class="rounded-lg border border-emerald-200 bg-emerald-50 p-1.5 text-emerald-600 transition hover:bg-emerald-100 hover:text-emerald-700"
                                            title="چاپ رسید"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if($delivery->notes)
                                <tr class="bg-slate-50/70">
                                    <td colspan="8" class="px-4 pb-2 pt-2 text-xs text-slate-600">
                                        <span class="font-bold text-slate-700">توضیحات:</span>
                                        {{ $delivery->notes }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                    {{ trim($deliverySearch ?? "") !== "" ? "موردی برای جستجوی فعلی پیدا نشد." : "هنوز هیچ تحویلی برای این خدمت ثبت نشده است." }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
