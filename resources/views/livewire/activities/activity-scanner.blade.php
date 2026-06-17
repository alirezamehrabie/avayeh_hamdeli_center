<div
    x-data="idCardScanner({ resolveScan: (payload) => $wire.resolveScannedQr(payload) })"
    x-init="init()"
    x-on:id-card-scanner-resume.window="resumeFromWire()"
    class="space-y-4"
    dir="rtl"
>
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
                    <div wire:ignore x-ref="scanner" id="activity-scanner-reader-{{ $activityId }}" class="h-full w-full"></div>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="aspect-square w-[min(72%,420px)] rounded-3xl border-2 border-emerald-300/90 shadow-[0_0_0_9999px_rgba(15,23,42,0.28)]"></div>
                    </div>
                    <div class="absolute bottom-4 right-4 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">QR مددجو را داخل قاب قرار دهید</div>
                </div>

                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">دوربین فعال</label>
                        <select x-model="selectedDeviceId" @change="switchCamera()" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                            <template x-for="camera in cameras" :key="camera.id">
                                <option :value="camera.id" x-text="camera.label"></option>
                            </template>
                        </select>
                    </div>
                    <button type="button" @click="startCamera()" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">فعال‌سازی دوربین</button>
                    <button type="button" wire:click="resumeScanning" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-100">ادامه اسکن</button>
                </div>
            </div>

            <aside class="space-y-4">
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

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-extrabold text-slate-800">ثبت دستی</h2>
                    <input type="text" wire:model.live.debounce.300ms="manualSearch" placeholder="جستجو با نام، کد مددجو یا کد ملی" class="mt-3 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">

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
