<div class="{{ $embedded ? '' : 'mx-auto max-w-5xl' }}" dir="rtl">
    <div class="space-y-4 pb-24 sm:pb-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600">حامی کودک</p>
                    <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">ثبت نام حامی</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">اطلاعات حامی جدید را با حداقل مراحل ثبت کنید.</p>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-black text-slate-900">اطلاعات اصلی</h2>
                    <span class="text-xs font-semibold text-slate-400">موارد ستاره دار الزامی هستند</span>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="sponsor-first-name" class="mb-1.5 block text-sm font-bold text-slate-700">نام <span class="text-rose-500">*</span></label>
                        <input
                            id="sponsor-first-name"
                            type="text"
                            wire:model.live.debounce.400ms="firstName"
                            autocomplete="given-name"
                            placeholder="نام"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                        @error('firstName') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="sponsor-last-name" class="mb-1.5 block text-sm font-bold text-slate-700">نام خانوادگی <span class="text-rose-500">*</span></label>
                        <input
                            id="sponsor-last-name"
                            type="text"
                            wire:model.live.debounce.400ms="lastName"
                            autocomplete="family-name"
                            placeholder="نام خانوادگی"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                        @error('lastName') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="sponsor-monthly-donation" class="mb-1.5 block text-sm font-bold text-slate-700">مبلغ واریزی ماهیانه <span class="text-rose-500">*</span></label>
                        <input
                            id="sponsor-monthly-donation"
                            type="text"
                            inputmode="numeric"
                            wire:model.live.debounce.400ms="monthlyDonationAmount"
                            x-data
                            x-on:input="$el.value = $el.value.replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                            placeholder="مبلغ واریزی ماهیانه"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition ltr:text-left focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                        <div class="mt-1.5 min-h-5">
                            @error('monthlyDonationAmount')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                @if($this->formattedDonation)
                                    <p class="text-xs font-semibold text-slate-500">{{ $this->formattedDonation }}</p>
                                @endif
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="sponsor-mobile" class="mb-1.5 block text-sm font-bold text-slate-700">شماره موبایل <span class="text-rose-500">*</span></label>
                        <input
                            id="sponsor-mobile"
                            type="tel"
                            inputmode="numeric"
                            dir="ltr"
                            x-data="{ mobile: $wire.entangle('mobile').live }"
                            x-model="mobile"
                            x-on:input="mobile = $el.value.replace(/\D/g, '').slice(0, 11)"
                            autocomplete="tel"
                            maxlength="11"
                            pattern="[0-9]{11}"
                            placeholder="شماره موبایل"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                        @error('mobile') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex min-h-12 items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="text-xs font-bold text-slate-500">نام کامل یکپارچه</span>
                            <span class="truncate text-sm font-black text-slate-800">{{ $this->fullName !== '' ? $this->fullName : '-' }}</span>
                        </div>
                    </div>

                    <div>
                        <fieldset x-data="{ active: $wire.entangle('isSocialMediaActive').live }">
                            <legend class="mb-1.5 block text-sm font-bold text-slate-700">آیا با همین شماره در فضای مجازی فعال هستید؟ <span class="text-rose-500">*</span></legend>
                            <div class="grid grid-cols-2 gap-1.5">
                                @foreach(['yes' => 'بله', 'no' => 'خیر'] as $value => $label)
                                    <label
                                        class="flex min-h-11 cursor-pointer select-none items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold transition duration-150 ease-out active:scale-[0.99]"
                                        x-bind:class="active === @js($value) ? 'border-teal-300 bg-teal-50 text-teal-800 shadow-[inset_0_0_0_1px_rgba(20,184,166,0.12)]' : 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:bg-teal-50/40'"
                                    >
                                        <input
                                            type="radio"
                                            value="{{ $value }}"
                                            x-model="active"
                                            class="sr-only"
                                        >
                                        <span
                                            class="grid size-5 shrink-0 place-items-center rounded-full border transition duration-150"
                                            x-bind:class="active === @js($value) ? 'border-teal-500 bg-teal-500' : 'border-slate-300 bg-white'"
                                            aria-hidden="true"
                                        >
                                            <svg
                                                viewBox="0 0 16 16"
                                                fill="none"
                                                class="size-3.5 text-white transition-all duration-200 ease-out [stroke-dasharray:16]"
                                                x-bind:class="active === @js($value) ? 'scale-100 opacity-100 [stroke-dashoffset:0]' : 'scale-75 opacity-0 [stroke-dashoffset:16]'"
                                            >
                                                <path d="M3.5 8.2 6.6 11 12.5 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('isSocialMediaActive') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </fieldset>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-black text-slate-900">ترجیحات و یادآوری</h2>
                </div>

                <div class="grid gap-3 lg:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <label for="sponsor-child-preferences" class="mb-1.5 block text-sm font-bold text-slate-700">مشخصات خاص کودک</label>
                        <textarea
                            id="sponsor-child-preferences"
                            wire:model.blur="childPreferences"
                            rows="3"
                            placeholder="مشخصات خاصی از کودک تحت پوشش مدنظر دارید؟"
                            class="min-h-32 w-full resize-y rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-800 outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-100"
                        ></textarea>
                        @error('childPreferences') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <fieldset x-data="{ selected: $wire.entangle('monthlyPaymentReminderMethods').live }">
                        <legend class="mb-1.5 block text-sm font-bold text-slate-700">روش یادآوری واریز ماهیانه <span class="text-rose-500">*</span></legend>
                        <div class="grid gap-1.5 sm:grid-cols-3 lg:grid-cols-1">
                            @foreach($reminderMethods as $value => $label)
                                <label
                                    class="flex min-h-11 cursor-pointer select-none items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold transition duration-150 ease-out active:scale-[0.99]"
                                    x-bind:class="selected.includes(@js($value)) ? 'border-teal-300 bg-teal-50 text-teal-800 shadow-[inset_0_0_0_1px_rgba(20,184,166,0.12)]' : 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:bg-teal-50/40'"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $value }}"
                                        x-model="selected"
                                        class="sr-only"
                                    >
                                    <span
                                        class="grid size-5 shrink-0 place-items-center rounded-md border transition duration-150"
                                        x-bind:class="selected.includes(@js($value)) ? 'border-teal-500 bg-teal-500' : 'border-slate-300 bg-white'"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            viewBox="0 0 16 16"
                                            fill="none"
                                            class="size-3.5 text-white transition-all duration-200 ease-out [stroke-dasharray:16]"
                                            x-bind:class="selected.includes(@js($value)) ? 'scale-100 opacity-100 [stroke-dashoffset:0]' : 'scale-75 opacity-0 [stroke-dashoffset:16]'"
                                        >
                                            <path d="M3.5 8.2 6.6 11 12.5 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="truncate">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('monthlyPaymentReminderMethods') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </fieldset>
                </div>
            </section>

            <div class="sticky bottom-0 z-20 -mx-2 border-t border-slate-200 bg-white/95 px-2 py-3 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur sm:static sm:mx-0 sm:rounded-2xl sm:border sm:px-4 sm:shadow-sm">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="flex h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-black text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto sm:min-w-44"
                >
                    <span wire:loading.remove wire:target="save">ثبت نام حامی</span>
                    <span wire:loading wire:target="save">در حال ثبت...</span>
                </button>
            </div>
        </form>
    </div>
</div>
