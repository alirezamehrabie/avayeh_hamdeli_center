<div
    @if(filled($miscEditUrl))
        role="link"
        tabindex="0"
        onclick="if (! event.target.closest('a')) window.location.href = '{{ $miscEditUrl }}';"
        onkeydown="if ((event.key === 'Enter' || event.key === ' ') && ! event.target.closest('a')) { event.preventDefault(); window.location.href = '{{ $miscEditUrl }}'; }"
    @endif
    class="flex flex-col rounded-2xl border border-emerald-200 bg-white p-3 shadow-sm transition hover:border-emerald-300 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-500/10 active:scale-[0.99]"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-[11px] font-medium tracking-wide text-slate-400">{{ $service->code }}</p>
            <h3 class="mt-1 truncate text-sm font-black leading-5 text-slate-800">{{ $service->serviceName?->name ?? '—' }}</h3>
        </div>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
        <span>{{ $service->serviceCategory?->name ?? 'نامشخص' }}</span>
        <span>•</span>
        <span>{{ number_format((float) $service->total_quantity, 2) }} {{ $serviceUnitLabel }}</span>
        <span>•</span>
        <span>{{ $distributionStartDate }}</span>
    </div>

    <div class="mt-2 border-t border-slate-100 pt-2">
        <p class="truncate text-sm text-slate-600">
            {{ $service->socialWorkers->first()?->full_name ?? '—' }}
        </p>
    </div>

    @if(filled($miscEditUrl))
        <div class="mt-auto border-t border-slate-200 pt-3 sm:pt-4">
        <a href="{{ $miscEditUrl }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-sm shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 active:scale-[0.99] active:bg-emerald-800 sm:w-auto">
            ویرایش خدمت
        </a>
        </div>
    @endif
</div>
