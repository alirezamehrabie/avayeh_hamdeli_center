<div class="flex flex-col rounded-2xl border border-emerald-200 bg-white p-3 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-[11px] font-medium tracking-wide text-slate-400">{{ $service->code }}</p>
            <h3 class="mt-1 truncate text-sm font-black leading-5 text-slate-800">{{ $service->serviceName?->name ?? '—' }}</h3>
        </div>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
        <div
            class="relative"
            x-data="{ open: false }"
            x-on:keydown.escape.window="open = false"
        >
            <button
                type="button"
                @click.stop="open = ! open"
                @keydown.enter.stop.prevent="open = ! open"
                @keydown.space.stop.prevent="open = ! open"
                :aria-expanded="open.toString()"
                aria-haspopup="dialog"
                class="inline-flex h-7 items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 text-[11px] font-black text-emerald-700 transition hover:border-emerald-200 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                aria-label="نمایش دسته‌بندی‌های خدمت"
            >
                <span>{{ $serviceCategories->count() ?: 0 }} دسته</span>
                <svg class="h-3.5 w-3.5 transition" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.origin.top.right
                @click.stop
                @click.outside="open = false"
                class="absolute right-0 z-30 mt-2 w-[min(16rem,calc(100vw-2rem))] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-2 text-right shadow-xl shadow-slate-900/10 ring-1 ring-black/5"
                role="dialog"
                aria-label="دسته‌بندی‌های خدمت"
            >
                <div class="mb-2 flex items-center justify-between gap-3 border-b border-slate-100 px-2 pb-2">
                    <span class="text-xs font-black text-slate-700">دسته‌بندی‌ها</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $serviceCategories->count() }} مورد</span>
                </div>

                @if($serviceCategories->isNotEmpty())
                    <div class="max-h-56 space-y-1 overflow-y-auto overscroll-contain pr-0.5">
                        @foreach($serviceCategories as $category)
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200/60">
                                <span class="min-w-0 truncate text-xs font-bold text-slate-700">{{ $category->name ?: 'نامشخص' }}</span>
                                <span class="shrink-0 text-[11px] font-semibold text-slate-500">
                                    {{ number_format((float) $category->quantity, 2) }} {{ $unitOptions[$category->unit] ?? ($category->unit ?? $serviceUnitLabel) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-400">
                        دسته‌بندی ثبت نشده است.
                    </p>
                @endif
            </div>
        </div>
        <span class="inline-flex h-7 items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 text-[11px] font-bold text-slate-500">
            {{ $distributionStartDate }}
        </span>
    </div>

    <div class="mt-2 border-t border-slate-100 pt-2">
        @php
            $miscCardSocialWorkers = $service->socialWorkers
                ->unique(fn ($worker) => (int) $worker->id)
                ->values();
            $visibleMiscCardSocialWorkers = $miscCardSocialWorkers->take(3);
            $hiddenMiscCardSocialWorkers = $miscCardSocialWorkers->slice(3)->values();
        @endphp

        @if($miscCardSocialWorkers->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach($visibleMiscCardSocialWorkers as $worker)
                    <span class="inline-flex max-w-full items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                        <span class="truncate">{{ $worker->full_name ?: '—' }}</span>
                    </span>
                @endforeach

                @if($hiddenMiscCardSocialWorkers->isNotEmpty())
                    <div
                        class="relative"
                        x-data="{ open: false }"
                        x-on:keydown.escape.window="open = false"
                    >
                        <button
                            type="button"
                            @click.stop="open = ! open"
                            @keydown.enter.stop.prevent="open = ! open"
                            @keydown.space.stop.prevent="open = ! open"
                            :aria-expanded="open.toString()"
                            aria-haspopup="dialog"
                            class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-black text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-100"
                            aria-label="نمایش مددکاران بیشتر"
                        >
                            +{{ $hiddenMiscCardSocialWorkers->count() }}
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            x-transition.origin.top.right
                            @click.stop
                            @click.outside="open = false"
                            class="absolute right-0 z-30 mt-2 w-[min(14rem,calc(100vw-2rem))] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-2 text-right shadow-xl shadow-slate-900/10 ring-1 ring-black/5"
                            role="dialog"
                            aria-label="مددکاران بیشتر"
                        >
                            <div class="mb-2 flex items-center justify-between gap-3 border-b border-slate-100 px-2 pb-2">
                                <span class="text-xs font-black text-slate-700">مددکاران دیگر</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $hiddenMiscCardSocialWorkers->count() }} مورد</span>
                            </div>

                            <div class="max-h-48 space-y-1 overflow-y-auto overscroll-contain pr-0.5">
                                @foreach($hiddenMiscCardSocialWorkers as $worker)
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200/60">
                                        <span class="block truncate">{{ $worker->full_name ?: '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-400">بدون مددکار</span>
        @endif
    </div>

    @if(filled($miscEditUrl))
        <div class="mt-auto border-t border-slate-200 pt-3 sm:pt-4">
        <a href="{{ $miscEditUrl }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-sm shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 active:scale-[0.99] active:bg-emerald-800 sm:w-auto">
            ویرایش خدمت
        </a>
        </div>
    @endif
</div>
