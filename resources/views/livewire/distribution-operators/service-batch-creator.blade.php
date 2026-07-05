<div
    x-data="{
        shouldFocusValidationError: false,
        isEditingService: @js($isEditing),
        hasUnsavedChanges: @entangle('hasUnsavedChanges').live,
        isSaving: false,
        beforeUnloadHandler: null,
        navigationGuardHandler: null,
        init() {
            if (window.Livewire) {
                Livewire.hook('morph.updated', () => {
                    // A morph after a save attempt means the request did NOT redirect
                    // (validation failed and we're still on the form), so the intentional
                    // -save flag is cleared and the unsaved-change guard is restored.
                    this.isSaving = false;
                    this.focusFirstValidationError();
                });
            }

            this.navigationGuardHandler = () => this.canNavigateAway();
            window.distributionOperatorConfirmServiceEditNavigation = this.navigationGuardHandler;
            this.beforeUnloadHandler = (event) => {
                if (! this.hasDirtyEdit()) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            };

            window.addEventListener('beforeunload', this.beforeUnloadHandler);
        },
        destroy() {
            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            }

            if (window.distributionOperatorConfirmServiceEditNavigation === this.navigationGuardHandler) {
                delete window.distributionOperatorConfirmServiceEditNavigation;
            }
        },
        hasDirtyEdit() {
            // A confirmed final submit is an intentional exit, so the unsaved-change
            // guard must stand down while the save/redirect is in flight; it is
            // restored (isSaving = false) if the save fails validation.
            return this.isEditingService && this.hasUnsavedChanges && ! this.isSaving;
        },
        canNavigateAway() {
            return ! this.hasDirtyEdit()
                || window.confirm('تغییرات ذخیره‌نشده دارید. بدون ذخیره خارج می‌شوید؟');
        },
        confirmNavigation(event) {
            if (! this.canNavigateAway()) {
                event.preventDefault();
            }
        },
        markValidationFocusPending() {
            this.shouldFocusValidationError = true;
        },
        focusFirstValidationError() {
            if (! this.shouldFocusValidationError) {
                return;
            }

            this.$nextTick(() => {
                const error = this.$el.querySelector('[data-validation-error]');

                if (! error) {
                    this.shouldFocusValidationError = false;
                    return;
                }

                const scope = error.closest('[data-validation-scope]') || error;
                scope.scrollIntoView({ behavior: 'smooth', block: 'center' });

                window.setTimeout(() => {
                    const field = scope.querySelector('input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])');
                    field?.focus({ preventScroll: true });
                }, 250);

                this.shouldFocusValidationError = false;
            });
        },
    }"
    data-service-batch-creator-root
    class="space-y-3"
>
    @php
        $hasSelectedMode = $isEditing || in_array($mode, ['predefined', 'misc'], true);
    @endphp

    @if ($errors->any())
        <div data-validation-error class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-bold">لطفا خطاهای فرم را بررسی کنید.</p>
            <ul class="mt-2 list-disc space-y-1 pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$isEditing)
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/40 px-4 py-2.5 sm:px-5 sm:py-3">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 items-center rounded-lg bg-slate-900 px-2 text-[10px] font-black text-white">خدمت</span>
                        <h1 class="truncate text-sm font-black text-slate-900 sm:text-[15px]">تخصیص خدمت</h1>
                    </div>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500">انتخاب نوع ثبت خدمت برای ادامه</p>
                </div>
                <span class="shrink-0 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-500">
                    مرحله ۱
                </span>
            </div>
        </div>

            <div class="space-y-3 p-4 sm:p-5">

                <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                    <button type="button" wire:click="choosePredefinedMode" class="group relative flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 text-right transition focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 sm:px-3.5 {{ $mode === 'predefined' ? 'border-cyan-300 bg-cyan-50/70 shadow-[0_0_0_1px_rgba(34,211,238,0.08)]' : 'border-slate-200 bg-white hover:border-cyan-200 hover:bg-cyan-50/30' }}" aria-pressed="{{ $mode === 'predefined' ? 'true' : 'false' }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $mode === 'predefined' ? 'bg-cyan-500 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-cyan-100 group-hover:text-cyan-700' }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold leading-5 text-slate-800">{{ $mode === 'predefined' && $selectedService ? 'تغییر خدمت / پویش' : 'انتخاب خدمت / پویش' }}</span>
                            <span class="mt-0.5 block text-[11px] font-medium leading-4 text-slate-500">استفاده از خدمات از پیش تعریف‌شده</span>
                        </span>
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition {{ $mode === 'predefined' ? 'border-cyan-500 bg-cyan-500/10' : 'border-slate-300 bg-white' }}">
                            <span class="h-2 w-2 rounded-full {{ $mode === 'predefined' ? 'bg-cyan-500' : 'bg-transparent' }}"></span>
                        </span>
                    </button>

                    <button type="button" wire:click="requestMiscServiceName" class="group relative flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 text-right transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:px-3.5 {{ $mode === 'misc' ? 'border-emerald-300 bg-emerald-50/70 shadow-[0_0_0_1px_rgba(16,185,129,0.08)]' : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/30' }}" aria-pressed="{{ $mode === 'misc' ? 'true' : 'false' }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $mode === 'misc' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-emerald-100 group-hover:text-emerald-700' }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold leading-5 text-slate-800">تعریف خدمت جدید</span>
                            <span class="mt-0.5 block text-[11px] font-medium leading-4 text-slate-500">ثبت خدمت متفرقه برای مددکار</span>
                        </span>
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition {{ $mode === 'misc' ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-300 bg-white' }}">
                            <span class="h-2 w-2 rounded-full {{ $mode === 'misc' ? 'bg-emerald-500' : 'bg-transparent' }}"></span>
                        </span>
                    </button>
                </div>
                @unless($hasSelectedMode)
                    <p class="px-1 text-[11px] font-medium leading-5 text-slate-400">یکی از دو حالت را انتخاب کنید.</p>
                @endunless
            </div>

    </div>
    @endif

    @if($confirmingMiscServiceName)
        <div
            x-data="{
                viewportHeight: window.innerHeight,
                viewportOffsetTop: 0,
                viewportSyncHandler: null,
                init() {
                    document.body.classList.add('overflow-hidden');
                    document.documentElement.classList.add('overflow-hidden');

                    this.viewportSyncHandler = () => this.syncViewport();
                    window.addEventListener('resize', this.viewportSyncHandler);

                    if (window.visualViewport) {
                        window.visualViewport.addEventListener('resize', this.viewportSyncHandler);
                        window.visualViewport.addEventListener('scroll', this.viewportSyncHandler);
                    }

                    this.syncViewport();

                    this.$nextTick(() => {
                        this.focusInput();
                        this.scrollFieldIntoView();
                    });
                },
                destroy() {
                    document.body.classList.remove('overflow-hidden');
                    document.documentElement.classList.remove('overflow-hidden');

                    if (this.viewportSyncHandler) {
                        window.removeEventListener('resize', this.viewportSyncHandler);
                    }

                    if (window.visualViewport && this.viewportSyncHandler) {
                        window.visualViewport.removeEventListener('resize', this.viewportSyncHandler);
                        window.visualViewport.removeEventListener('scroll', this.viewportSyncHandler);
                    }
                },
                syncViewport() {
                    if (window.visualViewport) {
                        this.viewportHeight = window.visualViewport.height;
                        this.viewportOffsetTop = window.visualViewport.offsetTop;

                        return;
                    }

                    this.viewportHeight = window.innerHeight;
                    this.viewportOffsetTop = 0;
                },
                focusInput() {
                    const input = this.$refs.serviceNameInput;

                    if (! input) {
                        return;
                    }

                    input.focus({ preventScroll: true });

                    const length = input.value?.length ?? 0;

                    if (typeof input.setSelectionRange === 'function') {
                        input.setSelectionRange(length, length);
                    }
                },
                scrollFieldIntoView() {
                    this.$nextTick(() => {
                        const field = this.$refs.serviceNameField ?? this.$refs.serviceNameInput;

                        field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                },
                close() {
                    document.body.classList.remove('overflow-hidden');
                    document.documentElement.classList.remove('overflow-hidden');
                    $wire.cancelMiscServiceName();
                },
            }"
            x-on:keydown.escape.window.prevent="close()"
            class="fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm"
            x-bind:style="`height:${viewportHeight}px; transform:translateY(${viewportOffsetTop}px);`"
            role="dialog"
            aria-modal="true"
            aria-labelledby="misc-service-name-title"
        >
            <div class="absolute inset-0" @click="close()"></div>

            <div class="relative flex h-full items-end justify-center px-0 pt-2 sm:items-center sm:px-4 sm:py-6">
                <form
                    wire:submit.prevent="confirmMiscServiceName"
                    class="relative flex max-h-full w-full flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white text-right shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:max-w-md sm:rounded-[28px] sm:shadow-2xl"
                    x-bind:style="'padding-bottom:calc(env(safe-area-inset-bottom) + 0.75rem);'"
                    @click.stop
                >
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200/80 px-4 pb-3 pt-3 sm:border-b-0 sm:px-6 sm:pb-0 sm:pt-6">
                        <div class="flex-1 sm:hidden">
                            <div class="mx-auto h-1.5 w-14 rounded-full bg-slate-200"></div>
                        </div>
                        <button
                            type="button"
                            @click="close()"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                            aria-label="بستن"
                        >
                            <span class="text-xl leading-none">&times;</span>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 pb-4 pt-4 sm:px-6 sm:pb-6 sm:pt-2">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>

                        <div class="mt-4 text-center">
                            <h2 id="misc-service-name-title" class="text-lg font-extrabold text-slate-800">نام خدمت جدید</h2>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-600">نام اختصاصی این خدمت متفرقه را وارد کنید</p>
                        </div>

                        <div x-ref="serviceNameField" class="mt-5 scroll-mt-24 text-right">
                            <label for="misc-service-name-input" class="mb-2 block text-sm font-bold text-slate-700">نام خدمت</label>
                            <input
                                id="misc-service-name-input"
                                x-ref="serviceNameInput"
                                type="text"
                                wire:model.blur="miscServiceName"
                                maxlength="120"
                                autocomplete="off"
                                inputmode="text"
                                dir="rtl"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                placeholder="مثلاً سفره ام‌البنین - 14"
                                @focus="window.setTimeout(() => scrollFieldIntoView(), 180)"
                            >
                            @error('miscServiceName')
                                <p data-validation-error class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-4 pt-3 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" @click="close()" class="inline-flex min-h-12 flex-1 items-center justify-center rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200 active:scale-[0.98]">
                            انصراف
                        </button>
                        <button type="submit" class="inline-flex min-h-12 flex-1 items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500 active:scale-[0.98]">
                            تأیید و ادامه
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @php
        $isPredefinedWorkflow = $mode === 'predefined' && ! $isEditing;
        $editWorkerGroups = collect($miscWorkerGroups ?? []);
        $editWorkerStepComplete = $isEditing
            && $editWorkerGroups->isNotEmpty()
            && $editWorkerGroups->every(fn ($group) => (int) ($group['social_worker_id'] ?? 0) > 0);
        $editQuantityStepComplete = $isEditing
            && $editWorkerStepComplete
            && $editWorkerGroups->every(function ($group) use ($unitOptions) {
                $categories = $group['categories'] ?? [];

                if (empty($categories)) {
                    return false;
                }

                foreach ($categories as $category) {
                    if (trim((string) ($category['name'] ?? '')) === ''
                        || (float) ($category['quantity'] ?? 0) <= 0
                        || ! array_key_exists((string) ($category['unit'] ?? ''), $unitOptions)) {
                        return false;
                    }
                }

                return true;
            });
        $hasSelectedMiscServiceType = $isEditing || in_array($miscServiceType, array_keys($typeOptions), true);
        $serviceStepComplete = $isPredefinedWorkflow ? (bool) $selectedService : $hasSelectedMiscServiceType;
        $workerStepUnlocked = $serviceStepComplete;
        $workerStepComplete = $isEditing ? $editWorkerStepComplete : (bool) $socialWorkerId;
        $quantityStepUnlocked = $serviceStepComplete && $workerStepComplete;
        $quantityStepComplete = $isEditing ? $editQuantityStepComplete : $canRequestSaveConfirmation;
        $reviewStepUnlocked = $canRequestSaveConfirmation;
    @endphp

    @if($hasSelectedMode)
    <form wire:submit.prevent="requestSaveConfirmation" x-on:submit="markValidationFocusPending(); window.dispatchEvent(new CustomEvent('misc-worker-save-attempt'))" class="space-y-2.5">
        @if($mode === 'predefined' && !$isEditing)
            <section wire:key="predefined-service-batch-section" class="overflow-visible rounded-xl border border-cyan-100/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <div class="border-b border-cyan-100/80 bg-cyan-50/30 px-4 py-3 sm:px-5 sm:py-4">
                    @php
                        $predefinedHeaderTitle = $selectedService
                            ? ($selectedService->name ?: ($selectedService->serviceName?->name ?? 'خدمت انتخاب‌شده'))
                            : 'انتخاب خدمت / پویش';
                    @endphp
                    <div
                        wire:key="predefined-service-header-{{ $selectedServiceId ?: 'empty' }}"
                        class="flex items-center gap-2.5"
                        x-data="{
                            fullTitle: @js($predefinedHeaderTitle),
                            shownTitle: '',
                            hasAnimated: false,
                            cursorVisible: false,
                            init() {
                                this.typeTitle();
                            },
                            typeTitle() {
                                this.hasAnimated = true;
                                this.cursorVisible = true;
                                const chars = Array.from(this.fullTitle || '');
                                const step = 34;

                                chars.forEach((char, index) => {
                                    window.setTimeout(() => {
                                        this.shownTitle += char;

                                        if (index === chars.length - 1) {
                                            window.setTimeout(() => {
                                                this.cursorVisible = false;
                                            }, 450);
                                        }
                                    }, index * step);
                                });
                            },
                        }"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500 text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="overflow-hidden whitespace-nowrap text-[15px] font-bold tracking-[-0.01em] text-cyan-900/90" :title="fullTitle" aria-label="{{ $predefinedHeaderTitle }}" data-predefined-service-header-title="{{ $predefinedHeaderTitle }}">
                                <span x-text="shownTitle"></span><span x-show="cursorVisible" class="inline-block h-4 w-0.5 translate-y-0.5 rounded-full bg-cyan-500/80 align-middle" x-transition.opacity></span>
                            </h2>
                        </div>
                        @include('livewire.distribution-operators.partials.predefined-service-selector', ['compact' => true])
                    </div>
                </div>

                <div class="grid gap-3 p-3 sm:p-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    @if($workerStepUnlocked)
                        @include('livewire.distribution-operators.partials.social-worker-selector', [
                            'accent' => 'cyan',
                            'selectorId' => 'predefined-social-worker-selector',
                        ])
                    @else
                        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <p class="text-sm font-black text-slate-500">۲. انتخاب مددکار</p>
                            <p class="mt-1 text-xs font-bold leading-5 text-slate-400">ابتدا خدمت را انتخاب کنید تا انتخاب مددکار فعال شود.</p>
                        </div>
                    @endif

                    @if($selectedService && ! $quantityStepUnlocked)
                        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                            <p class="text-sm font-black text-slate-600">۳. ثبت مقدارها</p>
                            <p class="mt-1 text-xs font-bold leading-5 text-slate-400">پس از انتخاب مددکار، مقدارهای قابل تخصیص نمایش داده می‌شود.</p>
                        </div>
                    @elseif($selectedService)
                        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-slate-50/80 p-2.5">
                            <div class="mb-2 flex items-center justify-between gap-2 px-1">
                                <div class="min-w-0">
                                    <p class="truncate text-[11px] font-bold text-slate-500">{{ $selectedService->code }} · موجودی کل: {{ number_format((float) $selectedService->total_quantity, 2) }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-cyan-100 px-2 py-0.5 text-[10px] font-bold text-cyan-700">{{ count($selectedServiceCategories) }} دسته</span>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($selectedServiceCategories as $category)
                                    @php
                                        $categoryMetrics = $selectedServiceCategoryMetrics[(int) $category->id] ?? ['quantity' => (float) $category->quantity, 'allocated' => 0.0, 'assignable' => 0.0];
                                        $totalStockQuantity = (float) $categoryMetrics['quantity'];
                                        $assignableQuantity = (float) $categoryMetrics['assignable'];
                                        $allocatedPreview = $this->predefinedAllocationForCategory((int) $category->id);
                                        $remainingPreview = max(0, $assignableQuantity - $allocatedPreview);
                                        $isOverAllocated = $allocatedPreview > $assignableQuantity;
                                        $usesDecimalQuantity = in_array((string) $category->unit, ['kilogram', 'gram', 'kg', 'g'], true);
                                        $formatCategoryQuantity = fn (float|int $quantity) => number_format((float) $quantity, $usesDecimalQuantity ? 2 : 0);
                                        $assignableQuantityLabel = $formatCategoryQuantity($assignableQuantity);
                                        $allocatedPreviewLabel = $formatCategoryQuantity($allocatedPreview);
                                        $quantityInputStep = $usesDecimalQuantity ? '0.01' : '1';
                                        $quantityInputMode = $usesDecimalQuantity ? 'decimal' : 'numeric';
                                        $categoryUnitLabel = $unitOptions[$category->unit] ?? $category->unit;
                                    @endphp
                                    <div class="rounded-xl border {{ $isOverAllocated ? 'border-rose-200 bg-rose-50/70' : 'border-slate-200 bg-white' }} p-2.5 shadow-sm shadow-slate-900/[0.02]">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="min-w-0 truncate text-sm font-black text-slate-900">{{ $category->name }}</p>
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-cyan-100/70 bg-cyan-50/60 px-2 py-0.5 text-[11px]">
                                                <span class="font-bold tabular-nums text-slate-700" dir="ltr">{{ $assignableQuantityLabel }}</span>
                                                <span class="text-[10px] font-semibold text-slate-400">{{ $categoryUnitLabel }}</span>
                                            </span>
                                        </div>

                                        <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto_auto] gap-1.5">
                                            <input
                                                type="number"
                                                min="0"
                                                step="{{ $quantityInputStep }}"
                                                max="{{ $assignableQuantity }}"
                                                wire:model.live.debounce.250ms="predefinedAllocations.{{ $category->id }}"
                                                inputmode="{{ $quantityInputMode }}"
                                                class="min-w-0 rounded-lg border {{ $isOverAllocated ? 'border-rose-300 bg-rose-50 text-rose-900 focus:border-rose-400 focus:ring-rose-100' : 'border-slate-200 bg-slate-50 text-slate-900 focus:border-cyan-400 focus:ring-cyan-100' }} px-2.5 py-2 text-center text-sm font-black outline-none transition focus:ring-2"
                                                placeholder="۰"
                                                aria-invalid="{{ $isOverAllocated ? 'true' : 'false' }}"
                                            >
                                            <button
                                                type="button"
                                                wire:click="useMaxPredefinedAllocation({{ $category->id }})"
                                                @disabled($assignableQuantity <= 0)
                                                class="rounded-lg border border-cyan-100 bg-cyan-50 px-2 py-2 text-[11px] font-bold text-cyan-700 transition hover:bg-cyan-100 disabled:opacity-40"
                                            >
                                                حداکثر
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="clearPredefinedAllocation({{ $category->id }})"
                                                @disabled($allocatedPreview <= 0)
                                                class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[11px] font-bold text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-40"
                                            >
                                                پاک
                                            </button>
                                        </div>

                                        @if($isOverAllocated)
                                            <p class="mt-1.5 text-[11px] font-bold text-rose-600">بیشتر از موجودی قابل تخصیص</p>
                                        @elseif($allocatedPreview > 0)
                                            <p class="mt-1.5 text-[11px] font-semibold text-slate-400">{{ $allocatedPreviewLabel }} از {{ $assignableQuantityLabel }}</p>
                                        @endif

                                        @error('predefinedAllocations.' . $category->id) <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section wire:key="misc-service-batch-section" class="overflow-visible rounded-xl border border-emerald-100/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <div class="border-b border-emerald-100/80 bg-emerald-50/30 px-4 py-3 sm:px-5 sm:py-4">
                    @php
                        $miscHeaderTitle = $isEditing ? 'جزئیات خدمت' : trim($miscServiceName);
                        $miscHeaderTitle = $miscHeaderTitle !== '' ? $miscHeaderTitle : 'تعریف خدمت جدید';
                    @endphp
                    <div
                        class="flex items-center gap-2.5"
                        x-data="{
                            fullTitle: @js($miscHeaderTitle),
                            shownTitle: @js($isEditing ? $miscHeaderTitle : ''),
                            hasAnimated: @js($isEditing),
                            cursorVisible: false,
                            init() {
                                if (this.hasAnimated) {
                                    return;
                                }

                                this.typeTitle();
                            },
                            typeTitle() {
                                this.hasAnimated = true;
                                this.cursorVisible = true;
                                const chars = Array.from(this.fullTitle || '');
                                const step = 34;

                                chars.forEach((char, index) => {
                                    window.setTimeout(() => {
                                        this.shownTitle += char;

                                        if (index === chars.length - 1) {
                                            window.setTimeout(() => {
                                                this.cursorVisible = false;
                                            }, 450);
                                        }
                                    }, index * step);
                                });
                            },
                        }"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white">
                            @if($isEditing)
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M11 5H6.5A2.5 2.5 0 0 0 4 7.5v10A2.5 2.5 0 0 0 6.5 20h10A2.5 2.5 0 0 0 19 17.5V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M13 4.8 18.2 10M11.5 11.5 17.8 5.2a1.84 1.84 0 1 1 2.6 2.6l-6.3 6.3-3.1.6.5-3.2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 7.5h10M7 12h10M7 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M5.5 3.5h13A2.5 2.5 0 0 1 21 6v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18V6a2.5 2.5 0 0 1 2.5-2.5Z" stroke="currentColor" stroke-width="2" />
                                </svg>
                            @endif
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-extrabold text-emerald-950" :title="fullTitle" aria-label="{{ $miscHeaderTitle }}" data-service-header-title="{{ $miscHeaderTitle }}">
                                <span x-text="shownTitle"></span><span x-show="cursorVisible" class="inline-block h-4 w-0.5 translate-y-0.5 rounded-full bg-emerald-500 align-middle" x-transition.opacity></span>
                            </h2>
                        </div>
                    </div>
                </div>

                @if($isEditing)
                    @include('livewire.distribution-operators.partials.misc-edit-body')
                @else
                <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4">
                    <div class="sm:col-span-2 xl:col-span-1">
                        <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
                        <div
                            x-data="{
                                serviceType: @entangle('miscServiceType').live,
                                get options() {
                                    return [
                                        {
                                            value: 'individual',
                                            title: 'شخصی',
                                            subtitle: 'ثبت برای مددجو',
                                            activeClass: 'border-cyan-300 bg-cyan-50/80 shadow-sm',
                                            inactiveClass: 'border-slate-200 bg-white hover:border-cyan-200 hover:bg-cyan-50/40',
                                            iconActiveClass: 'bg-cyan-600 text-white',
                                            iconInactiveClass: 'bg-cyan-100 text-cyan-700',
                                            titleActiveClass: 'text-cyan-950',
                                            titleInactiveClass: 'text-slate-800',
                                            subtitleActiveClass: 'text-cyan-700',
                                            subtitleInactiveClass: 'text-slate-500',
                                            dotActiveClass: 'bg-cyan-600',
                                        },
                                        {
                                            value: 'family',
                                            title: 'خانوادگی',
                                            subtitle: 'ثبت برای سرپرست',
                                            activeClass: 'border-amber-400 bg-amber-50 shadow-sm',
                                            inactiveClass: 'border-slate-200 bg-white hover:border-amber-200 hover:bg-amber-50/40',
                                            iconActiveClass: 'bg-amber-500 text-white',
                                            iconInactiveClass: 'bg-amber-100 text-amber-700',
                                            titleActiveClass: 'text-amber-950',
                                            titleInactiveClass: 'text-slate-800',
                                            subtitleActiveClass: 'text-amber-700',
                                            subtitleInactiveClass: 'text-slate-500',
                                            dotActiveClass: 'bg-amber-500',
                                        },
                                    ];
                                },
                                selectServiceType(value) {
                                    this.serviceType = value;
                                },
                            }"
                        >
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                                <template x-for="item in options" :key="item.value">
                                    <button
                                        type="button"
                                        x-on:click="selectServiceType(item.value)"
                                        class="group flex min-h-[56px] w-full items-center gap-2.5 rounded-2xl border px-3 py-2.5 text-right transition focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                        x-bind:class="serviceType === item.value
                                            ? item.activeClass
                                            : item.inactiveClass"
                                        x-bind:aria-pressed="(serviceType === item.value).toString()"
                                    >
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition"
                                            x-bind:class="serviceType === item.value
                                                ? item.iconActiveClass
                                                : item.iconInactiveClass"
                                        >
                                            <template x-if="item.value === 'individual'">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </template>
                                            <template x-if="item.value === 'family'">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M9 11a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm6 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4 19a5 5 0 0 1 10 0M13 19a5 5 0 0 1 7 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </template>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span
                                                x-text="item.title"
                                                class="block text-sm font-black leading-5"
                                                x-bind:class="serviceType === item.value ? item.titleActiveClass : item.titleInactiveClass"
                                            ></span>
                                            <span
                                                x-text="item.subtitle"
                                                class="mt-0.5 block text-[11px] font-semibold leading-4"
                                                x-bind:class="serviceType === item.value ? item.subtitleActiveClass : item.subtitleInactiveClass"
                                            ></span>
                                        </span>
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full transition"
                                            x-bind:class="serviceType === item.value ? item.dotActiveClass : 'bg-slate-200'"
                                            aria-hidden="true"
                                        ></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    @if($workerStepUnlocked)
                        @include('livewire.distribution-operators.partials.social-worker-selector', [
                            'accent' => 'emerald',
                            'selectorId' => 'misc-social-worker-selector',
                        ])
                    @else
                        <div class="sm:col-span-2 xl:col-span-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-center">
                                <p class="text-sm font-black text-slate-600">۲. انتخاب مددکار</p>
                                <p class="mt-1 text-xs font-bold leading-5 text-slate-400">ابتدا نوع خدمت را تعیین کنید</p>
                            </div>
                        </div>
                    @endif
                </div>

                @if($workerStepUnlocked && ! $quantityStepUnlocked)
                    <div class="mx-4 mb-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-center sm:mx-5">
                        <p class="text-sm font-black text-slate-600">۳. ثبت دسته‌بندی‌ها</p>
                        <p class="mt-1 text-xs font-bold leading-5 text-slate-400">پس از انتخاب مددکار، دسته‌بندی‌ها و مقدار خدمت را وارد کنید.</p>
                    </div>
                @elseif($quantityStepUnlocked)
                <div
                    x-data="{
                        scrollToNewCategory(event) {
                            const index = event.detail?.index;

                            this.$nextTick(() => {
                                const selector = Number.isInteger(index)
                                    ? `[data-service-category-index='${index}']`
                                    : '[data-service-category-index]:last-of-type';
                                const category = this.$el.querySelector(selector);

                                if (! category) {
                                    return;
                                }

                                category.scrollIntoView({ behavior: 'smooth', block: 'center' });

                                window.setTimeout(() => {
                                    const input = category.querySelector('[data-category-name-input]');
                                    input?.focus({ preventScroll: true });

                                    // Keyboard-aware scroll adjustment on mobile
                                    if (window.visualViewport) {
                                        const handleKeyboardAppearance = () => {
                                            const rect = category.getBoundingClientRect();
                                            const bottomPadding = 60;

                                            if (rect.bottom > window.visualViewport.height - bottomPadding) {
                                                const offset = rect.bottom - (window.visualViewport.height - bottomPadding);
                                                window.scrollBy({ top: offset, behavior: 'smooth' });
                                            }
                                        };

                                        window.visualViewport.addEventListener('resize', handleKeyboardAppearance);
                                    }
                                }, 350);
                            });
                        }
                    }"
                    x-on:service-category-added.window="scrollToNewCategory($event)"
                    class="px-4 pb-4 sm:px-5"
                >
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5 sm:px-3.5">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-black text-slate-800">دسته‌بندی‌های خدمت</p>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ count($miscCategories) }}</span>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach($miscCategories as $index => $category)
                                <div data-service-category-index="{{ $index }}"
                                     x-data="{
                                         open: {{ $index === count($miscCategories) - 1 ? 'true' : 'false' }},
                                         isNewCategory: {{ $index === count($miscCategories) - 1 ? 'true' : 'false' }},
                                         nameHasValue: @js(trim((string) ($category['name'] ?? '')) !== ''),
                                     }"
                                     class="p-3 sm:p-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-black text-emerald-700">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-bold text-slate-800">
                                            {{ $category['name'] ?: 'دسته‌بندی ' . ($index + 1) }}
                                        </span>
                                        <button
                                            type="button"
                                            x-on:click="open = !open"
                                            class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                        >
                                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none">
                                                <path d="M19 9l-7 7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="
                                                window.dispatchEvent(new CustomEvent('open-notification-modal', {
                                                    detail: {
                                                        config: {
                                                            type: 'warning',
                                                            title: 'حذف دسته‌بندی',
                                                            message: 'آیا از حذف این دسته‌بندی مطمئن هستید؟',
                                                            buttons: [
                                                                {
                                                                    label: 'حذف',
                                                                    action: 'event',
                                                                    event: 'confirm-misc-category-delete',
                                                                    payload: { index: {{ $index }} },
                                                                    variant: 'danger',
                                                                },
                                                                {
                                                                    label: 'انصراف',
                                                                    action: 'close',
                                                                    variant: 'secondary',
                                                                },
                                                            ],
                                                        },
                                                    },
                                                }))
                                            "
                                            class="shrink-0 rounded-lg p-1.5 text-rose-400 transition hover:bg-rose-50 hover:text-rose-600"
                                            aria-label="حذف دسته‌بندی {{ $index + 1 }}"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div x-show="open" x-collapse class="mt-3 space-y-2.5">
                                        <input
                                            type="text"
                                            wire:model.blur="miscCategories.{{ $index }}.name"
                                            data-category-name-input
                                            @input="nameHasValue = $el.value.trim().length > 0"
                                            x-bind:class="isNewCategory && ! nameHasValue
                                                ? 'border-rose-400 bg-rose-50 ring-2 ring-rose-200'
                                                : 'border-slate-200 bg-slate-50 focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100'"
                                            class="w-full rounded-lg border px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400"
                                            placeholder="نام دسته‌بندی"
                                        >
                                        @error("miscCategories.$index.name") <p class="text-[11px] text-rose-600">{{ $message }}</p> @enderror

                                        <div class="grid grid-cols-2 gap-2">
                                            <input
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                wire:model.blur="miscCategories.{{ $index }}.quantity"
                                                class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                                                placeholder="مقدار"
                                            >
                                            <select wire:model="miscCategories.{{ $index }}.unit" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                                @foreach($unitOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error("miscCategories.$index.quantity") <p class="text-[11px] text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endforeach

                            <button
                                type="button"
                                wire:click="addCategory"
                                wire:loading.attr="disabled"
                                wire:target="addCategory"
                                class="flex w-full items-center justify-center gap-2 px-3 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <span class="text-lg leading-none" wire:loading.remove wire:target="addCategory">+</span>
                                <svg wire:loading wire:target="addCategory" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0110 10" stroke-linecap="round"></path></svg>
                                افزودن دسته‌بندی
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 px-4 pb-5 sm:grid-cols-2 sm:px-5 xl:grid-cols-4">
                    <div class="sm:col-span-2">
                        <div x-data="jalaliDateTimeField($wire.entangle('date').live)">
                            <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    x-ref="input"
                                    x-model="draft"
                                    x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                                    x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                                    x-on:jalali-picker-open="handlePickerOpen()"
                                    x-on:jalali-picker-close="handlePickerClose()"
                                    x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                                    readonly
                                    inputmode="none"
                                    autocomplete="off"
                                    data-jdp-readonly
                                    data-jdp
                                    placeholder="1405/04/03"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pe-11 text-sm font-medium text-slate-700 outline-none transition ltr:text-left focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                                >
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <i class="bi bi-calendar2-event text-base"></i>
                                </span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">تاریخ با تقویم شمسی ثبت می‌شود (پیش فرض: تاریح امروز)</p>
                            @error('date') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="sm:col-span-2 xl:col-span-4">
                        <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
                        <textarea rows="3" wire:model.blur="miscDescription" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                    </div>
                </div>
                @endif
                @endif
            </section>
        @endif

        @if($hasPredefinedOverAllocation)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                مقدار یک یا چند دسته‌بندی از موجودی قابل تخصیص بیشتر است. برای ثبت، مقدار را اصلاح کنید یا از گزینه حداکثر استفاده کنید.
            </div>
        @endif

        @if(! $canRequestSaveConfirmation && !empty($savePreventionMessages))
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 px-3 py-2.5 text-amber-900">
                <div class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[10px] font-black text-amber-700">!</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-black leading-5 text-amber-800">برای ادامه، این موارد را کامل کنید</p>
                        <ul class="mt-1.5 space-y-1 text-[11px] font-semibold leading-5 text-amber-700">
                    @foreach($savePreventionMessages as $message)
                            <li class="flex items-start gap-1.5">
                                <span class="mt-[7px] h-1 w-1 shrink-0 rounded-full bg-amber-500"></span>
                                <span>{{ $message }}</span>
                            </li>
                    @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @php
            $reviewRows = $reviewSummary['rows'] ?? [];
            $reviewItemCount = count($reviewRows);
            // Per-worker breakdown so a multi-worker edit can be confirmed as a whole (Item 4).
            $reviewGroups = $reviewSummary['groups'] ?? [];
            $reviewWorkerCount = count($reviewGroups);

            // In edit mode the save action is the most-repeated task, so keep it
            // pinned to the bottom of the viewport instead of buried below every
            // worker accordion. Stays below the worker/category sheets (z-40+).
            if ($isEditing) {
                $reviewCardClasses = 'sticky bottom-[calc(0.5rem+env(safe-area-inset-bottom))] z-30 border-emerald-200 bg-white shadow-[0_-6px_24px_rgba(15,23,42,0.12)]';
            } else {
                $reviewCardClasses = $reviewStepUnlocked
                    ? 'border-emerald-200 bg-white shadow-sm shadow-emerald-900/5'
                    : 'border-slate-200 bg-slate-50';
            }
        @endphp
        <div class="rounded-2xl border px-3 py-2.5 {{ $reviewCardClasses }}">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0 flex-1" @if($isEditing && $reviewWorkerCount > 1) x-data="{ showBreakdown: false }" @endif>
                    <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
{{--                        <span class="inline-flex h-6 items-center rounded-lg {{ $reviewStepUnlocked ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' }} px-2.5 text-[11px] font-black">۴</span>--}}
                        <span class="max-w-full truncate text-sm font-black {{ $reviewStepUnlocked ? 'text-slate-900' : 'text-slate-500' }}">{{ $reviewSummary['service_name'] ?? '-' }}</span>
                        @if(!empty($reviewSummary['service_type']))
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $reviewSummary['service_type'] }}</span>
                        @elseif(!empty($reviewSummary['service_code']))
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $reviewSummary['service_code'] }}</span>
                        @endif
                    </div>
                    @if($isEditing && $reviewWorkerCount > 1)
                        {{-- Multi-worker edit: one-line total that expands into a per-worker breakdown. --}}
                        <button
                            type="button"
                            @click="showBreakdown = ! showBreakdown"
                            :aria-expanded="showBreakdown.toString()"
                            class="mt-1 flex w-full items-center gap-1.5 text-right text-[11px] font-bold leading-5 text-slate-500"
                        >
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 font-black text-emerald-700">
                                <i class="bi bi-people-fill text-[10px]"></i>
                                {{ $reviewWorkerCount }} مددکار
                            </span>
                            <span class="text-slate-300">/</span>
                            {{ $reviewItemCount }} قلم
                            <span class="text-slate-300">/</span>
                            جمع {{ $reviewSummary['total_quantity_label'] ?? '0.00' }}
                            <i class="bi bi-chevron-down mr-auto shrink-0 text-slate-400 transition-transform duration-200" :class="showBreakdown ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="showBreakdown" x-collapse style="display: none;" class="mt-2 space-y-1.5 max-h-[50vh] overflow-y-auto">
                            @foreach($reviewGroups as $reviewGroup)
                                <div class="flex items-center gap-2 rounded-lg border border-slate-100 bg-slate-50/70 px-2.5 py-1.5">
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-700">{{ $reviewGroup['worker_name'] ?? 'مددکار' }}</span>
                                    @if(!empty($reviewGroup['worker_code']))
                                        <span class="shrink-0 text-[10px] font-bold text-slate-400">کد {{ $reviewGroup['worker_code'] }}</span>
                                    @endif
                                    <span class="shrink-0 text-[10px] font-bold text-slate-500">{{ count($reviewGroup['rows'] ?? []) }} قلم</span>
                                    <span class="shrink-0 text-[10px] font-black text-emerald-700">جمع {{ $reviewGroup['total_quantity_label'] ?? '0.00' }}</span>
                                </div>
                            @endforeach
                            <p class="px-1 text-[10px] font-bold text-slate-400">تاریخ ثبت: {{ $reviewSummary['date_label'] ?? '-' }}</p>
                        </div>
                    @else
                        <p class="mt-1 truncate text-[11px] font-bold leading-5 text-slate-500">
                            {{ $reviewSummary['worker_name'] ?? '-' }}
                            <span class="mx-1 text-slate-300">/</span>
                            {{ $reviewSummary['date_label'] ?? '-' }}
                            <span class="mx-1 text-slate-300">/</span>
                            {{ $reviewItemCount }} قلم
                            <span class="mx-1 text-slate-300">/</span>
                            جمع {{ $reviewSummary['total_quantity_label'] ?? '0.00' }}
                        </p>
                    @endif
                </div>
                <div class="flex flex-col gap-2 xl:shrink-0 xl:flex-row xl:items-center xl:gap-2.5">
                    {{-- Dirty-state cue: only in edit mode, clears once the save resets hasUnsavedChanges (Item 10). --}}
                    <span
                        x-show="isEditingService && hasUnsavedChanges"
                        style="display: none;"
                        class="inline-flex items-center gap-1.5 self-start rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black text-amber-700 ring-1 ring-amber-200"
                    >
                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        تغییرات ذخیره‌نشده
                    </span>
                    <button
                        type="submit"
                        @disabled(! $isEditing && ! $canRequestSaveConfirmation)
                        wire:loading.attr="disabled"
                        wire:target="requestSaveConfirmation"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 px-4 py-3 text-xs font-black text-white shadow-sm shadow-emerald-700/25 transition hover:border-emerald-300 hover:from-emerald-600 hover:via-emerald-700 hover:to-teal-700 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-300 disabled:text-slate-500 disabled:shadow-none focus:outline-none focus:ring-4 focus:ring-emerald-500/20 active:scale-[0.99] active:from-emerald-700 active:via-emerald-800 active:to-teal-800 sm:w-auto"
                    >
                        <span wire:loading.remove wire:target="requestSaveConfirmation">
                            {{ $mode === 'predefined' && !$isEditing ? 'تأیید تحویل' : 'تأیید خدمت' }}
                        </span>
                        <span wire:loading wire:target="requestSaveConfirmation">در حال بررسی...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if($confirmingBatchSave)
        <div
            x-data="{
                scrollLocked: false,
                init() {
                    this.lockPageScroll();
                    this.$nextTick(() => this.$refs.cancelButton?.focus({ preventScroll: true }));
                },
                destroy() {
                    this.releasePageScrollLock();
                },
                lockPageScroll() {
                    if (this.scrollLocked) {
                        return;
                    }

                    const currentLocks = Number(document.documentElement.dataset.distributionOperatorSheetLocks || 0);

                    document.documentElement.dataset.distributionOperatorSheetLocks = String(currentLocks + 1);
                    document.documentElement.classList.add('overflow-hidden');
                    this.scrollLocked = true;
                },
                releasePageScrollLock() {
                    if (! this.scrollLocked) {
                        return;
                    }

                    const currentLocks = Number(document.documentElement.dataset.distributionOperatorSheetLocks || 0);
                    const remainingLocks = Math.max(0, currentLocks - 1);

                    if (remainingLocks > 0) {
                        document.documentElement.dataset.distributionOperatorSheetLocks = String(remainingLocks);
                    } else {
                        delete document.documentElement.dataset.distributionOperatorSheetLocks;
                        document.documentElement.classList.remove('overflow-hidden');
                    }

                    this.scrollLocked = false;
                },
                close() {
                    this.releasePageScrollLock();
                    $wire.cancelSaveConfirmation();
                },
            }"
            x-on:keydown.escape.window.prevent="close()"
            @click.self="close()"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="operator-confirmation-title"
        >
            <div class="flex max-h-[92svh] w-full max-w-3xl flex-col rounded-t-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[88vh] sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                    <div class="min-w-0">
                        <h3 id="operator-confirmation-title" class="text-lg font-black text-slate-900">{{ $confirmationSummary['title'] ?? 'تأیید نهایی' }}</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500">اگر خلاصه زیر درست است، ثبت نهایی را تأیید کنید.</p>
                    </div>
                    <button
                        type="button"
                        x-ref="cancelButton"
                        @click="close()"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                        aria-label="بستن"
                    >
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                    @php
                        $confirmationRows = $confirmationSummary['rows'] ?? [];
                        $confirmationItemCount = count($confirmationRows);
                        $depletingRowsCount = (int) ($confirmationSummary['depleting_rows_count'] ?? 0);
                        $largeQuantityRowsCount = (int) ($confirmationSummary['large_quantity_rows_count'] ?? 0);
                        $hasEmptyDescriptionWarning = (bool) ($confirmationSummary['has_empty_description_warning'] ?? false);
                    @endphp
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="mb-3 flex items-start gap-2 rounded-2xl bg-white/70 px-3 py-2 ring-1 ring-emerald-100">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-black text-emerald-700">!</span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-emerald-900">قبل از ثبت نهایی، خدمت، مددکار و مقدار کل را دوباره بررسی کنید.</p>
                                <p class="mt-1 truncate text-[11px] font-bold text-emerald-700">
                                    {{ $confirmationSummary['service_name'] ?? '-' }}
                                    <span class="mx-1 text-emerald-300">/</span>
                                    {{ $confirmationSummary['worker_name'] ?? '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <p class="text-[11px] font-bold text-emerald-700">اقلام</p>
                                <p class="mt-1 text-xl font-black text-emerald-950">{{ $confirmationItemCount }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-emerald-700">جمع مقدار</p>
                                <p class="mt-1 text-xl font-black text-emerald-950">{{ $confirmationSummary['total_quantity_label'] ?? '0.00' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-emerald-700">تاریخ</p>
                                <p class="mt-1 text-sm font-black text-emerald-950">{{ $confirmationSummary['date_label'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($depletingRowsCount > 0 || $largeQuantityRowsCount > 0 || $hasEmptyDescriptionWarning)
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            @if($depletingRowsCount > 0)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                                    <p class="text-[11px] font-black text-amber-800">اتمام موجودی</p>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-amber-700">{{ $depletingRowsCount }} قلم بعد از ثبت مانده صفر خواهد داشت.</p>
                                </div>
                            @endif

                            @if($largeQuantityRowsCount > 0)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                                    <p class="text-[11px] font-black text-amber-800">مقدار بزرگ</p>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-amber-700">{{ $largeQuantityRowsCount }} قلم مقدار غیرمعمول بزرگ دارد.</p>
                                </div>
                            @endif

                            @if($hasEmptyDescriptionWarning)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                                    <p class="text-[11px] font-black text-amber-800">بدون توضیحات</p>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-amber-700">برای خدمت متفرقه توضیحی ثبت نشده است.</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($reviewWarnings))
                        <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                            <p class="text-xs font-black text-amber-800">هشدارها</p>
                            <ul class="mt-1.5 space-y-1 text-xs font-semibold leading-5 text-amber-700">
                                @foreach($reviewWarnings as $warning)
                                    <li class="flex items-start gap-1.5">
                                        <span class="mt-[8px] h-1 w-1 shrink-0 rounded-full bg-amber-500"></span>
                                        <span>{{ $warning }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-500">خدمت</p>
                            <p class="mt-1 text-sm font-black text-slate-900">{{ $confirmationSummary['service_name'] ?? '-' }}</p>
                            @if(!empty($confirmationSummary['service_code']))
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $confirmationSummary['service_code'] }}</p>
                            @endif
                            @if(!empty($confirmationSummary['service_type']))
                                <p class="mt-2 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">{{ $confirmationSummary['service_type'] }}</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-500">مددکار</p>
                            <p class="mt-1 text-sm font-black text-slate-900">{{ $confirmationSummary['worker_name'] ?? '-' }}</p>
                            @if(!empty($confirmationSummary['worker_code']))
                                <p class="mt-1 text-xs font-bold text-slate-500">کد {{ $confirmationSummary['worker_code'] }}</p>
                            @endif
                        </div>
                    </div>

                    @if(!empty($confirmationSummary['description']))
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold text-slate-500">توضیحات</p>
                            <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-700">{{ $confirmationSummary['description'] }}</p>
                        </div>
                    @endif

                    @if(!empty($confirmationSummary['groups']))
                        <div class="mt-3 space-y-3">
                            @foreach($confirmationSummary['groups'] as $group)
                                <div class="rounded-2xl border border-slate-200 bg-white">
                                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-3 py-2.5">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                <i class="bi bi-person-badge text-sm"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-black text-slate-900">{{ $group['worker_name'] }}</p>
                                                @if(!empty($group['worker_code']))
                                                    <p class="text-[11px] font-bold text-slate-400">کد {{ $group['worker_code'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ count($group['rows']) }} قلم · جمع {{ $group['total_quantity_label'] }}</span>
                                    </div>
                                    <div class="divide-y divide-slate-100">
                                        @foreach($group['rows'] as $row)
                                            <div class="grid gap-2 px-3 py-2.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-black text-slate-800">{{ $row['name'] }}</p>
                                                    <p class="mt-0.5 text-xs font-bold text-slate-500">{{ $row['unit_label'] }}</p>
                                                    @if(!empty($row['is_large_quantity']))
                                                        <p class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700 ring-1 ring-amber-100">مقدار بزرگ؛ بررسی شود</p>
                                                    @endif
                                                </div>
                                                <div class="rounded-xl {{ !empty($row['is_large_quantity']) ? 'bg-amber-50 ring-1 ring-amber-100' : 'bg-slate-50' }} px-3 py-2 text-right">
                                                    <p class="text-[10px] font-bold {{ !empty($row['is_large_quantity']) ? 'text-amber-700' : 'text-slate-500' }}">مقدار</p>
                                                    <p class="text-sm font-black {{ !empty($row['is_large_quantity']) ? 'text-amber-900' : 'text-slate-900' }}">{{ $row['quantity_label'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-white">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-3">
                            <p class="text-sm font-black text-slate-900">جزئیات اقلام</p>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $confirmationItemCount }} قلم</span>
                        </div>
                        <div class="max-h-56 divide-y divide-slate-100 overflow-y-auto">
                            @forelse($confirmationRows as $row)
                                <div class="grid gap-2 px-3 py-3 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center {{ !empty($row['consumes_all_remaining']) ? 'bg-amber-50/40' : '' }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-800">{{ $row['name'] }}</p>
                                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $row['unit_label'] }}</p>
                                        @if(!empty($row['consumes_all_remaining']))
                                            <p class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700 ring-1 ring-amber-100">بعد از ثبت مانده صفر می‌شود</p>
                                        @elseif(!empty($row['is_large_quantity']))
                                            <p class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700 ring-1 ring-amber-100">مقدار بزرگ؛ بررسی شود</p>
                                        @endif
                                    </div>
                                    <div class="rounded-xl {{ !empty($row['is_large_quantity']) ? 'bg-amber-50 ring-1 ring-amber-100' : 'bg-slate-50' }} px-3 py-2 text-right">
                                        <p class="text-[10px] font-bold {{ !empty($row['is_large_quantity']) ? 'text-amber-700' : 'text-slate-500' }}">مقدار</p>
                                        <p class="text-sm font-black {{ !empty($row['is_large_quantity']) ? 'text-amber-900' : 'text-slate-900' }}">{{ $row['quantity_label'] }}</p>
                                    </div>
                                    @if(($confirmationSummary['mode'] ?? '') === 'predefined')
                                        <div class="rounded-xl {{ !empty($row['consumes_all_remaining']) ? 'bg-amber-100 ring-1 ring-amber-200' : 'bg-emerald-50' }} px-3 py-2 text-right">
                                            <p class="text-[10px] font-bold {{ !empty($row['consumes_all_remaining']) ? 'text-amber-800' : 'text-emerald-700' }}">مانده بعد از ثبت</p>
                                            <p class="text-sm font-black {{ !empty($row['consumes_all_remaining']) ? 'text-amber-950' : 'text-emerald-800' }}">{{ $row['remaining_label'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-sm font-bold text-slate-500">مقداری برای ثبت انتخاب نشده است.</div>
                            @endforelse
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-3 sm:flex-row sm:justify-end sm:px-5 sm:pb-4">
                    <button
                        type="button"
                        @click="close()"
                        class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                    >
                        بازگشت به ویرایش
                    </button>
                    <button
                        type="button"
                        x-on:click="isSaving = true"
                        wire:click="confirmSaveBatch"
                        wire:loading.attr="disabled"
                        wire:target="confirmSaveBatch"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
                    >
                        <span wire:loading.remove wire:target="confirmSaveBatch">تأیید و ثبت نهایی</span>
                        <span wire:loading wire:target="confirmSaveBatch">در حال ثبت...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    @endif
</div>
