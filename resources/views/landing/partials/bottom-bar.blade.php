<!-- نوار اقدام پایین موبایل -->
<div
    x-cloak
    x-show="floatingBarVisible"
    x-transition.opacity.duration.300ms
    class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden"
    role="region"
    aria-label="اقدام سریع"
>
    <div class="mx-auto flex max-w-md items-center gap-3">
        <a
            href="#help"
            class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_58%,#A4184B_135%)] px-4 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-[#5964AE]/25 active:translate-y-px"
        >
            <i class="bi bi-heart-fill" aria-hidden="true"></i>
            همدلی کن
        </a>
        <a
            href="#contact"
            class="inline-flex h-14 w-14 items-center justify-center rounded-xl border border-[#1572A1]/25 bg-[#1572A1]/10 text-[#1572A1]"
            aria-label="تماس با ما"
        >
            <i class="bi bi-telephone text-lg" aria-hidden="true"></i>
        </a>
    </div>
</div>