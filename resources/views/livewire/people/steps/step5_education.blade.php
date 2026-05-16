
{{-- Step 5: Education Status --}}
@if($current_step === 5)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <h4 class="mb-0 fw-bold">وضعیت تحصیلی</h4>
                    <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 5</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">آیا مددجو در حال تحصیل است؟ </label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="1" wire:model.live="is_studying" id="studying_yes">
                                <label class="form-check-label" for="studying_yes">بله</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="0" wire:model.live="is_studying" id="studying_no">
                                <label class="form-check-label" for="studying_no">خیر</label>
                            </div>
                        </div>
                        @error('is_studying') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">مقطع تحصیلی</label>
                        <select class="form-select form-select-sm @error('education_level_id') is-invalid @enderror" wire:model.blur="education_level_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($is_studying != '1') disabled @endif>
                            <option value="">— انتخاب کنید —</option>
                            @foreach($educationLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                        @error('education_level_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">نام مدرسه/دانشگاه</label>
                        <input type="text" class="form-control form-control-sm @error('school_name') is-invalid @enderror" wire:model.blur="school_name" placeholder="نام مرکز آموزشی" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($is_studying != '1') disabled @endif>
                        @error('school_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">رشته تحصیلی</label>
                        <input type="text" class="form-control form-control-sm @error('major') is-invalid @enderror" wire:model.blur="major" placeholder="مثال: حسابداری" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($is_studying != '1') disabled @endif>
                        @error('major') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <hr class="my-4">

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">آیا همزمان با تحصیل کار می‌کند؟ </label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="1" wire:model.live="works_alongside_study" id="works_yes">
                                <label class="form-check-label" for="works_yes">بله</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="0" wire:model.live="works_alongside_study" id="works_no">
                                <label class="form-check-label" for="works_no">خیر</label>
                            </div>
                        </div>
                        @error('works_alongside_study') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>


                    <div class="col-md-3" x-data="{ amount: @js($monthly_income ?? '') }">
                        <label class="form-label small fw-semibold mb-1">درآمد ماهیانه از کار (ریال)</label>
                        <input type="number" class="form-control form-control-sm @error('monthly_income') is-invalid @enderror" wire:model.blur="monthly_income" x-model="amount" min="0" placeholder="150,000,000" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;" @if($works_alongside_study != '1') disabled @endif>
                        <x-money-preview/>
                        @error('monthly_income') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    @if($is_studying == '0')
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">دلیل عدم تحصیل</label>
                            <select class="form-select form-select-sm @error('reason_for_not_studying') is-invalid @enderror" wire:model.live="reason_for_not_studying" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                <option value="graduation">فارغ التحصیلی</option>
                                <option value="dropped_out">ترک تحصیل</option>
                                <option value="below_school_age">زیر سن مدرسه</option>
                            </select>
                            @error('reason_for_not_studying') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($this->shouldShowEducationDegreeField())
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">مدرک تحصیلی</label>
                                <select class="form-select form-select-sm @error('education_degree') is-invalid @enderror" wire:model.blur="education_degree" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($academicLevels as $level)
                                        <option value="{{ $level->id }}">{{ $level->title }}</option>
                                    @endforeach
                                </select>
                                @error('education_degree') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    @endif

                </div>
            </div>
        </div>
    </div>
@endif
