<div class="{{ $embedded ? '' : 'mx-auto max-w-5xl' }}" dir="rtl">
    <div class="space-y-4 pb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600">حامی کودک</p>
                    <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">لیست حامیان</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">فهرست حامیان ثبت‌شده، مبلغ واریزی ماهیانه و تعداد کودکان مددجوی اختصاص‌یافته.</p>
                </div>

                <div class="inline-flex w-fit items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">
                    {{ $this->persianNumber(number_format($sponsors->total())) }} حامی
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            @if (session()->has('success'))
                <div class="mb-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="hidden rounded-xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-500 md:grid md:grid-cols-[0.75fr_1fr_1fr_1fr_1.1fr_0.9fr_auto] md:items-center md:gap-3">
                <span>کد حامی</span>
                <span>نام</span>
                <span>نام خانوادگی</span>
                <span>موبایل</span>
                <span>مبلغ ماهیانه</span>
                <span>تعداد مددجویان</span>
                <span class="w-32 text-center">عملیات</span>
            </div>

            <div class="mt-0 space-y-2 md:mt-2">
                @forelse($sponsors as $sponsor)
                    <article class="rounded-xl border border-slate-200 bg-white p-3 transition hover:border-indigo-100 hover:bg-slate-50/60 sm:p-4 md:grid md:grid-cols-[0.75fr_1fr_1fr_1fr_1.1fr_0.9fr_auto] md:items-center md:gap-3">
                        <div class="flex items-center justify-between gap-3 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">کد حامی</span>
                            <span class="inline-flex rounded-lg bg-indigo-50 px-2.5 py-1 text-sm font-black text-indigo-700" dir="ltr">{{ $sponsor->supporter_code ?: '-' }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">نام</span>
                            <span class="truncate text-sm font-bold text-slate-800">{{ $sponsor->user?->first_name ?: '-' }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">نام خانوادگی</span>
                            <span class="truncate text-sm font-bold text-slate-800">{{ $sponsor->user?->last_name ?: '-' }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">موبایل</span>
                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-700">{{ $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-') }}</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">مبلغ ماهیانه</span>
                            <div class="min-w-0 text-left md:text-right">
                                <span class="block text-sm font-black text-emerald-700">{{ $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) }} ریال</span>
                                <span class="mt-0.5 block truncate text-[11px] font-semibold leading-5 text-teal-700">{{ $this->donationAmountInTomanWords((int) $sponsor->monthly_donation_amount) }}</span>
                            </div>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 md:mt-0 md:block">
                            <span class="text-xs font-bold text-slate-400 md:hidden">تعداد مددجویان</span>
                            <span class="inline-flex rounded-lg bg-cyan-50 px-2.5 py-1 text-sm font-black text-cyan-700">
                                {{ $this->persianNumber((int) $sponsor->beneficiaries->count()) }} نفر
                            </span>
                        </div>

                        <div class="mt-3 flex gap-2 md:mt-0">
                            <button type="button" wire:click="showDetails({{ $sponsor->id }})" class="flex h-10 flex-1 items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 px-3 text-sm font-black text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100 md:w-24">
                                جزئیات
                            </button>
                            <button type="button" wire:click="editSponsor({{ $sponsor->id }})" class="flex h-10 flex-1 items-center justify-center rounded-lg border border-amber-100 bg-amber-50 px-3 text-sm font-black text-amber-700 transition hover:border-amber-200 hover:bg-amber-100 focus:outline-none focus:ring-4 focus:ring-amber-100 md:w-20">
                                ویرایش
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                        <p class="text-sm font-bold text-slate-600">هنوز حامی‌ای ثبت نشده است.</p>
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
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-indigo-600">{{ $isEditing ? 'ویرایش حامی' : 'جزئیات حامی' }}</p>
                        <h2 class="mt-1 text-lg font-black text-slate-900">{{ $selectedSponsor['fullName'] }}</h2>
                    </div>
                    <button type="button" wire:click="closeDetails" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition hover:bg-slate-200">
                        <span class="sr-only">بستن</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 5L15 15M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                @if($isEditing)
                    <form wire:submit.prevent="updateSponsor" class="mt-4 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">نام</label>
                                <input type="text" wire:model.live.debounce.400ms="editFirstName" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                                @error('editFirstName') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">نام خانوادگی</label>
                                <input type="text" wire:model.live.debounce.400ms="editLastName" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                                @error('editLastName') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">شماره موبایل</label>
                                <input type="tel" wire:model.live.debounce.400ms="editMobile" dir="ltr" maxlength="11" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-left text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                                @error('editMobile') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">مبلغ واریزی ماهیانه</label>
                                <input type="text" wire:model.live.debounce.400ms="editMonthlyDonationAmount" dir="ltr" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-left text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                                @error('editMonthlyDonationAmount') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-700">مشخصات خاص کودک</label>
                            <textarea wire:model.blur="editChildPreferences" rows="3" class="min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                            @error('editChildPreferences') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <fieldset>
                                <legend class="mb-1.5 block text-sm font-bold text-slate-700">روش‌های یادآوری</legend>
                                <div class="space-y-1.5">
                                    @foreach(\App\Models\SponsorProfile::reminderMethodOptions() as $value => $label)
                                        <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700">
                                            <input type="checkbox" wire:model.live="editMonthlyPaymentReminderMethods" value="{{ $value }}" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('editMonthlyPaymentReminderMethods') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </fieldset>

                            <fieldset>
                                <legend class="mb-1.5 block text-sm font-bold text-slate-700">آیا در فضای مجازی فعال است؟</legend>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700">
                                        <input type="radio" wire:model.live="editIsSocialMediaActive" value="yes" class="border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span>بله</span>
                                    </label>
                                    <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700">
                                        <input type="radio" wire:model.live="editIsSocialMediaActive" value="no" class="border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span>خیر</span>
                                    </label>
                                </div>
                                @error('editIsSocialMediaActive') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </fieldset>
                        </div>

                        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:justify-end">
                            <button type="button" wire:click="cancelEdit" class="h-11 rounded-lg border border-slate-200 px-4 text-sm font-black text-slate-600 transition hover:bg-slate-50">
                                انصراف
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="updateSponsor" class="h-11 rounded-lg bg-indigo-600 px-4 text-sm font-black text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70">
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                @else
                    <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-100">
                        <div class="grid grid-cols-[8rem_1fr] items-center gap-3 px-3 py-2.5">
                            <span class="text-xs font-bold text-slate-400">کد حامی</span>
                            <span class="truncate text-left text-sm font-black text-indigo-700" dir="ltr">{{ $selectedSponsor['supporterCode'] ?? '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[8rem_1fr] items-center gap-3 px-3 py-2.5">
                            <span class="text-xs font-bold text-slate-400">شماره موبایل</span>
                            <span class="truncate text-left text-sm font-black text-slate-800">{{ $selectedSponsor['mobile'] }}</span>
                        </div>
                        <div class="grid grid-cols-[8rem_1fr] items-center gap-3 px-3 py-2.5">
                            <span class="text-xs font-bold text-slate-400">مبلغ واریزی ماهیانه</span>
                            <div class="min-w-0">
                                <span class="block truncate text-sm font-black text-emerald-700">{{ $selectedSponsor['monthlyDonationAmount'] }}</span>
                                <span class="mt-0.5 block truncate text-[11px] font-semibold leading-5 text-teal-700">{{ $selectedSponsor['monthlyDonationAmountInWords'] }}</span>
                            </div>
                        </div>
                        <div class="px-3 py-2.5">
                            <span class="text-xs font-bold text-slate-400">روش‌های یادآوری</span>
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
                        <div class="px-3 py-2.5">
                            <span class="text-xs font-bold text-slate-400">مددجویان اختصاص‌یافته</span>
                            <div class="mt-2 mb-3">
                                <span class="inline-flex rounded-lg bg-cyan-50 px-2.5 py-1 text-sm font-black text-cyan-700">
                                    {{ $this->persianNumber((int) ($selectedSponsor['beneficiariesCount'] ?? 0)) }} کودک مددجو
                                </span>
                            </div>
                            <div class="mt-2 space-y-2">
                                @forelse($selectedSponsor['beneficiaries'] ?? [] as $beneficiary)
                                    <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-2.5 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $beneficiary['full_name'] }}</p>
                                            <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiary['person_code'] }}</p>
                                        </div>
                                        <button type="button" wire:click="removeBeneficiaryFromSelectedSponsor({{ $beneficiary['id'] }})" class="rounded-md bg-rose-50 px-2 py-1 text-xs font-black text-rose-700 transition hover:bg-rose-100">حذف</button>
                                    </div>
                                @empty
                                    <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-center text-sm font-semibold text-slate-500">هیچ مددجویی اختصاص داده نشده است.</p>
                                @endforelse
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                                <input type="text" wire:model.live.debounce.400ms="beneficiaryCode" dir="ltr" placeholder="کد مددجو" class="h-10 rounded-lg border border-slate-200 px-3 text-left text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                                <button type="button" wire:click="lookupBeneficiary" class="h-10 rounded-lg border border-indigo-100 bg-indigo-50 px-3 text-xs font-black text-indigo-700 transition hover:bg-indigo-100">بررسی</button>
                                <button type="button" wire:click="addBeneficiaryToSelectedSponsor" class="h-10 rounded-lg bg-teal-600 px-3 text-xs font-black text-white transition hover:bg-teal-700">افزودن</button>
                            </div>
                            @error('beneficiaryCode') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror

                            @if($beneficiaryPreview)
                                <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">{{ $beneficiaryPreview['full_name'] }}</p>
                                            <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiaryPreview['person_code'] }}</p>
                                        </div>
                                        <span class="text-xs font-bold text-indigo-700">{{ $beneficiaryPreview['supporters_count'] }} حامی فعلی</span>
                                    </div>
                                    @if($beneficiaryPreview['supporters_count'] > 0)
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($beneficiaryPreview['supporters'] as $supporter)
                                                <span class="rounded-md bg-white px-2 py-1 text-xs font-bold text-slate-600">{{ $supporter['full_name'] }} - {{ $supporter['supporter_code'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
