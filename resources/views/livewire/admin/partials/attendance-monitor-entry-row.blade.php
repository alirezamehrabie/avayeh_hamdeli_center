{{-- resources/views/livewire/admin/partials/attendance-monitor-entry-row.blade.php --}}
{{-- Expects: $entry (AttendanceSheetEntry) --}}
@php
    use App\Helpers\Morilog\Jalalian;
    use App\Helpers\Morilog\CalendarUtils;

    $fa = fn ($value) => CalendarUtils::convertNumbers((string) $value);
    $methodLabel = fn ($method) => $method === 'qr' ? 'QR' : 'دستی';
@endphp
<li class="flex items-center gap-3 px-4 py-2.5 sm:px-5">
    <div class="min-w-0 flex-1">
        <p class="truncate text-xs font-bold text-slate-800 sm:text-sm">{{ $entry->person_name }}</p>
        <p class="mt-0.5 text-[10px] text-slate-400 sm:text-[11px]">
            {{ $entry->person_code ?: '—' }}
            @if($entry->national_id) · {{ $fa($entry->national_id) }} @endif
        </p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5 text-[10px] sm:gap-2 sm:text-[11px]">
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
