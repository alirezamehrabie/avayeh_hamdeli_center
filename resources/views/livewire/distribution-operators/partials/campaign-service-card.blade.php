<div
    role="link"
    tabindex="0"
    onclick="if (! event.target.closest('a')) window.location.href = '{{ $allocationEditUrl }}';"
    onkeydown="if ((event.key === 'Enter' || event.key === ' ') && ! event.target.closest('a')) { event.preventDefault(); window.location.href = '{{ $allocationEditUrl }}'; }"
    class="flex cursor-pointer flex-col rounded-2xl border border-blue-200 border-r-4 border-r-blue-500 bg-white p-3 shadow-sm transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-blue-500/10 active:scale-[0.99] sm:rounded-3xl sm:p-4"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-semibold text-violet-700">{{ $service->code }}</p>
            <h3 class="mt-1 line-clamp-2 text-base font-black leading-6 text-slate-800">{{ $service->serviceName?->name ?? '—' }}</h3>
        </div>
        <div class="flex shrink-0 flex-col items-end gap-1">
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700">{{ $operatorAllocationCount }} مددکار</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">{{ number_format($operatorAllocatedQuantity, 2) }} {{ $serviceUnitLabel }}</span>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-black {{ $statusClasses[$serviceStatus] ?? 'border-slate-200 bg-slate-50 text-slate-600' }}">
            {{ $statusLabels[$serviceStatus] ?? 'وضعیت نامشخص' }}
        </span>
        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-black {{ $dateStateClass }}">
            {{ $dateStateLabel }}
        </span>
    </div>

    <div class="mt-3 space-y-3 text-sm text-slate-600 sm:mt-4">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5">
            <div class="flex items-center justify-between gap-3">
                <span class="text-[11px] font-black text-slate-500">دسته‌بندی‌های خدمت</span>
                <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-black text-slate-500">{{ $serviceCategories->count() }} دسته</span>
            </div>
            <div class="mt-2 flex flex-wrap gap-1.5">
                @forelse($visibleCategoryNames as $categoryName)
                    <span class="inline-flex max-w-full items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                        <span class="truncate">{{ $categoryName }}</span>
                    </span>
                @empty
                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200">بدون زیردسته</span>
                @endforelse
                @if($hiddenCategoryCount > 0)
                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-500 ring-1 ring-slate-200">+{{ $hiddenCategoryCount }}</span>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-black text-slate-500">ظرفیت و تخصیص</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">{{ $allocatedPercentage }}٪ تخصیص‌یافته</p>
                </div>
                <div class="text-left">
                    <p class="text-[11px] font-bold text-emerald-600">باقی‌مانده</p>
                    <p class="mt-1 text-base font-bold text-emerald-700">{{ number_format($remainingCapacity, 2) }} <span class="text-[11px] text-emerald-600">{{ $serviceUnitLabel }}</span></p>
                </div>
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-blue-500" style="width: {{ $allocatedPercentage }}%;"></div>
            </div>

            <div class="mt-2 flex items-start justify-between gap-3 text-[10px] leading-4">
                <div class="min-w-0 text-right text-slate-500">
                    <span class="block">مقدار تخصیص‌یافته</span>
                    <span class="mt-0.5 block text-[11px] font-bold text-slate-800">{{ number_format($totalAllocatedQuantity, 2) }} {{ $serviceUnitLabel }}</span>
                </div>
                <div class="min-w-0 text-left text-blue-600">
                    <span class="block">ظرفیت کل</span>
                    <span class="mt-0.5 block text-[11px] font-bold text-blue-700">{{ number_format($totalCapacity, 2) }} {{ $serviceUnitLabel }}</span>
                </div>
            </div>
        </div>

        <div>
            <span class="font-bold text-slate-800">مددکاران تخصیص‌یافته:</span>
            <ul class="mt-1 space-y-1">
                @forelse($operatorAllocations->take(3) as $allocation)
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400"></span>
                        <span>{{ $allocation->socialWorker?->full_name ?? '—' }}</span>
                        <span class="text-xs text-slate-400">({{ number_format((float) $allocation->allocated_quantity, 2) }})</span>
                    </li>
                @empty
                    <li>—</li>
                @endforelse
                @if($operatorAllocations->count() > 3)
                    <li class="flex items-center gap-2 text-xs font-bold text-blue-600">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-blue-300"></span>
                        <span>+{{ $operatorAllocations->count() - 3 }} مددکار دیگر</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    @if(filled($service->description))
        <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-500 sm:mt-4">{{ $service->description }}</p>
    @endif

    <div class="mt-auto border-t border-slate-200 pt-3 sm:pt-4">
        <a href="{{ $allocationEditUrl }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20 active:scale-[0.99] active:bg-blue-800 sm:w-auto">
            مدیریت تخصیص‌ها
        </a>
    </div>
</div>
