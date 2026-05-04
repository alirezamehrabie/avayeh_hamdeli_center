<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mb-0">فرم ثبت‌نام مددجوی جدید</h3>
        </div>
        <div class="card-body">
            {{-- شروع فرم --}}
            <form wire:submit.prevent="save">

                {{-- Wizard Progress Bar --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">مرحله {{ $current_step }} از {{ $total_steps }}: {{ $wizard_steps[$current_step] }}</h5>
                        <span class="badge bg-primary">{{ number_format($this->wizardProgress, 0) }}% تکمیل شده</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             role="progressbar" 
                             style="width: {{ $this->wizardProgress }}%;" 
                             aria-valuenow="{{ $this->wizardProgress }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            {{ number_format($this->wizardProgress, 0) }}%
                        </div>
                    </div>
                    
                    {{-- Step Indicators --}}
                    <div class="d-flex justify-content-between mt-3 flex-wrap">
                        @foreach($wizard_steps as $stepNum => $stepName)
                            <div class="text-center" style="flex: 1; min-width: 80px;">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                                    @if($stepNum < $current_step) bg-success text-white
                                    @elseif($stepNum == $current_step) bg-primary text-white
                                    @else bg-secondary text-white @endif"
                                    style="width: 35px; height: 35px; font-weight: bold;">
                                    @if($stepNum < $current_step)
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        {{ $stepNum }}
                                    @endif
                                </div>
                                <div class="small" style="font-size: 0.75rem;">{{ $stepName }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

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


                {{-- Step 1: Personal Information --}}
                @if($current_step === 1)
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


                        <div class="col-md-4">
                            <label class="form-label">شماره موبایل مددجو</label>
                            <input type="text" class="form-control" wire:model="phone_number" maxlength="11" placeholder="مثلاً 09121234567">
                            @error('phone_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- جنسیت --}}
                        <div class="col-md-2">
                            <label class="form-label">جنسیت <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="male" wire:model.live="gender"
                                           id="gender_male">
                                    <label class="form-check-label" for="gender_male">مرد</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="female" wire:model.live="gender"
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
                                <input type="radio" class="form-check-input" value="general" wire:model.live="sadaat_status" id="sadaat_status_general"> عام
                            </label>
                            <label class="radio-inline">
                                <input type="radio" class="form-check-input" value="sadaat" wire:model.live="sadaat_status" id="sadaat_status_sadaat"> سادات
                            </label>
                            @error('sadaat_status') <span
                                class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- نسب سادات: نمایش شرطی با Blade --}}
                        @if($sadaat_status === 'سادات')
                            <div class="form-group col-md-4">
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
                                <option value="child">فرزند</option>
                                {{-- <option value="سرپرست">سرپرست</option> --}}
                            </select>
                            @error('role') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 2: Skills and Talents --}}
                @if($current_step === 2)
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">مهارت‌ها و استعدادها</h4>
                    <div class="row g-3">
                        {{-- بخش انتخاب مهارت‌ها --}}
                        <div class="col-12 mb-3">
                            <label class="form-label font-bold">مهارت‌ها</label>
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
                    </div>
                </div>
                @endif

                {{-- Step 3: Disability Information --}}
                @if($current_step === 3)
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات معلولیت و آسیب</h4>
                    <div class="row g-3">

                        {{-- نوع آسیب (چند انتخابی) --}}
                        <div class="col-12 mb-3 mt-3">
                            <label class="form-label font-bold">نوع آسیب (قابل انتخاب چندتایی)</label>

                            <div class="card p-3 bg-light">
                                <div class="row">

                                    @forelse($allHarmTypes as $harm)
                                        <div class="col-md-3 col-sm-6 mb-2">
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value="{{ $harm->id }}"
                                                    wire:model="harm_types"
                                                    id="harm_{{ $harm->id }}"
                                                >
                                                <label class="form-check-label" for="harm_{{ $harm->id }}">
                                                    {{ $harm->title }}
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted">
                                            هیچ نوع آسیبی در سیستم ثبت نشده است.
                                        </div>
                                    @endforelse

                                </div>
                            </div>

                            @error('harm_types')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                            @enderror
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

                        <div class="col-md-3 mb-3">
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

                        <div class="col-md-3 mb-3">
                            <label class="form-label">توضیحات معلولیت</label>
                            <textarea class="form-control h-1" wire:model.blur="disability_description"
                                      @if($has_disability != '1') disabled @endif></textarea>
                            @error('disability_description') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 4: Identity Documents --}}
                @if($current_step === 4)
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">مدارک شناسایی</h4>
                    <div class="row g-3">

                        <!-- بخش تصویر کارت ملی -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">تصویر کارت ملی (ثبت آنلاین یا آپلود)</label>
                            <div class="card p-3" style="background: #f8f9fa;">
                                <div class="row">
                                    <div class="col-md-7 border-start">
                                        <div id="camera-container-id-card" wire:ignore>
                                            <video id="video-id-card" width="100%" height="200" autoplay class="rounded border d-none"></video>
                                            <canvas id="canvas-id-card" width="640" height="480" class="d-none"></canvas>
                                            <div id="photo-preview-id-card" class="text-center">
                                                @if($photo_id_card)
                                                    <img src="{{ $photo_id_card->temporaryUrl() }}" class="img-thumbnail" style="max-height: 150px;">
                                                @elseif($captured_id_card_base64)
                                                    <img src="{{ $captured_id_card_base64 }}" id="captured-img-id-card" class="img-thumbnail" style="max-height: 150px;">
                                                @elseif($mode == 'edit' && $person && $person->photo_id_card)
                                                    <img src="{{ asset('storage/' . $person->photo_id_card) }}" id="captured-img-id-card" class="img-thumbnail" style="max-height: 150px;">
                                                @else
                                                    <img src="{{ asset('assets/images/no-image.png') }}" id="captured-img-id-card" class="img-thumbnail" style="max-height: 150px;">
                                                @endif
                                            </div>
                                            <div class="mt-2 text-center">
                                                @if($mode == 'edit')
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning"
                                                            onclick="confirmRetakePhoto('id-card', 'captured_id_card_base64', 'کارت ملی')">
                                                        <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            onclick="setupCamera('id-card', 'captured_id_card_base64')">
                                                        <i class="bi bi-camera"></i> فعالسازی دوربین
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        id="capture-btn-id-card"
                                                        class="btn btn-sm btn-success d-none"
                                                        onclick="takePhoto('id-card', 'captured_id_card_base64')">
                                                    <i class="bi bi-camera-fill"></i> گرفتن عکس
                                                </button>
                                                {{-- دکمه "گرفتن مجدد" بعد از عکس گرفتن توسط JS ایجاد می‌شود --}}
                                            </div>
                                        </div>
                                        <input type="hidden" wire:model="captured_id_card_base64">
                                    </div>
                                    <div class="col-md-5">
                                        <p class="small text-muted">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="photo_id_card" class="form-control" accept="image/*">
                                        @error('photo_id_card') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- بخش تصویر شناسنامه -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">تصویر شناسنامه (ثبت آنلاین یا آپلود)</label>
                            <div class="card p-3" style="background: #f8f9fa;">
                                <div class="row">
                                    <div class="col-md-7 border-start">
                                        <div id="camera-container-birth-cert" wire:ignore>
                                            <video id="video-birth-cert" width="100%" height="200" autoplay class="rounded border d-none"></video>
                                            <canvas id="canvas-birth-cert" width="640" height="480" class="d-none"></canvas>
                                            <div id="photo-preview-birth-cert" class="text-center">
                                                @if($photo_birth_certificate)
                                                    <img src="{{ $photo_birth_certificate->temporaryUrl() }}" class="img-thumbnail" style="max-height: 150px;">
                                                @elseif($captured_birth_certificate_base64)
                                                    <img src="{{ $captured_birth_certificate_base64 }}" id="captured-img-birth-cert" class="img-thumbnail" style="max-height: 150px;">
                                                @elseif($mode == 'edit' && $person && $person->photo_birth_certificate)
                                                    <img src="{{ asset('storage/' . $person->photo_birth_certificate) }}" id="captured-img-birth-cert" class="img-thumbnail" style="max-height: 150px;">
                                                @else
                                                    <img src="{{ asset('assets/images/no-image.png') }}" id="captured-img-birth-cert" class="img-thumbnail" style="max-height: 150px;">
                                                @endif
                                            </div>
                                            <div class="mt-2 text-center">
                                                @if($mode == 'edit')
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning"
                                                            onclick="confirmRetakePhoto('birth-cert', 'captured_birth_certificate_base64', 'شناسنامه')">
                                                        <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            onclick="setupCamera('birth-cert', 'captured_birth_certificate_base64')">
                                                        <i class="bi bi-camera"></i> فعالسازی دوربین
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        id="capture-btn-birth-cert"
                                                        class="btn btn-sm btn-success d-none"
                                                        onclick="takePhoto('birth-cert', 'captured_birth_certificate_base64')">
                                                    <i class="bi bi-camera-fill"></i> گرفتن عکس
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" wire:model="captured_birth_certificate_base64">
                                    </div>
                                    <div class="col-md-5">
                                        <p class="small text-muted">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="photo_birth_certificate" class="form-control" accept="image/*">
                                        @error('photo_birth_certificate') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>




                        <!-- بخش تصویر مددجو -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">تصویر مددجو (ثبت آنلاین یا آپلود)</label>
                            <div class="card p-3" style="background: #f8f9fa;">
                                <div class="row">
                                    <div class="col-md-6 border-start">
                                        <div id="camera-container-profile" wire:ignore>
                                            <video id="video-profile" width="100%" height="240" autoplay class="rounded border d-none"></video>
                                            <canvas id="canvas-profile" width="640" height="480" class="d-none"></canvas>
                                            <div id="photo-preview-profile" class="text-center">
                                                @if($profile_photo)
                                                    <img src="{{ $profile_photo->temporaryUrl() }}" class="img-thumbnail" style="max-height: 200px;">
                                                @elseif($captured_photo_base64)
                                                    <img src="{{ $captured_photo_base64 }}" id="captured-img-profile" class="img-thumbnail" style="max-height: 200px;">
                                                @elseif($mode == 'edit' && $person && $person->profile_photo)
                                                    <img src="{{ asset('storage/' . $person->profile_photo) }}" id="captured-img-profile" class="img-thumbnail" style="max-height: 200px;">
                                                @else
                                                    <img src="{{ asset('assets/images/no-image.png') }}" id="captured-img-profile" class="img-thumbnail" style="max-height: 200px;">
                                                @endif
                                            </div>
                                            <div class="mt-2 text-center">
                                                @if($mode == 'edit')
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning"
                                                            onclick="confirmRetakePhoto('profile', 'captured_photo_base64', 'مددجو')">
                                                        <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            onclick="setupCamera('profile', 'captured_photo_base64')">
                                                        <i class="bi bi-camera"></i> فعالسازی دوربین
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        id="capture-btn-profile"
                                                        class="btn btn-sm btn-success d-none"
                                                        onclick="takePhoto('profile', 'captured_photo_base64')">
                                                    <i class="bi bi-camera-fill"></i> گرفتن عکس
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" wire:model="captured_photo_base64">
                                    </div>
                                    <div class="col-md-6">
                                        <p class="small text-muted">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="profile_photo" class="form-control" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>






                    </div>
                </div>
                @endif

                {{-- Step 5: Education Status --}}
                @if($current_step === 5)
                <div class="mb-5">
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
                            <label class="form-label">درآمد ماهیانه از کار (ریال)</label>
                            <input type="number" class="form-control" wire:model.blur="monthly_income" min="0"
                                   @if($works_alongside_study != '1') disabled @endif placeholder="به ریال">
                            @error('monthly_income') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 6: Family Status --}}
                @if($current_step === 6)
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


                        {{-- remarried_parent --}}
                        <div class="col-md-4 mb-3">
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
                            <label class="form-label">فرزندان از ازدواج قبلی</label>
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
                @endif

                {{-- Step 7: Guardian and Livelihood Information --}}
                @if($current_step === 7)
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

                            @if($guardian_national_code && strlen((string) $guardian_national_code) === 10)
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
                            @elseif(strlen((string) $guardian_national_code) > 0 && strlen((string) $guardian_national_code) < 10)
                                <div class="alert alert-secondary mt-2 p-2">
                                    کد ملی سرپرست باید ۱۰ رقم باشد.
                                </div>
                            @endif
                        </div>


                        <div class="col-md-4">
                            <label class="form-label d-flex justify-content-between">
                                <span>مددکار مسئول <span class="text-danger">*</span></span>
                                @if($guardian_exists_in_db)
                                    <small class="text-muted">
                                        {{ $allow_social_worker_edit ? '🔓 در حال ویرایش' : '🔒 قفل شده (از تغییر مددکار اطمینان کامل پیدا کنید)' }}
                                    </small>
                                @endif
                            </label>

                            <div class="input-group">
                                <select class="form-select @error('social_worker_id') is-invalid @enderror"
                                        wire:model.blur="social_worker_id"
                                    {{ ($guardian_exists_in_db && !$allow_social_worker_edit) ? 'disabled' : '' }}>
                                    <option value="">انتخاب کنید...</option>
                                    @foreach($socialWorkers as $worker)
                                        <option value="{{ $worker->id }}">{{ $worker->first_name . ' ' . $worker->last_name }}</option>
                                    @endforeach
                                </select>

                                @if($guardian_exists_in_db)
                                    <button class="btn {{ $allow_social_worker_edit ? 'btn-warning' : 'btn-outline-secondary' }}"
                                            type="button"
                                            wire:click="$toggle('allow_social_worker_edit')"
                                            title="تغییر مددکار خانواده">
                                        <i class="bi {{ $allow_social_worker_edit ? 'bi-unlock-fill' : 'bi-lock-fill' }}"></i>
                                        {{ $allow_social_worker_edit ? 'لغو' : 'تغییر' }}
                                    </button>
                                @endif
                            </div>

                            @error('social_worker_id') <span class="text-danger small">{{ $message }}</span> @enderror
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
                            <label class="form-label">دهک اقتصادی</label>
                            <select wire:model="economic_decile" class="form-control">
                                <option value="">انتخاب کنید...</option>
                                @foreach($deciles as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('guardian.economic_decile') <span class="text-danger">{{ $message }}</span> @enderror
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
                            <label class="form-label">تعداد فرزندان تحت پوشش مرکز</label>
                            <span class="badge bg-secondary">
    {{ $children_count ?? 0 }} نفر تحت پوشش
</span>
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
                            <label class="form-label">فرزندان مطلقه در منزل</label>
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
                            <label class="form-label">آیا اعضای خانواده شاغل هستند؟</label>
                            <div>
                                <input type="radio" wire:model.live="any_family_employed" value="1"> بله
                                <input type="radio" wire:model.live="any_family_employed" value="0" class="ms-3"> خیر
                            </div>
                        </div>

                        @if($any_family_employed == '1')
                            <div class="col-md-4">
                                <label class="form-label">توضیحات اعضای شاغل</label>
                                <textarea class="form-control" wire:model="any_family_employed_description" rows="2"></textarea>
                                @error('any_family_employed_description')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

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

                        <div class="col-md-4">
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

                        @if($has_vehicle == '1')
                            <div class="col-md-4">
                                <label class="form-label">مالکیت وسیله نقلیه</label>
                                <div class="d-flex align-items-center mt-1">
                                    <label class="me-3">
                                        <input type="radio" wire:model="vehicle_ownership_type" value="personal">
                                        <span class="ms-1">شخصی</span>
                                    </label>

                                    <label class="me-3">
                                        <input type="radio" wire:model="vehicle_ownership_type" value="company">
                                        <span class="ms-1">شراکتی</span>
                                    </label>

                                    <label>
                                        <input type="radio" wire:model="vehicle_ownership_type" value="rented">
                                        <span class="ms-1">استیجاری</span>
                                    </label>
                                </div>
                                @error('vehicle_ownership_type')
                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Step 8: Banking Information --}}
                @if($current_step === 8)
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات بانکی</h4>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">حساب شخصی دارد؟ <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="has_own_account" id="has_own_account_yes">
                                    <label class="form-check-label" for="has_own_account_yes">بله</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="has_own_account" id="has_own_account_no">
                                    <label class="form-check-label" for="has_own_account_no">خیر</label>
                                </div>
                            </div>
                            @error('has_own_account') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">نسبت دارنده حساب</label>
                            {{-- این فیلد اکنون فقط برای نمایش است و مقدار آن توسط منطق Livewire تعیین می‌شود --}}
                            <input type="text"
                                   class="form-control"
                                   value="{{ $accountRelations->find($account_owner_relation_id)?->name ?? 'انتخاب نشده' }}"
                                   readonly
                                   disabled
                                   placeholder="به طور خودکار تعیین می‌شود"
                            >
                            {{-- wire:model از روی این input حذف شده است، زیرا مقدار آن توسط کاربر تغییر نمی‌کند.
                                 Livewire مقدار account_owner_relation_id را از طریق پراپرتی خودش مدیریت می‌کند. --}}
                            @error('account_owner_relation_id') <span class="text-danger small">{{ $message }}</span> @enderror
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
                                   placeholder="IRXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="26">
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
                                   placeholder="IRXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="26">
                            @error('subsidy_sheba_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 9: Housing Status and Contact Information --}}
                @if($current_step === 9)
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
                                <label class="form-label">مبلغ ودیعه (ریال)</label>
                                <input type="number" class="form-control" wire:model.blur="deposit_amount" min="0"
                                       placeholder="به ریال">
                                @error('deposit_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">اجاره ماهیانه (ریال)</label>
                                <input type="number" class="form-control" wire:model.blur="monthly_rent" min="0"
                                       placeholder="به ریال">
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
                            <label class="form-label">شماره تلفن ثابت</label>
                            <input type="text" class="form-control" wire:model.blur="landline_phone" maxlength="11">
                            @error('landline_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره تلفن فرد معتمد</label>
                            <input type="text" class="form-control" wire:model.blur="trusted_person_phone" maxlength="11">
                            @error('trusted_person_phone') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <!-- بخش نوع پیام‌رسان -->
                        <div class="col-md-4 mb-3">
                            <label for="messenger_type" class="form-label">نوع پیام‌رسان (انتخابی یا تایپی)</label>

                            <input wire:model="messenger_type"
                                   type="text"
                                   class="form-control @error('messenger_type') is-invalid @enderror"
                                   id="messenger_type"
                                   list="messenger_list"
                                   placeholder="انتخاب کنید یا بنویسید...">

                            <!-- لیست پیشنهادی که در زمان کلیک یا تایپ ظاهر می‌شود -->
                            <datalist id="messenger_list">
                                <option value="ایتا">
                                <option value="بله">
                                <option value="پیامک">
                                <option value="روبیکا">
                                <option value="سروش">
                                <option value="شاد">
                                <option value="تلگرام">
                                <option value="واتس‌اپ">
                            </datalist>

                            @error('messenger_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره یا آیدی پیام‌رسان</label>
                            <input type="text" class="form-control" wire:model.blur="messenger_number" maxlength="11">
                            @error('messenger_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 10: Support Needs Level and Assistance Coverage --}}
                @if($current_step === 10)
                <div class="mb-5">
                    <h4 class="border-bottom pb-2 mb-3 font-bold">سطح نیاز و پوشش حمایتی</h4>

                    <div class="row g-3">
                        <!-- انتخاب نهاد -->
                        <div class="col-md-4">
                            <label>نوع نهاد حمایتی</label>
                            <select wire:model.live="support_organization_id" class="form-control">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($support_organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- فیلد شرطی برای "خیریه دیگر" -->
                        @php
                            $otherId = $support_organizations->where('slug', 'other')->first()?->id;
                        @endphp

                        @if($support_organization_id == $otherId)
                            <div x-transition class="col-md-4">
                                <label>نام خیریه را وارد کنید</label>
                                <input type="text" wire:model="other_organization_name" class="form-control" placeholder="مثلاً: خیریه امام علی (ع)">
                                @error('other_organization_name') <span class="text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endif

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

                        <!-- بخش تصویر کارت حمایت -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">تصویر کارت حمایت (ثبت آنلاین یا آپلود)</label>
                            <div class="card p-3" style="background: #f8f9fa;">
                                <div class="row">
                                    <div class="col-md-7 border-start">
                                        <div id="camera-container-support-card" wire:ignore>
                                            <video id="video-support-card" width="100%" height="200" autoplay class="rounded border d-none"></video>
                                            <canvas id="canvas-support-card" width="640" height="480" class="d-none"></canvas>
                                            <div id="photo-preview-support-card" class="text-center">
                                                @if($support_card_image && !is_string($support_card_image))
                                                    {{-- فایل آپلود شده جدید (Temporary Upload) --}}
                                                    <img src="{{ $support_card_image->temporaryUrl() }}" class="img-thumbnail" style="max-height: 150px;">
                                                @elseif($captured_support_card_base64)
                                                    {{-- تصویر گرفته شده با دوربین --}}
                                                    <img src="{{ $captured_support_card_base64 }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
                                                @elseif($mode == 'edit' && $person && $person->supportCoverage && $person->supportCoverage->support_card_image)
                                                    {{-- تصویر ذخیره شده قبلی در حالت ویرایش --}}
                                                    <img src="{{ asset('storage/' . $person->supportCoverage->support_card_image) }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
                                                @else
                                                    {{-- تصویر پیش‌فرض --}}
                                                    <img src="{{ asset('assets/images/no-image.png') }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
                                                @endif
                                            </div>
                                            <div class="mt-2 text-center">
                                                @if($mode == 'edit')
                                                    {{-- حالت ویرایش: دکمه "تصویر مجدد" با تأییدیه --}}
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning"
                                                            onclick="confirmRetakePhoto('support-card', 'captured_support_card_base64', 'کارت حمایت')">
                                                        <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                    </button>
                                                @else
                                                    {{-- حالت ایجاد: دکمه معمولی "فعالسازی دوربین" --}}
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            onclick="setupCamera('support-card', 'captured_support_card_base64')">
                                                        <i class="bi bi-camera"></i> فعالسازی دوربین
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        id="capture-btn-support-card"
                                                        class="btn btn-sm btn-success d-none"
                                                        onclick="takePhoto('support-card', 'captured_support_card_base64')">
                                                    <i class="bi bi-camera-fill"></i> گرفتن عکس
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" wire:model="captured_support_card_base64">
                                    </div>
                                    <div class="col-md-5">
                                        <p class="small text-muted">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="support_card_image" class="form-control" accept="image/*">
                                        @error('support_card_image') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
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
                @endif

                {{-- Wizard Navigation Buttons --}}
                <div class="d-flex justify-content-between mt-5 mb-3">
                    {{-- Previous Button --}}
                    @if($current_step > 1)
                        <button type="button" wire:click="previousStep" class="btn btn-secondary">
                            <i class="bi bi-arrow-right"></i> مرحله قبل
                        </button>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex gap-2">
                        {{-- Skip Button (not on last step) --}}
                        @if($current_step < $total_steps)
                            <button type="button" wire:click="skipStep" class="btn btn-outline-warning">
                                <i class="bi bi-skip-forward"></i> رد کردن
                            </button>
                        @endif

                        {{-- Next or Submit Button --}}
                        @if($current_step < $total_steps)
                            <button type="button" wire:click="nextStep" class="btn btn-primary">
                                مرحله بعد <i class="bi bi-arrow-left"></i>
                            </button>
                        @else
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-success btn-lg">
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    در حال ذخیره...
                                </span>
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    ثبت نهایی اطلاعات
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@script
<script>
    /**
     * تأییدیه قبل از فعال‌سازی دوربین (فقط در حالت Edit)
     */
    window.confirmRetakePhoto = function(cameraId, base64VarName, label) {
        if (!confirm('آیا می‌خواهید تصویر ' + label + ' را مجدداً ثبت کنید؟')) {
            return;
        }
        setupCamera(cameraId, base64VarName);
    };

    /**
     * فعال‌سازی دوربین
     */
    window.setupCamera = function(cameraId, base64VarName) {
        const video = document.getElementById('video-' + cameraId);
        const captureBtn = document.getElementById('capture-btn-' + cameraId);
        const preview = document.getElementById('photo-preview-' + cameraId);

        if (!video) return;

        // مخفی کردن دکمه "گرفتن مجدد" اگر وجود داشت
        const retakeBtn = document.getElementById('retake-btn-' + cameraId);
        if (retakeBtn) {
            retakeBtn.classList.add('d-none');
        }

        // درخواست دسترسی به دوربین
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        })
            .then(function(stream) {
                video.srcObject = stream;
                video.classList.remove('d-none');
                captureBtn.classList.remove('d-none');

                // مخفی کردن پیش‌نمایش فعلی
                if (preview) {
                    preview.style.display = 'none';
                }
            })
            .catch(function(err) {
                alert('خطا در دسترسی به دوربین: ' + err.message);
            });
    };

    /**
     * گرفتن عکس از دوربین + نمایش دکمه "گرفتن مجدد"
     */
    window.takePhoto = function(cameraId, base64VarName) {
        const video = document.getElementById('video-' + cameraId);
        const canvas = document.getElementById('canvas-' + cameraId);
        const preview = document.getElementById('photo-preview-' + cameraId);
        const captureBtn = document.getElementById('capture-btn-' + cameraId);

        if (!video || !canvas) return;

        // رسم تصویر روی canvas
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        // تبدیل به base64
        const base64 = canvas.toDataURL('image/png');

        // ارسال به Livewire
        $wire.set(base64VarName, base64);

        // متوقف کردن دوربین
        const stream = video.srcObject;
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }

        // مخفی کردن ویدیو
        video.classList.add('d-none');

        // نمایش پیش‌نمایش عکس گرفته شده
        if (preview) {
            preview.style.display = 'block';
            preview.innerHTML = `
                <img src="${base64}" class="img-thumbnail" style="max-height: 150px;">
                <p class="text-success small mt-1 mb-0">📷 عکس ثبت شد</p>
            `;
        }

        // مخفی کردن دکمه "گرفتن عکس"
        captureBtn.classList.add('d-none');

        // ✅ نمایش دکمه "گرفتن مجدد"
        let retakeBtn = document.getElementById('retake-btn-' + cameraId);
        if (!retakeBtn) {
            // ایجاد دکمه برای اولین بار
            retakeBtn = document.createElement('button');
            retakeBtn.type = 'button';
            retakeBtn.id = 'retake-btn-' + cameraId;
            retakeBtn.className = 'btn btn-sm btn-outline-warning mt-1';
            retakeBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> گرفتن مجدد';
            retakeBtn.onclick = function() {
                retakePhoto(cameraId, base64VarName);
            };
            // اضافه کردن کنار دکمه‌های موجود
            captureBtn.parentNode.appendChild(retakeBtn);
        } else {
            // نمایش دکمه‌ای که قبلاً ایجاد شده
            retakeBtn.classList.remove('d-none');
        }
    };

    /**
     * ✅ گرفتن مجدد عکس - دوبین را دوباره فعال می‌کند
     */
    window.retakePhoto = function(cameraId, base64VarName) {
        // پاک کردن عکس قبلی از Livewire
        $wire.set(base64VarName, null);

        // مخفی کردن دکمه "گرفتن مجدد"
        const retakeBtn = document.getElementById('retake-btn-' + cameraId);
        if (retakeBtn) {
            retakeBtn.classList.add('d-none');
        }

        // ریست پیش‌نمایش
        const preview = document.getElementById('photo-preview-' + cameraId);
        if (preview) {
            preview.innerHTML = `
                <img src="{{ asset('assets/images/no-image.png') }}"
                     id="captured-img-${cameraId}"
                     class="img-thumbnail"
                     style="max-height: 150px;">
            `;
        }

        // فعال‌سازی مجدد دوربین
        setupCamera(cameraId, base64VarName);
    };
</script>
@endscript
