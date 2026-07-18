<div class="space-y-4" dir="rtl">
    {{-- Header --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 sm:text-2xl">تحویل مستقیم خدمت به مددجو / سرپرست</h1>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        تحویل نهایی بدون مددکار
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">خدمت انتخاب‌شده به‌صورت مستقیم و نهایی برای گیرنده ثبت می‌شود؛ بدون تخصیص سهمیه به مددکار اجتماعی.</p>
            </div>
            <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>

        {{-- Stepper --}}
        @php
            $steps = [
                1 => 'انتخاب خدمت',
                2 => 'انتخاب گیرنده',
                3 => 'اقلام و مقادیر',
                4 => 'بازبینی و تأیید',
            ];
        @endphp
        <ol class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="مراحل تحویل مستقیم خدمت">
            @foreach($steps as $number => $title)
                @php
                    $isDone = $step > $number || $successState;
                    $isCurrent = $step === $number && ! $successState;
                @endphp
                <li
                    class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition {{ $isCurrent ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : ($isDone ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400') }}"
                    @if($isCurrent) aria-current="step" @endif
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] {{ $isCurrent ? 'bg-indigo-600 text-white' : ($isDone ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500') }}">
                        @if($isDone)
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            {{ $this->persianNumber($number) }}
                        @endif
                    </span>
                    <span class="truncate">{{ $title }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- ============================================================= --}}
    {{-- Success state --}}
    {{-- ============================================================= --}}
    @if($successState)
        <section
            x-data="deliveryReceipt(@js($successState['receipt']))"
            @keydown.escape.window="receiptOpen = false"
            class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm"
            aria-live="polite"
        >
            <div class="border-b border-emerald-100 bg-gradient-to-l from-white to-emerald-50/70 px-5 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">تحویل مستقیم با موفقیت ثبت شد</h2>
                            <p class="mt-0.5 text-xs text-slate-500">تحویل به‌صورت نهایی در سامانه ثبت شد و در گزارش‌ها قابل مشاهده است.</p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-dashed border-emerald-300 bg-white px-4 py-2 text-center">
                        <p class="text-[10px] font-bold text-slate-400">کد پیگیری</p>
                        <p class="mt-0.5 font-mono text-sm font-black tracking-wider text-emerald-700" dir="ltr">{{ $successState['tracking_code'] }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 px-5 py-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-xs">
                    <p class="mb-3 text-sm font-black text-slate-700">مشخصات تحویل</p>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                        <div>
                            <dt class="text-slate-400">خدمت</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $successState['service_name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">کد خدمت</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $successState['service_code'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">نوع تحویل</dt>
                            <dd class="mt-0.5 font-bold text-emerald-700">تحویل مستقیم نهایی</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">تاریخ تحویل</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $successState['delivered_at'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">گیرنده ({{ $successState['recipient']['type_label'] ?? '-' }})</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $successState['recipient']['name'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">کد ملی</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $successState['recipient']['national_id'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">اپراتور تحویل</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $successState['operator_name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">جمع ارزش</dt>
                            <dd class="mt-0.5 font-bold text-emerald-700">{{ number_format($successState['total_value']) }} ریال</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="mb-3 text-sm font-black text-slate-700">اقلام تحویل‌شده</p>
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-slate-500">
                                    <th class="px-3 py-2 text-right font-bold">دسته‌بندی</th>
                                    <th class="px-3 py-2 text-center font-bold">مقدار</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($successState['items'] as $item)
                                    <tr class="bg-white">
                                        <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ $item['category'] }}</td>
                                        <td class="px-3 py-2 text-center font-bold text-slate-900">{{ $item['quantity'] }} {{ $item['unitLabel'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2">
                        <span class="text-[11px] font-bold text-slate-500">جمع مقدار</span>
                        <div class="flex flex-wrap items-center gap-1">
                            @foreach($successState['unit_totals'] as $unitTotal)
                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                    <span class="text-slate-500">{{ $unitTotal['label'] }}:</span>
                                    <span class="text-slate-900">{{ $unitTotal['total'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 bg-slate-50/60 px-5 py-4">
                <button
                    type="button"
                    wire:click="startNewDelivery"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    ثبت تحویل جدید
                </button>
                <button
                    type="button"
                    @click="receiptOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    aria-haspopup="dialog"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    رسید تحویل
                </button>
                <button
                    type="button"
                    wire:click="changeService"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                >
                    انتخاب خدمت دیگر
                </button>
            </div>

            {{-- Receipt modal (canvas-exportable, same system as service reports) --}}
            <template x-teleport="body">
                <div
                    x-show="receiptOpen"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4 py-6 backdrop-blur-sm"
                    style="display: none;"
                >
                    <div
                        @click.outside="receiptOpen = false"
                        role="dialog"
                        aria-modal="true"
                        aria-label="رسید تحویل خدمت"
                        class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white text-right text-slate-800 shadow-2xl"
                    >
                        <div class="flex items-start justify-between gap-3 border-b border-dashed border-slate-300 bg-slate-50 px-5 py-4">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-600">رسید تحویل خدمت</p>
                                <h3 class="mt-1 truncate text-lg font-black text-slate-900">{{ $successState['receipt']['serviceName'] }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500">کد خدمت: {{ $successState['receipt']['serviceCode'] ?: '-' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="downloadImage()"
                                    :disabled="downloading"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    aria-label="دانلود تصویر رسید"
                                >
                                    <svg x-show="!downloading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                    </svg>
                                    <svg x-show="downloading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="downloading ? 'در حال ساخت…' : 'دانلود تصویر'"></span>
                                </button>
                                <button type="button" @click="receiptOpen = false" class="rounded-full border border-slate-200 bg-white p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="بستن">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18" /></svg>
                                </button>
                            </div>
                        </div>

                        <p x-show="downloadError" x-cloak class="border-b border-rose-100 bg-rose-50 px-5 py-2 text-xs font-semibold text-rose-700" x-text="downloadError"></p>

                        <div class="flex-1 overflow-y-auto px-5 py-4">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-xs">
                                <div>
                                    <p class="text-slate-400">نوع گیرنده</p>
                                    <p class="mt-0.5 font-bold text-slate-800">{{ $successState['receipt']['recipientType'] }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">نام گیرنده</p>
                                    <p class="mt-0.5 font-bold text-slate-800">{{ $successState['receipt']['recipientName'] }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">کد ملی</p>
                                    <p class="mt-0.5 font-bold text-slate-800">{{ $successState['receipt']['nationalId'] }}</p>
                                </div>
                                @if($successState['receipt']['recipientCode'] !== null)
                                    <div>
                                        <p class="text-slate-400">{{ $successState['receipt']['recipientCodeLabel'] }}</p>
                                        <p class="mt-0.5 font-bold text-slate-800">{{ $successState['receipt']['recipientCode'] }}</p>
                                    </div>
                                @endif
                                <div class="col-span-2">
                                    <p class="text-slate-400">اطلاعات پیگیری</p>
                                    <p class="mt-0.5 font-bold text-slate-800" dir="ltr">{{ $successState['tracking_code'] }}</p>
                                </div>
                                @if($successState['receipt']['mobile'])
                                    <div>
                                        <p class="text-slate-400">موبایل</p>
                                        <p class="mt-0.5 font-bold text-slate-800">{{ $successState['receipt']['mobile'] }}</p>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-slate-400">تاریخ تحویل</p>
                                    <p class="mt-0.5 font-bold text-slate-800">{{ $successState['receipt']['date'] }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="mb-2 text-xs font-black text-slate-700">اقلام تحویل‌شده</p>
                                <div class="overflow-hidden rounded-2xl border border-slate-200">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="bg-slate-100 text-slate-500">
                                                <th class="px-3 py-2 text-right font-bold">دسته‌بندی</th>
                                                <th class="px-3 py-2 text-center font-bold">مقدار</th>
                                                <th class="px-3 py-2 text-center font-bold">تاریخ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($successState['receipt']['items'] as $item)
                                                <tr class="bg-white">
                                                    <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ $item['category'] }}</td>
                                                    <td class="px-3 py-2 text-center font-bold text-slate-900">{{ $item['quantity'] }} {{ $item['unitLabel'] }}</td>
                                                    <td class="px-3 py-2 text-center text-slate-500">{{ $item['date'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3">
                                <span class="text-xs font-bold text-slate-500">جمع مقدار</span>
                                <div class="flex flex-wrap items-center justify-end gap-1">
                                    @foreach($successState['receipt']['unitTotals'] as $unitTotal)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                            <span class="text-slate-500">{{ $unitTotal['label'] }}:</span>
                                            <span class="text-slate-900">{{ $unitTotal['total'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 px-5 py-3 text-left">
                            <button type="button" @click="receiptOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100">بستن</button>
                        </div>
                    </div>
                </div>
            </template>
        </section>
    @else
        {{-- ============================================================= --}}
        {{-- Step 1: service selection --}}
        {{-- ============================================================= --}}
        @if($step === 1)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-800">انتخاب خدمت</h2>
                        <p class="mt-1 text-xs text-slate-500">همه خدمات ثبت‌شده و قابل تحویل — بدون محدودیت سازنده (مدیر یا اپراتور توزیع)</p>
                    </div>
                    <div class="relative w-full sm:max-w-xs">
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <label for="direct-delivery-service-search" class="sr-only">جستجوی خدمت</label>
                        <input
                            id="direct-delivery-service-search"
                            type="text"
                            wire:model.live.debounce.300ms="serviceSearch"
                            placeholder="جستجو بر اساس نام، کد یا دسته‌بندی خدمت"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-9 pl-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                        >
                    </div>
                </div>

                <div class="mt-4" wire:loading.class="opacity-50" wire:target="serviceSearch">
                    @if($deliverableServices->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-sm text-slate-500">
                            {{ trim($serviceSearch) !== '' ? 'خدمتی مطابق جستجوی فعلی پیدا نشد.' : 'در حال حاضر خدمت قابل تحویلی ثبت نشده است.' }}
                        </div>
                    @else
                        <ul class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3" role="list">
                            @foreach($deliverableServices as $service)
                                @php
                                    $typeMeta = \App\Models\Service::typeDisplayMeta($service->service_type);
                                    $isFamily = $service->service_type === 'family';
                                @endphp
                                <li>
                                    <button
                                        type="button"
                                        wire:click="selectService({{ $service->id }})"
                                        class="group flex w-full flex-col rounded-2xl border border-slate-200 bg-white p-4 text-right shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                        aria-label="انتخاب خدمت {{ $service->serviceName?->name ?: $service->name }}"
                                    >
                                        <div class="flex w-full items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-black text-slate-900">{{ $service->serviceName?->name ?: ($service->name ?: '-') }}</p>
                                                <p class="mt-1 text-[11px] text-slate-500">کد: {{ $service->code }} · {{ $this->persianNumber((string) $service->categories_count) }} دسته‌بندی</p>
                                            </div>
                                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $isFamily ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200' }}">
                                                {{ $typeMeta['label'] }}
                                            </span>
                                        </div>
                                        <div class="mt-3 flex w-full items-center justify-between text-[11px]">
                                            <span class="text-slate-400">
                                                گیرنده: {{ $isFamily ? 'سرپرست خانوار' : 'مددجو' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 font-bold text-indigo-600 transition group-hover:gap-2">
                                                انتخاب
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                                            </span>
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endif

        {{-- Selected service summary bar (steps 2+) --}}
        @if($step >= 2 && $selectedService)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-900">{{ $selectedService->serviceName?->name ?: ($selectedService->name ?: '-') }}</p>
                        <p class="text-[11px] text-slate-500">کد: {{ $selectedService->code }} · نوع: {{ $this->serviceTypeLabel }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="changeService"
                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-[11px] font-bold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                >
                    تغییر خدمت
                </button>
            </div>
        @endif

        {{-- ============================================================= --}}
        {{-- Step 2: recipient --}}
        {{-- ============================================================= --}}
        @if($step === 2 && $selectedService)
            @php $isFamily = $selectedService->service_type === 'family'; @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-black text-slate-800">
                    انتخاب گیرنده — {{ $isFamily ? 'سرپرست خانوار (خدمت خانوادگی)' : 'مددجو (خدمت شخصی)' }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $isFamily
                        ? 'این خدمت خانوادگی است و باید به سرپرست خانوار تحویل شود.'
                        : 'این خدمت شخصی است و باید به خود مددجو تحویل شود.' }}
                </p>

                @if(! $manualEntry)
                    <div class="relative mt-4 w-full sm:max-w-md">
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <label for="direct-delivery-recipient-search" class="sr-only">جستجوی گیرنده</label>
                        <input
                            id="direct-delivery-recipient-search"
                            type="text"
                            wire:model.live.debounce.300ms="recipientSearch"
                            placeholder="{{ $isFamily ? 'نام، کد ملی، کد خانوار یا موبایل سرپرست' : 'نام، کد ملی، کد مددجو یا موبایل مددجو' }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pr-9 pl-3 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"
                            autocomplete="off"
                        >
                        <span wire:loading wire:target="recipientSearch" class="absolute left-3 top-1/2 -translate-y-1/2">
                            <svg class="h-4 w-4 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="mt-4 space-y-2" aria-live="polite">
                        @if(mb_strlen(trim($recipientSearch)) >= 2)
                            @forelse($recipientCandidates as $candidate)
                                <button
                                    type="button"
                                    wire:click="selectRecipient({{ $candidate['id'] }})"
                                    class="flex w-full flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-right transition hover:border-indigo-200 hover:bg-indigo-50/40 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-900">{{ $candidate['name'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">
                                            کد ملی: {{ $candidate['national_id'] }} · {{ $candidate['code_label'] }}: {{ $candidate['code'] }}
                                            @if(! empty($candidate['meta'])) · {{ $candidate['meta'] }} @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600">
                                        انتخاب گیرنده
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                                    </span>
                                </button>
                            @empty
                                <div wire:loading.remove wire:target="recipientSearch" class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 px-4 py-6 text-center">
                                    <p class="text-sm font-bold text-amber-800">گیرنده‌ای با این مشخصات پیدا نشد.</p>
                                    <p class="mt-1 text-xs text-amber-700">در صورت اطمینان، می‌توانید مشخصات گیرنده را به‌صورت دستی ثبت کنید.</p>
                                    <button
                                        type="button"
                                        wire:click="startManualEntry"
                                        class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-200"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        ثبت دستی گیرنده
                                    </button>
                                </div>
                            @endforelse
                        @else
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-xs text-slate-500">
                                برای جستجو حداقل ۲ کاراکتر وارد کنید. جستجو با حروف فارسی و ارقام فارسی/عربی نیز کار می‌کند.
                            </div>
                            <div class="text-center">
                                <button
                                    type="button"
                                    wire:click="startManualEntry"
                                    class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-slate-500 underline-offset-4 transition hover:text-indigo-600 hover:underline focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                >
                                    گیرنده در سامانه ثبت نشده است؟ ثبت دستی
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Manual entry form --}}
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-black text-amber-800">ثبت دستی {{ $isFamily ? 'سرپرست' : 'مددجو' }}</p>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200">تحویل به گیرنده ثبت‌نشده</span>
                        </div>
                        <p class="mt-1 text-[11px] text-amber-700">اگر کد ملی واردشده متعلق به فرد ثبت‌شده‌ای باشد، تحویل به‌صورت خودکار به همان پرونده متصل می‌شود.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div>
                                <label for="manual-national-id" class="mb-1 block text-xs font-bold text-slate-600">کد ملی <span class="text-rose-500">*</span></label>
                                <input
                                    id="manual-national-id"
                                    type="text"
                                    inputmode="numeric"
                                    wire:model.defer="manualNationalId"
                                    maxlength="10"
                                    dir="ltr"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @error('manualNationalId') border-rose-400 @enderror"
                                    placeholder="0000000000"
                                >
                                @error('manualNationalId') <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="manual-full-name" class="mb-1 block text-xs font-bold text-slate-600">نام و نام خانوادگی <span class="text-rose-500">*</span></label>
                                <input
                                    id="manual-full-name"
                                    type="text"
                                    wire:model.defer="manualFullName"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @error('manualFullName') border-rose-400 @enderror"
                                    placeholder="{{ $isFamily ? 'نام سرپرست خانوار' : 'نام مددجو' }}"
                                >
                                @error('manualFullName') <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="manual-mobile" class="mb-1 block text-xs font-bold text-slate-600">موبایل (اختیاری)</label>
                                <input
                                    id="manual-mobile"
                                    type="text"
                                    inputmode="numeric"
                                    wire:model.defer="manualMobile"
                                    maxlength="11"
                                    dir="ltr"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @error('manualMobile') border-rose-400 @enderror"
                                    placeholder="09xxxxxxxxx"
                                >
                                @error('manualMobile') <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="confirmManualEntry"
                                wire:loading.attr="disabled"
                                wire:target="confirmManualEntry"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                تأیید گیرنده و ادامه
                            </button>
                            <button
                                type="button"
                                wire:click="cancelManualEntry"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                            >
                                بازگشت به جستجو
                            </button>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- ============================================================= --}}
        {{-- Recipient summary bar (steps 3+) --}}
        {{-- ============================================================= --}}
        @if($step >= 3 && $recipientSummary !== [])
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border {{ ($recipientSummary['is_manual'] ?? false) ? 'border-amber-200 bg-amber-50/60' : 'border-emerald-100 bg-emerald-50/60' }} px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ ($recipientSummary['is_manual'] ?? false) ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-900">
                            {{ $recipientSummary['name'] ?? '-' }}
                            <span class="mr-1 text-[11px] font-bold {{ ($recipientSummary['is_manual'] ?? false) ? 'text-amber-700' : 'text-emerald-700' }}">({{ $recipientSummary['type_label'] ?? '-' }})</span>
                        </p>
                        <p class="text-[11px] text-slate-500">
                            کد ملی: {{ $recipientSummary['national_id'] ?? '-' }}
                            @if(($recipientSummary['code'] ?? '-') !== '-') · {{ $recipientSummary['code_label'] }}: {{ $recipientSummary['code'] }} @endif
                            @if(! empty($recipientSummary['meta'])) · {{ $recipientSummary['meta'] }} @endif
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="changeRecipient"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                >
                    تغییر گیرنده
                </button>
            </div>
        @endif

        {{-- ============================================================= --}}
        {{-- Step 3: items and quantities --}}
        {{-- ============================================================= --}}
        @if($step === 3 && $selectedService)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-black text-slate-800">اقلام و مقادیر تحویل</h2>
                <p class="mt-1 text-xs text-slate-500">برای هر دسته‌بندی مقدار تحویل را وارد کنید. حداقل یک مورد الزامی است.</p>

                <div class="mt-4 space-y-3">
                    @foreach($selectedService->categories as $category)
                        @php
                            $metric = $categoryMetrics[$category->id] ?? ['remaining_stock' => 0];
                            $unitLabel = $category->unit ? ($unitOptions[$category->unit] ?? $category->unit) : '-';
                            $usesDecimals = \App\Models\Service::unitUsesDecimalPrecision($category->unit);
                            $remaining = \App\Models\Service::formatQuantityForUnit($metric['remaining_stock'], $category->unit);
                            $outOfStock = (float) $metric['remaining_stock'] <= 0;
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-4 py-3 {{ $outOfStock ? 'border-slate-200 bg-slate-50 opacity-60' : 'border-slate-200 bg-white' }}">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-900">{{ $category->name }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    واحد: {{ $unitLabel }} · موجودی باقی‌مانده: <span class="font-bold {{ $outOfStock ? 'text-rose-500' : 'text-emerald-600' }}">{{ $remaining }} {{ $unitLabel }}</span>
                                </p>
                            </div>
                            <div class="w-32">
                                <label for="category-quantity-{{ $category->id }}" class="sr-only">مقدار {{ $category->name }}</label>
                                <input
                                    id="category-quantity-{{ $category->id }}"
                                    type="text"
                                    inputmode="decimal"
                                    dir="ltr"
                                    wire:model.defer="categoryQuantities.{{ $category->id }}"
                                    @if($outOfStock) disabled @endif
                                    placeholder="{{ $usesDecimals ? '0.00' : '0' }}"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-bold text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-100 @error('categoryQuantities.'.$category->id) border-rose-400 @enderror"
                                >
                                @error('categoryQuantities.'.$category->id)
                                    <p class="mt-1 text-[10px] font-bold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="direct-delivered-at" class="mb-1 block text-xs font-bold text-slate-600">تاریخ تحویل (شمسی)</label>
                        <input
                            id="direct-delivered-at"
                            type="text"
                            dir="ltr"
                            wire:model.defer="deliveredAt"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-bold text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @error('deliveredAt') border-rose-400 @enderror"
                            placeholder="1405/04/27"
                        >
                        @error('deliveredAt') <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="direct-delivery-notes" class="mb-1 block text-xs font-bold text-slate-600">یادداشت (اختیاری)</label>
                        <input
                            id="direct-delivery-notes"
                            type="text"
                            wire:model.defer="notes"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            placeholder="توضیح کوتاه درباره این تحویل"
                        >
                        @error('notes') <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        wire:click="changeRecipient"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                        مرحله قبل
                    </button>
                    <button
                        type="button"
                        wire:click="goToReview"
                        wire:loading.attr="disabled"
                        wire:target="goToReview"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        بازبینی نهایی
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                    </button>
                </div>
            </section>
        @endif

        {{-- ============================================================= --}}
        {{-- Step 4: review and confirm --}}
        {{-- ============================================================= --}}
        @if($step === 4 && $selectedService)
            @php
                $reviewQuantities = collect($categoryQuantities)
                    ->map(fn ($quantity) => (float) \App\Helpers\PersianText::normalizeDigits((string) $quantity))
                    ->filter(fn ($quantity) => $quantity > 0);
                $reviewCategories = $selectedService->categories->keyBy('id');
                $reviewTotalValue = $reviewQuantities
                    ->map(fn ($quantity, $categoryId) => $quantity * (float) ($reviewCategories->get((int) $categoryId)?->value ?? 0))
                    ->sum();
            @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-black text-slate-800">بازبینی نهایی تحویل</h2>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200">تحویل مستقیم نهایی — بدون مددکار اجتماعی</span>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-xs">
                        <div>
                            <dt class="text-slate-400">خدمت</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $selectedService->serviceName?->name ?: ($selectedService->name ?: '-') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">کد خدمت</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $selectedService->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">نوع خدمت</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $this->serviceTypeLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">نوع تحویل</dt>
                            <dd class="mt-0.5 font-bold text-emerald-700">تحویل مستقیم نهایی</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">گیرنده ({{ $recipientSummary['type_label'] ?? '-' }})</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $recipientSummary['name'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">کد ملی</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $recipientSummary['national_id'] ?? '-' }}</dd>
                        </div>
                        @if(! empty($recipientSummary['mobile']))
                            <div>
                                <dt class="text-slate-400">موبایل</dt>
                                <dd class="mt-0.5 font-bold text-slate-800" dir="ltr">{{ $recipientSummary['mobile'] }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-slate-400">تاریخ تحویل</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ $deliveredAt }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">اپراتور تحویل</dt>
                            <dd class="mt-0.5 font-bold text-slate-800">{{ trim((auth()->user()->first_name ?? '').' '.(auth()->user()->last_name ?? '')) ?: (auth()->user()->name ?? '-') }}</dd>
                        </div>
                        @if(trim($notes) !== '')
                            <div class="col-span-2">
                                <dt class="text-slate-400">یادداشت</dt>
                                <dd class="mt-0.5 font-bold text-slate-800">{{ $notes }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="mb-3 text-sm font-black text-slate-700">اقلام انتخاب‌شده</p>
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-500">
                                        <th class="px-3 py-2 text-right font-bold">دسته‌بندی</th>
                                        <th class="px-3 py-2 text-center font-bold">مقدار</th>
                                        <th class="px-3 py-2 text-center font-bold">ارزش</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($reviewQuantities as $categoryId => $quantity)
                                        @php
                                            $category = $reviewCategories->get((int) $categoryId);
                                            $unitKey = $category?->unit;
                                            $unitLabel = $unitKey ? ($unitOptions[$unitKey] ?? $unitKey) : '-';
                                        @endphp
                                        <tr class="bg-white">
                                            <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ $category?->name ?: '-' }}</td>
                                            <td class="px-3 py-2 text-center font-bold text-slate-900">
                                                {{ \App\Models\Service::formatQuantityForUnit($quantity, $unitKey) }} {{ $unitLabel }}
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-emerald-600">
                                                {{ number_format((int) round($quantity * (float) ($category?->value ?? 0))) }} ریال
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 flex items-center justify-between rounded-xl border border-dashed border-emerald-300 bg-emerald-50/60 px-3 py-2 text-xs">
                            <span class="font-bold text-slate-600">جمع ارزش تحویل</span>
                            <span class="font-black text-emerald-700">{{ number_format((int) round($reviewTotalValue)) }} ریال</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        wire:click="backToItems"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                        ویرایش اقلام
                    </button>
                    <button
                        type="button"
                        wire:click="confirmDelivery"
                        wire:loading.attr="disabled"
                        wire:target="confirmDelivery"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg wire:loading.remove wire:target="confirmDelivery" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <svg wire:loading wire:target="confirmDelivery" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="confirmDelivery">تأیید و ثبت نهایی تحویل</span>
                        <span wire:loading wire:target="confirmDelivery">در حال ثبت…</span>
                    </button>
                </div>
            </section>
        @endif
    @endif
</div>
