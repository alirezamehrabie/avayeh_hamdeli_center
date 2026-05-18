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
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200">
                        <img src="{{ asset('images/logo-sm.png') }}" alt="لوگوی مرکز آوای همدلی" class="h-10 w-10 object-contain">
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-cyan-700">پنل مددکار اجتماعی</p>
                        <h1 class="text-base font-black text-slate-800 sm:text-lg">مرکز نیکوکاری تخصصی کودکان آوای همدلی</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600 sm:block">
                        <span class="font-semibold text-slate-800">
                            {{ trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'کاربر' }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 transition hover:bg-rose-100"
                        >
                            خروج از سیستم
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
