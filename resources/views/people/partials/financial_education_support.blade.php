<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3">۵. اطلاعات مالی، تحصیلی و حمایتی</h4>
    <div class="row g-3">
        {{-- Bank Info --}}
        <div class="col-md-3">
            <label class="form-label">حساب شخصی دارد؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_own_account" value="1" @if(old('has_own_account') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_own_account" value="0" @if(old('has_own_account', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-3"><label for="account_holder_relation" class="form-label">نسبت دارنده حساب</label><input type="text" class="form-control" name="account_holder_relation" value="{{ old('account_holder_relation') }}"></div>
        <div class="col-md-3"><label for="bank_name" class="form-label">نام بانک</label><input type="text" class="form-control" name="bank_name" value="{{ old('bank_name') }}"></div>
        <div class="col-md-3"><label for="card_number" class="form-label">شماره کارت</label><input type="text" class="form-control" name="card_number" value="{{ old('card_number') }}"></div>
        <div class="col-md-6"><label for="sheba_number" class="form-label">شماره شبا</label><input type="text" class="form-control" name="sheba_number" value="{{ old('sheba_number') }}" placeholder="IR..." style="text-align:left; direction:ltr;"></div>
        <div class="col-md-3"><label for="subsidy_card_number" class="form-label">شماره کارت یارانه</label><input type="text" class="form-control" name="subsidy_card_number" value="{{ old('subsidy_card_number') }}"></div>
        <div class="col-md-6"><label for="subsidy_sheba_number" class="form-label">شماره شبا یارانه</label><input type="text" class="form-control" name="subsidy_sheba_number" value="{{ old('subsidy_sheba_number') }}" placeholder="IR..." style="text-align:left; direction:ltr;"></div>

        {{-- Education Info --}}
        <div class="col-12"><hr class="my-4"></div>
        <div class="col-md-3">
            <label class="form-label">در حال تحصیل است؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="is_studying" value="1" @if(old('is_studying') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="is_studying" value="0" @if(old('is_studying', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-3"><label for="school_name" class="form-label">نام مدرسه/دانشگاه</label><input type="text" class="form-control" name="school_name" value="{{ old('school_name') }}"></div>
        <div class="col-md-3"><label for="major" class="form-label">رشته تحصیلی</label><input type="text" class="form-control" name="major" value="{{ old('major') }}"></div>
        <div class="col-md-3"><label for="education_level" class="form-label">مقطع تحصیلی</label><input type="text" class="form-control" name="education_level" value="{{ old('education_level') }}"></div>
        <div class="col-md-12"><label for="drop_reason" class="form-label">دلیل ترک تحصیل</label><textarea class="form-control" name="drop_reason">{{ old('drop_reason') }}</textarea></div>
        <div class="col-md-4">
            <label class="form-label">همزمان با تحصیل کار می‌کند؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="works_alongside_study" value="1" @if(old('works_alongside_study') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="works_alongside_study" value="0" @if(old('works_alongside_study', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-4"><label for="monthly_income" class="form-label">درآمد ماهانه (تومان)</label><input type="number" class="form-control" name="monthly_income" value="{{ old('monthly_income') }}"></div>
        <div class="col-md-12"><label for="talent_description" class="form-label">توضیح استعداد خاص</label><textarea class="form-control" name="talent_description">{{ old('talent_description') }}</textarea></div>

        {{-- Support Coverage --}}
        <div class="col-12"><hr class="my-4"></div>
        <div class="col-md-3"><label for="organization_type" class="form-label">نوع سازمان حمایتی</label><input type="text" class="form-control" name="organization_type" value="{{ old('organization_type') }}" placeholder="کمیته امداد، بهزیستی، ..."></div>
        <div class="col-md-3"><label for="organization_name" class="form-label">نام دقیق سازمان</label><input type="text" class="form-control" name="organization_name" value="{{ old('organization_name') }}"></div>
        <div class="col-md-3"><label for="coverage_start_date" class="form-label">تاریخ شروع پوشش</label><input type="date" class="form-control" name="coverage_start_date" value="{{ old('coverage_start_date') }}"></div>
        <div class="col-md-3"><label for="support_card_image" class="form-label">تصویر کارت پوشش</label><input class="form-control" type="file" name="support_card_image"></div>
    </div>
</div>
