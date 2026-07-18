<div class="space-y-4" dir="rtl">
    @php
        $badgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'ongoing' => 'bg-amber-100 text-amber-700',
            'closed' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="min-h-[104px] border-b border-blue-700/30 bg-[#2563EB] px-4 py-4 text-white sm:px-6 sm:py-6">
            <div class="flex flex-col gap-3 sm:gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-lg font-extrabold leading-tight text-white sm:mt-2 sm:text-2xl">مدیریت فعالیت‌ها</h1>
                    <p class="mt-1 hidden max-w-3xl text-sm leading-6 text-blue-50/90 sm:mt-2 sm:block">فعالیت‌ها را ایجاد، ویرایش و در چرخه برگزاری مدیریت کنید.</p>
                </div>
                @can('full-access')
                    <button type="button" wire:click="createActivity" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60">افزودن فعالیت</button>
                @endcan
            </div>
        </div>

        <div class="space-y-4 p-4">
            @if (session('activity-success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('activity-success') }}</div>
            @endif

            @error('status')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror
            @error('starts_at')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror
            @error('startsFrom')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror
            @error('startsUntil')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror

            <div class="sticky top-0 z-20 space-y-3 rounded-2xl border border-slate-100 bg-slate-50/40 shadow-sm transition-all duration-200">
                <div class="space-y-3 px-3 pt-3">
                    <div class="relative" x-data="{ helpOpen: false }">
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در کد، نام، مکان یا نام ثبت‌کننده..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100">

                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                <i class="bi bi-search text-sm"></i>
                            </span>

                            <div class="absolute inset-y-0 left-0 flex items-center pl-2">
                                @if($search)
                                    <button type="button" wire:click="$set('search', '')" class="inline-flex items-center justify-center rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none">
                                        <i class="bi bi-x-lg text-sm"></i>
                                    </button>
                                @else
                                    <div class="relative">
                                        <button type="button" @click="helpOpen = !helpOpen" class="inline-flex items-center justify-center rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none">
                                            <i class="bi bi-question-circle text-sm"></i>
                                        </button>

                                        <div x-show="helpOpen" @click.outside="helpOpen = false" x-transition class="absolute left-0 z-20 mt-2 w-64 rounded-lg border border-slate-200 bg-white p-3 shadow-lg" style="display: none;">
                                            <div class="space-y-2">
                                                <h4 class="text-xs font-semibold text-slate-900">نحوه جستجو:</h4>
                                                <ul class="space-y-1.5 text-xs text-slate-600">
                                                    <li class="flex gap-2">
                                                        <span class="shrink-0 font-semibold text-slate-700">کد:</span>
                                                        <span>کد فعالیت را جستجو کنید (مثال: ACT001)</span>
                                                    </li>
                                                    <li class="flex gap-2">
                                                        <span class="shrink-0 font-semibold text-slate-700">نام:</span>
                                                        <span>نام فعالیت را جستجو کنید (مثال: ورزشی)</span>
                                                    </li>
                                                    <li class="flex gap-2">
                                                        <span class="shrink-0 font-semibold text-slate-700">مکان:</span>
                                                        <span>محل برگزاری را جستجو کنید (مثال: سالن)</span>
                                                    </li>
                                                    <li class="flex gap-2">
                                                        <span class="shrink-0 font-semibold text-slate-700">ثبت‌کننده:</span>
                                                        <span>نام کسی که فعالیت را ایجاد کرد</span>
                                                    </li>
                                                </ul>
                                                <div class="border-t border-slate-100 pt-2">
                                                    <p class="text-[11px] text-slate-500">جستجو در تمام موارد بالا همزمان انجام می‌شود.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($search)
                            <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2.5 ring-1 ring-violet-200">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-[11px] font-medium text-slate-600 shrink-0">جستجو فعال:</span>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 truncate">
                                        <i class="bi bi-search text-sm"></i>
                                        {{ Str::limit($search, 30, '...') }}
                                    </span>
                                </div>
                                <button type="button" wire:click="$set('search', '')" class="inline-flex items-center justify-center shrink-0 rounded-full text-slate-400 transition hover:text-slate-600 hover:bg-slate-100 p-1">
                                    <i class="bi bi-x-lg text-sm"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                <button type="button" wire:click="$toggle('filtersPanelOpen')" class="flex w-full items-center justify-between border-t border-slate-200 px-3 py-3 hover:bg-slate-100/40 transition">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-700">فیلترهای بیشتر</span>
                        @if($this->getActiveFilterCount() > 0)
                            <span class="inline-flex items-center justify-center min-w-5 rounded-full bg-violet-100 px-2 text-[11px] font-bold text-violet-700">{{ $this->getActiveFilterCount() }}</span>
                        @endif
                    </div>
                    <div class="flex items-center transition duration-300 {{ $filtersPanelOpen ? 'rotate-180' : '' }}">
                        <i class="bi bi-chevron-down text-slate-500"></i>
                    </div>
                </button>

                @if($filtersPanelOpen)
                    <div class="space-y-3 border-t border-slate-200 px-3 pb-3 max-h-[500px] overflow-y-auto animate-slideDown">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-600">وضعیت فعالیت:</label>
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $badgeClasses = [
                                        'draft' => 'bg-slate-100 text-slate-700 ring-slate-300',
                                        'ongoing' => 'bg-amber-100 text-amber-700 ring-amber-300',
                                        'closed' => 'bg-emerald-100 text-emerald-700 ring-emerald-300',
                                        'cancelled' => 'bg-rose-100 text-rose-700 ring-rose-300',
                                    ];
                                @endphp
                                <button type="button" wire:click="$set('statusFilter', 'all')" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $statusFilter === 'all' ? 'bg-slate-800 text-white ring-2 ring-slate-400' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                                    همه
                                    <span class="inline-flex items-center justify-center min-w-5 rounded-full bg-slate-200 px-1.5 text-[10px] font-bold text-slate-700">
                                        @php
                                            $totalCount = collect($statusCounts)->sum();
                                        @endphp
                                        {{ $totalCount }}
                                    </span>
                                </button>
                                @foreach($statusOptions as $value => $label)
                                    <button type="button" wire:click="$set('statusFilter', '{{ $value }}')" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $statusFilter === $value ? 'ring-2 ring-offset-2 ' . $badgeClasses[$value] : 'ring-1 ' . $badgeClasses[$value] }} {{ $statusFilter === $value ? $badgeClasses[$value] . ' ring-offset-slate-50 shadow-sm' : 'hover:shadow-sm' }}">
                                        {{ $label }}
                                        <span class="inline-flex items-center justify-center min-w-5 rounded-full {{ $statusFilter === $value ? 'bg-white/30' : 'bg-black/10' }} px-1.5 text-[10px] font-bold">
                                            {{ $statusCounts[$value] ?? 0 }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <select wire:model.live="typeFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                <option value="all">همه نوع‌ها</option>
                                @foreach($typeOptions as $value => $label)
                                    @php
                                        $typeIcon = $this->getActivityTypeInfo($value)['icon'];
                                    @endphp
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="relative">
                                <select wire:model="sortBy" wire:change="updateSort($event.target.value)" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2 pr-8 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                    @foreach($this->getSortOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ $sortBy === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    @if($sortDirection === 'asc')
                                        <i class="bi bi-arrow-up text-slate-500 text-sm"></i>
                                    @else
                                        <i class="bi bi-arrow-down text-slate-500 text-sm"></i>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <label class="text-[11px] font-semibold text-slate-600">بازه زمانی:</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" wire:click="applyDatePreset('today')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">امروز</button>
                                    <button type="button" wire:click="applyDatePreset('week')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">این هفته</button>
                                    <button type="button" wire:click="applyDatePreset('month')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">این ماه</button>
                                    <button type="button" wire:click="applyDatePreset('30days')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">30 روز</button>
                                    <button type="button" wire:click="applyDatePreset('all')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">همه</button>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">تاریخ شروع <span class="text-slate-400">(مثال: 1403/01/15)</span></label>
                                    <input type="text" wire:model.live.debounce.500ms="startsFrom" placeholder="1403/01/15" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100" data-jdp>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">تاریخ پایان <span class="text-slate-400">(مثال: 1403/01/20)</span></label>
                                    <input type="text" wire:model.live.debounce.500ms="startsUntil" placeholder="1403/01/20" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100" data-jdp>
                                </div>
                            </div>

                            @if($errors->has('startsFrom') || $errors->has('startsUntil'))
                                <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                                    @error('startsFrom'){{ $message }}@enderror
                                    @error('startsUntil'){{ $message }}@enderror
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            <button type="button" wire:click="resetFilters" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                <i class="bi bi-arrow-clockwise me-1.5 text-xs"></i>
                                ریست کردن
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if($this->hasAnyFiltersApplied())
                <div class="sticky top-0 z-20 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="shrink-0 text-sm font-semibold text-slate-700">
                                <i class="bi bi-funnel me-1.5"></i>
                                فیلترهای فعال:
                            </span>
                            <div class="flex flex-wrap gap-2 min-w-0">
                                @if($search)
                                    <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-xs font-medium text-violet-700 ring-1 ring-violet-200 shrink-0">
                                        <i class="bi bi-search text-sm"></i>
                                        <span class="truncate max-w-[120px]">{{ Str::limit($search, 15, '...') }}</span>
                                        <button type="button" wire:click="$set('search', '')" class="ml-1 shrink-0 text-violet-400 hover:text-violet-600 transition">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                @endif

                                @if($statusFilter !== 'all')
                                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200 shrink-0">
                                        <i class="bi bi-tag text-sm"></i>
                                        <span>{{ $statusOptions[$statusFilter] ?? $statusFilter }}</span>
                                        <button type="button" wire:click="$set('statusFilter', 'all')" class="ml-1 shrink-0 text-amber-400 hover:text-amber-600 transition">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                @endif

                                @if($typeFilter !== 'all')
                                    <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200 shrink-0">
                                        <i class="bi bi-folder text-sm"></i>
                                        <span>{{ $typeOptions[$typeFilter] ?? $typeFilter }}</span>
                                        <button type="button" wire:click="$set('typeFilter', 'all')" class="ml-1 shrink-0 text-blue-400 hover:text-blue-600 transition">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                @endif

                                @if($startsFrom)
                                    <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200 shrink-0">
                                        <i class="bi bi-calendar-range text-sm"></i>
                                        <span>از: {{ $startsFrom }}</span>
                                        <button type="button" wire:click="$set('startsFrom', null)" class="ml-1 shrink-0 text-rose-400 hover:text-rose-600 transition">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                @endif

                                @if($startsUntil)
                                    <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200 shrink-0">
                                        <i class="bi bi-calendar-range text-sm"></i>
                                        <span>تا: {{ $startsUntil }}</span>
                                        <button type="button" wire:click="$set('startsUntil', null)" class="ml-1 shrink-0 text-rose-400 hover:text-rose-600 transition">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="resetFilters" class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
                            <i class="bi bi-x-circle text-sm"></i>
                            پاک کردن همه
                        </button>
                    </div>
                </div>
            @endif

            <div class="space-y-3">
                    @php
                        $filterLoadingTarget = 'search, statusFilter, typeFilter, startsFrom, startsUntil, sortBy, updateSort, applyDatePreset, resetFilters, clearDateFilters, previousPage, nextPage, gotoPage';
                    @endphp

                    <!-- Item 10: Loading skeleton with progress feedback -->
                    <div wire:loading.delay.flex wire:target="{{ $filterLoadingTarget }}" class="hidden flex-col gap-3">
                        <div class="flex items-center gap-2.5 rounded-xl border border-violet-100 bg-violet-50/70 px-4 py-3">
                            <span class="relative flex h-2.5 w-2.5 shrink-0">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-violet-500"></span>
                            </span>
                            <span class="text-sm font-semibold text-violet-700">در حال جستجو در {{ number_format($this->totalActivitiesCount()) }} فعالیت…</span>
                        </div>

                        @for($i = 0; $i < 4; $i++)
                            <div class="animate-pulse rounded-2xl border border-slate-200 bg-white p-3 sm:p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-5 w-20 rounded-md bg-slate-200"></div>
                                            <div class="h-4 w-32 rounded bg-slate-200"></div>
                                        </div>
                                        <div class="h-4 w-3/4 rounded bg-slate-100"></div>
                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                            <div class="h-3 rounded bg-slate-100"></div>
                                            <div class="h-3 rounded bg-slate-100"></div>
                                            <div class="h-3 rounded bg-slate-100"></div>
                                        </div>
                                        <div class="h-2 w-full rounded-full bg-slate-100"></div>
                                    </div>
                                    <div class="h-8 w-8 shrink-0 rounded-lg bg-slate-100"></div>
                                </div>
                            </div>
                        @endfor
                    </div>

                    <!-- Results list (hidden while filter/search/pagination loads) -->
                    <div wire:loading.remove wire:target="{{ $filterLoadingTarget }}" class="space-y-3">
                    @forelse($activities as $activity)
                        @php
                            $creator = $activity->creator;
                            $creatorName = $creator?->full_name ?: $creator?->name ?: 'نامشخص';
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 transition hover:border-violet-200 hover:bg-slate-50/40">
                            <div class="flex flex-col gap-3 sm:gap-4 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0 flex-1">
                                    <!-- Header: Code + Name + Status -->
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2">
                                                <span class="inline-flex w-fit shrink-0 items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $activity->code }}</span>
                                                <h2 class="min-w-0 flex-1 text-base font-bold text-slate-900">{{ $activity->name }}</h2>
                                            </div>
                                        </div>
                                        <span class="w-fit shrink-0 inline-flex items-center justify-center rounded-full px-2.5 py-1 text-[10px] font-bold leading-5 ring-1 ring-inset ring-black/5 sm:px-3 sm:py-1 sm:text-[11px] {{ $badgeClasses[$activity->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusOptions[$activity->status] ?? $activity->status }}</span>
                                    </div>

                                    <!-- Compact meta row: date · location · type -->
                                    @php
                                        $typeInfo = $this->getActivityTypeInfo($activity->activity_type);
                                        $capacityInfo = $this->getCapacityInfo($activity);
                                    @endphp
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-slate-600">
                                        <span class="inline-flex items-center gap-1 font-semibold text-slate-800">
                                            <i class="bi bi-calendar-event text-slate-400"></i>
                                            <span class="truncate">{{ $this->formatJalaliDateTime($activity->starts_at) }}</span>
                                        </span>
                                        <span class="text-slate-300">·</span>
                                        <span class="inline-flex min-w-0 items-center gap-1">
                                            <i class="bi bi-geo-alt text-slate-400"></i>
                                            <span class="truncate max-w-[10rem]">{{ $activity->location ?: '—' }}</span>
                                        </span>
                                        <span class="text-slate-300">·</span>
                                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium {{ $typeInfo['color'] }}">
                                            <i class="bi {{ $typeInfo['icon'] }}"></i>
                                            {{ $typeInfo['label'] }}
                                        </span>
                                    </div>

                                    <!-- Compact capacity: single representation -->
                                    <div class="mt-2 flex items-center gap-2">
                                        <i class="bi bi-people shrink-0 text-xs text-slate-400"></i>
                                        @if($capacityInfo['isUnlimited'])
                                            <span class="text-xs font-semibold text-slate-600">{{ $activity->attendances_count }} نفر · نامحدود</span>
                                        @else
                                            <span class="text-xs font-bold {{ $capacityInfo['class'] }}">{{ $activity->attendances_count }}/{{ $activity->capacity }}</span>
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full {{ $capacityInfo['barClass'] }}" style="width: {{ min(100, $capacityInfo['percentage']) }}%"></div>
                                            </div>
                                            <span class="text-[11px] font-semibold {{ $capacityInfo['class'] }}">{{ $capacityInfo['percentage'] }}%</span>
                                        @endif
                                    </div>

                                    <!-- Matched Search Fields -->
                                    @php
                                        $matchedFields = $this->getMatchedSearchFields($activity);
                                    @endphp
                                    @if($matchedFields)
                                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                            <span class="text-[10px] font-medium text-slate-500">موارد مطابق:</span>
                                            @foreach($matchedFields as $field)
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-700">
                                                    <i class="bi bi-check-circle text-xs me-1"></i>
                                                    {{ $field }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-2 md:mt-0 md:pt-1">
                                    @if($activity->status === 'ongoing')
                                        <button type="button" wire:click="openScanner({{ $activity->id }})" class="inline-flex items-center justify-center gap-1.5 rounded-full bg-cyan-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-cyan-700">
                                            <i class="bi bi-qr-code text-sm"></i>
                                            <span>ثبت ورود / خروج</span>
                                        </button>
                                    @else
                                        <button type="button" wire:click="selectActivity({{ $activity->id }})" class="inline-flex items-center justify-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-xs font-bold text-violet-700 transition hover:bg-violet-100">
                                            <i class="bi bi-info-circle text-sm"></i>
                                            <span>جزئیات</span>
                                        </button>
                                    @endif

                                    <!-- Desktop/tablet: hover dropdown menu -->
                                    <div class="relative hidden md:block" x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                            <i class="bi bi-list text-lg font-bold"></i>
                                        </button>

                                        <div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 z-10 mt-2 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg" style="display: none;">
                                            @if($activity->status === 'ongoing')
                                                <button type="button" wire:click="selectActivity({{ $activity->id }})" class="w-full px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                                    <i class="bi bi-info-circle me-2 text-slate-500"></i>
                                                    جزئیات و مدیریت
                                                </button>
                                                <div class="border-t border-slate-100"></div>
                                            @endif

                                            @can('full-access')
                                                <button type="button" wire:click="editActivity({{ $activity->id }})" class="w-full px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                                    <i class="bi bi-pencil me-2 text-slate-500"></i>
                                                    ویرایش فعالیت
                                                </button>
                                                <button type="button" wire:click="openOperatorAssignment({{ $activity->id }})" class="w-full px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                                    <i class="bi bi-person-badge me-2 text-slate-500"></i>
                                                    تخصیص اپراتور
                                                </button>
                                            @endcan
                                        </div>
                                    </div>

                                    <!-- Mobile: bottom sheet (matches the beneficiary list action sheet pattern) -->
                                    <div
                                        class="relative md:hidden"
                                        x-data="{
                                            open: false,
                                            historyPushed: false,
                                            openSheet() {
                                                if (this.open) {
                                                    return;
                                                }

                                                this.open = true;
                                                window.history.pushState({ activityActionSheet: {{ $activity->id }} }, '', window.location.href);
                                                this.historyPushed = true;
                                            },
                                            closeSheet(fromPopState = false) {
                                                if (! this.open) {
                                                    return;
                                                }

                                                this.open = false;

                                                if (this.historyPushed) {
                                                    this.historyPushed = false;

                                                    if (! fromPopState) {
                                                        window.history.back();
                                                    }
                                                }
                                            },
                                        }"
                                        @click.stop
                                        @keydown.escape.window="closeSheet()"
                                        @popstate.window="closeSheet(true)"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                                            aria-label="اقدامات بیشتر"
                                            aria-haspopup="dialog"
                                            :aria-expanded="open.toString()"
                                            @click="open ? closeSheet() : openSheet()"
                                        >
                                            <i class="bi bi-list text-lg font-bold"></i>
                                        </button>

                                        <div
                                            x-show="open"
                                            x-transition.opacity
                                            class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-[1px]"
                                            style="display: none;"
                                            @click="closeSheet()"
                                            aria-hidden="true"
                                        ></div>

                                        <div
                                            x-show="open"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="translate-y-6 opacity-0"
                                            x-transition:enter-end="translate-y-0 opacity-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="translate-y-0 opacity-100"
                                            x-transition:leave-end="translate-y-6 opacity-0"
                                            class="fixed inset-x-0 bottom-0 z-50 rounded-t-3xl border border-slate-200 bg-white px-4 pb-5 pt-3 shadow-2xl"
                                            style="display: none;"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-label="اقدامات فعالیت"
                                            @click.stop
                                        >
                                            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-200"></div>
                                            <div class="mb-3 border-b border-slate-100 pb-3 text-right">
                                                <p class="truncate text-sm font-extrabold text-slate-900">{{ $activity->name }}</p>
                                                <p class="mt-1 text-[11px] font-semibold text-slate-500" dir="ltr">{{ $activity->code }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                @if($activity->status === 'ongoing')
                                                    <button
                                                        type="button"
                                                        wire:click="selectActivity({{ $activity->id }})"
                                                        class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-3 text-right text-sm font-bold text-violet-700 transition hover:bg-violet-100 focus:outline-none focus:ring-2 focus:ring-violet-100"
                                                        @click="closeSheet()"
                                                    >
                                                        <i class="bi bi-info-circle text-base"></i>
                                                        جزئیات و مدیریت
                                                    </button>
                                                @endif

                                                @can('full-access')
                                                    <button
                                                        type="button"
                                                        wire:click="editActivity({{ $activity->id }})"
                                                        class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-amber-100 bg-amber-50/70 px-4 py-3 text-right text-sm font-bold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-100"
                                                        @click="closeSheet()"
                                                    >
                                                        <i class="bi bi-pencil text-base"></i>
                                                        ویرایش فعالیت
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="openOperatorAssignment({{ $activity->id }})"
                                                        class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-cyan-100 bg-cyan-50/70 px-4 py-3 text-right text-sm font-bold text-cyan-800 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                                        @click="closeSheet()"
                                                    >
                                                        <i class="bi bi-person-badge text-base"></i>
                                                        تخصیص اپراتور
                                                    </button>
                                                @endcan
                                            </div>

                                            <button
                                                type="button"
                                                class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                @click="closeSheet()"
                                            >
                                                بستن
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        @if($this->totalActivitiesCount() === 0)
                            <!-- No Activities At All -->
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                                <div class="mx-auto w-fit">
                                    <div class="rounded-full bg-slate-100 p-4 w-fit mx-auto">
                                        <i class="bi bi-inbox text-2xl text-slate-400"></i>
                                    </div>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-slate-900">هنوز فعالیتی ایجاد نشده</h3>
                                <p class="mt-2 text-sm text-slate-600 max-w-sm mx-auto">شروع کنید با ایجاد اولین فعالیت خود تا بتوانید آن را مدیریت کنید.</p>
                                @can('full-access')
                                    <button type="button" wire:click="createActivity" class="mt-4 inline-flex items-center gap-2 rounded-full bg-violet-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700">
                                        <i class="bi bi-plus-lg text-sm"></i>
                                        ایجاد فعالیت جدید
                                    </button>
                                @endcan
                            </div>
                        @elseif($this->hasAnyFiltersApplied())
                            <!-- No Results Due to Filters -->
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8">
                                <div class="text-center">
                                    <div class="mx-auto w-fit">
                                        <div class="rounded-full bg-slate-100 p-4 w-fit mx-auto">
                                            <i class="bi bi-funnel text-2xl text-slate-400"></i>
                                        </div>
                                    </div>
                                    <h3 class="mt-4 text-base font-semibold text-slate-900">نتیجه‌ای یافت نشد</h3>
                                    <p class="mt-2 text-sm text-slate-600 max-w-sm mx-auto">فیلترهای اعمال شده شامل هیچ فعالیتی نیستند. سعی کنید فیلترها را تعدیل کنید.</p>
                                </div>

                                <div class="mt-6 space-y-3">
                                    <p class="text-sm font-semibold text-slate-700">فیلترهای فعال:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @if($search)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                                جستجو: {{ Str::limit($search, 20, '...') }}
                                                <button type="button" wire:click="$set('search', '')" class="text-slate-400 hover:text-slate-600">
                                                    <i class="bi bi-x-lg text-sm"></i>
                                                </button>
                                            </span>
                                        @endif
                                        @if($statusFilter !== 'all')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                                وضعیت: {{ $statusOptions[$statusFilter] ?? $statusFilter }}
                                                <button type="button" wire:click="$set('statusFilter', 'all')" class="text-slate-400 hover:text-slate-600">
                                                    <i class="bi bi-x-lg text-sm"></i>
                                                </button>
                                            </span>
                                        @endif
                                        @if($typeFilter !== 'all')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                                نوع: {{ $typeOptions[$typeFilter] ?? $typeFilter }}
                                                <button type="button" wire:click="$set('typeFilter', 'all')" class="text-slate-400 hover:text-slate-600">
                                                    <i class="bi bi-x-lg text-sm"></i>
                                                </button>
                                            </span>
                                        @endif
                                        @if($startsFrom)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                                از: {{ $startsFrom }}
                                                <button type="button" wire:click="$set('startsFrom', null)" class="text-slate-400 hover:text-slate-600">
                                                    <i class="bi bi-x-lg text-sm"></i>
                                                </button>
                                            </span>
                                        @endif
                                        @if($startsUntil)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                                تا: {{ $startsUntil }}
                                                <button type="button" wire:click="$set('startsUntil', null)" class="text-slate-400 hover:text-slate-600">
                                                    <i class="bi bi-x-lg text-sm"></i>
                                                </button>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Item 10: Smart contextual suggestions -->
                                <div class="mt-6 rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                                    <div class="flex items-center gap-2">
                                        <i class="bi bi-lightbulb text-amber-500"></i>
                                        <p class="text-sm font-semibold text-slate-700">این موارد را امتحان کنید:</p>
                                    </div>
                                    <div class="mt-3 flex flex-col gap-2">
                                        @if($startsFrom || $startsUntil)
                                            <button type="button" wire:click="clearDateFilters" class="group inline-flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2.5 text-right ring-1 ring-slate-200 transition hover:ring-amber-300 hover:bg-amber-50">
                                                <span class="flex items-center gap-2 text-xs font-medium text-slate-700">
                                                    <i class="bi bi-calendar-x text-slate-400 group-hover:text-amber-500"></i>
                                                    بازه زمانی را حذف کنید و دوباره جستجو کنید
                                                </span>
                                                <i class="bi bi-arrow-left text-slate-300 group-hover:text-amber-500"></i>
                                            </button>
                                        @endif
                                        @if($statusFilter !== 'all')
                                            <button type="button" wire:click="$set('statusFilter', 'all')" class="group inline-flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2.5 text-right ring-1 ring-slate-200 transition hover:ring-amber-300 hover:bg-amber-50">
                                                <span class="flex items-center gap-2 text-xs font-medium text-slate-700">
                                                    <i class="bi bi-funnel text-slate-400 group-hover:text-amber-500"></i>
                                                    فیلتر وضعیت «{{ $statusOptions[$statusFilter] ?? $statusFilter }}» را بردارید
                                                </span>
                                                <i class="bi bi-arrow-left text-slate-300 group-hover:text-amber-500"></i>
                                            </button>
                                        @endif
                                        @if($typeFilter !== 'all')
                                            <button type="button" wire:click="$set('typeFilter', 'all')" class="group inline-flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2.5 text-right ring-1 ring-slate-200 transition hover:ring-amber-300 hover:bg-amber-50">
                                                <span class="flex items-center gap-2 text-xs font-medium text-slate-700">
                                                    <i class="bi bi-tag text-slate-400 group-hover:text-amber-500"></i>
                                                    فیلتر نوع «{{ $typeOptions[$typeFilter] ?? $typeFilter }}» را بردارید
                                                </span>
                                                <i class="bi bi-arrow-left text-slate-300 group-hover:text-amber-500"></i>
                                            </button>
                                        @endif
                                        @if($search)
                                            <button type="button" wire:click="$set('search', '')" class="group inline-flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2.5 text-right ring-1 ring-slate-200 transition hover:ring-amber-300 hover:bg-amber-50">
                                                <span class="flex items-center gap-2 text-xs font-medium text-slate-700">
                                                    <i class="bi bi-search text-slate-400 group-hover:text-amber-500"></i>
                                                    عبارت جستجو «{{ Str::limit($search, 20, '...') }}» را پاک کنید
                                                </span>
                                                <i class="bi bi-arrow-left text-slate-300 group-hover:text-amber-500"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-wrap gap-2">
                                    <button type="button" wire:click="resetFilters" class="inline-flex items-center gap-1.5 rounded-full bg-slate-800 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-900">
                                        <i class="bi bi-arrow-counterclockwise text-sm"></i>
                                        ریست تمام فیلترها
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- Default No Results -->
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                                <div class="mx-auto w-fit">
                                    <div class="rounded-full bg-slate-100 p-4 w-fit mx-auto">
                                        <i class="bi bi-inbox text-2xl text-slate-400"></i>
                                    </div>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-slate-900">فعالیتی یافت نشد</h3>
                                <p class="mt-2 text-sm text-slate-600">هیچ فعالیتی در سیستم وجود ندارد.</p>
                            </div>
                        @endif
                    @endforelse

                    @if($activities->hasPages())
                        <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3">
                            {{ $activities->links('vendor.livewire.tailwind-mobile-persian') }}
                        </div>
                    @endif
                    </div>
                </div>

            <!-- Detail drawer: full-screen on mobile, side panel on desktop -->
            @if($selectedActivity)
                @php
                    $drawerType = $this->getActivityTypeInfo($selectedActivity->activity_type);
                    $drawerCapacity = $this->getCapacityInfo($selectedActivity);
                    $drawerCreator = $selectedActivity->creator;
                    $drawerCreatorName = $drawerCreator?->full_name ?: $drawerCreator?->name ?: 'نامشخص';
                @endphp
                <div
                    wire:key="activity-drawer-{{ $selectedActivity->id }}"
                    x-data="{ open: false, close() { this.open = false; setTimeout(() => $wire.clearSelectedActivity(), 200); } }"
                    x-init="$nextTick(() => open = true)"
                    @keydown.escape.window="close()"
                >
                    <!-- Backdrop -->
                    <div
                        x-show="open"
                        x-transition.opacity
                        class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-[1px]"
                        style="display: none;"
                        @click="close()"
                        aria-hidden="true"
                    ></div>

                    <!-- Panel -->
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="-translate-x-full opacity-0"
                        x-transition:enter-end="translate-x-0 opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="-translate-x-full opacity-0"
                        class="fixed inset-y-0 left-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl"
                        style="display: none;"
                        role="dialog"
                        aria-modal="true"
                        aria-label="جزئیات فعالیت"
                    >
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold text-slate-400">جزئیات و مدیریت فعالیت</p>
                                <h2 class="mt-0.5 truncate text-base font-bold text-slate-900">{{ $selectedActivity->name }}</h2>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="text-[11px] font-semibold text-slate-500" dir="ltr">{{ $selectedActivity->code }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badgeClasses[$selectedActivity->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusOptions[$selectedActivity->status] ?? $selectedActivity->status }}</span>
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-medium {{ $drawerType['color'] }}">
                                        <i class="bi {{ $drawerType['icon'] }}"></i>
                                        {{ $drawerType['label'] }}
                                    </span>
                                </div>
                            </div>
                            <button type="button" @click="close()" class="shrink-0 rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="بستن">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <!-- One action bar: all management actions -->
                        <div class="flex flex-wrap gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                            @if($selectedActivity->status === 'ongoing')
                                <button type="button" wire:click="openScanner({{ $selectedActivity->id }})" class="inline-flex items-center gap-1.5 rounded-full bg-cyan-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-cyan-700">
                                    <i class="bi bi-qr-code text-sm"></i>
                                    ثبت ورود / خروج
                                </button>
                            @endif
                            <button type="button" wire:click="exportAttendances" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                <i class="bi bi-file-earmark-spreadsheet text-sm"></i>
                                خروجی اکسل
                            </button>
                            @can('full-access')
                                <button type="button" wire:click="openOperatorAssignment({{ $selectedActivity->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">
                                    <i class="bi bi-person-badge text-sm"></i>
                                    تخصیص اپراتور
                                </button>
                                <button type="button" wire:click="editActivity({{ $selectedActivity->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-800 transition hover:bg-amber-100">
                                    <i class="bi bi-pencil text-sm"></i>
                                    ویرایش
                                </button>
                            @endcan
                        </div>

                        <!-- Tabs -->
                        @can('full-access')
                            @php $canManage = true; @endphp
                        @else
                            @php $canManage = false; @endphp
                        @endcan
                        <div x-data="{ tab: 'info' }" class="flex min-h-0 flex-1 flex-col">
                            <div class="flex gap-1 border-b border-slate-200 px-2">
                                <button type="button" @click="tab = 'info'" :class="tab === 'info' ? 'border-violet-500 text-violet-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-3 py-2.5 text-xs font-bold transition">
                                    اطلاعات
                                </button>
                                <button type="button" @click="tab = 'attendance'" :class="tab === 'attendance' ? 'border-violet-500 text-violet-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-3 py-2.5 text-xs font-bold transition">
                                    حضور
                                    <span class="ms-1 inline-flex items-center justify-center h-4 rounded-full bg-slate-200 px-1.5 text-[10px] font-bold text-slate-700">{{ count($filteredAttendances) }}</span>
                                </button>
                                @if($canManage)
                                    <button type="button" @click="tab = 'status'" :class="tab === 'status' ? 'border-violet-500 text-violet-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-3 py-2.5 text-xs font-bold transition">
                                        وضعیت
                                    </button>
                                @endif
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                                <!-- Info tab -->
                                <div x-show="tab === 'info'" class="space-y-4">
                                    <!-- Key stats -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                            <p class="text-[10px] font-semibold text-slate-500">ظرفیت</p>
                                            @if($drawerCapacity['isUnlimited'])
                                                <p class="mt-1.5 text-sm font-bold text-slate-900">نامحدود</p>
                                            @else
                                                <p class="mt-1.5 text-sm font-bold {{ $drawerCapacity['class'] }}">{{ $selectedActivity->attendances_count }}/{{ $selectedActivity->capacity }} · {{ $drawerCapacity['percentage'] }}%</p>
                                            @endif
                                        </div>
                                        <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                            <p class="text-[10px] font-semibold text-slate-500">کل حضور</p>
                                            <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $selectedActivity->attendances_count }} نفر</p>
                                        </div>
                                    </div>

                                    <!-- Attendance breakdown -->
                                    <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                        <p class="mb-2.5 text-[10px] font-semibold text-slate-500">آمار حضور</p>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700">{{ $selectedActivity->present_attendances_count }}</span>
                                                <span class="text-xs text-slate-600">حاضر</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-[11px] font-bold text-rose-700">{{ $selectedActivity->absent_attendances_count }}</span>
                                                <span class="text-xs text-slate-600">غایب</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-[11px] font-bold text-amber-700">{{ $selectedActivity->late_attendances_count ?? 0 }}</span>
                                                <span class="text-xs text-slate-600">دیر رسید</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Entry/exit breakdown -->
                                    <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                        <p class="mb-2.5 text-[10px] font-semibold text-slate-500">آمار ورود و خروج</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2 py-1.5 ring-1 ring-emerald-100">
                                                <i class="bi bi-box-arrow-in-left text-sm text-emerald-600"></i>
                                                <span class="text-[11px] text-slate-600">ورود</span>
                                                <span class="ms-auto text-xs font-bold text-emerald-700">{{ $selectedActivity->checked_in_attendances_count ?? 0 }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2 py-1.5 ring-1 ring-indigo-100">
                                                <i class="bi bi-box-arrow-left text-sm text-indigo-600"></i>
                                                <span class="text-[11px] text-slate-600">خروج</span>
                                                <span class="ms-auto text-xs font-bold text-indigo-700">{{ $selectedActivity->checked_out_attendances_count ?? 0 }}</span>
                                            </div>
                                            <div class="col-span-2 flex items-center gap-1.5 rounded-lg bg-sky-50 px-2 py-1.5 ring-1 ring-sky-100">
                                                <i class="bi bi-person-check text-sm text-sky-600"></i>
                                                <span class="text-[11px] text-slate-600">حاضر در فعالیت (بدون ثبت خروج)</span>
                                                <span class="ms-auto text-xs font-bold text-sky-700">{{ $selectedActivity->still_present_attendances_count ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Event details -->
                                    <div class="space-y-2 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-600 ring-1 ring-slate-200">
                                        <p><strong class="text-slate-700">شروع:</strong> {{ $this->formatJalaliDateTime($selectedActivity->starts_at) }}</p>
                                        <p><strong class="text-slate-700">پایان:</strong> {{ $this->formatJalaliDateTime($selectedActivity->ends_at) }}</p>
                                        <p><strong class="text-slate-700">مکان:</strong> {{ $selectedActivity->location ?: '—' }}</p>
                                        <p><strong class="text-slate-700">ثبت‌کننده:</strong> {{ $drawerCreatorName }}</p>
                                        <p><strong class="text-slate-700">توضیحات:</strong> {{ $selectedActivity->description ?: 'ثبت نشده' }}</p>
                                        <p><strong class="text-slate-700">یادداشت وضعیت:</strong> {{ $selectedActivity->status_notes ?: 'ثبت نشده' }}</p>
                                    </div>
                                </div>

                                <!-- Attendance tab -->
                                <div x-show="tab === 'attendance'" x-cloak class="space-y-2">
                                    <p class="text-[11px] text-slate-500">نمایش حداکثر {{ $attendanceDisplayLimit }} حضور آخر؛ برای فهرست کامل از خروجی اکسل استفاده کنید.</p>
                                    <input type="text" wire:model.live.debounce.300ms="attendanceSearch" placeholder="جستجو مددجو یا ثبت‌کننده" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select wire:model.live="attendanceMethodFilter" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                            <option value="all">همه روش‌ها</option>
                                            @foreach($attendanceMethodOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="attendanceStatusFilter" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                            <option value="all">همه وضعیت‌ها</option>
                                            @foreach($attendanceStatusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="attendancePresenceFilter" class="col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" aria-label="فیلتر ورود و خروج">
                                            <option value="all">ورود و خروج: همه</option>
                                            @foreach($attendancePresenceOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse($filteredAttendances as $attendance)
                                            <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 transition hover:bg-slate-100">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="font-bold text-slate-800">{{ $attendance->person?->full_name ?: '—' }}</p>
                                                    @if($attendance->checked_out_at)
                                                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">
                                                            <i class="bi bi-box-arrow-left"></i>
                                                            خارج شده
                                                        </span>
                                                    @elseif($attendance->checked_in_at)
                                                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700">
                                                            <i class="bi bi-person-check"></i>
                                                            حاضر
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-[11px]">{{ $attendance->person?->person_code ?: '—' }} · {{ $attendance->person?->national_id ?: '—' }}</p>
                                                <p class="mt-1 text-[11px]">{{ $attendanceMethodOptions[$attendance->registration_method] ?? $attendance->registration_method }} · {{ $attendanceStatusOptions[$attendance->status] ?? $attendance->status }}</p>
                                                <p class="text-[11px] text-slate-500">
                                                    <span class="font-semibold text-emerald-600">ورود:</span>
                                                    {{ $this->formatJalaliDateTime($attendance->checked_in_at) }}
                                                </p>
                                                <p class="text-[11px] text-slate-500">
                                                    <span class="font-semibold text-indigo-600">خروج:</span>
                                                    {{ $attendance->checked_out_at ? $this->formatJalaliDateTime($attendance->checked_out_at) : 'ثبت نشده' }}
                                                    @if($attendance->check_out_method)
                                                        · {{ $attendanceMethodOptions[$attendance->check_out_method] ?? $attendance->check_out_method }}
                                                    @endif
                                                </p>
                                            </div>
                                        @empty
                                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400">هنوز حضوری ثبت نشده</p>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Status tab (full-access only) -->
                                @if($canManage)
                                    <div x-show="tab === 'status'" x-cloak class="space-y-3">
                                        <textarea wire:model="transitionNotes" rows="2" placeholder="یادداشت تغییر وضعیت (اختیاری)" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs focus:border-violet-300 focus:ring-2 focus:ring-violet-100"></textarea>
                                        <div class="flex flex-col gap-2">
                                            @forelse($this->allowedTransitionTargets($selectedActivity) as $targetStatus)
                                                <button type="button" wire:click="transitionActivity({{ $selectedActivity->id }}, '{{ $targetStatus }}')" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-900">
                                                    تغییر به {{ $statusOptions[$targetStatus] ?? $targetStatus }}
                                                </button>
                                            @empty
                                                <p class="py-2 text-center text-xs font-semibold text-slate-400">تغییر وضعیت دیگری مجاز نیست.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @can('full-access')
        @if($assigningOperatorActivityId)
            <div
                x-data
                x-on:close-activity-operator-assignment-modal.window="$wire.closeOperatorAssignment()"
                x-on:keydown.escape.window="$wire.closeOperatorAssignment()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div
                    x-on:click.outside="$wire.closeOperatorAssignment()"
                    class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl"
                >
                    <livewire:admin.activity-operator-assignment
                        :activity-id="$assigningOperatorActivityId"
                        :key="'activity-operator-assignment-modal-' . $assigningOperatorActivityId"
                    />
                </div>
            </div>
        @endif
    @endcan

    <style>
        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                overflow: hidden;
            }
            to {
                opacity: 1;
                max-height: 500px;
                overflow: visible;
            }
        }

        .animate-slideDown {
            animation: slideDown 0.3s ease-in-out forwards;
        }
    </style>
</div>

<script>
document.addEventListener('livewire:navigated', initializeDatePickers);
document.addEventListener('DOMContentLoaded', initializeDatePickers);

function initializeDatePickers() {
    const datepickers = document.querySelectorAll('[data-jdp]');
    datepickers.forEach(element => {
        if (!element._jdpInitialized) {
            // Prevent manual time input by blocking non-date characters
            element.addEventListener('input', function(e) {
                // Allow only digits, forward slashes (for date format)
                this.value = this.value.replace(/[^\d/]/g, '');
                // Limit to YYYY/MM/DD format
                if (this.value.length > 10) {
                    this.value = this.value.substring(0, 10);
                }
            });

            new jalaliDatepicker.JalaliDatePicker(element, {
                minDate: new Date(1900, 0, 1),
                maxDate: new Date(),
                closeAfterSelect: true,
                format: 'yyyy/MM/dd',
                altFieldFormat: 'yyyy/MM/dd',
                buttons: false,
                showCloseButton: true,
                timePicker: false,
                timePickerSeconds: false,
            });
            element._jdpInitialized = true;
        }
    });
}
</script>
