<div class="{{ $embedded ? '' : 'mx-auto max-w-5xl' }}" dir="rtl">
    <div class="space-y-4 pb-4">
        <div class="border-b border-slate-200 bg-white/80 px-1 pb-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold text-indigo-600">حامی کودک</p>
                    <h1 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">{{ $isEditing ? 'ویرایش حامی' : 'ثبت نام حامی' }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $isEditing ? 'اطلاعات حامی را بررسی و بروزرسانی کنید.' : 'اطلاعات حامی جدید را با حداقل مراحل ثبت کنید.' }}</p>
                </div>
                @if($isEditing)
                    <a href="{{ route('child-supporter.sponsor-list') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                        بازگشت به لیست
                    </a>
                @endif
            </div>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-4 pb-20 sm:pb-0">
            @php
                $steps = [
                    1 => 'اطلاعات حامی',
                    2 => 'مبلغ حمایت',
                    3 => 'مددجویان',
                    4 => 'ترجیحات',
                    5 => 'بازبینی',
                ];
            @endphp

            <nav aria-label="مراحل ثبت نام" class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                <ol class="grid grid-cols-5 gap-2">
                    @foreach($steps as $stepNumber => $stepLabel)
                        <li>
                            <button
                                type="button"
                                wire:click="goToStep({{ $stepNumber }})"
                                class="flex w-full flex-col items-center gap-2 rounded-lg px-2 py-2 text-center transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                aria-current="{{ $currentStep === $stepNumber ? 'step' : 'false' }}"
                            >
                                <span class="grid size-8 place-items-center rounded-full text-xs font-bold transition {{ $currentStep === $stepNumber ? 'bg-indigo-600 text-white' : ($currentStep > $stepNumber ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $stepNumber }}
                                </span>
                                <span class="hidden text-xs font-bold sm:block {{ $currentStep === $stepNumber ? 'text-indigo-700' : 'text-slate-500' }}">{{ $stepLabel }}</span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            </nav>

            @if($currentStep === 1)
            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">اطلاعات اصلی</h2>
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
                            class="h-12 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                            aria-describedby="sponsor-first-name-help"
                        >
                        <div id="sponsor-first-name-help" class="mt-1.5 min-h-5">
                            @error('firstName')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">نام را مطابق اطلاعات شناسنامه‌ای وارد کنید.</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="sponsor-last-name" class="mb-1.5 block text-sm font-bold text-slate-700">نام خانوادگی <span class="text-rose-500">*</span></label>
                        <input
                            id="sponsor-last-name"
                            type="text"
                            wire:model.live.debounce.400ms="lastName"
                            autocomplete="family-name"
                            placeholder="نام خانوادگی"
                            class="h-12 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                            aria-describedby="sponsor-last-name-help"
                        >
                        <div id="sponsor-last-name-help" class="mt-1.5 min-h-5">
                            @error('lastName')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">حداقل دو حرف وارد شود.</p>
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
                            placeholder="09123456789"
                            class="h-12 w-full rounded-lg border border-slate-200 bg-white px-3 text-left text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                            aria-describedby="sponsor-mobile-help"
                        >
                        <div id="sponsor-mobile-help" class="mt-1.5 min-h-5">
                            @error('mobile')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">شماره باید با 09 شروع شود و 11 رقم باشد.</p>
                            @enderror
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex min-h-12 items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="text-xs font-bold text-slate-500">نام کامل یکپارچه</span>
                            <span class="truncate text-sm font-bold text-slate-800">{{ $this->fullName !== '' ? $this->fullName : '-' }}</span>
                        </div>
                    </div>

                    <div>
                        <fieldset x-data="{ active: $wire.entangle('isSocialMediaActive').live }">
                            <legend class="mb-1.5 block text-sm font-bold text-slate-700">فعال در پیام‌رسان با همین شماره؟ <span class="text-rose-500">*</span></legend>
                            <div class="grid grid-cols-2 gap-1.5">
                                @foreach(['yes' => 'بله', 'no' => 'خیر'] as $value => $label)
                                    <label
                                        class="flex min-h-11 cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition duration-150 ease-out active:scale-[0.99]"
                                        x-bind:class="active === @js($value) ? 'border-indigo-300 bg-indigo-50 text-indigo-800' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50/40'"
                                    >
                                        <input
                                            type="radio"
                                            value="{{ $value }}"
                                            x-model="active"
                                            class="sr-only"
                                        >
                                        <span
                                            class="grid size-5 shrink-0 place-items-center rounded-full border transition duration-150"
                                            x-bind:class="active === @js($value) ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 bg-white'"
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
                            <div class="mt-1.5 min-h-5">
                                @error('isSocialMediaActive')
                                    <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @else
                                    <p class="text-xs font-semibold text-slate-400">برای هماهنگی و یادآوری‌های بعدی استفاده می‌شود.</p>
                                @enderror
                            </div>
                        </fieldset>
                    </div>
                </div>
            </section>
            @elseif($currentStep === 2)
            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">مبلغ حمایت ماهیانه</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-400">مبلغ تعهد ماهانه حامی را وارد کنید.</p>
                </div>

                <div class="max-w-xl">
                    <label for="sponsor-monthly-donation" class="mb-1.5 block text-sm font-bold text-slate-700">مبلغ واریزی ماهیانه <span class="text-rose-500">*</span></label>
                    <input
                        id="sponsor-monthly-donation"
                        type="text"
                        inputmode="numeric"
                        wire:model.live.debounce.400ms="monthlyDonationAmount"
                        x-data
                        x-on:input="$el.value = $el.value.replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        placeholder="مثلا 1,000,000"
                        class="h-12 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition ltr:text-left focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                        aria-describedby="sponsor-monthly-donation-help"
                    >
                    <div id="sponsor-monthly-donation-help" class="mt-1.5 min-h-5">
                        @error('monthlyDonationAmount')
                            <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @else
                            @if($this->formattedDonation)
                                <p class="text-xs font-semibold text-slate-500">{{ $this->formattedDonation }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">مبلغ را به ریال و فقط با عدد وارد کنید.</p>
                            @endif
                        @enderror
                    </div>
                </div>
            </section>
            @elseif($currentStep === 3)

            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">مددجویان اختصاص‌یافته</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-400">در صورت داشتن کد مددجو، او را به حامی اختصاص دهید. این مرحله اختیاری است.</p>
                </div>

                <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label for="assigned-beneficiary-code" class="block text-sm font-bold text-slate-700">کد مددجو</label>
                            <span class="text-xs font-semibold text-slate-400">اختیاری</span>
                        </div>
                        <input
                            id="assigned-beneficiary-code"
                            type="text"
                            wire:model.live.debounce.400ms="beneficiaryCode"
                            dir="ltr"
                            placeholder="14000"
                            class="h-12 w-full rounded-lg border border-slate-200 bg-white px-3 text-left text-sm text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                            aria-describedby="assigned-beneficiary-code-help"
                        >
                        <div id="assigned-beneficiary-code-help" class="mt-1.5 min-h-5">
                            @error('beneficiaryCode')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">پس از وارد کردن کد معتبر، پیش‌نمایش مددجو به صورت خودکار نمایش داده می‌شود.</p>
                            @enderror
                        </div>
                    </div>

                    <button type="button" wire:click="addBeneficiary" class="h-12 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                        افزودن مددجو
                    </button>
                </div>

                @if($beneficiaryPreview)
                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ $beneficiaryPreview['full_name'] }}</p>
                                <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiaryPreview['person_code'] }}</p>
                            </div>
                            <span class="text-xs font-bold text-indigo-700">{{ $beneficiaryPreview['supporters_count'] }} حامی فعلی</span>
                        </div>
                        <p class="mt-2 text-xs font-semibold text-slate-500">برای اختصاص این مددجو به حامی، دکمه افزودن مددجو را بزنید.</p>

                        @if($beneficiaryPreview['supporters_count'] > 0)
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($beneficiaryPreview['supporters'] as $supporter)
                                    <span class="rounded-md bg-white px-2 py-1 text-xs font-bold text-slate-600">
                                        {{ $supporter['full_name'] }} - {{ $supporter['supporter_code'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="mt-3 space-y-2">
                    @forelse($assignedBeneficiaries as $beneficiary)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $beneficiary['full_name'] }}</p>
                                <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiary['person_code'] }}</p>
                            </div>
                            <button type="button" wire:click="removeBeneficiary({{ $beneficiary['id'] }})" class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">
                                حذف
                            </button>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-sm font-semibold text-slate-500">هنوز مددجویی اختصاص داده نشده است.</p>
                    @endforelse
                </div>
            </section>
            @elseif($currentStep === 4)

            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">ترجیحات و یادآوری</h2>
                </div>

                <div class="grid gap-3 lg:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label for="sponsor-child-preferences" class="block text-sm font-bold text-slate-700">مشخصات خاص کودک</label>
                            <span class="text-xs font-semibold text-slate-400">اختیاری</span>
                        </div>
                        <textarea
                            id="sponsor-child-preferences"
                            wire:model.blur="childPreferences"
                            rows="3"
                            placeholder="مثلا سن، جنسیت، شرایط خاص یا توضیح مورد نیاز"
                            class="min-h-32 w-full resize-y rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                            aria-describedby="sponsor-child-preferences-help"
                        ></textarea>
                        <div id="sponsor-child-preferences-help" class="mt-1.5 min-h-5">
                            @error('childPreferences')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">اگر ترجیح خاصی وجود ندارد، این بخش را خالی بگذارید.</p>
                            @enderror
                        </div>
                    </div>

                    <fieldset x-data="{ selected: $wire.entangle('monthlyPaymentReminderMethods').live }">
                        <legend class="mb-1.5 block text-sm font-bold text-slate-700">روش یادآوری واریز ماهیانه <span class="text-rose-500">*</span></legend>
                        <div class="grid gap-1.5 sm:grid-cols-3 lg:grid-cols-1">
                            @foreach($reminderMethods as $value => $label)
                                <label
                                    class="flex min-h-11 cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition duration-150 ease-out active:scale-[0.99]"
                                    x-bind:class="selected.includes(@js($value)) ? 'border-indigo-300 bg-indigo-50 text-indigo-800' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50/40'"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $value }}"
                                        x-model="selected"
                                        class="sr-only"
                                    >
                                    <span
                                        class="grid size-5 shrink-0 place-items-center rounded-md border transition duration-150"
                                        x-bind:class="selected.includes(@js($value)) ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 bg-white'"
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
                        <div class="mt-1.5 min-h-5">
                            @error('monthlyPaymentReminderMethods')
                                <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @else
                                <p class="text-xs font-semibold text-slate-400">حداقل یک روش یادآوری انتخاب شود.</p>
                            @enderror
                        </div>
                    </fieldset>
                </div>
            </section>
            @elseif($currentStep === 5)
            <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">بازبینی نهایی</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-400">اطلاعات وارد شده را پیش از ثبت بررسی کنید.</p>
                </div>

                <div class="mb-4 rounded-lg border px-3 py-3 {{ $this->isReadyForReview ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : 'border-amber-100 bg-amber-50 text-amber-800' }}">
                    <p class="text-sm font-bold">{{ $this->isReadyForReview ? 'آماده ثبت نهایی' : 'نیازمند تکمیل اطلاعات' }}</p>
                    <p class="mt-1 text-xs font-semibold opacity-80">
                        {{ $this->isReadyForReview ? 'اطلاعات ضروری تکمیل شده است. پیش از ثبت، جزئیات زیر را مرور کنید.' : 'برای ثبت نهایی، مراحل قبل را بررسی و موارد الزامی را تکمیل کنید.' }}
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-800">اطلاعات حامی</h3>
                            <button type="button" wire:click="goToStep(1)" class="text-xs font-bold text-indigo-700 transition hover:text-indigo-900">
                                ویرایش
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold text-slate-500">نام کامل</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800">{{ $this->fullName !== '' ? $this->fullName : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">شماره موبایل</p>
                                <p class="mt-1 text-sm font-bold text-slate-800" dir="ltr">{{ $mobile !== '' ? $mobile : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">وضعیت پیام‌رسان</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $isSocialMediaActive === 'yes' ? 'فعال' : ($isSocialMediaActive === 'no' ? 'غیرفعال' : '-') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-800">مبلغ حمایت</h3>
                            <button type="button" wire:click="goToStep(2)" class="text-xs font-bold text-indigo-700 transition hover:text-indigo-900">
                                ویرایش
                            </button>
                        </div>
                        <p class="text-xs font-bold text-slate-500">مبلغ ماهیانه</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ $this->formattedDonation !== '' ? $this->formattedDonation : '-' }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-800">مددجویان اختصاص‌یافته</h3>
                            <button type="button" wire:click="goToStep(3)" class="text-xs font-bold text-indigo-700 transition hover:text-indigo-900">
                                ویرایش
                            </button>
                        </div>
                        @if(count($assignedBeneficiaries) > 0)
                            <div class="space-y-2">
                                @foreach($assignedBeneficiaries as $beneficiary)
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                        <p class="truncate text-sm font-bold text-slate-800">{{ $beneficiary['full_name'] }}</p>
                                        <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiary['person_code'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm font-semibold text-slate-500">مددجویی اختصاص داده نشده است.</p>
                        @endif
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-800">ترجیحات و یادآوری</h3>
                            <button type="button" wire:click="goToStep(4)" class="text-xs font-bold text-indigo-700 transition hover:text-indigo-900">
                                ویرایش
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold text-slate-500">روش یادآوری</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    {{ collect($monthlyPaymentReminderMethods)->map(fn ($method) => $reminderMethods[$method] ?? $method)->join('، ') ?: '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500">ترجیحات کودک</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ filled($childPreferences) ? $childPreferences : 'ثبت نشده' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            <div class="sticky bottom-0 z-20 -mx-2 border-t border-slate-200 bg-white/95 px-2 py-3 backdrop-blur sm:static sm:mx-0 sm:rounded-xl sm:border sm:px-4">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="button"
                        wire:click="previousStep"
                        @disabled($currentStep === 1)
                        class="flex h-12 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-32"
                    >
                        مرحله قبل
                    </button>

                    @if($currentStep < 5)
                        <button
                            type="button"
                            wire:click="nextStep"
                            class="flex h-12 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100 sm:min-w-44"
                        >
                            مرحله بعد
                        </button>
                    @else
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            @disabled(! $this->isReadyForReview)
                            class="flex h-12 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-60 sm:min-w-44"
                        >
                            <span wire:loading.remove wire:target="save">{{ $isEditing ? 'ذخیره تغییرات' : 'ثبت نام حامی' }}</span>
                            <span wire:loading wire:target="save">{{ $isEditing ? 'در حال ذخیره...' : 'در حال ثبت...' }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
