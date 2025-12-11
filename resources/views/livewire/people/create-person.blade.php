<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mb-0">فرم ثبت‌نام مددجوی جدید</h3>
        </div>
        <div class="card-body">
            {{-- شروع فرم --}}
            <form wire:submit.prevent="save">
                {{-- پیام‌های فلش برای موفقیت یا خطا --}}
                @if (session()->has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any() && $is_submitted)
                    <div class="alert alert-danger">
                        <p><strong>لطفاً خطاهای زیر را برطرف کنید:</strong></p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات فردی مددجو</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.blur="first_name">
                            @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.blur="last_name">
                            @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" maxlength="10" wire:model.live="national_id">
                            @error('national_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- تاریخ تولد 3 بخشی --}}
                        <div class="col-md-4">
                            <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model.blur="birth_day" class="form-select">
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="birth_month" class="form-select">
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month)
                                            <option value="{{ $key }}">{{ $month }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="birth_year" class="form-select">
                                        <option value="">سال</option>
                                        @foreach(range(1300, 1420) as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @error('birth_day') <span class="text-danger small">{{ $message }}</span> <br> @enderror
                            @error('birth_month') <span class="text-danger small">{{ $message }}</span> <br> @enderror
                            @error('birth_year') <span class="text-danger small">{{ $message }}</span> <br> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام پدر</label>
                            <input type="text" class="form-control" wire:model.blur="father_name">
                            @error('father_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">کد ملی پدر</label>
                            <input type="text" class="form-control" maxlength="10" wire:model.blur="father_national_id">
                            @error('father_national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">کد ملی مادر</label>
                            <input type="text" class="form-control" maxlength="10" wire:model.blur="mother_national_id">
                            @error('mother_national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- جنسیت --}}
                        <div class="col-md-2">
                            <label class="form-label">جنسیت <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="مرد" wire:model.live="gender"
                                           id="gender_male">
                                    <label class="form-check-label" for="gender_male">مرد</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="زن" wire:model.live="gender"
                                           id="gender_female">
                                    <label class="form-check-label" for="gender_female">زن</label>
                                </div>
                            </div>
                            @error('gender') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- وضعیت سادات (با wire:model.live برای تغییر آنی) --}}
                        <div class="form-group col-md-2">
                            <label class="d-block">وضعیت سادات <span class="text-danger">*</span></label>
                            <label class="radio-inline me-3">
                                <input type="radio" value="عام" wire:model.live="sadaat_status"
                                       id="sadaat_status_general"> عام
                            </label>
                            <label class="radio-inline">
                                <input type="radio" value="سادات" wire:model.live="sadaat_status"
                                       id="sadaat_status_sadaat"> سادات
                            </label>
                            @error('sadaat_status') <span
                                class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- نسب سادات: نمایش شرطی با Blade --}}
                        @if($sadaat_status === 'سادات')
                            <div class="form-group col-md-3">
                                <label>نسب سادات <span class="text-danger">*</span></label>
                                <select wire:model.blur="sadaat_relation_id" class="form-control">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($sadaatRelations as $rel)
                                        <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                                    @endforeach
                                </select>
                                @error('sadaat_relation_id') <span
                                    class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">نقش در خانواده <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="role">
                                <option value="فرزند">فرزند</option>
                                {{-- <option value="سرپرست">سرپرست</option> --}}
                            </select>
                            @error('role') <span class="text-danger small">{{ $message }}</span> @enderror
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
                                                    wire:model.live="skills"
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
                            @error('skills') <span
                                class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group mt-2 col-12">
                            <label>توضیحات استعداد:</label>
                            <textarea wire:model.blur="skills_description" class="form-control"></textarea>
                            @error('skills_description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- معلولیت (با wire:model.live) --}}
                        <div class="col-md-2 mb-3">
                            <label class="form-label">آیا دارای معلولیت هست؟</label>
                            <div>
                                <input type="radio" value="1" wire:model.live="has_disability" id="has_disability_yes">
                                <label for="has_disability_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="has_disability" id="has_disability_no">
                                <label for="has_disability_no">خیر</label>
                            </div>
                            @error('has_disability') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">نوع معلولیت</label>
                            {{-- اگر معلولیت ندارد، فیلد غیرفعال شود --}}
                            <select class="form-select" wire:model.blur="disability_type_id"
                                    @if($has_disability != '1') disabled @endif>
                                <option value="">انتخاب کنید...</option>
                                @foreach($disabilityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('disability_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">توضیحات معلولیت</label>
                            <textarea class="form-control h-1" wire:model.blur="disability_description"
                                      @if($has_disability != '1') disabled @endif></textarea>
                            @error('disability_description') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">مددکار مسئول <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="social_worker_id">
                                <option value="">انتخاب کنید...</option>
                                @foreach($socialWorkers as $worker)
                                    <option value="{{ $worker->id }}">{{ $worker->fullName }} ({{ $worker->role }})
                                    </option>
                                @endforeach
                            </select>
                            @error('social_worker_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تصویر کارت ملی</label>
                            <input class="form-control" type="file" wire:model="photo_id_card">
                            @error('photo_id_card') <span class="text-danger small">{{ $message }}</span> @enderror
                            @if ($photo_id_card)
                                <img src="{{ $photo_id_card->temporaryUrl() }}" class="img-thumbnail mt-2"
                                     style="max-height: 100px;">
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تصویر شناسنامه</label>
                            <input class="form-control" type="file" wire:model="photo_birth_certificate">
                            @error('photo_birth_certificate') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                            @if ($photo_birth_certificate)
                                <img src="{{ $photo_birth_certificate->temporaryUrl() }}" class="img-thumbnail mt-2"
                                     style="max-height: 100px;">
                            @endif
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
                            <select class="form-select" wire:model.live="guardian_relation_type_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($guardianRelationTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->title }}</option>
                                @endforeach
                            </select>
                            @error('guardian_relation_type_id') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">دهک اقتصادی</label>
                            <select class="form-select" wire:model.blur="economic_decile">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($deciles as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('economic_decile') <span class="text-danger small">{{ $message }}</span> @enderror
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
                                        <input type="number" class="form-control" wire:model.blur="death_year"
                                               placeholder="مثال: 1399">
                                        @error('death_year') <span
                                            class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">علت فوت</label>
                                        <input type="text" class="form-control" wire:model.blur="death_reason">
                                        @error('death_reason') <span
                                            class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-4 mb-3">
                            <label for="divorced_parent" class="form-label">وضعیت طلاق والدین</label>
                            <select wire:model.blur="divorced_parent" id="divorced_parent" class="form-select">
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
                            <select wire:model.blur="remarried_parent" id="remarried_parent" class="form-select">
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
                            <input type="number" class="form-control" wire:model.blur="children_from_previous_marriage"
                                   placeholder="تعداد">
                            @error('children_from_previous_marriage') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- معلولیت والدین --}}
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="has_parent_disability"
                                       wire:model.live="has_parent_disability">
                                <label class="form-check-label" for="has_parent_disability">
                                    والدین دارای معلولیت هستند؟
                                </label>
                            </div>
                            @error('has_parent_disability') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($has_parent_disability)
                            <div class="col-md-12">
                                <label class="form-label">توضیحات معلولیت والدین</label>
                                <textarea class="form-control" wire:model.blur="parent_disability_description"
                                          rows="2"></textarea>
                                @error('parent_disability_description') <span
                                    class="text-danger small">{{ $message }}</span> @enderror
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
                            $relName = $guardianRelationTypes->find($guardian_relation_type_id)?->title; // Changed from ->name to ->title based on seeders.
                        @endphp
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="bi bi-info-circle-fill fs-4 me-2"></i>
                            <div>
                                شما در حال تکمیل اطلاعات برای <strong>{{ $relName }}</strong> هستید.
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">کد ملی سرپرست <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" maxlength="10"
                                   wire:model.live.debounce.500ms="guardian_national_code">
                            @error('guardian_national_code') <span
                                class="text-danger small">{{ $message }}</span> @enderror

                            @if($guardian_national_code && strlen($guardian_national_code) === 10)
                                @if($guardian_exists_in_db)
                                    <div class="alert alert-success mt-2 p-2">
                                        <i class="bi bi-check-circle me-1"></i> سرپرست یافت شد:
                                        <strong>{{ $guardian_first_name }} {{ $guardian_last_name }}</strong>
                                    </div>
                                @else
                                    <div class="alert alert-warning mt-2 p-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i> سرپرست با این کد ملی یافت نشد.
                                        لطفاً اطلاعات زیر را تکمیل کنید تا سرپرست جدید ثبت شود.
                                    </div>
                                @endif
                            @elseif(strlen($guardian_national_code) > 0 && strlen($guardian_national_code) < 10)
                                <div class="alert alert-secondary mt-2 p-2">
                                    کد ملی سرپرست باید ۱۰ رقم باشد.
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام سرپرست <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.blur="guardian_first_name"
                                   @if($guardian_exists_in_db) disabled @endif>
                            @error('guardian_first_name') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام خانوادگی سرپرست <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.blur="guardian_last_name"
                                   @if($guardian_exists_in_db) disabled @endif>
                            @error('guardian_last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- تاریخ تولد سرپرست --}}
                        <div class="col-md-5">
                            <label class="form-label">تاریخ تولد سرپرست <span class="text-danger">*</span></label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model.blur="guardian_birth_day" class="form-select"
                                            @if($guardian_exists_in_db) disabled @endif>
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="guardian_birth_month" class="form-select"
                                            @if($guardian_exists_in_db) disabled @endif>
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month)
                                            <option value="{{ $key }}">{{ $month }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="guardian_birth_year" class="form-select"
                                            @if($guardian_exists_in_db) disabled @endif>
                                        <option value="">سال</option>
                                        @foreach(range(1300, 1420) as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @error('guardian_birth_year') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                            @error('guardian_birth_month') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                            @error('guardian_birth_day') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">شماره تماس سرپرست</label>
                            <input type="text" class="form-control" wire:model.blur="guardian_phone_number"
                                   maxlength="11">
                            @error('guardian_phone_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شغل سرپرست <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="occupation_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($occupations as $job)
                                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                                @endforeach
                            </select>
                            @error('occupation_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نوع شغل سرپرست</label>
                            <select class="form-select" wire:model.blur="job_type_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($jobTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('job_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تعداد فرزندان سرپرست</label>
                            <input type="number" class="form-control" wire:model.blur="children_count" min="0">
                            @error('children_count') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تعداد فرزندان ساکن در منزل</label>
                            <input type="number" class="form-control" wire:model.blur="children_in_house" min="0">
                            @error('children_in_house') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">وضعیت بیمه سرپرست <span class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="insurance_status" id="insurance_yes">
                                <label for="insurance_yes">دارد</label>
                                <input type="radio" value="0" wire:model.live="insurance_status" id="insurance_no">
                                <label for="insurance_no">ندارد</label>
                            </div>
                            @error('insurance_status') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">نوع بیمه</label>
                            <select class="form-select" wire:model.blur="insurance_type_id"
                                    @if($insurance_status != '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($insuranceTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('insurance_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- فرزندان طلاق در منزل (جدید) --}}
                        <div class="col-md-4">
                            <label class="form-label">فرزندان طلاق در منزل</label>
                            <select class="form-select" wire:model="divorced_child_at_home">
                                <option value="">انتخاب کنید...</option>
                                <option value="none">ندارد</option>
                                <option value="boy">فقط پسر</option>
                                <option value="girl">فقط دختر</option>
                                <option value="both">پسر و دختر</option>
                            </select>
                            @error('divorced_child_at_home') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">متوسط درآمد ماهیانه (ریال)</label>
                            <input type="number" class="form-control" wire:model="average_income" min="0"
                                   placeholder="مثال: 50000000">
                            @error('average_income') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">آیا از اعضای خانواده شاغل هستند؟ <span
                                    class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="any_family_employed" id="employed_yes">
                                <label for="employed_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="any_family_employed" id="employed_no">
                                <label for="employed_no">خیر</label>
                            </div>
                            @error('any_family_employed') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">آیا وسیله نقلیه دارند؟ <span class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="has_vehicle" id="vehicle_yes"> <label
                                    for="vehicle_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="has_vehicle" id="vehicle_no"> <label
                                    for="vehicle_no">خیر</label>
                            </div>
                            @error('has_vehicle') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">نوع وسیله نقلیه</label>
                            <select class="form-select" wire:model.blur="vehicle_type_id"
                                    @if($has_vehicle != '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($vehicleTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 4: وضعیت سکونت و تماس --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">وضعیت سکونت و اطلاعات تماس</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">وضعیت سکونت <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="residence_status_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($residenceStatusTypes as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            @error('residence_status_id') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">منطقه <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="district_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                            @error('district_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">بومی شهر هستید؟ <span class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="is_local_to_city" id="local_yes"> <label
                                    for="local_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="is_local_to_city" id="local_no"> <label
                                    for="local_no">خیر</label>
                            </div>
                            @error('is_local_to_city') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($residence_status_id == 2)
                            {{-- 2 برای "اجاره‌ای" --}}
                            <div class="col-md-4">
                                <label class="form-label">مبلغ ودیعه</label>
                                <input type="number" class="form-control" wire:model.blur="deposit_amount" min="0"
                                       placeholder="به تومان">
                                @error('deposit_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">اجاره ماهیانه</label>
                                <input type="number" class="form-control" wire:model.blur="monthly_rent" min="0"
                                       placeholder="به تومان">
                                @error('monthly_rent') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">مدت زمان سکونت (سال)</label>
                            <input type="number" class="form-control" wire:model.blur="residence_duration_years"
                                   min="0">
                            @error('residence_duration_years') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">آدرس کامل <span class="text-danger">*</span></label>
                            <textarea class="form-control" wire:model.blur="address" rows="3"></textarea>
                            @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره تلفن شخصی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.blur="personal_phone" maxlength="20">
                            @error('personal_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره تلفن ثابت</label>
                            <input type="text" class="form-control" wire:model.blur="landline_phone" maxlength="20">
                            @error('landline_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره تلفن فرد معتمد</label>
                            <input type="text" class="form-control" wire:model.blur="trusted_person_phone"
                                   maxlength="20">
                            @error('trusted_person_phone') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نوع پیام‌رسان</label>
                            <input type="text" class="form-control" wire:model.blur="messenger_type"
                                   placeholder="مثال: تلگرام، ایتا، واتساپ">
                            @error('messenger_type') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره یا آیدی پیام‌رسان</label>
                            <input type="text" class="form-control" wire:model.blur="messenger_number">
                            @error('messenger_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 5: اطلاعات بانکی و تحصیلی --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات بانکی و تحصیلی</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">آیا مددجو حساب بانکی شخصی دارد؟ <span class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="has_own_account" id="own_account_yes">
                                <label for="own_account_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="has_own_account" id="own_account_no">
                                <label for="own_account_no">خیر</label>
                            </div>
                            @error('has_own_account') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نسبت صاحب حساب با مددجو <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="account_owner_relation_id"
                                    @if($has_own_account == '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($accountRelations as $relation)
                                    <option value="{{ $relation->id }}" @if($loop->first) disabled @endif>{{ $relation->name }}</option>
                                @endforeach
                            </select>
                            @error('account_owner_relation_id') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام بانک <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="bank_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره کارت</label>
                            <input type="text" class="form-control" wire:model.live.debounce="card_number"
                                   maxlength="16">
                            @error('card_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره شبا</label>
                            <input type="text" class="form-control ltr-input" wire:model.live.debounce="sheba_number"
                                   placeholder="IRXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="24">
                            @error('sheba_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره کارت یارانه</label>
                            <input type="text" class="form-control" wire:model="subsidy_card_number"
                                   maxlength="16">
                            @error('subsidy_card_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره شبا یارانه</label>
                            <input type="text" class="form-control ltr-input" wire:model.blur="subsidy_sheba_number"
                                   placeholder="IRXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="24">
                            @error('subsidy_sheba_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <h5 class="mt-5 border-bottom pb-2 mb-3 font-bold">وضعیت تحصیلی</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">آیا مددجو در حال تحصیل است؟ <span
                                    class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="is_studying" id="studying_yes"> <label
                                    for="studying_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="is_studying" id="studying_no"> <label
                                    for="studying_no">خیر</label>
                            </div>
                            @error('is_studying') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام مدرسه/دانشگاه</label>
                            <input type="text" class="form-control" wire:model.blur="school_name"
                                   @if($is_studying != '1') disabled @endif>
                            @error('school_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">رشته تحصیلی</label>
                            <input type="text" class="form-control" wire:model.blur="major"
                                   @if($is_studying != '1') disabled @endif>
                            @error('major') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">مقطع تحصیلی</label>
                            <select class="form-select" wire:model.blur="education_level_id"
                                    @if($is_studying != '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($educationLevels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                            @error('education_level_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($is_studying == '0')
                            <div class="col-md-8">
                                <label class="form-label">علت ترک تحصیل</label>
                                <textarea class="form-control" wire:model.blur="drop_reason" rows="1"></textarea>
                                @error('drop_reason') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">آیا همزمان با تحصیل کار می‌کند؟ <span class="text-danger">*</span></label>
                            <div>
                                <input type="radio" value="1" wire:model.live="works_alongside_study" id="works_yes">
                                <label for="works_yes">بله</label>
                                <input type="radio" value="0" wire:model.live="works_alongside_study" id="works_no">
                                <label for="works_no">خیر</label>
                            </div>
                            @error('works_alongside_study') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">درآمد ماهیانه از کار</label>
                            <input type="number" class="form-control" wire:model.blur="monthly_income" min="0"
                                   @if($works_alongside_study != '1') disabled @endif placeholder="به تومان">
                            @error('monthly_income') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- جدا کننده --}}
                <hr class="my-5 border-2">

                {{-- بخش 6: سطح نیاز و پوشش حمایتی --}}
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">سطح نیاز و پوشش حمایتی</h4>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">نوع نهاد حمایتی</label>
                            <input type="text" class="form-control" wire:model.blur="organization_type"
                                   placeholder="مثال: کمیته امداد، بهزیستی">
                            @error('organization_type') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاریخ شروع پوشش</label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model.blur="coverage_start_day" class="form-select">
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="coverage_start_month" class="form-select">
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month)
                                            <option value="{{ $key }}">{{ $month }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="coverage_start_year" class="form-select">
                                        <option value="">سال</option>
                                        @foreach(range(1300, 1420) as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @error('coverage_start_year') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                            @error('coverage_start_month') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                            @error('coverage_start_day') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تصویر کارت حمایت</label>
                            <input class="form-control" type="file" wire:model="support_card_image">
                            @error('support_card_image') <span class="text-danger small">{{ $message }}</span> @enderror
                            @if ($support_card_image)
                                <img src="{{ $support_card_image->temporaryUrl() }}" class="img-thumbnail mt-2"
                                     style="max-height: 100px;">
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">سطح نیاز (بر اساس ارزیابی اولیه مددکار) <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.blur="need_level_id">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($needLevelTypes as $level)
                                    <option value="{{ $level->id }}">{{ $level->title }}</option>
                                @endforeach
                            </select>
                            @error('need_level_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- دکمه ذخیره نهایی --}}
                <div class="d-grid gap-2 mt-5 mb-3">
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-lg py-3 shadow">
                        {{-- حالت لودینگ --}}
                        <span wire:loading wire:target="save"> <!-- wire:target به حالت اولیه بازگردانده شد -->
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        در حال پردازش و ذخیره اطلاعات...
                        </span>
                        {{-- حالت عادی --}}
                        <span wire:loading.remove wire:target="save"> <!-- wire:target به حالت اولیه بازگردانده شد -->
                            <i class="bi bi-check-circle-fill me-2"></i>
                                        ثبت نهایی اطلاعات مددجو
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
