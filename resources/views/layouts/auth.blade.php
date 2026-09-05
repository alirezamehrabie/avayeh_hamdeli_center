<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ورود به سامانه آوای همدلی' }}</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')
    @stack('styles')
</head>
<body class="min-h-screen bg-white antialiased">
    {{ $slot }}

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
