
{{-- Step 10: Support Needs Level and Assistance Coverage --}}
@if($current_step === 10)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <h4 class="mb-0 fw-bold">سطح نیاز و پوشش حمایتی</h4>
                    <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 10</span>
                </div>

                <div class="row g-3">
                    <!-- انتخاب نهاد -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-1">نوع نهاد حمایتی</label>
                        <select wire:model.live="support_organization_id" class="form-select form-select-sm" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="">— انتخاب کنید —</option>
                            @foreach($support_organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(filled($support_organization_id))
                        <div x-transition class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">توضیحات نهاد حمایتی</label>
                            <input type="text" wire:model.blur="support_organization_description" class="form-control form-control-sm @error('support_organization_description') is-invalid @enderror" placeholder="توضیحات تکمیلی درباره این پوشش" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('support_organization_description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- فیلد شرطی برای "خیریه دیگر" -->
                    @php
                        $otherId = $support_organizations->where('slug', 'other')->first()?->id;
                    @endphp

                    @if($support_organization_id == $otherId)
                        <div x-transition class="col-lg-4 col-md-6">
                            <label class="form-label small fw-semibold mb-1">نام خیریه را وارد کنید</label>
                            <input type="text" wire:model="other_organization_name" class="form-control form-control-sm @error('other_organization_name') is-invalid @enderror" placeholder="مثلاً: خیریه امام علی (ع)" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            @error('other_organization_name') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="col-lg-4 col-md-12">
                        <label class="form-label small fw-semibold mb-1">تاریخ شروع پوشش</label>
                        <div class="row g-2 dir-ltr">
                            <div class="col-4">
                                <select wire:model.blur="coverage_start_day" class="form-select form-select-sm @error('coverage_start_day') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">روز</option>
                                    @foreach(range(1, 31) as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <select wire:model.blur="coverage_start_month" class="form-select form-select-sm @error('coverage_start_month') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">ماه</option>
                                    @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                    @foreach($months as $key => $month)
                                        <option value="{{ $key }}">{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <select wire:model.blur="coverage_start_year" class="form-select form-select-sm @error('coverage_start_year') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">سال</option>
                                    @foreach(range(1300, 1420) as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('coverage_start_year') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                        @error('coverage_start_month') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                        @error('coverage_start_day') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <!-- بخش تصویر کارت حمایت -->
                    <div class="col-lg-8 col-md-12 mb-2">
                        <label class="form-label small fw-semibold mb-2">تصویر کارت حمایت</label>
                        <div class="border p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="row">
                                <div class="col-md-7 border-start">
                                    <div id="camera-container-support-card" wire:ignore>
                                        <video id="video-support-card" width="100%" height="200" autoplay class="rounded border d-none"></video>
                                        <canvas id="canvas-support-card" width="640" height="480" class="d-none"></canvas>
                                        <div id="photo-preview-support-card" class="text-center">
                                            @if($support_card_image && !is_string($support_card_image))
                                                {{-- فایل آپلود شده جدید (Temporary Upload) --}}
                                                <img src="{{ $support_card_image->temporaryUrl() }}" class="img-thumbnail" style="max-height: 150px;">
                                            @elseif($captured_support_card_base64)
                                                {{-- تصویر گرفته شده با دوربین --}}
                                                <img src="{{ \Illuminate\Support\Str::startsWith($captured_support_card_base64, 'data:image') ? $captured_support_card_base64 : asset($captured_support_card_base64) }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
                                            @elseif($mode == 'edit' && $person && $person->supportCoverage && $person->supportCoverage->support_card_image)
                                                {{-- تصویر ذخیره شده قبلی در حالت ویرایش --}}
                                                <img src="{{ asset($person->supportCoverage->support_card_image) }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
                                            @else
                                                {{-- تصویر پیش‌فرض --}}
                                                <img src="{{ asset('images/no-image.png') }}" id="captured-img-support-card" class="img-thumbnail" style="max-height: 150px;">
                                            @endif
                                        </div>
                                        <div class="mt-2 text-center">
                                            @if($mode == 'edit')
                                                {{-- حالت ویرایش: دکمه "تصویر مجدد" با تأییدیه --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        onclick="confirmRetakePhoto('support-card', 'captured_support_card_base64', 'کارت حمایت')">
                                                    <i class="bi bi-arrow-repeat"></i> تصویر مجدد
                                                </button>
                                            @else
                                                {{-- حالت ایجاد: دکمه معمولی "فعالسازی دوربین" --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        onclick="setupCamera('support-card', 'captured_support_card_base64')">
                                                    <i class="bi bi-camera"></i> فعالسازی دوربین
                                                </button>
                                            @endif
                                            <button type="button"
                                                    id="capture-btn-support-card"
                                                    class="btn btn-sm btn-success d-none"
                                                    onclick="takePhoto('support-card', 'captured_support_card_base64')">
                                                <i class="bi bi-camera-fill"></i> گرفتن عکس
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <p class="small text-muted mb-1">یا انتخاب فایل:</p>
                                    <input type="file" wire:model="support_card_image" class="form-control form-control-sm" accept="image/*" style="border-radius: 12px; background: #ffffff; border-color: #dbe3ec; min-height: 42px;">
                                    @error('support_card_image') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-1">سطح نیاز (براساس ارزیابی مدیریت و سرگروهان) </label>
                        <select class="form-select form-select-sm @error('need_level_id') is-invalid @enderror" wire:model.blur="need_level_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="">— انتخاب کنید —</option>
                            @foreach($needLevelTypes as $level)
                                <option value="{{ $level->id }}">{{ $level->title }}</option>
                            @endforeach
                        </select>
                        @error('need_level_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
