<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحه مدیریت آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        @font-face { font-family: 'iransans'; } /* اگر فونت دارید */
        body { font-family: 'iransans', 'Vazir', Tahoma; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

{{ $slot }}

<x-notification-modal />

@livewireScriptConfig
@stack('scripts')
</body>
</html>
