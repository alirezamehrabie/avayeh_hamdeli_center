<div>
    <div>
        <div class="card shadow-sm">
            {{-- هدر کارت با رنگ نارنجی برای متمایز کردن از صفحه ایجاد --}}
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0">ویرایش اطلاعات مددکار</h3>
            </div>

            <div class="card-body">
                {{-- شروع فرم --}}
                <form wire:submit.prevent="update">

                    {{-- پیام‌های موفقیت و خطا مطابق الگوی create-person --}}
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <p><strong>لطفاً خطاهای زیر را برطرف کنید:</strong></p>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- بخش اول: اطلاعات شناسایی --}}
                    <div class="mb-5">
                        <h4 class="border-bottom pb-2 mb-3 font-bold text-primary">اطلاعات فردی و شناسایی</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نام <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.blur="first_name">
                                @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.blur="last_name">
                                @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            
                            <div class="col-md-4">
                                <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-center" maxlength="10"
                                           wire:model.blur="national_id"
                                        {{ $canEditNationalId ? '' : 'readonly' }}>
                                    @if(!$canEditNationalId)
                                        <button type="button" class="btn btn-outline-warning" wire:click="$set('canEditNationalId', true)">
                                            ویرایش
                                        </button>
                                    @endif
                                </div>
                                @error('national_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">شماره شناسنامه</label>
                                <input type="text" class="form-control text-center" wire:model.blur="id_number">
                            </div>

                            {{-- تاریخ تولد ۳ بخشی مطابق الگوی مددجو --}}
                            <div class="col-md-4">
                                <label class="form-label">تاریخ تولد</label>
                                <div class="row g-2 dir-ltr">
                                    <div class="col-4">
                                        <select wire:model.blur="birth_day" class="form-select text-center">
                                            <option value="">روز</option>
                                            @foreach(range(1, 31) as $day) <option value="{{ $day }}">{{ $day }}</option> @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select wire:model.blur="birth_month" class="form-select text-center">
                                            <option value="">ماه</option>
                                            @php $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند']; @endphp
                                            @foreach($months as $key => $month) <option value="{{ $key }}">{{ $month }}</option> @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select wire:model.blur="birth_year" class="form-select text-center">
                                            <option value="">سال</option>
                                            @foreach(range(1320, 1410) as $year) <option value="{{ $year }}">{{ $year }}</option> @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">شماره موبایل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-center font-mono" wire:model.blur="mobile" maxlength="11" placeholder="09120000000">
                                @error('mobile') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <!-- فیلد تحصیلات مددکار -->
                            <div class="col-md-4 mb-3">
                                <label for="academic_level_id" class="form-label">تحصیلات</label>
                                <select wire:model="academic_level_id" id="academic_level_id" class="form-select @error('academic_level_id') is-invalid @enderror">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($academicLevels as $level)
                                        <option value="{{ $level->id }}">{{ $level->title }}</option>
                                    @endforeach
                                </select>
                                @error('academic_level_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="occupation_id" class="form-label">شغل</label>
                                <select wire:model="occupation_id" id="occupation_id" class="form-select @error('occupation_id') is-invalid @enderror">
                                    <option value="">— انتخاب شغل —</option>
                                    @foreach($allOccupations as $occupation)
                                        <option value="{{ $occupation->id }}">{{ $occupation->name }}</option>
                                    @endforeach
                                </select>
                                @error('occupation_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">تعداد اعضای خانواده</label>
                                <input type="number" class="form-control text-center" wire:model.blur="family_members_count">
                            </div>

                            {{-- بخش آپلود تصویر مشابه استایل مددجو --}}
                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-bold">تصویر مددکار</label>
                                <div class="card p-3 bg-light">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 border-start">
                                            <input type="file" wire:model="photo" class="form-control">
                                            <div wire:loading wire:target="photo" class="text-primary small mt-1">در حال آپلود...</div>
                                            @error('photo') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-6 text-center">
                                            @if ($photo)
                                                <img src="{{ $photo->temporaryUrl() }}" class="img-thumbnail" style="max-height: 120px;">
                                            @elseif ($existingPhoto)
                                                <img src="{{ asset('storage/' . $existingPhoto) }}" class="img-thumbnail" style="max-height: 120px;">
                                            @else
                                                <div class="text-muted small border rounded p-3 bg-white">تصویر انتخاب نشده است</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5 border-2">

                    {{-- بخش دوم: اطلاعات حرفه‌ای --}}
                    <div class="mb-5">
                        <h4 class="border-bottom pb-2 mb-3 font-bold text-success">اطلاعات حرفه‌ای و منطقه خدمت</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">تاریخ شروع همکاری</label>
                                <div class="row g-2 dir-ltr">
                                    <div class="col-4">
                                        <select wire:model.blur="start_day" class="form-select text-center">
                                            <option value="">روز</option>
                                            @foreach(range(1, 31) as $day) <option value="{{ $day }}">{{ $day }}</option> @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select wire:model.blur="start_month" class="form-select text-center">
                                            <option value="">ماه</option>
                                            @foreach($months as $key => $month) <option value="{{ $key }}">{{ $month }}</option> @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <select wire:model.blur="start_year" class="form-select text-center">
                                            <option value="">سال</option>
                                            @foreach(range(1380, 1410) as $year) <option value="{{ $year }}">{{ $year }}</option> @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label for="district_id" class="form-label">منطقه تحت پوشش</label>
                                <select wire:model="district_id" id="district_id" class="form-select @error('district_id') is-invalid @enderror">
                                    <option value="">— انتخاب منطقه —</option>
                                    @foreach($allDistricts as $district)
                                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                                    @endforeach
                                </select>
                                @error('district_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- آمارهای عددی در باکس‌های کوچک --}}
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white text-center shadow-sm">
                                    <label class="small text-muted d-block mb-1">تعداد افراد تحت پوشش</label>
                                    <input type="number" readonly class="form-control text-center fw-bold text-primary bg-light" value="{{ $covered_people_count }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white text-center shadow-sm">
                                    <label class="small text-muted d-block mb-1">تعداد خانوار تحت پوشش</label>
                                    <input type="number" readonly class="form-control text-center fw-bold text-primary bg-light" value="{{ $covered_households_count }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white text-center shadow-sm">
                                    <label class="small text-muted d-block mb-1">تعداد کودکان تحت پوشش</label>
                                    <input type="number" readonly class="form-control text-center fw-bold text-primary bg-light" value="{{ $covered_children_count }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5 border-2">

                    {{-- بخش سوم: همکار جایگزین --}}
                    <div class="mb-5">
                        <h4 class="border-bottom pb-2 mb-3 font-bold text-warning">اطلاعات همکار جایگزین (علی‌البدل)</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نام همکار جایگزین</label>
                                <input type="text" class="form-control" wire:model.blur="substitute_first_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نام خانوادگی همکار جایگزین</label>
                                <input type="text" class="form-control" wire:model.blur="substitute_last_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تلفن همراه جایگزین</label>
                                <input type="text" class="form-control text-center font-mono" wire:model.blur="substitute_mobile" maxlength="11">
                            </div>
                        </div>
                    </div>

                    {{-- دکمه‌های عملیاتی مطابق طرح مددجو --}}
                    <div class="d-flex justify-content-end gap-2 mt-4 mb-5">
                        <a href="{{ route('social-workers.index') }}" class="btn btn-outline-secondary px-4">بازگشت</a>
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-warning px-5 fw-bold">
                            <span wire:loading.remove wire:target="update">بروزرسانی اطلاعات</span>
                            <span wire:loading wire:target="update">
                            <span class="spinner-border spinner-border-sm" role="status"></span> در حال بروزرسانی...
                        </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
