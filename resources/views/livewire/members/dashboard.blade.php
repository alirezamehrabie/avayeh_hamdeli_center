@php($firstName = trim($person?->first_name ?? ''))
<div
    dir="rtl"
    class="relative min-h-[100svh] overflow-hidden bg-[#f8fbff]"
>
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#5964AE]/22 blur-3xl sm:h-96 sm:w-96"></div>
    <div class="pointer-events-none absolute -left-24 bottom-[-6rem] h-64 w-64 rounded-full bg-[#7C6BD8]/18 blur-3xl sm:h-80 sm:w-80"></div>

    <main class="relative z-10 mx-auto flex min-h-[100svh] w-full max-w-3xl flex-col px-4 py-6 sm:px-6 sm:py-10">
        {{-- هدر پنل --}}
        <header class="relative overflow-hidden rounded-2xl bg-[linear-gradient(140deg,#3f4a8f_0%,#5964AE_55%,#7C6BD8_125%)] px-4 py-4 text-white shadow-xl shadow-[#5964AE]/25 sm:rounded-3xl sm:px-8 sm:py-6">
            <div class="pointer-events-none absolute inset-0 opacity-[0.14]" style="background-image: radial-gradient(rgba(255,255,255,0.9) 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="relative flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 sm:gap-4">
                    <div class="inline-flex rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-white/50 sm:p-2.5">
                        <img class="h-auto w-9 sm:w-14" src="{{ asset('/images/logo-sm.png') }}" alt="لوگوی آوای همدلی">
                    </div>
                    <div>
                        <p class="text-[10px] font-medium text-white/85 sm:text-xs">پنل اعضای آوای همدلی</p>
                        <h1 class="mt-0.5 text-base font-black leading-tight sm:text-2xl">
                            {{ $firstName !== '' ? $firstName.' جان' : 'عضو گرامی' }}، خوش آمدید
                        </h1>
                    </div>
                </div>
                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur sm:inline-flex">
                    <i class="bi bi-people text-2xl" aria-hidden="true"></i>
                </span>
            </div>
        </header>

        {{-- اطلاعات عضو (تراکم بالا در یک ردیف) --}}
        <section class="mt-3 flex flex-wrap items-center gap-2 sm:mt-4 sm:gap-3">
            <div class="inline-flex min-w-0 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 shadow-sm sm:gap-2 sm:px-3.5 sm:py-2">
                <i class="bi bi-upc-scan shrink-0 text-sm text-[#5964AE]" aria-hidden="true"></i>
                <span class="text-[10px] font-bold text-slate-500 sm:text-xs">کد عضویت</span>
                <span class="text-xs font-black tracking-wide text-slate-900 sm:text-sm" dir="ltr">{{ $person?->person_code }}</span>
            </div>
            <div class="inline-flex min-w-0 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 shadow-sm sm:gap-2 sm:px-3.5 sm:py-2">
                <i class="bi bi-person-vcard shrink-0 text-sm text-[#5964AE]" aria-hidden="true"></i>
                <span class="text-[10px] font-bold text-slate-500 sm:text-xs">کد ملی</span>
                <span class="text-xs font-black tracking-wide text-slate-900 sm:text-sm" dir="ltr">
                    {{ $person ? substr($person->national_id, 0, 4).'•••••'.substr($person->national_id, -2) : '' }}
                </span>
            </div>
        </section>

        {{-- ارتباط با مدیریت --}}
        <a href="{{ route('member.messages') }}"
           class="group relative mt-3 block overflow-hidden rounded-2xl bg-[linear-gradient(135deg,#0b4d75_0%,#1572A1_55%,#36A9DF_135%)] px-4 py-4 text-white shadow-lg shadow-[#1572A1]/30 ring-1 ring-[#1572A1]/40 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#1572A1]/40 focus:outline-none focus-visible:ring-4 focus-visible:ring-[#36A9DF]/50 sm:mt-4 sm:px-6 sm:py-5">
            <div class="pointer-events-none absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(rgba(255,255,255,0.9) 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="pointer-events-none absolute -left-10 -top-16 h-36 w-36 rounded-full bg-white/15 blur-2xl"></div>

            <div class="relative flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/30 sm:h-12 sm:w-12">
                        <i class="bi bi-chat-dots text-xl" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-black sm:text-base">ارتباط با مدیریت</h2>
                        <p class="mt-0.5 text-[11px] font-medium leading-5 text-white/85 sm:text-xs">
                            @if($unreadCount > 0)
                                مدیریت به پیام شما پاسخ داده است.
                            @else
                                ارسال و دریافت پیام محرمانه
                            @endif
                        </p>
                        @if($unreadCount > 0)
                            <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[10px] font-black text-[#0b4d75] shadow-sm sm:text-[11px]">
                                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                                {{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }} پاسخ جدید
                            </span>
                        @endif
                    </div>
                </div>
                <i class="bi bi-arrow-left shrink-0 text-lg transition-transform duration-200 group-hover:-translate-x-1" aria-hidden="true"></i>
            </div>
        </a>

        {{-- راهنمای اولیه --}}
        <section class="mt-3 flex items-center justify-center gap-2 rounded-xl border border-dashed border-[#5964AE]/30 bg-[#f1f1fb]/60 px-3 py-2.5 text-center sm:mt-4 sm:gap-3 sm:px-6 sm:py-4">
            <i class="bi bi-stars shrink-0 text-lg text-[#5964AE] sm:text-2xl" aria-hidden="true"></i>
            <p class="text-[11px] font-bold leading-5 text-slate-700 sm:text-sm">بخش‌های پنل اعضا در حال توسعه است؛ به‌زودی وضعیت خدمات و پرونده از همین پنل قابل پیگیری است.</p>
        </section>

        {{-- خروج --}}
        <footer class="mt-auto pt-5 sm:pt-8">
            <form method="POST" action="{{ route('member.logout') }}">
                @csrf
                <button
                    type="submit"
                    class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-[#5964AE]/25 bg-white px-5 text-sm font-extrabold text-[#5964AE] shadow-sm transition hover:border-[#5964AE]/45 hover:bg-[#f1f1fb] focus:outline-none focus:ring-4 focus:ring-[#5964AE]/20 sm:min-h-[50px]"
                >
                    <i class="bi bi-box-arrow-left text-lg" aria-hidden="true"></i>
                    خروج از حساب
                </button>
            </form>
        </footer>
    </main>
</div>
