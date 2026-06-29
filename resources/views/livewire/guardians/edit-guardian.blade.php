<div>
    <div class="container mx-auto p-4">
        <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/25 to-white p-5 shadow-sm">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">ویرایش اطلاعات سرپرست</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ trim(($guardian->first_name ?? '') . ' ' . ($guardian->last_name ?? '')) ?: 'سرپرست بدون نام' }}
                        <span class="mx-1">|</span>
                        کد سرپرست: {{ $guardian->guardian_code }}
                    </p>
                </div>
                <button type="button" wire:click="backToList" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    بازگشت به لیست سرپرستان
                </button>
            </div>

            <div class="mb-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-amber-100 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">تعداد مددجویان مرتبط</p>
                    <p class="mt-1 text-lg font-extrabold text-amber-700">{{ $guardian->people->count() }} نفر</p>
                </div>
                <div class="rounded-xl border border-sky-100 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">آخرین به‌روزرسانی</p>
                    <p class="mt-1 text-sm font-bold text-sky-700">{{ optional($guardian->updated_at)->format('Y/m/d H:i') ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">کد سرپرست</p>
                    <p class="mt-1 text-lg font-extrabold text-emerald-700">{{ $guardian->guardian_code }}</p>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="mb-2 font-semibold">لطفا خطاهای زیر را رفع کنید:</p>
                    <ul class="list-disc pe-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $fieldClass = function (string $field, string $extra = '') use ($errors): string {
                    $stateClass = $errors->has($field)
                        ? 'border-rose-300 bg-rose-50/40 text-rose-900 focus:border-rose-400 focus:ring-rose-100'
                        : 'border-slate-200 focus:border-amber-400 focus:ring-amber-100';

                    return trim('w-full rounded-xl border px-3 py-2 text-sm transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 '.$stateClass.' '.$extra);
                };
            @endphp

            <form wire:submit.prevent="save" class="space-y-5">
                <div class="sticky top-0 z-20 -mx-1 rounded-xl border border-slate-200 bg-white/95 px-3 py-2 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/85">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-500">فرم ویرایش سرپرست</p>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="backToList" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                انصراف
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-amber-400 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-amber-500 disabled:cursor-wait disabled:opacity-70">
                                <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
                                <span wire:loading wire:target="save">در حال ذخیره...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">مشخصات اصلی سرپرست</h2>
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">کد ملی</label>
                            <input type="text" wire:model.blur="national_code" maxlength="10" @class([$fieldClass('national_code')])>
                            @error('national_code')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نام</label>
                            <input type="text" wire:model.blur="first_name" @class([$fieldClass('first_name')])>
                            @error('first_name')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نام خانوادگی</label>
                            <input type="text" wire:model.blur="last_name" @class([$fieldClass('last_name')])>
                            @error('last_name')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">موبایل</label>
                            <input type="text" wire:model.blur="guardian_phone_number" maxlength="11" @class([$fieldClass('guardian_phone_number')])>
                            @error('guardian_phone_number')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">سال تولد</label>
                            <input type="number" wire:model.blur="guardian_birth_year" @class([$fieldClass('guardian_birth_year')])>
                            @error('guardian_birth_year')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">ماه تولد</label>
                            <input type="number" wire:model.blur="guardian_birth_month" @class([$fieldClass('guardian_birth_month')])>
                            @error('guardian_birth_month')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">روز تولد</label>
                            <input type="number" wire:model.blur="guardian_birth_day" @class([$fieldClass('guardian_birth_day')])>
                            @error('guardian_birth_day')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">مددکار</label>
                            <select wire:model="social_worker_id" @class([$fieldClass('social_worker_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($socialWorkers as $worker)
                                    <option value="{{ $worker->id }}">{{ trim(($worker->first_name ?? '') . ' ' . ($worker->last_name ?? '')) }}</option>
                                @endforeach
                            </select>
                            @error('social_worker_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">وضعیت معیشتی</h2>
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">تعداد فرزندان در منزل</label>
                            <input type="number" value="{{ (int) ($children_in_house ?? 0) }}" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                            <p class="mt-1 text-[11px] font-medium text-slate-500">این عدد بر اساس اطلاعات خانوار محاسبه می‌شود.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">دهک اقتصادی</label>
                            <input type="number" min="1" max="10" wire:model.blur="economic_decile" @class([$fieldClass('economic_decile')])>
                            @error('economic_decile')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شغل</label>
                            <select wire:model="occupation_id" @class([$fieldClass('occupation_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($occupations as $occupation)
                                    <option value="{{ $occupation->id }}">{{ $occupation->name }}</option>
                                @endforeach
                            </select>
                            @error('occupation_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نوع شغل</label>
                            <select wire:model="job_type_id" @class([$fieldClass('job_type_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($jobTypes as $jobType)
                                    <option value="{{ $jobType->id }}">{{ $jobType->name }}</option>
                                @endforeach
                            </select>
                            @error('job_type_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div x-data="{ amount: @js($average_income ?? '') }">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">متوسط درآمد ماهیانه (ریال)</label>
                            <input type="number" wire:model.blur="average_income" x-model="amount" @class([$fieldClass('average_income')])>
                            <p x-show="amount !== '' && amount !== null" x-cloak class="mt-1 text-xs font-medium text-emerald-600" x-text="new Intl.NumberFormat('fa-IR').format(Number(amount || 0)) + ' ریال'"></p>
                            @error('average_income')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">وضعیت بیمه</label>
                            <select wire:model.live="insurance_status" @class([$fieldClass('insurance_status')])>
                                <option value="0">ندارد</option>
                                <option value="1">دارد</option>
                            </select>
                            <p class="mt-1 text-[11px] font-medium text-slate-500">در صورت انتخاب «دارد»، نوع بیمه باید مشخص شود.</p>
                            @error('insurance_status')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نوع بیمه</label>
                            <select wire:model="insurance_type_id" @disabled(! (bool) $insurance_status) @class([$fieldClass('insurance_type_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($insuranceTypes as $insuranceType)
                                    <option value="{{ $insuranceType->id }}">{{ $insuranceType->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] font-medium {{ (bool) $insurance_status ? 'text-slate-500' : 'text-slate-400' }}">
                                {{ (bool) $insurance_status ? 'انتخاب نوع بیمه الزامی است.' : 'با انتخاب وضعیت بیمه «دارد» فعال می‌شود.' }}
                            </p>
                            @error('insurance_type_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">اشتغال اعضای خانواده</label>
                            <select wire:model.live="any_family_employed" @class([$fieldClass('any_family_employed')])>
                                <option value="0">ندارد</option>
                                <option value="1">دارد</option>
                            </select>
                            <p class="mt-1 text-[11px] font-medium text-slate-500">در صورت انتخاب «دارد»، شرح اشتغال اعضا باید ثبت شود.</p>
                            @error('any_family_employed')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شرح اشتغال اعضا</label>
                            <input type="text" wire:model.blur="any_family_employed_description" @disabled(! (bool) $any_family_employed) @class([$fieldClass('any_family_employed_description')])>
                            <p class="mt-1 text-[11px] font-medium {{ (bool) $any_family_employed ? 'text-slate-500' : 'text-slate-400' }}">
                                {{ (bool) $any_family_employed ? 'نام عضو، نوع کار یا منبع درآمد را وارد کنید.' : 'با انتخاب اشتغال اعضای خانواده «دارد» فعال می‌شود.' }}
                            </p>
                            @error('any_family_employed_description')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-0 flex-1">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">اعضای غیرمددجوی ساکن در خانوار</label>
                                <input type="text" wire:model.blur="new_extra_household_member_description" wire:keydown.enter.prevent="addExtraHouseholdMember" maxlength="255" placeholder="مثلا: مادربزرگ، فرزند غیرفعال پرونده" @class([$fieldClass('new_extra_household_member_description', 'bg-white')])>
                                @error('new_extra_household_member_description')
                                    <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="button" wire:click="addExtraHouseholdMember" class="inline-flex h-10 items-center justify-center rounded-xl border border-amber-300 bg-amber-400 px-4 text-xs font-bold text-white transition hover:bg-amber-500">
                                افزودن
                            </button>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse($extra_household_members as $index => $member)
                                <div wire:key="extra-household-member-{{ $index }}" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2">
                                    <input type="text" wire:model.blur="extra_household_members.{{ $index }}.description" maxlength="255" @class([$fieldClass("extra_household_members.$index.description", 'min-w-0 flex-1 rounded-lg')])>
                                    <button type="button" wire:click="removeExtraHouseholdMember({{ $index }})" class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-bold text-rose-600 transition hover:bg-rose-100">
                                        حذف
                                    </button>
                                </div>
                                @error("extra_household_members.$index.description")
                                    <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            @empty
                                <p class="text-xs font-medium text-slate-500">عضو غیرمددجوی اضافه‌ای برای این خانوار ثبت نشده است.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">وسیله نقلیه و اطلاعات بانکی سرپرست</h2>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">وضعیت خودرو</label>
                            <select wire:model.live="has_vehicle" @class([$fieldClass('has_vehicle')])>
                                <option value="0">ندارد</option>
                                <option value="1">دارد</option>
                            </select>
                            <p class="mt-1 text-[11px] font-medium text-slate-500">در صورت انتخاب «دارد»، نوع و مالکیت وسیله باید مشخص شود.</p>
                            @error('has_vehicle')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نوع وسیله</label>
                            <select wire:model="vehicle_type_id" @disabled(! (bool) $has_vehicle) @class([$fieldClass('vehicle_type_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($vehicleTypes as $vehicleType)
                                    <option value="{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] font-medium {{ (bool) $has_vehicle ? 'text-slate-500' : 'text-slate-400' }}">
                                {{ (bool) $has_vehicle ? 'انتخاب نوع وسیله الزامی است.' : 'با انتخاب وضعیت خودرو «دارد» فعال می‌شود.' }}
                            </p>
                            @error('vehicle_type_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">مالکیت وسیله</label>
                            <select wire:model="vehicle_ownership_type" @disabled(! (bool) $has_vehicle) @class([$fieldClass('vehicle_ownership_type')])>
                                <option value="">انتخاب نشده</option>
                                <option value="personal">شخصی</option>
                                <option value="company">شراکتی</option>
                                <option value="rented">استیجاری</option>
                            </select>
                            <p class="mt-1 text-[11px] font-medium {{ (bool) $has_vehicle ? 'text-slate-500' : 'text-slate-400' }}">
                                {{ (bool) $has_vehicle ? 'انتخاب مالکیت وسیله الزامی است.' : 'با انتخاب وضعیت خودرو «دارد» فعال می‌شود.' }}
                            </p>
                            @error('vehicle_ownership_type')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">بانک</label>
                            <select wire:model="bank_id" @class([$fieldClass('bank_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره کارت</label>
                            <input type="text" wire:model.blur="card_number" maxlength="16" @class([$fieldClass('card_number')])>
                            @error('card_number')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره شبا</label>
                            <input type="text" wire:model.blur="sheba_number" maxlength="26" @class([$fieldClass('sheba_number')])>
                            @error('sheba_number')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره کارت یارانه</label>
                            <input type="text" wire:model.blur="subsidy_card_number" maxlength="16" @class([$fieldClass('subsidy_card_number')])>
                            @error('subsidy_card_number')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره شبای یارانه</label>
                            <input type="text" wire:model.blur="subsidy_sheba_number" maxlength="26" @class([$fieldClass('subsidy_sheba_number')])>
                            @error('subsidy_sheba_number')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نسبت مالک حساب</label>
                            <select wire:model="account_owner_relation_id" @class([$fieldClass('account_owner_relation_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($accountRelations as $relation)
                                    <option value="{{ $relation->id }}">{{ $relation->name }}</option>
                                @endforeach
                            </select>
                            @error('account_owner_relation_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">اطلاعات سکونت و تماس</h2>
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">وضعیت سکونت</label>
                            <select wire:model="residence_status_id" @class([$fieldClass('residence_status_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($residenceStatusTypes as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            @error('residence_status_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">ناحیه</label>
                            <select wire:model="district_id" @class([$fieldClass('district_id')])>
                                <option value="">انتخاب نشده</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                            @error('district_id')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div x-data="{ amount: @js($deposit_amount ?? '') }">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">ودیعه</label>
                            <input type="number" wire:model.blur="deposit_amount" x-model="amount" @class([$fieldClass('deposit_amount')])>
                            <p x-show="amount !== '' && amount !== null" x-cloak class="mt-1 text-xs font-medium text-emerald-600" x-text="new Intl.NumberFormat('fa-IR').format(Number(amount || 0)) + ' ریال'"></p>
                            @error('deposit_amount')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div x-data="{ amount: @js($monthly_rent ?? '') }">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">اجاره ماهانه</label>
                            <input type="number" wire:model.blur="monthly_rent" x-model="amount" @class([$fieldClass('monthly_rent')])>
                            <p x-show="amount !== '' && amount !== null" x-cloak class="mt-1 text-xs font-medium text-emerald-600" x-text="new Intl.NumberFormat('fa-IR').format(Number(amount || 0)) + ' ریال'"></p>
                            @error('monthly_rent')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">مدت سکونت (سال)</label>
                            <input type="number" wire:model.blur="residence_duration_years" @class([$fieldClass('residence_duration_years')])>
                            @error('residence_duration_years')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">تلفن ثابت</label>
                            <input type="text" wire:model.blur="landline_phone" @class([$fieldClass('landline_phone')])>
                            @error('landline_phone')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">تلفن مورد اعتماد</label>
                            <input type="text" wire:model.blur="trusted_person_phone" @class([$fieldClass('trusted_person_phone')])>
                            @error('trusted_person_phone')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره پیام‌رسان</label>
                            <input type="text" wire:model.blur="messenger_number" @class([$fieldClass('messenger_number')])>
                            @error('messenger_number')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">آدرس</label>
                        <textarea wire:model.blur="address" rows="2" @class([$fieldClass('address')])></textarea>
                        @error('address')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-3 text-base font-bold text-slate-800">مددجویان مرتبط</h2>
                    <div class="grid gap-2 md:grid-cols-3">
                        @forelse($guardian->people as $person)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <p class="font-semibold text-slate-800">{{ trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) }}</p>
                                <p class="text-xs text-slate-500">کد مددجو: {{ $person->person_code }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">هیچ مددجویی به این سرپرست متصل نیست.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="backToList" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        انصراف
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-amber-400 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-500 disabled:cursor-wait disabled:opacity-70">
                        <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
                        <span wire:loading wire:target="save">در حال ذخیره...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
