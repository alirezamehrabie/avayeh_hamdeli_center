@php
    // نقطه ورود به صفحۀ مجله؛ پس از راه‌اندازی صفحۀ مجله فقط همین مسیر عوض می‌شود.
    $magazineUrl = '/magazine';
@endphp

<!-- بخش مجلۀ همدلی: کارت افقی و کلیک‌پذیر به عنوان نقطه ورود -->
<section id="magazine" class="landing-section bg-white pb-8 pt-0 sm:pb-16" aria-labelledby="magazine-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <a
            href="{{ $magazineUrl }}"
            class="group block overflow-hidden rounded-2xl bg-[linear-gradient(140deg,#f2f9fd_0%,#ffffff_46%,#fdf2f6_100%)] shadow-[0_2px_10px_rgba(89,100,174,0.08)] ring-1 ring-slate-100 transition duration-500 ease-out hover:-translate-y-0.5 hover:shadow-[0_10px_28px_rgba(89,100,174,0.16)] hover:ring-[#5964AE]/25 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1572A1] sm:rounded-3xl"
            aria-label="ورود به مجلۀ همدلی"
        >
            <div class="flex flex-row items-center gap-3 px-3 py-3 text-right sm:gap-6 sm:px-8 sm:py-9">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#1572A1_0%,#5964AE_55%,#A4184B_130%)] text-white shadow-md shadow-[#5964AE]/25 sm:h-20 sm:w-20 sm:rounded-[1.75rem]">
                    <i class="bi bi-journal-bookmark-fill text-lg sm:text-3xl" aria-hidden="true"></i>
                </span>

                <span id="magazine-title" class="min-w-0 flex-1">
                    <span class="mt-0.5 block truncate text-base font-black leading-6 text-slate-900 sm:mt-0 sm:text-2xl lg:text-3xl">
                        مجلۀ همدلی
                    </span>
                    <span class="mt-0.5 block truncate text-[11px] leading-5 text-slate-500 sm:mt-2 sm:text-sm sm:leading-7">
                        روایت‌ها، گزارش‌ها و نوشته‌های کودکان؛ هر شماره یک خواندنی.
                    </span>
                </span>

                <span class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_58%,#A4184B_135%)] text-white shadow-md shadow-[#5964AE]/25 transition duration-300 group-hover:shadow-lg group-hover:shadow-[#1572A1]/30 max-sm:h-9 max-sm:w-11 max-sm:rounded-xl sm:min-h-12 sm:px-6 sm:py-3">
                    <span class="hidden text-sm font-extrabold sm:inline sm:text-base">مشاهده مجله</span>
                    <i class="bi bi-chevron-left text-lg sm:transition sm:duration-300 group-hover:-translate-x-1" aria-hidden="true"></i>
                </span>
            </div>
        </a>
    </div>
</section>
