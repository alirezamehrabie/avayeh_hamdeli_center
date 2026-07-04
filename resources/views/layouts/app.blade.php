<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'مرکز نیکوکاری تخصصی کودکان آوای همدلی')</title>

    @livewireStyles

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- استایل سفارشی تقویم --}}
    @stack('styles')

    <!-- استایل‌های محلی پروژه -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

@include('layouts.header')

<x-flash-alerts />

<main class="container py-4">
    {{ $slot }}
</main>

<x-notification-modal />

@livewireScriptConfig
@stack('scripts')
</body>
</html>

