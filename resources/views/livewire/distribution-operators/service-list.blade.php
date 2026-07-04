<div class="space-y-4">

    @include('livewire.distribution-operators.partials.latest-misc-service-card')

    <div class="-mt-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-slate-200 px-4 py-4">
            <div class="flex flex-col gap-2">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-black text-slate-800">فهرست خدمات</h2>
                        <span
                            wire:loading.class="opacity-60"
                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500"
                        >
                            {{ number_format($services->total()) }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs font-medium text-slate-500">
                        {{ $activeTab === 'campaigns' ? 'لیست کمپین‌ها' : 'لیست متفرقه' }} - نمایش خدمات اپراتور توزیع
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-1 rounded-2xl border border-slate-200 bg-slate-100 p-1" role="tablist" aria-label="فیلتر نوع خدمات">
                <button
                    type="button"
                    wire:click="switchTab('campaigns')"
                    wire:loading.attr="disabled"
                    role="tab"
                    aria-selected="{{ $activeTab === 'campaigns' ? 'true' : 'false' }}"
                    class="flex h-11 items-center justify-center gap-2 rounded-xl px-3 text-sm font-bold transition {{ $activeTab === 'campaigns' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-white/70 hover:text-slate-800' }}"
                >
                    <span class="truncate">پویش‌ها</span>
                    <span class="shrink-0 rounded-full {{ $activeTab === 'campaigns' ? 'bg-blue-100 text-blue-700' : 'bg-white text-slate-500' }} px-2 py-0.5 text-[11px] font-black leading-5">
                        {{ $counts['campaigns'] ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="switchTab('misc')"
                    wire:loading.attr="disabled"
                    role="tab"
                    aria-selected="{{ $activeTab === 'misc' ? 'true' : 'false' }}"
                    class="flex h-11 items-center justify-center gap-2 rounded-xl px-3 text-sm font-bold transition {{ $activeTab === 'misc' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-white/70 hover:text-slate-800' }}"
                >
                    <span class="truncate">متفرقه</span>
                    <span class="shrink-0 rounded-full {{ $activeTab === 'misc' ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-500' }} px-2 py-0.5 text-[11px] font-black leading-5">
                        {{ $counts['misc'] ?? 0 }}
                    </span>
                </button>
            </div>

            <div
                id="operator-service-catalog"
                class="space-y-2"
                x-data="{
                    open: false,
                    historyStatePushed: false,
                    query: @entangle('search').live,
                    options: @js($catalogSearchOptions),
                    isMobileSheet: window.matchMedia('(max-width: 639px)').matches,
                    init() {
                        this.mediaQuery = window.matchMedia('(max-width: 639px)');
                        this.mediaQuery.addEventListener('change', event => this.isMobileSheet = event.matches);
                        this.handlePopState = () => {
                            if (this.open) {
                                this.historyStatePushed = false;
                                this.close(false);
                            }
                        };
                        window.addEventListener('popstate', this.handlePopState);
                        this.$watch('open', value => {
                            document.documentElement.classList.toggle('overflow-hidden', value && this.isMobileSheet);
                            if (value) {
                                this.pushSheetHistoryState();
                                if (! this.isMobileSheet) {
                                    this.$nextTick(() => this.$refs.searchInput?.focus({ preventScroll: true }));
                                }
                            }
                        });
                    },
                    pushSheetHistoryState() {
                        if (! this.isMobileSheet || this.historyStatePushed) {
                            return;
                        }

                        window.history.pushState({ operatorServiceSearchSheet: true }, '', window.location.href);
                        this.historyStatePushed = true;
                    },
                    close(syncHistory = true) {
                        if (syncHistory && this.historyStatePushed) {
                            this.historyStatePushed = false;
                            window.history.back();
                            return;
                        }

                        this.historyStatePushed = false;
                        this.open = false;
                    },
                    normalized(value) {
                        return String(value || '').toLowerCase().trim();
                    },
                    get filteredOptions() {
                        const term = this.normalized(this.query);

                        if (! term) {
                            return this.options;
                        }

                        return this.options.filter(service => [
                            service.name,
                            service.code,
                            service.categories,
                        ].some(value => this.normalized(value).includes(term)));
                    },
                    select(serviceId) {
                        this.$wire.selectCatalogService(Number(serviceId)).then(() => this.close());
                    },
                }"
                x-on:keydown.escape.window="close()"
                wire:key="operator-service-catalog-{{ $activeTab }}"
            >
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="open = true"
                        :aria-expanded="open.toString()"
                        aria-haspopup="dialog"
                        class="inline-flex h-11 w-14 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50/70 text-slate-500 transition hover:border-slate-300 hover:bg-white hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-100 sm:w-11"
                        aria-label="جستجوی خدمت"
                    >
                        <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" d="m20 20-4.15-4.15" />
                            <circle cx="10.75" cy="10.75" r="6.25" />
                        </svg>
                    </button>

                    <label class="min-w-0 flex-1 sm:max-w-48">
                        <span class="sr-only">مرتب‌سازی</span>
                        <select
                            wire:model.live="sort"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                        >
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div
                    x-cloak
                    x-show="open"
                    x-transition.opacity.duration.150ms
                    @click="close()"
                    class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm sm:hidden"
                    aria-hidden="true"
                ></div>

                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
                    @click.outside="close()"
                    class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85svh] min-h-[48svh] flex-col rounded-t-3xl border border-slate-200 bg-slate-50 p-3 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:absolute sm:inset-auto sm:mt-2 sm:min-h-0 sm:w-96 sm:rounded-2xl sm:bg-white sm:p-2 sm:shadow-xl"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="operator-service-search-title"
                    dir="rtl"
                >
                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-200/70 pb-3 sm:hidden">
                        <div class="min-w-0">
                            <h3 id="operator-service-search-title" class="text-sm font-black text-slate-800">جستجوی خدمت</h3>
                            <p class="mt-0.5 text-xs font-medium text-slate-500">آخرین خدمات همین بخش نمایش داده می‌شود.</p>
                        </div>
                        <button
                            type="button"
                            @click="close()"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200"
                            aria-label="بستن"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5.5 5.5L14.5 14.5M14.5 5.5L5.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <div class="sticky top-0 z-10 bg-slate-50 pb-2 sm:bg-white">
                        <label class="relative block">
                            <span class="sr-only">عبارت جستجوی خدمت</span>
                            <input
                                x-ref="searchInput"
                                type="search"
                                x-model.debounce.120ms="query"
                                placeholder="نام، کد، مددکار یا دسته‌بندی..."
                                autocomplete="off"
                                class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pl-9 text-sm font-semibold text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                            >
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.197 5.197a7.5 7.5 0 0 0 10.606 10.606Z" />
                            </svg>
                        </label>
                    </div>

                    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain pb-[calc(env(safe-area-inset-bottom)+0.5rem)] sm:max-h-72 sm:pb-0">
                        <template x-for="service in filteredOptions" :key="service.id">
                            <button
                                type="button"
                                @click="select(service.id)"
                                class="flex w-full items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-right shadow-sm transition hover:border-blue-200 hover:bg-blue-50/50 focus:outline-none focus:ring-2 focus:ring-blue-100 active:bg-blue-50"
                            >
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        <span class="truncate text-sm font-black text-slate-800" x-text="service.name"></span>
                                        <span x-show="service.code" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="service.code"></span>
                                    </span>
                                    <span class="mt-1 block truncate text-[11px] font-semibold text-slate-500" x-text="service.categories || 'بدون دسته‌بندی ثبت‌شده'"></span>
                                </span>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500" x-text="service.createdAt"></span>
                            </button>
                        </template>

                        <div
                            x-show="filteredOptions.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-xs font-bold text-slate-400"
                        >
                            خدمتی با این جستجو پیدا نشد.
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 text-xs font-bold text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        نمایش {{ number_format($services->firstItem() ?? 0) }} تا {{ number_format($services->lastItem() ?? 0) }} از {{ number_format($services->total()) }} نتیجه
                    </span>
                    @if($hasActiveFilters)
                        <button type="button" wire:click="clearCatalogFilters" class="self-start text-xs font-black text-slate-500 underline-offset-4 transition hover:text-slate-800 hover:underline sm:self-auto">
                            پاک‌کردن فیلترها
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div wire:loading.class="opacity-60" wire:target="switchTab,search,sort,clearCatalogFilters" class="grid gap-4 p-4 transition-opacity md:grid-cols-2 xl:grid-cols-3">
            @forelse($services as $service)
                @php
                    $isMisc = $activeTab === 'misc';
                    $operatorAllocations = $service->workerAllocations
                        ->filter(fn ($a) => (int) $a->assigned_by_user_id === (int) auth()->id())
                        ->groupBy(fn ($a) => (int) $a->social_worker_id)
                        ->map(function ($allocations) {
                            $allocation = $allocations->first();
                            $allocation->allocated_quantity = $allocations->sum(fn ($a) => (float) $a->allocated_quantity);

                            return $allocation;
                        })
                        ->values();
                    $operatorAllocatedQuantity = $operatorAllocations->sum(fn ($a) => (float) $a->allocated_quantity);
                    $operatorAllocationCount = $operatorAllocations->count();
                    $serviceUnitLabel = $unitOptions[$service->service_unit] ?? ($service->service_unit ?? '-');
                    $serviceCategories = $service->categories;
                    $visibleCategoryNames = $serviceCategories->pluck('name')->filter()->take(3)->values();
                    $hiddenCategoryCount = max(0, $serviceCategories->count() - $visibleCategoryNames->count());
                    $totalCapacity = (float) ($serviceCategories->sum(fn ($category) => (float) $category->quantity) ?: $service->total_quantity);
                    $totalAllocatedQuantity = $service->workerAllocations->sum(fn ($allocation) => (float) $allocation->allocated_quantity);
                    $remainingCapacity = max(0, $totalCapacity - $totalAllocatedQuantity);
                    $allocatedPercentage = $totalCapacity > 0
                        ? min(100, round(($totalAllocatedQuantity / $totalCapacity) * 100))
                        : 0;
                    $distributionStartDate = \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d');
                    $serviceStatus = (string) ($service->status ?? '');
                    $statusLabels = [
                        'draft' => 'پیش‌نویس',
                        'approved' => 'آماده توزیع',
                        'in_distribution' => 'در حال توزیع',
                        'completed' => 'تکمیل شده',
                    ];
                    $statusClasses = [
                        'draft' => 'border-slate-200 bg-slate-50 text-slate-600',
                        'approved' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
                        'in_distribution' => 'border-blue-100 bg-blue-50 text-blue-700',
                        'completed' => 'border-green-100 bg-green-50 text-green-700',
                    ];
                    $today = now()->startOfDay();
                    $startDate = $service->distribution_start_date?->copy()->startOfDay();
                    $endDate = $service->distribution_end_date?->copy()->startOfDay();
                    $dateStateLabel = 'بدون بازه';
                    $dateStateClass = 'border-slate-200 bg-slate-50 text-slate-600';

                    if ($endDate && $endDate->lt($today)) {
                        $dateStateLabel = 'پایان‌یافته';
                        $dateStateClass = 'border-rose-100 bg-rose-50 text-rose-700';
                    } elseif ($startDate && $startDate->gt($today)) {
                        $dateStateLabel = 'در انتظار شروع';
                        $dateStateClass = 'border-amber-100 bg-amber-50 text-amber-700';
                    } elseif ($startDate) {
                        $dateStateLabel = 'بازه فعال';
                        $dateStateClass = 'border-emerald-100 bg-emerald-50 text-emerald-700';
                    }
                    $allocationEditUrl = ! $isMisc && $operatorAllocations->isNotEmpty()
                        ? route('distribution-operator.edit-allocations', $service->id)
                        : null;
                    $miscEditUrl = $isMisc
                        ? route('distribution-operator.edit-service', $service->id)
                        : null;
                @endphp

                @if($isMisc)
                    @include('livewire.distribution-operators.partials.misc-service-card')
                @else
                    @include('livewire.distribution-operators.partials.campaign-service-card')
                @endif
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    @if($hasActiveFilters)
                        نتیجه‌ای با این جستجو یا فیلترها پیدا نشد.
                    @elseif($activeTab === 'misc')
                        هنوز خدمت متفرقه‌ای توسط این اپراتور تعریف نشده است.
                    @else
                        هنوز خدمتی از لیست کمپین‌ها به این اپراتور تخصیص داده نشده است.
                    @endif
                </div>
            @endforelse
        </div>

        @if($services->hasPages())
            <div class="border-t border-slate-200 px-4 py-4">
                {{ $services->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#operator-service-catalog']) }}
            </div>
        @endif
    </div>
</div>
