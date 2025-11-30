@extends('layouts.app')

@section('title', 'ثبت مددجوی جدید')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">فرم ثبت‌نام مددجوی جدید</h3>
        </div>
        <div class="card-body">


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


<!-- اسکریپت برای فعال/غیرفعال کردن لیست بیمه -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const insuranceYes = document.getElementById('insurance_yes');
        const insuranceNo = document.getElementById('insurance_no');
        const insuranceSelect = document.getElementById('insurance_type_id');

        function toggleInsuranceSelect() {
            if (insuranceYes.checked) {
                insuranceSelect.disabled = false;
            } else {
                insuranceSelect.disabled = true;
                insuranceSelect.value = ''; // پاک کردن مقدار اگر غیرفعال شد
            }
        }

        // شنونده رویداد
        insuranceYes.addEventListener('change', toggleInsuranceSelect);
        insuranceNo.addEventListener('change', toggleInsuranceSelect);

        // اجرای اولیه (برای زمان ادیت یا برگشت از ولیدیشن)
        toggleInsuranceSelect();
    });
</script>


<!-- Script to toggle Vehicle Select -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const yes = document.getElementById('vehicle_yes');
        const no = document.getElementById('vehicle_no');
        const wrapper = document.getElementById('vehicle_type_wrapper');
        const select = document.getElementById('vehicle_type_id');

        function update() {
            if (yes.checked) {
                wrapper.style.display = 'block';
                select.required = true;
            } else {
                wrapper.style.display = 'none';
                select.required = false;
                select.value = '';
            }
        }

        update();
        yes.addEventListener('change', update);
        no.addEventListener('change', update);
    });
</script>



{{-- Script for Logic Control --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const accYes = document.getElementById('account_yes');
        const accNo = document.getElementById('account_no');
        const relationSelect = document.getElementById('account_owner_relation_id');
        const otherBox = document.getElementById('other_relation_box');
        const otherInput = document.getElementById('other_account_owner_relation');

        // پیدا کردن آپشن‌های کلیدی
        let selfOptionElement = null;
        for (let option of relationSelect.options) {
            if (option.dataset.isSelf === 'true') selfOptionElement = option;
        }

        // تابع اصلی مدیریت وضعیت
        function updateState() {
            // 1. منطق حساب شخصی (بله/خیر)
            if (accYes.checked) {
                // حالت بله: انتخاب خودکار "شخص مددجو" و قفل کردن
                if (selfOptionElement) {
                    selfOptionElement.disabled = false;
                    relationSelect.value = selfOptionElement.value;
                }
                relationSelect.style.pointerEvents = 'none';
                relationSelect.style.backgroundColor = '#e9ecef';
            } else {
                // حالت خیر: آزاد کردن لیست
                relationSelect.style.pointerEvents = 'auto';
                relationSelect.style.backgroundColor = '';

                if (selfOptionElement) {
                    selfOptionElement.disabled = true; // غیرفعال کردن گزینه "مددجو"
                    // اگر روی مددجو مانده بود، پاکش کن
                    if (relationSelect.value === selfOptionElement.value) {
                        relationSelect.value = '';
                    }
                }
            }

            // 2. منطق نمایش فیلد "سایر"
            // بررسی می‌کنیم گزینه انتخاب شده فعلی آیا "سایر" است؟
            const selectedOption = relationSelect.options[relationSelect.selectedIndex];
            const isOther = selectedOption && selectedOption.dataset.isOther === 'true';

            if (isOther) {
                otherBox.style.display = 'block';
                otherInput.required = true; // در فرانت هم اجباری شود
            } else {
                otherBox.style.display = 'none';
                otherInput.required = false;
                otherInput.value = ''; // پاک کردن مقدار اگر مخفی شد
            }
        }

        // رویدادها
        accYes.addEventListener('change', updateState);
        accNo.addEventListener('change', updateState);
        relationSelect.addEventListener('change', updateState); // تغییر در لیست هم باید چک شود

        // اجرای اولیه
        updateState();
    });
</script>



{{--Card Number--}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // المان‌های کارت اصلی
        const vCard = document.getElementById('visual_card_number');
        const hCard = document.getElementById('card_number');

        // المان‌های کارت یارانه
        const vSubsidy = document.getElementById('visual_subsidy_card_number');
        const hSubsidy = document.getElementById('subsidy_card_number');

        let subsidyManuallyEdited = false;

        // اگر مقداری از قبل وجود دارد (مثلاً بعد از خطای فرم)، آن را در لود اولیه فرمت کن
        if (hCard.value) vCard.value = formatCardNumber(hCard.value);
        if (hSubsidy.value) {
            vSubsidy.value = formatCardNumber(hSubsidy.value);
            subsidyManuallyEdited = true; // چون مقدار دارد فرض میکنیم دستی پر شده
        }

        // --- تابع اصلی فرمت‌دهی ---
        function formatCardNumber(value) {
            // حذف تمام کاراکترهای غیر عددی
            const cleanVal = value.replace(/\D/g, '');

            // افزودن خط تیره هر 4 رقم
            // این regex گروه‌های 4 تایی اعداد را پیدا کرده و بین آنها خط تیره می‌گذارد
            const formatted = cleanVal.match(/.{1,4}/g)?.join('-') || '';

            return formatted.substring(0, 19); // اطمینان از رعایت طول
        }

        // --- تابع هندلر ورودی ---
        function handleInput(visualInput, hiddenInput, isSubsidy = false) {
            // 1. حذف کاراکترهای غیر مجاز و خط تیره‌ها برای ذخیره در هیدن
            let rawValue = visualInput.value.replace(/\D/g, '');

            // محدودیت 16 رقم برای مقدار خام
            if (rawValue.length > 16) rawValue = rawValue.substring(0, 16);

            // آپدیت فیلد مخفی
            hiddenInput.value = rawValue;

            // 2. فرمت کردن مقدار برای نمایش به کاربر
            const formattedValue = formatCardNumber(rawValue);

            // فقط اگر مقدار تغییر کرده آپدیت کن (برای جلوگیری از پرش مکان نما در وسط ویرایش)
            if (visualInput.value !== formattedValue) {
                visualInput.value = formattedValue;
            }

            // 3. منطق کپی شدن خودکار (Sync)
            if (!isSubsidy && !subsidyManuallyEdited) {
                hSubsidy.value = rawValue;          // کپی مقدار خام
                vSubsidy.value = formattedValue;    // کپی مقدار فرمت شده
            }

            // اگر کاربر دارد در فیلد یارانه تایپ می‌کند، فلگ ادیت دستی را روشن کن
            if (isSubsidy) {
                subsidyManuallyEdited = (rawValue.length > 0);
            }
        }

        // --- اتصال رویدادها ---
        vCard.addEventListener('input', function() {
            handleInput(vCard, hCard, false);
        });

        vSubsidy.addEventListener('input', function() {
            handleInput(vSubsidy, hSubsidy, true);
        });
    });
</script>

