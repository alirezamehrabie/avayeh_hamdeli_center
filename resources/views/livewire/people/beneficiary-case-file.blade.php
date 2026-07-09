<div class="space-y-4" dir="rtl">
    @php
        $selectedPerson = $this->selectedPerson;
        $searchResults = $this->searchResults;
        $timeline = $this->timeline;
        $caseFileTotals = $this->caseFileTotals;
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
                        <span class="text-xs font-bold text-slate-400">آخرین {{ number_format($timeline->count()) }} رکورد</span>
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
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($timeline as $row)
                                            <tr>
                                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $this->formatDate($row['date']) }}</td>
                                                <td class="px-4 py-3">
                                                    <span @class([
                                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                                        'bg-emerald-50 text-emerald-700' => $row['type'] === 'service',
                                                        'bg-cyan-50 text-cyan-700' => $row['type'] === 'activity',
                                                        'bg-violet-50 text-violet-700' => $row['type'] === 'manual',
                                                    ])>
                                                        {{ $row['badge'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-bold text-slate-800">{{ $row['title'] }}</p>
                                                    @if($row['subtitle'])
                                                        <p class="mt-1 text-xs text-slate-500">{{ $row['subtitle'] }}</p>
                                                    @endif
                                                    @if(! empty($row['details']))
                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                            @foreach($row['details'] as $label => $value)
                                                                <span class="rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">{{ $label }}: {{ $value }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if(($row['attachments'] ?? collect())->isNotEmpty())
                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                            @foreach($row['attachments'] as $attachment)
                                                                <a
                                                                    href="{{ $attachment->url }}"
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    class="inline-flex items-center gap-1 rounded-lg border border-violet-100 bg-violet-50 px-2 py-1 text-[11px] font-bold text-violet-700 transition hover:bg-violet-100"
                                                                >
                                                                    <i class="bi bi-paperclip"></i>
                                                                    <span>{{ $attachment->original_name }}</span>
                                                                    @if($attachment->size_label)
                                                                        <span class="font-semibold text-violet-400">({{ $attachment->size_label }})</span>
                                                                    @endif
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['quantity'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['value'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-4">
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
                                <input
                                    id="case-record-date"
                                    type="date"
                                    wire:model.defer="recordedAt"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                >
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
