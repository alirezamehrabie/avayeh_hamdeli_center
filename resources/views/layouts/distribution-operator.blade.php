<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیشخوان اپراتور توزیع - آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-800">
    <div
        x-data="{
            sidebarOpen: window.innerWidth >= 1024,
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
            },
            syncSidebar() {
                this.sidebarOpen = window.innerWidth >= 1024;
            }
        }"
        x-init="syncSidebar(); window.addEventListener('resize', () => syncSidebar())"
        class="flex h-screen overflow-hidden"
        dir="rtl"
    >
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
            <div class="mb-8 border-b border-indigo-700/30 pb-0">
                <div class="space-y-3 text-center">
                    <div class="flex justify-center">
                        <div class="h-14 w-14 overflow-hidden rounded-2xl opacity-80 shadow-lg shadow-indigo-500/10">
                            <img src="{{ asset('images/logo-wh.webp') }}" width="150" height="150" alt="لوگوی آوای همدلی" class="h-full w-full object-cover">
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-[11px] font-light tracking-wide text-indigo-100/80">
                            مرکز نیکوکاری تخصصی کودکان
                        </p>
                        <h1 class="bg-gradient-to-l from-indigo-200 via-purple-100 to-indigo-200 bg-clip-text text-3xl font-black text-transparent">
                            آوای همدلی
                        </h1>
                        <p class="mt-2 text-xs font-semibold text-violet-200">پنل اپراتور توزیع</p>
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
                $isDefineServiceActive = request()->routeIs('distribution-operator.define-service');
                $isServiceListActive = request()->routeIs('distribution-operator.service-list')
                    || request()->routeIs('distribution-operator.edit-service')
                    || request()->routeIs('distribution-operator.edit-allocations');
                $gateNavigationItems = [
                    [
                        'label' => 'گیت ورود',
                        'hint' => 'ورود',
                        'route' => 'distribution-operator.gates.entry',
                        'ability' => 'access-distribution-inbound-gate',
                        'active' => request()->routeIs('distribution-operator.gates.entry'),
                    ],
                    [
                        'label' => 'گیت تحویل',
                        'hint' => 'تحویل',
                        'route' => 'distribution-operator.gates.delivery',
                        'ability' => 'access-distribution-delivery-gate',
                        'active' => request()->routeIs('distribution-operator.gates.delivery'),
                    ],
                    [
                        'label' => 'گیت خروج',
                        'hint' => 'خروج',
                        'route' => 'distribution-operator.gates.exit',
                        'ability' => 'access-distribution-outbound-gate',
                        'active' => request()->routeIs('distribution-operator.gates.exit'),
                    ],
                ];
                $isUserAccountActive = request()->routeIs('distribution-operator.user-account');
            @endphp

            <nav class="flex-1 space-y-2">
                <a
                    href="{{ route('distribution-operator.define-service') }}"
                    class="flex items-center justify-between rounded-lg px-4 py-2.5 transition-colors {{ $isDefineServiceActive ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}"
                >
                    <span>تخصیص خدمت</span>
                    <span class="text-xs text-indigo-100/80">ثبت</span>
                </a>

                <a
                    href="{{ route('distribution-operator.service-list') }}"
                    class="flex items-center justify-between rounded-lg px-4 py-2.5 transition-colors {{ $isServiceListActive ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}"
                >
                    <span>فهرست خدمات</span>
                    <span class="text-xs text-indigo-100/80">ویرایش</span>
                </a>

                @foreach($gateNavigationItems as $gateNavigationItem)
                    @can($gateNavigationItem['ability'])
                        <a
                            href="{{ route($gateNavigationItem['route']) }}"
                            class="flex items-center justify-between rounded-lg px-4 py-2.5 transition-colors {{ $gateNavigationItem['active'] ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}"
                        >
                            <span>{{ $gateNavigationItem['label'] }}</span>
                            <span class="text-xs text-indigo-100/80">{{ $gateNavigationItem['hint'] }}</span>
                        </a>
                    @endcan
                @endforeach

                @can('access-activity-operator-panel')
                    <div class="my-1 border-t border-indigo-700/70" role="separator" aria-hidden="true"></div>

                    <a
                        href="{{ route('activity-operator.dashboard') }}"
                        class="flex items-center justify-between rounded-lg px-4 py-2.5 transition-colors hover:bg-indigo-800 bg-blue-700"
                    >
                        <span>حضور و غیاب</span>
                        <span class="text-xs text-indigo-100/80">فعالیت</span>
                    </a>
                @endcan

                <div class="my-1 border-t border-indigo-700/70" role="separator" aria-hidden="true"></div>

                <a
                    href="{{ route('distribution-operator.user-account') }}"
                    class="flex items-center justify-between rounded-lg px-4 py-2.5 transition-colors {{ $isUserAccountActive ? 'bg-indigo-700' : 'hover:bg-indigo-800' }}"
                >
                    <div class="flex items-center">
                        <span>حساب کاربری</span>
                    </div>
                    <span class="text-xs text-indigo-100/80">پروفایل</span>
                </a>
            </nav>

                <div class="mt-auto pt-4 border-t border-indigo-800/50">
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <button type="submit"
                                @click.prevent="if (confirm('آیا مطمئن هستید که می‌خواهید از سیستم خارج شوید؟')) $el.closest('form').submit()"
                                class="group flex min-h-12 w-full items-center gap-3 rounded-xl border border-indigo-700/50 bg-indigo-800/25 px-4 py-3 text-right text-sm font-semibold text-indigo-100 shadow-sm shadow-indigo-950/10 transition-all duration-200 hover:border-rose-300/30 hover:bg-indigo-800/40 hover:text-white hover:shadow-md hover:shadow-indigo-950/20 focus:outline-none focus:ring-4 focus:ring-indigo-400/20 active:scale-[0.99]">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-600/35 bg-indigo-500/10 text-indigo-200 transition-all duration-200 group-hover:border-rose-300/40 group-hover:bg-rose-400/10 group-hover:text-rose-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </div>
                            <span class="transition-colors group-hover:text-white">خروج از سیستم</span>
                        </button>
                    </form>
                </div>
        </aside>

        <div
            x-show="sidebarOpen"
            x-transition.opacity.duration.300ms
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"
            style="display: none;"
        ></div>

        <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">
            <header class="flex items-center justify-between gap-4 border-b bg-white px-4 py-3 shadow-sm sm:px-6">
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        @click="toggleSidebar()"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        aria-label="نمایش یا پنهان‌سازی منو"
                    >
                        <svg x-show="!sidebarOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" style="display: none;">
                            <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <svg x-show="sidebarOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" style="display: none;">
                            <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>

                    <div class="relative">
                        <div class="inline-flex items-center bg-gray-50 border border-gray-100 rounded-lg p-1 pl-3">
                            <span class="bg-indigo-600 text-white text-[10px] px-2 py-1 rounded-md font-bold ml-2 shadow-sm">اپراتور توزیع</span>
                            <span class="text-xs font-semibold text-gray-700">{{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? 'کاربر' }}</span>
                        </div>
                    </div>

                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-white ring-2 ring-indigo-100 shadow-sm">
                            @if (auth()->user()->profile_photo_path)
                                <img
                                    src="{{ asset(auth()->user()->profile_photo_path) }}"
                                    alt="تصویر پروفایل {{ auth()->user()->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-indigo-600 text-sm font-bold text-white">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->first_name ?: 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-notification-modal />

    @livewireScriptConfig
    @if (session()->has('distribution-operator-notification'))
        <script>
            window.addEventListener('alpine:init', () => {
                window.requestAnimationFrame(() => {
                    window.dispatchEvent(new CustomEvent('open-notification-modal', {
                        detail: {
                            config: @js(session('distribution-operator-notification')),
                        },
                    }));
                });
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
