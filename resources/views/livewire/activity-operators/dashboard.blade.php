@php
    use App\Helpers\Morilog\Jalalian;

    $userFirstName = trim((string) (auth()->user()->first_name ?? ''));
    $userDisplayName = $userFirstName !== '' ? $userFirstName : 'همکار گرامی';
@endphp

<div class="space-y-6">
    <!-- Header Greeting & Today's Date -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">پیشخوان اپراتور فعالیت</h1>
            <p class="mt-1 text-xs font-semibold text-slate-500 sm:text-sm">
                <span>خوش‌آمدید، {{ $userDisplayName }}</span>
                <span class="mx-1 text-slate-300">|</span>
                <span>سامانه ثبت‌نام و مدیریت حضور و غیاب فعالیت‌ها</span>
            </p>
        </div>
        <div class="flex items-center gap-2 self-stretch sm:self-auto justify-between sm:justify-end">
            <button
                type="button"
                wire:click="$refresh"
                wire:loading.attr="disabled"
                class="inline-flex min-h-[42px] items-center justify-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm shadow-slate-900/[0.03] transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 disabled:opacity-60"
                title="به‌روزرسانی داده‌های پیشخوان"
            >
                <i class="bi bi-arrow-clockwise text-indigo-600 transition-transform" wire:loading.class="animate-spin"></i>
                <span class="text-slate-600">به‌روزرسانی</span>
            </button>
            <span class="inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm shadow-slate-900/[0.03]">
                <i class="bi bi-calendar3 text-indigo-600"></i>
                <span class="text-slate-500 hidden xs:inline">امروز:</span>
                <span class="tabular-nums font-black text-slate-900">{{ Jalalian::now()->format('l، d F Y') }}</span>
            </span>
        </div>
    </div>

    <!-- Hero Banner: If there is an ongoing activity right now -->
    @if($ongoingActivity)
        @php
            $ongoingJalaliDate = $ongoingActivity->starts_at ? Jalalian::fromDateTime($ongoingActivity->starts_at)->format('Y/m/d') : null;
            $ongoingJalaliTime = $ongoingActivity->starts_at ? Jalalian::fromDateTime($ongoingActivity->starts_at)->format('H:i') : null;
            $ongoingCapacity = $ongoingActivity->capacity;
            $ongoingAttendees = $ongoingActivity->attendances_count ?? 0;
            $ongoingPercentage = ($ongoingCapacity && $ongoingCapacity > 0)
                ? min(100, (int) round(($ongoingAttendees / $ongoingCapacity) * 100))
                : null;
        @endphp
        <div class="relative overflow-hidden rounded-2xl border border-emerald-200/90 bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-teal-500/10 p-4 sm:p-5 shadow-sm shadow-emerald-950/5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3 sm:gap-3.5 min-w-0">
                    <span class="flex h-11 w-11 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30 ring-4 ring-emerald-100">
                        <i class="bi bi-broadcast text-lg sm:text-xl animate-pulse"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-ping"></span>
                                در حال برگزاری هم‌اکنون
                            </span>
                            @if(isset($typeOptions[$ongoingActivity->activity_type]))
                                <span class="rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-200">
                                    {{ $typeOptions[$ongoingActivity->activity_type] }}
                                </span>
                            @endif
                        </div>
                        <h2 class="mt-1.5 text-base font-black text-slate-900 sm:text-lg line-clamp-2 sm:truncate">
                            {{ $ongoingActivity->name }}
                        </h2>
                        <div class="mt-2 flex flex-wrap items-center gap-2 sm:gap-3.5 text-xs text-slate-500">
                            @if($ongoingActivity->location)
                                <span class="inline-flex items-center gap-1.5 text-slate-600">
                                    <i class="bi bi-geo-alt text-emerald-600"></i>
                                    <span>{{ $ongoingActivity->location }}</span>
                                </span>
                            @endif
                            @if($ongoingJalaliTime)
                                <span class="inline-flex items-center gap-1.5 text-slate-600">
                                    <i class="bi bi-clock text-emerald-600"></i>
                                    <span>ساعت شروع:</span>
                                    <span class="tabular-nums font-bold text-slate-800" dir="ltr">{{ $ongoingJalaliTime }}</span>
                                </span>
                            @endif
                            @if($ongoingJalaliDate)
                                <span class="inline-flex items-center gap-1.5 text-slate-600">
                                    <i class="bi bi-calendar-event text-emerald-600"></i>
                                    <span class="tabular-nums font-bold text-slate-800" dir="ltr">{{ $ongoingJalaliDate }}</span>
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white/80 px-2.5 py-1 text-xs font-bold text-emerald-900 shadow-sm">
                                <i class="bi bi-people-fill text-emerald-600"></i>
                                <span>حاضرین:</span>
                                <span class="tabular-nums font-black" dir="ltr">{{ number_format($ongoingAttendees) }}</span>
                                @if($ongoingCapacity && $ongoingCapacity > 0)
                                    <span class="text-slate-400 font-normal">از</span>
                                    <span class="tabular-nums text-slate-700" dir="ltr">{{ number_format($ongoingCapacity) }}</span>
                                    <span class="text-[10px] font-semibold text-emerald-700">({{ $ongoingPercentage }}٪)</span>
                                @else
                                    <span class="text-[10px] font-normal text-slate-500">نفر</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 pt-1 sm:pt-0">
                    <a
                        href="{{ route('activity-operator.check-in', ['id' => $ongoingActivity->id]) }}"
                        class="inline-flex w-full sm:w-auto min-h-[46px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-emerald-600/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 active:scale-95"
                    >
                        <i class="bi bi-qr-code-scan text-base"></i>
                        <span>ورود به اسکنر و ثبت حضور</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics KPI Cards (Compact 2x2 on Mobile, 4 Cols on Desktop) -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <!-- Card 1: Total Assigned Activities -->
        <div class="group flex items-center justify-between rounded-xl sm:rounded-2xl border border-indigo-100 bg-gradient-to-b from-indigo-50/30 via-white to-white p-3 sm:p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-500 truncate">فعالیت‌های من</p>
                <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tabular-nums text-indigo-700" dir="ltr">
                    {{ number_format($assignedActivitiesCount) }}
                </p>
            </div>
            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-calendar-event text-base sm:text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Ongoing Activities -->
        <div class="group flex items-center justify-between rounded-xl sm:rounded-2xl border border-emerald-100 bg-gradient-to-b from-emerald-50/30 via-white to-white p-3 sm:p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-500 truncate">آماده برگزاری</p>
                <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tabular-nums text-emerald-600" dir="ltr">
                    {{ number_format($ongoingActivitiesCount) }}
                </p>
            </div>
            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-play-circle text-base sm:text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Total Recorded Attendances -->
        <div class="group flex items-center justify-between rounded-xl sm:rounded-2xl border border-cyan-100 bg-gradient-to-b from-cyan-50/30 via-white to-white p-3 sm:p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-500 truncate">حضورهای ثبت‌شده</p>
                <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tabular-nums text-cyan-600" dir="ltr">
                    {{ number_format($totalAttendancesRecorded) }}
                </p>
            </div>
            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-cyan-600 text-white shadow-md shadow-cyan-600/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-person-check text-base sm:text-xl"></i>
            </div>
        </div>

        <!-- Card 4: Completed Activities -->
        <div class="group flex items-center justify-between rounded-xl sm:rounded-2xl border border-slate-200/90 bg-gradient-to-b from-slate-50/40 via-white to-white p-3 sm:p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs font-bold text-slate-500 truncate">خاتمه‌یافته</p>
                <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tabular-nums text-slate-700" dir="ltr">
                    {{ number_format($completedActivitiesCount) }}
                </p>
            </div>
            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-slate-700 text-white shadow-md shadow-slate-700/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-check2-circle text-base sm:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Upcoming Activities List Card -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/40 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center justify-between sm:justify-start gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 shadow-sm">
                        <i class="bi bi-list-task text-sm"></i>
                    </span>
                    <h2 class="text-sm font-black text-slate-900 sm:text-base">فعالیت‌های پیش‌رو</h2>
                    <span class="rounded-full bg-slate-200/70 px-2 py-0.5 text-[10px] font-bold text-slate-600 tabular-nums">
                        {{ count($upcomingActivities) }} رویداد
                    </span>
                </div>

                <a
                    href="{{ route('activity-operator.activity-list') }}"
                    class="group sm:hidden inline-flex items-center gap-1 text-xs font-bold text-indigo-600 transition hover:text-indigo-800"
                >
                    <span>همه</span>
                    <i class="bi bi-chevron-left text-[10px] transition-transform duration-200 group-hover:-translate-x-0.5"></i>
                </a>
            </div>

            <!-- Filter Tabs / Pills (Full-width Segmented on Mobile, Inline on Desktop) -->
            <div class="flex items-center gap-2 justify-between sm:justify-end">
                <div class="inline-flex w-full sm:w-auto items-center justify-between sm:justify-start rounded-xl bg-slate-200/60 p-1 text-xs">
                    <button
                        type="button"
                        wire:click="setFilter('all')"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center min-h-[34px] gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition-all {{ $filter === 'all' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        <span>همه</span>
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] tabular-nums {{ $filter === 'all' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-300/60 text-slate-600' }}">{{ $activeActivitiesCount }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setFilter('ongoing')"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center min-h-[34px] gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition-all {{ $filter === 'ongoing' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span>آماده برگزاری</span>
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] tabular-nums {{ $filter === 'ongoing' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-300/60 text-slate-600' }}">{{ $ongoingActivitiesCount }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setFilter('today')"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center min-h-[34px] gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition-all {{ $filter === 'today' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        <span>امروز</span>
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] tabular-nums {{ $filter === 'today' ? 'bg-amber-50 text-amber-700' : 'bg-slate-300/60 text-slate-600' }}">{{ $todayActivitiesCount }}</span>
                    </button>
                </div>

                <a
                    href="{{ route('activity-operator.activity-list') }}"
                    class="hidden sm:inline-flex group items-center gap-1 text-xs font-bold text-indigo-600 transition hover:text-indigo-800 pr-1"
                >
                    <span>فهرست کامل</span>
                    <i class="bi bi-chevron-left text-[10px] transition-transform duration-200 group-hover:-translate-x-0.5"></i>
                </a>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($upcomingActivities as $activity)
                @php
                    $isOngoing = $activity->status === 'ongoing';
                    $statusLabel = $statusOptions[$activity->status] ?? $activity->status;
                    $statusClasses = match($activity->status) {
                        'ongoing' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/80',
                        'draft' => 'bg-amber-50 text-amber-700 ring-amber-200/80',
                        'closed' => 'bg-slate-100 text-slate-600 ring-slate-200/80',
                        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200/80',
                        default => 'bg-slate-100 text-slate-600 ring-slate-200/80',
                    };
                    $statusDotClass = match($activity->status) {
                        'ongoing' => 'bg-emerald-500 animate-pulse',
                        'draft' => 'bg-amber-500',
                        'closed' => 'bg-slate-400',
                        'cancelled' => 'bg-rose-500',
                        default => 'bg-slate-400',
                    };
                    $jalaliDate = $activity->starts_at ? Jalalian::fromDateTime($activity->starts_at)->format('Y/m/d') : null;
                    $jalaliTime = $activity->starts_at ? Jalalian::fromDateTime($activity->starts_at)->format('H:i') : null;

                    $capacity = $activity->capacity;
                    $attendees = $activity->attendances_count ?? 0;
                    $hasCapacity = $capacity && $capacity > 0;
                    $capacityPercentage = $hasCapacity ? min(100, (int) round(($attendees / $capacity) * 100)) : null;
                    $isFull = $hasCapacity && $attendees >= $capacity;
                @endphp
                <div class="flex flex-col gap-3.5 p-4 sm:p-5 transition-colors duration-150 hover:bg-indigo-50/20 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-black text-slate-900 sm:text-base line-clamp-2 sm:truncate">{{ $activity->name }}</p>
                            <span class="shrink-0 rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-600 tracking-wider" dir="ltr">
                                {{ $activity->code }}
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 {{ $statusClasses }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                <span>{{ $statusLabel }}</span>
                            </span>
                        </div>

                        <div class="mt-2.5 flex flex-wrap items-center gap-2.5 sm:gap-3.5 text-xs text-slate-500">
                            @if(isset($typeOptions[$activity->activity_type]))
                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-tag text-slate-400"></i>
                                    <span>{{ $typeOptions[$activity->activity_type] }}</span>
                                </span>
                            @endif

                            @if($activity->location)
                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-geo-alt text-slate-400"></i>
                                    <span>{{ $activity->location }}</span>
                                </span>
                            @endif

                            @if($jalaliDate)
                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-calendar-event text-slate-400"></i>
                                    <span class="tabular-nums font-semibold text-slate-800" dir="ltr">{{ $jalaliDate }}</span>
                                </span>
                            @endif

                            @if($jalaliTime)
                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-clock text-slate-400"></i>
                                    <span class="tabular-nums font-semibold text-slate-800" dir="ltr">{{ $jalaliTime }}</span>
                                </span>
                            @endif

                            <div class="inline-flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 shadow-sm">
                                    <i class="bi bi-people text-slate-400"></i>
                                    <span>حاضرین:</span>
                                    <span class="font-bold tabular-nums text-slate-900" dir="ltr">{{ number_format($attendees) }}</span>
                                    @if($hasCapacity)
                                        <span class="text-slate-400 font-normal">از</span>
                                        <span class="font-bold tabular-nums text-slate-700" dir="ltr">{{ number_format($capacity) }}</span>
                                        <span class="text-[10px] font-bold {{ $isFull ? 'text-amber-600' : 'text-slate-500' }}">({{ $capacityPercentage }}٪)</span>
                                    @else
                                        <span class="text-[10px] text-slate-400">نفر</span>
                                    @endif
                                </span>

                                @if($hasCapacity)
                                    <div class="inline-flex items-center gap-1.5" title="درصد تکمیل ظرفیت: {{ $capacityPercentage }}٪">
                                        <div class="h-1.5 w-12 sm:w-16 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full transition-all duration-300 {{ $isFull ? 'bg-amber-500' : ($capacityPercentage > 80 ? 'bg-indigo-500' : 'bg-emerald-500') }}"
                                                style="width: {{ $capacityPercentage }}%"
                                            ></div>
                                        </div>
                                        @if($isFull)
                                            <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-700">تکمیل</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Touch-friendly Action Button (Full-width on Mobile, Compact on Desktop) -->
                    <div class="flex shrink-0 items-center gap-2 pt-1 sm:pt-0">
                        @if($isOngoing)
                            <a
                                href="{{ route('activity-operator.check-in', ['id' => $activity->id]) }}"
                                class="inline-flex w-full sm:w-auto min-h-[42px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-700 active:scale-95"
                            >
                                <i class="bi bi-qr-code-scan"></i>
                                <span>ثبت حضور (اسکنر)</span>
                            </a>
                        @else
                            <a
                                href="{{ route('activity-operator.activity-list') }}"
                                class="inline-flex w-full sm:w-auto min-h-[38px] items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 active:scale-95"
                            >
                                <span>مشاهده جزئیات</span>
                                <i class="bi bi-arrow-left text-[11px]"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3 shadow-inner">
                        <i class="bi bi-calendar-x text-2xl"></i>
                    </span>
                    <p class="text-sm font-bold text-slate-700">
                        @if($filter === 'ongoing')
                            در حال حاضر هیچ فعالیتی با وضعیت آماده برگزاری وجود ندارد
                        @elseif($filter === 'today')
                            هیچ فعالیتی برای تاریخ امروز برنامه‌ریزی نشده است
                        @else
                            در حال حاضر هیچ فعالیتی در برنامه زمانی شما وجود ندارد
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        @if($filter !== 'all')
                            می‌توانید با انتخاب گزینه «همه»، تمام فعالیت‌های فعال را مشاهده کنید.
                        @else
                            فعالیت‌های جدید پس از واگذاری توسط مدیریت در این بخش نمایش داده خواهند شد.
                        @endif
                    </p>
                    @if($filter !== 'all')
                        <div class="mt-4">
                            <button
                                type="button"
                                wire:click="setFilter('all')"
                                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 active:scale-95"
                            >
                                <span>نمایش همه فعالیت‌ها</span>
                                <i class="bi bi-arrow-left text-[11px]"></i>
                            </button>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions (High Polish Micro-interactions & Mobile Touch Ergonomics) -->
    <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2">
        <a
            href="{{ route('activity-operator.activity-list') }}"
            class="group flex min-h-[72px] items-center justify-between rounded-2xl border border-indigo-100 bg-gradient-to-br from-white via-indigo-50/20 to-indigo-50/40 p-4 sm:p-5 shadow-sm shadow-indigo-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md active:scale-[0.98]"
        >
            <div class="flex items-center gap-3 sm:gap-3.5 min-w-0">
                <span class="flex h-11 w-11 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25 transition duration-200 group-hover:scale-105">
                    <i class="bi bi-collection text-lg sm:text-xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-black text-slate-900 transition-colors group-hover:text-indigo-900">فهرست فعالیت‌های من</p>
                    <p class="mt-0.5 text-xs text-slate-500 line-clamp-1 sm:line-clamp-none">مشاهده تمام فعالیت‌های تعیین‌شده، جزئیات و فیلترها</p>
                </div>
            </div>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition duration-200 group-hover:bg-indigo-600 group-hover:text-white group-hover:-translate-x-1">
                <i class="bi bi-arrow-left text-sm"></i>
            </span>
        </a>

        <a
            href="{{ route('activity-operator.reports') }}"
            class="group flex min-h-[72px] items-center justify-between rounded-2xl border border-cyan-100 bg-gradient-to-br from-white via-cyan-50/20 to-cyan-50/40 p-4 sm:p-5 shadow-sm shadow-cyan-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-md active:scale-[0.98]"
        >
            <div class="flex items-center gap-3 sm:gap-3.5 min-w-0">
                <span class="flex h-11 w-11 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-600 text-white shadow-md shadow-cyan-600/25 transition duration-200 group-hover:scale-105">
                    <i class="bi bi-graph-up-arrow text-lg sm:text-xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-black text-slate-900 transition-colors group-hover:text-cyan-900">مشاهده گزارش‌ها و آمار</p>
                    <p class="mt-0.5 text-xs text-slate-500 line-clamp-1 sm:line-clamp-none">آمار تفکیکی حضور و غیاب‌ها، لیست افراد و خروجی‌ها</p>
                </div>
            </div>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 transition duration-200 group-hover:bg-cyan-600 group-hover:text-white group-hover:-translate-x-1">
                <i class="bi bi-arrow-left text-sm"></i>
            </span>
        </a>
    </div>
</div>
