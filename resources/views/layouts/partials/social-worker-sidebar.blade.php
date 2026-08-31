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
                    <img src="{{ asset('images/logo-wh.webp') }}" width="150" height="150" alt="لوگوی آوای همدلی" class="h-full w-full object-cover">
                </div>
            </div>

            <div>
                <p class="mb-2 text-[11px] font-light tracking-wide text-indigo-100/80">
                    پنل مددکار اجتماعی
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
        $isServiceDeliveryActive = request()->routeIs('social-worker.dashboard');
        $isAttendanceActive = request()->routeIs('social-worker.attendance');
        $isDeliveryHistoryActive = request()->routeIs('social-worker.delivery-history');
        $isUserAccountActive = request()->routeIs('social-worker.user-account');
    @endphp

    <nav class="flex-1 space-y-2">
        <a href="{{ route('social-worker.dashboard') }}" class="flex items-center rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isServiceDeliveryActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
            </svg>
            <span>تحویل خدمت</span>
        </a>

        <a href="{{ route('social-worker.attendance') }}" class="flex items-center rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isAttendanceActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m-9 9h10a2 2 0 002-2V7a2 2 0 00-2-2h-1V3H8v2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>حضور و غیاب</span>
        </a>

        <a href="{{ route('social-worker.delivery-history') }}" class="flex items-center rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isDeliveryHistoryActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>تاریخچه تحویل</span>
        </a>

        <a href="{{ route('social-worker.user-account') }}" class="flex items-center rounded-lg px-4 py-2.5 transition-all duration-200 {{ $isUserAccountActive ? 'bg-indigo-700 text-white shadow-sm' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' }}">
            <svg class="ml-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>حساب کاربری</span>
        </a>
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
