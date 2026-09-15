<div>
    <div class="container mx-auto p-0">
        @php
            $people = $this->people;
        @endphp

        {{-- هویت رنگی بخش: rose (مددجو) --}}
        <div class="rounded-2xl border border-rose-100/80 bg-gradient-to-br from-white via-rose-50/40 to-white p-3 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-l from-rose-700 to-pink-700 text-white shadow-sm sm:flex">
                        <i class="bi bi-slash-circle text-base"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl lg:text-2xl">بلاک‌لیست مددجویان</h1>
                            <span class="inline-flex items-center whitespace-nowrap rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100">
                                {{ number_format($people->total()) }} مورد
                            </span>
                        </div>
                        <p class="mt-1 hidden text-sm text-slate-500 sm:block">مددجویان حذف‌شده را ببینید و در صورت نیاز، نظارت را با همان کد مددجوی قبلی بازیابی کنید</p>
                    </div>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($people->isEmpty())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm ring-1 ring-slate-100">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                        <i class="bi bi-inbox text-2xl"></i>
                    </div>
                    <h2 class="mt-4 text-base font-extrabold text-slate-800">هیچ مددجویی در بلاک‌لیست نیست</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        مددجویانی که از سامانه حذف می‌شوند اینجا فهرست می‌شوند؛ تا زمانی موردی حذف نشده، این فهرست خالی است.
                    </p>
                </div>
            @else
                {{-- نمای موبایل: کارت‌های عمودی --}}
                <div class="mt-4 space-y-2.5 md:hidden">
                    @foreach ($people as $person)
                        <article wire:key="blocked-person-card-{{ $person->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                            <div class="px-3 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $person->full_name ?: 'بدون نام' }}</h2>
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                            <span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-rose-700" dir="ltr">{{ $person->person_code }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $person->national_id ?: '-' }}</span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $person->gender === 'female' ? 'bg-rose-400' : 'bg-sky-400' }}" aria-hidden="true"></span>
                                                {{ $person->gender_label ?: 'نامشخص' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-left">
                                        <p class="text-[10px] font-semibold text-slate-400">تاریخ حذف</p>
                                        <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-slate-700" dir="ltr">{{ $person->deleted_at?->format('Y/m/d H:i') ?? '-' }}</p>
                                    </div>
                                </div>

                                <dl class="mt-3 flex items-center gap-4 text-right">
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-semibold text-slate-400">سرپرست</dt>
                                        <dd class="mt-0.5 truncate text-xs font-bold text-slate-700">{{ trim(($person->guardian?->first_name ?? '') . ' ' . ($person->guardian?->last_name ?? '')) ?: '—' }}</dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-semibold text-slate-400">تولد</dt>
                                        <dd class="mt-0.5 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $person->birth_date ?? 'نامشخص' }}</dd>
                                    </div>
                                </dl>

                                @if (filled($person->deletion_reason))
                                    <p class="mt-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">
                                        <span class="font-bold text-slate-500">علت حذف:</span>
                                        {{ $person->deletion_reason }}
                                    </p>
                                @endif
                            </div>

                            <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                                <button
                                    type="button"
                                    wire:click="restoreSupervision({{ $person->id }})"
                                    wire:confirm="نظارت این مددجو با همان کد قبلی بازیابی شود؟"
                                    wire:loading.attr="disabled"
                                    wire:target="restoreSupervision({{ $person->id }})"
                                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <i class="bi bi-arrow-counterclockwise text-sm" wire:loading.remove wire:target="restoreSupervision({{ $person->id }})"></i>
                                    <span wire:loading.remove wire:target="restoreSupervision({{ $person->id }})">بازیابی نظارت</span>
                                    <span wire:loading wire:target="restoreSupervision({{ $person->id }})" class="inline-flex items-center gap-2">
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
                            <thead class="bg-gradient-to-l from-rose-700 to-pink-700 text-white">
                                <tr>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد مددجو</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد ملی</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-right font-bold">سرپرست</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">تاریخ تولد</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-right font-bold">علت حذف</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">تاریخ حذف</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">بازیابی نظارت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($people as $person)
                                    <tr wire:key="blocked-person-row-{{ $person->id }}" class="transition hover:bg-rose-50/70">
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600">{{ $person->person_code }}</td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="font-bold text-slate-800">{{ $person->full_name ?: 'بدون نام' }}</span>
                                            <span class="ms-2 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 align-middle text-[10px] font-semibold text-slate-500">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $person->gender === 'female' ? 'bg-rose-400' : 'bg-sky-400' }}" aria-hidden="true"></span>
                                                {{ $person->gender_label ?: 'نامشخص' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600">{{ $person->national_id ?: '-' }}</td>
                                        <td class="px-5 py-4 text-right font-medium text-slate-700">{{ trim(($person->guardian?->first_name ?? '') . ' ' . ($person->guardian?->last_name ?? '')) ?: '—' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center text-xs tabular-nums text-slate-600">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                        <td class="px-5 py-4 text-right text-slate-700">
                                            @if (filled($person->deletion_reason))
                                                <div class="max-w-[240px] truncate" title="{{ $person->deletion_reason }}">{{ $person->deletion_reason }}</div>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $person->deleted_at?->format('Y/m/d H:i') ?? '-' }}</td>
                                        <td class="px-5 py-4 text-center">
                                            <button
                                                type="button"
                                                wire:click="restoreSupervision({{ $person->id }})"
                                                wire:confirm="نظارت این مددجو با همان کد قبلی بازیابی شود؟"
                                                wire:loading.attr="disabled"
                                                wire:target="restoreSupervision({{ $person->id }})"
                                                class="inline-flex min-h-10 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                <i class="bi bi-arrow-counterclockwise" wire:loading.remove wire:target="restoreSupervision({{ $person->id }})"></i>
                                                <span wire:loading.remove wire:target="restoreSupervision({{ $person->id }})">بازیابی نظارت</span>
                                                <span wire:loading wire:target="restoreSupervision({{ $person->id }})" class="inline-flex items-center gap-1.5">
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
                    {{ $people->links('vendor.livewire.tailwind-mobile-persian') }}
                </div>
            @endif
        </div>
    </div>
</div>
