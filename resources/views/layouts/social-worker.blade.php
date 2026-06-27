<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیشخوان مددکار - آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <x-flash-alerts />

    <div
        x-data="{
            sidebarOpen: window.innerWidth >= 1024,
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
            },
            syncSidebar() {
                if (window.innerWidth >= 1024) {
                    this.sidebarOpen = true;
                }
            }
        }"
        x-init="syncSidebar(); window.addEventListener('resize', () => syncSidebar())"
        class="flex h-screen overflow-hidden"
    >
        @include('layouts.partials.social-worker-sidebar', ['activeSection' => $activeSection ?? 'service-delivery'])

        <div
            x-show="sidebarOpen"
            x-transition.opacity.duration.300ms
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"
            style="display: none;"
        ></div>

        <div class="flex w-full flex-1 flex-col overflow-y-auto">
            <header class="flex items-center justify-between gap-4 border-b bg-white px-6 py-4 shadow-sm">
                <div class="flex items-center gap-4">
                    <button type="button" @click="toggleSidebar()" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100" aria-label="نمایش یا پنهان‌سازی منو">
                        <svg x-show="!sidebarOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" style="display: none;"><path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <svg x-show="sidebarOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>

                    <div class="relative">
                        <div class="inline-flex items-center bg-gray-50 border border-gray-100 rounded-lg p-1 pl-3">
                            <span class="bg-indigo-600 text-white text-[10px] px-2 py-1 rounded-md font-bold ml-2 shadow-sm">مددکار</span>
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

            <main class="px-1 py-4">
                <div class="container mx-auto">
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
