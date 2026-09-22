@extends('layouts.magazine')

@section('content')
    @php
        // رنگ شاخۀ «ارتباط با خدا» — هم‌رنگ کارت همان بخش در مجله
        $accent = '#0e7a55';

        // آیتم‌های این بخش
        $items = [
            [
                'title' => 'زیارت عاشورا',
                'subtitle' => 'متن کامل زیارت امام حسین علیه‌السلام',
                'href' => route('magazine.ziyarat-ashura'),
                'icon' => 'shrine',
            ],
            [
                'title' => 'دعای توسل',
                'subtitle' => 'توسل به پیامبر و ائمه اطهار علیهم‌السلام',
                'href' => route('magazine.tavassol'),
                'icon' => 'tawassul',
            ],
        ];

        $icons = [
            'shrine' => '<path d="M12 2v1.7"/><path d="M12 3.7c2.7 1.7 4.1 3.8 4.1 6.1H7.9c0-2.3 1.4-4.4 4.1-6.1Z"/><path d="M7.9 9.8h8.2"/><path d="M9.3 9.8V20M14.7 9.8V20"/><path d="M12.6 20v-2.4a.9.9 0 0 0-1.2-.9 1.4 1.4 0 0 0-.9.9V20"/><path d="M4.8 20h14.4"/>',
            'tawassul' => '<path d="M12 3.4c2.2 2.3 3.4 5 3.4 7.8 0 2-.9 4-2.5 5.6a1.2 1.2 0 0 1-1.8 0C9.5 15.2 8.6 13.2 8.6 11.2c0-2.8 1.2-5.5 3.4-7.8Z"/><path d="M12 6.8v9"/><path d="m9.5 17.8-.7 2.6M14.5 17.8l.7 2.6"/>',
        ];
    @endphp

    @include('partials.magazine-sub-header', [
        'backHref' => route('magazine.index'),
        'backLabel' => 'بازگشت به مجلۀ همدلی',
        'pageTitle' => 'ارتباط با خدا',
    ])

    <main id="magazine-top" class="bg-white pb-14 pt-16 sm:pb-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <!-- عنوان بخش، هم‌سبک تیترهای مجله -->
            <div class="flex items-center justify-center gap-2 pt-6 sm:gap-3 sm:pt-10" data-reveal>
                <span class="h-px w-8 bg-gradient-to-l from-transparent to-[{{ $accent }}]/50 sm:w-16" aria-hidden="true"></span>
                <h1 class="text-sm font-bold text-[{{ $accent }}] sm:text-base">ارتباط با خدا</h1>
                <span class="h-px w-8 bg-gradient-to-r from-transparent to-[{{ $accent }}]/50 sm:w-16" aria-hidden="true"></span>
            </div>
            <p class="mt-2 text-center text-[11px] leading-5 text-slate-500 sm:mt-3 sm:text-sm sm:leading-6" data-reveal>
                زیارت‌ها و ادعیه‌های مرکز آوای همدلی
            </p>

            <!-- آیتم‌های بخش -->
            <div class="mt-4 grid grid-cols-1 gap-2.5 sm:mt-9 sm:grid-cols-2 sm:gap-4">
                @foreach($items as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="group relative flex flex-row items-center gap-2.5 overflow-hidden rounded-2xl border border-slate-100 bg-white py-2.5 pr-3 pl-2 shadow-[0_1px_3px_rgba(15,23,42,0.05)] transition duration-500 ease-out hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[{{ $accent }}] sm:flex-col sm:items-stretch sm:gap-0 sm:rounded-[1.5rem] sm:p-5"
                        data-reveal
                    >
                        <!-- خط رنگی زیر کارت -->
                        <span class="absolute inset-x-0 bottom-0 h-[3px] origin-right scale-x-0 transition-transform duration-500 ease-out group-hover:scale-x-100" style="background-color: {{ $accent }};" aria-hidden="true"></span>

                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[0.95rem] text-white shadow-sm transition duration-500 ease-out group-hover:-rotate-3 group-hover:scale-105 sm:h-14 sm:w-14 sm:rounded-[1.3rem] sm:self-start"
                            style="background-color: {{ $accent }};"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5 sm:h-[1.7rem] sm:w-[1.7rem]" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                {!! $icons[$item['icon']] !!}
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1 sm:mt-4 sm:shrink-0">
                            <span class="block truncate text-[13px] font-extrabold leading-6 text-slate-900 transition-colors duration-300 group-hover:text-[{{ $accent }}] sm:text-base sm:leading-7">{{ $item['title'] }}</span>
                            <span class="block truncate text-[10px] leading-5 text-slate-400 sm:mt-1 sm:text-xs sm:leading-6 sm:text-slate-500">{{ $item['subtitle'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </main>
@endsection
