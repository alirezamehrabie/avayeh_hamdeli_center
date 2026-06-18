<div
    x-data="{
        ...idCardScanner({
            resolveScan: (payload) => $wire.resolveScannedQr(payload),
            successSoundUrl: '/sounds/scan-success.wav',
            activityName: @js($activity?->name ?? ''),
        }),
        mobileHeaderExpanded: false,
    }"
    x-init="init()"
    x-on:id-card-scanner-resume.window="resumeFromWire()"
    class="space-y-4"
    dir="rtl"
>
    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes activity-scanner-frame-pulse {
            0%, 100% {
                border-color: rgba(110, 231, 183, 0.82);
                box-shadow:
                    0 0 0 1px rgba(255, 255, 255, 0.16),
                    0 0 22px rgba(16, 185, 129, 0.18),
                    0 0 0 9999px rgba(15, 23, 42, 0.28);
            }

            50% {
                border-color: rgba(45, 212, 191, 1);
                box-shadow:
                    0 0 0 1px rgba(255, 255, 255, 0.22),
                    0 0 34px rgba(20, 184, 166, 0.34),
                    0 0 0 9999px rgba(15, 23, 42, 0.28);
            }
        }

        @keyframes activity-scanner-corner-pulse {
            0%, 100% {
                opacity: 0.7;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.035);
            }
        }

        .activity-scanner-frame {
            animation: activity-scanner-frame-pulse 1.8s ease-in-out infinite;
        }

        .activity-scanner-frame-corner {
            animation: activity-scanner-corner-pulse 1.8s ease-in-out infinite;
        }
    </style>

    <div
        x-cloak
        x-show="successBanner.visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-3 opacity-0 sm:-translate-x-3 sm:translate-y-0"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave-end="translate-y-2 opacity-0 sm:-translate-x-2 sm:translate-y-0"
        class="pointer-events-none fixed inset-x-3 bottom-3 z-[80] sm:inset-x-auto sm:bottom-5 sm:left-5 sm:w-[24rem]"
        dir="rtl"
        role="status"
        aria-live="polite"
    >
        <div
            class="overflow-hidden rounded-2xl border bg-white text-right shadow-xl ring-1 ring-slate-950/5"
            :class="{
                'border-emerald-200 shadow-emerald-950/10': successBanner.variant === 'success',
                'border-amber-200 shadow-amber-950/10': successBanner.variant === 'warning',
                'border-rose-200 shadow-rose-950/10': successBanner.variant === 'error',
            }"
        >
            <div
                class="h-1"
                :class="{
                    'bg-emerald-500': successBanner.variant === 'success',
                    'bg-amber-500': successBanner.variant === 'warning',
                    'bg-rose-500': successBanner.variant === 'error',
                }"
            ></div>

            <div class="flex gap-3 p-4">
                <div
                    class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-emerald-50 text-emerald-600': successBanner.variant === 'success',
                        'bg-amber-50 text-amber-600': successBanner.variant === 'warning',
                        'bg-rose-50 text-rose-600': successBanner.variant === 'error',
                    }"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path x-show="successBanner.variant === 'success'" d="M5 12.5 9.5 17 19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                        <path x-show="successBanner.variant === 'warning'" d="M12 7v6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                        <path x-show="successBanner.variant === 'warning'" d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        <path x-show="successBanner.variant === 'error'" d="M7 7l10 10M17 7 7 17" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p
                        class="text-xs font-extrabold"
                        :class="{
                            'text-emerald-600': successBanner.variant === 'success',
                            'text-amber-600': successBanner.variant === 'warning',
                            'text-rose-600': successBanner.variant === 'error',
                        }"
                        x-text="successBanner.variant === 'success' ? 'ثبت موفق' : (successBanner.variant === 'warning' ? 'نیاز به توجه' : 'خطای اسکن')"
                    ></p>
                    <h2 class="mt-1 text-sm font-black leading-6 text-slate-900" x-text="successBanner.message"></h2>
                    <div class="mt-1">
                        <p
                            class="truncate"
                            x-show="successBanner.name"
                            :class="(() => {
                                const statusName = ['success', 'warning', 'error'].includes(successBanner.name)
                                    ? successBanner.name
                                    : successBanner.variant;

                                if (statusName === 'success') {
                                    return 'text-lg font-black text-emerald-600';
                                }

                                if (statusName === 'warning') {
                                    return 'text-lg font-black text-amber-600';
                                }

                                if (statusName === 'error') {
                                    return 'text-lg font-black text-rose-600';
                                }

                                return 'text-sm font-bold text-slate-700';
                            })()"
                            x-text="successBanner.name"
                        ></p>
                        <p class="text-sm font-semibold text-slate-500" x-show="!successBanner.name">اطلاعات مددجو در دسترس نیست</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-slate-500" x-text="successBanner.time"></p>
                    </div>
                    <p class="mt-2 truncate text-[11px] font-medium text-slate-400" x-show="successBanner.activityName" x-text="successBanner.activityName"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="rounded-t-3xl bg-gradient-to-l from-emerald-500 via-teal-500 to-cyan-500 px-4 py-3 text-white sm:px-5 sm:py-4">
            <div class="flex items-start justify-between gap-3 lg:hidden">
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg font-extrabold leading-6 sm:text-xl">ثبت حضور فعالیت</h1>
                    <div class="mt-2 inline-flex max-w-full items-center rounded-full bg-white/14 px-3 py-1 text-xs font-bold text-emerald-50 ring-1 ring-white/15">
                        <span class="truncate">{{ $activity?->name ?? '-' }}</span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="mobileHeaderExpanded = !mobileHeaderExpanded"
                    class="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-bold shadow-sm transition hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/20"
                    :aria-expanded="mobileHeaderExpanded.toString()"
                    aria-controls="activity-scanner-mobile-header-panel"
                >
                    <span>جزئیات</span>
                    <svg class="size-4 transition" :class="{ 'rotate-180': mobileHeaderExpanded }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <div
                x-cloak
                x-show="mobileHeaderExpanded"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                id="activity-scanner-mobile-header-panel"
                class="mt-3 space-y-2 lg:hidden"
            >
                <div class="rounded-2xl border border-white/15 bg-white/10 p-3 text-xs font-bold backdrop-blur-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-emerald-100">وضعیت</span>
                        <span>{{ \App\Models\Activity::STATUS_OPTIONS[$activity?->status] ?? $activity?->status }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span class="text-emerald-100">حضور</span>
                        <span>{{ $activity?->present_attendances_count ?? 0 }} / {{ $activity?->capacity ?: '∞' }}</span>
                    </div>
                    <button type="button" wire:click="backToActivities" class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-bold transition hover:bg-white/20">
                        بازگشت
                    </button>
                </div>
            </div>

            <div class="hidden flex-col gap-3 lg:flex lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold">ثبت حضور فعالیت</h1>
                    <p class="mt-1 text-sm text-emerald-50">{{ $activity?->name ?? '-' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                    <span class="rounded-full bg-white/15 px-3 py-1.5">وضعیت: {{ \App\Models\Activity::STATUS_OPTIONS[$activity?->status] ?? $activity?->status }}</span>
                    <span class="rounded-full bg-white/15 px-3 py-1.5">حضور: {{ $activity?->present_attendances_count ?? 0 }} / {{ $activity?->capacity ?: '∞' }}</span>
                    <button type="button" wire:click="backToActivities" class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 hover:bg-white/20">بازگشت</button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 p-4 xl:grid-cols-[minmax(0,1.5fr)_25rem]">
            <div class="space-y-4">
                <div class="relative h-[clamp(320px,65svh,560px)] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                    <div wire:ignore x-ref="scanner" id="activity-scanner-reader-{{ $activityId }}" class="qr-scanner-reader h-full w-full"></div>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="activity-scanner-frame relative aspect-square w-[min(72%,420px)] rounded-3xl border-2 border-emerald-300/90">
                            <span class="activity-scanner-frame-corner absolute right-0 top-0 h-12 w-12 rounded-tr-3xl border-r-4 border-t-4 border-teal-200"></span>
                            <span class="activity-scanner-frame-corner absolute left-0 top-0 h-12 w-12 rounded-tl-3xl border-l-4 border-t-4 border-teal-200"></span>
                            <span class="activity-scanner-frame-corner absolute bottom-0 right-0 h-12 w-12 rounded-br-3xl border-b-4 border-r-4 border-teal-200"></span>
                            <span class="activity-scanner-frame-corner absolute bottom-0 left-0 h-12 w-12 rounded-bl-3xl border-b-4 border-l-4 border-teal-200"></span>
                        </div>
                    </div>
                    <div class="absolute bottom-4 right-4 rounded-full border border-white/20 bg-slate-950/55 px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-slate-950/25 backdrop-blur-md ring-1 ring-white/10">
                        QR مددجو را داخل قاب قرار دهید
                    </div>
                </div>

                <div class="activity-scanner-controls grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto] md:items-end md:gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">دوربین فعال</label>
                        <select x-model="selectedDeviceId" @change="switchCamera()" :disabled="startingCamera || !cameras.length" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <template x-for="camera in cameras" :key="camera.id">
                                <option :value="camera.id" x-text="camera.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-xl border px-4 py-3 text-xs font-bold md:min-w-32"
                        :class="{
                            'border-emerald-200 bg-emerald-50 text-emerald-700': status === 'scanning',
                            'border-amber-200 bg-amber-50 text-amber-700': status === 'paused' || resolvingScan,
                            'border-rose-200 bg-rose-50 text-rose-700': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                            'border-slate-200 bg-slate-50 text-slate-600': status === 'initializing',
                        }"
                    >
                        <span class="inline-flex size-2.5 rounded-full"
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
                        class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none md:min-w-36"
                    >
                        <span x-cloak x-show="startingCamera">در حال فعال‌سازی...</span>
                        <span x-cloak x-show="!startingCamera && cameraActive">راه‌اندازی مجدد</span>
                        <span x-cloak x-show="!startingCamera && !cameraActive">فعال‌سازی دوربین</span>
                    </button>
                    <button
                        type="button"
                        wire:click="resumeScanning"
                        :disabled="startingCamera || scanning || resolvingScan || ['camera_denied', 'unsupported'].includes(status)"
                        class="rounded-xl border border-emerald-300 bg-white px-5 py-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 md:min-w-32"
                    >
                        <span x-cloak x-show="resolvingScan">در حال بررسی...</span>
                        <span x-cloak x-show="!resolvingScan && scanning">اسکن فعال است</span>
                        <span x-cloak x-show="!resolvingScan && !scanning">ادامه اسکن</span>
                    </button>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">وضعیت اسکن</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600" x-text="message"></p>
                    @if($lastScanResult)
                        <div class="mt-3 rounded-2xl border px-4 py-3 text-sm {{ ($lastScanResult['ok'] ?? false) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                            <p class="font-black">{{ $lastScanResult['message'] ?? '-' }}</p>
                            @if($lastScanResult['person'] ?? null)
                                <p class="mt-1">{{ $lastScanResult['person']['name'] ?? '-' }}</p>
                                <p class="text-xs" dir="ltr">{{ $lastScanResult['person']['person_code'] ?? '-' }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_14px_32px_rgba(15,23,42,0.06)] ring-1 ring-slate-100/70">
                    <h2 class="text-sm font-extrabold text-slate-800">ثبت دستی</h2>
                    <div class="relative mt-3">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                            <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="m14.2 14.2 3.3 3.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="manualSearch" placeholder="جستجو با نام، کد مددجو یا کد ملی" class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 py-2 pl-4 pr-11 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100">
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach($manualCandidates as $candidate)
                            <button type="button" wire:click="selectManualPerson({{ $candidate->id }})" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-right text-xs hover:bg-emerald-50">
                                <span class="font-bold text-slate-800">{{ $candidate->full_name ?: trim($candidate->first_name . ' ' . $candidate->last_name) }}</span>
                                <span class="block text-slate-500">{{ $candidate->person_code }} · {{ $candidate->national_id }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if($selectedPerson)
                        <div class="mt-3 rounded-2xl bg-emerald-50 p-3 text-xs text-emerald-800">
                            مددجوی انتخاب‌شده: <strong>{{ $selectedPerson->full_name ?: trim($selectedPerson->first_name . ' ' . $selectedPerson->last_name) }}</strong>
                        </div>
                        <button type="button" wire:click="manualCheckIn" class="mt-3 w-full rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">ثبت حضور دستی</button>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">آخرین ثبت‌ها</h2>
                    <div class="mt-3 space-y-2">
                        @forelse($recentAttendances as $attendance)
                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                <p class="font-bold text-slate-800">{{ $attendance->person?->full_name ?: '-' }}</p>
                                <p>{{ $attendance->registration_method === 'qr' ? 'QR' : 'دستی' }} · {{ $attendance->checked_in_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($attendance->checked_in_at)->format('H:i:s') : '-' }} · {{ $attendance->recorder?->full_name ?: $attendance->recorder?->name ?: '-' }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">هنوز حضوری ثبت نشده است.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
