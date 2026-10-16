<div>
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
            class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto bg-slate-950/80 p-4 backdrop-blur-md"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mandatory-announcement-title"
        >
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative my-8 w-full max-w-2xl overflow-hidden rounded-3xl border bg-white shadow-2xl ring-1 ring-slate-900/10 {{ $priority === 'urgent' ? 'border-rose-300' : ($priority === 'normal' ? 'border-indigo-200' : 'border-amber-300') }}"
                @click.stop
            >
                {{-- Header Strip with Priority Banner --}}
                <div class="px-6 py-5 {{ $priority === 'urgent' ? 'bg-gradient-to-r from-rose-600 via-rose-700 to-red-700 text-white' : ($priority === 'normal' ? 'bg-gradient-to-r from-indigo-600 to-indigo-800 text-white' : 'bg-gradient-to-r from-amber-500 via-amber-600 to-orange-600 text-white') }}">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm shadow-inner">
                                @if($priority === 'urgent')
                                    <i class="bi bi-shield-exclamation text-2xl text-white animate-bounce"></i>
                                @elseif($priority === 'normal')
                                    <i class="bi bi-info-circle text-2xl text-white"></i>
                                @else
                                    <i class="bi bi-exclamation-triangle text-2xl text-white"></i>
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-white/25 px-2.5 py-0.5 text-[11px] font-extrabold tracking-wide text-white backdrop-blur-sm">
                                        @if($priority === 'urgent')
                                            بخشنامه فوری و الزامی
                                        @elseif($priority === 'normal')
                                            پیام اداری مدیریت
                                        @else
                                            اطلاعیه مهم مدیریت مرکز
                                        @endif
                                    </span>
                                    <span class="inline-flex h-2 w-2 rounded-full bg-white {{ $priority === 'urgent' ? 'animate-ping' : '' }}"></span>
                                </div>
                                <h2 id="mandatory-announcement-title" class="mt-1 text-lg font-black tracking-tight text-white sm:text-xl">
                                    {{ $title }}
                                </h2>
                            </div>
                        </div>

                        <div class="hidden shrink-0 text-left text-xs font-semibold text-white/85 sm:block">
                            <p class="truncate">{{ $creatorName }}</p>
                            <p class="mt-0.5 text-[11px] text-white/70" dir="ltr">{{ $createdAtJalali }}</p>
                        </div>
                    </div>
                </div>

                {{-- Body Content --}}
                <div class="space-y-4 p-6 sm:p-7">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 text-xs font-bold text-slate-500 sm:hidden">
                        <span>فرستنده: {{ $creatorName }}</span>
                        <span dir="ltr">{{ $createdAtJalali }}</span>
                    </div>

                    {{-- Warning Note --}}
                    <div class="flex items-start gap-3 rounded-2xl border p-3.5 text-xs font-bold leading-6 {{ $priority === 'urgent' ? 'border-rose-100 bg-rose-50/70 text-rose-900' : 'border-amber-100 bg-amber-50/70 text-amber-900' }}">
                        <i class="bi bi-lock-fill shrink-0 text-base {{ $priority === 'urgent' ? 'text-rose-600' : 'text-amber-600' }}"></i>
                        <span>
                            همکار گرامی؛ مطالعه و تأیید این پیام از سوی مدیریت مرکز <span class="underline underline-offset-4 decoration-2">الزامی</span> است. تا زمان تأیید مطالعه، دسترسی به پیشخوان و سایر بخش‌های پنل مسدود خواهد بود.
                        </span>
                    </div>

                    {{-- Announcement Text --}}
                    <div class="max-h-[42vh] overflow-y-auto overscroll-contain rounded-2xl border border-slate-200 bg-slate-50/60 p-4 text-sm font-medium leading-8 text-slate-800 shadow-inner sm:p-5 sm:text-base sm:leading-9">
                        <div class="whitespace-pre-line text-justify">
                            {!! nl2br(e($body)) !!}
                        </div>
                    </div>

                    {{-- Action Footer --}}
                    <div class="pt-2">
                        <button
                            type="button"
                            wire:click="acknowledge"
                            wire:loading.attr="disabled"
                            :disabled="!canSubmit"
                            class="group relative flex w-full items-center justify-center gap-3 rounded-2xl px-6 py-4 text-base font-black shadow-lg transition-all duration-200 focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60 {{ $priority === 'urgent' ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-rose-600/25 hover:from-rose-700 hover:to-red-700 focus:ring-rose-200' : 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-emerald-600/25 hover:from-emerald-700 hover:to-teal-700 focus:ring-emerald-200' }}"
                        >
                            {{-- Normal State --}}
                            <span wire:loading.remove wire:target="acknowledge" class="inline-flex items-center gap-2">
                                <template x-if="!canSubmit">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="bi bi-hourglass-split animate-spin text-lg"></i>
                                        <span>لطفاً متن پیام را مطالعه فرمایید (<span x-text="countdown"></span> ثانیه دیگر)...</span>
                                    </span>
                                </template>
                                <template x-if="canSubmit">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="bi bi-check2-circle text-xl transition-transform group-hover:scale-110"></i>
                                        <span>پیام را به طور کامل مطالعه کردم و تأیید می‌کنم</span>
                                    </span>
                                </template>
                            </span>

                            {{-- Loading State --}}
                            <span wire:loading wire:target="acknowledge" class="inline-flex items-center gap-2">
                                <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
