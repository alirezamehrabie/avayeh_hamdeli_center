<div class="{{ $embedded ? '' : 'mx-auto max-w-5xl' }}" dir="rtl">
    <div class="space-y-4 pb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600">حامی کودک</p>
                    <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">لیست حامیان</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">نمایش حامیان ثبت شده و مبلغ حمایت ماهیانه.</p>
                </div>

                <div class="inline-flex w-fit items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">
                    {{ $this->persianNumber(number_format($sponsors->total())) }} حامی
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="hidden rounded-xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-500 md:grid md:grid-cols-[1fr_1fr_1.1fr_1.1fr_auto] md:items-center md:gap-3">
                <span>نام</span>
                <span>نام خانوادگی</span>
                <span>شماره موبایل</span>
                <span>مبلغ ماهیانه</span>
                <span class="w-24 text-center">جزئیات</span>
            </div>

            <div class="mt-0 space-y-2 md:mt-2">
                @forelse($sponsors as $sponsor)
                    <article class="rounded-xl border border-slate-200 bg-white p-3 transition hover:border-indigo-100 hover:bg-slate-50/60 sm:p-4 md:grid md:grid-cols-[1fr_1fr_1.1fr_1.1fr_auto] md:items-center md:gap-3">
                        <div class="flex items-center justify-between gap-3 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">نام</span>
                            <span class="truncate text-sm font-bold text-slate-800">{{ $sponsor->user?->first_name ?: '-' }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">نام خانوادگی</span>
                            <span class="truncate text-sm font-bold text-slate-800">{{ $sponsor->user?->last_name ?: '-' }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">شماره موبایل</span>
                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-700">{{ $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-') }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">مبلغ ماهیانه</span>
                            <span class="text-sm font-black text-emerald-700">{{ $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) }} ریال</span>
                        </div>

                        <div class="mt-3 md:mt-0">
                            <button
                                type="button"
                                wire:click="showDetails({{ $sponsor->id }})"
                                class="flex h-10 w-full items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 px-3 text-sm font-black text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100 md:w-24"
                            >
                                جزئیات
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                        <p class="text-sm font-bold text-slate-600">هنوز حامی ثبت نشده است.</p>
                        <p class="mt-1 text-xs text-slate-400">بعد از ثبت نام حامی، اطلاعات اینجا نمایش داده می‌شود.</p>
                    </div>
                @endforelse
            </div>

            @if($sponsors->hasPages())
                <div class="mt-4">
                    {{ $sponsors->links() }}
                </div>
            @endif
        </section>
    </div>

    @if($selectedSponsor)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/35 p-2 sm:items-center sm:p-4" wire:click.self="closeDetails">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-indigo-600">جزئیات حامی</p>
                        <h2 class="mt-1 text-lg font-black text-slate-900">{{ $selectedSponsor['fullName'] }}</h2>
                    </div>
                    <button type="button" wire:click="closeDetails" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition hover:bg-slate-200">
                        <span class="sr-only">بستن</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 5L15 15M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-100">
                    <div class="grid grid-cols-[7rem_1fr] items-center gap-3 px-3 py-2.5">
                        <span class="text-xs font-bold text-slate-400">شماره موبایل</span>
                        <span class="truncate text-left text-sm font-black text-slate-800">{{ $selectedSponsor['mobile'] }}</span>
                    </div>
                    <div class="grid grid-cols-[7rem_1fr] items-center gap-3 px-3 py-2.5">
                        <span class="text-xs font-bold text-slate-400">مبلغ ماهیانه</span>
                        <span class="truncate text-sm font-black text-emerald-700">{{ $selectedSponsor['monthlyDonationAmount'] }}</span>
                    </div>
                    <div class="px-3 py-2.5">
                        <span class="text-xs font-bold text-slate-400">روش یادآوری</span>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @forelse($selectedSponsor['reminderMethods'] as $method)
                                <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ $method }}</span>
                            @empty
                                <span class="text-sm font-semibold text-slate-500">-</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="px-3 py-2.5">
                        <span class="text-xs font-bold text-slate-400">مشخصات خاص کودک</span>
                        <p class="mt-1 max-h-28 overflow-y-auto text-sm leading-6 text-slate-700">{{ $selectedSponsor['childPreferences'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
