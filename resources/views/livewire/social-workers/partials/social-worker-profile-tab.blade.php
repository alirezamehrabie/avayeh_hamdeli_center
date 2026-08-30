@foreach($detailSections as $section)
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900">{{ $section['title'] }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $section['subtitle'] }}</p>
            </div>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $section['accent'] }}">
                <i class="bi {{ $section['icon'] }} text-base"></i>
            </span>
        </div>

        <div class="grid gap-2 {{ $section['grid'] }}">
            @foreach($section['items'] as $item)
                <div class="flex min-h-[4.75rem] items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                        <i class="bi {{ $item['icon'] }} text-sm"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold text-slate-500">{{ $item['label'] }}</p>
                        <p class="mt-1 break-words text-sm font-bold leading-6 text-slate-900" @if(isset($item['dir'])) dir="{{ $item['dir'] }}" @endif>
                            {{ $item['value'] }}
                        </p>
                        @if(isset($item['meta_label']))
                            <div class="mt-2 inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $section['metaAccent'] }}">
                                <span class="shrink-0 opacity-75">{{ $item['meta_label'] }}:</span>
                                <span class="truncate">{{ $item['meta_value'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-3 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-extrabold text-slate-900">خانوارهای تحت پوشش</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">فهرست خانوارهای تخصیص‌یافته به این مددکار</p>
        </div>
        <span class="inline-flex items-center justify-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100">
            {{ number_format((int) ($selectedWorker->guardians_count ?? 0)) }} خانوار
        </span>
    </div>

    @if($selectedWorker->guardians->isNotEmpty())
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($selectedWorker->guardians->take(60) as $guardian)
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="truncate text-sm font-bold text-slate-900">
                        {{ trim(($guardian->first_name ?? '') . ' ' . ($guardian->last_name ?? '')) ?: 'بدون نام' }}
                    </p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                        <span dir="ltr">کد خانوار: {{ $guardian->guardian_code ?: '-' }}</span>
                        <span>{{ number_format((int) ($guardian->people_count ?? 0)) }} مددجو</span>
                    </div>
                </div>
            @endforeach
        </div>
        @if($selectedWorker->guardians->count() > 60)
            <p class="mt-3 text-center text-[11px] text-slate-500">و {{ number_format($selectedWorker->guardians->count() - 60) }} خانوار دیگر…</p>
        @endif
    @else
        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center text-xs text-slate-500">
            خانواری برای این مددکار ثبت نشده است.
        </p>
    @endif
</div>
