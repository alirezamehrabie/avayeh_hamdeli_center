<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-900">گزارش‌های حضور و غیاب</h1>
            <p class="mt-1 text-slate-600">مشاهده و تحلیل آمار ثبت‌نام حضور برای فعالیت‌های خود</p>
        </div>
        <a href="{{ route('activity-operator.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            بازگشت
        </a>
    </div>

    <!-- Activity Selector -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <label class="mb-3 block text-sm font-bold text-slate-900">انتخاب فعالیت</label>
        <div class="grid gap-2 md:grid-cols-2">
            @forelse($activities as $activity)
                <button type="button" wire:click="selectActivity({{ $activity->id }})" class="flex items-center justify-between rounded-lg border-2 px-4 py-3 transition {{ $selectedActivity?->id === $activity->id ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}">
                    <div class="text-right">
                        <p class="font-bold text-slate-900">{{ $activity->name }}</p>
                        <p class="text-xs text-slate-600">{{ $activity->code }} • {{ $activity->present_attendances_count }} / {{ $activity->attendances_count }}</p>
                    </div>
                </button>
            @empty
                <div class="col-span-2 text-center py-6">
                    <p class="text-slate-600">هیچ فعالیتی برای نمایش وجود ندارد</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($selectedActivity)
        <!-- Activity Stats -->
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-600">مجموع ثبت‌نام‌ها</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ $selectedActivity->attendances_count }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-600">حاضر</p>
                <p class="mt-2 text-2xl font-black text-emerald-600">{{ $selectedActivity->present_attendances_count }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-600">با تأخیر</p>
                <p class="mt-2 text-2xl font-black text-amber-600">{{ $selectedActivity->late_attendances_count }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-600">غایب</p>
                <p class="mt-2 text-2xl font-black text-rose-600">{{ $selectedActivity->absent_attendances_count }}</p>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="font-black text-slate-900">لیست حضور و غیاب</h2>
                    <div class="flex flex-col gap-2 md:flex-row">
                        <input type="search" wire:model.live.debounce.300ms="attendanceSearch" class="h-10 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="جستجو">
                        <select wire:model.live="attendanceStatusFilter" class="h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            <option value="all">همه وضعیت‌ها</option>
                            @foreach($attendanceStatusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="divide-y">
                @forelse($filteredAttendances as $attendance)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-50">
                        <div>
                            <p class="font-bold text-slate-900">{{ $attendance->person->full_name }}</p>
                            <p class="text-xs text-slate-600">{{ $attendance->person->person_code }} • ورود: {{ $attendance->checked_in_at?->format('Y/m/d H:i') ?? '—' }}</p>
                            <p class="text-xs text-slate-600">خروج: {{ $attendance->checked_out_at?->format('Y/m/d H:i') ?? 'ثبت نشده' }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 text-right">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $attendance->status === 'present' ? 'bg-emerald-100 text-emerald-700' : ($attendance->status === 'late' ? 'bg-amber-100 text-amber-700' : ($attendance->status === 'absent' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700')) }}">
                                {{ $attendanceStatusOptions[$attendance->status] ?? $attendance->status }}
                            </span>
                            @if($attendance->checked_out_at)
                                <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">خارج شده</span>
                            @elseif($attendance->checked_in_at)
                                <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">حاضر</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center">
                        <p class="text-slate-600">هیچ ثبت‌نامی برای نمایش وجود ندارد</p>
                    </div>
                @endforelse
            </div>
        </div>

        @if($filteredAttendances->hasPages())
            <div class="flex justify-center">
                {{ $filteredAttendances->links() }}
            </div>
        @endif

        <div class="flex justify-center">
            <button type="button" wire:click="clearSelectedActivity" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                پاک کردن
            </button>
        </div>
    @else
        <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
            <p class="text-slate-600">فعالیتی را انتخاب کنید تا گزارش ثبت‌نام را مشاهده کنید</p>
        </div>
    @endif
</div>
