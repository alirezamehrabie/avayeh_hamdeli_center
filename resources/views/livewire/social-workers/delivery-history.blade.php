<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-slate-700 via-indigo-600 to-cyan-600 px-6 py-6 text-white">
            <h1 class="text-2xl font-extrabold">تاریخچه تحویل</h1>
            @if($selectedService)
                <p class="mt-2 flex max-w-3xl items-baseline gap-2 truncate">
                    <span class="text-xs font-medium text-cyan-50/75 sm:text-sm">سوابق تحویل شما:</span>
                    <span class="text-sm font-extrabold tracking-tight text-white sm:text-lg">{{ $selectedService->serviceName?->name ?: 'خدمت نامشخص' }}</span>
                    <span class="shrink-0 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-medium text-cyan-50/60 ring-1 ring-white/10">
                        {{ $this->persianNumber($selectedService->code) }}
                    </span>
                </p>
            @else
                <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                    ابتدا خدمت موردنظر را انتخاب کنید تا سوابق تحویل همان خدمت نمایش داده شود.
                </p>
            @endif
        </div>

        @if(!$selectedService)
            <div class="p-6">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($services as $service)
                        <button
                            type="button"
                            wire:click="selectService({{ $service->id }})"
                            class="group relative w-full overflow-hidden rounded-2xl border border-slate-100 bg-white text-right shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-100"
                            aria-label="مشاهده تحویل‌های خدمت {{ $this->persianNumber($service->code) }}"
                        >

                            {{-- رنگ‌بار بالای کارت --}}
                            <div class="h-1 bg-gradient-to-r from-cyan-400 to-blue-500"></div>

                            <div class="p-4">
                                {{-- هدر کارت --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="inline-block text-[10px] font-bold tracking-widest text-cyan-500 uppercase">خدمت</span>
                                        <h2 class="mt-0.5 truncate text-base font-black text-slate-800">{{ $this->persianNumber($service->code) }}</h2>
                                        <p class="truncate text-xs text-slate-400">{{ $service->serviceName?->name ?: '—' }}</p>
                                    </div>
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 transition group-hover:bg-cyan-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </span>
                                </div>

                                {{-- جداکننده --}}
                                <div class="my-3 border-t border-slate-50"></div>

                                {{-- اطلاعات --}}
                                <dl class="space-y-2 text-xs">
                                    <div class="flex justify-between gap-2">
                                        <dt class="text-slate-400">دسته‌بندی</dt>
                                        <dd class="font-semibold text-slate-700 text-left">{{ $service->serviceCategory?->name ?: '—' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <dt class="text-slate-400">نوع خدمت</dt>
                                        <dd class="font-semibold text-slate-700">{{ \App\Models\Service::TYPE_OPTIONS[$service->service_type] ?? '—' }}</dd>
                                    </div>
                                    @if($service->description)
                                        <div class="flex justify-between gap-2">
                                            <dt class="text-slate-400">توضیحات</dt>
                                            <dd class="font-semibold text-slate-700 truncate max-w-[55%] text-left">{{ $service->description }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                {{-- آخرین تحویل --}}
                                <div class="mt-3 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                    <span class="text-[11px] text-slate-400">آخرین تحویل</span>
                                    <span class="text-[11px] font-bold text-slate-700">{{ $this->formatLastDeliveryDate($service->last_delivery_at) }}</span>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center text-sm text-slate-400">
                            هیچ سابقه تحویلی ثبت نشده است.
                        </div>
                    @endforelse
                </div>

            </div>
        @else
            <div class="border-b border-slate-200 px-3 py-2.5 sm:px-5 sm:py-4">
                <div class="mx-auto max-w-3xl">
                    <div class="mt-1 flex flex-wrap justify-center gap-1.5">
                        @forelse($selectedService->categories->take(4) as $index => $category)
                            <span class="inline-flex max-w-[48%] items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold leading-4 text-slate-600 ring-1 ring-inset ring-slate-200/70 sm:max-w-none sm:text-[11px]">
                                <span class="truncate">{{ $category->name ?: '-' }}</span>
                            </span>
                        @empty
                            <span class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold leading-4 text-slate-600 ring-1 ring-inset ring-slate-200/70">{{ $selectedService->serviceCategory?->name ?: '-' }}</span>
                        @endforelse
                    </div>

                    <dl class="mt-2 grid grid-cols-3 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50/70 text-center">
                            <div class="min-w-0 px-1.5 py-2">
                                <dt class="flex items-center justify-center gap-1 text-[10px] font-bold text-slate-400">
                                    <svg class="h-3.5 w-3.5 text-cyan-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M8 7h8M8 12h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <span>تعداد</span>
                                </dt>
                                <dd class="mt-0.5 truncate text-xs font-black text-slate-800 sm:text-sm">{{ $this->persianNumber(number_format((int) ($selectedService->worker_deliveries_count ?? 0))) }}</dd>
                            </div>
                            <div class="min-w-0 border-s border-slate-100 px-1.5 py-2">
                                <dt class="flex items-center justify-center gap-1 text-[10px] font-bold text-slate-400">
                                    <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 16h16M7 16V8m5 8V5m5 11v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <span>مقدار</span>
                                </dt>
                                <dd class="mt-0.5 truncate text-xs font-black text-slate-800 sm:text-sm">{{ $this->formatQuantity($selectedService->worker_delivered_quantity ?? 0) }}</dd>
                            </div>
                            <div class="min-w-0 border-s border-slate-100 px-1.5 py-2">
                                <dt class="flex items-center justify-center gap-1 text-[10px] font-bold text-slate-400">
                                    @if($selectedService->service_type === 'family')
                                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM16 10a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM3.5 19c.6-3 2.4-5 4.5-5s3.9 2 4.5 5M12.5 18c.5-2.4 1.8-4 3.5-4 1.8 0 3.2 1.6 3.7 4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @else
                                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM5 20c.8-4 3.3-6 7-6s6.2 2 7 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                    <span>نوع</span>
                                </dt>
                                <dd class="mt-0.5 truncate text-xs font-black text-slate-800 sm:text-sm">
                                    {{ $selectedService->service_type === 'family' ? 'خانوادگی' : ($selectedService->service_type === 'individual' ? 'شخصی' : '-') }}
                                </dd>
                            </div>
                        </dl>
                </div>
            </div>

            <div class="hidden">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <button
                            type="button"
                            wire:click="backToServices"
                            class="mb-3 inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-100 sm:rounded-2xl sm:px-4 sm:py-2 sm:text-sm"
                        >
                            بازگشت به خدمات
                        </button>
                        <h2 class="truncate text-lg font-black text-slate-800 sm:text-xl">{{ $this->persianNumber($selectedService->code) }} - {{ $selectedService->serviceName?->name }}</h2>
                        <p class="mt-1 truncate text-xs font-medium text-slate-500 sm:text-sm">
                            {{ $selectedService->serviceCategory?->name ?: '-' }} | {{ \App\Models\Service::TYPE_OPTIONS[$selectedService->service_type] ?? '-' }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[32rem]">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <dt class="text-[10px] font-bold text-slate-400">تعداد</dt>
                            <dd class="mt-0.5 text-sm font-black text-slate-800">{{ $this->persianNumber(number_format((int) ($selectedService->worker_deliveries_count ?? 0))) }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <dt class="text-[10px] font-bold text-slate-400">مقدار</dt>
                            <dd class="mt-0.5 text-sm font-black text-slate-800">{{ $this->formatQuantity($selectedService->worker_delivered_quantity ?? 0) }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <dt class="text-[10px] font-bold text-slate-400">آخرین تحویل</dt>
                            <dd class="mt-0.5 text-sm font-black text-slate-800">{{ $this->formatLastDeliveryDate($selectedService->worker_last_delivery_at ?? null) }}</dd>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2">
                            <dt class="text-[10px] font-bold text-emerald-600/70">ارزش</dt>
                            <dd class="mt-0.5 truncate text-sm font-black text-emerald-700">{{ $this->formatCurrency($selectedService->worker_delivered_value ?? 0) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div
                x-data="{ filtersOpen: false }"
                class="border-b border-slate-100 bg-slate-50/50 px-3 py-2 sm:px-5"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white px-3 py-2 text-right text-xs font-bold text-slate-600 shadow-sm transition hover:border-cyan-100 hover:bg-cyan-50/50 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                    x-on:click="filtersOpen = ! filtersOpen"
                    x-bind:aria-expanded="filtersOpen.toString()"
                    aria-controls="delivery-history-filters"
                >
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-cyan-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                        </svg>
                        <span>جستجو و فیلتر</span>
                    </span>
                    <span class="inline-flex items-center gap-2 text-[10px] text-slate-400">
                        @if($deliverySearch !== '' || $deliveryDateFrom !== '' || $deliveryDateTo !== '')
                            <span class="rounded-full bg-cyan-50 px-2 py-0.5 font-black text-cyan-700 ring-1 ring-cyan-100">فعال</span>
                        @endif
                        <svg class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': filtersOpen }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </button>

                <div
                    x-cloak
                    x-show="filtersOpen"
                    x-collapse.duration.200ms
                    id="delivery-history-filters"
                    class="pt-2"
                >
                <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_9rem_9rem_auto] sm:items-center">
                    <label class="relative block">
                        <span class="sr-only">جستجو در سوابق تحویل</span>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="deliverySearch"
                            placeholder="جستجو نام یا کد ملی"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-cyan-300 focus:ring-4 focus:ring-cyan-100 focus:ring-offset-2"
                        >
                    </label>

                    <div x-data="jalaliDateTimeField($wire.entangle('deliveryDateFrom').live)">
                        <label class="relative block">
                            <span class="sr-only">از تاریخ</span>
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
                                class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 outline-none transition placeholder:text-slate-400 focus:border-cyan-300 focus:ring-4 focus:ring-cyan-100 focus:ring-offset-2"
                            >
                        </label>
                    </div>

                    <div x-data="jalaliDateTimeField($wire.entangle('deliveryDateTo').live)">
                        <label class="relative block">
                            <span class="sr-only">تا تاریخ</span>
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
                                class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 outline-none transition placeholder:text-slate-400 focus:border-cyan-300 focus:ring-4 focus:ring-cyan-100 focus:ring-offset-2"
                            >
                        </label>
                    </div>

                    @if($deliverySearch !== '' || $deliveryDateFrom !== '' || $deliveryDateTo !== '')
                        <button
                            type="button"
                            wire:click="clearDeliveryFilters"
                            class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-cyan-100 focus:ring-offset-2"
                        >
                            حذف فیلتر
                        </button>
                    @endif
                </div>
                </div>
            </div>

            <div class="space-y-2 bg-slate-50/60 px-3 py-3 sm:hidden">
                @php
                    $mobileDeliveryGroups = $deliveries->getCollection()
                        ->groupBy(fn ($delivery) => $delivery->person_id
                            ? 'person-'.$delivery->person_id
                            : ($delivery->guardian_id ? 'guardian-'.$delivery->guardian_id : 'national-'.$delivery->recipient_national_id)
                        )
                        ->map(function ($group) {
                            return [
                                'recipient' => $group->first(),
                                'items' => $group,
                            ];
                        });
                @endphp

                @forelse($mobileDeliveryGroups as $deliveryGroup)
                    @php($recipientDelivery = $deliveryGroup['recipient'])
                    <article class="rounded-2xl border border-t-[3px] border-slate-200/70 border-r-cyan-200 border-t-cyan-100 bg-white px-3 py-2.5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-[15px] font-black leading-5 tracking-tight text-slate-900">{{ $recipientDelivery->recipient_name ?: '-' }}</h3>
                                <p class="mt-0.5 truncate text-[11px] font-medium leading-4 text-slate-400">
                                    <span class="text-slate-300">کد ملی</span>
                                    <span>{{ $this->persianNumber($recipientDelivery->recipient_national_id ?: '-') }}</span>
                                </p>
                            </div>
                            <time class="shrink-0 rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-bold leading-4 text-slate-500 ring-1 ring-inset ring-slate-100">
                                {{ $this->formatLastDeliveryDate($recipientDelivery->delivered_at) }}
                            </time>
                        </div>

                        <div class="mt-2 overflow-hidden rounded-xl border border-slate-100 bg-slate-50/80 text-[11px]">
                            @foreach($deliveryGroup['items'] as $deliveryItem)
                                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-2.5 py-2 {{ $loop->first ? '' : 'border-t border-slate-100' }}">
                                    <span class="truncate font-semibold text-slate-600">{{ $deliveryItem->serviceCategory?->name ?: '-' }}</span>
                                    <span class="shrink-0">
                                        <span class="font-black text-slate-900">{{ $this->formatQuantity($deliveryItem->delivered_quantity) }}</span>
                                        @if($deliveryItem->serviceCategory?->unit)
                                            <span class="mr-0.5 text-[10px] font-medium text-slate-400">{{ $this->formatUnitLabel($deliveryItem->serviceCategory->unit) }}</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                        @if($deliverySearch !== '' || $deliveryDateFrom !== '' || $deliveryDateTo !== '')
                            <p class="font-bold text-slate-700">نتیجه‌ای برای فیلترهای فعلی یافت نشد.</p>
                            <button
                                type="button"
                                wire:click="clearDeliveryFilters"
                                class="mt-3 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                            >
                                حذف فیلتر
                            </button>
                        @else
                            <p>هیچ سابقه تحویلی برای این خدمت یافت نشد.</p>
                        @endif
                    </div>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950 text-white">
                    <tr>
                        <th class="px-4 py-4 text-right font-bold">گیرنده</th>
                        <th class="px-4 py-4 text-center font-bold">کد ملی</th>
                        <th class="px-4 py-4 text-center font-bold">مقدار</th>
                        <th class="px-4 py-4 text-center font-bold">ارزش</th>
                        <th class="px-4 py-4 text-center font-bold">تاریخ</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($deliveries as $delivery)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-4 text-slate-700">{{ $delivery->recipient_name }}</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ $this->persianNumber($delivery->recipient_national_id) }}</td>
                            <td class="px-4 py-4 text-center font-bold text-slate-800">{{ $this->formatQuantity($delivery->delivered_quantity) }}</td>
                            <td class="px-4 py-4 text-center font-bold text-emerald-700">{{ $this->formatCurrency($delivery->delivered_total_value) }}</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ $this->formatLastDeliveryDate($delivery->delivered_at) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">هیچ سابقه تحویلی برای این خدمت یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>
</div>
