<div class="space-y-4">
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

    @if($service)
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- Service info --}}
        <div class="border-b border-slate-100 bg-blue-50/30 px-4 py-3 sm:px-5">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h2 class="truncate text-sm font-black text-slate-900">{{ $service->name ?: ($service->serviceName?->name ?? 'خدمت انتخاب‌شده') }}</h2>
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-bold text-blue-700">{{ $service->code }}</span>
                    </div>
                    <p class="mt-0.5 text-[11px] font-bold text-slate-500">
                        موجودی کل: {{ number_format((float) $service->total_quantity, 2) }}
                        · {{ count($service->categories) }} دسته‌بندی
                    </p>
                </div>
            </div>
        </div>

        {{-- Category metrics --}}
        <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
            <p class="text-xs font-black text-slate-700">وضعیت موجودی دسته‌بندی‌ها</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($service->categories as $category)
                    @php
                        $metrics = $categoryMetrics[(int) $category->id] ?? ['quantity' => 0, 'allocated' => 0, 'assignable' => 0];
                        $totalQuantity = max(0, (float) $metrics['quantity']);
                        $allocatedQuantity = max(0, min((float) $metrics['allocated'], $totalQuantity));
                        $remainingQuantity = max(0, (float) $metrics['assignable']);
                        $allocatedPercent = $totalQuantity > 0 ? min(100, round(($allocatedQuantity / $totalQuantity) * 100, 1)) : 0;
                        $remainingPercent = $totalQuantity > 0 ? min(100, round(($remainingQuantity / $totalQuantity) * 100, 1)) : 0;
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-blue-50/30 to-slate-50 px-3 py-2.5 shadow-sm shadow-slate-200/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black text-slate-800">{{ $category->name }}</p>
                                <p class="mt-0.5 text-[10px] font-medium tracking-tight text-slate-400">
                                    موجودی کل: {{ $this->formatQuantityForUnit($totalQuantity, (string) $category->unit) }} {{ $unitOptions[$category->unit] ?? $category->unit }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full border border-blue-100 bg-white/90 px-2 py-0.5 text-[10px] font-bold text-blue-700 shadow-sm">
                                {{ $unitOptions[$category->unit] ?? $category->unit }}
                            </span>
                        </div>

                        <div class="mt-3">
                            <div class="flex items-center justify-between text-[9px] font-bold tracking-tight">
                                <span class="text-blue-700">تخصیص‌شده {{ $allocatedPercent }}٪</span>
                                <span class="text-slate-400">باقی‌مانده {{ $remainingPercent }}٪</span>
                            </div>
                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-slate-200/80">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-blue-500 via-blue-600 to-cyan-500 transition-all duration-300"
                                    style="width: {{ $allocatedPercent }}%;"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-2 text-[9px] font-medium tracking-tight">
                            <p class="text-slate-400">
                                تخصیص‌شده
                                <span class="mr-1 text-blue-700">{{ $this->formatQuantityForUnit($allocatedQuantity, (string) $category->unit) }}</span>
                            </p>
                            <p class="text-slate-400">
                                آماده تخصیص
                                <span class="mr-1 text-slate-700">{{ $this->formatQuantityForUnit($remainingQuantity, (string) $category->unit) }}</span>
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Operator allocations --}}
        <div class="px-4 py-4 sm:px-5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-black text-slate-800">تخصیص‌های شما</p>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">{{ $operatorAllocations->count() }} مددکار</span>
            </div>

            @if($operatorAllocations->isEmpty())
                <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                    <p class="text-sm font-bold text-slate-500">هنوز تخصیصی ثبت نشده است.</p>
                </div>
            @else
                <div class="mt-3 space-y-3">
                    @foreach($operatorAllocations as $workerId => $allocations)
                        @php
                            $firstAllocation = $allocations->first();
                            $worker = $firstAllocation->socialWorker;
                        @endphp
                        <div
                            x-data="{
                                open: false,
                                maxHeight: 0,
                                refreshHeight() {
                                    this.$nextTick(() => {
                                        this.maxHeight = this.$refs.content ? this.$refs.content.scrollHeight : 0;
                                    });
                                },
                                toggle() {
                                    this.open = ! this.open;
                                    this.refreshHeight();
                                }
                            }"
                            x-init="refreshHeight()"
                            @resize.window.debounce.100ms="refreshHeight()"
                            class="relative isolate rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow duration-200"
                        >
                            <div
                                x-cloak
                                x-show="open"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="absolute -left-2 -top-2 z-[100]"
                            >
                                <button
                                    type="button"
                                    wire:click="confirmRemoveWorkerAllocations({{ $workerId }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-rose-400 shadow-md shadow-slate-300/40 transition hover:border-rose-200 hover:bg-rose-50/60 hover:text-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    title="حذف تمام تخصیص‌های این مددکار"
                                    aria-label="حذف تمام تخصیص‌های این مددکار"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M10 4.75h4a1 1 0 0 1 1 1v.75h3.25a.75.75 0 0 1 0 1.5h-1.03l-.63 9.91a2 2 0 0 1-2 1.87H9.41a2 2 0 0 1-2-1.87L6.78 8H5.75a.75.75 0 0 1 0-1.5H9v-.75a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 10.25v5.5M14 10.25v5.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                                <button
                                    type="button"
                                    @click="toggle()"
                                    class="flex min-w-0 flex-1 items-center gap-3 text-right"
                                    :aria-expanded="open.toString()"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate text-sm font-black text-slate-900">{{ $worker?->full_name ?? '—' }}</p>
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">{{ $allocations->count() }} مورد</span>
                                        </div>
                                        <p class="mt-0.5 text-[11px] font-bold text-slate-500">
                                            کد: {{ $worker?->worker_code ?? '—' }}
                                            @if($worker?->district)
                                                <span class="text-slate-300">·</span>
                                                {{ $worker->district->name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400 shadow-sm transition duration-300 hover:border-blue-200 hover:text-blue-600" :class="open ? 'rotate-180 text-blue-600 border-blue-200' : ''">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </button>
                            </div>

                            <div
                                class="overflow-hidden transition-all duration-300 ease-out"
                                x-bind:style="open ? `max-height: ${maxHeight}px; opacity: 1;` : 'max-height: 0px; opacity: 0;'"
                            >
                                <div x-ref="content" class="divide-y divide-slate-100">
                                    @foreach($allocations as $allocation)
                                        @php
                                            $category = $service->categories->firstWhere('id', $allocation->service_category_id);
                                            $metrics = $categoryMetrics[(int) $allocation->service_category_id] ?? ['quantity' => 0, 'allocated' => 0, 'assignable' => 0];
                                            $currentValue = $this->allocationQuantities[$workerId][$allocation->service_category_id] ?? (float) $allocation->allocated_quantity;
                                            $inputValue = (float) $currentValue > 0 ? $currentValue : '';
                                            $totalOthersAllocated = $metrics['allocated'] - (float) $allocation->allocated_quantity;
                                            $maxAllowed = max(0, $metrics['quantity'] - $totalOthersAllocated);
                                        @endphp
                                        <div class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-bold text-slate-800">{{ $category?->name ?? '—' }}</p>
                                                    <p class="text-[11px] font-bold text-slate-400">
                                                        موجودی کل: {{ $this->formatQuantityForUnit($metrics['quantity'], (string) ($category?->unit ?? '')) }}
                                                        · سقف قابل تخصیص: {{ $this->formatQuantityForUnit($maxAllowed, (string) ($category?->unit ?? '')) }}
                                                    </p>
                                                </div>

                                                <div class="flex items-center gap-1.5">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="{{ $maxAllowed }}"
                                                        step="0.01"
                                                        value="{{ $inputValue }}"
                                                        wire:change="updateAllocationQuantity({{ $allocation->id }}, $event.target.value)"
                                                        inputmode="decimal"
                                                        placeholder="0"
                                                        class="w-24 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm font-black text-slate-900 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                                    >
                                                    <span class="text-xs font-bold text-slate-400">{{ $unitOptions[$category?->unit] ?? ($category?->unit ?? '-') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Add new social worker --}}
        <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
            <p class="text-sm font-black text-slate-800">افزودن مددکار جدید</p>
            <div class="mt-2">
                @include('livewire.distribution-operators.partials.social-worker-selector', [
                    'accent' => 'cyan',
                    'selectorId' => 'allocation-social-worker-selector-' . $service->id,
                    'socialWorkerId' => $addingSocialWorkerId,
                ])
            </div>

            @if($addingSocialWorkerId)
                <div class="mt-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                    <p class="text-sm font-bold text-blue-800">مددکار انتخاب‌شده: {{ $socialWorkerQuery }}</p>
                    <button
                        type="button"
                        wire:click="clearSocialWorkerSelection"
                        class="mt-1 text-xs font-bold text-blue-600 hover:text-blue-800"
                    >
                        تغییر انتخاب
                    </button>
                </div>
            @endif

            @if($addingSocialWorkerId)
                <button
                    type="button"
                    wire:click="addSocialWorkerAllocation"
                    class="mt-2 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700"
                >
                    افزودن مددکار
                </button>
            @endif
        </div>
    </div>

    {{-- Save / Cancel --}}
    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
        <button
            type="button"
            wire:click="cancelEditing"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
        >
            بازگشت به فهرست
        </button>
        <button
            type="button"
            wire:click="requestSaveConfirmation"
            class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-xs font-black text-white shadow-sm shadow-emerald-700/20 transition hover:bg-emerald-800"
        >
            ذخیره تغییرات
        </button>
    </div>

    {{-- Confirmation modal --}}
    @if($confirmingSave)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div class="flex max-h-[85svh] w-full max-w-lg flex-col rounded-t-3xl border border-slate-200 bg-white shadow-2xl sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                    <div class="min-w-0">
                        <h3 class="text-lg font-black text-slate-900">تأیید ذخیره تغییرات</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500">تغییرات تخصیص‌ها پس از تأیید ذخیره می‌شوند.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="cancelSaveConfirmation"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                    >
                        <span>×</span>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-[11px] font-bold text-blue-700">خدمت</p>
                                <p class="mt-1 text-sm font-black text-blue-950">{{ $service->name ?: ($service->serviceName?->name ?? '—') }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-blue-700">تعداد مددکاران</p>
                                <p class="mt-1 text-sm font-black text-blue-950">{{ $operatorAllocations->count() }} نفر</p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-sm font-bold text-slate-700">آیا از ذخیره تغییرات اطمینان دارید؟</p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-4 pb-4 pt-3 sm:flex-row sm:justify-end sm:px-5">
                    <button
                        type="button"
                        wire:click="cancelSaveConfirmation"
                        class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                    >
                        بازگشت
                    </button>
                    <button
                        type="button"
                        wire:click="confirmSave"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800"
                    >
                        تأیید و ذخیره
                    </button>
                </div>
            </div>
        </div>
    @endif

    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
            <p class="text-sm font-bold text-slate-500">خدمت مورد نظر یافت نشد.</p>
        </div>
    @endif
</div>
