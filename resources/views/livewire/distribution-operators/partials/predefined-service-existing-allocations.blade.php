@php
    $existingAllocations = $this->predefinedExistingAllocations;
    $existingWorkersCount = $existingAllocations->count();
    $totalExistingAllocated = (float) $existingAllocations->sum('total_allocated');
    $totalExistingDelivered = (float) $existingAllocations->sum('total_delivered');
@endphp

<div
    wire:key="predefined-existing-allocations-card"
    x-data="{
        expanded: false
    }"
    class="overflow-hidden rounded-2xl border {{ $existingWorkersCount > 0 ? 'border-indigo-100 bg-gradient-to-b from-indigo-50/40 to-white' : 'border-slate-200 bg-slate-50/60' }} transition-all"
>
    <div
        class="flex cursor-pointer items-center justify-between gap-3 px-3.5 py-3 select-none hover:bg-indigo-50/30 transition sm:px-4"
        @click="expanded = !expanded"
    >
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $existingWorkersCount > 0 ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'bg-slate-200 text-slate-500' }}">
                <i class="bi bi-people-fill text-sm"></i>
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-black text-slate-800 sm:text-sm">سوابق تخصیص‌های ثبت‌شده پیشین</span>
                    @if($existingWorkersCount > 0)
                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-black text-indigo-700">
                            {{ $existingWorkersCount }} مددکار
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                            بدون سهمیه قبلی
                        </span>
                    @endif
                </div>
                <p class="mt-0.5 text-[11px] font-medium text-slate-500 truncate">
                    @if($existingWorkersCount > 0)
                        مجموع تخصیص: <span class="font-bold tabular-nums text-slate-700">{{ number_format($totalExistingAllocated, 2) }}</span>
                        @if($totalExistingDelivered > 0)
                            · تحویل‌شده: <span class="font-bold tabular-nums text-emerald-600">{{ number_format($totalExistingDelivered, 2) }}</span>
                        @endif
                    @else
                        تمام موجودی این خدمت آزاد است و هنوز به مددکاری سهمیه داده نشده است.
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="text-[11px] font-bold text-indigo-600 hidden sm:inline" x-text="expanded ? 'بستن سوابق' : 'مشاهده سوابق'"></span>
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 transition-transform duration-200 shadow-sm" :class="{ 'rotate-180': expanded }">
                <i class="bi bi-chevron-down text-xs"></i>
            </span>
        </div>
    </div>

    <div
        x-show="expanded"
        x-collapse
        class="border-t border-slate-100 bg-white/70 p-3 sm:p-4 space-y-2.5"
    >
        @if($existingWorkersCount === 0)
            <div class="rounded-xl border border-dashed border-slate-200 bg-white p-4 text-center">
                <p class="text-xs font-bold text-slate-500">تا کنون هیچ سهمیه‌ای برای این خدمت به مددکاران ثبت نشده است.</p>
                <p class="mt-1 text-[11px] font-medium text-slate-400">از فرم زیر می‌توانید اولین سهمیه‌ها را برای مددکاران مورد نظر ثبت کنید.</p>
            </div>
        @else
            <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($existingAllocations as $item)
                    @php
                        $isAlreadyInForm = collect($predefinedWorkerGroups)->contains(fn ($grp) => (int) ($grp['social_worker_id'] ?? 0) === (int) $item['worker_id']);
                    @endphp
                    <div class="flex flex-col justify-between rounded-xl border {{ $isAlreadyInForm ? 'border-cyan-200 bg-cyan-50/30' : 'border-slate-200 bg-white' }} p-3 shadow-sm transition hover:border-indigo-200">
                        <div>
                            <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <p class="truncate text-xs font-black text-slate-900 sm:text-sm">{{ $item['worker_name'] }}</p>
                                        @if($isAlreadyInForm)
                                            <span class="shrink-0 rounded bg-cyan-100 px-1.5 py-0.5 text-[9px] font-bold text-cyan-800">در فرم</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-[10px] font-semibold text-slate-400">کد: {{ $item['worker_code'] }} · {{ $item['district'] }}</p>
                                </div>
                                <span class="rounded-lg bg-indigo-50 px-2 py-0.5 text-[11px] font-black text-indigo-700">
                                    {{ number_format($item['total_allocated'], 2) }}
                                </span>
                            </div>

                            <div class="mt-2.5 space-y-1.5 text-[11px]">
                                @foreach($item['categories'] as $cat)
                                    <div class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2 py-1 text-slate-700">
                                        <span class="truncate font-bold">{{ $cat['name'] }}:</span>
                                        <span class="shrink-0 tabular-nums font-black" dir="ltr">
                                            {{ number_format($cat['allocated_quantity'], 2) }}
                                            @if($cat['delivered_quantity'] > 0)
                                                <span class="text-[10px] font-semibold text-emerald-600">({{ number_format($cat['delivered_quantity'], 2) }} تحویل)</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2 text-[10px]">
                            <span class="truncate text-slate-400">
                                ثبت: {{ $item['categories']->first()['assigned_by_name'] ?? '-' }}
                            </span>
                            @if($item['is_fully_assigned_by_current_user'])
                                <button
                                    type="button"
                                    wire:click="loadPredefinedExistingWorkerIntoForm({{ $item['worker_id'] }})"
                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2 py-1 font-bold text-indigo-700 transition hover:bg-indigo-100 active:scale-95"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                    <span>{{ $isAlreadyInForm ? 'مشاهده در فرم' : 'ویرایش در فرم' }}</span>
                                </button>
                            @else
                                <span class="rounded bg-amber-50 px-1.5 py-0.5 font-bold text-amber-700 ring-1 ring-amber-200">
                                    اپراتور دیگر
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
