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
        <div class="mt-8 grid grid-cols-3 gap-2.5 sm:mt-14 sm:gap-6" data-reveal>
            <!-- ورود پرسنل -->
            <a
                href="{{ route('login') }}"
                class="group relative flex flex-col items-center overflow-hidden rounded-2xl border-2 border-[#1572A1] bg-[linear-gradient(155deg,#1572A1_0%,#2E97CC_55%,#36A9DF_100%)] p-3 pt-7 text-center shadow-xl shadow-[#1572A1]/30 transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#1572A1]/45 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40 sm:rounded-3xl sm:p-8"
            >
                <span class="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/15 blur-2xl" aria-hidden="true"></span>

                <span
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white shadow-lg shadow-[#0b4d75]/25 backdrop-blur transition duration-300 group-hover:scale-105 sm:h-16 sm:w-16 sm:rounded-2xl"
                >
                    <i class="bi bi-person-badge text-lg sm:text-2xl" aria-hidden="true"></i>
                </span>

                <h2 class="relative mt-3 min-h-[2.1rem] text-xs font-black leading-tight text-white sm:mt-5 sm:min-h-0 sm:text-lg">ورود پرسنل</h2>
                <p class="relative mt-2 hidden text-sm leading-relaxed text-blue-50/90 sm:block">مدیران، مددکاران اجتماعی و اپراتورهای مرکز</p>

                <span class="relative mt-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#1572A1] shadow-lg shadow-[#0b4d75]/25 sm:hidden">
                    <i class="bi bi-arrow-left text-sm" aria-hidden="true"></i>
                </span>
                <span class="relative mt-6 hidden min-h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-[#1572A1] shadow-lg shadow-[#0b4d75]/15 sm:inline-flex">
                    ورود به پنل
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </span>
            </a>

            <!-- ورود اعضا -->
            <button
                type="button"
                aria-disabled="true"
                class="group relative flex flex-col items-center overflow-hidden rounded-2xl border-2 border-[#5964AE] bg-[linear-gradient(155deg,#5964AE_0%,#6B5FC9_55%,#7C6BD8_100%)] p-3 pt-7 text-center shadow-xl shadow-[#5964AE]/30 transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#5964AE]/45 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40 sm:rounded-3xl sm:p-8"
            >
                <span class="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/15 blur-2xl" aria-hidden="true"></span>

                <span
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white shadow-lg shadow-[#3b3f6e]/25 backdrop-blur transition duration-300 group-hover:scale-105 sm:h-16 sm:w-16 sm:rounded-2xl"
                >
                    <i class="bi bi-people text-lg sm:text-2xl" aria-hidden="true"></i>
                </span>

                <h2 class="relative mt-3 min-h-[2.1rem] text-xs font-black leading-tight text-white sm:mt-5 sm:min-h-0 sm:text-lg">ورود اعضا</h2>
                <p class="relative mt-2 hidden text-sm leading-relaxed text-indigo-50/90 sm:block">خانواده‌های تحت پوشش مرکز</p>

                <span class="relative mt-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#5964AE] shadow-lg shadow-[#3b3f6e]/25 sm:hidden">
                    <i class="bi bi-arrow-left text-sm" aria-hidden="true"></i>
                </span>
                <span class="relative mt-6 hidden min-h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-[#5964AE] shadow-lg shadow-[#3b3f6e]/15 sm:inline-flex">
                    ورود به پنل
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </span>
            </button>

            <!-- ورود حامی -->
            <button
                type="button"
                aria-disabled="true"
                class="group relative flex flex-col items-center overflow-hidden rounded-2xl border-2 border-[#A4184B] bg-[linear-gradient(155deg,#A4184B_0%,#C0205A_55%,#E11D74_100%)] p-3 pt-7 text-center shadow-xl shadow-[#A4184B]/30 transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#A4184B]/45 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40 sm:rounded-3xl sm:p-8"
            >
                <span class="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/15 blur-2xl" aria-hidden="true"></span>

                <span
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white shadow-lg shadow-[#780f33]/25 backdrop-blur transition duration-300 group-hover:scale-105 sm:h-16 sm:w-16 sm:rounded-2xl"
                >
                    <i class="bi bi-heart text-lg sm:text-2xl" aria-hidden="true"></i>
                </span>

                <h2 class="relative mt-3 min-h-[2.1rem] text-xs font-black leading-tight text-white sm:mt-5 sm:min-h-0 sm:text-lg">ورود حامی</h2>
                <p class="relative mt-2 hidden text-sm leading-relaxed text-rose-50/90 sm:block">حامیان کودکان و اعضای هیئت‌مدیره</p>

                <span class="relative mt-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#A4184B] shadow-lg shadow-[#780f33]/25 sm:hidden">
                    <i class="bi bi-arrow-left text-sm" aria-hidden="true"></i>
                </span>
                <span class="relative mt-6 hidden min-h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-[#A4184B] shadow-lg shadow-[#780f33]/15 sm:inline-flex">
                    ورود به پنل
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
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
