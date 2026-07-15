<div class="container mx-auto max-w-7xl p-0" dir="rtl">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 xl:p-7">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl xl:text-[1.7rem]">گزارش‌گیری پیشرفته مددجویان</h1>
                <p class="mt-1 text-sm text-slate-500">فیلترهای پویا، مدیریت ستون‌ها و ذخیره قالب‌های جستجو</p>
            </div>

            <div class="flex items-center gap-2 lg:flex-shrink-0">
                <button wire:click="exportToExcel"
                        title="خروجی اکسل"
                        class="group flex h-11 w-11 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 transition hover:bg-emerald-100 hover:text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/>
                        <line x1="8" y1="17" x2="16" y2="17"/>
                        <line x1="10" y1="9" x2="8" y2="9"/>
                    </svg>
                </button>

{{--                <button wire:click="exportToPdf"--}}
{{--                        title="خروجی PDF"--}}
{{--                        class="group flex h-11 w-11 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700">--}}
{{--                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"--}}
{{--                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">--}}
{{--                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>--}}
{{--                        <polyline points="14 2 14 8 20 8"/>--}}
{{--                        <path d="M9 13h1a2 2 0 0 1 0 4H9v-4z"/>--}}
{{--                        <path d="M14 13h1.5a1.5 1.5 0 0 1 0 3H14v-3z"/>--}}
{{--                        <line x1="17" y1="17" x2="17" y2="17"/>--}}
{{--                    </svg>--}}
{{--                </button>--}}

            </div>


        </div>

        <section class="mb-4 border-y border-emerald-200 bg-emerald-50/60 px-4 py-4 sm:px-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="min-w-0 flex-1">
                    <label for="ai-beneficiary-search" class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-800">
                        <i class="bi bi-stars text-emerald-700" aria-hidden="true"></i>
                        جستجوی مددجویان (هوش مصنوعی)
                    </label>
                    <textarea
                        id="ai-beneficiary-search"
                        wire:model.defer="aiSearchQuery"
                        wire:keydown.ctrl.enter="interpretAiSearch"
                        rows="2"
                        maxlength="1000"
                        class="w-full resize-y rounded-lg border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-emerald-400 focus:ring focus:ring-emerald-100"
                        placeholder="مثال: کودکان دارای معلولیت که در شش ماه گذشته هیچ خدمتی دریافت نکرده‌اند"
                    ></textarea>
                    @error('aiSearchQuery')
                        <p class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="button"
                    wire:click="interpretAiSearch"
                    wire:loading.attr="disabled"
                    wire:target="interpretAiSearch"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60 lg:w-auto lg:shrink-0"
                >
                    <i wire:loading.remove wire:target="interpretAiSearch" class="bi bi-stars" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="interpretAiSearch">تفسیر درخواست</span>
                    <span wire:loading wire:target="interpretAiSearch">در حال تفسیر...</span>
                </button>
            </div>

            @if($aiSearchError)
                <div class="mt-3 border-r-4 border-rose-500 bg-white px-3 py-2 text-xs font-semibold text-rose-700" role="alert">
                    {{ $aiSearchError }}
                </div>
            @endif

            @if($pendingAiSearch)
                <div class="mt-4 border border-emerald-200 bg-white p-4" aria-live="polite">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-800">فیلترهای برداشت‌شده برای تأیید</h2>
                            @if(filled($pendingAiSearch['interpretation'] ?? null))
                                <p class="mt-1 text-xs leading-6 text-slate-600">{{ $pendingAiSearch['interpretation'] }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs font-semibold text-emerald-700">
                            {{ count($pendingAiSearch['filters'] ?? []) }} شرط
                        </span>
                    </div>

                    <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        @foreach(($pendingAiSearch['summaries'] ?? []) as $summary)
                            <div class="flex min-h-10 items-center gap-2 border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                                <i class="bi bi-funnel text-emerald-700" aria-hidden="true"></i>
                                <span class="break-words">{{ $summary }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if(!empty($pendingAiSearch['unresolved']))
                        <div class="mt-3 border-r-4 border-amber-400 bg-amber-50 px-3 py-2">
                            <p class="text-xs font-bold text-amber-800">مواردی که نیاز به بررسی دارند</p>
                            @foreach($pendingAiSearch['unresolved'] as $item)
                                <p class="mt-1 text-xs text-amber-800">{{ $item }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="cancelAiSearch" class="min-h-10 rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            انصراف
                        </button>
                        <button type="button" wire:click="confirmAiSearch" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            تأیید و اجرای جستجو
                        </button>
                    </div>
                </div>
            @endif
        </section>


        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سراسری</label>
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <input type="text" wire:model.live.debounce.300ms="globalSearch" class="w-full min-w-0 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100 lg:flex-1" placeholder="نام، کد ملی یا موبایل...">
                @if(trim($globalSearch) !== '' || count($filters) > 0)
                    <button type="button" wire:click="clearAllFilters" class="w-full rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 lg:w-auto lg:shrink-0">
                        پاک‌کردن همه فیلترها
                    </button>
                @endif
            </div>
        </div>

        <div class="mb-4 rounded-xl border border-slate-200 p-4">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <h2 class="text-sm font-bold text-slate-700">فیلترهای فعال</h2>
                <div x-data="{ open: false, query: '', activeType: 'all' }" class="relative w-full lg:w-auto">
                    <button type="button" @click="open=!open" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white lg:w-auto">+ افزودن فیلتر</button>
                    <div x-show="open" @click.outside="open=false" x-transition class="absolute inset-x-0 right-0 z-20 mt-2 w-full max-w-full rounded-xl border border-slate-200 bg-white p-3 shadow-xl lg:inset-x-auto lg:left-0 lg:right-auto lg:w-[24rem] lg:max-w-[90vw]">
                        <div class="mb-3">
                            <input type="text" x-model="query" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs focus:border-indigo-300 focus:ring focus:ring-indigo-100" placeholder="جستجو در نام فیلترها...">
                        </div>

                        <div class="mb-3 flex flex-wrap gap-1.5 text-[11px]">
                            <button type="button" @click="activeType='all'" :class="activeType === 'all' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600'" class="rounded-full border px-2.5 py-1 font-semibold">همه</button>
                            <button type="button" @click="activeType='text'" :class="activeType === 'text' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600'" class="rounded-full border px-2.5 py-1 font-semibold">متنی</button>
                            <button type="button" @click="activeType='select'" :class="activeType === 'select' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600'" class="rounded-full border px-2.5 py-1 font-semibold">انتخابی</button>
                            <button type="button" @click="activeType='date'" :class="activeType === 'date' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600'" class="rounded-full border px-2.5 py-1 font-semibold">تاریخی</button>
                            <button type="button" @click="activeType='number'" :class="activeType === 'number' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600'" class="rounded-full border px-2.5 py-1 font-semibold">عددی</button>
                        </div>

                        <div class="max-h-72 overflow-y-auto rounded-lg border border-slate-100 bg-slate-50/60 p-2">
                            <div class="grid gap-1.5 sm:grid-cols-2">
                                @foreach($filterableFields as $fieldKey => $meta)
                                    <button
                                        type="button"
                                        wire:click="addFilter('{{ $fieldKey }}')"
                                        @click="open=false"
                                        x-show="(activeType === 'all' || activeType === '{{ $meta['type'] ?? '' }}') && '{{ Str::lower($meta['label']) }}'.includes(query.toLowerCase())"
                                        class="flex min-h-11 items-center justify-between rounded-lg border border-transparent bg-white px-3 py-2 text-right text-xs text-slate-700 transition hover:border-indigo-100 hover:bg-indigo-50/60">
                                        <span>{{ $meta['label'] }}</span>
                                        <span class="mr-2 rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">
                                            {{ $meta['type'] === 'text' ? 'متنی' : ($meta['type'] === 'select' ? 'انتخابی' : ($meta['type'] === 'date' ? 'تاریخی' : 'عددی')) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3 xl:grid xl:grid-cols-2 xl:gap-3 xl:space-y-0">
                @forelse($filters as $index => $filter)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-semibold text-slate-600">{{ $filterableFields[$filter['field']]['label'] ?? $filter['field'] }}</p>
                            <button type="button" wire:click="removeFilter({{ $index }})" class="self-start text-xs font-semibold text-rose-600 sm:self-auto">حذف</button>
                        </div>

                        @if(($filter['type'] ?? null) === 'text' && ($filter['field'] ?? null) === 'caseworker')
                            <div
                                x-data="{
                                    open: false,
                                    activeIndex: 0,
                                    move(step) {
                                        const items = this.$refs.options?.querySelectorAll('[data-option]') ?? [];
                                        if (!items.length) return;
                                        this.activeIndex = (this.activeIndex + step + items.length) % items.length;
                                        items[this.activeIndex].scrollIntoView({ block: 'nearest' });
                                    },
                                    choose() {
                                        const items = this.$refs.options?.querySelectorAll('[data-option]') ?? [];
                                        if (!items.length) return;
                                        items[this.activeIndex].click();
                                    }
                                }"
                                @click.outside="open = false"
                                class="space-y-2"
                            >
                                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    @foreach(($filter['selected'] ?? []) as $selectedWorker)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-medium text-indigo-700">
                                            <span>{{ $selectedWorker['name'] }} - کد: {{ $selectedWorker['code'] }}</span>
                                            <button type="button" wire:click="removeSocialWorker({{ $index }}, {{ (int) $selectedWorker['id'] }})" class="text-indigo-500 hover:text-rose-600">×</button>
                                        </span>
                                    @endforeach

                                    <input
                                        type="text"
                                        x-on:focus="open = true"
                                        x-on:keydown.arrow-down.prevent="open = true; move(1)"
                                        x-on:keydown.arrow-up.prevent="open = true; move(-1)"
                                        x-on:keydown.enter.prevent="choose()"
                                        wire:model.live.debounce.300ms="socialWorkerSearch.{{ $index }}"
                                        class="min-w-[12rem] flex-1 border-0 bg-transparent px-0 py-1 text-xs focus:ring-0"
                                        placeholder="نام یا کد مددکار را وارد کنید..."
                                    >
                                </div>

                                @php
                                    $workerOptions = $socialWorkerOptions[$index] ?? [];
                                    $searchTerm = trim($socialWorkerSearch[$index] ?? '');
                                @endphp

                                @if(mb_strlen($searchTerm) >= 2)
                                    <div x-show="open" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                                        <div x-ref="options" class="max-h-64 overflow-y-auto py-1">
                                            @forelse($workerOptions as $optionIndex => $workerOption)
                                                <button
                                                    type="button"
                                                    data-option
                                                    wire:click="selectSocialWorker({{ $index }}, {{ $workerOption['id'] }})"
                                                    x-on:mouseenter="activeIndex = {{ $optionIndex }}"
                                                    :class="activeIndex === {{ $optionIndex }} ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700'"
                                                    class="flex w-full items-center justify-between px-3 py-2 text-right text-xs hover:bg-indigo-50"
                                                >
                                                    <span>{{ $workerOption['name'] }}</span>
                                                    <span class="text-[11px] text-slate-500">کد مددکار: {{ $workerOption['code'] }}</span>
                                                </button>
                                            @empty
                                                <div class="px-3 py-2 text-xs text-slate-500">موردی یافت نشد.</div>
                                            @endforelse
                                        </div>

                                        @if(($socialWorkerHasMore[$index] ?? false) && count($workerOptions) >= 25)
                                            <div class="border-t border-slate-100 p-2">
                                                <button type="button" wire:click="loadMoreSocialWorkers({{ $index }})" class="w-full rounded-md bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100">
                                                    نمایش نتایج بیشتر
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-500">برای جستجو حداقل ۲ کاراکتر وارد کنید.</p>
                                @endif
                            </div>
                        @elseif(($filter['type'] ?? null) === 'text')
                            <div class="grid gap-2 md:grid-cols-3">
                                <select wire:model.live="filters.{{ $index }}.operator" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                    <option value="contains">شامل</option>
                                    <option value="exact">برابر</option>
                                </select>
                                <input type="text" wire:model.live.debounce.300ms="filters.{{ $index }}.value" class="md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" placeholder="مقدار فیلتر">
                            </div>
                        @endif

                        @if(($filter['type'] ?? null) === 'select')
                            <select wire:model.live="filters.{{ $index }}.value" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">انتخاب کنید</option>
                                @foreach(($filterableFields[$filter['field']]['options'] ?? []) as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if(($filter['type'] ?? null) === 'date')
                            @php($dateModes = $filterableFields[$filter['field']]['date_modes'] ?? ['exact', 'range', 'month', 'year'])
                            <div class="space-y-2">
                                <select wire:model.live="filters.{{ $index }}.mode" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                    @if(in_array('exact', $dateModes, true))
                                        <option value="exact">تاریخ دقیق</option>
                                    @endif
                                    @if(in_array('range', $dateModes, true))
                                        <option value="range">بازه تاریخ</option>
                                    @endif
                                    @if(in_array('month', $dateModes, true))
                                        <option value="month">ماه خاص</option>
                                    @endif
                                    @if(in_array('year', $dateModes, true))
                                        <option value="year">سال خاص</option>
                                    @endif
                                </select>

                                @if(($filter['mode'] ?? 'exact') === 'exact')
                                    <input type="text" wire:model.live="filters.{{ $index }}.exact" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" placeholder="مثال: 1402/07/15">
                                @endif

                                @if(($filter['mode'] ?? null) === 'range')
                                    <div class="grid gap-2 md:grid-cols-2">
                                        <input type="text" wire:model.live="filters.{{ $index }}.from" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" placeholder="از تاریخ (1400/01/01)">
                                        <input type="text" wire:model.live="filters.{{ $index }}.to" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" placeholder="تا تاریخ (1402/12/29)">
                                    </div>
                                @endif

                                @if(($filter['mode'] ?? null) === 'month')
                                    <select wire:model.live="filters.{{ $index }}.month" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                        <option value="">انتخاب ماه</option>
                                        @foreach(\App\Models\Person::$months as $monthValue => $monthLabel)
                                            <option value="{{ $monthValue }}">{{ $monthLabel }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                @if(($filter['mode'] ?? null) === 'year')
                                    <input type="number" wire:model.live="filters.{{ $index }}.year" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" placeholder="سال تولد">
                                @endif
                            </div>
                        @endif

                        @if(($filter['type'] ?? null) === 'number')
                            <div class="grid gap-2 md:grid-cols-3">
                                <select wire:model.live="filters.{{ $index }}.operator" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                    @if(($filter['field'] ?? null) === 'months_without_service')
                                        <option value="gte">حداقل</option>
                                    @else
                                    <option value="eq">= برابر</option>
                                    <option value="gt">&gt; بزرگ‌تر</option>
                                    <option value="gte">&gt;= بزرگ‌تر یا مساوی</option>
                                    <option value="lt">&lt; کوچک‌تر</option>
                                    <option value="lte">&lt;= کوچک‌تر یا مساوی</option>
                                    @endif
                                </select>
                                <input
                                    type="number"
                                    @if(in_array(($filter['field'] ?? ''), ['service_delivery_quantity'], true)) step="any" @endif
                                    wire:model.live.debounce.300ms="filters.{{ $index }}.value"
                                    class="md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"
                                    placeholder="مقدار عددی"
                                >
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-500">هنوز فیلتری اضافه نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="mb-4 grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
            <div class="rounded-xl border border-slate-200 p-4">
                <h2 class="mb-3 text-sm font-bold text-slate-700">مدیریت ستون‌ها</h2>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($availableColumns as $columnKey => $columnLabel)
                        <label class="flex min-w-0 items-center gap-2 rounded-md border border-slate-100 bg-slate-50 px-2 py-1.5 text-xs">
                            <input type="checkbox" @checked(in_array($columnKey, $visibleColumns, true)) wire:click="toggleColumn('{{ $columnKey }}')">
                            <span class="min-w-0 break-words">{{ $columnLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <h2 class="mb-3 text-sm font-bold text-slate-700">فیلترهای ذخیره شده</h2>
                <div class="mb-3 flex flex-col gap-2 md:flex-row">
                    <input type="text" wire:model.defer="saveFilterName" class="w-full min-w-0 rounded-lg border border-slate-200 px-3 py-2 text-xs md:flex-1" placeholder="نام فیلتر">
                    <button type="button" wire:click="saveCurrentFilters" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white md:w-auto md:shrink-0">ذخیره</button>
                </div>
                <div class="space-y-2">
                    @forelse($this->savedFilters as $saved)
                        <div class="flex flex-col gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 lg:flex-row lg:items-center">
                            <button type="button" wire:click="loadSavedFilter({{ $saved->id }})" class="flex min-w-0 flex-1 flex-col items-start justify-between gap-1 text-right text-xs hover:text-indigo-700 lg:flex-row lg:items-center lg:gap-3">
                                <span class="break-words">{{ $saved->name }}</span>
                                <span class="shrink-0 text-slate-400">{{ $saved->created_at->format('Y/m/d') }}</span>
                            </button>
                            <button type="button" wire:click="deleteSavedFilter({{ $saved->id }})" wire:confirm="آیا از حذف این فیلتر ذخیره‌شده مطمئن هستید؟" class="self-start rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 lg:self-auto">
                                حذف
                            </button>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">فیلتر ذخیره شده‌ای وجود ندارد.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-4 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-4 rounded-xl border border-sky-200 bg-gradient-to-l from-sky-50 to-cyan-50 p-4">
            @php($activeColumnFilterCount = collect($columnFilters)->filter(fn ($value) => filled($value))->count())
            <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-sky-700">خلاصه لحظه‌ای نتایج جستجو</p>
                    <p class="mt-1 text-sm font-bold text-slate-800">
                        تعداد رکوردهای یافت‌شده:
                        <span class="rounded-md bg-white px-2 py-0.5 text-sky-700">{{ number_format($this->people->total()) }}</span>
                    </p>
                </div>
                <div class="text-xs text-slate-600 lg:text-left">
                    <span class="font-semibold">تعداد شروط فعال:</span>
                    <span class="rounded-md bg-white px-2 py-0.5 text-slate-800">{{ count($filters) + $activeColumnFilterCount }}</span>
                </div>
            </div>
            @if(count($filters) > 0 || $activeColumnFilterCount > 0)
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($filters as $index => $filter)
                        <button type="button" wire:click="removeFilter({{ $index }})" class="rounded-full border border-sky-200 bg-white px-2.5 py-1 text-[11px] text-slate-700 hover:bg-sky-50">
                            {{ $filterableFields[$filter['field']]['label'] ?? $filter['field'] }} ×
                        </button>
                    @endforeach
                    @foreach($columnFilters as $columnKey => $value)
                        @if(filled($value))
                            <span class="rounded-full border border-cyan-200 bg-white px-2.5 py-1 text-[11px] text-slate-700">
                                {{ $availableColumns[$columnKey] ?? $columnKey }}: {{ $value }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div
                class="max-h-[70vh] overflow-auto overscroll-contain"
                tabindex="0"
                role="region"
                aria-label="Advanced beneficiary report results"
            >
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-100 text-slate-700 shadow-sm">
                    <tr>
                        @foreach($visibleColumns as $columnKey)
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold">
                                @if($this->isSortableColumn($columnKey))
                                    <button type="button" wire:click="toggleSort('{{ $columnKey }}')" class="flex items-center gap-1 font-bold text-slate-700 hover:text-indigo-700">
                                        <span>{{ $availableColumns[$columnKey] ?? $columnKey }}</span>
                                        <span class="text-[10px] text-slate-500">
                                            @if($sortColumn === $columnKey)
                                                {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                            @else
                                                ↕
                                            @endif
                                        </span>
                                    </button>
                                @else
                                    <span>{{ $availableColumns[$columnKey] ?? $columnKey }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                    <tr class="border-t border-slate-200 bg-white/80">
                        @foreach($visibleColumns as $columnKey)
                            @php($columnFilter = $columnFilterDefinitions[$columnKey] ?? null)
                            <th class="px-4 py-2 text-right">
                                @if($columnFilter)
                                    @if(($columnFilter['type'] ?? null) === 'select')
                                        <select wire:model.live="columnFilters.{{ $columnKey }}" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-normal text-slate-600">
                                            <option value="">همه</option>
                                            @foreach(($columnFilter['options'] ?? []) as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input
                                            type="{{ $columnFilter['type'] === 'number' ? 'number' : 'text' }}"
                                            wire:model.live.debounce.300ms="columnFilters.{{ $columnKey }}"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-normal text-slate-600"
                                            placeholder="{{ $columnFilter['placeholder'] ?? 'فیلتر...' }}"
                                        >
                                    @endif
                                @else
                                    <span class="text-[11px] font-normal text-slate-300">-</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($this->people as $person)
                        <tr wire:key="advanced-person-row-{{ $person->id }}" wire:click="showPersonInfo({{ $person->id }})" class="cursor-pointer transition hover:bg-slate-50">
                            @foreach($visibleColumns as $columnKey)
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-700">
                                    {{ $reportColumnRegistry->value($person, $columnKey) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(count($visibleColumns), 1) }}" class="px-4 py-8 text-center text-xs text-slate-500">داده‌ای یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $this->people->links() }}
        </div>

        @include('livewire.people.partials.person-details-modal')
    </div>
</div>
