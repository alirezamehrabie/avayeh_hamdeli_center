<div class="space-y-6">

    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-800">فهرست خدمات</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">خدمات بر اساس حالت ثبت، در دو دسته جدا نمایش داده می‌شوند.</p>
                </div>
                <span wire:loading.class="opacity-60" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                    {{ number_format($services->total()) }} خدمت
                </span>
            </div>

            <div class="grid gap-2 rounded-2xl bg-slate-100 p-1 sm:grid-cols-2" role="tablist" aria-label="فیلتر نوع خدمات">
                <button
                    type="button"
                    wire:click="switchTab('campaigns')"
                    wire:loading.attr="disabled"
                    role="tab"
                    aria-selected="{{ $activeTab === 'campaigns' ? 'true' : 'false' }}"
                    class="flex min-h-16 items-center justify-between gap-3 rounded-xl px-4 py-3 text-right transition {{ $activeTab === 'campaigns' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-white/70 hover:text-slate-800' }}"
                >
                    <span>
                        <span class="block text-sm font-black">لیست کمپین‌ها</span>
                        <span class="mt-1 block text-xs font-semibold opacity-75">انتخاب از خدمات/کمپین</span>
                    </span>
                    <span class="shrink-0 rounded-full {{ $activeTab === 'campaigns' ? 'bg-blue-100 text-blue-700' : 'bg-white text-slate-500' }} px-3 py-1 text-xs font-black">
                        {{ $counts['campaigns'] ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="switchTab('misc')"
                    wire:loading.attr="disabled"
                    role="tab"
                    aria-selected="{{ $activeTab === 'misc' ? 'true' : 'false' }}"
                    class="flex min-h-16 items-center justify-between gap-3 rounded-xl px-4 py-3 text-right transition {{ $activeTab === 'misc' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-white/70 hover:text-slate-800' }}"
                >
                    <span>
                        <span class="block text-sm font-black">لیست متفرقه</span>
                        <span class="mt-1 block text-xs font-semibold opacity-75">تعریف انتخاب جدید</span>
                    </span>
                    <span class="shrink-0 rounded-full {{ $activeTab === 'misc' ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-500' }} px-3 py-1 text-xs font-black">
                        {{ $counts['misc'] ?? 0 }}
                    </span>
                </button>
            </div>

            <div id="operator-service-catalog" class="space-y-2">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="relative block sm:w-80 lg:w-96">
                        <span class="sr-only">جستجوی خدمت</span>
                        <input
                            type="search"
                            wire:model.live.debounce.350ms="search"
                            placeholder="جستجوی خدمت..."
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 pl-9 text-sm font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                        >
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.197 5.197a7.5 7.5 0 0 0 10.606 10.606Z" />
                        </svg>
                    </label>

                    <label class="block sm:w-40">
                        <span class="sr-only">مرتب‌سازی</span>
                        <select
                            wire:model.live="sort"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                        >
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
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
                    $isMisc = (int) ($service->created_by ?? 0) === (int) auth()->id();
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
