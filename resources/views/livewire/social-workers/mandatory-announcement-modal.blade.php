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
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center bg-slate-950/70 p-0 sm:p-4 backdrop-blur-md"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mandatory-announcement-title"
        >
            {{-- کارت اصلی پاپ‌آپ: گوشه‌های گرد عمیق در موبایل (BottomSheet Style) و کارت شیک در دسکتاپ --}}
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="relative flex flex-col w-full max-w-2xl max-h-[92vh] sm:max-h-[88vh] overflow-hidden rounded-t-[28px] sm:rounded-3xl border border-slate-200/90 bg-white shadow-2xl"
                @click.stop
            >
                {{-- نوار نشانگر کشیدن (Drag Handle) فقط مخصوص موبایل --}}
                <div class="sm:hidden flex justify-center pt-3 pb-1">
                    <span class="h-1.5 w-12 rounded-full bg-slate-200"></span>
                </div>

                {{-- سربرگ پیام: تفکیک‌شده، خلوت و آرام بدون فشردگی و درهم‌ریختگی --}}
                <div class="border-b border-slate-100 bg-white px-5 sm:px-8 pt-3 sm:pt-6 pb-4 sm:pb-5">
                    <div class="flex items-center justify-between gap-3">
                        {{-- بج نوع پیام --}}
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $priority === 'urgent' ? 'bg-rose-50 text-rose-700 border border-rose-200/80' : ($priority === 'normal' ? 'bg-slate-100 text-slate-700 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200/80') }}">
                            <span class="h-2 w-2 rounded-full {{ $priority === 'urgent' ? 'bg-rose-500' : ($priority === 'normal' ? 'bg-slate-400' : 'bg-amber-500') }}"></span>
                            @if($priority === 'urgent')
                                بخشنامه فوری و الزامی
                            @elseif($priority === 'normal')
                                پیام اداری
                            @else
                                اطلاعیه مهم
                            @endif
                        </span>

                        {{-- تاریخ صدور پیام --}}
                        <span class="text-xs text-slate-400 font-medium" dir="ltr">{{ $createdAtJalali }}</span>
                    </div>

                    {{-- موضوع / عنوان اصلی بخشنامه --}}
                    <h2 id="mandatory-announcement-title" class="mt-3.5 sm:mt-4 text-xl sm:text-2xl font-black text-slate-900 leading-snug sm:leading-relaxed break-words">
                        {{ $title }}
                    </h2>

                    {{-- فرستنده پیام به شکل زیرنویس آرام و خلوت --}}
                    @if($creatorName)
                        <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="text-slate-400">ابلاغ‌شده از سوی:</span>
                            <span class="font-bold text-slate-700">{{ $creatorName }}</span>
                        </div>
                    @endif
                </div>

                {{-- بدنه و کادر برجسته و قابل توجه متن پیام --}}
                <div class="flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-8 sm:py-6 bg-slate-50/60">
                    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm overflow-hidden border-r-4 {{ $priority === 'urgent' ? 'border-r-rose-500' : 'border-r-emerald-500' }}">
                        {{-- نوار بالای کادر متن برای هویت‌بخشی رسمی --}}
                        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 sm:px-6 py-2.5 text-xs font-bold text-slate-600">
                            <span class="inline-flex items-center gap-2">
                                <i class="bi bi-file-earmark-text text-sm {{ $priority === 'urgent' ? 'text-rose-600' : 'text-emerald-600' }}"></i>
                                <span>متن بخشنامه</span>
                            </span>
                            <span class="text-[11px] font-normal text-slate-400">نسخه ابلاغی به مددکار</span>
                        </div>

                        {{-- متن پیام با وضوح و خوانایی عالی --}}
                        <div class="p-4 sm:p-6">
                            <div class="text-slate-900 text-base sm:text-lg leading-8 sm:leading-9 font-normal text-right whitespace-pre-line selection:bg-emerald-100 selection:text-emerald-900">
                                {!! nl2br(e($body)) !!}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- بخش اقدام و دکمه تاییدیه سبز و متناسب با تم پلتفرم --}}
                <div class="border-t border-slate-100 bg-white p-4 sm:p-6 space-y-3 shadow-lg">
                    <template x-if="!canSubmit">
                        <div class="flex items-center justify-center gap-1.5 text-xs text-amber-800 bg-amber-50/90 border border-amber-200/80 rounded-xl py-2 px-3">
                            <i class="bi bi-hourglass-split text-amber-600 animate-spin text-xs"></i>
                            <span>فعال‌سازی دکمه تأیید پس از <span class="font-bold font-mono" x-text="countdown"></span> ثانیه مطالعه</span>
                        </div>
                    </template>

                    <button
                        type="button"
                        wire:click="acknowledge"
                        wire:loading.attr="disabled"
                        :disabled="!canSubmit"
                        class="w-full relative flex items-center justify-center gap-2 rounded-xl sm:rounded-2xl py-3.5 sm:py-4 px-5 text-sm sm:text-base font-black transition-all duration-200 focus:outline-none focus:ring-4 disabled:cursor-not-allowed"
                        :class="canSubmit
                            ? 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 active:scale-[0.99] text-white shadow-md hover:shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/30 focus:ring-emerald-200'
                            : 'bg-slate-100 text-slate-400 border border-slate-200 focus:ring-slate-100'"
                    >
                        {{-- حالت عادی دکمه --}}
                        <span wire:loading.remove wire:target="acknowledge" class="inline-flex items-center gap-2">
                            <template x-if="!canSubmit">
                                <span class="inline-flex items-center gap-1.5 text-xs sm:text-sm text-slate-400 font-bold">
                                    <span>مطالعه بخشنامه...</span>
                                </span>
                            </template>
                            <template x-if="canSubmit">
                                <span class="inline-flex items-center gap-2">
                                    <i class="bi bi-check2-circle text-lg sm:text-xl font-bold text-white"></i>
                                    <span>تأیید و ادامه</span>
                                </span>
                            </template>
                        </span>

                        {{-- حالت در حال ارسال و لودینگ --}}
                        <span wire:loading wire:target="acknowledge" class="inline-flex items-center gap-2 text-white">
                            <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span class="text-xs sm:text-sm font-bold">در حال ثبت تأییدیه...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
