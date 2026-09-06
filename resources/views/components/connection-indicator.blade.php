{{-- فاز ۲ PWA: نشانگر وضعیت اتصال + هشدار شکست عملیات در حالت آفلاین --}}
{{-- وضعیت از resources/js/connection-status.js می‌آید (رویداد connection:status) --}}
<div x-cloak
     x-data="{
        status: window.pwaConnection ? window.pwaConnection.status : 'checking',
        showChecking: false,
        checkingTimer: null,
        toast: false,
        toastTimer: null,
        init() {
            // بازخوانی وضعیت پس از نصب: اگر اولین رویداد پیش از attach شدن
            // listener این کامپوننت شلیک شده باشد، اینجا همگام می‌شویم.
            this.$nextTick(() => {
                if (window.pwaConnection) {
                    this.status = window.pwaConnection.status;
                }
            });
            this.$watch('status', (value) => this.applyCheckingDelay(value));
            this.applyCheckingDelay(this.status);
        },
        // «در حال اتصال» فقط وقتی نمایش داده می‌شود که بیش از ۳٫۵ ثانیه طول
        // کشیده باشد (اتصال واقعاً کند); سنجش‌های سریع چیزی نشان نمی‌دهند.
        applyCheckingDelay(value) {
            if (value === 'checking') {
                if (!this.checkingTimer) {
                    this.checkingTimer = setTimeout(() => {
                        this.checkingTimer = null;
                        if (this.status === 'checking') {
                            this.showChecking = true;
                        }
                    }, 3500);
                }
            } else {
                clearTimeout(this.checkingTimer);
                this.checkingTimer = null;
                this.showChecking = false;
            }
        },
     }"
     x-on:connection:status.window="status = $event.detail.status"
     x-on:pwa:livewire-failed.window="if (status !== 'online') { toast = true; clearTimeout(toastTimer); toastTimer = setTimeout(() => { toast = false }, 6000) }"
     class="pointer-events-none fixed bottom-4 left-4 z-[9998] print:hidden"
     style="max-width: calc(100vw - 2rem); bottom: calc(1rem + env(safe-area-inset-bottom));"
>
    {{-- درخواستی که در حالت آفلاین شکست خورده: صریحاً بگوییم چیزی ذخیره نشده --}}
    <div x-show="toast"
         x-transition.opacity.duration.300ms
         class="mb-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow-xl">
        اتصال اینترنت قطع است — عملیات انجام نشد و هیچ چیزی ذخیره نشد.
    </div>

    {{-- عمداً هیچ نشانگری برای حالت آنلاین وجود ندارد. «در حال اتصال» هم فوری
         نمایش داده نمی‌شود؛ فقط اگر بیش از ۳٫۵ ثانیه طول بکشد (showChecking). --}}
    <div x-show="showChecking"
         class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50/90 px-3 py-1 text-xs font-bold text-amber-800 shadow-sm">
        <span aria-hidden="true">🟡</span>
        <span>در حال اتصال...</span>
    </div>

    <div x-show="status === 'offline'"
         class="inline-flex animate-pulse items-center gap-1.5 rounded-full bg-rose-600 px-3 py-1 text-xs font-bold text-white shadow-lg">
        <span aria-hidden="true">🔴</span>
        <span>بدون اتصال</span>
    </div>
</div>
