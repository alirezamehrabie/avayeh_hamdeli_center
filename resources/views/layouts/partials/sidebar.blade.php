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
    <div class="mb-8 pb-0 border-b border-indigo-700/30">
        <div class="text-center space-y-3">
            <!-- Logo/Icon -->
            <div class="flex justify-center">
                <div class="w-16 h-16 opacity-80 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 overflow-hidden">
                    <!-- در اینجا آدرس تصویر لوگوی خود را وارد کنید -->
                    <img src="{{ asset("images/logo-wh.png")  }}" alt="Logo" class="w-full h-full object-cover">
                </div>
            </div>

            <!-- Title -->
            <div>
                <p class="text-[11px] text-indigo-100/80 font-light tracking-wide mb-2">
                    مرکز نیکوکاری تخصصی کودکان
                </p>
                <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-l from-indigo-200 via-purple-100 to-indigo-200">
                    آوای همدلی
                </h1>
            </div>

            <!-- Version Badge -->
            <div class="flex justify-center pt-1">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-semibold text-indigo-100 bg-indigo-900/40 rounded-full border border-indigo-600/40 backdrop-blur-sm">
                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                نسخه {{ config('app.asset_version')  }}
            </span>
            </div>
        </div>
    </div>



    @php
        $dashboardMode = $dashboardMode ?? false;
        $activeSection = $activeSection ?? null;
        $isActive = fn ($sections) => in_array($activeSection, (array) $sections, true);
        $peopleOpen = $dashboardMode ? $isActive(['people-fast-create', 'people-list', 'people-block-list', 'person-create', 'person-edit']) : request()->routeIs('people.*');
        $socialWorkersOpen = $dashboardMode ? $isActive(['social-workers-list', 'social-workers-block-list', 'social-worker-create', 'social-worker-edit']) : request()->routeIs('social-workers.*');
        $guardiansOpen = $dashboardMode ? $isActive(['guardians-list', 'guardians-block-list']) : request()->routeIs('guardians.*');
        $reportsOpen = $dashboardMode ? $isActive(['advanced-reports', 'advanced-beneficiary-report', 'advanced-operator-report', 'advanced-supervisor-report', 'advanced-social-worker-report']) : false;
        $servicesOpen = $dashboardMode ? $isActive(['define-services', 'service-delivery']) : false;
        $systemSettingsOpen = $dashboardMode ? $isActive(['system-settings-user-management', 'system-settings-user-account']) : request()->routeIs('admin.user-management') || request()->routeIs('admin.user-account');
        $defaultOpenMenu = $peopleOpen
            ? 'people'
            : ($socialWorkersOpen
                ? 'social-workers'
                : ($guardiansOpen
                    ? 'guardians'
                    : ($servicesOpen
                        ? 'services'
                        : ($reportsOpen
                            ? 'reports'
                            : ($systemSettingsOpen ? 'system-settings' : '')))));
    @endphp

    <nav x-data="{ openMenu: '{{ $defaultOpenMenu }}' }" class="flex-1 space-y-2">
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
            <div>
                <button type="button" @click="openMenu = openMenu === 'people' ? '' : 'people'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span>مددجویان</span>
                    </div>
                    <svg :class="openMenu === 'people' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'people'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @if($dashboardMode)

                        <button type="button" wire:click="selectSection('people-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'people-list' || $activeSection === 'person-edit' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            <i class="fa fa-users"></i> لیست مددجویان
                        </button>

                        @can('people-register')
                            <button type="button" wire:click="selectSection('person-create')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'person-create' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                                <i class="fa fa-user-plus"></i> فرم کامل ثبت نام
                            </button>

                            <button type="button" wire:click="selectSection('people-fast-create')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'people-fast-create' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                                <i class="fa fa-bolt"></i> ثبت سریع مددجو
                            </button>
                        @endcan

                        @can('people-delete')
                            <button type="button" wire:click="selectSection('people-block-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'people-block-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                                <i class="fa fa-ban"></i> مددجویان غیرفعال
                            </button>
                        @endcan
                    @else
                        @can('people-register')
                            <a href="{{ route('people.fast-create') }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white"><i class="fa fa-bolt"></i> ثبت سریع فرد</a>
                        @endcan
                        <a href="{{ route('people.index') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('people.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i class="fa fa-users"></i> لیست مددجویان</a>
                        @can('people-delete')
                            <a href="{{ route('people.block-list') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('people.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i class="fa fa-ban"></i> بلاک لیست مددجویان</a>
                        @endcan
                        @can('people-register')
                            <a href="{{ route('people.form', ['mode' => 'create']) }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white"><i class="fa fa-user-plus"></i> ثبت مددجوی جدید (کامل)</a>
                        @endcan
                    @endif
                </div>
            </div>
        @endcan

        @can('manage-social-workers')
            @if($dashboardMode)
                <div>
                    <button type="button" @click="openMenu = openMenu === 'social-workers' ? '' : 'social-workers'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['social-workers-list', 'social-workers-block-list', 'social-worker-create', 'social-worker-edit']) ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>مددکاران</span>
                        </div>
                        <svg :class="openMenu === 'social-workers' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openMenu === 'social-workers'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <button type="button" wire:click="selectSection('social-workers-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'social-workers-list' || $activeSection === 'social-worker-edit' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست مددکاران</button>
                        <button type="button" wire:click="selectSection('social-worker-create')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'social-worker-create' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">ثبت مددکار جدید</button>
                        <button type="button" wire:click="selectSection('social-workers-block-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'social-workers-block-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">مددکاران غیرفعال</button>
                    </div>
                </div>
            @else
                <div>
                    <button type="button" @click="openMenu = openMenu === 'social-workers' ? '' : 'social-workers'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('social-workers.*') ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>مددکاران اجتماعی</span>
                        </div>
                        <svg :class="openMenu === 'social-workers' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openMenu === 'social-workers'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <a href="{{ route('social-workers.index') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست مددکاران</a>
                        <a href="{{ route('social-workers.block-list') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">بلاک لیست مددکاران </a>
                        <a href="{{ route('social-workers.create') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.create') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">ثبت مددکار جدید</a>
                    </div>
                </div>
            @endif
        @endcan

        @can('full-access')
        @if($dashboardMode)
            <div>
                <button type="button" @click="openMenu = openMenu === 'guardians' ? '' : 'guardians'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['guardians-list', 'guardians-block-list']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6 3 3 0 000 6zM5 12a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                        <span>سرپرستان</span>
                    </div>
                    <svg :class="openMenu === 'guardians' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'guardians'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    <button type="button" wire:click="selectSection('guardians-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'guardians-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست سرپرستان</button>
                    <button type="button" wire:click="selectSection('guardians-block-list')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'guardians-block-list' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">سرپرستان غیرفعال</button>
                </div>
            </div>
        @else
            <div>
                <button type="button" @click="openMenu = openMenu === 'guardians' ? '' : 'guardians'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('guardians.*') ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6 3 3 0 000 6zM5 12a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                        <span>سرپرستان</span>
                    </div>
                    <svg :class="openMenu === 'guardians' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'guardians'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    <a href="{{ route('guardians.index') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('guardians.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست سرپرستان</a>
                    <a href="{{ route('guardians.block-list') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('guardians.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">بلاک لیست سرپرستان</a>
                </div>
            </div>
        @endif

        @if($dashboardMode)
            <div>
                <button type="button" @click="openMenu = openMenu === 'services' ? '' : 'services'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['define-services', 'service-delivery']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"></path></svg>
                        <span>خدمات</span>
                    </div>
                    <svg :class="openMenu === 'services' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'services'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    <button type="button" wire:click="selectSection('define-services')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'define-services' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">تعریف خدمات</button>
                    <button type="button" wire:click="selectSection('service-delivery')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'service-delivery' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">تحویل خدمات</button>
                </div>
            </div>
        @endif
        @endcan

        @if($dashboardMode && auth()->user()?->can('access-admin-panel'))
            <div>
                <button type="button" @click="openMenu = openMenu === 'reports' ? '' : 'reports'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['advanced-reports', 'advanced-beneficiary-report', 'advanced-operator-report', 'advanced-supervisor-report', 'advanced-social-worker-report']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>گزارش پیشرفته</span>
                    </div>
                    <svg :class="openMenu === 'reports' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'reports'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @can('full-access')
                        <button type="button" wire:click="selectSection('advanced-beneficiary-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-beneficiary-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش مددجویان</button>
                    @endcan
                    <button type="button" wire:click="selectSection('advanced-operator-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-operator-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش اپراتورها</button>
                    @can('full-access')
                        <button type="button" wire:click="selectSection('advanced-supervisor-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-supervisor-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش سرپرستان</button>
                        <button type="button" wire:click="selectSection('advanced-social-worker-report')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'advanced-social-worker-report' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">گزارش مددکاران</button>
                    @endcan
                </div>
            </div>
        @else
            <a href="{{ route('admin.dashboard', ['section' => auth()->user()?->can('full-access') ? 'advanced-reports' : 'advanced-operator-report']) }}"
               class="flex items-center px-4 py-3 rounded-lg transition-colors hover:bg-indigo-800">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>{{ auth()->user()?->can('full-access') ? 'Advanced Reports' : 'Operator Report' }}</span>
            </a>
        @endif

        @if($dashboardMode)
            <div>
                <button type="button" @click="openMenu = openMenu === 'system-settings' ? '' : 'system-settings'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ $isActive(['system-settings-user-management', 'system-settings-user-account']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-1.14 1.603-1.14 1.902 0a1.724 1.724 0 002.573 1.01c1-.58 2.18.6 1.6 1.6a1.724 1.724 0 001.01 2.573c1.14.3 1.14 1.603 0 1.902a1.724 1.724 0 00-1.01 2.573c.58 1-.6 2.18-1.6 1.6a1.724 1.724 0 00-2.573 1.01c-.3 1.14-1.603 1.14-1.902 0a1.724 1.724 0 00-2.573-1.01c-1 .58-2.18-.6-1.6-1.6a1.724 1.724 0 00-1.01-2.573c-1.14-.3-1.14-1.603 0-1.902a1.724 1.724 0 001.01-2.573c-.58-1 .6-2.18 1.6-1.6a1.724 1.724 0 002.573-1.01z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7z"></path></svg>
                        <span>تنظیمات سیستم</span>
                    </div>
                    <svg :class="openMenu === 'system-settings' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'system-settings'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @can('full-access')
                        <button type="button" wire:click="selectSection('system-settings-user-management')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'system-settings-user-management' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            مدیریت کاربران
                        </button>
                    @endcan
                    <button type="button" wire:click="selectSection('system-settings-user-account')" class="block w-full text-right px-4 py-2 text-sm {{ $activeSection === 'system-settings-user-account' ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                        حساب کاربری
                    </button>
                </div>
            </div>
        @else
            <div>
                <button type="button" @click="openMenu = openMenu === 'system-settings' ? '' : 'system-settings'" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('admin.user-management') || request()->routeIs('admin.user-account') ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-1.14 1.603-1.14 1.902 0a1.724 1.724 0 002.573 1.01c1-.58 2.18.6 1.6 1.6a1.724 1.724 0 001.01 2.573c1.14.3 1.14 1.603 0 1.902a1.724 1.724 0 00-1.01 2.573c.58 1-.6 2.18-1.6 1.6a1.724 1.724 0 00-2.573 1.01c-.3 1.14-1.603 1.14-1.902 0a1.724 1.724 0 00-2.573-1.01c-1 .58-2.18-.6-1.6-1.6a1.724 1.724 0 00-1.01-2.573c-1.14-.3-1.14-1.603 0-1.902a1.724 1.724 0 001.01-2.573c-.58-1 .6-2.18 1.6-1.6a1.724 1.724 0 002.573-1.01z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7z"></path></svg>
                        <span>تنظیمات سیستم</span>
                    </div>
                    <svg :class="openMenu === 'system-settings' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'system-settings'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @can('full-access')
                        <a href="{{ route('admin.user-management') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.user-management') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            مدیریت کاربران
                        </a>
                    @endcan
                    <a href="{{ route('admin.user-account') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.user-account') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                        حساب کاربری
                    </a>
                </div>
            </div>
        @endif

    </nav>

    <div class="mt-auto pt-4 border-t border-indigo-800/50">
        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <button type="submit"
                    @click.prevent="if (confirm('آیا مطمئن هستید که می‌خواهید از سیستم خارج شوید؟')) $el.closest('form').submit()"
                    class="group flex w-full items-center gap-3 rounded-xl bg-indigo-800/30 px-4 py-3 text-sm font-semibold text-indigo-100 shadow-sm transition-all duration-200 hover:bg-indigo-700/40 hover:shadow-md hover:shadow-indigo-500/20 active:scale-[0.98]">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 transition-all duration-200 group-hover:bg-indigo-400/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </div>
                <span class="transition-colors group-hover:text-white">خروج از سیستم</span>
            </button>
        </form>
    </div>


</aside>
