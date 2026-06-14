<aside
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-8"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-250"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-8"
    class="fixed inset-y-0 right-0 z-40 flex w-64 shrink-0 flex-col overflow-y-auto bg-indigo-900 px-4 py-8 text-white shadow-2xl transition-all duration-300 lg:relative lg:z-auto lg:h-screen lg:shadow-none"
    style="display: none;"
>
    <div class="mb-8 border-b border-indigo-700/30 pb-6">
        <div class="space-y-3 text-center">
            <div class="flex justify-center">
                <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl opacity-80 shadow-lg shadow-indigo-500/10">
                    <img src="{{ asset('images/logo-wh.png') }}" alt="Logo" class="h-full w-full object-cover">
                </div>
            </div>

            <div>
                <p class="mb-2 text-[11px] font-light tracking-wide text-indigo-100/80">
                    پنل حامی کودک
                </p>
                <h1 class="bg-gradient-to-l from-indigo-200 via-purple-100 to-indigo-200 bg-clip-text text-3xl font-black text-transparent">
                    آوای همدلی
                </h1>
            </div>

            <div class="flex justify-center pt-1">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-600/40 bg-indigo-900/40 px-3 py-1 text-[10px] font-semibold text-indigo-100 backdrop-blur-sm">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                    نسخه {{ config('app.asset_version') }}
                </span>
            </div>
        </div>
    </div>

    @php
        $isDashboardActive = request()->routeIs('child-supporter.dashboard');
        $isSponsorRegistrationActive = request()->routeIs('child-supporter.sponsor-registration');
        $isUserAccountActive = request()->routeIs('child-supporter.user-account');
        $isSystemSettingsActive = $isUserAccountActive;
    @endphp

    <nav class="flex-1 space-y-2" x-data="{ openMenu: {{ \Illuminate\Support\Js::from($isSystemSettingsActive ? 'system-settings' : '') }} }">
        <a href="{{ route('child-supporter.dashboard') }}" class="flex items-center rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isDashboardActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 12l9-8 9 8-2 1.75V20a1 1 0 01-1 1h-4v-6h-4v6H6a1 1 0 01-1-1v-6.25L3 12z"/>
            </svg>
            <span>پیشخوان</span>
        </a>

        <a href="{{ route('child-supporter.sponsor-registration') }}" class="flex items-center rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isSponsorRegistrationActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <svg class="ml-3 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l7.78-8.84a5.5 5.5 0 0 0 1.06-7.78z"/>
                <path d="M12 8v6M9 11h6"/>
            </svg>
            <span>ثبت نام حامی</span>
        </a>

        <button type="button" @click="openMenu = openMenu === 'system-settings' ? '' : 'system-settings'"
                class="flex w-full items-center justify-between rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isSystemSettingsActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <div class="flex items-center">
                <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.362 2.362 1.724 1.724 0 001.066 2.573 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.362 2.362 1.724 1.724 0 00-2.573 1.066 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.362-2.362 1.724 1.724 0 00-1.066-2.573 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.362-2.362 1.724 1.724 0 002.573-1.066z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8.75A3.25 3.25 0 1112 15.25 3.25 3.25 0 0112 8.75z"/>
                </svg>
                <span>تنظیمات سیستم</span>
            </div>
            <svg class="h-4 w-4 transition-transform duration-200" :class="openMenu === 'system-settings' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="openMenu === 'system-settings'" x-collapse.duration.250ms class="mr-8 mt-2 space-y-1">
            <a href="{{ route('child-supporter.user-account') }}"
               class="block rounded px-4 py-2 text-sm {{ $isUserAccountActive ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:text-white' }}">
                حساب کاربری
            </a>
        </div>
    </nav>

    <div class="mt-6 border-t border-indigo-700/30 pt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center rounded-lg px-4 py-2.5 text-rose-100 transition-all duration-200 hover:bg-rose-500/10 hover:text-white"
            >
                <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1"/>
                </svg>
                <span>خروج از سیستم</span>
            </button>
        </form>
    </div>
</aside>
