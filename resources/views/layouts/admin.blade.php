<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحه مدیریت آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        @font-face { font-family: 'Vazir'; src: url('/fonts/Vazir.woff2'); } /* اگر فونت دارید */
        body { font-family: 'Vazir', Tahoma, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

{{ $slot }}

@livewireScriptConfig
@stack('scripts')
</body>
</html>
