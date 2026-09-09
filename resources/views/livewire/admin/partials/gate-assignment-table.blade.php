{{-- Gate authorization table (shared by the delivered section and the integrity
     checks). $rows: paginator of GateEntryAssignment with relations loaded;
     $showLedger: add the Exit ledger time column;
     $collapsedListRows: initial visible row count before the expand toggle. --}}
@php
    $visibleLimit = $collapsedListRows ?? PHP_INT_MAX;
    $hiddenRowCount = max(0, count($rows) - $visibleLimit);
@endphp

<x-gate.collapsible-list :hidden-count="$hiddenRowCount">
<div class="overflow-x-auto">
    <table class="w-full min-w-[720px] text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-slate-500">
                <th class="px-4 py-3 text-right text-xs font-bold">گیرنده</th>
                <th class="px-4 py-3 text-right text-xs font-bold">دسته‌بندی</th>
                <th class="px-4 py-3 text-center text-xs font-bold">زمان ثبت مجوز (ورود)</th>
                <th class="px-4 py-3 text-center text-xs font-bold">زمان تحویل (Delivery)</th>
                <th class="px-4 py-3 text-center text-xs font-bold">اپراتور تحویل</th>
                <th class="px-4 py-3 text-center text-xs font-bold">وضعیت</th>
                @if($showLedger)
                    <th class="px-4 py-3 text-center text-xs font-bold">زمان ثبت در دفترچه خروج</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($rows as $assignment)
                <tr class="align-top transition hover:bg-slate-50/80" @if($loop->index >= $visibleLimit) x-show="listOpen" x-cloak x-transition.opacity.duration.300ms @endif>
                    <td class="px-4 py-3">
                        <p class="font-bold text-slate-900">{{ $assignment->recipient_name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $assignment->national_id ?: '—' }}
                            @if($assignment->person?->person_code)
                                · کد مددجو: {{ $assignment->person->person_code }}
                            @elseif($assignment->guardian?->guardian_code)
                                · کد خانوار: {{ $assignment->guardian->guardian_code }}
                            @endif
                        </p>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $assignment->serviceCategory?->name ?: '—' }}</td>
                    <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $jalaliDateTime($assignment->assigned_at) }}</td>
                    <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $jalaliDateTime($assignment->delivered_at) }}</td>
                    <td class="px-4 py-3 text-center text-xs text-slate-600">
                        {{ trim((string) ($assignment->deliveredBy?->first_name.' '.$assignment->deliveredBy?->last_name)) ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php $label = $statusLabels[$assignment->status] ?? $assignment->status; @endphp
                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ match ($assignment->status) {
                            \App\Models\GateEntryAssignment::STATUS_PENDING => 'bg-amber-100 text-amber-700',
                            \App\Models\GateEntryAssignment::STATUS_DELIVERED => 'bg-indigo-100 text-indigo-700',
                            default => 'bg-emerald-100 text-emerald-700',
                        } }}">{{ $label }}</span>
                    </td>
                    @if($showLedger)
                        <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $jalaliDateTime($assignment->delivery?->delivered_at) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-gate.collapsible-list>
