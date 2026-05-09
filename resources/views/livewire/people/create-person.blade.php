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
                                <button type="button" wire:click="goToStep({{ $stepNum }})" class="btn p-0 border-0 bg-transparent">
                                    <div class="position-relative d-inline-flex">
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
                                        @if($show_step_error_badges && ($this->stepIncompleteCounts[$stepNum] ?? 0) > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white"
                                                  style="font-size: 0.65rem; min-width: 1.35rem;">
                                                {{ $this->stepIncompleteCounts[$stepNum] }}
                                            </span>
                                        @endif
                                    </div>
                                </button>
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

                @if($mode === 'edit' && $person)
                    <div class="mb-4 rounded-4 border bg-white p-3 p-md-4 shadow-sm" style="border-color: #dbe3ec;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h5 class="mb-0 fw-bold text-slate-800">رهگیری فعالیت کاربران</h5>
                            <span class="badge text-bg-light border">شناسه مددجو: {{ $person->person_code ?? '-' }}</span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="rounded-3 border p-3 h-100" style="border-color: #e2e8f0; background: #f8fafc;">
                                    <p class="small text-muted mb-1">ایجادکننده</p>
                                    <p class="fw-semibold mb-1">{{ $person->creator?->name ?? 'نامشخص' }}</p>
                                    <p class="small mb-0 text-slate-600">{{ optional($person->created_at)->format('Y/m/d H:i:s') ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="rounded-3 border p-3 h-100" style="border-color: #e2e8f0; background: #f8fafc;">
                                    <p class="small text-muted mb-1">آخرین ویرایش توسط</p>
                                    <p class="fw-semibold mb-1">{{ $person->updater?->name ?? $person->creator?->name ?? 'نامشخص' }}</p>
                                    <p class="small mb-0 text-slate-600">{{ optional($person->updated_at)->format('Y/m/d H:i:s') ?? '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div hidden class="rounded-3 border p-3" style="border-color: #e2e8f0;">
                            <p class="fw-semibold mb-2">تاریخچه تغییرات</p>
                            @if($person->auditLogs->isEmpty())
                                <p class="small text-muted mb-0">هنوز لاگی برای این مددجو ثبت نشده است.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>زمان</th>
                                            <th>کاربر</th>
                                            <th>نوع عملیات</th>
                                            <th>فیلدهای تغییر یافته</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($person->auditLogs as $log)
                                            <tr>
                                                <td class="small">{{ optional($log->created_at)->format('Y/m/d H:i:s') }}</td>
                                                <td class="small">{{ $log->user?->name ?? 'سیستم/نامشخص' }}</td>
                                                <td class="small fw-semibold">
                                                    @if($log->action === 'created')
                                                        ایجاد
                                                    @elseif($log->action === 'updated')
                                                        ویرایش
                                                    @elseif($log->action === 'deleted')
                                                        حذف
                                                    @else
                                                        {{ $log->action }}
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @if(!empty($log->changed_fields))
                                                        {{ implode('، ', $log->changed_fields) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif


                {{-- Step 1: Personal Information --}}
                @if($current_step === 1)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <div>
                                    <h5 class="mb-1 fw-bold">اطلاعات فردی مددجو</h5>
                                    <p class="mb-0 small text-muted">ابتدا اطلاعات هویتی را وارد کنید، سپس اطلاعات تکمیلی را ثبت نمایید.</p>
                                </div>
                                <small class="text-muted">فیلدهای <span class="text-danger">*</span> الزامی هستند.</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">نام <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('first_name') is-invalid @enderror" wire:model.blur="first_name" placeholder="مثال: امیرعلی" autocomplete="given-name" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @error('first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">نام خانوادگی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('last_name') is-invalid @enderror" wire:model.blur="last_name" placeholder="مثال: رضایی" autocomplete="family-name" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">کد ملی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('national_id') is-invalid @enderror" maxlength="10" wire:model.live="national_id" inputmode="numeric" placeholder="10 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @error('national_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">تاریخ تولد <span class="text-danger">*</span></label>
                                    <div class="row g-2 dir-ltr">
                                        <div class="col-4">
                                            <select wire:model.blur="birth_day" class="form-select form-select-sm @error('birth_day') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                                <option value="">روز</option>
                                                @foreach(range(1, 31) as $day)
                                                    <option value="{{ $day }}">{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select wire:model.blur="birth_month" class="form-select form-select-sm @error('birth_month') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                                <option value="">ماه</option>
                                                @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                                @foreach($months as $key => $month)
                                                    <option value="{{ $key }}">{{ $month }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select wire:model.blur="birth_year" class="form-select form-select-sm @error('birth_year') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                                <option value="">سال</option>
                                                @foreach(range(1300, 1420) as $year)
                                                    <option value="{{ $year }}">{{ $year }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @error('birth_day') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    @error('birth_month') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    @error('birth_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">کد ملی پدر</label>
                                    <input type="text" class="form-control form-control-sm @error('father_national_id') is-invalid @enderror" maxlength="10" wire:model.live.debounce.350ms="father_national_id" inputmode="numeric" placeholder="10 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @if($showFatherSuggestions && strlen(trim((string)$father_national_id)) >= 5 && $this->fatherSuggestions->count())
                                        <div class="mt-1 rounded-3 border bg-white shadow-sm" style="border-color: #dbe3ec; max-height: 150px; overflow-y: auto;">
                                            @foreach($this->fatherSuggestions as $fatherSuggestion)
                                                <button
                                                    type="button"
                                                    wire:click="selectFatherFromSuggestions('{{ $fatherSuggestion->father_national_id }}')"
                                                    class="w-100 border-0 bg-transparent px-2 py-1 text-start transition hover:bg-cyan-50"
                                                    style="border-bottom: 1px solid #f1f5f9;"
                                                >
                                                    <div class="small fw-semibold text-slate-800">{{ $fatherSuggestion->father_name }}</div>
                                                    <div class="small text-muted">{{ $fatherSuggestion->father_national_id }}</div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                    @error('father_national_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">کد ملی مادر</label>
                                    <input type="text" class="form-control form-control-sm @error('mother_national_id') is-invalid @enderror" maxlength="10" wire:model.blur="mother_national_id" inputmode="numeric" placeholder="10 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @error('mother_national_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">نام پدر <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('father_name') is-invalid @enderror" wire:model.blur="father_name" placeholder="نام پدر" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @error('father_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">شماره موبایل مددجو</label>
                                    <input type="text" class="form-control form-control-sm @error('phone_number') is-invalid @enderror" wire:model="phone_number" maxlength="11" inputmode="numeric" placeholder="09xxxxxxxxx" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    @error('phone_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>



                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-2">نقش در خانواده <span class="text-danger">*</span></label>
                                    <select disabled class="form-select form-select-sm @error('role') is-invalid @enderror" wire:model.blur="role" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                        <option value="child">فرزند</option>
                                    </select>
                                    @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-2">جنسیت <span class="text-danger">*</span></label>
                                    <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important;">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" value="male" wire:model.live="gender" id="gender_male">
                                            <label class="form-check-label small" for="gender_male">مرد</label>
                                        </div>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" value="female" wire:model.live="gender" id="gender_female">
                                            <label class="form-check-label small" for="gender_female">زن</label>
                                        </div>
                                    </div>
                                    @error('gender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-2">وضعیت سادات <span class="text-danger">*</span></label>
                                    <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important;">
                                        <div class="form-check mb-0">
                                            <input type="radio" class="form-check-input" value="general" wire:model.live="sadaat_status" id="sadaat_status_general">
                                            <label class="form-check-label small" for="sadaat_status_general">عام</label>
                                        </div>
                                        <div class="form-check mb-0">
                                            <input type="radio" class="form-check-input" value="sadaat" wire:model.live="sadaat_status" id="sadaat_status_sadaat">
                                            <label class="form-check-label small" for="sadaat_status_sadaat">سادات</label>
                                        </div>
                                    </div>
                                    @error('sadaat_status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                @if($sadaat_status === 'sadaat')
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">نسب سادات <span class="text-danger">*</span></label>
                                        <select wire:model.blur="sadaat_relation_id" class="form-select form-select-sm @error('sadaat_relation_id') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                            <option value="">— انتخاب کنید —</option>
                                            @foreach($sadaatRelations as $rel)
                                                <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('sadaat_relation_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                </div>
                @endif

                {{-- Step 2: Skills and Talents --}}
                @if($current_step === 2)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <div>
                                    <h5 class="mb-1 fw-bold">مهارت‌ها و استعدادها</h5>
                                    <p class="mb-0 small text-muted">مهارت های مرتبط را انتخاب کنید و در صورت نیاز توضیح کوتاه وارد نمایید.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-2">انتخاب مهارت ها</label>
                                    <div class="border p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                                        <div class="row g-2">
                                            @forelse($allSkills as $skill)
                                                <div class="col-lg-3 col-md-4 col-sm-6">
                                                    <label for="skill_{{ $skill->id }}" class="w-100 h-100 d-flex align-items-center gap-2 px-3 py-2 border bg-white" style="border-radius: 10px; border-color: #e2e8f0 !important; cursor: pointer;">
                                                        <input
                                                            class="form-check-input m-0"
                                                            type="checkbox"
                                                            value="{{ $skill->id }}"
                                                            wire:model.live="skills"
                                                            id="skill_{{ $skill->id }}"
                                                        >
                                                        <span class="small text-dark">{{ $skill->name }}</span>
                                                    </label>
                                                </div>
                                            @empty
                                                <div class="col-12 text-muted small">هیچ مهارتی در سیستم تعریف نشده است.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    @error('skills') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">توضیحات استعداد</label>
                                    <textarea wire:model.blur="skills_description"
                                              class="form-control form-control-sm @error('skills_description') is-invalid @enderror"
                                              rows="4"
                                              placeholder="در صورت نیاز، توضیح کوتاهی درباره استعدادها یا مهارت های ویژه ثبت کنید..."
                                              style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                                    @error('skills_description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 3: Disability Information --}}
                @if($current_step === 3)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <div>
                                    <h5 class="mb-1 fw-bold">اطلاعات معلولیت و آسیب</h5>
                                    <p class="mb-0 small text-muted">نوع آسیب را مشخص کنید و در صورت وجود معلولیت، جزئیات آن را تکمیل نمایید.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-2">نوع آسیب (چند انتخابی)</label>
                                    <div class="border p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                                        <div class="row g-2">
                                            @forelse($allHarmTypes as $harm)
                                                <div class="col-lg-3 col-md-4 col-sm-6">
                                                    <label for="harm_{{ $harm->id }}" class="w-100 h-100 d-flex align-items-center gap-2 px-3 py-2 border bg-white" style="border-radius: 10px; border-color: #e2e8f0 !important; cursor: pointer;">
                                                        <input
                                                            class="form-check-input m-0"
                                                            type="checkbox"
                                                            value="{{ $harm->id }}"
                                                            wire:model="harm_types"
                                                            id="harm_{{ $harm->id }}"
                                                        >
                                                        <span class="small text-dark">{{ $harm->title }}</span>
                                                    </label>
                                                </div>
                                            @empty
                                                <div class="col-12 text-muted small">هیچ نوع آسیبی در سیستم ثبت نشده است.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    @error('harm_types') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-2">آیا دارای معلولیت هست؟</label>
                                    <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                        <div class="form-check mb-0">
                                            <input type="radio" class="form-check-input" value="1" wire:model.live="has_disability" id="has_disability_yes">
                                            <label class="form-check-label small" for="has_disability_yes">بله</label>
                                        </div>
                                        <div class="form-check mb-0">
                                            <input type="radio" class="form-check-input" value="0" wire:model.live="has_disability" id="has_disability_no">
                                            <label class="form-check-label small" for="has_disability_no">خیر</label>
                                        </div>
                                    </div>
                                    @error('has_disability') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">نوع معلولیت</label>
                                    <select class="form-select form-select-sm @error('disability_type_id') is-invalid @enderror"
                                            wire:model.blur="disability_type_id"
                                            style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                            @if($has_disability != '1') disabled @endif>
                                        <option value="">انتخاب کنید...</option>
                                        @foreach($disabilityTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('disability_type_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">توضیحات معلولیت</label>
                                    <textarea class="form-control form-control-sm @error('disability_description') is-invalid @enderror"
                                              wire:model.blur="disability_description"
                                              rows="3"
                                              placeholder="در صورت نیاز توضیح کوتاه ثبت کنید..."
                                              style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"
                                              @if($has_disability != '1') disabled @endif></textarea>
                                    @error('disability_description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 4: Identity Documents --}}
                @if($current_step === 4)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">مدارک شناسایی</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 4</span>
                            </div>
                            <div class="row g-4">

                        <!-- بخش تصویر کارت ملی -->
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-2">تصویر کارت ملی</label>
                            <div class="border h-100 p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
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
                                                    <img src="{{ asset($person->photo_id_card) }}" id="captured-img-id-card" class="img-thumbnail" style="max-height: 150px;">
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
                                        <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="photo_id_card" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                        @error('photo_id_card') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- بخش تصویر شناسنامه -->
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-2">تصویر شناسنامه</label>
                            <div class="border h-100 p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
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
                                                    <img src="{{ asset($person->photo_birth_certificate) }}" id="captured-img-birth-cert" class="img-thumbnail" style="max-height: 150px;">
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
                                        <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="photo_birth_certificate" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                        @error('photo_birth_certificate') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>




                        <!-- بخش تصویر مددجو -->
                        <div class="col-lg-4 col-md-12">
                            <label class="form-label small fw-semibold mb-2">تصویر مددجو</label>
                            <div class="border h-100 p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
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
                                                    <img src="{{ asset($person->profile_photo) }}" id="captured-img-profile" class="img-thumbnail" style="max-height: 200px;">
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
                                        <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="profile_photo" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                    </div>
                                </div>
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
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">وضعیت تحصیلی</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 5</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">آیا مددجو در حال تحصیل است؟ <span class="text-danger">*</span></label>
                                    <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" value="1" wire:model.live="is_studying" id="studying_yes">
                                            <label class="form-check-label" for="studying_yes">بله</label>
                                        </div>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" value="0" wire:model.live="is_studying" id="studying_no">
                                            <label class="form-check-label" for="studying_no">خیر</label>
                                        </div>
                                    </div>
                                    @error('is_studying') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">آیا همزمان با تحصیل کار می‌کند؟ <span class="text-danger">*</span></label>
                                    <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" value="1" wire:model.live="works_alongside_study" id="works_yes">
                                            <label class="form-check-label" for="works_yes">بله</label>
                                        </div>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" value="0" wire:model.live="works_alongside_study" id="works_no">
                                            <label class="form-check-label" for="works_no">خیر</label>
                                        </div>
                                    </div>
                                    @error('works_alongside_study') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">نام مدرسه/دانشگاه</label>
                                    <input type="text" class="form-control form-control-sm @error('school_name') is-invalid @enderror" wire:model.blur="school_name" placeholder="نام مرکز آموزشی" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($is_studying != '1') disabled @endif>
                                    @error('school_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">رشته تحصیلی</label>
                                    <input type="text" class="form-control form-control-sm @error('major') is-invalid @enderror" wire:model.blur="major" placeholder="مثال: حسابداری" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($is_studying != '1') disabled @endif>
                                    @error('major') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">مقطع تحصیلی</label>
                                    <select class="form-select form-select-sm @error('education_level_id') is-invalid @enderror" wire:model.blur="education_level_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($is_studying != '1') disabled @endif>
                                        <option value="">— انتخاب کنید —</option>
                                        @foreach($educationLevels as $level)
                                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('education_level_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">درآمد ماهیانه از کار (ریال)</label>
                                    <input type="number" class="form-control form-control-sm @error('monthly_income') is-invalid @enderror" wire:model.blur="monthly_income" min="0" placeholder="به ریال" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($works_alongside_study != '1') disabled @endif>
                                    @error('monthly_income') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                @if($is_studying == '0')
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold mb-1">علت ترک تحصیل</label>
                                        <textarea class="form-control form-control-sm @error('drop_reason') is-invalid @enderror" wire:model.blur="drop_reason" rows="2" placeholder="در صورت نیاز توضیح کوتاه ثبت کنید..." style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                                        @error('drop_reason') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 6: Family Status --}}
                @if($current_step === 6)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">وضعیت خانوادگی</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 6</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">نسبت سرپرست با مددجو <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('guardian_relation_type_id') is-invalid @enderror" wire:model.live="guardian_relation_type_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                        <option value="">— انتخاب کنید —</option>
                                        @foreach($guardianRelationTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('guardian_relation_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1" for="remarried_parent">ازدواج مجدد والدین</label>
                                    <select wire:model.live="remarried_parent" id="remarried_parent" class="form-select form-select-sm @error('remarried_parent') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                        <option value="">انتخاب کنید...</option>
                                        <option value="none">خیر</option>
                                        <option value="father">فقط پدر</option>
                                        <option value="mother">فقط مادر</option>
                                        <option value="both">هر دو</option>
                                    </select>
                                    @error('remarried_parent') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">فرزندان از ازدواج قبلی والدین</label>
                                    <input type="number" class="form-control form-control-sm @error('children_from_previous_marriage') is-invalid @enderror" wire:model.live.debounce.250ms="children_from_previous_marriage" placeholder="تعداد" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @disabled($remarried_parent === 'none')>
                                    @error('children_from_previous_marriage') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">وضعیت معلولیت والدین</label>
                                    <div class="border rounded-3 p-2 d-flex align-items-center gap-2" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="has_parent_disability" wire:model.live="has_parent_disability">
                                            <label class="form-check-label" for="has_parent_disability">والدین دارای معلولیت هستند؟</label>
                                        </div>
                                    </div>
                                    @error('has_parent_disability') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                @if($has_parent_disability)
                                    <div class="col-md-12">
                                        <label class="form-label small fw-semibold mb-1">توضیحات معلولیت والدین</label>
                                        <textarea class="form-control form-control-sm @error('parent_disability_description') is-invalid @enderror" wire:model.blur="parent_disability_description" rows="2" placeholder="در صورت نیاز توضیحات بیشتری ثبت کنید..." style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                                        @error('parent_disability_description') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 7: Guardian and Livelihood Information --}}
                @if($current_step === 7)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">اطلاعات سرپرست و معیشت</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 7</span>
                            </div>

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
                        <div
                            class="col-lg-4 col-md-6"
                            x-data="{
                                openSuggestions: false,
                                activeSuggestionIndex: -1,
                                suggestionIds: @js($this->guardianSuggestions->pluck('id')->values()->all()),
                                resetActive() {
                                    this.activeSuggestionIndex = this.suggestionIds.length ? 0 : -1;
                                },
                                moveActive(direction) {
                                    if (!this.openSuggestions) {
                                        this.openSuggestions = true;
                                    }
                                    if (!this.suggestionIds.length) {
                                        this.activeSuggestionIndex = -1;
                                        return;
                                    }
                                    if (this.activeSuggestionIndex === -1) {
                                        this.activeSuggestionIndex = 0;
                                        return;
                                    }
                                    this.activeSuggestionIndex = (this.activeSuggestionIndex + direction + this.suggestionIds.length) % this.suggestionIds.length;
                                },
                                chooseActive() {
                                    if (!this.openSuggestions || this.activeSuggestionIndex < 0 || !this.suggestionIds.length) {
                                        return;
                                    }
                                    const guardianId = this.suggestionIds[this.activeSuggestionIndex];
                                    if (!guardianId) {
                                        return;
                                    }
                                    $wire.selectGuardianFromSuggestions(guardianId);
                                    this.openSuggestions = false;
                                    this.activeSuggestionIndex = -1;
                                    this.$nextTick(() => this.$refs.guardianInput?.blur());
                                }
                            }"
                            @click.outside="openSuggestions = false; activeSuggestionIndex = -1"
                            @guardian-selected.window="openSuggestions = false; activeSuggestionIndex = -1; $nextTick(() => $refs.guardianInput?.blur())"
                        >
                            <label class="form-label small fw-semibold mb-1">کد ملی سرپرست <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input
                                    x-ref="guardianInput"
                                    type="text"
                                    class="form-control form-control-sm @error('guardian_national_code') is-invalid @enderror"
                                    maxlength="10"
                                    wire:model.live.debounce.500ms="guardian_national_code"
                                    @focus="openSuggestions = true; resetActive()"
                                    @input="if (!openSuggestions) { openSuggestions = true }; resetActive()"
                                    @keydown.escape.window="openSuggestions = false"
                                    @keydown.down.prevent="moveActive(1)"
                                    @keydown.up.prevent="moveActive(-1)"
                                    @keydown.enter.prevent="chooseActive()"
                                    style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                >

                                <div
                                    x-show="openSuggestions"
                                    @mousedown.stop
                                    x-transition
                                    class="position-absolute start-0 w-100 mt-2 border bg-white shadow-sm overflow-hidden"
                                    style="z-index: 30; border-radius: 14px; border-color: #dbe3ec !important;"
                                >
                                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="background: #f8fafc; border-color: #eef2f7 !important;">
                                        <span class="small fw-semibold text-secondary">سرپرستان ثبت‌شده</span>
                                        <span class="badge rounded-pill text-bg-light border">{{ $this->guardianSuggestions->count() }}</span>
                                    </div>

                                    <div style="max-height: 260px; overflow-y: auto;">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead class="sticky-top" style="background: #f8fafc;">
                                            <tr>
                                                <th class="small text-center fw-semibold text-secondary py-2">کد ملی سرپرست</th>
                                                <th class="small text-end fw-semibold text-secondary py-2">نام سرپرست</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($this->guardianSuggestions as $suggestedGuardian)
                                                <tr
                                                    wire:key="guardian-suggestion-{{ $suggestedGuardian->id }}"
                                                    wire:click="selectGuardianFromSuggestions({{ $suggestedGuardian->id }})"
                                                    @mouseenter="activeSuggestionIndex = {{ $loop->index }}"
                                                    @click="openSuggestions = false; activeSuggestionIndex = -1; $nextTick(() => $refs.guardianInput?.blur())"
                                                    class="cursor-pointer"
                                                    :class="{ 'table-primary': activeSuggestionIndex === {{ $loop->index }} }"
                                                    style="cursor: pointer;"
                                                >
                                                    <td class="text-center small py-2 fw-semibold text-dark">{{ $suggestedGuardian->national_code }}</td>
                                                    <td class="text-end small py-2 text-dark">{{ trim(($suggestedGuardian->first_name ?? '') . ' ' . ($suggestedGuardian->last_name ?? '')) ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center py-4 text-muted small">
                                                        سرپرستی یافت نشد. می‌توانید مقدار جدید را دستی وارد کنید.
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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


                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1 d-flex justify-content-between">
                                <span>مددکار مسئول <span class="text-danger">*</span></span>
                                @if($guardian_exists_in_db)
                                    <small class="text-muted">
                                        {{ $allow_social_worker_edit ? '🔓 در حال ویرایش' : '🔒 قفل شده (از تغییر مددکار اطمینان کامل پیدا کنید)' }}
                                    </small>
                                @endif
                            </label>

                            <div class="input-group">
                                <select class="form-select form-select-sm @error('social_worker_id') is-invalid @enderror"
                                        wire:model.blur="social_worker_id"
                                    {{ ($guardian_exists_in_db && !$allow_social_worker_edit) ? 'disabled' : '' }} style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
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
                            <label class="form-label small fw-semibold mb-1">نام سرپرست <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('guardian_first_name') is-invalid @enderror" wire:model.blur="guardian_first_name" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                   @if($guardian_exists_in_db) disabled @endif>
                            @error('guardian_first_name') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">نام خانوادگی سرپرست <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('guardian_last_name') is-invalid @enderror" wire:model.blur="guardian_last_name" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                   @if($guardian_exists_in_db) disabled @endif>
                            @error('guardian_last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- تاریخ تولد سرپرست --}}
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">تاریخ تولد سرپرست <span class="text-danger">*</span></label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model.blur="guardian_birth_day" class="form-select form-select-sm @error('guardian_birth_day') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                            @if($guardian_exists_in_db) disabled @endif>
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="guardian_birth_month" class="form-select form-select-sm @error('guardian_birth_month') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                            @if($guardian_exists_in_db) disabled @endif>
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month)
                                            <option value="{{ $key }}">{{ $month }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="guardian_birth_year" class="form-select form-select-sm @error('guardian_birth_year') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
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
                            <label class="form-label small fw-semibold mb-1">شماره تماس سرپرست</label>
                            <input type="text" class="form-control form-control-sm @error('guardian_phone_number') is-invalid @enderror" wire:model.blur="guardian_phone_number" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                   maxlength="11">
                            @error('guardian_phone_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">دهک اقتصادی</label>
                            <select wire:model="economic_decile" class="form-select form-select-sm @error('guardian.economic_decile') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">انتخاب کنید...</option>
                                @foreach($deciles as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('guardian.economic_decile') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>


                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">شغل سرپرست <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('occupation_id') is-invalid @enderror" wire:model.blur="occupation_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($occupations as $job)
                                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                                @endforeach
                            </select>
                            @error('occupation_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">نوع شغل سرپرست</label>
                            <select class="form-select form-select-sm @error('job_type_id') is-invalid @enderror" wire:model.blur="job_type_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($jobTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('job_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">تعداد فرزندان تحت پوشش مرکز</label>
                            <div class="border rounded-3 px-3 py-2 d-flex align-items-center justify-content-between" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                <span class="text-muted small">تعداد ثبت‌شده</span>
                                <span class="badge text-bg-secondary">{{ $children_count ?? 0 }} نفر</span>
                            </div>
                            @error('children_count') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">تعداد فرزندان ساکن در منزل</label>
                            <input disabled type="number" class="form-control form-control-sm @error('children_in_house') is-invalid @enderror" wire:model.blur="children_in_house" min="0" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('children_in_house') <span class="text-danger small">{{ $message }}</span> @enderror
                            <div class="mt-1 rounded-2 px-2 py-1 small" style="background: #eef6fb; color: #4b5563; border: 1px solid #d9e8f3;">
                                نحوه محاسبه: ({{ $this->childrenInHouseFormula['children_count'] }}) فرزندان تحت پوشش
                                + ({{ $this->childrenInHouseFormula['children_from_previous_marriage'] }}) فرزندان ازدواج قبلیِ
                                + ({{ $this->childrenInHouseFormula['extra_household_members'] }}) افراد افزوده‌شده دستی
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold mb-1">افراد غیرمددجو ساکن در منزل</label>
                            <div class="border rounded-3 p-2" style="background: #f8fafc; border-color: #dbe3ec !important;">
                                <div class="d-flex gap-2 align-items-center">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        wire:model.defer="new_extra_household_member_description"
                                        placeholder="مثال: خاله مجرد، فرزند طلاق"
                                        style="border-radius: 10px; background: #ffffff; border-color: #dbe3ec; min-height: 38px;"
                                    >
                                    <button
                                        type="button"
                                        wire:click="addExtraHouseholdMember"
                                        class="btn btn-sm text-white px-3"
                                        style="background: #53BEEA; border-radius: 10px; min-height: 38px; white-space: nowrap;"
                                    >
                                        + افزودن نفر
                                    </button>
                                </div>

                                @if(!empty($extra_household_members))
                                    <div class="mt-2 row g-2">
                                        @foreach($extra_household_members as $index => $member)
                                            <div class="col-12 col-md-4">
                                                <div class="d-flex justify-content-between align-items-center rounded-2 px-2 py-1 h-100" style="background: #ffffff; border: 1px solid #e5edf5;">
                                                    <span class="small text-slate-700 text-truncate pe-2" title="{{ $member['description'] ?? '-' }}">{{ $member['description'] ?? '-' }}</span>
                                                    <button
                                                        type="button"
                                                        wire:click="removeExtraHouseholdMember({{ $index }})"
                                                        class="btn btn-sm p-0 text-danger"
                                                        style="font-size: 12px; white-space: nowrap;"
                                                    >
                                                        حذف
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-2 small text-muted">
                                    مجموع افراد افزوده‌شده: {{ count($extra_household_members ?? []) }} نفر
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">وضعیت بیمه سرپرست <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="insurance_status" id="insurance_yes">
                                    <label class="form-check-label" for="insurance_yes">دارد</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="insurance_status" id="insurance_no">
                                    <label class="form-check-label" for="insurance_no">ندارد</label>
                                </div>
                            </div>
                            @error('insurance_status') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">نوع بیمه</label>
                            <select class="form-select form-select-sm @error('insurance_type_id') is-invalid @enderror" wire:model.blur="insurance_type_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                    @if($insurance_status != '1') disabled @endif>
                                <option value="">— انتخاب کنید —</option>
                                @foreach($insuranceTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('insurance_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>


                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">متوسط درآمد ماهیانه (ریال)</label>
                            <input type="number" class="form-control form-control-sm @error('average_income') is-invalid @enderror" wire:model="average_income" min="0" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                   placeholder="مثال: 50000000">
                            @error('average_income') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>


                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">آیا اعضای خانواده شاغل هستند؟</label>
                            <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" wire:model.live="any_family_employed" value="1" id="any_family_employed_yes">
                                    <label class="form-check-label" for="any_family_employed_yes">بله</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" wire:model.live="any_family_employed" value="0" id="any_family_employed_no">
                                    <label class="form-check-label" for="any_family_employed_no">خیر</label>
                                </div>
                            </div>
                        </div>

                        @if($any_family_employed == '1')
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold mb-1">توضیحات اعضای شاغل</label>
                                <textarea class="form-control form-control-sm @error('any_family_employed_description') is-invalid @enderror" wire:model="any_family_employed_description" rows="2" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                                @error('any_family_employed_description')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">آیا وسیله نقلیه دارند؟ <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="has_vehicle" id="vehicle_yes">
                                    <label class="form-check-label" for="vehicle_yes">بله</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="has_vehicle" id="vehicle_no">
                                    <label class="form-check-label" for="vehicle_no">خیر</label>
                                </div>
                            </div>
                            @error('has_vehicle') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">نوع وسیله نقلیه</label>
                            <select class="form-select form-select-sm @error('vehicle_type_id') is-invalid @enderror" wire:model.blur="vehicle_type_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
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
                                <label class="form-label small fw-semibold mb-1">مالکیت وسیله نقلیه</label>
                                <div class="border rounded-3 p-2 d-flex align-items-center flex-wrap gap-3" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" wire:model="vehicle_ownership_type" value="personal" id="vehicle_ownership_personal">
                                        <label class="form-check-label" for="vehicle_ownership_personal">شخصی</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" wire:model="vehicle_ownership_type" value="company" id="vehicle_ownership_company">
                                        <label class="form-check-label" for="vehicle_ownership_company">شراکتی</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" wire:model="vehicle_ownership_type" value="rented" id="vehicle_ownership_rented">
                                        <label class="form-check-label" for="vehicle_ownership_rented">استیجاری</label>
                                    </div>
                                </div>
                                @error('vehicle_ownership_type')
                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 8: Banking Information --}}
                @if($current_step === 8)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">اطلاعات بانکی</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 8</span>
                            </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">حساب شخصی دارد؟ <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
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

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">نسبت دارنده حساب</label>
                            {{-- این فیلد اکنون فقط برای نمایش است و مقدار آن توسط منطق Livewire تعیین می‌شود --}}
                            <input type="text"
                                   class="form-control form-control-sm"
                                   value="{{ $accountRelations->find($account_owner_relation_id)?->name ?? 'انتخاب نشده' }}"
                                   readonly
                                   disabled
                                   placeholder="به طور خودکار تعیین می‌شود"
                                   style="border-radius: 12px; background: #eef2f7; border-color: #dbe3ec; min-height: 42px;"
                            >
                            {{-- wire:model از روی این input حذف شده است، زیرا مقدار آن توسط کاربر تغییر نمی‌کند.
                                 Livewire مقدار account_owner_relation_id را از طریق پراپرتی خودش مدیریت می‌کند. --}}
                            @error('account_owner_relation_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <label class="form-label small fw-semibold mb-1">نام بانک <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('bank_id') is-invalid @enderror" wire:model.blur="bank_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره کارت</label>
                            <input type="text" class="form-control form-control-sm @error('card_number') is-invalid @enderror" wire:model.live.debounce="card_number"
                                   maxlength="16" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('card_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره شبا</label>
                            <input type="text" class="form-control form-control-sm ltr-input @error('sheba_number') is-invalid @enderror" wire:model.live.debounce="sheba_number"
                                   placeholder="IRXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="26" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('sheba_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره کارت یارانه</label>
                            <input type="text" class="form-control form-control-sm @error('subsidy_card_number') is-invalid @enderror" wire:model="subsidy_card_number"
                                   maxlength="16" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('subsidy_card_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره شبا یارانه</label>
                            <input type="text" class="form-control form-control-sm ltr-input @error('subsidy_sheba_number') is-invalid @enderror" wire:model.blur="subsidy_sheba_number"
                                   placeholder="IRXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="26" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('subsidy_sheba_number') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 9: Housing Status and Contact Information --}}
                @if($current_step === 9)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">وضعیت سکونت و اطلاعات تماس</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 9</span>
                            </div>

                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">وضعیت سکونت <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('residence_status_id') is-invalid @enderror" wire:model.blur="residence_status_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($residenceStatusTypes as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            @error('residence_status_id') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 col-md-6" x-data="{ open: false }">
                            <label class="form-label small fw-semibold mb-1">منطقه <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <button type="button" @click="open = !open" class="btn w-100 d-flex align-items-center justify-content-between text-end @error('district_id') border-danger @enderror" style="border-radius: 12px; background: #f8fafc; border: 1px solid #dbe3ec; min-height: 42px;">
                                    <span class="text-truncate">
                                        {{ $districts->firstWhere('id', $district_id)?->name ?? '— انتخاب منطقه —' }}
                                    </span>
                                    <i class="bi bi-funnel"></i>
                                </button>

                                <div x-show="open" @click.outside="open=false" x-transition class="position-absolute start-0 w-100 mt-2 p-2 bg-white border shadow-sm" style="z-index: 25; border-radius: 12px; border-color: #dbe3ec !important;">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                                        <input type="text" id="district-search-grid" oninput="filterDistrictGrid()" class="form-control border-0" placeholder="جستجو در مناطق...">
                                    </div>
                                    <div id="district-grid-list" class="row g-2" style="max-height: 260px; overflow-y: auto;">
                                        <div class="col-12 district-item" data-name="">
                                            <button type="button" class="btn btn-sm w-100 text-end" style="background: #f8fafc;" wire:click="$set('district_id', '')" @click="open=false">
                                            — انتخاب کنید —
                                            </button>
                                        </div>
                                        @foreach($districts as $district)
                                            <div class="col-md-4 col-6 district-item" data-name="{{ mb_strtolower($district->name) }}">
                                                <button
                                                    type="button"
                                                    wire:click="$set('district_id', '{{ $district->id }}')"
                                                    @click="open=false"
                                                    class="btn btn-sm w-100 text-end d-flex justify-content-between align-items-center"
                                                    style="background: #ffffff; border: 1px solid #eef2f7; border-radius: 8px;">
                                                    <span class="text-truncate">{{ $district->name }}</span>
                                                    @if((string)$district_id === (string)$district->id)
                                                        <i class="bi bi-check2 text-success ms-1"></i>
                                                    @endif
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('district_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <label class="form-label small fw-semibold mb-1">بومی شهر هستید؟ <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" value="1" wire:model.live="is_local_to_city" id="local_yes">
                                    <label class="form-check-label" for="local_yes">بله</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" value="0" wire:model.live="is_local_to_city" id="local_no">
                                    <label class="form-check-label" for="local_no">خیر</label>
                                </div>
                            </div>
                            @error('is_local_to_city') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($residence_status_id == 2)
                            {{-- 2 برای "اجاره‌ای" --}}
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label small fw-semibold mb-1">مبلغ ودیعه (ریال)</label>
                                <input type="number" class="form-control form-control-sm @error('deposit_amount') is-invalid @enderror" wire:model.blur="deposit_amount" min="0"
                                       placeholder="به ریال" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                @error('deposit_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label small fw-semibold mb-1">اجاره ماهیانه (ریال)</label>
                                <input type="number" class="form-control form-control-sm @error('monthly_rent') is-invalid @enderror" wire:model.blur="monthly_rent" min="0"
                                       placeholder="به ریال" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                @error('monthly_rent') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small fw-semibold mb-1">مدت زمان سکونت (سال)</label>
                            <input type="number" class="form-control form-control-sm @error('residence_duration_years') is-invalid @enderror" wire:model.blur="residence_duration_years"
                                   min="0" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('residence_duration_years') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">آدرس کامل <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm @error('address') is-invalid @enderror" wire:model.blur="address" rows="3" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                            @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>


                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره تلفن ثابت</label>
                            <input type="text" class="form-control form-control-sm @error('landline_phone') is-invalid @enderror" wire:model.blur="landline_phone" maxlength="11" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('landline_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره تلفن فرد معتمد</label>
                            <input type="text" class="form-control form-control-sm @error('trusted_person_phone') is-invalid @enderror" wire:model.blur="trusted_person_phone" maxlength="11" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('trusted_person_phone') <span
                                class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <!-- بخش نوع پیام‌رسان -->
                        <div class="col-lg-4 col-md-6">
                            <label for="messenger_type" class="form-label small fw-semibold mb-1">نوع پیام‌رسان (انتخابی یا تایپی)</label>

                            <input wire:model="messenger_type"
                                   type="text"
                                   class="form-control form-control-sm @error('messenger_type') is-invalid @enderror"
                                   id="messenger_type"
                                   list="messenger_list"
                                   placeholder="انتخاب کنید یا بنویسید..." style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">

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

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">شماره یا آیدی پیام‌رسان</label>
                            <input type="text" class="form-control form-control-sm @error('messenger_number') is-invalid @enderror" wire:model.blur="messenger_number" maxlength="11" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('messenger_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Step 10: Support Needs Level and Assistance Coverage --}}
                @if($current_step === 10)
                <div class="mb-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                                <h4 class="mb-0 fw-bold">سطح نیاز و پوشش حمایتی</h4>
                                <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 10</span>
                            </div>

                    <div class="row g-3">
                        <!-- انتخاب نهاد -->
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">نوع نهاد حمایتی</label>
                            <select wire:model.live="support_organization_id" class="form-select form-select-sm" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
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
                            <div x-transition class="col-lg-4 col-md-6">
                                <label class="form-label small fw-semibold mb-1">نام خیریه را وارد کنید</label>
                                <input type="text" wire:model="other_organization_name" class="form-control form-control-sm @error('other_organization_name') is-invalid @enderror" placeholder="مثلاً: خیریه امام علی (ع)" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                @error('other_organization_name') <span class="text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="col-lg-4 col-md-12">
                            <label class="form-label small fw-semibold mb-1">تاریخ شروع پوشش</label>
                            <div class="row g-2 dir-ltr">
                                <div class="col-4">
                                    <select wire:model.blur="coverage_start_day" class="form-select form-select-sm @error('coverage_start_day') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                        <option value="">روز</option>
                                        @foreach(range(1, 31) as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="coverage_start_month" class="form-select form-select-sm @error('coverage_start_month') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                        <option value="">ماه</option>
                                        @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                        @foreach($months as $key => $month)
                                            <option value="{{ $key }}">{{ $month }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select wire:model.blur="coverage_start_year" class="form-select form-select-sm @error('coverage_start_year') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
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
                        <div class="col-lg-8 col-md-12 mb-2">
                            <label class="form-label small fw-semibold mb-2">تصویر کارت حمایت</label>
                            <div class="border p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
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
                                                    <img src="{{ asset($person->supportCoverage->support_card_image) }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
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
                                        <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                        <input type="file" wire:model="support_card_image" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                        @error('support_card_image') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">سطح نیاز (بر اساس ارزیابی اولیه مددکار) <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('need_level_id') is-invalid @enderror" wire:model.blur="need_level_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($needLevelTypes as $level)
                                    <option value="{{ $level->id }}">{{ $level->title }}</option>
                                @endforeach
                            </select>
                            @error('need_level_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Wizard Navigation Buttons --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-3 p-3 p-md-4 border" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                    {{-- Previous Button --}}
                    @if($current_step > 1)
                        <button type="button" wire:click="previousStep" class="btn px-4" style="border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; min-height: 42px;">
                            <i class="bi bi-arrow-right-circle me-1"></i> مرحله قبل
                        </button>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        {{-- Temporary Save Button (sections 1-9) --}}
                        @if($current_step <= 9)
                            <button type="submit" wire:loading.attr="disabled" class="btn px-4" style="border-radius: 12px; border: 1px solid #16a34a; background: #f0fdf4; color: #166534; min-height: 42px;">
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    در حال ذخیره...
                                </span>
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-save2-fill me-1"></i> ذخیره موقت مددجو
                                </span>
                            </button>
                        @endif

                        {{-- Skip Button (not on last step) --}}
                        @if($current_step < $total_steps)
                            <button type="button" wire:click="skipStep" class="btn px-4" style="border-radius: 12px; border: 1px solid #fbbf24; background: #fffbeb; color: #92400e; min-height: 42px;">
                                <i class="bi bi-skip-forward-circle me-1"></i> رد کردن
                            </button>
                        @endif

                        {{-- Next or Submit Button --}}
                        @if($current_step < $total_steps)
                            <button type="button" wire:click="nextStep" class="btn btn-primary px-4" style="border-radius: 12px; min-height: 42px; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.18);">
                                مرحله بعد <i class="bi bi-arrow-left-circle ms-1"></i>
                            </button>
                        @else
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-success px-4" style="border-radius: 12px; min-height: 42px; box-shadow: 0 6px 16px rgba(22, 163, 74, 0.2);">
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    در حال ذخیره...
                                </span>
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check2-circle me-2"></i>
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

    window.filterDistrictGrid = function() {
        const input = document.getElementById('district-search-grid');
        const items = document.querySelectorAll('#district-grid-list .district-item');
        if (!input || !items.length) return;
        const query = input.value.trim().toLowerCase();
        items.forEach((item, index) => {
            if (index === 0) {
                item.style.display = '';
                return;
            }
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            item.style.display = name.includes(query) ? '' : 'none';
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
