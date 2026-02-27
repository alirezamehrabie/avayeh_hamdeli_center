<div>
    <div class="card shadow-sm pb-3">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-2 text-lg">ثبت‌نام سریع فرد جدید</h3>
            <p class="mb-2 text-sm">فیلدهای ضروری را برای ثبت سریع پر کنید. اطلاعات اضافی را می‌توانید بعداً ویرایش کنید.</p>
        </div>
        <div class="card-body pt-4">
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Row 1: First name - Last name - National code - Father's name (all in one line) -->
                    <div class="col-md-3">
                        <label class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model.blur="first_name" placeholder="">
                        @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model.blur="last_name" placeholder="">
                        @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" maxlength="10" wire:model.live="national_id" placeholder="">
                        @error('national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">نام پدر</label>
                        <input type="text" class="form-control" wire:model.blur="father_name" placeholder="">
                        @error('father_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <!-- Row 2: Optional father's nationality - Optional mother's nationality - Gender - Date of birth -->
                    <div class="col-md-3 pt-3">
                        <label class="form-label">جنسیت</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" value="male" wire:model.blur="gender" id="gender_male">
                                <label class="form-check-label" for="gender_male">مرد</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" value="female" wire:model.blur="gender" id="gender_female">
                                <label class="form-check-label" for="gender_female">زن</label>
                            </div>
                        </div>
                        @error('gender') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3 pt-3">
                        <label class="form-label">تاریخ تولد</label>
                        <div class="row g-2 dir-ltr">
                            <div class="col-4">
                                <select wire:model.blur="birth_day" class="form-select">
                                    <option value="">روز</option>
                                    @foreach(range(1, 31) as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                </select>
                                @error('birth_day') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-4">
                                <select wire:model.blur="birth_month" class="form-select">
                                    <option value="">ماه</option>
                                    @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                    @foreach($months as $key => $month)
                                        <option value="{{ $key }}">{{ $month }}</option>
                                    @endforeach
                                </select>
                                @error('birth_month') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-4">
                                <select wire:model.blur="birth_year" class="form-select">
                                    <option value="">سال</option>
                                    @foreach(range(1300, 1420) as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                                @error('birth_year') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 pt-3">
                        <label class="form-label">کد ملی پدر (اختیاری)</label>
                        <input type="text" class="form-control" maxlength="10" wire:model.live="father_national_id" placeholder="">
                        @error('father_national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3 pt-3">
                        <label class="form-label">کد ملی مادر (اختیاری)</label>
                        <input type="text" class="form-control" maxlength="10" wire:model.live="mother_national_id" placeholder="">
                        @error('mother_national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                </div>

                <div class="mt-4 pt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> ثبت اطلاعات سریع
                    </button>
                    <a href="{{ url()->previous() ?? '/' }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> لغو
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
