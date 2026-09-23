<!-- بخش تماس با ما: طراحی شیشه‌ای و مدرن ۲۰۲۶ با معماری فوق‌سبک و اسکرول ۶۰/۱۲۰ فریم روان -->
<section
    id="contact"
    x-data="{ showMapModal: false, addressCopied: false, addressCopyTimer: null }"
    class="landing-section relative overflow-hidden bg-[#f6f9fc] py-12 sm:py-20 lg:py-24"
    aria-labelledby="contact-title"
>
    <!-- هاله‌های نوری آمبینت بهینه و فوق‌سبک با گرادیان شعاعی خالص (بدون پردازش سنگین فیلترهای مات‌کننده GPU) -->
    <div class="pointer-events-none absolute inset-0 [background:radial-gradient(circle_at_15%_25%,rgba(54,169,223,0.09)_0%,transparent_45%),radial-gradient(circle_at_85%_70%,rgba(164,24,75,0.08)_0%,transparent_50%),radial-gradient(circle_at_45%_50%,rgba(89,100,174,0.06)_0%,transparent_45%),radial-gradient(circle_at_20%_85%,rgba(16,185,129,0.06)_0%,transparent_40%)]" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6">
        <!-- عنوان اصلی بخش -->
        <div data-reveal class="mx-auto max-w-2xl text-center">
            <x-landing.section-title id="contact-title" heading="تماس با ما" />
            <p class="mt-2 text-xs font-semibold text-slate-500 sm:text-sm">
                راه‌های ارتباط و گفتگو با مرکز آوای همدلی
            </p>
        </div>

        <!-- گرید دو ستونه: هاب ارتباطی ۲×۲ + فرم شیشه‌ای -->
        <div class="mt-8 grid grid-cols-1 items-start gap-6 sm:mt-12 lg:grid-cols-12 lg:gap-10">
            <!-- ستون درگاه‌های ارتباطی -->
            <div class="lg:col-span-6">
                <div data-reveal class="mb-4 text-center sm:text-right">
                    <h3 class="text-xl font-black text-slate-900 sm:text-2xl">
                        همراه شماییم؛
                        <span class="bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] bg-clip-text text-transparent">
                            شنوای صدای شما
                        </span>
                    </h3>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500 leading-relaxed">
                        برای پاسخگویی سریع، از درگاه‌های زیر با ما در ارتباط باشید:
                    </p>
                </div>

                <!-- گرید ۲ در ۲ شیشه‌ای و مدرن ۲۰۲۶ (2x2 Glassmorphic Action Hub) -->
                @php
                    $channels = [
                        [
                            'icon' => 'bi-telephone-fill',
                            'title' => 'تلفن همراه',
                            'subtitle' => '۰۹۱۳&nbsp;۶۴۷&nbsp;۶۹۴۹',
                            'href' => 'tel:+989136476949',
                            'color' => 'text-[#1572A1]',
                            'icon_glow' => 'bg-[#1572A1]/12',
                            'card_glow' => 'from-[#1572A1]/10 via-[#36A9DF]/5 to-transparent',
                            'hover_border' => 'hover:border-[#1572A1]/50',
                            'hover_text' => 'group-hover:text-[#1572A1]',
                            'arrow_bg' => 'group-hover:bg-[#1572A1]',
                            'ltr' => true,
                            'title_attr' => 'تماس تلفنی با ۰۹۱۳۶۴۷۶۹۴۹',
                            'copyable' => true,
                            'copy_val' => '09136476949',
                        ],
                        [
                            'icon' => 'bi-whatsapp',
                            'title' => 'واتس‌اپ مرکز',
                            'subtitle' => 'ارسال پیام و مدارک',
                            'href' => 'https://wa.me/989136476949?text=' . rawurlencode('سلام، جهت ارتباط با مرکز نیکوکاری آوای همدلی پیام می‌دهم.'),
                            'color' => 'text-emerald-600',
                            'icon_glow' => 'bg-emerald-500/12',
                            'card_glow' => 'from-emerald-500/10 via-emerald-400/5 to-transparent',
                            'hover_border' => 'hover:border-emerald-500/50',
                            'hover_text' => 'group-hover:text-emerald-600',
                            'arrow_bg' => 'group-hover:bg-emerald-600',
                            'ltr' => false,
                            'title_attr' => 'ارسال پیام در واتس‌اپ',
                        ],
                        [
                            'icon' => 'bi-instagram',
                            'title' => 'اینستاگرام رسمی',
                            'subtitle' => '@avaayeh_hamdely',
                            'href' => 'https://instagram.com/avaayeh_hamdely',
                            'color' => 'text-[#A4184B]',
                            'icon_glow' => 'bg-[#A4184B]/12',
                            'card_glow' => 'from-[#A4184B]/10 via-[#D4205F]/5 to-transparent',
                            'hover_border' => 'hover:border-[#A4184B]/50',
                            'hover_text' => 'group-hover:text-[#A4184B]',
                            'arrow_bg' => 'group-hover:bg-[#A4184B]',
                            'ltr' => true,
                            'title_attr' => 'صفحه رسمی اینستاگرام',
                        ],
                        [
                            'icon' => 'bi-geo-alt-fill',
                            'title' => 'مسیریابی حضوری',
                            'subtitle' => 'خمینی‌شهر، خ منتظری',
                            'href' => 'https://nshn.ir/d2_bZ6ndVxTR86',
                            'color' => 'text-[#5964AE]',
                            'icon_glow' => 'bg-[#5964AE]/12',
                            'card_glow' => 'from-[#5964AE]/10 via-[#8b5fe0]/5 to-transparent',
                            'hover_border' => 'hover:border-[#5964AE]/50',
                            'hover_text' => 'group-hover:text-[#5964AE]',
                            'arrow_bg' => 'group-hover:bg-[#5964AE]',
                            'ltr' => false,
                            'title_attr' => 'خمینی‌شهر، خیابان منتظری، بین کوچه ۵۴ و ۵۶ (مسیریابی با نشان، بلد و گوگل مپ)',
                            'is_map' => true,
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-2 gap-2.5 min-[380px]:gap-3.5 sm:gap-4">
                    @foreach($channels as $channel)
                        <a
                            x-data="{ copied: false, copyTimer: null }"
                            href="{{ $channel['href'] }}"
                            target="{{ str_starts_with($channel['href'], 'http') ? '_blank' : '_self' }}"
                            rel="{{ str_starts_with($channel['href'], 'http') ? 'noopener noreferrer' : '' }}"
                            title="{{ $channel['title_attr'] }}"
                            @if(!empty($channel['is_map']))
                                @click.prevent="showMapModal = true"
                            @endif
                            class="group relative flex min-h-[105px] min-[380px]:min-h-[114px] sm:min-h-[122px] flex-col justify-between overflow-hidden rounded-2xl border border-white/90 bg-white/80 p-3 min-[380px]:p-3.5 sm:p-4 shadow-[0_8px_24px_-6px_rgba(15,23,42,0.05),inset_0_1px_2px_rgba(255,255,255,0.95)] backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/95 {{ $channel['hover_border'] }} hover:shadow-[0_12px_30px_-6px_rgba(15,23,42,0.08),inset_0_1px_2px_rgba(255,255,255,1)] active:scale-[0.98] touch-manipulation select-none"
                        >
                            <!-- پرتو صیقلی لبه بالایی شیشه (Specular Rim Light) -->
                            <span class="pointer-events-none absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-white to-transparent" aria-hidden="true"></span>

                            <!-- بازتاب رنگی نرم در پس‌زمینه شیشه‌ای (Ambient Color Reflection سبک بدون بلور سنگین) -->
                            <span class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.7)_0%,transparent_70%)] opacity-0 transition-opacity duration-300 group-hover:opacity-100" aria-hidden="true"></span>

                            <!-- ردیف بالای کارت: نماد گلس‌مورفیک چندلایه + فلش شیشه‌ای -->
                            <div class="relative z-10 flex items-center justify-between">
                                <!-- نماد شیشه‌ای با حاشیه مویین براق (بدون افکت بلور تو در تو جهت حفظ سرعت ۶۰ فریم) -->
                                <div class="relative flex h-9 w-9 min-[380px]:h-10 min-[380px]:w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl border border-white/90 bg-white/90 {{ $channel['color'] }} shadow-[0_4px_12px_rgba(0,0,0,0.03),inset_0_1px_2px_rgba(255,255,255,1)] transition-transform duration-200 group-hover:scale-105">
                                    <span class="pointer-events-none absolute inset-0 rounded-xl {{ $channel['icon_glow'] }}" aria-hidden="true"></span>
                                    <i class="{{ $channel['icon'] }} relative z-10 text-sm min-[380px]:text-base sm:text-lg" aria-hidden="true"></i>
                                    <span class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-white/80" aria-hidden="true"></span>
                                </div>

                                <!-- دکمه فلش شیشه‌ای پیل سبک -->
                                <span class="flex h-5.5 w-5.5 min-[380px]:h-6 min-[380px]:w-6 items-center justify-center rounded-full border border-white/90 bg-white/85 text-slate-400 shadow-2xs transition-all duration-200 {{ $channel['arrow_bg'] }} group-hover:border-transparent group-hover:text-white group-hover:-translate-x-0.5 group-hover:-translate-y-0.5">
                                    <i class="bi bi-arrow-up-left text-[10px] min-[380px]:text-[11px]" aria-hidden="true"></i>
                                </span>
                            </div>

                            <!-- ردیف متنی کارت: بسیار خلوت، کاملاً خوانا و درشت -->
                            <div class="relative z-10 mt-2.5 min-[380px]:mt-3 text-right">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="block text-[10px] min-[380px]:text-[11px] sm:text-xs font-bold text-slate-500">{{ $channel['title'] }}</span>
                                    @if(!empty($channel['copyable']))
                                        <button
                                            type="button"
                                            @click.stop.prevent="
                                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                                    navigator.clipboard.writeText('{{ $channel['copy_val'] }}');
                                                } else {
                                                    const el = document.createElement('textarea');
                                                    el.value = '{{ $channel['copy_val'] }}';
                                                    document.body.appendChild(el);
                                                    el.select();
                                                    document.execCommand('copy');
                                                    document.body.removeChild(el);
                                                }
                                                copied = true;
                                                clearTimeout(copyTimer);
                                                copyTimer = setTimeout(() => { copied = false; }, 2000);
                                            "
                                            class="inline-flex items-center gap-0.5 min-[380px]:gap-1 rounded-md border px-1 min-[380px]:px-1.5 py-0.5 text-[9px] min-[380px]:text-[10px] font-bold transition-all active:scale-90"
                                            :class="copied
                                                ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-700 shadow-2xs'
                                                : 'border-white/90 bg-white/90 text-slate-500 hover:border-[#1572A1]/40 hover:bg-white hover:text-[#1572A1] shadow-2xs'"
                                            :title="copied ? 'شماره کپی شد' : 'کپی شماره همراه'"
                                            aria-label="کپی شماره تلفن همراه"
                                        >
                                            <i :class="copied ? 'bi-check2 text-emerald-600 text-[10px] min-[380px]:text-[11px]' : 'bi-copy text-[8.5px] min-[380px]:text-[9px]'" aria-hidden="true"></i>
                                            <span x-text="copied ? 'کپی شد' : 'کپی'" class="text-[8.5px] min-[380px]:text-[9.5px]">کپی</span>
                                        </button>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-right">
                                    <span class="inline-block max-w-full truncate text-[11px] min-[380px]:text-xs sm:text-sm font-black tabular-nums tracking-normal text-slate-900 transition-colors {{ $channel['hover_text'] }}" @if($channel['ltr']) dir="ltr" @endif>
                                        {!! $channel['subtitle'] !!}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- نوار مینی‌مال ساعات مراجعه حضوری شیشه‌ای بهینه -->
                <div class="mt-3.5 flex items-start sm:items-center gap-2.5 rounded-2xl border border-white/90 bg-white/80 p-2.5 min-[380px]:p-3 text-[11px] min-[380px]:text-xs text-slate-600 shadow-[0_4px_16px_-4px_rgba(15,23,42,0.04),inset_0_1px_1.5px_rgba(255,255,255,0.9)] backdrop-blur-sm">
                    <span class="flex h-6.5 w-6.5 min-[380px]:h-7 min-[380px]:w-7 shrink-0 items-center justify-center rounded-xl border border-white/80 bg-[#5964AE]/10 text-[#5964AE] shadow-2xs mt-0.5 sm:mt-0">
                        <i class="bi bi-clock-fill text-[11px] min-[380px]:text-xs" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1 leading-4.5 sm:leading-5">
                        <span class="font-bold text-slate-800">ساعات مراجعه حضوری:</span>
                        <span class="text-slate-500">شنبه تا چهارشنبه ۸ الی ۱۴ | پنج‌شنبه‌ها تا ۱۲:۳۰</span>
                    </div>
                </div>
            </div>

            <!-- ستون فرم پیام شیشه‌ای (حذف انیمیشن ترنزیشن سنگین ستون هنگام اسکرول) -->
            <div class="lg:col-span-6">
                <div
                    x-data="{
                        submitting: false,
                        submitted: false,
                        phoneError: '',
                        formData: { name: '', phone: '', message: '' },
                        normalizePhone(val) {
                            if (!val) return '';
                            let cleaned = val.toString()
                                .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
                                .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
                                .replace(/[\s\-_+()]/g, '');
                            if (cleaned.startsWith('00989') && cleaned.length === 14) {
                                cleaned = '0' + cleaned.substring(4);
                            } else if (cleaned.startsWith('989') && cleaned.length === 12) {
                                cleaned = '0' + cleaned.substring(2);
                            } else if (cleaned.startsWith('9') && cleaned.length === 10) {
                                cleaned = '0' + cleaned;
                            }
                            return cleaned;
                        },
                        validatePhone() {
                            const raw = (this.formData.phone || '').trim();
                            if (!raw) {
                                this.phoneError = 'لطفاً شماره تلفن همراه خود را وارد کنید.';
                                return false;
                            }
                            const normalized = this.normalizePhone(raw);
                            const iranMobileRegex = /^09\d{9}$/;
                            if (!iranMobileRegex.test(normalized)) {
                                this.phoneError = 'شماره همراه باید ۱۱ رقم و با ۰۹ آغاز شود (مثال: ۰۹۱۲۳۴۵۶۷۸۹).';
                                return false;
                            }
                            this.phoneError = '';
                            return true;
                        },
                        submitForm() {
                            if (!this.formData.name.trim()) return;
                            if (!this.validatePhone()) {
                                document.getElementById('landing-phone')?.focus();
                                return;
                            }
                            if (!this.formData.message.trim()) return;

                            this.formData.phone = this.normalizePhone(this.formData.phone);
                            this.submitting = true;
                            setTimeout(() => {
                                this.submitting = false;
                                this.submitted = true;
                            }, 700);
                        },
                        resetForm() {
                            this.submitted = false;
                            this.phoneError = '';
                            this.formData = { name: '', phone: '', message: '' };
                        }
                    }"
                    class="relative rounded-3xl border border-white/90 bg-white/85 p-4 sm:p-7 shadow-[0_16px_40px_-12px_rgba(15,23,42,0.06),inset_0_1px_2px_rgba(255,255,255,0.95)] backdrop-blur-sm"
                >
                    <!-- نمای پیش از ارسال فرم -->
                    <div x-show="!submitted" x-transition:enter="transition ease-out duration-300">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-[#1572A1]/20 bg-[#1572A1]/5 px-3 py-1 text-xs font-bold text-[#1572A1]">
                                <i class="bi bi-chat-dots-fill text-xs" aria-hidden="true"></i>
                                پیام مستقیم
                            </span>
                            <span class="text-[11px] font-medium text-slate-400">پاسخ سریع</span>
                        </div>

                        <h4 class="mt-2 text-lg font-black text-slate-900 sm:text-xl">
                            پیام یا پرسش خود را بگذارید
                        </h4>

                        <form class="mt-4 space-y-3 sm:space-y-3.5" @submit.prevent="submitForm()">
                            <div>
                                <label for="landing-name" class="mb-1 block text-xs font-bold text-slate-700">نام و نام خانوادگی</label>
                                <input
                                    id="landing-name"
                                    x-model="formData.name"
                                    type="text"
                                    required
                                    autocomplete="name"
                                    class="block min-h-11 w-full rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 outline-none transition-all duration-200 hover:border-slate-300 focus:border-[#1572A1] focus:bg-white focus:ring-3 focus:ring-[#1572A1]/10"
                                    placeholder="نام شما"
                                >
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="landing-phone" class="block text-xs font-bold text-slate-700">شماره تلفن همراه</label>
                                    <span class="text-[10px] text-slate-400">جهت هماهنگی و تماس</span>
                                </div>
                                <div class="relative">
                                    <input
                                        id="landing-phone"
                                        x-model="formData.phone"
                                        @input="if (phoneError) validatePhone()"
                                        @blur="if (formData.phone) validatePhone()"
                                        type="tel"
                                        inputmode="numeric"
                                        required
                                        autocomplete="tel"
                                        :class="phoneError ? 'border-rose-400 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10' : 'border-slate-200/80 bg-slate-50/70 text-slate-900 hover:border-slate-300 focus:border-[#1572A1] focus:bg-white focus:ring-[#1572A1]/10'"
                                        class="block min-h-11 w-full rounded-xl border px-3.5 pr-10 text-base sm:text-sm placeholder:text-slate-400 outline-none transition-all duration-200 focus:ring-3"
                                        placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                                        dir="ltr"
                                    >
                                    <!-- نشانگر تایید صحت شماره (تیک سبز در صورت اعتبار کامل) -->
                                    <div
                                        x-show="formData.phone && !phoneError && /^09\d{9}$/.test(normalizePhone(formData.phone))"
                                        x-cloak
                                        x-transition
                                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-emerald-500"
                                        title="شماره معتبر است"
                                    >
                                        <i class="bi bi-check-circle-fill text-base" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <p
                                    x-show="phoneError"
                                    x-cloak
                                    x-transition
                                    role="alert"
                                    class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-rose-600"
                                >
                                    <i class="bi bi-exclamation-circle-fill text-xs shrink-0" aria-hidden="true"></i>
                                    <span x-text="phoneError"></span>
                                </p>
                            </div>

                            <div>
                                <label for="landing-message" class="mb-1 block text-xs font-bold text-slate-700">متن پیام یا دیدگاه</label>
                                <textarea
                                    id="landing-message"
                                    x-model="formData.message"
                                    rows="3"
                                    required
                                    class="block w-full resize-none rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 py-2.5 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 outline-none transition-all duration-200 hover:border-slate-300 focus:border-[#1572A1] focus:bg-white focus:ring-3 focus:ring-[#1572A1]/10"
                                    placeholder="پیام یا پرسش خود را اینجا بنویسید..."
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] px-5 py-3 text-xs sm:text-sm font-extrabold text-white shadow-md shadow-[#1572A1]/20 transition-all duration-300 hover:shadow-lg hover:shadow-[#5964AE]/30 active:translate-y-0 touch-manipulation disabled:opacity-80"
                            >
                                <span x-show="!submitting" class="inline-flex items-center gap-2">
                                    <i class="bi bi-send-fill text-xs" aria-hidden="true"></i>
                                    <span>ارسال پیام</span>
                                </span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>در حال ارسال پیام...</span>
                                </span>
                            </button>

                            <div class="flex items-center justify-center gap-1.5 text-center text-[10px] leading-4 text-slate-400">
                                <i class="bi bi-shield-check text-emerald-600" aria-hidden="true"></i>
                                <span>اطلاعات شما نزد مرکز آوای همدلی کاملاً محرمانه است.</span>
                            </div>
                        </form>
                    </div>

                    <!-- نمای پس از ارسال پیام (Glass Success State) -->
                    <div
                        x-show="submitted"
                        x-cloak
                        x-transition:enter="transition ease-out duration-400"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="flex flex-col items-center justify-center py-7 text-center"
                    >
                        <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-400/30 bg-emerald-500/10 text-emerald-600 shadow-[0_8px_20px_-4px_rgba(16,185,129,0.2)]">
                            <span class="pointer-events-none absolute inset-0 rounded-2xl ring-1 ring-inset ring-white/80" aria-hidden="true"></span>
                            <i class="bi bi-check2 text-3xl" aria-hidden="true"></i>
                        </div>

                        <h4 class="mt-3.5 text-base sm:text-lg font-black text-slate-900">
                            پیام شما با موفقیت ثبت شد
                        </h4>

                        <p class="mt-2 max-w-sm text-xs leading-relaxed text-slate-600">
                            از همراهی پرمهرتان سپاسگزاریم؛ همکاران ما در مرکز آوای همدلی در سریع‌ترین زمان با شماره ثبت‌شده تماس خواهند گرفت.
                        </p>

                        <div class="mt-3.5 inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 px-3 py-1.5 text-[11px] font-bold text-slate-500">
                            <i class="bi bi-shield-lock-fill text-emerald-600 text-xs" aria-hidden="true"></i>
                            <span>اطلاعات تماس شما نزد مرکز محفوظ است</span>
                        </div>

                        <button
                            type="button"
                            @click="resetForm()"
                            class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white/95 px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs transition-all duration-200 hover:border-[#1572A1]/40 hover:bg-white hover:text-[#1572A1] active:scale-95"
                        >
                            <i class="bi bi-arrow-repeat text-xs" aria-hidden="true"></i>
                            <span>ارسال پیام جدید</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- پنجره شیشه‌ای انتخاب مسیریاب و مشاهده نشانی دقیق (Mobile Action Sheet & Desktop Modal) -->
    <!-- اتصال مستقیم به body با x-teleport جهت تضمین باز شدن کامل و دقیق به صورت Bottom Sheet در موبایل -->
    <template x-teleport="body">
        <div
            x-show="showMapModal"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="showMapModal = false"
            class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4 bg-slate-900/50 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
            aria-labelledby="map-modal-title"
        >
            <!-- لایه پس‌زمینه برای بستن با کلیک بیرون -->
            <div class="fixed inset-0" @click="showMapModal = false" aria-hidden="true"></div>

            <!-- بدنه شیشه‌ای مدال و باتم‌شیت موبایل -->
            <div
                x-show="showMapModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
                x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
                class="relative z-10 w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl border border-white/90 bg-white/95 p-5 pb-7 sm:p-6 sm:pb-6 shadow-2xl backdrop-blur-md"
            >
                <!-- دستگیره سوایپ در موبایل -->
                <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-300/80 sm:hidden" aria-hidden="true"></div>

                <!-- سربرگ پنجره -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#5964AE]/10 text-[#5964AE]">
                            <i class="bi bi-geo-alt-fill text-base" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h4 id="map-modal-title" class="text-sm sm:text-base font-black text-slate-900">
                                مسیریابی به مرکز آوای همدلی
                            </h4>
                            <p class="text-[11px] text-slate-500">انتخاب اپلیکیشن نقشه یا مشاهده نشانی دقیق</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showMapModal = false"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition active:scale-95"
                        aria-label="بستن"
                    >
                        <i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- کادر نشانی دقیق متنی همراه با دکمه کپی -->
                <div class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50/90 p-3.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-right">
                            <span class="block text-[11px] font-bold text-slate-400">نشانی دقیق:</span>
                            <p class="mt-1 text-xs sm:text-sm font-black text-slate-800 leading-relaxed">
                                خمینی‌شهر، خیابان منتظری، بین کوچه ۵۴ و ۵۶
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="
                                const addr = 'خمینی‌شهر، خیابان منتظری، بین کوچه ۵۴ و ۵۶';
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(addr);
                                } else {
                                    const el = document.createElement('textarea');
                                    el.value = addr;
                                    document.body.appendChild(el);
                                    el.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(el);
                                }
                                addressCopied = true;
                                clearTimeout(addressCopyTimer);
                                addressCopyTimer = setTimeout(() => { addressCopied = false; }, 2500);
                            "
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-xs font-bold transition active:scale-95"
                            :class="addressCopied
                                ? 'border-emerald-500/50 bg-emerald-50 text-emerald-700'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-[#5964AE]/40 hover:text-[#5964AE] shadow-xs'"
                            aria-label="کپی نشانی متنی"
                        >
                            <i :class="addressCopied ? 'bi-check2 text-emerald-600' : 'bi-copy'" class="text-xs" aria-hidden="true"></i>
                            <span x-text="addressCopied ? 'کپی شد' : 'کپی نشانی'">کپی نشانی</span>
                        </button>
                    </div>
                </div>

                <!-- فهرست گزینه‌های مسیریابی -->
                <div class="mt-4 space-y-2.5">
                    <span class="block text-[11px] font-bold text-slate-400">باز کردن در اپلیکیشن نقشه:</span>

                    <!-- گزینه ۱: نشان (پیشنهادی با لینک رسمی) -->
                    <a
                        href="https://nshn.ir/d2_bZ6ndVxTR86"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex items-center justify-between rounded-2xl border border-sky-200 bg-sky-50/70 p-3 text-right transition hover:border-sky-400 hover:bg-sky-100/80"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1976D2] text-white shadow-xs">
                                <i class="bi bi-cursor-fill text-lg" aria-hidden="true"></i>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs sm:text-sm font-black text-slate-900">مسیریابی با نشان</span>
                                    <span class="rounded-full bg-sky-200/90 px-2 py-0.5 text-[10px] font-black text-sky-800">پیشنهادی</span>
                                </div>
                                <span class="text-[11px] text-slate-500">نقطه ثبت‌شده و دقیق مرکز در نقشه</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-left text-slate-400 group-hover:text-sky-700 transition-transform group-hover:-translate-x-1" aria-hidden="true"></i>
                    </a>

                    <!-- گزینه ۲: گوگل مپ (Google Maps) -->
                    <a
                        href="https://www.google.com/maps/search/?api=1&query=32.6815273,51.5289338"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white p-3 text-right transition hover:border-emerald-300 hover:bg-emerald-50/40"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                                <i class="bi bi-google text-lg" aria-hidden="true"></i>
                            </span>
                            <div>
                                <span class="block text-xs sm:text-sm font-black text-slate-900">مسیریابی با گوگل مپ</span>
                                <span class="text-[11px] text-slate-500">مختصات دقیق ۳۲.۶۸۱۵ , ۵۱.۵۲۸۹</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-left text-slate-400 group-hover:text-emerald-700 transition-transform group-hover:-translate-x-1" aria-hidden="true"></i>
                    </a>

                    <!-- گزینه ۳: بلد (Balad) -->
                    <a
                        href="https://balad.ir/location?latitude=32.6815273&longitude=51.5289338"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white p-3 text-right transition hover:border-amber-300 hover:bg-amber-50/40"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs">
                                <i class="bi bi-compass-fill text-lg" aria-hidden="true"></i>
                            </span>
                            <div>
                                <span class="block text-xs sm:text-sm font-black text-slate-900">مسیریابی با بلد</span>
                                <span class="text-[11px] text-slate-500">مسیریاب و نقشه ایرانی بلد</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-left text-slate-400 group-hover:text-amber-700 transition-transform group-hover:-translate-x-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </template>
</section>
