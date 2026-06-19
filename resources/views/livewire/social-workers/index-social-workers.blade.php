<div>
    {{-- resources/views/livewire/social-workers/index-social-workers.blade.php --}}
    @php
        $socialWorkers = $this->socialWorkers;
        $searchNeedsMoreInput = $this->searchNeedsMoreInput();
    @endphp

    <div class="container mx-auto p-4">
        <div class="rounded-2xl border bg-gradient-to-br from-white to-cyan-50/30 p-5 shadow-sm" style="border-color: #bfe9f8;">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">لیست مددکاران اجتماعی</h1>
                    <p class="mt-1 text-sm text-slate-500">مدیریت اطلاعات مددکاران، کد مددکاری و راه‌های ارتباطی</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-2xl border bg-white/90 px-5 py-3 shadow-sm ring-1 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md" style="border-color: #cfeefb;">
                        <p class="text-xs font-semibold text-slate-500">تعداد مددکاران</p>
                        <div class="mt-1 flex items-center justify-center gap-3" dir="ltr">
                            <span class="text-xl font-extrabold tracking-tight iranyekan-bold" style="color: #1d9dcf;">{{ number_format($totalSocialWorkers) }}</span>
                        </div>
                    </div>

                @if($embedded)
                    <button type="button" wire:click="createSocialWorker" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2" style="background-color: #53BEEA;">
                        ثبت مددکار جدید
                    </button>
                @else
                    <a href="{{ route('social-workers.create') }}" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2" style="background-color: #53BEEA;">
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
                        class="w-full rounded-2xl border bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:outline-none focus:ring-4"
                        style="border-color: #bfe9f8;"
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
                            wire:model.live.debounce.600ms="search"
                            class="w-full rounded-2xl border bg-white px-10 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4"
                            style="border-color: #bfe9f8;"
                            placeholder="عبارت جستجو را وارد کنید..."
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center" style="color: #53BEEA;">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-5.4a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                            </svg>
                        </span>
                    </div>
                </div>
                @if($searchNeedsMoreInput)
                    <div class="mt-2.5 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-800 sm:text-xs">
                        برای جستجوی متنی حداقل ۲ کاراکتر وارد کنید.
                    </div>
                @endif
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="space-y-3 md:hidden">
                @forelse ($socialWorkers as $worker)
                    <article wire:key="social-worker-card-{{ $worker->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                        <button
                            type="button"
                            wire:click="toggleSocialWorker({{ $worker->id }})"
                            class="block w-full px-4 py-4 text-right transition hover:bg-cyan-50/50 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</h2>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-500">
                                        <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-cyan-700" dir="ltr">{{ $worker->worker_code ?: '-' }}</span>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $worker->national_id ?: '-' }}</span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-left">
                                    <p class="text-[10px] font-semibold text-slate-400">تحت پوشش</p>
                                    <p class="mt-0.5 text-xs font-extrabold text-slate-800">{{ $this->getCoveredCountForWorker($worker) }} نفر</p>
                                </div>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-2 text-right">
                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                    <dt class="text-[10px] font-bold text-slate-400">موبایل</dt>
                                    <dd class="mt-1 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $worker->mobile ?: '-' }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                    <dt class="text-[10px] font-bold text-slate-400">وضعیت</dt>
                                    <dd class="mt-1 text-xs font-bold text-slate-700">{{ $expandedSocialWorkerId === $worker->id ? 'باز شده' : 'بسته' }}</dd>
                                </div>
                            </dl>
                        </button>

                        <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" wire:click.stop="toggleSocialWorker({{ $worker->id }})" class="inline-flex min-h-9 items-center justify-center rounded-xl border border-cyan-200 bg-white px-2 text-xs font-bold text-cyan-700 transition hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                                    {{ $expandedSocialWorkerId === $worker->id ? 'بستن' : 'سرپرستان' }}
                                </button>
                                @if($embedded)
                                    <button type="button" wire:click.stop="editSocialWorker({{ $worker->id }})" class="inline-flex min-h-9 items-center justify-center rounded-xl border px-2 text-xs font-bold transition focus:outline-none focus:ring-2" style="border-color: #bfe9f8; background-color: #eff9fd; color: #1d9dcf;">
                                        ویرایش
                                    </button>
                                @else
                                    <a href="{{ route('social-workers.edit', $worker) }}" class="inline-flex min-h-9 items-center justify-center rounded-xl border px-2 text-xs font-bold transition" style="border-color: #bfe9f8; background-color: #eff9fd; color: #1d9dcf;">
                                        ویرایش
                                    </a>
                                @endif
                                <button type="button" wire:click.stop="deleteSocialWorker({{ $worker->id }})" wire:confirm="آیا از حذف این مددکار مطمئن هستید؟" class="inline-flex min-h-9 items-center justify-center rounded-xl border border-rose-200 bg-white px-2 text-xs font-bold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    حذف
                                </button>
                            </div>
                        </div>

                        @if($expandedSocialWorkerId === $worker->id)
                            @php
                                $guardians = $this->getGuardiansForWorker($worker->id);
                                $coveredDetailsLoaded = $this->hasLoadedCoveredDetailsForWorker($worker->id);
                                $coveredDetails = $this->getCoveredDetailsForWorker($worker->id);
                            @endphp
                            <div class="space-y-4 border-t border-cyan-100 bg-cyan-50/30 p-3" wire:init="loadCoveredDetailsForWorker({{ $worker->id }})">
                                <section class="rounded-2xl border border-cyan-100 bg-white p-3">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h3 class="text-xs font-extrabold text-slate-700">سرپرستان تحت پوشش</h3>
                                        <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[10px] font-bold text-cyan-700">{{ count($guardians) }} سرپرست</span>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse($guardians as $guardian)
                                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="min-w-0 truncate text-xs font-extrabold text-slate-800">{{ trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')) ?: '-' }}</p>
                                                    <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $guardian['people_count'] }} نفر</span>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-bold text-slate-500">
                                                    <span class="rounded-full bg-white px-2 py-0.5" dir="ltr">{{ $guardian['national_code'] ?: '-' }}</span>
                                                    <span class="rounded-full bg-white px-2 py-0.5" dir="ltr">{{ $guardian['guardian_phone_number'] ?: '-' }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">سرپرستی برای این مددکار ثبت نشده است.</p>
                                        @endforelse
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-emerald-100 bg-white p-3">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h3 class="text-xs font-extrabold text-slate-700">جزئیات آمار تحت پوشش</h3>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-700">{{ $this->getCoveredCountForWorker($worker) }} نفر</span>
                                    </div>

                                    @if(! $coveredDetailsLoaded)
                                        <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">در حال بارگذاری جزئیات...</p>
                                    @else
                                        <div class="space-y-2">
                                            @forelse($coveredDetails as $detail)
                                                @php
                                                    $details = $coveredDetails;
                                                    $currentGuardianGroup = $detail['guardian_group'] ?? '-';
                                                    $previousGuardianGroup = $loop->index > 0 ? ($details[$loop->index - 1]['guardian_group'] ?? '-') : null;
                                                    $isNewSourceGroup = $loop->first || $currentGuardianGroup !== $previousGuardianGroup;
                                                @endphp
                                                @if($isNewSourceGroup)
                                                    <p class="rounded-xl bg-cyan-50 px-3 py-2 text-[10px] font-extrabold text-cyan-800">سرپرست مشترک: {{ $currentGuardianGroup }}</p>
                                                @endif
                                                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="min-w-0 truncate text-xs font-extrabold text-slate-800">{{ $detail['name'] ?: '-' }}</p>
                                                        <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700">{{ $detail['role_label'] }}</span>
                                                    </div>
                                                    <p class="mt-1 text-[10px] font-bold text-slate-500" dir="ltr">{{ $detail['national_id'] }}</p>
                                                    <p class="mt-2 text-[10px] leading-5 text-slate-600">{{ implode('، ', $detail['sources']) }}</p>
                                                </div>
                                            @empty
                                                <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">موردی برای نمایش ثبت نشده است.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                </section>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm font-bold text-slate-500 shadow-sm">
                        @if($search)
                            نتیجه‌ای برای این جستجو پیدا نشد.
                        @else
                            هنوز مددکاری ثبت نشده است.
                        @endif
                    </div>
                @endforelse
            </div>

            <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="text-white" style="background: linear-gradient(to left, #53BEEA, #39addc);">
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
                        @forelse ($socialWorkers as $worker)
                            <tr
                                wire:key="social-worker-{{ $worker->id }}"
                                class="transition hover:bg-slate-50"
                            >
                                <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $worker->worker_code }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="font-light text-slate-800">{{ $worker->full_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $worker->national_id }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $worker->mobile }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-800">{{ $this->getCoveredCountForWorker($worker) }} نفر</td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" wire:click="toggleSocialWorker({{ $worker->id }})" class="inline-flex items-center justify-center rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                                            {{ $expandedSocialWorkerId === $worker->id ? 'بستن' : 'سرپرستان' }}
                                        </button>
                                        @if($embedded)
                                            <button type="button" wire:click.stop="editSocialWorker({{ $worker->id }})" class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition" style="border-color: #bfe9f8; background-color: #eff9fd; color: #1d9dcf;">
                                                ویرایش
                                            </button>
                                        @else
                                            <a href="{{ route('social-workers.edit', $worker) }}" wire:click.stop class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition" style="border-color: #bfe9f8; background-color: #eff9fd; color: #1d9dcf;">
                                                ویرایش
                                            </a>
                                        @endif

                                        <button type="button" wire:click.stop="deleteSocialWorker({{ $worker->id }})" wire:confirm="آیا از حذف این مددکار مطمئن هستید؟" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                            حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if($expandedSocialWorkerId === $worker->id)
                                @php
                                    $guardians = $this->getGuardiansForWorker($worker->id);
                                    $coveredDetailsLoaded = $this->hasLoadedCoveredDetailsForWorker($worker->id);
                                    $coveredDetails = $this->getCoveredDetailsForWorker($worker->id);
                                @endphp
                                <tr class="bg-cyan-50/40" wire:key="social-worker-panel-{{ $worker->id }}">
                                    <td colspan="6" class="px-5 py-4">
                                        <div
                                            x-data="{ show: false }"
                                            x-init="$nextTick(() => show = true)"
                                            x-show="show"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                                            class="rounded-2xl border border-cyan-100 bg-white p-4 shadow-sm"
                                        >
                                            <div class="mb-3 flex items-center justify-between">
                                                <h2 class="text-sm font-bold text-slate-700">سرپرستان تحت پوشش {{ $worker->full_name }}</h2>
                                                <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">{{ count($guardians) }} سرپرست</span>
                                            </div>

                                            <div class="overflow-x-auto" wire:init="loadCoveredDetailsForWorker({{ $worker->id }})">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-50 text-slate-600">
                                                        <tr>
                                                            <th class="px-4 py-3 text-center font-bold">ردیف</th>
                                                            <th class="px-4 py-3 text-center font-bold">کد ملی سرپرست</th>
                                                            <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی سرپرست</th>
                                                            <th class="px-4 py-3 text-center font-bold">موبایل سرپرست</th>
                                                            <th class="px-4 py-3 text-center font-bold">تعداد مددجویان تحت سرپرستی</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @forelse ($guardians as $guardian)
                                                            <tr class="transition hover:bg-slate-50">
                                                                <td class="px-4 py-3 text-center font-medium text-slate-700">{{ $loop->iteration }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-600">{{ $guardian['national_code'] ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-right text-slate-700">{{ trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')) ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-600">{{ $guardian['guardian_phone_number'] ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-600">{{ $guardian['people_count'] }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">سرپرستی برای این مددکار ثبت نشده است.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="mt-6 mb-3 flex items-center justify-between">
                                                <h2 class="text-sm font-bold text-slate-700">جزئیات افراد مؤثر در آمار تحت پوشش</h2>
                                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    {{ $this->getCoveredCountForWorker($worker) }} نفر (بر پایه کد ملی)
                                                </span>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-50 text-slate-600">
                                                        <tr>
                                                            <th class="px-4 py-3 text-center font-bold">ردیف</th>
                                                            <th class="px-4 py-3 text-center font-bold">کد ملی</th>
                                                            <th class="px-4 py-3 text-right font-bold">نام</th>
                                                            <th class="px-4 py-3 text-center font-bold">دسته</th>
                                                            <th class="px-4 py-3 text-right font-bold">منبع ثبت</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @if(! $coveredDetailsLoaded)
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                                                    Ø¯Ø± Ø­Ø§Ù„ Ø¨Ø§Ø±Ú¯Ø°Ø§Ø±ÛŒ Ø¬Ø²Ø¦ÛŒØ§Øª...
                                                                </td>
                                                            </tr>
                                                        @else
                                                        @forelse ($coveredDetails as $detail)
                                                            @php
                                                                $details = $coveredDetails;
                                                                $currentGuardianGroup = $detail['guardian_group'] ?? '-';
                                                                $previousGuardianGroup = $loop->index > 0 ? ($details[$loop->index - 1]['guardian_group'] ?? '-') : null;
                                                                $isNewSourceGroup = $loop->first || $currentGuardianGroup !== $previousGuardianGroup;
                                                            @endphp
                                                            @if($isNewSourceGroup)
                                                                <tr class="bg-cyan-50/70">
                                                                    <td colspan="5" class="px-4 py-2.5 text-right text-[11px] font-bold text-cyan-800">
                                                                        سرپرست مشترک: {{ $currentGuardianGroup }}
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                            <tr class="transition hover:bg-slate-50">
                                                                <td class="px-4 py-3 text-center font-medium text-slate-700">{{ $loop->iteration }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-700">{{ $detail['national_id'] }}</td>
                                                                <td class="px-4 py-3 text-right text-slate-700">{{ $detail['name'] ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                                                        {{ $detail['role_label'] }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-right text-slate-600">{{ implode('، ', $detail['sources']) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                                                    موردی برای نمایش ثبت نشده است.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
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

            <div class="mt-3">
                {{ $socialWorkers->links('vendor.livewire.tailwind-mobile-persian') }}
            </div>
        </div>
    </div>
</div>
