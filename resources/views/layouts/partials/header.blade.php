<header class="flex items-center justify-between gap-4 px-6 py-4 bg-white border-b shadow-sm">
    <div class="flex items-center gap-4">

        <button type="button" @click="toggleSidebar()" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100" aria-label="نمایش یا پنهان‌سازی منو">
            <svg x-show="!sidebarOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none" style="display: none;"><path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <svg x-show="sidebarOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none"><path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>

        <div class="relative">
            <span class="text-gray-700 font-semibold">{{ auth()->user()->access_level === 'manager' ? 'مدیریت محترم ←' : 'اپراتور محترم ←' }} {{ auth()->user()->first_name ?? '' }}  {{ auth()->user()->last_name ?? 'کاربر' }} </span>
        </div>
    </div>

    <div class="flex items-center">
        <!-- نمایش تاریخ امروز شمسی (با کمک پکیج‌های تاریخ یا به صورت دستی) -->
        <div class="text-sm text-gray-500 ml-4">

        </div>

        <div class="relative">
            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-white ring-1 ring-amber-50">
                <img src="{{ asset('images/logo-sm.png') }}" alt="لوگوی مرکز آوای همدلی" class="h-10 w-10 object-contain">
            </div>
        </div>
    </div>
</header>
