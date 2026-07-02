<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-900">پیشخوان اپراتور فعالیت</h1>
            <p class="mt-1 text-slate-600">خوش‌آمدید، {{ auth()->user()->first_name ?? 'کاربر' }}!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">فعالیت‌های تعیین‌شده</p>
                    <p class="mt-2 text-3xl font-black text-indigo-600">{{ $assignedActivitiesCount }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">فعالیت‌های در حال برگزاری</p>
                    <p class="mt-2 text-3xl font-black text-emerald-600">{{ $ongoingActivitiesCount }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">حضور ثبت‌شده</p>
                    <p class="mt-2 text-3xl font-black text-cyan-600">{{ $totalAttendancesRecorded }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-cyan-100">
                    <svg class="h-6 w-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">دسترسی سریع</p>
                    <p class="mt-2 text-sm text-indigo-600 font-semibold">بروید به فعالیت‌ها</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Activities -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="font-black text-slate-900">فعالیت‌های آتی</h2>
        </div>
        <div class="divide-y">
            @forelse($upcomingActivities as $activity)
                <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-50">
                    <div>
                        <p class="font-bold text-slate-900">{{ $activity->name }}</p>
                        <p class="text-sm text-slate-600">{{ $activity->code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">{{ $activity->starts_at?->format('Y/m/d H:i') }}</p>
                        <p class="text-xs text-slate-600">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold">
                                {{ $activity->status === 'ongoing' ? 'آماده برگزاری' : ($activity->status === 'draft' ? 'پیش نویس' : $activity->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center">
                    <p class="text-slate-600">هیچ فعالیتی در نزدیک‌ترو آینده وجود ندارد</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('activity-operator.activity-list') }}" class="flex items-center justify-between rounded-2xl border-2 border-indigo-200 bg-indigo-50 p-6 transition hover:bg-indigo-100">
            <div>
                <p class="text-sm font-semibold text-indigo-700">مشاهده لیست فعالیت‌ها</p>
                <p class="mt-1 text-sm text-indigo-600">دسترسی به تمام فعالیت‌های تعیین‌شده</p>
            </div>
            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </a>

        <a href="{{ route('activity-operator.reports') }}" class="flex items-center justify-between rounded-2xl border-2 border-cyan-200 bg-cyan-50 p-6 transition hover:bg-cyan-100">
            <div>
                <p class="text-sm font-semibold text-cyan-700">مشاهده گزارش‌ها</p>
                <p class="mt-1 text-sm text-cyan-600">آمار ثبت‌نام حضور و غیاب</p>
            </div>
            <svg class="h-6 w-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </a>
    </div>
</div>
