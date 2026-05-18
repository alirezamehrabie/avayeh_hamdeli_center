

{{-- Step 3: Disability Information --}}
@if($current_step === 3)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <div>
                        <h5 class="mb-1 fw-bold">اطلاعات معلولیت و آسیب</h5>
                        <p class="mb-0 small text-muted">نوع آسیب را مشخص کنید و در صورت وجود بیماری خاص یا معلولیت، جزئیات آن را تکمیل نمایید.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-2">نوع آسیب (چند انتخابی) <span class="text-danger">*</span></label>
                        <div class="border p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="row g-2">
                                @forelse($allHarmTypes as $harm)
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="harm_{{ $harm->id }}" class="w-100 h-100 d-flex align-items-center gap-2 px-3 py-2 border bg-white" style="border-radius: 10px; border-color: #e2e8f0 !important; cursor: pointer;">
                                            <input
                                                class="form-check-input m-0 p-2"
                                                type="checkbox"
                                                value="{{ $harm->id }}"
                                                wire:model="harm_types"
                                                id="harm_{{ $harm->id }}"
                                            >
                                            <span class="small text-dark">{{ $harm->title }}</span>
                                        </label>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted small">هیچ نوع آسیبی در سیستم ثبت نشده است.</div>
                                @endforelse
                            </div>
                        </div>
                        @error('harm_types') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-2">آیا دارای بیماری خاص یا معلولیت هست؟</label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                            <div class="form-check mb-0">
                                <input type="radio" class="form-check-input" value="1" wire:model.live="has_disability" id="has_disability_yes">
                                <label class="form-check-label small" for="has_disability_yes">بله</label>
                            </div>
                            <div class="form-check mb-0">
                                <input type="radio" class="form-check-input" value="0" wire:model.live="has_disability" id="has_disability_no">
                                <label class="form-check-label small" for="has_disability_no">خیر</label>
                            </div>
                        </div>
                        @error('has_disability') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">نوع بیماری خاص یا معلولیت</label>
                        <select class="form-select form-select-sm @error('disability_type_id') is-invalid @enderror"
                                wire:model.blur="disability_type_id"
                                style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                                @if($has_disability != '1') disabled @endif>
                            <option value="">انتخاب کنید...</option>
                            @foreach($disabilityTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('disability_type_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">توضیحات بیماری یا معلولیت</label>
                        <textarea class="form-control form-control-sm @error('disability_description') is-invalid @enderror"
                                  wire:model.blur="disability_description"
                                  rows="3"
                                  placeholder="توضیح بیماری خاص یا معلولیت مددجو ..."
                                  style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"
                                  @if($has_disability != '1') disabled @endif></textarea>
                        @error('disability_description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
