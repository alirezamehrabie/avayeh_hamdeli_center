{{-- resources/views/livewire/admin/attendance-monitor.blade.php --}}
@php
    use App\Helpers\Morilog\Jalalian;
    use App\Helpers\Morilog\CalendarUtils;

    $fa = fn ($value) => CalendarUtils::convertNumbers((string) $value);
    $tabCount = fn (string $key) => match ($key) {
        'present' => $stats['present'],
        'archive' => $stats['archived'],
        default => $stats['sheets'],
    };
@endphp

<div class="space-y-6" dir="rtl" wire:poll.20s>
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header + live indicator --}}
    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm sm:rounded-[32px]">
        <div class="bg-gradient-to-l from-emerald-600 via-teal-600 to-cyan-600 px-4 py-4 text-white sm:px-6 sm:py-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-xl font-extrabold sm:text-2xl">پایش حضور و غیاب مددجویان</h1>
                    <p class="mt-1.5 text-xs text-emerald-50/90 sm:text-sm">
                        مشاهده زنده ثبت‌های ورود و خروج مددکاران اجتماعی
                    </p>
                </div>
                <div class="inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-white/15 px-3 py-1.5 text-[11px] font-bold ring-1 ring-white/25 backdrop-blur sm:self-auto">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                    </span>
                    به‌روزرسانی خودکار · {{ $fa(Jalalian::now()->format('H:i')) }}
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 p-4 sm:px-6 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                <p class="text-[11px] font-semibold text-slate-500 sm:text-xs">شیت‌های فعال</p>
                <p class="mt-1 text-2xl font-black text-slate-800">{{ $fa(number_format($stats['sheets'])) }}</p>
            </div>
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-3 sm:p-4">
                <p class="text-[11px] font-semibold text-sky-700">ورودها</p>
                <p class="mt-1 text-2xl font-black text-sky-800">{{ $fa(number_format($stats['checkIns'])) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                <p class="text-[11px] font-semibold text-slate-500">خروج‌ها</p>
                <p class="mt-1 text-2xl font-black text-slate-700">{{ $fa(number_format($stats['checkOuts'])) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 sm:p-4">
                <p class="text-[11px] font-semibold text-emerald-700">حاضر همین حالا</p>
                <p class="mt-1 flex items-center gap-1.5 text-2xl font-black text-emerald-800">
                    {{ $fa(number_format($stats['present'])) }}
                    @if($stats['present'] > 0)
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 border-t border-slate-200 px-4 py-3 sm:px-6">
            @foreach(\App\Livewire\Admin\AttendanceMonitor::TAB_OPTIONS as $key => $label)
                @php $isActive = $activeTab === $key; @endphp
                <button
                    type="button"
                    wire:click="setActiveTab('{{ $key }}')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition {{ $isActive ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    {{ $label }}
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $isActive ? 'bg-white/25' : 'bg-white text-slate-500' }}">
                        {{ $fa(number_format($tabCount($key))) }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
            <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_12rem] lg:grid-cols-[minmax(0,1fr)_12rem_auto] lg:items-center">
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="جستجوی نام مددجو، کد ملی، کد پرسنلی یا نام شیت"
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                >
                <select
                    wire:model.live="socialWorkerFilter"
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                    aria-label="فیلتر مددکار"
                >
                    <option value="">همه مددکاران</option>
                    @foreach($socialWorkers as $worker)
                        <option value="{{ $worker->id }}">{{ $worker->full_name }} ({{ $fa($worker->worker_code) }})</option>
                    @endforeach
                </select>
                @if($search !== '' || $socialWorkerFilter !== '')
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 lg:justify-self-end"
                    >
                        پاک کردن فیلترها
                    </button>
                @endif
            </div>

            @if($activeTab !== 'archive')
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span class="ml-1 text-[11px] font-semibold text-slate-400">بازه زمانی:</span>
                    @foreach(\App\Livewire\Admin\AttendanceMonitor::RANGE_OPTIONS as $key => $label)
                        <button
                            type="button"
                            wire:click="setRange('{{ $key }}')"
                            class="rounded-lg px-3 py-1.5 text-[11px] font-bold transition {{ $range === $key ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Results --}}
    @if($activeTab === 'sheets')
        @forelse($sheets as $sheet)
            <div
                wire:key="sheet-{{ $sheet->id }}"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex items-center gap-1 p-3 sm:p-4">
                    <button
                        type="button"
                        wire:click="toggleSheet({{ $sheet->id }})"
                        class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1 text-right transition hover:bg-slate-50"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-extrabold text-slate-900 sm:text-base">{{ $sheet->name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500 sm:text-xs">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ $sheet->socialWorker?->full_name ?: 'مددکار حذف‌شده' }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span>{{ $fa(Jalalian::fromDateTime($sheet->created_at)->format('Y/m/d H:i')) }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                            @if($sheet->present_count > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-700 sm:text-xs">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                                    {{ $fa($sheet->present_count) }} حاضر
                                </span>
                            @endif
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-bold text-sky-700 sm:text-xs">
                                ورود {{ $fa($sheet->check_ins_count) }}
                            </span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform {{ $expandedSheetId === $sheet->id ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <button
                        type="button"
                        wire:click="askArchiveSheet({{ $sheet->id }})"
                        title="انتقال به بایگانی"
                        aria-label="انتقال «{{ $sheet->name }}» به بایگانی"
                        class="shrink-0 rounded-xl p-2.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-100"
                    >
                        <svg class="h-4 w-4 sm:h-[18px] sm:w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                @if($expandedSheetId === $sheet->id)
                    <div class="border-t border-slate-100 bg-slate-50/60">
                        @if($expandedEntries->isEmpty())
                            <p class="p-6 text-center text-xs text-slate-400">هنوز کسی در این شیت ثبت نشده است.</p>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach($expandedEntries as $entry)
                                    @include('livewire.admin.partials.attendance-monitor-entry-row', ['entry' => $entry])
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center">
                <p class="text-sm font-semibold text-slate-500">هیچ شیت حضور و غیابی در این بازه یافت نشد.</p>
                <p class="mt-1 text-xs text-slate-400">با تغییر بازه زمانی یا فیلترها دوباره تلاش کنید.</p>
            </div>
        @endforelse

        @if($sheets->hasPages())
            <div>{{ $sheets->links('vendor.livewire.tailwind-mobile-persian') }}</div>
        @endif
    @elseif($activeTab === 'present')
        @if($presentEntries->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center">
                <p class="text-sm font-semibold text-slate-500">هم‌اکنون مددجوی در هیچ شیتی حاضر نیست.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <ul class="divide-y divide-slate-100">
                    @foreach($presentEntries as $entry)
                        <li class="flex items-center gap-3 p-4 sm:px-5">
                            <span class="relative flex h-2.5 w-2.5 shrink-0">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-slate-800 sm:text-sm">{{ $entry->person_name }}</p>
                                <p class="mt-0.5 truncate text-[10px] text-slate-400 sm:text-[11px]">
                                    {{ $entry->sheet?->name ?: 'شیت حذف‌شده' }}
                                    · {{ $entry->sheet?->socialWorker?->full_name ?: 'مددکار حذف‌شده' }}
                                </p>
                            </div>
                            <div class="shrink-0 text-left">
                                <p class="text-[10px] font-semibold text-emerald-700 sm:text-xs">{{ $fa($this->formatPresenceDuration($entry->checked_in_at)) }} است</p>
                                <p class="mt-0.5 text-[10px] text-slate-400 sm:text-[11px]">
                                    از {{ $fa(Jalalian::fromDateTime($entry->checked_in_at)->format('H:i')) }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($presentEntries->hasPages())
                <div>{{ $presentEntries->links('vendor.livewire.tailwind-mobile-persian') }}</div>
            @endif
        @endif
    @else
        {{-- Archive --}}
        @forelse($archiveSheets as $sheet)
            <div
                wire:key="archive-sheet-{{ $sheet->id }}"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex items-center gap-1 p-3 sm:p-4">
                    <button
                        type="button"
                        wire:click="toggleSheet({{ $sheet->id }})"
                        class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1 text-right transition hover:bg-slate-50"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-extrabold text-slate-700 sm:text-base">{{ $sheet->name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500 sm:text-xs">
                                <span>{{ $sheet->socialWorker?->full_name ?: 'مددکار حذف‌شده' }}</span>
                                <span class="text-slate-300">•</span>
                                <span>بایگانی: {{ $fa(Jalalian::fromDateTime($sheet->deleted_at)->format('Y/m/d H:i')) }}</span>
                                @if($sheet->archiver)
                                    <span class="text-slate-300">•</span>
                                    <span>توسط {{ $sheet->archiver->name }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                            <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 sm:inline-flex sm:text-xs">
                                {{ $fa($sheet->entries_count) }} ثبت · ورود {{ $fa($sheet->check_ins_count) }} · خروج {{ $fa($sheet->check_outs_count) }}
                            </span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform {{ $expandedSheetId === $sheet->id ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <button
                        type="button"
                        wire:click="restoreSheet({{ $sheet->id }})"
                        wire:loading.attr="disabled"
                        class="shrink-0 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-100 sm:text-xs"
                    >
                        بازگردانی
                    </button>
                </div>

                <p class="px-4 pb-3 text-[10px] font-bold text-slate-500 sm:hidden">
                    {{ $fa($sheet->entries_count) }} ثبت · ورود {{ $fa($sheet->check_ins_count) }} · خروج {{ $fa($sheet->check_outs_count) }}
                </p>

                @if($expandedSheetId === $sheet->id)
                    <div class="border-t border-slate-100 bg-slate-50/60">
                        @if($expandedEntries->isEmpty())
                            <p class="p-6 text-center text-xs text-slate-400">این شیت هیچ ثبتی ندارد.</p>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach($expandedEntries as $entry)
                                    @include('livewire.admin.partials.attendance-monitor-entry-row', ['entry' => $entry])
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center">
                <p class="text-sm font-semibold text-slate-500">بایگانی حضور و غیاب خالی است.</p>
                <p class="mt-1 text-xs text-slate-400">شیت‌هایی که از پایش حذف کنید، با تمام رکوردهایشان اینجا نگهداری می‌شوند.</p>
            </div>
        @endforelse

        @if($archiveSheets->hasPages())
            <div>{{ $archiveSheets->links('vendor.livewire.tailwind-mobile-persian') }}</div>
        @endif
    @endif

    {{-- Archive confirmation modal --}}
    @if($sheetToArchive)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="archive-sheet-title"
            wire:key="archive-confirm-modal"
        >
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cancelArchiveSheet"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 id="archive-sheet-title" class="text-base font-extrabold text-slate-900">انتقال به بایگانی</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            حضور و غیاب «<span class="font-bold text-slate-800">{{ $sheetToArchive->name }}</span>»
                            از دسترس مددکار خارج می‌شود و به بایگانی منتقل می‌گردد.
                            تمام رکوردهای ورود و خروج محفوظ می‌مانند و بعداً قابل بازگردانی است.
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 p-3 text-xs font-semibold text-slate-600">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span>مددکار: {{ $sheetToArchive->socialWorker?->full_name ?: '—' }}</span>
                        <span class="text-slate-300">•</span>
                        <span>تعداد ثبت‌ها: {{ $fa($sheetToArchive->entries_count ?? 0) }}</span>
                    </div>
                    @if(($sheetToArchive->present_count ?? 0) > 0)
                        <div class="mt-2 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-800">
                            توجه: {{ $fa($sheetToArchive->present_count) }} مددجو هنوز خروجشان ثبت نشده است.
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
                    <button
                        type="button"
                        wire:click="cancelArchiveSheet"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 sm:flex-1"
                    >
                        انصراف
                    </button>
                    <button
                        type="button"
                        wire:click="confirmArchiveSheet"
                        wire:loading.attr="disabled"
                        wire:target="confirmArchiveSheet"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:cursor-wait disabled:opacity-60 sm:flex-1"
                    >
                        <span wire:loading.remove wire:target="confirmArchiveSheet">بله، به بایگانی منتقل شود</span>
                        <span wire:loading wire:target="confirmArchiveSheet">در حال انتقال...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
