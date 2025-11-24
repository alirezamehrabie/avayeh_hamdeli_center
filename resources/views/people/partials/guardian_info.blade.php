<div class="mb-5 mt-5">
    <h4 class="border-bottom pb-2 mb-3">۳. اطلاعات سرپرست</h4>

    <!-- START: Dynamic Guardian Alert -->
    <div id="guardian-dynamic-alert" class="alert alert-info align-items-center d-none" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle-fill flex-shrink-0 me-2 ms-2" viewBox="0 0 16 16">
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
        </svg>
        <div>
            توجه: سرپرست این مددجو <strong id="guardian-role-text" class="text-decoration-underline mx-1"></strong> می‌باشد.
        </div>
    </div>
    <!-- END: Dynamic Guardian Alert -->
    <div class="row g-3">
        <div class="col-md-3"><label for="guardian_birth_date" class="form-label">تاریخ تولد سرپرست</label><input type="date" class="form-control" name="guardian_birth_date" value="{{ old('guardian_birth_date') }}"></div>


        <label for="occupation_id">شغل سرپرست</label>
        <select name="occupation_id" id="occupation_id" class="form-control">
            <option value="">— انتخاب کنید —</option>
            @foreach(\App\Models\Occupation::orderBy('sort_order')->get() as $occ)
                <option value="{{ $occ->id }}"
                    {{ old('occupation_id') == $occ->id ? 'selected' : '' }}>
                    {{ $occ->name }}
                </option>
            @endforeach
        </select>
        @error('occupation_id')
        <span class="text-danger">{{ $message }}</span>
        @enderror

        <div class="col-md-3">
            <label for="job_type_id" class="form-label">نوع قرارداد کاری</label>
            <select name="job_type_id" id="job_type_id" class="form-control">
                <option value="">— انتخاب کنید —</option>
                @foreach($jobTypes as $type)
                    <option value="{{ $type->id }}"
                        {{ old('job_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3"><label for="guardian_phone_number" class="form-label">شماره تماس سرپرست</label><input type="text" class="form-control" name="guardian_phone_number" value="{{ old('guardian_phone_number') }}"></div>
        <div class="col-md-3"><label for="children_count" class="form-label">تعداد کل فرزندان</label><input type="number" class="form-control" name="children_count" value="{{ old('children_count') }}"></div>
        <div class="col-md-3"><label for="children_in_house" class="form-label">فرزندان ساکن در منزل</label><input type="number" class="form-control" name="children_in_house" value="{{ old('children_in_house') }}"></div>

        <!-- Insurance Status Radio -->
        <div class="col-md-3">
            <label class="form-label">وضعیت بیمه <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="insurance_status" id="insurance_yes" value="1"
                        {{ old('insurance_status') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="insurance_yes">دارد</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="insurance_status" id="insurance_no" value="0"
                        {{ old('insurance_status', '0') == '0' ? 'checked' : '' }}>
                    <label class="form-check-label" for="insurance_no">ندارد</label>
                </div>
            </div>
        </div>

        <!-- Insurance Type Select -->
        <div class="col-md-3">
            <label for="insurance_type_id" class="form-label">نوع پوشش بیمه</label>
            <select name="insurance_type_id" id="insurance_type_id" class="form-control" disabled>
                <option value="">— انتخاب کنید —</option>
                @foreach($insuranceTypes as $insType)
                    <option value="{{ $insType->id }}"
                        {{ old('insurance_type_id') == $insType->id ? 'selected' : '' }}>
                        {{ $insType->name }}
                    </option>
                @endforeach
            </select>
            @error('insurance_type_id')
            <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>

        {{-- Divorced Child At Home Select --}}
        <div class="col-md-3">
            <label for="divorced_child_at_home" class="form-label">فرزند مطلقه در منزل</label>
            <select name="divorced_child_at_home" id="divorced_child_at_home" class="form-select">
                @php
                    // لیست اصلاح شده با ۴ گزینه
                    $options = [
                        'ندارد',
                        'پسر',
                        'دختر',
                        'پسر / دختر'
                    ];

                    // اگر کاربر قبلاً انتخابی داشته (old) آن را بگذار، در غیر این صورت 'ندارد' را انتخاب کن
                    $selectedValue = old('divorced_child_at_home', 'ندارد');
                @endphp

                @foreach($options as $option)
                    <option value="{{ $option }}"
                        {{ $selectedValue == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>

            @error('divorced_child_at_home')
            <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>



        <div class="col-md-3"><label for="average_income" class="form-label">درآمد متوسط خانوار (تومان)</label><input type="number" class="form-control" name="average_income" value="{{ old('average_income') }}"></div>
        <div class="col-md-3">
            <label class="form-label">آیا کسی در خانواده شاغل است؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="any_family_employed" value="1" @if(old('any_family_employed') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="any_family_employed" value="0" @if(old('any_family_employed', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <!-- Has Vehicle Radio -->
        <div class="col-md-3">
            <label class="form-label">آیا وسیله نقلیه دارید؟ <span class="text-danger">*</span></label>
            <div>
                {{-- پیش‌فرض: خیر (Value=0) --}}
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="has_vehicle" id="vehicle_yes" value="1"
                        {{ old('has_vehicle') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="vehicle_yes">بله</label>
                </div>

                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="has_vehicle" id="vehicle_no" value="0"
                        {{ old('has_vehicle', '0') == '0' ? 'checked' : '' }}> {{-- پیش فرض checked روی 0 --}}
                    <label class="form-check-label" for="vehicle_no">خیر</label>
                </div>
            </div>
        </div>

        <!-- Vehicle Type Select -->
        <div class="col-md-6">
            <label for="vehicle_type_id" class="form-label">نوع وسیله نقلیه</label>
            <select name="vehicle_type_id" id="vehicle_type_id" class="form-select" disabled>
                <option value="">— انتخاب کنید —</option>
                @foreach($vehicleTypes as $vType)
                    <option value="{{ $vType->id }}"
                        {{ old('vehicle_type_id') == $vType->id ? 'selected' : '' }}>
                        {{ $vType->name }}
                    </option>
                @endforeach
            </select>
            @error('vehicle_type_id')
            <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
