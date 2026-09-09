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
            {{-- Sticky active-gate band: orange = exit gate.
                 Compact single-row band (same shape as the Delivery Gate) so the sticky header
                 doesn't eat the small mobile viewport. --}}
            <div class="sticky top-0 z-20 border-b border-orange-100 bg-orange-50/95 px-3 py-2 backdrop-blur supports-[backdrop-filter]:bg-orange-50/80 sm:px-5 sm:py-3">
                <div class="flex items-center justify-between gap-2 sm:gap-3">
                    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-orange-600 px-2 py-0.5 text-[10px] font-black text-white sm:px-3 sm:py-1 sm:text-[11px]">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-orange-200"></span>
                            <span class="sm:hidden">خروج فعال</span>
                            <span class="hidden sm:inline">گیت خروج فعال</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black text-slate-900">{{ $selectedService->name }}</p>
                            <p class="mt-0.5 flex items-center gap-x-2 overflow-hidden whitespace-nowrap text-[11px] font-semibold text-slate-500 sm:gap-x-3">
                                <span class="hidden sm:inline" dir="ltr">{{ $selectedService->code }}</span>
                                <span class="hidden sm:inline">{{ \App\Models\Service::TYPE_OPTIONS[$selectedService->service_type] ?? $selectedService->service_type }}</span>
                                <span class="text-orange-600">
                                    <span class="sm:hidden">باقی:</span>
                                    <span class="hidden sm:inline">باقی‌مانده:</span>
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
                        title="تغییر خدمت"
                        aria-label="تغییر خدمت"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-orange-200 bg-white text-orange-700 transition hover:bg-orange-100 sm:h-auto sm:w-auto sm:gap-1.5 sm:px-3 sm:py-1.5 sm:text-xs sm:font-bold"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/>
                        </svg>
                        <span class="hidden sm:inline">تغییر خدمت</span>
                    </button>
                </div>
            </div>
        @else
            <div class="border-b border-slate-100 bg-slate-50/60 px-4 py-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900">گیت خروج</h1>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        انتخاب خدمت، اسکن QR و تأیید نهایی خروج اقلام تحویل‌شده در گیت تحویل.
                    </p>
                </div>
            </div>
        @endif

        @if(! $selectedService)
            {{-- Step 1: Service selection --}}
            <div class="px-4 py-4">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-extrabold text-slate-800">انتخاب خدمت گیت خروج</h2>

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
            @php($deliveredItems = $this->deliveredItems)
            @php($finalizedItems = $this->finalizedItems)
            @php($pendingItems = $this->pendingItems)
            {{-- Same rule as the Delivery Gate: if any category of the service has a thumbnail,
                 every checklist row reserves the same fixed-size slot, so the sheet's rows keep
                 one uniform height and shape. --}}
            @php($serviceHasThumbnails = $selectedService?->categories->contains(fn ($category) => filled($category->image_path)))

            {{-- Step 2: Scan + finalize exit --}}
            <div
                x-data="{
                    ...idCardScanner({
                        resolveScan: (payload) => $wire.resolveScannedQr(payload),
                        successSoundUrl: '/sounds/scan-card.wav',
                        enableResultBanner: false,
                        autoStart: true,
                        autoResumeAfterSuccess: false,
                    }),
                    // itemsSheetOpen + Android-back close handler (see sheetBackGuard in app.js).
                    ...sheetBackGuard('itemsSheetOpen'),
                }"
                x-init="init(); bindSheetBack()"
                x-on:id-card-scanner-resume.window="resumeFromWire(); itemsSheetOpen = false"
                x-on:exit-gate-subject-loaded.window="itemsSheetOpen = true"
                x-on:keydown.window.ctrl.enter.prevent="triggerNextScanShortcut()"
                x-on:keydown.window.meta.enter.prevent="triggerNextScanShortcut()"
                class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] {{ $lastScanResult ? 'pb-24 sm:pb-24 lg:pb-5' : '' }}"
            >
                {{-- Left: identity (kept at the top so it stays visible at a glance) + scanner --}}
                <div class="flex min-h-0 flex-col gap-4">
                    {{-- Identity card: one dense block (name+badges / codes / worker+demographics)
                         so the scanner keeps the vertical space it needs — same shape as the Delivery Gate. --}}
                    <div class="min-h-[5.5rem]">
                        {{-- Skeleton while the scan resolves on the server --}}
                        <div
                            wire:loading.flex
                            wire:target="resolveScannedQr, selectManualSubject"
                            class="hidden animate-pulse items-center gap-2.5 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"
                        >
                            <div class="h-10 w-10 shrink-0 rounded-xl bg-slate-200"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-2/3 rounded bg-slate-200"></div>
                                <div class="h-2.5 w-1/2 rounded bg-slate-100"></div>
                            </div>
                        </div>

                        @if($lastScanResult)
                            @php($isDuplicateScan = ($lastScanResult['code_key'] ?? null) === 'duplicate')
                            @php($isPerson = ($lastScanResult['type'] ?? null) === \App\Models\QrIdentity::SUBJECT_PERSON)
                            <div
                                wire:loading.remove
                                wire:target="resolveScannedQr, selectManualSubject"
                                class="rounded-2xl border p-3 shadow-sm {{ $isDuplicateScan ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-white' }}"
                            >
                                <div class="flex items-center gap-2.5">
                                    {{-- Avatar + subject-type anchor (emerald = مددجو, amber = خانوار) --}}
                                    <div class="relative shrink-0">
                                        @if($lastScanResult['avatar_url'] ?? null)
                                            <img
                                                src="{{ $lastScanResult['avatar_url'] }}"
                                                alt="{{ $lastScanResult['name'] ?? '' }}"
                                                class="h-10 w-10 rounded-xl object-cover ring-1 {{ $isPerson ? 'ring-emerald-200' : 'ring-amber-200' }}"
                                            >
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded-xl text-sm font-black {{ $isPerson ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ mb_substr(trim($lastScanResult['name'] ?? '-'), 0, 1) }}
                                            </div>
                                        @endif
                                        <span class="absolute -bottom-1 -left-1 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white {{ $isPerson ? 'bg-emerald-500' : 'bg-amber-500' }}">
                                            @if($isPerson)
                                                <svg class="h-2.5 w-2.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"/></svg>
                                            @else
                                                <svg class="h-2.5 w-2.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                            @endif
                                        </span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="min-w-0 truncate text-sm font-black text-slate-900">{{ $lastScanResult['name'] ?? '-' }}</p>
                                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isPerson ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $lastScanResult['subject_label'] ?? '-' }}
                                            </span>
                                            @if($isDuplicateScan)
                                                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">تکراری</span>
                                            @endif
                                        </div>
                                        {{-- Father directly under the full name (registration-form order);
                                             national id dropped — the person code already identifies. --}}
                                        @if(! empty($lastScanResult['identity']['father_name']))
                                            <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-400">نام پدر: {{ $lastScanResult['identity']['father_name'] }}</p>
                                        @endif
                                        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] font-semibold text-slate-500">
                                            <span>{{ $lastScanResult['code_label'] ?? 'کد' }}: <span class="font-bold text-slate-700" dir="ltr">{{ $lastScanResult['code'] ?? '-' }}</span></span>
                                            <span class="text-slate-300">·</span>
                                            <span class="inline-flex min-w-0 items-center gap-1 font-bold text-sky-700">
                                                <svg class="h-3 w-3 shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"/></svg>
                                                مددکار: <span class="min-w-0 truncate">{{ $lastScanResult['social_worker'] ?: '-' }}</span>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Demographics under a hairline; the father row moved up with the name. --}}
                                @php($demographics = collect($lastScanResult['details'] ?? [])->reject(fn ($detail) => ($detail['label'] ?? '') === 'نام پدر'))
                                @if($demographics->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-slate-100 pt-2 text-[11px] font-bold">
                                        @foreach($demographics as $detail)
                                            <span class="text-slate-500">{{ $detail['label'] }}: <span class="text-slate-700">{{ $detail['value'] }}</span></span>
                                            @if(! $loop->last)
                                                <span class="text-slate-300">·</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                @if(! empty($lastScanResult['proxy_recipient']['label']))
                                    <div class="mt-2 flex items-center justify-between gap-2 rounded-xl border border-amber-300 bg-amber-50 px-2.5 py-1.5">
                                        <span class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-amber-600">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                            تحویل به غیر از مددجو
                                        </span>
                                        <span class="min-w-0 truncate text-xs font-black text-amber-700">{{ $lastScanResult['proxy_recipient']['label'] }}</span>
                                    </div>
                                @endif

                                @if(! empty($lastScanResult['extra_fields']))
                                    <div class="mt-2 flex flex-wrap gap-1.5 border-t border-slate-100 pt-2">
                                        @foreach($lastScanResult['extra_fields'] as $extra)
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold">
                                                <span class="text-indigo-400">{{ $extra['label'] }}:</span>
                                                <span class="text-indigo-700">{{ $extra['value'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <div
                                wire:loading.remove
                                wire:target="resolveScannedQr, selectManualSubject"
                                class="flex h-[5.5rem] items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 text-center"
                            >
                                <p class="text-xs font-semibold text-slate-400">پس از اسکن، اطلاعات هویتی فرد اینجا نمایش داده می‌شود.</p>
                            </div>
                        @endif
                    </div>

                    <div class="relative h-[clamp(240px,38svh,380px)] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <div
                            wire:ignore
                            x-ref="scanner"
                            id="exit-gate-scanner-reader"
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

                    {{-- Next scan is the operator's most-used action, so it takes the leading slot
                         and the solid primary treatment; re-arming the camera is secondary. --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            wire:click="resumeScanning"
                            title="اسکن نفر بعدی (Ctrl + Enter)"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98]"
                            :class="nextScanShortcutActive ? 'ring-2 ring-emerald-300 ring-offset-1' : ''"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/></svg>
                            <span>اسکن نفر بعدی</span>
                            <kbd class="hidden rounded border border-white/30 bg-white/20 px-1.5 py-0.5 text-[10px] font-semibold text-white sm:inline-block">Ctrl + Enter</kbd>
                        </button>
                        <button
                            type="button"
                            @click="startCamera()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h1.6l1.1-1.6h4.6l1.1 1.6H19a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3.2"/></svg>
                            فعال‌سازی دوربین
                        </button>
                    </div>

                    {{-- Scan feedback (success / duplicate / error) stays a prominent banner; the standing
                         camera guidance is a quiet caption so it doesn't compete with the scanner on mobile. --}}
                    @if(in_array($scanStatus, ['paused', 'scan_error'], true))
                        <div class="rounded-2xl border px-4 py-3 text-sm font-semibold
                            @class([
                                'border-amber-200 bg-amber-50 text-amber-700' => ($lastScanResult['code_key'] ?? null) === 'duplicate',
                                'border-emerald-200 bg-emerald-50 text-emerald-700' => ($lastScanResult['code_key'] ?? null) !== 'duplicate',
                                'border-rose-200 bg-rose-50 text-rose-700' => $scanStatus === 'scan_error',
                            ])">
                            {{ $scanMessage }}
                        </div>
                    @else
                        <p class="flex items-center justify-center gap-1.5 px-1 text-center text-xs font-medium leading-5 text-slate-400">
                            @if($scanStatus === 'scanning')
                                <span class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-emerald-400"></span>
                            @else
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V6a2 2 0 012-2h2M16 4h2a2 2 0 012 2v2M20 16v2a2 2 0 01-2 2h-2M8 20H6a2 2 0 01-2-2v-2M4 12h16"/>
                                </svg>
                            @endif
                            <span>{{ $scanMessage }}</span>
                        </p>
                    @endif

                    {{-- Manual fallback: when the camera fails or a QR is damaged --}}
                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <button
                            type="button"
                            wire:click="toggleManualSearch"
                            class="flex w-full items-center justify-between gap-2 px-4 py-3 text-sm font-bold text-slate-700"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-3.5-3.5"/></svg>
                                جستجوی دستی مددجو
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

                {{-- Right: delivered items to verify + finalize exit.
                     Selection lives entirely on the client so ticks are instant; the checked ids are
                     snapshotted into the confirmation modal's payload and handed to finalizeExit in
                     one request. The key is tied to the scanned subject so a new scan reseeds it. --}}
                <div
                    class="flex min-h-0 flex-col gap-3"
                    x-data="{
                        selected: [],
                        toggle(id) {
                            this.selected.includes(id)
                                ? this.selected = this.selected.filter(i => i !== id)
                                : this.selected.push(id);
                        },
                        selectAll(ids) { this.selected = ids; },
                        deselectAll() { this.selected = []; },
                    }"
                    x-on:exit-gate-finalized.window="selected = []"
                    wire:key="exit-column-{{ $scannedPersonId ?? 0 }}-{{ $scannedGuardianId ?? 0 }}"
                >
                    {{-- Mobile-only backdrop: tapping it parks the sheet off-screen again. --}}
                    <div
                        x-cloak
                        x-show="itemsSheetOpen"
                        @click="itemsSheetOpen = false"
                        @touchmove.prevent
                        x-transition:enter="transition-opacity ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition-opacity ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
                    ></div>

                    {{-- The exit panel: a plain column on desktop, a bottom sheet on mobile.
                         translate-y-full parks it below the viewport while closed; the lg:* classes
                         reset every sheet property so the desktop grid layout stays untouched. --}}
                    <div
                        x-cloak
                        :class="itemsSheetOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
                        class="fixed inset-x-0 bottom-0 z-40 flex max-h-[85svh] min-h-0 flex-col gap-3 overflow-y-auto overscroll-contain rounded-t-3xl border-t border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-out lg:static lg:z-auto lg:max-h-none lg:translate-y-0 lg:overflow-visible lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none"
                    >
                        {{-- Fixed sheet top (mobile): grabber + compact identity box. Sticky inside
                             the sheet's scroll area, so it stays pinned while the items scroll under it. --}}
                        <div class="sticky top-0 z-10 bg-white px-4 pb-2 pt-3 lg:hidden">
                            <div class="mx-auto h-1.5 w-12 rounded-full bg-slate-200"></div>

                            @if($lastScanResult)
                                @php($identity = $lastScanResult['identity'] ?? null)
                                @php($isPersonSubject = ($lastScanResult['type'] ?? null) === \App\Models\QrIdentity::SUBJECT_PERSON)
                                <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 shadow-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="flex min-w-0 flex-1 items-center gap-2.5">
                                            @if($lastScanResult['avatar_url'] ?? null)
                                                <img src="{{ $lastScanResult['avatar_url'] }}" alt="" class="h-9 w-9 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                                            @else
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base font-black {{ $isPersonSubject ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ mb_substr(trim($lastScanResult['name'] ?? '-'), 0, 1) }}
                                                </span>
                                            @endif
                                            <span class="flex min-w-0 flex-col">
                                                <span class="flex min-w-0 items-baseline gap-1.5">
                                                    <span class="truncate text-sm font-black text-slate-900">{{ $identity['name'] ?? $lastScanResult['name'] ?? '-' }}</span>
                                                    @if(! empty($identity['father_name']))
                                                        <span class="shrink-0 text-[10px] font-semibold text-slate-400">( پدر: {{ $identity['father_name'] }} )</span>
                                                    @endif
                                                </span>
                                                <span class="mt-0.5 flex min-w-0 items-center gap-1.5 text-[11px] font-bold text-slate-500">
                                                    <span class="shrink-0">{{ $identity['code_label'] ?? 'کد' }}: <span class="text-slate-700" dir="ltr">{{ $identity['code'] ?? '-' }}</span></span>
                                                    <span class="shrink-0 text-slate-300">·</span>
                                                    <span class="min-w-0 truncate text-sky-700">مددکار: {{ $identity['worker'] ?: '-' }}</span>
                                                </span>
                                            </span>
                                        </span>
                                        <button
                                            type="button"
                                            @click="itemsSheetOpen = false"
                                            aria-label="بستن"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                                        </button>
                                    </div>

                                    {{-- Proxy delivery must stay in view while confirming the exit:
                                         these items were handed to somebody else, not the scanned subject. --}}
                                    @if(! empty($lastScanResult['proxy_recipient']['label']))
                                        <div class="mt-2 flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-2 py-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                            <span class="shrink-0 text-[10px] font-black text-amber-600">تحویل به غیر از مددجو:</span>
                                            <span class="min-w-0 truncate text-[11px] font-bold text-amber-700">{{ $lastScanResult['proxy_recipient']['label'] }}</span>
                                        </div>
                                    @endif

                                    @if(! empty($identity['chips']))
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 border-t border-slate-200/70 pt-2">
                                            @foreach($identity['chips'] as $chip)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold ring-1 ring-slate-200">
                                                    <span class="text-slate-400">{{ $chip['label'] }}:</span>
                                                    <span class="text-slate-700">{{ $chip['value'] }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <span class="text-sm font-extrabold text-slate-800">اقلام تحویل‌شده برای تأیید خروج</span>
                                    <button
                                        type="button"
                                        @click="itemsSheetOpen = false"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                                        بستن
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if(! $lastScanResult)
                            {{-- Desktop header --}}
                            <div class="flex items-center justify-between gap-2 max-lg:hidden">
                                <h2 class="text-sm font-extrabold text-slate-800">اقلام تحویل‌شده برای تأیید خروج</h2>
                            </div>

                            <div class="mx-4 flex flex-1 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center lg:mx-0">
                                <p class="text-sm font-bold text-slate-600">برای مشاهده اقلام تحویل‌شده، ابتدا QR فرد را اسکن کنید.</p>
                            </div>
                        @else
                            {{-- Toolbar: select-all / clear + selected count. On desktop it keeps the
                                 classic header row (title + controls); on mobile the identity box above
                                 already carries the context, so only the controls remain. --}}
                            @if($deliveredItems->isNotEmpty())
                                <div class="mx-4 flex flex-wrap items-center justify-between gap-x-3 gap-y-2 lg:mx-0">
                                    <h2 class="text-sm font-extrabold text-slate-800 max-lg:hidden">اقلام تحویل‌شده برای تأیید خروج</h2>
                                    <div class="flex flex-1 items-center justify-end gap-2 max-lg:w-full">
                                        <template x-if="selected.length === 0">
                                            <button
                                                type="button"
                                                x-on:click="selectAll(@js($deliveredItems->pluck('id')->values()->all()))"
                                                class="whitespace-nowrap rounded-full bg-slate-100 px-3.5 py-1.5 text-[11px] font-bold text-slate-600 transition hover:bg-emerald-100 hover:text-emerald-700"
                                            >
                                                انتخاب همه ({{ $deliveredItems->count() }})
                                            </button>
                                        </template>
                                        <template x-if="selected.length > 0">
                                            <button
                                                type="button"
                                                x-on:click="deselectAll()"
                                                class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-amber-50 px-3.5 py-1.5 text-[11px] font-bold text-amber-700 transition hover:bg-slate-100 hover:text-slate-600"
                                            >
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                                                لغو انتخاب همه
                                            </button>
                                        </template>
                                        <span class="shrink-0 whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700">
                                            <span x-text="selected.length"></span>/{{ $deliveredItems->count() }} انتخاب شد
                                        </span>
                                    </div>
                                </div>
                            @elseif($finalizedItems->isNotEmpty())
                                <div class="flex items-center justify-between gap-2 max-lg:hidden">
                                    <h2 class="text-sm font-extrabold text-slate-800">اقلام تحویل‌شده برای تأیید خروج</h2>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold text-indigo-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        خروج نهایی‌شده
                                    </span>
                                </div>
                            @endif

                            {{-- Undelivered Entry Gate authorizations: they are not finalizable, but they must
                                 not slip past the exit unnoticed. Surface them and hand the operator off to the
                                 Delivery Gate for the same subject, or point at the Entry Gate for revocation. --}}
                            @if($pendingItems->isNotEmpty())
                                @php($handoffSubject = ($scannedSubjectType === \App\Models\QrIdentity::SUBJECT_GUARDIAN ? 'guardian:'.$scannedGuardianId : 'person:'.$scannedPersonId))
                                {{-- Only an operator who holds the Delivery Gate permission may confirm items
                                     there themselves; everyone else must alert the Delivery Gate staff verbally
                                     so that gate confirms the item under its own account. --}}
                                @php($canHandoffToDelivery = auth()->user()->can('access-distribution-delivery-gate'))
                                <div class="mx-4 rounded-2xl border border-rose-300 bg-rose-50 p-4 lg:mx-0">
                                    <p class="inline-flex items-center gap-1.5 text-sm font-bold text-rose-700">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86l-8 13.9A2 2 0 004 21h16a2 2 0 001.7-3.24l-8-13.9a2 2 0 00-3.4 0z"/></svg>
                                        {{ $pendingItems->count() }} قلم مجاز از گیت ورود هنوز در گیت تحویل تأیید نشده است
                                    </p>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-rose-600">
                                        @if($canHandoffToDelivery)
                                            تکلیف این اقلام را روشن کنید: یا با دکمه «تأیید در گیت تحویل» تحویلشان را تأیید کنید، یا در صورت عدم نیاز، اپراتور گیت ورود مجوزشان را از این فرد حذف کند تا در گیت خروج به مشکل برنخورید.
                                        @else
                                            تکلیف این اقلام را روشن کنید: یا به اپراتور گیت تحویل تذکر دهید تا با حساب کاربری خودش این اقلام را در گیت تحویل تأیید کند، یا در صورت عدم نیاز، اپراتور گیت ورود مجوزشان را از این فرد حذف کند تا در گیت خروج به مشکل برنخورید.
                                        @endif
                                    </p>
                                    <div class="mt-3 space-y-2">
                                        @foreach($pendingItems as $item)
                                            @php($category = $item->serviceCategory)
                                            <div
                                                wire:key="exit-gate-pending-{{ $item->id }}"
                                                class="flex items-center justify-between gap-3 rounded-xl border border-rose-200 bg-white px-3 py-2.5"
                                            >
                                                <span class="flex min-w-0 items-center gap-2.5">
                                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 border-rose-300 bg-rose-50 text-[11px] font-black text-rose-500">؟</span>
                                                    <span class="flex min-w-0 flex-col">
                                                        <span class="truncate text-sm font-bold text-slate-800">{{ $category?->name ?? '-' }}</span>
                                                        <span class="text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category?->code ?? '-' }}</span>
                                                    </span>
                                                </span>
                                                @if($canHandoffToDelivery)
                                                    <a
                                                        href="{{ route('distribution-operator.gates.delivery', ['service' => $selectedService->id, 'subject' => $handoffSubject]) }}"
                                                        class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-rose-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-rose-700 active:scale-[0.98] sm:px-3 sm:py-1.5 sm:text-[11px]"
                                                    >
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7-7l-7 7 7 7"/></svg>
                                                        تأیید در گیت تحویل
                                                    </a>
                                                @else
                                                    <span class="shrink-0 rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-bold text-rose-700">تذکر به اپراتور گیت تحویل</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($deliveredItems->isEmpty() && $finalizedItems->isNotEmpty())
                                @if(! $exitUnlocked)
                                    {{-- Locked: manager password required before any category can be unticked --}}
                                    <div x-data="{ showUnlock: false }" class="mx-4 space-y-3 lg:mx-0">
                                        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 px-5 py-4 text-center">
                                            <p class="inline-flex items-center justify-center gap-1.5 text-sm font-bold text-indigo-700">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM16 11V7a4 4 0 00-8 0v4"/></svg>
                                                این فرد قبلاً از گیت خروج تأیید شده است.
                                            </p>
                                            <p class="mt-1 text-xs font-semibold text-indigo-500">اقلام زیر به‌صورت نهایی ثبت و قفل شده‌اند.</p>
                                            <button
                                                type="button"
                                                @click="showUnlock = ! showUnlock"
                                                class="mt-3 inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl border border-indigo-300 bg-white px-4 py-2.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 4v3m-5 7h10a2 2 0 002-2v-6a2 2 0 00-2-2H7a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                                باز کردن قفل برای اصلاح
                                            </button>
                                        </div>

                                        <div x-show="showUnlock" style="display: none;" class="rounded-2xl border border-amber-300 bg-amber-50 p-4">
                                            <p class="text-xs font-bold text-amber-800">برای باز کردن قفل، رمز عبور حساب مدیریت را وارد کنید.</p>
                                            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                                <input
                                                    type="password"
                                                    wire:model="managerPassword"
                                                    wire:keydown.enter="unlockExit"
                                                    placeholder="رمز عبور مدیریت"
                                                    class="w-full rounded-xl border border-amber-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                                >
                                                <button
                                                    type="button"
                                                    wire:click="unlockExit"
                                                    wire:loading.attr="disabled"
                                                    wire:target="unlockExit"
                                                    class="inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-amber-700 active:scale-[0.98] disabled:opacity-60"
                                                >
                                                    تأیید و باز کردن قفل
                                                </button>
                                            </div>
                                            @if($unlockError)
                                                <p class="mt-2 text-xs font-bold text-rose-600">{{ $unlockError }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="mx-4 flex items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3.5 lg:mx-0">
                                        <div>
                                            <p class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-800">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 4v3m-5 7h10a2 2 0 002-2v-6a2 2 0 00-2-2H7a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                                قفل خروج باز شد
                                            </p>
                                            <p class="mt-1 text-xs font-semibold text-amber-700">برای لغو تحویل هر دسته‌بندی، روی «لغو تحویل» همان قلم بزنید.</p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="lockExit"
                                            class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-xl border border-amber-300 bg-white px-3.5 py-2.5 text-xs font-bold text-amber-700 transition hover:bg-amber-100"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM16 11V7a4 4 0 00-8 0v4"/></svg>
                                            بستن قفل
                                        </button>
                                    </div>
                                @endif

                                <div class="mx-4 space-y-2 lg:mx-0">
                                    @foreach($finalizedItems as $item)
                                        @php($category = $item->serviceCategory)
                                        <div
                                            wire:key="exit-gate-finalized-{{ $item->id }}"
                                            class="flex w-full items-center justify-between gap-2 rounded-2xl border-2 px-3 py-2.5 text-right sm:gap-3 sm:px-4 sm:py-3 {{ $exitUnlocked ? 'border-amber-200 bg-amber-50/40' : 'border-indigo-200 bg-indigo-50/40' }}"
                                        >
                                            <span class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border {{ $exitUnlocked ? 'border-amber-600 bg-amber-600' : 'border-indigo-600 bg-indigo-600' }} text-white">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                @if($serviceHasThumbnails)
                                                    <x-category-thumbnail :category="$category" sizeClass="h-10 w-10" roundedClass="rounded-lg" />
                                                @endif
                                                <span class="flex min-w-0 flex-col">
                                                    <span class="truncate text-sm font-bold text-slate-800">{{ $category?->name ?? '-' }}</span>
                                                    <span class="truncate text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category?->code ?? '-' }}</span>
                                                </span>
                                            </span>
                                            <span class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                                                @if($category?->unitLabel)
                                                    <span class="hidden rounded-md bg-white px-2 py-0.5 text-[10px] font-bold text-slate-500 sm:inline-block">{{ $category->unitLabel }}</span>
                                                @endif
                                                @if($exitUnlocked)
                                                    <button
                                                        type="button"
                                                        wire:click="cancelFinalizedCategory({{ $item->id }})"
                                                        wire:confirm="این قلم از تحویل‌های نهایی فرد حذف و برای تحویل مجدد آزاد می‌شود. ادامه می‌دهید؟"
                                                        wire:loading.attr="disabled"
                                                        class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full bg-rose-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-rose-700 active:scale-[0.98] disabled:opacity-60 sm:px-2.5 sm:py-1 sm:text-[11px]"
                                                    >
                                                        لغو تحویل
                                                    </button>
                                                @else
                                                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-bold text-indigo-700">ثبت نهایی شد</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($exitUnlocked && $deliveredItems->isEmpty() && $finalizedItems->isEmpty())
                                <div class="mx-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-8 text-center lg:mx-0">
                                    <p class="text-sm font-bold text-emerald-700">همه اقلام نهایی‌شده این فرد لغو و آزاد شدند.</p>
                                    <p class="mt-1 text-xs font-semibold text-emerald-600">اکنون می‌توانید فرد بعدی را اسکن کنید.</p>
                                </div>
                            @elseif($deliveredItems->isEmpty())
                                <div class="mx-4 rounded-2xl border border-dashed border-amber-300 bg-amber-50 px-5 py-8 text-center lg:mx-0">
                                    <p class="text-sm font-bold text-amber-700">برای این فرد قلمی برای این خدمت در گیت تحویل ثبت نشده است.</p>
                                    <p class="mt-1 text-xs font-semibold text-amber-600">فقط اقلام تحویل‌شده در گیت تحویل قابل تأیید خروج هستند.</p>
                                </div>
                            @else
                                {{-- Interactive checklist — the whole row is the tap target; ticks are pure
                                     client state, so they land instantly on mobile. --}}
                                <div class="mx-4 space-y-2 lg:mx-0">
                                    @foreach($deliveredItems as $item)
                                        @php($category = $item->serviceCategory)
                                        <button
                                            type="button"
                                            @click="toggle({{ $item->id }})"
                                            wire:key="exit-gate-item-{{ $item->id }}"
                                            class="flex w-full items-center justify-between gap-2 rounded-2xl border-2 px-3 py-2.5 text-right transition active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-1 sm:gap-3 sm:px-4 sm:py-3"
                                            :class="selected.includes({{ $item->id }}) ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 bg-white hover:border-emerald-400 hover:bg-emerald-50/40'"
                                            :aria-pressed="selected.includes({{ $item->id }})"
                                        >
                                            <span class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                                                <span
                                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2 shadow-sm transition"
                                                    :class="selected.includes({{ $item->id }}) ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 bg-white'"
                                                >
                                                    <svg x-show="selected.includes({{ $item->id }})" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                @if($serviceHasThumbnails)
                                                    <x-category-thumbnail :category="$category" sizeClass="h-10 w-10" roundedClass="rounded-lg" />
                                                @endif
                                                <span class="flex min-w-0 flex-col">
                                                    <span class="truncate text-sm font-extrabold text-slate-900">{{ $category?->name ?? '-' }}</span>
                                                    <span class="truncate text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category?->code ?? '-' }}</span>
                                                </span>
                                            </span>
                                            <span class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                                                @if($category?->unitLabel)
                                                    <span class="hidden rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 sm:inline-block">{{ $category->unitLabel }}</span>
                                                @endif
                                                <span
                                                    class="hidden rounded-full px-3 py-1 text-[11px] font-black transition sm:block"
                                                    :class="selected.includes({{ $item->id }}) ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'"
                                                    x-text="selected.includes({{ $item->id }}) ? 'انتخاب شد' : 'تحویل شد'"
                                                >تحویل شد</span>
                                                <span
                                                    class="h-2.5 w-2.5 shrink-0 rounded-full transition sm:hidden"
                                                    :class="selected.includes({{ $item->id }}) ? 'bg-emerald-600' : 'bg-slate-300'"
                                                ></span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Fixed sheet bottom (mobile): solid bar above the home-indicator safe
                                     area carrying the finalize action; on desktop it keeps the plain
                                     page-sticky gradient treatment. The button arms itself only after the
                                     first tick, and the checked ids ride into the confirmation modal. --}}
                                <div class="sticky bottom-[env(safe-area-inset-bottom)] z-10 mt-1 border-t border-slate-100 bg-white px-4 pb-[calc(1rem_+_env(safe-area-inset-bottom))] pt-3 lg:bottom-0 lg:border-0 lg:bg-transparent lg:bg-gradient-to-t lg:from-white lg:via-white lg:to-transparent lg:px-1 lg:pb-1">
                                    <button
                                        type="button"
                                        x-bind:disabled="selected.length === 0"
                                        wire:loading.attr="disabled"
                                        wire:target="finalizeExitConfirmed"
                                        x-on:click="
                                            if (selected.length === 0) return;
                                            window.dispatchEvent(new CustomEvent('open-notification-modal', {
                                                detail: {
                                                    config: {
                                                        type: 'warning',
                                                        icon: 'warning',
                                                        title: 'تأیید خروج و ثبت نهایی تحویل',
                                                        message: 'با تأیید خروج، ' + selected.length + ' قلم انتخاب‌شده به‌صورت نهایی ثبت و قفل می‌شوند و قابل تغییر نخواهند بود.',
                                                        buttons: [
                                                            {
                                                                label: 'تأیید خروج',
                                                                action: 'event',
                                                                event: 'exit-gate-confirm-finalize',
                                                                payload: { ids: selected },
                                                                variant: 'success',
                                                            },
                                                            {
                                                                label: 'انصراف',
                                                                action: 'close',
                                                                variant: 'secondary',
                                                            },
                                                        ],
                                                    },
                                                },
                                            }))
                                        "
                                        title="تأیید خروج و ثبت نهایی"
                                        class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-emerald-600 px-4 py-3.5 text-[15px] font-black text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:opacity-100 sm:px-5 sm:text-base"
                                    >
                                        <svg wire:loading.remove wire:target="finalizeExitConfirmed" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span wire:loading.remove wire:target="finalizeExitConfirmed">
                                            <span x-text="selected.length === 0 ? 'تأیید خروج و ثبت نهایی' : 'تأیید خروج و ثبت نهایی (' + selected.length + ' قلم)'"></span>
                                        </span>
                                        <span wire:loading wire:target="finalizeExitConfirmed">در حال ثبت نهایی…</span>
                                    </button>
                                    <p x-show="selected.length > 0" style="display: none;" class="mt-1.5 text-center text-[11px] font-semibold text-slate-400">اقلام انتخاب‌شده پس از تأیید، نهایی و قفل می‌شوند.</p>
                                    <p x-show="selected.length === 0" style="display: none;" class="mt-1.5 text-center text-[11px] font-bold text-amber-600">برای فعال‌شدن دکمه، ابتدا حداقل یک قلم را انتخاب کنید.</p>
                                </div>
                            @endif

                            {{-- No checklist on screen (finalized / cancelled / empty subject): the exit
                                 action is done or blocked, so the sheet's own footer advances straight to
                                 the next person. Mobile-only; the desktop column keeps its left-side controls. --}}
                            @if($deliveredItems->isEmpty())
                                <div class="sticky bottom-[env(safe-area-inset-bottom)] z-10 mt-1 border-t border-slate-100 bg-white px-4 pb-[calc(1rem_+_env(safe-area-inset-bottom))] pt-3 lg:hidden">
                                    <button
                                        type="button"
                                        wire:click="resumeScanning"
                                        wire:loading.attr="disabled"
                                        wire:target="resumeScanning"
                                        class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-emerald-600 px-4 py-3.5 text-[15px] font-black text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99] disabled:opacity-60"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/></svg>
                                        <span wire:loading.remove wire:target="resumeScanning">اسکن نفر بعدی</span>
                                        <span wire:loading wire:target="resumeScanning">در حال آماده‌سازی…</span>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Mobile-only reopen bar: pinned to the bottom of the viewport (instead of
                         buried in the page flow under the scanner + manual search) so the exit
                         panel is always one thumb-tap away after the sheet closes. Only exists
                         while a subject is on screen. --}}
                    @if($lastScanResult)
                        <button
                            type="button"
                            x-cloak
                            x-show="!itemsSheetOpen"
                            @click="itemsSheetOpen = true"
                            class="fixed inset-x-4 bottom-[calc(1rem_+_env(safe-area-inset-bottom))] z-30 flex items-center justify-between gap-2 rounded-2xl border border-orange-200 bg-white/95 px-4 py-3 text-sm font-bold text-orange-700 shadow-lg backdrop-blur transition active:scale-[0.99] lg:hidden"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                اقلام تحویل‌شده
                            </span>
                            @if($deliveredItems->isNotEmpty())
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-black text-emerald-600">
                                    <span x-text="selected.length"></span>/{{ $deliveredItems->count() }} انتخاب شد
                                </span>
                            @elseif($pendingItems->isNotEmpty())
                                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-black text-rose-600">{{ $pendingItems->count() }} قلم تأییدنشده</span>
                            @else
                                <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-black text-indigo-600">مشاهده وضعیت خروج</span>
                            @endif
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
