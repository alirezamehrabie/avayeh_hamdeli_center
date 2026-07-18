export function createAttendanceResultBannerState() {
    return {
        visible: false,
        variant: 'success',
        message: '',
        name: '',
        time: '',
        activityName: '',
    };
}

export function attendanceResultBanner() {
    return {
        showResultBanner(result = {}, fallbackMessage = '') {
            if (this.successBannerTimer) {
                window.clearTimeout(this.successBannerTimer);
            }

            const variant = this.resultBannerVariant(result);
            const isCheckOutResult = ['checked_out', 'already_checked_out', 'not_checked_in'].includes(result?.code);

            this.successBanner = {
                visible: true,
                variant,
                message: this.resultBannerMessage(result, fallbackMessage),
                name: result?.person?.name || '',
                time: this.toPersianDigits(
                    (isCheckOutResult ? result?.attendance?.checked_out_time : result?.attendance?.checked_in_time)
                        || this.currentDisplayTime()
                ),
                activityName: result?.activity?.name || this.activityName || '',
            };

            this.successBannerTimer = window.setTimeout(() => {
                this.successBanner.visible = false;
                this.successBannerTimer = null;
            }, 2000);
        },
        closeResultBanner() {
            if (this.successBannerTimer) {
                window.clearTimeout(this.successBannerTimer);
                this.successBannerTimer = null;
            }

            this.successBanner.visible = false;
        },
        resultBannerVariant(result = {}) {
            if (result?.code === 'duplicate' || result?.code === 'already_checked_out') {
                return 'warning';
            }

            return result?.ok ? 'success' : 'error';
        },
        resultBannerMessage(result = {}, fallbackMessage = '') {
            if (result?.code === 'duplicate') {
                return 'حضور این مددجو قبلاً ثبت شده است';
            }

            if (result?.code === 'already_checked_out') {
                return 'خروج این مددجو قبلاً ثبت شده است';
            }

            if (result?.code === 'checked_out') {
                return 'خروج ثبت شد';
            }

            if (result?.ok) {
                return 'حضور با موفقیت ثبت شد';
            }

            return fallbackMessage || result?.message || this.errorMessageForResultCode(result?.code);
        },
        errorMessageForResultCode(code = '') {
            const messages = {
                invalid_qr: 'کد QR نامعتبر است',
                not_beneficiary: 'این QR متعلق به مددجو نیست',
                beneficiary_unavailable: 'اطلاعات مددجو در دسترس نیست',
                activity_unavailable: 'فعالیت پیدا نشد',
                activity_not_active: 'این کد برای فعالیت فعلی فعال نیست',
                capacity_full: 'ظرفیت فعالیت تکمیل شده است',
                not_checked_in: 'برای این مددجو ورودی ثبت نشده است',
                processing_failed: 'خطا در پردازش کد',
            };

            return messages[code] || 'خطا در پردازش کد';
        },
        currentDisplayTime() {
            return new Intl.DateTimeFormat('fa-IR-u-nu-arabext', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            }).format(new Date());
        },
        toPersianDigits(value) {
            return String(value || '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
        },
        clearResultBannerTimer() {
            if (this.successBannerTimer) {
                window.clearTimeout(this.successBannerTimer);
                this.successBannerTimer = null;
            }
        },
    };
}
