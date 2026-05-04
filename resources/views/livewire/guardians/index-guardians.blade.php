<div>
    <div class="container mx-auto p-4">
        <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/30 to-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">لیست سرپرستان</h1>
                    <p class="mt-1 text-sm text-slate-500">مشاهده سرپرستان و مددجویان تحت نظارت هر سرپرست</p>
                </div>

                <div wire:poll.5s class="rounded-2xl border border-emerald-100 bg-white/90 px-5 py-3 shadow-sm ring-1 ring-emerald-50 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold text-slate-500">تعداد سرپرستان</p>
                    <div class="mt-1 flex items-center justify-center gap-3" dir="ltr">
                        <span class="relative flex h-3 w-3" aria-label="به‌روزرسانی زنده">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                            <span class="relative inline-flex h-3 w-3 animate-pulse rounded-full bg-emerald-500 shadow-sm shadow-emerald-300"></span>
                        </span>
                        <span class="text-xl font-extrabold tracking-tight text-emerald-600 iranyekan-bold">{{ number_format($totalGuardians) }}</span>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <label for="guardian-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سریع سرپرستان</label>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="guardian-search-field"
                        wire:model.live="searchField"
                        class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                        aria-label="معیار جستجو"
                    >
                        <option value="all">همه فیلدها</option>
                        <option value="national_code">کد ملی سرپرست</option>
                        <option value="full_name">نام و نام خانوادگی</option>
                        <option value="mobile">موبایل</option>
                    </select>

                    <div class="relative">
                        <input
                            id="guardian-search"
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            class="w-full rounded-2xl border border-amber-200 bg-white px-10 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            placeholder="عبارت جستجو را وارد کنید..."
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-amber-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-5.4a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                            </svg>
                        </span>
                    </div>
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gradient-to-l from-amber-500 to-yellow-400 text-white">
                        <tr>
                            <th class="w-14 px-3 py-4 text-center font-bold">ردیف</th>
                            <th class="px-5 py-4 text-center font-bold">کد ملی سرپرست</th>
                            <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                            <th class="px-5 py-4 text-center font-bold">موبایل</th>
                            <th class="px-5 py-4 text-center font-bold">تعداد مددجویان تحت نظارت</th>
                            <th class="px-5 py-4 text-center font-bold">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($this->guardians as $guardian)
                            <tr wire:key="guardian-row-{{ $guardian->id }}" wire:click="toggleGuardian({{ $guardian->id }})" class="cursor-pointer transition hover:bg-amber-50/70">
                                <td class="px-3 py-4 text-center text-xs font-extrabold text-slate-500">{{ ($this->guardians->firstItem() ?? 1) + $loop->index }}</td>
                                <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $guardian->national_code }}</td>
                                <td class="px-5 py-4 text-right font-light text-slate-800">{{ trim($guardian->first_name . ' ' . $guardian->last_name) }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $guardian->guardian_phone_number ?? '-' }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-800">{{ $guardian->people_count }} نفر</td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" wire:click.stop="toggleGuardian({{ $guardian->id }})" onclick="event.stopPropagation()" class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:border-amber-300 hover:bg-amber-100">
                                            {{ $expandedGuardianId === $guardian->id ? 'بستن' : 'مشاهده مددجویان' }}
                                        </button>
                                        <button type="button" wire:click.stop="showHouseholdInfo({{ $guardian->id }})" onclick="event.stopPropagation()" class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xژs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100">
                                            اطلاعات خانوار
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            @if($expandedGuardianId === $guardian->id)
                                <tr class="bg-amber-50/40" wire:key="guardian-panel-{{ $guardian->id }}">
                                    <td colspan="6" class="px-5 py-4">
                                        <div
                                            x-data="{ show: false }"
                                            x-init="$nextTick(() => show = true)"
                                            x-show="show"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                                            class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm"
                                        >
                                            <div class="mb-3 flex items-center justify-between">
                                                <h2 class="text-sm font-bold text-slate-700">مددجویان مرتبط با {{ trim($guardian->first_name . ' ' . $guardian->last_name) }}</h2>
                                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $guardian->people_count }} نفر</span>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-50 text-slate-600">
                                                    <tr>
                                                        <th class="px-4 py-3 text-center font-bold">کد مددجو</th>
                                                        <th class="px-4 py-3 text-center font-bold">کد ملی مددجو</th>
                                                        <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی</th>
                                                        <th class="px-4 py-3 text-right font-bold">نام پدر</th>
                                                        <th class="px-4 py-3 text-center font-bold">تاریخ تولد</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                    @forelse($guardian->people as $person)
                                                        <tr class="transition hover:bg-slate-50">
                                                            <td class="px-4 py-3 text-center font-medium text-slate-700">{{ $person->person_code }}</td>
                                                            <td class="px-4 py-3 text-center text-slate-600">{{ $person->national_id }}</td>
                                                            <td class="px-4 py-3 text-right text-slate-700">{{ $person->full_name }}</td>
                                                            <td class="px-4 py-3 text-right text-slate-600">{{ $person->father_name ?? '-' }}</td>
                                                            <td class="px-4 py-3 text-center text-slate-600">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">مددجویی برای این سرپرست ثبت نشده است.</td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">{{ $search ? 'سرپرستی مطابق جستجو پیدا نشد.' : 'هنوز سرپرستی ثبت نشده است.' }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                {{ $this->guardians->links() }}
            </div>
        </div>
    </div>
    @if($this->selectedGuardian)
    @php
        $selectedGuardian = $this->selectedGuardian;
        $vehicleOwnershipLabels = [
            'personal' => 'شخصی',
            'company' => 'شراکتی',
            'rented' => 'استیجاری',
        ];
    @endphp

    <div
        wire:key="household-modal"
        x-data="{
            open: @js($showHouseholdModal),
            close() {
                this.open = false;
                setTimeout(() => $wire.closeHouseholdModal(), 220);
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
                <button type="button" @click="close()" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25" aria-label="بستن">
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
                        <p class="mt-1 text-xl font-extrabold text-emerald-700" dir="rtl">{{ $selectedGuardian->average_income ? number_format($selectedGuardian->average_income) : '-' }} ریال </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
