<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'مرکز نیکوکاری تخصصی کودکان آوای همدلی')</title>

    {{-- Bootstrap RTL --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- ✅ Persian Datepicker CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css" rel="stylesheet">

    {{-- فونت وزیر --}}
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- استایل سفارشی تقویم --}}
    @stack('styles')

    <!-- استایل‌های محلی پروژه -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
        }
    </style>
    @livewireStyles
</head>
<body>
@include('layouts.header')

<main class="container py-4">
    @yield('content')
</main>

{{-- jQuery (مورد نیاز برای persian-datepicker) --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
@livewireScripts
</body>
</html>

