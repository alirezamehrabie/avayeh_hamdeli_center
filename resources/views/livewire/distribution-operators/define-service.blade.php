<div class="space-y-6">
    @if($canManageServices)
        @include('livewire.distribution-operators.partials.latest-misc-service-card')

        <livewire:distribution-operators.service-batch-creator />
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white px-4 py-8 text-center shadow-sm">
            <p class="text-sm font-black text-slate-700">بخشی برای نمایش وجود ندارد</p>
            <p class="mt-1 text-xs font-medium text-slate-400">برای ادامه، از منوی کنار یکی از بخش‌ها را انتخاب کنید.</p>
        </div>
    @endif
</div>
