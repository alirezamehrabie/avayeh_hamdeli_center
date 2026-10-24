@php
    $selectedWorker = $this->selectedWorker;
    $identityItems = [];
    $metricItems = [];
    $detailSections = [];

    if ($selectedWorker) {
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
    }
@endphp

<div
    wire:key="shared-social-worker-modal"
    x-show="isOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 px-0 pb-0 pt-8 backdrop-blur-sm sm:items-center sm:p-4 print:hidden no-print"
    style="display: none;"
>
    {{-- پس‌زمینه تیره با قابلیت بستن فوری مودال --}}
    <div class="absolute inset-0" @click="close()"></div>

    {{-- کانتینر دیالوگ مودال با انیمیشن روان --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="relative flex h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 sm:h-[90vh] sm:rounded-3xl"
        @click.stop
        tabindex="-1"
    >
        {{-- سربرگ مودال: رندر فوری و بلادرنگ اطلاعات اولیه از روی کارت بدون تاخیر شبکه --}}
        <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 sm:px-5 sm:py-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-0.5 text-[11px] font-extrabold text-indigo-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                        <span>کارنامه و پرونده مددکار</span>
                    </span>

                    <span
                        x-show="worker.rank"
                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-black text-amber-800 ring-1 ring-amber-300/60"
                    >
                        <i class="bi bi-trophy-fill text-amber-500 text-xs"></i>
                        <span x-text="'رتبه ' + (worker.rank || '-')"></span>
                    </span>

                    <span
                        x-show="worker.grade"
                        class="rounded-full px-2 py-0.5 text-[10px] font-black ring-1 bg-slate-100 text-slate-700 ring-slate-200"
                        x-text="worker.grade"
                    ></span>

                    <span
                        x-show="!worker.is_active"
                        class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-200"
                    >
                        غیرفعال
                    </span>
                </div>

                <h2 class="mt-2 truncate text-lg font-black text-slate-900 sm:text-xl" x-text="worker.name || 'در حال بارگذاری...'"></h2>

                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                    <span dir="ltr">کد مددکار: <span class="font-bold text-slate-700" x-text="worker.code || '-'"></span></span>
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <span>امتیاز ماه: <span class="font-black text-indigo-700" x-text="worker.score || '۰'"></span> از ۱۰۰</span>
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <span class="inline-flex items-center gap-1 text-amber-500">
                        <i class="bi bi-star-fill text-xs"></i>
                        <span class="font-bold text-slate-700" x-text="worker.stars || '۰'"></span>
                    </span>
                </div>
            </div>

            <button
                type="button"
                @click="close()"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-2xl leading-none text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                aria-label="بستن"
            >
                &times;
            </button>
        </div>

        {{-- تب‌های ناوبری مودال --}}
        <div class="flex flex-wrap gap-2 border-b border-slate-100 bg-white px-4 pb-3 pt-1.5 sm:px-5">
            @foreach($workerModalTabs as $tabKey => $tab)
                @php($isActiveTab = $workerModalTab === $tabKey)
                <button
                    type="button"
                    wire:click="setWorkerModalTab('{{ $tabKey }}')"
                    wire:loading.attr="disabled"
                    :disabled="isLoading"
                    class="inline-flex h-9 min-w-fit shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 text-[13px] font-bold transition disabled:opacity-50 {{ $isActiveTab ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    @if($isActiveTab) aria-current="page" @endif
                >
                    <i class="bi {{ $tab['icon'] }} text-sm"></i>
                    <span>{{ $tab['label'] }}</span>
                    <span wire:loading wire:target="setWorkerModalTab('{{ $tabKey }}')" class="inline-block animate-spin text-xs">
                        <i class="bi bi-arrow-repeat"></i>
                    </span>
                </button>
            @endforeach
        </div>

        {{-- بدنه مودال: وضعیت بارگذاری (Skeleton)، پیام خطا با تلاش مجدد، یا محتوای تفصیلی --}}
        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-slate-50 p-3 sm:p-5">
            {{-- ۱. اسکلتون لودر شکیل هنگام انتظار برای دریافت جزئیات --}}
            <div x-show="isLoading" class="space-y-4 animate-pulse">
                {{-- کارت خلاصه عملکرد و امتیاز --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto">
                            <div class="h-24 w-24 rounded-2xl bg-slate-200 shrink-0"></div>
                            <div class="space-y-2 flex-1">
                                <div class="h-5 w-40 rounded-lg bg-slate-200"></div>
                                <div class="h-3.5 w-56 rounded-lg bg-slate-200"></div>
                                <div class="h-3 w-28 rounded-lg bg-slate-200"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full sm:w-64">
                            <div class="h-16 rounded-xl bg-slate-100"></div>
                            <div class="h-16 rounded-xl bg-slate-100"></div>
                        </div>
                    </div>
                </div>

                {{-- سنجه‌های عملکردی ۴گانه --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="h-28 rounded-2xl border border-slate-200 bg-white p-3 space-y-2.5">
                        <div class="h-3.5 w-24 bg-slate-200 rounded"></div>
                        <div class="h-7 w-20 bg-slate-300 rounded"></div>
                        <div class="h-2 w-full bg-slate-100 rounded"></div>
                    </div>
                    <div class="h-28 rounded-2xl border border-slate-200 bg-white p-3 space-y-2.5">
                        <div class="h-3.5 w-24 bg-slate-200 rounded"></div>
                        <div class="h-7 w-20 bg-slate-300 rounded"></div>
                        <div class="h-2 w-full bg-slate-100 rounded"></div>
                    </div>
                    <div class="h-28 rounded-2xl border border-slate-200 bg-white p-3 space-y-2.5">
                        <div class="h-3.5 w-24 bg-slate-200 rounded"></div>
                        <div class="h-7 w-20 bg-slate-300 rounded"></div>
                        <div class="h-2 w-full bg-slate-100 rounded"></div>
                    </div>
                    <div class="h-28 rounded-2xl border border-slate-200 bg-white p-3 space-y-2.5">
                        <div class="h-3.5 w-24 bg-slate-200 rounded"></div>
                        <div class="h-7 w-20 bg-slate-300 rounded"></div>
                        <div class="h-2 w-full bg-slate-100 rounded"></div>
                    </div>
                </div>

                {{-- باکس‌های جزئیات و تاریخچه --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                    <div class="h-4 w-48 rounded bg-slate-200"></div>
                    <div class="h-20 rounded-xl bg-slate-100"></div>
                    <div class="h-20 rounded-xl bg-slate-100"></div>
                </div>
            </div>

            {{-- ۲. مدیریت وضعیت خطا با دکمه تلاش مجدد --}}
            <div x-show="!isLoading && loadError" class="rounded-2xl border border-rose-200 bg-rose-50/70 p-6 text-center" style="display: none;">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 mb-3">
                    <i class="bi bi-exclamation-triangle text-xl"></i>
                </div>
                <h3 class="text-sm font-black text-rose-900" x-text="loadError"></h3>
                <p class="mt-1 text-xs font-semibold text-rose-700">امکان دریافت اطلاعات کارنامه تفصیلی از سرور وجود ندارد.</p>
                <button
                    type="button"
                    @click="retryLoad()"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-200"
                >
                    <i class="bi bi-arrow-clockwise text-sm"></i>
                    <span>تلاش مجدد</span>
                </button>
            </div>

            {{-- ۳. رندر محتوای کامل پس از اتمام بارگذاری داده‌های سرور --}}
            <div x-show="!isLoading && !loadError" style="display: none;">
                {{-- لودر انتقال بین زبانه‌ها --}}
                <div wire:loading wire:target="setWorkerModalTab" class="py-12 text-center">
                    <div class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 shadow-sm border border-slate-200 text-xs font-bold text-slate-600">
                        <svg class="h-4 w-4 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span>در حال بارگذاری اطلاعات برگه...</span>
                    </div>
                </div>

                <div wire:loading.remove wire:target="setWorkerModalTab">
                    @if($selectedWorker)
                        @if($workerModalTab === 'performance')
                            @include('livewire.social-workers.partials.social-worker-performance-tab')
                        @else
                            @include('livewire.social-workers.partials.social-worker-profile-tab')
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
