{{-- Compact per-section filter/sort toolbar for the gate report tab.
     $section: one of GateTechnicalReport::CONTROL_SECTIONS (pending,
     delivered, checks) — state lives in the component's $listControls array
     and only affects that section's lists. The category control is a
     searchable combobox (same Alpine pattern as the service-report
     «مددکار اجتماعی» filter). --}}
@php
    $categoryList = $categoryOptions->map(fn ($name, $id): array => ['id' => (int) $id, 'name' => $name])->values()->all();
    $selectedCategoryId = (int) ($this->listControls[$section]['category'] ?? 0);
    $selectedCategoryName = $selectedCategoryId ? ($categoryOptions[$selectedCategoryId] ?? null) : null;
@endphp
<div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-5 py-2.5">
    <div class="relative">
        <svg class="pointer-events-none absolute start-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
        </svg>
        <input
            type="text"
            wire:model.live.debounce.600ms="listControls.{{ $section }}.recipient"
            placeholder="جست‌وجوی نام گیرنده…"
            class="h-8 w-40 rounded-lg border border-slate-200 bg-white ps-7 pe-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-200 sm:w-48"
        />
    </div>

    <div
        x-data="{
            open: false,
            query: '',
            categories: @js($categoryList),
            normalizeSearchText(value) {
                let text = String(value ?? '');

                text = text
                    .replace(/[يى]/g, 'ی')
                    .replace(/ك/g, 'ک')
                    .replace(/[ۀة]/g, 'ه')
                    .replace(/[آأإٱ]/g, 'ا')
                    .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
                    .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
                text = text.replace(/[\u200C\u200D\uFEFF\u00A0]/g, ' ');

                return text.toLowerCase().replace(/\s+/g, ' ').trim();
            },
            get filtered() {
                const query = this.normalizeSearchText(this.query);

                if (! query) {
                    return this.categories;
                }

                const terms = query.split(' ');

                return this.categories.filter((category) => {
                    const haystack = this.normalizeSearchText(category.name).replace(/ /g, '');

                    return terms.every((term) => haystack.includes(term.replace(/ /g, '')));
                });
            },
            choose(id) {
                this.$wire.set('listControls.{{ $section }}.category', id === null ? '' : String(id));
                this.open = false;
                this.query = '';
            },
        }"
        class="relative"
    >
        <button
            type="button"
            aria-label="دسته‌بندی"
            @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
            class="flex h-8 w-40 items-center justify-between gap-1.5 rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 outline-none transition hover:border-slate-300 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200 sm:w-48"
        >
            <span class="truncate">{{ $selectedCategoryName ?: 'همه دسته‌بندی‌ها' }}</span>
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            @click.outside="open = false"
            class="absolute z-30 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl"
        >
            <input
                x-ref="search"
                type="text"
                x-model="query"
                placeholder="جستجوی دسته‌بندی…"
                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-1 focus:ring-indigo-200"
            >

            <div class="mt-1 max-h-56 space-y-0.5 overflow-y-auto">
                <button type="button" @click="choose(null)" class="w-full rounded-lg px-2.5 py-1.5 text-right text-xs font-bold text-slate-600 transition hover:bg-slate-50">همه دسته‌بندی‌ها</button>
                <template x-for="category in filtered" :key="category.id">
                    <button type="button" @click="choose(category.id)" x-text="category.name" class="w-full truncate rounded-lg px-2.5 py-1.5 text-right text-xs text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"></button>
                </template>
                <p x-show="filtered.length === 0" class="px-2.5 py-1.5 text-[11px] text-slate-400">دسته‌بندی با این جستجو یافت نشد.</p>
            </div>
        </div>
    </div>

    <select
        wire:model.live="listControls.{{ $section }}.sort"
        class="h-8 rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-200"
    >
        @foreach($sortOptions as $sortKey => $sortLabel)
            <option value="{{ $sortKey }}">{{ $sortLabel }}</option>
        @endforeach
    </select>

    @if($this->isSectionFiltered($section))
        <button
            type="button"
            wire:click="resetListControls('{{ $section }}')"
            class="ms-auto inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-bold text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            پاک‌سازی فیلترها
        </button>
    @endif
</div>
