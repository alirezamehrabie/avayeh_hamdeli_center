<!-- پابرگ -->
<footer id="site-footer" class="relative bg-[linear-gradient(145deg,#1e2b4a_0%,#38538C_60%,#5a2a4a_140%)] text-white">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
            <!-- برند -->
            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-wh.webp') }}" alt="لوگوی آوای همدلی" class="h-12 w-12 rounded-xl object-cover">
                    <div>
                        <p class="text-base font-black">آوای همدلی</p>
                        <p class="text-[11px] text-white/70">مرکز نیکوکاری تخصصی کودکان</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-7 text-white/75">
                    با همدلیِ شما، کودکان در نیاز فرصت کودکی، آموزش و امید را به دست می‌آورند.
                </p>
                <div class="mt-5 flex gap-2">
                    @foreach([['bi-instagram','اینستاگرام'],['bi-whatsapp','واتس‌اپ'],['bi-telegram','تلگرام'],['bi-envelope','ایمیل']] as $social)
                        <a
                            href="#contact"
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white/85 transition hover:bg-white/20 hover:text-white"
                            aria-label="{{ $social[1] }}"
                        >
                            <i class="{{ $social[0] }} text-lg" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- دسترسی سریع -->
            <nav aria-label="دسترسی سریع">
                <h3 class="text-sm font-black text-white/90">دسترسی سریع</h3>
                <ul class="mt-4 space-y-2.5">
                    @foreach([['#about','درباره ما'],['#services','خدمات'],['#impact','عددهای امید'],['#stories','قصه‌های همدلی'],['#help','کمک شما']] as $link)
                        <li>
                            <a href="{{ $link[0] }}" class="inline-flex items-center gap-2 text-sm text-white/70 transition hover:text-white">
                                <i class="bi bi-chevron-left text-xs" aria-hidden="true"></i>
                                {{ $link[1] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <!-- ورود پرسنل -->
            <div>
                <h3 class="text-sm font-black text-white/90">اطلاعات بیشتر</h3>
                <ul class="mt-4 space-y-2.5">
                    <li>
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm text-white/70 transition hover:text-white">
                            <i class="bi bi-box-arrow-in-left text-xs" aria-hidden="true"></i>
                            ورود پرسنل و مدیران
                        </a>
                    </li>
                    <li>
                        <a href="tel:+989136476949" class="inline-flex items-center gap-2 text-sm text-white/70 transition hover:text-white">
                            <i class="bi bi-telephone text-xs" aria-hidden="true"></i>
                            ۰۹۱۳ ۶۴۷ ۶۹۴۹
                        </a>
                    </li>
                    <li class="text-sm text-white/70">شنبه تا پنجشنبه، ۸ تا ۱۶</li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-center text-xs text-white/60 sm:flex-row sm:text-right">
            <p>© ۱۴۰۵ مرکز نیکوکاری تخصصی کودکان آوای همدلی. همه‌ی حقوق محفوظ است.</p>
            <p class="inline-flex items-center gap-1">ساخته‌شده با <span class="text-[#ff5d8f]">♥</span> برای کودکان</p>
        </div>
    </div>
</footer>