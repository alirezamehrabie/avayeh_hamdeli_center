<div x-cloak x-show="previewOpen">
    <div
        x-show="previewOpen"
        x-transition.opacity.duration.150ms
        class="fixed inset-0 z-20 bg-white/30 backdrop-blur-[3px]"
        aria-hidden="true"
    ></div>

    <div
        x-show="previewOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        class="absolute right-0 top-full z-30 mt-2 w-full origin-top rounded-2xl border border-slate-200 bg-white p-3 text-right shadow-xl shadow-slate-900/10 ring-1 ring-black/5 sm:w-96"
    >
        <div class="mb-2 flex items-center justify-between gap-3">
            <p class="truncate text-xs font-black text-slate-700">{{ $latestMiscServiceSummary['name'] }}</p>
            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                {{ number_format($latestMiscServiceCategories->count()) }} گروه
            </span>
        </div>

        @if($latestMiscServiceCategories->isNotEmpty())
            <div class="max-h-64 space-y-1 overflow-y-auto overscroll-contain pr-0.5">
                @foreach($latestMiscServiceCategories as $category)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200/60">
                        <span class="min-w-0 truncate text-xs font-bold text-slate-700">{{ $category->name }}</span>
                        <span class="shrink-0 text-[11px] font-semibold text-slate-500">
                            {{ number_format((float) $category->quantity, 2) }} {{ $unitOptions[$category->unit] ?? $category->unit }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-semibold text-slate-500 ring-1 ring-slate-200/60">
                دسته‌ای برای این خدمت ثبت نشده است.
            </div>
        @endif
    </div>
</div>
