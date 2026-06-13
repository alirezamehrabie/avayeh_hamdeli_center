<div x-data="{ detailsOpen: false, details: null }" class="space-y-6">
    @php
        $badgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'approved' => 'bg-emerald-100 text-emerald-700',
            'in_distribution' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-sky-100 text-sky-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-cyan-600 via-sky-600 to-blue-600 px-6 py-6 text-white">

        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="mt-2 text-2xl font-extrabold">لیست خدمات</h1>
                    <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                        خدمات تعریف شده را یکجا مشاهده کنید، جزئیات را بررسی کنید و برای ویرایش به فرم تعریف خدمات بروید.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs text-cyan-100">تعداد کل خدمات</p>
                    <p class="mt-1 text-lg font-bold">{{ $services->count() }} خدمت</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-700">فهرست خدمات تعریف‌شده</h2>
                <p class="mt-1 text-sm text-slate-500">نمایش فشرده خدمات با امکان مشاهده جزئیات و ورود سریع به ویرایش</p>
            </div>
            <button
                type="button"
                wire:click="createService"
                class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-bold text-cyan-700 transition hover:bg-cyan-100"
            >
                تعریف خدمت جدید
            </button>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($services as $service)
                @php
                    $creator = $service->creator;
                    $creatorName = $creator?->full_name ?: $creator?->name ?: 'نامشخص';
                    $creatorLabel = $creator?->access_level === \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR
                        ? '' . $creatorName
                        : '' . $creatorName;
                @endphp
                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/80">
                <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500">شناسه</p>
                            <p class="mt-1 text-sm font-black text-slate-800">{{ $service->code }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ $statusOptions[$service->status] ?? $service->status }}
                        </span>
                    </div>

                    <div class="mt-3 rounded-xl bg-sky-50/60 px-3 py-2">
                        <p class="text-[10px] font-medium tracking-wide text-sky-600">
                            ایجاد کننده
                        </p>

                        <p class="mt-0.5 text-xs font-bold leading-5 text-sky-950">
                            {{ $creatorLabel }}
                        </p>
                    </div>



                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50/70 px-3 py-2">
                            <p class="text-[10px] font-medium tracking-wide text-slate-500">نام خدمت</p>
                            <p class="mt-0.5 text-sm font-bold text-slate-800">
                                {{ $service->serviceName?->name ?: '-' }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50/70 px-3 py-2">
                            <p class="text-[10px] font-medium tracking-wide text-slate-500">دسته‌بندی</p>
                            <p class="mt-0.5 text-sm font-bold text-slate-800">
                                {{ $service->serviceCategory?->name ?: '-' }}
                            </p>
                        </div>
                    </div>


                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="min-w-0 rounded-xl bg-slate-50/70 px-3 py-2">
                            <p class="text-[10px] font-medium tracking-wide text-slate-500">ارزش کل</p>
                            <p class="mt-0.5 truncate text-sm font-bold text-slate-800">
                                {{ number_format($service->total_service_value) }} ریال
                            </p>
                        </div>

                        <div class="min-w-0 rounded-xl bg-slate-50/70 px-3 py-2">
                            <p class="text-[10px] font-medium tracking-wide text-slate-500">تعداد مددکار</p>
                            <p class="mt-0.5 truncate text-sm font-bold text-slate-800">
                                {{ $service->socialWorkers->count()}} نفر
                            </p>
                        </div>
                    </div>


                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="editService({{ $service->id }})"
                            class="flex-1 rounded-2xl bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100 hover:text-emerald-800"
                        >
                            ویرایش
                        </button>

                        <button
                            type="button"
                            @click="details = @js([
                                'code' => $service->code,
                                'name' => $service->serviceName?->name ?: '-',
                                'category' => $service->serviceCategory?->name ?: '-',
                                'type' => $typeOptions[$service->service_type] ?? $service->service_type,
                                'status' => $statusOptions[$service->status] ?? $service->status,
                                'priority' => $service->priority ? ($priorityOptions[$service->priority] ?? $service->priority) : 'بدون اولویت',
                                'quantity' => number_format((float) $service->total_quantity, 2) . ' ' . ($unitOptions[$service->service_unit] ?? ($service->service_unit ?? '-')),
                                'delivered' => number_format((float) $service->quantity_delivered, 2),
                                'remaining' => number_format($service->remaining_quantity, 2),
                                'value' => number_format($service->total_service_value) . ' ریال',
                                'district' => $service->district?->name ?: 'بدون منطقه',
                                'start' => optional($service->distribution_start_date)->format('Y-m-d') ?: '-',
                                'end' => optional($service->distribution_end_date)->format('Y-m-d') ?: '-',
                                'creator' => $service->creator?->full_name ?: $service->creator?->name ?: '-',
                                'description' => $service->description ?: 'توضیحی ثبت نشده است.',
                                'status_notes' => $service->status_notes ?: 'یادداشتی ثبت نشده است.',
                                'workers_count' => $service->socialWorkers->count(),
                            ]); detailsOpen = true"
                            class="rounded-2xl px-4 py-2.5 text-sm font-bold text-gray-700 transition bg-gray-50 hover:bg-gray-100"
                        >
                            جزئیات
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-slate-500">
                    هنوز خدمتی تعریف نشده است.
                </div>
            @endforelse
        </div>
    </div>

    <div
        x-show="detailsOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 px-4"
        style="display: none;"
    >
        <div @click.outside="detailsOpen = false" class="w-full max-w-2xl rounded-[30px] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-lg font-black text-slate-800">جزئیات خدمت</h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="details?.code"></p>
                </div>
                <button type="button" @click="detailsOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto px-5 py-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">نام</p><p class="mt-1 font-bold text-slate-800" x-text="details?.name"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">دسته‌بندی</p><p class="mt-1 font-bold text-slate-800" x-text="details?.category"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">نوع</p><p class="mt-1 font-bold text-slate-800" x-text="details?.type"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">وضعیت / اولویت</p><p class="mt-1 font-bold text-slate-800"><span x-text="details?.status"></span> - <span x-text="details?.priority"></span></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">مقدار کل</p><p class="mt-1 font-bold text-slate-800" x-text="details?.quantity"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">ارزش کل</p><p class="mt-1 font-bold text-slate-800" x-text="details?.value"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">تحویل / باقی‌مانده</p><p class="mt-1 font-bold text-slate-800"><span x-text="details?.delivered"></span> / <span x-text="details?.remaining"></span></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">تعداد مددکار</p><p class="mt-1 font-bold text-slate-800" x-text="details?.workers_count"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">منطقه</p><p class="mt-1 font-bold text-slate-800" x-text="details?.district"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">شروع / پایان</p><p class="mt-1 font-bold text-slate-800"><span x-text="details?.start"></span> - <span x-text="details?.end"></span></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2"><p class="text-xs text-slate-500">توضیحات خدمت</p><p class="mt-1 font-bold text-slate-800" x-text="details?.description"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2"><p class="text-xs text-slate-500">یادداشت وضعیت</p><p class="mt-1 font-bold text-slate-800" x-text="details?.status_notes"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2"><p class="text-xs text-slate-500">ایجادکننده</p><p class="mt-1 font-bold text-slate-800" x-text="details?.creator"></p></div>
                </div>
            </div>
        </div>
    </div>
</div>
