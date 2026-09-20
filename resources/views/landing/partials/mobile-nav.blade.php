<!-- کشوی ناوبری موبایل: از سمت راست (RTL) وارد می‌شود -->
<!-- ذخیره‌سازی در سطح body و خارج از هدر: backdrop-blur هدر برای فرزندان fixed
     containing block می‌سازد، پس پنل باید خواهر هدر باشد. -->
<div
    x-cloak
    x-show="mobileNavOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="closeMobileNav()"
    class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm lg:hidden"
    aria-hidden="true"
></div>

<aside
    x-cloak
    id="mobile-nav"
    x-show="mobileNavOpen"
    role="dialog"
    aria-modal="true"
    aria-label="منوی ناوبری"
    @keydown.escape="closeMobileNav()"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    class="fixed inset-y-0 right-0 z-50 flex max-w-xs w-80 flex-col overflow-y-auto bg-white shadow-2xl lg:hidden"
>
    <!-- سربرگ کشو -->
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <a href="#top" @click="closeMobileNav(true)" class="flex min-w-0 items-center gap-2.5" aria-label="آوای همدلی">
            <img
                src="{{ asset('images/logo-sm.png') }}"
                alt="لوگوی آوای همدلی"
                class="h-10 w-10 shrink-0 rounded-xl object-cover shadow-sm"
            >
            <span class="min-w-0">
                <span class="block truncate text-sm font-black text-slate-900">آوای همدلی</span>
                <span class="block truncate text-[10px] leading-4 text-slate-500">مرکز نیکوکاری تخصصی کودکان</span>
            </span>
        </a>
        <button
            type="button"
            x-ref="mobileNavClose"
            @click="closeMobileNav()"
            aria-label="بستن منو"
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#1572A1]/40 hover:text-[#1572A1]"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M6 6l12 12M18 6L6 18"></path>
            </svg>
        </button>
    </div>

    <!-- لینک‌ها -->
    <nav class="flex-1 px-3 py-4" aria-label="ناوبری موبایل">
        <div class="space-y-1">
            @foreach($navLinks as $link)
                <a
                    href="{{ $link['href'] }}"
                    @click="closeMobileNav(true)"
                    class="flex min-h-12 items-center gap-3 rounded-2xl px-4 text-sm font-bold text-slate-700 transition hover:bg-indigo-50 hover:text-[#5964AE]"
                >
                    <i class="bi {{ $link['icon'] }} shrink-0 text-base text-[#5964AE]" aria-hidden="true"></i>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    <!-- اقدامات -->
    <div class="space-y-2 border-t border-slate-100 px-5 py-5">
        <a
            href="#help"
            @click="closeMobileNav(true)"
            class="flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_60%,#A4184B_135%)] px-5 text-sm font-extrabold text-white shadow-md shadow-[#5964AE]/20 transition active:translate-y-px"
        >
            <i class="bi bi-heart-fill" aria-hidden="true"></i>
            همدلی کنید
        </a>
        <a
            href="{{ $loginEntryUrl }}"
            @click="closeMobileNav(true)"
            class="flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 transition hover:border-[#1572A1]/40 hover:text-[#1572A1]"
        >
            <i class="bi bi-box-arrow-in-left" aria-hidden="true"></i>
            {{ $loginEntryLabel }}
        </a>
    </div>
</aside>
