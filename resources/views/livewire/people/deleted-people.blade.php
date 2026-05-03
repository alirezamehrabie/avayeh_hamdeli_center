<div>
    <div class="card shadow-sm">
        <div class="card-header bg-pink-800 text-white">
            <h3 class="mb-0">بلاک لیست مددجویان</h3>
            <p class="mb-0 text-sm">مشاهده مددجویان حذف‌شده و بازیابی نظارت با کد مددجوی قبلی</p>
        </div>
        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>کد مددجو</th>
                            <th>نام و نام خانوادگی</th>
                            <th>کد ملی</th>
                            <th>تاریخ تولد</th>
                            <th>جنسیت</th>
                            <th>تاریخ حذف</th>
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
                                <td>{{ $person->deleted_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <button wire:click="restoreSupervision({{ $person->id }})" class="btn btn-sm btn-success">
                                        <i class="fa fa-undo"></i> Restore Supervision
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">هیچ مددجوی حذف‌شده‌ای وجود ندارد.</td>
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
