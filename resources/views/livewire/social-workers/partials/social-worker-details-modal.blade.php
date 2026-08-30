@if($this->selectedWorker)
    @php
        $selectedWorker = $this->selectedWorker;
        $deliveredValue = (int) ($selectedWorker->service_deliveries_sum_delivered_total_value ?? 0);
        $identityItems = [
            ['label' => 'نام و نام خانوادگی', 'value' => $selectedWorker->full_name ?: '-', 'icon' => 'bi-person'],
            ['label' => 'کد مددکار', 'value' => $selectedWorker->worker_code ?: '-', 'icon' => 'bi-hash', 'dir' => 'ltr'],
            ['label' => 'کد ملی', 'value' => $selectedWorker->national_id ?: '-', 'icon' => 'bi-credit-card-2-front', 'dir' => 'ltr'],
            ['label' => 'تاریخ تولد', 'value' => $selectedWorker->formatted_birth_date ?: '-', 'icon' => 'bi-calendar3', 'meta_label' => 'سن', 'meta_value' => $selectedWorker->age !== null ? $selectedWorker->age . ' سال' : '-'],
            ['label' => 'شماره موبایل', 'value' => $selectedWorker->mobile ?: '-', 'icon' => 'bi-phone', 'dir' => 'ltr'],
            ['label' => 'تاریخ شروع همکاری', 'value' => $selectedWorker->formatted_start_date ?: '-', 'icon' => 'bi-calendar-check'],
            ['label' => 'منطقه / ناحیه', 'value' => $selectedWorker->district?->name ?: '-', 'icon' => 'bi-geo-alt'],
            ['label' => 'شغل', 'value' => $selectedWorker->occupation?->name ?: '-', 'icon' => 'bi-briefcase'],
            ['label' => 'مقطع تحصیلی', 'value' => $selectedWorker->academicLevel?->title ?: '-', 'icon' => 'bi-mortarboard'],
        ];
        $metricItems = [
            ['label' => 'خانوارهای تحت پوشش', 'value' => number_format((int) ($selectedWorker->guardians_count ?? 0)) . ' خانوار', 'icon' => 'bi-house-heart'],
            ['label' => 'مددجویان تحت پوشش', 'value' => number_format((int) ($selectedWorker->people_count ?? 0)) . ' نفر', 'icon' => 'bi-people-fill'],
            ['label' => 'خدمات تخصیص‌یافته', 'value' => number_format((int) ($selectedWorker->services_count ?? 0)) . ' خدمت', 'icon' => 'bi-box-seam'],
            ['label' => 'تحویل‌های انجام‌شده', 'value' => number_format((int) ($selectedWorker->service_deliveries_count ?? 0)) . ' مورد', 'icon' => 'bi-check2-circle'],
            ['label' => 'ارزش کل تحویل‌ها', 'value' => $deliveredValue ? number_format($deliveredValue) . ' ریال' : '-', 'icon' => 'bi-cash-coin'],
            ['label' => 'وضعیت فعالیت', 'value' => $selectedWorker->is_active ? 'فعال' : 'غیرفعال', 'icon' => 'bi-person-check'],
        ];
        $detailSections = [
            [
                'title' => 'اطلاعات هویتی',
                'subtitle' => 'مشخصات اصلی و سازمانی مددکار',
                'icon' => 'bi-person-vcard',
                'accent' => 'text-cyan-700 bg-cyan-50 ring-cyan-100',
                'metaAccent' => 'text-cyan-700 bg-cyan-50 ring-cyan-100',
                'items' => $identityItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
            [
                'title' => 'شاخص‌های عملکرد',
                'subtitle' => 'حجم کاری، خدمات و تحویل‌ها',
                'icon' => 'bi-graph-up-arrow',
                'accent' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
                'metaAccent' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
                'items' => $metricItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
        ];
    @endphp

    <div
        wire:key="social-worker-modal"
        x-data="{
            open: @js($showWorkerModal),
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
                    Promise.resolve($wire.closeWorkerModal()).finally(() => {
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
            class="relative flex h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 sm:h-[90vh] sm:rounded-3xl"
            @click.stop
            tabindex="-1"
        >
            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                        <span>پرونده مددکار</span>
                    </div>
                    <h2 class="mt-2 truncate text-lg font-extrabold text-slate-900 sm:text-xl">
                        {{ $selectedWorker->full_name ?: 'بدون نام' }}
                    </h2>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                        <span dir="ltr">کد مددکار: {{ $selectedWorker->worker_code ?: '-' }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span dir="ltr">{{ $selectedWorker->national_id ?: '-' }}</span>
                    </div>
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

            <div class="flex flex-wrap gap-2 border-b border-slate-100 bg-white px-4 pb-3 pt-1.5 sm:px-5">
                @foreach($workerModalTabs as $tabKey => $tab)
                    @php($isActiveTab = $workerModalTab === $tabKey)
                    <button
                        type="button"
                        wire:click="setWorkerModalTab('{{ $tabKey }}')"
                        class="inline-flex h-9 min-w-fit shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 text-[13px] font-bold transition {{ $isActiveTab ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        @if($isActiveTab) aria-current="page" @endif
                    >
                        <i class="bi {{ $tab['icon'] }} text-sm"></i>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-slate-50 p-3 sm:p-5">
                @if($workerModalTab === 'performance')
                    @include('livewire.social-workers.partials.social-worker-performance-tab')
                @else
                    @include('livewire.social-workers.partials.social-worker-profile-tab')
                @endif
            </div>
        </div>
    </div>
@endif
