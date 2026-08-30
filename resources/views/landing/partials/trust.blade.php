<!-- بخش اعتماد -->
<section class="border-y border-slate-100 bg-white" aria-label="دلایل اعتماد">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 py-10 sm:px-6 lg:grid-cols-4">
        @php
            $trusts = [
                ['icon' => 'bi-shield-check', 'title' => 'شفافیت کامل', 'desc' => 'گزارش دقیق از هر خدمت'],
                ['icon' => 'bi-heart', 'title' => 'با مهربانی', 'desc' => 'همدلی در تخصص و مراقبت'],
                ['icon' => 'bi-people', 'title' => 'تیم متخصص', 'desc' => 'مددکاران و مربیان دلسوز'],
                ['icon' => 'bi-gift', 'title' => 'هدیه امید', 'desc' => 'آموزش، تغذیه و لباس'],
            ];
        @endphp
        @foreach($trusts as $trust)
            <div class="flex flex-col items-center gap-2.5 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#5964AE]/8 text-[#5964AE]">
                    <i class="{{ $trust['icon'] }} text-2xl" aria-hidden="true"></i>
                </span>
                <span class="text-sm font-black text-slate-900">{{ $trust['title'] }}</span>
                <span class="text-xs leading-5 text-slate-500">{{ $trust['desc'] }}</span>
            </div>
        @endforeach
    </div>
</section>