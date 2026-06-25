<div class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:rounded-3xl sm:p-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-semibold text-violet-700">{{ $service->code }}</p>
            <h3 class="mt-1 line-clamp-2 text-base font-black leading-6 text-slate-800">{{ $service->serviceName?->name ?? '—' }}</h3>
        </div>
        <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">تعریف جدید</span>
    </div>

    <div class="mt-3 space-y-2 text-sm text-slate-600 sm:mt-4">
        <p><span class="font-bold text-slate-800">دسته‌بندی:</span> {{ $service->serviceCategory?->name ?? 'نامشخص' }}</p>
        <p><span class="font-bold text-slate-800">تعداد:</span> {{ number_format((float) $service->total_quantity, 2) }} {{ $serviceUnitLabel }}</p>
        <p><span class="font-bold text-slate-800">مددکار:</span> {{ $service->socialWorkers->first()?->full_name ?? '—' }}</p>
        <p><span class="font-bold text-slate-800">تاریخ:</span> {{ $distributionStartDate }}</p>
    </div>

    @if(filled($service->description))
        <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-500 sm:mt-4">{{ $service->description }}</p>
    @endif

    <div class="mt-auto border-t border-slate-200 pt-3 sm:pt-4">
        <a href="{{ route('distribution-operator.edit-service', $service->id) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-violet-200 bg-white px-4 py-2.5 text-sm font-bold text-violet-700 transition hover:bg-violet-50 focus:outline-none focus:ring-4 focus:ring-violet-500/10 active:scale-[0.99] active:bg-violet-100">
            ویرایش خدمت
        </a>
    </div>
</div>
