@php
    // نقطه ورود به صفحۀ مجله؛ پس از راه‌اندازی صفحۀ مجله فقط همین مسیر عوض می‌شود.
    $magazineUrl = '/magazine';
@endphp

@once
    <style>
        @keyframes magazine-shine {
            0% { transform: translateX(-120%) skewX(-14deg); }
            18%, 100% { transform: translateX(300%) skewX(-14deg); }
        }
        .magazine-shine {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 55%;
            pointer-events: none;
            background: linear-gradient(
                105deg,
                transparent 0%,
                rgba(255, 255, 255, 0.20) 42%,
                rgba(255, 255, 255, 0.38) 50%,
                rgba(255, 255, 255, 0.20) 58%,
                transparent 100%
            );
            animation: magazine-shine 9s ease-in-out 2.5s infinite;
            /* در دورۀ تأخیر ۲٫۵ ثانیه‌ای، پرتو بیرون کارت (framing 0%) نگه داشته
               می‌شود تا نورِ منجمد وسط کارت دیده نشود. */
            animation-fill-mode: backwards;
            will-change: transform;
        }
        @media (prefers-reduced-motion: reduce) {
            .magazine-shine { display: none; }
        }
    </style>
@endonce

<!-- بخش مجلۀ همدلی: کارت افقی و کلیک‌پذیر به عنوان نقطه ورود -->
<section id="magazine" class="landing-section bg-white pb-8 pt-0 sm:pb-16" aria-labelledby="magazine-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <a
            href="{{ $magazineUrl }}"
            class="group relative block overflow-hidden rounded-2xl shadow-[0_6px_24px_rgba(21,114,161,0.28)] transition duration-500 ease-out hover:-translate-y-0.5 hover:shadow-[0_14px_36px_rgba(21,114,161,0.38)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1572A1] sm:rounded-[1.75rem]"
            style="background: linear-gradient(112deg, #1572A1 0%, #249CCB 55%, #4FB9E8 100%);"
        >
            <!-- نور سبکی که هر چند ثانیه روی کارت برق می‌زند -->
            <span class="magazine-shine" aria-hidden="true"></span>

            <div class="relative z-10 flex flex-row items-center gap-3 py-3 pl-4 pr-5 text-white sm:gap-6 sm:py-7 sm:pl-8 sm:pr-10">
                <!-- کاشی نشان -->
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[1.15rem] bg-white/15 text-white sm:h-16 sm:w-16 sm:rounded-[1.6rem] lg:h-[4.5rem] lg:w-[4.5rem]">
                    <svg viewBox="0 0 24 24" class="h-[1.65rem] w-[1.65rem] sm:h-9 sm:w-9 lg:h-10 lg:w-10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6.8 3h10.4A2.8 2.8 0 0 1 20 5.8v12.4A2.8 2.8 0 0 1 17.2 21H6.8A2.8 2.8 0 0 1 4 18.2V5.8A2.8 2.8 0 0 1 6.8 3Z"/>
                        <path d="M6.8 8.4h10.4"/>
                        <path d="M6.8 12.8h10.4M6.8 16.8h6.4"/>
                        <circle cx="17.1" cy="16.8" r="1.1" fill="currentColor" stroke="none"/>
                    </svg>
                </span>

                <span class="min-w-0 flex-1">
                    <h2 id="magazine-title" class="truncate text-base font-black leading-6 text-white sm:text-2xl sm:leading-9 lg:text-[1.75rem]">
                        مجلۀ همدلی
                    </h2>
                    <p class="mt-0.5 flex items-center gap-1.5 text-[11px] leading-5 text-white/85 sm:mt-1.5 sm:text-sm sm:leading-6">
                        <span class="hidden shrink-0 font-bold text-white sm:inline">نشریۀ مرکز</span>
                        <span class="hidden h-3 w-px shrink-0 bg-white/40 sm:inline" aria-hidden="true"></span>
                        <span class="truncate">روایت‌ها، گزارش‌ها و نوشته‌های کودکان</span>
                    </p>
                </span>

                <!-- دکورۀ ورود -->
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-[#1572A1] shadow-sm transition duration-300 group-hover:bg-sky-50 sm:h-auto sm:w-auto sm:min-h-12 sm:rounded-2xl sm:px-6 sm:py-3">
                    <span class="hidden text-sm font-extrabold leading-5 sm:inline">مشاهدۀ مجله</span>
                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 sm:h-[18px] sm:w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5.4"/>
                        <path d="m11.4 5.6-6 6.4 6 6.4"/>
                    </svg>
                </span>
            </div>
        </a>
    </div>
</section>
