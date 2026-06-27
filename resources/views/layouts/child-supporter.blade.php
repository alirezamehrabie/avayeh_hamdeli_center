<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیشخوان حامی کودک - آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="child-supporter-shell h-screen overflow-hidden bg-slate-100 text-slate-900">
    <x-flash-alerts />

    <div
        x-data="{
            sidebarOpen: window.innerWidth >= 1024,
            resizeHandler: null,
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
            },
            syncSidebar() {
                if (window.innerWidth >= 1024) {
                    this.sidebarOpen = true;
                }
            },
            init() {
                this.syncSidebar();
                this.resizeHandler = () => this.syncSidebar();
                window.addEventListener('resize', this.resizeHandler);
                this.$watch('sidebarOpen', (value) => {
                    document.body.classList.toggle('overflow-hidden', value && window.innerWidth < 1024);
                });
            },
            destroy() {
                if (this.resizeHandler) {
                    window.removeEventListener('resize', this.resizeHandler);
                }
                document.body.classList.remove('overflow-hidden');
            }
        }"
        x-init="init(); return () => destroy()"
        class="flex h-dvh overflow-hidden"
    >
        @include('layouts.partials.child-supporter-sidebar')

        <div
            x-show="sidebarOpen"
            x-transition.opacity.duration.300ms
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
            style="display: none;"
        ></div>

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <header class="shrink-0 flex items-center justify-between gap-4 border-b bg-white px-6 py-4 shadow-sm">
                <div class="flex items-center gap-4">
                    <button type="button" @click="toggleSidebar()" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100" aria-label="نمایش یا پنهان‌سازی منو">
                        <svg x-show="!sidebarOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" style="display: none;"><path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <svg x-show="sidebarOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>

                    <div class="relative">
                        <div class="inline-flex items-center rounded-lg border border-gray-100 bg-gray-50 p-1 pl-3">
                            <span class="ml-2 rounded-md bg-indigo-600 px-2 py-1 text-[10px] font-bold text-white shadow-sm">حامی کودک</span>
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
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto px-1 py-4">
                <div class="container mx-auto min-w-0">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <x-notification-modal />

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
