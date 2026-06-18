<div
    x-data="idCardScanner({
        resolveScan: (payload) => $wire.resolveScannedQr(payload),
        activityName: @js($activity?->name ?? ''),
    })"
    x-init="init()"
    x-on:id-card-scanner-resume.window="resumeFromWire()"
    class="space-y-4"
    dir="rtl"
>
    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes attendance-success-check {
            from {
                stroke-dashoffset: 48;
            }

            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes attendance-success-pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.18;
            }

            50% {
                transform: scale(1.18);
                opacity: 0.32;
            }
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

        .attendance-success-check {
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: attendance-success-check 650ms ease-out 120ms forwards;
        }

        .attendance-success-pulse {
            animation: attendance-success-pulse 1.35s ease-in-out infinite;
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
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="pointer-events-none fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/35 px-4 backdrop-blur-sm"
        dir="rtl"
    >
        <div
            x-show="successBanner.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-4 scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-97 opacity-0"
            class="relative w-full max-w-2xl overflow-hidden rounded-[2rem] border bg-white text-center shadow-2xl ring-1 ring-white/80 md:rounded-[2.25rem]"
            :class="{
                'border-emerald-200/80 shadow-emerald-950/25': successBanner.variant === 'success',
                'border-amber-200/80 shadow-amber-950/20': successBanner.variant === 'warning',
                'border-rose-200/80 shadow-rose-950/20': successBanner.variant === 'error',
            }"
            role="status"
            aria-live="polite"
        >
            <div
                class="absolute inset-x-0 top-0 h-2 bg-gradient-to-l"
                :class="{
                    'from-emerald-400 via-teal-400 to-cyan-400': successBanner.variant === 'success',
                    'from-amber-400 via-orange-400 to-yellow-400': successBanner.variant === 'warning',
                    'from-rose-500 via-red-500 to-orange-500': successBanner.variant === 'error',
                }"
            ></div>
            <div
                class="absolute -left-20 -top-20 size-56 rounded-full blur-3xl"
                :class="{
                    'bg-emerald-200/45': successBanner.variant === 'success',
                    'bg-amber-200/45': successBanner.variant === 'warning',
                    'bg-rose-200/45': successBanner.variant === 'error',
                }"
            ></div>
            <div
                class="absolute -bottom-24 -right-20 size-64 rounded-full blur-3xl"
                :class="{
                    'bg-cyan-200/45': successBanner.variant === 'success',
                    'bg-yellow-200/45': successBanner.variant === 'warning',
                    'bg-orange-200/45': successBanner.variant === 'error',
                }"
            ></div>

            <div class="relative px-6 py-7 md:px-9 md:py-8">
                <div class="mx-auto flex size-24 items-center justify-center md:size-28">
                    <div
                        class="attendance-success-pulse absolute size-24 rounded-full md:size-28"
                        :class="{
                            'bg-emerald-400': successBanner.variant === 'success',
                            'bg-amber-400': successBanner.variant === 'warning',
                            'bg-rose-400': successBanner.variant === 'error',
                        }"
                    ></div>
                    <div
                        class="relative flex size-16 items-center justify-center rounded-full bg-gradient-to-br text-white shadow-xl ring-8 md:size-20"
                        :class="{
                            'from-emerald-500 to-teal-500 shadow-emerald-700/25 ring-emerald-100': successBanner.variant === 'success',
                            'from-amber-500 to-orange-500 shadow-amber-700/25 ring-amber-100': successBanner.variant === 'warning',
                            'from-rose-500 to-red-500 shadow-rose-700/25 ring-rose-100': successBanner.variant === 'error',
                        }"
                    >
                        <svg class="size-10 md:size-12" viewBox="0 0 52 52" fill="none" aria-hidden="true">
                            <path x-show="successBanner.variant === 'success'" class="attendance-success-check" d="M14 27.5 22.5 36 39 17" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
                            <path x-show="successBanner.variant === 'warning'" d="M26 13v17" stroke="currentColor" stroke-width="6" stroke-linecap="round" />
                            <path x-show="successBanner.variant === 'warning'" d="M26 39h.01" stroke="currentColor" stroke-width="7" stroke-linecap="round" />
                            <path x-show="successBanner.variant === 'error'" d="M17 17l18 18M35 17 17 35" stroke="currentColor" stroke-width="6" stroke-linecap="round" />
                        </svg>
                    </div>
                </div>

                <div class="mt-5">
                    <p
                        class="text-xs font-extrabold tracking-[0.22em]"
                        :class="{
                            'text-emerald-600': successBanner.variant === 'success',
                            'text-amber-600': successBanner.variant === 'warning',
                            'text-rose-600': successBanner.variant === 'error',
                        }"
                        x-text="successBanner.variant === 'success' ? 'ثبت موفق' : (successBanner.variant === 'warning' ? 'نیاز به توجه' : 'خطای اسکن')"
                    ></p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950 md:text-4xl" x-text="successBanner.message"></h2>
                    <div
                        class="mx-auto mt-5 max-w-xl rounded-3xl border px-5 py-4"
                        :class="{
                            'border-emerald-100 bg-emerald-50/80': successBanner.variant === 'success',
                            'border-amber-100 bg-amber-50/80': successBanner.variant === 'warning',
                            'border-rose-100 bg-rose-50/80': successBanner.variant === 'error',
                        }"
                    >
                        <p class="truncate text-2xl font-black text-slate-900 md:text-3xl" x-show="successBanner.name" x-text="successBanner.name"></p>
                        <p class="text-sm font-semibold text-slate-500" x-show="!successBanner.name">اطلاعات مددجو در دسترس نیست</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-slate-500" x-text="successBanner.time"></p>
                    </div>
                    <p class="mx-auto mt-4 max-w-xl truncate rounded-full border border-slate-200 bg-white/75 px-4 py-2 text-xs font-semibold text-slate-500" x-show="successBanner.activityName" x-text="successBanner.activityName"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-emerald-600 via-teal-600 to-cyan-600 px-5 py-4 text-white">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
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

                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end md:gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">دوربین فعال</label>
                        <select x-model="selectedDeviceId" @change="switchCamera()" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                            <template x-for="camera in cameras" :key="camera.id">
                                <option :value="camera.id" x-text="camera.label"></option>
                            </template>
                        </select>
                    </div>
                    <button type="button" @click="startCamera()" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100 md:min-w-36">فعال‌سازی دوربین</button>
                    <button type="button" wire:click="resumeScanning" class="rounded-xl border border-emerald-300 bg-white px-5 py-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-100 md:min-w-32">ادامه اسکن</button>
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
