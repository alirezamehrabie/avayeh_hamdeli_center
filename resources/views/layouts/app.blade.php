<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'مرکز نیکوکاری تخصصی کودکان آوای همدلی')</title>

    @livewireStyles

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')

    {{-- استایل سفارشی تقویم --}}
    @stack('styles')

    <!-- استایل‌های محلی پروژه -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="pwa-safe-top">

@include('layouts.header')

<x-flash-alerts />

<main class="container py-4">
    {{ $slot }}
</main>

<x-notification-modal />

@livewireScriptConfig
<x-connection-indicator />

@stack('scripts')
</body>
</html>

