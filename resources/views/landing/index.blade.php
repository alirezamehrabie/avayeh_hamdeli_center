@extends('layouts.landing')

@section('content')
    @php
        // منبع واحد لینک‌های ناوبری: هم دسکتاپ و هم کشوی موبایل
        $navLinks = [
            ['href' => '#services', 'label' => 'خدمات', 'icon' => 'bi-grid'],
            ['href' => '#about', 'label' => 'درباره ما', 'icon' => 'bi-info-circle'],
            ['href' => '#impact', 'label' => 'عددهای ما', 'icon' => 'bi-bar-chart-line'],
            ['href' => '#stories', 'label' => 'قصه‌ها', 'icon' => 'bi-chat-heart'],
            ['href' => '#help', 'label' => 'کمک شما', 'icon' => 'bi-heart'],
            ['href' => '#contact', 'label' => 'تماس', 'icon' => 'bi-telephone'],
        ];

        // کاربر وارد‌شده به صفحه انتخاب نوع ورود نمی‌رود؛ مستقیم به پنل خود هدایت می‌شود.
        $currentPanelUrl = app(App\Services\LoginRedirector::class)->currentPanelUrl();
        $loginEntryUrl = $currentPanelUrl ?? route('login.select');
        $loginEntryLabel = $currentPanelUrl ? 'پنل کاربری' : 'ورود';
    @endphp
    @include('landing.partials.header')
    @include('landing.partials.slider')
    @include('landing.partials.services')
    @include('landing.partials.magazine')
    @include('landing.partials.impact')
    @include('landing.partials.hero')
    @include('landing.partials.trust')
    @include('landing.partials.about')
    @include('landing.partials.stories')
    @include('landing.partials.help')
    @include('landing.partials.contact')
    @include('landing.partials.footer')
    @include('landing.partials.bottom-bar')
    @include('landing.partials.mobile-nav')
@endsection