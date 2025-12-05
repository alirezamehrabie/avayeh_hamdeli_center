<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3">۵. اطلاعات مالی، تحصیلی و حمایتی</h4>
    <div class="row g-3">

        {{-- Bank Info Section --}}
        <div class="col-md-3">
            <label class="form-label">حساب شخصی دارد؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="has_own_account" id="account_yes" value="1"
                           {{ old('has_own_account') == '1' ? 'checked' : '' }} required>
                    <label class="form-check-label" for="account_yes">بله</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="has_own_account" id="account_no" value="0"
                           {{ old('has_own_account', '0') == '0' ? 'checked' : '' }} required>
                    <label class="form-check-label" for="account_no">خیر</label>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <label for="account_owner_relation_id" class="form-label">نسبت دارنده حساب</label>
            <select name="account_owner_relation_id" id="account_owner_relation_id" class="form-select">
                <option value="">— انتخاب کنید —</option>
                @foreach($accountRelations as $relation)
                    <option value="{{ $relation->id }}"
                            data-is-self="{{ $relation->name === 'شخص مددجو' ? 'true' : 'false' }}"
                            data-is-other="{{ $relation->name === 'سایر' ? 'true' : 'false' }}"
                        {{ old('account_owner_relation_id') == $relation->id ? 'selected' : '' }}>
                        {{ $relation->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- فیلد مخفی برای توضیحات "سایر" --}}
        <div class="col-md-3" id="other_relation_box" style="display: none;">
            <label for="other_account_owner_relation" class="form-label">نام نسبت (سایر)</label>
            <input type="text" class="form-control" name="other_account_owner_relation" id="other_account_owner_relation"
                   value="{{ old('other_account_owner_relation') }}" placeholder="مثلاً: عمو، همسایه...">
        </div>

        <div class="col-md-3">
            <label for="bank_id" class="form-label">نام بانک</label>
            <select class="form-select" name="bank_id" id="bank_id">
                <option value="">انتخاب کنید...</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                        {{ $bank->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Card Number Section --}}
        <div class="col-md-3">
            <label for="visual_card_number" class="form-label">شماره کارت</label>

            {{-- فیلد نمایشی برای کاربر (فرمت شده) --}}
            <input type="text" class="form-control card-mask"
                   id="visual_card_number"
                   maxlength="19"
                   placeholder="____-____-____-____"
                   dir="ltr" style="text-align: center; letter-spacing: 1px;">

            {{-- فیلد اصلی که به سرور ارسال می‌شود (مخفی) --}}
            <input type="hidden" name="card_number" id="card_number" value="{{ old('card_number') }}">
        </div>


        <div class="col-md-3">
            <label for="visual_subsidy_card_number" class="form-label">شماره کارت یارانه</label>

            {{-- فیلد نمایشی یارانه --}}
            <input type="text" class="form-control card-mask"
                   id="visual_subsidy_card_number"
                   maxlength="19"
                   placeholder="____-____-____-____"
                   dir="ltr" style="text-align: center; letter-spacing: 1px;">

            {{-- فیلد اصلی یارانه (مخفی) --}}
            <input type="hidden" name="subsidy_card_number" id="subsidy_card_number" value="{{ old('subsidy_card_number') }}">
        </div>



        <div class="col-md-6">
            <label for="sheba_number" class="form-label">شماره شبا</label>
            <input type="text" class="form-control" name="sheba_number" value="{{ old('sheba_number') }}" placeholder="IR..." style="text-align:left; direction:ltr;">
        </div>


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
        <p>مقطع تحصیلی</p>
        <select name="education_level_id" class="form-control">
            <option value="">— انتخاب کنید —</option>
            @foreach(\App\Models\EducationLevel::orderBy('sort_order')->get() as $level)
                <option value="{{ $level->id }}"
                    {{ old('education_level_id', $education->education_level_id ?? '') == $level->id ? 'selected' : '' }}>
                    {{ $level->name }}
                </option>
            @endforeach
        </select>

        <div class="col-md-12"><label for="drop_reason" class="form-label">دلیل ترک تحصیل</label><textarea class="form-control" name="drop_reason">{{ old('drop_reason') }}</textarea></div>
        <div class="col-md-4">
            <label class="form-label">همزمان با تحصیل کار می‌کند؟ <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="works_alongside_study" value="1" @if(old('works_alongside_study') == '1') checked @endif required><label class="form-check-label">بله</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="works_alongside_study" value="0" @if(old('works_alongside_study', '0') == '0') checked @endif required><label class="form-check-label">خیر</label></div>
            </div>
        </div>
        <div class="col-md-4"><label for="monthly_income" class="form-label">درآمد ماهانه (تومان)</label><input type="number" class="form-control" name="monthly_income" value="{{ old('monthly_income') }}"></div>

        <div class="col-12"><hr class="my-4"></div>

        <div class="col-md-4"><label for="organization_type" class="form-label">نوع سازمان حمایتی</label><input type="text" class="form-control" name="organization_type" value="{{ old('organization_type') }}" placeholder="کمیته امداد، بهزیستی، ..."></div>
        <div class="col-md-4">
            <x-jalali-datepicker
                name="coverage_start_date"
                label="تاریخ شروع پوشش حمایتی"
                :required="false"
                dayName="coverage_start_day"
                monthName="coverage_start_month"
                yearName="coverage_start_year"
                fullDateName="coverage_start_date_full"
                dayValue="{{ old('coverage_start_day') }}"
                monthValue="{{ old('coverage_start_month') }}"
                yearValue="{{ old('coverage_start_year') }}"
            />
        </div>
        <div class="col-md-4"><label for="support_card_image" class="form-label">تصویر کارت پوشش</label><input class="form-control" type="file" name="support_card_image"></div>



    </div>
</div>
