<!-- بخش درباره ما -->
<section id="about" class="landing-section bg-[#f8fbff]" aria-labelledby="about-title">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <!-- تصویر -->
            <div class="order-2 lg:order-1" data-reveal>
                <div class="relative">
                    <div class="overflow-hidden rounded-[2rem] bg-white p-4 shadow-xl shadow-slate-900/8 ring-1 ring-slate-100">
                        <div class="flex aspect-[4/3] items-center justify-center rounded-[1.6rem] bg-[linear-gradient(145deg,#f0f6fb_0%,#f6eff4_100%)]">
                            <img
                                src="{{ asset('images/logo-sm.png') }}"
                                alt="نشان آوای همدلی"
                                class="w-1/2 object-contain"
                                loading="lazy"
                            >
                        </div>
                    </div>
                    <div class="absolute -bottom-5 right-6 flex items-center gap-3 rounded-2xl border border-[#36A9DF]/20 bg-white px-5 py-3.5 shadow-lg shadow-slate-900/10">
                        <span class="text-2xl">💛</span>
                        <span class="text-xs font-bold leading-5 text-slate-700">با هم، آینده‌ی<br>بهتری برای کودکان</span>
                    </div>
                </div>
            </div>

            <!-- متن -->
            <div class="order-1 lg:order-2" data-reveal>
                <span class="inline-flex items-center gap-2 rounded-full bg-[#5964AE]/10 px-4 py-1.5 text-xs font-bold text-[#5964AE]">
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    درباره ما
                </span>
                <h2 id="about-title" class="mt-4 text-2xl font-black leading-snug text-slate-900 sm:text-3xl lg:text-4xl">
                    جایی که هر کودک، دیده می‌شود
                </h2>
                <p class="mt-5 text-base leading-8 text-slate-600 sm:text-lg">
                    «آوای همدلی» خانه‌ی محلیِ کودکانِ بی‌سرپرست و آسیب‌دیده است؛ خانه‌ای که در آن هیچ بچه‌ای بی‌پناه نمی‌ماند. ما باور داریم هر کودک، فارغ از هر شرایطی، شایسته‌ی آموزش، غذای گرم، لباس و عشق است. اینجا بچه‌ها را به حال خودشان رها نمی‌کنیم؛ کنارشان می‌ایستیم تا خودشان بال دربیاورند و روی پای خودشان بایستند. «آوای همدلی» با همراهیِ شما، جایی است که کودکیِ هیچ بچه‌ای از دست نمی‌رود.
                </p>

                <ul class="mt-7 grid grid-cols-1 gap-3 sm:grid-cols-2" aria-label="خدمات اصلی">
                    @php
                        $aboutList = [
                            ['icon' => 'bi-book', 'label' => 'آموزش و توانمندسازی'],
                            ['icon' => 'bi-cup-hot', 'label' => 'تغذیه و سلامت'],
                            ['icon' => 'bi-person-heart', 'label' => 'مراقبت تخصصی'],
                            ['icon' => 'bi-emoji-smile', 'label' => 'فضای امن و امیدبخش'],
                        ];
                    @endphp
                    @foreach($aboutList as $item)
                        <li class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white px-4 py-3 shadow-sm">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#5964AE]/8 text-[#5964AE]">
                                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span class="text-sm font-bold text-slate-800">{{ $item['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>