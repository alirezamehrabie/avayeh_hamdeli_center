<div class="space-y-6">
    @if($latestMiscService && $latestMiscServiceSummary)
        <section class="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/70 via-white to-white px-3 py-3 shadow-sm shadow-emerald-900/5 sm:px-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 7.5h14M8 12h8M10 16.5h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700 ring-1 ring-emerald-100">
                                آخرین خدمت
                            </span>
                            <h2 class="truncate text-sm font-black text-slate-900 sm:text-base">
                                {{ $latestMiscServiceSummary['name'] }}
                            </h2>
                        </div>

                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-bold text-slate-500">
                            <span>
                                {{ rtrim(rtrim(number_format($latestMiscServiceSummary['total_quantity'], 2), '0'), '.') }}
                                {{ $latestMiscServiceSummary['unit_label'] }}
                            </span>
                            <span class="text-slate-300">•</span>
                            <span>{{ number_format($latestMiscServiceSummary['worker_count']) }} مددکار</span>
                            @if($latestMiscServiceSummary['date'])
                                <span class="text-slate-300">•</span>
                                <span>{{ $latestMiscServiceSummary['date'] }}</span>
                            @endif
                            <span class="hidden text-emerald-700 sm:inline">امروز {{ number_format($todayMiscCount) }} خدمت</span>
                        </div>

                        @if($latestMiscServiceSummary['category_names']->isNotEmpty())
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach($latestMiscServiceSummary['category_names'] as $categoryName)
                                    <span class="inline-flex max-w-28 items-center rounded-full bg-white/75 px-2 py-0.5 text-[10px] font-bold text-emerald-800 ring-1 ring-emerald-100">
                                        <span class="truncate">{{ $categoryName }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 md:flex md:shrink-0 md:items-center">
                    <a
                        href="{{ route('distribution-operator.edit-service', $latestMiscService->id) }}"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 text-xs font-black text-white shadow-sm shadow-emerald-600/15 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16.5 4.5l3 3L8 19H5v-3L16.5 4.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        ویرایش
                    </a>
                    <a
                        href="{{ route('distribution-operator.service-list', ['tab' => 'misc']) }}"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        لیست
                    </a>
                </div>
            </div>
        </section>
    @endif

    <livewire:distribution-operators.service-batch-creator />
</div>
