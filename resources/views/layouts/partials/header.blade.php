<header class="flex items-center justify-between gap-4 px-6 py-4 bg-white border-b shadow-sm">
    <div class="flex items-center gap-4">

        <button type="button" @click="toggleSidebar()" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100" aria-label="نمایش یا پنهان‌سازی منو">
            <svg x-show="!sidebarOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none" style="display: none;"><path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <svg x-show="sidebarOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none"><path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>

        <div class="relative">
            <div class="inline-flex items-center rounded-lg border border-gray-100 bg-gray-50 p-1 pl-3 shadow-sm">
                <span class="ml-2 rounded-md bg-indigo-600 px-2 py-1 text-[10px] font-bold text-white shadow-sm">
                    {{ auth()->user()->access_level === 'manager' ? 'مدیریت' : 'ادمین' }}
                </span>

                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700">
                    <span>{{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? 'کاربر' }}</span>
                    <span class="inline-flex h-5 w-5 items-center justify-center" title="حساب تاییدشده" aria-label="حساب تاییدشده">
                        <svg class="h-5 w-5 drop-shadow-[0_1px_1px_rgba(14,165,233,0.22)]" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="admin-verified-badge" x1="4" y1="4" x2="16" y2="16" gradientUnits="userSpaceOnUse">
                                    <stop offset="0" stop-color="#5BB7FF" />
                                    <stop offset="1" stop-color="#1D8DFF" />
                                </linearGradient>
                            </defs>
                            <circle cx="10" cy="10" r="8.75" fill="url(#admin-verified-badge)" />
                            <path d="M6.55 10.25L8.7 12.4L13.4 7.7" stroke="#FFFFFF" stroke-width="1.95" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </span>
            </div>
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
