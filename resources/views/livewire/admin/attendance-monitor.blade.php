{{-- resources/views/livewire/admin/attendance-monitor.blade.php --}}
@php
    use App\Helpers\Morilog\Jalalian;
    use App\Helpers\Morilog\CalendarUtils;

    $fa = fn ($value) => CalendarUtils::convertNumbers((string) $value);
    $methodLabel = fn ($method) => $method === 'qr' ? 'QR' : 'دستی';
@endphp

<div class="space-y-6" dir="rtl" wire:poll.20s>
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
                <p class="text-[11px] font-semibold text-sky-700">وردها</p>
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
                        {{ $fa(number_format($key === 'present' ? $stats['present'] : $stats['sheets'])) }}
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
        </div>
    </div>

    {{-- Results --}}
    @if($activeTab === 'sheets')
        @forelse($sheets as $sheet)
            <div
                wire:key="sheet-{{ $sheet->id }}"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <button
                    type="button"
                    wire:click="toggleSheet({{ $sheet->id }})"
                    class="flex w-full items-center gap-3 p-4 text-right transition hover:bg-slate-50 sm:px-5"
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

                @if($expandedSheetId === $sheet->id)
                    <div class="border-t border-slate-100 bg-slate-50/60">
                        @if($expandedEntries->isEmpty())
                            <p class="p-6 text-center text-xs text-slate-400">هنوز کسی در این شیت ثبت نشده است.</p>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach($expandedEntries as $entry)
                                    <li class="flex items-center gap-3 px-4 py-2.5 sm:px-5">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-bold text-slate-800 sm:text-sm">{{ $entry->person_name }}</p>
                                            <p class="mt-0.5 text-[10px] text-slate-400 sm:text-[11px]">
                                                {{ $entry->person_code ?: '—' }}
                                                @if($entry->national_id) · {{ $fa($entry->national_id) }} @endif
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2 text-[10px] sm:text-[11px]">
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2 py-1 font-semibold text-sky-700">
                                                ورود {{ $fa(Jalalian::fromDateTime($entry->checked_in_at)->format('H:i')) }}
                                                <span class="text-sky-400">({{ $methodLabel($entry->check_in_method) }})</span>
                                            </span>
                                            @if($entry->checked_out_at)
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 font-semibold text-slate-600">
                                                    خروج {{ $fa(Jalalian::fromDateTime($entry->checked_out_at)->format('H:i')) }}
                                                    <span class="text-slate-400">({{ $methodLabel($entry->check_out_method) }})</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-1 font-bold text-emerald-700">
                                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                                                    حاضر
                                                </span>
                                            @endif
                                        </div>
                                    </li>
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
    @else
        {{-- Present now --}}
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
    @endif
</div>
