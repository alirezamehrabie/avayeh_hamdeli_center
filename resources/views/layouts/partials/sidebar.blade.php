<aside
    id="dashboard-sidebar"
    x-ref="sidebarPanel"
    x-show="sidebarOpen"
    tabindex="-1"
    aria-label="منوی اصلی"
    @keydown.escape.stop="closeSidebarOnMobile()"
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
                <div
                    class="w-14 h-14 opacity-80 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/10 overflow-hidden">
                    <!-- در اینجا آدرس تصویر لوگوی خود را وارد کنید -->
                    <img src="{{ asset('images/logo-wh.webp') }}" width="150" height="150" alt="لوگوی آوای همدلی" class="w-full h-full object-cover">
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
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-semibold text-indigo-100 bg-indigo-900/40 rounded-full border border-indigo-600/40 backdrop-blur-sm">
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
        $peopleOpen = $dashboardMode ? $isActive(['people-fast-create', 'people-list', 'people-incomplete-cases', 'people-block-list', 'person-create', 'person-edit', 'beneficiary-case-file']) : request()->routeIs('people.*');
        $socialWorkersOpen = $dashboardMode ? $isActive(['social-workers-list', 'social-workers-block-list', 'social-worker-create', 'social-worker-edit']) : request()->routeIs('social-workers.*');
        $guardiansOpen = $dashboardMode ? $isActive(['guardians-list', 'guardians-block-list']) : request()->routeIs('guardians.*');
        $reportsOpen = $dashboardMode ? $isActive(['advanced-reports', 'advanced-service-report', 'advanced-beneficiary-report', 'advanced-operator-report', 'advanced-supervisor-report', 'advanced-social-worker-report', 'advanced-gate-report']) : false;
        $servicesOpen = $dashboardMode ? $isActive(['service-definition', 'service-management', 'service-list', 'service-delivery', 'service-delivery-social-worker', 'service-delivery-beneficiary', 'service-archive']) : false;
        $activitiesOpen = $dashboardMode ? $isActive(['activity-definition', 'activity-list', 'activity-scanner', 'activity-operator-assignments']) : false;
        $childSupporterOpen = $dashboardMode ? $isActive(['child-supporter-sponsor-registration', 'child-supporter-sponsor-edit', 'child-supporter-sponsor-list']) : false;
        $specialFeaturesOpen = $dashboardMode ? $isActive(['special-features-id-card-scanner']) : false;
        $notificationsOpen = $dashboardMode ? $isActive(['notifications-center', 'notifications-settings']) : false;
        $userManagementOpen = $dashboardMode ? $isActive(['system-settings-user-definition', 'system-settings-user-list']) : request()->routeIs('admin.user-definition') || request()->routeIs('admin.user-management') || request()->routeIs('admin.user-list');
        $systemSettingsOpen = $dashboardMode ? $isActive(['system-settings-user-account']) : request()->routeIs('admin.user-account');
        $dashboardReportsLinkActive = ! $dashboardMode && request()->routeIs('admin.dashboard') && request()->query('section') && in_array(request()->query('section'), ['advanced-reports', 'advanced-operator-report'], true);
        $defaultOpenMenu = '';

        if ($peopleOpen) {
            $defaultOpenMenu = 'people';
        } elseif ($socialWorkersOpen) {
            $defaultOpenMenu = 'social-workers';
        } elseif ($guardiansOpen) {
            $defaultOpenMenu = 'guardians';
        } elseif ($servicesOpen) {
            $defaultOpenMenu = 'services';
        } elseif ($reportsOpen) {
            $defaultOpenMenu = 'reports';
        } elseif ($childSupporterOpen) {
            $defaultOpenMenu = 'child-supporter';
        } elseif ($specialFeaturesOpen) {
            $defaultOpenMenu = 'special-features';
        } elseif ($notificationsOpen) {
            $defaultOpenMenu = 'notifications';
        } elseif ($userManagementOpen) {
            $defaultOpenMenu = 'user-management';
        } elseif ($systemSettingsOpen) {
            $defaultOpenMenu = 'system-settings';
        }

        $user = auth()->user();
        $unreadNotificationsCount = ($dashboardMode && $user?->can('manage-notifications'))
            ? \App\Models\ManagerNotification::query()->forManager($user->id)->unread()->count()
            : 0;
        $dashboardMenuItems = [
            'people' => [
                ['section' => 'people-list', 'label' => 'لیست مددجویان', 'icon' => 'fa fa-users', 'active' => ['people-list', 'person-edit'], 'visible' => $user?->can('manage-people')],
                ['section' => 'people-incomplete-cases', 'label' => 'پرونده‌های ناقص', 'icon' => 'fa fa-exclamation-triangle', 'visible' => $user?->can('access-admin-panel')],
                ['section' => 'person-create', 'label' => 'فرم کامل ثبت نام', 'icon' => 'fa fa-user-plus', 'visible' => $user?->can('people-register')],
                ['section' => 'people-fast-create', 'label' => 'ثبت سریع مددجو', 'icon' => 'fa fa-bolt', 'visible' => $user?->can('people-register')],
                ['section' => 'people-block-list', 'label' => 'مددجویان غیرفعال', 'icon' => 'fa fa-ban', 'visible' => $user?->can('people-delete')],
                ['section' => 'beneficiary-case-file', 'label' => 'پرونده مددجو', 'icon' => 'fa fa-folder-open', 'visible' => $user?->can('access-admin-panel')],
            ],
            'social-workers' => [
                ['section' => 'social-workers-list', 'label' => 'لیست مددکاران', 'active' => ['social-workers-list', 'social-worker-edit']],
                ['section' => 'social-worker-create', 'label' => 'ثبت مددکار جدید'],
                ['section' => 'social-workers-block-list', 'label' => 'مددکاران غیرفعال'],
            ],
            'guardians' => [
                ['section' => 'guardians-list', 'label' => 'لیست سرپرستان'],
                ['section' => 'guardians-block-list', 'label' => 'سرپرستان غیرفعال'],
            ],
            'services' => [
                ['section' => 'service-list', 'label' => 'مدیریت خدمات'],
                ['section' => 'service-delivery', 'label' => 'تحویل خدمات', 'active' => ['service-delivery', 'service-delivery-social-worker', 'service-delivery-beneficiary']],
            ],
            'activities' => [
                ['section' => 'activity-list', 'label' => 'مدیریت فعالیت‌ها'],
                ['section' => 'activity-definition', 'label' => 'تعریف فعالیت', 'visible' => $user?->can('full-access')],
            ],
            'child-supporter' => [
                ['section' => 'child-supporter-sponsor-registration', 'label' => 'ثبت نام حامی'],
                ['section' => 'child-supporter-sponsor-list', 'label' => 'لیست حامیان', 'active' => ['child-supporter-sponsor-list', 'child-supporter-sponsor-edit']],
            ],
            'special-features' => [
                ['section' => 'special-features-id-card-scanner', 'label' => 'اسکن کارت شناسایی'],
            ],
            'notifications' => [
                ['section' => 'notifications-center', 'label' => 'مرکز اعلان‌ها', 'badge' => $unreadNotificationsCount],
                ['section' => 'notifications-settings', 'label' => 'تنظیمات اعلان‌ها'],
            ],
            'reports' => [
                ['section' => 'advanced-beneficiary-report', 'label' => 'گزارش مددجویان', 'visible' => $user?->can('full-access')],
                ['section' => 'advanced-supervisor-report', 'label' => 'گزارش سرپرستان', 'visible' => $user?->can('full-access')],
                ['section' => 'advanced-social-worker-report', 'label' => 'گزارش مددکاران', 'visible' => $user?->can('full-access')],
                ['section' => 'advanced-service-report', 'label' => 'گزارش خدمات'],
                ['section' => 'advanced-operator-report', 'label' => 'گزارش اپراتورها'],
                ['section' => 'advanced-gate-report', 'label' => 'گزارش گیت‌ها'],
            ],
            'user-management' => [
                ['section' => 'system-settings-user-definition', 'label' => 'تعریف کاربر'],
                ['section' => 'system-settings-user-list', 'label' => 'لیست کاربران'],
            ],
            'system-settings' => [
                ['section' => 'system-settings-user-account', 'label' => 'حساب کاربری'],
            ],
        ];
    @endphp

    <nav x-data="{ openMenu: '{{ $defaultOpenMenu }}' }" class="flex-1 space-y-2">
        @if($dashboardMode)
            <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'overview' })"
                    class="flex items-center w-full px-4 py-2.5 rounded-lg transition-colors {{ $activeSection === 'overview' ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>پیشخوان</span>
            </button>
        @else
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>پیشخوان</span>
            </a>
        @endif

        @if($dashboardMode ? ($user?->can('manage-people') || $user?->can('access-admin-panel')) : $user?->can('manage-people'))
            <div>
                <button type="button" @click="openMenu = openMenu === 'people' ? '' : 'people'"
                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $peopleOpen ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>مددجویان</span>
                    </div>
                    <svg :class="openMenu === 'people' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="openMenu === 'people'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @if($dashboardMode)

                        @foreach($dashboardMenuItems['people'] as $item)
                            @continue(array_key_exists('visible', $item) && ! $item['visible'])
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                            >
                                @isset($item['icon'])
                                    <i class="{{ $item['icon'] }}"></i>
                                @endisset
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    @else
                        @can('people-register')
                            <a href="{{ route('people.fast-create') }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('people.fast-create') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i
                                    class="fa fa-bolt"></i> ثبت سریع فرد</a>
                        @endcan
                        <a href="{{ route('people.index') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('people.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i
                                class="fa fa-users"></i> لیست مددجویان</a>
                        @can('people-delete')
                            <a href="{{ route('people.block-list') }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('people.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i
                                    class="fa fa-ban"></i> بلاک لیست مددجویان</a>
                        @endcan
                        @can('people-register')
                            <a href="{{ route('people.form', ['mode' => 'create']) }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('people.form') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}"><i
                                    class="fa fa-user-plus"></i> ثبت مددجوی جدید (کامل)</a>
                        @endcan
                    @endif
                </div>
            </div>
        @endif

        @can('manage-social-workers')
            @if($dashboardMode)
                <div>
                    <button type="button" @click="openMenu = openMenu === 'social-workers' ? '' : 'social-workers'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $isActive(['social-workers-list', 'social-workers-block-list', 'social-worker-create', 'social-worker-edit']) ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>مددکاران</span>
                        </div>
                        <svg :class="openMenu === 'social-workers' ? 'rotate-180' : ''"
                             class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'social-workers'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        @foreach($dashboardMenuItems['social-workers'] as $item)
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                            >
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    </div>
                </div>
            @else
                <div>
                    <button type="button" @click="openMenu = openMenu === 'social-workers' ? '' : 'social-workers'"
                            class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('social-workers.*') ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>مددکاران اجتماعی</span>
                        </div>
                        <svg :class="openMenu === 'social-workers' ? 'rotate-180' : ''"
                             class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'social-workers'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <a href="{{ route('social-workers.index') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست
                            مددکاران</a>
                        <a href="{{ route('social-workers.block-list') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">بلاک
                            لیست مددکاران </a>
                        <a href="{{ route('social-workers.create') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('social-workers.create') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">ثبت
                            مددکار جدید</a>
                    </div>
                </div>
            @endif
        @endcan

        @can('full-access')
            @if($dashboardMode)
                <div>
                    <button type="button" @click="openMenu = openMenu === 'guardians' ? '' : 'guardians'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $isActive(['guardians-list', 'guardians-block-list']) ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"
                                      d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6 3 3 0 000 6zM5 12a3 3 0 100-6 3 3 0 000 6z"></path>
                            </svg>
                            <span>سرپرستان</span>
                        </div>
                        <svg :class="openMenu === 'guardians' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'guardians'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        @foreach($dashboardMenuItems['guardians'] as $item)
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                            >
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    </div>
                </div>
            @else
                <div>
                    <button type="button" @click="openMenu = openMenu === 'guardians' ? '' : 'guardians'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('guardians.*') ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6 3 3 0 000 6zM5 12a3 3 0 100-6 3 3 0 000 6z"></path>
                            </svg>
                            <span>سرپرستان</span>
                        </div>
                        <svg :class="openMenu === 'guardians' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'guardians'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <a href="{{ route('guardians.index') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('guardians.index') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">لیست
                            سرپرستان</a>
                        <a href="{{ route('guardians.block-list') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('guardians.block-list') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">بلاک
                            لیست سرپرستان</a>
                    </div>
                </div>
            @endif

            @if($dashboardMode)
                <div>
                    <button type="button" @click="openMenu = openMenu === 'services' ? '' : 'services'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $isActive(['service-definition', 'service-management', 'service-list', 'service-delivery', 'service-delivery-social-worker', 'service-delivery-beneficiary', 'service-archive']) ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h8m-8 6h16"></path>
                            </svg>
                            <span>خدمات</span>
                        </div>
                        <svg :class="openMenu === 'services' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'services'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        @foreach($dashboardMenuItems['services'] as $item)
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                            >
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endcan


        @can('access-admin-panel')
            @if($dashboardMode)
                <div>
                    <button type="button" @click="openMenu = openMenu === 'activities' ? '' : 'activities'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $activitiesOpen ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>فعالیت‌ها</span>
                        </div>
                        <svg :class="openMenu === 'activities' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'activities'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        @foreach($dashboardMenuItems['activities'] as $item)
                            @continue(array_key_exists('visible', $item) && ! $item['visible'])
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                            >
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endcan

        @if($dashboardMode)
            <div>
                <button type="button" @click="openMenu = openMenu === 'child-supporter' ? '' : 'child-supporter'"
                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $childSupporterOpen ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span>حامی کودکان</span>
                    </div>
                    <svg :class="openMenu === 'child-supporter' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="openMenu === 'child-supporter'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @foreach($dashboardMenuItems['child-supporter'] as $item)
                        <x-sidebar.dashboard-section-button
                            :section="$item['section']"
                            :active="$isActive($item['active'] ?? $item['section'])"
                        >
                            {{ $item['label'] }}
                        </x-sidebar.dashboard-section-button>
                    @endforeach
                </div>
            </div>

            <div>
                <button type="button" @click="openMenu = openMenu === 'special-features' ? '' : 'special-features'"
                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $specialFeaturesOpen ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <path d="M8 9h4"></path>
                            <path d="M8 13h2.5"></path>
                            <circle cx="16.5" cy="12" r="2"></circle>
                        </svg>
                        <span>امکانات ویژه</span>
                    </div>
                    <svg :class="openMenu === 'special-features' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="openMenu === 'special-features'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @foreach($dashboardMenuItems['special-features'] as $item)
                        <x-sidebar.dashboard-section-button
                            :section="$item['section']"
                            :active="$isActive($item['active'] ?? $item['section'])"
                        >
                            {{ $item['label'] }}
                        </x-sidebar.dashboard-section-button>
                    @endforeach
                </div>
            </div>

            @can('manage-notifications')
                <div>
                    <button type="button" @click="openMenu = openMenu === 'notifications' ? '' : 'notifications'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $notificationsOpen ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            <span>اعلان‌ها</span>
                            @if($unreadNotificationsCount > 0)
                                <span class="mr-2 inline-flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-rose-500/90 px-1.5 text-[10px] font-bold leading-none text-white shadow-sm shadow-rose-900/30"
                                      aria-label="{{ $unreadNotificationsCount }} اعلان خوانده‌نشده">
                                    {{ $unreadNotificationsCount > 99 ? '۹۹+' : \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadNotificationsCount) }}
                                </span>
                            @endif
                        </div>
                        <svg :class="openMenu === 'notifications' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'notifications'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        @foreach($dashboardMenuItems['notifications'] as $item)
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                                :badge="$item['badge'] ?? null"
                            >
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    </div>
                </div>
            @endcan
        @endif


        @if($dashboardMode && auth()->user()?->can('access-admin-panel'))
            <div>
                <button type="button" @click="openMenu = openMenu === 'reports' ? '' : 'reports'"
                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $isActive(['advanced-reports', 'advanced-service-report', 'advanced-beneficiary-report', 'advanced-operator-report', 'advanced-supervisor-report', 'advanced-social-worker-report', 'advanced-gate-report']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2ZM9 17v-6m4 6V7m4 10v-4"/>
                        </svg>
                        <span>گزارش پیشرفته</span>
                    </div>
                    <svg :class="openMenu === 'reports' ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="openMenu === 'reports'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">

                    @foreach($dashboardMenuItems['reports'] as $item)
                        @continue(array_key_exists('visible', $item) && ! $item['visible'])
                        <x-sidebar.dashboard-section-button
                            :section="$item['section']"
                            :active="$isActive($item['active'] ?? $item['section'])"
                        >
                            {{ $item['label'] }}
                        </x-sidebar.dashboard-section-button>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ route('admin.dashboard', ['section' => auth()->user()?->can('full-access') ? 'advanced-reports' : 'advanced-operator-report']) }}"
               class="flex items-center px-4 py-3 rounded-lg transition-colors {{ $dashboardReportsLinkActive ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}">
                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span>{{ auth()->user()?->can('full-access') ? 'گزارش‌های پیشرفته' : 'گزارش اپراتورها' }}</span>
            </a>
        @endif

        @if($dashboardMode)
            @can('full-access')
                <div>
                    <button type="button" @click="openMenu = openMenu === 'user-management' ? '' : 'user-management'"
                            class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $userManagementOpen ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M19 8v6"/>
                                <path d="M22 11h-6"/>
                            </svg>
                            <span>مدیریت کاربران</span>
                        </div>
                        <svg :class="openMenu === 'user-management' ? 'rotate-180' : ''"
                             class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'user-management'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        @foreach($dashboardMenuItems['user-management'] as $item)
                            <x-sidebar.dashboard-section-button
                                :section="$item['section']"
                                :active="$isActive($item['active'] ?? $item['section'])"
                            >
                                {{ $item['label'] }}
                            </x-sidebar.dashboard-section-button>
                        @endforeach
                    </div>
                </div>
            @endcan

            <div>
                <button type="button" @click="openMenu = openMenu === 'system-settings' ? '' : 'system-settings'"
                        class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg hover:bg-indigo-800 {{ $isActive(['system-settings-user-account']) ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33 1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                        </svg>

                        <span>تنظیمات برنامه</span>
                    </div>
                    <svg :class="openMenu === 'system-settings' ? 'rotate-180' : ''"
                         class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="openMenu === 'system-settings'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    @foreach($dashboardMenuItems['system-settings'] as $item)
                        @continue(array_key_exists('visible', $item) && ! $item['visible'])
                        <x-sidebar.dashboard-section-button
                            :section="$item['section']"
                            :active="$isActive($item['active'] ?? $item['section'])"
                        >
                            {{ $item['label'] }}
                        </x-sidebar.dashboard-section-button>
                    @endforeach
                </div>
            </div>
        @else
            @can('full-access')
                <div>
                    <button type="button" @click="openMenu = openMenu === 'user-management' ? '' : 'user-management'"
                            class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('admin.user-definition') || request()->routeIs('admin.user-management*') || request()->routeIs('admin.user-list*') ? 'bg-indigo-700' : '' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 ml-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M19 8v6"/>
                                <path d="M22 11h-6"/>
                            </svg>
                            <span>مدیریت کاربران</span>
                        </div>
                        <svg :class="openMenu === 'user-management' ? 'rotate-180' : ''"
                             class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="openMenu === 'user-management'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                        <a href="{{ route('admin.user-definition') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.user-definition') || request()->routeIs('admin.user-management') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            تعریف کاربر
                        </a>
                        <a href="{{ route('admin.user-list') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.user-list*') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
                            لیست کاربران
                        </a>
                    </div>
                </div>
            @endcan

            <div>
                <button type="button" @click="openMenu = openMenu === 'system-settings' ? '' : 'system-settings'"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-indigo-800 {{ request()->routeIs('admin.user-account') ? 'bg-indigo-700' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M11.049 2.927c.3-1.14 1.603-1.14 1.902 0a1.724 1.724 0 002.573 1.01c1-.58 2.18.6 1.6 1.6a1.724 1.724 0 001.01 2.573c1.14.3 1.14 1.603 0 1.902a1.724 1.724 0 00-1.01 2.573c.58 1-.6 2.18-1.6 1.6a1.724 1.724 0 00-2.573 1.01c-.3 1.14-1.603 1.14-1.902 0a1.724 1.724 0 00-2.573-1.01c-1 .58-2.18-.6-1.6-1.6a1.724 1.724 0 00-1.01-2.573c-1.14-.3-1.14-1.603 0-1.902a1.724 1.724 0 001.01-2.573c-.58-1 .6-2.18 1.6-1.6a1.724 1.724 0 002.573-1.01z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7z"></path>
                        </svg>
                        <span>تنظیمات سیستم</span>
                    </div>
                    <svg :class="openMenu === 'system-settings' ? 'rotate-180' : ''"
                         class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="openMenu === 'system-settings'" x-collapse.duration.250ms class="mt-2 mr-8 space-y-1">
                    <a href="{{ route('admin.user-account') }}"
                       class="block px-4 py-2 text-sm {{ request()->routeIs('admin.user-account') ? 'text-white bg-indigo-800 rounded' : 'text-indigo-200 hover:text-white' }}">
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
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 transition-all duration-200 group-hover:bg-indigo-400/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-300" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <span class="transition-colors group-hover:text-white">خروج از سیستم</span>
            </button>
        </form>
    </div>


</aside>
