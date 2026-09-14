<div
    x-data="{
        ...sheetBackGuard('categoriesOpen'),
        categories: [],
        categoryTitle: '',
        detailsOpen: false,
        details: null,
        workersOpen: false,
        workersSummary: null,
        openDetails(payload) {
            this.details = payload;
            this.detailsOpen = true;
        },
        openWorkers(payload) {
            this.workersSummary = payload;
            this.workersOpen = true;
        }
    }"
    x-init="bindSheetBack()"
    x-on:service-workers-loaded.window="openWorkers($event.detail.summary)"
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
        $barClasses = [
            'draft' => 'bg-slate-300',
            'approved' => 'bg-emerald-500',
            'in_distribution' => 'bg-amber-500',
            'completed' => 'bg-sky-500',
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

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        href="{{ route('admin.service-management') }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/12 text-cyan-50/90 shadow-sm shadow-cyan-950/10 ring-1 ring-white/10 transition duration-200 hover:-translate-y-0.5 hover:bg-white/18 hover:text-white focus:outline-none focus:ring-4 focus:ring-white/25 active:translate-y-0 active:scale-[0.98] sm:h-10 sm:w-10"
                        title="تنظیمات خدمات"
                        aria-label="تنظیمات خدمات"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.757.426 1.757 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.757-2.924 1.757-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.757-.426-1.757-2.924 0-3.35A1.724 1.724 0 0 0 5.38 7.753c-.94-1.543.826-3.31 2.37-2.37 1 .608 2.296.07 2.573-1.066Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </a>
                    <a
                        href="{{ route('admin.service-archive') }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/12 text-cyan-50/90 shadow-sm shadow-cyan-950/10 ring-1 ring-white/10 transition duration-200 hover:-translate-y-0.5 hover:bg-white/18 hover:text-white focus:outline-none focus:ring-4 focus:ring-white/25 active:translate-y-0 active:scale-[0.98] sm:h-10 sm:w-10"
                        title="بایگانی خدمات"
                        aria-label="بایگانی خدمات"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v2A1.5 1.5 0 0 1 18.5 11h-13A1.5 1.5 0 0 1 4 9.5v-2Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 11v6.5A1.5 1.5 0 0 0 7.5 19h9a1.5 1.5 0 0 0 1.5-1.5V11"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14h4"/>
                        </svg>
                    </a>
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
            @if (session()->has('service-list-success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('service-list-success') }}
                </div>
            @endif

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
                    $detailsPayload = [
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
                    ];
                @endphp

                <article
                    x-data="{ detailsPayload: @js($detailsPayload) }"
                    @click="openDetails(detailsPayload)"
                    class="relative cursor-pointer overflow-hidden rounded-[28px] border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:border-slate-300 hover:shadow-md sm:px-5"
                >
                    <span class="absolute inset-y-0 right-0 w-1 {{ $barClasses[$service->status] ?? 'bg-slate-300' }}" aria-hidden="true"></span>

                    <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap sm:gap-4">
                        <div class="flex shrink-0 flex-col items-start gap-1.5">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold tracking-wide text-slate-600">
                                {{ $service->code }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 self-start rounded-full px-2.5 py-1 text-[11px] font-bold {{ $badgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-60"></span>
                                {{ $statusOptions[$service->status] ?? $service->status }}
                            </span>
                        </div>

                        <div class="w-full min-w-0 sm:order-none sm:w-auto sm:flex-1">
                            <p class="text-[11px] font-medium text-slate-400">نام خدمت</p>
                            <p class="mt-0.5 truncate text-base font-black leading-6 text-slate-800 sm:text-lg sm:leading-7">
                                {{ $service->serviceName?->name ?: '-' }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
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
                                @click.stop="openDetails(detailsPayload)"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 hover:text-slate-800"
                                title="جزئیات"
                                aria-label="جزئیات"
                                >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 6H18m0 0v4.5M18 6l-6 6m-6 6h4.5M6 18v-4.5M6 18l6-6"/>
                                </svg>
                            </button>

                            <button
                                type="button"
                                @click.stop
                                wire:click="openDeleteServiceConfirmation({{ $service->id }})"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                title="حذف خدمت"
                                aria-label="حذف خدمت"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.74 9-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17M18.16 19.67A2.25 2.25 0 0 1 15.92 21H8.08a2.25 2.25 0 0 1-2.24-1.96L4.77 5.79m14.46 0A48.23 48.23 0 0 0 12 5.25c-2.43 0-4.82.18-7.23.54m14.46 0L18.16 19.67M4.77 5.79c.34-.06.68-.11 1.02-.17m0 0L5.25 4.5A2.25 2.25 0 0 1 7.5 2.25h9A2.25 2.25 0 0 1 18.75 4.5l-.54 1.12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            @click.stop="categoryTitle = @js($service->serviceName?->name ?: 'خدمت'); categories = @js($service->categories->map(fn ($category) => [
                                'name' => $category->name,
                                'quantity' => $this->formatQuantityForUnit($category->quantity, (string) $category->unit),
                                'unit' => $unitOptions[$category->unit] ?? ($category->unit ?? '-'),
                            ])->values()); categoriesOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50/80 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            title="مشاهده زیر‌دسته‌ها"
                        >
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h10M4 17h7"/>
                            </svg>
                            <span>{{ $service->categories->count() }} زیردسته</span>
                        </button>

                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50/80 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <span class="font-bold text-slate-700">{{ number_format($service->total_service_value) }}</span>
                            <span>ریال</span>
                        </span>

                        @include('livewire.services.partials.delivery-summary-trigger', [
                            'service' => $service,
                            'unitOptions' => $unitOptions,
                            'label' => 'مددکار',
                            'lazy' => true,
                            'compact' => true,
                        ])

                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50/80 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z"/>
                            </svg>
                            <span>{{ $creatorName }}</span>
                            <span class="text-slate-300">|</span>
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                            <span class="text-slate-500">{{ $createdAt }}</span>
                        </span>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-slate-500">
                    {{ trim($search ?? '') !== '' ? 'هیچ خدمتی برای جستجوی فعلی یافت نشد.' : 'هنوز خدمتی تعریف نشده است.' }}
                </div>
            @endforelse

            @if($services->hasPages())
                <div class="pt-1">
                    {{ $services->links('vendor.livewire.tailwind-mobile-persian') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Subcategories: bottom sheet on phones (same overlay/back behavior as the gate and
         filter sheets), centered dialog on desktop. sheetBackGuard parks the sheet off-screen
         when Android Back is pressed. --}}
    <div
        x-cloak
        x-show="categoriesOpen"
        @click="categoriesOpen = false"
        @touchmove.prevent
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 !mt-0 bg-slate-950/40"
    ></div>

    <div
        x-cloak
        @keydown.escape.window="categoriesOpen = false"
        :class="categoriesOpen
            ? 'translate-y-0 lg:scale-100 lg:opacity-100'
            : 'translate-y-full lg:translate-y-0 lg:scale-95 lg:opacity-0 lg:pointer-events-none'"
        class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85svh] min-h-0 flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl transition duration-300 ease-out lg:inset-0 lg:!m-auto lg:h-fit lg:w-full lg:max-w-lg lg:rounded-[28px] lg:border lg:border-slate-200/80"
    >
        <div class="flex-none bg-gradient-to-l from-slate-50 via-white to-cyan-50 px-4 pb-3.5 pt-3 sm:px-5">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-200 lg:hidden"></div>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="shrink-0 text-base font-black text-slate-900 sm:text-lg">زیر‌دسته‌های خدمت</h3>
                        <span class="shrink-0 rounded-full bg-cyan-100 px-2.5 py-0.5 text-[11px] font-black text-cyan-800 ring-1 ring-cyan-200" x-text="`${categories.length} زیردسته`"></span>
                    </div>
                    <p class="mt-1 truncate text-sm font-semibold text-slate-500" x-text="categoryTitle"></p>
                </div>
                <button
                    type="button"
                    @click="categoriesOpen = false"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="بستن"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-6">
            <template x-if="categories.length">
                <ul class="divide-y divide-slate-100">
                    <template x-for="(category, index) in categories" :key="`${category.name}-${index}`">
                        <li class="flex min-h-12 items-center justify-between gap-3 px-4 py-2.5 sm:px-5">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.698 1.786.576 2.334-.068l3.652-4.33a1.73 1.73 0 0 0 .12-2.081L11.66 3.66A1.73 1.73 0 0 0 10.35 3.08Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6h.008v.008H6V6Z"/>
                                    </svg>
                                </span>
                                <p class="truncate text-sm font-bold text-slate-800 sm:text-[15px]" x-text="category.name"></p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-100/80 px-3 py-1.5 text-xs ring-1 ring-slate-200/80">
                                <span class="font-black text-slate-800" x-text="category.quantity"></span>
                                <span class="font-semibold text-slate-500" x-text="category.unit"></span>
                            </span>
                        </li>
                    </template>
                </ul>
            </template>

            <template x-if="!categories.length">
                <div class="px-4 py-10 text-center">
                    <p class="text-sm font-bold text-slate-600">زیر‌دسته‌ای برای این خدمت ثبت نشده است.</p>
                </div>
            </template>
        </div>
    </div>

    @include('livewire.services.partials.delivery-summary-modal')

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
