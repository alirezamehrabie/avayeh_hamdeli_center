<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-indigo-700 via-indigo-700 to-cyan-700 px-6 py-6 text-white">
            <h1 class="text-2xl font-extrabold">تاریخچه تحویل</h1>
            <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                ابتدا خدمت موردنظر را انتخاب کنید تا سوابق تحویل همان خدمت نمایش داده شود.
            </p>
        </div>

        @if(!$selectedService)
            <div class="p-6">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($services as $service)
                        <div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">

                            {{-- رنگ‌بار بالای کارت --}}
                            <div class="h-1 bg-gradient-to-r from-cyan-400 to-blue-500"></div>

                            <div class="p-4">
                                {{-- هدر کارت --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="inline-block text-[10px] font-bold tracking-widest text-cyan-500 uppercase">خدمت</span>
                                        <h2 class="mt-0.5 truncate text-base font-black text-slate-800">{{ $service->service_code }}</h2>
                                        <p class="truncate text-xs text-slate-400">{{ $service->serviceName?->name ?: '—' }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="selectService({{ $service->id }})"
                                        class="shrink-0 flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 hover:bg-cyan-100 transition"
                                        aria-label="مشاهده تحویل‌های خدمت {{ $service->service_code }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
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
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center text-sm text-slate-400">
                            هیچ سابقه تحویلی ثبت نشده است.
                        </div>
                    @endforelse
                </div>

            </div>
        @else
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <button
                            type="button"
                            wire:click="backToServices"
                            class="mb-3 inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            بازگشت به خدمات
                        </button>
                        <h2 class="text-xl font-black text-slate-800">{{ $selectedService->service_code }} - {{ $selectedService->serviceName?->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $selectedService->serviceCategory?->name ?: '-' }} | {{ \App\Models\Service::TYPE_OPTIONS[$selectedService->service_type] ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
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
                            <td class="px-4 py-4 text-center text-slate-700">{{ $delivery->recipient_national_id }}</td>
                            <td class="px-4 py-4 text-center font-bold text-slate-800">{{ number_format((float) $delivery->delivered_quantity, 2) }}</td>
                            <td class="px-4 py-4 text-center font-bold text-emerald-700">{{ number_format($delivery->delivered_total_value) }} IRR</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ optional($delivery->delivered_at)->format('Y-m-d') }}</td>
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
