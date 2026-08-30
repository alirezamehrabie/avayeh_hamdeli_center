<!-- بخش تماس -->
<section id="contact" class="landing-section bg-[#f8fbff] py-16 sm:py-24" aria-labelledby="contact-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-16">
            <!-- اطلاعات تماس -->
            <div data-reveal>
                <span class="inline-flex items-center gap-2 rounded-full bg-[#36A9DF]/10 px-4 py-1.5 text-xs font-bold text-[#1572A1]">
                    <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                    تماس با ما
                </span>
                <h2 id="contact-title" class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl lg:text-4xl">
                    در تماس باشیم
                </h2>
                <p class="mt-3 max-w-md text-base leading-8 text-slate-600">
                    برای همدلی، سؤال یا دریافت اطلاعات بیشتر، از راه‌های زیر با ما در ارتباط باشید.
                </p>

                <ul class="mt-8 space-y-3">
                    @php
                        $contactItems = [
                            ['icon' => 'bi-telephone', 'title' => 'تلفن', 'value' => '(۰۲۱) ۱۲۳۴ ۵۶۷۸', 'href' => 'tel:+982112345678', 'ltr' => false],
                            ['icon' => 'bi-whatsapp', 'title' => 'واتس‌اپ', 'value' => '۰۹۱۲ ۳۴۵ ۶۷۸۹', 'href' => '#', 'ltr' => false],
                            ['icon' => 'bi-instagram', 'title' => 'اینستاگرام', 'value' => '@avaye_hamdeli', 'href' => '#', 'ltr' => true],
                            ['icon' => 'bi-geo-alt', 'title' => 'آدرس', 'value' => 'خمینی‌شهر، خیابان منتظری، کوچه ۶۰', 'href' => '#', 'ltr' => false],
                        ];
                    @endphp
                    @foreach($contactItems as $item)
                        <li>
                            <a
                                href="{{ $item['href'] }}"
                                class="flex min-h-16 items-center gap-4 rounded-2xl border border-slate-100 bg-white px-5 py-4 shadow-sm transition hover:border-[#1572A1]/30 hover:shadow-md"
                            >
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#5964AE]/8 text-[#5964AE]">
                                    <i class="{{ $item['icon'] }} text-xl" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold text-slate-500">{{ $item['title'] }}</span>
                                    <span class="mt-0.5 block truncate text-sm font-black text-slate-800" @if($item['ltr']) dir="ltr" @endif>{{ $item['value'] }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- فرم پیام -->
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8" data-reveal>
                <h3 class="text-lg font-black text-slate-900">پیام بگذارید</h3>
                <p class="mt-1 text-sm text-slate-500">ما در اولین فرصت پاسخ می‌دهیم.</p>

                <form class="mt-6 space-y-4" @submit.prevent="alert('در نسخه‌ی پیش‌نمایش، اطلاعات شما ذخیره نمی‌شود.')">
                    <div>
                        <label for="landing-name" class="mb-1.5 block text-sm font-bold text-slate-700">نام</label>
                        <input
                            id="landing-name"
                            type="text"
                            required
                            class="block min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#1572A1] focus:ring-4 focus:ring-[#1572A1]/20"
                            placeholder="نام شما"
                        >
                    </div>
                    <div>
                        <label for="landing-phone" class="mb-1.5 block text-sm font-bold text-slate-700">شماره تماس</label>
                        <input
                            id="landing-phone"
                            type="tel"
                            inputmode="tel"
                            required
                            class="block min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#1572A1] focus:ring-4 focus:ring-[#1572A1]/20"
                            placeholder="۰۹۱۲ ۳۴۵ ۶۷۸۹"
                        >
                    </div>
                    <div>
                        <label for="landing-message" class="mb-1.5 block text-sm font-bold text-slate-700">پیام</label>
                        <textarea
                            id="landing-message"
                            rows="4"
                            required
                            class="block w-full resize-none rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#1572A1] focus:ring-4 focus:ring-[#1572A1]/20"
                            placeholder="نظر، سؤال یا درخواست خود را بنویسید..."
                        ></textarea>
                    </div>
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_58%,#A4184B_135%)] px-6 py-4 text-sm font-extrabold text-white shadow-lg shadow-[#5964AE]/20 transition hover:shadow-xl"
                    >
                        <i class="bi bi-send-fill" aria-hidden="true"></i>
                        ارسال پیام
                    </button>
                    <p class="text-center text-[11px] leading-5 text-slate-400">
                        با ارسال پیام، رضایت می‌دهید مرکز برای پاسخگویی با شما تماس بگیرد.
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>