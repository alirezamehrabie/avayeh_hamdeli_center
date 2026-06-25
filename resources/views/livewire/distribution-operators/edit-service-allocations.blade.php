<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('distribution-operator.service-list') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-black text-slate-800">ویرایش تخصیص‌های خدمت</h1>
            <p class="text-xs font-semibold text-slate-500">بازگشت به <span class="text-violet-600">فهرست خدمات</span></p>
        </div>
    </div>

    <livewire:distribution-operators.service-allocation-editor :editing-service-id="$serviceId" :key="'edit-allocations-' . $serviceId" />
</div>
