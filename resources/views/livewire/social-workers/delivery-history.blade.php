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
                        <button
                            type="button"
                            wire:click="selectService({{ $service->id }})"
                            class="group relative w-full overflow-hidden rounded-2xl border border-slate-100 bg-white text-right shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-100"
                            aria-label="مشاهده تحویل‌های خدمت {{ $service->code }}"
                        >

                            {{-- رنگ‌بار بالای کارت --}}
                            <div class="h-1 bg-gradient-to-r from-cyan-400 to-blue-500"></div>

                            <div class="p-4">
                                {{-- هدر کارت --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="inline-block text-[10px] font-bold tracking-widest text-cyan-500 uppercase">خدمت</span>
                                        <h2 class="mt-0.5 truncate text-base font-black text-slate-800">{{ $service->code }}</h2>
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
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <button
                            type="button"
                            wire:click="backToServices"
                            class="mb-3 inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-100 sm:rounded-2xl sm:px-4 sm:py-2 sm:text-sm"
                        >
                            بازگشت به خدمات
                        </button>
                        <h2 class="truncate text-lg font-black text-slate-800 sm:text-xl">{{ $selectedService->code }} - {{ $selectedService->serviceName?->name }}</h2>
                        <p class="mt-1 truncate text-xs font-medium text-slate-500 sm:text-sm">
                            {{ $selectedService->serviceCategory?->name ?: '-' }} | {{ \App\Models\Service::TYPE_OPTIONS[$selectedService->service_type] ?? '-' }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[32rem]">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <dt class="text-[10px] font-bold text-slate-400">تعداد</dt>
                            <dd class="mt-0.5 text-sm font-black text-slate-800">{{ number_format((int) ($selectedService->worker_deliveries_count ?? 0)) }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <dt class="text-[10px] font-bold text-slate-400">مقدار</dt>
                            <dd class="mt-0.5 text-sm font-black text-slate-800">{{ number_format((float) ($selectedService->worker_delivered_quantity ?? 0), 2) }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <dt class="text-[10px] font-bold text-slate-400">آخرین تحویل</dt>
                            <dd class="mt-0.5 text-sm font-black text-slate-800">{{ $this->formatLastDeliveryDate($selectedService->worker_last_delivery_at ?? null) }}</dd>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2">
                            <dt class="text-[10px] font-bold text-emerald-600/70">ارزش</dt>
                            <dd class="mt-0.5 truncate text-sm font-black text-emerald-700">{{ number_format((int) ($selectedService->worker_delivered_value ?? 0)) }} IRR</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="divide-y divide-slate-100 sm:hidden">
                @forelse($deliveries as $delivery)
                    <article class="px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-extrabold text-slate-800">{{ $delivery->recipient_name ?: '-' }}</h3>
                                <p class="mt-0.5 text-[11px] font-medium text-slate-400">{{ $delivery->recipient_national_id ?: '-' }}</p>
                            </div>
                            <time class="shrink-0 rounded-lg bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-500">
                                {{ optional($delivery->delivered_at)->format('Y-m-d') ?: '-' }}
                            </time>
                        </div>

                        <dl class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
                            <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-2.5 py-2">
                                <dt class="text-slate-400">مقدار</dt>
                                <dd class="font-extrabold text-slate-800">{{ number_format((float) $delivery->delivered_quantity, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2 rounded-xl bg-emerald-50/70 px-2.5 py-2">
                                <dt class="text-emerald-600/70">ارزش</dt>
                                <dd class="font-extrabold text-emerald-700">{{ number_format($delivery->delivered_total_value) }} IRR</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-500">هیچ سابقه تحویلی برای این خدمت یافت نشد.</div>
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
