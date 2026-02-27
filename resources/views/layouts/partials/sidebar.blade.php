<aside class="flex flex-col w-64 h-screen px-4 py-8 overflow-y-auto bg-indigo-900 text-white transition-all duration-300">
    <div class="flex items-center justify-center mb-8 border-b border-indigo-800 pb-4">
        <span class="text-xl font-bold">آوای همدلی</span>
    </div>

    <nav class="flex-1 space-y-2">
        <!-- داشبورد اصلی -->
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}">
            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span>پیشخوان</span>
        </a>

        <!-- بخش مددجویان (فقط برای افراد دارای اجازه) -->
        @can('manage-people')
            <div x-data="{ open: {{ request()->routeIs('people.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span>مددجویان</span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" class="mt-2 mr-8 space-y-1">
                    <a href="{{ route('people.fast-create') }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white">
                        <i class="fa fa-bolt"></i> ثبت سریع فرد
                    </a>
                    <a href="{{ route('people.index') }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white">
                        <i class="fa fa-users"></i> لیست مددجویان
                    </a>
                    <a href="{{ route('people.form', ['mode' => 'create']) }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white">
                        <i class="fa fa-user-plus"></i> ثبت مددجوی جدید (کامل)
                    </a>
                </div>
            </div>
        @endcan

        <!-- بخش مددکاران -->
        @can('manage-social-workers')
            <a href="{{ route('social-workers.index') }}"
               class="flex items-center px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('social-workers.*') ? 'bg-indigo-700' : '' }}">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <span>مددکاران اجتماعی</span>
            </a>
        @endcan
    </nav>

    <!-- دکمه خروج در پایین سایدبار -->
    <div class="mt-auto pt-4 border-t border-indigo-800">
        <!-- فرم خروج -->
        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <button type="submit" @click.prevent="if (confirm('آیا مطمئن هستید که می‌خواهید از سیستم خارج شوید؟')) $el.closest('form').submit()"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-red-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                خروج از سیستم
            </button>
        </form>
    </div>
</aside>
