<div class="space-y-6" dir="rtl">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-black text-slate-900">گیت ورود</h1>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        انتخاب خدمت، اسکن QR و تخصیص دسته‌بندی‌های مجاز برای مرحله تحویل.
                    </p>
                </div>

                @if($selectedService)
                    <div class="flex items-center gap-2">
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                            {{ $selectedService->name }}
                        </span>
                        <button
                            type="button"
                            wire:click="changeService"
                            wire:confirm="با تغییر خدمت، اسکن جاری پاک می‌شود. ادامه می‌دهید؟"
                            class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                        >
                            تغییر خدمت
                        </button>
                    </div>
                @endif
            </div>
        </div>

        @if(! $selectedService)
            {{-- Step 1: Service selection --}}
            <div class="px-5 py-6">
                <h2 class="mb-4 text-sm font-extrabold text-slate-800">۱. انتخاب خدمت گیت ورود</h2>

                @if($gateServices->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                        <p class="text-sm font-bold text-slate-700">خدمتی با قابلیت تحویل از گیت یافت نشد.</p>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($gateServices as $service)
                            <button
                                type="button"
                                wire:click="selectService({{ $service->id }})"
                                wire:key="gate-service-{{ $service->id }}"
                                class="group flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-4 text-right transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-extrabold text-slate-800 group-hover:text-indigo-700">{{ $service->name }}</span>
                                    <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" dir="ltr">{{ $service->code }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-500">
                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-indigo-600">{{ Str::limit($service->categories_count . ' دسته‌بندی', 30) }}</span>
                                    <span>{{ \App\Models\Service::TYPE_OPTIONS[$service->service_type] ?? $service->service_type }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            {{-- Step 2: Scan + assign --}}
            <div
                x-data="idCardScanner({
                    resolveScan: (payload) => $wire.resolveScannedQr(payload),
                    successSoundUrl: '/sounds/scan-card.wav',
                    enableResultBanner: false,
                    autoStart: false,
                })"
                x-init="init()"
                x-on:id-card-scanner-resume.window="resumeFromWire()"
                class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
            >
                {{-- Left: scanner + identity --}}
                <div class="flex min-h-0 flex-col gap-4">
                    <div class="relative h-[clamp(260px,42svh,420px)] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
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
                            class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            اسکن نفر بعدی
                        </button>
                    </div>

                    <div class="rounded-2xl border px-4 py-3 text-sm font-semibold
                        @class([
                            'border-emerald-200 bg-emerald-50 text-emerald-700' => $scanStatus === 'paused',
                            'border-rose-200 bg-rose-50 text-rose-700' => $scanStatus === 'scan_error',
                            'border-slate-200 bg-slate-50 text-slate-600' => ! in_array($scanStatus, ['paused', 'scan_error'], true),
                        ])">
                        {{ $scanMessage }}
                    </div>

                    {{-- Identity card --}}
                    @if($lastScanResult)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold
                                    {{ ($lastScanResult['type'] ?? null) === \App\Models\QrIdentity::SUBJECT_PERSON ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $lastScanResult['title'] ?? '-' }}
                                </span>
                            </div>
                            <p class="mt-3 text-lg font-black text-slate-900">{{ $lastScanResult['name'] ?? '-' }}</p>
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
                        </div>
                    @endif
                </div>

                {{-- Right: categories --}}
                <div class="flex min-h-0 flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-extrabold text-slate-800">دسته‌بندی‌های خدمت</h2>
                        @if($lastScanResult)
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold text-indigo-600">
                                {{ count($assignedCategoryIds) }} انتخاب‌شده
                            </span>
                        @endif
                    </div>

                    @if(! $lastScanResult)
                        <div class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                            <p class="text-sm font-bold text-slate-600">برای تخصیص دسته‌بندی، ابتدا QR فرد را اسکن کنید.</p>
                        </div>
                    @elseif($selectedService->categories->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                            <p class="text-sm font-bold text-slate-600">برای این خدمت دسته‌بندی‌ای تعریف نشده است.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($selectedService->categories as $category)
                                @php($isChecked = in_array($category->id, $assignedCategoryIds, true))
                                <button
                                    type="button"
                                    wire:click="toggleCategory({{ $category->id }})"
                                    wire:key="entry-gate-category-{{ $category->id }}"
                                    @class([
                                        'flex w-full items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-right transition',
                                        'border-indigo-300 bg-indigo-50' => $isChecked,
                                        'border-slate-200 bg-white hover:border-indigo-200 hover:bg-slate-50' => ! $isChecked,
                                    ])
                                >
                                    <span class="flex items-center gap-3">
                                        <span @class([
                                            'flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition',
                                            'border-indigo-600 bg-indigo-600 text-white' => $isChecked,
                                            'border-slate-300 bg-white' => ! $isChecked,
                                        ])>
                                            @if($isChecked)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </span>
                                        <span class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800">{{ $category->name }}</span>
                                            <span class="text-[11px] font-semibold text-slate-400" dir="ltr">{{ $category->code }}</span>
                                        </span>
                                    </span>
                                    @if($category->unit)
                                        <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $category->unit }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
