<div>
    <div class="container mx-auto p-4">
        <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/30 to-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">فهرست مددکاران تعلیق‌شده</h1>
                    <p class="mt-1 text-sm text-slate-500">لیست مددکاران حذف‌شده و امکان بازگردانی با کد مددکاری قبلی</p>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gradient-to-l from-amber-500 to-yellow-400 text-white">
                        <tr>
                            <th class="px-5 py-4 text-center font-bold">کد مددکاری</th>
                            <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                            <th class="px-5 py-4 text-center font-bold">کد ملی</th>
                            <th class="px-5 py-4 text-center font-bold">موبایل</th>
                            <th class="px-5 py-4 text-center font-bold">آمار تحت پوشش</th>
                            <th class="px-5 py-4 text-center font-bold">تاریخ حذف</th>
                            <th class="px-5 py-4 text-center font-bold">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($this->deletedSocialWorkers as $worker)
                            <tr class="transition hover:bg-amber-50/70">
                                <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $worker->worker_code }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="font-light text-slate-800">{{ $worker->full_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $worker->national_id }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $worker->mobile }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-800">{{ $worker->covered_people_count }} نفر</td>
                                <td class="px-5 py-4 text-center font-light text-slate-600">{{ $worker->deleted_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" wire:click="restoreSocialWorker({{ $worker->id }})" class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100">
                                            همکاری مجدد
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                    هیچ مددکار حذف‌شده‌ای وجود ندارد.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                {{ $this->deletedSocialWorkers->links() }}
            </div>
        </div>
    </div>
</div>
