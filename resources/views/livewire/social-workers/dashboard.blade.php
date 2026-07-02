<div class="space-y-6"
     x-data="{
        quotaEntries: @entangle('recipientEntries'),
        toNumber(value) {
            const normalized = String(value ?? '')
                .replace(/[۰-۹]/g, (char) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(char)))
                .replace(/[٠-٩]/g, (char) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(char)))
                .replace(',', '.');
            const number = parseFloat(normalized);

            return Number.isFinite(number) ? number : 0;
        },
        formatDecimal(value) {
            return Math.max(0, this.toNumber(value)).toFixed(2);
        },
        setCategoryQuantity(rowIndex, categoryId, value) {
            if (!this.quotaEntries[rowIndex]) {
                this.quotaEntries[rowIndex] = {};
            }

            if (!this.quotaEntries[rowIndex].category_quantities) {
                this.quotaEntries[rowIndex].category_quantities = {};
            }

            this.quotaEntries[rowIndex].category_quantities[categoryId] = value;
        },
        categoryQuantity(rowIndex, categoryId) {
            return this.toNumber(this.quotaEntries?.[rowIndex]?.category_quantities?.[categoryId]);
        },
        categoryPending(categoryId) {
            return Object.values(this.quotaEntries ?? {}).reduce((total, entry) => {
                return total + this.toNumber(entry?.category_quantities?.[categoryId]);
            }, 0);
        },
        availableForInput(rowIndex, categoryId, remainingStock, remainingAllocation) {
            const currentQuantity = this.categoryQuantity(rowIndex, categoryId);
            const otherRowsPending = Math.max(0, this.categoryPending(categoryId) - currentQuantity);

            return Math.max(0, Math.min(
                this.toNumber(remainingStock),
                this.toNumber(remainingAllocation) - otherRowsPending
            ));
        },
        exceedsRemainingQuota(rowIndex, categoryId, remainingStock, remainingAllocation) {
            const currentQuantity = this.categoryQuantity(rowIndex, categoryId);

            return currentQuantity > 0
                && currentQuantity > this.availableForInput(rowIndex, categoryId, remainingStock, remainingAllocation);
        },
        persianNumber(value) {
            return String(value ?? '').replace(/[0-9.,]/g, (char) => ({
                '0': '۰',
                '1': '۱',
                '2': '۲',
                '3': '۳',
                '4': '۴',
                '5': '۵',
                '6': '۶',
                '7': '۷',
                '8': '۸',
                '9': '۹',
                '.': '٫',
                ',': '٬',
            })[char] ?? char);
        },
     }"
     x-on:social-worker-delivery-validation-failed.window="
        $nextTick(() => {
            const field = $event.detail?.field;
            const selector = field ? `[data-error-field='${field}']` : null;
            const target = selector ? document.querySelector(selector) : document.querySelector('[data-error-field]');
            const fallback = document.querySelector('.ring-rose-100');
            const element = target || fallback;

            if (!element) {
                return;
            }

            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => {
                if (typeof element.focus === 'function' && !element.disabled) {
                    element.focus({ preventScroll: true });
                }
            }, 350);
        })
     ">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="rounded-t-2xl border-b border-slate-200 bg-white px-4 py-3 sm:px-6 sm:py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg font-black text-slate-900 sm:text-2xl">ثبت تحویل خدمت</h1>
                </div>
            </div>
        </div>

        <div class="px-3 py-4 sm:px-4 sm:py-6">
            <form wire:submit.prevent="saveDelivery"
                  class="space-y-5 {{ $selectedService ? 'pb-20 md:pb-0' : 'pb-0' }}">
                <div class="{{ $selectedService ? 'grid gap-4 xl:gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] xl:items-start' : 'mx-auto grid max-w-2xl gap-4' }}">
                    <div class="space-y-5">
                    <section class="rounded-2xl border {{ $selectedService ? 'border-cyan-100' : 'border-slate-200' }} bg-white shadow-sm shadow-slate-100">
                        <div class="p-4 sm:p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $selectedService ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-cyan-700' }} text-sm font-black">
                                        <i class="bi bi-box-seam"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="text-base font-black text-slate-900">انتخاب خدمت</h2>
                                            @if($selectedService)
                                                <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                                    انتخاب شده
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-black text-slate-500">
                                                    انتخاب نشده
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 text-xs font-medium leading-5 text-slate-500 sm:text-sm">
                                            برای شروع ثبت تحویل، یکی از خدمات تخصیص‌یافته را انتخاب کنید.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 px-4 pb-4 pt-3 sm:px-5 sm:pb-5 sm:pt-4">

                        @php
                            $serviceOptions = $assignedServices->map(function ($service) {
                                $serviceTypeLabel = $service->service_type === 'family' ? 'خانوادگی' : 'شخصی';
                                $serviceCategories = $service->categories?->sortBy('sort_id') ?? collect();
                                $categoryNames = $serviceCategories->pluck('name')->filter()->values();
                                $remainingAllocation = (float) ($service->worker_remaining_allocation ?? 0);

                                return [
                                    'id' => (string) $service->id,
                                    'label' => $this->serviceDropdownLabel($service),
                                    'code' => $this->persianNumber($service->code),
                                    'name' => (string) ($service->serviceName?->name ?? ''),
                                    'type' => $serviceTypeLabel,
                                    'remaining' => $this->persianNumber(number_format($remainingAllocation, 2)),
                                    'remainingRaw' => $remainingAllocation,
                                    'description' => (string) ($service->description ?: ''),
                                    'categories' => $categoryNames->take(4)->all(),
                                    'categorySummary' => $categoryNames->implode('، '),
                                    'search' => mb_strtolower(trim(implode(' ', array_filter([
                                        (string) $service->code,
                                        (string) ($service->serviceName?->name ?? ''),
                                        $serviceTypeLabel,
                                        (string) ($service->description ?? ''),
                                        $categoryNames->implode(' '),
                                    ])))),
                                ];
                            })->values();
                            $serviceOptionsChecksum = md5($serviceOptions->toJson(JSON_UNESCAPED_UNICODE));
                        @endphp

                        <div
                            class="relative"
                            data-service-selector-panel
                            wire:key="social-worker-service-selector-{{ $selectedServiceId ?: 'empty' }}-{{ $serviceOptionsChecksum }}"
                            x-data="{
                                open: false,
                                query: '',
                                selected: @entangle('selectedServiceId').live,
                                services: @js($serviceOptions),
                                historyActive: false,
                                get isMobileSheet() {
                                    return window.matchMedia('(max-width: 639px)').matches;
                                },
                                get selectedId() {
                                    return this.selected === null || this.selected === undefined || this.selected === ''
                                        ? ''
                                        : String(this.selected);
                                },
                                get selectedService() {
                                    return this.services.find((service) => service.id === this.selectedId) || null;
                                },
                                get selectedText() {
                                    return this.selectedService?.label || 'انتخاب خدمت';
                                },
                                get filteredServices() {
                                    const value = this.query.trim().toLocaleLowerCase();

                                    if (value === '') {
                                        return this.services;
                                    }

                                    return this.services.filter((service) => service.search.includes(value));
                                },
                                openSelector() {
                                    this.open = true;
                                    this.pushSelectorHistory();
                                    if (!this.isMobileSheet) {
                                        this.$nextTick(() => this.$refs.serviceSearch?.focus());
                                    }
                                },
                                closeSelector({ syncHistory = true } = {}) {
                                    const shouldRestoreHistory = syncHistory && this.historyActive;

                                    this.open = false;

                                    if (shouldRestoreHistory) {
                                        this.historyActive = false;
                                        window.history.back();
                                    } else if (!syncHistory) {
                                        this.historyActive = false;
                                    }
                                },
                                toggleSelector() {
                                    this.open ? this.closeSelector() : this.openSelector();
                                },
                                pushSelectorHistory() {
                                    if (!this.isMobileSheet || this.historyActive) {
                                        return;
                                    }

                                    try {
                                        window.history.pushState({
                                            ...(window.history.state || {}),
                                            socialWorkerServiceSelector: true,
                                        }, '', window.location.href);
                                        this.historyActive = true;
                                    } catch (error) {
                                        this.historyActive = false;
                                    }
                                },
                                handleSelectorPopState() {
                                    if (!this.open) {
                                        this.historyActive = false;
                                        return;
                                    }

                                    this.closeSelector({ syncHistory: false });
                                },
                                choose(service) {
                                    this.selected = Number(service.id);
                                    this.query = '';
                                    this.closeSelector();
                                },
                                clear() {
                                    this.selected = null;
                                    this.query = '';
                                    this.closeSelector();
                                },
                            }"
                            x-init="window.addEventListener('popstate', () => handleSelectorPopState()); $watch('open', (value) => {
                                document.documentElement.classList.toggle('overflow-hidden', value && isMobileSheet);
                            })"
                            x-on:keydown.escape.window="closeSelector()"
                        >
                            {{-- Trigger Button --}}
                            <button
                                type="button"
                                data-service-selector-trigger
                                @click="toggleSelector()"
                                :aria-expanded="open.toString()"
                                aria-haspopup="dialog"
                                class="flex w-full items-center justify-between rounded-xl border border-slate-300
                                   bg-white px-3 py-2.5 text-right text-sm font-bold text-slate-700 shadow-sm
                                   transition hover:border-cyan-300 hover:bg-cyan-50/50 hover:text-cyan-800 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/10 active:scale-[0.98]"
                            >
                                <span class="truncate" x-text="selectedText"></span>

                                <svg
                                    class="ms-2 h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div
                                x-show="open"
                                x-transition.opacity.duration.150ms
                                @click="closeSelector()"
                                class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm sm:hidden"
                                style="display: none;"
                                aria-hidden="true"
                            ></div>

                            {{-- Mobile Bottom Sheet / Desktop Dropdown Panel --}}
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
                                @click.outside="closeSelector()"
                                class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85svh] flex-col rounded-t-3xl border border-slate-200 bg-slate-50 p-3 shadow-[0_-18px_45px_rgba(15,23,42,0.22)]
                                sm:absolute sm:inset-auto sm:mt-2 sm:max-h-72 sm:w-full sm:overflow-y-auto sm:overscroll-contain sm:rounded-2xl sm:p-2 sm:shadow-xl"
                                dir="rtl"
                                role="dialog"
                                aria-modal="true"
                                style="display: none;"
                            >
                                <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-200/70 pb-3 sm:hidden">
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-black text-slate-800">انتخاب خدمت</h3>
                                    </div>
                                    <button
                                        type="button"
                                        @click="closeSelector()"
                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200"
                                        aria-label="بستن"
                                    >
                                        <i class="bi bi-x-lg text-sm"></i>
                                    </button>
                                </div>
                                {{-- Default Option --}}
                                <div class="sticky top-0 z-10 bg-slate-50 pb-2">
                                    <div class="relative">
                                        <input
                                            type="search"
                                            x-ref="serviceSearch"
                                            x-model.debounce.100ms="query"
                                            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/10"
                                            placeholder="جستجو بر اساس نام، کد، نوع یا دسته‌بندی"
                                            autocomplete="off"
                                        >
                                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"/>
                                        </svg>
                                    </div>
                                </div>

                                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-0.5 sm:overflow-visible sm:pr-0">
                                {{-- Service Options --}}
                                <template x-for="service in filteredServices" :key="service.id">
                                    <button
                                        type="button"
                                        @click="choose(service)"
                                        :class="{ 'border-cyan-200 bg-cyan-50/80 shadow-cyan-100/70': selectedId === service.id }"
                                        class="mb-2 flex min-h-[4.75rem] w-full flex-col gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-right shadow-sm shadow-slate-200/70 transition hover:border-cyan-200 hover:bg-cyan-50/50 active:bg-cyan-50 sm:mb-2 sm:min-h-0 sm:px-4 sm:py-3"
                                        dir="rtl"
                                        role="option"
                                        :aria-selected="(selectedId === service.id).toString()"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                                    <span class="text-[10px] font-medium text-slate-400" x-text="service.code"></span>
                                                    <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 sm:px-2"
                                                          x-text="service.type"></span>
                                                </div>
                                                <p class="mt-1 line-clamp-2 text-[13px] font-extrabold leading-5 text-slate-800 sm:truncate sm:text-sm"
                                                   x-text="service.name"></p>
                                            </div>

                                            <span class="inline-flex shrink-0 flex-col items-center rounded-xl border px-2.5 py-1 text-center"
                                                  :class="service.remainingRaw > 0 ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-rose-100 bg-rose-50 text-rose-600'">
                                                <span class="text-[9px] font-bold leading-none">باقی‌مانده</span>
                                                <span class="mt-1 text-xs font-black leading-none" x-text="service.remaining"></span>
                                            </span>
                                        </div>

                                        <p class="line-clamp-2 text-[11px] leading-4 text-slate-500 sm:truncate"
                                           x-text="service.categorySummary || 'دسته‌بندی برای این خدمت ثبت نشده است.'">
                                        </p>
                                    </button>
                                </template>

                                <div
                                    x-show="filteredServices.length === 0"
                                    class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-4 text-center text-xs font-bold text-slate-400"
                                    style="display: none;"
                                >
                                    خدمتی با این جستجو پیدا نشد.
                                </div>
                            </div>
                            </div>
                        </div>

                        {{-- Hidden Input for wire:model --}}
                        <input type="hidden" wire:model="selectedServiceId">

                        {{-- Validation Error --}}
                        @error('selectedServiceId')
                        <p class="mt-2 flex items-center gap-1 text-sm text-rose-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $message }}
                        </p>
                        @enderror
                        </div>

                    </section>

                    @if($selectedService)
                    <section
                        class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-100"
                        x-data="{ quotaSummaryOpen: false }"
                    >
                        @php
                            $formatQuotaValue = static function ($value): string {
                                $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

                                return $formatted === '-0' || $formatted === '' ? '0' : $formatted;
                            };
                        @endphp

                        <button
                            type="button"
                            class="group flex w-full flex-col gap-2 rounded-2xl p-3 text-right transition duration-200 hover:bg-slate-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-200 sm:p-4"
                            x-on:click="quotaSummaryOpen = !quotaSummaryOpen"
                            x-bind:aria-expanded="quotaSummaryOpen.toString()"
                            aria-controls="service-quota-summary-details"
                        >
                        <div class="flex w-full items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-black text-slate-600">
                                    <i class="bi bi-box-seam"></i>
                                </span>
                                <div class="min-w-0">
                                    <h2 class="text-sm font-extrabold text-slate-800">خلاصه سهمیه خدمت</h2>
                                    @if($selectedService)
                                        <p class="mt-1 truncate text-xs font-bold leading-5 text-slate-500">
                                            {{ $selectedService->serviceName?->name }}
                                        </p>
                                        <p class="mt-0.5 text-[10px] font-bold leading-4 text-slate-400" dir="ltr">
                                            <span class="text-emerald-700">
                                                {{ $this->persianNumber($formatQuotaValue($selectedServiceTotals['remaining'] ?? 0)) }}
                                            </span>
                                            <span class="px-0.5">/</span>
                                            <span>{{ $this->persianNumber($formatQuotaValue($selectedServiceTotals['allocated'] ?? 0)) }}</span>
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs font-bold leading-5 text-slate-400">
                                            ابتدا خدمت را انتخاب کنید.
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                            @if($selectedService)
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-500 shadow-sm">
                                    {{ $this->selectedServiceTypeLabel }}
                                </span>
                            @endif
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition duration-200 group-hover:border-cyan-100 group-hover:text-cyan-700">
                                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" x-bind:class="{ 'rotate-180': quotaSummaryOpen }"></i>
                                </span>
                            </div>
                        </div>

                        </button>

                        @if($selectedService)
                            <div
                                id="service-quota-summary-details"
                                class="overflow-hidden border-t border-slate-100"
                                x-show="quotaSummaryOpen"
                                x-collapse.duration.250ms
                                x-cloak
                            >
                                <div class="p-3 pt-2 sm:p-4 sm:pt-3">

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <p class="min-w-0 text-sm font-bold text-slate-700 sm:text-base">
                                    <span class="block truncate">{{ $selectedService->serviceName?->name }}</span>
                                </p>
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold text-slate-500">
                                    {{ $this->persianNumber($selectedService->code) }}
                                </span>
                            </div>

                            @if($selectedService->description)
                                <p class="mt-2 line-clamp-1 text-xs leading-5 text-slate-500">
                                    {{ $selectedService->description }}
                                </p>
                            @endif

                            @php
                                $serviceCategories = $selectedService->categories?->sortBy('sort_id') ?? collect();
                            @endphp

                            <div class="mt-2.5 grid gap-2 sm:grid-cols-2">
                                @forelse($serviceCategories as $category)
                                        @php
                                            $metrics = $categoryMetrics[$category->id] ?? [
                                                'allocated' => 0,
                                                'worker_delivered' => 0,
                                                'remaining_allocation' => 0,
                                            ];
                                            $allocated = (float) $metrics['allocated'];
                                            $delivered = (float) $metrics['worker_delivered'];
                                            $remaining = (float) $metrics['remaining_allocation'];
                                            $progress = $allocated > 0 ? min(100, max(0, ($delivered / $allocated) * 100)) : 0;
                                            $quotaState = match (true) {
                                                $remaining <= 0 => [
                                                    'label' => 'اتمام سهمیه',
                                                    'badge' => 'border-rose-200 bg-rose-50 text-rose-700',
                                                    'value' => 'text-rose-700',
                                                    'track' => 'bg-rose-100',
                                                    'fill' => 'bg-rose-500',
                                                ],
                                                $remaining / max($allocated, 1) <= 0.25 => [
                                                    'label' => 'رو به اتمام',
                                                    'badge' => 'border-amber-200 bg-amber-50 text-amber-700',
                                                    'value' => 'text-amber-700',
                                                    'track' => 'bg-amber-100',
                                                    'fill' => 'bg-amber-500',
                                                ],
                                                default => [
                                                    'label' => 'قابل تحویل',
                                                    'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                                    'value' => 'text-emerald-700',
                                                    'track' => 'bg-emerald-100',
                                                    'fill' => 'bg-emerald-500',
                                                ],
                                            };
                                            $unitLabel = \App\Models\Service::unitOptions()[$category->unit] ?? $category->unit;
                                        @endphp

                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm shadow-slate-100/50">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-extrabold leading-5 text-slate-800">
                                                    {{ $category->name }}
                                                </p>
                                                <p class="mt-0.5 text-[10px] font-bold leading-4 text-slate-400">
                                                    {{ $unitLabel }}
                                                </p>
                                            </div>

                                            <span class="inline-flex shrink-0 items-center rounded-lg border px-2 py-0.5 text-[9px] font-bold leading-4 {{ $quotaState['badge'] }}">
                                                {{ $quotaState['label'] }}
                                            </span>
                                        </div>

                                        <div class="mt-1.5 flex items-end justify-between gap-3">
                                            <div class="min-w-0">
                                                <span class="block text-[9px] font-bold leading-4 text-slate-400">باقی‌مانده</span>
                                                <span class="block truncate text-base font-extrabold leading-6 {{ $quotaState['value'] }}">
                                                    {{ $this->persianNumber($formatQuotaValue($remaining)) }}
                                                    <span class="text-[10px] font-bold text-slate-400">{{ $unitLabel }}</span>
                                                </span>
                                            </div>

                                            <div class="shrink-0 text-left text-[10px] font-bold leading-4 text-slate-500">
                                                <span class="block">تحویل {{ $this->persianNumber($formatQuotaValue($delivered)) }}</span>
                                                <span class="block text-slate-400">از {{ $this->persianNumber($formatQuotaValue($allocated)) }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-2 flex items-center gap-2">
                                            <div class="h-1.5 min-w-0 flex-1 rounded-full {{ $quotaState['track'] }}">
                                                <div
                                                    class="h-1.5 rounded-full {{ $quotaState['fill'] }}"
                                                    style="width: {{ $progress }}%"
                                                ></div>
                                            </div>
                                            <span class="shrink-0 text-[9px] font-bold text-slate-400" dir="ltr">{{ $this->persianNumber(number_format($progress, 0)) }}%</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-center">
                                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                            <i class="bi bi-inbox text-lg"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-bold text-slate-700">
                                            برای این خدمت سهمیه قابل تحویل ثبت نشده است.
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            سهمیه‌ای ثبت نشده است.
                                        </p>
                                    </div>
                                @endforelse
                            </div>



                                </div>
                            </div>
                        @else
                        @endif

                    </section>
                    @endif

                    @if($serviceSelectionWarning !== '')
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                            {{ $serviceSelectionWarning }}
                        </div>
                    @endif


                    @if($selectedService)
                    <section class="rounded-2xl border border-cyan-100 bg-white p-3 shadow-sm shadow-slate-100 sm:p-4">
                        <!-- Header Section -->
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $selectedService ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-400' }} text-sm font-black">۲</span>
                                <div class="min-w-0">
                                @php
                                    $recipientSectionTitle = match ($selectedService?->service_type) {
                                        'family' => 'خانواده‌های دریافت‌کننده خدمت',
                                        'individual' => 'مددجویان دریافت‌کننده خدمت',
                                        default => 'گیرندگان خدمت',
                                    };
                                @endphp

                                <h2 class="text-base font-extrabold text-slate-800 md:text-lg">{{ $recipientSectionTitle }}</h2>
                                </div>
                            </div>
                            @if($selectedService)
                                <button type="button" wire:click="addRecipientField"
                                        class="hidden min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-cyan-700 bg-cyan-600 px-4 text-sm font-black text-white shadow-sm shadow-cyan-200 transition hover:border-cyan-600 hover:bg-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 active:scale-[0.98] md:inline-flex">
                                    <i class="bi bi-plus-lg text-base"></i>
                                    <span>افزودن گیرنده</span>
                                </button>
                            @endif
                        </div>

                        @if(!$selectedService)
                            <div class="rounded-2xl border border-dashed border-cyan-200 bg-cyan-50/60 px-4 py-5 text-center">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-cyan-600 shadow-sm">
                                    <i class="bi bi-list-check text-lg"></i>
                                </div>
                                <h3 class="mt-3 text-sm font-extrabold text-slate-800">ابتدا خدمت را انتخاب کنید</h3>
                                <button
                                    type="button"
                                    wire:click="requireServiceSelection"
                                    x-data
                                    x-on:click="$nextTick(() => {
                                        const panel = document.querySelector('[data-service-selector-panel]');
                                        const trigger = document.querySelector('[data-service-selector-trigger]');
                                        panel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        window.setTimeout(() => trigger?.focus({ preventScroll: true }), 350);
                                    })"
                                    class="mt-4 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-cyan-200 bg-white px-4 text-xs font-black text-cyan-700 shadow-sm transition hover:bg-cyan-50 active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-cyan-500/15"
                                >
                                    <i class="bi bi-arrow-up-short text-base"></i>
                                    انتخاب خدمت
                                </button>
                            </div>
                        @else
                        <!-- Recipients List -->
                        <div class="space-y-3">
                            @foreach($recipientEntries as $index => $entry)
                                    @php
                                        $rowCategoryQuantitiesForHeader = collect($entry['category_quantities'] ?? []);
                                        $rowEnteredCategoryCountForHeader = $rowCategoryQuantitiesForHeader
                                            ->filter(fn ($quantity) => (float) $quantity > 0)
                                            ->count();
                                        $rowTotalQuantityForHeader = $rowCategoryQuantitiesForHeader
                                            ->sum(fn ($quantity) => (float) $quantity);
                                        $recipientDisplayName = trim((string) ($entry['resolved_name'] ?: $entry['full_name'] ?? ''));
                                        $recipientNationalId = trim((string) ($entry['national_id'] ?? ''));
                                        $isUnregisteredRecipient = (bool) ($entry['is_unregistered'] ?? false);
                                        $familyMemberCount = $selectedService?->service_type === 'family'
                                            && array_key_exists('family_members_count', $entry)
                                            && $entry['family_members_count'] !== null
                                                ? (int) $entry['family_members_count']
                                                : null;
                                        $recipientStatus = match (true) {
                                            $recipientDisplayName !== '' && $isUnregisteredRecipient => [
                                                'label' => 'ثبت دستی',
                                                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                                            ],
                                            $recipientDisplayName !== '' => [
                                                'label' => 'شناسایی شده',
                                                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                            ],
                                            $recipientNationalId !== '' && $isUnregisteredRecipient => [
                                                'label' => 'نیاز به تکمیل',
                                                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                                            ],
                                            $recipientNationalId !== '' => [
                                                'label' => 'در حال جستجو',
                                                'class' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
                                            ],
                                            default => [
                                                'label' => 'خالی',
                                                'class' => 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                        };
                                        $rowErrorPrefix = 'recipientEntries.' . $index . '.';
                                        $rowErrors = collect($errors->getMessages())
                                            ->filter(fn ($messages, $key) => $key === 'recipientEntries' || str_starts_with($key, $rowErrorPrefix))
                                            ->flatMap(fn ($messages) => $messages)
                                            ->values();
                                        $hasRowErrors = $rowErrors->isNotEmpty();
                                        $rowHasEnteredData = $recipientDisplayName !== ''
                                            || $recipientNationalId !== ''
                                            || filled($entry['mobile'] ?? '')
                                            || $rowEnteredCategoryCountForHeader > 0
                                            || filled($entry['qr_token'] ?? '');
                                        $rowNeedsInput = $recipientDisplayName === '' || $rowEnteredCategoryCountForHeader === 0;
                                        $recipientEditorOpen = $hasRowErrors || $rowNeedsInput || $loop->last;
                                    @endphp

                                <article class="relative overflow-hidden rounded-2xl border bg-white shadow-sm transition-all {{ $hasRowErrors ? 'border-rose-300 ring-4 ring-rose-100' : 'border-slate-200' }}">
                                    <div class="h-1 w-full {{ $hasRowErrors ? 'bg-rose-200' : ($recipientEditorOpen ? 'bg-cyan-500/70' : 'bg-slate-200') }}"></div>
                                    <div class="p-3 sm:p-4">
                                    <div class="mb-3 border-b border-slate-100 pb-1 md:mb-4 md:pb-4">
                                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-sm font-extrabold text-slate-900">
                                                        گیرنده {{ $this->persianNumber($index + 1) }}
                                                    </h3>
                                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-bold {{ $recipientStatus['class'] }}">
                                                        {{ $recipientStatus['label'] }}
                                                    </span>
                                                    @if($hasRowErrors)
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[10px] font-black text-rose-700">
                                                            <i class="bi bi-exclamation-circle text-xs"></i>
                                                            نیاز به اصلاح
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($recipientDisplayName === '')
                                                    <div class="mt-2">
                                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                                            هنوز ثبت نشده
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-2 md:w-44">
                                                @if(count($recipientEntries) > 1)
                                                    <button type="button"
                                                            wire:click="removeRecipientField({{ $index }})"
                                                            @if($rowHasEnteredData)
                                                                wire:confirm="اطلاعات واردشده برای این گیرنده حذف می‌شود. ادامه می‌دهید؟"
                                                            @endif
                                                            class="inline-flex min-h-9 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-rose-600 transition hover:border-rose-200 hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                                        <i class="bi bi-trash3 text-sm"></i>
                                                        حذف گیرنده
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                    </div>

                                    <details class="group" @if($recipientEditorOpen) open @endif>
                                        <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right transition hover:border-slate-300 hover:bg-slate-100/70 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                            <div class="min-w-0">
                                                <span class="block text-xs font-extrabold text-slate-700">
                                                    {{ $recipientEditorOpen ? 'تکمیل گیرنده و مقدار' : 'ویرایش گیرنده و مقادیر' }}
                                                </span>
                                                <span class="mt-0.5 block truncate text-[10px] font-bold text-slate-400">
                                                    @if($recipientDisplayName !== '' && $rowEnteredCategoryCountForHeader > 0)
                                                        {{ $recipientDisplayName }} / {{ $this->persianNumber($rowEnteredCategoryCountForHeader) }} دسته / جمع {{ $this->persianNumber(number_format($rowTotalQuantityForHeader, 2)) }}
                                                    @elseif($recipientDisplayName !== '')
                                                        مقدار تحویل هنوز وارد نشده است.
                                                    @else
                                                        گیرنده هنوز شناسایی نشده است.
                                                    @endif
                                                </span>
                                            </div>
                                            <span class="inline-flex shrink-0 items-center gap-2 text-[10px] font-bold text-slate-500 group-open:text-cyan-700">
                                                <span class="hidden sm:inline">جزئیات</span>
                                                <i class="bi bi-chevron-down text-xs text-slate-400 transition group-open:rotate-180"></i>
                                            </span>
                                        </summary>

                                    <div class="mt-3 space-y-3 md:space-y-4">
                                        <div class="border-b border-slate-100 pb-3">
                                            <div class="mb-3 flex items-center gap-2">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600 group-open:bg-cyan-600 group-open:text-white">۱</span>
                                                <h3 class="text-xs font-extrabold text-slate-700">شناسایی گیرنده</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                                                <!-- Recipient Search Input -->
                                                <div class="md:col-span-12">
                                                    @php
                                                        $recipientSuggestionItems = collect($this->recipientSuggestions[$index] ?? []);
                                                        $recipientSuggestionType = $this->selectedService?->service_type === 'family' ? 'guardian' : 'person';
                                                        $recipientSuggestionTypeLabel = $recipientSuggestionType === 'guardian' ? 'سرپرست' : 'مددجو';
                                                        $recipientSearchTitle = $recipientSuggestionType === 'guardian'
                                                            ? 'جستجوی سرپرست / خانواده'
                                                            : 'جستجوی مددجو';
                                                        $recipientSearchPlaceholder = $recipientSuggestionType === 'guardian'
                                                            ? 'نام یا کد ملی سرپرست خانواده'
                                                            : 'نام یا کد ملی مددجو';
                                                        $recipientServiceTypeChip = $recipientSuggestionType === 'guardian'
                                                            ? 'خدمت خانوادگی'
                                                            : 'خدمت شخصی';
                                                        $recipientNationalDigits = preg_replace('/\D+/', '', $recipientNationalId) ?? '';
                                                        $showManualRecipientNotice = $isUnregisteredRecipient
                                                            && strlen($recipientNationalDigits) === 10
                                                            && $recipientSuggestionItems->isEmpty()
                                                            && $this->activeRecipientSearchIndex === $index;
                                                        // Field-level search outcome shown as a chip on the input
                                                        // itself, so the worker always knows which of the input's two
                                                        // modes (name lookup vs. 10-digit auto-resolve) is in effect.
                                                        $recipientFieldSearchState = match (true) {
                                                            $recipientDisplayName !== '' && ! $isUnregisteredRecipient => [
                                                                'label' => 'شناسایی شد',
                                                                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                                                'icon' => 'bi-check-circle-fill',
                                                            ],
                                                            $isUnregisteredRecipient => [
                                                                'label' => 'یافت نشد',
                                                                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                                                                'icon' => 'bi-exclamation-circle-fill',
                                                            ],
                                                            $recipientNationalId !== '' => [
                                                                'label' => 'در حال جستجو…',
                                                                'class' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
                                                                'icon' => 'bi-search',
                                                            ],
                                                            default => null,
                                                        };
                                                    @endphp
                                                    <div
                                                        class="rounded-2xl border p-2.5 transition {{ $recipientDisplayName === '' && $recipientNationalId === '' ? 'border-cyan-300 bg-cyan-50/70' : 'border-slate-200 bg-slate-50' }}"
                                                        x-data="{
                                                            recipientSheetOpen: false,
                                                            recipientSheetHistoryActive: false,
                                                            get isMobileRecipientSheet() {
                                                                return window.matchMedia('(max-width: 639px)').matches;
                                                            },
                                                            openRecipientSheet() {
                                                                if (!this.isMobileRecipientSheet) {
                                                                    return;
                                                                }

                                                                this.recipientSheetOpen = true;
                                                                this.pushRecipientSheetHistory();
                                                                this.$nextTick(() => this.$refs.recipientSheetSearch?.focus());
                                                            },
                                                            closeRecipientSheet({ syncHistory = true } = {}) {
                                                                const shouldRestoreHistory = syncHistory && this.recipientSheetHistoryActive;

                                                                this.recipientSheetOpen = false;

                                                                if (shouldRestoreHistory) {
                                                                    this.recipientSheetHistoryActive = false;
                                                                    window.history.back();
                                                                } else if (!syncHistory) {
                                                                    this.recipientSheetHistoryActive = false;
                                                                }
                                                            },
                                                            pushRecipientSheetHistory() {
                                                                if (!this.isMobileRecipientSheet || this.recipientSheetHistoryActive) {
                                                                    return;
                                                                }

                                                                try {
                                                                    window.history.pushState({
                                                                        ...(window.history.state || {}),
                                                                        socialWorkerRecipientSearch: {{ $index }},
                                                                    }, '', window.location.href);
                                                                    this.recipientSheetHistoryActive = true;
                                                                } catch (error) {
                                                                    this.recipientSheetHistoryActive = false;
                                                                }
                                                            },
                                                            handleRecipientSheetPopState() {
                                                                if (!this.recipientSheetOpen) {
                                                                    this.recipientSheetHistoryActive = false;
                                                                    return;
                                                                }

                                                                this.closeRecipientSheet({ syncHistory: false });
                                                            },
                                                            recipientSuggestionButtons() {
                                                                return Array.from(this.$el.querySelectorAll('[data-recipient-suggestion]'))
                                                                    .filter((button) => button.offsetParent !== null);
                                                            },
                                                            focusRecipientSuggestion(direction, event) {
                                                                const items = this.recipientSuggestionButtons();

                                                                if (items.length === 0) {
                                                                    return;
                                                                }

                                                                event?.preventDefault();

                                                                const current = items.indexOf(document.activeElement);
                                                                let next = current === -1
                                                                    ? (direction > 0 ? 0 : items.length - 1)
                                                                    : current + direction;

                                                                if (next < 0) {
                                                                    next = items.length - 1;
                                                                }

                                                                if (next >= items.length) {
                                                                    next = 0;
                                                                }

                                                                items[next].focus();
                                                            },
                                                            chooseRecipientSuggestionFromInput() {
                                                                const items = this.recipientSuggestionButtons();

                                                                if (items.length === 0) {
                                                                    return;
                                                                }

                                                                items[0].click();
                                                            },
                                                        }"
                                                        x-init="window.addEventListener('popstate', () => handleRecipientSheetPopState()); $watch('recipientSheetOpen', (value) => {
                                                            document.documentElement.classList.toggle('overflow-hidden', value && isMobileRecipientSheet);
                                                        })"
                                                        x-on:keydown.escape.window="closeRecipientSheet()"
                                                        x-on:keydown.arrow-down="focusRecipientSuggestion(1, $event)"
                                                        x-on:keydown.arrow-up="focusRecipientSuggestion(-1, $event)"
                                                    >
                                                        <div class="mb-2 flex items-center justify-between gap-2">
                                                            <label class="inline-flex min-w-0 items-center gap-2 text-xs font-black text-slate-700">
                                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-cyan-700 shadow-sm">
                                                                    <i class="bi bi-person-plus text-sm"></i>
                                                                </span>
                                                                <span class="truncate">{{ $recipientSearchTitle }}</span>
                                                            </label>
                                                            <div class="flex shrink-0 items-center gap-1.5">
                                                                <span class="hidden rounded-full border border-cyan-100 bg-white px-2 py-0.5 text-[10px] font-black text-cyan-700 sm:inline">
                                                                    {{ $recipientServiceTypeChip }}
                                                                </span>
                                                                @if($recipientFieldSearchState)
                                                                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-black {{ $recipientFieldSearchState['class'] }}">
                                                                        <i class="bi {{ $recipientFieldSearchState['icon'] }} text-[10px]"></i>
                                                                        {{ $recipientFieldSearchState['label'] }}
                                                                    </span>
                                                                @else
                                                                    <span class="hidden rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-400 sm:inline">جستجو</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="relative">
                                                        <input
                                                            type="text"
                                                            data-error-field="recipientEntries.{{ $index }}.national_id"
                                                            wire:model.live.debounce.300ms="recipientEntries.{{ $index }}.national_id"
                                                            wire:focus="setActiveRecipientSearch({{ $index }})"
                                                            x-on:focus="openRecipientSheet()"
                                                            x-on:click="openRecipientSheet()"
                                                            x-on:keydown.enter.prevent="chooseRecipientSuggestionFromInput()"
                                                            @disabled(!$this->selectedService)
                                                            class="h-12 w-full rounded-xl border border-cyan-300 bg-white py-2.5 pl-12 pr-10 text-sm font-bold text-slate-800 transition-all placeholder:text-xs placeholder:font-normal placeholder:text-slate-400 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10"
                                                            placeholder="{{ $recipientSearchPlaceholder }}"
                                                            autocomplete="off"
                                                        >
                                                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-cyan-600">
                                                            <span wire:loading.remove wire:target="recipientEntries.{{ $index }}.national_id">
                                                                <i class="bi bi-search text-base"></i>
                                                            </span>
                                                            <span wire:loading.flex wire:target="recipientEntries.{{ $index }}.national_id" class="hidden">
                                                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-cyan-200 border-t-cyan-600" aria-hidden="true"></span>
                                                                <span class="sr-only">در حال جستجو</span>
                                                            </span>
                                                        </span>
                                                        <button
                                                            type="button"
                                                            x-on:click.prevent="$dispatch('open-recipient-qr-scanner', { index: {{ $index }} })"
                                                            @disabled(!$this->selectedService)
                                                            class="absolute left-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200/80 disabled:cursor-not-allowed disabled:opacity-50"
                                                            title="اسکن QR"
                                                            aria-label="اسکن QR"
                                                        >
                                                            <i class="bi bi-qr-code-scan text-base"></i>
                                                        </button>
                                                        </div>

                                                        @if($recipientDisplayName === '' && $recipientNationalId === '')
                                                            <p class="mt-1.5 flex items-center gap-1 px-0.5 text-[10px] font-medium leading-4 text-slate-400">
                                                                <i class="bi bi-info-circle text-[10px] text-slate-300"></i>
                                                                نام را تایپ کنید یا کد ملی ۱۰ رقمی را کامل وارد کنید.
                                                            </p>
                                                        @endif

                                                        {{-- Selected recipient confirmation (compact, subtle) --}}
                                                        @if($recipientDisplayName !== '' || $recipientNationalId !== '')
                                                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 px-0.5 text-[11px] leading-4">
                                                                <span class="inline-flex items-center gap-1 font-bold {{ $recipientDisplayName !== '' ? 'text-emerald-600' : 'text-slate-400' }}">
                                                                    <i class="bi bi-check-circle-fill text-[10px]"></i>
                                                                    <span>گیرنده:</span>
                                                                </span>
                                                                @if($recipientDisplayName !== '')
                                                                    <span class="min-w-0 truncate font-extrabold text-slate-700">{{ $recipientDisplayName }}</span>
                                                                @else
                                                                    <span class="font-bold text-slate-400">در حال شناسایی…</span>
                                                                @endif
                                                                @if($familyMemberCount !== null)
                                                                    <span class="text-slate-300">·</span>
                                                                    <span class="font-bold text-slate-500">{{ $this->persianNumber($familyMemberCount) }} خانوار</span>
                                                                @endif
                                                                @if($recipientNationalId !== '')
                                                                    <span class="text-slate-300">·</span>
                                                                    <span class="font-bold text-slate-500" dir="ltr">{{ $this->persianNumber($recipientNationalId) }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        @if($recipientSuggestionItems->isNotEmpty() && $this->activeRecipientSearchIndex === $index)
                                                            <div class="absolute z-20 mt-1 hidden max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5 sm:block">
                                                                @foreach($recipientSuggestionItems as $suggestion)
                                                                    <button type="button"
                                                                            data-recipient-suggestion
                                                                            wire:click="selectRecipientSuggestion({{ $index }}, '{{ $recipientSuggestionType }}', {{ $suggestion->id }})"
                                                                    class="flex min-h-16 w-full items-center justify-between gap-3 border-b border-slate-50 px-4 py-3 text-right transition hover:bg-cyan-50/70 focus:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-cyan-400 last:border-b-0"
                                                                    >
                                            <span class="block">
                                                <span class="block text-sm font-bold text-slate-800">{{ trim(($suggestion->first_name ?? '') . ' ' . ($suggestion->last_name ?? '')) ?: '-' }}</span>
                                                <span class="text-[10px] text-slate-400">{{ $recipientSuggestionTypeLabel }}</span>
                                            </span>
                                                                        <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-mono font-bold text-slate-600">
                                                {{ $recipientSuggestionType === 'guardian' ? $suggestion->national_code : $suggestion->national_id }}
                                            </span>
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        <div
                                                            x-show="recipientSheetOpen"
                                                            x-transition.opacity.duration.150ms
                                                            @click="closeRecipientSheet()"
                                                            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm sm:hidden"
                                                            style="display: none;"
                                                            aria-hidden="true"
                                                        ></div>
                                                        <div
                                                            x-show="recipientSheetOpen"
                                                            x-transition:enter="transition ease-out duration-200"
                                                            x-transition:enter-start="translate-y-full opacity-80"
                                                            x-transition:enter-end="translate-y-0 opacity-100"
                                                            x-transition:leave="transition ease-in duration-150"
                                                            x-transition:leave-start="translate-y-0 opacity-100"
                                                            x-transition:leave-end="translate-y-full opacity-80"
                                                            @click.outside="closeRecipientSheet()"
                                                            class="fixed inset-x-0 bottom-0 z-50 flex max-h-[86svh] min-h-[44svh] flex-col rounded-t-3xl border border-slate-200 bg-slate-50 p-3 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:hidden"
                                                            role="dialog"
                                                            aria-modal="true"
                                                            aria-label="جستجوی گیرنده"
                                                            dir="rtl"
                                                            style="display: none;"
                                                        >
                                                            <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-200/70 pb-3">
                                                                <div class="min-w-0">
                                                                    <h4 class="text-sm font-black leading-5 text-slate-800">
                                                                        {{ $recipientSearchTitle }}
                                                                    </h4>
                                                                    <p class="mt-0.5 truncate text-[11px] font-bold leading-5 text-slate-500">
                                                                        <span wire:loading.remove wire:target="recipientEntries.{{ $index }}.national_id">
                                                                            {{ $recipientSuggestionItems->isNotEmpty() ? $this->persianNumber($recipientSuggestionItems->count()) . ' مورد پیدا شد' : $recipientSearchPlaceholder . ' را وارد کنید' }}
                                                                        </span>
                                                                        <span wire:loading.inline-flex wire:target="recipientEntries.{{ $index }}.national_id" class="hidden items-center gap-1.5 text-cyan-700">
                                                                            <span class="h-3 w-3 animate-spin rounded-full border-2 border-cyan-200 border-t-cyan-600" aria-hidden="true"></span>
                                                                            در حال جستجو…
                                                                        </span>
                                                                    </p>
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    @click="closeRecipientSheet(); $wire.set('activeRecipientSearchIndex', null)"
                                                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200"
                                                                    aria-label="بستن جستجوی گیرنده"
                                                                >
                                                                    <i class="bi bi-x-lg text-sm"></i>
                                                                </button>
                                                            </div>

                                                            <div class="sticky top-0 z-10 bg-slate-50 pb-2">
                                                                <div class="relative">
                                                                    <input
                                                                        type="search"
                                                                        x-ref="recipientSheetSearch"
                                                                        data-error-field="recipientEntries.{{ $index }}.national_id"
                                                                        wire:model.live.debounce.300ms="recipientEntries.{{ $index }}.national_id"
                                                                        wire:focus="setActiveRecipientSearch({{ $index }})"
                                                                        x-on:keydown.enter.prevent="chooseRecipientSuggestionFromInput()"
                                                                        @disabled(!$this->selectedService)
                                                                        class="min-h-12 w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-3 text-sm text-slate-700 shadow-sm transition placeholder:text-xs placeholder:font-normal placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/10"
                                                                        placeholder="{{ $recipientSearchPlaceholder }}"
                                                                        autocomplete="off"
                                                                    >
                                                                    <svg wire:loading.remove wire:target="recipientEntries.{{ $index }}.national_id" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                              d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"/>
                                                                    </svg>
                                                                    <span wire:loading.flex wire:target="recipientEntries.{{ $index }}.national_id" class="pointer-events-none absolute left-3 top-1/2 hidden h-4 w-4 -translate-y-1/2 items-center justify-center">
                                                                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-cyan-200 border-t-cyan-600" aria-hidden="true"></span>
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain pb-[calc(env(safe-area-inset-bottom)+0.5rem)]">
                                                                @if($recipientSuggestionItems->isNotEmpty() && $this->activeRecipientSearchIndex === $index)
                                                                    @foreach($recipientSuggestionItems as $suggestion)
                                                                        @php
                                                                            $mobileSuggestionName = trim(($suggestion->first_name ?? '') . ' ' . ($suggestion->last_name ?? '')) ?: '-';
                                                                            $mobileSuggestionNationalId = $recipientSuggestionType === 'guardian' ? $suggestion->national_code : $suggestion->national_id;
                                                                        @endphp
                                                                        <button type="button"
                                                                                data-recipient-suggestion
                                                                                wire:click="selectRecipientSuggestion({{ $index }}, '{{ $recipientSuggestionType }}', {{ $suggestion->id }})"
                                                                                @click="closeRecipientSheet()"
                                                                                class="flex min-h-[5rem] w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-3.5 py-3 text-right shadow-sm shadow-slate-200/70 transition hover:border-cyan-200 hover:bg-cyan-50/60 active:bg-cyan-50 focus:outline-none focus:ring-4 focus:ring-cyan-500/10"
                                                                        >
                                                                            <span class="min-w-0 flex-1">
                                                                                <span class="block truncate text-sm font-black leading-5 text-slate-800">{{ $mobileSuggestionName }}</span>
                                                                                <span class="mt-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black leading-4 text-slate-500">{{ $recipientSuggestionTypeLabel }}</span>
                                                                            </span>
                                                                            <span class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-black text-slate-700" dir="ltr">
                                                                                {{ $this->persianNumber($mobileSuggestionNationalId) }}
                                                                            </span>
                                                                        </button>
                                                                    @endforeach
                                                                @elseif($showManualRecipientNotice)
                                                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-right shadow-sm shadow-amber-100/80">
                                                                        <div class="flex items-start gap-3">
                                                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                                                                                <i class="bi bi-person-exclamation text-lg"></i>
                                                                            </span>
                                                                            <div class="min-w-0 flex-1">
                                                                                <p class="text-sm font-black leading-6 text-amber-900">ثبت دستی گیرنده</p>
                                                                                <p class="mt-1 text-xs font-bold leading-5 text-amber-800">
                                                                                    این کد ملی در پرونده‌ها یافت نشد.
                                                                                </p>
                                                                                <button
                                                                                    type="button"
                                                                                    @click="closeRecipientSheet(); $wire.set('activeRecipientSearchIndex', null); $nextTick(() => window.setTimeout(() => {
                                                                                        const field = document.getElementById('recipient-{{ $index }}-full-name');
                                                                                        field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                                                                        field?.focus({ preventScroll: true });
                                                                                    }, 250))"
                                                                                    class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-amber-300 bg-white px-3 text-xs font-black text-amber-800 shadow-sm transition hover:bg-amber-100 focus:outline-none focus:ring-4 focus:ring-amber-400/20"
                                                                                >
                                                                                    <i class="bi bi-pencil-square text-sm"></i>
                                                                                    تکمیل اطلاعات دستی
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @elseif(mb_strlen(trim((string) ($entry['national_id'] ?? ''))) >= 2 && $this->activeRecipientSearchIndex === $index)
                                                                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center">
                                                                        <i class="bi bi-search text-xl text-slate-300"></i>
                                                                        <p class="mt-2 text-xs font-black text-slate-500">نتیجه‌ای پیدا نشد.</p>
                                                                    </div>
                                                                @else
                                                                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center">
                                                                        <i class="bi bi-search text-xl text-slate-300"></i>
                                                                        <p class="mt-2 text-xs font-black text-slate-500">برای جستجو حداقل دو کاراکتر وارد کنید.</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="hidden">
                                                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                                                        <label class="mb-1.5 block text-[10px] font-bold text-slate-400 mr-1">روش جایگزین: QR card token or URL</label>
                                                        <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_auto]">
                                                            <input
                                                                type="text"
                                                                wire:model.defer="recipientEntries.{{ $index }}.qr_token"
                                                                @disabled(!$this->selectedService)
                                                                class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 shadow-sm focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all placeholder:text-slate-300"
                                                                placeholder="Paste scanned QR payload"
                                                                autocomplete="off"
                                                            >
                                                            <button
                                                                type="button"
                                                                wire:click="resolveRecipientQr({{ $index }})"
                                                                @disabled(!$this->selectedService)
                                                                class="inline-flex items-center justify-center rounded-lg border border-cyan-200 bg-white px-3 py-2 text-[11px] font-black text-cyan-700 transition hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-500/15"
                                                            >
                                                                Resolve QR
                                                            </button>
                                                        </div>
                                                        @error('recipientEntries.' . $index . '.qr_token') <p class="mt-1 text-[10px] font-bold text-rose-500 mr-1">{{ $message }}</p> @enderror
                                                    </div>
                                                </div>
                                                @error('recipientEntries.' . $index . '.qr_token') <p class="md:col-span-12 rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                            <div class="mb-3 flex items-center gap-2">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600">۲</span>
                                                <h3 class="text-xs font-extrabold text-slate-700">تایید گیرنده</h3>
                                            </div>

                                            @if($recipientDisplayName !== '' && ! ($entry['is_unregistered'] ?? false))
                                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-3 py-2.5">
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                        <div class="min-w-0">
                                                            <p class="truncate text-sm font-black text-emerald-900">{{ $recipientDisplayName }}</p>
                                                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-bold text-emerald-700">
                                                                <span>{{ $recipientSuggestionTypeLabel }}</span>
                                                                @if($recipientNationalId !== '')
                                                                    <span class="text-emerald-300">|</span>
                                                                    <span dir="ltr">{{ $this->persianNumber($recipientNationalId) }}</span>
                                                                @endif
                                                                @if($familyMemberCount !== null)
                                                                    <span class="text-emerald-300">|</span>
                                                                    <span>{{ $this->persianNumber($familyMemberCount) }} خانوار</span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <span class="inline-flex w-fit items-center gap-1 rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                                            <i class="bi bi-check2-circle text-xs"></i>
                                                            تایید شده
                                                        </span>
                                                    </div>
                                                </div>
                                            @elseif($entry['is_unregistered'] ?? false)
                                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3">
                                                    <div class="flex items-start gap-2.5">
                                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                                                            <i class="bi bi-exclamation-triangle text-sm"></i>
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <h4 class="text-xs font-black text-amber-900">تکمیل اطلاعات گیرنده دستی</h4>
                                                                <span class="rounded-full border border-amber-200 bg-white px-2 py-0.5 text-[9px] font-black text-amber-700">
                                                                    قبل از ثبت مقدار تکمیل شود
                                                                </span>
                                                            </div>
                                                            <p class="mt-1 text-[11px] font-bold leading-5 text-amber-800">
                                                                {{ $entry['not_found_notice'] ?: 'در پرونده‌ها یافت نشد.' }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                        <div>
                                                            <label class="mb-1.5 block text-[10px] font-black text-amber-900">
                                                                نام کامل
                                                                <span class="text-rose-600">*</span>
                                                            </label>
                                                            <input type="text"
                                                                   id="recipient-{{ $index }}-full-name"
                                                                   data-error-field="recipientEntries.{{ $index }}.full_name"
                                                                   wire:model.blur="recipientEntries.{{ $index }}.full_name"
                                                                   class="h-11 w-full rounded-xl border border-amber-200 bg-white px-3 text-sm font-bold text-slate-800 shadow-sm transition placeholder:text-slate-300 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-400/15"
                                                                   placeholder="نام و نام خانوادگی">
                                                            @error('recipientEntries.' . $index . '.full_name') <p class="mt-1 rounded-lg bg-rose-50 px-2 py-1.5 text-[11px] font-bold text-rose-700">{{ $message }}</p> @enderror
                                                        </div>

                                                        <div>
                                                            <label class="mb-1.5 block text-[10px] font-black text-amber-900">
                                                                موبایل
                                                                <span class="font-bold text-amber-700">(اختیاری)</span>
                                                            </label>
                                                            <input type="tel"
                                                                   data-error-field="recipientEntries.{{ $index }}.mobile"
                                                                   inputmode="numeric"
                                                                   pattern="[0-9]*"
                                                                   oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                                                   wire:model.blur="recipientEntries.{{ $index }}.mobile"
                                                                   class="h-11 w-full rounded-xl border border-amber-200 bg-white px-3 text-sm font-bold text-slate-800 shadow-sm transition placeholder:text-slate-300 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-400/15"
                                                                   placeholder="۰۹xxxxxxxxx"
                                                                   maxlength="11">
                                                            @error('recipientEntries.' . $index . '.mobile') <p class="mt-1 rounded-lg bg-rose-50 px-2 py-1.5 text-[11px] font-bold text-rose-700">{{ $message }}</p> @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs font-bold text-slate-500">
                                                    ابتدا گیرنده را با جستجو یا QR شناسایی کنید؛ سپس مقدار دسته‌بندی‌ها را وارد کنید.
                                                </div>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 gap-3">
                                            <div class="pt-1">
                                                @php
                                                    $rowCategoryQuantities = collect($entry['category_quantities'] ?? []);
                                                    $enteredCategoryCount = $rowCategoryQuantities
                                                        ->filter(fn ($quantity) => (float) $quantity > 0)
                                                        ->count();
                                                    $rowTotalQuantity = $rowCategoryQuantities
                                                        ->sum(fn ($quantity) => (float) $quantity);
                                                    $visibleCategories = $assignableCategories
                                                        ->filter(function ($category) use ($categoryMetrics, $entry) {
                                                            $remainingStock = (float) ($categoryMetrics[$category->id]['remaining_stock'] ?? 0);
                                                            $currentQuantity = (float) data_get($entry, 'category_quantities.' . (int) $category->id, 0);

                                                            return $remainingStock > 0 || $currentQuantity > 0;
                                                        })
                                                        ->values();
                                                    $hiddenUnavailableCount = max(0, $assignableCategories->count() - $visibleCategories->count());
                                                @endphp

                                                <details class="group rounded-2xl bg-slate-50 p-2.5" open>
                                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-1 py-1.5 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                        <div class="flex min-w-0 items-center gap-2">
                                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600 group-open:bg-cyan-600 group-open:text-white">۳</span>
                                                            <div class="min-w-0">
                                                                <h3 class="truncate text-xs font-extrabold text-slate-700">دسته‌بندی و مقدار</h3>
                                                            </div>
                                                        </div>

                                                        <div class="flex shrink-0 items-center gap-2">
                                                            @if($rowTotalQuantity > 0)
                                                                <span class="rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">
                                                                    جمع: {{ $this->persianNumber(number_format($rowTotalQuantity, 2)) }}
                                                                </span>
                                                            @endif
                                                            <i class="bi bi-chevron-down text-xs text-slate-400 transition group-open:rotate-180"></i>
                                                        </div>
                                                    </summary>

                                                    <div class="mt-2">
                                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                                            @forelse($visibleCategories as $category)
                                                                @php
                                                                    $metrics = $categoryMetrics[$category->id] ?? ['remaining_stock' => 0, 'remaining_allocation' => 0];
                                                                    $remainingStock = (float) $metrics['remaining_stock'];
                                                                    $currentQuantity = (float) data_get($entry, 'category_quantities.' . (int) $category->id, 0);
                                                                    $pendingCategoryQuantity = collect($recipientEntries)->sum(fn ($entry) => (float) data_get($entry, 'category_quantities.' . (int) $category->id, 0));
                                                                    $otherRowsPendingQuantity = max(0, $pendingCategoryQuantity - $currentQuantity);
                                                                    $availableForCurrentInput = max(0, min(
                                                                        $remainingStock,
                                                                        (float) $metrics['remaining_allocation'] - $otherRowsPendingQuantity
                                                                    ));
                                                                    $exceedsRemainingQuota = $currentQuantity > 0 && $currentQuantity > $availableForCurrentInput;
                                                                    $isUnavailable = $remainingStock <= 0;
                                                                @endphp

                                                                <div class="grid grid-cols-[minmax(0,1fr)_7.5rem] items-center gap-2 border-b border-slate-200 bg-white px-3 py-2.5 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_9rem]"
                                                                     x-data="{
                                                                        rowIndex: {{ (int) $index }},
                                                                        categoryId: '{{ (int) $category->id }}',
                                                                        remainingStock: {{ json_encode(number_format($remainingStock, 2, '.', '')) }},
                                                                        remainingAllocation: {{ json_encode(number_format((float) $metrics['remaining_allocation'], 2, '.', '')) }},
                                                                        get available() {
                                                                            return availableForInput(this.rowIndex, this.categoryId, this.remainingStock, this.remainingAllocation);
                                                                        },
                                                                        get exceeds() {
                                                                            return exceedsRemainingQuota(this.rowIndex, this.categoryId, this.remainingStock, this.remainingAllocation);
                                                                        },
                                                                     }">
                                                                    <div class="min-w-0">
                                                                        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                                                            <h4 class="max-w-full truncate text-xs font-bold text-slate-800 sm:text-sm">{{ $category->name }}</h4>
                                                                            @if($isUnavailable)
                                                                                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[9px] font-bold text-rose-600">ناموجود</span>
                                                                            @endif
                                                                        </div>
                                                                        <p class="mt-1 flex flex-wrap items-center gap-1 text-[10px] font-bold text-slate-400">
                                                                            <span>باقی‌مانده:</span>
                                                                            <span x-text="persianNumber(formatDecimal(available))">{{ $this->persianNumber(number_format($availableForCurrentInput, 2)) }}</span>
                                                                            <span>{{ $unitOptions[$category->unit] ?? $category->unit }}</span>
                                                                        </p>
                                                                    </div>

                                                                    <div>
                                                                        <label class="sr-only" for="recipient-{{ $index }}-category-{{ $category->id }}">مقدار تحویلی {{ $category->name }}</label>
                                                                        <input id="recipient-{{ $index }}-category-{{ $category->id }}"
                                                                               type="number"
                                                                               data-error-field="recipientEntries.{{ $index }}.category_quantities.{{ $category->id }}"
                                                                               min="0.01"
                                                                               max="{{ number_format($availableForCurrentInput, 2, '.', '') }}"
                                                                               x-bind:max="formatDecimal(available)"
                                                                               step="0.01"
                                                                               inputmode="decimal"
                                                                               wire:model.live.debounce.300ms="recipientEntries.{{ $index }}.category_quantities.{{ $category->id }}"
                                                                               x-on:input="setCategoryQuantity(rowIndex, categoryId, $event.target.value)"
                                                                               @disabled(!$this->selectedService || ($isUnavailable && $currentQuantity <= 0))
                                                                               class="h-11 w-full rounded-xl border bg-slate-50 px-2.5 text-center text-sm font-black text-slate-800 transition placeholder:text-slate-300 focus:bg-white focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                                                               x-bind:class="exceeds
                                                                                    ? 'border-rose-300 bg-rose-50 text-rose-700 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10'
                                                                                    : 'border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10'"
                                                                               placeholder="۰">
                                                                        <p x-cloak x-show="exceeds" class="mt-1.5 text-[10px] font-bold text-rose-600">
                                                                                بیشتر از سهمیۀ مجاز
                                                                        </p>
                                                                        @error('recipientEntries.' . $index . '.category_quantities.' . $category->id) <p class="mt-1 rounded-lg bg-rose-50 px-2 py-1.5 text-[11px] font-bold text-rose-700">{{ $message }}</p> @enderror
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="bg-amber-50 px-3 py-3 text-xs font-bold text-amber-700">
                                                                    دسته‌بندی قابل تخصیص برای این خدمت وجود ندارد.
                                                                </div>
                                                            @endforelse
                                                        </div>

                                                        @if($hiddenUnavailableCount > 0)
                                                            <p class="mt-2 text-[10px] font-bold text-slate-400">
                                                                {{ $this->persianNumber($hiddenUnavailableCount) }} دسته ناموجود پنهان شد.
                                                            </p>
                                                        @endif

                                                        @error('recipientEntries.' . $index . '.service_category_id') <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Errors -->
                                    @error('recipientEntries.' . $index . '.national_id') <p class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                                    @error('recipientEntries.' . $index . '.quantity') <p class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                                    @error('recipientEntries') <p class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror

                                    </div>
                                    </details>
                                </article>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addRecipientField"
                                class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-cyan-700 bg-cyan-600 px-4 py-3 text-sm font-black text-white shadow-sm shadow-cyan-200 transition hover:border-cyan-600 hover:bg-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60 md:hidden">
                            <i class="bi bi-plus-lg text-base"></i>
                            <span>افزودن گیرنده</span>
                        </button>

                        <div
                            x-data="{
                                ...idCardScanner({
                                    resolveScan: async (payload, scanner) => {
                                        const index = scanner.activeRecipientIndex;

                                        if (index === null || index === undefined) {
                                            return {
                                                ok: false,
                                                status: 'scan_error',
                                                message: 'ردیف گیرنده برای ثبت QR مشخص نیست.',
                                                result: {
                                                    ok: false,
                                                    code: 'missing_recipient_row',
                                                    message: 'ردیف گیرنده برای ثبت QR مشخص نیست.',
                                                },
                                            };
                                        }

                                        const response = await $wire.resolveScannedRecipientQr(index, payload);

                                        if (response?.ok) {
                                            window.setTimeout(() => window.dispatchEvent(new CustomEvent('close-recipient-qr-scanner')), 700);
                                        }

                                        return response;
                                    },
                                    successSoundUrl: '/sounds/scan-card.wav',
                                    enableResultBanner: false,
                                    autoStart: false,
                                    autoResumeAfterError: false,
                                }),
                                qrScannerOpen: false,
                                openingScanner: false,
                                activeRecipientIndex: null,
                                scannerHistoryActive: false,
                                pushScannerHistory() {
                                    if (this.scannerHistoryActive) {
                                        return;
                                    }

                                    try {
                                        window.history.pushState({
                                            ...(window.history.state || {}),
                                            serviceRecipientQrScanner: true,
                                        }, '', window.location.href);
                                        this.scannerHistoryActive = true;
                                    } catch (error) {
                                        this.scannerHistoryActive = false;
                                    }
                                },
                                handleScannerPopState() {
                                    if (!this.qrScannerOpen) {
                                        this.scannerHistoryActive = false;
                                        return;
                                    }

                                    this.closeScanner({ syncHistory: false });
                                },
                                async openScanner(index) {
                                    if (this.openingScanner) {
                                        return;
                                    }

                                    this.activeRecipientIndex = index;
                                    this.openingScanner = true;
                                    this.qrScannerOpen = true;
                                    this.pushScannerHistory();

                                    try {
                                        await $nextTick();
                                        await this.waitForScannerFrame();
                                        await this.waitForScannerBoot();
                                        await this.startCamera();
                                    } finally {
                                        this.openingScanner = false;
                                    }
                                },
                                async waitForScannerFrame() {
                                    for (let attempt = 0; attempt < 20; attempt++) {
                                        const rect = this.$refs.scanner?.getBoundingClientRect();

                                        if (rect?.width > 160 && rect?.height > 160) {
                                            return;
                                        }

                                        await new Promise((resolve) => requestAnimationFrame(resolve));
                                    }
                                },
                                async waitForScannerBoot() {
                                    for (let attempt = 0; attempt < 50; attempt++) {
                                        if (this.html5QrCode && this.Html5Qrcode) {
                                            return;
                                        }

                                        await new Promise((resolve) => setTimeout(resolve, 100));
                                    }

                                    await this.init();
                                },
                                closeScanner({ syncHistory = true } = {}) {
                                    const shouldRestoreHistory = syncHistory && this.scannerHistoryActive;

                                    this.qrScannerOpen = false;
                                    this.activeRecipientIndex = null;
                                    this.stopCamera();

                                    if (shouldRestoreHistory) {
                                        this.scannerHistoryActive = false;
                                        window.history.back();
                                    } else if (!syncHistory) {
                                        this.scannerHistoryActive = false;
                                    }
                                },
                            }"
                            x-init="init(); window.addEventListener('popstate', () => handleScannerPopState()); $watch('qrScannerOpen', (open) => document.documentElement.classList.toggle('overflow-hidden', open))"
                            x-on:open-recipient-qr-scanner.window="openScanner($event.detail.index)"
                            x-on:close-recipient-qr-scanner.window="closeScanner()"
                            x-on:keydown.escape.window="if (qrScannerOpen) closeScanner()"
                            x-show="qrScannerOpen"
                            x-cloak
                            x-transition.opacity.duration.150ms
                            class="fixed inset-0 z-[90] flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-3"
                            role="dialog"
                            aria-modal="true"
                            style="display: none;"
                        >
                            <div
                                @click.outside="closeScanner()"
                                class="flex max-h-[calc(100svh-1rem)] w-full max-w-md flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white text-slate-900 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:rounded-2xl sm:shadow-xl sm:shadow-slate-900/15"
                                dir="rtl"
                            >
                                <div class="flex items-center justify-between border-b border-slate-200/70 px-3 py-3">
                                    <div class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                        <span class="inline-flex h-2 w-2 rounded-full"
                                              :class="{
                                                'bg-emerald-500': ['ready', 'scanning'].includes(status),
                                                'bg-amber-500': status === 'paused',
                                                'bg-rose-500': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                                                'bg-slate-300': status === 'initializing',
                                              }"></span>
                                        <span x-text="statusLabel()"></span>
                                    </div>
                                    <button type="button" @click="closeScanner()" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200" aria-label="بستن">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <div class="relative min-h-[320px] flex-1 bg-slate-950 sm:aspect-square sm:flex-none">
                                    <div wire:ignore x-ref="scanner" id="service-recipient-scanner" class="qr-scanner-reader h-full w-full"></div>
                                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                        <div class="aspect-square w-[min(70%,300px)] rounded-2xl border border-white/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.24)]"></div>
                                    </div>
                                    <div
                                        x-show="openingScanner || startingCamera || (!cameraActive && ['ready', 'camera_denied', 'scan_error', 'unsupported'].includes(status))"
                                        x-transition.opacity.duration.150ms
                                        class="absolute inset-0 flex items-center justify-center bg-white/92 px-5 text-center backdrop-blur-sm"
                                        style="display: none;"
                                    >
                                        <div class="w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/10">
                                            <div
                                                x-show="openingScanner || startingCamera || status === 'initializing'"
                                                class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-cyan-500"
                                                style="display: none;"
                                                aria-hidden="true"
                                            ></div>
                                            <div
                                                x-show="['camera_denied', 'scan_error', 'unsupported'].includes(status)"
                                                class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-500"
                                                style="display: none;"
                                                aria-hidden="true"
                                            >
                                                <i class="bi bi-camera-video-off text-lg"></i>
                                            </div>
                                            <div
                                                x-show="status === 'ready' && !startingCamera"
                                                class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-cyan-50 text-cyan-600"
                                                style="display: none;"
                                                aria-hidden="true"
                                            >
                                                <i class="bi bi-camera-video text-lg"></i>
                                            </div>

                                            <p class="text-sm font-extrabold text-slate-800">
                                                <span x-show="openingScanner || startingCamera || status === 'initializing'">در حال فعال‌سازی دوربین</span>
                                                <span x-show="status === 'ready' && !startingCamera">دوربین آماده شروع است</span>
                                                <span x-show="status === 'camera_denied'">دسترسی به دوربین انجام نشد</span>
                                                <span x-show="status === 'scan_error'">اسکن انجام نشد</span>
                                                <span x-show="status === 'unsupported'">دوربین پشتیبانی نمی‌شود</span>
                                            </p>
                                            <p class="mt-2 text-xs leading-5 text-slate-500" x-text="message"></p>

                                            <div class="mt-4 grid gap-2" x-show="!openingScanner && !startingCamera && status !== 'unsupported'" style="display: none;">
                                                <button
                                                    type="button"
                                                    @click="status === 'scan_error' && cameraActive ? resumeScan() : startCamera()"
                                                    class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-cyan-700 bg-cyan-600 px-3 text-xs font-black text-white transition hover:border-cyan-600 hover:bg-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/20"
                                                >
                                                    <i class="bi bi-camera-video"></i>
                                                    <span x-show="status === 'ready'">شروع اسکن</span>
                                                    <span x-show="status === 'scan_error'">اسکن دوباره</span>
                                                    <span x-show="!['ready', 'scan_error'].includes(status)">تلاش دوباره</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2 px-3 py-3">
                                    <div
                                        x-show="status === 'scan_error'"
                                        x-transition.opacity.duration.150ms
                                        class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-rose-700"
                                        style="display: none;"
                                    >
                                        <div class="flex items-start gap-2">
                                            <i class="bi bi-exclamation-triangle mt-0.5 shrink-0 text-sm"></i>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-extrabold">QR قابل ثبت نیست</p>
                                                <p class="mt-1 text-[11px] leading-5 text-rose-600" x-text="message"></p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="resumeScan()"
                                            class="mt-2 inline-flex min-h-9 w-full items-center justify-center gap-2 rounded-lg border border-cyan-200 bg-white px-3 text-xs font-black text-cyan-700 transition hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-500/15"
                                        >
                                            <i class="bi bi-qr-code-scan"></i>
                                            اسکن دوباره
                                        </button>
                                    </div>
                                    <p x-show="status !== 'scan_error'" class="text-xs leading-5 text-slate-500" x-text="message"></p>
                                    <div class="grid gap-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                @click="status === 'scan_error' && cameraActive ? resumeScan() : startCamera()"
                                                :disabled="startingCamera"
                                                class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-cyan-700 bg-cyan-600 px-3 text-xs font-black text-white transition hover:border-cyan-600 hover:bg-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 disabled:cursor-wait disabled:opacity-60"
                                            >
                                                <i class="bi bi-arrow-clockwise"></i>
                                                <span>شروع / تلاش دوباره</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="closeScanner()"
                                                class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-600 transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            >
                                                <i class="bi bi-x-lg"></i>
                                                <span>بستن</span>
                                            </button>
                                        </div>

                                        <details class="group rounded-xl border border-slate-200 bg-slate-50">
                                            <summary class="flex min-h-10 cursor-pointer list-none items-center justify-between gap-3 px-3 text-xs font-black text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-200">
                                                <span class="inline-flex items-center gap-2">
                                                    <i class="bi bi-sliders"></i>
                                                    تنظیمات دوربین
                                                </span>
                                                <i class="bi bi-chevron-down text-[10px] text-slate-400 transition group-open:rotate-180"></i>
                                            </summary>

                                            <div class="grid gap-2 border-t border-slate-200 px-3 py-3">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button
                                                        type="button"
                                                        @click="cycleCamera()"
                                                        :disabled="startingCamera || cameras.length < 2"
                                                        class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 text-[11px] font-black text-slate-600 transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:opacity-45"
                                                    >
                                                        <i class="bi bi-camera-video"></i>
                                                        <span>تعویض دوربین</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click="toggleTorch()"
                                                        :disabled="!cameraActive || !supportsTorch()"
                                                        class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border px-2 text-[11px] font-black transition focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:opacity-45"
                                                        :class="torchEnabled ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
                                                    >
                                                        <i class="bi" :class="torchEnabled ? 'bi-lightbulb-fill' : 'bi-lightbulb'"></i>
                                                        <span x-text="torchEnabled ? 'چراغ روشن' : 'چراغ'"></span>
                                                    </button>
                                                </div>

                                                <div class="grid gap-2" x-show="cameras.length > 1" style="display: none;">
                                                    <label class="text-[10px] font-bold text-slate-500">انتخاب دوربین</label>
                                                    <select
                                                        x-model="selectedDeviceId"
                                                        @change="switchCamera()"
                                                        :disabled="startingCamera"
                                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-100 disabled:cursor-wait disabled:opacity-60"
                                                    >
                                                        <template x-for="camera in cameras" :key="camera.id">
                                                            <option :value="camera.id" x-text="camera.label"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div
                                                    x-show="cameraActive && supportsZoom()"
                                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2"
                                                    style="display: none;"
                                                >
                                                    <div class="mb-1 flex items-center justify-between gap-3 text-[11px] font-bold text-slate-500">
                                                        <span>بزرگنمایی</span>
                                                        <span dir="ltr" x-text="`${persianNumber(Number(zoomLevel || 1).toFixed(1))}x`"></span>
                                                    </div>
                                                    <input
                                                        type="range"
                                                        x-model.number="zoomLevel"
                                                        @input.debounce.120ms="applyZoom()"
                                                        :min="zoomMin()"
                                                        :max="zoomMax()"
                                                        :step="zoomStep()"
                                                        class="w-full accent-slate-700"
                                                    >
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </section>
                    @endif


                    @if($selectedService)
                    <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm shadow-slate-100 sm:p-4">
                        <div class="mb-3 flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $selectedService ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-400' }} text-xs font-black">۳</span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-black text-slate-900">جزئیات تحویل</h2>
                            </div>
                        </div>
                        @if(!$selectedService)
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="bi bi-calendar2-check text-lg"></i>
                                </div>
                                <h3 class="mt-3 text-sm font-extrabold text-slate-800">جزئیات تحویل پس از انتخاب خدمت فعال می‌شود</h3>
                                <button
                                    type="button"
                                    wire:click="requireServiceSelection"
                                    x-data
                                    x-on:click="$nextTick(() => {
                                        const panel = document.querySelector('[data-service-selector-panel]');
                                        const trigger = document.querySelector('[data-service-selector-trigger]');
                                        panel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        window.setTimeout(() => trigger?.focus({ preventScroll: true }), 350);
                                    })"
                                    class="mt-4 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-cyan-200 bg-white px-4 text-xs font-black text-cyan-700 shadow-sm transition hover:bg-cyan-50 active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-cyan-500/15"
                                >
                                    <i class="bi bi-arrow-up-short text-base"></i>
                                    انتخاب خدمت
                                </button>
                            </div>
                        @else
                            <div class="grid gap-3 md:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                                <div x-data="jalaliDateTimeField($wire.entangle('deliveredAt').live)">
                                    <label class="mb-1.5 block text-xs font-black text-slate-600">تاریخ تحویل</label>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            x-ref="input"
                                            x-model="draft"
                                            x-on:change="syncFromInput()"
                                            x-on:blur="syncFromInput()"
                                            x-on:jalali-picker-open="handlePickerOpen()"
                                            x-on:jalali-picker-close="handlePickerClose()"
                                            x-on:jalali-picker-confirm="confirm()"
                                            readonly
                                            inputmode="none"
                                            autocomplete="off"
                                            data-error-field="deliveredAt"
                                            data-jdp-readonly
                                            data-jdp
                                            data-jdp-only-date
                                            placeholder="۱۴۰۵/۰۳/۱۶"
                                            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 pe-10 text-sm font-bold text-slate-700 outline-none transition ltr:text-left focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-500/10"
                                        >
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <i class="bi bi-calendar2-event text-sm"></i>
                                        </span>
                                    </div>
                                    @error('deliveredAt') <p class="mt-1 rounded-lg bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-black text-slate-600">یادداشت</label>
                                    <textarea wire:model.blur="notes" rows="2"
                                              placeholder="اختیاری"
                                              class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-500/10"></textarea>
                                    @error('notes') <p class="mt-1 rounded-lg bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                    </section>
                    @endif

                    </div>

                    @if($selectedService)
                    <aside class="space-y-4 xl:sticky xl:top-4">
                    @if($selectedService)
                        @php
                            $filledRecipientCount = collect($recipientEntries)
                                ->filter(fn ($entry) => filled($entry['national_id'] ?? '') || filled($entry['resolved_name'] ?? '') || filled($entry['full_name'] ?? ''))
                                ->count();
                            $manualRecipientCount = collect($recipientEntries)
                                ->filter(fn ($entry) => (bool) ($entry['is_unregistered'] ?? false))
                                ->count();
                            $unresolvedRecipientCount = collect($recipientEntries)
                                ->filter(fn ($entry) => filled($entry['national_id'] ?? '') && blank($entry['resolved_name'] ?? '') && ! (bool) ($entry['is_unregistered'] ?? false))
                                ->count();
                            $categoryReviewTotals = $assignableCategories
                                ->map(function ($category) use ($recipientEntries) {
                                    $quantity = collect($recipientEntries)
                                        ->sum(fn ($entry) => (float) data_get($entry, 'category_quantities.' . (int) $category->id, 0));

                                    return [
                                        'name' => $category->name,
                                        'unit' => $unitOptions[$category->unit] ?? $category->unit,
                                        'quantity' => $quantity,
                                    ];
                                })
                                ->filter(fn ($row) => (float) $row['quantity'] > 0)
                                ->values();
                            $totalCategoryCount = $categoryReviewTotals->count();

                            // Live remaining quota per category = allocation left minus what is
                            // pending in the current form, capped by remaining stock. Kept visible
                            // while quantities are entered so over-allocation is caught early.
                            // Whole quantities (e.g. عدد) render without decimals; only fractional
                            // values (e.g. کیلوگرم) keep two decimals, to avoid visual noise.
                            $formatQuota = fn (float $value): string => round($value, 2) == round($value)
                                ? number_format($value, 0)
                                : number_format($value, 2);
                            $quotaStripCategories = $assignableCategories
                                ->map(function ($category) use ($recipientEntries, $categoryMetrics, $unitOptions, $formatQuota) {
                                    $metrics = $categoryMetrics[$category->id] ?? ['remaining_stock' => 0, 'remaining_allocation' => 0];
                                    $pending = collect($recipientEntries)
                                        ->sum(fn ($entry) => (float) data_get($entry, 'category_quantities.' . (int) $category->id, 0));
                                    $baseRemaining = max(0, (float) $metrics['remaining_allocation']);
                                    $liveRemaining = max(0, min(
                                        (float) $metrics['remaining_stock'],
                                        (float) $metrics['remaining_allocation'] - $pending
                                    ));
                                    $state = match (true) {
                                        $liveRemaining <= 0 => ['dot' => 'bg-rose-500', 'value' => 'text-rose-700', 'chip' => 'border-rose-100 bg-rose-50'],
                                        $baseRemaining > 0 && $liveRemaining / $baseRemaining <= 0.25 => ['dot' => 'bg-amber-500', 'value' => 'text-amber-700', 'chip' => 'border-amber-100 bg-amber-50'],
                                        default => ['dot' => 'bg-emerald-500', 'value' => 'text-emerald-700', 'chip' => 'border-emerald-100 bg-emerald-50'],
                                    };

                                    return [
                                        'name' => $category->name,
                                        'unit' => $unitOptions[$category->unit] ?? $category->unit,
                                        'pending' => $pending,
                                        'remaining' => $liveRemaining,
                                        'remaining_display' => $formatQuota($liveRemaining),
                                        'state' => $state,
                                    ];
                                })
                                ->values();
                            $overallPending = collect($recipientEntries)
                                ->sum(fn ($entry) => collect($entry['category_quantities'] ?? [])->sum(fn ($quantity) => (float) $quantity));
                            $overallLiveRemaining = max(0, (float) ($selectedServiceTotals['remaining'] ?? 0) - $overallPending);
                            $overallLiveRemainingDisplay = $formatQuota($overallLiveRemaining);
                        @endphp

                        {{-- Remaining quota panel (desktop / tablet) — mirrored by the mobile bar below --}}
                        <section class="hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm shadow-slate-100 sm:p-4 md:block">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700">
                                        <i class="bi bi-speedometer2 text-sm"></i>
                                    </span>
                                    <h2 class="truncate text-sm font-black text-slate-800">باقی‌ماندهٔ سهمیه</h2>
                                </div>
                                <span class="inline-flex flex-col items-end leading-none">
                                    <span class="text-[9px] font-bold text-slate-400">مجموع</span>
                                    <span class="mt-1 text-sm font-black {{ $overallLiveRemaining <= 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $this->persianNumber($overallLiveRemainingDisplay) }}
                                    </span>
                                </span>
                            </div>

                            @if($quotaStripCategories->isNotEmpty())
                                <div class="space-y-1.5">
                                    @foreach($quotaStripCategories as $row)
                                        <div class="flex items-center justify-between gap-3 rounded-xl border {{ $row['state']['chip'] }} px-2.5 py-1.5">
                                            <span class="inline-flex min-w-0 items-center gap-1.5">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $row['state']['dot'] }}"></span>
                                                <span class="min-w-0 truncate text-[11px] font-bold text-slate-700">{{ $row['name'] }}</span>
                                            </span>
                                            <span class="shrink-0 text-xs font-black {{ $row['state']['value'] }}">
                                                {{ $this->persianNumber($row['remaining_display']) }}
                                                <span class="text-[9px] font-bold text-slate-400">{{ $row['unit'] }}</span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="rounded-xl bg-slate-50 px-2.5 py-2 text-xs font-bold text-slate-400">
                                    سهمیهٔ قابل تحویلی برای این خدمت ثبت نشده است.
                                </p>
                            @endif
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm shadow-slate-100 sm:p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-sm font-black text-white">۴</span>
                                    <div class="min-w-0">
                                    <h2 class="text-sm font-black text-slate-800">مرور نهایی تحویل</h2>
                                    <p class="mt-1 truncate text-xs font-bold text-slate-500">
                                        {{ $selectedService->serviceName?->name }}
                                        <span class="text-slate-300">|</span>
                                        {{ $this->persianNumber($selectedService->code) }}
                                    </p>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-black text-slate-600">
                                    تاریخ: {{ $deliveredAt !== '' ? $this->persianNumber($deliveredAt) : '-' }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <div class="rounded-2xl bg-slate-50 px-2.5 py-2.5">
                                    <span class="block text-[9px] font-bold text-slate-400">گیرندگان</span>
                                    <span class="mt-1 block text-sm font-extrabold text-slate-800">{{ $this->persianNumber($filledRecipientCount) }}</span>
                                </div>
                                <div class="rounded-2xl bg-amber-50 px-2.5 py-2.5">
                                    <span class="block text-[9px] font-bold text-amber-600">ثبت دستی</span>
                                    <span class="mt-1 block text-sm font-extrabold text-amber-800">{{ $this->persianNumber($manualRecipientCount) }}</span>
                                </div>
                                <div class="rounded-2xl bg-cyan-50 px-2.5 py-2.5">
                                    <span class="block text-[9px] font-bold text-cyan-600">دسته‌ها</span>
                                    <span class="mt-1 block text-sm font-extrabold text-slate-800">{{ $this->persianNumber($totalCategoryCount) }}</span>
                                </div>
                            </div>

                            <div class="mt-3 rounded-2xl bg-slate-50 px-3 py-2">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <h3 class="text-[11px] font-black text-slate-700">جمع مقادیر بر اساس دسته‌بندی</h3>
                                </div>
                                @if($categoryReviewTotals->isNotEmpty())
                                    <div class="space-y-1.5">
                                        @foreach($categoryReviewTotals as $row)
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-2.5 py-2">
                                                <span class="min-w-0 truncate text-xs font-bold text-slate-700">{{ $row['name'] }}</span>
                                                <span class="shrink-0 text-xs font-extrabold text-slate-800">
                                                    {{ $this->persianNumber(number_format((float) $row['quantity'], 2)) }}
                                                    <span class="text-[10px] text-slate-400">{{ $row['unit'] }}</span>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="rounded-xl bg-white px-2.5 py-2 text-xs font-bold text-slate-400">
                                        هنوز مقداری برای تحویل وارد نشده است.
                                    </p>
                                @endif
                            </div>

                            @if($manualRecipientCount > 0 || $unresolvedRecipientCount > 0)
                                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] font-bold leading-5 text-amber-800">
                                    @if($manualRecipientCount > 0)
                                        <p>{{ $this->persianNumber($manualRecipientCount) }} ثبت دستی</p>
                                    @endif
                                    @if($unresolvedRecipientCount > 0)
                                        <p>{{ $this->persianNumber($unresolvedRecipientCount) }} گیرنده شناسایی‌نشده</p>
                                    @endif
                                </div>
                            @endif
                        </section>

                        <div class="fixed inset-x-0 bottom-0 z-30 rounded-t-3xl border-t border-slate-200 bg-white/95 px-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] pt-3 shadow-[0_-10px_24px_rgba(15,23,42,0.10)] backdrop-blur md:hidden">
                            <div class="mx-auto max-w-xl">
                                @if($quotaStripCategories->isNotEmpty())
                                    <div class="mb-2.5 -mx-1 flex items-center gap-1.5 overflow-x-auto px-1 pb-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-black text-slate-500">
                                            <i class="bi bi-speedometer2 text-cyan-600"></i>
                                            باقی‌مانده
                                        </span>
                                        @foreach($quotaStripCategories as $row)
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full border {{ $row['state']['chip'] }} px-2 py-1">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $row['state']['dot'] }}"></span>
                                                <span class="max-w-[6rem] truncate text-[10px] font-bold text-slate-600">{{ $row['name'] }}</span>
                                                <span class="text-[10px] font-black {{ $row['state']['value'] }}">
                                                    <span dir="ltr">{{ $this->persianNumber($row['remaining_display']) }}</span>
                                                    <span class="text-[9px] font-bold text-slate-400">{{ $row['unit'] }}</span>
                                                </span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <button type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="saveDelivery"
                                        class="inline-flex min-h-[3.25rem] w-full items-center justify-center gap-2 rounded-2xl border border-emerald-800 bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200/80 transition hover:border-emerald-700 hover:bg-emerald-600 active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-emerald-500/25 disabled:cursor-wait disabled:border-emerald-500 disabled:bg-emerald-500 disabled:opacity-80">
                                    <span wire:loading.remove wire:target="saveDelivery" class="inline-flex items-center justify-center gap-2">
                                        <i class="bi bi-check2-circle text-base"></i>
                                        ثبت تحویل
                                    </span>
                                    <span wire:loading wire:target="saveDelivery" class="inline-flex items-center justify-center gap-2">
                                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                                        در حال ثبت...
                                    </span>
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Submit Button --}}
                    <button type="submit"
                            @disabled(!$this->selectedService)
                            wire:loading.attr="disabled"
                            wire:target="saveDelivery"
                            class="hidden min-h-14 w-full items-center justify-center gap-2 rounded-2xl border border-emerald-800 bg-emerald-700 px-5 py-3.5 text-base font-black text-white shadow-lg shadow-emerald-200/80 transition hover:border-emerald-700 hover:bg-emerald-600 active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-emerald-500/25 disabled:cursor-wait disabled:border-emerald-500 disabled:bg-emerald-500 disabled:opacity-80 md:inline-flex">
                        <span wire:loading.remove wire:target="saveDelivery" class="inline-flex items-center justify-center gap-2">
                            <i class="bi bi-check2-circle text-base"></i>
                            ثبت تحویل
                        </span>
                        <span wire:loading wire:target="saveDelivery" class="inline-flex items-center justify-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                            در حال ثبت تحویل...
                        </span>
                    </button>
                    </aside>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
