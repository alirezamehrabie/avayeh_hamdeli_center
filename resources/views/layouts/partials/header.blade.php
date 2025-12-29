<header class="flex items-center justify-between px-6 py-4 bg-white border-b shadow-sm">
    <div class="flex items-center">

        <button class="text-gray-500 focus:outline-none lg:hidden">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none"><path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <div class="relative mx-4 lg:mx-0">
            <span class="text-gray-700 font-semibold">خوش آمدید، {{ auth()->user()->name ?? 'مدیر سیستم' }}</span>
        </div>
    </div>

    <div class="flex items-center">
        <!-- نمایش تاریخ امروز شمسی (با کمک پکیج‌های تاریخ یا به صورت دستی) -->
        <div class="text-sm text-gray-500 ml-4">

        </div>

        <div class="relative">
            <img class="w-10 h-10 rounded-full object-cover border-2 border-indigo-500"
                 src="https://ui-avatars.com/api/?name={{ auth()->user()->name ?? 'Admin' }}&background=4338ca&color=fff" alt="Avatar">
        </div>
    </div>
</header>
