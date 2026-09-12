<div class="mx-auto max-w-4xl">
    @php
        $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];

        $labelClass = 'mb-2 block text-sm font-bold text-slate-700';
        $errorClass = 'mt-1.5 block text-xs font-bold leading-5 text-rose-600';

        $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100';
        $inputInvalidClass = 'w-full rounded-xl border border-rose-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-rose-400 focus:ring-4 focus:ring-rose-100';

        $selectClass = 'w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100';
        $selectInvalidClass = 'w-full rounded-xl border border-rose-300 bg-white px-3 py-3 text-sm text-slate-700 outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100';

        // سلکت‌های سه‌بخشی تاریخ (روز/ماه/سال) داخل یک ردیف
        $dateSelectClass = 'w-full rounded-xl border border-slate-300 bg-white px-1 py-3 text-center text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100';

        $sectionHeaderIconClass = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cyan-100 bg-cyan-50 text-cyan-600';
    @endphp

    {{-- سربرگ صفحه --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">ثبت مددکار جدید</h1>
            <p class="mt-1 text-sm text-slate-500">اطلاعات هویتی، حرفه‌ای و حساب کاربری مددکار را تکمیل کنید</p>
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

        {{-- بخش اول: اطلاعات فردی و شناسایی --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="{{ $sectionHeaderIconClass }}" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-800 sm:text-lg">اطلاعات فردی و شناسایی</h2>
                    <p class="text-xs text-slate-500 sm:text-sm">مشخصات هویتی، تحصیلات و راه‌های ارتباطی مددکار</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">نام <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="first_name" placeholder="مثلاً علی" class="{{ $errors->has('first_name') ? $inputInvalidClass : $inputClass }}">
                    @error('first_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">نام خانوادگی <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="last_name" placeholder="مثلاً محمدی" class="{{ $errors->has('last_name') ? $inputInvalidClass : $inputClass }}">
                    @error('last_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">کد ملی <span class="text-rose-500">*</span></label>
                    <input type="text" inputmode="numeric" maxlength="10" wire:model.blur="national_id" placeholder="کد ملی ۱۰ رقمی" class="{{ $errors->has('national_id') ? $inputInvalidClass : $inputClass }} text-center">
                    @error('national_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">شماره شناسنامه</label>
                    <input type="text" inputmode="numeric" wire:model.blur="id_number" class="{{ $inputClass }} text-center">
                </div>

                <div>
                    <label class="{{ $labelClass }}">تاریخ تولد</label>
                    <div class="grid grid-cols-3 gap-2">
                        <select wire:model.blur="birth_day" class="{{ $dateSelectClass }}" aria-label="روز تولد">
                            <option value="">روز</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                        <select wire:model.blur="birth_month" class="{{ $dateSelectClass }}" aria-label="ماه تولد">
                            <option value="">ماه</option>
                            @foreach($months as $key => $month)
                                <option value="{{ $key }}">{{ $month }}</option>
                            @endforeach
                        </select>
                        <select wire:model.blur="birth_year" class="{{ $dateSelectClass }}" aria-label="سال تولد">
                            <option value="">سال</option>
                            @foreach(range(1320, 1410) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">شماره موبایل <span class="text-rose-500">*</span></label>
                    <input type="text" inputmode="numeric" maxlength="11" wire:model.blur="mobile" placeholder="09120000000" class="{{ $errors->has('mobile') ? $inputInvalidClass : $inputClass }} text-center">
                    @error('mobile') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="academic_level_id" class="{{ $labelClass }}">تحصیلات</label>
                    <select wire:model="academic_level_id" id="academic_level_id" class="{{ $errors->has('academic_level_id') ? $selectInvalidClass : $selectClass }}">
                        <option value="">— انتخاب کنید —</option>
                        @foreach($academicLevels as $level)
                            <option value="{{ $level->id }}">{{ $level->title }}</option>
                        @endforeach
                    </select>
                    @error('academic_level_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="occupation_id" class="{{ $labelClass }}">شغل</label>
                    <select wire:model="occupation_id" id="occupation_id" class="{{ $errors->has('occupation_id') ? $selectInvalidClass : $selectClass }}">
                        <option value="">— انتخاب شغل —</option>
                        @foreach($allOccupations as $occupation)
                            <option value="{{ $occupation->id }}">{{ $occupation->name }}</option>
                        @endforeach
                    </select>
                    @error('occupation_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">تعداد اعضای خانواده</label>
                    <input type="number" inputmode="numeric" wire:model.blur="family_members_count" class="{{ $inputClass }} text-center">
                </div>

                {{-- آپلود تصویر مددکار --}}
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="{{ $labelClass }}">تصویر مددکار</label>
                    <div class="flex flex-col gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 sm:flex-row sm:items-center">
                        <div class="flex h-24 w-full shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white sm:order-2 sm:w-28">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover" alt="پیش‌نمایش تصویر مددکار">
                            @else
                                <span class="flex flex-col items-center gap-1.5 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 6h.008v.008H18V6Zm2.25 12H3.75A1.5 1.5 0 0 1 2.25 16.5V7.5A1.5 1.5 0 0 1 3.75 6h16.5a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5Z"/>
                                    </svg>
                                    <span class="text-[11px] font-bold">تصویر انتخاب نشده</span>
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1 sm:order-1">
                            <input type="file" accept="image/jpeg,image/png" wire:model="photo" class="w-full text-sm text-slate-600 transition file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-cyan-50 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-cyan-700 hover:file:bg-cyan-100 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            <p class="mt-2 text-[11px] font-semibold text-slate-500">فرمت JPG یا PNG — حداکثر ۲ مگابایت</p>
                            <div wire:loading wire:target="photo" class="mt-1.5 flex items-center gap-1.5 text-xs font-bold text-cyan-700">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-600"></span>
                                در حال آپلود...
                            </div>
                            @error('photo') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- بخش دوم: اطلاعات حرفه‌ای و منطقه خدمت --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="{{ $sectionHeaderIconClass }}" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-800 sm:text-lg">اطلاعات حرفه‌ای و منطقه خدمت</h2>
                    <p class="text-xs text-slate-500 sm:text-sm">سابقه همکاری، منطقه تحت پوشش و آمار مددجویان</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">تاریخ شروع همکاری</label>
                    <div class="grid grid-cols-3 gap-2">
                        <select wire:model.blur="start_day" class="{{ $dateSelectClass }}" aria-label="روز شروع همکاری">
                            <option value="">روز</option>
                            @foreach(range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                        <select wire:model.blur="start_month" class="{{ $dateSelectClass }}" aria-label="ماه شروع همکاری">
                            <option value="">ماه</option>
                            @foreach($months as $key => $month)
                                <option value="{{ $key }}">{{ $month }}</option>
                            @endforeach
                        </select>
                        <select wire:model.blur="start_year" class="{{ $dateSelectClass }}" aria-label="سال شروع همکاری">
                            <option value="">سال</option>
                            @foreach(range(1380, 1410) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label for="district_id" class="{{ $labelClass }}">منطقه تحت پوشش</label>
                    <select wire:model="district_id" id="district_id" class="{{ $errors->has('district_id') ? $selectInvalidClass : $selectClass }}">
                        <option value="">— انتخاب منطقه —</option>
                        @foreach($allDistricts as $district)
                            <option value="{{ $district->id }}">{{ $district->name }}</option>
                        @endforeach
                    </select>
                    @error('district_id') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">تعداد افراد تحت پوشش</label>
                    <input type="number" inputmode="numeric" wire:model.blur="covered_people_count" class="{{ $inputClass }} text-center font-bold">
                </div>
                <div>
                    <label class="{{ $labelClass }}">تعداد خانوار تحت پوشش</label>
                    <input type="number" inputmode="numeric" wire:model.blur="covered_households_count" class="{{ $inputClass }} text-center font-bold">
                </div>
                <div>
                    <label class="{{ $labelClass }}">تعداد کودکان تحت پوشش</label>
                    <input type="number" inputmode="numeric" wire:model.blur="covered_children_count" class="{{ $inputClass }} text-center font-bold">
                </div>
            </div>
        </section>

        {{-- بخش سوم: همکار جایگزین --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="{{ $sectionHeaderIconClass }}" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-800 sm:text-lg">اطلاعات همکار جایگزین <span class="text-xs font-semibold text-slate-400">(اختیاری)</span></h2>
                    <p class="text-xs text-slate-500 sm:text-sm">در صورت عدم حضور مددکار، همکار جایگزین پاسخگو خواهد بود</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">نام همکار جایگزین</label>
                    <input type="text" wire:model.blur="substitute_first_name" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">نام خانوادگی همکار جایگزین</label>
                    <input type="text" wire:model.blur="substitute_last_name" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">تلفن همراه جایگزین</label>
                    <input type="text" inputmode="numeric" maxlength="11" wire:model.blur="substitute_mobile" placeholder="09120000000" class="{{ $inputClass }} text-center">
                </div>
            </div>
        </section>

        {{-- بخش چهارم: حساب کاربری مددکار --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="{{ $sectionHeaderIconClass }}" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.007a2.25 2.25 0 0 0-2.25-2.25H4.5a2.25 2.25 0 0 0-2.25 2.25v6.007a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-800 sm:text-lg">حساب کاربری مددکار</h2>
                    <p class="text-xs text-slate-500 sm:text-sm">اطلاعات ورود به پنل مددکار — پس از ثبت قابل تغییر است</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">نام کاربری ورود <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.blur="account_username" placeholder="مثال: sw01" dir="ltr" class="{{ $errors->has('account_username') ? $inputInvalidClass : $inputClass }} text-left">
                    @error('account_username') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">رمز عبور <span class="text-rose-500">*</span></label>
                    <input type="password" wire:model.blur="account_password" placeholder="حداقل ۸ کاراکتر" class="{{ $errors->has('account_password') ? $inputInvalidClass : $inputClass }}">
                    @error('account_password') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">تکرار رمز عبور <span class="text-rose-500">*</span></label>
                    <input type="password" wire:model.blur="account_password_confirmation" placeholder="تکرار رمز عبور" class="{{ $inputClass }}">
                </div>
            </div>
        </section>

        {{-- دکمه‌های عملیاتی --}}
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row-reverse sm:items-center sm:justify-start">
            <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-[#53BEEA] px-8 text-sm font-bold text-white shadow-sm transition hover:bg-[#45b5e6] focus:outline-none focus:ring-4 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-11 sm:w-auto">
                <span wire:loading.remove wire:target="save">ثبت اطلاعات مددکار</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    در حال ثبت...
                </span>
            </button>
            <button type="button" onclick="history.back()" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-6 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 sm:min-h-11 sm:w-auto">
                انصراف
            </button>
        </div>
    </form>
</div>
