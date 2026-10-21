@php
    $groupIndex = $groupIndex ?? 0;
    $workerGroup = $workerGroup ?? [];
    $groupWorkerId = (int) ($workerGroup['social_worker_id'] ?? 0);
    $canRemoveGroup = count($predefinedWorkerGroups) > 1;
    // A group with no worker and no entered quantity can be removed silently; a
    // filled group asks for confirmation so accidental taps don't drop work.
    $groupHasData = $groupWorkerId > 0
        || collect($workerGroup['allocations'] ?? [])->contains(fn ($quantity) => (float) $quantity > 0);
@endphp

<div
    data-predefined-worker-group-index="{{ $groupIndex }}"
    data-validation-scope
    wire:key="predefined-worker-group-{{ $workerGroup['uid'] ?? $groupIndex }}"
    class="rounded-2xl border border-cyan-200 bg-white shadow-sm"
>
    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-cyan-50/40 px-3.5 py-2.5 sm:px-4">
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-[11px] font-black text-cyan-700">{{ $groupIndex + 1 }}</span>
        <p class="min-w-0 flex-1 truncate text-sm font-black text-slate-800">
            {{ $groupWorkerId ? trim((string) ($workerGroup['worker_display'] ?? '')) ?: 'مددکار' : 'مددکار جدید' }}
        </p>
        @if($canRemoveGroup)
            <button
                type="button"
                @if($groupHasData)
                    x-on:click="
                        window.dispatchEvent(new CustomEvent('open-notification-modal', {
                            detail: {
                                config: {
                                    type: 'warning',
                                    title: 'حذف مددکار',
                                    message: 'آیا از حذف این مددکار و مقدارهای ثبت‌شده برای او مطمئن هستید؟',
                                    buttons: [
                                        {
                                            label: 'حذف',
                                            action: 'event',
                                            event: 'confirm-predefined-worker-group-delete',
                                            payload: { index: {{ $groupIndex }} },
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
                @else
                    wire:click="removePredefinedWorkerGroup({{ $groupIndex }})"
                @endif
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600"
                aria-label="حذف این مددکار"
            >
                <i class="bi bi-trash text-sm"></i>
            </button>
        @endif
    </div>

    <div class="space-y-3 p-3 sm:p-3.5">
        @include('livewire.distribution-operators.partials.predefined-group-social-worker-selector', [
            'groupIndex' => $groupIndex,
            'group' => $workerGroup,
        ])

        @if(! $groupWorkerId)
            <div class="flex items-center gap-2.5 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-3.5 py-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 ring-1 ring-slate-200">
                    <i class="bi bi-lock text-sm"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-black text-slate-500">مقدارها</p>
                    <p class="mt-0.5 text-[11px] font-bold leading-5 text-slate-400">ابتدا مددکار را انتخاب کنید</p>
                </div>
            </div>
        @else
            @if($workerGroup['has_existing_allocation'] ?? false)
                <div class="flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/80 px-3.5 py-2 text-xs font-bold text-indigo-800 shadow-sm">
                    <i class="bi bi-info-circle-fill text-indigo-600 text-sm shrink-0"></i>
                    <span class="flex-1">این مددکار از قبل در این خدمت سهمیه دارد. مقادیر واردشده جایگزین سهمیه قبلی خواهند شد.</span>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-2.5">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($selectedServiceCategories as $category)
                        @php
                            $categoryMetrics = $selectedServiceCategoryMetrics[(int) $category->id] ?? ['quantity' => (float) $category->quantity, 'allocated' => 0.0, 'assignable' => 0.0];
                            // Assignable for THIS worker: the shared pool minus what other
                            // worker groups have already claimed for the same category.
                            $assignableQuantity = $this->predefinedGroupAssignableForCategory((int) $groupIndex, (int) $category->id);
                            $allocatedPreview = $this->predefinedGroupAllocationForCategory((int) $groupIndex, (int) $category->id);
                            $previousAllocation = (float) ($workerGroup['previous_allocations'][(int) $category->id] ?? 0);
                            $deliveredQuantity = (float) ($workerGroup['delivered_quantities'][(int) $category->id] ?? 0);
                            $isOverAllocated = $allocatedPreview > $assignableQuantity;
                            $isBelowDelivered = $deliveredQuantity > 0 && $allocatedPreview < $deliveredQuantity;
                            $hasError = $isOverAllocated || $isBelowDelivered;
                            $usesDecimalQuantity = in_array((string) $category->unit, ['kilogram', 'gram', 'kg', 'g'], true);
                            $formatCategoryQuantity = fn (float|int $quantity) => number_format((float) $quantity, $usesDecimalQuantity ? 2 : 0);
                            $assignableQuantityLabel = $formatCategoryQuantity($assignableQuantity);
                            $allocatedPreviewLabel = $formatCategoryQuantity($allocatedPreview);
                            $quantityInputStep = $usesDecimalQuantity ? '0.01' : '1';
                            $quantityInputMode = $usesDecimalQuantity ? 'decimal' : 'numeric';
                            $categoryUnitLabel = $unitOptions[$category->unit] ?? $category->unit;
                        @endphp
                        <div class="rounded-xl border {{ $hasError ? 'border-rose-200 bg-rose-50/70' : 'border-slate-200 bg-white' }} p-2.5 shadow-sm shadow-slate-900/[0.02]">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-900">{{ $category->name }}</p>
                                    @if($previousAllocation > 0 || $deliveredQuantity > 0)
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                                            @if($previousAllocation > 0)
                                                <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-indigo-700 ring-1 ring-indigo-200">
                                                    <span>قبلی:</span>
                                                    <span dir="ltr">{{ $formatCategoryQuantity($previousAllocation) }}</span>
                                                </span>
                                            @endif
                                            @if($deliveredQuantity > 0)
                                                <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-700 ring-1 ring-emerald-200">
                                                    <span>تحویل:</span>
                                                    <span dir="ltr">{{ $formatCategoryQuantity($deliveredQuantity) }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-cyan-100/70 bg-cyan-50/60 px-2 py-0.5 text-[11px]">
                                    <span class="font-bold tabular-nums text-slate-700" dir="ltr">{{ $assignableQuantityLabel }}</span>
                                    <span class="text-[10px] font-semibold text-slate-400">{{ $categoryUnitLabel }}</span>
                                </span>
                            </div>

                            <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto_auto] gap-1.5">
                                <input
                                    type="number"
                                    min="{{ $deliveredQuantity > 0 ? $deliveredQuantity : 0 }}"
                                    step="{{ $quantityInputStep }}"
                                    max="{{ $assignableQuantity }}"
                                    wire:model.live.debounce.250ms="predefinedWorkerGroups.{{ $groupIndex }}.allocations.{{ $category->id }}"
                                    inputmode="{{ $quantityInputMode }}"
                                    class="min-w-0 rounded-lg border {{ $hasError ? 'border-rose-300 bg-rose-50 text-rose-900 focus:border-rose-400 focus:ring-rose-100' : 'border-slate-200 bg-slate-50 text-slate-900 focus:border-cyan-400 focus:ring-cyan-100' }} px-2.5 py-2 text-center text-sm font-black outline-none transition focus:ring-2"
                                    placeholder="۰"
                                    aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                                >
                                <button
                                    type="button"
                                    wire:click="useMaxPredefinedGroupAllocation({{ $groupIndex }}, {{ $category->id }})"
                                    @disabled($assignableQuantity <= 0)
                                    class="rounded-lg border border-cyan-100 bg-cyan-50 px-2 py-2 text-[11px] font-bold text-cyan-700 transition hover:bg-cyan-100 disabled:opacity-40"
                                >
                                    حداکثر
                                </button>
                                <button
                                    type="button"
                                    wire:click="clearPredefinedGroupAllocation({{ $groupIndex }}, {{ $category->id }})"
                                    @disabled($allocatedPreview <= 0)
                                    class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[11px] font-bold text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-40"
                                >
                                    پاک
                                </button>
                            </div>

                            @if($isOverAllocated)
                                <p class="mt-1.5 text-[11px] font-bold text-rose-600">بیشتر از موجودی قابل تخصیص</p>
                            @elseif($isBelowDelivered)
                                <p class="mt-1.5 text-[11px] font-bold text-rose-600">کمتر از مقدار تحویل‌شده ({{ $formatCategoryQuantity($deliveredQuantity) }})</p>
                            @elseif($allocatedPreview > 0)
                                <p class="mt-1.5 text-[11px] font-semibold text-slate-400">{{ $allocatedPreviewLabel }} از {{ $assignableQuantityLabel }}</p>
                            @endif

                            @error('predefinedWorkerGroups.' . $groupIndex . '.allocations.' . $category->id) <p data-validation-error class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
                @error('predefinedWorkerGroups.' . $groupIndex . '.allocations') <p data-validation-error class="mt-2 text-[11px] text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>
</div>
