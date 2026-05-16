<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mt-0 py-2 fw-bold">ثبت اطلاعات مددجوی جدید</h3>
            <p class="text-sm mb-2 fw-light">اپراتور گرامی لطفاً تمامی اطلاعات موردنیاز را با دقت وارد کنید.</p>
        </div>
        <div class="card-body">
            {{-- شروع فرم --}}
            <form wire:submit.prevent="save">

                {{-- Wizard Progress Bar --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">مرحله {{ $current_step }} از {{ $total_steps }}: {{ $wizard_steps[$current_step] }}</h5>
                        <span class="badge bg-primary">{{ number_format($this->wizardProgress, 0) }}% تکمیل شده</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                             role="progressbar"
                             style="width: {{ $this->wizardProgress }}%;"
                             aria-valuenow="{{ $this->wizardProgress }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            {{ number_format($this->wizardProgress, 0) }}%
                        </div>
                    </div>

                    {{-- Step Indicators --}}
                    <div class="d-flex justify-content-between mt-3 flex-wrap">
                        @foreach($wizard_steps as $stepNum => $stepName)
                            <div class="text-center" style="flex: 1; min-width: 80px;">
                                <button type="button" wire:click="goToStep({{ $stepNum }})" class="btn p-0 border-0 bg-transparent">
                                    <div class="position-relative d-inline-flex">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                                            @if($stepNum < $current_step) bg-success text-white
                                            @elseif($stepNum == $current_step) bg-primary text-white
                                            @else bg-secondary text-white @endif"
                                            style="width: 35px; height: 35px; font-weight: bold;">
                                            @if($stepNum < $current_step)
                                                <i class="bi bi-check-lg"></i>
                                            @else
                                                {{ $stepNum }}
                                            @endif
                                        </div>
                                        @if($show_step_error_badges && ($this->stepIncompleteCounts[$stepNum] ?? 0) > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white"
                                                  style="font-size: 0.65rem; min-width: 1.35rem;">
                                                {{ $this->stepIncompleteCounts[$stepNum] }}
                                            </span>
                                        @endif
                                    </div>
                                </button>
                                <div class="small" style="font-size: 0.75rem;">{{ $stepName }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- پیام‌های فلش برای موفقیت یا خطا --}}
                @if (session()->has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any() && $is_submitted)
                    <div class="alert alert-danger">
                        <p><strong>لطفاً خطاهای زیر را برطرف کنید:</strong></p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($mode === 'edit' && $person)
                    <div class="mb-4 rounded-4 border bg-white p-3 p-md-4 shadow-sm" style="border-color: #dbe3ec;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h5 class="mb-0 fw-bold text-slate-800">رهگیری فعالیت کاربران</h5>
                            <span class="badge text-bg-light border">شناسه مددجو: {{ $person->person_code ?? '-' }}</span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="rounded-3 border p-3 h-100" style="border-color: #e2e8f0; background: #f8fafc;">
                                    <p class="small text-muted mb-1">ایجادکننده</p>
                                    <p class="fw-semibold mb-1">{{ $person->creator?->name ?? 'نامشخص' }}</p>
                                    <p class="small mb-0 text-slate-600">{{ optional($person->created_at)->format('Y/m/d H:i:s') ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="rounded-3 border p-3 h-100" style="border-color: #e2e8f0; background: #f8fafc;">
                                    <p class="small text-muted mb-1">آخرین ویرایش توسط</p>
                                    <p class="fw-semibold mb-1">{{ $person->updater?->name ?? $person->creator?->name ?? 'نامشخص' }}</p>
                                    <p class="small mb-0 text-slate-600">{{ optional($person->updated_at)->format('Y/m/d H:i:s') ?? '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div hidden class="rounded-3 border p-3" style="border-color: #e2e8f0;">
                            <p class="fw-semibold mb-2">تاریخچه تغییرات</p>
                            @if($person->auditLogs->isEmpty())
                                <p class="small text-muted mb-0">هنوز لاگی برای این مددجو ثبت نشده است.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>زمان</th>
                                            <th>کاربر</th>
                                            <th>نوع عملیات</th>
                                            <th>فیلدهای تغییر یافته</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($person->auditLogs as $log)
                                            <tr>
                                                <td class="small">{{ optional($log->created_at)->format('Y/m/d H:i:s') }}</td>
                                                <td class="small">{{ $log->user?->name ?? 'سیستم/نامشخص' }}</td>
                                                <td class="small fw-semibold">
                                                    @if($log->action === 'created')
                                                        ایجاد
                                                    @elseif($log->action === 'updated')
                                                        ویرایش
                                                    @elseif($log->action === 'deleted')
                                                        حذف
                                                    @else
                                                        {{ $log->action }}
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @if(!empty($log->changed_fields))
                                                        {{ implode('، ', $log->changed_fields) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($current_step === 1)
                    @include('livewire.people.steps.step1_personal_info')
                @endif

                @if($current_step === 2)
                    @include('livewire.people.steps.step2_skills')
                @endif

                @if($current_step === 3)
                    @include('livewire.people.steps.step3_disability')
                @endif

                @if($current_step === 4)
                    @include('livewire.people.steps.step4_documents')
                @endif

                @if($current_step === 5)
                    @include('livewire.people.steps.step5_education')
                @endif

                @if($current_step === 6)
                    @include('livewire.people.steps.step6_family')
                @endif

                @if($current_step === 7)
                    @include('livewire.people.steps.step7_guardian')
                @endif

                @if($current_step === 8)
                    @include('livewire.people.steps.step8_bank')
                @endif

                @if($current_step === 9)
                    @include('livewire.people.steps.step9_residence')
                @endif

                @if($current_step === 10)
                    @include('livewire.people.steps.step10_support')
                @endif



                {{-- Wizard Navigation Buttons --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-3 p-3 p-md-4 border" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                    {{-- Previous Button --}}
                    @if($current_step > 1)
                        <button type="button" wire:click="previousStep" class="btn px-4" style="border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; min-height: 42px;">
                            <i class="bi bi-arrow-right-circle me-1"></i> مرحله قبل
                        </button>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        {{-- Temporary Save Button (sections 1-9) --}}
                        @if($current_step <= 9)
                            <button type="submit" wire:loading.attr="disabled" class="btn px-4" style="border-radius: 12px; border: 1px solid #16a34a; background: #f0fdf4; color: #166534; min-height: 42px;">
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    در حال ذخیره...
                                </span>
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-save2-fill me-1"></i> ذخیره موقت مددجو
                                </span>
                            </button>
                        @endif

                        {{-- Skip Button (not on last step) --}}
                        @if($current_step < $total_steps)
                            <button type="button" wire:click="skipStep" class="btn px-4" style="border-radius: 12px; border: 1px solid #fbbf24; background: #fffbeb; color: #92400e; min-height: 42px;">
                                <i class="bi bi-skip-forward-circle me-1"></i> رد کردن
                            </button>
                        @endif

                        {{-- Next or Submit Button --}}
                        @if($current_step < $total_steps)
                            <button type="button" wire:click="nextStep" class="btn btn-primary px-4" style="border-radius: 12px; min-height: 42px; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.18);">
                                مرحله بعد <i class="bi bi-arrow-left-circle ms-1"></i>
                            </button>
                        @else
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-success px-4" style="border-radius: 12px; min-height: 42px; box-shadow: 0 6px 16px rgba(22, 163, 74, 0.2);">
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    در حال ذخیره...
                                </span>
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check2-circle me-2"></i>
                                    ثبت نهایی اطلاعات
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@script
<script>
    /**
     * تأییدیه قبل از فعال‌سازی دوربین (فقط در حالت Edit)
     */
    window.confirmRetakePhoto = function(cameraId, base64VarName, label) {
        if (!confirm('آیا می‌خواهید تصویر ' + label + ' را مجدداً ثبت کنید؟')) {
            return;
        }
        setupCamera(cameraId, base64VarName);
    };

    /**
     * فعال‌سازی دوربین
     */
    window.setupCamera = function(cameraId, base64VarName) {
        const video = document.getElementById('video-' + cameraId);
        const captureBtn = document.getElementById('capture-btn-' + cameraId);
        const preview = document.getElementById('photo-preview-' + cameraId);

        if (!video) return;

        // مخفی کردن دکمه "گرفتن مجدد" اگر وجود داشت
        const retakeBtn = document.getElementById('retake-btn-' + cameraId);
        if (retakeBtn) {
            retakeBtn.classList.add('d-none');
        }

        // درخواست دسترسی به دوربین
        navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 960 },
                height: { ideal: 720 }
            }
        })
            .then(function(stream) {
                video.srcObject = stream;
                video.classList.remove('d-none');
                captureBtn.classList.remove('d-none');

                // مخفی کردن پیش‌نمایش فعلی
                if (preview) {
                    preview.style.display = 'none';
                }
            })
            .catch(function(err) {
                alert('خطا در دسترسی به دوربین: ' + err.message);
            });
    };

    window.filterDistrictGrid = function() {
        const input = document.getElementById('district-search-grid');
        const items = document.querySelectorAll('#district-grid-list .district-item');
        if (!input || !items.length) return;
        const query = input.value.trim().toLowerCase();
        items.forEach((item, index) => {
            if (index === 0) {
                item.style.display = '';
                return;
            }
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            item.style.display = name.includes(query) ? '' : 'none';
        });
    };


    /**
     * گرفتن عکس از دوربین + نمایش دکمه "گرفتن مجدد"
     */
    window.takePhoto = function(cameraId, base64VarName) {
        const video = document.getElementById('video-' + cameraId);
        const canvas = document.getElementById('canvas-' + cameraId);
        const preview = document.getElementById('photo-preview-' + cameraId);
        const captureBtn = document.getElementById('capture-btn-' + cameraId);

        if (!video || !canvas) return;

        // ثبت تصویر با رزولوشن بالاتر برای خوانایی بهتر مدارک
        const ctx = canvas.getContext('2d');
        const sourceWidth = video.videoWidth || 960;
        const sourceHeight = video.videoHeight || 720;
        const targetWidth = 960;
        const scale = sourceWidth > targetWidth ? (targetWidth / sourceWidth) : 1;

        canvas.width = Math.round(sourceWidth * scale);
        canvas.height = Math.round(sourceHeight * scale);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const jpegQuality = cameraId === 'id-card' || cameraId === 'birth-cert' ? 0.92 : 0.9;
        const base64 = canvas.toDataURL('image/jpeg', jpegQuality);

        // ذخیره روی سرور به‌صورت فایل موقت و نگه‌داری فقط مسیر در state
        $wire.storeCapturedImage(base64VarName, base64);

        // متوقف کردن دوربین
        const stream = video.srcObject;
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }

        // مخفی کردن ویدیو
        video.classList.add('d-none');

        // نمایش پیش‌نمایش عکس گرفته شده
        if (preview) {
            preview.style.display = 'block';
            preview.innerHTML = `
                <img src="${base64}" class="img-thumbnail" style="max-height: 150px;">
                <p class="text-success small mt-1 mb-0">📷 عکس ثبت شد</p>
            `;
        }

        // مخفی کردن دکمه "گرفتن عکس"
        captureBtn.classList.add('d-none');

        // ✅ نمایش دکمه "گرفتن مجدد"
        let retakeBtn = document.getElementById('retake-btn-' + cameraId);
        if (!retakeBtn) {
            // ایجاد دکمه برای اولین بار
            retakeBtn = document.createElement('button');
            retakeBtn.type = 'button';
            retakeBtn.id = 'retake-btn-' + cameraId;
            retakeBtn.className = 'btn btn-sm btn-outline-warning mt-1';
            retakeBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> گرفتن مجدد';
            retakeBtn.onclick = function() {
                retakePhoto(cameraId, base64VarName);
            };
            // اضافه کردن کنار دکمه‌های موجود
            captureBtn.parentNode.appendChild(retakeBtn);
        } else {
            // نمایش دکمه‌ای که قبلاً ایجاد شده
            retakeBtn.classList.remove('d-none');
        }
    };

    /**
     * ✅ گرفتن مجدد عکس - دوبین را دوباره فعال می‌کند
     */
    window.retakePhoto = function(cameraId, base64VarName) {
        // پاک کردن عکس قبلی از Livewire
        $wire.set(base64VarName, null);

        // مخفی کردن دکمه "گرفتن مجدد"
        const retakeBtn = document.getElementById('retake-btn-' + cameraId);
        if (retakeBtn) {
            retakeBtn.classList.add('d-none');
        }

        // ریست پیش‌نمایش
        const preview = document.getElementById('photo-preview-' + cameraId);
        if (preview) {
            preview.innerHTML = `
                <img src="{{ asset('images/no-images.png') }}"
                     id="captured-img-${cameraId}"
                     class="img-thumbnail"
                     style="max-height: 150px;">
            `;
        }

        // فعال‌سازی مجدد دوربین
        setupCamera(cameraId, base64VarName);
    };
</script>
@endscript
