@if($current_step === 6)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <h4 class="mb-0 fw-bold">وضعیت خانوادگی</h4>
                    <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 6</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">نسبت سرپرست با مددجو <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm @error('guardian_relation_type_id') is-invalid @enderror" wire:model.live="guardian_relation_type_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="">— انتخاب کنید —</option>
                            @foreach($guardianRelationTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->title }}</option>
                            @endforeach
                        </select>
                        @error('guardian_relation_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1" for="remarried_parent">ازدواج مجدد والدین</label>
                        <select wire:model.live="remarried_parent" id="remarried_parent" class="form-select form-select-sm @error('remarried_parent') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="">انتخاب کنید...</option>
                            <option value="none">خیر</option>
                            <option value="father">فقط پدر</option>
                            <option value="mother">فقط مادر</option>
                            <option value="both">هر دو</option>
                        </select>
                        @error('remarried_parent') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">فرزندان از ازدواج قبلی والدین</label>
                        <input type="number" class="form-control form-control-sm @error('children_from_previous_marriage') is-invalid @enderror" wire:model.live.debounce.250ms="children_from_previous_marriage" placeholder="تعداد" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @disabled($remarried_parent === 'none')>
                        @error('children_from_previous_marriage') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">وضعیت بیماری یا معلولیت والدین</label>
                        <div class="border rounded-3 p-2 d-flex align-items-center gap-2" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="has_parent_disability" wire:model.live="has_parent_disability">
                                <label class="form-check-label text-sm" for="has_parent_disability">والدین دارای بیماری خاص یا معلولیت هستند؟</label>
                            </div>
                        </div>
                        @error('has_parent_disability') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    @if($has_parent_disability)
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold mb-1">توضیحات بیماری یا معلولیت والدین</label>
                            <textarea class="form-control form-control-sm @error('parent_disability_description') is-invalid @enderror" wire:model.blur="parent_disability_description" rows="2" placeholder="توضیح بیماری خاص یا معلولیت والدین ...." style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                            @error('parent_disability_description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">جزئیات و شرح حال مددجـو</label>
                        <textarea
                            class="p-3 fw-normal form-control form-control-sm @error('client_case_history') is-invalid @enderror"
                            wire:model.blur="client_case_history"
                            rows="4"
                            placeholder="خلاصه شرح حال، تاریخچه مشکلات و سوابق مددجو را اینجا وارد کنید ..."
                            style="border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0;"
                        ></textarea>
                        @error('client_case_history') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
