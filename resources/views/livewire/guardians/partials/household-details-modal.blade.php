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
        $vehicleOwnershipLabels = [
            'personal' => 'شخصی',
            'company' => 'شرکتی',
            'rented' => 'استیجاری',
        ];
        $closeMethod = $closeMethod ?? 'closeHouseholdModal';
        $wireKey = $wireKey ?? 'household-modal';
        $openState = $openState ?? true;
    @endphp

    <div
        wire:key="{{ $wireKey }}"
        x-data="{
            open: @js($openState),
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
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
            class="relative w-full max-w-4xl overflow-hidden rounded-3xl border border-amber-100 bg-white shadow-2xl"
            @click.stop
        >
            <div class="flex items-start justify-between gap-4 bg-gradient-to-l from-amber-500 to-yellow-400 px-6 py-5 text-white">
                <div>
                    <h2 class="text-xl font-extrabold">اطلاعات خانوار</h2>
                    <p class="mt-1 text-sm text-white/85">{{ trim($selectedGuardian->first_name . ' ' . $selectedGuardian->last_name) }}</p>
                </div>
                <button type="button" @click="close()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25"
                        aria-label="بستن">
                    &times;
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">کد ملی سرپرست</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->national_code ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">مددکار اختصاص‌یافته</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->socialWorker?->full_name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">نام سرپرست</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->first_name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">نام خانوادگی سرپرست</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->last_name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">تاریخ تولد سرپرست</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->guardian_formatted_birth_date ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">شماره موبایل سرپرست</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->guardian_phone_number ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">دهک اقتصادی خانوار</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->economic_decile ? 'دهک ' . $selectedGuardian->economic_decile : '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">شغل سرپرست</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->occupation?->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">وضعیت بیمه و نوع بیمه</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $selectedGuardian->insurance_status ? 'دارد' : 'ندارد' }}
                            @if($selectedGuardian->insurance_status && $selectedGuardian->insuranceType)
                                - {{ $selectedGuardian->insuranceType->name }}
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">وضعیت مالکیت وسیله نقلیه و نوع وسیله</p>
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $selectedGuardian->has_vehicle ? 'دارد' : 'ندارد' }}
                            @if($selectedGuardian->has_vehicle)
                                @if($selectedGuardian->vehicleType)
                                    - {{ $selectedGuardian->vehicleType->name }}
                                @endif
                                @if($selectedGuardian->vehicle_ownership_type)
                                    ({{ $vehicleOwnershipLabels[$selectedGuardian->vehicle_ownership_type] ?? $selectedGuardian->vehicle_ownership_type }})
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 md:col-span-2">
                        <p class="text-xs font-semibold text-emerald-700">متوسط درآمد ماهیانه</p>
                        <p class="mt-1 text-xl font-extrabold text-emerald-700" dir="rtl">
                            {{ $selectedGuardian->average_income ? number_format($selectedGuardian->average_income) : '-' }} ریال
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">وضعیت سکونت</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->residence?->residenceStatus?->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold text-slate-500">محدوده سکونت</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->residence?->district?->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 md:col-span-2">
                        <p class="text-xs font-semibold text-slate-500">آدرس کامل</p>
                        <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->residence?->address ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4 md:col-span-2">
                        <div class="mb-3">
                            <p class="text-sm font-bold text-cyan-800">ترکیب اعضای خانوار</p>
                            <p class="mt-1 text-xs text-slate-600">این بخش مبنای تعداد نهایی اعضای ساکن در خانوار را شفاف نشان می‌دهد و خود سرپرست نیز در این عدد لحاظ شده است.</p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-6">
                            <div class="rounded-xl border border-sky-100 bg-white p-3">
                                <p class="text-[11px] font-semibold text-slate-500">سرپرست</p>
                                <p class="mt-1 text-lg font-extrabold text-sky-700">{{ $guardianResidentCount }}</p>
                            </div>
                            <div class="rounded-xl border border-cyan-100 bg-white p-3">
                                <p class="text-[11px] font-semibold text-slate-500">تحت پوشش مرکز</p>
                                <p class="mt-1 text-lg font-extrabold text-slate-800">{{ $childrenCount }}</p>
                            </div>
                            <div class="rounded-xl border border-violet-100 bg-white p-3">
                                <p class="text-[11px] font-semibold text-slate-500">ازدواج قبلی</p>
                                <p class="mt-1 text-lg font-extrabold text-violet-700">{{ $childrenFromPreviousMarriageApplied }}</p>
                            </div>
                            <div class="rounded-xl border border-emerald-100 bg-white p-3">
                                <p class="text-[11px] font-semibold text-slate-500">افراد غیرمددجو</p>
                                <p class="mt-1 text-lg font-extrabold text-emerald-700">{{ $extraHouseholdMembersCount }}</p>
                            </div>
                            <div class="rounded-xl border border-rose-100 bg-white p-3">
                                <p class="text-[11px] font-semibold text-slate-500">مادر</p>
                                <p class="mt-1 text-lg font-extrabold text-rose-700">{{ $motherResidentCount }}</p>
                            </div>
                            <div class="rounded-xl border border-amber-100 bg-white p-3">
                                <p class="text-[11px] font-semibold text-slate-500">جمع نهایی</p>
                                <p class="mt-1 text-lg font-extrabold text-amber-700">{{ $childrenInHouse }}</p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700">
                            <span class="font-semibold">فرمول:</span>
                            <span class="ms-1">{{ $guardianResidentCount }}</span>
                            <span class="mx-1">+</span>
                            <span>{{ $childrenCount }}</span>
                            <span class="mx-1">+</span>
                            <span>{{ $childrenFromPreviousMarriageApplied }}</span>
                            <span class="mx-1">+</span>
                            <span>{{ $extraHouseholdMembersCount }}</span>
                            <span class="mx-1">+</span>
                            <span>{{ $motherResidentCount }}</span>
                            <span class="mx-1">=</span>
                            <span class="font-extrabold text-slate-900">{{ $childrenInHouse }}</span>
                        </div>

                        @if($guardianIsMother)
                            <div class="mt-3 rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-xs text-sky-800">
                                در این خانوار، سرپرست همان مادر است؛ بنابراین «مادر» جداگانه به عدد نهایی اضافه نشده و در سهم سرپرست محاسبه شده است.
                            </div>
                        @endif

                        <div class="mt-3 rounded-xl border border-cyan-100 bg-white p-3">
                            <p class="mb-2 text-xs font-semibold text-slate-600">شرح افراد غیرمددجو ساکن در منزل</p>
                            @if($extraHouseholdMembersCount > 0)
                                <div class="grid gap-2 md:grid-cols-3">
                                    @foreach($extraHouseholdMembers as $member)
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                                            {{ $member['description'] ?? '-' }}
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-500">برای این خانوار فرد غیرمددجو ثبت نشده است.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
