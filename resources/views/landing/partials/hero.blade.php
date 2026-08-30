<!-- بخش آغازین: هیرو -->
<section id="hero" class="relative overflow-hidden bg-[#f8fbff] pb-16 pt-16 sm:pb-24 sm:pt-20">
    <!-- بلاب‌های رنگی نرم -->
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#36A9DF]/22 blur-3xl sm:h-96 sm:w-96" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-24 top-1/4 h-64 w-64 rounded-full bg-[#D4205F]/14 blur-3xl sm:h-80 sm:w-80" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(238,248,253,0.9)_0%,rgba(255,255,255,0.85)_45%,rgba(252,241,246,0.9)_100%)]" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-14">
            <!-- متن -->
            <div class="text-center lg:text-right">
                <span
                    data-reveal
                    class="inline-flex items-center gap-2 rounded-full border border-[#36A9DF]/25 bg-white/70 px-4 py-1.5 text-xs font-bold text-[#1572A1] backdrop-blur"
                >
                    <span class="h-2 w-2 rounded-full bg-[#36A9DF]" aria-hidden="true"></span>
                    مرکز نیکوکاری تخصصی کودکان
                </span>

                <h2 class="mt-5 text-3xl font-black leading-[1.35] text-slate-900 sm:text-4xl lg:text-5xl lg:leading-[1.25]">
                    بگذار صدای
                    <span class="bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] bg-clip-text text-transparent">
                        همدلی
                    </span>
                    به گوش کودکان برسد
                </h2>

                <p class="mx-auto mt-5 max-w-xl text-base leading-8 text-slate-600 sm:text-lg lg:mx-0">
                    آوای همدلی کنار کودکان در نیاز می‌ایستد؛ با آموزش، تغذیه، لباس و فرصتی برای لبخند. هر همدلیِ شما، تک‌تک این کودکان را یک قدم به امید نزدیک‌تر می‌کند.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                    <a
                        href="#help"
                        class="inline-flex min-h-14 items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_58%,#A4184B_135%)] px-8 py-4 text-base font-extrabold text-white shadow-lg shadow-[#5964AE]/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#1572A1]/30 active:translate-y-0"
                    >
                        <i class="bi bi-heart-fill" aria-hidden="true"></i>
                        همراهی کن
                    </a>
                    <a
                        href="#about"
                        class="inline-flex min-h-14 items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white/80 px-8 py-4 text-base font-bold text-slate-700 backdrop-blur transition hover:border-[#1572A1]/40 hover:text-[#1572A1]"
                    >
                        بیشتر بدانید
                        <i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- تصویر / نشان -->
            <div class="relative mx-auto w-full max-w-sm lg:max-w-md" data-reveal>
                <div class="relative overflow-hidden rounded-[2rem] bg-[linear-gradient(145deg,#1572A1_0%,#5964AE_50%,#A4184B_120%)] p-4 shadow-2xl shadow-[#5964AE]/25">
                    <div class="flex aspect-square items-center justify-center rounded-[1.6rem] bg-white/95">
                        <img
                            src="{{ asset('images/logo-sm.png') }}"
                            alt="لوگوی مرکز نیکوکاری آوای همدلی"
                            class="w-2/3 object-contain"
                            loading="eager"
                        >
                    </div>
                </div>

                <!-- نشان شناور شفافیت -->
                <div class="absolute -bottom-4 right-3 flex items-center gap-3 rounded-2xl border border-white bg-white/95 px-4 py-3 shadow-xl shadow-slate-900/10 backdrop-blur sm:right-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1572A1]/10 text-[#1572A1]">
                        <i class="bi bi-shield-check text-xl" aria-hidden="true"></i>
                    </span>
                    <span>
                        <span class="block text-xs font-black text-slate-900">شفاف و امن</span>
                        <span class="block text-[11px] text-slate-500">هر کمک، پیگیری می‌شود</span>
                    </span>
                </div>

                <!-- نشان شناور تعداد -->
                <div class="absolute -top-4 left-3 rounded-2xl border border-white bg-white/95 px-4 py-3 shadow-xl shadow-slate-900/10 backdrop-blur sm:left-6">
                    <span class="flex items-center gap-2">
                        <span class="text-lg font-black text-[#A4184B]">+۲۰۰۰</span>
                        <span class="text-[11px] font-medium leading-4 text-slate-600">کودک در کنار ما<br>هر روز لبخند می‌زنند</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>
