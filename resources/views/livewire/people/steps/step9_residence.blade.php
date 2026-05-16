
{{-- Step 9: Housing Status and Contact Information --}}
@if($current_step === 9)
    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <h4 class="mb-0 fw-bold">وضعیت سکونت و اطلاعات تماس</h4>
                    <span class="badge text-dark" style="background: #eef2ff; border: 1px solid #dbe3ec;">مرحله 9</span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-1">وضعیت سکونت </label>
                        <select class="form-select form-select-sm @error('residence_status_id') is-invalid @enderror" wire:model.blur="residence_status_id" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="">— انتخاب کنید —</option>
                            @foreach($residenceStatusTypes as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        @error('residence_status_id') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-4 col-md-6" x-data="{ open: false }">
                        <label class="form-label small fw-semibold mb-1">منطقه </label>
                        <div class="position-relative">
                            <button type="button" @click="open = !open" class="btn w-100 d-flex align-items-center justify-content-between text-end @error('district_id') border-danger @enderror" style="border-radius: 12px; background: #f8fafc; border: 1px solid #dbe3ec; min-height: 42px;">
                                    <span class="text-truncate">
                                        {{ $districts->firstWhere('id', $district_id)?->name ?? '— انتخاب منطقه —' }}
                                    </span>
                                <i class="bi bi-funnel"></i>
                            </button>

                            <div x-show="open" @click.outside="open=false" x-transition class="position-absolute start-0 w-100 mt-2 p-2 bg-white border shadow-sm" style="z-index: 25; border-radius: 12px; border-color: #dbe3ec !important;">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                                    <input type="text" id="district-search-grid" oninput="filterDistrictGrid()" class="form-control border-0" placeholder="جستجو در مناطق...">
                                </div>
                                <div id="district-grid-list" class="row g-2" style="max-height: 260px; overflow-y: auto;">
                                    <div class="col-12 district-item" data-name="">
                                        <button type="button" class="btn btn-sm w-100 text-end" style="background: #f8fafc;" wire:click="$set('district_id', '')" @click="open=false">
                                            — انتخاب کنید —
                                        </button>
                                    </div>
                                    @foreach($districts as $district)
                                        <div class="col-md-4 col-6 district-item" data-name="{{ mb_strtolower($district->name) }}">
                                            <button
                                                type="button"
                                                wire:click="$set('district_id', '{{ $district->id }}')"
                                                @click="open=false"
                                                class="btn btn-sm w-100 text-end d-flex justify-content-between align-items-center"
                                                style="background: #ffffff; border: 1px solid #eef2f7; border-radius: 8px;">
                                                <span class="text-truncate">{{ $district->name }}</span>
                                                @if((string)$district_id === (string)$district->id)
                                                    <i class="bi bi-check2 text-success ms-1"></i>
                                                @endif
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('district_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-4 col-md-12">
                        <label class="form-label small fw-semibold mb-1">بومی شهر هستید؟ </label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important; min-height: 42px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="1" wire:model.live="is_local_to_city" id="local_yes">
                                <label class="form-check-label" for="local_yes">بله</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="0" wire:model.live="is_local_to_city" id="local_no">
                                <label class="form-check-label" for="local_no">خیر</label>
                            </div>
                        </div>
                        @error('is_local_to_city') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    @if($residence_status_id == 2)
                        {{-- 2 برای "اجاره‌ای" --}}
                        <div class="col-lg-3 col-md-6" x-data="{ amount: @js($deposit_amount ?? '') }">
                            <label class="form-label small fw-semibold mb-1">مبلغ ودیعه (ریال)</label>
                            <input
                                type="number"
                                class="form-control form-control-sm @error('deposit_amount') is-invalid @enderror"
                                wire:model.blur="deposit_amount"
                                x-model="amount"
                                min="0"
                                placeholder="100,000,000,000"
                                style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                            >
                            <x-money-preview/>
                            @error('deposit_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>


                        <div class="col-lg-3 col-md-6" x-data="{ amount: @js($monthly_rent ?? '') }">
                            <label class="form-label small fw-semibold mb-1">اجاره ماهیانه (ریال)</label>
                            <input
                                type="number"
                                class="form-control form-control-sm @error('monthly_rent') is-invalid @enderror"
                                wire:model.blur="monthly_rent"
                                x-model="amount"
                                min="0"
                                placeholder="50,000,000"
                                style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;"
                            >
                            <x-money-preview/>
                            @error('monthly_rent') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    @endif


                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-semibold mb-1">سال‌های سکونت در منطقه</label>
                        <input type="number" class="form-control form-control-sm @error('residence_duration_years') is-invalid @enderror" wire:model.blur="residence_duration_years"
                               min="0" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('residence_duration_years') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">آدرس کامل محل سکونت </label>
                        <textarea class="form-control form-control-sm @error('address') is-invalid @enderror" wire:model.blur="address" rows="3" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec;"></textarea>
                        @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>


                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره تلفن ثابت</label>
                        <input type="text" class="form-control form-control-sm @error('landline_phone') is-invalid @enderror" wire:model.blur="landline_phone" maxlength="11" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('landline_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره تلفن فرد معتمد</label>
                        <input type="text" class="form-control form-control-sm @error('trusted_person_phone') is-invalid @enderror" wire:model.blur="trusted_person_phone" maxlength="11" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('trusted_person_phone') <span
                            class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <!-- بخش نوع پیام‌رسان -->
                    <div class="col-lg-4 col-md-6">
                        <label for="messenger_type" class="form-label small fw-semibold mb-1">نوع پیام‌رسان (انتخابی یا تایپی)</label>

                        <input wire:model="messenger_type"
                               type="text"
                               class="form-control form-control-sm @error('messenger_type') is-invalid @enderror"
                               id="messenger_type"
                               list="messenger_list"
                               placeholder="انتخاب کنید یا بنویسید..." style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">

                        <!-- لیست پیشنهادی که در زمان کلیک یا تایپ ظاهر می‌شود -->
                        <datalist id="messenger_list">
                            <option value="ایتا">
                            <option value="بله">
                            <option value="پیامک">
                            <option value="روبیکا">
                            <option value="سروش">
                            <option value="شاد">
                            <option value="تلگرام">
                            <option value="واتس‌اپ">
                        </datalist>

                        @error('messenger_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-semibold mb-1">شماره یا آیدی پیام‌رسان</label>
                        <input type="text" class="form-control form-control-sm @error('messenger_number') is-invalid @enderror" wire:model.blur="messenger_number" maxlength="11" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('messenger_number') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
