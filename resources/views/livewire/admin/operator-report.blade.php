<div class="space-y-6" dir="rtl">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-cyan-600">Advanced Reports / Operator Report</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-800">گزارش عملکرد اپراتورها</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    این بخش روند ثبت، ویرایش، حذف و لاگ فعالیت کاربران سیستم را در بازه‌های ساعتی، روزانه، ماهانه و سالانه نمایش می‌دهد.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-cyan-100 bg-cyan-50 px-4 py-3">
                    <p class="text-xs font-semibold text-cyan-700">کل فعالیت‌ها</p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($totalActivities) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                    <p class="text-xs font-semibold text-emerald-700">ثبت‌ها</p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($totalRegistrations) }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3">
                    <p class="text-xs font-semibold text-amber-700">ویرایش‌ها</p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($totalEdits) }}</p>
                </div>
                <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3">
                    <p class="text-xs font-semibold text-rose-700">حذف‌ها</p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($totalDeletions) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">نوع بازه</label>
                <select wire:model.live="timeframe" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                    @foreach($timeframeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">تاریخ مرجع</label>
                @if($timeframe === 'year')
                    <select wire:model.live="referenceYear" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        @foreach($yearOptions as $yearValue)
                            <option value="{{ $yearValue }}">{{ $yearValue }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="grid gap-2 {{ $timeframe === 'month' ? 'sm:grid-cols-2' : ($timeframe === 'day' ? 'sm:grid-cols-3' : 'sm:grid-cols-4') }}">
                        <select wire:model.live="referenceYear" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            @foreach($yearOptions as $yearValue)
                                <option value="{{ $yearValue }}">{{ $yearValue }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="referenceMonth" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            @foreach($monthOptions as $monthValue => $monthLabel)
                                <option value="{{ $monthValue }}">{{ $monthLabel }}</option>
                            @endforeach
                        </select>

                        @if($timeframe === 'day' || $timeframe === 'hour')
                            <select wire:model.live="referenceDay" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                @foreach($dayOptions as $dayValue)
                                    <option value="{{ $dayValue }}">{{ $dayValue }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if($timeframe === 'hour')
                            <select wire:model.live="referenceHour" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                @foreach($hourOptions as $hourValue)
                                    <option value="{{ $hourValue }}">{{ str_pad((string) $hourValue, 2, '0', STR_PAD_LEFT) }}:00</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endif
            </div>

            @if($canViewAllOperators)
                <div>
                    <label class="mb-2 block text-xs font-semibold text-slate-600">انتخاب اپراتور</label>
                    <select wire:model.live="selectedOperatorId" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        <option value="">همه اپراتورها</option>
                        @foreach($operatorOptions as $operatorOption)
                            <option value="{{ $operatorOption->id }}">
                                {{ ($operatorOption->first_name ?: '-') . ' - ' . ($operatorOption->last_name ?: $operatorOption->name) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-[11px] text-slate-500">برای مشاهده همه گزارش‌ها گزینه «همه اپراتورها» را انتخاب کنید.</p>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold text-slate-500">بازه انتخاب‌شده</p>
                <p class="mt-2 text-sm font-bold text-slate-800">{{ $selectedRangeLabel }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    @if($canViewAllOperators)
                        تمام اپراتورها در این بازه تحلیل می‌شوند.
                    @else
                        فقط گزارش عملکرد حساب کاربری شما نمایش داده می‌شود.
                    @endif
                </p>
            </div>
        </div>
    </section>

    @if($reports->isEmpty())
        <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <h2 class="text-lg font-bold text-slate-800">اپراتوری برای این فیلتر پیدا نشد</h2>
            <p class="mt-2 text-sm text-slate-500">فیلترها را تغییر دهید یا نام اپراتور دیگری را جستجو کنید.</p>
        </section>
    @endif

    @foreach($reports as $report)
        <section x-data="{ openPanel: null }" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-l from-slate-50 to-white px-6 py-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-800">{{ $report['operator']->full_name ?: $report['operator']->name }}</h2>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $report['operator']->name }}</span>
                            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">
                                {{ $report['operator']->access_level === 'manager' ? 'مدیریت' : ($report['operator']->access_level === 'admin' ? 'ادمین' : 'اپراتور') }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">
                            آخرین فعالیت: {{ $report['latestActivityAt'] ?? 'بدون فعالیت در این بازه' }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <button
                            type="button"
                            @click="openPanel = openPanel === 'registrations' ? null : 'registrations'"
                            class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 text-right transition hover:-translate-y-0.5 hover:shadow-sm"
                        >
                            <div class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-emerald-200/70 to-transparent"></div>
                            <p class="text-xs font-semibold text-emerald-700">تعداد مددجوی ثبت‌شده</p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($report['registrations']) }}</p>
                            <div class="relative mt-4 flex items-center justify-between text-xs font-semibold text-emerald-800">
                                <span>جزئیات ثبت‌ها</span>
                                <svg :class="openPanel === 'registrations' ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="openPanel = openPanel === 'edits' ? null : 'edits'"
                            class="group relative overflow-hidden rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4 text-right transition hover:-translate-y-0.5 hover:shadow-sm"
                        >
                            <div class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-amber-200/70 to-transparent"></div>
                            <p class="text-xs font-semibold text-amber-700">تعداد ویرایش رکورد</p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($report['edits']) }}</p>
                            <div class="relative mt-4 flex items-center justify-between text-xs font-semibold text-amber-800">
                                <span>جزئیات ویرایش‌ها</span>
                                <svg :class="openPanel === 'edits' ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="openPanel = openPanel === 'deletions' ? null : 'deletions'"
                            class="group relative overflow-hidden rounded-2xl border border-rose-100 bg-rose-50 px-4 py-4 text-right transition hover:-translate-y-0.5 hover:shadow-sm"
                        >
                            <div class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-rose-200/70 to-transparent"></div>
                            <p class="text-xs font-semibold text-rose-700">تعداد حذف</p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($report['deletions']) }}</p>
                            <div class="relative mt-4 flex items-center justify-between text-xs font-semibold text-rose-800">
                                <span>جزئیات حذف‌ها</span>
                                <svg :class="openPanel === 'deletions' ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="openPanel = openPanel === 'logs' ? null : 'logs'"
                            class="group relative overflow-hidden rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4 text-right transition hover:-translate-y-0.5 hover:shadow-sm"
                        >
                            <div class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-sky-200/70 to-transparent"></div>
                            <p class="text-xs font-semibold text-sky-700">کل لاگ‌ها</p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($report['totalActivities']) }}</p>
                            <div class="relative mt-4 flex items-center justify-between text-xs font-semibold text-sky-800">
                                <span>لاگ‌ها و نمودارها</span>
                                <svg :class="openPanel === 'logs' ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4 border-t border-slate-200 bg-slate-50/80 px-6 py-6">
                <div x-show="openPanel === 'registrations'" x-collapse.duration.300ms class="rounded-3xl border border-emerald-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">جزئیات ثبت مددجو</h3>
                            <p class="text-xs text-slate-500">آخرین ثبت‌های انجام‌شده توسط این اپراتور</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $report['registrationLogs']->count() }} مورد اخیر</span>
                    </div>
                    @if($report['registrationLogs']->isEmpty())
                        <div class="rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 px-4 py-8 text-center text-sm text-slate-500">
                            در این بازه ثبت جدیدی انجام نشده است.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($report['registrationLogs'] as $log)
                                @include('livewire.admin.partials.operator-log-item', ['log' => $log])
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="openPanel === 'edits'" x-collapse.duration.300ms class="rounded-3xl border border-amber-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">جزئیات ویرایش رکوردها</h3>
                            <p class="text-xs text-slate-500">آخرین ویرایش‌های ثبت‌شده در پرونده‌ها</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $report['editLogs']->count() }} مورد اخیر</span>
                    </div>
                    @if($report['editLogs']->isEmpty())
                        <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 px-4 py-8 text-center text-sm text-slate-500">
                            در این بازه ویرایشی ثبت نشده است.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($report['editLogs'] as $log)
                                @include('livewire.admin.partials.operator-log-item', ['log' => $log])
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="openPanel === 'deletions'" x-collapse.duration.300ms class="rounded-3xl border border-rose-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">جزئیات حذف‌ها</h3>
                            <p class="text-xs text-slate-500">پرونده‌هایی که توسط این اپراتور حذف شده‌اند</p>
                        </div>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $report['deletionLogs']->count() }} مورد اخیر</span>
                    </div>
                    @if($report['deletionLogs']->isEmpty())
                        <div class="rounded-2xl border border-dashed border-rose-200 bg-rose-50/50 px-4 py-8 text-center text-sm text-slate-500">
                            در این بازه حذفی ثبت نشده است.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($report['deletionLogs'] as $log)
                                @include('livewire.admin.partials.operator-log-item', ['log' => $log])
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="openPanel === 'logs'" x-collapse.duration.300ms class="space-y-4">
                    <div class="grid gap-6 xl:grid-cols-5">
                        <div class="xl:col-span-3 rounded-3xl border border-slate-200 bg-white p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-800">روند فعالیت</h3>
                                    <p class="text-xs text-slate-500">تغییرات حجم عملیات در بازه انتخاب‌شده</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                    بیشینه: {{ $report['trendData']['max'] }}
                                </span>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-40 w-full">
                                    <defs>
                                        <linearGradient id="operator-trend-{{ $report['operator']->id }}" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.35" />
                                            <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.05" />
                                        </linearGradient>
                                    </defs>
                                    @for($i = 0; $i <= 4; $i++)
                                        <line x1="0" y1="{{ $i * 25 }}" x2="100" y2="{{ $i * 25 }}" stroke="#e2e8f0" stroke-dasharray="2 2" />
                                    @endfor
                                    <polyline points="{{ $report['trendData']['points'] }} 100,100 0,100" fill="url(#operator-trend-{{ $report['operator']->id }})" stroke="none" />
                                    <polyline points="{{ $report['trendData']['points'] }}" fill="none" stroke="#0891b2" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <div class="mt-3 grid grid-cols-6 gap-2 text-center text-[11px] text-slate-500 md:grid-cols-12">
                                    @foreach($report['trendData']['buckets'] as $bucket)
                                        <div class="space-y-1">
                                            <div class="h-16 rounded-full bg-white px-1 py-2">
                                                <div class="flex h-full items-end justify-center">
                                                    <div class="w-full rounded-full bg-cyan-500/80" style="height: {{ $report['trendData']['max'] > 0 ? max(8, ($bucket['count'] / $report['trendData']['max']) * 100) : 8 }}%"></div>
                                                </div>
                                            </div>
                                            <p class="truncate">{{ $bucket['label'] }}</p>
                                            <p class="font-semibold text-slate-700">{{ $bucket['count'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-2 space-y-6">
                            <div class="rounded-3xl border border-slate-200 bg-white p-5">
                                <h3 class="text-base font-bold text-slate-800">ترکیب عملیات</h3>
                                <p class="mt-1 text-xs text-slate-500">نسبت ثبت، ویرایش و حذف در لاگ‌های این اپراتور</p>

                                <div class="mt-4 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                    <div class="flex h-4 w-full">
                                        @foreach($report['distribution'] as $slice)
                                            <div class="{{ $slice['class'] }} h-4" style="width: {{ $slice['width'] }}%"></div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach($report['distribution'] as $slice)
                                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                            <div class="flex items-center gap-2 text-slate-600">
                                                <span class="h-3 w-3 rounded-full {{ $slice['class'] }}"></span>
                                                <span>{{ $slice['label'] }}</span>
                                            </div>
                                            <div class="text-left">
                                                <p class="font-bold text-slate-800">{{ $slice['count'] }}</p>
                                                <p class="text-xs text-slate-500">{{ $slice['width'] }}%</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-white p-5">
                                <h3 class="text-base font-bold text-slate-800">خلاصه عملکرد</h3>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs font-semibold text-slate-500">میانگین فعالیت در هر بخش</p>
                                        <p class="mt-2 text-lg font-bold text-slate-800">
                                            {{ count($report['trendData']['buckets']) > 0 ? number_format($report['totalActivities'] / count($report['trendData']['buckets']), 1) : '0.0' }}
                                        </p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs font-semibold text-slate-500">شدیدترین نوع فعالیت</p>
                                        @php
                                            $dominant = collect($report['distribution'])->sortByDesc('count')->first();
                                        @endphp
                                        <p class="mt-2 text-lg font-bold text-slate-800">{{ $dominant['label'] ?? 'بدون داده' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">لاگ فعالیت کاربر</h3>
                                <p class="text-xs text-slate-500">آخرین عملیات ثبت‌شده برای این اپراتور در بازه انتخاب‌شده</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ $report['logs']->count() }} لاگ اخیر</span>
                        </div>

                        @if($report['logs']->isEmpty())
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                در بازه انتخاب‌شده فعالیتی برای این کاربر ثبت نشده است.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($report['logs'] as $log)
                                    @include('livewire.admin.partials.operator-log-item', ['log' => $log])
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endforeach
</div>
