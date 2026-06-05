<div class="space-y-6">
    @php
        $statusBadgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'approved' => 'bg-emerald-100 text-emerald-700',
            'in_distribution' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-sky-100 text-sky-700',
        ];
    @endphp

    @if(! $selectedService)
        <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
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

                    <label class="block w-full lg:max-w-md">
                        <span class="sr-only">جستجوی خدمات</span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="جستجو با شناسه، نام، دسته‌بندی و ..."
                            class="w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-indigo-100/70 backdrop-blur outline-none transition focus:border-white/40 focus:bg-white/15"
                        >
                    </label>
                </div>
            </div>

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

                <div class="grid gap-4 border-b border-slate-200 px-6 py-5 md:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">وضعیت خدمت</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $statusOptions[$selectedService->status] ?? $selectedService->status }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">منطقه</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedService->district?->name ?: 'بدون منطقه' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">بازه توزیع</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ optional($selectedService->distribution_start_date)->format('Y-m-d') ?: '-' }}
                            تا
                            {{ optional($selectedService->distribution_end_date)->format('Y-m-d') ?: '-' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">ارزش کل خدمت</p>
                        <p class="mt-1 font-bold text-slate-800">{{ number_format($selectedService->total_service_value) }} ریال</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                        <tr>
                            <th class="px-4 py-4 text-right font-bold">گیرنده</th>
                            <th class="px-4 py-4 text-right font-bold">نوع گیرنده</th>
                            <th class="px-4 py-4 text-center font-bold">کد ملی</th>
                            <th class="px-4 py-4 text-center font-bold">مقدار تحویل</th>
                            <th class="px-4 py-4 text-center font-bold">ارزش تحویل</th>
                            <th class="px-4 py-4 text-right font-bold">مددکار / ثبت‌کننده</th>
                            <th class="px-4 py-4 text-center font-bold">تاریخ تحویل</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($selectedService->deliveries->sortByDesc('delivered_at') as $delivery)
                            @php
                                $recipientType = $delivery->person
                                    ? 'مددجو'
                                    : ($delivery->guardian ? 'سرپرست' : 'ثبت دستی');
                                $guardianLabel = $delivery->person?->guardian?->full_name
                                    ?: $delivery->guardian?->full_name;
                                $socialWorkerName = $delivery->socialWorker?->full_name ?: '-';
                                $creatorName = $delivery->creator?->full_name ?: $delivery->creator?->name ?: '-';
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
                                <td class="px-4 py-4 text-right text-slate-700">{{ $recipientType }}</td>
                                <td class="px-4 py-4 text-center text-slate-700">{{ $delivery->recipient_national_id }}</td>
                                <td class="px-4 py-4 text-center font-bold text-slate-800">
                                    {{ number_format((float) $delivery->delivered_quantity, 2) }}
                                    {{ $unitOptions[$selectedService->service_unit] ?? $selectedService->service_unit }}
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-emerald-700">
                                    {{ number_format($delivery->delivered_total_value) }} ریال
                                </td>
                                <td class="px-4 py-4 text-slate-700">
                                    <p class="font-semibold text-slate-900">مددکار: {{ $socialWorkerName }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ثبت‌کننده: {{ $creatorName }}</p>
                                </td>
                                <td class="px-4 py-4 text-center text-slate-700">
                                    {{ optional($delivery->delivered_at)->format('Y-m-d') ?: '-' }}
                                </td>
                            </tr>
                            @if($delivery->notes)
                                <tr class="bg-slate-50/70">
                                    <td colspan="7" class="px-4 pb-4 pt-0 text-xs text-slate-600">
                                        <span class="font-bold text-slate-700">توضیحات:</span>
                                        {{ $delivery->notes }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                    هنوز هیچ تحویلی برای این خدمت ثبت نشده است.
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
