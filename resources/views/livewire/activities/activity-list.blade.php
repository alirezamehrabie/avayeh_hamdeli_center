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

            <div class="space-y-3 rounded-2xl border border-slate-100 bg-slate-50/40 p-3">
                <div class="relative" x-data="{ helpOpen: false, isSearching: false }" @wire:updated.debounce.100ms="isSearching = false">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در کد، نام، مکان یا نام ثبت‌کننده..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100" @input="isSearching = true">

                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <i class="bi bi-search text-sm"></i>
                    </span>

                    <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <div class="relative">
                            <button type="button" @click="helpOpen = !helpOpen" class="inline-flex items-center justify-center rounded-full text-slate-400 transition hover:text-slate-600 focus:outline-none">
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
                    </div>

                    @if($search)
                        <div class="absolute inset-y-0 left-8 flex items-center">
                            <div x-show="isSearching" x-transition class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                <div class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-pulse"></div>
                                جستجو...
                            </div>
                        </div>
                    @endif
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

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-3">
                    @forelse($activities as $activity)
                        @php
                            $creator = $activity->creator;
                            $creatorName = $creator?->full_name ?: $creator?->name ?: 'نامشخص';
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-violet-200 hover:bg-slate-50/40">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <!-- Header: Code + Name + Status -->
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex shrink-0 items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $activity->code }}</span>
                                                <h2 class="min-w-0 flex-1 text-base font-bold text-slate-900">{{ $activity->name }}</h2>
                                            </div>
                                        </div>
                                        <span class="inline-flex max-w-24 shrink-0 items-center justify-center rounded-full px-2 py-0.5 text-[10px] font-bold leading-5 ring-1 ring-inset ring-black/5 sm:max-w-none sm:px-3 sm:py-1 sm:text-[11px] {{ $badgeClasses[$activity->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusOptions[$activity->status] ?? $activity->status }}</span>
                                    </div>

                                    <!-- Main Context: Date + Location + Attendance -->
                                    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                        <div class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                            <i class="bi bi-calendar-event text-slate-500"></i>
                                            {{ $this->formatJalaliDateTime($activity->starts_at) }}
                                        </div>
                                        <div class="hidden text-slate-300 sm:block">|</div>
                                        <div class="flex items-center gap-1.5 text-sm text-slate-600">
                                            <i class="bi bi-geo-alt text-slate-400"></i>
                                            {{ $activity->location ?: '—' }}
                                        </div>
                                        <div class="hidden text-slate-300 sm:block">|</div>
                                        <div class="flex items-center gap-1.5 text-sm text-slate-600">
                                            <i class="bi bi-people text-slate-400"></i>
                                            <span>{{ $activity->attendances_count }} نفر</span>
                                        </div>
                                    </div>

                                    <!-- Capacity Indicator -->
                                    @php
                                        $capacityInfo = $this->getCapacityInfo($activity);
                                        $capacityPercentage = $capacityInfo['percentage'];
                                        $capacityClass = $capacityInfo['class'];
                                        $capacityText = $capacityInfo['text'];
                                        $isUnlimited = $capacityInfo['isUnlimited'];
                                    @endphp

                                    @if(!$isUnlimited)
                                        <div class="mt-3 w-full space-y-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] font-medium text-slate-600">ظرفیت:</span>
                                                <span class="text-[11px] font-bold {{ $capacityClass }}">{{ $capacityText }}</span>
                                            </div>
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full transition-all duration-300 {{ $capacityInfo['barClass'] }}" style="width: {{ min(100, $capacityPercentage) }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-3">
                                            <span class="text-[11px] font-medium text-slate-500">ظرفیت: نامحدود</span>
                                        </div>
                                    @endif

                                    <!-- Secondary Info: Type + End Time + Creator -->
                                    <div class="mt-3 border-t border-slate-100 pt-3">
                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-semibold text-slate-500">نوع</p>
                                                <p class="mt-1 truncate rounded-lg bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700">{{ $typeOptions[$activity->activity_type] ?? $activity->activity_type }}</p>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-semibold text-slate-500">پایان</p>
                                                <p class="mt-1 truncate text-xs text-slate-600">{{ $this->formatJalaliDateTime($activity->ends_at) }}</p>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-semibold text-slate-500">ثبت‌کننده</p>
                                                <p class="mt-1 truncate text-xs text-slate-600">{{ $creatorName }}</p>
                                            </div>
                                        </div>
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
                                <div class="flex shrink-0 items-center justify-end gap-2 lg:pt-1">
                                    @if($activity->status === 'ongoing')
                                        <button type="button" wire:click="openScanner({{ $activity->id }})" class="inline-flex items-center gap-1.5 rounded-full bg-cyan-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-cyan-700">
                                            <i class="bi bi-qr-code text-sm"></i>
                                            ثبت حضور
                                        </button>
                                    @else
                                        <button type="button" wire:click="selectActivity({{ $activity->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-xs font-bold text-violet-700 transition hover:bg-violet-100">
                                            <i class="bi bi-info-circle text-sm"></i>
                                            جزئیات
                                        </button>
                                    @endif

                                    <div class="relative" x-data="{ open: false }">
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

                <aside class="{{ $selectedActivity ? 'order-first xl:order-none' : 'hidden xl:block' }} rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                    @if($selectedActivity)
                        <div class="space-y-4">
                            <!-- Header -->
                            <div class="border-b border-slate-200 pb-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-400">جزئیات فعالیت</p>
                                        <h2 class="mt-1 truncate text-lg font-bold text-slate-900">{{ $selectedActivity->name }}</h2>
                                    </div>
                                    <button type="button" wire:click="clearSelectedActivity" class="shrink-0 text-xs font-semibold text-slate-400 hover:text-slate-600">بستن</button>
                                </div>
                            </div>

                            <!-- Key Stats Cards -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-500">وضعیت</p>
                                    <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $statusOptions[$selectedActivity->status] ?? $selectedActivity->status }}</p>
                                </div>
                                <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                    <p class="text-[10px] font-semibold text-slate-500">ظرفیت</p>
                                    <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $selectedActivity->capacity ?: 'نامحدود' }}</p>
                                </div>
                            </div>

                            <!-- Attendance Stats -->
                            <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                                <p class="text-[10px] font-semibold text-slate-500 mb-2.5">آمار حضور</p>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700">{{ $selectedActivity->present_attendances_count }}</span>
                                        <span class="text-xs text-slate-600">حاضر</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-rose-100 text-[11px] font-bold text-rose-700">{{ $selectedActivity->absent_attendances_count }}</span>
                                        <span class="text-xs text-slate-600">غایب</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-amber-100 text-[11px] font-bold text-amber-700">{{ $selectedActivity->late_attendances_count ?? 0 }}</span>
                                        <span class="text-xs text-slate-600">دیر رسید</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="flex flex-col gap-2">
                                @if($selectedActivity->status === 'ongoing')
                                    <button type="button" wire:click="openScanner({{ $selectedActivity->id }})" class="inline-flex items-center justify-center gap-1.5 rounded-full bg-cyan-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-cyan-700">
                                        <i class="bi bi-qr-code text-sm"></i>
                                        ثبت حضور QR
                                    </button>
                                @endif
                                <button type="button" wire:click="exportAttendances" class="inline-flex items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i class="bi bi-file-earmark-spreadsheet text-sm"></i>
                                    خروجی اکسل
                                </button>
                                @can('full-access')
                                    <button type="button" wire:click="openOperatorAssignment({{ $selectedActivity->id }})" class="inline-flex items-center justify-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">
                                        <i class="bi bi-person-badge text-sm"></i>
                                        تخصیص اپراتور
                                    </button>
                                @endcan
                            </div>

                            <!-- Collapsible: Event Details -->
                            <div x-data="{ eventOpen: false }" class="rounded-lg border border-slate-200 bg-white">
                                <button type="button" @click="eventOpen = !eventOpen" class="w-full flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition">
                                    <div class="flex items-center gap-2">
                                        <i class="bi bi-calendar-event text-slate-500"></i>
                                        <h3 class="text-sm font-semibold text-slate-800">اطلاعات برگزاری</h3>
                                    </div>
                                    <i class="bi transition-transform" :class="eventOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                </button>
                                <div x-show="eventOpen" x-transition class="border-t border-slate-200 px-4 py-3 space-y-2 text-xs text-slate-600 leading-relaxed">
                                    <p><strong class="text-slate-700">شروع:</strong> {{ $this->formatJalaliDateTime($selectedActivity->starts_at) }}</p>
                                    <p><strong class="text-slate-700">پایان:</strong> {{ $this->formatJalaliDateTime($selectedActivity->ends_at) }}</p>
                                    <p><strong class="text-slate-700">مکان:</strong> {{ $selectedActivity->location ?: '—' }}</p>
                                    <p><strong class="text-slate-700">توضیحات:</strong> {{ $selectedActivity->description ?: 'ثبت نشده' }}</p>
                                    <p><strong class="text-slate-700">یادداشت وضعیت:</strong> {{ $selectedActivity->status_notes ?: 'ثبت نشده' }}</p>
                                </div>
                            </div>

                            <!-- Collapsible: Status Transition (full-access only) -->
                            @can('full-access')
                                <div x-data="{ transitionOpen: false }" class="rounded-lg border border-slate-200 bg-white">
                                    <button type="button" @click="transitionOpen = !transitionOpen" class="w-full flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition">
                                        <div class="flex items-center gap-2">
                                            <i class="bi bi-arrow-repeat text-slate-500"></i>
                                            <h3 class="text-sm font-semibold text-slate-800">تغییر وضعیت</h3>
                                        </div>
                                        <i class="bi transition-transform" :class="transitionOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                    </button>
                                    <div x-show="transitionOpen" x-transition class="border-t border-slate-200 px-4 py-3 space-y-3">
                                        <textarea wire:model="transitionNotes" rows="2" placeholder="یادداشت تغییر وضعیت (اختیاری)" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs focus:border-violet-300 focus:ring-2 focus:ring-violet-100"></textarea>
                                        <div class="flex flex-col gap-2">
                                            @forelse($this->allowedTransitionTargets($selectedActivity) as $targetStatus)
                                                <button type="button" wire:click="transitionActivity({{ $selectedActivity->id }}, '{{ $targetStatus }}')" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-900">
                                                    تغییر به {{ $statusOptions[$targetStatus] ?? $targetStatus }}
                                                </button>
                                            @empty
                                                <p class="text-xs font-semibold text-slate-400 py-2 text-center">تغییر وضعیت دیگری مجاز نیست.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endcan

                            <!-- Collapsible: Attendance List -->
                            <div x-data="{ attendanceOpen: {{ count($filteredAttendances) > 0 ? 'true' : 'false' }} }" class="rounded-lg border border-slate-200 bg-white">
                                <button type="button" @click="attendanceOpen = !attendanceOpen" class="w-full flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition">
                                    <div class="flex items-center gap-2">
                                        <i class="bi bi-people text-slate-500"></i>
                                        <h3 class="text-sm font-semibold text-slate-800">فهرست شرکت‌کنندگان</h3>
                                        <span class="inline-flex items-center justify-center h-5 rounded-full bg-slate-200 px-2 text-[10px] font-bold text-slate-700">{{ count($filteredAttendances) }}</span>
                                    </div>
                                    <i class="bi transition-transform" :class="attendanceOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                </button>
                                <div x-show="attendanceOpen" x-transition class="border-t border-slate-200 px-4 py-3 space-y-2">
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
                                    </div>
                                    <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                                        @forelse($filteredAttendances as $attendance)
                                            <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 hover:bg-slate-100 transition">
                                                <p class="font-bold text-slate-800">{{ $attendance->person?->full_name ?: '—' }}</p>
                                                <p class="text-[11px]">{{ $attendance->person?->person_code ?: '—' }} · {{ $attendance->person?->national_id ?: '—' }}</p>
                                                <p class="text-[11px] mt-1">{{ $attendanceMethodOptions[$attendance->registration_method] ?? $attendance->registration_method }} · {{ $attendanceStatusOptions[$attendance->status] ?? $attendance->status }}</p>
                                                <p class="text-[11px] text-slate-500">{{ $this->formatJalaliDateTime($attendance->checked_in_at) }}</p>
                                            </div>
                                        @empty
                                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400">هنوز حضوری ثبت نشده</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex min-h-72 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center">
                            <div class="rounded-full bg-slate-100 p-4 mb-4">
                                <i class="bi bi-cursor-text text-2xl text-slate-400"></i>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900">هنوز فعالیتی انتخاب نشده</h3>
                            <p class="mt-2 text-sm text-slate-600 max-w-xs">یک فعالیت را از لیست سمت چپ انتخاب کنید تا جزئیات آن را مشاهده کنید و چرخه زندگی آن را مدیریت کنید.</p>
                            <div class="mt-4 space-y-2 text-[11px] text-slate-500">
                                <p class="flex items-start gap-2">
                                    <i class="bi bi-info-circle shrink-0 mt-0.5"></i>
                                    <span>اطلاعات کامل فعالیت</span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <i class="bi bi-check2-circle shrink-0 mt-0.5"></i>
                                    <span>مدیریت حضور شرکت‌کنندگان</span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <i class="bi bi-person-badge shrink-0 mt-0.5"></i>
                                    <span>تخصیص اپراتورها</span>
                                </p>
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
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
