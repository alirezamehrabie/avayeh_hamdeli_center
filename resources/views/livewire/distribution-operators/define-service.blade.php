<div class="space-y-6">
    @if($canManageServices)
        @include('livewire.distribution-operators.partials.latest-misc-service-card')

        <livewire:distribution-operators.service-batch-creator />
    @else
        @php
            $gateShortcuts = collect([
                [
                    'ability' => 'access-distribution-inbound-gate',
                    'route' => 'distribution-operator.gates.entry',
                    'label' => 'گیت ورود',
                    'hint' => 'ثبت ورود مددجو در ایستگاه',
                    'icon' => 'entry',
                    'classes' => 'from-emerald-500 via-emerald-600 to-teal-600 shadow-emerald-600/25 hover:shadow-emerald-700/30',
                ],
                [
                    'ability' => 'access-distribution-delivery-gate',
                    'route' => 'distribution-operator.gates.delivery',
                    'label' => 'گیت تحویل',
                    'hint' => 'تحویل خدمت به مددجو',
                    'icon' => 'delivery',
                    'classes' => 'from-indigo-500 via-indigo-600 to-blue-600 shadow-indigo-600/25 hover:shadow-indigo-700/30',
                ],
                [
                    'ability' => 'access-distribution-outbound-gate',
                    'route' => 'distribution-operator.gates.exit',
                    'label' => 'گیت خروج',
                    'hint' => 'خروج و تسویه نهایی مددجو',
                    'icon' => 'exit',
                    'classes' => 'from-orange-500 via-orange-600 to-amber-600 shadow-orange-600/25 hover:shadow-orange-700/30',
                ],
            ])->filter(fn (array $shortcut): bool => auth()->user()->can($shortcut['ability']))->values();
        @endphp

        @if($gateShortcuts->isNotEmpty())
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/40 px-4 py-3 sm:px-5 sm:py-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 items-center rounded-lg bg-slate-900 px-2 text-[10px] font-black text-white">ایستگاه</span>
                        <h1 class="text-sm font-black text-slate-900 sm:text-[15px]">بخش‌های فعال شما</h1>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">برای شروع کار، یکی از گیت‌های توزیع را انتخاب کنید.</p>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                    @foreach($gateShortcuts as $gateShortcut)
                        <a
                            href="{{ route($gateShortcut['route']) }}"
                            class="group relative flex items-center gap-3 overflow-hidden rounded-2xl bg-gradient-to-r {{ $gateShortcut['classes'] }} px-4 py-4 text-white shadow-lg transition duration-200 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-slate-200"
                        >
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-inset ring-white/25 transition group-hover:bg-white/25">
                                @switch($gateShortcut['icon'])
                                    @case('entry')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M14 7l-5 5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M9 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                        @break
                                    @case('delivery')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M4 7.5l8 4.5 8-4.5M12 12v9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('exit')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M8 17l-5-5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M3 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                        @break
                                @endswitch
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-black">{{ $gateShortcut['label'] }}</span>
                                <span class="mt-0.5 block truncate text-[11px] font-medium text-white/80">{{ $gateShortcut['hint'] }}</span>
                            </span>

                            <svg class="h-5 w-5 shrink-0 opacity-70 transition group-hover:-translate-x-0.5 group-hover:opacity-100" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200/70 bg-white px-4 py-8 text-center shadow-sm">
                <p class="text-sm font-black text-slate-700">بخشی برای نمایش وجود ندارد</p>
                <p class="mt-1 text-xs font-medium text-slate-400">برای ادامه، از منوی کنار یکی از بخش‌ها را انتخاب کنید.</p>
            </div>
        @endif
    @endif
</div>
