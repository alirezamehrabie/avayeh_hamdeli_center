@if($selectedGuardian)
    @php
        $extraHouseholdMembers = is_array($selectedGuardian->extra_household_members ?? null)
            ? $selectedGuardian->extra_household_members
            : [];
        $compositionFormula = $selectedGuardian->household_composition_formula;
        $extraHouseholdMembersCount = (int) ($compositionFormula['non_beneficiaries'] ?? count($extraHouseholdMembers));
        $childrenCount = (int) ($compositionFormula['beneficiaries'] ?? $selectedGuardian->children_count ?? $selectedGuardian->people_count ?? 0);
        $childrenInHouse = (int) ($compositionFormula['final_residents'] ?? $selectedGuardian->children_in_house ?? 0);
        $childrenFromPreviousMarriageApplied = (int) ($compositionFormula['previous_marriage_members'] ?? 0);
        $guardianResidentCount = (int) ($compositionFormula['guardian'] ?? 1);
        $motherResidentCount = (int) ($compositionFormula['mother_counted_separately'] ?? ($compositionFormula['mother'] ?? 0));
        $motherResidentRawCount = (int) ($compositionFormula['mother'] ?? 0);
        $guardianIsMother = $motherResidentRawCount > 0 && $motherResidentCount === 0;
        $guardianFullName = trim(collect([$selectedGuardian->first_name, $selectedGuardian->last_name])->filter()->implode(' '));
        $guardianFullName = $guardianFullName !== '' ? $guardianFullName : 'بدون نام';
        $vehicleOwnershipLabels = [
            'personal' => 'شخصی',
            'company' => 'شرکتی',
            'rented' => 'استیجاری',
        ];
        $insuranceValue = $selectedGuardian->insurance_status
            ? 'دارد' . ($selectedGuardian->insuranceType ? ' - ' . $selectedGuardian->insuranceType->name : '')
            : 'ندارد';
        $vehicleValue = $selectedGuardian->has_vehicle
            ? collect([
                'دارد',
                $selectedGuardian->vehicleType?->name,
                $selectedGuardian->vehicle_ownership_type ? '(' . ($vehicleOwnershipLabels[$selectedGuardian->vehicle_ownership_type] ?? $selectedGuardian->vehicle_ownership_type) . ')' : null,
            ])->filter()->implode(' - ')
            : 'ندارد';
        $incomeValue = $selectedGuardian->average_income ? number_format($selectedGuardian->average_income) . ' ریال' : '-';
        $closeMethod = $closeMethod ?? 'closeHouseholdModal';
        $wireKey = $wireKey ?? 'household-modal';
        $openState = $openState ?? true;
        $showEditAction = method_exists($this, 'editGuardian') && auth()->user()?->can('full-access');
        $guardianItems = [
            ['label' => 'نام سرپرست', 'value' => $selectedGuardian->first_name ?: '-', 'icon' => 'bi-person'],
            ['label' => 'نام خانوادگی سرپرست', 'value' => $selectedGuardian->last_name ?: '-', 'icon' => 'bi-person-vcard'],
            ['label' => 'کد ملی سرپرست', 'value' => $selectedGuardian->national_code ?: '-', 'icon' => 'bi-credit-card-2-front', 'dir' => 'ltr', 'copy' => 'guardian-national-code'],
            ['label' => 'تاریخ تولد سرپرست', 'value' => $selectedGuardian->guardian_formatted_birth_date ?: '-', 'icon' => 'bi-calendar3'],
            ['label' => 'شماره موبایل سرپرست', 'value' => $selectedGuardian->guardian_phone_number ?: '-', 'icon' => 'bi-phone', 'dir' => 'ltr', 'copy' => 'guardian-phone'],
            ['label' => 'مددکار اختصاص‌یافته', 'value' => $selectedGuardian->socialWorker?->full_name ?: '-', 'icon' => 'bi-person-check', 'highlight' => true],
        ];
        $supportItems = [
            ['label' => 'شغل سرپرست', 'value' => $selectedGuardian->occupation?->name ?: '-', 'icon' => 'bi-briefcase'],
            ['label' => 'دهک اقتصادی خانوار', 'value' => $selectedGuardian->economic_decile ? 'دهک ' . $selectedGuardian->economic_decile : '-', 'icon' => 'bi-bar-chart-steps'],
            ['label' => 'وضعیت بیمه و نوع بیمه', 'value' => $insuranceValue, 'icon' => 'bi-shield-check'],
            ['label' => 'وسیله نقلیه و مالکیت', 'value' => $vehicleValue, 'icon' => 'bi-car-front'],
            ['label' => 'متوسط درآمد ماهیانه', 'value' => $incomeValue, 'icon' => 'bi-cash-stack', 'wide' => true, 'emphasis' => true],
        ];
        $residenceItems = [
            ['label' => 'وضعیت سکونت', 'value' => $selectedGuardian->residence?->residenceStatus?->name ?: '-', 'icon' => 'bi-house-heart'],
            ['label' => 'محدوده سکونت', 'value' => $selectedGuardian->residence?->district?->name ?: '-', 'icon' => 'bi-geo-alt'],
            ['label' => 'آدرس کامل', 'value' => $selectedGuardian->residence?->address ?: '-', 'icon' => 'bi-signpost-2', 'wide' => true],
        ];
        $compositionItems = [
            ['label' => 'سرپرست', 'value' => $guardianResidentCount, 'icon' => 'bi-person-badge', 'tone' => 'text-amber-700 bg-amber-50 ring-amber-100'],
            ['label' => 'تحت پوشش مرکز', 'value' => $childrenCount, 'icon' => 'bi-people', 'tone' => 'text-cyan-700 bg-cyan-50 ring-cyan-100'],
            ['label' => 'ازدواج قبلی', 'value' => $childrenFromPreviousMarriageApplied, 'icon' => 'bi-diagram-2', 'tone' => 'text-violet-700 bg-violet-50 ring-violet-100'],
            ['label' => 'افراد غیرمددجو', 'value' => $extraHouseholdMembersCount, 'icon' => 'bi-person-plus', 'tone' => 'text-emerald-700 bg-emerald-50 ring-emerald-100'],
            ['label' => 'مادر', 'value' => $motherResidentCount, 'icon' => 'bi-person-heart', 'tone' => 'text-rose-700 bg-rose-50 ring-rose-100'],
            ['label' => 'جمع نهایی', 'value' => $childrenInHouse, 'icon' => 'bi-calculator', 'tone' => 'text-orange-700 bg-orange-50 ring-orange-100'],
        ];
        $detailSections = [
            [
                'title' => 'مشخصات سرپرست',
                'subtitle' => 'هویت، تماس و مددکار مرتبط با خانوار',
                'icon' => 'bi-person-vcard',
                'accent' => 'text-amber-700 bg-amber-50 ring-amber-100',
                'items' => $guardianItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
            [
                'title' => 'اقتصاد و پشتیبانی',
                'subtitle' => 'درآمد، شغل، بیمه و دارایی‌های مهم خانوار',
                'icon' => 'bi-wallet2',
                'accent' => 'text-orange-700 bg-orange-50 ring-orange-100',
                'items' => $supportItems,
                'grid' => 'sm:grid-cols-2',
            ],
            [
                'title' => 'سکونت',
                'subtitle' => 'وضعیت، محدوده و نشانی ثبت‌شده خانوار',
                'icon' => 'bi-house-door',
                'accent' => 'text-lime-700 bg-lime-50 ring-lime-100',
                'items' => $residenceItems,
                'grid' => 'sm:grid-cols-2',
            ],
        ];
    @endphp

    <div
        wire:key="{{ $wireKey }}"
        x-data="{
            open: @js($openState),
            copiedField: null,
            async copyText(value, field) {
                if (! value || value === '-') return;

                try {
                    await navigator.clipboard.writeText(value);
                    this.copiedField = field;
                    setTimeout(() => {
                        if (this.copiedField === field) {
                            this.copiedField = null;
                        }
                    }, 1400);
                } catch (error) {
                    this.copiedField = null;
                }
            },
            close() {
                this.open = false;
                setTimeout(() => $wire.{{ $closeMethod }}(), 220);
            }
        }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end justify-center bg-stone-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-4"
        @keydown.escape.window="close()"
        style="display: none;"
    >
        <div class="absolute inset-0" @click="close()"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative flex h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-t-3xl border border-amber-100 bg-white shadow-2xl shadow-stone-950/20 sm:h-auto sm:max-h-[90vh] sm:rounded-3xl"
            @click.stop
        >
            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-amber-100 bg-gradient-to-l from-amber-500 via-yellow-400 to-orange-400 px-4 py-3 text-white sm:px-5 sm:py-4">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/15 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                        <span>پرونده خانوار</span>
                    </div>
                    <h2 class="mt-2 truncate text-lg font-extrabold sm:text-xl">
                        {{ $guardianFullName }}
                    </h2>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-white/85">
                        <span dir="ltr">{{ $selectedGuardian->national_code ?: '-' }}</span>
                        <span class="h-1 w-1 rounded-full bg-white/60"></span>
                        <span>{{ number_format($childrenInHouse) }} نفر در خانوار</span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if($showEditAction)
                        <button
                            type="button"
                            wire:click="editGuardian({{ $selectedGuardian->id }})"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/30 bg-white/15 text-white shadow-sm transition hover:bg-white/25 focus:outline-none focus:ring-4 focus:ring-white/25"
                            aria-label="ویرایش"
                        >
                            <i class="bi bi-pencil-square text-base"></i>
                        </button>
                    @endif
                    <button
                        type="button"
                        @click="close()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/30 bg-white/15 text-2xl leading-none text-white shadow-sm transition hover:bg-white/25 focus:outline-none focus:ring-4 focus:ring-white/25"
                        aria-label="بستن"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <div class="grid min-h-0 flex-1 gap-4 overflow-y-auto bg-amber-50/45 p-3 sm:p-5 lg:grid-cols-[17rem_minmax(0,1fr)] lg:gap-5">
                <aside class="rounded-2xl border border-amber-100 bg-white p-3 shadow-sm lg:sticky lg:top-0 lg:self-start">
                    <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-4">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-200">
                            <i class="bi bi-house-heart text-4xl"></i>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="break-words text-base font-extrabold leading-7 text-slate-900">{{ $guardianFullName }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">سرپرست خانوار</p>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-xl border border-amber-100 bg-amber-50 p-3">
                            <p class="text-[11px] font-semibold text-amber-700">اعضای نهایی</p>
                            <p class="mt-1 text-lg font-extrabold text-amber-900">{{ number_format($childrenInHouse) }}</p>
                        </div>
                        <div class="rounded-xl border border-orange-100 bg-orange-50 p-3">
                            <p class="text-[11px] font-semibold text-orange-700">تحت پوشش</p>
                            <p class="mt-1 text-lg font-extrabold text-orange-900">{{ number_format($childrenCount) }}</p>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[11px] font-semibold text-slate-500">کد ملی</p>
                            <p class="mt-1 truncate text-sm font-bold text-slate-900" dir="ltr">{{ $selectedGuardian->national_code ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[11px] font-semibold text-slate-500">مددکار</p>
                            <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $selectedGuardian->socialWorker?->full_name ?: '-' }}</p>
                        </div>
                    </div>
                </aside>

                <section class="min-w-0 space-y-3">
                    <div class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-extrabold text-slate-900">نمای کلی خانوار</p>
                                <p class="mt-1 text-xs leading-6 text-slate-500">اطلاعات سرپرست، وضعیت اقتصادی، سکونت و ترکیب اعضای خانوار</p>
                            </div>
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                                <i class="bi bi-kanban text-base"></i>
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2">
                                <p class="text-[11px] font-semibold text-amber-700">درآمد ماهیانه</p>
                                <p class="mt-1 truncate text-sm font-extrabold text-amber-950">{{ $incomeValue }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-[11px] font-semibold text-slate-500">بیمه</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $insuranceValue }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-[11px] font-semibold text-slate-500">سکونت</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $selectedGuardian->residence?->residenceStatus?->name ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @foreach($detailSections as $section)
                        <div class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-extrabold text-slate-900">{{ $section['title'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $section['subtitle'] }}</p>
                                </div>
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $section['accent'] }}">
                                    <i class="bi {{ $section['icon'] }} text-base"></i>
                                </span>
                            </div>

                            <div class="grid gap-2 {{ $section['grid'] }}">
                                @foreach($section['items'] as $item)
                                    @php
                                        $isHighlighted = !empty($item['highlight']);
                                        $isWide = !empty($item['wide']);
                                        $isEmphasis = !empty($item['emphasis']);
                                    @endphp
                                    <div class="{{ $isWide ? 'sm:col-span-2' : '' }} flex min-h-[4.75rem] items-start gap-3 rounded-xl border p-3 {{ $isHighlighted ? 'border-amber-200 bg-amber-50/80 shadow-sm' : ($isEmphasis ? 'border-emerald-100 bg-emerald-50/70' : 'border-slate-100 bg-slate-50') }}">
                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $isHighlighted ? 'bg-white text-amber-700 ring-amber-200' : ($isEmphasis ? 'bg-white text-emerald-700 ring-emerald-200' : 'bg-white text-slate-500 ring-slate-200') }}">
                                            <i class="bi {{ $item['icon'] }} text-sm"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold {{ $isHighlighted ? 'text-amber-700' : ($isEmphasis ? 'text-emerald-700' : 'text-slate-500') }}">{{ $item['label'] }}</p>
                                            <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                                                <p class="min-w-0 break-words text-sm font-bold leading-6 {{ $isHighlighted ? 'text-amber-950' : ($isEmphasis ? 'text-emerald-800' : 'text-slate-900') }}" @if(isset($item['dir'])) dir="{{ $item['dir'] }}" @endif>
                                                    {{ $item['value'] }}
                                                </p>
                                                @if(!empty($item['copy']))
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-amber-200 bg-white text-amber-600 transition hover:border-amber-300 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-100"
                                                        @click="copyText(@js($item['value']), @js($item['copy']))"
                                                        aria-label="کپی {{ $item['label'] }}"
                                                    >
                                                        <i class="bi bi-copy text-xs" x-show="copiedField !== @js($item['copy'])"></i>
                                                        <i class="bi bi-check2 text-sm text-emerald-600" x-show="copiedField === @js($item['copy'])" style="display: none;"></i>
                                                    </button>
                                                    <span
                                                        x-show="copiedField === @js($item['copy'])"
                                                        x-transition.opacity.duration.150ms
                                                        class="shrink-0 text-[11px] font-medium text-emerald-600"
                                                        style="display: none;"
                                                    >
                                                        کپی شد
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-extrabold text-slate-900">ترکیب اعضای خانوار</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">فرمول تعداد نهایی افراد ساکن در خانوار با احتساب سرپرست</p>
                            </div>
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-100">
                                <i class="bi bi-people-fill text-base"></i>
                            </span>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                            @foreach($compositionItems as $compositionItem)
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-[11px] font-semibold text-slate-500">{{ $compositionItem['label'] }}</p>
                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg ring-1 {{ $compositionItem['tone'] }}">
                                            <i class="bi {{ $compositionItem['icon'] }} text-xs"></i>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xl font-extrabold text-slate-900">{{ number_format($compositionItem['value']) }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 overflow-x-auto rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900">
                            <div class="inline-flex min-w-max items-center gap-2">
                                <span>فرمول:</span>
                                <span>{{ $guardianResidentCount }}</span>
                                <span>+</span>
                                <span>{{ $childrenCount }}</span>
                                <span>+</span>
                                <span>{{ $childrenFromPreviousMarriageApplied }}</span>
                                <span>+</span>
                                <span>{{ $extraHouseholdMembersCount }}</span>
                                <span>+</span>
                                <span>{{ $motherResidentCount }}</span>
                                <span>=</span>
                                <span class="text-base font-extrabold text-orange-800">{{ $childrenInHouse }}</span>
                            </div>
                        </div>

                        @if($guardianIsMother)
                            <div class="mt-3 rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-xs leading-6 text-sky-800">
                                در این خانوار، سرپرست همان مادر است؛ بنابراین «مادر» جداگانه به عدد نهایی اضافه نشده و در سهم سرپرست محاسبه شده است.
                            </div>
                        @endif

                        <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="mb-2 text-xs font-semibold text-slate-600">شرح افراد غیرمددجو ساکن در منزل</p>
                            @if($extraHouseholdMembersCount > 0 && count($extraHouseholdMembers) > 0)
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach($extraHouseholdMembers as $member)
                                        <div class="rounded-lg border border-slate-100 bg-white px-3 py-2 text-xs leading-6 text-slate-700">
                                            {{ $member['description'] ?? '-' }}
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-500">برای این خانوار فرد غیرمددجو ثبت نشده است.</p>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endif
