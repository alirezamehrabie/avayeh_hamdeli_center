@extends('layouts.landing')

@php($title = 'انتخاب نوع ورود | آوای همدلی')

@section('content')
<!-- صفحه انتخاب نوع ورود -->
<section class="relative min-h-screen overflow-hidden bg-[#f8fbff] px-4 py-8 sm:px-6 sm:py-14">
    <!-- بلاب‌های رنگی نرم (هماهنگ با هیرو) -->
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#36A9DF]/22 blur-3xl sm:h-96 sm:w-96" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-24 bottom-[-6rem] h-64 w-64 rounded-full bg-[#D4205F]/14 blur-3xl sm:h-80 sm:w-80" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-5xl">
        <!-- نوار بالا: بازگشت + برند -->
        <div class="flex items-center justify-between gap-4">
            <a
                href="{{ route('landing.preview') }}"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-bold text-slate-700 backdrop-blur transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:min-h-0"
            >
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                بازگشت به خانه
            </a>
            <a href="{{ route('landing.preview') }}" class="flex items-center gap-2.5" aria-label="آوای همدلی">
                <img
                    src="{{ asset('images/logo-sm.png') }}"
                    alt="لوگوی آوای همدلی"
                    class="h-9 w-9 rounded-xl object-cover shadow-sm sm:h-10 sm:w-10"
                >
                <span class="hidden min-w-0 sm:block">
                    <span class="block truncate text-sm font-black text-slate-900">آوای همدلی</span>
                    <span class="block truncate text-[10px] leading-4 text-slate-500">مرکز نیکوکاری تخصصی کودکان</span>
                </span>
            </a>
        </div>

        <!-- عنوان -->
        <div class="mt-10 text-center sm:mt-16" data-reveal>
            <span
                class="inline-flex items-center gap-2 rounded-full border border-[#36A9DF]/25 bg-white/70 px-4 py-1.5 text-xs font-bold text-[#1572A1] backdrop-blur"
            >
                <span class="h-2 w-2 rounded-full bg-[#36A9DF]" aria-hidden="true"></span>
                ورود به حساب کاربری
            </span>

            <h1 class="mt-5 text-3xl font-black leading-[1.35] text-slate-900 sm:text-4xl">
                نوع
                <span class="bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] bg-clip-text text-transparent">
                    ورود
                </span>
                خود را انتخاب کنید
            </h1>

            <p class="mx-auto mt-4 max-w-xl text-base leading-8 text-slate-600">
                برای ادامه، مشخص کنید که به‌عنوان کدام بخش از خانواده آوای همدلی می‌خواهید وارد شوید.
            </p>
        </div>

        <!-- کارت‌های انتخاب -->
        <div class="mt-10 grid grid-cols-1 gap-6 sm:mt-14 md:grid-cols-3" data-reveal>
            <!-- ورود پرسنل -->
            <a
                href="{{ route('login') }}"
                class="group relative flex flex-col items-center rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-900/5 transition duration-300 hover:-translate-y-1 hover:border-[#1572A1]/40 focus:outline-none focus-visible:ring-4 focus-visible:ring-[#1572A1]/20"
            >
                <span
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_58%,#A4184B_135%)] text-white shadow-lg shadow-[#5964AE]/25 transition duration-300 group-hover:scale-105"
                >
                    <i class="bi bi-person-badge text-2xl" aria-hidden="true"></i>
                </span>

                <h2 class="mt-5 text-lg font-black text-slate-900">ورود پرسنل</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">مدیران، مددکاران اجتماعی و اپراتورهای مرکز</p>

                <span
                    class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#1572A1]/10 px-5 text-sm font-extrabold text-[#1572A1]"
                >
                    ورود به پنل
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </span>
            </a>

            <!-- ورود اعضا (به‌زودی) -->
            <button
                type="button"
                disabled
                aria-disabled="true"
                class="relative flex cursor-not-allowed flex-col items-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-8 text-center opacity-75"
            >
                <span class="absolute right-4 top-4 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">
                    به‌زودی
                </span>

                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#5964AE]/10 text-[#5964AE]/70">
                    <i class="bi bi-people text-2xl" aria-hidden="true"></i>
                </span>

                <h2 class="mt-5 text-lg font-black text-slate-900">ورود اعضا</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">خانواده‌های تحت پوشش مرکز</p>

                <span class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-slate-100 px-5 text-sm font-extrabold text-slate-400">
                    در دسترس نیست
                </span>
            </button>

            <!-- ورود حامی (به‌زودی) -->
            <button
                type="button"
                disabled
                aria-disabled="true"
                class="relative flex cursor-not-allowed flex-col items-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-8 text-center opacity-75"
            >
                <span class="absolute right-4 top-4 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">
                    به‌زودی
                </span>

                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#A4184B]/10 text-[#A4184B]/70">
                    <i class="bi bi-heart text-2xl" aria-hidden="true"></i>
                </span>

                <h2 class="mt-5 text-lg font-black text-slate-900">ورود حامی</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">حامیان کودکان و اعضای هیئت‌مدیره</p>

                <span class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-slate-100 px-5 text-sm font-extrabold text-slate-400">
                    در دسترس نیست
                </span>
            </button>
        </div>

        <!-- یادآوری -->
        <p class="mt-10 text-center text-xs text-slate-500 sm:mt-14">
            <i class="bi bi-shield-check ml-1 align-middle" aria-hidden="true"></i>
            در صورت نیاز به راهنمایی، با شماره
            <a href="tel:+989136476949" class="font-bold text-[#1572A1]" dir="ltr">۰۹۱۳ ۶۴۷ ۶۹۴۹</a>
            تماس بگیرید.
        </p>
    </div>
</section>
@endsection
