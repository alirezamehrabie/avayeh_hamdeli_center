<div>
    <div class="container mx-auto p-4">
        <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-white via-rose-50/30 to-white p-6 shadow-sm sm:p-7">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">بلاک لیست سرپرستان</h1>
                    <p class="mt-1 text-sm text-slate-500">سرپرستان حذف‌شده به همراه کل اعضای خانواده و امکان بازیابی یک‌جای خانوار</p>
                </div>
                <div class="rounded-2xl border border-rose-100 bg-white/90 px-5 py-3 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">تعداد خانوارهای حذف‌شده</p>
                    <p class="mt-1 text-center text-xl font-extrabold text-rose-700">{{ number_format($this->deletedGuardians->total()) }}</p>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-5 rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                    <div class="w-full lg:max-w-sm">
                        <label for="guardian-national-id-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجو با کد ملی سرپرست</label>
                        <input
                            id="guardian-national-id-search"
                            type="text"
                            wire:model.defer="nationalIdSearch"
                            wire:keydown.enter="searchByNationalId"
                            inputmode="numeric"
                            dir="ltr"
                            placeholder="مثلاً 12345678910"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-rose-300 focus:ring focus:ring-rose-100"
                        >
                    </div>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <button
                            type="button"
                            wire:click="searchByNationalId"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700"
                        >
                            جستجو
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gradient-to-l from-rose-600 to-pink-500 text-white">
                            <tr>
                                <th class="px-5 py-4 text-center font-bold">کد ملی سرپرست</th>
                                <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                <th class="px-5 py-4 text-center font-bold">موبایل</th>
                                <th class="px-5 py-4 text-center font-bold">مددجویان حذف‌شده</th>
                                <th class="px-5 py-4 text-right font-bold">علت حذف</th>
                                <th class="px-5 py-4 text-center font-bold">تاریخ حذف</th>
                                <th class="px-5 py-4 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($this->deletedGuardians as $guardian)
                                <tr class="transition hover:bg-rose-50/70">
                                    <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $guardian->national_code }}</td>
                                    <td class="px-5 py-4 text-right font-light text-slate-800">{{ trim($guardian->first_name . ' ' . $guardian->last_name) }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $guardian->guardian_phone_number ?? '-' }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-800">{{ $guardian->people_count }} نفر</td>
                                    <td class="px-5 py-4 text-right font-light text-slate-700">{{ $guardian->deletion_reason ?? '-' }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $guardian->deleted_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            type="button"
                                            wire:click="restoreFamily({{ $guardian->id }})"
                                            class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100"
                                        >
                                            بازیابی خانوار
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                        @if ($appliedNationalIdSearch !== '')
                                            سرپرستی با این کد ملی یافت نشد.
                                        @else
                                            هیچ سرپرست حذف‌شده‌ای وجود ندارد.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $this->deletedGuardians->links() }}
            </div>
        </div>
    </div>
</div>
