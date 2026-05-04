<div>
    <div class="container mx-auto p-4">
        <div class="rounded-2xl border bg-gradient-to-br from-white via-rose-50/30 to-white p-6 shadow-sm sm:p-7" style="border-color: #f5d0e1;">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">بلاک لیست مددجویان</h1>
                    <p class="mt-1 text-sm text-slate-500">مشاهده مددجویان حذف‌شده و بازیابی نظارت با کد مددجوی قبلی</p>
                </div>
                <div class="rounded-2xl border bg-white/90 px-5 py-3 shadow-sm" style="border-color: #f5d0e1;">
                    <p class="text-xs font-semibold text-slate-500">تعداد موارد</p>
                    <p class="mt-1 text-center text-xl font-extrabold" style="color: #9D174D;">{{ number_format($this->people->total()) }}</p>
                </div>
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
                                <th class="px-5 py-4 text-center font-bold">کد مددجو</th>
                                <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                <th class="px-5 py-4 text-center font-bold">کد ملی</th>
                                <th class="px-5 py-4 text-center font-bold">تاریخ تولد</th>
                                <th class="px-5 py-4 text-center font-bold">جنسیت</th>
                                <th class="px-5 py-4 text-center font-bold">تاریخ حذف</th>
                                <th class="px-5 py-4 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($this->people as $person)
                                <tr class="transition hover:bg-rose-50/70">
                                    <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $person->person_code }}</td>
                                    <td class="px-5 py-4 text-right font-light text-slate-800">{{ $person->full_name }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->national_id }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">
                                        @if($person->gender == 'male')
                                            مرد
                                        @elseif($person->gender == 'female')
                                            زن
                                        @else
                                            نامشخص
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->deleted_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <button wire:click="restoreSupervision({{ $person->id }})" class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100">
                                            بازیابی نظارت
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">هیچ مددجوی حذف‌شده‌ای وجود ندارد.</td>
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
