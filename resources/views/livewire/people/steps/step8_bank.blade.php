
{{-- Step 8: Banking Information --}}
@if($current_step === 8)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <h4 class="mb-0 fw-bold">اطلاعات بانکی</h4>
                    <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 8</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">حساب شخصی دارد؟ </label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" value="1" wire:model.live="has_own_account" id="has_own_account_yes">
                                <label class="form-check-label" for="has_own_account_yes">بله</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" value="0" wire:model.live="has_own_account" id="has_own_account_no">
                                <label class="form-check-label" for="has_own_account_no">خیر</label>
                            </div>
                        </div>
                        @error('has_own_account') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">نسبت دارنده حساب</label>
                        {{-- این فیلد اکنون فقط برای نمایش است و مقدار آن توسط منطق Livewire تعیین می‌شود --}}
                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $accountRelations->find($account_owner_relation_id)?->name ?? 'انتخاب نشده' }}"
                               readonly
                               disabled
                               placeholder="به طور خودکار تعیین می‌شود"
                               style="border-radius: 12px; background: #eef2f7; border-color: #dbe3ec; min-height: 42px;"
                        >
                        @error('account_owner_relation_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-4 col-md-12">
                        <label class="form-label small fw-semibold mb-1">نام بانک </label>
                        <select class="form-select form-select-sm @error('bank_id') is-invalid @enderror" wire:model.blur="bank_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="">— انتخاب کنید —</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        @error('bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره کارت</label>
                        <input type="text" class="form-control form-control-sm @error('card_number') is-invalid @enderror" wire:model.live.debounce="card_number"
                               maxlength="16" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('card_number') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره شبا</label>
                        <input type="text" class="form-control form-control-sm ltr-input @error('sheba_number') is-invalid @enderror" wire:model.live.debounce.200ms="sheba_number"
                               placeholder="24 رقم بعد از IR" maxlength="26" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('sheba_number') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره کارت یارانه</label>
                        <input type="text" class="form-control form-control-sm @error('subsidy_card_number') is-invalid @enderror" wire:model="subsidy_card_number"
                               maxlength="16" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('subsidy_card_number') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره شبا یارانه</label>
                        <input type="text" class="form-control form-control-sm ltr-input @error('subsidy_sheba_number') is-invalid @enderror" wire:model.blur="subsidy_sheba_number"
                               placeholder="24 رقم بعد از IR" maxlength="26" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('subsidy_sheba_number') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
