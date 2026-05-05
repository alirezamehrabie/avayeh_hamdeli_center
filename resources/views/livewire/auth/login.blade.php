<div dir="rtl" class="min-h-screen bg-slate-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-[#e7f6fd] via-white to-[#f5eef6]"></div>
    <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-[#36A9DF]/25 blur-3xl"></div>
    <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-[#D4205F]/20 blur-3xl"></div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid w-full max-w-6xl overflow-hidden rounded-3xl bg-white/90 shadow-2xl ring-1 ring-slate-200 backdrop-blur md:grid-cols-2">
            <section class="hidden md:flex flex-col justify-between bg-gradient-to-b from-[#f5f7fa] via-[#eef2f6] to-[#e7ebf0] p-10 text-slate-800">
                <div>
                    <img class="w-56" src="{{ asset('/images/logo-sm.png') }}" alt="لوگوی آوای همدلی">
                    <h1 class="mt-8 text-3xl font-black leading-tight text-slate-800">سامانه آوای همدلی</h1>
                    <p class="mt-4 text-sm leading-7 text-slate-600">
                        یک بستر امن برای مدیریت خدمات، پیگیری امور مددجویان و هماهنگی بهتر میان کاربران و مدیران.
                    </p>
                </div>
                <ul class="space-y-3 text-sm text-slate-600">
                    <li>طراحی واکنش گرا برای موبایل، تبلت و دسکتاپ</li>
                    <li>ورود امن با محدودسازی تلاش های ناموفق</li>
                    <li>تجربه کاربری ساده و سریع برای همه نقش ها</li>
                </ul>
            </section>

            <section class="p-6 sm:p-10 lg:p-12">
                <div class="mx-auto w-full max-w-md">
                    <div class="md:hidden mb-6 text-center">
                        <img class="w-44 mx-auto" src="{{ asset('/images/logo-sm.png') }}" alt="لوگوی آوای همدلی">
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">ورود به سامانه</h2>
                    <p class="mt-2 text-sm text-slate-600">برای دسترسی به پنل کاربری یا مدیریت، اطلاعات خود را وارد کنید.</p>

                    <form class="mt-8 space-y-5" wire:submit.prevent="login" autocomplete="on" novalidate>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">نوع پنل</label>
                            <div class="relative grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1 overflow-hidden">
                                <span aria-hidden="true"
                                      class="pointer-events-none absolute top-1 bottom-1 w-[calc(50%-0.25rem)] rounded-lg bg-white shadow-sm transition-transform duration-300 ease-out"
                                      style="transform: translateX({{ $portal === 'admin' ? '0%' : '-100%' }}); right: 0.25rem;"></span>
                                <button type="button"
                                        wire:click="$set('portal', 'admin')"
                                        wire:loading.attr="disabled"
                                        wire:target="login"
                                        aria-pressed="{{ $portal === 'admin' ? 'true' : 'false' }}"
                                        class="relative z-10 rounded-lg px-3 py-2 text-center text-sm font-semibold transition-colors duration-200 focus:outline-none disabled:opacity-60 {{ $portal === 'admin' ? 'text-[#5964AE]' : 'text-slate-600 hover:text-slate-800' }}">
                                    مدیر
                                </button>
                                <button type="button"
                                        wire:click="$set('portal', 'user')"
                                        wire:loading.attr="disabled"
                                        wire:target="login"
                                        aria-pressed="{{ $portal === 'user' ? 'true' : 'false' }}"
                                        class="relative z-10 rounded-lg px-3 py-2 text-center text-sm font-semibold transition-colors duration-200 focus:outline-none disabled:opacity-60 {{ $portal === 'user' ? 'text-[#5964AE]' : 'text-slate-600 hover:text-slate-800' }}">
                                    کاربر
                                </button>
                            </div>
                            @error('portal') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-bold text-slate-700">ایمیل</label>
                            <input wire:model.defer="email" id="email" type="email" required autocomplete="username" inputmode="email" autocapitalize="off" spellcheck="false" dir="ltr"
                                   class="w-full rounded-xl border border-[#BBBBBB] px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-[#36A9DF] focus:outline-none focus:ring-4 focus:ring-[#36A9DF]/20"
                                   placeholder="example@domain.com">
                            @error('email') <span class="mt-1 block text-xs text-red-600" role="alert" aria-live="assertive">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-bold text-slate-700">رمز عبور</label>
                            <input wire:model.defer="password" id="password" type="password" required minlength="6" maxlength="128" autocomplete="current-password"
                                   class="w-full rounded-xl border border-[#BBBBBB] px-4 py-3 text-slate-900 placeholder-slate-400 transition focus:border-[#36A9DF] focus:outline-none focus:ring-4 focus:ring-[#36A9DF]/20"
                                   placeholder="رمز عبور خود را وارد کنید">
                            @error('password') <span class="mt-1 block text-xs text-red-600" role="alert" aria-live="assertive">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <label class="inline-flex items-center gap-2 text-slate-700">
                                <input wire:model="remember" id="remember-me" type="checkbox" class="h-4 w-4 rounded border-[#BBBBBB] text-[#5964AE] focus:ring-[#36A9DF]">
                                مرا به خاطر بسپار
                            </label>
                        </div>

                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="login"
                                class="w-full rounded-xl bg-gradient-to-r from-[#6f78b6] via-[#5b95c7] to-[#4a86be] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#5964AE]/10 transition hover:from-[#6670b0] hover:via-[#558fc2] hover:to-[#447fb6] focus:outline-none focus:ring-4 focus:ring-[#36A9DF]/20 disabled:cursor-not-allowed disabled:opacity-70">
                            <span wire:loading.remove wire:target="login">ورود امن به سامانه</span>
                            <span wire:loading wire:target="login">در حال بررسی اطلاعات...</span>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
