<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'مرکز نیکوکاری تخصصی کودکان آوای همدلی')</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <!-- فونت وزیر (Vazirmatn) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@latest/Vazirmatn.css">

    <!-- استایل‌های محلی پروژه -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            direction: rtl;
            text-align: right;
        }
        label, input, select, textarea, button {
            font-family: 'Vazirmatn', sans-serif;
        }
    </style>
</head>
<body>
<header style="background:#fff; box-shadow:0 4px 15px rgba(0,0,0,0.05); padding:12px 36px; border-radius:12px; display:flex; align-items:center; position: sticky; top: 0; z-index: 1">
    <img src="{{ asset('images/logo.png') }}" style="height:64px;">
    <div style="margin-right:16px;">
        <h1 style="font-size:22px; color:#3aa5dc; font-family:'Vazirmatn',sans-serif;">مرکز نیکوکاری تخصصی کودکان آوای همدلی</h1>
        <span style="color:#d01e61; font-family:'Vazirmatn',sans-serif;">شماره ثبت 140137681</span>
    </div>
</header>

<main style="padding:24px;">
    @yield('content')
</main>
</body>
</html>

