{{-- Section 1: Person Info --}}

<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3 font-bold">اطلاعات فردی مددجو</h4>
    <div class="row g-3">
        <div class="col-md-4"><label for="first_name" class="form-label">نام <span class="text-danger">*</span></label><input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" required></div>
        <div class="col-md-4"><label for="last_name" class="form-label">نام خانوادگی <span class="text-danger">*</span></label><input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" required></div>
        <div class="col-md-4"><label for="national_id" class="form-label">کد ملی <span class="text-danger">*</span></label><input type="text" class="form-control" name="national_id" maxlength="10" value="{{ old('national_id') }}" required></div>


        {{-- تاریخ تولد: سه فیلد مجزا --}}
        <div class="col-md-4">
            <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
            <div class="row g-2 dir-ltr">
                {{-- روز --}}
                <div class="col-4">
                    <select name="birth_day" class="form-select" required>
                        <option value="">روز</option>
                        @foreach(range(1, 31) as $day)
                            <option value="{{ $day }}"
                                {{ old('birth_day', $person->birth_day ?? '') == $day ? 'selected' : '' }}>
                                {{ $day }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ماه --}}
                <div class="col-4">
                    <select name="birth_month" class="form-select" required>
                        <option value="">ماه</option>
                        @php
                            $months = [
                                1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
                                4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
                                7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
                                10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
                            ];
                        @endphp
                        @foreach($months as $key => $month)
                            <option value="{{ $key }}"
                                {{ old('birth_month', $person->birth_month ?? '') == $key ? 'selected' : '' }}>
                                {{ $month }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- سال --}}
                <div class="col-4">
                    <select name="birth_year" class="form-select" required>
                        <option value="">سال</option>
                        @foreach(range(1300, 1420) as $year)
                            <option value="{{ $year }}"
                                {{ old('birth_year', $person->birth_year ?? '') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- نمایش خطاهای احتمالی برای هر سه فیلد --}}
            @if($errors->hasAny(['birth_day', 'birth_month', 'birth_year']))
                <div class="text-danger small mt-1">لطفاً تاریخ تولد را کامل وارد کنید.</div>
            @endif
        </div>



        <div class="col-md-4"><label for="father_name" class="form-label">نام پدر</label><input type="text" class="form-control" name="father_name" value="{{ old('father_name') }}"></div>


    <div class="col-md-2">
        <label for="father_national_id" class="form-label">کد ملی پدر</label>
        <input type="text"
               class="form-control"
               name="father_national_id"
               value="{{ old('father_national_id') }}"
               maxlength="10">
    </div>


    <div class="col-md-2">
        <label for="mother_national_id" class="form-label">کد ملی مادر</label>
        <input type="text"
               class="form-control"
               name="mother_national_id"
               value="{{ old('mother_national_id') }}"
               maxlength="10">
    </div>

        {{--جنسیت--}}
        <div class="col-md-2">
            <label class="form-label">جنسیت <span class="text-danger">*</span></label>
            <div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gender" value="مرد" @if(old('gender') == 'مرد') checked @endif required><label class="form-check-label">مرد</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gender" value="زن" @if(old('gender') == 'زن') checked @endif required><label class="form-check-label">زن</label></div>
            </div>
        </div>

    {{-- وضعیت سادات --}}
    <div class="form-group col-md-2">
        <label class="d-block">وضعیت سادات <span class="text-danger">*</span></label>
        <label class="radio-inline me-3">
            <input
                type="radio"
                name="sadaat_status"
                value="عام"
                {{ old('sadaat_status', 'عام') === 'عام' ? 'checked' : '' }}
            >
            عام
        </label>
        <label class="radio-inline">
            <input
                type="radio"
                name="sadaat_status"
                value="سادات"
                {{ old('sadaat_status') === 'سادات' ? 'checked' : '' }}
            >
            سادات
        </label>
        @error('sadaat_status')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- نسب سادات (مخفی/نمایان بر اساس رادیو بالا) --}}
    <div
        id="sadaat_relation_container"
        class="form-group col-md-3"
        style="display: none;"
    >
        <label for="sadaat_relation_id">نسب سادات <span class="text-danger">*</span></label>
        <select
            name="sadaat_relation_id"
            id="sadaat_relation_id"
            class="form-control"
        >
            <option value="">— انتخاب کنید —</option>
            @foreach($sadaatRelations as $rel)
                <option
                    value="{{ $rel->id }}"
                    {{ old('sadaat_relation_id') == $rel->id ? 'selected' : '' }}
                >
                    {{ $rel->name }}
                </option>
            @endforeach
        </select>
        @error('sadaat_relation_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
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
    <div class="col-md-2 mb-3">
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

    <div class="col-md-5 mb-3">
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

    <div class="col-md-5 mb-3">
        <label for="disability_description" class="form-label">توضیحات معلولیت</label>
        <textarea class="form-control h-1" id="disability_description" name="disability_description"
              {{ old('has_disability','0') == '1' ? '' : 'disabled' }}>{{ old('disability_description') }}</textarea>
    </div>



        <div class="col-md-4">
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
        <div class="col-md-4"><label for="photo_id_card" class="form-label">تصویر کارت ملی</label><input class="form-control" type="file" name="photo_id_card"></div>
        <div class="col-md-4"><label for="photo_birth_certificate" class="form-label">تصویر شناسنامه</label><input class="form-control" type="file" name="photo_birth_certificate"></div>

        <div class="form-group col-md-4 mt-3">
            <label>آپلود چهره در لحظه:</label>
            <button type="button" id="openCameraBtn" class="btn btn-primary">ثبت چهره</button>
            <input type="hidden" name="photo_live_capture" id="photo_live_capture">

            <video id="cameraStream" autoplay playsinline style="display:none; width:300px; border-radius:8px;"></video>
            <canvas id="photoCanvas" style="display:none;"></canvas>
        </div>

    </div>
</div>

@push('scripts')

{{-- اسکریپت وضعیت سادات --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('sadaat_relation_container');
            const radios = document.querySelectorAll('input[name="sadaat_status"]');

            function toggleSadaatRelation() {
                const selected = document.querySelector('input[name="sadaat_status"]:checked').value;
                if (selected === 'سادات') {
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                    const select = container.querySelector('select');
                    if (select) select.value = '';
                }
            }

            radios.forEach(radio => {
                radio.addEventListener('change', toggleSadaatRelation);
            });

            toggleSadaatRelation();
        });
    </script>

{{-- اسکریپت معلولیت --}}
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

@endpush
