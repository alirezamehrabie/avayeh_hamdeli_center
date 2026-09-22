@extends('layouts.landing')

@php
    $title = 'تمامی خدمات | آوای همدلی';
    $servicesList = array_values($services ?? []);
    $titlesJson = json_encode(array_column($servicesList, 'title'), JSON_UNESCAPED_UNICODE);
@endphp

@section('content')
    {{-- هدر صفحه --}}
    <header
        x-cloak
        x-data="{
            scrolled: false,
            updateScroll() {
                this.scrolled = window.scrollY > 8;
            }
        }"
        x-init="updateScroll()"
        @scroll.window.passive="updateScroll()"
        class="fixed inset-x-0 top-0 z-40 transition-all duration-300"
        :class="scrolled ? 'bg-white/95 shadow-sm backdrop-blur-md' : 'bg-white/70 backdrop-blur-sm'"
    >
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-3 sm:gap-4 sm:px-6">
            {{-- گروه راست (در RTL): بازگشت + برند --}}
            <div class="flex min-w-0 items-center gap-1.5 sm:gap-2.5">
                <a
                    href="{{ route('landing.preview') }}#services"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:h-11 sm:w-11"
                    aria-label="بازگشت به صفحه اصلی"
                    title="بازگشت به صفحه اصلی"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5.4"/><path d="m11.4 5.6-6 6.4 6 6.4"/>
                    </svg>
                </a>

                <a href="{{ route('landing.preview') }}" class="flex min-w-0 items-center gap-2 sm:gap-2.5" aria-label="آوای همدلی">
                    <img
                        src="{{ asset('images/logo-sm.png') }}"
                        alt="لوگوی آوای همدلی"
                        class="h-8 w-8 shrink-0 rounded-lg object-cover shadow-sm sm:h-10 sm:w-10 sm:rounded-xl"
                    >
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-black leading-5 text-slate-900 sm:text-base sm:leading-6">آوای همدلی</span>
                        <span class="block truncate text-[9px] leading-3.5 text-slate-500 sm:text-[11px] sm:leading-4">لیست تمامی خدمات</span>
                    </span>
                </a>
            </div>

            {{-- اقدامات --}}
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <a
                    href="{{ route('landing.preview') }}#services"
                    class="inline-flex min-h-10 items-center gap-1.5 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1] sm:min-h-0 sm:gap-2 sm:px-4 sm:py-2.5 sm:text-sm"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>صفحه اصلی</span>
                </a>
            </div>
        </div>
    </header>

    {{-- بدنه اصلی صفحه --}}
    <main class="min-h-screen bg-[#f8fafc] pb-16 pt-24 sm:pb-24 sm:pt-28">
        <div
            x-data="{
                search: '',
                matches(title) {
                    if (! this.search.trim()) return true;
                    return title.toLowerCase().includes(this.search.trim().toLowerCase());
                }
            }"
            class="mx-auto max-w-4xl px-4 sm:px-6"
        >
            {{-- تیتر و معرفی بخش --}}
            <div class="text-center" data-reveal>
                <div class="inline-flex items-center gap-1.5 rounded-full border border-[#1572A1]/20 bg-[#1572A1]/5 px-3.5 py-1 text-xs font-semibold text-[#1572A1]">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#1572A1]" aria-hidden="true"></span>
                    <span>خدمات مرکز آوای همدلی</span>
                </div>

                <h1 class="mt-3 text-2xl font-black text-slate-900 sm:text-3xl sm:leading-tight">
                    تمامی خدمات مرکز
                </h1>

                <p class="mx-auto mt-2 max-w-xl text-xs leading-6 text-slate-500 sm:text-sm sm:leading-7">
                    فهرست کلیه برنامه‌ها و خدمات حمایتی، آموزشی، معیشتی و درمانی ارائه شده به کودکان و خانواده‌های تحت پوشش
                </p>
            </div>

            {{-- نوار ابزار: شمارش و جستجوی آنی --}}
            <div class="mt-8 flex flex-col gap-3 sm:mt-10 sm:flex-row sm:items-center sm:justify-between" data-reveal>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-700 sm:text-sm">لیست خدمات</span>
                    <span class="inline-flex items-center rounded-full bg-slate-200/80 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                        {{ count($servicesList) }} خدمت
                    </span>
                </div>

                {{-- فیلد جستجوی زنده --}}
                <div class="relative w-full sm:w-72">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="جستجوی عنوان خدمت..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-9 pl-3 text-xs text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-[#1572A1] focus:outline-none focus:ring-2 focus:ring-[#1572A1]/20 sm:text-sm"
                        aria-label="جستجوی خدمت"
                    >
                    <button
                        type="button"
                        x-show="search.length > 0"
                        @click="search = ''"
                        x-cloak
                        class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 hover:text-slate-600"
                        aria-label="پاک کردن جستجو"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- نمایش لیستی آیتم‌ها (List View) --}}
            <ul class="mt-4 space-y-3 sm:mt-5 sm:space-y-3.5" data-reveal>
                @foreach($servicesList as $index => $service)
                    <li
                        x-show="matches(@js($service['title']))"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-2.5 shadow-[0_1px_3px_rgba(15,23,42,0.03)] transition-all duration-300 hover:-translate-y-0.5 hover:border-[#1572A1]/40 hover:shadow-[0_8px_20px_rgba(21,114,161,0.08)] sm:gap-4 sm:p-3.5"
                    >
                        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                            {{-- تصویر خدمت در حالت لیستی --}}
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-slate-50 border border-slate-100/80 p-1 transition duration-500 ease-out group-hover:scale-[1.04] sm:h-18 sm:w-18 sm:rounded-2xl sm:p-1.5">
                                <img
                                    src="{{ $service['image'] }}"
                                    alt="{{ $service['title'] }}"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-full w-full rounded-lg sm:rounded-xl object-cover"
                                >
                            </div>

                            {{-- مشخصات خدمت --}}
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-5 items-center justify-center rounded-md bg-slate-100 px-1.5 text-[10px] font-bold text-slate-500 transition-colors group-hover:bg-[#1572A1]/10 group-hover:text-[#1572A1] sm:h-6 sm:px-2 sm:text-xs">
                                        {{ sprintf('%02d', $index + 1) }}
                                    </span>
                                    <h2 class="truncate text-xs font-bold text-slate-900 transition-colors group-hover:text-[#1572A1] sm:text-base">
                                        {{ $service['title'] }}
                                    </h2>
                                </div>
                                <p class="mt-1 hidden text-xs text-slate-400 sm:block">
                                    خدمت حمایتی و اختصاصی مرکز نیکوکاری تخصصی آوای همدلی
                                </p>
                            </div>
                        </div>

                        {{-- نشان فعال بودن --}}
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 sm:text-xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                <span>فعال</span>
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- پیام عدم یافتن نتیجه در جستجو --}}
            <div
                x-cloak
                x-show="search.trim() !== '' && ! ({{ $titlesJson }}).some(t => t.toLowerCase().includes(search.trim().toLowerCase()))"
                class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center sm:p-12"
            >
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>
                <p class="mt-3 text-sm font-bold text-slate-700">خدمتی با این عنوان پیدا نشد</p>
                <p class="mt-1 text-xs text-slate-400">لطفاً املای عبارت را بررسی کنید یا عبارت دیگری را جستجو نمایید.</p>
                <button
                    type="button"
                    @click="search = ''"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-[#1572A1]/40 hover:text-[#1572A1]"
                >
                    مشاهده تمامی خدمات
                </button>
            </div>

            {{-- پیوند بازگشت به بخش خدمات صفحه اصلی --}}
            <div class="mt-10 flex justify-center sm:mt-12" data-reveal>
                <a
                    href="{{ route('landing.preview') }}#services"
                    class="group inline-flex items-center gap-2 rounded-full border border-slate-200/90 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#1572A1]/40 hover:text-[#1572A1] hover:shadow-md sm:px-6 sm:py-3 sm:text-sm"
                >
                    <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                    <span>بازگشت به صفحه اصلی</span>
                </a>
            </div>
        </div>
    </main>

    {{-- پابرگ سایت --}}
    @include('landing.partials.footer')
@endsection
