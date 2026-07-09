<div>
    <div x-data="jalaliDateTimeField($wire.entangle('date').live)">
        <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
        <div class="relative">
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
                placeholder="1405/04/03"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pe-11 text-sm font-medium text-slate-700 outline-none transition ltr:text-left focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
            >
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                <i class="bi bi-calendar2-event text-base"></i>
            </span>
        </div>
        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $hint ?? 'تاریخ با تقویم شمسی ثبت می‌شود' }}</p>
        @error('date') <p data-validation-error class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
