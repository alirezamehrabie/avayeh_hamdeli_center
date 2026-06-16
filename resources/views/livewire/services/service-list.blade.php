<div
    x-data="{
        detailsOpen: false,
        details: null,
        categoriesOpen: false,
        categories: [],
        categoryTitle: '',
        openDetails(payload) {
            this.details = payload;
            this.detailsOpen = true;
        }
    }"
    class="space-y-4"
    dir="rtl"
>
    @php
        $badgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'approved' => 'bg-emerald-100 text-emerald-700',
            'in_distribution' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-sky-100 text-sky-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-cyan-600 via-sky-600 to-blue-600 px-5 py-4 text-white">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-extrabold leading-7 text-white">لیست خدمات</h1>
                    <p class="mt-1.5 hidden max-w-3xl text-xs leading-6 text-cyan-50/90 lg:block">
                        خدمات تعریف شده را یکجا مشاهده کنید، جزئیات را بررسی کنید و برای ویرایش به فرم تعریف خدمات بروید.
                    </p>
                </div>

                <div class="flex shrink-0 items-center">
                    <button
                        type="button"
                        wire:click="createService"
                        class="group inline-flex h-9 items-center justify-center gap-2 rounded-full border border-white/15 bg-white/12 px-3.5 text-xs font-semibold text-cyan-50/90 shadow-sm shadow-cyan-950/10 ring-1 ring-white/10 transition duration-200 hover:-translate-y-0.5 hover:bg-white/18 hover:text-white focus:outline-none focus:ring-4 focus:ring-white/25 active:translate-y-0 active:scale-[0.98] sm:h-10 sm:px-4 sm:text-sm"
                    >
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/20 transition duration-200 group-hover:scale-110 group-hover:bg-white/20 group-active:scale-95">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/>
                            </svg>
                        </span>
                        <span>افزودن خدمت</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-4 px-4 py-4">
            <div class="flex flex-col gap-3 rounded-[26px] border border-slate-200 bg-slate-50/80 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-3.5">
                <div class="flex flex-col gap-3 sm:flex-1 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.35-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="جستجو با کد، نام خدمت، زیر‌دسته یا ایجادکننده ..."
                            class="h-10 w-full rounded-2xl border border-slate-200 bg-white py-2 pl-4 pr-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100 sm:h-9"
                        >
                    </div>

                    <div class="relative sm:w-52">
                        <select
                            wire:model.live="statusFilter"
                            class="h-10 w-full rounded-2xl border border-slate-200 bg-white py-2 pl-4 pr-4 text-sm font-medium text-slate-700 outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100 sm:h-9"
                        >
                            <option value="all">همه وضعیت‌ها</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @forelse($services as $service)
                @php
                    $creator = $service->creator;
                    $creatorName = $creator?->full_name ?: $creator?->name ?: 'نامشخص';
                    $createdAt = $service->created_at
                        ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->created_at)->format('Y/m/d')
                        : '-';
                @endphp

                <article
                    @click="openDetails(@js([
                        'code' => $service->code,
                        'name' => $service->serviceName?->name ?: '-',
                        'category' => $service->serviceCategory?->name ?: '-',
                        'type' => $typeOptions[$service->service_type] ?? $service->service_type,
                        'status' => $statusOptions[$service->status] ?? $service->status,
                        'priority' => $service->priority ? ($priorityOptions[$service->priority] ?? $service->priority) : 'بدون اولویت',
                        'quantity' => $this->formatReadableNumber($service->total_quantity) . ' ' . ($unitOptions[$service->service_unit] ?? ($service->service_unit ?? '-')),
                        'value' => number_format($service->total_service_value) . ' ریال',
                        'district' => $service->district?->name ?: 'بدون منطقه',
                        'start' => $service->distribution_start_date ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d') : '-',
                        'end' => $service->distribution_end_date ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_end_date)->format('Y/m/d') : '-',
                        'creator' => $service->creator?->full_name ?: $service->creator?->name ?: '-',
                        'description' => $service->description ?: 'توضیحی ثبت نشده است.',
                        'status_notes' => $service->status_notes ?: 'یادداشتی ثبت نشده است.',
                        'workers_count' => $service->uniqueSocialWorkersCount(),
                        'created_at' => $service->created_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->created_at)->format('Y/m/d') : '-',
                        'categories' => $service->categories->map(fn ($category) => [
                            'name' => $category->name,
                            'summary' => $this->formatReadableNumber($category->quantity)
                                . ' × '
                                . number_format((int) ($category->value ?? 0))
                                . ' ریال = '
                                . number_format((int) round(((float) $category->quantity) * ((float) ($category->value ?? 0))))
                                . ' ریال',
                        ])->values(),
                        'categories_total' => number_format((int) ($service->total_service_value ?? 0)) . ' ریال',
                        'categories_total_words' => \App\Helpers\PersianNumber::rialToTomanWords((int) ($service->total_service_value ?? 0)),
                    ]))"
                    class="cursor-pointer rounded-[28px] border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:border-slate-300 hover:shadow-md sm:px-5"
                >
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex min-w-0 flex-1 flex-col gap-4 xl:flex-row xl:items-center xl:gap-3">
                            <div class="flex items-center justify-between gap-3 xl:w-auto xl:justify-start">
                                <span class="inline-flex shrink-0 items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                    {{ $service->code }}
                                </span>

                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold {{ $badgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $statusOptions[$service->status] ?? $service->status }}
                                </span>
                            </div>

                            <div class="min-w-0 xl:min-w-[13rem] xl:flex-[1.2]">
                                <p class="text-[11px] font-medium text-slate-400">نام خدمت</p>
                                <p class="mt-1 truncate text-sm font-black text-slate-800 sm:text-base">
                                    {{ $service->serviceName?->name ?: '-' }}
                                </p>
                            </div>

                            <div class="xl:w-auto xl:flex-[0.75]">
                                <p class="text-[11px] font-medium text-slate-400">زیر‌دسته‌ها</p>
                                <button
                                    type="button"
                                    @click.stop="categoryTitle = @js($service->serviceName?->name ?: 'خدمت'); categories = @js($service->categories->map(fn ($category) => [
                                        'name' => $category->name,
                                        'quantity' => number_format((float) $category->quantity, 2),
                                        'unit' => $unitOptions[$category->unit] ?? ($category->unit ?? '-'),
                                    ])->values()); categoriesOpen = true"
                                    class="mt-1 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                >
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h10M4 17h7m9-7l2 2 4-4"/>
                                    </svg>
                                    <span>{{ $service->categories->count() }} مورد</span>
                                </button>
                            </div>

                            <div class="min-w-0 xl:flex-[0.9]">
                                <p class="text-[11px] font-medium text-slate-400">ارزش کل</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800">
                                    {{ number_format($service->total_service_value) }} ریال
                                </p>
                            </div>

                            <div class="xl:flex-[0.7]">
                                <p class="text-[11px] font-medium text-slate-400">مددکاران</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $service->uniqueSocialWorkersCount() }} نفر</p>
                            </div>

                            <div class="min-w-0 xl:min-w-[12rem] xl:flex-1">
                                <p class="text-[11px] font-medium text-slate-400">ایجاد شده توسط</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800">{{ $creatorName }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $createdAt }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3 xl:border-t-0 xl:pt-0">
                            <button
                                type="button"
                                @click.stop
                                wire:click="editService({{ $service->id }})"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                                title="ویرایش"
                                aria-label="ویرایش"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536M9 11l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 14.536A4 4 0 0110.414 15.6L7 16l.4-3.414A4 4 0 018.464 10.88z"/>
                                </svg>
                            </button>

                            <button
                                type="button"
                                @click.stop="openDetails(@js([
                                    'code' => $service->code,
                                    'name' => $service->serviceName?->name ?: '-',
                                    'category' => $service->serviceCategory?->name ?: '-',
                                    'type' => $typeOptions[$service->service_type] ?? $service->service_type,
                                    'status' => $statusOptions[$service->status] ?? $service->status,
                                    'priority' => $service->priority ? ($priorityOptions[$service->priority] ?? $service->priority) : 'بدون اولویت',
                                    'quantity' => $this->formatReadableNumber($service->total_quantity) . ' ' . ($unitOptions[$service->service_unit] ?? ($service->service_unit ?? '-')),
                                    'value' => number_format($service->total_service_value) . ' ریال',
                                    'district' => $service->district?->name ?: 'بدون منطقه',
                                    'start' => $service->distribution_start_date ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d') : '-',
                                    'end' => $service->distribution_end_date ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_end_date)->format('Y/m/d') : '-',
                                    'creator' => $service->creator?->full_name ?: $service->creator?->name ?: '-',
                                    'description' => $service->description ?: 'توضیحی ثبت نشده است.',
                                    'status_notes' => $service->status_notes ?: 'یادداشتی ثبت نشده است.',
                                    'workers_count' => $service->uniqueSocialWorkersCount(),
                                    'created_at' => $service->created_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($service->created_at)->format('Y/m/d') : '-',
                                    'categories' => $service->categories->map(fn ($category) => [
                                        'name' => $category->name,
                                        'summary' => $this->formatReadableNumber($category->quantity)
                                            . ' × '
                                            . number_format((int) ($category->value ?? 0))
                                            . ' ریال = '
                                            . number_format((int) round(((float) $category->quantity) * ((float) ($category->value ?? 0))))
                                            . ' ریال',
                                    ])->values(),
                                    'categories_total' => number_format((int) ($service->total_service_value ?? 0)) . ' ریال',
                                    'categories_total_words' => \App\Helpers\PersianNumber::rialToTomanWords((int) ($service->total_service_value ?? 0)),
                                ]))"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 hover:text-slate-800"
                                title="جزئیات"
                                aria-label="جزئیات"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 6H18m0 0v4.5M18 6l-6 6m-6 6h4.5M6 18v-4.5M6 18l6-6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-slate-500">
                    {{ trim($search ?? '') !== '' ? 'هیچ خدمتی برای جستجوی فعلی یافت نشد.' : 'هنوز خدمتی تعریف نشده است.' }}
                </div>
            @endforelse
        </div>
    </div>

    <div
        x-show="categoriesOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/35 px-4"
        style="display: none;"
    >
        <div @click.outside="categoriesOpen = false" class="w-full max-w-xl rounded-[28px] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <h3 class="text-lg font-black text-slate-800">زیر‌دسته‌های خدمت</h3>
                    <p class="mt-1 truncate text-sm text-slate-500" x-text="categoryTitle"></p>
                </div>
                <button type="button" @click="categoriesOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700" aria-label="بستن">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto px-5 py-5">
                <template x-if="categories.length">
                    <div class="space-y-3">
                        <template x-for="(category, index) in categories" :key="`${category.name}-${index}`">
                            <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-400">نام دسته</p>
                                    <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="category.name"></p>
                                </div>
                                <div class="flex items-center gap-2 self-start rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 sm:self-center">
                                    <span x-text="category.quantity"></span>
                                    <span class="text-slate-300">|</span>
                                    <span x-text="category.unit"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!categories.length">
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        زیر‌دسته‌ای برای این خدمت ثبت نشده است.
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div
        x-show="detailsOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 px-4"
        style="display: none;"
    >
        <div @click.outside="detailsOpen = false" class="w-full max-w-3xl rounded-[28px] border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
            <div class="flex items-start justify-between border-b border-slate-200 px-4 py-3 sm:px-5">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-black text-slate-800">جزئیات خدمت</h3>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200" x-text="details?.code"></span>
                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-bold text-violet-700 ring-1 ring-violet-100" x-text="`${details?.status ?? '-'} / ${details?.priority ?? '-'}`"></span>
                    </div>
                    <p class="mt-1 truncate text-sm font-semibold text-slate-600" x-text="details?.name"></p>
                </div>
                <button type="button" @click="detailsOpen = false" class="rounded-full border border-slate-200 bg-white p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="max-h-[78vh] overflow-y-auto px-4 py-4 sm:px-5">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                    <div class="space-y-3">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-2xl border border-sky-100 bg-sky-50/70 px-3 py-2.5"><p class="text-[11px] text-sky-700/70">نوع</p><p class="mt-1 text-sm font-bold text-slate-800" x-text="details?.type"></p></div>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 px-3 py-2.5"><p class="text-[11px] text-emerald-700/70">تعداد مددکار</p><p class="mt-1 text-sm font-bold text-slate-800" x-text="details?.workers_count"></p></div>
                            <div class="rounded-2xl border border-amber-100 bg-amber-50/60 px-3 py-2.5"><p class="text-[11px] text-amber-800/70">مقدار کل</p><p class="mt-1 text-sm font-bold text-slate-800" x-text="details?.quantity"></p></div>
                            <div class="rounded-2xl border border-rose-100 bg-rose-50/60 px-3 py-2.5"><p class="text-[11px] text-rose-700/70">ارزش کل</p><p class="mt-1 text-sm font-bold text-slate-800" x-text="details?.value"></p></div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5"><p class="text-[11px] text-slate-500">منطقه</p><p class="mt-1 text-sm font-bold text-slate-800" x-text="details?.district"></p></div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-sm font-bold text-slate-800">زیر‌دسته‌های خدمت</h4>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200/80" x-text="`${details?.categories?.length ?? 0} مورد`"></span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <template x-if="details?.categories?.length">
                                    <div class="max-h-56 space-y-2 overflow-y-auto pe-1 sm:max-h-64">
                                        <template x-for="(category, index) in (details?.categories ?? [])" :key="`${category.name}-${index}`">
                                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5">
                                                <p class="truncate text-[13px] font-bold text-slate-800" x-text="category.name"></p>
                                                <p class="mt-0.5 text-[11px] leading-5 text-slate-500 sm:text-xs" x-text="category.summary"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!(details?.categories?.length)">
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-xs text-slate-500">
                                        زیر‌دسته‌ای برای این خدمت ثبت نشده است.
                                    </div>
                                </template>

                                <div class="rounded-2xl border border-sky-100 bg-sky-50/80 px-4 py-3">
                                    <p class="text-xs font-medium text-sky-700/80">جمع کل ارزش خدمت</p>
                                    <p class="mt-1 text-sm font-black text-sky-900" x-text="details?.categories_total"></p>
                                    <p class="mt-1 break-words text-[11px] leading-5 text-sky-800/75 sm:max-w-[24rem]" x-text="details?.categories_total_words"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5"><p class="text-[11px] text-slate-500">ایجاد شده توسط</p><p class="mt-1 text-sm font-bold text-slate-800"><span x-text="details?.creator"></span> <span class="text-slate-400">-</span> <span x-text="details?.created_at"></span></p></div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5"><p class="text-[11px] text-slate-500">شروع / پایان</p><p class="mt-1 text-sm font-bold text-slate-800"><span x-text="details?.start"></span> - <span x-text="details?.end"></span></p></div>
                        </div>

                        <div class="space-y-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-3">
                                <p class="text-[11px] text-slate-500">توضیحات خدمت</p>
                                <p class="mt-1 text-sm font-semibold leading-6 text-slate-800" x-text="details?.description"></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-3">
                                <p class="text-[11px] text-slate-500">یادداشت وضعیت</p>
                                <p class="mt-1 text-sm font-semibold leading-6 text-slate-800" x-text="details?.status_notes"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
