@if($current_step === 1)

    <div class="mb-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                    <div>
                        <h5 class="mb-1 fw-bold">اطلاعات فردی مددجو</h5>
                        <p class="mb-0 small text-muted">ابتدا اطلاعات هویتی را وارد کنید، سپس اطلاعات تکمیلی را ثبت نمایید.</p>
                    </div>
                    <small class="text-muted">فیلدهای <span class="text-danger">*</span> الزامی هستند.</small>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('first_name') is-invalid @enderror" wire:model.blur="first_name" placeholder="مثال: امیرعلی" autocomplete="given-name" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">نام خانوادگی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('last_name') is-invalid @enderror" wire:model.blur="last_name" placeholder="مثال: رضایی" autocomplete="family-name" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">کد ملی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('national_id') is-invalid @enderror" maxlength="10" wire:model.live.debounce.350ms="national_id" inputmode="numeric" placeholder="10 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('national_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">سریال شناسنامه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('shenasnameh_serial') is-invalid @enderror" maxlength="6" wire:model.live="shenasnameh_serial" inputmode="numeric" placeholder="6 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('shenasnameh_serial') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">شماره سری <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('shenasnameh_series_number') is-invalid @enderror" maxlength="2" wire:model.live="shenasnameh_series_number" inputmode="numeric" placeholder="2 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('shenasnameh_series_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2 position-relative"
                         x-data="{
                                        openSeriesLetters: false,
                                        selectedSeriesLetter: @entangle('shenasnameh_series_letter'),
                                        seriesLetterOptions: @js($this->shenasnamehSeriesLetterOptions),
                                        selectSeriesLetter(letter) {
                                            if (!this.seriesLetterOptions.includes(letter)) return;
                                            this.selectedSeriesLetter = letter;
                                            this.openSeriesLetters = false;
                                        },
                                        selectSeriesLetterByKey(event) {
                                            const key = (event.key || '').trim();
                                            if (key.length !== 1) return;
                                            if (!this.seriesLetterOptions.includes(key)) return;
                                            event.preventDefault();
                                            this.selectSeriesLetter(key);
                                        },
                                        clearSeriesLetter() {
                                            this.selectedSeriesLetter = '';
                                            this.openSeriesLetters = false;
                                        }
                                     }"
                         @click.outside="openSeriesLetters = false">
                        <label class="form-label small fw-semibold mb-1">حرف سری <span class="text-danger">*</span></label>
                        <div class="border @error('shenasnameh_series_letter') border-danger @else border-light @enderror p-2"
                             style="border-radius: 12px; background: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">انتخاب از لیست</small>
                                <button type="button"
                                        x-cloak
                                        x-show="selectedSeriesLetter"
                                        @click="clearSeriesLetter()"
                                        class="btn btn-link btn-sm p-0 text-decoration-none">
                                    پاک کردن
                                </button>
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-between"
                                    @click="openSeriesLetters = !openSeriesLetters"
                                    @keydown="selectSeriesLetterByKey($event)"
                                    :aria-expanded="openSeriesLetters.toString()"
                                    aria-haspopup="listbox"
                                    style="border-radius: 10px; min-height: 34px;">
                                <span x-text="selectedSeriesLetter || 'انتخاب حرف سری'"></span>
                                <span class="small" x-text="openSeriesLetters ? '▲' : '▼'"></span>
                            </button>

                            <div x-show="openSeriesLetters"
                                 x-cloak
                                 x-transition
                                 class="position-absolute start-0 end-0 mt-1 mx-2 p-2 border border-light rounded-3 bg-white shadow-sm z-3"
                                 role="listbox"
                                 aria-label="حروف سری شناسنامه"
                                 style="max-height: 220px; overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none;">
                                <div class="row g-1">
                                    @foreach($this->shenasnamehSeriesLetterOptions as $letter)
                                        <div class="col-3">
                                            <button type="button"
                                                    role="option"
                                                    :aria-selected="selectedSeriesLetter === '{{ $letter }}' ? 'true' : 'false'"
                                                    @click="selectSeriesLetter('{{ $letter }}')"
                                                    class="btn btn-sm w-100"
                                                    :class="selectedSeriesLetter === '{{ $letter }}' ? 'btn-primary' : 'btn-outline-secondary'"
                                                    style="border-radius: 10px; min-height: 34px;">
                                                {{ $letter }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('shenasnameh_series_letter') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>


                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">تاریخ تولد <span class="text-danger">*</span></label>
                        <div class="row g-2 dir-ltr">
                            <div class="col-4">
                                <select wire:model.blur="birth_day" class="form-select form-select-sm @error('birth_day') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">روز</option>
                                    @foreach(range(1, 31) as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <select wire:model.blur="birth_month" class="form-select form-select-sm @error('birth_month') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">ماه</option>
                                    @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                    @foreach($months as $key => $month)
                                        <option value="{{ $key }}">{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <select wire:model.blur="birth_year" class="form-select form-select-sm @error('birth_year') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                    <option value="">سال</option>
                                    @foreach(range(1300, 1420) as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('birth_day') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('birth_month') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('birth_year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <hr>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">کد ملی پدر <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('father_national_id') is-invalid @enderror" maxlength="10" wire:model.live.debounce.450ms="father_national_id" inputmode="numeric" placeholder="10 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @if($showFatherSuggestions && strlen(trim((string)$father_national_id)) >= 5 && $this->fatherSuggestions->count())
                            <div class="mt-1 rounded-3 border bg-white shadow-sm" style="border-color: #dbe3ec; max-height: 150px; overflow-y: auto;">
                                @foreach($this->fatherSuggestions as $fatherSuggestion)
                                    <button
                                        type="button"
                                        wire:click="selectFatherFromSuggestions('{{ $fatherSuggestion->father_national_id }}')"
                                        class="w-100 border-0 bg-transparent px-2 py-1 text-start transition hover:bg-cyan-50"
                                        style="border-bottom: 1px solid #f1f5f9;"
                                    >
                                        <div class="small fw-semibold text-slate-800">{{ $fatherSuggestion->father_name }}</div>
                                        <div class="small text-muted">{{ $fatherSuggestion->father_national_id }}</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @error('father_national_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">کد ملی مادر <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('mother_national_id') is-invalid @enderror" maxlength="10" wire:model.live.debounce.350ms="mother_national_id" inputmode="numeric" placeholder="10 رقم" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @if($detected_mother_hint)
                            <span class="mt-1 text-xs px-2" style="color: #1f2937;">{{ $detected_mother_hint }}</span>
                        @endif
                        @error('mother_national_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">نام پدر <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('father_name') is-invalid @enderror" wire:model.blur="father_name" placeholder="مثال: عباس" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('father_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">شماره موبایل مددجو <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('phone_number') is-invalid @enderror" wire:model="phone_number" maxlength="11" inputmode="numeric" placeholder="09123456789" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                        @error('phone_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>



                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-2">نقش در خانواده </label>
                        <select disabled class="form-select form-select-sm @error('role') is-invalid @enderror" wire:model.blur="role" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                            <option value="child">فرزند</option>
                        </select>
                        @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-2">جنسیت <span class="text-danger">*</span></label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="male" wire:model.live="gender" id="gender_male">
                                <label class="form-check-label small" for="gender_male">مرد</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" value="female" wire:model.live="gender" id="gender_female">
                                <label class="form-check-label small" for="gender_female">زن</label>
                            </div>
                        </div>
                        @error('gender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-2">وضعیت سادات <span class="text-danger">*</span></label>
                        <div class="border rounded-3 p-2 d-flex gap-3 flex-wrap" style="background: #f8fafc; border-color: #dbe3ec !important;">
                            <div class="form-check mb-0">
                                <input type="radio" class="form-check-input" value="general" wire:model.live="sadaat_status" id="sadaat_status_general">
                                <label class="form-check-label small" for="sadaat_status_general">عام</label>
                            </div>
                            <div class="form-check mb-0">
                                <input type="radio" class="form-check-input" value="sadaat" wire:model.live="sadaat_status" id="sadaat_status_sadaat">
                                <label class="form-check-label small" for="sadaat_status_sadaat">سادات</label>
                            </div>
                        </div>
                        @error('sadaat_status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    @if($sadaat_status === 'sadaat')
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">نسب سادات </label>
                            <select wire:model.blur="sadaat_relation_id" class="form-select form-select-sm @error('sadaat_relation_id') is-invalid @enderror" style="border-radius: 12px; background: #f8fafc; border-color: #dbe3ec; min-height: 42px;">
                                <option value="">— انتخاب کنید —</option>
                                @foreach($sadaatRelations as $rel)
                                    <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                                @endforeach
                            </select>
                            @error('sadaat_relation_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>
            </div>
        </div>
@endif
