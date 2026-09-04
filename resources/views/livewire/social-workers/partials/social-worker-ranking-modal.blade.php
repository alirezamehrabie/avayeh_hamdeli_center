@if($showRankingModal)
    @php
        $ranking = $this->ranking;
        $rankingRows = $ranking['rows'];
        $accentPalette = [
            'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-100', 'bar' => 'bg-emerald-500', 'stroke' => '#10b981'],
            'teal' => ['bg' => 'bg-teal-50', 'text' => 'text-teal-700', 'ring' => 'ring-teal-100', 'bar' => 'bg-teal-500', 'stroke' => '#14b8a6'],
            'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-100', 'bar' => 'bg-amber-500', 'stroke' => '#f59e0b'],
            'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'ring' => 'ring-orange-100', 'bar' => 'bg-orange-500', 'stroke' => '#f97316'],
            'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'ring' => 'ring-rose-100', 'bar' => 'bg-rose-500', 'stroke' => '#f43f5e'],
            'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'ring' => 'ring-slate-200', 'bar' => 'bg-slate-400', 'stroke' => '#94a3b8'],
        ];
        $medalAccents = [
            1 => 'from-amber-400 to-amber-600 text-white',
            2 => 'from-slate-300 to-slate-500 text-white',
            3 => 'from-orange-400 to-orange-600 text-white',
        ];
    @endphp

    <div
        wire:key="social-worker-ranking-modal"
        x-data="{
            open: @js($showRankingModal),
            previousActiveElement: null,
            closing: false,
            scrollLockStyles: null,
            allowBackdropClose: false,
            init() {
                this.previousActiveElement = document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
                this.allowBackdropClose = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                this.lockScroll();
                this.$nextTick(() => {
                    (this.$refs.closeButton || this.$refs.dialog)?.focus({ preventScroll: true });
                });
            },
            destroy() {
                this.unlockScroll();
            },
            lockScroll() {
                if (this.scrollLockStyles) return;
                this.scrollLockStyles = {
                    bodyOverflow: document.body.style.overflow,
                    htmlOverflow: document.documentElement.style.overflow,
                };
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
            },
            unlockScroll() {
                if (! this.scrollLockStyles) return;
                document.body.style.overflow = this.scrollLockStyles.bodyOverflow;
                document.documentElement.style.overflow = this.scrollLockStyles.htmlOverflow;
                this.scrollLockStyles = null;
            },
            closeFromBackdrop() {
                if (! this.allowBackdropClose) return;
                this.close();
            },
            close() {
                if (this.closing) return;
                this.closing = true;
                this.open = false;
                setTimeout(() => {
                    Promise.resolve($wire.closeRankingModal()).finally(() => {
                        this.unlockScroll();
                        this.$nextTick(() => {
                            if (this.previousActiveElement?.isConnected) {
                                this.previousActiveElement.focus({ preventScroll: true });
                            }
                        });
                    });
                }, 200);
            }
        }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 px-0 pb-0 pt-8 backdrop-blur-sm sm:items-center sm:p-4"
        @keydown.escape.window="close()"
        style="display: none;"
    >
        <div class="absolute inset-0" @click="closeFromBackdrop()"></div>

        <div
            x-ref="dialog"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 sm:h-auto sm:max-h-[90vh] sm:rounded-3xl"
            @click.stop
            tabindex="-1"
        >
            <div class="sticky top-0 z-10 border-b border-slate-100 bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                            <i class="bi bi-trophy-fill text-[11px]"></i>
                            <span>رتبه‌بندی عملکرد</span>
                        </div>
                        <h2 class="mt-2 truncate text-lg font-extrabold text-slate-900 sm:text-xl">جدول رتبه‌بندی مددکاران</h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            @if($rangeLabel = $this->rankingRangeLabel)
                                <span class="text-amber-700">{{ $rangeLabel }}</span>؛
                            @endif
                            بر پایهٔ امتیاز کل ارزیابی عملکرد؛ {{ number_format($ranking['evaluated']) }} مددکار از {{ number_format($ranking['total']) }} نتیجهٔ فیلترشده
                        </p>
                    </div>

                    <button
                        x-ref="closeButton"
                        type="button"
                        @click="close()"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-2xl leading-none text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        aria-label="بستن"
                    >
                        &times;
                    </button>
                </div>

                {{-- بازهٔ ارزیابی: مدیر می‌تواند عملکرد را در یک بازهٔ تاریخی دلخواه بسنجد؛ خالی یعنی همهٔ زمان‌ها. --}}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold text-slate-600">
                        <i class="bi bi-calendar3 text-amber-600"></i>
                        بازهٔ ارزیابی:
                    </span>

                    <div x-data="jalaliDateTimeField($wire.entangle('rankingDateFrom').live)" class="w-32">
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
                            aria-label="از تاریخ بازهٔ ارزیابی"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        >
                    </div>

                    <span class="text-[11px] font-bold text-slate-400">تا</span>

                    <div x-data="jalaliDateTimeField($wire.entangle('rankingDateTo').live)" class="w-32">
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
                            aria-label="تا تاریخ بازهٔ ارزیابی"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        >
                    </div>

                    @if($this->rankingRange)
                        <button
                            type="button"
                            wire:click="clearRankingRange"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold text-slate-500 outline-none transition hover:bg-slate-50 hover:text-slate-800 focus:ring-2 focus:ring-amber-100"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            حذف بازه (همهٔ زمان‌ها)
                        </button>
                    @endif
                </div>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto bg-slate-50 p-3 sm:p-4">
                @forelse($rankingRows as $index => $row)
                    @php
                        $rank = $index + 1;
                        $palette = $accentPalette[$row['grade']['accent']] ?? $accentPalette['slate'];
                        $gaugeCircumference = 2 * M_PI * 20;
                        $gaugeOffset = $gaugeCircumference * (1 - min(100, max(0, $row['score'])) / 100);
                    @endphp

                    <button
                        type="button"
                        wire:key="ranking-row-{{ $row['id'] }}"
                        wire:click="showWorkerFromRanking({{ $row['id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="showWorkerFromRanking({{ $row['id'] }})"
                        class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 text-right shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/40 disabled:cursor-wait"
                    >
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-black {{ isset($medalAccents[$rank]) ? 'bg-gradient-to-br ' . $medalAccents[$rank] : 'bg-slate-100 text-slate-500' }}">
                            {{ $rank }}
                        </span>

                        <div class="relative h-14 w-14 shrink-0">
                            <svg class="h-14 w-14 -rotate-90" viewBox="0 0 48 48">
                                <circle cx="24" cy="24" r="20" fill="none" stroke="#e2e8f0" stroke-width="5"></circle>
                                <circle
                                    cx="24" cy="24" r="20" fill="none"
                                    stroke="{{ $palette['stroke'] }}"
                                    stroke-width="5"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ round($gaugeCircumference, 2) }}"
                                    stroke-dashoffset="{{ round($gaugeOffset, 2) }}"
                                ></circle>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-[11px] font-black text-slate-900">
                                {{ number_format($row['score'], 1) }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="truncate text-sm font-extrabold text-slate-900">{{ $row['full_name'] }}</p>
                                @unless($row['is_active'])
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-100">غیرفعال</span>
                                @endunless
                            </div>

                            <div class="mt-1 flex items-center gap-1" role="img" aria-label="امتیاز {{ number_format($row['stars'], 1) }} از ۵ ستاره">
                                @for($starIndex = 1; $starIndex <= 5; $starIndex++)
                                    @php
                                        $starIcon = $row['stars'] >= $starIndex
                                            ? 'bi-star-fill'
                                            : ($row['stars'] >= $starIndex - 0.5 ? 'bi-star-half' : 'bi-star');
                                    @endphp
                                    <i class="bi {{ $starIcon }} text-xs {{ $row['stars'] >= $starIndex - 0.5 ? 'text-amber-500' : 'text-slate-300' }}"></i>
                                @endfor
                                <span class="mr-1 text-[11px] font-bold text-slate-600">{{ number_format($row['stars'], 1) }} / ۵</span>
                                <span class="mr-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold ring-1 {{ $palette['bg'] }} {{ $palette['text'] }} {{ $palette['ring'] }}">
                                    {{ $row['grade']['label'] }}
                                </span>
                            </div>

                            <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-[10px] font-semibold text-slate-500">
                                <span dir="ltr">کد {{ $row['worker_code'] }}</span>
                                <span>{{ number_format($row['deliveries']) }} تحویل</span>
                                <span>{{ number_format($row['allocations']) }} تخصیص</span>
                                @if($row['median_hours'] !== null)
                                    <span>میانهٔ واکنش {{ number_format($row['median_hours'], 1) }} ساعت</span>
                                @endif
                                @if($row['pending_overdue'] > 0)
                                    <span class="text-rose-600">{{ number_format($row['pending_overdue']) }} تخصیص بی‌پاسخ</span>
                                @endif
                            </div>
                        </div>

                        <i class="bi bi-chevron-left shrink-0 text-sm text-slate-300" wire:loading.remove wire:target="showWorkerFromRanking({{ $row['id'] }})"></i>
                        <svg wire:loading wire:target="showWorkerFromRanking({{ $row['id'] }})" class="h-4 w-4 shrink-0 animate-spin text-indigo-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </button>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i class="bi bi-trophy text-xl"></i>
                        </span>
                        <p class="mt-3 text-sm font-extrabold text-slate-900">مددکاری برای رتبه‌بندی یافت نشد</p>
                        <p class="mx-auto mt-2 max-w-md text-xs leading-6 text-slate-500">
                            با فیلترها و بازهٔ ارزیابی فعلی نتیجه‌ای وجود ندارد. در صورت نیاز بازه را حذف کنید یا فیلترها را تغییر دهید.
                        </p>
                    </div>
                @endforelse

                @if($ranking['truncated'])
                    <p class="rounded-xl border border-dashed border-slate-200 bg-white p-3 text-center text-[11px] font-semibold text-slate-500">
                        فقط {{ number_format(\App\Livewire\SocialWorkers\AdvancedSocialWorkerReport::RANKING_LIMIT) }} مددکار برتر نمایش داده می‌شود؛ برای دیدن بقیه فیلترها را محدودتر کنید.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
