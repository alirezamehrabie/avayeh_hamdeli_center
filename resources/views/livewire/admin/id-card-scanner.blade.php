<div
    x-data="idCardScanner({
        resolveScan: (payload) => $wire.resolveScannedQr(payload),
        updateStatus: (status, message = '') => $wire.setScanStatus(status, message),
    })"
    x-init="init()"
    x-on:id-card-scanner-pause.window="pauseFromWire()"
    x-on:id-card-scanner-resume.window="resumeFromWire()"
    class="space-y-5"
    dir="rtl"
>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800">اسکنر کارت شناسایی</h1>
                    <p class="mt-1 text-sm text-slate-500">اسکن زنده QR برای نمایش اطلاعات مددجو یا خانوار</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold"
                     :class="{
                        'bg-emerald-50 text-emerald-700': ['ready', 'scanning'].includes(@js($scanStatus)),
                        'bg-amber-50 text-amber-700': @js($scanStatus) === 'paused',
                        'bg-rose-50 text-rose-700': ['camera_denied', 'scan_error', 'unsupported'].includes(@js($scanStatus)),
                        'bg-slate-100 text-slate-700': @js($scanStatus) === 'initializing',
                     }">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full"
                          :class="{
                            'bg-emerald-500': ['ready', 'scanning'].includes(@js($scanStatus)),
                            'bg-amber-500': @js($scanStatus) === 'paused',
                            'bg-rose-500': ['camera_denied', 'scan_error', 'unsupported'].includes(@js($scanStatus)),
                            'bg-slate-400': @js($scanStatus) === 'initializing',
                          }"></span>
                    <span>{{ $scanStatus }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1.35fr)_360px]">
            <div class="space-y-4">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                    <video
                        x-ref="video"
                        autoplay
                        playsinline
                        muted
                        class="aspect-[16/10] w-full object-cover"
                    ></video>

                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="h-[58%] w-[70%] rounded-3xl border-2 border-emerald-300/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.35)]"></div>
                    </div>

                    <div class="absolute bottom-4 right-4 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">
                        کد QR را داخل قاب قرار دهید
                    </div>
                </div>

                <div class="flex flex-col gap-3 md:flex-row md:items-end">
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

                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="startCamera()"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                        >
                            فعال‌سازی دوربین
                        </button>
                        <button
                            type="button"
                            wire:click="resumeScanning"
                            class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            ادامه اسکن
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">وضعیت اسکن</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $scanMessage }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">آخرین نتیجه</h2>
                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        @if($resolvedSubjectType === \App\Models\QrIdentity::SUBJECT_PERSON && $this->selectedPerson)
                            <p class="font-semibold text-emerald-700">مددجو شناسایی شد</p>
                            <p class="mt-1">{{ $this->selectedPerson->full_name ?: '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">کد مددجو: {{ $this->selectedPerson->person_code ?: '-' }}</p>
                        @elseif($resolvedSubjectType === \App\Models\QrIdentity::SUBJECT_GUARDIAN && $this->selectedGuardian)
                            <p class="font-semibold text-amber-700">خانوار شناسایی شد</p>
                            <p class="mt-1">{{ $this->selectedGuardian->full_name ?: '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">کد خانوار: {{ $this->selectedGuardian->guardian_code ?: '-' }}</p>
                        @else
                            <p>هنوز نتیجه معتبری ثبت نشده است.</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs leading-6 text-slate-500">
                    این بخش از دوربین دستگاه برای تشخیص QR استفاده می‌کند. اگر مرورگر از تشخیص زنده QR پشتیبانی نکند، پیام راهنما نمایش داده می‌شود.
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
