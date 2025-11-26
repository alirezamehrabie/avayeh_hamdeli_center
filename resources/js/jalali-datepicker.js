/**
 * Jalali Date Picker با ذخیره‌سازی جداگانه روز، ماه و سال
 */

import persianDate from 'persian-date';
import 'persian-datepicker';

// تنظیمات پیش‌فرض persianDate
persianDate.toLocale('fa');

class JalaliDatePicker {
    constructor() {
        this.monthNames = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
            'مرداد', 'شهریور', 'مهر', 'آبان',
            'آذر', 'دی', 'بهمن', 'اسفند'
        ];
        this.init();
    }

    init() {
        document.querySelectorAll('.jalali-datepicker').forEach(input => {
            this.initializePicker(input);
        });

        // دکمه‌های پاک کردن
        document.querySelectorAll('.clear-date-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const inputGroup = e.target.closest('.input-group');
                const displayInput = inputGroup.querySelector('.jalali-datepicker');
                this.clearDate(displayInput);
            });
        });

        // مقداردهی اولیه اگر old values وجود دارد
        this.initializeFromOldValues();
    }

    initializePicker(input) {
        const dayField = input.dataset.dayField;
        const monthField = input.dataset.monthField;
        const yearField = input.dataset.yearField;
        const fullField = input.dataset.fullField;
        const baseName = input.id.replace('_display', '');

        $(input).persianDatepicker({
            format: 'YYYY/MM/DD',
            initialValue: false,
            autoClose: true,
            position: 'auto',
            onlyTimePicker: false,
            onlySelectOnDate: true,
            calendarType: 'persian',
            inputDelay: 800,
            observer: true,

            navigator: {
                enabled: true,
                scroll: {
                    enabled: true
                },
                text: {
                    btnNextText: '<',
                    btnPrevText: '>'
                }
            },

            toolbox: {
                enabled: true,
                calendarSwitch: {
                    enabled: false
                },
                todayButton: {
                    enabled: true,
                    text: {
                        fa: 'امروز'
                    }
                },
                submitButton: {
                    enabled: true,
                    text: {
                        fa: 'تایید'
                    }
                }
            },

            timePicker: {
                enabled: false
            },

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

            // رویداد انتخاب تاریخ
            onSelect: (unix) => {
                const pDate = new persianDate(unix);

                const year = pDate.year();
                const month = pDate.month();
                const day = pDate.date();

                // ذخیره در فیلدهای مخفی
                document.getElementById(dayField).value = day;
                document.getElementById(monthField).value = month;
                document.getElementById(yearField).value = year;
                document.getElementById(fullField).value = `${year}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;

                // نمایش مقادیر
                this.updatePartsDisplay(baseName, year, month, day);

                // Trigger change event برای validation های احتمالی
                document.getElementById(dayField).dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById(monthField).dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById(yearField).dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    updatePartsDisplay(baseName, year, month, day) {
        const partsContainer = document.getElementById(`${baseName}_parts`);
        if (partsContainer) {
            partsContainer.style.display = 'block';
            document.getElementById(`${baseName}_year_display`).textContent = year;
            document.getElementById(`${baseName}_month_display`).textContent = `${month} (${this.monthNames[month - 1]})`;
            document.getElementById(`${baseName}_day_display`).textContent = day;
        }
    }

    clearDate(displayInput) {
        const dayField = displayInput.dataset.dayField;
        const monthField = displayInput.dataset.monthField;
        const yearField = displayInput.dataset.yearField;
        const fullField = displayInput.dataset.fullField;
        const baseName = displayInput.id.replace('_display', '');

        // پاک کردن مقادیر
        displayInput.value = '';
        document.getElementById(dayField).value = '';
        document.getElementById(monthField).value = '';
        document.getElementById(yearField).value = '';
        document.getElementById(fullField).value = '';

        // مخفی کردن نمایش
        const partsContainer = document.getElementById(`${baseName}_parts`);
        if (partsContainer) {
            partsContainer.style.display = 'none';
        }
    }

    initializeFromOldValues() {
        document.querySelectorAll('.jalali-datepicker').forEach(input => {
            const dayField = input.dataset.dayField;
            const monthField = input.dataset.monthField;
            const yearField = input.dataset.yearField;
            const baseName = input.id.replace('_display', '');

            const day = document.getElementById(dayField)?.value;
            const month = document.getElementById(monthField)?.value;
            const year = document.getElementById(yearField)?.value;

            if (day && month && year) {
                // بروزرسانی نمایش
                input.value = `${year}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                this.updatePartsDisplay(baseName, year, month, day);
            }
        });
    }
}

// اجرا بعد از لود صفحه
document.addEventListener('DOMContentLoaded', () => {
    new JalaliDatePicker();
});

// Export برای استفاده در جاهای دیگر
export default JalaliDatePicker;
