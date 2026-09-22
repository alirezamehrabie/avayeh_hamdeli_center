@extends('layouts.magazine')

@section('content')
    @php
        // رنگ شاخۀ «ارتباط با خدا»
        $accent = '#0e7a55';
    @endphp

    @include('partials.magazine-sub-header', [
        'backHref' => route('magazine.spiritual'),
        'backLabel' => 'بازگشت به ارتباط با خدا',
        'pageTitle' => 'زیارت عاشورا',
    ])

    <main id="magazine-top" class="bg-white pb-14 pt-16 sm:pb-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <!-- عنوان صفحه، هم‌سبک تیترهای مجله -->
            <div class="flex items-center justify-center gap-2 pt-6 sm:gap-3 sm:pt-10" data-reveal>
                <span class="h-px w-8 bg-gradient-to-l from-transparent to-[{{ $accent }}]/50 sm:w-16" aria-hidden="true"></span>
                <h1 class="text-sm font-bold text-[{{ $accent }}] sm:text-base">زیارت عاشورا</h1>
                <span class="h-px w-8 bg-gradient-to-r from-transparent to-[{{ $accent }}]/50 sm:w-16" aria-hidden="true"></span>
            </div>
            <p class="mt-2 text-center text-[11px] leading-5 text-slate-500 sm:mt-3 sm:text-sm sm:leading-6" data-reveal>
                متن کامل و معتبر زیارت امام حسین علیه‌السلام
            </p>

            <!-- متن زیارت با فونت قرآنی -->
            <div class="font-quran mx-auto mt-6 max-w-3xl space-y-5 text-justify text-[1.1rem] leading-[2.2] text-slate-800 sm:mt-10 sm:space-y-7 sm:text-[1.25rem] sm:leading-[2.4]">
                @foreach($paragraphs as $paragraph)
                    @php
                        // عبارت‌های راهنمای خواندن (مثل «صد مرتبه می گویی :») به‌صورت برچسب جدا از متن عربی
                        $instruction = null;
                        $body = $paragraph;
                        if (preg_match('/^(.+?گویی\s*:)\s*(.*)$/u', $paragraph, $m)) {
                            $instruction = trim($m[1]);
                            $body = trim($m[2]);
                        }
                    @endphp
                    <p>
                        @if($instruction !== null)
                            <span class="mb-1.5 block font-sans text-[11px] font-bold leading-5 text-[{{ $accent }}]/80 sm:text-xs sm:leading-6">{{ $instruction }}</span>
                        @endif
                        <span>{{ $body }}</span>
                    </p>
                @endforeach
            </div>
        </div>
    </main>
@endsection
