<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mb-0">لیست مددجویان</h3>
            <p class="mb-0 text-sm">جستجو و مدیریت افراد ثبت شده</p>
        </div>
        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="mb-4">
                <label for="beneficiary-search" class="form-label fw-semibold">جستجوی سریع</label>
                <div class="row g-2">
                    <div class="col-12 col-md-4 col-lg-3">
                        <select
                            id="beneficiary-search-field"
                            class="form-select"
                            wire:model.live="searchField"
                            aria-label="معیار جستجو"
                        >
                            <option value="all">همه فیلدها</option>
                            <option value="person_code">کد مددجو</option>
                            <option value="full_name">نام و نام خانوادگی</option>
                            <option value="first_name">نام</option>
                            <option value="last_name">نام خانوادگی</option>
                            <option value="national_id">کد ملی</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-8 col-lg-9">
                        <input
                            id="beneficiary-search"
                            type="text"
                            class="form-control"
                            wire:model.live.debounce.300ms="search"
                            placeholder="عبارت جستجو را وارد کنید..."
                        >
                    </div>
                </div>
                @error('search') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>کد مددجو</th>
                            <th>نام و نام خانوادگی</th>
                            <th>کد ملی</th>
                            <th>تاریخ تولد</th>
                            <th>جنسیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->people as $person)
                            <tr>
                                <td>{{ $person->person_code }}</td>
                                <td>{{ $person->full_name }}</td>
                                <td>{{ $person->national_id }}</td>
                                <td>{{ $person->birth_date ?? 'نامشخص' }}</td>
                                <td>
                                    @if($person->gender == 'male')
                                        <span class="badge bg-primary">مرد</span>
                                    @elseif($person->gender == 'female')
                                        <span class="badge text-white" style="background-color: #e83e8c;">زن</span>
                                    @else
                                        <span class="badge bg-secondary">نامشخص</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button wire:click="editPerson({{ $person->id }})" class="btn btn-sm btn-primary">
                                            <i class="fa fa-edit"></i> ویرایش
                                        </button>
                                        <button wire:click="quickEditPerson({{ $person->id }})" class="btn btn-sm btn-warning">
                                            <i class="fa fa-bolt"></i> ویرایش سریع
                                        </button>
                                        <button wire:click="deletePerson({{ $person->id }})" wire:confirm="آیا از انتقال این مددجو به بلاک لیست مطمئن هستید؟" class="btn btn-sm btn-outline-danger">
                                            <i class="fa fa-ban"></i> حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">هیچ مددجویی ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->people->links() }}
            </div>
        </div>
    </div>
</div>
