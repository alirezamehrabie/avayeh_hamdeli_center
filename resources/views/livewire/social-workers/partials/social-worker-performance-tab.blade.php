@php
    $performance = $this->workerPerformance;
@endphp

@if(! $performance || ! $performance['has_data'])
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
            <i class="bi bi-speedometer2 text-xl"></i>
        </span>
        <p class="mt-3 text-sm font-extrabold text-slate-900">داده‌ای برای ارزیابی وجود ندارد</p>
        <p class="mx-auto mt-2 max-w-md text-xs leading-6 text-slate-500">
            برای این مددکار هنوز تخصیص توزیع یا تحویل ثبت‌شده‌ای وجود ندارد. پس از نخستین تخصیص خدمت،
            شاخص‌های سرعت واکنش و ایفای تعهد به‌صورت خودکار محاسبه می‌شوند.
        </p>
    </div>
@else
    @php
        $accentPalette = [
            'emerald' => ['ring' => 'ring-emerald-100', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'bar' => 'bg-emerald-500'],
            'teal' => ['ring' => 'ring-teal-100', 'bg' => 'bg-teal-50', 'text' => 'text-teal-700', 'bar' => 'bg-teal-500'],
            'amber' => ['ring' => 'ring-amber-100', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'bar' => 'bg-amber-500'],
            'orange' => ['ring' => 'ring-orange-100', 'bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'bar' => 'bg-orange-500'],
            'rose' => ['ring' => 'ring-rose-100', 'bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'bar' => 'bg-rose-500'],
            'sky' => ['ring' => 'ring-sky-100', 'bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'bar' => 'bg-sky-500'],
            'indigo' => ['ring' => 'ring-indigo-100', 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'bar' => 'bg-indigo-500'],
            'violet' => ['ring' => 'ring-violet-100', 'bg' => 'bg-violet-50', 'text' => 'text-violet-700', 'bar' => 'bg-violet-500'],
            'slate' => ['ring' => 'ring-slate-200', 'bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'bar' => 'bg-slate-400'],
        ];
        $grade = $accentPalette[$performance['grade']['accent']] ?? $accentPalette['slate'];
        $score = $performance['score'];
        $stars = $performance['stars'];
        $gaugeCircumference = 2 * M_PI * 52;
        $gaugeOffset = $gaugeCircumference * (1 - min(100, max(0, $score)) / 100);
        $gaugeStroke = [
            'emerald' => '#10b981',
            'teal' => '#14b8a6',
            'amber' => '#f59e0b',
            'orange' => '#f97316',
            'rose' => '#f43f5e',
            'slate' => '#94a3b8',
        ][$performance['grade']['accent']] ?? '#94a3b8';
        $responseStats = $performance['metrics']['response']['stats'];
        $monthlyMax = max(1, collect($performance['monthly_activity'])->max('count') ?: 1);
    @endphp

    {{-- امتیاز کلی و ستاره‌ها --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <div class="flex items-center gap-4">
                <div class="relative h-32 w-32 shrink-0">
                    <svg class="h-32 w-32 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="12"></circle>
                        <circle
                            cx="60" cy="60" r="52" fill="none"
                            stroke="{{ $gaugeStroke }}"
                            stroke-width="12"
                            stroke-linecap="round"
                            stroke-dasharray="{{ round($gaugeCircumference, 2) }}"
                            stroke-dashoffset="{{ round($gaugeOffset, 2) }}"
                        ></circle>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-black text-slate-900">{{ number_format($score, 1) }}</span>
                        <span class="text-[10px] font-bold text-slate-400">از ۱۰۰</span>
                    </div>
                </div>

                <div class="min-w-0">
                    <p class="text-[11px] font-semibold tracking-[0.14em] text-slate-400">امتیاز کل عملکرد</p>
                    <p class="mt-1 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-extrabold ring-1 {{ $grade['bg'] }} {{ $grade['text'] }} {{ $grade['ring'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $grade['bar'] }}"></span>
                        {{ $performance['grade']['label'] }}
                    </p>

                    @if(! empty($performance['range']))
                        <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold text-amber-700 ring-1 ring-amber-100">
                            <i class="bi bi-calendar3"></i>
                            ارزیابی در بازهٔ {{ $performance['range']['from'] ?? 'آغاز' }} تا {{ $performance['range']['to'] ?? 'امروز' }}
                        </p>
                    @endif

                    <div class="mt-3 flex items-center gap-1.5" role="img" aria-label="امتیاز {{ number_format($stars, 1) }} از ۵ ستاره">
                        @for($starIndex = 1; $starIndex <= 5; $starIndex++)
                            @php
                                $starIcon = $stars >= $starIndex
                                    ? 'bi-star-fill'
                                    : ($stars >= $starIndex - 0.5 ? 'bi-star-half' : 'bi-star');
                            @endphp
                            <i class="bi {{ $starIcon }} text-lg {{ $stars >= $starIndex - 0.5 ? 'text-amber-500' : 'text-slate-300' }}"></i>
                        @endfor
                        <span class="mr-1 text-sm font-extrabold text-slate-700">{{ number_format($stars, 1) }} / ۵</span>
                    </div>

                    <p class="mt-2 text-[11px] leading-5 text-slate-500">
                        امتیاز از ترکیب وزنی پنج شاخص زیر محاسبه می‌شود و مبنای آن داده‌های واقعی تخصیص و تحویل خدمت است.
                    </p>
                </div>
            </div>

            <div class="grid flex-1 gap-2 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">میانهٔ زمان واکنش</p>
                    <p class="mt-1 text-lg font-black text-slate-900">
                        {{ $responseStats['median_hours'] !== null ? number_format($responseStats['median_hours'], 1) . ' ساعت' : '-' }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">از ساخت تخصیص تا نخستین ثبت تحویل</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">واکنش زیر ۲۴ ساعت</p>
                    <p class="mt-1 text-lg font-black text-slate-900">{{ number_format($responseStats['same_day_share'], 1) }}٪</p>
                    <p class="mt-1 text-[10px] text-slate-500">سهم تخصیص‌های پاسخ‌داده‌شده در همان روز</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">تحویل‌های ثبت‌شده</p>
                    <p class="mt-1 text-lg font-black text-slate-900">{{ number_format($performance['delivery_summary']['count']) }} مورد</p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        آخرین تحویل: {{ $performance['delivery_summary']['last_delivered_at'] ?? '-' }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">تخصیص‌های بی‌پاسخ</p>
                    <p class="mt-1 text-lg font-black {{ $responseStats['pending_overdue'] > 0 ? 'text-rose-700' : 'text-slate-900' }}">
                        {{ number_format($responseStats['pending_overdue']) }} مورد
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        {{ number_format($responseStats['pending_in_grace']) }} مورد در مهلت ۲۴ ساعتهٔ اولیه
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- شاخص‌های وزنی --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900">شاخص‌های ارزیابی و وزن آن‌ها</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">هر شاخص روی مقیاس ۰ تا ۱۰۰ سنجیده و با وزن خود در امتیاز کل ضرب می‌شود.</p>
            </div>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                <i class="bi bi-sliders text-base"></i>
            </span>
        </div>

        <div class="space-y-2">
            @foreach($performance['metrics'] as $metric)
                @php
                    $palette = $accentPalette[$metric['accent']] ?? $accentPalette['slate'];
                @endphp
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $palette['bg'] }} {{ $palette['text'] }} {{ $palette['ring'] }}">
                                <i class="bi {{ $metric['icon'] }} text-sm"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $metric['label'] }}</p>
                                <p class="mt-0.5 text-[11px] leading-5 text-slate-500">{{ $metric['description'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200">وزن {{ $metric['weight'] }}٪</span>
                            <span class="text-sm font-black text-slate-900">{{ number_format($metric['score'], 1) }}</span>
                        </div>
                    </div>

                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                        <div class="h-full rounded-full {{ $palette['bar'] }} transition-all duration-500" style="width: {{ min(100, max(0, $metric['score'])) }}%;"></div>
                    </div>

                    @php
                        $metricNotes = match($metric['key']) {
                            'fulfillment' => [
                                'تخصیص‌یافته: ' . number_format($metric['stats']['allocated_quantity'], 2),
                                'تحویل‌شده: ' . number_format($metric['stats']['delivered_quantity'], 2),
                                'تکمیل‌شده: ' . number_format($metric['stats']['completed_allocations']) . ' از ' . number_format($metric['stats']['total_allocations']),
                            ],
                            'coverage' => [
                                'شروع‌شده: ' . number_format($metric['stats']['started_allocations']),
                                'بدون اقدام: ' . number_format($metric['stats']['idle_allocations']),
                            ],
                            'punctuality' => $metric['stats']['is_estimated']
                                ? ['خدمتی با بازهٔ پایان مشخص برای سنجش دیرکرد وجود ندارد؛ امتیاز خنثی لحاظ شده است.']
                                : [
                                    'در موعد: ' . number_format($metric['stats']['on_time']),
                                    'با تأخیر: ' . number_format($metric['stats']['late']),
                                ],
                            'consistency' => [
                                'ماه‌های فعال: ' . number_format($metric['stats']['active_months']) . ' از ' . number_format($metric['stats']['total_months']),
                            ],
                            'response' => [
                                'پاسخ‌داده‌شده: ' . number_format($metric['stats']['answered']),
                                'سریع‌ترین واکنش: ' . ($metric['stats']['fastest_hours'] !== null ? number_format($metric['stats']['fastest_hours'], 1) . ' ساعت' : '-'),
                            ],
                            default => [],
                        };
                    @endphp

                    @if($metricNotes !== [])
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[10px] font-semibold text-slate-500">
                            @foreach($metricNotes as $note)
                                <span>{{ $note }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- توزیع سرعت واکنش --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900">توزیع سرعت واکنش به تخصیص‌ها</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    مبنای محاسبه: زمان ساخت تخصیص توسط اپراتور تا نخستین ثبت تحویل توسط مددکار در پنل خودش.
                </p>
            </div>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                <i class="bi bi-stopwatch text-base"></i>
            </span>
        </div>

        @if($responseStats['answered'] > 0)
            <div class="space-y-2">
                @foreach($performance['response_distribution'] as $bucket)
                    @php
                        $bucketPalette = $accentPalette[$bucket['accent']] ?? $accentPalette['slate'];
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-[11px] font-bold text-slate-600">{{ $bucket['label'] }}</span>
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $bucketPalette['bar'] }} transition-all duration-500" style="width: {{ $bucket['share'] }}%;"></div>
                        </div>
                        <span class="w-24 shrink-0 text-left text-[11px] font-bold text-slate-600" dir="ltr">
                            {{ number_format($bucket['count']) }} ({{ number_format($bucket['share'], 1) }}%)
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center text-xs text-slate-500">
                هنوز هیچ تخصیصی توسط این مددکار پاسخ داده نشده است.
            </p>
        @endif
    </div>

    {{-- روند فعالیت ماهانه --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900">
                    @if(! empty($performance['range']))
                        روند تحویل در {{ count($performance['monthly_activity']) }} ماهِ بازهٔ ارزیابی
                    @else
                        روند تحویل در {{ count($performance['monthly_activity']) }} ماه گذشته
                    @endif
                </p>
                <p class="mt-1 text-xs leading-5 text-slate-500">تعداد تحویل ثبت‌شده بر اساس ماه شمسی</p>
            </div>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-100">
                <i class="bi bi-bar-chart-line text-base"></i>
            </span>
        </div>

        <div class="rounded-xl border border-violet-100 bg-violet-50/30 p-4">
            <div class="flex h-48 items-end gap-2">
                @foreach($performance['monthly_activity'] as $month)
                    @php($barHeight = round($month['count'] / $monthlyMax * 100, 2))
                    <div class="flex h-full flex-1 flex-col items-center justify-end">
                        <p class="mb-2 text-[11px] font-bold text-violet-700">{{ number_format($month['count']) }}</p>
                        <div
                            class="w-full rounded-t-md bg-gradient-to-t from-violet-600 via-violet-500 to-violet-400 transition-all duration-300"
                            style="height: {{ max($barHeight, $month['count'] > 0 ? 4 : 0) }}%;"
                            title="{{ $month['label'] }} {{ $month['year'] }}: {{ number_format($month['count']) }} تحویل"
                        ></div>
                        <p class="mt-2 whitespace-nowrap text-[10px] text-slate-600">{{ $month['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-3 lg:grid-cols-2">
        {{-- کانال تحویل --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-extrabold text-slate-900">ترکیب کانال‌های تحویل</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">شیوهٔ رساندن خدمت به مددجو</p>
                </div>
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                    <i class="bi bi-signpost-split text-base"></i>
                </span>
            </div>

            @if($performance['channel_breakdown'] !== [])
                <div class="space-y-2">
                    @foreach($performance['channel_breakdown'] as $channel)
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600">
                                <span>{{ $channel['label'] }}</span>
                                <span dir="ltr">{{ number_format($channel['count']) }} ({{ number_format($channel['share'], 1) }}%)</span>
                            </div>
                            <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-sky-500 transition-all duration-500" style="width: {{ $channel['share'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center text-xs text-slate-500">
                    تحویلی برای تفکیک کانال ثبت نشده است.
                </p>
            @endif
        </div>

        {{-- خلاصهٔ ارزش --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-extrabold text-slate-900">خلاصهٔ عملکرد مالی و زمانی</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">ارزش خدمات رسانده‌شده و بازهٔ فعالیت</p>
                </div>
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                    <i class="bi bi-cash-coin text-base"></i>
                </span>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">ارزش کل تحویل‌ها</p>
                    <p class="mt-1 text-sm font-black text-slate-900">{{ number_format($performance['delivery_summary']['value']) }} ریال</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">تعداد تحویل</p>
                    <p class="mt-1 text-sm font-black text-slate-900">{{ number_format($performance['delivery_summary']['count']) }} مورد</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">نخستین تحویل</p>
                    <p class="mt-1 text-sm font-black text-slate-900">{{ $performance['delivery_summary']['first_delivered_at'] ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold text-slate-500">آخرین تحویل</p>
                    <p class="mt-1 text-sm font-black text-slate-900">{{ $performance['delivery_summary']['last_delivered_at'] ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- تخصیص‌های نیازمند پیگیری --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900">تخصیص‌های نیازمند پیگیری</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">تخصیص‌هایی که تحویل آن‌ها ثبت نشده یا ناتمام مانده است</p>
            </div>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-100">
                <i class="bi bi-exclamation-triangle text-base"></i>
            </span>
        </div>

        @if($performance['open_allocations'] !== [])
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] text-slate-500">
                            <th class="px-2 py-2 text-right font-bold">خدمت</th>
                            <th class="px-2 py-2 text-right font-bold">دسته‌بندی</th>
                            <th class="px-2 py-2 text-right font-bold">تخصیص / تحویل</th>
                            <th class="px-2 py-2 text-right font-bold">زمان تخصیص</th>
                            <th class="px-2 py-2 text-right font-bold">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($performance['open_allocations'] as $allocation)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="px-2 py-2 font-bold text-slate-800">{{ $allocation['service_label'] }}</td>
                                <td class="px-2 py-2 text-slate-600">{{ $allocation['category_label'] }}</td>
                                <td class="px-2 py-2 text-slate-600" dir="ltr">
                                    {{ number_format($allocation['delivered_quantity'], 2) }} / {{ number_format($allocation['allocated_quantity'], 2) }}
                                </td>
                                <td class="px-2 py-2 text-slate-600" dir="ltr">{{ $allocation['assigned_at'] }}</td>
                                <td class="px-2 py-2">
                                    @if($allocation['response_label'] !== null)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-100">
                                            شروع‌شده پس از {{ $allocation['response_label'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-100">
                                            بی‌پاسخ ({{ number_format($allocation['pending_days'] ?? 0) }} روز)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="rounded-xl border border-dashed border-emerald-200 bg-emerald-50/60 p-4 text-center text-xs font-bold text-emerald-700">
                همهٔ تخصیص‌های این مددکار به‌طور کامل تحویل و ثبت شده‌اند.
            </p>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-100/70 p-4">
        <p class="text-[11px] font-extrabold text-slate-700">روش محاسبه</p>
        <ul class="mt-2 space-y-1 text-[11px] leading-6 text-slate-600">
            <li>سرعت واکنش ({{ $performance['weights']['response'] }}٪): فاصلهٔ زمانی ساخت تخصیص توسط اپراتور تا نخستین ثبت تحویل توسط مددکار. تخصیص بی‌پاسخ پس از ۲۴ ساعت جریمه و پس از ۱۴ روز امتیاز صفر می‌گیرد.</li>
            <li>ایفای تعهد ({{ $performance['weights']['fulfillment'] }}٪): نسبت مقدار تحویل‌شده به مقدار تخصیص‌یافته.</li>
            <li>پوشش تخصیص ({{ $performance['weights']['coverage'] }}٪): سهم تخصیص‌هایی که حداقل یک تحویل دارند.</li>
            <li>وقت‌شناسی ({{ $performance['weights']['punctuality'] }}٪): سهم تحویل‌های ثبت‌شده تا پایان بازهٔ توزیع خدمت.</li>
            <li>تداوم فعالیت ({{ $performance['weights']['consistency'] }}٪): تعداد ماه‌های فعال در بازهٔ اخیر.</li>
        </ul>
    </div>
@endif
