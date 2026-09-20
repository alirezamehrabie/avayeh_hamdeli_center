@extends('layouts.landing')

@php($title = 'انتخاب نوع ورود | آوای همدلی')

@section('content')
<style>
    /* نقشه خاتمِ هشت‌پر — فقط پس‌زمینهٔ صفحه */
    .khatam-sky {
        background-image: url('{{ asset('images/landing/pattern-khatam-sky.svg') }}');
        background-size: 120px 120px;
    }

    /* کاشی آیکون یکسان برای همهٔ کارت‌ها — squircle مدرن */
    .icon-tile {
        display: flex;
        height: 3rem;
        width: 3rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 1.05rem;
    }

    @media (min-width: 640px) {
        .icon-tile {
            height: 3.5rem;
            width: 3.5rem;
        }
    }
</style>

<!-- صفحه انتخاب نوع ورود -->
<section class="relative flex min-h-screen flex-col overflow-hidden bg-[#f4f9fd] px-4 py-8 sm:px-6 sm:py-14">
    <!-- گرادیان ملایمِ برند (هماهنگ با هیرو) -->
    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(238,248,253,0.9)_0%,rgba(255,255,255,0.85)_45%,rgba(252,241,246,0.9)_100%)]" aria-hidden="true"></div>

    <!-- بلاب‌های رنگی نرم -->
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#36A9DF]/22 blur-3xl sm:h-96 sm:w-96" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-24 bottom-[-6rem] h-64 w-64 rounded-full bg-[#D4205F]/12 blur-3xl sm:h-80 sm:w-80" aria-hidden="true"></div>
    <div class="pointer-events-none absolute left-1/2 top-1/3 h-56 w-56 -translate-x-1/2 rounded-full bg-[#5964AE]/12 blur-3xl sm:h-72 sm:w-72" aria-hidden="true"></div>

    <!-- نقش هندسی خاتم روی کل صفحه -->
    <div class="khatam-sky pointer-events-none absolute inset-0 opacity-[0.05]" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto flex w-full max-w-5xl flex-1 flex-col">
        <!-- نوار بالا: بازگشت + برند -->
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <a
                href="{{ route('landing.preview') }}"
                class="inline-flex min-h-10 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-xl border border-slate-200 bg-white/80 px-3 py-2 text-xs font-bold text-slate-700 backdrop-blur transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:min-h-0 sm:gap-2 sm:px-4 sm:py-2.5 sm:text-sm"
            >
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                بازگشت به خانه
            </a>
            <a href="{{ route('landing.preview') }}" class="flex min-w-0 items-center gap-2 sm:gap-2.5" aria-label="آوای همدلی">
                <img
                    src="{{ asset('images/logo-sm.png') }}"
                    alt="لوگوی آوای همدلی"
                    class="h-8 w-8 shrink-0 rounded-lg object-cover shadow-sm sm:h-10 sm:w-10 sm:rounded-xl"
                >
                <span class="min-w-0">
                    <span class="block truncate text-xs font-black leading-5 text-slate-900 sm:text-sm sm:leading-5">آوای همدلی</span>
                    <span class="block truncate text-[9px] leading-3.5 text-slate-500 sm:text-[10px] sm:leading-4">مرکز نیکوکاری تخصصی کودکان</span>
                </span>
            </a>
        </div>

        <!-- محتوای وسط‌چین‌شده: عنوان + کارت‌ها -->
        <div class="flex flex-1 flex-col justify-center pb-10 sm:pb-20">
        <!-- عنوان -->
        <div class="mt-2 text-center sm:mt-0" data-reveal>
            <!-- حدیث -->
            <div class="flex items-center justify-center gap-2 sm:gap-3">
                <span class="h-px w-8 bg-gradient-to-l from-transparent to-[#1572A1]/50 sm:w-16" aria-hidden="true"></span>
                <p class="inline-flex flex-col items-center gap-0.5">
                    <span class="text-[13px] font-bold text-[#1572A1] sm:text-base">«خَیرُ النّاسِ مَن یَنفَعُ النّاسَ»</span>
                    <span class="text-[10px] text-slate-500 sm:text-[11px]">بهترین مردم، کسی است که به مردم سود برساند</span>
                </p>
                <span class="h-px w-8 bg-gradient-to-r from-transparent to-[#1572A1]/50 sm:w-16" aria-hidden="true"></span>
            </div>

            <span
                class="mt-3 inline-flex items-center gap-2 rounded-full border border-[#1572A1]/25 bg-white/70 px-4 py-1.5 text-xs font-bold text-[#1572A1] backdrop-blur sm:mt-5"
            >
                <span class="h-2 w-2 rotate-45 bg-[#5964AE]" aria-hidden="true"></span>
                ورود به حساب کاربری
            </span>

            <h1 class="mt-5 hidden text-2xl font-black leading-[1.4] text-slate-900 sm:block sm:text-4xl sm:leading-[1.35]">
                نوع
                <span class="bg-gradient-to-l from-[#1572A1] via-[#5964AE] to-[#A4184B] bg-clip-text text-transparent">
                    ورود
                </span>
                خود را انتخاب کنید
            </h1>

            <p class="mx-auto mt-4 hidden max-w-xl text-sm leading-7 text-slate-600 sm:block sm:text-base sm:leading-8">
                برای ادامه، مشخص کنید که به‌عنوان کدام بخش از خانوادهٔ آوای همدلی می‌خواهید وارد شوید.
            </p>
        </div>

        <!-- کارت‌های fill رنگی: موبایل لیست عمودی، دسکتاپ سه‌ستونه -->
        <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-10 sm:grid-cols-3 sm:gap-5 lg:gap-6" data-reveal>
            <!-- ورود پرسنل -->
            <a
                href="{{ route('login') }}"
                class="group relative flex flex-row items-center gap-4 overflow-hidden rounded-2xl border-2 border-[#1572A1] bg-[linear-gradient(155deg,#0f5a80_0%,#1572A1_55%,#36A9DF_100%)] p-4 text-right shadow-lg shadow-[#1572A1]/30 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#1572A1]/45 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40 sm:flex-col sm:gap-0 sm:rounded-3xl sm:p-8 sm:text-center"
            >
                <span class="pointer-events-none absolute -left-8 -top-10 h-28 w-28 rounded-full bg-white/12 blur-2xl" aria-hidden="true"></span>

                <span class="icon-tile relative bg-white text-[#1572A1] shadow-lg shadow-[#0b4d75]/25 ring-1 ring-black/5 transition duration-300 group-hover:-rotate-6 group-hover:scale-105">
                    <i class="bi bi-person-badge-fill text-2xl" aria-hidden="true"></i>
                </span>

                <span class="relative min-w-0 flex-1 sm:mt-5">
                    <h2 class="truncate text-lg font-black leading-8 text-white sm:text-xl">ورود پرسنل</h2>
                    <p class="mt-0.5 truncate text-[11px] leading-5 text-sky-50/90 sm:mt-2 sm:text-sm sm:leading-relaxed">مدیران، مددکاران و اپراتورهای مرکز</p>
                </span>

                <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/95 text-[#1572A1] shadow-md transition group-hover:bg-white sm:hidden">
                    <i class="bi bi-arrow-left text-sm" aria-hidden="true"></i>
                </span>
                <span class="relative mt-6 hidden min-h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-[#1572A1] shadow-lg shadow-[#0b4d75]/15 transition group-hover:gap-3 sm:inline-flex">
                    ورود به پنل
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </span>
            </a>

            <!-- ورود اعضا -->
            <a
                href="{{ route('member.login') }}"
                class="group relative flex flex-row items-center gap-4 overflow-hidden rounded-2xl border-2 border-[#0e7a55] bg-[linear-gradient(155deg,#0b5d46_0%,#0e7a55_55%,#1fa06f_100%)] p-4 text-right shadow-lg shadow-[#0e7a55]/30 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#0e7a55]/45 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40 sm:flex-col sm:gap-0 sm:rounded-3xl sm:p-8 sm:text-center"
            >
                <span class="pointer-events-none absolute -left-8 -top-10 h-28 w-28 rounded-full bg-white/12 blur-2xl" aria-hidden="true"></span>

                <span class="icon-tile relative bg-white text-[#0e7a55] shadow-lg shadow-[#08402f]/25 ring-1 ring-black/5 transition duration-300 group-hover:-rotate-6 group-hover:scale-105">
                    <i class="bi bi-people-fill text-2xl" aria-hidden="true"></i>
                </span>

                <span class="relative min-w-0 flex-1 sm:mt-5">
                    <h2 class="truncate text-lg font-black leading-8 text-white sm:text-xl">ورود اعضا</h2>
                    <p class="mt-0.5 truncate text-[11px] leading-5 text-emerald-50/90 sm:mt-2 sm:text-sm sm:leading-relaxed">خانواده‌های تحت پوشش مرکز</p>
                </span>

                <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/95 text-[#0e7a55] shadow-md transition group-hover:bg-white sm:hidden">
                    <i class="bi bi-arrow-left text-sm" aria-hidden="true"></i>
                </span>
                <span class="relative mt-6 hidden min-h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-[#0e7a55] shadow-lg shadow-[#08402f]/15 transition group-hover:gap-3 sm:inline-flex">
                    ورود به پنل
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </span>
            </a>

            <!-- ورود حامی (به‌زودی) -->
            <button
                type="button"
                aria-disabled="true"
                class="group relative flex cursor-not-allowed flex-row items-center gap-4 overflow-hidden rounded-2xl border-2 border-[#b01a51] bg-[linear-gradient(155deg,#b01a51_0%,#D4205F_55%,#f0558f_100%)] p-4 text-right opacity-95 shadow-lg shadow-[#D4205F]/25 sm:flex-col sm:gap-0 sm:rounded-3xl sm:p-8 sm:text-center"
            >
                <span class="pointer-events-none absolute -left-8 -top-10 h-28 w-28 rounded-full bg-white/12 blur-2xl" aria-hidden="true"></span>

                <span class="absolute left-3 top-3 z-10 hidden items-center gap-1 rounded-full bg-white/90 px-2.5 py-0.5 text-[10px] font-black text-[#D4205F] shadow sm:inline-flex" aria-hidden="true">
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                    به‌زودی
                </span>

                <span class="icon-tile relative bg-white/95 text-[#D4205F] shadow-lg shadow-[#8d1244]/20 ring-1 ring-black/5">
                    <i class="bi bi-heart-fill text-2xl" aria-hidden="true"></i>
                </span>

                <span class="relative min-w-0 flex-1 sm:mt-5">
                    <h2 class="truncate text-lg font-black leading-8 text-white sm:text-xl">ورود حامی</h2>
                    <p class="mt-0.5 truncate text-[11px] leading-5 text-rose-50/90 sm:mt-2 sm:text-sm sm:leading-relaxed">
                        <span class="sm:hidden">این بخش به‌زودی فعال می‌شود</span>
                        <span class="hidden sm:inline">حامیان و اعضای هیئت‌مدیره</span>
                    </p>
                </span>

                <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/85 text-[#D4205F] shadow-md sm:hidden">
                    <i class="bi bi-hourglass-split text-sm" aria-hidden="true"></i>
                </span>
                <span class="relative mt-6 hidden min-h-11 items-center gap-2 rounded-xl bg-white/85 px-5 text-sm font-extrabold text-[#D4205F] shadow-lg shadow-[#8d1244]/15 sm:inline-flex">
                    به‌زودی
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                </span>
            </button>
        </div>

        <!-- جداکننده + یادآوری -->
        <div class="mt-6 text-center sm:mt-14" data-reveal>
            <div class="flex items-center justify-center gap-3" aria-hidden="true">
                <span class="h-px w-14 bg-gradient-to-l from-transparent to-[#1572A1]/40 sm:w-24"></span>
                <span class="h-2 w-2 rotate-45 bg-[#5964AE]/70"></span>
                <span class="h-px w-14 bg-gradient-to-r from-transparent to-[#1572A1]/40 sm:w-24"></span>
            </div>
            <p class="mt-4 text-center text-xs leading-6 text-slate-500">
                <i class="bi bi-shield-check ml-1 align-middle text-[#1572A1]" aria-hidden="true"></i>
                در صورت نیاز به راهنمایی، با شماره
                <a href="tel:+989136476949" class="font-bold text-[#1572A1]" dir="ltr">۰۹۱۳ ۶۴۷ ۶۹۴۹</a>
                تماس بگیرید.
            </p>
        </div>
        </div>
        <!-- /محتوای وسط‌چین‌شده -->
    </div>
</section>
@endsection
