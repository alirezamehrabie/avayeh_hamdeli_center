<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات سکونت و تماس</h4>
    <div class="row g-3">
        <div class="col-md-3">
            <label for="residence_status" class="form-label">وضعیت سکونت <span class="text-danger">*</span></label>
            <select name="residence_status_id" class="form-control" required>
                <option value="">انتخاب وضعیت سکونت...</option>
                @foreach(\App\Models\ResidenceStatusType::orderBy('sort_order')->get() as $status)
                    <option value="{{ $status->id }}"
                        {{ old('residence_status_id', $residence->residence_status_id ?? '') == $status->id ? 'selected' : '' }}>
                        {{ $status->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">بومی شهر محل سکونت؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="is_local_to_city" value="1" @if(old('is_local_to_city') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="is_local_to_city" value="0" @if(old('is_local_to_city', '1') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-3"><label for="deposit_amount" class="form-label">میزان ودیعه (تومان)</label><input type="number" class="form-control" name="deposit_amount" value="{{ old('deposit_amount') }}"></div>
        <div class="col-md-3"><label for="monthly_rent" class="form-label">اجاره ماهانه (تومان)</label><input type="number" class="form-control" name="monthly_rent" value="{{ old('monthly_rent') }}"></div>
        <div class="col-md-6"><label for="residence_duration_years" class="form-label">مدت اقامت فعلی (سال)</label><input type="number" class="form-control" name="residence_duration_years" value="{{ old('residence_duration_years') }}"></div>
        <div class="form-group">
            <label for="district_id">ناحیه / محل</label>
            <select name="district_id" id="district_id" class="form-control">
                <option value="">— انتخاب کنید —</option>
                @foreach(\App\Models\District::orderBy('sort_order')->get() as $d)
                    <option value="{{ $d->id }}"
                        {{ old('residence_status_id', $residence->district_id ?? '') == $d->id ? 'selected' : '' }}>
                        {{ $d->name }}
                    </option>
                @endforeach
            </select>
            @error('district_id')
            <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-12"><label for="address" class="form-label">نشانی کامل <span class="text-danger">*</span></label><textarea class="form-control" name="address" required>{{ old('address') }}</textarea></div>
        <div class="col-md-3"><label for="personal_phone" class="form-label">شماره تماس شخصی</label><input type="text" class="form-control" name="personal_phone" value="{{ old('personal_phone') }}"></div>
        <div class="col-md-3"><label for="landline_phone" class="form-label">تلفن ثابت</label><input type="text" class="form-control" name="landline_phone" value="{{ old('landline_phone') }}"></div>
        <div class="col-md-3"><label for="trusted_person_phone" class="form-label">شماره فرد معتمد</label><input type="text" class="form-control" name="trusted_person_phone" value="{{ old('trusted_person_phone') }}"></div>
        <div class="col-md-3">
            <label for="messenger_type" class="form-label">نوع پیام‌رسان</label>
            <input class="form-control" list="datalistOptions" name="messenger_type" value="{{ old('messenger_type') }}" placeholder="ایتا، واتس‌اپ و...">
            <datalist id="datalistOptions">
                <option value="ایتا">
                <option value="واتس‌اپ">
                <option value="تلگرام">
                <option value="بله">
                <option value="روبیکا">
            </datalist>
        </div>
        <div class="col-md-3"><label for="messenger_number" class="form-label">شماره پیام‌رسان</label><input type="text" class="form-control" name="messenger_number" value="{{ old('messenger_number') }}"></div>
    </div>
</div>
