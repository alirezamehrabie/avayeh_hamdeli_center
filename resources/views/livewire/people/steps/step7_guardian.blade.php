

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
                        <label class="form-label small fw-semibold mb-1">تاریخ تولد سرپرست </label>
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
                        <label class="form-label small fw-semibold mb-1">شغل سرپرست </label>
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


                    <div class="col-md-4" x-data="{ amount: @js($average_income ?? '') }">
                        <label class="form-label small fw-semibold mb-1">متوسط درآمد ماهیانه (ریال)</label>
                        <input
                            type="number"
                            class="form-control form-control-sm @error('average_income') is-invalid @enderror"
                            wire:model="average_income"
                            x-model="amount"
                            min="0"
                            style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                            placeholder="250,000,000"
                        >
                        <x-money-preview/>
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
                        <label class="form-label small fw-semibold mb-1">آیا وسیله نقلیه دارند؟ </label>
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
