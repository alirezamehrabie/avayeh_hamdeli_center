<div class="space-y-4">
    @php
        $hasSelectedMode = $isEditing || in_array($mode, ['predefined', 'misc'], true);
    @endphp
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
        <div class="border-b border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-5 sm:py-3.5">
            <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-black leading-tight text-slate-900 sm:text-[1.35rem]">{{ $isEditing ? 'ویرایش خدمت متفرقه' : 'تخصیص خدمت' }}</h1>
                    <p class="mt-1 text-[11px] leading-5 text-slate-500 sm:text-xs">
                        تعریف و تحویل خدمات به مددکاران اجتماعی
                    </p>
                </div>

                @if($isEditing)
                    <button
                        type="button"
                        wire:click="cancelEditing"
                        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 lg:self-auto"
                    >
                        انصراف
                    </button>
                @endif
            </div>
        </div>

        @if(!$isEditing)
            <div class="space-y-4 p-4 sm:p-5">

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="group relative flex min-h-20 cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 {{ $mode === 'predefined' ? 'border-cyan-400 bg-cyan-50/60 ring-1 ring-cyan-100' : 'border-slate-200 bg-white hover:border-cyan-200 hover:bg-cyan-50/30' }}">
                        <input type="radio" value="predefined" wire:model.change="mode" class="sr-only">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $mode === 'predefined' ? 'bg-cyan-500 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-cyan-100 group-hover:text-cyan-700' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-black text-slate-800">انتخاب خدمت / پویش</span>
                        </span>
                        <span class="h-2.5 w-2.5 rounded-full {{ $mode === 'predefined' ? 'bg-cyan-500' : 'bg-slate-300' }}"></span>
                    </label>

                    <label class="group relative flex min-h-20 cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2 {{ $mode === 'misc' ? 'border-emerald-400 bg-emerald-50/60 ring-1 ring-emerald-100' : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/30' }}">
                        <input type="radio" value="misc" wire:model.change="mode" class="sr-only">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $mode === 'misc' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-emerald-100 group-hover:text-emerald-700' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-black text-slate-800">تعریف خدمت جدید</span>
                        </span>
                        <span class="h-2.5 w-2.5 rounded-full {{ $mode === 'misc' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                    </label>
                </div>
                @unless($hasSelectedMode)
                    <p class="text-xs font-bold text-slate-400">برای ادامه، یکی از دو حالت بالا را انتخاب کنید.</p>
                @endunless
            </div>

        @endif
    </div>

    @php
        $isPredefinedWorkflow = $mode === 'predefined' && ! $isEditing;
        $hasSelectedMiscServiceType = $isEditing || in_array($miscServiceType, array_keys($typeOptions), true);
        $serviceStepComplete = $isPredefinedWorkflow ? (bool) $selectedService : $hasSelectedMiscServiceType;
        $workerStepUnlocked = $serviceStepComplete;
        $workerStepComplete = (bool) $socialWorkerId;
        $quantityStepUnlocked = $serviceStepComplete && $workerStepComplete;
        $quantityStepComplete = $canRequestSaveConfirmation;
        $reviewStepUnlocked = $canRequestSaveConfirmation;
        $workflowAccent = $isPredefinedWorkflow ? 'cyan' : 'emerald';
        $workflowSteps = [
            [
                'number' => 1,
                'title' => $isPredefinedWorkflow ? 'انتخاب خدمت' : 'مشخصات خدمت',
                'status' => $serviceStepComplete ? 'done' : 'active',
            ],
            [
                'number' => 2,
                'title' => 'انتخاب مددکار',
                'status' => ! $workerStepUnlocked ? 'locked' : ($workerStepComplete ? 'done' : 'active'),
            ],
            [
                'number' => 3,
                'title' => $isPredefinedWorkflow ? 'ثبت مقدارها' : 'ثبت دسته‌بندی‌ها',
                'status' => ! $quantityStepUnlocked ? 'locked' : ($quantityStepComplete ? 'done' : 'active'),
            ],
            [
                'number' => 4,
                'title' => 'مرور و ثبت',
                'status' => ! $reviewStepUnlocked ? 'locked' : 'active',
            ],
        ];
        $completedWorkflowSteps = count(array_filter($workflowSteps, fn ($step) => $step['status'] === 'done'));
        $activeWorkflowStep = null;

        foreach ($workflowSteps as $workflowStep) {
            if ($workflowStep['status'] === 'active') {
                $activeWorkflowStep = $workflowStep;
                break;
            }
        }

        $workflowProgressPercent = count($workflowSteps) > 0
            ? (($completedWorkflowSteps + ($activeWorkflowStep ? 0.5 : 0)) / count($workflowSteps)) * 100
            : 0;
        $workflowProgressPercent = max(8, min(100, $workflowProgressPercent));
        $workflowProgressClass = $workflowAccent === 'cyan'
            ? 'from-cyan-500 via-sky-500 to-cyan-400'
            : 'from-emerald-500 via-teal-500 to-emerald-400';
        $workflowTrackClass = $workflowAccent === 'cyan'
            ? 'bg-cyan-50'
            : 'bg-emerald-50';
        $workflowLabelClass = $workflowAccent === 'cyan'
            ? 'text-cyan-700'
            : 'text-emerald-700';
    @endphp

    @if($hasSelectedMode)
    <form wire:submit.prevent="requestSaveConfirmation" class="space-y-3">
        @if($mode === 'predefined' && !$isEditing)
            <section wire:key="predefined-service-batch-section" class="overflow-visible rounded-2xl border border-cyan-100 bg-white shadow-sm">
                <div class="border-b border-cyan-100 bg-cyan-50/30 px-4 py-3 sm:px-5">
                    <div class="h-2.5 overflow-hidden rounded-full {{ $workflowTrackClass }}">
                        <div
                            class="h-full rounded-full bg-gradient-to-l {{ $workflowProgressClass }} transition-all duration-300"
                            style="width: {{ $workflowProgressPercent }}%;"
                        ></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-[11px] font-bold">
                        <p class="truncate text-slate-500">
                            {{ $activeWorkflowStep ? 'مرحله فعلی: ' . $activeWorkflowStep['title'] : 'همه مراحل تکمیل شده است' }}
                        </p>
                        <p class="shrink-0 {{ $workflowLabelClass }}">
                            {{ $completedWorkflowSteps }} از {{ count($workflowSteps) }} مرحله
                        </p>
                    </div>
                </div>
                <div class="border-b border-cyan-100 bg-cyan-50/40 px-4 py-2.5 sm:px-5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500 text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-black text-slate-900">انتخاب خدمت / پویش</h2>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    @include('livewire.distribution-operators.partials.predefined-service-selector')

                    @if($workerStepUnlocked)
                        @include('livewire.distribution-operators.partials.social-worker-selector', [
                            'accent' => 'cyan',
                            'selectorId' => 'predefined-social-worker-selector',
                        ])
                    @else
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-black text-slate-500">۲. انتخاب مددکار</p>
                            <p class="mt-1 text-xs font-bold leading-5 text-slate-400">ابتدا خدمت را انتخاب کنید تا انتخاب مددکار فعال شود.</p>
                        </div>
                    @endif
                </div>

                @if($selectedService && ! $quantityStepUnlocked)
                    <div class="mx-4 mb-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-center sm:mx-5 sm:mb-5">
                        <p class="text-sm font-black text-slate-600">۳. ثبت مقدارها</p>
                        <p class="mt-1 text-xs font-bold leading-5 text-slate-400">پس از انتخاب مددکار، مقدارهای قابل تخصیص نمایش داده می‌شود.</p>
                    </div>
                @elseif($selectedService)
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
                                    $categoryMetrics = $selectedServiceCategoryMetrics[(int) $category->id] ?? ['quantity' => (float) $category->quantity, 'allocated' => 0.0, 'assignable' => 0.0];
                                    $totalStockQuantity = (float) $categoryMetrics['quantity'];
                                    $alreadyAllocatedQuantity = (float) $categoryMetrics['allocated'];
                                    $assignableQuantity = (float) $categoryMetrics['assignable'];
                                    $allocatedPreview = $this->predefinedAllocationForCategory((int) $category->id);
                                    $remainingPreview = max(0, $assignableQuantity - $allocatedPreview);
                                    $isOverAllocated = $allocatedPreview > $assignableQuantity;
                                @endphp
                                <div class="rounded-2xl border bg-white p-4 shadow-sm transition {{ $isOverAllocated ? 'border-rose-200 ring-2 ring-rose-100' : 'border-slate-200' }}">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900">{{ $category->name }}</p>
                                            <p class="mt-1 text-xs font-bold text-slate-500">وضعیت موجودی این دسته‌بندی</p>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                            {{ $unitOptions[$category->unit] ?? $category->unit }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <p class="text-[10px] font-bold text-slate-500">موجودی کل</p>
                                            <p class="mt-1 text-sm font-black text-slate-900">{{ number_format($totalStockQuantity, 2) }}</p>
                                        </div>
                                        <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2">
                                            <p class="text-[10px] font-bold text-amber-700">قبلاً تخصیص‌یافته</p>
                                            <p class="mt-1 text-sm font-black text-amber-800">{{ number_format($alreadyAllocatedQuantity, 2) }}</p>
                                        </div>
                                        <div class="rounded-xl border border-cyan-100 bg-cyan-50 px-3 py-2">
                                            <p class="text-[10px] font-bold text-cyan-700">قابل تخصیص اکنون</p>
                                            <p class="mt-1 text-sm font-black text-cyan-800">{{ number_format($assignableQuantity, 2) }}</p>
                                        </div>
                                        <div class="rounded-xl border {{ $isOverAllocated ? 'border-rose-200 bg-rose-50' : 'border-emerald-100 bg-emerald-50' }} px-3 py-2">
                                            <p class="text-[10px] font-bold {{ $isOverAllocated ? 'text-rose-700' : 'text-emerald-700' }}">مانده پس از این ثبت</p>
                                            <p class="mt-1 text-sm font-black {{ $isOverAllocated ? 'text-rose-800' : 'text-emerald-800' }}">{{ number_format($remainingPreview, 2) }}</p>
                                        </div>
                                    </div>

                                    <label class="mt-3 block text-xs font-bold text-slate-600" for="predefined-allocation-{{ $category->id }}">
                                        مقدار تخصیص در این ثبت
                                    </label>
                                    <div class="mt-1 grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                        <input
                                            id="predefined-allocation-{{ $category->id }}"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            max="{{ $assignableQuantity }}"
                                            wire:model.live.debounce.250ms="predefinedAllocations.{{ $category->id }}"
                                            inputmode="decimal"
                                            class="min-w-0 rounded-xl border {{ $isOverAllocated ? 'border-rose-300 bg-rose-50 text-rose-900 focus:border-rose-400 focus:ring-rose-100' : 'border-slate-300 bg-white text-slate-900 focus:border-cyan-400 focus:ring-cyan-100' }} px-3 py-2 text-center text-sm font-black outline-none transition focus:ring-4"
                                            placeholder="0"
                                            aria-describedby="predefined-allocation-help-{{ $category->id }}"
                                            aria-invalid="{{ $isOverAllocated ? 'true' : 'false' }}"
                                        >
                                        <div class="flex gap-1.5">
                                            <button
                                                type="button"
                                                wire:click="useMaxPredefinedAllocation({{ $category->id }})"
                                                @disabled($assignableQuantity <= 0)
                                                class="rounded-xl border border-cyan-100 bg-cyan-50 px-3 py-2 text-xs font-black text-cyan-700 transition hover:border-cyan-200 hover:bg-cyan-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-400"
                                            >
                                                حداکثر
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="clearPredefinedAllocation({{ $category->id }})"
                                                @disabled($allocatedPreview <= 0)
                                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-300"
                                            >
                                                پاک
                                            </button>
                                        </div>
                                    </div>
                                    <p id="predefined-allocation-help-{{ $category->id }}" class="mt-2 text-[11px] font-semibold {{ $isOverAllocated ? 'text-rose-700' : 'text-slate-500' }}">
                                        @if($isOverAllocated)
                                            مقدار واردشده از موجودی قابل تخصیص این دسته‌بندی بیشتر است.
                                        @elseif($assignableQuantity <= 0)
                                            موجودی قابل تخصیصی برای این دسته‌بندی باقی نمانده است.
                                        @else
                                            از {{ number_format($assignableQuantity, 2) }} واحد قابل تخصیص، {{ number_format($allocatedPreview, 2) }} واحد در این ثبت وارد شده است.
                                        @endif
                                    </p>
                                    @error('predefinedAllocations.' . $category->id) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @else
            <section wire:key="misc-service-batch-section" class="overflow-visible rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50/30 px-4 py-3 sm:px-5">
                    <div class="h-2.5 overflow-hidden rounded-full {{ $workflowTrackClass }}">
                        <div
                            class="h-full rounded-full bg-gradient-to-l {{ $workflowProgressClass }} transition-all duration-300"
                            style="width: {{ $workflowProgressPercent }}%;"
                        ></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-[11px] font-bold">
                        <p class="truncate text-slate-500">
                            {{ $activeWorkflowStep ? 'مرحله فعلی: ' . $activeWorkflowStep['title'] : 'همه مراحل تکمیل شده است' }}
                        </p>
                        <p class="shrink-0 {{ $workflowLabelClass }}">
                            {{ $completedWorkflowSteps }} از {{ count($workflowSteps) }} مرحله
                        </p>
                    </div>
                </div>
                <div class="border-b border-emerald-100 bg-emerald-50/40 px-4 py-2.5 sm:px-5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-black text-slate-900">{{ $isEditing ? 'ویرایش خدمت متفرقه' : 'تعریف خدمت جدید' }}</h2>
                        </div>
                    </div>
                </div>

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
                                            activeClass: 'border-rose-300 bg-rose-50/80 shadow-sm',
                                            inactiveClass: 'border-slate-200 bg-white hover:border-rose-200 hover:bg-rose-50/40',
                                            iconActiveClass: 'bg-rose-600 text-white',
                                            iconInactiveClass: 'bg-rose-100 text-rose-700',
                                            titleActiveClass: 'text-rose-950',
                                            titleInactiveClass: 'text-slate-800',
                                            subtitleActiveClass: 'text-rose-700',
                                            subtitleInactiveClass: 'text-slate-500',
                                            dotActiveClass: 'bg-rose-600',
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
                                <p class="mt-1 text-xs font-bold leading-5 text-slate-400">پس از انتخاب نوع خدمت، امکان جستجو و انتخاب مددکار فعال می‌شود.</p>
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
                <div class="space-y-4 px-4 pb-4 sm:px-5">
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-black text-slate-800">دسته‌بندی‌های خدمت</p>
                            <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">هر ردیف شامل نام، مقدار و واحد خدمت است.</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-black text-slate-500 shadow-sm">{{ count($miscCategories) }} ردیف</span>
                    </div>

                    @foreach($miscCategories as $index => $category)
                        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black tracking-wide text-slate-400">دسته‌بندی {{ $index + 1 }}</p>
                                    <p class="mt-1 text-sm font-bold text-slate-700">مشخصات خدمت</p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeCategory({{ $index }})"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                    aria-label="حذف دسته‌بندی {{ $index + 1 }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-2 block text-xs font-bold text-slate-600">نام دسته‌بندی</label>
                                    <input
                                        type="text"
                                        wire:model.blur="miscCategories.{{ $index }}.name"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                                        placeholder="مثال: بسته غذایی"
                                    >
                                    @error("miscCategories.$index.name") <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="mb-2 block text-xs font-bold text-slate-600">مقدار</label>
                                        <input
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            wire:model.blur="miscCategories.{{ $index }}.quantity"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                                            placeholder="0"
                                        >
                                        @error("miscCategories.$index.quantity") <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-bold text-slate-600">واحد</label>
                                        <select wire:model="miscCategories.{{ $index }}.unit" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100">
                                            @foreach($unitOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-4 pb-5 sm:px-5">
                    <button
                        type="button"
                        wire:click="addCategory"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-800 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100 sm:w-auto"
                    >
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-base leading-none text-emerald-700 shadow-sm">+</span>
                        افزودن دسته‌بندی
                    </button>
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
        <div class="rounded-2xl border {{ $reviewStepUnlocked ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }} px-4 py-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
            <div class="min-w-0">
                <p class="text-sm font-black {{ $reviewStepUnlocked ? 'text-emerald-900' : 'text-slate-600' }}">۴. مرور و ثبت نهایی</p>
                <p class="mt-1 text-xs font-bold leading-5 {{ $reviewStepUnlocked ? 'text-emerald-700' : 'text-slate-400' }}">
                    {{ $reviewStepUnlocked ? 'همه اطلاعات لازم کامل است. برای بازبینی نهایی ادامه دهید.' : 'پس از تکمیل مرحله‌های قبل، مرور نهایی فعال می‌شود.' }}
                </p>
            </div>
            <button
                type="submit"
                @disabled(! $canRequestSaveConfirmation)
                wire:loading.attr="disabled"
                wire:target="requestSaveConfirmation"
                class="mt-3 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:shadow-none sm:mt-0 sm:w-auto"
            >
                <span wire:loading.remove wire:target="requestSaveConfirmation">
                    {{ $mode === 'predefined' && !$isEditing ? 'مرور و تأیید تحویل' : 'مرور و تأیید خدمت جدید' }}
                </span>
                <span wire:loading wire:target="requestSaveConfirmation">در حال بررسی...</span>
            </button>
        </div>
    </form>

    @if($confirmingBatchSave)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="operator-confirmation-title">
            <div class="flex max-h-[92svh] w-full max-w-3xl flex-col rounded-t-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[88vh] sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                    <div class="min-w-0">
                        <h3 id="operator-confirmation-title" class="text-lg font-black text-slate-900">{{ $confirmationSummary['title'] ?? 'تأیید نهایی' }}</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500">پیش از ثبت نهایی، خدمت، مددکار و مقادیر را بررسی کنید.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="cancelSaveConfirmation"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                        aria-label="بستن"
                    >
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                    <div class="grid gap-3 sm:grid-cols-2">
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
                            <p class="mt-2 text-xs font-bold text-slate-500">{{ $confirmationSummary['date_label'] ?? '' }}</p>
                        </div>
                    </div>

                    @if(!empty($confirmationSummary['description']))
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold text-slate-500">توضیحات</p>
                            <p class="mt-1 line-clamp-3 text-sm leading-6 text-slate-700">{{ $confirmationSummary['description'] }}</p>
                        </div>
                    @endif

                    <div class="mt-3 rounded-2xl border border-slate-200 bg-white">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-3">
                            <p class="text-sm font-black text-slate-900">مقادیر ثبت‌شونده</p>
                            <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">جمع: {{ $confirmationSummary['total_quantity_label'] ?? '0.00' }}</span>
                        </div>
                        <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                            @forelse($confirmationSummary['rows'] ?? [] as $row)
                                <div class="grid gap-2 px-3 py-3 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-800">{{ $row['name'] }}</p>
                                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $row['unit_label'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-right">
                                        <p class="text-[10px] font-bold text-slate-500">مقدار</p>
                                        <p class="text-sm font-black text-slate-900">{{ $row['quantity_label'] }}</p>
                                    </div>
                                    @if(($confirmationSummary['mode'] ?? '') === 'predefined')
                                        <div class="rounded-xl bg-emerald-50 px-3 py-2 text-right">
                                            <p class="text-[10px] font-bold text-emerald-700">مانده بعد از ثبت</p>
                                            <p class="text-sm font-black text-emerald-800">{{ $row['remaining_label'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-sm font-bold text-slate-500">مقداری برای ثبت انتخاب نشده است.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-3 sm:flex-row sm:justify-end sm:px-5 sm:pb-4">
                    <button
                        type="button"
                        wire:click="cancelSaveConfirmation"
                        class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                    >
                        بازگشت به ویرایش
                    </button>
                    <button
                        type="button"
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

