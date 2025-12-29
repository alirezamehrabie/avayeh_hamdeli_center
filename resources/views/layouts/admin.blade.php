<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت آوای همدلی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        @font-face { font-family: 'Vazir'; src: url('/fonts/Vazir.woff2'); } /* اگر فونت دارید */
        body { font-family: 'Vazir', Tahoma, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    @include('layouts.partials.sidebar')

    <div class="flex flex-col flex-1 w-full overflow-y-auto">
        <!-- Header -->
        @include('layouts.partials.header')

        <!-- Main Content -->
        <main class="p-6">
            <div class="container mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
