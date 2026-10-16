<div @if(! $hasPending) wire:poll.60s="refreshPending" @endif>
    @if($hasPending)
        <div
            wire:key="mandatory-announcement-overlay-{{ $announcementId }}"
            x-data="{
                countdown: @js($minReadingSeconds),
                canSubmit: @js($minReadingSeconds <= 0),
                timer: null,
                init() {
                    document.body.style.overflow = 'hidden';

                    if (this.countdown > 0) {
                        this.timer = setInterval(() => {
                            this.countdown--;
                            if (this.countdown <= 0) {
                                clearInterval(this.timer);
                                this.canSubmit = true;
                            }
                        }, 1000);
                    } else {
                        this.canSubmit = true;
                    }
                },
                destroy() {
                    if (this.timer) {
                        clearInterval(this.timer);
                    }
                    document.body.style.overflow = '';
                }
            }"
            @keydown.escape.window.prevent
            class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mandatory-announcement-title"
        >
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-250"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative my-8 w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl"
                @click.stop
            >
                {{-- سربرگ خلوت و مینیمال بدون رنگ‌های تند و حواس‌پرت‌کن --}}
                <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-5 sm:px-8">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $priority === 'urgent' ? 'bg-rose-50 text-rose-700 border border-rose-200/80' : ($priority === 'normal' ? 'bg-slate-100 text-slate-700 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200/80') }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $priority === 'urgent' ? 'bg-rose-500' : ($priority === 'normal' ? 'bg-slate-400' : 'bg-amber-500') }}"></span>
                                @if($priority === 'urgent')
                                    بخشنامه فوری و الزامی
                                @elseif($priority === 'normal')
                                    پیام اداری مدیریت
                                @else
                                    اطلاعیه مهم مدیریت
                                @endif
                            </span>

                            @if($isBlocking)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                    <i class="bi bi-lock text-xs"></i>
                                    تأیید الزامی
                                </span>
                            @endif
                        </div>

                        <div class="text-left text-xs text-slate-400 font-medium">
                            <span class="block text-slate-600 font-semibold">{{ $creatorName }}</span>
                            <span class="text-[11px] text-slate-400" dir="ltr">{{ $createdAtJalali }}</span>
                        </div>
                    </div>

                    {{-- عنوان اصلی پیام با تایپوگرافی مشخص و قابل توجه --}}
                    <h2 id="mandatory-announcement-title" class="mt-4 text-xl font-black text-slate-900 sm:text-2xl leading-snug">
                        {{ $title }}
                    </h2>
                </div>

                {{-- بدنه و متن اصلی بخشنامه: متمرکز، خوانا، با کنتراست عالی و فاصله خطوط مناسب --}}
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="max-h-[50vh] overflow-y-auto overscroll-contain pr-1 pl-2">
                        <div class="text-base sm:text-lg leading-relaxed sm:leading-9 text-slate-800 font-normal whitespace-pre-line text-justify selection:bg-cyan-100">
                            {!! nl2br(e($body)) !!}
                        </div>
                    </div>

                    {{-- بخش تأیید نهایی --}}
                    <div class="border-t border-slate-100 pt-5 space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-500 px-1">
                            <span class="inline-flex items-center gap-1.5 text-slate-500">
                                <i class="bi bi-shield-check text-slate-400"></i>
                                برای ادامه فعالیت در سامانه، لطفاً پیام را مطالعه و تأیید نمایید.
                            </span>
                            <template x-if="!canSubmit">
                                <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">
                                    <span x-text="countdown"></span> ثانیه تا فعال شدن دکمه
                                </span>
                            </template>
                        </div>

                        <button
                            type="button"
                            wire:click="acknowledge"
                            wire:loading.attr="disabled"
                            :disabled="!canSubmit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-3.5 px-5 text-sm font-bold transition-all duration-200 focus:outline-none focus:ring-4 disabled:cursor-not-allowed"
                            :class="canSubmit
                                ? 'bg-slate-900 hover:bg-slate-800 text-white shadow-sm hover:shadow focus:ring-slate-200 active:scale-[0.99]'
                                : 'bg-slate-100 text-slate-400 border border-slate-200 focus:ring-slate-100'"
                        >
                            {{-- وضعیت عادی --}}
                            <span wire:loading.remove wire:target="acknowledge" class="inline-flex items-center gap-2">
                                <template x-if="!canSubmit">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="bi bi-hourglass-split text-base"></i>
                                        <span>لطفاً متن پیام را با دقت مطالعه فرمایید...</span>
                                    </span>
                                </template>
                                <template x-if="canSubmit">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="bi bi-check2 text-lg font-bold"></i>
                                        <span>پیام را به طور کامل مطالعه کردم و تأیید می‌کنم</span>
                                    </span>
                                </template>
                            </span>

                            {{-- وضعیت در حال ارسال --}}
                            <span wire:loading wire:target="acknowledge" class="inline-flex items-center gap-2 text-white">
                                <svg class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span>در حال ثبت تأییدیه و بازگشایی پنل...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
