<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'ورود به سامانه آوای همدلی' }}</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')
    @stack('styles')
    <style>
        body { font-family: 'iransans', 'Vazir', Tahoma, sans-serif; }
    </style>
</head>
<body class="pwa-safe-top pwa-safe-bottom min-h-screen bg-[#f8fbff] antialiased">
    {{ $slot }}

    @livewireScriptConfig
    <x-connection-indicator />

    @stack('scripts')
</body>
</html>
