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
            {{-- Sticky active-gate band: green = entry gate.
                 Compact single-row band (same shape as the Delivery Gate) so the sticky header
                 doesn't eat the small mobile viewport. --}}
            <div class="sticky top-0 z-20 border-b border-emerald-100 bg-emerald-50/95 px-3 py-2 backdrop-blur supports-[backdrop-filter]:bg-emerald-50/80 sm:px-5 sm:py-3">
                <div class="flex items-center justify-between gap-2 sm:gap-3">
                    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-black text-white sm:px-3 sm:py-1 sm:text-[11px]">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-300"></span>
                            <span class="sm:hidden">ورود فعال</span>
                            <span class="hidden sm:inline">گیت ورود فعال</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black text-slate-900">{{ $selectedService->name }}</p>
                            <p class="mt-0.5 flex items-center gap-x-2 overflow-hidden whitespace-nowrap text-[11px] font-semibold text-slate-500 sm:gap-x-3">
                                <span class="hidden sm:inline" dir="ltr">{{ $selectedService->code }}</span>
                                <span class="hidden sm:inline">{{ \App\Models\Service::TYPE_OPTIONS[$selectedService->service_type] ?? $selectedService->service_type }}</span>
                                <span class="text-emerald-600">
                                    <span class="sm:hidden">باقی:</span>
                                    <span class="hidden sm:inline">باقی‌مانده:</span>
                                    <span dir="ltr">{{ rtrim(rtrim(number_format((float) $selectedService->remaining_quantity, 2), '0'), '.') }}</span>
                                    /
                                    <span dir="ltr">{{ rtrim(rtrim(number_format((float) $selectedService->total_quantity, 2), '0'), '.') }}</span>
                                </span>
                                <span class="shrink-0 text-emerald-700">
                                    <span class="sm:hidden">ورود:</span>
                                    <span class="hidden sm:inline">ورود مجاز امروز:</span>
                                    <span dir="ltr">{{ $this->authorizedToday }}</span>
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
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-200 bg-white text-emerald-700 transition hover:bg-emerald-100 sm:h-auto sm:w-auto sm:gap-1.5 sm:px-3 sm:py-1.5 sm:text-xs sm:font-bold"
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
                    <h1 class="text-xl font-black text-slate-900">گیت ورود</h1>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        انتخاب خدمت، اسکن QR و تخصیص دسته‌بندی‌های مجاز برای مرحله تحویل.
                    </p>
                </div>
            </div>
        @endif

        @if(! $selectedService)
            {{-- Step 1: Service selection --}}
            <div class="px-4 py-4">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-extrabold text-slate-800">انتخاب خدمت گیت ورود</h2>

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
            {{-- Extra display fields: defined once per service, reused at every gate.
                 Configuration surface — a collapsed strip above the working area. --}}
            <div class="border-b border-slate-100 px-4 py-2.5 sm:px-5 sm:py-3">
                <button
                    type="button"
                    wire:click="toggleFieldConfig"
                    class="flex w-full items-center justify-between gap-2 text-sm font-bold text-slate-700"
                >
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                        فیلدهای نمایش اضافه این خدمت
                        @if($entryFields->isNotEmpty())
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-600">{{ $entryFields->count() }}</span>
                        @endif
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition-transform {{ $showFieldConfig ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                @if($showFieldConfig)
                    <div class="mt-3 space-y-2">
                        <p class="text-[11px] font-semibold text-slate-400">
                            فیلدهای تعریف‌شده هنگام اسکن هر فرد پر می‌شوند و در گیت تحویل و خروج نمایش داده می‌شوند.
                        </p>

                        @foreach($entryFields as $field)
                            <div wire:key="entry-field-config-{{ $field->id }}" class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 transition sm:flex-row sm:items-center sm:gap-3" wire:loading.attr="disabled" wire:target="removeEntryField({{ $field->id }})">
                                <input
                                    type="text"
                                    wire:model.blur="entryFieldDrafts.{{ $field->id }}.title"
                                    wire:target="updatedEntryFieldDrafts"
                                    placeholder="عنوان فیلد (مثلاً: سایز کفش)"
                                    class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100 transition"
                                >
                                <select
                                    wire:model.blur="entryFieldDrafts.{{ $field->id }}.type"
                                    wire:target="updatedEntryFieldDrafts"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100 transition sm:w-40"
                                >
                                    @foreach(\App\Models\ServiceEntryField::TYPE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button
                                    type="button"
                                    wire:click="removeEntryField({{ $field->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="removeEntryField({{ $field->id }})"
                                    class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg wire:loading.remove wire:target="removeEntryField({{ $field->id }})" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m1 0v12a1 1 0 01-1 1H8a1 1 0 01-1-1V7"/></svg>
                                    <svg wire:loading wire:target="removeEntryField({{ $field->id }})" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0110 10" stroke-linecap="round"></path></svg>
                                    حذف
                                </button>
                            </div>
                        @endforeach

                        <button
                            type="button"
                            wire:click="addEntryField"
                            wire:loading.attr="disabled"
                            wire:target="addEntryField"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50 px-4 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading.remove wire:target="addEntryField" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                            <svg wire:loading wire:target="addEntryField" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0110 10" stroke-linecap="round"></path></svg>
                            افزودن فیلد
                        </button>
                    </div>
                @endif
            </div>

            {{-- Step 2: Scan + authorize --}}
            <div
                x-data="{
                    ...idCardScanner({
                        resolveScan: (payload) => $wire.resolveScannedQr(payload),
                        successSoundUrl: '/sounds/scan-card.wav',
                        enableResultBanner: false,
                        autoStart: true,
                        autoResumeAfterSuccess: false,
                    }),
                    categoriesSheetOpen: false,
                }"
                x-init="init()"
                x-on:id-card-scanner-resume.window="resumeFromWire(); categoriesSheetOpen = false"
                x-on:entry-gate-subject-loaded.window="categoriesSheetOpen = true"
                x-on:keydown.window.ctrl.enter.prevent="triggerNextScanShortcut()"
                x-on:keydown.window.meta.enter.prevent="triggerNextScanShortcut()"
                class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] {{ $lastScanResult ? 'pb-24 sm:pb-24 lg:pb-5' : '' }}"
            >
                {{-- Left: identity (kept at the top so it stays visible at a glance) + scanner --}}
                <div class="flex min-h-0 flex-col gap-4">
                    {{-- Identity card: one dense block (name+badges / codes / worker+demographics)
                         so the scanner keeps the vertical space it needs — same shape as the
                         Delivery Gate. The editable extra-field inputs live in the sheet's form. --}}
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
                            id="entry-gate-scanner-reader"
                            class="qr-scanner-reader h-full w-full"
                        ></div>

                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="aspect-square w-[min(70%,320px)] max-h-[calc(100%-4rem)] rounded-2xl border-2 border-emerald-300/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.28)]"></div>
                        </div>

                        <div class="absolute bottom-3 right-3 rounded-full bg-slate-950/70 px-3 py-1.5 text-[11px] font-semibold text-white backdrop-blur">
                            کد QR را داخل قاب قرار دهید
                        </div>

                        {{-- Camera permission / error overlay --}}
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

                {{-- Right: the subject's authorization form — extra fields, delivery method and the
                     categories checklist. Optimistic tick state lives on the client so taps land
                     instantly; the key is tied to the scanned subject so a new scan reseeds it from
                     the server's DB state. --}}
                @php($serviceHasThumbnails = $selectedService->categories->contains(fn ($category) => filled($category->image_path)))
                <div
                    class="flex min-h-0 flex-col gap-3"
                    x-data="entryGateCategories(@js(array_map('intval', $assignedCategoryIds)))"
                    wire:key="entry-gate-categories-{{ $scannedPersonId ?? 0 }}-{{ $scannedGuardianId ?? 0 }}"
                >
                    {{-- Mobile-only backdrop: tapping it parks the sheet off-screen again. --}}
                    <div
                        x-cloak
                        x-show="categoriesSheetOpen"
                        @click="categoriesSheetOpen = false"
                        @touchmove.prevent
                        x-transition:enter="transition-opacity ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition-opacity ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
                    ></div>

                    {{-- The authorization panel: a plain column on desktop, a bottom sheet on mobile.
                         translate-y-full parks it below the viewport while closed; the lg:* classes
                         reset every sheet property so the desktop grid layout stays untouched. --}}
                    <div
                        x-cloak
                        :class="categoriesSheetOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
                        class="fixed inset-x-0 bottom-0 z-40 flex max-h-[85svh] min-h-0 flex-col gap-3 overflow-y-auto overscroll-contain rounded-t-3xl border-t border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-out lg:static lg:z-auto lg:max-h-none lg:translate-y-0 lg:overflow-visible lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none"
                    >
                        {{-- Fixed sheet top (mobile): grabber + compact identity box. Sticky inside
                             the sheet's scroll area, so it stays pinned while the form scrolls under it. --}}
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
                                            @click="categoriesSheetOpen = false"
                                            aria-label="بستن"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                                        </button>
                                    </div>

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
                                    <span class="text-sm font-extrabold text-slate-800">دسته‌بندی‌های مجاز</span>
                                    <button
                                        type="button"
                                        @click="categoriesSheetOpen = false"
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
                                <h2 class="text-sm font-extrabold text-slate-800">دسته‌بندی‌های مجاز</h2>
                            </div>

                            <div class="mx-4 flex flex-1 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center lg:mx-0">
                                <p class="text-sm font-bold text-slate-600">برای تخصیص دسته‌بندی، ابتدا QR فرد را اسکن کنید.</p>
                            </div>

                            {{-- The delivery-method box waits for a subject too, and it keeps its
                                 place ahead of the category checklist. --}}
                            <div class="mx-4 rounded-2xl border border-slate-200 bg-white p-3 lg:mx-0">
                                <h3 class="flex items-center gap-2 text-sm font-extrabold text-slate-800">
                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    نحوه تحویل
                                </h3>
                                <p class="mt-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2.5 text-center text-[11px] font-semibold text-slate-500">
                                    برای ثبت نحوه تحویل، ابتدا QR فرد را اسکن کنید.
                                </p>
                            </div>
                        @else
                            {{-- Editable extra fields (identity-adjacent data captured at entry). --}}
                            @if($entryFields->isNotEmpty())
                                <div class="mx-4 rounded-2xl border border-slate-200 bg-white p-3 lg:mx-0">
                                    <p class="mb-2 text-[11px] font-bold text-slate-500">اطلاعات تکمیلی</p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach($entryFields as $field)
                                            <div wire:key="entry-field-value-{{ $field->id }}" class="relative flex flex-col gap-1">
                                                <label class="text-[11px] font-semibold text-slate-500">{{ $field->title ?: 'بدون عنوان' }}</label>
                                                <div class="relative">
                                                    @if($field->type === \App\Models\ServiceEntryField::TYPE_EDUCATION_LEVEL)
                                                        <select
                                                            wire:model.blur="entryFieldValues.{{ $field->id }}"
                                                            wire:target="updatedEntryFieldValues"
                                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100 transition"
                                                        >
                                                            <option value="">— انتخاب کنید —</option>
                                                            @foreach($this->educationLevels as $level)
                                                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input
                                                            type="{{ $field->type === \App\Models\ServiceEntryField::TYPE_NUMBER ? 'number' : 'text' }}"
                                                            wire:model.blur="entryFieldValues.{{ $field->id }}"
                                                            wire:target="updatedEntryFieldValues"
                                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100 transition"
                                                        >
                                                    @endif
                                                    <div wire:loading.delay.200ms wire:target="updatedEntryFieldValues.{{ $field->id }}" class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                                                        <svg class="h-4 w-4 animate-spin text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                                            <path d="M12 2a10 10 0 0110 10" stroke-linecap="round"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Delivery method: who physically receives the items (recorded per subject
                                 per service). It gates the confirm action, so it lives in the sheet right
                                 next to «ارسال مجوز و نفر بعدی». --}}
                            <div class="mx-4 rounded-2xl border p-3 transition-colors lg:mx-0 {{ $isProxyDelivery ? 'border-amber-300 bg-amber-50/50' : 'border-slate-200 bg-white' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="flex items-center gap-2 text-sm font-extrabold text-slate-800">
                                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        نحوه تحویل
                                    </h3>
                                    @if($isProxyDelivery && $this->proxyRecipientLabel !== '')
                                        <span class="inline-flex min-w-0 items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                            <span class="min-w-0 truncate">تحویل به: {{ $this->proxyRecipientLabel }}</span>
                                        </span>
                                    @endif
                                </div>

                                <label class="mt-2 block cursor-pointer">
                                    <input type="checkbox" wire:model.live="isProxyDelivery" class="peer sr-only">
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:ring-4 peer-checked:ring-amber-100 peer-focus-visible:ring-4 peer-focus-visible:ring-amber-200">
                                        {{-- The tick reads $wire directly: a peer-checked: variant only reaches siblings
                                             of the input, not this nested span. --}}
                                        <span
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2 transition"
                                            :class="$wire.isProxyDelivery ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-300 bg-white text-transparent'"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                        </span>
                                        <span class="flex min-w-0 flex-col">
                                            <span class="text-sm font-extrabold text-slate-800">تحویل به غیر از مددجو</span>
                                            <span class="text-[11px] font-semibold text-slate-500">اگر خدمت به شخص دیگری تحویل می‌شود، این گزینه را فعال کنید.</span>
                                        </span>
                                    </div>
                                </label>

                                <div x-show="$wire.isProxyDelivery" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[11px] font-bold text-slate-500">گیرنده خدمت</label>
                                        <select
                                            wire:model.live="proxyRecipientType"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 transition focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                        >
                                            <option value="">— انتخاب کنید —</option>
                                            @foreach(\App\Models\GateEntryDeliveryRecipient::TYPE_OPTIONS as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div x-show="$wire.proxyRecipientType === '{{ \App\Models\GateEntryDeliveryRecipient::TYPE_OTHER }}'" x-cloak class="flex flex-col gap-1">
                                        <label class="text-[11px] font-bold text-slate-500">نام و مشخصات گیرنده</label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                wire:model.live.debounce.600ms="proxyRecipientName"
                                                placeholder="مثلاً: مریم رضایی، همسایه، ۰۹۱۲۰۰۰۰۰۰۰"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 placeholder:text-slate-400 transition focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                            >
                                            <div wire:loading.delay.200ms wire:target="proxyRecipientName" class="pointer-events-none absolute inset-y-0 left-2 flex items-center">
                                                <svg class="h-4 w-4 animate-spin text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                                    <path d="M12 2a10 10 0 0110 10" stroke-linecap="round"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($proxyError)
                                    <p class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600">{{ $proxyError }}</p>
                                @endif
                            </div>

                            {{-- Categories: desktop keeps the classic header row; on mobile the sheet's
                                 identity header already carries context, so the badge row rides sticky. --}}
                            <div class="mx-4 flex items-center justify-between gap-2 lg:mx-0">
                                <h2 class="text-sm font-extrabold text-slate-800">دسته‌بندی‌های خدمت</h2>
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-600">
                                    <span x-text="assignedCount">{{ count($assignedCategoryIds) }}</span> انتخاب‌شده
                                </span>
                            </div>

                            @if($selectedService->categories->isEmpty())
                                <div class="mx-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center lg:mx-0">
                                    <p class="text-sm font-bold text-slate-600">برای این خدمت دسته‌بندی‌ای تعریف نشده است.</p>
                                </div>

                                {{-- Nothing to authorize: advance the queue from the sheet itself. --}}
                                <div class="sticky bottom-[env(safe-area-inset-bottom)] z-10 mt-1 border-t border-slate-100 bg-white px-4 pb-[calc(1rem_+_env(safe-area-inset-bottom))] pt-3 lg:hidden">
                                    <button
                                        type="button"
                                        wire:click="resumeScanning"
                                        wire:loading.attr="disabled"
                                        wire:target="resumeScanning"
                                        class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-emerald-600 px-4 py-3.5 text-[15px] font-black text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99] disabled:opacity-60"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3.7L20 9M19 15a7 7 0 01-11 3.7L4 15"/></svg>
                                        <span>اسکن نفر بعدی</span>
                                    </button>
                                </div>
                            @else
                                {{-- Mobile keeps every row single-line: name truncates and the unit chip
                                     yields to sm and up (same rule as the Delivery Gate rows). --}}
                                <div class="mx-4 grid gap-2 sm:grid-cols-2 sm:gap-3 lg:mx-0">
                                    @foreach($selectedService->categories as $category)
                                        @php($isLocked = in_array($category->id, $lockedCategoryIds, true))
                                        @php($cid = (int) $category->id)
                                        @if($isLocked)
                                            {{-- Locked: already delivered/finalized downstream, so it is read-only here. --}}
                                            <div
                                                wire:key="entry-gate-category-{{ $category->id }}"
                                                class="flex w-full cursor-not-allowed items-center justify-between gap-2 rounded-2xl border-2 border-emerald-300 bg-emerald-50/50 px-3 py-2.5 text-right opacity-60 sm:gap-3 sm:px-4 sm:py-3"
                                            >
                                                <span class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2 border-emerald-600 bg-emerald-600 text-white">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                    </span>
                                                    @if($serviceHasThumbnails)
                                                        <x-category-thumbnail :category="$category" sizeClass="h-10 w-10" roundedClass="rounded-lg" />
                                                    @endif
                                                    <span class="flex min-w-0 flex-col">
                                                        <span class="truncate text-sm font-extrabold text-slate-800">{{ $category->name }}</span>
                                                        <span class="truncate text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category->code }}</span>
                                                    </span>
                                                </span>
                                                <span class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">ثبت‌شده</span>
                                                    @if($category->unitLabel)
                                                        <span class="hidden rounded-md bg-white px-2 py-0.5 text-[10px] font-bold text-slate-500 sm:inline-block">{{ $category->unitLabel }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @else
                                            {{-- Toggleable: the whole row is the only tap target; the tick is client
                                                 state so the tap lands instantly and @click persists in the background. --}}
                                            <button
                                                type="button"
                                                @click="toggle({{ $cid }})"
                                                wire:key="entry-gate-category-{{ $category->id }}"
                                                class="group flex w-full items-center justify-between gap-2 rounded-2xl border-2 px-3 py-2.5 text-right transition active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-1 sm:gap-3 sm:px-4 sm:py-3"
                                                :class="isAssigned({{ $cid }})
                                                    ? 'border-indigo-500 bg-indigo-50'
                                                    : 'border-slate-300 bg-white hover:border-indigo-400 hover:bg-indigo-50/40'"
                                                :aria-pressed="isAssigned({{ $cid }})"
                                            >
                                                <span class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                                                    <span
                                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2 shadow-sm transition"
                                                        :class="[
                                                            isAssigned({{ $cid }}) ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white',
                                                            isSaving({{ $cid }}) ? 'animate-pulse' : '',
                                                        ]"
                                                    >
                                                        <svg x-show="isAssigned({{ $cid }})" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                                    </span>
                                                    @if($serviceHasThumbnails)
                                                        <x-category-thumbnail :category="$category" sizeClass="h-10 w-10" roundedClass="rounded-lg" />
                                                    @endif
                                                    <span class="flex min-w-0 flex-col">
                                                        <span class="truncate text-sm font-extrabold text-slate-900 group-hover:text-indigo-700 transition-colors">{{ $category->name }}</span>
                                                        <span class="truncate text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category->code }}</span>
                                                    </span>
                                                </span>
                                                <span class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                                                    @if($category->unitLabel)
                                                        <span class="hidden rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 sm:inline-block">{{ $category->unitLabel }}</span>
                                                    @endif
                                                    <span
                                                        class="h-2.5 w-2.5 shrink-0 rounded-full transition sm:hidden"
                                                        :class="isAssigned({{ $cid }}) ? 'bg-indigo-600' : 'bg-slate-300'"
                                                    ></span>
                                                </span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>

                                {{-- Fixed sheet bottom (mobile): the primary confirm bar, always at thumb
                                     height above the safe area. Ticks are already persisted, so this only
                                     validates the delivery-method declaration and arms the next scan.
                                     On desktop it keeps its plain page-sticky gradient behavior. --}}
                                <div class="sticky bottom-[env(safe-area-inset-bottom)] z-10 mt-1 border-t border-slate-100 bg-white px-4 pb-[calc(1rem_+_env(safe-area-inset-bottom))] pt-3 lg:bottom-0 lg:border-0 lg:bg-transparent lg:bg-gradient-to-t lg:from-white lg:via-white lg:to-transparent lg:px-1 lg:pb-1">
                                    <button
                                        type="button"
                                        wire:click="confirmPermission"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmPermission"
                                        title="تأیید مجوز و اسکن نفر بعدی (Ctrl + Enter)"
                                        class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-emerald-600 px-4 py-3.5 text-[15px] font-black text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 sm:px-5 sm:text-base"
                                        :class="nextScanShortcutActive ? 'ring-2 ring-emerald-300 ring-offset-1' : ''"
                                    >
                                        {{-- Loading spinner --}}
                                        <svg
                                            wire:loading
                                            wire:target="confirmPermission"
                                            class="h-5 w-5 animate-spin"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                            <path d="M12 2a10 10 0 0110 10" stroke-linecap="round"></path>
                                        </svg>

                                        {{-- Checkmark icon (hidden during loading) --}}
                                        <svg
                                            wire:loading.remove
                                            wire:target="confirmPermission"
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>

                                        {{-- Text (hidden during loading) --}}
                                        <div wire:loading.remove wire:target="confirmPermission" class="flex items-center gap-2">
                                            <span>ارسال مجوز و نفر بعدی</span>
                                            <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold" dir="ltr">
                                                <span x-text="assignedCount">{{ count($assignedCategoryIds) }}</span>/{{ $selectedService?->categories->count() ?? 0 }}
                                            </span>
                                        </div>

                                        {{-- Loading text --}}
                                        <span wire:loading wire:target="confirmPermission">در حال پردازش...</span>
                                    </button>
                                    <p class="mt-1.5 text-center text-[11px] font-semibold text-slate-400">دسته‌بندی‌های انتخاب‌شده ثبت شده‌اند؛ با تأیید به نفر بعدی می‌روید.</p>
                                    @if($proxyError)
                                        <p class="mt-1 text-center text-[11px] font-bold text-rose-600">{{ $proxyError }}</p>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Mobile-only reopen bar: pinned to the bottom of the viewport (instead of
                         buried in the page flow under the scanner + manual search) so the
                         authorization sheet is always one thumb-tap away after it closes. Only
                         exists while a subject is on screen. --}}
                    @if($lastScanResult)
                        <button
                            type="button"
                            x-cloak
                            x-show="!categoriesSheetOpen"
                            @click="categoriesSheetOpen = true"
                            class="fixed inset-x-4 bottom-[calc(1rem_+_env(safe-area-inset-bottom))] z-30 flex items-center justify-between gap-2 rounded-2xl border border-emerald-200 bg-white/95 px-4 py-3 text-sm font-bold text-emerald-700 shadow-lg backdrop-blur transition active:scale-[0.99] lg:hidden"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                اقلام و مجوز ورود
                            </span>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-black text-indigo-600">
                                <span x-text="assignedCount">{{ count($assignedCategoryIds) }}</span> انتخاب‌شده
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
