<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3">۳. اطلاعات سرپرست</h4>
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

        <div class="col-md-3"><label for="job_type" class="form-label">نوع کار</label><input type="text" class="form-control" name="job_type" value="{{ old('job_type') }}" placeholder="کارگر فصلی، روزمزد، ..."></div>
        <div class="col-md-3"><label for="guardian_phone_number" class="form-label">شماره تماس سرپرست</label><input type="text" class="form-control" name="guardian_phone_number" value="{{ old('guardian_phone_number') }}"></div>
        <div class="col-md-3"><label for="children_count" class="form-label">تعداد کل فرزندان</label><input type="number" class="form-control" name="children_count" value="{{ old('children_count') }}"></div>
        <div class="col-md-3"><label for="children_in_house" class="form-label">فرزندان ساکن در منزل</label><input type="number" class="form-control" name="children_in_house" value="{{ old('children_in_house') }}"></div>
        <div class="col-md-3"><label for="insurance_status" class="form-label">وضعیت بیمه</label><input type="text" class="form-control" name="insurance_status" value="{{ old('insurance_status') }}"></div>
        <div class="col-md-3"><label for="other_insurance" class="form-label">نام بیمه دیگر</label><input type="text" class="form-control" name="other_insurance" value="{{ old('other_insurance') }}"></div>
        <div class="col-md-3"><label for="divorced_child_at_home" class="form-label">فرزند مطلقه در منزل</label><input type="text" class="form-control" name="divorced_child_at_home" value="{{ old('divorced_child_at_home') }}" placeholder="دختر / پسر / هیچکدام"></div>
        <div class="col-md-3"><label for="average_income" class="form-label">درآمد متوسط خانوار (تومان)</label><input type="number" class="form-control" name="average_income" value="{{ old('average_income') }}"></div>
        <div class="col-md-3">
            <label class="form-label">آیا کسی در خانواده شاغل است؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="any_family_employed" value="1" @if(old('any_family_employed') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="any_family_employed" value="0" @if(old('any_family_employed', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">آیا وسیله نقلیه دارید؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_vehicle" value="1" @if(old('has_vehicle') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_vehicle" value="0" @if(old('has_vehicle', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-6"><label for="vehicle_type" class="form-label">نوع وسیله نقلیه</label><input type="text" class="form-control" name="vehicle_type" value="{{ old('vehicle_type') }}" placeholder="موتورسیکلت، پراید، ..."></div>
    </div>
</div>
