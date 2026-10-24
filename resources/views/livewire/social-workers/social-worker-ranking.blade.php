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

<div
    class="container mx-auto max-w-7xl p-3 sm:p-5 xl:p-6"
    dir="rtl"
    x-data="{
        isOpen: @js($showWorkerModal),
        isLoading: false,
        loadError: null,
        activeReqId: 0,
        originalBodyOverflow: '',
        worker: {
            id: null,
            name: '',
            code: '',
            rank: null,
            score: '0.0',
            grade: '',
            accent: 'slate',
            stars: '0.0',
            is_active: true
        },
        init() {
            this.$watch('$wire.rankingMonth', () => this.resetModalState());
            this.$watch('$wire.rankingYear', () => this.resetModalState());
            this.$watch('$wire.showWorkerModal', (val) => {
                if (!val && this.isOpen) {
                    this.isOpen = false;
                    this.unlockScroll();
                }
            });
        },
        resetModalState() {
            this.isOpen = false;
            this.isLoading = false;
            this.loadError = null;
            this.activeReqId++;
            this.unlockScroll();
        },
        extractCardData(cardEl) {
            if (!cardEl) return null;
            const id = parseInt(cardEl.getAttribute('data-worker-id'), 10);
            if (!id || isNaN(id)) return null;

            return {
                id: id,
                name: cardEl.getAttribute('data-worker-name') || '',
                code: cardEl.getAttribute('data-worker-code') || '',
                rank: cardEl.getAttribute('data-worker-rank') ? parseInt(cardEl.getAttribute('data-worker-rank'), 10) : null,
                score: cardEl.getAttribute('data-worker-score') || '0.0',
                grade: cardEl.getAttribute('data-worker-grade') || '',
                accent: cardEl.getAttribute('data-worker-accent') || 'slate',
                stars: cardEl.getAttribute('data-worker-stars') || '0.0',
                is_active: cardEl.getAttribute('data-worker-active') === '1',
                year: parseInt(cardEl.getAttribute('data-ranking-year'), 10) || null,
                month: parseInt(cardEl.getAttribute('data-ranking-month'), 10) || null,
            };
        },
        handleCardClick(event) {
            if (event.target.closest('[data-no-modal], select, button, input, a')) {
                return;
            }

            const card = event.target.closest('[data-worker-card]');
            if (!card) return;

            const data = this.extractCardData(card);
            if (data) {
                this.openWorkerModal(data);
            }
        },
        openWorkerModal(data) {
            if (!data || !data.id) return;

            if (this.isOpen && this.worker.id === data.id && this.isLoading) {
                return;
            }

            this.worker = Object.assign({
                id: null,
                name: '',
                code: '',
                rank: null,
                score: '0.0',
                grade: '',
                accent: 'slate',
                stars: '0.0',
                is_active: true
            }, data);

            this.isOpen = true;
            this.isLoading = true;
            this.loadError = null;
            this.lockScroll();

            const reqId = ++this.activeReqId;
            const year = data.year || parseInt(this.$wire.rankingYear, 10) || null;
            const month = data.month || parseInt(this.$wire.rankingMonth, 10) || null;

            this.$wire.showWorkerInfo(data.id, year, month)
                .then(() => {
                    if (this.activeReqId === reqId) {
                        this.isLoading = false;
                        this.loadError = null;
                    }
                })
                .catch(() => {
                    if (this.activeReqId === reqId) {
                        this.isLoading = false;
                        this.loadError = 'خطا در بارگذاری جزئیات عملکرد مددکار.';
                    }
                });
        },
        retryLoad() {
            if (!this.worker.id) return;
            this.isLoading = true;
            this.loadError = null;
            const reqId = ++this.activeReqId;
            const year = parseInt(this.$wire.rankingYear, 10) || null;
            const month = parseInt(this.$wire.rankingMonth, 10) || null;

            this.$wire.showWorkerInfo(this.worker.id, year, month)
                .then(() => {
                    if (this.activeReqId === reqId) {
                        this.isLoading = false;
                        this.loadError = null;
                    }
                })
                .catch(() => {
                    if (this.activeReqId === reqId) {
                        this.isLoading = false;
                        this.loadError = 'خطا در بارگذاری جزئیات عملکرد مددکار.';
                    }
                });
        },
        close() {
            this.isOpen = false;
            this.activeReqId++;
            this.unlockScroll();
            this.$wire.closeWorkerModal();
        },
        lockScroll() {
            if (!this.originalBodyOverflow) {
                this.originalBodyOverflow = document.body.style.overflow;
            }
            document.body.style.overflow = 'hidden';
        },
        unlockScroll() {
            document.body.style.overflow = this.originalBodyOverflow || '';
        }
    }"
    @keydown.escape.window="if (isOpen) close()"
>
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
                    <button
                        type="button"
                        wire:click="$dispatch('open-dashboard-section', { section: 'advanced-social-worker-report' })"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 print:hidden no-print"
                        title="بازگشت به گزارش پیشرفته مددکاران"
                    >
                        <i class="bi bi-arrow-right text-sm"></i>
                        <span>بازگشت به گزارش پیشرفته</span>
                    </button>

                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700">
                        <i class="bi bi-trophy-fill text-[11px]"></i>
                        <span>رتبه‌بندی ماهانه</span>
                    </span>
                </div>

                <h1 class="mt-2 text-lg sm:text-xl xl:text-2xl font-black text-slate-800">
                    رتبه‌بندی و ضریب ارزیابی مدیریت مددکاران اجتماعی
                </h1>
                <p class="mt-1 text-xs font-semibold text-slate-500">
                    رتبه‌بندی عملکرد بر اساس شاخص‌های فعالیت و سرعت واکنش در
                    @if($rankingMode === 'monthly')
                        ماه <span class="font-extrabold text-amber-700">{{ $this->rankingMonthName }} {{ $this->rankingYear }}</span>
                    @else
                        <span class="font-extrabold text-amber-700">{{ $this->rankingRangeLabel ?? 'بازه زمانی سفارشی' }}</span>
                    @endif
                </p>
            </div>

            {{-- آمار خلاصه هدر (تفکیک مددکاران دارای فعالیت، کل مشمولین و میانگین امتیاز) --}}
            <div class="flex flex-wrap items-center gap-2 lg:shrink-0">
                <div class="inline-flex items-center gap-2.5 rounded-xl border border-indigo-100 bg-indigo-50/60 px-3.5 py-2 text-xs font-extrabold text-indigo-900 shadow-sm">
                    <span title="تعداد مددکاران دارای داده‌های فعالیت در این بازه">
                        دارای فعالیت: <span class="font-black text-indigo-700">{{ number_format($ranking['active_evaluated'] ?? 0) }}</span> نفر
                    </span>
                    <span class="text-indigo-300">|</span>
                    <span title="تعداد کل مددکاران مشمول فیلتر">
                        کل مددکاران: <span class="font-black text-slate-700">{{ number_format($ranking['total'] ?? $ranking['evaluated']) }}</span> نفر
                    </span>
                    <span class="text-indigo-300">|</span>
                    <span title="میانگین امتیاز مددکاران دارای فعالیت">
                        میانگین امتیاز: <span class="font-black text-amber-700">{{ number_format($ranking['average_score'], 1) }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- نوار ابزار فیلتر با تفکیک دقیق ارزیابی ماهانه و بازه سفارشی --}}
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5 print:hidden no-print">
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- سوئیچر حالت ماهانه / بازه سفارشی --}}
                <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 p-1">
                    <button
                        type="button"
                        wire:click="setRankingMode('monthly')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $rankingMode === 'monthly' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        <i class="bi bi-calendar-month text-xs"></i>
                        <span>ارزیابی ماهانه</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setRankingMode('custom')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $rankingMode === 'custom' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        <i class="bi bi-calendar-range text-xs"></i>
                        <span>بازه سفارشی</span>
                    </button>
                </div>

                {{-- فیلترهای حالت ماهانه --}}
                @if($rankingMode === 'monthly')
                    <div class="flex flex-wrap items-center gap-1.5">
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
                @else
                    {{-- فیلترهای حالت بازه سفارشی --}}
                    <div class="flex items-center gap-1.5">
                        <div x-data="jalaliDateTimeField($wire.entangle('rankingDateFrom').live)" class="w-24 sm:w-28">
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

                        <div x-data="jalaliDateTimeField($wire.entangle('rankingDateTo').live)" class="w-24 sm:w-28">
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
                                title="حذف بازه زمانی و ارزیابی تمام دوران"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                            >
                                <i class="bi bi-x text-base"></i>
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- فیلترهای تکمیلی (جستجو و وضعیت) --}}
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

    {{-- نوار نشانگر وضعیت بارگذاری شبکه رتبه‌بندی --}}
    <div
        wire:loading
        wire:target="rankingMonth, rankingYear, search, status, rankingDateFrom, rankingDateTo, setRankingToCurrentMonth, setRankingToPreviousMonth, clearRankingRange"
        class="mb-3 flex items-center justify-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50/90 py-2.5 px-4 text-xs font-bold text-indigo-700 shadow-sm transition"
    >
        <svg class="h-4 w-4 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
        </svg>
        <span>در حال به‌روزرسانی نتایج و شاخص‌های ارزیابی عملکرد...</span>
    </div>

    {{-- شبکه ۳ ستونه در دسکتاپ و تک‌ستونه در موبایل همراه با Event Delegation مطمئن و بازخورد بصری بارگذاری --}}
    <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 transition-opacity duration-200"
        wire:loading.class="opacity-50 pointer-events-none"
        wire:target="rankingMonth, rankingYear, search, status, rankingDateFrom, rankingDateTo, setRankingToCurrentMonth, setRankingToPreviousMonth, clearRankingRange"
        @click="handleCardClick($event)"
        @keydown.enter="handleCardClick($event)"
        @keydown.space.prevent="handleCardClick($event)"
    >
        @forelse($rankingRows as $index => $row)
            @php
                $rank = $index + 1;
                $palette = $accentPalette[$row['grade']['accent']] ?? $accentPalette['slate'];
                $gaugeRadius = 22;
                $gaugeCircumference = 2 * M_PI * $gaugeRadius;
                $gaugeOffset = $gaugeCircumference * (1 - min(100, max(0, $row['score'])) / 100);
            @endphp

            {{-- کارت رتبه‌بندی مددکار (کلیک روی کل کارت مودال جزئیات را بلافاصله باز می‌کند) --}}
            <div
                wire:key="worker-card-{{ $this->rankingYear }}-{{ $this->rankingMonth }}-{{ $row['id'] }}"
                data-worker-card="true"
                data-worker-id="{{ $row['id'] }}"
                data-worker-name="{{ $row['full_name'] }}"
                data-worker-code="{{ $row['worker_code'] }}"
                data-worker-rank="{{ $rank }}"
                data-worker-score="{{ number_format($row['score'], 1) }}"
                data-worker-grade="{{ $row['grade']['label'] }}"
                data-worker-accent="{{ $row['grade']['accent'] }}"
                data-worker-stars="{{ number_format($row['stars'], 1) }}"
                data-worker-active="{{ $row['is_active'] ? '1' : '0' }}"
                data-ranking-year="{{ $this->rankingYear }}"
                data-ranking-month="{{ $this->rankingMonth }}"
                @click="handleCardClick($event)"
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
                     ۱. جلوگیری قطعی از باز شدن ناخواسته مودال با @click.stop و @mousedown.stop
                     ۲. مخفی‌سازی کامل در خروجی PDF و چاپ با print:hidden و no-print --}}
                <div
                    @click.stop
                    @mousedown.stop
                    class="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2 print:hidden no-print"
                    data-no-print
                    data-no-modal="true"
                >
                    <div class="flex items-center gap-1.5" @click.stop @mousedown.stop>
                        <span class="text-[10px] font-black text-slate-600">ضریب مدیریت:</span>

                        @if($rankingMode === 'monthly')
                            @if($isManager)
                                <div class="relative flex items-center" @click.stop @mousedown.stop>
                                    <select
                                        @click.stop
                                        @mousedown.stop
                                        wire:change="updateManagementScore({{ $row['id'] }}, $event.target.value)"
                                        wire:loading.attr="disabled"
                                        wire:target="updateManagementScore({{ $row['id'] }}, $event.target.value)"
                                        class="rounded-lg border border-amber-300 bg-amber-50/70 py-1 pr-2 pl-6 text-xs font-black text-amber-950 outline-none transition hover:bg-amber-100/70 focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-200 disabled:opacity-50"
                                        title="تغییر ضریب ارزیابی مدیریت برای ماه {{ $this->rankingMonthName }}"
                                    >
                                        @for($s = 10; $s >= 1; $s--)
                                            <option value="{{ $s }}" @selected((int) round($row['management_score']) === $s)>
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
                                    {{ (int) round($row['management_score']) }} از ۱۰
                                </span>
                            @endif
                        @else
                            {{-- در حالت بازه سفارشی، ضریب مدیریت میانگین ماه‌های این بازه است --}}
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-black text-amber-900 ring-1 ring-amber-200/70"
                                title="در بازه سفارشی، ضریب مدیریت میانگین ماه‌های تحت پوشش این بازه است."
                            >
                                <i class="bi bi-calculator text-[10px]"></i>
                                <span>{{ number_format($row['management_score'], 1) }}</span>
                                <span class="text-[9px] font-semibold text-amber-700">(میانگین)</span>
                            </span>
                        @endif
                    </div>

                    {{-- فرمول کسر امتیاز در صورت ضریب کمتر از ۱۰ --}}
                    <div>
                        @if($row['management_score'] < 10)
                            <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-md ring-1 ring-rose-200/60" title="امتیاز عملکرد × (ضریب مدیریت ÷ ۱۰)">
                                پایه: {{ number_format($row['base_score'], 1) }} × {{ round($row['management_score'] / 10, 2) }}
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

    {{-- هشدار سقف نمایش ۱۰۰ مددکار برتر --}}
    @if(!empty($ranking['truncated']))
        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs font-bold text-amber-900 shadow-sm print:hidden no-print">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                    <i class="bi bi-info-circle-fill text-sm"></i>
                </span>
                <p>
                    فقط <span class="font-black text-amber-950">{{ number_format($ranking['limit'] ?? 100) }}</span> مددکار برتر نمایش داده شده‌اند. برای مشاهده سایر مددکاران، فیلتر وضعیت یا جستجو را محدودتر کنید.
                </p>
            </div>
            <span class="inline-flex shrink-0 items-center rounded-xl bg-amber-200/60 px-3 py-1 text-[11px] font-black text-amber-900 ring-1 ring-amber-300">
                مجموع: {{ number_format($ranking['total'] ?? $ranking['evaluated']) }} مددکار
            </span>
        </div>
    @endif

    {{-- مودال جزئیات پرونده و ارزیابی تفصیلی عملکرد مددکار --}}
    @include('livewire.social-workers.partials.social-worker-details-modal')
</div>
