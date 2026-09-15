<div>
    <div class="container mx-auto p-0">
        @php
            $guardians = $this->deletedGuardians;
        @endphp

        {{-- هویت رنگی بخش: amber (سرپرست) --}}
        <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/30 to-white p-3 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-l from-amber-500 to-yellow-400 text-white shadow-sm sm:flex">
                        <i class="bi bi-person-x-fill text-base"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl lg:text-2xl">سرپرستان غیرفعال</h1>
                            <span class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-100">
                                {{ number_format($guardians->total()) }} مورد
                            </span>
                        </div>
                        <p class="mt-1 hidden text-sm text-slate-500 sm:block">سرپرستان حذف‌شده به‌همراه کل اعضای خانواده؛ بازیابی، کل خانوار را با همان کدهای قبلی برمی‌گرداند</p>
                    </div>
                </div>

                @if ($appliedNationalIdSearch !== '')
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm sm:mb-1">
                        <i class="bi bi-search text-[10px] text-slate-400"></i>
                        نتایج جستجو برای
                        <span class="font-mono" dir="ltr">{{ $appliedNationalIdSearch }}</span>
                    </span>
                @endif
            </div>

            {{-- جستجو با کد ملی سرپرست --}}
            <div class="mt-4 rounded-2xl border border-amber-100/80 bg-white/80 p-3 sm:p-4">
                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="guardian-national-id-search" class="mb-2 block text-sm font-bold text-slate-700">جستجو با کد ملی سرپرست</label>
                        <input
                            id="guardian-national-id-search"
                            type="text"
                            wire:model.defer="nationalIdSearch"
                            wire:keydown.enter="searchByNationalId"
                            inputmode="numeric"
                            maxlength="10"
                            dir="ltr"
                            placeholder="کد ملی ۱۰ رقمی"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-4 focus:ring-amber-100"
                        >
                    </div>
                    <button
                        type="button"
                        wire:click="searchByNationalId"
                        wire:loading.attr="disabled"
                        wire:target="searchByNationalId"
                        class="inline-flex min-h-12 w-full items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-amber-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-100 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-[52px] sm:w-auto"
                    >
                        <i class="bi bi-search text-xs" wire:loading.remove wire:target="searchByNationalId"></i>
                        <span wire:loading.remove wire:target="searchByNationalId">جستجو</span>
                        <span wire:loading wire:target="searchByNationalId" class="inline-flex items-center gap-2">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            در حال جستجو...
                        </span>
                    </button>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($guardians->isEmpty())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm ring-1 ring-slate-100">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                        <i class="bi {{ $appliedNationalIdSearch !== '' ? 'bi-search' : 'bi-inbox' }} text-2xl"></i>
                    </div>
                    <h2 class="mt-4 text-base font-extrabold text-slate-800">
                        @if ($appliedNationalIdSearch !== '')
                            سرپرستی با این کد ملی در فهرست غیرفعال‌ها یافت نشد
                        @else
                            هیچ سرپرست غیرفعالی وجود ندارد
                        @endif
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        @if ($appliedNationalIdSearch !== '')
                            کد ملی دیگری وارد کنید و دوباره جستجو کنید؛ جستجو با فیلد خالی، کل فهرست را نمایش می‌دهد.
                        @else
                            سرپرستانی که با کل خانواده حذف می‌شوند اینجا فهرست می‌شوند؛ تا زمانی موردی حذف نشده، این فهرست خالی است.
                        @endif
                    </p>
                </div>
            @else
                {{-- نمای موبایل: کارت‌های عمودی --}}
                <div class="mt-4 space-y-2.5 md:hidden">
                    @foreach ($guardians as $guardian)
                        <article wire:key="blocked-guardian-card-{{ $guardian->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                            <div class="px-3 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <h2 class="truncate text-sm font-extrabold text-slate-900">{{ trim($guardian->first_name . ' ' . $guardian->last_name) ?: 'بدون نام' }}</h2>
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-500">
                                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-amber-700" dir="ltr">{{ $guardian->national_code ?: '-' }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $guardian->guardian_phone_number ?: '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-left">
                                        <p class="text-[10px] font-semibold text-slate-400">تاریخ حذف</p>
                                        <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-slate-700" dir="ltr">{{ $guardian->deleted_at?->format('Y/m/d H:i') ?? '-' }}</p>
                                    </div>
                                </div>

                                <dl class="mt-3 grid grid-cols-2 gap-2 text-right">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <dt class="text-[10px] font-bold text-slate-400">مددجویان حذف‌شده</dt>
                                        <dd class="mt-1 text-xs font-bold text-slate-700">{{ number_format((int) $guardian->people_count) }} نفر</dd>
                                    </div>
                                    <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2">
                                        <dt class="text-[10px] font-bold text-slate-400">علت حذف</dt>
                                        <dd class="mt-1 truncate text-xs font-bold text-slate-700">{{ $guardian->deletion_reason ?: '—' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                                <button
                                    type="button"
                                    wire:click="restoreFamily({{ $guardian->id }})"
                                    wire:confirm="سرپرست و همه اعضای خانواده با همان کدهای قبلی بازیابی شوند؟"
                                    wire:loading.attr="disabled"
                                    wire:target="restoreFamily({{ $guardian->id }})"
                                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <i class="bi bi-arrow-counterclockwise text-sm" wire:loading.remove wire:target="restoreFamily({{ $guardian->id }})"></i>
                                    <span wire:loading.remove wire:target="restoreFamily({{ $guardian->id }})">بازیابی خانوار</span>
                                    <span wire:loading wire:target="restoreFamily({{ $guardian->id }})" class="inline-flex items-center gap-2">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        در حال بازیابی...
                                    </span>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- نمای دسکتاپ: جدول --}}
                <div class="mt-4 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 md:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead class="bg-gradient-to-l from-amber-500 to-yellow-400 text-white">
                                <tr>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد ملی سرپرست</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">موبایل</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">مددجویان حذف‌شده</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-right font-bold">علت حذف</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">تاریخ حذف</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">بازیابی خانوار</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($guardians as $guardian)
                                    <tr wire:key="blocked-guardian-row-{{ $guardian->id }}" class="transition hover:bg-amber-50/70">
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $guardian->national_code ?: '-' }}</td>
                                        <td class="px-5 py-4 text-right font-bold text-slate-800">{{ trim($guardian->first_name . ' ' . $guardian->last_name) ?: 'بدون نام' }}</td>
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $guardian->guardian_phone_number ?: '-' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center">
                                            <span class="font-bold tabular-nums text-slate-800">{{ number_format((int) $guardian->people_count) }}</span>
                                            <span class="text-xs text-slate-500">نفر</span>
                                        </td>
                                        <td class="px-5 py-4 text-right text-slate-700">
                                            @if (filled($guardian->deletion_reason))
                                                <div class="max-w-[240px] truncate" title="{{ $guardian->deletion_reason }}">{{ $guardian->deletion_reason }}</div>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $guardian->deleted_at?->format('Y/m/d H:i') ?? '-' }}</td>
                                        <td class="px-5 py-4 text-center">
                                            <button
                                                type="button"
                                                wire:click="restoreFamily({{ $guardian->id }})"
                                                wire:confirm="سرپرست و همه اعضای خانواده با همان کدهای قبلی بازیابی شوند؟"
                                                wire:loading.attr="disabled"
                                                wire:target="restoreFamily({{ $guardian->id }})"
                                                class="inline-flex min-h-10 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                <i class="bi bi-arrow-counterclockwise" wire:loading.remove wire:target="restoreFamily({{ $guardian->id }})"></i>
                                                <span wire:loading.remove wire:target="restoreFamily({{ $guardian->id }})">بازیابی خانوار</span>
                                                <span wire:loading wire:target="restoreFamily({{ $guardian->id }})" class="inline-flex items-center gap-1.5">
                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                    در حال بازیابی...
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    {{ $guardians->links('vendor.livewire.tailwind-mobile-persian') }}
                </div>
            @endif
        </div>
    </div>
</div>
