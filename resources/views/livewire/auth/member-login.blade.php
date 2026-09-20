<div
    dir="rtl"
    class="relative min-h-[100svh] overflow-hidden bg-[#f8fbff]"
    style="margin-inline: calc(50% - 50vw); width: 100vw;"
>
    {{-- هاله‌های نوری پس‌زمینه (سرداب members / بنفش-نیلویی) --}}
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#5964AE]/22 blur-3xl sm:h-96 sm:w-96"></div>
    <div class="pointer-events-none absolute -left-24 top-1/4 h-64 w-64 rounded-full bg-[#7C6BD8]/18 blur-3xl sm:h-80 sm:w-80"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-[radial-gradient(circle_at_50%_100%,rgba(89,100,174,0.18),transparent_58%)]"></div>

    <main class="relative z-10 flex min-h-[100svh] items-center justify-center px-0 py-0 sm:px-6 sm:py-8 lg:px-8">
        <div class="grid min-h-[100svh] w-full max-w-md overflow-hidden bg-white shadow-[0_24px_80px_rgba(89,100,174,0.16)] ring-1 ring-slate-200/70 sm:min-h-0 sm:max-w-lg sm:rounded-[1.75rem] lg:max-w-5xl lg:grid-cols-[0.92fr_1.08fr]">

            {{-- پنل برند (دسکتاپ) --}}
            <aside class="relative hidden overflow-hidden lg:block">
                <div class="absolute inset-0 bg-[linear-gradient(150deg,#3f4a8f_0%,#5964AE_52%,#7C6BD8_118%)]"></div>
                <div class="absolute inset-0 opacity-[0.16]" style="background-image: radial-gradient(rgba(255,255,255,0.9) 1px, transparent 1px); background-size: 22px 22px;"></div>
                <div class="pointer-events-none absolute -left-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-[#6B5FC9]/35 blur-3xl"></div>
                <div class="pointer-events-none absolute -left-16 top-16 h-56 w-56 rounded-full border border-white/15"></div>
                <div class="pointer-events-none absolute -left-8 top-24 h-56 w-56 rounded-full border border-white/10"></div>

                <div class="relative flex h-full min-h-[560px] flex-col justify-between p-10 text-white">
                    <div>
                        <div class="inline-flex rounded-2xl bg-white p-4 shadow-2xl shadow-slate-900/25 ring-1 ring-white/60">
                            <img
                                class="h-auto w-44"
                                src="{{ asset('/images/logo-sm.png') }}"
                                alt="لوگوی آوای همدلی"
                            >
                        </div>

                        <div class="mt-12 max-w-sm">
                            <p class="text-xs font-medium tracking-wide text-white/85">مرکز نیکوکاری تخصصی کودکان</p>
                            <h1 class="mt-3 text-3xl font-black leading-[2.75rem]">
                                پنل اعضای آوای همدلی
                            </h1>
                            <div class="mt-5 h-1 w-16 rounded-full bg-white/45"></div>
                            <p class="mt-5 text-sm leading-8 text-white/90">
                                ویژه خانواده‌های تحت پوشش مرکز؛ همراهی ما را در یک فضای امن پیگیری کنید.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl bg-white/12 px-4 py-4 ring-1 ring-white/25 backdrop-blur-sm">
                        <div class="flex items-start gap-3">
                            <i class="bi bi-shield-check shrink-0 text-2xl text-white" aria-hidden="true"></i>
                            <div>
                                <p class="text-sm font-black text-white">ورود محرمانه اعضا</p>
                                <p class="mt-1 text-xs font-medium leading-6 text-white/90">
                                    کد ملی و کد عضویت خود را تنها در این سامانه وارد کنید و هرگز در اختیار دیگران قرار ندهید.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- ستون فرم --}}
            <section class="relative flex flex-col">
                {{-- نوار برند موبایل/PWA --}}
                <div class="relative overflow-hidden bg-[linear-gradient(140deg,#3f4a8f_0%,#5964AE_55%,#7C6BD8_125%)] px-6 pb-16 pt-9 text-white lg:hidden">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.14]" style="background-image: radial-gradient(rgba(255,255,255,0.9) 1px, transparent 1px); background-size: 20px 20px;"></div>
                    <div class="pointer-events-none absolute -left-10 -top-14 h-44 w-44 rounded-full bg-white/12 blur-2xl"></div>
                    <div class="relative flex flex-col items-center text-center">
                        <div class="inline-flex rounded-2xl bg-white p-3 shadow-xl shadow-slate-900/20 ring-1 ring-white/50">
                            <img
                                class="h-auto w-20"
                                src="{{ asset('/images/logo-sm.png') }}"
                                alt="لوگوی آوای همدلی"
                            >
                        </div>
                        <h2 class="mt-4 text-xl font-black">ورود اعضا</h2>
                        <p class="mt-1.5 text-xs font-medium leading-6 text-white/90">
                            ویژه خانواده‌های تحت پوشش مرکز آوای همدلی
                        </p>
                    </div>
                </div>

                {{-- ! لازم است چون Bootstrap کلاس .px-4 را با !important (24px) تعریف می‌کند و padding واکنش‌گرا را می‌پوشاند --}}
                <div class="relative -mt-9 flex flex-1 flex-col rounded-t-[1.75rem] bg-white px-4 pb-7 pt-8 shadow-[0_-14px_40px_rgba(89,100,174,0.14)] sm:!px-8 sm:pb-9 lg:rounded-none lg:!px-12 lg:py-14 lg:shadow-none">
                    {{-- هدر فرم (دسکتاپ) --}}
                    <header class="mb-6 hidden lg:mt-3 lg:mb-10 lg:block">
                        <p class="text-xs font-medium text-[#5964AE]">مرکز نیکوکاری تخصصی کودکان آوای همدلی</p>
                        <h2 class="mt-2 text-3xl font-black text-slate-900 lg:mt-3">
                            ورود اعضا
                        </h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600 lg:mt-3">
                            برای ورود به پنل اعضا، کد ملی و کد عضویت خود را وارد کنید.
                        </p>
                    </header>

                    <form class="space-y-3.5 sm:space-y-5" wire:submit.prevent="login" autocomplete="on" novalidate>
                        <div>
                            <label for="nationalId" class="mb-1.5 block text-sm font-bold text-slate-700 sm:mb-2">
                                کد ملی
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex w-10 items-center justify-center text-[#5964AE] sm:w-12">
                                    <i class="bi bi-person-vcard text-lg" aria-hidden="true"></i>
                                </span>
                                <input
                                    wire:model="nationalId"
                                    id="nationalId"
                                    type="text"
                                    required
                                    autocomplete="off"
                                    inputmode="numeric"
                                    maxlength="10"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    dir="ltr"
                                    lang="en"
                                    wire:loading.attr="disabled"
                                    wire:target="login"
                                    class="block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-10 text-center text-base tracking-widest text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#5964AE] focus:bg-white focus:ring-4 focus:ring-[#5964AE]/20 disabled:cursor-wait disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-500 sm:min-h-[54px] sm:pr-12"
                                    placeholder="۱۰ رقم کد ملی"
                                    aria-describedby="@error('nationalId') nationalId-error @enderror"
                                    aria-invalid="@error('nationalId') true @else false @enderror"
                                >
                            </div>
                            @error('nationalId')
                                <span id="nationalId-error" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600" role="alert" aria-live="assertive">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label for="personCode" class="mb-1.5 block text-sm font-bold text-slate-700 sm:mb-2">
                                کد عضویت (کد مددجو)
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex w-10 items-center justify-center text-[#5964AE] sm:w-12">
                                    <i class="bi bi-upc-scan text-lg" aria-hidden="true"></i>
                                </span>
                                <input
                                    wire:model="personCode"
                                    id="personCode"
                                    type="text"
                                    required
                                    autocomplete="off"
                                    inputmode="numeric"
                                    maxlength="8"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    dir="ltr"
                                    lang="en"
                                    wire:loading.attr="disabled"
                                    wire:target="login"
                                    class="block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-10 text-center text-base tracking-widest text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#5964AE] focus:bg-white focus:ring-4 focus:ring-[#5964AE]/20 disabled:cursor-wait disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-500 sm:min-h-[54px] sm:pr-12"
                                    placeholder="کد عضویت درج‌شده در پرونده"
                                    aria-describedby="@error('personCode') personCode-error @enderror"
                                    aria-invalid="@error('personCode') true @else false @enderror"
                                >
                            </div>
                            @error('personCode')
                                <span id="personCode-error" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600" role="alert" aria-live="assertive">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        @error('auth')
                            <div
                                id="member-login-auth-error"
                                class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm font-semibold leading-6 text-rose-700"
                                role="alert"
                                aria-live="assertive"
                            >
                                <i class="bi bi-exclamation-triangle mt-0.5 shrink-0 text-base" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="login"
                            class="group flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#3f4a8f_0%,#5964AE_58%,#7C6BD8_130%)] px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#5964AE]/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#5964AE]/35 focus:outline-none focus:ring-4 focus:ring-[#5964AE]/30 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 sm:min-h-[54px]"
                        >
                            <span wire:loading.remove wire:target="login" class="inline-flex items-center gap-2">
                                ورود به پنل اعضا
                                <i class="bi bi-arrow-left-short text-xl transition group-hover:-translate-x-0.5" aria-hidden="true"></i>
                            </span>
                            <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                در حال بررسی اطلاعات...
                            </span>
                        </button>
                    </form>

                    <div class="mt-5 rounded-2xl border border-slate-200/80 bg-slate-50/70 px-2.5 py-3 text-center text-[11px] leading-5 text-slate-600 sm:mt-7 sm:px-3 sm:text-xs sm:leading-6">
                        <p>در صورت فراموشی کد عضویت یا بروز مشکل در ورود، با پشتیبانی مرکز تماس بگیرید.</p>
                        <a
                            href="{{ route('login.select') }}"
                            class="mt-2 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-[#5964AE]/20 bg-white px-3 text-xs font-bold text-[#5964AE] transition hover:border-[#5964AE]/35 hover:bg-[#f1f1fb] focus:outline-none focus:ring-4 focus:ring-[#5964AE]/15"
                        >
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            بازگشت به صفحه انتخاب نوع ورود
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
