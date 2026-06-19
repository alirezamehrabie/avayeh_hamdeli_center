<div>
@if($showQrModal)
    <div
        x-data="{
            showScanUrl: false,
            lastActiveElement: null,
            scrollLockStyles: null,
            init() {
                this.lastActiveElement = document.activeElement;
                this.lockScroll();
                this.$nextTick(() => this.$refs.closeButton?.focus());
            },
            destroy() {
                this.unlockScroll();
            },
            lockScroll() {
                if (this.scrollLockStyles) return;

                this.scrollLockStyles = {
                    bodyOverflow: document.body.style.overflow,
                    bodyPosition: document.body.style.position,
                    bodyTop: document.body.style.top,
                    bodyWidth: document.body.style.width,
                    htmlOverflow: document.documentElement.style.overflow,
                    bodyPaddingRight: document.body.style.paddingRight,
                    scrollY: window.scrollY,
                };

                const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;

                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.top = `-${this.scrollLockStyles.scrollY}px`;
                document.body.style.width = '100%';

                if (scrollbarWidth > 0) {
                    document.body.style.paddingRight = `${scrollbarWidth}px`;
                }
            },
            unlockScroll() {
                if (! this.scrollLockStyles) return;

                document.body.style.overflow = this.scrollLockStyles.bodyOverflow;
                document.body.style.position = this.scrollLockStyles.bodyPosition;
                document.body.style.top = this.scrollLockStyles.bodyTop;
                document.body.style.width = this.scrollLockStyles.bodyWidth;
                document.documentElement.style.overflow = this.scrollLockStyles.htmlOverflow;
                document.body.style.paddingRight = this.scrollLockStyles.bodyPaddingRight;
                window.scrollTo(0, this.scrollLockStyles.scrollY);
                this.scrollLockStyles = null;
            },
            focusableElements() {
                return Array.from(this.$refs.dialog.querySelectorAll([
                    'a[href]',
                    'button:not([disabled])',
                    'textarea:not([disabled])',
                    'input:not([disabled])',
                    'select:not([disabled])',
                    '[tabindex]:not([tabindex=\'-1\'])',
                ].join(','))).filter((element) => element.offsetParent !== null);
            },
            trapFocus(event) {
                const focusable = this.focusableElements();

                if (!focusable.length) {
                    event.preventDefault();
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            },
            async close() {
                await $wire.closeQrModal();
                this.$nextTick(() => this.lastActiveElement?.focus?.());
            },
            closeFromBackdrop() {
                if ($wire.confirmingQrLifecycleAction) {
                    this.$refs.dialog?.focus();
                    return;
                }

                this.close();
            }
        }"
        @keydown.escape.window.prevent="close()"
        @keydown.tab="trapFocus($event)"
        @click.self="closeFromBackdrop()"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 px-2 pt-6 backdrop-blur-sm sm:items-center sm:p-4"
    >
        <div
            x-ref="dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="qr-identity-modal-title"
            aria-describedby="qr-identity-modal-description"
            tabindex="-1"
            class="w-full max-w-lg overflow-hidden rounded-t-[1.75rem] border border-cyan-100 bg-white shadow-2xl sm:max-w-2xl sm:max-h-[90vh] sm:rounded-3xl"
        >
            <div class="flex items-start justify-between gap-3 bg-cyan-700 px-3.5 py-3 text-white sm:gap-4 sm:px-6 sm:py-5">
                <div class="min-w-0">
                    <h2 id="qr-identity-modal-title" class="text-base font-extrabold sm:text-xl">کارت QR {{ $subjectLabel }}</h2>
                    <p id="qr-identity-modal-description" class="mt-1 truncate text-xs text-white/85 sm:text-sm">{{ $subjectName }} - {{ $subjectCode }}</p>
                </div>
                <button type="button" @click="close()" x-ref="closeButton" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-xl leading-none text-white transition hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/70 sm:h-9 sm:w-9 sm:text-2xl" aria-label="بستن">&times;</button>
            </div>

            <div class="max-h-[78vh] space-y-3 overflow-y-auto p-3 sm:max-h-[calc(90vh-5.5rem)] sm:space-y-4 sm:p-6">
                @if($publicCode && $qrMarkup)
                    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:gap-4 sm:p-4 lg:grid-cols-[300px_minmax(0,1fr)] lg:items-center">
                        <div class="flex min-h-[14rem] w-full items-center justify-center rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm sm:min-h-[21rem] sm:p-3 lg:w-[300px]">
                            <div class="aspect-square w-full max-w-[190px] sm:max-w-[280px] [&>svg]:block [&>svg]:h-full [&>svg]:w-full">
                                {!! $qrMarkup !!}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-500">شناسه کارت</p>
                            <p class="mt-1 break-all font-mono text-sm font-black text-slate-900 sm:text-lg">{{ $publicCode }}</p>
                            <button
                                type="button"
                                @click="showScanUrl = !showScanUrl"
                                class="mt-3 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                                :aria-expanded="showScanUrl.toString()"
                            >
                                <span>نشانی اسکن احراز هویت‌شده</span>
                                <span class="text-[10px] text-slate-400" x-text="showScanUrl ? 'پنهان' : 'نمایش'"></span>
                            </button>
                            <div
                                x-show="showScanUrl"
                                x-transition.opacity.duration.150ms
                                class="mt-2 break-all rounded-xl bg-white px-2.5 py-2 text-[11px] leading-5 text-slate-700 sm:px-3 sm:text-xs"
                                style="display: none;"
                            >
                                {{ $scanUrl }}
                            </div>
                            <p class="mt-2 text-[11px] leading-5 text-slate-500 sm:mt-3 sm:text-xs">این QR فقط پس از ورود کارکنان قابل استفاده است و شامل اطلاعات شخصی نیست.</p>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs leading-5 text-amber-800 sm:px-4 sm:py-3 sm:text-sm">برای این {{ $subjectLabel }} QR فعال وجود ندارد.</div>
                @endif

                @if($issuedQrToken)
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs leading-5 text-emerald-800 sm:px-4 sm:py-3 sm:text-sm">
                        QR جدید صادر شد. نشانی اسکن نمایش‌داده‌شده برای چاپ به‌روزرسانی شده است.
                    </div>
                @endif

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-[11px] leading-5 text-amber-900 sm:px-4 sm:py-3 sm:text-xs sm:leading-6">
                    این کارت به عنوان کارت هویتی بلندمدت چاپ می‌شود. صدور مجدد یا ابطال فقط در شرایط کنترل‌شده، با دسترسی کامل و ثبت علت مجاز است.
                </div>

                @if($confirmingQrLifecycleAction)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3 sm:p-4">
                        <p class="text-xs font-black text-rose-800 sm:text-sm">
                            {{ $qrLifecycleAction === 'reissue' ? 'تایید صدور مجدد QR' : 'تایید ابطال QR' }}
                        </p>
                        <label class="mt-2.5 block text-xs font-bold text-slate-700 sm:mt-3" for="qr-lifecycle-reason">علت اقدام</label>
                        <textarea
                            id="qr-lifecycle-reason"
                            wire:model.defer="qrLifecycleReason"
                            rows="3"
                            class="mt-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100 sm:text-sm"
                            placeholder="علت دقیق ابطال یا صدور مجدد کارت را ثبت کنید..."
                        ></textarea>
                        @error('qrLifecycleReason') <p class="mt-1 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                        <div class="mt-2.5 grid grid-cols-2 gap-2 sm:mt-3 sm:flex sm:flex-row sm:justify-end">
                            <button type="button" wire:click="cancelQrLifecycleAction" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 sm:w-auto sm:px-4 sm:text-sm">انصراف</button>
                            <button type="button" wire:click="confirmQrLifecycleAction" class="rounded-xl bg-rose-700 px-3 py-2 text-xs font-bold text-white sm:w-auto sm:px-4 sm:text-sm">تایید نهایی</button>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-row sm:flex-wrap sm:justify-end">
                    @can('full-access')
                        <button
                            type="button"
                            wire:click="requestQrLifecycleAction('revoke')"
                            @disabled(!$publicCode)
                            class="flex min-h-14 items-center justify-center rounded-2xl border border-rose-200 bg-gradient-to-b from-rose-50 to-white px-2 py-2 text-center text-[11px] font-semibold leading-4 text-rose-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md disabled:opacity-50 sm:min-h-0 sm:w-auto sm:px-4 sm:text-sm"
                        >
                            ابطال
                        </button>
                        <button
                            type="button"
                            wire:click="requestQrLifecycleAction('reissue')"
                            class="flex min-h-14 items-center justify-center rounded-2xl bg-gradient-to-b from-cyan-600 to-cyan-700 px-2 py-2 text-center text-[11px] font-semibold leading-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:min-h-0 sm:w-auto sm:px-4 sm:text-sm"
                        >
                            صدور مجدد
                        </button>
                    @endcan
                    <button
                        type="button"
                        @click="close()"
                        class="flex min-h-14 items-center justify-center rounded-2xl border border-slate-200 bg-white px-2 py-2 text-center text-[11px] font-semibold leading-4 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:min-h-0 sm:w-auto sm:px-4 sm:text-sm"
                    >
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
