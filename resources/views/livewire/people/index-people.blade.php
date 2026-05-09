<div>
    <div class="container mx-auto p-4">
        <div class="rounded-2xl border bg-gradient-to-br from-white via-rose-50/30 to-white p-6 shadow-sm sm:p-7" style="border-color: #f5d0e1;">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">لیست مددجویان</h1>
                    <p class="mt-1 text-sm text-slate-500">جستجو و مدیریت افراد ثبت شده</p>
                </div>
                <div class="rounded-2xl border bg-white/90 px-5 py-3 shadow-sm" style="border-color: #f5d0e1;">
                    <p class="text-xs font-semibold text-slate-500">تعداد نمایش داده شده</p>
                    <p class="mt-1 text-center text-xl font-extrabold" style="color: #9D174D;">{{ number_format($this->people->total()) }}</p>
                </div>
            </div>

            <div class="mb-6 rounded-2xl border bg-white/70 p-4 sm:p-5" style="border-color: #f5d0e1;">
                <label for="beneficiary-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سریع</label>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="beneficiary-search-field"
                        wire:model.live="searchField"
                        class="w-full rounded-2xl border bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:outline-none focus:ring-4"
                        style="border-color: #f5d0e1;"
                        aria-label="معیار جستجو"
                    >
                        <option value="all">همه فیلدها</option>
                        <option value="person_code">کد مددجو</option>
                        <option value="full_name">نام و نام خانوادگی</option>
                        <option value="first_name">نام</option>
                        <option value="last_name">نام خانوادگی</option>
                        <option value="national_id">کد ملی</option>
                    </select>

                    <input
                        id="beneficiary-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4"
                        style="border-color: #f5d0e1;"
                        placeholder="عبارت جستجو را وارد کنید..."
                    >
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="text-white" style="background: linear-gradient(to left, #9D174D, #be185d);">
                            <tr>
                                <th class="px-5 py-4 text-center font-bold">ردیف</th>
                                <th class="px-5 py-4 text-center font-bold">کد مددجو</th>
                                <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                <th class="px-5 py-4 text-center font-bold">کد ملی</th>
                                <th class="px-5 py-4 text-center font-bold">تاریخ تولد</th>
                                <th class="px-5 py-4 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($this->people as $person)
                                <tr class="transition hover:bg-rose-50/70">
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $this->people->firstItem() + $loop->index }}</td>
                                    <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $person->person_code }}</td>
                                    <td class="px-5 py-4 text-right font-light text-slate-800">{{ $person->full_name }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->national_id }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                    <td class="px-5 py-4 text-center">
                                        @php
                                            $trackingTooltip = '<div class="tracking-tooltip-wrap" dir="rtl">'
                                                . '<div class="tracking-tooltip-title">رهگیری ثبت</div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> ایجادکننده </span><span class="value">' . e($person->creator?->name ?? 'نامشخص') . '</span></div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> زمان ایجاد </span><span class="value">' . e(optional($person->created_at)->format('Y/m/d H:i') ?? '-') . '</span></div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> آخرین ویرایش توسط </span><span class="value">' . e($person->updater?->name ?? $person->creator?->name ?? 'نامشخص') . '</span></div>'
                                                . '<div class="tracking-tooltip-row"><span class="label">زمان آخرین ویرایش </span><span class="value">' . e(optional($person->updated_at)->format('Y/m/d H:i') ?? '-') . '</span></div>'
                                                . '</div>';
                                        @endphp
                                        <div class="flex items-center justify-center gap-2 whitespace-nowrap">
                                            <button
                                                type="button"
                                                class="js-tracking-tooltip inline-flex h-9 w-9 items-center justify-center rounded-full border bg-white text-slate-600 shadow-sm transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                                style="border-color: #f5d0e1;"
                                                aria-label="رهگیری ثبت"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-html="true"
                                                data-bs-custom-class="beneficiary-tracking-tooltip"
                                                data-bs-title="{{ $trackingTooltip }}"
                                                x-data="{}"
                                                x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', sanitize: false, delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                            >
                                                <i class="bi bi-clock-history"></i>
                                            </button>

                                            <button
                                                wire:click="editPerson({{ $person->id }})"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                                aria-label="ویرایش کامل"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="ویرایش کامل اطلاعات مددجو"
                                                x-data="{}"
                                                x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <button
                                                wire:click="quickEditPerson({{ $person->id }})"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                                aria-label="ویرایش سریع"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="ویرایش سریع اطلاعات کلیدی"
                                                x-data="{}"
                                                x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                            >
                                                <i class="bi bi-lightning-charge"></i>
                                            </button>

                                            <button
                                                wire:click="deletePerson({{ $person->id }})"
                                                wire:confirm="آیا از انتقال این مددجو به بلاک لیست مطمئن هستید؟"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                                aria-label="حذف"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="انتقال به بلاک لیست"
                                                x-data="{}"
                                                x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                            >
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">هیچ مددجویی ثبت نشده است.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $this->people->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .tooltip.beneficiary-tracking-tooltip .tooltip-inner {
            max-width: 21rem;
            min-width: 17rem;
            text-align: right;
            direction: rtl;
            border-radius: 0.95rem;
            border: 1px solid #fecdd3;
            background: linear-gradient(180deg, #fff 0%, #fffafc 100%);
            color: #334155;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
            padding: 0.7rem 0.8rem;
            font-size: 0.74rem;
            line-height: 1.5;
        }

        .tooltip.beneficiary-tracking-tooltip .tooltip-arrow::before {
            border-top-color: #fecdd3;
            border-bottom-color: #fecdd3;
            border-left-color: #fecdd3;
            border-right-color: #fecdd3;
        }

        .tooltip .tooltip-inner {
            border-radius: 0.75rem;
            font-size: 0.72rem;
            background: #fff;
            color: #334155;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
            padding: 0.42rem 0.62rem;
        }

        .tooltip .tooltip-arrow::before {
            border-top-color: #e2e8f0;
            border-bottom-color: #e2e8f0;
            border-left-color: #e2e8f0;
            border-right-color: #e2e8f0;
        }

        .tracking-tooltip-wrap {
            display: grid;
            gap: 0.42rem;
        }

        .tracking-tooltip-title {
            margin-bottom: 0.1rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid #ffe4e6;
            font-size: 0.78rem;
            font-weight: 800;
            color: #9f1239;
        }

        .tracking-tooltip-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: baseline;
            font-size: 0.73rem;
        }

        .tracking-tooltip-row .label {
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
        }

        .tracking-tooltip-row .value {
            color: #0f172a;
            font-weight: 700;
            text-align: left;
            direction: ltr;
        }
    </style>
@endpush
