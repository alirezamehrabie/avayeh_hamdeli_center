@php
    $ranking = $this->ranking;
    $rankingRows = $ranking['rows'];
    $accentPalette = [
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-200', 'bar' => 'bg-emerald-500', 'stroke' => '#10b981'],
        'teal' => ['bg' => 'bg-teal-50', 'text' => 'text-teal-700', 'ring' => 'ring-teal-200', 'bar' => 'bg-teal-500', 'stroke' => '#14b8a6'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-200', 'bar' => 'bg-amber-500', 'stroke' => '#f59e0b'],
        'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'ring' => 'ring-orange-200', 'bar' => 'bg-orange-500', 'stroke' => '#f97316'],
        'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'ring' => 'ring-rose-200', 'bar' => 'bg-rose-500', 'stroke' => '#f43f5e'],
        'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'ring' => 'ring-slate-200', 'bar' => 'bg-slate-400', 'stroke' => '#94a3b8'],
    ];
    $medalAccents = [
        1 => 'from-amber-400 via-amber-500 to-amber-600 text-white shadow-amber-200',
        2 => 'from-slate-300 via-slate-400 to-slate-500 text-white shadow-slate-200',
        3 => 'from-orange-400 via-orange-500 to-orange-600 text-white shadow-orange-200',
    ];
    $isManager = auth()->user()?->isManager() ?? false;
@endphp

<div class="container mx-auto max-w-7xl p-3 sm:p-5 xl:p-6" dir="rtl">
    {{-- استایل‌های بهینه‌سازی چاپ و خروجی PDF --}}
    <style>
        @media print {
            .print\:hidden, .no-print, [data-no-print] {
                display: none !important;
            }
            body {
                background: white !important;
                color: #0f172a !important;
            }
            .container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .shadow-sm, .shadow-md, .shadow-2xl {
                box-shadow: none !important;
            }
            .border {
                border-color: #e2e8f0 !important;
            }
        }
    </style>

    {{-- نوار هشدار پایان ماه شمسی برای مدیریت --}}
    @if($isManager && $this->isEndOfJalaliMonth)
        <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-gradient-to-l from-amber-50 to-orange-50 p-3.5 text-amber-950 shadow-sm print:hidden no-print">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                    <i class="bi bi-bell-fill text-base animate-bounce"></i>
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-amber-950">
                        یادآوری ارزیابی پایان ماه ({{ $this->currentJalaliMonthName }})
                    </h2>
                    <p class="mt-0.5 text-[11px] font-semibold text-amber-800">
                        در روزهای پایانی ماه قرار دارید. لطفاً «ضریب ارزیابی مدیریت» مددکاران را بررسی و نهایی نمایید.
                    </p>
                </div>
            </div>

            <span class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-amber-600/10 px-3 py-1.5 text-[11px] font-extrabold text-amber-800 ring-1 ring-amber-300/60">
                <i class="bi bi-calendar-check"></i>
                <span>ماه ارزیابی: {{ $this->rankingMonthName }} {{ $this->rankingYear }}</span>
            </span>
        </div>
    @endif

    {{-- هدر صفحه اختصاصی رتبه‌بندی --}}
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('admin.dashboard', ['section' => 'advanced-social-worker-report']) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 print:hidden no-print"
                        title="بازگشت به گزارش پیشرفته مددکاران"
                    >
                        <i class="bi bi-arrow-right text-sm"></i>
                        <span>بازگشت به گزارش پیشرفته</span>
                    </a>

                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700">
                        <i class="bi bi-trophy-fill text-[11px]"></i>
                        <span>رتبه‌بندی ماهانه</span>
                    </span>
                </div>

                <h1 class="mt-2 text-lg sm:text-xl xl:text-2xl font-black text-slate-800">
                    رتبه‌بندی و ضریب ارزیابی مدیریت مددکاران اجتماعی
                </h1>
                <p class="mt-1 text-xs font-semibold text-slate-500">
                    رتبه‌بندی عملکرد بر اساس شاخص‌های فعالیت و سرعت واکنش در ماه
                    <span class="font-extrabold text-amber-700">{{ $this->rankingMonthName }} {{ $this->rankingYear }}</span>
                </p>
            </div>

            {{-- ابزارهای سریع هدر: چاپ/اسکرین‌شات و خلاصه --}}
            <div class="flex flex-wrap items-center gap-2 lg:shrink-0 print:hidden no-print">
                <button
                    type="button"
                    onclick="window.print()"
                    title="چاپ یا ذخیره صفحه (مناسب ثبت اسکرین‌شات و گزارش)"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                    <i class="bi bi-printer text-sm"></i>
                    <span>چاپ / خروجی</span>
                </button>

                <div class="inline-flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-3 py-2 text-xs font-extrabold text-indigo-900">
                    <span>مددکاران: {{ number_format($ranking['evaluated']) }} نفر</span>
                    <span class="text-indigo-300">|</span>
                    <span>میانگین امتیاز: {{ number_format($ranking['average_score'], 1) }}</span>
                </div>
            </div>
        </div>

        {{-- نوار ابزار فیلتر و انتخاب ماه (فشرده و بهینه) --}}
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5 print:hidden no-print">
            {{-- انتخاب ماه و سال ارزیابی --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-black text-slate-700">
                    <i class="bi bi-calendar-check text-indigo-600"></i>
                    ماه ارزیابی:
                </span>

                <select
                    wire:model.live="rankingMonth"
                    aria-label="انتخاب ماه ارزیابی"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                >
                    @foreach($this->jalaliMonths as $mNum => $mTitle)
                        <option value="{{ $mNum }}">{{ $mTitle }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="rankingYear"
                    aria-label="انتخاب سال ارزیابی"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                >
                    @foreach($this->jalaliYears as $yNum)
                        <option value="{{ $yNum }}">{{ $yNum }}</option>
                    @endforeach
                </select>

                <button
                    type="button"
                    wire:click="setRankingToCurrentMonth"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-bold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    ماه جاری
                </button>

                <button
                    type="button"
                    wire:click="setRankingToPreviousMonth"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-bold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    ماه قبل
                </button>
            </div>

            {{-- فیلترهای تکمیلی (جستجو، وضعیت، بازه تاریخی) --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-40 sm:w-48">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="جستجوی نام یا کد..."
                        class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pr-8 pl-2 text-xs font-bold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                    >
                    <i class="bi bi-search absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                </div>

                <select
                    wire:model.live="status"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="all">همه وضعیت‌ها</option>
                    <option value="active">فقط فعالان</option>
                    <option value="inactive">فقط غیرفعالان</option>
                </select>

                <div class="flex items-center gap-1">
                    <div x-data="jalaliDateTimeField($wire.entangle('rankingDateFrom').live)" class="w-24">
                        <input
                            type="text"
                            x-ref="input"
                            x-model="draft"
                            x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:jalali-picker-open="handlePickerOpen()"
                            x-on:jalali-picker-close="handlePickerClose()"
                            x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            readonly
                            inputmode="none"
                            autocomplete="off"
                            data-jdp-readonly
                            data-jdp
                            data-jdp-only-date
                            placeholder="از تاریخ"
                            class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-bold text-slate-600 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        >
                    </div>

                    <span class="text-[10px] text-slate-400">تا</span>

                    <div x-data="jalaliDateTimeField($wire.entangle('rankingDateTo').live)" class="w-24">
                        <input
                            type="text"
                            x-ref="input"
                            x-model="draft"
                            x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            x-on:jalali-picker-open="handlePickerOpen()"
                            x-on:jalali-picker-close="handlePickerClose()"
                            x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                            readonly
                            inputmode="none"
                            autocomplete="off"
                            data-jdp-readonly
                            data-jdp
                            data-jdp-only-date
                            placeholder="تا تاریخ"
                            class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-bold text-slate-600 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        >
                    </div>

                    @if($this->rankingRange)
                        <button
                            type="button"
                            wire:click="clearRankingRange"
                            title="حذف بازه زمانی"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                        >
                            <i class="bi bi-x text-base"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- پیام‌های تاییدیه یا خطای اعمال ضریب --}}
        @if($rankingSuccessMessage)
            <div class="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200 print:hidden no-print">
                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                <span>{{ $rankingSuccessMessage }}</span>
            </div>
        @endif
        @if($rankingErrorMessage)
            <div class="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-800 ring-1 ring-rose-200 print:hidden no-print">
                <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                <span>{{ $rankingErrorMessage }}</span>
            </div>
        @endif
    </div>

    {{-- شبکه ۳ ستونه در دسکتاپ و تک‌ستونه در موبایل --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
        @forelse($rankingRows as $index => $row)
            @php
                $rank = $index + 1;
                $palette = $accentPalette[$row['grade']['accent']] ?? $accentPalette['slate'];
                $gaugeRadius = 22;
                $gaugeCircumference = 2 * M_PI * $gaugeRadius;
                $gaugeOffset = $gaugeCircumference * (1 - min(100, max(0, $row['score'])) / 100);
            @endphp

            {{-- کارت رتبه‌بندی مددکار (کلیک روی کل کارت مودال جزئیات را باز می‌کند) --}}
            <div
                wire:key="worker-card-{{ $row['id'] }}"
                wire:click="showWorkerInfo({{ $row['id'] }})"
                role="button"
                tabindex="0"
                class="group cursor-pointer flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-white p-3 sm:p-3.5 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-200"
                title="مشاهده جزئیات ارزیابی عملکرد {{ $row['full_name'] }}"
            >
                <div>
                    {{-- بخش سربرگ کارت: رتبه، اطلاعات مددکار، و نمودار دایره‌ای برجسته --}}
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            {{-- نشان رتبه با گرادیان مدال برای ۳ رتبه اول --}}
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-black shadow-sm {{ isset($medalAccents[$rank]) ? 'bg-gradient-to-br ' . $medalAccents[$rank] : 'bg-slate-100 text-slate-600' }}">
                                {{ $rank }}
                            </span>

                            {{-- نام و کد مددکار --}}
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm sm:text-[14px] font-black text-slate-900 group-hover:text-indigo-600 transition">
                                    {{ $row['full_name'] }}
                                </h3>
                                <div class="mt-0.5 flex items-center gap-1.5 text-[11px] font-semibold text-slate-500">
                                    <span dir="ltr">کد: {{ $row['worker_code'] }}</span>
                                    @unless($row['is_active'])
                                        <span class="rounded-full bg-rose-50 px-1.5 py-0.2 text-[9px] font-bold text-rose-700 ring-1 ring-rose-100">غیرفعال</span>
                                    @endunless
                                </div>
                            </div>
                        </div>

                        {{-- نمودار دایره‌ای امتیاز با تمرکز بصری بالا و اندازه بزرگ‌تر --}}
                        <div class="relative h-14 w-14 shrink-0" title="امتیاز نهایی: {{ number_format($row['score'], 1) }} از ۱۰۰">
                            <svg class="h-14 w-14 -rotate-90" viewBox="0 0 54 54">
                                <circle cx="27" cy="27" r="{{ $gaugeRadius }}" fill="none" stroke="#f1f5f9" stroke-width="4.5"></circle>
                                <circle
                                    cx="27" cy="27" r="{{ $gaugeRadius }}" fill="none"
                                    stroke="{{ $palette['stroke'] }}"
                                    stroke-width="4.5"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ round($gaugeCircumference, 2) }}"
                                    stroke-dashoffset="{{ round($gaugeOffset, 2) }}"
                                ></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                                <span class="text-xs sm:text-[13px] font-black text-slate-900">
                                    {{ number_format($row['score'], 1) }}
                                </span>
                                <span class="text-[8px] font-extrabold text-slate-400 mt-0.5">از ۱۰۰</span>
                            </div>
                        </div>
                    </div>

                    {{-- ستاره‌ها با اندازه بزرگ‌تر، جلوه بصری عالی و برچسب سطح کیفی --}}
                    <div class="mt-3 flex items-center justify-between border-t border-slate-100/90 pt-2.5">
                        <div class="flex items-center gap-1" role="img" aria-label="{{ number_format($row['stars'], 1) }} از ۵ ستاره">
                            @for($starIndex = 1; $starIndex <= 5; $starIndex++)
                                @php
                                    $starIcon = $row['stars'] >= $starIndex
                                        ? 'bi-star-fill'
                                        : ($row['stars'] >= $starIndex - 0.5 ? 'bi-star-half' : 'bi-star');
                                @endphp
                                <i class="bi {{ $starIcon }} text-sm sm:text-base {{ $row['stars'] >= $starIndex - 0.5 ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200' }}"></i>
                            @endfor
                            <span class="mr-1 text-xs font-black text-slate-700">{{ number_format($row['stars'], 1) }}</span>
                        </div>

                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold ring-1 {{ $palette['bg'] }} {{ $palette['text'] }} {{ $palette['ring'] }}">
                            {{ $row['grade']['label'] }}
                        </span>
                    </div>
                </div>

                {{-- بخش اختصاصی ضریب ارزیابی مدیریت:
                     ۱. جلوگیری از باز شدن ناخواسته مودال با event.stopPropagation()
                     ۲. مخفی‌سازی کامل در خروجی PDF و چاپ با print:hidden و no-print --}}
                <div
                    @click.stop
                    class="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2 print:hidden no-print"
                    data-no-print
                >
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-black text-slate-600">ضریب مدیریت:</span>

                        @if($isManager)
                            <div class="relative flex items-center" @click.stop>
                                <select
                                    @click.stop
                                    wire:change="updateManagementScore({{ $row['id'] }}, $event.target.value)"
                                    wire:loading.attr="disabled"
                                    wire:target="updateManagementScore({{ $row['id'] }}, $event.target.value)"
                                    class="rounded-lg border border-amber-300 bg-amber-50/70 py-1 pr-2 pl-6 text-xs font-black text-amber-950 outline-none transition hover:bg-amber-100/70 focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-200 disabled:opacity-50"
                                    title="تغییر ضریب ارزیابی مدیریت برای ماه {{ $this->rankingMonthName }}"
                                >
                                    @for($s = 10; $s >= 1; $s--)
                                        <option value="{{ $s }}" @selected($row['management_score'] === $s)>
                                            {{ $s }} {{ $s === 10 ? '(پیش‌فرض)' : '' }}
                                        </option>
                                    @endfor
                                </select>

                                <svg wire:loading wire:target="updateManagementScore({{ $row['id'] }}, $event.target.value)" class="absolute left-1.5 h-3.5 w-3.5 animate-spin text-amber-600" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </div>
                        @else
                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-black text-slate-700">
                                {{ $row['management_score'] }} از ۱۰
                            </span>
                        @endif
                    </div>

                    {{-- فرمول کسر امتیاز در صورت ضریب کمتر از ۱۰ --}}
                    <div>
                        @if($row['management_score'] < 10)
                            <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-md ring-1 ring-rose-200/60" title="امتیاز عملکرد × (ضریب مدیریت ÷ ۱۰)">
                                پایه: {{ number_format($row['base_score'], 1) }} × {{ $row['management_score'] / 10 }}
                            </span>
                        @else
                            <span class="text-[9px] font-semibold text-slate-400">
                                ضریب کامل (۱۰)
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i class="bi bi-trophy text-2xl"></i>
                </span>
                <p class="mt-3 text-sm font-black text-slate-900">مددکاری در رتبه‌بندی یافت نشد</p>
                <p class="mx-auto mt-1 max-w-md text-xs text-slate-500">
                    با فیلترهای انتخابی یا در این ماه نتیجه‌ای یافت نشد. بازه را حذف کنید یا فیلتر را تغییر دهید.
                </p>
            </div>
        @endforelse
    </div>

    {{-- مودال جزئیات پرونده و ارزیابی تفصیلی عملکرد مددکار --}}
    @include('livewire.social-workers.partials.social-worker-details-modal')
</div>
