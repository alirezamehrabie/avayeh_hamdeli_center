{{-- Step 2: Skills and Talents --}}
@if($current_step === 2)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <div>
                        <h5 class="mb-1 fw-bold">مهارت‌ها و استعدادها</h5>
                        <p class="mb-0 small text-muted">مهارت های مرتبط را انتخاب کنید و در صورت نیاز توضیح کوتاه وارد نمایید.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-2">انتخاب مهارت ها</label>
                        <div class="border p-3 p-md-4" style="border-radius: 14px; background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="row g-2">
                                @forelse($allSkills as $skill)
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="skill_{{ $skill->id }}" class="w-100 h-100 d-flex align-items-center gap-2 px-3 py-2 border bg-white" style="border-radius: 10px; border-color: #e2e8f0 !important; cursor: pointer;">
                                            <input
                                                class="form-check-input m-0"
                                                type="checkbox"
                                                value="{{ $skill->id }}"
                                                wire:model="skills"
                                                id="skill_{{ $skill->id }}"
                                            >
                                            <span class="small text-dark">{{ $skill->name }}</span>
                                        </label>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted small">هیچ مهارتی در سیستم تعریف نشده است.</div>
                                @endforelse
                            </div>
                        </div>
                        @error('skills') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">توضیحات استعداد</label>
                        <textarea wire:model.blur="skills_description"
                                  class="form-control form-control-sm @error('skills_description') is-invalid @enderror"
                                  rows="4"
                                  placeholder="در صورت نیاز، توضیح کوتاهی درباره استعدادها یا مهارت‌های ویژه ثبت کنید ..."
                                  style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                        @error('skills_description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
