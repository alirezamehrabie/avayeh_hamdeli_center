<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mb-0">فرم ثبت‌نام مددجوی جدید</h3>
        </div>
        <div class="card-body">

            {{-- شروع فرم --}}
            <form wire:submit.prevent="save">
                {{-- فعلا متد save نداریم، فقط برای جلوگیری از ریلود --}}

                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات فردی مددجو</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="first_name">
                            @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="last_name">
                            @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" maxlength="10" wire:model="national_id">
                            @error('national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- تاریخ تولد 3 بخشی --}}
                        <div class="col-md-4">
                            <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model="birth_day" class="form-select">
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day) <option value="{{ $day }}">{{ $day }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model="birth_month" class="form-select">
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month) <option value="{{ $key }}">{{ $month }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model="birth_year" class="form-select">
                                        <option value="">سال</option>
                                        @foreach(range(1300, 1420) as $year) <option value="{{ $year }}">{{ $year }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            @error('birth_year') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام پدر</label>
                            <input type="text" class="form-control" wire:model="father_name">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">کد ملی پدر</label>
                            <input type="text" class="form-control" maxlength="10" wire:model="father_national_id">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">کد ملی مادر</label>
                            <input type="text" class="form-control" maxlength="10" wire:model="mother_national_id">
                        </div>

                        {{-- جنسیت --}}
                        <div class="col-md-2">
                            <label class="form-label">جنسیت <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="مرد" wire:model="gender">
                                    <label class="form-check-label">مرد</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="زن" wire:model="gender">
                                    <label class="form-check-label">زن</label>
                                </div>
                            </div>
                            @error('gender') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- وضعیت سادات (با wire:model.live برای تغییر آنی) --}}
                        <div class="form-group col-md-2">
                            <label class="d-block">وضعیت سادات <span class="text-danger">*</span></label>
                            <label class="radio-inline me-3">
                                <input type="radio" value="عام" wire:model.live="sadaat_status"> عام
                            </label>
                            <label class="radio-inline">
                                <input type="radio" value="سادات" wire:model.live="sadaat_status"> سادات
                            </label>
                        </div>

                        {{-- نسب سادات: نمایش شرطی با Blade --}}
                        @if($sadaat_status === 'سادات')
                            <div class="form-group col-md-3">
                                <label>نسب سادات <span class="text-danger">*</span></label>
                                <select wire:model="sadaat_relation_id" class="form-control">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($sadaatRelations as $rel)
                                        <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                                    @endforeach
                                </select>
                                @error('sadaat_relation_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">نقش در خانواده <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="role">
                                <option value="فرزند">فرزند</option>
                                <option disabled value="سرپرست">سرپرست</option>
                            </select>
                        </div>

                        {{-- بخش انتخاب مهارت‌ها --}}
                        <div class="col-12 mb-3">
                            <label class="form-label font-bold">مهارت‌ها و استعدادها</label>
                            <div class="card p-3 bg-light">
                                <div class="row">
                                    @forelse($allSkills as $skill)
                                        <div class="col-md-3 col-sm-6 mb-2">
                                            <div class="form-check">
                                                <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        value="{{ $skill->id }}"
                                                        wire:model="skills"
                                                        id="skill_{{ $skill->id }}"
                                                >
                                                <label class="form-check-label" for="skill_{{ $skill->id }}">
                                                    {{ $skill->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted">هیچ مهارتی در سیستم تعریف نشده است.</div>
                                    @endforelse
                                </div>
                            </div>
                            @error('skills') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group mt-2 col-12">
                            <label>توضیحات استعداد:</label>
                            <textarea wire:model="skills_description" class="form-control"></textarea>
                        </div>

                        {{-- معلولیت (با wire:model.live) --}}
                        <div class="col-md-2 mb-3">
                            <label class="form-label">آیا دارای معلولیت هست؟</label>
                            <div>
                                <input type="radio" value="1" wire:model.live="has_disability"> <label>بله</label>
                                <input type="radio" value="0" wire:model.live="has_disability"> <label>خیر</label>
                            </div>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">نوع معلولیت</label>
                            {{-- اگر معلولیت ندارد، فیلد غیرفعال شود --}}
                            <select class="form-select" wire:model="disability_type_id" @if($has_disability != '1') disabled @endif>
                                <option value="">انتخاب کنید...</option>
                                @foreach($disabilityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">توضیحات معلولیت</label>
                            <textarea class="form-control h-1" wire:model="disability_description" @if($has_disability != '1') disabled @endif></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">مددکار مسئول <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="social_worker_id">
                                <option value="">انتخاب کنید...</option>
                                @foreach($socialWorkers as $worker)
                                    <option value="{{ $worker->id }}">{{ $worker->fullName }} ({{ $worker->role }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تصویر کارت ملی</label>
                            <input class="form-control" type="file" wire:model="photo_id_card">
                            @error('photo_id_card') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تصویر شناسنامه</label>
                            <input class="form-control" type="file" wire:model="photo_birth_certificate">
                            @error('photo_birth_certificate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                    </div>
                </div>
                {{-- جدا کننده بخش‌ها --}}

                <hr class="my-5 border-2">

                {{-- بخش 2: وضعیت خانوادگی --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">وضعیت خانوادگی</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">نسبت سرپرست با مددجو <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="guardian_relation_type_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($guardianRelationTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->title }}</option>
                                @endforeach
                            </select>
                            @error('guardian_relation_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">دهک اقتصادی</label>
                            <select class="form-select" wire:model="economic_decile">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($deciles as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- وضعیت حیات والدین --}}
                        <div class="col-md-4 mb-3">
                            <label for="living_parents" class="form-label">وضعیت حیات والدین</label>
                            <select wire:model.live="living_parents" id="living_parents" class="form-select">
                                <option value="">انتخاب کنید...</option>
                                {{-- مقادیر انگلیسی برای دیتابیس - متن فارسی برای کاربر --}}
                                <option value="both_alive">هر دو در قید حیات</option>
                                <option value="father_dead">پدر فوت شده</option>
                                <option value="mother_dead">مادر فوت شده</option>
                                <option value="both_dead">هر دو فوت شده</option>
                            </select>
                            @error('living_parents')
                            <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- فیلدهای شرطی فوت --}}
                        @if(in_array($living_parents, ['father_dead', 'mother_dead', 'both_dead']))
                            <div class="col-12 bg-light p-3 rounded border">
                                <h6 class="text-muted mb-3">اطلاعات فوت</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">سال فوت</label>
                                        <input type="number" class="form-control" wire:model="death_year" placeholder="مثال: 1399">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">علت فوت</label>
                                        <input type="text" class="form-control" wire:model="death_reason">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-4 mb-3">
                            <label for="divorced_parent" class="form-label">وضعیت طلاق والدین</label>
                            <select wire:model="divorced_parent" id="divorced_parent" class="form-select">
                                <option value="">انتخاب کنید...</option>
                                <option value="none">خیر (طلاق نگرفته‌اند)</option>
                                <option value="divorced">بله (طلاق گرفته‌اند)</option>
                            </select>
                            @error('divorced_parent')
                            <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- remarried_parent --}}
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="remarried_parent">ازدواج مجدد والدین</label>
                            <select wire:model="remarried_parent" id="remarried_parent" class="form-select">
                                <option value="">انتخاب کنید...</option>
                                {{-- مقادیر انگلیسی برای دیتابیس - متن فارسی برای نمایش --}}
                                <option value="none">خیر</option>
                                <option value="father">فقط پدر</option>
                                <option value="mother">فقط مادر</option>
                                <option value="both">هر دو</option>
                            </select>
                            @error('remarried_parent')
                            <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">فرزندان از ازدواج دیگر</label>
                            <input type="number" class="form-control" wire:model="children_from_previous_marriage" placeholder="تعداد">
                        </div>

                        {{-- معلولیت والدین --}}
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="has_parent_disability" wire:model.live="has_parent_disability">
                                <label class="form-check-label" for="has_parent_disability">
                                    والدین دارای معلولیت هستند؟
                                </label>
                            </div>
                        </div>

                        @if($has_parent_disability)
                            <div class="col-md-12">
                                <label class="form-label">توضیحات معلولیت والدین</label>
                                <textarea class="form-control" wire:model="parent_disability_description" rows="2"></textarea>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 3: اطلاعات سرپرست --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات سرپرست و معیشت</h4>

                    {{-- نمایش هشدار داینامیک بر اساس انتخاب نسبت در مرحله قبل --}}
                    @if($guardian_relation_type_id)
                        @php
                            $relName = $guardianRelationTypes->find($guardian_relation_type_id)?->name;
                        @endphp
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="bi bi-info-circle-fill fs-4 me-2"></i>
                            <div>
                                شما در حال تکمیل اطلاعات برای <strong>{{ $relName }}</strong> هستید.
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        {{-- تاریخ تولد سرپرست --}}
                        <div class="col-md-5">
                            <label class="form-label">تاریخ تولد سرپرست</label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model="guardian_birth_day" class="form-select">
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day) <option value="{{ $day }}">{{ $day }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model="guardian_birth_month" class="form-select">
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month) <option value="{{ $key }}">{{ $month }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model="guardian_birth_year" class="form-select">
                                        <option value="">سال</option>
                                        @foreach(range(1300, 1420) as $year) <option value="{{ $year }}">{{ $year }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">شماره تماس سرپرست</label>
                            <input type="text" class="form-control" wire:model="guardian_phone_number" maxlength="11">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شغل سرپرست</label>
                            <select class="form-select" wire:model="occupation_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($occupations as $job)
                                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نوع شغل</label>
                            <select class="form-select" wire:model="job_type_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($jobTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- وضعیت بیمه (شرطی) --}}
                        <div class="col-md-4">
                            <label class="form-label">وضعیت بیمه</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="insurance_status">
                                    <label class="form-check-label">دارد</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="insurance_status">
                                    <label class="form-check-label">ندارد</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نوع بیمه</label>
                            <select class="form-select" wire:model="insurance_type_id" @if($insurance_status != '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($insuranceTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">تعداد فرزندان</label>
                            <input type="number" class="form-control" wire:model="children_count">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">فرزندان ساکن در منزل</label>
                            <input type="number" class="form-control" wire:model="children_in_house">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">فرزند طلاق ساکن در منزل</label>
                            <input type="text" class="form-control" wire:model="divorced_child_at_home" placeholder="توضیحات">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">میانگین درآمد ماهیانه (تومان)</label>
                            <input type="text" class="form-control" wire:model="average_income">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">آیا فرد دیگری در خانواده شاغل است؟</label>
                            <select class="form-select" wire:model="any_family_employed">
                                <option value="0">خیر</option>
                                <option value="1">بله</option>
                            </select>
                        </div>

                        {{-- وضعیت خودرو (شرطی) --}}
                        <div class="col-md-4">
                            <label class="form-label">خودرو دارد؟</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="has_vehicle">
                                    <label class="form-check-label">بله</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="has_vehicle">
                                    <label class="form-check-label">خیر</label>
                                </div>
                            </div>
                        </div>

                        {{-- نمایش نوع خودرو فقط اگر "بله" باشد --}}
                        @if($has_vehicle == '1')
                            <div class="col-md-4">
                                <label class="form-label">نوع خودرو <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model="vehicle_type_id">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($vehicleTypes as $vType)
                                        <option value="{{ $vType->id }}">{{ $vType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 4: اطلاعات سکونت و تماس --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات سکونت و تماس</h4>

                    <div class="row g-3">
                        {{-- وضعیت مالکیت --}}
                        <div class="col-md-4">
                            <label class="form-label">وضعیت مالکیت مسکن <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="residence_status_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($residenceStatusTypes as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            @error('residence_status_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- منطق نمایش فیلدهای اجاره: اگر "مستاجر" در نام وضعیت باشد --}}
                        @php
                            $selectedStatusName = $residenceStatusTypes->find($residence_status_id)?->name ?? '';
                            $isTenant = str_contains($selectedStatusName, 'مستاجر') || str_contains($selectedStatusName, 'اجاره');
                        @endphp

                        @if($isTenant)
                            <div class="col-md-4">
                                <label class="form-label">مبلغ رهن (تومان)</label>
                                <input type="text" class="form-control" wire:model="deposit_amount">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">اجاره ماهیانه (تومان)</label>
                                <input type="text" class="form-control" wire:model="monthly_rent">
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">منطقه شهرداری</label>
                            <select class="form-select" wire:model="district_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">وضعیت بومی بودن</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model="is_local_to_city">
                                    <label class="form-check-label">بومی</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model="is_local_to_city">
                                    <label class="form-check-label">غیر بومی</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">سابقه سکونت (سال)</label>
                            <input type="number" class="form-control" wire:model="residence_duration_years">
                        </div>

                        <div class="col-12">
                            <label class="form-label">آدرس دقیق منزل <span class="text-danger">*</span></label>
                            <textarea class="form-control" wire:model="address" rows="2"></textarea>
                            @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <hr class="my-4 col-12 border-secondary-subtle">
                        <h6 class="text-muted col-12 mb-2">اطلاعات تماس</h6>

                        <div class="col-md-4">
                            <label class="form-label">تلفن همراه (شخصی/اصلی)</label>
                            <input type="text" class="form-control" wire:model="personal_phone" maxlength="11">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تلفن ثابت</label>
                            <input type="text" class="form-control" wire:model="landline_phone">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تلفن فرد معتمد (ضروری)</label>
                            <input type="text" class="form-control" wire:model="trusted_person_phone" maxlength="11">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">پیام‌رسان اصلی</label>
                            <select class="form-select" wire:model="messenger_type">
                                <option value="">— انتخاب کنید —</option>
                                <option value="eitaa">ایتا</option>
                                <option value="rubika">روبیکا</option>
                                <option value="whatsapp">واتساپ</option>
                                <option value="telegram">تلگرام</option>
                                <option value="other">سایر</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره در پیام‌رسان</label>
                            <input type="text" class="form-control" wire:model="messenger_number" placeholder="اگر متفاوت با همراه است وارد کنید">
                        </div>

                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 5: اطلاعات مالی، تحصیلی و حمایتی --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات مالی، تحصیلی و حمایتی</h4>
                    <div class="row g-3">

                        {{-- --- بانک و حساب --- --}}
                        <div class="col-md-3">
                            <label class="form-label">حساب شخصی دارد؟ <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="has_own_account">
                                    <label class="form-check-label">بله</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="has_own_account">
                                    <label class="form-check-label">خیر</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">نسبت دارنده حساب</label>
                            {{-- اگر حساب شخصی دارد، این فیلد را قفل کن تا کاربر نتواند تغییر دهد (چون خودکار ست شده) --}}
                            <select class="form-select" wire:model.live="account_owner_relation_id" @if($has_own_account == '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($accountRelations as $relation)
                                    <option value="{{ $relation->id }}">{{ $relation->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- نمایش فیلد متنی اگر "سایر" انتخاب شده باشد --}}
                        @php
                            $selectedRelationName = $accountRelations->find($account_owner_relation_id)?->name ?? '';
                        @endphp
                        @if($selectedRelationName === 'سایر')
                            <div class="col-md-3">
                                <label class="form-label">نام نسبت (سایر)</label>
                                <input type="text" class="form-control" wire:model="other_account_owner_relation" placeholder="مثلاً: همسایه...">
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label">نام بانک</label>
                            <select class="form-select" wire:model="bank_id">
                                <option value="">انتخاب کنید...</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">شماره کارت</label>
                            <input type="text" class="form-control dir-ltr" wire:model="card_number" placeholder="16 رقم بدون خط تیره" maxlength="19">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">شماره کارت یارانه</label>
                            <input type="text" class="form-control dir-ltr" wire:model="subsidy_card_number" maxlength="19">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">شماره شبا</label>
                            <input type="text" class="form-control dir-ltr text-start" wire:model="sheba_number" placeholder="IR...">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">شماره شبا یارانه</label>
                            <input type="text" class="form-control dir-ltr text-start" wire:model="subsidy_sheba_number" placeholder="IR...">
                        </div>

                        <div class="col-12"><hr class="my-4 text-muted"></div>

                        {{-- --- تحصیلات --- --}}
                        <div class="col-md-3">
                            <label class="form-label">در حال تحصیل است؟ <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="is_studying">
                                    <label class="form-check-label">بله</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="is_studying">
                                    <label class="form-check-label">خیر</label>
                                </div>
                            </div>
                        </div>

                        @if($is_studying == '1')
                            <div class="col-md-3">
                                <label class="form-label">نام مدرسه/دانشگاه</label>
                                <input type="text" class="form-control" wire:model="school_name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">رشته تحصیلی</label>
                                <input type="text" class="form-control" wire:model="major">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">مقطع تحصیلی</label>
                                <select wire:model="education_level_id" class="form-select">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($educationLevels as $level)
                                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            {{-- اگر محصل نیست، دلیل ترک تحصیل --}}
                            <div class="col-md-9">
                                <label class="form-label">دلیل ترک تحصیل</label>
                                <input type="text" class="form-control" wire:model="drop_reason">
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">همزمان با تحصیل کار می‌کند؟</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model="works_alongside_study">
                                    <label class="form-check-label">بله</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model="works_alongside_study">
                                    <label class="form-check-label">خیر</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">درآمد ماهانه دانش‌آموز (تومان)</label>
                            <input type="number" class="form-control" wire:model="monthly_income">
                        </div>

                        <div class="col-12"><hr class="my-4 text-muted"></div>

                        {{-- --- حمایت‌های سازمانی --- --}}
                        <div class="col-md-4">
                            <label class="form-label">نوع سازمان حمایتی</label>
                            <input type="text" class="form-control" wire:model="organization_type" placeholder="کمیته امداد، بهزیستی، ...">
                        </div>

                        {{-- تاریخ شروع پوشش (۳ فیلد) --}}
                        <div class="col-md-4">
                            <label class="form-label">تاریخ شروع پوشش حمایتی</label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model="coverage_start_day" class="form-select">
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day) <option value="{{ $day }}">{{ $day }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model="coverage_start_month" class="form-select">
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month) <option value="{{ $key }}">{{ $month }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model="coverage_start_year" class="form-select">
                                        <option value="">سال</option>
                                        @foreach(range(1380, 1410) as $year) <option value="{{ $year }}">{{ $year }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تصویر کارت پوشش</label>
                            <input class="form-control" type="file" wire:model="support_card_image">
                            @error('support_card_image') <span class="text-danger small">{{ $message }}</span> @enderror

                            {{-- پیش‌نمایش تصویر آپلود شده --}}
                            @if ($support_card_image)
                                <div class="mt-2">
                                    <img src="{{ $support_card_image->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 6: سطح نیاز و ثبت نهایی --}}
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3 font-bold">سطح نیاز</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">سطح نیاز تشخیص داده شده <span class="text-danger">*</span></label>
                        <select wire:model="need_level_id" class="form-select form-select-lg">
                            <option value="">انتخاب سطح نیاز...</option>
                            @foreach($needLevelTypes as $level)
                                <option value="{{ $level->id }}">
                                    {{ $level->title }} - {{ $level->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('need_level_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- دکمه ذخیره نهایی --}}
                <div class="d-grid gap-2 mt-5 mb-3">
                    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-lg py-3 shadow">
                        {{-- حالت لودینگ --}}
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            در حال پردازش و ذخیره اطلاعات...
                        </span>

                        {{-- حالت عادی --}}
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            ثبت نهایی اطلاعات مددجو
                        </span>
                    </button>
                </div>

                {{-- نمایش لیست خطاها در پایین فرم (اختیاری برای دیباگ راحت‌تر) --}}
                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </form> {{-- پایان فرم --}}
        </div>
    </div>
</div> {{-- پایان div اصلی کامپوننت --}}
