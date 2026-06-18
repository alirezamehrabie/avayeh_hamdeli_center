<div
    x-data="idCardScanner({
        resolveScan: (payload) => $wire.resolveScannedQr(payload),
    })"
    x-init="init()"
    x-on:id-card-scanner-pause.window="pauseFromWire()"
    x-on:id-card-scanner-resume.window="resumeFromWire()"
    class="min-h-0 lg:h-full"
    dir="rtl"
>
    <div class="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:h-full lg:min-h-[560px]">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800">اسکنر کارت شناسایی</h1>
                    <p class="mt-1 text-sm text-slate-500">اسکن زنده QR برای نمایش اطلاعات مددجو یا خانوار</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold"
                     :class="{
                        'bg-emerald-50 text-emerald-700': ['ready', 'scanning'].includes(status),
                        'bg-amber-50 text-amber-700': status === 'paused',
                        'bg-rose-50 text-rose-700': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                        'bg-slate-100 text-slate-700': status === 'initializing',
                     }">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full"
                          :class="{
                            'bg-emerald-500': ['ready', 'scanning'].includes(status),
                            'bg-amber-500': status === 'paused',
                            'bg-rose-500': ['camera_denied', 'scan_error', 'unsupported'].includes(status),
                            'bg-slate-400': status === 'initializing',
                          }"></span>
                    <span x-text="statusLabel()"></span>
                </div>
            </div>
        </div>

        <div class="grid min-h-0 flex-1 gap-4 p-3 sm:gap-5 sm:p-5 lg:grid-cols-[minmax(0,1.7fr)_380px] lg:items-start">
            <div class="flex min-h-0 flex-col gap-4">
                <div class="relative h-[clamp(320px,70svh,560px)] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 sm:aspect-[4/3] sm:h-auto sm:min-h-[360px] md:max-h-[64svh] lg:aspect-video lg:max-h-[56vh] lg:min-h-[300px]">
                    <div
                        wire:ignore
                        x-ref="scanner"
                        id="id-card-scanner-reader"
                        class="qr-scanner-reader h-full w-full"
                    ></div>

                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="aspect-square w-[min(72%,calc(100%-5rem),420px)] max-h-[calc(100%-5rem)] rounded-2xl border-2 border-emerald-300/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.28)] sm:w-[min(68%,420px)] sm:rounded-3xl"></div>
                    </div>

                    <div class="absolute bottom-3 right-3 max-w-[calc(100%-1.5rem)] rounded-full bg-slate-950/70 px-3 py-1.5 text-[11px] font-semibold text-white backdrop-blur sm:bottom-4 sm:right-4 sm:text-xs">
                        کد QR را داخل قاب قرار دهید
                    </div>

                    <div
                        x-show="cameraActive"
                        x-transition.opacity.duration.150ms
                        class="absolute left-3 top-3 max-w-[calc(100%-1.5rem)] rounded-full bg-slate-950/70 px-3 py-1.5 text-[10px] font-semibold text-white backdrop-blur sm:left-4 sm:top-4 sm:max-w-[calc(100%-2rem)] sm:text-[11px]"
                        dir="ltr"
                        style="display: none;"
                    >
                        <span x-text="cameraSettings.width && cameraSettings.height ? `${cameraSettings.width}x${cameraSettings.height}` : 'camera'"></span>
                        <span x-show="cameraSettings.zoom"> · zoom <span x-text="Number(cameraSettings.zoom).toFixed(1)"></span>x</span>
                        <span x-show="cameraCapabilities.torch"> · torch</span>
                        <span> · enhanced</span>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_auto_auto] xl:items-end">
                    <div class="flex-1">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">دوربین فعال</label>
                        <select
                            x-model="selectedDeviceId"
                            @change="switchCamera()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        >
                            <template x-for="camera in cameras" :key="camera.id">
                                <option :value="camera.id" x-text="camera.label"></option>
                            </template>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="startCamera()"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 xl:w-auto"
                    >
                        فعال‌سازی دوربین
                    </button>
                    <button
                        type="button"
                        wire:click="resumeScanning"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 xl:w-auto"
                    >
                        ادامه اسکن
                    </button>
                </div>
            </div>

            <div class="flex min-h-0 flex-col gap-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">وضعیت اسکن</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600" x-text="message"></p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">آخرین نتیجه</h2>
                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        @if($lastScanResult)
                            <p class="font-semibold {{ ($lastScanResult['type'] ?? null) === \App\Models\QrIdentity::SUBJECT_PERSON ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $lastScanResult['title'] ?? '-' }}
                            </p>
                            <p class="mt-1">{{ $lastScanResult['name'] ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $lastScanResult['code_label'] ?? 'کد' }}:
                                <span dir="ltr">{{ $lastScanResult['code'] ?? '-' }}</span>
                            </p>
                        @else
                            <p>هنوز نتیجه معتبری ثبت نشده است.</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs leading-6 text-slate-500">
                    این بخش از دوربین دستگاه برای تشخیص QR استفاده می‌کند. پس از شناسایی، نتیجه در همین بخش نمایش داده می‌شود.
                </div>
            </div>
        </div>
    </div>

    @include('livewire.people.partials.person-details-modal')

    @include('livewire.guardians.partials.household-details-modal', [
        'selectedGuardian' => $this->selectedGuardian,
        'openState' => $showHouseholdModal,
        'closeMethod' => 'closeHouseholdModal',
        'wireKey' => 'scanner-household-modal',
    ])
</div>
