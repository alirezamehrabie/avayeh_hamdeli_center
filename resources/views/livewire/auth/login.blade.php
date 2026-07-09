<div
    dir="rtl"
    class="relative min-h-[100svh] overflow-hidden bg-[#f8fbff]"
    style="margin-inline: calc(50% - 50vw); width: 100vw;"
>
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#36A9DF]/22 blur-3xl sm:h-96 sm:w-96"></div>
    <div class="pointer-events-none absolute -left-24 top-1/4 h-64 w-64 rounded-full bg-[#D4205F]/14 blur-3xl sm:h-80 sm:w-80"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-[radial-gradient(circle_at_50%_100%,rgba(89,100,174,0.16),transparent_58%)]"></div>
    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(238,248,253,0.88)_0%,rgba(255,255,255,0.84)_44%,rgba(252,241,246,0.88)_100%)]"></div>

    <div class="relative z-10 flex min-h-[100svh] items-stretch justify-center px-0 py-0 sm:items-center sm:px-6 sm:py-6 lg:px-8">
        <div class="grid min-h-[100svh] w-full max-w-md overflow-hidden border border-white bg-white/95 shadow-[0_24px_80px_rgba(89,100,174,0.16)] ring-1 ring-slate-200/80 backdrop-blur sm:min-h-0 sm:rounded-2xl lg:max-w-5xl lg:grid-cols-[0.88fr_1.12fr]">
            <aside class="relative hidden overflow-hidden bg-[#5964AE] lg:block">
                <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(21,114,161,0.98)_0%,rgba(68,77,153,0.97)_48%,rgba(164,24,75,0.96)_100%)]"></div>
                <div class="absolute inset-x-0 top-0 h-24 bg-white/10"></div>
                <div class="absolute bottom-0 left-0 right-0 h-40 bg-[linear-gradient(0deg,rgba(15,23,42,0.34),transparent)]"></div>

                <div class="relative flex h-full min-h-[560px] flex-col justify-between p-10 text-white">
                    <div>
                        <div class="inline-flex rounded-2xl bg-white p-4 shadow-xl shadow-slate-900/10">
                            <img
                                class="h-auto w-48"
                                src="{{ asset('/images/logo-sm.png') }}"
                                alt="لوگوی آوای همدلی"
                            >
                        </div>

                        <div class="mt-12 max-w-sm">
                            <p class="text-xs font-medium text-white">مرکز نیکوکاری تخصصی کودکان آوای همدلی</p>
                            <h1 class="mt-3 text-3xl font-black leading-10">
                                سامانه خدمات آوای همدلی
                            </h1>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white/16 px-4 py-4 mt-4 ring-1 ring-white/25">
                        <div class="flex items-start gap-3">
                            <i class="bi bi-shield-check shrink-0 text-2xl text-white" aria-hidden="true"></i>
                            <div>
                                <p class="text-sm font-black text-white">ورود محرمانه کاربران مجاز</p>
                                <p class="mt-1 text-xs font-medium leading-6 text-white/90">
                                    جهت حفاظت از اطلاعات محرمانه، پس از پایان کار به صورت صحیح از حساب خارج شوید.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="relative flex min-h-[100svh] px-4 py-4 sm:min-h-0 sm:px-7 sm:py-8 lg:px-14 lg:py-12">
                <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#5964AE,#36A9DF,#D4205F)] lg:hidden"></div>

                <div class="mx-auto flex w-full max-w-md flex-col justify-center">
                    <header class="mb-4 sm:mb-7 lg:mb-8">
                        <div class="flex items-center gap-2.5 lg:hidden">
                            <img
                                class="h-auto w-20 shrink-0 sm:w-32"
                                src="{{ asset('/images/logo-sm.png') }}"
                                alt="لوگوی آوای همدلی"
                            >
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold leading-5 text-[#5964AE] sm:text-xs">مرکز نیکوکاری آوای همدلی</p>
                                <h2 class="text-xl font-black text-slate-900 sm:mt-1 sm:text-2xl">
                                    ورود به سامانه
                                </h2>
                                <p class="mt-0.5 text-[11px] leading-5 text-slate-600 sm:mt-1 sm:text-sm sm:leading-6">
                                    برای پیگیری خدمات وارد حساب خود شوید.
                                </p>
                            </div>
                        </div>

                        <div class="hidden lg:block">
                            <h2 class="mt-3 text-3xl font-black text-slate-900">
                                ورود به سامانه
                            </h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                برای ثبت و پیگیری خدمات، اطلاعات حساب خود را وارد کنید.
                            </p>
                        </div>
                    </header>

                    <form class="space-y-3.5 sm:space-y-5" wire:submit.prevent="login" autocomplete="on" novalidate>
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-bold text-slate-700 sm:mb-2">
                                نام کاربری
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#4d56a3]">
                                    <i class="bi bi-person text-lg" aria-hidden="true"></i>
                                </span>
                                <input
                                    wire:model="email"
                                    id="email"
                                    type="text"
                                    required
                                    autocomplete="username"
                                    inputmode="email"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    dir="ltr"
                                    lang="en"
                                    wire:loading.attr="disabled"
                                    wire:target="login"
                                    class="block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pl-4 pr-12 text-left text-base text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#1572A1] focus:bg-white focus:ring-4 focus:ring-[#1572A1]/20 disabled:cursor-wait disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-500 sm:min-h-[54px]"
                                    placeholder="نام کاربری"
                                    aria-describedby="@error('email') email-error @enderror"
                                    aria-invalid="@error('email') true @else false @enderror"
                                >
                            </div>
                            @error('email')
                                <span id="email-error" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600" role="alert" aria-live="assertive">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div x-data="{ showPassword: false, capsLockOn: false }">
                            <label for="password" class="mb-1.5 block text-sm font-bold text-slate-700 sm:mb-2">
                                رمز عبور
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#4d56a3]">
                                    <i class="bi bi-lock text-lg" aria-hidden="true"></i>
                                </span>
                                <input
                                    wire:model="password"
                                    id="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    x-on:keydown="capsLockOn = $event.getModifierState && $event.getModifierState('CapsLock')"
                                    x-on:keyup="capsLockOn = $event.getModifierState && $event.getModifierState('CapsLock')"
                                    x-on:blur="capsLockOn = false"
                                    required
                                    minlength="6"
                                    maxlength="128"
                                    autocomplete="current-password"
                                    dir="ltr"
                                    lang="en"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    wire:loading.attr="disabled"
                                    wire:target="login"
                                    class="block min-h-[48px] w-full rounded-xl border border-slate-300 bg-white px-12 py-3 text-left text-base text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-[#1572A1] focus:bg-white focus:ring-4 focus:ring-[#1572A1]/20 disabled:cursor-wait disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-500 sm:min-h-[54px]"
                                    placeholder="رمز عبور"
                                    aria-describedby="@error('password') password-error @enderror password-caps-warning"
                                    aria-invalid="@error('password') true @else false @enderror"
                                >
                                <button
                                    type="button"
                                    wire:loading.attr="disabled"
                                    wire:target="login"
                                    class="absolute inset-y-1 left-1 flex w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-[#4d56a3] focus:outline-none focus:ring-2 focus:ring-[#1572A1]/35 disabled:cursor-wait disabled:opacity-50"
                                    x-on:click="showPassword = !showPassword"
                                    x-bind:aria-label="showPassword ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور'"
                                    x-bind:aria-pressed="showPassword.toString()"
                                >
                                    <i class="bi text-lg" x-bind:class="showPassword ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
                                </button>
                            </div>
                            <p
                                id="password-caps-warning"
                                x-cloak
                                x-show="capsLockOn"
                                class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-amber-700"
                                role="status"
                            >
                                <i class="bi bi-shift" aria-hidden="true"></i>
                                کلید Caps Lock روشن است.
                            </p>
                            @error('password')
                                <span id="password-error" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600" role="alert" aria-live="assertive">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        @error('auth')
                            <div
                                id="login-auth-error"
                                class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm font-semibold leading-6 text-rose-700"
                                role="alert"
                                aria-live="assertive"
                            >
                                <i class="bi bi-exclamation-triangle mt-0.5 shrink-0 text-base" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <div class="flex items-center justify-between gap-3 pt-0.5 text-sm sm:pt-1">
                            <label for="remember-me" class="inline-flex min-h-11 cursor-pointer select-none items-center gap-2 text-slate-700">
                                <input
                                    wire:model="remember"
                                    id="remember-me"
                                    type="checkbox"
                                    wire:loading.attr="disabled"
                                    wire:target="login"
                                    class="h-5 w-5 rounded border-slate-400 text-[#5964AE] focus:ring-[#1572A1] disabled:cursor-wait disabled:opacity-60 sm:h-4 sm:w-4"
                                >
                                <span>مرا به خاطر بسپار</span>
                            </label>

                            <span class="hidden text-xs font-medium text-[#D4205F] sm:inline">ویژه کاربران مجاز مرکز</span>
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="login"
                            class="group flex min-h-[50px] w-full items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#4d56a3_0%,#1572A1_58%,#A4184B_130%)] px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#5964AE]/20 transition hover:shadow-xl hover:shadow-[#1572A1]/20 focus:outline-none focus:ring-4 focus:ring-[#1572A1]/30 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-70 sm:min-h-[54px]"
                        >
                            <span wire:loading.remove wire:target="login" class="inline-flex items-center gap-2">
                                ورود به سامانه
                                <i class="bi bi-arrow-left-short text-xl transition group-hover:-translate-x-0.5" aria-hidden="true"></i>
                            </span>
                            <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                در حال بررسی اطلاعات...
                            </span>
                        </button>
                    </form>

                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-3 text-center text-xs leading-6 text-slate-600 sm:mt-6">
                        <p>در صورت مشکل در ورود، با پشتیبانی فنی سامانه تماس بگیرید.</p>
                        @if($supportPhone['href'] && $supportPhone['label'])
                            <a
                                href="{{ $supportPhone['href'] }}"
                                class="mt-2 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-[#1572A1]/20 bg-white px-3 text-xs font-bold text-[#1572A1] transition hover:border-[#1572A1]/35 hover:bg-[#eef8fd] focus:outline-none focus:ring-4 focus:ring-[#1572A1]/15"
                            >
                                <i class="bi bi-telephone" aria-hidden="true"></i>
                                 پشتیبانی فنی: <span dir="ltr">{{ $supportPhone['label'] }}</span>
                            </a>
                        @else
                            <p class="mt-1 font-semibold text-slate-700">اطلاعات تماس از طریق مدیر داخلی مرکز اعلام می‌شود.</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
