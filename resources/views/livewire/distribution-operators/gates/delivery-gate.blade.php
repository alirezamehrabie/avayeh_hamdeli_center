@php
    $statusStyles = [
        'draft' => 'bg-slate-100 text-slate-600',
        'approved' => 'bg-emerald-100 text-emerald-700',
        'in_distribution' => 'bg-amber-100 text-amber-700',
        'completed' => 'bg-indigo-100 text-indigo-700',
    ];
@endphp

<div class="space-y-6" dir="rtl">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        @if($selectedService)
            {{-- Sticky active-gate band: always shows which service is locked while scanning --}}
            <div class="sticky top-0 z-20 border-b border-indigo-100 bg-indigo-50/95 px-5 py-3 backdrop-blur supports-[backdrop-filter]:bg-indigo-50/80">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-indigo-600 px-3 py-1 text-[11px] font-black text-white">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-300"></span>
                            گیت تحویل فعال
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-900">{{ $selectedService->name }}</p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-semibold text-slate-500">
                                <span dir="ltr">{{ $selectedService->code }}</span>
                                <span>{{ \App\Models\Service::TYPE_OPTIONS[$selectedService->service_type] ?? $selectedService->service_type }}</span>
                                <span class="text-indigo-600">
                                    باقی‌مانده:
                                    <span dir="ltr">{{ rtrim(rtrim(number_format((float) $selectedService->remaining_quantity, 2), '0'), '.') }}</span>
                                    /
                                    <span dir="ltr">{{ rtrim(rtrim(number_format((float) $selectedService->total_quantity, 2), '0'), '.') }}</span>
                                </span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="changeService"
                        wire:confirm="با تغییر خدمت، اسکن جاری پاک می‌شود. ادامه می‌دهید؟"
                        class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/>
                        </svg>
                        تغییر خدمت
                    </button>
                </div>
            </div>
        @else
            <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900">گیت تحویل</h1>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        انتخاب خدمت، اسکن QR و ثبت تحویل اقلام مجاز تأییدشده در گیت ورود.
                    </p>
                </div>
            </div>
        @endif

        @if(! $selectedService)
            {{-- Step 1: Service selection --}}
            <div class="px-5 py-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-extrabold text-slate-800">۱. انتخاب خدمت گیت تحویل</h2>

                    <div class="relative w-full sm:max-w-xs">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-3.5-3.5"/>
                            </svg>
                        </span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="serviceSearch"
                            placeholder="جستجوی نام، کد یا دسته‌بندی خدمت"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-9 pl-3 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        >
                        @if($this->serviceSearchActive)
                            <button
                                type="button"
                                wire:click="clearServiceSearch"
                                class="absolute inset-y-0 left-2 flex items-center rounded-md px-1 text-slate-400 transition hover:text-slate-600"
                                aria-label="پاک‌کردن جستجو"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        @endif
                    </div>
                </div>

                @if($gateServices->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                        <p class="text-sm font-bold text-slate-700">
                            {{ $this->serviceSearchActive ? 'خدمتی با این جستجو پیدا نشد.' : 'خدمتی با قابلیت تحویل از گیت یافت نشد.' }}
                        </p>
                        @if($this->serviceSearchActive)
                            <button type="button" wire:click="clearServiceSearch" class="mt-3 text-xs font-bold text-indigo-600 hover:text-indigo-700">
                                پاک‌کردن جستجو
                            </button>
                        @endif
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($gateServices as $service)
                            @php
                                $remaining = (float) $service->remaining_quantity;
                                $total = (float) $service->total_quantity;
                                $isDepleted = $total > 0 && $remaining <= 0;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectService({{ $service->id }})"
                                wire:key="gate-service-{{ $service->id }}"
                                class="group flex flex-col gap-2.5 rounded-2xl border border-slate-200 bg-white p-4 text-right transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-extrabold text-slate-800 group-hover:text-indigo-700">{{ $service->name }}</span>
                                    <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" dir="ltr">{{ $service->code }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-semibold">
                                    <span class="rounded-full px-2 py-0.5 {{ $statusStyles[$service->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ \App\Models\Service::STATUS_OPTIONS[$service->status] ?? $service->status }}
                                    </span>
                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-indigo-600">{{ $service->categories_count }} دسته‌بندی</span>
                                    <span class="rounded-full bg-slate-50 px-2 py-0.5 text-slate-500">{{ \App\Models\Service::TYPE_OPTIONS[$service->service_type] ?? $service->service_type }}</span>
                                </div>

                                <div class="flex items-center justify-between border-t border-slate-100 pt-2 text-[11px] font-semibold">
                                    <span class="text-slate-500">موجودی باقی‌مانده</span>
                                    <span class="{{ $isDepleted ? 'text-rose-600' : 'text-slate-800' }}" dir="ltr">
                                        {{ rtrim(rtrim(number_format($remaining, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($total, 2), '0'), '.') }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            {{-- Step 2: Scan + deliver --}}
            <div
                x-data="idCardScanner({
                    resolveScan: (payload) => $wire.resolveScannedQr(payload),
                    successSoundUrl: '/sounds/scan-card.wav',
                    enableResultBanner: false,
                    autoStart: true,
                    autoResumeAfterSuccess: false,
                })"
                x-init="init()"
                x-on:id-card-scanner-resume.window="resumeFromWire()"
                x-on:keydown.window.ctrl.enter.prevent="triggerNextScanShortcut()"
                x-on:keydown.window.meta.enter.prevent="triggerNextScanShortcut()"
                class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
            >
                {{-- Left: identity (kept at the top so it stays visible at a glance) + scanner --}}
                <div class="flex min-h-0 flex-col gap-4">
                    {{-- Identity card (fixed-height slot so the layout doesn't jump between scans) --}}
                    <div class="min-h-[8rem]">
                        {{-- Skeleton while the scan resolves on the server --}}
                        <div
                            wire:loading.flex
                            wire:target="resolveScannedQr, selectManualSubject"
                            class="hidden animate-pulse items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div class="h-14 w-14 shrink-0 rounded-2xl bg-slate-200"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3.5 w-2/3 rounded bg-slate-200"></div>
                                <div class="h-3 w-1/2 rounded bg-slate-100"></div>
                            </div>
                        </div>

                        @if($lastScanResult)
                            @php($isDuplicateScan = ($lastScanResult['code_key'] ?? null) === 'duplicate')
                            @php($isPerson = ($lastScanResult['type'] ?? null) === \App\Models\QrIdentity::SUBJECT_PERSON)
                            <div
                                wire:loading.remove
                                wire:target="resolveScannedQr, selectManualSubject"
                                class="rounded-2xl border p-4 shadow-sm {{ $isDuplicateScan ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-white' }}"
                            >
                                <div class="flex items-start gap-3">
                                    {{-- Avatar + subject-type anchor (emerald = مددجو, amber = خانوار) --}}
                                    <div class="relative shrink-0">
                                        @if($lastScanResult['avatar_url'] ?? null)
                                            <img
                                                src="{{ $lastScanResult['avatar_url'] }}"
                                                alt="{{ $lastScanResult['name'] ?? '' }}"
                                                class="h-14 w-14 rounded-2xl object-cover ring-2 {{ $isPerson ? 'ring-emerald-200' : 'ring-amber-200' }}"
                                            >
                                        @else
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl font-black {{ $isPerson ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ mb_substr(trim($lastScanResult['name'] ?? '-'), 0, 1) }}
                                            </div>
                                        @endif
                                        <span class="absolute -bottom-1 -left-1 flex h-6 w-6 items-center justify-center rounded-full ring-2 ring-white {{ $isPerson ? 'bg-emerald-500' : 'bg-amber-500' }}">
                                            @if($isPerson)
                                                <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"/></svg>
                                            @else
                                                <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                            @endif
                                        </span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $isPerson ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $lastScanResult['subject_label'] ?? '-' }}
                                            </span>
                                            @if($isDuplicateScan)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86l-8 13.9A2 2 0 004 21h16a2 2 0 001.7-3.24l-8-13.9a2 2 0 00-3.4 0z"/></svg>
                                                    تکراری
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 truncate text-lg font-black text-slate-900">{{ $lastScanResult['name'] ?? '-' }}</p>
                                    </div>
                                </div>

                                <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                        <dt class="font-semibold text-slate-500">{{ $lastScanResult['code_label'] ?? 'کد' }}</dt>
                                        <dd class="font-bold text-slate-800" dir="ltr">{{ $lastScanResult['code'] ?? '-' }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                        <dt class="font-semibold text-slate-500">کد ملی</dt>
                                        <dd class="font-bold text-slate-800" dir="ltr">{{ $lastScanResult['national_id'] ?? '-' }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 sm:col-span-2">
                                        <dt class="font-semibold text-slate-500">شماره تماس</dt>
                                        <dd class="font-bold text-slate-800" dir="ltr">{{ $lastScanResult['mobile'] ?? '-' }}</dd>
                                    </div>
                                </dl>

                                @if(! empty($lastScanResult['details']))
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($lastScanResult['details'] as $detail)
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold">
                                                <span class="text-slate-400">{{ $detail['label'] }}:</span>
                                                <span class="text-slate-700">{{ $detail['value'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if(! empty($lastScanResult['extra_fields']))
                                    <div class="mt-3 space-y-1.5 border-t border-slate-100 pt-3">
                                        <p class="text-[11px] font-bold text-slate-500">اطلاعات تکمیلی</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($lastScanResult['extra_fields'] as $extra)
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-semibold">
                                                    <span class="text-indigo-400">{{ $extra['label'] }}:</span>
                                                    <span class="text-indigo-700">{{ $extra['value'] }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div
                                wire:loading.remove
                                wire:target="resolveScannedQr, selectManualSubject"
                                class="flex h-[8rem] items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 text-center"
                            >
                                <p class="text-xs font-semibold text-slate-400">پس از اسکن، اطلاعات هویتی فرد اینجا نمایش داده می‌شود.</p>
                            </div>
                        @endif
                    </div>

                    <div class="relative h-[clamp(240px,38svh,380px)] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <div
                            wire:ignore
                            x-ref="scanner"
                            id="delivery-gate-scanner-reader"
                            class="qr-scanner-reader h-full w-full"
                        ></div>

                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="aspect-square w-[min(70%,320px)] max-h-[calc(100%-4rem)] rounded-2xl border-2 border-emerald-300/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.28)]"></div>
                        </div>

                        <div class="absolute bottom-3 right-3 rounded-full bg-slate-950/70 px-3 py-1.5 text-[11px] font-semibold text-white backdrop-blur">
                            کد QR را داخل قاب قرار دهید
                        </div>

                        {{-- Camera permission / error overlay: shown when access is denied or unsupported. --}}
                        <div
                            x-show="status === 'camera_denied' || status === 'unsupported'"
                            style="display: none;"
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-slate-950/90 px-5 text-center"
                        >
                            <svg class="h-9 w-9 text-rose-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
                            </svg>
                            <p class="max-w-xs text-sm font-semibold leading-6 text-white" x-text="message"></p>
                            <button
                                type="button"
                                x-show="status !== 'unsupported'"
                                @click="startCamera()"
                                class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-bold text-slate-800 transition hover:bg-slate-100"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/></svg>
                                تلاش مجدد برای دوربین
                            </button>
                            <p class="text-[11px] font-semibold text-slate-300">در صورت نبود دوربین، از «جستجوی دستی» پایین استفاده کنید</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            @click="startCamera()"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                        >
                            فعال‌سازی دوربین
                        </button>
                        <button
                            type="button"
                            wire:click="resumeScanning"
                            title="اسکن نفر بعدی (Ctrl + Enter)"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100"
                            :class="nextScanShortcutActive ? 'ring-2 ring-emerald-400 ring-offset-1 bg-emerald-100 scale-[0.98]' : ''"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/></svg>
                            <span>اسکن نفر بعدی</span>
                            <kbd class="hidden rounded border border-emerald-300 bg-white/70 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600 sm:inline-block">Ctrl + Enter</kbd>
                        </button>
                    </div>

                    <div class="rounded-2xl border px-4 py-3 text-sm font-semibold
                        @class([
                            'border-amber-200 bg-amber-50 text-amber-700' => $scanStatus === 'paused' && ($lastScanResult['code_key'] ?? null) === 'duplicate',
                            'border-emerald-200 bg-emerald-50 text-emerald-700' => $scanStatus === 'paused' && ($lastScanResult['code_key'] ?? null) !== 'duplicate',
                            'border-rose-200 bg-rose-50 text-rose-700' => $scanStatus === 'scan_error',
                            'border-slate-200 bg-slate-50 text-slate-600' => ! in_array($scanStatus, ['paused', 'scan_error'], true),
                        ])">
                        {{ $scanMessage }}
                    </div>

                    {{-- Manual fallback: when the camera fails or a QR is damaged --}}
                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <button
                            type="button"
                            wire:click="toggleManualSearch"
                            class="flex w-full items-center justify-between gap-2 px-4 py-3 text-sm font-bold text-slate-700"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-3.5-3.5"/></svg>
                                جستجوی دستی (در صورت خرابی QR یا دوربین)
                            </span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform {{ $showManualSearch ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        @if($showManualSearch)
                            <div class="border-t border-slate-100 px-4 py-3">
                                <input
                                    type="search"
                                    wire:model.live.debounce.300ms="manualSearch"
                                    placeholder="نام، کد مددجو/خانوار یا کد ملی"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                >

                                @if(strlen(trim($manualSearch)) >= 2)
                                    <div class="mt-3 space-y-2">
                                        @forelse($this->manualCandidates as $candidate)
                                            <button
                                                type="button"
                                                wire:key="manual-candidate-{{ $candidate['type'] }}-{{ $candidate['id'] }}"
                                                wire:click="selectManualSubject('{{ $candidate['type'] }}', {{ $candidate['id'] }})"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-right transition hover:border-indigo-200 hover:bg-indigo-50"
                                            >
                                                <span class="flex min-w-0 flex-col">
                                                    <span class="truncate text-sm font-bold text-slate-800">{{ $candidate['name'] }}</span>
                                                    <span class="text-[11px] font-semibold text-slate-400" dir="ltr">{{ $candidate['code'] }} · {{ $candidate['national_id'] }}</span>
                                                </span>
                                                <span class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold {{ $candidate['type'] === \App\Models\QrIdentity::SUBJECT_PERSON ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ $candidate['type'] === \App\Models\QrIdentity::SUBJECT_PERSON ? 'مددجو' : 'خانوار' }}
                                                </span>
                                            </button>
                                        @empty
                                            <p class="rounded-xl bg-slate-50 px-3 py-3 text-center text-xs font-semibold text-slate-500">موردی یافت نشد.</p>
                                        @endforelse
                                    </div>
                                @else
                                    <p class="mt-2 text-[11px] font-semibold text-slate-400">برای جستجو حداقل ۲ نویسه وارد کنید.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: authorized items to deliver --}}
                @php($authorizedItems = $this->authorizedItems)
                <div class="flex min-h-0 flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-extrabold text-slate-800">اقلام مجاز برای تحویل</h2>
                        @if($lastScanResult && $authorizedItems->isNotEmpty())
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">
                                {{ count($deliveredCategoryIds) }} از {{ $authorizedItems->count() }} تحویل شد
                            </span>
                        @endif
                    </div>

                    @if(! $lastScanResult)
                        <div class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                            <p class="text-sm font-bold text-slate-600">برای مشاهده اقلام مجاز، ابتدا QR فرد را اسکن کنید.</p>
                        </div>
                    @elseif($authorizedItems->isEmpty())
                        <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 px-5 py-8 text-center">
                            <p class="text-sm font-bold text-amber-700">برای این فرد در گیت ورود قلمی برای این خدمت ثبت نشده است.</p>
                            <p class="mt-1 text-xs font-semibold text-amber-600">فقط اقلام تأییدشده در گیت ورود قابل تحویل هستند.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($authorizedItems as $item)
                                @php($category = $item->serviceCategory)
                                @php($isFinalized = in_array($item->service_category_id, $finalizedCategoryIds, true))
                                @php($isDelivered = in_array($item->service_category_id, $deliveredCategoryIds, true))
                                <button
                                    type="button"
                                    @if(! $isFinalized) wire:click="toggleDelivered({{ $item->service_category_id }})" @endif
                                    @disabled($isFinalized)
                                    wire:key="delivery-gate-item-{{ $item->id }}"
                                    @class([
                                        'flex w-full items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-right transition',
                                        'cursor-not-allowed border-indigo-200 bg-indigo-50' => $isFinalized,
                                        'border-emerald-300 bg-emerald-50' => $isDelivered && ! $isFinalized,
                                        'border-slate-200 bg-white hover:border-emerald-200 hover:bg-slate-50' => ! $isDelivered && ! $isFinalized,
                                    ])
                                >
                                    <span class="flex items-center gap-3">
                                        <span @class([
                                            'flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition',
                                            'border-indigo-600 bg-indigo-600 text-white' => $isFinalized,
                                            'border-emerald-600 bg-emerald-600 text-white' => $isDelivered && ! $isFinalized,
                                            'border-slate-300 bg-white' => ! $isDelivered && ! $isFinalized,
                                        ])>
                                            @if($isFinalized)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            @elseif($isDelivered)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </span>
                                        <span class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800">{{ $category?->name ?? '-' }}</span>
                                            <span class="text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category?->code ?? '-' }}</span>
                                        </span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-2">
                                        @if($category?->unit)
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $category->unit }}</span>
                                        @endif
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                                            'bg-indigo-100 text-indigo-700' => $isFinalized,
                                            'bg-emerald-100 text-emerald-700' => $isDelivered && ! $isFinalized,
                                            'bg-slate-100 text-slate-500' => ! $isDelivered && ! $isFinalized,
                                        ])>
                                            {{ $isFinalized ? 'خروج نهایی شده' : ($isDelivered ? 'تحویل شد' : 'ثبت تحویل') }}
                                        </span>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Confirm + advance: deliveries are already saved per item on toggle, so this
                             closes out the current subject and jumps to the next scan in one tap. --}}
                        <div class="sticky bottom-0 mt-1 -mx-1 bg-gradient-to-t from-white via-white to-transparent px-1 pb-1 pt-3">
                            <button
                                type="button"
                                wire:click="confirmDelivery"
                                title="تأیید تحویل و اسکن نفر بعدی (Ctrl + Enter)"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-base font-black text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]"
                                :class="nextScanShortcutActive ? 'ring-2 ring-emerald-300 ring-offset-1' : ''"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>تأیید تحویل و نفر بعدی</span>
                                <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold" dir="ltr">{{ count($deliveredCategoryIds) }}/{{ $authorizedItems->count() }}</span>
                            </button>
                            <p class="mt-1.5 text-center text-[11px] font-semibold text-slate-400">اقلام علامت‌خورده ثبت شده‌اند؛ با تأیید به نفر بعدی می‌روید.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
