<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات سکونت و تماس</h4>

    <div class="row g-3">

        {{-- وضعیت سکونت --}}
        <div class="col-md-3">
            <label class="form-label">وضعیت سکونت <span class="text-danger">*</span></label>
            <select name="residence_status_id" class="form-control" required>
                <option value="">انتخاب وضعیت سکونت...</option>
                @foreach(\App\Models\ResidenceStatusType::orderBy('sort_order')->get() as $status)
                    <option value="{{ $status->id }}"
                        @selected(old('residence_status_id', $residence->residence_status_id ?? '') == $status->id)>
                        {{ $status->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- بومی بودن --}}
        <div class="col-md-3">
            <label class="form-label">بومی شهر محل سکونت؟ <span class="text-danger">*</span></label>
            <div class="d-flex gap-3">
                <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="is_local_to_city" value="1"
                           @checked(old('is_local_to_city') == '1') required>
                    بله
                </label>

                <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="is_local_to_city" value="0"
                           @checked(old('is_local_to_city', '1') == '0') required>
                    خیر
                </label>
            </div>
        </div>

        {{-- ودیعه / اجاره --}}
        <div class="col-md-3">
            <label class="form-label">میزان ودیعه (تومان)</label>
            <input type="number" name="deposit_amount" class="form-control"
                   value="{{ old('deposit_amount') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">اجاره ماهانه (تومان)</label>
            <input type="number" name="monthly_rent" class="form-control"
                   value="{{ old('monthly_rent') }}">
        </div>

        {{-- مدت سکونت --}}
        <div class="col-md-6">
            <label class="form-label">مدت سکونت (سال)</label>
            <input type="number" name="residence_duration_years" class="form-control"
                   value="{{ old('residence_duration_years') }}">
        </div>

        {{-- محله / ناحیه --}}
        <div class="col-md-6">
            <label class="form-label">محله / ناحیه <span class="text-danger">*</span></label>
            <select name="district_id" class="form-control" required>
                <option value="">انتخاب محله...</option>
                @foreach(\App\Models\District::orderBy('sort_order')->get() as $district)
                    <option value="{{ $district->id }}"
                        @selected(old('district_id', $residence->district_id ?? '') == $district->id)>
                        {{ $district->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- آدرس --}}
        <div class="col-md-12">
            <label class="form-label">آدرس کامل <span class="text-danger">*</span></label>
            <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
        </div>

        {{-- تلفن‌ها --}}
        <div class="col-md-4">
            <label class="form-label">شماره موبایل مددجو *</label>
            <input type="text" name="personal_phone" class="form-control"
                   value="{{ old('personal_phone') }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">تلفن ثابت</label>
            <input type="text" name="landline_phone" class="form-control"
                   value="{{ old('landline_phone') }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">شماره فرد معتمد</label>
            <input type="text" name="trusted_person_phone" class="form-control"
                   value="{{ old('trusted_person_phone') }}">
        </div>

        {{-- پیام‌رسان --}}
        <div class="col-md-3">
            <label class="form-label">نوع پیام‌رسان</label>
            <select name="messenger_type" class="form-control">
                <option value="">انتخاب کنید...</option>
                <option value="whatsapp" @selected(old('messenger_type') == 'whatsapp')>واتساپ</option>
                <option value="telegram" @selected(old('messenger_type') == 'telegram')>تلگرام</option>
                <option value="other" @selected(old('messenger_type') == 'other')>سایر</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">شماره پیام‌رسان</label>
            <input type="text" name="messenger_number" class="form-control"
                   value="{{ old('messenger_number') }}">
        </div>

    </div>
</div>
