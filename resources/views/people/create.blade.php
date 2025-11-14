@extends('layouts.app')

@section('title', 'ثبت مددجوی جدید')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">فرم ثبت‌نام مددجوی جدید</h3>
        </div>
        <div class="card-body">

            {{-- Display Success/Error Messages --}}
            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <p><strong>لطفاً خطاهای زیر را برطرف کنید:</strong></p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('people.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Section 1: Person Info --}}
                @include('people.partials.person_info')

                {{-- Section 2: Family Status --}}
                @include('people.partials.family_status')

                {{-- Section 3: Guardian Info --}}
                @include('people.partials.guardian_info')

                {{-- Section 4: Residence & Contact --}}
                @include('people.partials.residence_contact')

                {{-- Section 5: Financial, Education & Support --}}
                @include('people.partials.financial_education_support')


                {{-- Section 6: Needs Level --}}
                @include('people.partials.education_info')

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">ثبت نهایی اطلاعات مددجو</button>
                </div>
            </form>
        </div>
    </div>
@endsection


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const guardianRoleSelect = document.getElementById('guardian_role');
        const relationBox = document.getElementById('guardian_relation_box');
        const relationSelect = document.getElementById('guardian_relation');

        function toggleRelationVisibility() {
            const value = guardianRoleSelect.value;
            if (value === 'سایر') {
                relationBox.style.display = 'block';
            } else {
                relationBox.style.display = 'none';
                relationSelect.value = ''; // پاک کردن مقدار اگر پنهان شد
            }
        }
        // فراخوانی اولیه هنگام بارگذاری فرم
        toggleRelationVisibility();

        // تغییر پویا هنگام تغییر گزینه‌ها
        guardianRoleSelect.addEventListener('change', toggleRelationVisibility);
    });
</script>

