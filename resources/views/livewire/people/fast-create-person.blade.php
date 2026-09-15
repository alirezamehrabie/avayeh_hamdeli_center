<div class="mx-auto max-w-4xl">
    @php
        $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];

        $labelClass = 'mb-2 block text-sm font-bold text-slate-700';
        $errorClass = 'mt-1.5 block text-xs font-bold leading-5 text-rose-600';

        $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-rose-400 focus:ring-4 focus:ring-rose-100';
        $inputInvalidClass = 'w-full rounded-xl border border-rose-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-rose-400 focus:ring-4 focus:ring-rose-100';

        // سلکت‌های سه‌بخشی تاریخ (روز/ماه/سال) داخل یک ردیف
        $dateSelectClass = 'w-full rounded-xl border border-slate-300 bg-white px-1 py-3 text-center text-sm text-slate-700 outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100';

        $sectionHeaderIconClass = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-600';

        // کارت انتخاب جنسیت (راديو پنهان + لیبل هایلایت‌شونده)
        $genderOptionClass = 'flex min-h-[52px] cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-bold text-slate-600 transition peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 peer-focus-visible:ring-4 peer-focus-visible:ring-rose-100';
    @endphp

    {{-- سربرگ صفحه --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $person ? 'ویرایش سریع مددجو' : 'ثبت‌نام سریع فرد جدید' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $person ? 'فیلدهای ضروری مددجو را در فرم سریع ویرایش کنید.' : 'فقط فیلدهای ضروری را برای ثبت سریع کامل کنید؛ اطلاعات تکمیلی بعداً قابل ویرایش است.' }}</p>
        </div>
        <span class="inline-flex w-fit items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-500 sm:mb-1">
            <span class="text-rose-500">*</span>
            فیلدهای ستاره‌دار الزامی هستند
        </span>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">

        {{-- پیام‌های موفقیت، خطا و اعتبارسنجی --}}
        @if (session()->has('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                <p class="text-sm font-bold text-rose-700">لطفاً خطاهای زیر را برطرف کنید:</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-xs font-semibold leading-6 text-rose-600 sm:text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- بخش اول: اطلاعات هویتی --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="{{ $sectionHeaderIconClass }}" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-800 sm:text-lg">اطلاعات هویتی فرد</h2>
                    <p class="text-xs text-slate-500 sm:text-sm">نام، کد ملی، جنسیت و تاریخ تولد مددجو</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">نام <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="first_name" placeholder="مثلاً زهرا" class="{{ $errors->has('first_name') ? $inputInvalidClass : $inputClass }}">
                    @error('first_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">نام خانوادگی <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="last_name" placeholder="مثلاً احمدی" class="{{ $errors->has('last_name') ? $inputInvalidClass : $inputClass }}">
                    @error('last_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">کد ملی <span class="text-rose-500">*</span></label>
                    <input type="text" inputmode="numeric" maxlength="10" wire:model.live="national_id" placeholder="کد ملی ۱۰ رقمی" class="{{ $errors->has('national_id') ? $inputInvalidClass : $inputClass }} text-center">
                    @error('national_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">جنسیت <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="جنسیت">
                        <div>
                            <input type="radio" value="male" wire:model.blur="gender" id="gender_male" class="peer sr-only">
                            <label for="gender_male" class="{{ $genderOptionClass }}">مرد</label>
                        </div>
                        <div>
                            <input type="radio" value="female" wire:model.blur="gender" id="gender_female" class="peer sr-only">
                            <label for="gender_female" class="{{ $genderOptionClass }}">زن</label>
                        </div>
                    </div>
                    @error('gender') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label class="{{ $labelClass }}">تاریخ تولد <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <select wire:model.blur="birth_day" class="{{ $errors->has('birth_day') ? $inputInvalidClass.' px-1 text-center' : $dateSelectClass }}" aria-label="روز تولد">
                            <option value="">روز</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                        <select wire:model.blur="birth_month" class="{{ $errors->has('birth_month') ? $inputInvalidClass.' px-1 text-center' : $dateSelectClass }}" aria-label="ماه تولد">
                            <option value="">ماه</option>
                            @foreach($months as $key => $month)
                                <option value="{{ $key }}">{{ $month }}</option>
                            @endforeach
                        </select>
                        <select wire:model.blur="birth_year" class="{{ $errors->has('birth_year') ? $inputInvalidClass.' px-1 text-center' : $dateSelectClass }}" aria-label="سال تولد">
                            <option value="">سال</option>
                            @foreach(range(1300, 1420) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('birth_day') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                    @error('birth_month') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                    @error('birth_year') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        {{-- بخش دوم: اطلاعات والدین --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="{{ $sectionHeaderIconClass }}" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-800 sm:text-lg">اطلاعات والدین</h2>
                    <p class="text-xs text-slate-500 sm:text-sm">نام پدر و کدهای ملی پدر و مادر مطابق شناسنامه</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">نام پدر <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="father_name" placeholder="مثلاً حسن" class="{{ $errors->has('father_name') ? $inputInvalidClass : $inputClass }}">
                    @error('father_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">کد ملی پدر <span class="text-rose-500">*</span></label>
                    <input type="text" inputmode="numeric" maxlength="10" wire:model.live="father_national_id" placeholder="کد ملی ۱۰ رقمی" class="{{ $errors->has('father_national_id') ? $inputInvalidClass : $inputClass }} text-center">
                    @error('father_national_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">کد ملی مادر <span class="text-rose-500">*</span></label>
                    <input type="text" inputmode="numeric" maxlength="10" wire:model.live="mother_national_id" placeholder="کد ملی ۱۰ رقمی" class="{{ $errors->has('mother_national_id') ? $inputInvalidClass : $inputClass }} text-center">
                    @error('mother_national_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        {{-- دکمه‌های عملیاتی --}}
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row-reverse sm:items-center sm:justify-start">
            <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-rose-600 px-8 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-11 sm:w-auto">
                <span wire:loading.remove wire:target="save">{{ $person ? 'ذخیره ویرایش سریع' : 'ثبت اطلاعات سریع' }}</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    در حال ثبت...
                </span>
            </button>
            @if ($embedded)
                <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'people-list' })" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-6 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 sm:min-h-11 sm:w-auto">
                    انصراف
                </button>
            @else
                <button type="button" onclick="history.back()" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-6 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 sm:min-h-11 sm:w-auto">
                    انصراف
                </button>
            @endif
        </div>
    </form>
</div>
