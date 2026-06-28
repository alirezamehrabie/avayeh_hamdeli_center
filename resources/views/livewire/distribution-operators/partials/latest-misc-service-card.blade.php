@if($latestMiscService && $latestMiscServiceSummary)
    <a
        href="{{ route('distribution-operator.edit-service', $latestMiscService->id) }}"
        class="group flex items-center gap-3 rounded-2xl border border-slate-200/70 bg-white px-3 py-2.5 shadow-sm transition hover:border-slate-300 hover:bg-slate-50/60 focus:outline-none focus:ring-4 focus:ring-slate-100 sm:px-4"
    >
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 ring-1 ring-slate-200/60">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 7.5h10M7 12h7M7 16.5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                <path d="M5.5 4.5h13a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-13a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5" />
            </svg>
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-bold text-slate-400">آخرین خدمت</span>
                @if($latestMiscServiceSummary['date'])
                    <span class="text-[10px] font-semibold text-slate-300">•</span>
                    <span class="text-[10px] font-bold text-slate-400">{{ $latestMiscServiceSummary['date'] }}</span>
                @endif
            </div>
            <p class="truncate text-sm font-black text-slate-800">
                {{ $latestMiscServiceSummary['name'] }}
            </p>
        </div>

        <div class="hidden shrink-0 items-center gap-1.5 sm:flex">
            <span class="rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200/60">
                {{ number_format($latestMiscServiceSummary['category_count']) }} گروه
            </span>
            <span class="rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200/60">
                {{ number_format($latestMiscServiceSummary['allocation_count']) }} ارجاع
            </span>
        </div>

        <span class="flex shrink-0 items-center gap-1 rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white transition group-hover:bg-slate-800">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M16.5 4.5l3 3L8 19H5v-3L16.5 4.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>ویرایش</span>
        </span>
    </a>
@endif
