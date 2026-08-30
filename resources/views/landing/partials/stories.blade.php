<!-- بخش قصه‌ها -->
<section id="stories" class="landing-section bg-[#f8fbff] py-16 sm:py-24" aria-labelledby="stories-title">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="text-center" data-reveal>
            <span class="inline-flex items-center gap-2 rounded-full bg-[#D4205F]/10 px-4 py-1.5 text-xs font-bold text-[#A4184B]">
                <i class="bi bi-chat-heart-fill" aria-hidden="true"></i>
                قصه‌های همدلی
            </span>
            <h2 id="stories-title" class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl lg:text-4xl">
                روایت کوچک‌ترین تغییرها
            </h2>
        </div>

        <div
            class="mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 [scrollbar-width:thin]"
            x-data="{ active: 0 }"
            @scroll.capture.passive="$el.querySelectorAll('.story-card').forEach((c,i)=>{ const r=c.getBoundingClientRect(); if(r.left>=0 && r.right<=window.innerWidth+16) active=i; })"
            role="region"
            aria-label="قصه‌های همدلی"
            aria-roledescription="کاروسل"
        >
            @php
                $stories = [
                    ['quote' => 'با تغذیه‌ی سالم، نمره‌هایم بهتر شد و دوباره به مدرسه انگیزه گرفتم.', 'name' => 'به نام خودم', 'role' => 'کودک تحت پوشش', 'emoji' => '✏️'],
                    ['quote' => 'وقتی بسته‌ی لباس رسید، برای اولین بار با اعتماد به نفس به مهد برگشتم.', 'name' => 'حفظ حریم خصوصی', 'role' => 'کودک تحت پوشش', 'emoji' => '🎒'],
                    ['quote' => 'چند ماه همراهی، لبخند و امید را به خانواده‌ی ما برگرداند.', 'name' => 'به نام، حفظ حریم', 'role' => 'حامی و همراه', 'emoji' => '💛'],
                ];
            @endphp
            @foreach($stories as $index => $story)
                <article class="story-card relative min-w-[85%] snap-center rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:min-w-[48%] lg:min-w-[31.5%]">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#D4205F]/10 text-2xl">
                        {{ $story['emoji'] }}
                    </div>
                    <blockquote class="mt-4 text-sm font-medium leading-7 text-slate-700 sm:text-base">
                        «{{ $story['quote'] }}»
                    </blockquote>
                    <footer class="mt-5 flex items-center gap-2 text-xs">
                        <span class="font-black text-slate-900">{{ $story['name'] }}</span>
                        <span class="text-slate-400">•</span>
                        <span class="text-slate-500">{{ $story['role'] }}</span>
                    </footer>
                    <span class="absolute left-5 top-5 text-4xl text-[#5964AE]/10" aria-hidden="true">❝</span>
                </article>
            @endforeach
        </div>

        <!-- نقاط پیمایش -->
        <div class="mt-5 flex justify-center gap-2">
            @foreach($stories as $index => $story)
                <span
                    class="h-2 rounded-full transition-all duration-300"
                    :class="active === {{ $index }} ? 'w-6 bg-[#5964AE]' : 'w-2 bg-slate-300'"
                    aria-hidden="true"
                ></span>
            @endforeach
        </div>
    </div>
</section>