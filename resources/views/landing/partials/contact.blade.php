<!-- بخش تماس با ما: طراحی نوین ۲۰۲۶ کاملاً بهینه، خلوت و خوانا برای موبایل و دسکتاپ -->
<section id="contact" class="landing-section relative overflow-hidden bg-[#f8fbff] py-12 sm:py-20 lg:py-24" aria-labelledby="contact-title">
    <!-- هاله‌های نوری آمبینت بسیار لطیف -->
    <div class="pointer-events-none absolute -left-28 top-1/4 h-80 w-80 rounded-full bg-[#36A9DF]/8 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-28 bottom-1/4 h-80 w-80 rounded-full bg-[#A4184B]/6 blur-3xl" aria-hidden="true"></div>

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
            <div class="lg:col-span-6" data-reveal>
                <div class="mb-4 text-center sm:text-right">
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

                <!-- گرید ۲ در ۲ فوق‌العاده خوانا و ارگونومیک لمسی (2x2 Quick Action Hub) -->
                @php
                    $channels = [
                        [
                            'icon' => 'bi-telephone-fill',
                            'title' => 'تلفن همراه',
                            'subtitle' => '۰۹۱۳&nbsp;۶۴۷&nbsp;۶۹۴۹',
                            'href' => 'tel:+989136476949',
                            'color' => 'text-[#1572A1]',
                            'bg' => 'from-[#1572A1]/15 to-[#36A9DF]/10',
                            'ring' => 'group-hover:ring-[#1572A1]/30',
                            'hover_text' => 'group-hover:text-[#1572A1]',
                            'ltr' => true,
                            'title_attr' => 'تماس تلفنی با ۰۹۱۳۶۴۷۶۹۴۹',
                        ],
                        [
                            'icon' => 'bi-whatsapp',
                            'title' => 'واتس‌اپ مرکز',
                            'subtitle' => 'ارسال پیام و مدارک',
                            'href' => 'https://wa.me/989136476949',
                            'color' => 'text-emerald-600',
                            'bg' => 'from-emerald-500/15 to-emerald-400/10',
                            'ring' => 'group-hover:ring-emerald-500/30',
                            'hover_text' => 'group-hover:text-emerald-600',
                            'ltr' => false,
                            'title_attr' => 'ارسال پیام در واتس‌اپ',
                        ],
                        [
                            'icon' => 'bi-instagram',
                            'title' => 'اینستاگرام رسمی',
                            'subtitle' => '@avaayeh_hamdely',
                            'href' => 'https://instagram.com/avaayeh_hamdely',
                            'color' => 'text-[#A4184B]',
                            'bg' => 'from-[#A4184B]/15 to-[#D4205F]/10',
                            'ring' => 'group-hover:ring-[#A4184B]/30',
                            'hover_text' => 'group-hover:text-[#A4184B]',
                            'ltr' => true,
                            'title_attr' => 'صفحه رسمی اینستاگرام',
                        ],
                        [
                            'icon' => 'bi-geo-alt-fill',
                            'title' => 'مسیریابی حضوری',
                            'subtitle' => 'خمینی‌شهر، خ منتظری',
                            'href' => 'https://maps.google.com/?q=Khomeyni+Shahr+Montazeri',
                            'color' => 'text-[#5964AE]',
                            'bg' => 'from-[#5964AE]/15 to-[#8b5fe0]/10',
                            'ring' => 'group-hover:ring-[#5964AE]/30',
                            'hover_text' => 'group-hover:text-[#5964AE]',
                            'ltr' => false,
                            'title_attr' => 'خمینی‌شهر، خیابان منتظری، کوچه ۶۰',
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    @foreach($channels as $channel)
                        <a
                            href="{{ $channel['href'] }}"
                            target="{{ str_starts_with($channel['href'], 'http') ? '_blank' : '_self' }}"
                            rel="{{ str_starts_with($channel['href'], 'http') ? 'noopener noreferrer' : '' }}"
                            title="{{ $channel['title_attr'] }}"
                            class="group relative flex min-h-[108px] sm:min-h-[120px] flex-col justify-between rounded-2xl border border-white/90 bg-white/80 p-3.5 sm:p-4 shadow-[0_4px_16px_-4px_rgba(15,23,42,0.05)] backdrop-blur-xl ring-1 ring-slate-900/5 transition-all duration-300 hover:-translate-y-0.5 hover:bg-white hover:shadow-md hover:shadow-slate-900/8 active:scale-[0.98] touch-manipulation select-none"
                        >
                            <!-- ردیف بالای کارت: نماد گلس‌مورفیک + آیکون فلش باز شونده -->
                            <div class="flex items-center justify-between">
                                <div class="relative flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl border border-white/90 bg-gradient-to-br {{ $channel['bg'] }} {{ $channel['color'] }} shadow-xs backdrop-blur-md transition-transform duration-300 group-hover:scale-105">
                                    <i class="{{ $channel['icon'] }} text-base sm:text-lg" aria-hidden="true"></i>
                                    <span class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-white/60" aria-hidden="true"></span>
                                </div>
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100/70 text-slate-400 transition-all duration-300 group-hover:bg-[#1572A1] group-hover:text-white group-hover:-translate-x-0.5 group-hover:-translate-y-0.5">
                                    <i class="bi bi-arrow-up-left text-[11px]" aria-hidden="true"></i>
                                </span>
                            </div>

                            <!-- ردیف متنی کارت: بسیار خلوت، کاملاً خوانا و درشت -->
                            <div class="mt-3 text-right">
                                <span class="block text-[11px] sm:text-xs font-bold text-slate-500">{{ $channel['title'] }}</span>
                                <div class="mt-0.5 text-right">
                                    <span class="inline-block max-w-full truncate text-xs sm:text-sm font-black tabular-nums tracking-wide text-slate-900 transition-colors {{ $channel['hover_text'] }}" @if($channel['ltr']) dir="ltr" @endif>
                                        {!! $channel['subtitle'] !!}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- نوار مینی‌مال ساعات مراجعه حضوری -->
                <div class="mt-3.5 flex items-start sm:items-center gap-2.5 rounded-2xl border border-slate-200/70 bg-white/70 p-3 text-xs text-slate-600 shadow-xs backdrop-blur-sm">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-[#5964AE]/10 text-[#5964AE] mt-0.5 sm:mt-0">
                        <i class="bi bi-clock-fill text-xs" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1 leading-5">
                        <span class="font-bold text-slate-800">ساعات مراجعه حضوری:</span>
                        <span class="text-slate-500">شنبه تا چهارشنبه ۸ الی ۱۴ | پنج‌شنبه‌ها تا ۱۲:۳۰</span>
                    </div>
                </div>
            </div>

            <!-- ستون فرم پیام شیشه‌ای -->
            <div class="lg:col-span-6" data-reveal>
                <div class="relative rounded-3xl border border-white/90 bg-white/85 p-4 sm:p-7 shadow-[0_16px_40px_-12px_rgba(15,23,42,0.06)] backdrop-blur-xl ring-1 ring-slate-200/70">
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

                    <form class="mt-4 space-y-3 sm:space-y-3.5" @submit.prevent="alert('در نسخه‌ی پیش‌نمایش، اطلاعات شما ذخیره نمی‌شود.')">
                        <div>
                            <label for="landing-name" class="mb-1 block text-xs font-bold text-slate-700">نام و نام خانوادگی</label>
                            <input
                                id="landing-name"
                                type="text"
                                required
                                class="block min-h-11 w-full rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 outline-none transition-all duration-200 hover:border-slate-300 focus:border-[#1572A1] focus:bg-white focus:ring-3 focus:ring-[#1572A1]/10"
                                placeholder="نام شما"
                            >
                        </div>

                        <div>
                            <label for="landing-phone" class="mb-1 block text-xs font-bold text-slate-700">شماره تماس</label>
                            <input
                                id="landing-phone"
                                type="tel"
                                inputmode="tel"
                                required
                                class="block min-h-11 w-full rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 outline-none transition-all duration-200 hover:border-slate-300 focus:border-[#1572A1] focus:bg-white focus:ring-3 focus:ring-[#1572A1]/10"
                                placeholder="۰۹۱۲ ۳۴۵ ۶۷۸۹"
                                dir="ltr"
                            >
                        </div>

                        <div>
                            <label for="landing-message" class="mb-1 block text-xs font-bold text-slate-700">متن پیام یا دیدگاه</label>
                            <textarea
                                id="landing-message"
                                rows="3"
                                required
                                class="block w-full resize-none rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 py-2.5 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 outline-none transition-all duration-200 hover:border-slate-300 focus:border-[#1572A1] focus:bg-white focus:ring-3 focus:ring-[#1572A1]/10"
                                placeholder="پیام یا پرسش خود را اینجا بنویسید..."
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] px-5 py-3 text-xs sm:text-sm font-extrabold text-white shadow-md shadow-[#1572A1]/20 transition-all duration-300 hover:shadow-lg hover:shadow-[#5964AE]/30 active:translate-y-0 touch-manipulation"
                        >
                            <i class="bi bi-send-fill text-xs" aria-hidden="true"></i>
                            <span>ارسال پیام</span>
                        </button>

                        <div class="flex items-center justify-center gap-1.5 text-center text-[10px] leading-4 text-slate-400">
                            <i class="bi bi-shield-check text-emerald-600" aria-hidden="true"></i>
                            <span>اطلاعات شما نزد مرکز آوای همدلی کاملاً محرمانه است.</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>