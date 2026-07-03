@props(['compact' => false])

<div>
    @unless($compact)
        <label class="mb-2 block text-sm font-bold text-slate-700">خدمت / پویش </label>
    @endunless
    <div
        class="relative"
        x-data="{
            open: false,
            historyActive: false,
            search: @entangle('serviceSearch').live,
            get isMobileSheet() {
                return window.matchMedia('(max-width: 639px)').matches;
            },
            openSelector() {
                this.open = true;
                this.pushSelectorHistory();
            },
            closeSelector({ syncHistory = true } = {}) {
                const shouldRestoreHistory = syncHistory && this.historyActive;
                this.open = false;

                if (shouldRestoreHistory) {
                    this.historyActive = false;
                    window.history.back();
                } else if (! syncHistory) {
                    this.historyActive = false;
                }
            },
            toggleSelector() {
                this.open ? this.closeSelector() : this.openSelector();
            },
            pushSelectorHistory() {
                if (! this.isMobileSheet || this.historyActive) {
                    return;
                }

                try {
                    window.history.pushState({
                        ...(window.history.state || {}),
                        distributionOperatorServiceSelector: true,
                    }, '', window.location.href);
                    this.historyActive = true;
                } catch (error) {
                    this.historyActive = false;
                }
            },
            handleSelectorPopState() {
                if (! this.open) {
                    this.historyActive = false;
                    return;
                }

                this.closeSelector({ syncHistory: false });
            },
            choose(serviceId) {
                this.search = '';
                this.closeSelector();
                $wire.selectPredefinedService(Number(serviceId));
            },
            clear() {
                this.search = '';
                this.closeSelector();
                $wire.clearPredefinedServiceSelection();
            },
        }"
        x-init="
            window.addEventListener('popstate', () => handleSelectorPopState());
            window.addEventListener('open-predefined-service-selector', () => {
                openSelector();
            });
            $watch('open', (value) => {
                document.documentElement.classList.toggle('overflow-hidden', value && isMobileSheet);
            });
        "
        x-on:keydown.escape.window="closeSelector()"
    >
        @if($compact)
            <button
                type="button"
                @click="openSelector()"
                :aria-expanded="open.toString()"
                aria-haspopup="dialog"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cyan-200 bg-white text-cyan-700 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50 focus:outline-none focus:ring-4 focus:ring-cyan-500/10 active:scale-[0.98]"
                aria-label="{{ $selectedService ? 'تغییر خدمت' : 'انتخاب خدمت' }}"
                title="{{ $selectedService ? 'تغییر خدمت' : 'انتخاب خدمت' }}"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M17 3l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M3 7h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M7 21l-4-4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M21 17H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
            </button>
        @else
            <button
                type="button"
                @click="toggleSelector()"
                :aria-expanded="open.toString()"
                aria-haspopup="dialog"
                class="flex min-h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50/50 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/10"
            >
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-medium text-slate-400">
                        @if($selectedService)
                            {{ $selectedService->code }}
                        @else
                            یک خدمت یا پویش را انتخاب کنید
                        @endif
                    </span>
                    @if($selectedService)
                        <span class="mt-1 block truncate text-sm font-bold text-slate-900">
                            {{ $selectedService->name ?: ($selectedService->serviceName?->name ?? 'بدون عنوان') }}
                        </span>
                    @endif
                </span>
                <svg class="ms-3 h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        @endif

        <div
            x-show="open"
            x-transition.opacity.duration.150ms
            @click="closeSelector()"
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm sm:hidden"
            style="display: none;"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
            @click.outside="closeSelector()"
            class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85svh] flex-col rounded-t-3xl border border-slate-200 bg-slate-50 p-3 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:absolute sm:inset-auto sm:mt-2 sm:max-h-96 sm:w-full sm:rounded-2xl sm:p-2 sm:shadow-xl"
            dir="rtl"
            role="dialog"
            aria-modal="true"
            style="display: none;"
        >
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-200/70 pb-3 sm:hidden">
                <div class="min-w-0">
                    <h3 class="text-sm font-black text-slate-800">انتخاب خدمت</h3>
                    <p class="mt-1 text-xs text-slate-500">جستجو بر اساس نام، کد یا دسته‌بندی</p>
                </div>
                <button
                    type="button"
                    @click="closeSelector()"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    aria-label="بستن"
                >
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <div class="sticky top-0 z-10 bg-slate-50 pb-2">
                <div class="relative">
                    <input
                        type="search"
                        x-ref="serviceSearch"
                        x-model.debounce.250ms="search"
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/10"
                        placeholder="جستجو بر اساس نام، کد یا دسته‌بندی"
                        autocomplete="off"
                    >
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"/>
                    </svg>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-0.5 sm:pr-0">
                @forelse($serviceOptions as $serviceOption)
                    <button
                        type="button"
                        @click="choose({{ (int) $serviceOption['id'] }})"
                        class="mb-2.5 flex w-full flex-col gap-2 rounded-2xl border px-4 py-3 text-right transition {{ (int) $selectedServiceId === (int) $serviceOption['id'] ? 'border-cyan-500 bg-cyan-50/30 ring-1 ring-cyan-100' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70' }}"
                        role="option"
                        aria-selected="{{ (int) $selectedServiceId === (int) $serviceOption['id'] ? 'true' : 'false' }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-[11px] font-medium text-slate-500">
                                    <span class="tracking-wide text-slate-400">{{ $serviceOption['code'] }}</span>
                                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                    <span>{{ $serviceOption['status'] }}</span>
                                </div>
                                <p class="mt-1.5 line-clamp-2 text-sm font-bold leading-5 text-slate-900">{{ $serviceOption['name'] }}</p>
                            </div>
                            <div class="shrink-0 text-left">
                                <p class="text-[10px] font-medium text-slate-400">قابل تحویل</p>
                                <p class="mt-0.5 text-sm font-bold text-cyan-700">{{ $serviceOption['remainingLabel'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-2">
                            <p class="line-clamp-2 min-w-0 flex-1 text-xs leading-5 text-slate-500">{{ $serviceOption['categorySummary'] ?: 'دسته‌بندی ثبت نشده است.' }}</p>
                            <span class="shrink-0 text-[11px] font-medium text-slate-500">{{ $serviceOption['categoriesCount'] }} دسته</span>
                        </div>
                    </button>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-xs font-bold text-slate-500">
                        خدمت قابل تخصیصی با این جستجو پیدا نشد.
                    </div>
                @endforelse
            </div>

            <div class="mt-2 flex items-center justify-between gap-2 border-t border-slate-200/70 pt-2">
                @if($selectedService)
                    <button
                        type="button"
                        @click="clear()"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                    >
                        پاک کردن انتخاب
                    </button>
                @endif
                <span class="ms-auto text-[11px] font-bold text-slate-400">حداکثر ۲۵ نتیجه نمایش داده می‌شود</span>
            </div>
        </div>
    </div>
    @error('selectedServiceId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>
