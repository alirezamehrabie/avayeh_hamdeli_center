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

            <form wire:submit.prevent="save" class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">مشخصات اصلی سرپرست</h2>
                    <div class="grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">کد ملی</label>
                            <input type="text" wire:model.blur="national_code" maxlength="10" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نام</label>
                            <input type="text" wire:model.blur="first_name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نام خانوادگی</label>
                            <input type="text" wire:model.blur="last_name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">موبایل</label>
                            <input type="text" wire:model.blur="guardian_phone_number" maxlength="11" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">سال تولد</label>
                            <input type="number" wire:model.blur="guardian_birth_year" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">ماه تولد</label>
                            <input type="number" wire:model.blur="guardian_birth_month" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">روز تولد</label>
                            <input type="number" wire:model.blur="guardian_birth_day" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">مددکار</label>
                            <select wire:model="social_worker_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($socialWorkers as $worker)
                                    <option value="{{ $worker->id }}">{{ trim(($worker->first_name ?? '') . ' ' . ($worker->last_name ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">وضعیت معیشتی</h2>
                    <div class="grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">تعداد فرزندان در منزل</label>
                            <input type="number" wire:model.blur="children_in_house" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">دهک اقتصادی</label>
                            <input type="number" min="1" max="10" wire:model.blur="economic_decile" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شغل</label>
                            <select wire:model="occupation_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($occupations as $occupation)
                                    <option value="{{ $occupation->id }}">{{ $occupation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نوع شغل</label>
                            <select wire:model="job_type_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($jobTypes as $jobType)
                                    <option value="{{ $jobType->id }}">{{ $jobType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div x-data="{ amount: @js($average_income ?? '') }">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">متوسط درآمد ماهیانه (ریال)</label>
                            <input type="number" wire:model.blur="average_income" x-model="amount" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <p x-show="amount !== '' && amount !== null" x-cloak class="mt-1 text-xs font-medium text-emerald-600" x-text="new Intl.NumberFormat('en-US').format(Number(amount || 0)) + ' Rial'"></p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">وضعیت بیمه</label>
                            <select wire:model.live="insurance_status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="0">ندارد</option>
                                <option value="1">دارد</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نوع بیمه</label>
                            <select wire:model="insurance_type_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($insuranceTypes as $insuranceType)
                                    <option value="{{ $insuranceType->id }}">{{ $insuranceType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">اشتغال اعضای خانواده</label>
                            <select wire:model.live="any_family_employed" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="0">ندارد</option>
                                <option value="1">دارد</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شرح اشتغال اعضا</label>
                            <input type="text" wire:model.blur="any_family_employed_description" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">وسیله نقلیه و اطلاعات بانکی سرپرست</h2>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">وضعیت خودرو</label>
                            <select wire:model.live="has_vehicle" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="0">ندارد</option>
                                <option value="1">دارد</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نوع وسیله</label>
                            <select wire:model="vehicle_type_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($vehicleTypes as $vehicleType)
                                    <option value="{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">مالکیت وسیله</label>
                            <select wire:model="vehicle_ownership_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                <option value="personal">شخصی</option>
                                <option value="company">شراکتی</option>
                                <option value="rented">استیجاری</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">بانک</label>
                            <select wire:model="bank_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره کارت</label>
                            <input type="text" wire:model.blur="card_number" maxlength="16" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره شبا</label>
                            <input type="text" wire:model.blur="sheba_number" maxlength="26" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">نسبت مالک حساب</label>
                            <select wire:model="account_owner_relation_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($accountRelations as $relation)
                                    <option value="{{ $relation->id }}">{{ $relation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="mb-4 text-base font-bold text-slate-800">اطلاعات سکونت و تماس</h2>
                    <div class="grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">وضعیت سکونت</label>
                            <select wire:model="residence_status_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($residenceStatusTypes as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">ناحیه</label>
                            <select wire:model="district_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">انتخاب نشده</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-data="{ amount: @js($deposit_amount ?? '') }">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">ودیعه</label>
                            <input type="number" wire:model.blur="deposit_amount" x-model="amount" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <p x-show="amount !== '' && amount !== null" x-cloak class="mt-1 text-xs font-medium text-emerald-600" x-text="new Intl.NumberFormat('en-US').format(Number(amount || 0)) + ' Rial'"></p>
                        </div>
                        <div x-data="{ amount: @js($monthly_rent ?? '') }">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">اجاره ماهانه</label>
                            <input type="number" wire:model.blur="monthly_rent" x-model="amount" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <p x-show="amount !== '' && amount !== null" x-cloak class="mt-1 text-xs font-medium text-emerald-600" x-text="new Intl.NumberFormat('en-US').format(Number(amount || 0)) + ' Rial'"></p>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">مدت سکونت (سال)</label>
                            <input type="number" wire:model.blur="residence_duration_years" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">تلفن ثابت</label>
                            <input type="text" wire:model.blur="landline_phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">تلفن مورد اعتماد</label>
                            <input type="text" wire:model.blur="trusted_person_phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">شماره پیام‌رسان</label>
                            <input type="text" wire:model.blur="messenger_number" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">آدرس</label>
                        <textarea wire:model.blur="address" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
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
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-amber-400 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-500">
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
