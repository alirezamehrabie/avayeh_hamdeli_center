<!-- بخش کمک شما (قلب صفحه) -->
<section id="help" class="landing-section bg-white pb-16 sm:pb-24" aria-labelledby="help-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="text-center" data-reveal>
            <span class="inline-flex items-center gap-2 rounded-full bg-[#D4205F]/10 px-4 py-1.5 text-xs font-bold text-[#A4184B]">
                <i class="bi bi-hand-thumbs-up-fill" aria-hidden="true"></i>
                کمک شما
            </span>
            <h2 id="help-title" class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl lg:text-4xl">
                با شما، امید بیشتر می‌شود
            </h2>
            <p class="mx-auto mt-3 max-w-xl text-base leading-7 text-slate-600">
                هر راهی برای همدلی، ارزش یک لبخند را دارد.
            </p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-3">
            @php
                $helpCards = [
                    [
                        'icon' => 'bi-gift-fill',
                        'title' => 'نیکوکاری مالی',
                        'desc' => 'با کمک مالی مستقیم، وعده‌های غذایی، پوشاک و لوازم تحریر کودکان را تأمین کنید.',
                        'gradient' => 'from-[#1572A1] to-[#36A9DF]',
                        'cta' => 'کمک مالی',
                        'href' => '#contact',
                    ],
                    [
                        'icon' => 'bi-person-heart',
                        'title' => 'حمایت پیوسته',
                        'desc' => 'حامی یک کودک باشید و در مسیر آموزش و رشد او، نقش‌آفرینی مستمر داشته باشید.',
                        'gradient' => 'from-[#5964AE] to-[#8b5fe0]',
                        'cta' => 'حامی شوید',
                        'href' => '#contact',
                    ],
                    [
                        'icon' => 'bi-people-fill',
                        'title' => 'داوطلب همدل',
                        'desc' => 'از وقت و مهارت خود بگویید؛ مربی، همراه آموزشی و دوست کودکان باشید.',
                        'gradient' => 'from-[#A4184B] to-[#D4205F]',
                        'cta' => 'داوطلب شوم',
                        'href' => '#contact',
                    ],
                ];
            @endphp
            @foreach($helpCards as $card)
                <article
                    data-reveal
                    class="group flex flex-col overflow-hidden rounded-3xl border border-slate-100 bg-[#f8fbff] p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-[#5964AE]/10"
                >
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white bg-gradient-to-br {{ $card['gradient'] }} text-white shadow-md">
                        <i class="{{ $card['icon'] }} text-2xl" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-5 text-lg font-black text-slate-900">{{ $card['title'] }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-7 text-slate-600">{{ $card['desc'] }}</p>
                    <a
                        href="{{ $card['href'] }}"
                        class="mt-6 inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border-2 border-[#5964AE]/20 px-6 text-sm font-black text-[#5964AE] transition group-hover:border-[#5964AE]/40 group-hover:bg-[#5964AE] group-hover:text-white"
                    >
                        {{ $card['cta'] }}
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>