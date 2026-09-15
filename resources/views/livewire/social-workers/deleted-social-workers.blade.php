<div>
    <div class="container mx-auto p-0">
        @php
            $workers = $this->deletedSocialWorkers;
        @endphp

        {{-- هویت رنگی بخش: cyan (مددکار) --}}
        <div class="rounded-2xl border border-cyan-100/80 bg-gradient-to-br from-white via-cyan-50/40 to-white p-3 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-l from-cyan-600 to-sky-700 text-white shadow-sm sm:flex">
                        <i class="bi bi-person-slash text-base"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl lg:text-2xl">مددکاران غیرفعال</h1>
                            <span class="inline-flex items-center whitespace-nowrap rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-700 ring-1 ring-cyan-100">
                                {{ number_format($workers->total()) }} مورد
                            </span>
                        </div>
                        <p class="mt-1 hidden text-sm text-slate-500 sm:block">مددکاران حذف‌شده را ببینید و در صورت نیاز، همکاری را با همان کد مددکاری قبلی از سر بگیرید</p>
                    </div>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($workers->isEmpty())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm ring-1 ring-slate-100">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                        <i class="bi bi-inbox text-2xl"></i>
                    </div>
                    <h2 class="mt-4 text-base font-extrabold text-slate-800">هیچ مددکار غیرفعالی وجود ندارد</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        مددکارانی که از فهرست حذف یا غیرفعال می‌شوند اینجا نمایش داده می‌شوند تا بتوانید همکاریشان را بازیابی کنید.
                    </p>
                </div>
            @else
                {{-- نمای موبایل: کارت‌های عمودی --}}
                <div class="mt-4 space-y-2.5 md:hidden">
                    @foreach ($workers as $worker)
                        <article wire:key="inactive-worker-card-{{ $worker->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                            <div class="px-3 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</h2>
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                            <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-cyan-700" dir="ltr">{{ $worker->worker_code ?: '-' }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $worker->national_id ?: '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-left">
                                        <p class="text-[10px] font-semibold text-slate-400">تاریخ حذف</p>
                                        <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-slate-700" dir="ltr">{{ $worker->deleted_at?->format('Y/m/d H:i') ?? '-' }}</p>
                                    </div>
                                </div>

                                <dl class="mt-3 grid grid-cols-2 gap-2 text-right">
                                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                                        <dt class="text-[10px] font-bold text-slate-400">موبایل</dt>
                                        <dd class="mt-1 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $worker->mobile ?: '-' }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                                        <dt class="text-[10px] font-bold text-slate-400">افراد تحت پوشش</dt>
                                        <dd class="mt-1 truncate text-xs font-bold text-slate-700">{{ number_format((int) $worker->covered_people_count) }} نفر</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                                <button
                                    type="button"
                                    wire:click="restoreSocialWorker({{ $worker->id }})"
                                    wire:confirm="همکاری این مددکار با همان کد قبلی از سر گرفته شود؟"
                                    wire:loading.attr="disabled"
                                    wire:target="restoreSocialWorker({{ $worker->id }})"
                                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <i class="bi bi-arrow-counterclockwise text-sm" wire:loading.remove wire:target="restoreSocialWorker({{ $worker->id }})"></i>
                                    <span wire:loading.remove wire:target="restoreSocialWorker({{ $worker->id }})">از سرگیری همکاری</span>
                                    <span wire:loading wire:target="restoreSocialWorker({{ $worker->id }})" class="inline-flex items-center gap-2">
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
                            <thead class="bg-gradient-to-l from-cyan-600 to-sky-700 text-white">
                                <tr>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد مددکاری</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد ملی</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">موبایل</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">افراد تحت پوشش</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">تاریخ حذف</th>
                                    <th class="whitespace-nowrap px-5 py-4 text-center font-bold">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($workers as $worker)
                                    <tr wire:key="inactive-worker-row-{{ $worker->id }}" class="transition hover:bg-cyan-50/70">
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600">{{ $worker->worker_code ?: '-' }}</td>
                                        <td class="px-5 py-4 text-right font-bold text-slate-800">{{ $worker->full_name ?: 'بدون نام' }}</td>
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $worker->national_id ?: '-' }}</td>
                                        <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $worker->mobile ?: '-' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center">
                                            <span class="font-bold tabular-nums text-slate-800">{{ number_format((int) $worker->covered_people_count) }}</span>
                                            <span class="text-xs text-slate-500">نفر</span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">{{ $worker->deleted_at?->format('Y/m/d H:i') ?? '-' }}</td>
                                        <td class="px-5 py-4 text-center">
                                            <button
                                                type="button"
                                                wire:click="restoreSocialWorker({{ $worker->id }})"
                                                wire:confirm="همکاری این مددکار با همان کد قبلی از سر گرفته شود؟"
                                                wire:loading.attr="disabled"
                                                wire:target="restoreSocialWorker({{ $worker->id }})"
                                                class="inline-flex min-h-10 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                <i class="bi bi-arrow-counterclockwise" wire:loading.remove wire:target="restoreSocialWorker({{ $worker->id }})"></i>
                                                <span wire:loading.remove wire:target="restoreSocialWorker({{ $worker->id }})">از سرگیری همکاری</span>
                                                <span wire:loading wire:target="restoreSocialWorker({{ $worker->id }})" class="inline-flex items-center gap-1.5">
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
                    {{ $workers->links('vendor.livewire.tailwind-mobile-persian') }}
                </div>
            @endif
        </div>
    </div>
</div>
