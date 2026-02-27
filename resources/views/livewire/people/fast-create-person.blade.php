<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mb-0">ثبت‌نام سریع فرد جدید</h3>
            <p class="mb-0 text-sm">فقط فیلدهای ضروری را پر کنید (نام، نام خانوادگی، کد ملی، تاریخ تولد)</p>
        </div>
        <div class="card-body">
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
                    <div class="col-md-4">
                        <label class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model.blur="first_name" placeholder="مثال: علی">
                        @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model.blur="last_name" placeholder="مثال: محمدی">
                        @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" maxlength="10" wire:model.live="national_id" placeholder="مثال: 0012345678">
                        @error('national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">جنسیت <span class="text-danger">*</span></label>
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

                    <div class="col-md-12">
                        <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
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
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
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
