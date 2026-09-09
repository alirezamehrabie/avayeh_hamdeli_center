<div class="min-h-screen bg-slate-100/70 pb-16">
    {{-- Header --}}
    <div class="bg-gradient-to-l from-slate-900 via-slate-800 to-indigo-900 px-4 py-8 text-white sm:px-8">
        <div class="mx-auto max-w-6xl">
            <a
                href="{{ route('admin.dashboard', ['section' => 'advanced-service-report', 'channel' => 'gate', 'id' => $service->id]) }}"
                class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                بازگشت به گزارش خدمات
            </a>
            <h1 class="mt-3 text-2xl font-extrabold">{{ $service->serviceName?->name ?: $service->name }}</h1>
            <p class="mt-2 text-sm text-slate-200">{{ $service->code }} · گزارش فنی ایستگاه توزیع</p>

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
        </div>
    </div>

    {{-- Tabs (tab 2 will join this bar via GateTechnicalReport::TABS) --}}
    <div class="mx-auto mt-6 max-w-6xl px-4 sm:px-8">
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
        <div class="mx-auto mt-6 max-w-6xl space-y-6 px-4 sm:px-8">
            {{-- Section 1: authorized at Entry, not delivered at Delivery --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" id="pending-section">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">۱. مجوز ثبت‌شده در ورود، تحویل‌نشده در تحویل</h2>
                        <p class="mt-0.5 text-xs text-slate-500">دسته‌بندی‌هایی که در گیت ورود مجوز گرفته‌اند اما هنوز در گیت تحویل تأیید نشده‌اند.</p>
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
</div>
