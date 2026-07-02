<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-900">فعالیت‌های من</h1>
            <p class="mt-1 text-slate-600">لیست فعالیت‌های تعیین‌شده برای ثبت‌نام حضور و غیاب</p>
        </div>
        <a href="{{ route('activity-operator.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            بازگشت
        </a>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-3 md:flex-row md:items-end">
        <div class="flex-1">
            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">جستجو</label>
            <input type="search" wire:model.live.debounce.300ms="search" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="نام یا کد فعالیت">
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">وضعیت</label>
            <select wire:model.live="statusFilter" class="h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                <option value="all">همه وضعیت‌ها</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Activities List -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($activities as $activity)
            <div class="flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                <div class="flex-1 px-4 py-4">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <div>
                            <p class="font-bold text-slate-900">{{ $activity->name }}</p>
                            <p class="text-xs text-slate-600">{{ $activity->code }}</p>
                        </div>
                        <span class="inline-flex rounded-full bg-indigo-100 px-2 py-1 text-[10px] font-semibold text-indigo-700">
                            {{ $statusOptions[$activity->status] ?? $activity->status }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600">📍 {{ $activity->location }}</p>
                    <p class="mt-1 text-sm text-slate-600">🕐 {{ $activity->starts_at?->format('Y/m/d H:i') }}</p>
                    <div class="mt-3 flex gap-1 text-xs text-slate-600">
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-emerald-700">✓ {{ $activity->present_attendances_count ?? 0 }} حاضر</span>
                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-slate-700">مجموع: {{ $activity->attendances_count ?? 0 }}</span>
                    </div>
                </div>
                <div class="border-t border-slate-200 px-4 py-3 flex gap-2">
                    @if($activity->status === 'ongoing')
                        <a href="{{ route('activity-operator.check-in', ['id' => $activity->id]) }}" class="flex-1 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            ثبت حضور
                        </a>
                    @else
                        <button type="button" disabled class="flex-1 inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400">
                            غیر فعال
                        </button>
                    @endif
                    <button type="button" wire:click="selectActivity({{ $activity->id }})" class="flex-1 inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        جزئیات
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <p class="text-slate-600">هیچ فعالیتی برای نمایش وجود ندارد</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($activities->hasPages())
        <div class="flex justify-center">
            {{ $activities->links() }}
        </div>
    @endif
</div>
