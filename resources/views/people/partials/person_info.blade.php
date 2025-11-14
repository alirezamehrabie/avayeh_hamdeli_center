{{-- Section 1: Person Info --}}
<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3">۱. اطلاعات فردی مددجو</h4>
    <div class="row g-3">
        <div class="col-md-4"><label for="first_name" class="form-label">نام <span class="text-danger">*</span></label><input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" required></div>
        <div class="col-md-4"><label for="last_name" class="form-label">نام خانوادگی <span class="text-danger">*</span></label><input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" required></div>
        <div class="col-md-4"><label for="national_id" class="form-label">کد ملی <span class="text-danger">*</span></label><input type="text" class="form-control" name="national_id" value="{{ old('national_id') }}" required></div>
        <div class="form-group mt-3">
            <label for="birth_day">تاریخ تولد مددجو</label>
            <div class="row">
                <div class="col-md-3">
                    <label for="birth_day">روز تولد</label>
                    <input type="number" name="birth_day" id="birth_day" min="1" max="31" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label for="birth_month">ماه تولد</label>
                    <select name="birth_month" id="birth_month" class="form-control" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="فروردین">فروردین</option>
                        <option value="اردیبهشت">اردیبهشت</option>
                        <option value="خرداد">خرداد</option>
                        <option value="تیر">تیر</option>
                        <option value="مرداد">مرداد</option>
                        <option value="شهریور">شهریور</option>
                        <option value="مهر">مهر</option>
                        <option value="آبان">آبان</option>
                        <option value="آذر">آذر</option>
                        <option value="دی">دی</option>
                        <option value="بهمن">بهمن</option>
                        <option value="اسفند">اسفند</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="birth_year">سال تولد</label>
                    <input type="number" name="birth_year" id="birth_year" min="1300" max="1500" class="form-control" required>
                </div>
            </div>
        </div>
        <div class="col-md-4"><label for="father_name" class="form-label">نام پدر</label><input type="text" class="form-control" name="father_name" value="{{ old('father_name') }}"></div>
        <div class="col-md-4"><label for="father_national_id" class="form-label">کد ملی پدر</label><input type="text" class="form-control" name="father_national_id" value="{{ old('father_national_id') }}"></div>
        <div class="col-md-4"><label for="mother_national_id" class="form-label">کد ملی مادر</label><input type="text" class="form-control" name="mother_national_id" value="{{ old('mother_national_id') }}"></div>

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
                <option value="سرپرست" @if(old('role') == 'سرپرست') selected @endif>سرپرست</option>
            </select>
        </div>

        <div class="form-group neumorphic-input">
            <label for="guardian_role">نوع سرپرست:</label>
            <select name="guardian_role" id="guardian_role" class="form-control">
                <option value="">انتخاب کنید</option>
                <option value="پدر خانواده">پدر خانواده</option>
                <option value="مادر خانواده">مادر خانواده</option>
                <option value="سایر">سایر</option>
            </select>
        </div>


        <div class="form-group mt-3">
            <label>استعدادهای شخصی مددجو:</label><br>
            @foreach(['استعداد ورزشی','هنری','فرهنگی و مذهبی','علمی و تحصیلی','فنی و حرفه‌ای','رسانه‌ای و دیجیتال'] as $skill)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="skills[]" value="{{ $skill }}">
                    <label class="form-check-label">{{ $skill }}</label>
                </div>
            @endforeach
        </div>

        <div class="form-group mt-2">
            <label for="skills_description">توضیحات استعداد:</label>
            <textarea name="skills_description" class="form-control"></textarea>
        </div>



        <!-- بخش معلولیت مددجو -->
        <div class="form-group mt-3">
            <label>آیا فرد دارای معلولیت است؟</label><br>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="has_disability" id="has_disability_yes" value="1">
                <label class="form-check-label" for="has_disability_yes">بله</label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="has_disability" id="has_disability_no" value="0" checked>
                <label class="form-check-label" for="has_disability_no">خیر</label>
            </div>
        </div>

        <div id="disability_section" style="display:none;">
            <div class="form-group mt-2">
                <label for="disability_type">نوع معلولیت</label>
                <select id="disability_type" name="disability_type" class="form-control" disabled>
                    <option value="">انتخاب کنید...</option>
                    <option value="جسمی">جسمی</option>
                    <option value="ذهنی">ذهنی</option>
                    <option value="بینایی">بینایی</option>
                    <option value="شنوایی">شنوایی</option>
                </select>
            </div>

            <div class="form-group mt-2">
                <label for="disability_description">توضیحات تکمیلی معلولیت مددجو</label>
                <textarea id="disability_description" name="disability_description" class="form-control" disabled></textarea>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const yesRadio = document.getElementById('has_disability_yes');
                const noRadio  = document.getElementById('has_disability_no');
                const disabilitySection = document.getElementById('disability_section');
                const typeSelect = document.getElementById('disability_type');
                const descInput  = document.getElementById('disability_description');

                function toggleDisabilityFields() {
                    if (yesRadio.checked) {
                        disabilitySection.style.display = 'block';
                        typeSelect.disabled = false;
                        descInput.disabled = false;
                    } else {
                        disabilitySection.style.display = 'none';
                        typeSelect.disabled = true;
                        descInput.disabled = true;
                        typeSelect.value = '';
                        descInput.value = '';
                    }
                }

                yesRadio.addEventListener('change', toggleDisabilityFields);
                noRadio.addEventListener('change', toggleDisabilityFields);
                toggleDisabilityFields(); // initialize
            });
        </script>



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
            const openCameraBtn = document.getElementById('openCameraBtn');
            const cameraStream = document.getElementById('cameraStream');
            const photoCanvas = document.getElementById('photoCanvas');
            const photoInput = document.getElementById('photo_live_capture');

            openCameraBtn.addEventListener('click', async () => {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
                cameraStream.srcObject = stream;
                cameraStream.style.display = 'block';
                setTimeout(() => {
                    photoCanvas.width = cameraStream.videoWidth;
                    photoCanvas.height = cameraStream.videoHeight;
                    photoCanvas.getContext('2d').drawImage(cameraStream, 0, 0);
                    photoInput.value = photoCanvas.toDataURL('image/jpeg');
                    stream.getTracks().forEach(track => track.stop());
                    cameraStream.style.display = 'none';
                }, 3000); // بعد از ۳ ثانیه عکس گرفته می‌شود
            });
        </script>

    </div>
</div>
