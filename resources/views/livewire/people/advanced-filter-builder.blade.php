<div class="container mx-auto max-w-7xl p-0" dir="rtl">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 xl:p-7">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl xl:text-[1.7rem]">گزارش‌گیری پیشرفته مددجویان</h1>
                <p class="mt-1 text-sm text-slate-500">فیلترهای پویا، مدیریت ستون‌ها و ذخیره قالب‌های جستجو</p>
            </div>

            <div class="flex items-center gap-2 lg:flex-shrink-0">
                <button wire:click="exportToExcel"
                        class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100">
                    خروجی اکسل
                </button>

                <button wire:click="exportToPdf"
                        class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 transition hover:bg-rose-100">
                    خروجی PDF
                </button>
            </div>
        </div>


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

                        @if(($filter['type'] ?? null) === 'text')
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
                            <div class="space-y-2">
                                <select wire:model.live="filters.{{ $index }}.mode" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                    <option value="exact">تاریخ دقیق</option>
                                    <option value="range">بازه تاریخ</option>
                                    <option value="month">ماه خاص</option>
                                    <option value="year">سال خاص</option>
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
                                    <option value="eq">= برابر</option>
                                    <option value="gt">&gt; بزرگ‌تر</option>
                                    <option value="gte">&gt;= بزرگ‌تر یا مساوی</option>
                                    <option value="lt">&lt; کوچک‌تر</option>
                                    <option value="lte">&lt;= کوچک‌تر یا مساوی</option>
                                </select>
                                <input type="number" wire:model.live.debounce.300ms="filters.{{ $index }}.value" class="md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" placeholder="مقدار عددی">
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
                    <span class="rounded-md bg-white px-2 py-0.5 text-slate-800">{{ count($filters) }}</span>
                </div>
            </div>
            @if(count($filters) > 0)
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($filters as $index => $filter)
                        <button type="button" wire:click="removeFilter({{ $index }})" class="rounded-full border border-sky-200 bg-white px-2.5 py-1 text-[11px] text-slate-700 hover:bg-sky-50">
                            {{ $filterableFields[$filter['field']]['label'] ?? $filter['field'] }} ×
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        @foreach($visibleColumns as $columnKey)
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold">{{ $availableColumns[$columnKey] ?? $columnKey }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($this->people as $person)
                        <tr wire:key="advanced-person-row-{{ $person->id }}" wire:click="showPersonInfo({{ $person->id }})" class="cursor-pointer transition hover:bg-slate-50">
                            @foreach($visibleColumns as $columnKey)
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-700">
                                    @switch($columnKey)
                                        @case('full_name')
                                            {{ $person->full_name }}
                                            @break
                                        @case('birth_date')
                                            {{ $person->birth_date ?? '-' }}
                                            @break
                                        @case('created_at')
                                            {{ optional($person->created_at)->format('Y/m/d') }}
                                            @break
                                        @default
                                            {{ data_get($person, $columnKey) ?? '-' }}
                                    @endswitch
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
