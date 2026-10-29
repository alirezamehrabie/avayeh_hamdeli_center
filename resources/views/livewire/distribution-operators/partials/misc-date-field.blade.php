<div class="min-w-0 max-w-full">
    <div x-data="jalaliDateTimeField($wire.entangle('date').live)">
        <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
        <div class="relative min-w-0 max-w-full">
            <input
                type="text"
                x-ref="input"
                x-model="draft"
                x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                x-on:jalali-picker-open="handlePickerOpen()"
                x-on:jalali-picker-close="handlePickerClose()"
                x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                readonly
                inputmode="none"
                autocomplete="off"
                data-jdp-readonly
                data-jdp
                placeholder="{{ \App\Helpers\Morilog\Jalalian::now()->format('Y/m/d') }}"
                class="min-h-12 w-full cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 py-3 pe-11 ps-4 text-sm font-medium tabular-nums text-slate-700 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
            >
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                <i class="bi bi-calendar2-event text-base"></i>
            </span>
        </div>
        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $hint ?? 'تاریخ با تقویم شمسی ثبت می‌شود' }}</p>
        @error('date') <p data-validation-error class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
