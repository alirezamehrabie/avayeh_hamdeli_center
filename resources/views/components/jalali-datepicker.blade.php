@props([
    'name' => 'birth_date',
    'label' => 'تاریخ تولد',
    'required' => false,
    'dayName' => 'birth_day',
    'monthName' => 'birth_month',
    'yearName' => 'birth_year',
    'fullDateName' => 'birth_date_full',
    'dayValue' => null,
    'monthValue' => null,
    'yearValue' => null,
])

@php
    $uniqueId = $name . '_' . Str::random(8);

    // دریافت مقادیر از old یا پارامترهای ورودی
    $day = old($dayName, $dayValue);
    $month = old($monthName, $monthValue);
    $year = old($yearName, $yearValue);

    // ساخت تاریخ کامل فقط برای نمایش در input
    $displayValue = '';
    if ($year && $month && $day) {
        $displayValue = sprintf('%04d/%02d/%02d', (int)$year, (int)$month, (int)$day);
    }

    // تبدیل به آرایه برای استفاده در JS
    $initialDate = ($year && $month && $day) ? [
        'year' => (int)$year,
        'month' => (int)$month,
        'day' => (int)$day
    ] : null;
@endphp

<div class="mb-3 jalali-datepicker-wrapper" id="wrapper_{{ $uniqueId }}">
    <label for="{{ $uniqueId }}_display" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="input-group" dir="ltr">
        {{-- فیلد نمایشی تقویم --}}
        <input
            type="text"
            id="{{ $uniqueId }}_display"
            class="form-control jalali-dp-input text-center @error($dayName) is-invalid @enderror @error($monthName) is-invalid @enderror @error($yearName) is-invalid @enderror"
            placeholder="انتخاب تاریخ از تقویم"
            value="{{ $displayValue }}"
            autocomplete="off"
            readonly
            data-jdp-id="{{ $uniqueId }}"
        >
        <span class="input-group-text bg-success text-white cursor-pointer"
              onclick="document.getElementById('{{ $uniqueId }}_display').focus()">
            <i class="bi bi-calendar3"></i>
        </span>

        {{-- دکمه پاک کردن --}}
        <button type="button"
                class="btn btn-outline-danger"
                title="پاک کردن"
                onclick="clearJalaliDate_{{ $uniqueId }}()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    {{-- فیلدهای مخفی برای ذخیره جداگانه --}}
    <input type="hidden" name="{{ $dayName }}" id="{{ $uniqueId }}_day" value="{{ $day }}">
    <input type="hidden" name="{{ $monthName }}" id="{{ $uniqueId }}_month" value="{{ $month }}">
    <input type="hidden" name="{{ $yearName }}" id="{{ $uniqueId }}_year" value="{{ $year }}">
    <input type="hidden" name="{{ $fullDateName }}" id="{{ $uniqueId }}_full" value="{{ $displayValue }}">

    {{-- نمایش خطاها --}}
    @error($dayName)
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error($monthName)
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error($yearName)
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error($fullDateName)
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    {{-- نمایش مقادیر انتخاب شده --}}
    <div class="mt-2 small text-muted date-parts-display"
         id="{{ $uniqueId }}_parts"
         style="{{ $displayValue ? '' : 'display: none;' }}">
        <span class="badge bg-light text-dark border me-1">
            <i class="bi bi-calendar-event me-1"></i>
            سال: <strong class="text-success" id="{{ $uniqueId }}_year_display">{{ $year ?: '-' }}</strong>
        </span>
        <span class="badge bg-light text-dark border me-1">
            <i class="bi bi-calendar-month me-1"></i>
            ماه: <strong class="text-primary" id="{{ $uniqueId }}_month_display">{{ $month ?: '-' }}</strong>
        </span>
        <span class="badge bg-light text-dark border">
            <i class="bi bi-calendar-day me-1"></i>
            روز: <strong class="text-info" id="{{ $uniqueId }}_day_display">{{ $day ?: '-' }}</strong>
        </span>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            // ==================== تنظیمات اولیه ====================
            const uniqueId = '{{ $uniqueId }}';
            const initialDate = @json($initialDate);

            const monthNames = [
                'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
                'مرداد', 'شهریور', 'مهر', 'آبان',
                'آذر', 'دی', 'بهمن', 'اسفند'
            ];

            // ==================== عناصر DOM ====================
            const $display = $('#' + uniqueId + '_display');
            const $dayInput = $('#' + uniqueId + '_day');
            const $monthInput = $('#' + uniqueId + '_month');
            const $yearInput = $('#' + uniqueId + '_year');
            const $fullInput = $('#' + uniqueId + '_full');
            const $partsDisplay = $('#' + uniqueId + '_parts');
            const $dayDisplay = $('#' + uniqueId + '_day_display');
            const $monthDisplay = $('#' + uniqueId + '_month_display');
            const $yearDisplay = $('#' + uniqueId + '_year_display');

            // ==================== تابع بروزرسانی فیلدها ====================
            function updateFields(year, month, day) {
                $dayInput.val(day);
                $monthInput.val(month);
                $yearInput.val(year);

                const fullDate = year + '/' +
                    String(month).padStart(2, '0') + '/' +
                    String(day).padStart(2, '0');
                $fullInput.val(fullDate);

                // بروزرسانی نمایش
                $dayDisplay.text(day);
                $monthDisplay.text(month + ' (' + monthNames[month - 1] + ')');
                $yearDisplay.text(year);
                $partsDisplay.fadeIn(300);

                console.log('📅 تاریخ انتخاب شد:', { year, month, day, full: fullDate });
            }

            // ==================== پیکربندی Datepicker ====================
            $(document).ready(function() {

                // ✅ محاسبه مقدار اولیه به صورت صحیح
                let pickerOptions = {
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    position: 'auto',
                    calendarType: 'persian',
                    persianDigit: false, // استفاده از اعداد انگلیسی برای سازگاری بهتر

                    // ✅ مهم: غیرفعال کردن مقداردهی خودکار
                    initialValue: false,

                    toolbox: {
                        enabled: true,
                        calendarSwitch: { enabled: false },
                        todayButton: {
                            enabled: true,
                            text: { fa: 'امروز' }
                        },
                        submitButton: {
                            enabled: true,
                            text: { fa: 'تأیید' }
                        }
                    },

                    navigator: {
                        enabled: true,
                        scroll: { enabled: true },
                        text: {
                            btnNextText: '‹',
                            btnPrevText: '›'
                        }
                    },

                    timePicker: { enabled: false },

                    dayPicker: {
                        enabled: true,
                        titleFormat: 'YYYY MMMM'
                    },

                    monthPicker: {
                        enabled: true,
                        titleFormat: 'YYYY'
                    },

                    yearPicker: {
                        enabled: true,
                        titleFormat: 'YYYY'
                    },

                    onSelect: function(unix) {
                        const pDate = new persianDate(unix);
                        updateFields(pDate.year(), pDate.month(), pDate.date());
                    }
                };

                // ✅ اگر مقدار اولیه داریم، باید به صورت دستی تنظیم کنیم
                if (initialDate && initialDate.year && initialDate.month && initialDate.day) {
                    // ساخت یک persianDate از مقادیر شمسی
                    const pDate = new persianDate([
                        initialDate.year,
                        initialDate.month,
                        initialDate.day
                    ]);

                    // تنظیم مقدار اولیه به صورت Unix timestamp
                    pickerOptions.initialValue = true;
                    pickerOptions.initialValueType = 'unix';

                    // ✅ کلید اصلی: استفاده از unix timestamp صحیح
                    const unixTimestamp = pDate.unix() * 1000; // تبدیل به میلی‌ثانیه

                    console.log('📅 مقدار اولیه شمسی:', initialDate);
                    console.log('📅 Unix timestamp:', unixTimestamp);

                    // راه‌اندازی با مقدار اولیه
                    const picker = $display.persianDatepicker(pickerOptions);

                    // ✅ تنظیم دستی مقدار پس از راه‌اندازی
                    setTimeout(function() {
                        picker.setDate(unixTimestamp);
                    }, 100);

                } else {
                    // بدون مقدار اولیه
                    $display.persianDatepicker(pickerOptions);
                }
            });

            // ==================== تابع پاک کردن (Global) ====================
            window['clearJalaliDate_' + uniqueId] = function() {
                $display.val('');
                $dayInput.val('');
                $monthInput.val('');
                $yearInput.val('');
                $fullInput.val('');
                $partsDisplay.fadeOut(200);

                // ریست کردن datepicker
                const picker = $display.data('persianDatepicker');
                if (picker) {
                    picker.setDate(null);
                }

                console.log('🗑️ تاریخ پاک شد');
            };

        })();
    </script>
@endpush
