<div>
    {{-- resources/views/livewire/social-workers/index-social-workers.blade.php --}}

    <div class="container mx-auto p-4">
        <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/30 to-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">لیست مددکاران اجتماعی</h1>
                    <p class="mt-1 text-sm text-slate-500">مدیریت اطلاعات مددکاران، کد مددکاری و راه‌های ارتباطی</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div wire:poll.5s class="rounded-2xl border border-emerald-100 bg-white/90 px-5 py-3 shadow-sm ring-1 ring-emerald-50 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                        <p class="text-xs font-semibold text-slate-500">تعداد مددکاران</p>
                        <div class="mt-1 flex items-center justify-center gap-3" dir="ltr">
                            <span class="relative flex h-3 w-3" aria-label="به‌روزرسانی زنده">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-3 w-3 animate-pulse rounded-full bg-emerald-500 shadow-sm shadow-emerald-300"></span>
                            </span>
                            <span class="text-xl font-extrabold tracking-tight text-emerald-600 iranyekan-bold">{{ number_format($totalSocialWorkers) }}</span>
                        </div>
                    </div>

                @if($embedded)
                    <button type="button" wire:click="createSocialWorker" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-300">
                        ثبت مددکار جدید
                    </button>
                @else
                    <a href="{{ route('social-workers.create') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-300">
                        ثبت مددکار جدید
                    </a>
                @endif
                </div>
            </div>

            <div class="mb-5">
                <label for="social-worker-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سریع</label>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="social-worker-search-field"
                        wire:model.live="searchField"
                        class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                        aria-label="معیار جستجو"
                    >
                        <option value="all">همه فیلدها</option>
                        <option value="worker_code">کد مددکاری</option>
                        <option value="full_name">نام و نام خانوادگی</option>
                        <option value="first_name">نام</option>
                        <option value="last_name">نام خانوادگی</option>
                        <option value="national_id">کد ملی</option>
                        <option value="mobile">موبایل</option>
                    </select>

                    <div class="relative">
                        <input
                            id="social-worker-search"
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="w-full rounded-2xl border border-amber-200 bg-white px-10 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            placeholder="عبارت جستجو را وارد کنید..."
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-amber-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-5.4a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                            </svg>
                        </span>
                    </div>
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
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
                            <th class="px-5 py-4 text-center font-bold">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($this->socialWorkers as $worker)
                            <tr class="transition hover:bg-amber-50/70">
                                <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $worker->worker_code }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="font-light text-slate-800">{{ $worker->full_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $worker->national_id }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $worker->mobile }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-800">{{ $worker->covered_people_count }} نفر</td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($embedded)
                                            <button type="button" wire:click="editSocialWorker({{ $worker->id }})" class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:border-amber-300 hover:bg-amber-100">
                                                ویرایش
                                            </button>
                                        @else
                                            <a href="{{ route('social-workers.edit', $worker) }}" class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:border-amber-300 hover:bg-amber-100">
                                                ویرایش
                                            </a>
                                        @endif

                                        <button type="button" wire:click="deleteSocialWorker({{ $worker->id }})" wire:confirm="آیا از حذف این مددکار مطمئن هستید؟" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                            حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                    @if($search)
                                        نتیجه‌ای برای این جستجو پیدا نشد.
                                    @else
                                        هنوز مددکاری ثبت نشده است.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                {{ $this->socialWorkers->links() }}
            </div>
        </div>
    </div>
</div>
