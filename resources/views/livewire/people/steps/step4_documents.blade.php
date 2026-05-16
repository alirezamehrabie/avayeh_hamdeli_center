


{{-- Step 4: Identity Documents --}}
@if($current_step === 4)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <h4 class="mb-0 fw-bold">مدارک شناسایی</h4>
                    <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 4</span>
                </div>
                <div class="row g-4">

                    <!-- بخش تصویر کارت ملی -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-2">تصویر کارت ملی</label>
                        <div class="border h-100 p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="row">
                                <div class="col-md-7 border-start">
                                    <div id="camera-container-id-card" wire:ignore>
                                        <video id="video-id-card" width="100%" height="270" autoplay class="rounded border d-none"></video>
                                        <canvas id="canvas-id-card" width="960" height="720" class="d-none"></canvas>
                                        <div id="photo-preview-id-card" class="text-center">
                                            @if($photo_id_card)
                                                <img src="{{ $photo_id_card->temporaryUrl() }}" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @elseif($captured_id_card_base64)
                                                <img src="{{ \Illuminate\Support\Str::startsWith($captured_id_card_base64, 'data:image') ? $captured_id_card_base64 : asset($captured_id_card_base64) }}" id="captured-img-id-card" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @elseif($mode == 'edit' && $person && $person->photo_id_card)
                                                <img src="{{ asset($person->photo_id_card) }}" id="captured-img-id-card" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @else
                                                <img src="{{ asset('images/no-image.png') }}" id="captured-img-id-card" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @endif
                                        </div>
                                        <div class="mt-2 text-center">
                                            @if($mode == 'edit')
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        onclick="confirmRetakePhoto('id-card', 'captured_id_card_base64', 'کارت ملی')">
                                                    <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                </button>
                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        onclick="setupCamera('id-card', 'captured_id_card_base64')">
                                                    <i class="bi bi-camera"></i> فعالسازی دوربین
                                                </button>
                                            @endif
                                            <button type="button"
                                                    id="capture-btn-id-card"
                                                    class="btn btn-sm btn-success d-none"
                                                    onclick="takePhoto('id-card', 'captured_id_card_base64')">
                                                <i class="bi bi-camera-fill"></i> گرفتن عکس
                                            </button>
                                            {{-- دکمه "گرفتن مجدد" بعد از عکس گرفتن توسط JS ایجاد می‌شود --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                    <input type="file" wire:model="photo_id_card" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                    @error('photo_id_card') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- بخش تصویر شناسنامه -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-2">تصویر شناسنامه</label>
                        <div class="border h-100 p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="row">
                                <div class="col-md-7 border-start">
                                    <div id="camera-container-birth-cert" wire:ignore>
                                        <video id="video-birth-cert" width="100%" height="270" autoplay class="rounded border d-none"></video>
                                        <canvas id="canvas-birth-cert" width="960" height="720" class="d-none"></canvas>
                                        <div id="photo-preview-birth-cert" class="text-center">
                                            @if($photo_birth_certificate)
                                                <img src="{{ $photo_birth_certificate->temporaryUrl() }}" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @elseif($captured_birth_certificate_base64)
                                                <img src="{{ \Illuminate\Support\Str::startsWith($captured_birth_certificate_base64, 'data:image') ? $captured_birth_certificate_base64 : asset($captured_birth_certificate_base64) }}" id="captured-img-birth-cert" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @elseif($mode == 'edit' && $person && $person->photo_birth_certificate)
                                                <img src="{{ asset($person->photo_birth_certificate) }}" id="captured-img-birth-cert" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @else
                                                <img src="{{ asset('images/no-image.png') }}" id="captured-img-birth-cert" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @endif
                                        </div>
                                        <div class="mt-2 text-center">
                                            @if($mode == 'edit')
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        onclick="confirmRetakePhoto('birth-cert', 'captured_birth_certificate_base64', 'شناسنامه')">
                                                    <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                </button>
                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        onclick="setupCamera('birth-cert', 'captured_birth_certificate_base64')">
                                                    <i class="bi bi-camera"></i> فعالسازی دوربین
                                                </button>
                                            @endif
                                            <button type="button"
                                                    id="capture-btn-birth-cert"
                                                    class="btn btn-sm btn-success d-none"
                                                    onclick="takePhoto('birth-cert', 'captured_birth_certificate_base64')">
                                                <i class="bi bi-camera-fill"></i> گرفتن عکس
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                    <input type="file" wire:model="photo_birth_certificate" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                    @error('photo_birth_certificate') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- بخش تصویر مددجو -->
                    <div class="col-lg-4 col-md-12">
                        <label class="form-label small fw-semibold mb-2">تصویر مددجو</label>
                        <div class="border h-100 p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="row">
                                <div class="col-md-10 border-start">
                                    <div id="camera-container-profile" wire:ignore>
                                        <video id="video-profile" width="100%" height="324" autoplay class="rounded border d-none"></video>
                                        <canvas id="canvas-profile" width="960" height="720" class="d-none"></canvas>
                                        <div id="photo-preview-profile" class="text-center">
                                            @if($profile_photo)
                                                <img src="{{ $profile_photo->temporaryUrl() }}" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @elseif($captured_photo_base64)
                                                <img src="{{ \Illuminate\Support\Str::startsWith($captured_photo_base64, 'data:image') ? $captured_photo_base64 : asset($captured_photo_base64) }}" id="captured-img-profile" class="img-thumbnail" style="width: 100%; max-height: 205px; object-fit: contain;">
                                            @elseif($mode == 'edit' && $person && $person->profile_photo)
                                                <img src="{{ asset($person->profile_photo) }}" id="captured-img-profile" class="img-thumbnail" style="width: 100%; max-height: 270px; object-fit: contain;">
                                            @else
                                                <img src="{{ asset('images/no-image.png') }}" id="captured-img-profile" class="img-thumbnail" style="width: 100%; max-height: 270px; object-fit: contain;">
                                            @endif
                                        </div>
                                        <div class="mt-2 text-center">
                                            @if($mode == 'edit')
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        onclick="confirmRetakePhoto('profile', 'captured_photo_base64', 'مددجو')">
                                                    <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                </button>
                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        onclick="setupCamera('profile', 'captured_photo_base64')">
                                                    <i class="bi bi-camera"></i> فعالسازی دوربین
                                                </button>
                                            @endif
                                            <button type="button"
                                                    id="capture-btn-profile"
                                                    class="btn btn-sm btn-success d-none"
                                                    onclick="takePhoto('profile', 'captured_photo_base64')">
                                                <i class="bi bi-camera-fill"></i> گرفتن عکس
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                    <input type="file" wire:model="profile_photo" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endif
