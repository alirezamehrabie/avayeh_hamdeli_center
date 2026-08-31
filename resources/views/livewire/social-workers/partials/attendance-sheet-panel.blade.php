@php
    $isCheckOut = ($mode ?? 'in') === \App\Models\AttendanceSheet::MODE_OUT;
@endphp
<div
    wire:key="attendance-sheet-{{ $sheet->id }}"
    x-data="{
        ...idCardScanner({
            resolveScan: (payload) => $wire.resolveScannedQr(payload),
            successSoundUrl: '/sounds/scan-success.wav',
            activityName: @js($sheet->name),
        }),
        manualOpen: false,
    }"
    x-init="init()"
    x-on:id-card-scanner-resume.window="resumeFromWire()"
    class="space-y-3"
>
    <style>
        [x-cloak] { display: none !important; }

        @keyframes attendance-frame-pulse {
            0%, 100% { opacity: 0.75; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }

        .attendance-frame { animation: attendance-frame-pulse 1.8s ease-in-out infinite; }
    </style>

    <div
        x-cloak
        x-show="successBanner.visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-3 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        class="pointer-events-none fixed inset-x-3 bottom-3 z-[80] sm:inset-x-auto sm:bottom-5 sm:left-5 sm:w-[22rem]"
        role="status"
        aria-live="polite"
    >
        <div
            class="overflow-hidden rounded-2xl border bg-white text-right shadow-xl ring-1 ring-slate-950/5"
            :class="{
                'border-emerald-200': successBanner.variant === 'success',
                'border-amber-200': successBanner.variant === 'warning',
                'border-rose-200': successBanner.variant === 'error',
            }"
        >
            <div
                class="h-1.5"
                :class="{
                    'bg-emerald-500': successBanner.variant === 'success',
                    'bg-amber-500': successBanner.variant === 'warning',
                    'bg-rose-500': successBanner.variant === 'error',
                }"
            ></div>
            <div class="p-4">
                <p
                    class="text-lg font-black leading-7"
                    :class="{
                        'text-emerald-700': successBanner.variant === 'success',
                        'text-amber-700': successBanner.variant === 'warning',
                        'text-rose-700': successBanner.variant === 'error',
                    }"
                    x-text="successBanner.message"
                ></p>
                <p class="mt-1 truncate text-base font-black text-slate-900" x-show="successBanner.name" x-text="successBanner.name"></p>
                <p class="mt-1 text-sm font-bold text-slate-500" x-text="successBanner.time"></p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l {{ $isCheckOut ? 'from-rose-600 via-rose-500 to-orange-500' : 'from-emerald-600 via-emerald-500 to-teal-500' }} px-4 py-4 text-white">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold {{ $isCheckOut ? 'text-rose-50' : 'text-emerald-50' }}">حضور و غیاب</p>
                    <h1 class="mt-0.5 truncate text-lg font-black leading-7">{{ $sheet->name }}</h1>
                </div>
                <button
                    type="button"
                    wire:click="closeSheetView"
                    class="shrink-0 rounded-2xl border border-white/25 bg-white/10 px-3 py-2 text-xs font-black transition hover:bg-white/20"
                >
                    فهرست
                </button>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                <div class="rounded-2xl bg-white/15 px-3 py-2">
                    <p class="text-[11px] font-bold {{ $isCheckOut ? 'text-rose-50' : 'text-emerald-50' }}">ورود</p>
                    <p class="text-xl font-black">{{ $checkedInCount }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 px-3 py-2">
                    <p class="text-[11px] font-bold {{ $isCheckOut ? 'text-rose-50' : 'text-emerald-50' }}">خروج</p>
                    <p class="text-xl font-black">{{ $checkedOutCount }}</p>
                </div>
            </div>
        </div>

        <div class="p-3">
            <div class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5" role="tablist" aria-label="انتخاب حالت ورود یا خروج">
                <button
                    type="button"
                    wire:click="setMode('in')"
                    role="tab"
                    aria-selected="{{ $isCheckOut ? 'false' : 'true' }}"
                    class="rounded-xl px-3 py-3.5 text-base font-black transition focus:outline-none focus:ring-4 focus:ring-emerald-100 {{ $isCheckOut ? 'text-slate-500 hover:bg-white/70' : 'bg-emerald-600 text-white shadow-md' }}"
                >
                    ثبت ورود
                </button>
                <button
                    type="button"
                    wire:click="setMode('out')"
                    role="tab"
                    aria-selected="{{ $isCheckOut ? 'true' : 'false' }}"
                    class="rounded-xl px-3 py-3.5 text-base font-black transition focus:outline-none focus:ring-4 focus:ring-rose-100 {{ $isCheckOut ? 'bg-rose-600 text-white shadow-md' : 'text-slate-500 hover:bg-white/70' }}"
                >
                    ثبت خروج
                </button>
            </div>

            <p class="mt-2 rounded-2xl {{ $isCheckOut ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-2.5 text-center text-sm font-bold" aria-live="polite">
                {{ $isCheckOut ? 'هر QR که اسکن کنید، خروجش ثبت می‌شود.' : 'هر QR که اسکن کنید، ورودش ثبت می‌شود.' }}
            </p>

            <div class="relative mt-3 h-[clamp(300px,58svh,520px)] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                <div wire:ignore x-ref="scanner" id="attendance-scanner-reader-{{ $sheet->id }}" class="qr-scanner-reader h-full w-full"></div>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="attendance-frame relative aspect-square w-[min(74%,360px)] rounded-3xl border-2 {{ $isCheckOut ? 'border-rose-300/90' : 'border-emerald-300/90' }}"></div>
                </div>
                <div class="absolute inset-x-3 bottom-3 rounded-2xl bg-slate-950/60 px-3 py-2 text-center text-xs font-bold text-white backdrop-blur-md">
                    QR مددجو را داخل کادر بگیرید
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <div
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border px-3 py-3 text-xs font-black"
                    :class="{
                        'border-emerald-200 bg-emerald-50 text-emerald-700': status === 'scanning',
                        'border-amber-200 bg-amber-50 text-amber-700': status === 'paused' || resolvingScan,
                        'border-rose-200 bg-rose-50 text-rose-700': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                        'border-slate-200 bg-slate-50 text-slate-600': status === 'initializing',
                    }"
                >
                    <span
                        class="inline-flex size-2.5 rounded-full"
                        :class="{
                            'bg-emerald-500': status === 'scanning',
                            'bg-amber-500': status === 'paused' || resolvingScan,
                            'bg-rose-500': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                            'bg-slate-400': status === 'initializing',
                        }"
                    ></span>
                    <span x-text="statusLabel()"></span>
                </div>

                <button
                    type="button"
                    @click="startCamera()"
                    :disabled="startingCamera || status === 'unsupported'"
                    class="flex-1 rounded-2xl bg-slate-800 px-4 py-3 text-sm font-black text-white transition hover:bg-slate-900 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    <span x-cloak x-show="startingCamera">در حال فعال‌سازی…</span>
                    <span x-cloak x-show="!startingCamera && cameraActive">دوربین دوباره</span>
                    <span x-cloak x-show="!startingCamera && !cameraActive">فعال‌سازی دوربین</span>
                </button>
            </div>

            <button
                type="button"
                x-cloak
                x-show="cameraActive && !scanning && !resolvingScan"
                wire:click="resumeScanning"
                class="mt-2 w-full rounded-2xl {{ $isCheckOut ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} px-4 py-4 text-base font-black text-white shadow-lg transition"
            >
                ادامه اسکن نفر بعدی
            </button>

            <button
                type="button"
                x-cloak
                x-show="cameras.length > 1"
                @click="cycleCamera()"
                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
            >
                تغییر دوربین
            </button>

            @if($lastScanResult)
                @php
                    $isWarning = in_array($lastScanResult['code'] ?? '', ['duplicate', 'already_checked_out'], true);
                @endphp
                <div class="mt-3 rounded-2xl border px-4 py-3 text-sm {{ ($lastScanResult['ok'] ?? false) ? ($isWarning ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800') : 'border-rose-200 bg-rose-50 text-rose-800' }}" role="status" aria-live="polite">
                    <p class="font-black">{{ $lastScanResult['message'] ?? '-' }}</p>
                    @if($lastScanResult['person'] ?? null)
                        <p class="mt-1 text-base font-black">{{ $lastScanResult['person']['name'] ?? '-' }}</p>
                        <p class="text-xs font-bold" dir="ltr">{{ $lastScanResult['person']['national_id'] ?? $lastScanResult['person']['person_code'] ?? '-' }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <button
            type="button"
            @click="manualOpen = !manualOpen"
            class="flex w-full items-center justify-between gap-3 px-4 py-4 text-right"
            :aria-expanded="manualOpen.toString()"
        >
            <span class="text-sm font-extrabold text-slate-800">
                {{ $isCheckOut ? 'ثبت خروج بدون QR' : 'ثبت ورود بدون QR' }}
            </span>
            <svg class="size-5 shrink-0 text-slate-400 transition" :class="{ 'rotate-180': manualOpen }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <div x-cloak x-show="manualOpen" x-collapse class="border-t border-slate-100 p-4">
            <input
                type="text"
                wire:model.live.debounce.400ms="manualSearch"
                placeholder="نام یا کد ملی مددجو"
                aria-label="جستجوی مددجو"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-base font-bold text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
            >

            @if($isCheckOut)
                <p class="mt-2 text-xs font-semibold text-slate-500">فقط کسانی که ورودشان ثبت شده و خروج نزده‌اند نمایش داده می‌شوند.</p>
            @endif

            <div class="mt-3 space-y-2">
                @forelse($manualCandidates as $candidate)
                    <button
                        type="button"
                        wire:click="selectManualPerson({{ $candidate->id }})"
                        class="block w-full rounded-2xl border px-4 py-3 text-right transition {{ $selectedPerson?->id === $candidate->id ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 bg-slate-50 hover:bg-indigo-50' }}"
                    >
                        <span class="block text-sm font-black text-slate-800">{{ $candidate->full_name ?: trim($candidate->first_name.' '.$candidate->last_name) }}</span>
                        <span class="mt-0.5 block text-xs font-bold text-slate-500" dir="ltr">{{ $candidate->national_id ?: $candidate->person_code }}</span>
                    </button>
                @empty
                    @if(mb_strlen(trim($manualSearch)) >= 2)
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">مددجویی با این مشخصات پیدا نشد.</p>
                    @endif
                @endforelse
            </div>

            @if($selectedPerson)
                <button
                    type="button"
                    wire:click="manualRegister"
                    class="mt-3 w-full rounded-2xl px-4 py-4 text-base font-black text-white shadow-lg transition {{ $isCheckOut ? 'bg-rose-600 shadow-rose-700/20 hover:bg-rose-700' : 'bg-emerald-600 shadow-emerald-700/20 hover:bg-emerald-700' }}"
                >
                    {{ $isCheckOut ? 'ثبت خروج' : 'ثبت ورود' }} {{ $selectedPerson->full_name ?: trim($selectedPerson->first_name.' '.$selectedPerson->last_name) }}
                </button>
            @endif
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-extrabold text-slate-800">
            {{ $isCheckOut ? 'وضعیت خروج افراد این حضور و غیاب' : 'افراد ثبت‌شده در این حضور و غیاب' }}
        </h2>

        <div class="mt-3 space-y-2">
            @forelse($entries as $entry)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black text-slate-800">{{ $entry->person_name ?: '-' }}</p>
                            <p class="mt-0.5 text-xs font-bold text-slate-500" dir="ltr">{{ $entry->national_id ?: $entry->person_code }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-black {{ $entry->checked_out_at ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $entry->checked_out_at ? 'خارج شده' : 'داخل' }}
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-bold">
                        @if($entry->checked_in_at)
                            <span class="rounded-full bg-white px-2.5 py-1 text-emerald-700">
                                ورود {{ \App\Helpers\Morilog\Jalalian::fromDateTime($entry->checked_in_at)->format('H:i') }}
                            </span>
                        @endif
                        @if($entry->checked_out_at)
                            <span class="rounded-full bg-white px-2.5 py-1 text-rose-700">
                                خروج {{ \App\Helpers\Morilog\Jalalian::fromDateTime($entry->checked_out_at)->format('H:i') }}
                            </span>
                        @endif
                        <span class="rounded-full bg-white px-2.5 py-1 text-slate-500">
                            {{ \App\Models\AttendanceSheetEntry::METHOD_OPTIONS[$entry->check_in_method] ?? $entry->check_in_method }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="rounded-2xl bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-500">هنوز کسی ثبت نشده است.</p>
            @endforelse
        </div>
    </div>


</div>
