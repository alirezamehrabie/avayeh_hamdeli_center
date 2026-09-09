<div class="pb-6">
    {{-- Header --}}
    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-l from-slate-900 via-slate-800 to-indigo-900 px-5 py-6 text-white shadow-sm sm:px-7">
        <div>
            <button
                type="button"
                wire:click="backToServiceReport"
                class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                بازگشت به گزارش خدمات
            </button>
            <h1 class="mt-3 text-xl font-extrabold sm:text-2xl">{{ $service->serviceName?->name ?: $service->name }}</h1>
            <p class="mt-2 text-sm text-slate-200">{{ $service->code }} · گزارش فنی ایستگاه توزیع</p>

            @if($activeTab === \App\Livewire\Admin\GateTechnicalReport::TAB_GATE)
                <div class="mt-5 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 font-bold ring-1 ring-white/15">
                        {{ number_format($stats['authorized']) }} مجوز ثبت‌شده
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/20 px-3 py-1.5 font-bold text-amber-100 ring-1 ring-amber-300/30">
                        {{ number_format($stats['pending']) }} در انتظار تحویل
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-400/20 px-3 py-1.5 font-bold text-indigo-100 ring-1 ring-indigo-300/30">
                        {{ number_format($stats['delivered']) }} تحویل‌شده
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/20 px-3 py-1.5 font-bold text-emerald-100 ring-1 ring-emerald-300/30">
                        {{ number_format($stats['finalized']) }} خروج قطعی‌شده
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-bold ring-1 {{ $stats['discrepancies'] > 0 ? 'bg-rose-500/25 text-rose-100 ring-rose-300/40' : 'bg-emerald-500/20 text-emerald-100 ring-emerald-300/30' }}">
                        {{ $stats['discrepancies'] > 0 ? number_format($stats['discrepancies']).' مغایرت' : 'بدون مغایرت' }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabs (tab 2 will join this bar via GateTechnicalReport::TABS) --}}
    <div class="mt-5">
        <div class="flex items-center gap-2 border-b border-slate-200">
            @foreach($tabs as $tabKey => $tabLabel)
                <button
                    type="button"
                    wire:click="setTab('{{ $tabKey }}')"
                    class="-mb-px rounded-t-xl border-b-2 px-4 py-2.5 text-sm font-bold transition {{ $activeTab === $tabKey ? 'border-indigo-500 bg-white text-indigo-700' : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700' }}"
                >
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>
    </div>

    @if($activeTab === \App\Livewire\Admin\GateTechnicalReport::TAB_GATE)
        <div class="mt-5 space-y-6">
            {{-- Section 1: authorized at Entry, not delivered at Delivery --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" id="pending-section">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">۱. مجوز ثبت‌شده در ورود، تحویل‌نشده در تحویل</h2>
                        <p class="mt-0.5 text-xs text-slate-500">دسته‌بندی‌هایی که در گیت ورود مجوز گرفته‌اند اما هنوز در گیت تحویل تأیید نشده‌اند.</p>
                        @if($pendingTotal > 0)
                            <p class="mt-1 text-[11px] font-bold text-slate-400">
                                نمایش {{ number_format($pendingRows->firstItem() ?? 0) }}–{{ number_format($pendingRows->lastItem() ?? 0) }} از {{ number_format($pendingTotal) }} · صفحهٔ {{ $pendingRows->currentPage() }} از {{ $pendingRows->lastPage() }}
                            </p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-200">
                        {{ number_format($pendingTotal) }} مورد
                    </span>
                </div>

                @if($pendingTotal === 0)
                    <p class="px-5 py-8 text-center text-sm text-slate-400">همه مجوزهای ثبت‌شده تحویل تأییدشده دارند.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-white text-slate-500">
                                    <th class="px-4 py-3 text-right text-xs font-bold">گیرنده</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold">دسته‌بندی</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold">زمان ثبت مجوز (ورود)</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold">ثبت‌کننده در ورود</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold">وضعیت فعلی</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($pendingRows as $assignment)
                                    <tr class="align-top transition hover:bg-slate-50/80">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900">{{ $assignment->recipient_name }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ $assignment->national_id ?: '—' }}
                                                @if($assignment->person?->person_code)
                                                    · کد مددجو: {{ $assignment->person->person_code }}
                                                @elseif($assignment->guardian?->guardian_code)
                                                    · کد خانوار: {{ $assignment->guardian->guardian_code }}
                                                @endif
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $assignment->serviceCategory?->name ?: '—' }}</td>
                                        <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $jalaliDateTime($assignment->assigned_at) }}</td>
                                        <td class="px-4 py-3 text-center text-xs text-slate-600">
                                            {{ trim((string) ($assignment->creator?->first_name.' '.$assignment->creator?->last_name)) ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">{{ $statusLabels[$assignment->status] ?? $assignment->status }}</span>
                                            @if(in_array($assignment->id, $cancelledAssignmentIds, true))
                                                <span class="mt-1 inline-flex whitespace-nowrap rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-600 ring-1 ring-rose-200" title="این مورد قبلاً در گیت خروج لغو شده و دوباره در انتظار تحویل است.">سابقه لغو در خروج</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $pendingRows->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#pending-section']) }}
                    </div>
                @endif
            </section>

            {{-- Section 2: authorized at Entry AND delivered at Delivery --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" id="delivered-section">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">۲. مجوز ثبت‌شده در ورود، تحویل‌شده در تحویل</h2>
                        <p class="mt-0.5 text-xs text-slate-500">تحویل‌های تأییدشده در گیت تحویل (خروج قطعی‌شده ابتدا فهرست می‌شود)، به‌همراه زمان ثبت در دفترچه خروج.</p>
                        @if($deliveredTotal > 0)
                            <p class="mt-1 text-[11px] font-bold text-slate-400">
                                نمایش {{ number_format($deliveredRows->firstItem() ?? 0) }}–{{ number_format($deliveredRows->lastItem() ?? 0) }} از {{ number_format($deliveredTotal) }} · صفحهٔ {{ $deliveredRows->currentPage() }} از {{ $deliveredRows->lastPage() }}
                            </p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-200">
                        {{ number_format($deliveredTotal) }} مورد
                    </span>
                </div>

                @if($deliveredTotal === 0)
                    <p class="px-5 py-8 text-center text-sm text-slate-400">هنوز هیچ مجوزی در گیت تحویل تأیید نشده است.</p>
                @else
                    @include('livewire.admin.partials.gate-assignment-table', [
                        'rows' => $deliveredRows,
                        'showLedger' => true,
                    ])

                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $deliveredRows->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#delivered-section']) }}
                    </div>
                @endif
            </section>

            {{-- Section 3: Entry -> Delivery -> Exit reconciliation checks --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" id="checks-section">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h2 class="text-sm font-black text-slate-900">۳. بررسی سلامت گردش‌کار ورود ← تحویل ← خروج</h2>
                    <p class="mt-0.5 text-xs text-slate-500">مغایرت‌های وضعیت مجوزها با دفترچه تحویل و با آرشیو لغوها.</p>
                </div>

                @if($stats['discrepancies'] === 0)
                    <div class="flex items-center gap-2 px-5 py-6 text-sm font-bold text-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        هیچ مغایرتی بین سه مرحله گیت یافت نشد.
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($checks as $check)
                            @continue($check['count'] === 0)
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-rose-100 px-1.5 text-xs font-black text-rose-700">{{ number_format($check['count']) }}</span>
                                        <h3 class="text-sm font-extrabold text-slate-900">{{ $check['title'] }}</h3>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $check['hint'] }}</p>
                                <p class="mt-1 text-[11px] font-bold text-slate-400">
                                    نمایش {{ number_format($check['rows']->firstItem() ?? 0) }}–{{ number_format($check['rows']->lastItem() ?? 0) }} از {{ number_format($check['count']) }} · صفحهٔ {{ $check['rows']->currentPage() }} از {{ $check['rows']->lastPage() }}
                                </p>

                                @if($check['kind'] === 'assignment')
                                    <div class="mt-3 rounded-xl border border-rose-100">
                                        @include('livewire.admin.partials.gate-assignment-table', [
                                            'rows' => $check['rows'],
                                            'showLedger' => $check['showLedger'] ?? false,
                                        ])
                                    </div>
                                    <div class="mt-2">
                                        {{ $check['rows']->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#checks-section']) }}
                                    </div>
                                @else
                                    <div class="mt-3 overflow-x-auto rounded-xl border border-rose-100">
                                        <table class="w-full min-w-[560px] text-sm">
                                            <thead>
                                                <tr class="border-b border-slate-200 bg-slate-50 text-slate-500">
                                                    <th class="px-4 py-3 text-right text-xs font-bold">گیرنده</th>
                                                    <th class="px-4 py-3 text-right text-xs font-bold">دسته‌بندی</th>
                                                    <th class="px-4 py-3 text-center text-xs font-bold">مقدار تحویل</th>
                                                    <th class="px-4 py-3 text-center text-xs font-bold">زمان ثبت در دفترچه</th>
                                                    <th class="px-4 py-3 text-center text-xs font-bold">ثبت‌کننده</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($check['rows'] as $delivery)
                                                    <tr class="align-top transition hover:bg-slate-50/80">
                                                        <td class="px-4 py-3">
                                                            <p class="font-bold text-slate-900">{{ $delivery->recipient_name }}</p>
                                                            <p class="mt-0.5 text-xs text-slate-500">{{ $delivery->national_id ?: '—' }}</p>
                                                        </td>
                                                        <td class="px-4 py-3 text-slate-700">{{ $delivery->serviceCategory?->name ?: '—' }}</td>
                                                        <td class="px-4 py-3 text-center font-bold text-slate-800">
                                                            {{ \App\Models\Service::formatQuantityForUnit($delivery->delivered_quantity, $delivery->serviceCategory?->unit ?: null) }}
                                                            @php $unitKey = $delivery->serviceCategory?->unit; @endphp
                                                            @if($unitKey)
                                                                <span class="text-xs font-medium text-slate-400">{{ (\App\Models\Service::unitOptions()[$unitKey] ?? $unitKey) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $jalaliDateTime($delivery->delivered_at ?? $delivery->created_at) }}</td>
                                                        <td class="px-4 py-3 text-center text-xs text-slate-600">
                                                            {{ trim((string) ($delivery->creator?->first_name.' '.$delivery->creator?->last_name)) ?: '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2">
                                        {{ $check['rows']->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#checks-section']) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Exit cancellations: informational audit trail, not an error --}}
                <div class="border-t border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-black text-amber-700">{{ number_format($cancelledCount) }}</span>
                        <h3 class="text-sm font-extrabold text-slate-900">لغوهای ثبت‌شده در گیت خروج</h3>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">تحویل‌هایی که پس از خروج قطعی در گیت خروج لغو و بایگانی شده‌اند (مجوزشان به «در انتظار تحویل» برگشته است).</p>

                    @if($cancelledCount > 0)
                        <p class="mt-1 text-[11px] font-bold text-slate-400">
                            نمایش {{ number_format($cancelledRows->firstItem() ?? 0) }}–{{ number_format($cancelledRows->lastItem() ?? 0) }} از {{ number_format($cancelledCount) }} · صفحهٔ {{ $cancelledRows->currentPage() }} از {{ $cancelledRows->lastPage() }}
                        </p>

                        <div class="mt-3 overflow-x-auto rounded-xl border border-amber-100">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500">
                                        <th class="px-4 py-3 text-right text-xs font-bold">گیرنده</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold">دسته‌بندی</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold">مقدار</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold">زمان تحویل اصلی</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold">لغوکننده</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold">زمان لغو</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($cancelledRows as $cancellation)
                                        <tr class="align-top transition hover:bg-slate-50/80">
                                            <td class="px-4 py-3">
                                                <p class="font-bold text-slate-900">{{ trim((string) ($cancellation->person ? $cancellation->person->first_name.' '.$cancellation->person->last_name : ($cancellation->guardian?->full_name ?: data_get($cancellation->delivery_snapshot, 'full_name') ?: '—'))) }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    @if($cancellation->person?->person_code)
                                                        کد مددجو: {{ $cancellation->person->person_code }}
                                                    @elseif($cancellation->guardian?->guardian_code)
                                                        کد خانوار: {{ $cancellation->guardian->guardian_code }}
                                                    @endif
                                                </p>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">{{ $cancellation->serviceCategory?->name ?: '—' }}</td>
                                            <td class="px-4 py-3 text-center font-bold text-slate-800">
                                                {{ \App\Models\Service::formatQuantityForUnit($cancellation->delivered_quantity, $cancellation->serviceCategory?->unit ?: null) }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $cancellation->delivered_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($cancellation->delivered_at)->format('Y/m/d') : '—' }}</td>
                                            <td class="px-4 py-3 text-center text-xs text-slate-600">
                                                {{ trim((string) ($cancellation->canceller?->first_name.' '.$cancellation->canceller?->last_name)) ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $jalaliDateTime($cancellation->canceled_at) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            {{ $cancelledRows->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#checks-section']) }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    @endif

    @if($activeTab === \App\Livewire\Admin\GateTechnicalReport::TAB_OPERATORS && $operatorReports !== null)
        <div class="mt-5 space-y-6">
            {{-- Summary strip --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach($operatorReports as $report)
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                        <p class="text-xs font-black text-slate-700">{{ $report['label'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-bold">
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-100">{{ number_format($report['totalRecords']) }} عملیات</span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-100">{{ $report['activeCount'] }} مسئول فعال</span>
                            @if($report['idleCount'] > 0)
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700 ring-1 ring-amber-100" title="مسئولانی که مجوز این گیت را دارند اما در این خدمت هیچ ثبتی ندارند.">{{ $report['idleCount'] }} بدون ثبت</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach($operatorReports as $gateKey => $report)
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-black text-slate-900">عملکرد مسئولان {{ $report['label'] }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                @if($gateKey === 'entry')
                                    مجوزهای ثبت‌شده در گیت ورود توسط این مسئولان (به تفکیک دسته‌بندی).
                                @elseif($gateKey === 'delivery')
                                    تحویل‌های تأییدشده در گیت تحویل (ستون «خروج قطعی‌شده» نشان می‌دهد چقدر از آن‌ها در گیت خروج نهایی شده است).
                                @else
                                    رکوردهای ثبت‌شده در دفترچه تحویل توسط گیت خروج + لغوهای بایگانی‌شدهٔ همین مسئول.
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700 ring-1 ring-indigo-200">
                            {{ count($report['operators']) }} مسئول
                        </span>
                    </div>

                    @if($report['operators'] === [])
                        <p class="px-5 py-8 text-center text-sm text-slate-400">هیچ مسئولی با مجوز این گیت یافت نشد.</p>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($report['operators'] as $index => $operator)
                                <div x-data="{ operatorOpen: false }">
                                    <button
                                        type="button"
                                        @click="operatorOpen = !operatorOpen"
                                        :aria-expanded="operatorOpen ? 'true' : 'false'"
                                        class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-right transition hover:bg-slate-50 focus:outline-none focus-visible:bg-slate-50 sm:px-5"
                                    >
                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-500">{{ \Illuminate\Support\Str::substr($operator['name'], 0, 1) }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-extrabold text-slate-900">{{ $operator['name'] }}</span>
                                                @if($operator['username'])
                                                    <span class="block truncate text-[11px] text-slate-400">{{ $operator['username'] }}</span>
                                                @endif
                                            </span>
                                            @unless($operator['authorized'])
                                                <span class="inline-flex shrink-0 items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-600 ring-1 ring-rose-200" title="این کاربر در حال حاضر مجوز این گیت را ندارد (ثبت‌های تاریخی با نام او وجود دارد).">بدون مجوز فعلی</span>
                                            @endunless
                                            @if($operator['authorized'] && $operator['totalRecords'] === 0)
                                                <span class="inline-flex shrink-0 items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">بدون ثبت در این خدمت</span>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2 text-[11px]">
                                            <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-600 sm:inline-flex">{{ number_format($operator['totalRecords']) }} عملیات</span>
                                            <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-600 md:inline-flex">{{ number_format($operator['subjects']) }} مددجو/خانوار</span>
                                            <span class="hidden text-slate-400 lg:inline">{{ $operator['lastAt'] ? 'آخرین فعالیت: '.$jalaliDateTime($operator['lastAt']) : '—' }}</span>
                                            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="operatorOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </button>

                                    <div x-show="operatorOpen" x-collapse x-cloak>
                                        <div class="border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-5">
                                            <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                                                <span class="rounded-full bg-white px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">کل عملیات: {{ number_format($operator['totalRecords']) }}</span>
                                                @if($gateKey === 'entry')
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-100">تأییدشده در تحویل: {{ number_format($operator['extraTotal']) }}</span>
                                                @elseif($gateKey === 'delivery')
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-100">خروج قطعی‌شده: {{ number_format($operator['extraTotal']) }}</span>
                                                @else
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">مقدار کل: {{ $operator['quantityTotal'] }}</span>
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-emerald-700 ring-1 ring-emerald-100">ارزش کل: {{ number_format($operator['valueTotal']) }} ریال</span>
                                                    @if($operator['cancelledTotal'] > 0)
                                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-600 ring-1 ring-rose-100">لغوهای خروج: {{ number_format($operator['cancelledTotal']) }}</span>
                                                    @endif
                                                @endif
                                            </div>

                                            @if($operator['categories']->isEmpty())
                                                <p class="py-4 text-center text-xs text-slate-400">هیچ رکورد ثبت‌شده‌ای در این خدمت ندارد.</p>
                                            @else
                                                <div class="mt-2 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                                    <table class="w-full min-w-[520px] text-sm">
                                                        <thead>
                                                            <tr class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500">
                                                                <th class="px-3 py-2.5 text-right font-bold">دسته‌بندی</th>
                                                                <th class="px-3 py-2.5 text-center font-bold">واحد</th>
                                                                <th class="px-3 py-2.5 text-center font-bold">تعداد ثبت</th>
                                                                @if($gateKey === 'exit')
                                                                    <th class="px-3 py-2.5 text-center font-bold">مقدار</th>
                                                                    <th class="px-3 py-2.5 text-center font-bold">ارزش (ریال)</th>
                                                                    <th class="px-3 py-2.5 text-center font-bold">لغوشده</th>
                                                                @else
                                                                    <th class="px-3 py-2.5 text-center font-bold">{{ $report['extraColumn'] }}</th>
                                                                @endif
                                                                <th class="px-3 py-2.5 text-center font-bold">آخرین فعالیت</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-100">
                                                            @foreach($operator['categories'] as $categoryRow)
                                                                <tr class="align-middle transition hover:bg-slate-50/80">
                                                                    <td class="px-3 py-2.5">
                                                                        <div class="flex items-center gap-2.5">
                                                                            <x-category-thumbnail :category="$categoryRow['categoryModel']" size-class="h-9 w-9" rounded-class="rounded-lg" />
                                                                            <span class="font-bold text-slate-800">{{ $categoryRow['category'] }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-3 py-2.5 text-center text-xs text-slate-500">{{ $categoryRow['unitLabel'] }}</td>
                                                                    <td class="px-3 py-2.5 text-center font-extrabold text-slate-900">{{ number_format($categoryRow['records']) }}</td>
                                                                    @if($gateKey === 'exit')
                                                                        <td class="px-3 py-2.5 text-center text-xs font-bold text-slate-700">{{ $categoryRow['quantity'] }}</td>
                                                                        <td class="px-3 py-2.5 text-center text-xs font-bold text-emerald-600">{{ number_format((int) $categoryRow['totalValue']) }}</td>
                                                                        <td class="px-3 py-2.5 text-center text-xs font-bold {{ $categoryRow['cancelled'] > 0 ? 'text-rose-600' : 'text-slate-300' }}">{{ number_format($categoryRow['cancelled']) }}</td>
                                                                    @else
                                                                        <td class="px-3 py-2.5 text-center text-xs font-bold text-slate-700">{{ number_format($categoryRow['extra']) }}</td>
                                                                    @endif
                                                                    <td class="px-3 py-2.5 text-center text-xs text-slate-500">{{ $jalaliDateTime($categoryRow['lastAt']) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
