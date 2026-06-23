<div class="space-y-4">
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-bold">لطفا خطاهای فرم را بررسی کنید.</p>
            <ul class="mt-2 list-disc space-y-1 pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/70 p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">{{ $isEditing ? 'ویرایش خدمت متفرقه' : 'تخصیص خدمت و ایجاد خدمت متفرقه' }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        برای خدمات آماده، فقط از موجودی تأییدشده به مددکار تخصیص دهید. برای موارد خارج از فهرست، یک خدمت متفرقه جدید ایجاد و همان‌جا تخصیص داده می‌شود.
                    </p>
                </div>

                @if($isEditing)
                    <button
                        type="button"
                        wire:click="cancelEditing"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        انصراف
                    </button>
                @endif
            </div>
        </div>

        @if(!$isEditing)
            <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                <label class="group relative flex cursor-pointer gap-3 rounded-2xl border p-4 transition focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 {{ $mode === 'predefined' ? 'border-cyan-500 bg-cyan-50 shadow-sm ring-1 ring-cyan-200' : 'border-slate-200 bg-white hover:border-cyan-300 hover:bg-cyan-50/40' }}">
                    <input type="radio" value="predefined" wire:model.live="mode" class="sr-only">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $mode === 'predefined' ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-cyan-100 group-hover:text-cyan-700' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-black text-slate-900">تخصیص خدمت موجود</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">انتخاب خدمت تأییدشده و تخصیص مقدار از موجودی دسته‌بندی‌های آن.</span>
                    </span>
                    <span class="absolute left-4 top-4 h-3 w-3 rounded-full {{ $mode === 'predefined' ? 'bg-cyan-600' : 'bg-slate-200' }}"></span>
                </label>

                <label class="group relative flex cursor-pointer gap-3 rounded-2xl border p-4 transition focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2 {{ $mode === 'misc' ? 'border-emerald-500 bg-emerald-50 shadow-sm ring-1 ring-emerald-200' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/40' }}">
                    <input type="radio" value="misc" wire:model.live="mode" class="sr-only">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $mode === 'misc' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-emerald-100 group-hover:text-emerald-700' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-black text-slate-900">ایجاد خدمت متفرقه</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">ثبت {{ $nextMiscName }} برای موارد خارج از فهرست و تخصیص آن به مددکار.</span>
                    </span>
                    <span class="absolute left-4 top-4 h-3 w-3 rounded-full {{ $mode === 'misc' ? 'bg-emerald-600' : 'bg-slate-200' }}"></span>
                </label>
            </div>
        @endif
    </div>

    <form wire:submit.prevent="saveBatch" class="space-y-4">
        @if($mode === 'predefined' && !$isEditing)
            <section class="overflow-visible rounded-2xl border border-cyan-100 bg-white shadow-sm">
                <div class="border-b border-cyan-100 bg-cyan-50/60 px-4 py-3 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-white">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-black text-slate-900">تخصیص خدمت موجود</h2>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">ابتدا خدمت و مددکار را انتخاب کنید، سپس مقدار هر دسته‌بندی را وارد کنید.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">خدمت موجود برای تخصیص</label>
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
                                    this.$nextTick(() => this.$refs.serviceSearch?.focus());
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
                                $watch('open', (value) => {
                                    document.documentElement.classList.toggle('overflow-hidden', value && isMobileSheet);
                                });
                            "
                            x-on:keydown.escape.window="closeSelector()"
                        >
                            <button
                                type="button"
                                @click="toggleSelector()"
                                :aria-expanded="open.toString()"
                                aria-haspopup="dialog"
                                class="flex min-h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right text-sm font-bold text-slate-700 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50/50 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-500/10"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate">
                                        @if($selectedService)
                                            {{ $selectedService->code }} - {{ $selectedService->name ?: ($selectedService->serviceName?->name ?? 'بدون عنوان') }}
                                        @else
                                            یک خدمت تأییدشده را انتخاب کنید
                                        @endif
                                    </span>
                                    @if($selectedService)
                                        <span class="mt-0.5 block truncate text-xs font-semibold text-slate-500">
                                            {{ $selectedServiceCategories->count() }} دسته‌بندی آماده تخصیص
                                        </span>
                                    @endif
                                </span>
                                <svg class="ms-3 h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
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
                                            class="mb-2 flex w-full flex-col gap-2 rounded-2xl border px-3 py-3 text-right shadow-sm shadow-slate-200/70 transition hover:border-cyan-200 hover:bg-cyan-50/50 active:bg-cyan-50 {{ (int) $selectedServiceId === (int) $serviceOption['id'] ? 'border-cyan-300 bg-cyan-50/80' : 'border-slate-200 bg-white' }}"
                                            role="option"
                                            aria-selected="{{ (int) $selectedServiceId === (int) $serviceOption['id'] ? 'true' : 'false' }}"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <span class="text-[11px] font-bold text-slate-400">{{ $serviceOption['code'] }}</span>
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $serviceOption['status'] }}</span>
                                                        <span class="rounded-full bg-cyan-50 px-2 py-0.5 text-[10px] font-bold text-cyan-700">{{ $serviceOption['categoriesCount'] }} دسته</span>
                                                    </div>
                                                    <p class="mt-1 line-clamp-2 text-sm font-black leading-5 text-slate-900">{{ $serviceOption['name'] }}</p>
                                                </div>
                                                <span class="inline-flex shrink-0 flex-col items-center rounded-xl border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-center text-emerald-700">
                                                    <span class="text-[9px] font-bold leading-none">قابل تخصیص</span>
                                                    <span class="mt-1 text-xs font-black leading-none">{{ $serviceOption['remainingLabel'] }}</span>
                                                </span>
                                            </div>
                                            <p class="line-clamp-2 text-xs leading-5 text-slate-500">{{ $serviceOption['categorySummary'] ?: 'دسته‌بندی ثبت نشده است.' }}</p>
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

                    <div class="relative">
                        <label class="mb-2 block text-sm font-bold text-slate-700">مددکار</label>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="socialWorkerQuery"
                            wire:focus="$set('showSocialWorkerSuggestions', true)"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="نام یا کد مددکار"
                            autocomplete="off"
                        >
                        @if($socialWorkerQuery !== '')
                            <button type="button" wire:click="clearSocialWorkerSelection" class="absolute left-3 top-10 text-slate-400 hover:text-rose-600">×</button>
                        @endif
                        @error('socialWorkerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                        @if($showSocialWorkerSuggestions && mb_strlen(trim($socialWorkerQuery)) >= 2)
                            <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg">
                                @forelse($socialWorkerSuggestions as $worker)
                                    <button
                                        type="button"
                                        wire:click="selectSocialWorker({{ $worker->id }})"
                                        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right text-sm text-slate-700 transition hover:bg-cyan-50"
                                    >
                                        <span class="font-semibold">{{ $worker->full_name }}</span>
                                        <span class="text-xs text-slate-500">کد {{ $worker->worker_code }}</span>
                                    </button>
                                @empty
                                    <div class="px-4 py-3 text-sm text-slate-500">مددکاری یافت نشد.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                @if($selectedService)
                    <div class="mx-4 mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:mx-5 sm:mb-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-base font-black text-slate-900">{{ $selectedService->name ?: ($selectedService->serviceName?->name ?? 'خدمت انتخاب‌شده') }}</h2>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $selectedService->code }} - موجودی کل: {{ number_format((float) $selectedService->total_quantity, 2) }}</p>
                            </div>
                            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800">فقط تخصیص موجودی</span>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($selectedServiceCategories as $category)
                                @php
                                    $categoryMetrics = $selectedServiceCategoryMetrics[(int) $category->id] ?? ['allocated' => 0.0, 'assignable' => 0.0];
                                    $allocatedPreview = $this->predefinedAllocationForCategory((int) $category->id);
                                    $assignableQuantity = (float) $categoryMetrics['assignable'];
                                    $remainingPreview = max(0, $assignableQuantity - $allocatedPreview);
                                    $isOverAllocated = $allocatedPreview > $assignableQuantity;
                                @endphp
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900">{{ $category->name }}</p>
                                            <p class="mt-1 text-xs font-bold text-slate-500">موجودی: {{ number_format((float) $category->quantity, 2) }}</p>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                            {{ $unitOptions[$category->unit] ?? $category->unit }}
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        max="{{ $assignableQuantity }}"
                                        wire:model.live.debounce.250ms="predefinedAllocations.{{ $category->id }}"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-sm font-black text-slate-900"
                                        placeholder="0"
                                    >
                                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl {{ $isOverAllocated ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }} px-3 py-2">
                                        <span class="text-xs font-bold">مانده پس از تخصیص</span>
                                        <span class="text-sm font-black">{{ number_format($remainingPreview, 2) }}</span>
                                    </div>
                                    @error('predefinedAllocations.' . $category->id) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @else
            <section class="overflow-visible rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50/60 px-4 py-3 sm:px-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-base font-black text-slate-900">{{ $isEditing ? 'ویرایش خدمت متفرقه' : 'ایجاد ' . $nextMiscName }}</h2>
                                <p class="mt-0.5 text-xs leading-5 text-slate-500">دسته‌بندی‌ها را وارد کنید؛ مجموع آن‌ها موجودی اولیه و مقدار تخصیص به مددکار می‌شود.</p>
                            </div>
                        </div>
                        <span class="self-start rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 sm:self-auto">ایجاد و تخصیص</span>
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
                        <select wire:model="miscServiceType" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <label class="mb-2 block text-sm font-bold text-slate-700">مددکار</label>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="socialWorkerQuery"
                            wire:focus="$set('showSocialWorkerSuggestions', true)"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="نام یا کد مددکار"
                            autocomplete="off"
                        >
                        @if($showSocialWorkerSuggestions && mb_strlen(trim($socialWorkerQuery)) >= 2)
                            <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg">
                                @forelse($socialWorkerSuggestions as $worker)
                                    <button type="button" wire:click="selectSocialWorker({{ $worker->id }})" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right text-sm text-slate-700 transition hover:bg-emerald-50">
                                        <span class="font-semibold">{{ $worker->full_name }}</span>
                                        <span class="text-xs text-slate-500">کد {{ $worker->worker_code }}</span>
                                    </button>
                                @empty
                                    <div class="px-4 py-3 text-sm text-slate-500">مددکاری یافت نشد.</div>
                                @endforelse
                            </div>
                        @endif
                        @error('socialWorkerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" min="1" max="31" wire:model.blur="dateDay" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="روز">
                            <input type="number" min="1" max="12" wire:model.blur="dateMonth" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="ماه">
                            <input type="number" min="1300" max="1600" wire:model.blur="dateYear" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="سال">
                        </div>
                    </div>

                    <div class="sm:col-span-2 xl:col-span-4">
                        <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
                        <textarea rows="3" wire:model.blur="miscDescription" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                    </div>
                </div>

                <div class="space-y-3 px-4 pb-4 sm:px-5">
                    @foreach($miscCategories as $index => $category)
                        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[minmax(0,1fr)_160px_160px] xl:grid-cols-[minmax(0,1fr)_160px_160px_auto]">
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600">نام دسته‌بندی</label>
                                <input type="text" wire:model.blur="miscCategories.{{ $index }}.name" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="مثال: بسته غذایی">
                                @error("miscCategories.$index.name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600">مقدار</label>
                                <input type="number" min="0.01" step="0.01" wire:model.blur="miscCategories.{{ $index }}.quantity" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="0">
                                @error("miscCategories.$index.quantity") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600">واحد</label>
                                <select wire:model="miscCategories.{{ $index }}.unit" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                                    @foreach($unitOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end md:col-span-2 xl:col-span-1">
                                <button type="button" wire:click="removeCategory({{ $index }})" class="w-full rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-bold text-rose-700 transition hover:bg-rose-50 xl:w-auto">
                                    حذف
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-4 pb-5 sm:px-5">
                    <button
                        type="button"
                        wire:click="addCategory"
                        class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 sm:w-auto"
                    >
                        + افزودن دسته‌بندی
                    </button>
                </div>
            </section>
        @endif

        <div class="flex justify-end">
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800 sm:w-auto"
            >
                {{ $mode === 'predefined' && !$isEditing ? 'ثبت تخصیص خدمت موجود' : 'ثبت و تخصیص خدمت متفرقه' }}
            </button>
        </div>
    </form>
</div>
