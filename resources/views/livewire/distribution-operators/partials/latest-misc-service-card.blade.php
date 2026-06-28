@if($latestMiscService && $latestMiscServiceSummary)
    @php
        $latestMiscServiceCategories = $latestMiscService->categories
            ->sortBy([
                ['sort_id', 'desc'],
                ['id', 'desc'],
            ])
            ->values();
        $latestMiscServiceIsMisc = (int) ($latestMiscService->created_by ?? 0) === (int) auth()->id();
        $latestMiscServiceEditButtonClasses = $latestMiscServiceIsMisc
            ? 'border border-emerald-200 bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 text-white shadow-emerald-700/25 hover:border-emerald-300 hover:from-emerald-600 hover:via-emerald-700 hover:to-teal-700 focus:ring-emerald-500/20 active:from-emerald-700 active:via-emerald-800 active:to-teal-800'
            : 'bg-blue-600 text-white shadow-blue-600/20 hover:bg-blue-700 focus:ring-blue-500/20 active:bg-blue-800';
    @endphp

    <div
        x-data="pressHoldPreview()"
        x-on:keyup.escape.window="endPreview()"
        class="relative"
    >
        <div
            role="link"
            tabindex="0"
            onclick="window.location.href = '{{ route('distribution-operator.edit-service', $latestMiscService->id) }}';"
            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = '{{ route('distribution-operator.edit-service', $latestMiscService->id) }}'; }"
            x-on:click.capture="handleCardClick($event)"
            x-on:pointerdown="beginPreview()"
            x-on:pointerup.window="endPreview()"
            x-on:pointercancel.window="endPreview()"
            x-on:blur="endPreview()"
            x-on:contextmenu.prevent
            class="group flex items-center gap-3 rounded-2xl border border-slate-200/70 bg-white px-3 py-2.5 shadow-sm transition hover:border-slate-300 hover:bg-slate-50/60 focus:outline-none focus:ring-4 focus:ring-slate-100 sm:px-4"
        >
            <span
                class="flex h-9 w-9 shrink-0 select-none items-center justify-center rounded-xl bg-slate-100 text-slate-500 ring-1 ring-slate-200/60 transition group-hover:bg-slate-200/70 group-active:scale-95"
                aria-label="پیش‌نمایش دسته‌های خدمت"
            >
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

            <a
                href="{{ route('distribution-operator.edit-service', $latestMiscService->id) }}"
                x-on:click.stop
                x-on:keydown.stop
                x-on:pointerdown.stop
                x-on:pointerup.stop
                x-on:pointercancel.stop
                x-on:contextmenu.stop
                class="flex shrink-0 items-center gap-1 rounded-xl px-3 py-1.5 text-xs font-bold shadow-sm transition focus:outline-none focus:ring-4 active:scale-[0.99] {{ $latestMiscServiceEditButtonClasses }}"
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16.5 4.5l3 3L8 19H5v-3L16.5 4.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>ویرایش</span>
            </a>
        </div>

        @include('livewire.distribution-operators.partials.latest-misc-service-categories-preview')
    </div>
@endif
