{{-- Section 1: Person Info --}}

<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3">۱. اطلاعات فردی مددجو</h4>
    <div class="row g-3">
        <div class="col-md-4"><label for="first_name" class="form-label">نام <span class="text-danger">*</span></label><input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" required></div>
        <div class="col-md-4"><label for="last_name" class="form-label">نام خانوادگی <span class="text-danger">*</span></label><input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" required></div>
        <div class="col-md-4"><label for="national_id" class="form-label">کد ملی <span class="text-danger">*</span></label><input type="text" class="form-control" name="national_id" maxlength="10" value="{{ old('national_id') }}" required></div>

{{--        <div class="col-md-4 mb-3">--}}
{{--            <label for="birth_day" class="form-label">روز تولد</label>--}}

{{--            <!-- کلاس form-select در بوت‌استرپ ظاهر زیبایی به لیست می‌دهد -->--}}
{{--            <select class="form-select @error('birth_day') is-invalid @enderror"--}}
{{--                    id="birth_day"--}}
{{--                    name="birth_day">--}}

{{--                <option value="" disabled selected>انتخاب...</option>--}}

{{--                --}}{{-- حلقه برای تولید اعداد 1 تا 31 --}}
{{--                @for ($i = 1; $i <= 31; $i++)--}}
{{--                    <option value="{{ $i }}" {{ old('birth_day') == $i ? 'selected' : '' }}>--}}
{{--                        {{ $i }}--}}
{{--                    </option>--}}
{{--                @endfor--}}

{{--            </select>--}}

{{--            @error('birth_day')--}}
{{--            <div class="invalid-feedback">{{ $message }}</div>--}}
{{--            @enderror--}}
{{--        </div>--}}
{{--        --}}
{{--        <div class="col-md-4 mb-3">--}}
{{--                <label for="birth_month" class="form-label">ماه تولد</label>--}}
{{--                <select class="form-select @error('birth_month') is-invalid @enderror"--}}
{{--                        id="birth_month"--}}
{{--                        name="birth_month">--}}

{{--                    <option value="" disabled selected>انتخاب کنید...</option>--}}

{{--                    --}}{{-- استفاده از آرایه تعریف شده در مدل --}}
{{--                    @foreach(\App\Models\Person::$months as $key => $value)--}}
{{--                        --}}{{-- $key میشود عدد (1) و $value میشود نام (فروردین) --}}
{{--                        <option value="{{ $key }}" {{ old('birth_month') == $key ? 'selected' : '' }}>--}}
{{--                            {{ $value }}--}}
{{--                        </option>--}}
{{--                    @endforeach--}}

{{--                </select>--}}
{{--                @error('birth_month')--}}
{{--                <div class="invalid-feedback">{{ $message }}</div>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--        <div class="col-md-4 mb-3">--}}
{{--            <label for="birth_year" class="form-label">سال تولد</label>--}}
{{--            <select class="form-select @error('birth_year') is-invalid @enderror"--}}
{{--                    id="birth_year"--}}
{{--                    name="birth_year">--}}

{{--                <option value="" disabled selected>انتخاب...</option>--}}

{{--                --}}{{-- حلقه از سال 1404 به پایین تا 1300 --}}
{{--                --}}{{-- اگر می‌خواهید سال جاری پویا باشد، می‌توانید از jdate('Y') استفاده کنید --}}
{{--                @for ($i = 1404; $i >= 1300; $i--)--}}
{{--                    <option value="{{ $i }}" {{ old('birth_year') == $i ? 'selected' : '' }}>--}}
{{--                        {{ $i }}--}}
{{--                    </option>--}}
{{--                @endfor--}}

{{--            </select>--}}
{{--            @error('birth_year')--}}
{{--            <div class="invalid-feedback">{{ $message }}</div>--}}
{{--            @enderror--}}
{{--        </div>--}}

        {{-- ✅ تاریخ تولد با کامپوننت جدید --}}
        {{-- تاریخ تولد --}}
        <div class="col-md-4">
            <x-jalali-datepicker
                name="birth_date"
                label="تاریخ تولد"
                :required="true"
                day-name="birth_day"
                month-name="birth_month"
                year-name="birth_year"
                full-date-name="birth_date_full"
                :day-value="old('birth_day', $person->birth_day ?? null)"
                :month-value="old('birth_month', $person->birth_month ?? null)"
                :year-value="old('birth_year', $person->birth_year ?? null)"
            />
        </div>

        </div>

        <div class="col-md-4"><label for="father_name" class="form-label">نام پدر</label><input type="text" class="form-control" name="father_name" value="{{ old('father_name') }}"></div>
    <!-- نمونه اصلاح شده برای کد ملی پدر -->
    <div class="col-md-3">
        <label for="father_national_id" class="form-label">کد ملی پدر</label>
        <input type="text"
               class="form-control"
               name="father_national_id"
               value="{{ old('father_national_id') }}"
               maxlength="10"> {{-- این قسمت اضافه شد --}}
    </div>

    <!-- نمونه اصلاح شده برای کد ملی مادر -->
    <div class="col-md-3">
        <label for="mother_national_id" class="form-label">کد ملی مادر</label>
        <input type="text"
               class="form-control"
               name="mother_national_id"
               value="{{ old('mother_national_id') }}"
               maxlength="10"> {{-- این قسمت اضافه شد --}}
    </div>

    @include('people.partials.sadaat_status')


        {{--جنسیت--}}
        <div class="col-md-4">
            <label class="form-label">جنسیت <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gender" value="مرد" @if(old('gender') == 'مرد') checked @endif required><label class="form-check-label">مرد</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gender" value="زن" @if(old('gender') == 'زن') checked @endif required><label class="form-check-label">زن</label></div>
            </div>
        </div>

        <div class="col-md-4">
            <label for="role" class="form-label">نقش در خانواده <span class="text-danger">*</span></label>
            <select class="form-select" name="role" required>
                <option value="فرزند" @if(old('role') == 'فرزند') selected @endif>فرزند</option>
                <option disabled value="سرپرست" @if(old('role') == 'سرپرست') selected @endif>سرپرست</option>
            </select>
        </div>


        @include('people.partials.skills')

        <div class="form-group mt-2">
            <label for="skills_description">توضیحات استعداد:</label>
            <textarea name="skills_description" class="form-control"></textarea>
        </div>



        <!-- بخش معلولیت مددجو -->
    <div class="col-md-4 mb-3">
        <label class="form-label">آیا دارای معلولیت هست؟</label>
        <div>
            <input type="radio" id="has_disability_yes" name="has_disability" value="1"
                {{ old('has_disability') === '1' ? 'checked' : '' }}>
            <label for="has_disability_yes">بله</label>

            <input type="radio" id="has_disability_no" name="has_disability" value="0"
                {{ old('has_disability', '0') === '0' ? 'checked' : '' }}>
            <label for="has_disability_no">خیر</label>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <label for="disability_type" class="form-label">نوع معلولیت</label>
        <select class="form-select" id="disability_type" name="disability_type_id" {{ old('has_disability','0') == '1' ? '' : 'disabled' }}>
            <option value="">انتخاب کنید...</option>
            @foreach(\App\Models\DisabilityType::all() as $type)
                <option value="{{ $type->id }}"
                    {{ old('disability_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="disability_description" class="form-label">توضیحات معلولیت</label>
        <textarea class="form-control" id="disability_description" name="disability_description"
              {{ old('has_disability','0') == '1' ? '' : 'disabled' }}>{{ old('disability_description') }}</textarea>
    </div>



        <div class="col-md-12">
            <label for="social_worker_id" class="form-label">مددکار مسئول <span class="text-danger">*</span></label>
            <select class="form-select" name="social_worker_id" required>
                <option value="" disabled selected>یک مددکار را انتخاب کنید...</option>
                @forelse($socialWorkers as $worker)
                    <option value="{{ $worker->id }}" @if(old('social_worker_id') == $worker->id) selected @endif>{{ $worker->fullName }} ({{ $worker->role }})</option>
                @empty
                    <option value="" disabled>هیچ مددکاری ثبت نشده است!</option>
                @endforelse
            </select>
        </div>
        <div class="col-md-6"><label for="photo_id_card" class="form-label">تصویر کارت ملی</label><input class="form-control" type="file" name="photo_id_card"></div>
        <div class="col-md-6"><label for="photo_birth_certificate" class="form-label">تصویر شناسنامه</label><input class="form-control" type="file" name="photo_birth_certificate"></div>
        <div class="form-group mt-3">
            <label>آپلود چهره در لحظه:</label>
            <button type="button" id="openCameraBtn" class="btn btn-primary">ثبت چهره</button>
            <input type="hidden" name="photo_live_capture" id="photo_live_capture">

            <video id="cameraStream" autoplay playsinline style="display:none; width:300px; border-radius:8px;"></video>
            <canvas id="photoCanvas" style="display:none;"></canvas>
        </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = document.querySelectorAll('input[name="has_disability"]');
            const typeSelect = document.getElementById('disability_type');
            const descTextarea = document.getElementById('disability_description');

            function toggleDisabilityFields() {
                const selected = document.querySelector('input[name="has_disability"]:checked');
                if (selected && selected.value === '1') {
                    typeSelect.disabled = false;
                    descTextarea.disabled = false;
                } else {
                    typeSelect.disabled = true;
                    descTextarea.disabled = true;
                    typeSelect.value = '';
                    descTextarea.value = '';
                }
            }

            radios.forEach(radio => {
                radio.addEventListener('change', toggleDisabilityFields);
            });

            toggleDisabilityFields(); // اجرای اولیه
        });
    </script>



    </div>
</div>
