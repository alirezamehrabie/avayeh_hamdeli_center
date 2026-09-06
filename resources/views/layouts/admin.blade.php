<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>صفحه مدیریت آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')
    @livewireStyles
    <style>
        @font-face { font-family: 'iransans'; } /* اگر فونت دارید */
        body { font-family: 'iransans', 'Vazir', Tahoma; }
    </style>
</head>
<body class="pwa-safe-top h-full overflow-hidden bg-gray-100 text-gray-800">

<x-flash-alerts />

{{ $slot }}

<x-notification-modal />

@livewireScriptConfig
<x-connection-indicator />

@stack('scripts')
</body>
</html>
