<div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $log['actionColor'] }}">{{ $log['actionLabel'] }}</span>
            <span class="text-sm font-bold text-slate-800">{{ $log['personName'] }}</span>
            @if($log['personCode'])
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">{{ $log['personCode'] }}</span>
            @endif
        </div>
        <span class="text-xs text-slate-500">{{ $log['createdAt'] }}</span>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        @if($log['changedFieldsCount'] > 0)
            <span class="font-semibold text-slate-600">فیلدهای تغییرکرده:</span>
            @foreach($log['changedFields'] as $field)
                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $field }}</span>
            @endforeach
            @if($log['changedFieldsCount'] > count($log['changedFields']))
                <span class="rounded-full bg-slate-100 px-3 py-1">+{{ $log['changedFieldsCount'] - count($log['changedFields']) }} مورد دیگر</span>
            @endif
        @else
            <span>برای این عملیات جزئیات فیلدی ثبت نشده است.</span>
        @endif
    </div>
</div>
