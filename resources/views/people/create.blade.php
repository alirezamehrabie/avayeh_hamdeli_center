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
    document.getElementById('has_parent_disability').addEventListener('change', function () {
        document.getElementById('disability_description_box').style.display = this.checked ? 'block' : 'none';
    });
</script>


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


<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. گرفتن المان‌ها با استفاده از ID
        const relationSelect = document.getElementById('guardian_relation_type_id'); // در فایل family_status
        const alertBox = document.getElementById('guardian-dynamic-alert');          // در فایل guardian_info
        const roleTextSpan = document.getElementById('guardian-role-text');          // متن داخل alert

        // تابع برای بروزرسانی متن و نمایش باکس
        function updateGuardianMessage() {
            // اگر المان‌ها در صفحه نبودند (برای جلوگیری از ارور احتمالی) اجرا نشود
            if (!relationSelect || !alertBox || !roleTextSpan) return;

            // گرفتن گزینه انتخاب شده
            const selectedOption = relationSelect.options[relationSelect.selectedIndex];
            const selectedText = selectedOption.text.trim();
            const selectedValue = selectedOption.value;

            // بررسی اینکه آیا گزینه‌ای انتخاب شده است یا خیر (خالی نباشد و مقدار پیشفرض نباشد)
            // معمولا مقدار پیشفرض value="" یا disabled selected است
            if (selectedValue && selectedValue !== "") {
                // آپدیت کردن متن
                roleTextSpan.textContent = `(${selectedText})`;
                // نمایش باکس (حذف کلاس مخفی کننده بوت استرپ)
                alertBox.classList.remove('d-none');
                alertBox.classList.add('d-flex'); // برای تراز شدن آیکون و متن
            } else {
                // اگر چیزی انتخاب نشده بود، باکس مخفی شود
                alertBox.classList.add('d-none');
                alertBox.classList.remove('d-flex');
                roleTextSpan.textContent = '';
            }
        }

        // 2. گوش دادن به رویداد تغییر (Change Event)
        if (relationSelect) {
            relationSelect.addEventListener('change', updateGuardianMessage);

            // 3. اجرای تابع هنگام لود شدن صفحه
            // (برای حالتی که کاربر فرم را ارسال کرده و با خطا برگشته و مقادیر old() پر شده‌اند)
            updateGuardianMessage();
        }
    });
</script>


