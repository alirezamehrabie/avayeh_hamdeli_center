<div class="space-y-4" dir="rtl">
    @php
        $selectedPerson = $this->selectedPerson;
        $searchResults = $this->searchResults;
        $timeline = $this->timeline;
        $caseFileTotals = $this->caseFileTotals;
        $editingCaseRecord = $this->editingCaseRecord;
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400">پرونده خدمات و فعالیت‌ها</p>
                <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">پرونده مددجو</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    نسخه اولیه این بخش، سوابق ثبت‌شده در دفتر خدمات، گیت‌های توزیع و حضور در فعالیت‌ها را به‌صورت خواندنی نمایش می‌دهد.
                </p>
            </div>

            @if($selectedPerson)
                <button
                    type="button"
                    wire:click="clearSelection"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                >
                    انتخاب مددجوی دیگر
                </button>
            @endif
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-3"
             x-data="{
                ...idCardScanner({
                    resolveScan: async (payload) => {
                        const response = await $wire.resolveScannedBeneficiaryQr(payload);

                        if (response?.ok) {
                            window.setTimeout(() => window.dispatchEvent(new CustomEvent('close-beneficiary-case-file-qr-scanner')), 700);
                        }

                        return response;
                    },
                    successSoundUrl: '/sounds/scan-card.wav',
                    enableResultBanner: false,
                    autoStart: false,
                    autoResumeAfterError: false,
                    autoResumeAfterSuccess: false,
                 }),
                 qrScannerOpen: false,
                 openingScanner: false,
                 scannerHistoryActive: false,
                 pushScannerHistory() {
                    if (this.scannerHistoryActive) {
                        return;
                    }

                    try {
                        window.history.pushState({ ...(window.history.state || {}), beneficiaryCaseFileQrScanner: true }, '', window.location.href);
                        this.scannerHistoryActive = true;
                    } catch (error) {
                        this.scannerHistoryActive = false;
                    }
                 },
                 handleScannerPopState() {
                    if (!this.qrScannerOpen) {
                        this.scannerHistoryActive = false;
                        return;
                    }

                    this.closeScanner({ syncHistory: false });
                 },
                 async openScanner() {
                    if (this.openingScanner || this.qrScannerOpen) {
                        return;
                    }

                    this.openingScanner = true;
                    this.qrScannerOpen = true;
                    this.pushScannerHistory();

                    try {
                        await $nextTick();
                        await this.waitForScannerFrame();
                        await this.waitForScannerBoot();
                        await this.startCamera();
                    } finally {
                        this.openingScanner = false;
                    }
                 },
                 async waitForScannerFrame() {
                    for (let attempt = 0; attempt < 20; attempt++) {
                        const rect = this.$refs.scanner?.getBoundingClientRect();

                        if (rect?.width > 160 && rect?.height > 160) {
                            return;
                        }

                        await new Promise((resolve) => requestAnimationFrame(resolve));
                    }
                 },
                 async waitForScannerBoot() {
                    for (let attempt = 0; attempt < 50; attempt++) {
                        if (this.html5QrCode && this.Html5Qrcode) {
                            return;
                        }

                        await new Promise((resolve) => setTimeout(resolve, 100));
                    }

                    await this.init();
                 },
                 closeScanner({ syncHistory = true } = {}) {
                    const shouldRestoreHistory = syncHistory && this.scannerHistoryActive;

                    this.qrScannerOpen = false;
                    this.stopCamera();

                    if (shouldRestoreHistory) {
                        this.scannerHistoryActive = false;
                        window.history.back();
                    } else if (!syncHistory) {
                        this.scannerHistoryActive = false;
                    }
                 },
             }"
             x-init="init(); window.addEventListener('popstate', () => handleScannerPopState()); $watch('qrScannerOpen', (open) => document.documentElement.classList.toggle('overflow-hidden', open))"
             x-on:open-beneficiary-case-file-qr-scanner.window="openScanner()"
             x-on:close-beneficiary-case-file-qr-scanner.window="closeScanner()"
             x-on:keydown.escape.window="if (qrScannerOpen) closeScanner()"
             x-on:id-card-scanner-resume.window="resumeFromWire()"
             x-on:id-card-scanner-pause.window="pauseFromWire()"
        >
            <label for="case-file-person-search" class="mb-2 block text-sm font-bold text-slate-700">جستجوی مددجو</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                    <i class="bi bi-search text-sm"></i>
                </span>
                <input
                    id="case-file-person-search"
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pr-9 pl-14 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="نام، کد ملی یا کد مددجو..."
                >
                <button
                    type="button"
                    @click.prevent="$dispatch('open-beneficiary-case-file-qr-scanner')"
                    class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-300 hover:text-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    aria-label="اسکن QR"
                    title="اسکن QR"
                >
                    <i class="bi bi-qr-code-scan text-lg"></i>
                </button>
            </div>

            @if(trim($search) !== '' && $searchResults->isEmpty())
                <p class="mt-2 text-xs font-semibold text-slate-500">برای جستجوی متنی حداقل ۲ کاراکتر وارد کنید یا عبارت دقیق‌تری بنویسید.</p>
            @endif

            @if($searchResults->isNotEmpty())
                <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($searchResults as $person)
                        <button
                            type="button"
                            wire:click="selectPerson({{ $person->id }})"
                            class="rounded-xl border border-slate-200 bg-white p-3 text-right transition hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        >
                            <span class="block text-sm font-black text-slate-800">{{ $person->full_name ?: trim($person->first_name.' '.$person->last_name) }}</span>
                            <span class="mt-1 block text-xs text-slate-500">کد مددجو: {{ $person->person_code ?: '-' }}</span>
                            <span class="mt-1 block text-xs text-slate-500">کد ملی: {{ $person->national_id ?: '-' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div
                x-show="qrScannerOpen"
                x-cloak
                x-transition.opacity.duration.150ms
                class="fixed inset-0 z-[90] flex items-end justify-center bg-slate-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-3"
                role="dialog"
                aria-modal="true"
                style="display: none;"
            >
                <div
                    @click.outside="closeScanner()"
                    class="flex max-h-[calc(100svh-1rem)] w-full max-w-md flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white text-slate-900 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:rounded-2xl sm:shadow-xl sm:shadow-slate-900/15"
                    dir="rtl"
                >
                    <div class="flex items-center justify-between border-b border-slate-200/70 px-3 py-3">
                        <div class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                            <span class="inline-flex h-2 w-2 rounded-full"
                                  :class="{
                                    'bg-emerald-500': ['ready', 'scanning'].includes(status),
                                    'bg-amber-500': status === 'paused',
                                    'bg-rose-500': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                                    'bg-slate-300': status === 'initializing',
                                  }"></span>
                            <span x-text="statusLabel()"></span>
                        </div>
                        <button type="button" @click="closeScanner()" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200" aria-label="بستن">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="relative min-h-[320px] flex-1 bg-slate-950 sm:aspect-square sm:flex-none">
                        <div wire:ignore x-ref="scanner" id="beneficiary-case-file-scanner" class="qr-scanner-reader h-full w-full"></div>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="aspect-square w-[min(70%,300px)] rounded-2xl border border-white/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.24)]"></div>
                        </div>
                        <div
                            x-show="openingScanner || startingCamera || (!cameraActive && ['ready', 'camera_denied', 'scan_error', 'unsupported'].includes(status))"
                            x-transition.opacity.duration.150ms
                            class="absolute inset-0 flex items-center justify-center bg-white/92 px-5 text-center backdrop-blur-sm"
                            style="display: none;"
                        >
                            <div class="w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/10">
                                <div
                                    x-show="openingScanner || startingCamera || status === 'initializing'"
                                    class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-indigo-500"
                                    style="display: none;"
                                    aria-hidden="true"
                                ></div>
                                <div
                                    x-show="['camera_denied', 'scan_error', 'unsupported'].includes(status)"
                                    class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-500"
                                    style="display: none;"
                                    aria-hidden="true"
                                >
                                    <i class="bi bi-camera-video-off text-lg"></i>
                                </div>
                                <div
                                    x-show="status === 'ready' && !startingCamera"
                                    class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600"
                                    style="display: none;"
                                    aria-hidden="true"
                                >
                                    <i class="bi bi-camera-video text-lg"></i>
                                </div>

                                <p class="text-sm font-extrabold text-slate-800">
                                    <span x-show="openingScanner || startingCamera || status === 'initializing'">در حال فعال‌سازی دوربین</span>
                                    <span x-show="status === 'ready' && !startingCamera">دوربین آماده شروع است</span>
                                    <span x-show="status === 'camera_denied'">دسترسی به دوربین انجام نشد</span>
                                    <span x-show="status === 'scan_error'">اسکن انجام نشد</span>
                                    <span x-show="status === 'unsupported'">دوربین پشتیبانی نمی‌شود</span>
                                </p>
                                <p class="mt-2 text-xs leading-5 text-slate-500" x-text="message"></p>

                                <div class="mt-4 grid gap-2" x-show="!openingScanner && !startingCamera && status !== 'unsupported'" style="display: none;">
                                    <button
                                        type="button"
                                        @click="status === 'scan_error' && cameraActive ? resumeScan() : startCamera()"
                                        class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-indigo-700 bg-indigo-600 px-3 text-xs font-black text-white transition hover:border-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/20"
                                    >
                                        <i class="bi bi-camera-video"></i>
                                        <span x-show="status === 'ready'">شروع اسکن</span>
                                        <span x-show="status === 'scan_error'">اسکن دوباره</span>
                                        <span x-show="!['ready', 'scan_error'].includes(status)">تلاش دوباره</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 px-3 py-3">
                        <div
                            x-show="status === 'scan_error'"
                            x-transition.opacity.duration.150ms
                            class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-rose-700"
                            style="display: none;"
                        >
                            <div class="flex items-start gap-2">
                                <i class="bi bi-exclamation-triangle mt-0.5 shrink-0 text-sm"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-extrabold">QR قابل ثبت نیست</p>
                                    <p class="mt-1 text-[11px] leading-5 text-rose-600" x-text="message"></p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="resumeScan()"
                                class="mt-2 inline-flex min-h-9 w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-white px-3 text-xs font-black text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/15"
                            >
                                <i class="bi bi-qr-code-scan"></i>
                                اسکن دوباره
                            </button>
                        </div>
                        <p x-show="status !== 'scan_error'" class="text-xs leading-5 text-slate-500" x-text="message"></p>
                        <div class="grid gap-2">
                            <button
                                type="button"
                                @click="status === 'scan_error' && cameraActive ? resumeScan() : startCamera()"
                                :disabled="startingCamera"
                                class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-indigo-700 bg-indigo-600 px-3 text-xs font-black text-white transition hover:border-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-wait disabled:opacity-60"
                            >
                                <i class="bi bi-arrow-clockwise"></i>
                                <span>شروع / تلاش دوباره</span>
                            </button>

                            <button
                                type="button"
                                @click="closeScanner()"
                                class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-600 transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            >
                                <i class="bi bi-x-lg"></i>
                                <span>بستن</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
                </div>
    </section>

    @if(! $selectedPerson)
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                <i class="bi bi-folder2-open text-2xl"></i>
            </div>
            <h2 class="mt-4 text-base font-black text-slate-800">برای مشاهده پرونده، یک مددجو را انتخاب کنید</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                پس از انتخاب، خلاصه هویتی، خدمات دریافتی، فعالیت‌ها و ارزش‌های مالی ثبت‌شده در سیستم نمایش داده می‌شود.
            </p>
        </section>
    @else
        <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">{{ $selectedPerson->full_name ?: trim($selectedPerson->first_name.' '.$selectedPerson->last_name) }}</h2>
                            <p class="mt-1 text-sm text-slate-500">کد مددجو: {{ $selectedPerson->person_code ?: '-' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="exportToExcel"
                                wire:loading.attr="disabled"
                                wire:target="exportToExcel"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <i wire:loading.remove wire:target="exportToExcel" class="bi bi-file-earmark-excel"></i>
                                <i wire:loading wire:target="exportToExcel" class="bi bi-arrow-repeat animate-spin"></i>
                                <span wire:loading.remove wire:target="exportToExcel">خروجی اکسل آخرین رکوردها</span>
                                <span wire:loading wire:target="exportToExcel">در حال آماده‌سازی…</span>
                            </button>
                            <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                                {{ $selectedPerson->created_at ? 'عضویت از '.$this->formatDate($selectedPerson->created_at) : 'تاریخ عضویت ثبت نشده' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">کد ملی</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->national_id ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">سن</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->age ? $selectedPerson->age.' سال' : '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">سرپرست</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->guardian?->full_name ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">مددکار</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->guardian?->socialWorker ? trim($selectedPerson->guardian->socialWorker->first_name.' '.$selectedPerson->guardian->socialWorker->last_name) : '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900">خط زمانی پرونده</h2>
                            <p class="mt-1 text-sm text-slate-500">آخرین رکوردهای خدمات تحویل‌شده، حضورهای ثبت‌شده و رکوردهای دستی</p>
                        </div>
                        <span class="text-xs font-bold text-slate-400">{{ number_format($timeline->count()) }} رخداد پرونده</span>
                    </div>

                    @if($timeline->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                            برای این مددجو هنوز خدمت یا فعالیتی در دفتر فعلی ثبت نشده است.
                        </div>
                    @else
                        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-xs font-black text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 text-right">تاریخ</th>
                                            <th class="px-4 py-3 text-right">نوع</th>
                                            <th class="px-4 py-3 text-right">عنوان</th>
                                            <th class="px-4 py-3 text-right">مقدار/روش</th>
                                            <th class="px-4 py-3 text-right">ارزش/فاکتور</th>
                                            <th class="px-4 py-3 text-center">عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($timeline as $row)
                                            @if($row['type'] === 'service-group')
                                                <tr class="bg-emerald-50/60">
                                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $this->formatDate($row['date']) }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $row['badge'] }}</span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <p class="font-black text-slate-900">{{ $row['title'] }}</p>
                                                        <p class="mt-1 text-xs text-slate-500">{{ $row['subtitle'] }}</p>
                                                        @if(! empty($row['details']))
                                                            <div class="mt-2">
                                                                <x-details-popover :details="$row['details']" popover-title="جزئیات خدمت" />
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['quantity'] }}</td>
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['value'] }}</td>
                                                    <td class="px-4 py-3 text-center text-xs font-bold text-emerald-700">{{ number_format($row['children']->count()) }} دسته</td>
                                                </tr>
                                                @foreach($row['children'] as $child)
                                                    <tr class="bg-emerald-50/20">
                                                        <td class="px-4 py-3"></td>
                                                        <td class="px-4 py-3 text-xs font-bold text-emerald-700">جزئیات</td>
                                                        <td class="px-4 py-3 pr-8">
                                                            <p class="font-bold text-slate-800">{{ $child['category'] }}</p>
                                                            @if(! empty($child['details']))
                                                                <div class="mt-2">
                                                                    <x-details-popover :details="$child['details']" popover-title="جزئیات تحویل" />
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $child['quantity'] }}</td>
                                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $child['value'] }}</td>
                                                        <td class="px-4 py-3"></td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $this->formatDate($row['date']) }}</td>
                                                    <td class="px-4 py-3">
                                                        <span @class([
                                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                                            'bg-cyan-50 text-cyan-700' => $row['type'] === 'activity',
                                                            'bg-violet-50 text-violet-700' => $row['type'] === 'manual',
                                                        ])>{{ $row['badge'] }}</span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <p class="font-bold text-slate-800">{{ $row['title'] }}</p>
                                                        @if($row['subtitle']) <p class="mt-1 text-xs text-slate-500">{{ $row['subtitle'] }}</p> @endif
                                                        @if(! empty($row['details']))
                                                            <div class="mt-2">
                                                                <x-details-popover :details="$row['details']" :popover-title="$row['type'] === 'activity' ? 'جزئیات فعالیت' : 'جزئیات رکورد'" />
                                                            </div>
                                                        @endif
                                                        @if(($row['attachments'] ?? collect())->isNotEmpty())
                                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                                @foreach($row['attachments'] as $attachment)
                                                                    <a href="{{ $attachment->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-violet-100 bg-violet-50 px-2 py-1 text-[11px] font-bold text-violet-700 transition hover:bg-violet-100">
                                                                        <i class="bi bi-paperclip"></i><span>{{ $attachment->original_name }}</span>
                                                                        @if($attachment->size_label) <span class="font-semibold text-violet-400">({{ $attachment->size_label }})</span> @endif
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['quantity'] }}</td>
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['value'] }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        @if($row['type'] === 'manual' && ! empty($row['record_id']))
                                                            <button type="button" wire:click="startEditingCaseRecord({{ $row['record_id'] }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-violet-200 bg-violet-50 text-violet-700 transition hover:bg-violet-100 focus:outline-none focus:ring-4 focus:ring-violet-100" aria-label="ویرایش رکورد" title="ویرایش رکورد"><i class="bi bi-pencil-square text-sm"></i></button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-indigo-200 bg-white p-4 shadow-sm" aria-labelledby="ai-case-assistant-title">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold text-indigo-500">دستیار هوشمند</p>
                            <h2 id="ai-case-assistant-title" class="mt-1 text-base font-black text-slate-900">تحلیل پرونده</h2>
                        </div>
                        <button
                            type="button"
                            wire:click="generateAiCaseAnalysis"
                            wire:loading.attr="disabled"
                            wire:target="generateAiCaseAnalysis"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3 text-xs font-black text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-wait disabled:opacity-60"
                        >
                            <i wire:loading.remove wire:target="generateAiCaseAnalysis" class="bi bi-stars"></i>
                            <i wire:loading wire:target="generateAiCaseAnalysis" class="bi bi-arrow-repeat animate-spin"></i>
                            <span wire:loading.remove wire:target="generateAiCaseAnalysis">{{ $aiCaseSummary ? 'تحلیل دوباره' : 'ایجاد تحلیل' }}</span>
                            <span wire:loading wire:target="generateAiCaseAnalysis">در حال تحلیل</span>
                        </button>
                    </div>

                    @if($aiAssistantError !== '')
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-xs font-bold leading-6 text-rose-700" role="alert">
                            {{ $aiAssistantError }}
                        </div>
                    @endif

                    @if($aiCaseSummary)
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-black text-slate-800">خلاصه پیشنهادی</h3>
                                @if($aiGeneratedAt)
                                    <span class="text-[10px] font-semibold text-slate-400">{{ $aiGeneratedAt }}</span>
                                @endif
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $aiCaseSummary }}</p>
                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <h3 class="text-sm font-black text-slate-800">یادآوری‌های پیشنهادی</h3>

                            @if(session()->has('ai-reminder-success'))
                                <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
                                    {{ session('ai-reminder-success') }}
                                </div>
                            @endif

                            @if(count($aiReminderSuggestions) > 0)
                                <div class="mt-3 space-y-2">
                                    @foreach($aiReminderSuggestions as $index => $suggestion)
                                        @php
                                            $categoryLabel = match($suggestion['category']) {
                                                'today_tasks' => 'کارهای امروز',
                                                'pending_approvals' => 'تاییدهای در انتظار',
                                                'contract_deadlines' => 'موعدها',
                                                'required_reports' => 'گزارش‌های ضروری',
                                                default => 'پیگیری',
                                            };
                                        @endphp
                                        <label wire:key="ai-reminder-{{ $index }}-{{ md5($suggestion['title']) }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 transition hover:border-indigo-200 hover:bg-indigo-50/50">
                                            <input
                                                type="checkbox"
                                                wire:model="aiReminderSuggestions.{{ $index }}.selected"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            >
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-xs font-bold leading-5 text-slate-700">{{ $suggestion['title'] }}</span>
                                                <span class="mt-1 block text-[10px] font-semibold text-indigo-600">{{ $categoryLabel }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                @error('aiReminderSuggestions')
                                    <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                @enderror

                                <button
                                    type="button"
                                    wire:click="saveSelectedAiReminders"
                                    wire:loading.attr="disabled"
                                    wire:target="saveSelectedAiReminders"
                                    class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-xs font-black text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-wait disabled:opacity-60"
                                >
                                    <i class="bi bi-check2-square"></i>
                                    <span>ذخیره موارد انتخاب‌شده</span>
                                </button>
                            @else
                                <p class="mt-2 text-xs leading-6 text-slate-500">پیگیری مشخصی از سوابق فعلی پیشنهاد نشد.</p>
                            @endif
                        </div>

                        <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] leading-5 text-amber-700">
                            خروجی هوش مصنوعی پیشنهادی است و پیش از استفاده باید بررسی شود.
                        </p>
                    @endif
                </section>

                <section
                    class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm"
                    aria-labelledby="ai-follow-up-message-title"
                    x-data="{
                        copied: false,
                        async copyDraft() {
                            const text = this.$refs.draft?.value || '';

                            if (!text) {
                                return;
                            }

                            try {
                                await navigator.clipboard.writeText(text);
                            } catch (error) {
                                this.$refs.draft.focus();
                                this.$refs.draft.select();
                                document.execCommand('copy');
                            }

                            this.copied = true;
                            window.setTimeout(() => this.copied = false, 1800);
                        }
                    }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold text-emerald-600">دستیار پیام</p>
                            <h2 id="ai-follow-up-message-title" class="mt-1 text-base font-black text-slate-900">پیش‌نویس پیگیری</h2>
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700" aria-hidden="true">
                            <i class="bi bi-chat-square-text"></i>
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div>
                            <label for="follow-up-recipient" class="mb-1.5 block text-xs font-bold text-slate-700">گیرنده</label>
                            <select
                                id="follow-up-recipient"
                                wire:model.live="followUpRecipient"
                                class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2.5 text-xs font-semibold text-slate-700 focus:border-emerald-300 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="beneficiary">مددجو</option>
                                <option value="guardian">سرپرست</option>
                                <option value="social_worker">مددکار اجتماعی</option>
                                <option value="sponsor">حامی</option>
                                <option value="other">سایر</option>
                            </select>
                            @error('followUpRecipient')
                                <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="follow-up-purpose" class="mb-1.5 block text-xs font-bold text-slate-700">هدف</label>
                            <select
                                id="follow-up-purpose"
                                wire:model.live="followUpPurpose"
                                class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2.5 text-xs font-semibold text-slate-700 focus:border-emerald-300 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="case_follow_up">پیگیری پرونده</option>
                                <option value="appointment_reminder">یادآوری مراجعه</option>
                                <option value="document_request">درخواست مدارک</option>
                                <option value="service_notification">اطلاع‌رسانی خدمت</option>
                                <option value="payment_reminder">یادآوری پرداخت</option>
                                <option value="custom">موضوع دیگر</option>
                            </select>
                            @error('followUpPurpose')
                                <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="follow-up-channel" class="mb-1.5 block text-xs font-bold text-slate-700">کانال</label>
                            <select
                                id="follow-up-channel"
                                wire:model.live="followUpChannel"
                                class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2.5 text-xs font-semibold text-slate-700 focus:border-emerald-300 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="sms">پیامک</option>
                                <option value="whatsapp">واتساپ</option>
                            </select>
                            @error('followUpChannel')
                                <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="follow-up-tone" class="mb-1.5 block text-xs font-bold text-slate-700">لحن</label>
                            <select
                                id="follow-up-tone"
                                wire:model.live="followUpTone"
                                class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2.5 text-xs font-semibold text-slate-700 focus:border-emerald-300 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="respectful">محترمانه</option>
                                <option value="warm">صمیمی و محترمانه</option>
                                <option value="formal">رسمی</option>
                            </select>
                            @error('followUpTone')
                                <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="follow-up-details" class="mb-1.5 block text-xs font-bold text-slate-700">جزئیات لازم</label>
                        <textarea
                            id="follow-up-details"
                            wire:model.live.debounce.400ms="followUpDetails"
                            rows="4"
                            maxlength="1200"
                            class="w-full resize-y rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-700 placeholder:text-slate-400 focus:border-emerald-300 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            placeholder="برای نمونه: لطفا یادآوری شود مدارک تحصیلی تا پایان هفته تحویل داده شود."
                        ></textarea>
                        @error('followUpDetails')
                            <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="button"
                        wire:click="generateFollowUpMessage"
                        wire:loading.attr="disabled"
                        wire:target="generateFollowUpMessage"
                        class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 text-xs font-black text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-wait disabled:opacity-60"
                    >
                        <i wire:loading.remove wire:target="generateFollowUpMessage" class="bi bi-stars"></i>
                        <i wire:loading wire:target="generateFollowUpMessage" class="bi bi-arrow-repeat animate-spin"></i>
                        <span wire:loading.remove wire:target="generateFollowUpMessage">{{ $followUpDraft !== '' ? 'ایجاد دوباره' : 'ایجاد پیش‌نویس' }}</span>
                        <span wire:loading wire:target="generateFollowUpMessage">در حال نگارش</span>
                    </button>

                    @if($followUpError !== '')
                        <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs font-bold leading-6 text-rose-700" role="alert">
                            {{ $followUpError }}
                        </div>
                    @endif

                    @if($followUpDraft !== '')
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between gap-3">
                                <label for="follow-up-draft" class="text-xs font-black text-slate-800">متن قابل ویرایش</label>
                                @if($followUpGeneratedAt)
                                    <span class="text-[10px] font-semibold text-slate-400">{{ $followUpGeneratedAt }}</span>
                                @endif
                            </div>

                            <textarea
                                id="follow-up-draft"
                                x-ref="draft"
                                wire:model.live.debounce.500ms="followUpDraft"
                                rows="6"
                                maxlength="900"
                                class="mt-2 w-full resize-y rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm leading-7 text-slate-700 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            ></textarea>

                            @if($followUpReviewNote !== '')
                                <p class="mt-2 text-[11px] font-semibold leading-5 text-amber-700">{{ $followUpReviewNote }}</p>
                            @endif

                            <button
                                type="button"
                                x-on:click="copyDraft()"
                                class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-black text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <i class="bi" x-bind:class="copied ? 'bi-check2' : 'bi-copy'"></i>
                                <span x-text="copied ? 'کپی شد' : 'کپی متن'"></span>
                            </button>
                        </div>
                    @endif

                    <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] leading-5 text-amber-700">
                        متن فقط پیش‌نویس است؛ پیش از ارسال، اطلاعات و لحن آن را بررسی کنید.
                    </p>
                </section>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-black text-slate-900">خلاصه پرونده</h2>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2">
                            <span class="text-xs font-bold text-emerald-700">خدمات مستقیم مددجو</span>
                            <span class="text-sm font-black text-emerald-900">{{ number_format($caseFileTotals['direct_services_count']) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-teal-50 px-3 py-2">
                            <span class="text-xs font-bold text-teal-700">خدمات خانوار/سرپرست</span>
                            <span class="text-sm font-black text-teal-900">{{ number_format($caseFileTotals['family_services_count']) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-cyan-50 px-3 py-2">
                            <span class="text-xs font-bold text-cyan-700">حضور در فعالیت‌ها</span>
                            <span class="text-sm font-black text-cyan-900">{{ number_format($caseFileTotals['activity_attendances_count']) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 px-3 py-2">
                            <span class="text-xs font-bold text-amber-700">ارزش مستقیم + دستی</span>
                            <span class="text-sm font-black text-amber-900">{{ number_format($caseFileTotals['direct_services_value'] + $caseFileTotals['manual_records_amount']) }} ریال</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-orange-50 px-3 py-2">
                            <span class="text-xs font-bold text-orange-700">ارزش خانوار/سرپرست</span>
                            <span class="text-sm font-black text-orange-900">{{ number_format($caseFileTotals['family_services_value']) }} ریال</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-violet-50 px-3 py-2">
                            <span class="text-xs font-bold text-violet-700">رکوردهای دستی</span>
                            <span class="text-sm font-black text-violet-900">{{ number_format($caseFileTotals['manual_records_count']) }}</span>
                        </div>
                    </div>
                </div>

                @if($editingCaseRecordId)
                    <form wire:submit.prevent="updateCaseRecord" class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-black text-slate-900">ویرایش رکورد دستی</h2>
                                <p class="mt-1 text-xs leading-5 text-slate-500">تاریخ ذخیره‌شده به‌صورت شمسی بارگذاری شده و پس از ویرایش دوباره به فرمت پایگاه‌داده تبدیل می‌شود.</p>
                            </div>
                            <button
                                type="button"
                                wire:click="cancelEditingCaseRecord"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                            >
                                بستن
                            </button>
                        </div>

                        @if(session()->has('case-record-edit-success'))
                            <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
                                {{ session('case-record-edit-success') }}
                            </div>
                        @endif

                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="edit-case-record-type" class="mb-1 block text-xs font-bold text-slate-600">نوع رکورد</label>
                                <select
                                    id="edit-case-record-type"
                                    wire:model="editRecordType"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                >
                                    @foreach($recordTypeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editRecordType') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="edit-case-record-title" class="mb-1 block text-xs font-bold text-slate-600">عنوان</label>
                                <input
                                    id="edit-case-record-title"
                                    type="text"
                                    wire:model.defer="editRecordTitle"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                >
                                @error('editRecordTitle') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                <div>
                                    <label for="edit-case-record-date" class="mb-1 block text-xs font-bold text-slate-600">تاریخ</label>
                                    <div x-data="jalaliDateTimeField($wire.entangle('editRecordedAt').live)">
                                        <input
                                            id="edit-case-record-date"
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
                                            placeholder="انتخاب تاریخ شمسی"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                        >
                                    </div>
                                    @error('editRecordedAt') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="edit-case-record-amount" class="mb-1 block text-xs font-bold text-slate-600">مبلغ ریالی</label>
                                    <input
                                        id="edit-case-record-amount"
                                        type="number"
                                        min="0"
                                        step="1"
                                        wire:model.defer="editRecordAmount"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                    >
                                    @error('editRecordAmount') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="edit-case-record-reference" class="mb-1 block text-xs font-bold text-slate-600">شماره مرجع</label>
                                <input
                                    id="edit-case-record-reference"
                                    type="text"
                                    wire:model.defer="editRecordReferenceNumber"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                >
                                @error('editRecordReferenceNumber') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="edit-case-record-description" class="mb-1 block text-xs font-bold text-slate-600">توضیحات</label>
                                <textarea
                                    id="edit-case-record-description"
                                    rows="4"
                                    wire:model.defer="editRecordDescription"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                ></textarea>
                                @error('editRecordDescription') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="edit-case-record-attachments" class="mb-1 block text-xs font-bold text-slate-600">مدیریت پیوست‌ها</label>

                                @if(($editingCaseRecord?->attachments ?? collect())->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach($editingCaseRecord->attachments as $attachment)
                                            @php $markedForRemoval = in_array($attachment->id, $editRemovedAttachmentIds, true); @endphp
                                            <div @class([
                                                'flex items-center justify-between gap-2 rounded-xl border px-3 py-2 text-xs',
                                                'border-rose-200 bg-rose-50 text-rose-700' => $markedForRemoval,
                                                'border-slate-200 bg-white text-slate-600' => ! $markedForRemoval,
                                            ])>
                                                <div class="min-w-0">
                                                    <a href="{{ $attachment->url }}" target="_blank" rel="noopener noreferrer" class="truncate font-bold hover:underline">
                                                        {{ $attachment->original_name }}
                                                    </a>
                                                    <p class="mt-0.5 text-[11px] {{ $markedForRemoval ? 'text-rose-500' : 'text-slate-400' }}">
                                                        {{ $attachment->size_label ?: 'بدون اندازه' }}
                                                        @if($markedForRemoval)
                                                            · در انتظار حذف
                                                        @endif
                                                    </p>
                                                </div>
                                                @if($markedForRemoval)
                                                    <button
                                                        type="button"
                                                        wire:click="unmarkEditAttachmentForRemoval({{ $attachment->id }})"
                                                        class="shrink-0 rounded-lg border border-rose-200 bg-white px-2 py-1 font-bold text-rose-700 transition hover:bg-rose-100"
                                                    >
                                                        بازگردانی
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="markEditAttachmentForRemoval({{ $attachment->id }})"
                                                        class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 font-bold text-slate-600 transition hover:bg-slate-100"
                                                    >
                                                        حذف
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-3 text-xs text-slate-500">
                                        پیوست ثبت‌شده‌ای برای این رکورد وجود ندارد.
                                    </div>
                                @endif

                                @if(count($editRemovedAttachmentIds) > 0)
                                    <div class="mt-3 rounded-xl border {{ $editAttachmentRemovalConfirmed ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} px-3 py-3 text-xs">
                                        <p class="font-bold {{ $editAttachmentRemovalConfirmed ? 'text-emerald-700' : 'text-amber-700' }}">
                                            {{ $editAttachmentRemovalConfirmed ? 'حذف پیوست‌ها تایید شد.' : 'حذف پیوست‌ها هنوز تایید نهایی نشده است.' }}
                                        </p>
                                        <p class="mt-1 leading-5 {{ $editAttachmentRemovalConfirmed ? 'text-emerald-600' : 'text-amber-600' }}">
                                            {{ number_format(count($editRemovedAttachmentIds)) }} پیوست در انتظار حذف است. این حذف فقط بعد از ذخیره تغییرات اعمال می‌شود.
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            @if(! $editAttachmentRemovalConfirmed)
                                                <button
                                                    type="button"
                                                    wire:click="confirmEditAttachmentRemoval"
                                                    class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 font-bold text-amber-700 transition hover:bg-amber-100"
                                                >
                                                    تایید حذف
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="cancelEditAttachmentRemovalConfirmation"
                                                    class="rounded-lg border border-emerald-300 bg-white px-3 py-1.5 font-bold text-emerald-700 transition hover:bg-emerald-100"
                                                >
                                                    لغو تایید حذف
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @error('editRemovedAttachmentIds') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror

                                <input
                                    id="edit-case-record-attachments"
                                    type="file"
                                    multiple
                                    wire:model="editRecordAttachments"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                    class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:ml-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-700 focus:border-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-100"
                                >
                                <p class="mt-1 text-[11px] leading-5 text-slate-400">می‌توانید پیوست جدید اضافه کنید و حذف پیوست‌های فعلی را تا قبل از ذخیره نهایی، برگردانید. سقف کل پیوست‌ها ۵ فایل است.</p>
                                @error('editRecordAttachments') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                @error('editRecordAttachments.*') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror

                                @if(count($editRecordAttachments) > 0)
                                    <div class="mt-2 space-y-1">
                                        @foreach($editRecordAttachments as $index => $attachment)
                                            <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-2 py-1.5 text-xs text-slate-500">
                                                <span class="truncate">{{ $attachment->getClientOriginalName() }}</span>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <span>{{ number_format($attachment->getSize() / 1024, 1) }} KB</span>
                                                    <button
                                                        type="button"
                                                        wire:click="removeEditPendingAttachment({{ $index }})"
                                                        class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 font-bold text-slate-600 transition hover:bg-slate-100"
                                                    >
                                                        برداشتن
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="updateCaseRecord,editRecordAttachments"
                                    class="inline-flex flex-1 items-center justify-center rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-100 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    ذخیره تغییرات
                                </button>
                                <button
                                    type="button"
                                    wire:click="cancelEditingCaseRecord"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                                >
                                    انصراف
                                </button>
                            </div>
                        </div>
                    </form>
                @endif

                <form wire:submit.prevent="saveCaseRecord" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-black text-slate-900">ثبت رکورد پرونده</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">برای مواردی که در چرخه خدمات، گیت یا فعالیت ثبت نشده‌اند.</p>

                    @if(session()->has('case-record-success'))
                        <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
                            {{ session('case-record-success') }}
                        </div>
                    @endif

                    @if(session()->has('case-record-error'))
                        <div class="mt-3 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">
                            {{ session('case-record-error') }}
                        </div>
                    @endif

                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="case-record-type" class="mb-1 block text-xs font-bold text-slate-600">نوع رکورد</label>
                            <select
                                id="case-record-type"
                                wire:model="recordType"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                @foreach($recordTypeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('recordType') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="case-record-title" class="mb-1 block text-xs font-bold text-slate-600">عنوان</label>
                            <input
                                id="case-record-title"
                                type="text"
                                wire:model.defer="recordTitle"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                placeholder="مثلا فاکتور دارو یا پیگیری مددکاری"
                            >
                            @error('recordTitle') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label for="case-record-date" class="mb-1 block text-xs font-bold text-slate-600">تاریخ</label>
                                <div x-data="jalaliDateTimeField($wire.entangle('recordedAt').live)">
                                    <input
                                        id="case-record-date"
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
                                        placeholder="انتخاب تاریخ شمسی"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    >
                                </div>
                                @error('recordedAt') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="case-record-amount" class="mb-1 block text-xs font-bold text-slate-600">مبلغ ریالی</label>
                                <input
                                    id="case-record-amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    wire:model.defer="recordAmount"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    placeholder="اختیاری"
                                >
                                @error('recordAmount') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="case-record-reference" class="mb-1 block text-xs font-bold text-slate-600">شماره مرجع</label>
                            <input
                                id="case-record-reference"
                                type="text"
                                wire:model.defer="recordReferenceNumber"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                placeholder="شماره فاکتور، رسید یا ارجاع"
                            >
                            @error('recordReferenceNumber') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="case-record-description" class="mb-1 block text-xs font-bold text-slate-600">توضیحات</label>
                            <textarea
                                id="case-record-description"
                                rows="4"
                                wire:model.defer="recordDescription"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                placeholder="جزئیات رکورد پرونده..."
                            ></textarea>
                            @error('recordDescription') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="case-record-attachments" class="mb-1 block text-xs font-bold text-slate-600">پیوست‌ها</label>
                            <input
                                id="case-record-attachments"
                                type="file"
                                multiple
                                wire:model="recordAttachments"
                                accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:ml-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-indigo-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                            <p class="mt-1 text-[11px] leading-5 text-slate-400">حداکثر ۵ فایل؛ تصویر یا PDF؛ هر فایل تا ۴ مگابایت.</p>
                            @error('recordAttachments') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            @error('recordAttachments.*') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror

                            @if(count($recordAttachments) > 0)
                                <div class="mt-2 space-y-1">
                                    @foreach($recordAttachments as $attachment)
                                        <div class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2 py-1.5 text-xs text-slate-500">
                                            <span class="truncate">{{ $attachment->getClientOriginalName() }}</span>
                                            <span class="shrink-0">{{ number_format($attachment->getSize() / 1024, 1) }} KB</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="saveCaseRecord,recordAttachments"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            ثبت در پرونده
                        </button>
                    </div>
                </form>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-black text-slate-800">یادداشت پیوست‌ها</h2>
                    <p class="mt-2 text-xs leading-6 text-slate-500">
                        پیوست‌های رکورد دستی پس از ثبت، فقط از مسیر مجاز پرونده مددجو قابل مشاهده هستند. هر رکورد می‌تواند حداکثر ۵ فایل تصویر یا PDF تا سقف ۴ مگابایت برای هر فایل داشته باشد.
                    </p>
                </div>
            </aside>
        </section>
    @endif
</div>
