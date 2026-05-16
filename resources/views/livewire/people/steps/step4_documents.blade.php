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
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-2">تصویر کارت ملی</label>

                        <div class="border bg-light p-3 p-md-4 rounded-4 h-100 shadow-sm">

                            <!-- بخش دوربین -->
                            <div class="mb-4 pb-3 border-bottom">
                                <div id="camera-container-id-card" wire:ignore>

                                    <video id="video-id-card"
                                           width="100%"
                                           height="270"
                                           autoplay
                                           class="rounded-4 border bg-dark-subtle d-none w-100"></video>

                                    <canvas id="canvas-id-card"
                                            width="960"
                                            height="720"
                                            class="d-none"></canvas>

                                    <div id="photo-preview-id-card" class="text-center">
                                        @if($photo_id_card)
                                            <img src="{{ $photo_id_card->temporaryUrl() }}"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @elseif($captured_id_card_base64)
                                            <img src="{{ \Illuminate\Support\Str::startsWith($captured_id_card_base64, 'data:image') ? $captured_id_card_base64 : asset($captured_id_card_base64) }}"
                                                 id="captured-img-id-card"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @elseif($mode == 'edit' && $person && $person->photo_id_card)
                                            <img src="{{ asset($person->photo_id_card) }}"
                                                 id="captured-img-id-card"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @else
                                            <img src="{{ asset('images/no-images.png?v=3') }}"
                                                 id="captured-img-id-card"
                                                 class="img-thumbnail rounded-4 w-100 bg-white"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @endif
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                                        @if($mode == 'edit')
                                            <button type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0"
                                                    style="background:#fff7ed; color:#c2410c;"
                                                    onclick="confirmRetakePhoto('id-card', 'captured_id_card_base64', 'کارت ملی')">
                                                <i class="bi bi-arrow-repeat"></i>
                                                تصویر مجدد
                                            </button>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0"
                                                    style="background:#0069fa; color:#ffffff;"
                                                    onclick="setupCamera('id-card', 'captured_id_card_base64')">
                                                <i class="bi bi-camera"></i>
                                                فعالسازی دوربین
                                            </button>
                                        @endif

                                        <button type="button"
                                                id="capture-btn-id-card"
                                                class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0 d-none"
                                                style="background:#007953; color:#ffffff;"
                                                onclick="takePhoto('id-card', 'captured_id_card_base64')">
                                            <i class="bi bi-camera-fill"></i>
                                            گرفتن عکس
                                        </button>
                                    </div>


                                </div>
                            </div>

                            <!-- بخش انتخاب فایل -->
                            <div class="bg-white border rounded-4 p-3 p-md-4">
                                <div class="small fw-semibold text-secondary mb-2">یا انتخاب فایل</div>
                                <p class="small text-muted mb-3">تصویر موردنظر را از حافظه دستگاه بارگذاری کنید.</p>

                                <input type="file"
                                       wire:model="photo_id_card"
                                       id="photo_id_card"
                                       class="d-none"
                                       accept="image/*">

                                <label for="photo_id_card"
                                       class="w-100 rounded-4 p-4 text-center bg-light"
                                       style="border: 1px solid #cbd5e1; cursor: pointer;">
                                    <i class="bi bi-cloud-arrow-up fs-4 text-primary d-block mb-2"></i>
                                    <span class="d-block fw-semibold text-dark mb-1">برای انتخاب فایل کلیک کنید</span>
                                    <span class="small text-muted d-block">فرمت‌های مجاز: JPG, PNG</span>
                                </label>

                                @error('photo_id_card')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
                            </div>


                        </div>
                    </div>




                    <!-- بخش تصویر شناسنامه -->
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-2">تصویر شناسنامه</label>

                        <div class="border bg-light p-3 p-md-4 rounded-4 h-100 shadow-sm">

                            <!-- بخش دوربین -->
                            <div class="mb-4 pb-3 border-bottom">
                                <div id="camera-container-birth-cert" wire:ignore>

                                    <video id="video-birth-cert"
                                           width="100%"
                                           height="270"
                                           autoplay
                                           class="rounded-4 border bg-dark-subtle d-none w-100"></video>

                                    <canvas id="canvas-birth-cert"
                                            width="960"
                                            height="720"
                                            class="d-none"></canvas>

                                    <div id="photo-preview-birth-cert" class="text-center">
                                        @if($photo_birth_certificate)
                                            <img src="{{ $photo_birth_certificate->temporaryUrl() }}"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @elseif($captured_birth_certificate_base64)
                                            <img src="{{ \Illuminate\Support\Str::startsWith($captured_birth_certificate_base64, 'data:image') ? $captured_birth_certificate_base64 : asset($captured_birth_certificate_base64) }}"
                                                 id="captured-img-birth-cert"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @elseif($mode == 'edit' && $person && $person->photo_birth_certificate)
                                            <img src="{{ asset($person->photo_birth_certificate) }}"
                                                 id="captured-img-birth-cert"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @else
                                            <img src="{{ asset('images/no-images.png?v=3') }}"
                                                 id="captured-img-birth-cert"
                                                 class="img-thumbnail rounded-4 w-100 bg-white"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @endif
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                                        @if($mode == 'edit')
                                            <button type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0"
                                                    style="background:#fff7ed; color:#c2410c;"
                                                    onclick="confirmRetakePhoto('birth-cert', 'captured_birth_certificate_base64', 'شناسنامه')">
                                                <i class="bi bi-arrow-repeat"></i>
                                                تصویر مجدد
                                            </button>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0"
                                                    style="background:#0069fa; color:#ffffff;"
                                                    onclick="setupCamera('birth-cert', 'captured_birth_certificate_base64')">
                                                <i class="bi bi-camera"></i>
                                                فعالسازی دوربین
                                            </button>
                                        @endif

                                        <button type="button"
                                                id="capture-btn-birth-cert"
                                                class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0 d-none"
                                                style="background:#007953; color:#f2fffa;"
                                                onclick="takePhoto('birth-cert', 'captured_birth_certificate_base64')">
                                            <i class="bi bi-camera-fill"></i>
                                            گرفتن عکس
                                        </button>
                                    </div>


                                </div>
                            </div>

                            <!-- بخش انتخاب فایل -->
                            <div class="bg-white border rounded-4 p-3 p-md-4">
                                <div class="small fw-semibold text-secondary mb-2">یا انتخاب فایل</div>
                                <p class="small text-muted mb-3">تصویر موردنظر را از حافظه دستگاه بارگذاری کنید.</p>

                                <input type="file"
                                       wire:model="photo_birth_certificate"
                                       id="photo_birth_certificate"
                                       class="d-none"
                                       accept="image/*">

                                <label for="photo_birth_certificate"
                                       class="w-100 rounded-4 p-4 text-center bg-light"
                                       style="border: 1px solid #cbd5e1; cursor: pointer;">
                                    <i class="bi bi-cloud-arrow-up fs-4 text-primary d-block mb-2"></i>
                                    <span class="d-block fw-semibold text-dark mb-1">برای انتخاب فایل کلیک کنید</span>
                                    <span class="small text-muted d-block">فرمت‌های مجاز: JPG, PNG</span>
                                </label>

                                @error('photo_birth_certificate')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
                            </div>


                        </div>
                    </div>




                    <!-- بخش تصویر مددجو -->
                    <div class="col-xl-4 col-md-12">
                        <label class="form-label small fw-semibold text-secondary mb-2">تصویر مددجو</label>

                        <div class="border bg-light p-3 p-md-4 rounded-4 h-100 shadow-sm">

                            <!-- بخش دوربین -->
                            <div class="mb-4 pb-3 border-bottom">
                                <div id="camera-container-profile" wire:ignore>

                                    <video id="video-profile"
                                           width="100%"
                                           height="270"
                                           autoplay
                                           class="rounded-4 border bg-dark-subtle d-none w-100"></video>

                                    <canvas id="canvas-profile"
                                            width="960"
                                            height="720"
                                            class="d-none"></canvas>

                                    <div id="photo-preview-profile" class="text-center">
                                        @if($profile_photo)
                                            <img src="{{ $profile_photo->temporaryUrl() }}"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @elseif($captured_photo_base64)
                                            <img src="{{ \Illuminate\Support\Str::startsWith($captured_photo_base64, 'data:image') ? $captured_photo_base64 : asset($captured_photo_base64) }}"
                                                 id="captured-img-profile"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @elseif($mode == 'edit' && $person && $person->profile_photo)
                                            <img src="{{ asset($person->profile_photo) }}"
                                                 id="captured-img-profile"
                                                 class="img-thumbnail rounded-4 w-100"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @else
                                            <img src="{{ asset('images/no-images.png?v=3') }}"
                                                 id="captured-img-profile"
                                                 class="img-thumbnail rounded-4 w-100 bg-white"
                                                 style="max-height: 220px; object-fit: contain;">
                                        @endif
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                                        @if($mode == 'edit')
                                            <button type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0"
                                                    style="background:#fff7ed; color:#c2410c;"
                                                    onclick="confirmRetakePhoto('profile', 'captured_photo_base64', 'مددجو')">
                                                <i class="bi bi-arrow-repeat"></i>
                                                تصویر مجدد
                                            </button>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0"
                                                    style="background:#0069fa; color:#ffffff;"
                                                    onclick="setupCamera('profile', 'captured_photo_base64')">
                                                <i class="bi bi-camera"></i>
                                                فعالسازی دوربین
                                            </button>
                                        @endif

                                        <button type="button"
                                                id="capture-btn-profile"
                                                class="btn btn-sm rounded-3 px-3 py-2 d-flex align-items-center gap-1 border-0 d-none"
                                                style="background:#007953; color:#ffffff;"
                                                onclick="takePhoto('profile', 'captured_photo_base64')">
                                            <i class="bi bi-camera-fill"></i>
                                            گرفتن عکس
                                        </button>
                                    </div>


                                </div>
                            </div>

                            <!-- بخش انتخاب فایل -->
                            <div class="bg-white border rounded-4 p-3 p-md-4">
                                <div class="small fw-semibold text-secondary mb-2">یا انتخاب فایل</div>
                                <p class="small text-muted mb-3">تصویر موردنظر را از حافظه دستگاه بارگذاری کنید.</p>

                                <input type="file"
                                       wire:model="profile_photo"
                                       id="profile_photo"
                                       class="d-none"
                                       accept="image/*">

                                <label for="profile_photo"
                                       class="w-100 rounded-4 p-4 text-center bg-light"
                                       style="border: 1px solid #cbd5e1; cursor: pointer;">
                                    <i class="bi bi-cloud-arrow-up fs-4 text-primary d-block mb-2"></i>
                                    <span class="d-block fw-semibold text-dark mb-1">برای انتخاب فایل کلیک کنید</span>
                                    <span class="small text-muted">فرمت‌های مجاز: JPG, PNG</span>
                                </label>

                                @error('profile_photo')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
                            </div>


                        </div>
                    </div>



                </div>
            </div>
        </div>
    </div>
@endif
