<div class="container mx-auto p-4" dir="rtl">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">گزارش‌گیری پیشرفته مددجویان</h1>
                <p class="mt-1 text-sm text-slate-500">فیلترهای پویا، مدیریت ستون‌ها و ذخیره قالب‌های جستجو</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="exportToExcel" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">خروجی اکسل</button>
                <button wire:click="exportToPdf" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">خروجی PDF</button>
            </div>
        </div>

        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سراسری</label>
            <input type="text" wire:model.live.debounce.300ms="globalSearch" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100" placeholder="نام، کد ملی یا موبایل...">
        </div>

        <div class="mb-4 rounded-xl border border-slate-200 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-700">فیلترهای فعال</h2>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open=!open" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">+ افزودن فیلتر</button>
                    <div x-show="open" @click.outside="open=false" x-transition class="absolute left-0 z-20 mt-2 w-56 rounded-lg border border-slate-200 bg-white p-2 shadow-lg">
                        @foreach($filterableFields as $fieldKey => $meta)
                            <button type="button" wire:click="addFilter('{{ $fieldKey }}')" @click="open=false" class="block w-full rounded-md px-3 py-2 text-right text-xs text-slate-700 hover:bg-slate-100">
                                {{ $meta['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($filters as $index => $filter)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-semibold text-slate-600">{{ $filterableFields[$filter['field']]['label'] ?? $filter['field'] }}</p>
                            <button type="button" wire:click="removeFilter({{ $index }})" class="text-xs font-semibold text-rose-600">حذف</button>
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
                    </div>
                @empty
                    <p class="text-xs text-slate-500">هنوز فیلتری اضافه نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="mb-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-4">
                <h2 class="mb-3 text-sm font-bold text-slate-700">مدیریت ستون‌ها</h2>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($availableColumns as $columnKey => $columnLabel)
                        <label class="flex items-center gap-2 rounded-md border border-slate-100 bg-slate-50 px-2 py-1.5 text-xs">
                            <input type="checkbox" @checked(in_array($columnKey, $visibleColumns, true)) wire:click="toggleColumn('{{ $columnKey }}')">
                            <span>{{ $columnLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <h2 class="mb-3 text-sm font-bold text-slate-700">فیلترهای ذخیره شده</h2>
                <div class="mb-3 flex gap-2">
                    <input type="text" wire:model.defer="saveFilterName" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-xs" placeholder="نام فیلتر">
                    <button type="button" wire:click="saveCurrentFilters" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">ذخیره</button>
                </div>
                <div class="space-y-2">
                    @forelse($this->savedFilters as $saved)
                        <div class="flex items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <button type="button" wire:click="loadSavedFilter({{ $saved->id }})" class="flex flex-1 items-center justify-between text-xs hover:text-indigo-700">
                                <span>{{ $saved->name }}</span>
                                <span class="text-slate-400">{{ $saved->created_at->format('Y/m/d') }}</span>
                            </button>
                            <button type="button" wire:click="deleteSavedFilter({{ $saved->id }})" wire:confirm="آیا از حذف این فیلتر ذخیره‌شده مطمئن هستید؟" class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">
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
                        <tr class="hover:bg-slate-50">
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
    </div>
</div>
