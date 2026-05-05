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
    <div class="mb-8 border-b border-indigo-800 pb-4 text-center">
        <p class="text-xs pb-3">مرکز نیکوکاری تخصصی کودکان</p>
        <p class="text-2xl font-bold">آوای همـــــدلی</p>
    </div>

    @php
        $dashboardMode = $dashboardMode ?? false;
        $activeSection = $activeSection ?? null;
        $isActive = fn ($sections) => in_array($activeSection, (array) $sections, true);
    @endphp

    <nav class="flex-1 space-y-2">
        @if($dashboardMode)
            <button type="button" wire:click="selectSection('overview')"
                    class="flex items-center w-full px-4 py-3 rounded-lg transition-colors {{ $activeSection === 'overview' ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>پیشخوان</span>
            </button>
        @else
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>پیشخوان</span>
            </a>
        @endif

        @can('manage-people')
            <div x-data="{ open: {{ $dashboardMode ? ($isActive(['people-fast-create', 'people-list', 'people-block-list', 'person-create', 'person-edit']) ? 'true' : 'false') : (request()->routeIs('people.*') ? 'true' : 'false') }} }">
                <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span>مددجویان</span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @if($dashboardMode)
                        <button type="button" wire:click="selectSection('people-fast-create')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'people-fast-create' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            <i class="fa fa-bolt"></i> ثبت سریع مددجو
                        </button>
                        <button type="button" wire:click="selectSection('person-create')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'person-create' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            <i class="fa fa-user-plus"></i> فرم کامل ثبت نام
                        </button>
                        <button type="button" wire:click="selectSection('people-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'people-list' || $activeSection === 'person-edit' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            <i class="fa fa-users"></i> لیست مددجویان
                        </button>
                        <button type="button" wire:click="selectSection('people-block-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'people-block-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            <i class="fa fa-ban"></i> بلاک لیست مددجویان
                        </button>
                    @else
                        <a href="{{ route('people.fast-create') }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white"><i class="fa fa-bolt"></i> ثبت سریع فرد</a>
                        <a href="{{ route('people.index') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('people.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i class="fa fa-users"></i> لیست مددجویان</a>
                        <a href="{{ route('people.block-list') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('people.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i class="fa fa-ban"></i> بلاک لیست مددجویان</a>
                        <a href="{{ route('people.form', ['mode' => 'create']) }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white"><i class="fa fa-user-plus"></i> ثبت مددجوی جدید (کامل)</a>
                    @endif
                </div>
            </div>
        @endcan

        @can('manage-social-workers')
            @if($dashboardMode)
                <div x-data="{ open: {{ $isActive(['social-workers-list', 'social-workers-block-list', 'social-worker-create', 'social-worker-edit']) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['social-workers-list', 'social-workers-block-list', 'social-worker-create', 'social-worker-edit']) ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>مددکاران</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <button type="button" wire:click="selectSection('social-workers-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'social-workers-list' || $activeSection === 'social-worker-edit' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست مددکاران</button>
                        <button type="button" wire:click="selectSection('social-worker-create')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'social-worker-create' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">ثبت مددکار جدید</button>
                        <button type="button" wire:click="selectSection('social-workers-block-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'social-workers-block-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">بلاک لیست مددکاران</button>
                    </div>
                </div>
            @else
                <div x-data="{ open: {{ request()->routeIs('social-workers.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('social-workers.*') ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>مددکاران اجتماعی</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <a href="{{ route('social-workers.index') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست مددکاران</a>
                        <a href="{{ route('social-workers.block-list') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">بلاک لیست مددکاران</a>
                        <a href="{{ route('social-workers.create') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.create') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">ثبت مددکار جدید</a>
                    </div>
                </div>
            @endif
        @endcan

        @if($dashboardMode)
            <div x-data="{ open: {{ $isActive(['guardians-list']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['guardians-list']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6 3 3 0 000 6zM5 12a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                        <span>سرپرستان</span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    <button type="button" wire:click="selectSection('guardians-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'guardians-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست سرپرستان</button>
                </div>
            </div>
        @endif

        @if($dashboardMode)
            <div x-data="{ open: {{ $isActive(['advanced-reports', 'advanced-beneficiary-report', 'advanced-supervisor-report', 'advanced-social-worker-report']) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['advanced-reports', 'advanced-beneficiary-report', 'advanced-supervisor-report', 'advanced-social-worker-report']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>گزارش پیشرفته</span>
                    </div>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    <button type="button" wire:click="selectSection('advanced-beneficiary-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-beneficiary-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش مددجویان</button>
                    <button type="button" wire:click="selectSection('advanced-supervisor-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-supervisor-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش سرپرستان</button>
                    <button type="button" wire:click="selectSection('advanced-social-worker-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-social-worker-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش مددکاران</button>
                </div>
            </div>
        @else
            <a href="{{ route('admin.dashboard', ['section' => 'advanced-reports']) }}"
               class="flex items-center px-4 py-3 rounded-lg transition-colors hover:bg-indigo-800">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>Advanced Reports</span>
            </a>
        @endif

    </nav>

    <div class="mt-auto pt-4 border-t border-indigo-800">
        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <button type="submit" @click.prevent="if (confirm('آیا مطمئن هستید که می‌خواهید از سیستم خارج شوید؟')) $el.closest('form').submit()"
                    class="group flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-300/30 bg-white/10 px-4 py-3 text-sm font-semibold text-rose-50 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-rose-200/60 hover:bg-rose-500/20 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-rose-300/20">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/20 text-rose-100 transition group-hover:bg-rose-400/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                </span>
                خروج از سیستم
            </button>
        </form>
    </div>
</aside>
