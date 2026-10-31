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
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm shadow-slate-900/[0.03]">
                <i class="bi bi-calendar3 text-indigo-600"></i>
                <span class="text-slate-500">امروز:</span>
                <span class="tabular-nums font-black text-slate-900">{{ Jalalian::now()->format('l، d F Y') }}</span>
            </span>
        </div>
    </div>

    <!-- Hero Banner: If there is an ongoing activity right now -->
    @if($ongoingActivity)
        @php
            $ongoingJalaliDate = $ongoingActivity->starts_at ? Jalalian::fromDateTime($ongoingActivity->starts_at)->format('Y/m/d') : null;
            $ongoingJalaliTime = $ongoingActivity->starts_at ? Jalalian::fromDateTime($ongoingActivity->starts_at)->format('H:i') : null;
        @endphp
        <div class="relative overflow-hidden rounded-2xl border border-emerald-200/90 bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-teal-500/10 p-4 sm:p-5 shadow-sm shadow-emerald-950/5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3.5 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30 ring-4 ring-emerald-100">
                        <i class="bi bi-broadcast text-xl animate-pulse"></i>
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
                        <h2 class="mt-1.5 text-base font-black text-slate-900 sm:text-lg truncate">
                            {{ $ongoingActivity->name }}
                        </h2>
                        <div class="mt-1.5 flex flex-wrap items-center gap-3.5 text-xs text-slate-500">
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
                        </div>
                    </div>
                </div>

                <div class="shrink-0">
                    <a
                        href="{{ route('activity-operator.check-in', ['id' => $ongoingActivity->id]) }}"
                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-emerald-600/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 active:scale-95"
                    >
                        <i class="bi bi-qr-code-scan text-base"></i>
                        <span>ورود به اسکنر و ثبت حضور</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics KPI Cards (4 Harmonious Elevated Cards) -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Total Assigned Activities -->
        <div class="group flex items-center justify-between rounded-2xl border border-indigo-100 bg-gradient-to-b from-indigo-50/30 via-white to-white p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500">فعالیت‌های تعیین‌شده</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-indigo-700 sm:text-3xl" dir="ltr">
                    {{ number_format($assignedActivitiesCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-calendar-event text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Ongoing Activities -->
        <div class="group flex items-center justify-between rounded-2xl border border-emerald-100 bg-gradient-to-b from-emerald-50/30 via-white to-white p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500">فعالیت‌های آماده برگزاری</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-emerald-600 sm:text-3xl" dir="ltr">
                    {{ number_format($ongoingActivitiesCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-play-circle text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Total Recorded Attendances -->
        <div class="group flex items-center justify-between rounded-2xl border border-cyan-100 bg-gradient-to-b from-cyan-50/30 via-white to-white p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500">حضورهای ثبت‌شده</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-cyan-600 sm:text-3xl" dir="ltr">
                    {{ number_format($totalAttendancesRecorded) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-600 text-white shadow-md shadow-cyan-600/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-person-check text-xl"></i>
            </div>
        </div>

        <!-- Card 4: Completed Activities -->
        <div class="group flex items-center justify-between rounded-2xl border border-slate-200/90 bg-gradient-to-b from-slate-50/40 via-white to-white p-4 shadow-sm shadow-slate-900/[0.03] transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500">فعالیت‌های خاتمه‌یافته</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-slate-700 sm:text-3xl" dir="ltr">
                    {{ number_format($completedActivitiesCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-md shadow-slate-700/25 transition duration-200 group-hover:scale-105">
                <i class="bi bi-check2-circle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Upcoming Activities List Card -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/40 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 shadow-sm">
                    <i class="bi bi-list-task text-sm"></i>
                </span>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-black text-slate-900 sm:text-base">برنامه فعالیت‌های پیش‌رو</h2>
                    <span class="rounded-full bg-slate-200/70 px-2 py-0.5 text-[10px] font-bold text-slate-600 tabular-nums">
                        {{ count($upcomingActivities) }} رویداد
                    </span>
                </div>
            </div>
            <a
                href="{{ route('activity-operator.activity-list') }}"
                class="group inline-flex items-center gap-1 text-xs font-bold text-indigo-600 transition hover:text-indigo-800"
            >
                <span>مشاهده همه</span>
                <i class="bi bi-chevron-left text-[10px] transition-transform duration-200 group-hover:-translate-x-0.5"></i>
            </a>
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
                @endphp
                <div class="flex flex-col gap-3 p-4 transition-colors duration-150 hover:bg-indigo-50/20 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="truncate text-sm font-black text-slate-900 sm:text-base">{{ $activity->name }}</p>
                            <span class="shrink-0 rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-600 tracking-wider" dir="ltr">
                                {{ $activity->code }}
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 {{ $statusClasses }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                <span>{{ $statusLabel }}</span>
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-3.5 text-xs text-slate-500">
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

                            <span class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 shadow-sm">
                                <i class="bi bi-people text-slate-400"></i>
                                <span>حاضرین:</span>
                                <span class="font-bold tabular-nums text-slate-900" dir="ltr">{{ number_format($activity->attendances_count ?? 0) }}</span>
                                <span class="text-[10px] text-slate-400">نفر</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 self-end sm:self-center">
                        @if($isOngoing)
                            <a
                                href="{{ route('activity-operator.check-in', ['id' => $activity->id]) }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-700 active:scale-95"
                            >
                                <i class="bi bi-qr-code-scan"></i>
                                <span>ثبت حضور</span>
                            </a>
                        @else
                            <a
                                href="{{ route('activity-operator.activity-list') }}"
                                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 active:scale-95"
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
                    <p class="text-sm font-bold text-slate-700">در حال حاضر هیچ فعالیتی در برنامه زمانی شما وجود ندارد</p>
                    <p class="mt-1 text-xs text-slate-400">فعالیت‌های جدید پس از واگذاری توسط مدیریت در این بخش نمایش داده خواهند شد.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions (High Polish Micro-interactions & Correct RTL Direction) -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a
            href="{{ route('activity-operator.activity-list') }}"
            class="group flex items-center justify-between rounded-2xl border border-indigo-100 bg-gradient-to-br from-white via-indigo-50/20 to-indigo-50/40 p-4.5 sm:p-5 shadow-sm shadow-indigo-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"
        >
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25 transition duration-200 group-hover:scale-105">
                    <i class="bi bi-collection text-xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-black text-slate-900 transition-colors group-hover:text-indigo-900">فهرست فعالیت‌های من</p>
                    <p class="mt-0.5 text-xs text-slate-500 truncate">مشاهده تمام فعالیت‌های تعیین‌شده، جزئیات و فیلترها</p>
                </div>
            </div>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition duration-200 group-hover:bg-indigo-600 group-hover:text-white group-hover:-translate-x-1">
                <i class="bi bi-arrow-left text-sm"></i>
            </span>
        </a>

        <a
            href="{{ route('activity-operator.reports') }}"
            class="group flex items-center justify-between rounded-2xl border border-cyan-100 bg-gradient-to-br from-white via-cyan-50/20 to-cyan-50/40 p-4.5 sm:p-5 shadow-sm shadow-cyan-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-md"
        >
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-600 text-white shadow-md shadow-cyan-600/25 transition duration-200 group-hover:scale-105">
                    <i class="bi bi-graph-up-arrow text-xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-black text-slate-900 transition-colors group-hover:text-cyan-900">مشاهده گزارش‌ها و آمار</p>
                    <p class="mt-0.5 text-xs text-slate-500 truncate">آمار تفکیکی حضور و غیاب‌ها، لیست افراد و خروجی‌ها</p>
                </div>
            </div>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 transition duration-200 group-hover:bg-cyan-600 group-hover:text-white group-hover:-translate-x-1">
                <i class="bi bi-arrow-left text-sm"></i>
            </span>
        </a>
    </div>
</div>
