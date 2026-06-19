<div
    dir="rtl"
    class="min-h-[calc(100svh-2rem)] bg-[linear-gradient(135deg,#eef8fd_0%,#ffffff_42%,#fcf1f6_100%)]"
    style="margin-inline: calc(50% - 50vw); width: 100vw;"
>
    <div class="flex min-h-[calc(100svh-2rem)] items-center justify-center px-3 py-3 sm:px-6 sm:py-6 lg:px-8">
        <div class="grid w-full max-w-md overflow-hidden rounded-2xl border border-white bg-white/95 shadow-[0_24px_80px_rgba(89,100,174,0.16)] ring-1 ring-slate-200/80 backdrop-blur lg:max-w-5xl lg:grid-cols-[0.88fr_1.12fr]">
            <aside class="relative hidden overflow-hidden bg-[#5964AE] lg:block">
                <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(54,169,223,0.95)_0%,rgba(89,100,174,0.94)_48%,rgba(212,32,95,0.92)_100%)]"></div>
                <div class="absolute inset-x-0 top-0 h-24 bg-white/10"></div>
                <div class="absolute bottom-0 left-0 right-0 h-40 bg-[linear-gradient(0deg,rgba(15,23,42,0.22),transparent)]"></div>

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
                            <p class="text-sm font-bold text-white/85">مرکز نیکوکاری آوای همدلی</p>
                            <h1 class="mt-4 text-3xl font-black leading-10">
                                سامانه خدمات حمایتی آوای همدلی
                            </h1>
                            <p class="mt-5 text-sm leading-7 text-white/80">
                                برای ثبت و پیگیری خدمات مددجویان و خانواده‌های تحت پوشش، وارد حساب کاربری خود شوید.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-white/12 px-3 py-4 ring-1 ring-white/15">
                            <i class="bi bi-shield-check text-2xl" aria-hidden="true"></i>
                            <p class="mt-2 text-xs font-bold text-white/85">محرمانه</p>
                        </div>
                        <div class="rounded-xl bg-white/12 px-3 py-4 ring-1 ring-white/15">
                            <i class="bi bi-lightning-charge text-2xl" aria-hidden="true"></i>
                            <p class="mt-2 text-xs font-bold text-white/85">منظم</p>
                        </div>
                        <div class="rounded-xl bg-white/12 px-3 py-4 ring-1 ring-white/15">
                            <i class="bi bi-phone text-2xl" aria-hidden="true"></i>
                            <p class="mt-2 text-xs font-bold text-white/85">در دسترس</p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="relative px-4 py-5 sm:px-7 sm:py-8 lg:px-14 lg:py-12">
                <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#5964AE,#36A9DF,#D4205F)] lg:hidden"></div>

                <div class="mx-auto flex w-full max-w-md flex-col">
                    <header class="mb-5 sm:mb-7 lg:mb-8">
                        <div class="flex items-center gap-3 lg:hidden">
                            <img
                                class="h-auto w-28 shrink-0 sm:w-32"
                                src="{{ asset('/images/logo-sm.png') }}"
                                alt="لوگوی آوای همدلی"
                            >
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-[#5964AE]">مرکز نیکوکاری آوای همدلی</p>
                                <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">
                                    ورود به سامانه
                                </h2>
                                <p class="mt-1 text-xs leading-6 text-slate-500 sm:text-sm">
                                    برای پیگیری خدمات حمایتی وارد حساب خود شوید.
                                </p>
                            </div>
                        </div>

                        <div class="hidden lg:block">
                            <p class="text-sm font-bold text-[#5964AE]">مرکز نیکوکاری آوای همدلی</p>
                            <h2 class="mt-2 text-3xl font-black text-slate-900">
                                ورود به سامانه
                            </h2>
                            <p class="mt-3 text-sm leading-7 text-slate-500">
                                برای ثبت و پیگیری خدمات حمایتی، اطلاعات حساب خود را وارد کنید.
                            </p>
                        </div>
                    </header>

                    <form class="space-y-4 sm:space-y-5" wire:submit.prevent="login" autocomplete="on" novalidate>
                        <div>
                            <label for="email" class="mb-2 block text-sm font-bold text-slate-700">
                                نام کاربری یا ایمیل ثبت‌شده
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#5964AE]">
                                    <i class="bi bi-person text-lg" aria-hidden="true"></i>
                                </span>
                                <input
                                    wire:model.defer="email"
                                    id="email"
                                    type="text"
                                    required
                                    autocomplete="username"
                                    inputmode="email"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    dir="ltr"
                                    class="block min-h-[50px] w-full rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 pl-4 pr-12 text-left text-base text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-[#36A9DF] focus:bg-white focus:ring-4 focus:ring-[#36A9DF]/15 sm:min-h-[54px]"
                                    placeholder="شناسه همکار یا ایمیل شما"
                                    aria-describedby="@error('email') email-error @enderror"
                                >
                            </div>
                            @error('email')
                                <span id="email-error" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600" role="alert" aria-live="assertive">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div x-data="{ showPassword: false }">
                            <label for="password" class="mb-2 block text-sm font-bold text-slate-700">
                                رمز عبور
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#5964AE]">
                                    <i class="bi bi-lock text-lg" aria-hidden="true"></i>
                                </span>
                                <input
                                    wire:model.defer="password"
                                    id="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    required
                                    minlength="6"
                                    maxlength="128"
                                    autocomplete="current-password"
                                    dir="ltr"
                                    class="block min-h-[50px] w-full rounded-xl border border-slate-200 bg-slate-50/80 px-12 py-3 text-left text-base text-slate-900 shadow-inner shadow-slate-100 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-[#36A9DF] focus:bg-white focus:ring-4 focus:ring-[#36A9DF]/15 sm:min-h-[54px]"
                                    placeholder="رمز عبور حساب شما"
                                    aria-describedby="@error('password') password-error @enderror"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-1 left-1 flex w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-white hover:text-[#5964AE] focus:outline-none focus:ring-2 focus:ring-[#36A9DF]/30"
                                    x-on:click="showPassword = !showPassword"
                                    x-bind:aria-label="showPassword ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور'"
                                >
                                    <i class="bi text-lg" x-bind:class="showPassword ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
                                </button>
                            </div>
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

                        <div class="flex items-center justify-between gap-3 pt-1 text-sm">
                            <label for="remember-me" class="inline-flex cursor-pointer select-none items-center gap-2 text-slate-600">
                                <input
                                    wire:model="remember"
                                    id="remember-me"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-[#5964AE] focus:ring-[#36A9DF]"
                                >
                                <span>مرا به خاطر بسپار</span>
                            </label>

                            <span class="hidden text-xs font-medium text-[#D4205F] sm:inline">ویژه کاربران مجاز مرکز</span>
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="login"
                            class="group flex min-h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#5964AE_0%,#36A9DF_55%,#D4205F_130%)] px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#5964AE]/20 transition hover:shadow-xl hover:shadow-[#36A9DF]/20 focus:outline-none focus:ring-4 focus:ring-[#36A9DF]/25 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-70 sm:min-h-[54px]"
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

                    <p class="mt-5 text-center text-xs leading-6 text-slate-500 sm:mt-6">
                        در صورت مشکل در ورود، با مدیر سامانه مرکز تماس بگیرید.
                    </p>
                </div>
            </section>
        </div>
    </div>
</div>
