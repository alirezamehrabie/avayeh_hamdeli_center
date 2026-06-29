@if($this->selectedGuardian)
    @php
        $selectedGuardian = $this->selectedGuardian;
        $guardianJob = collect([
            $selectedGuardian->occupation?->name,
            $selectedGuardian->jobType?->name,
        ])->filter()->implode(' - ');
        $insuranceValue = $selectedGuardian->insurance_status
            ? ('دارد' . ($selectedGuardian->insuranceType?->name ? ' - ' . $selectedGuardian->insuranceType->name : ''))
            : 'ندارد';
        $vehicleValue = $selectedGuardian->has_vehicle
            ? ('دارد' . ($selectedGuardian->vehicleType?->name ? ' - ' . $selectedGuardian->vehicleType->name : ''))
            : 'ندارد';
        $localValue = $selectedGuardian->residence?->is_local_to_city === null
            ? '-'
            : ($selectedGuardian->residence->is_local_to_city ? 'بله' : 'خیر');
        $identityItems = [
            ['label' => 'نام و نام خانوادگی', 'value' => $selectedGuardian->full_name ?: '-', 'icon' => 'bi-person'],
            ['label' => 'کد خانوار', 'value' => $selectedGuardian->guardian_code ?: '-', 'icon' => 'bi-hash', 'dir' => 'ltr'],
            ['label' => 'کد ملی', 'value' => $selectedGuardian->national_code ?: '-', 'icon' => 'bi-credit-card-2-front', 'dir' => 'ltr'],
            ['label' => 'تاریخ تولد', 'value' => $selectedGuardian->guardian_formatted_birth_date ?: '-', 'icon' => 'bi-calendar3', 'meta_label' => 'سن', 'meta_value' => $selectedGuardian->guardian_age !== null ? $selectedGuardian->guardian_age . ' سال' : '-'],
            ['label' => 'شماره موبایل', 'value' => $selectedGuardian->guardian_phone_number ?: '-', 'icon' => 'bi-phone', 'dir' => 'ltr'],
            ['label' => 'مددکار مسئول', 'value' => $selectedGuardian->socialWorker?->full_name ?: '-', 'icon' => 'bi-person-check'],
        ];
        $economicItems = [
            ['label' => 'شغل', 'value' => $guardianJob ?: '-', 'icon' => 'bi-briefcase'],
            ['label' => 'دهک اقتصادی', 'value' => $selectedGuardian->economic_decile ? 'دهک ' . $selectedGuardian->economic_decile : '-', 'icon' => 'bi-bar-chart'],
            ['label' => 'میانگین درآمد ماهانه', 'value' => $selectedGuardian->average_income ? number_format((int) $selectedGuardian->average_income) . ' ریال' : '-', 'icon' => 'bi-cash-coin'],
            ['label' => 'بیمه', 'value' => $insuranceValue, 'icon' => 'bi-shield-check'],
            ['label' => 'وسیله نقلیه', 'value' => $vehicleValue, 'icon' => 'bi-truck'],
            ['label' => 'اشتغال اعضای خانواده', 'value' => $selectedGuardian->any_family_employed === null ? '-' : ($selectedGuardian->any_family_employed ? 'بله' : 'خیر'), 'icon' => 'bi-people'],
        ];
        $householdItems = [
            ['label' => 'تعداد فرزندان تحت تکفل', 'value' => number_format((int) ($selectedGuardian->children_count ?? 0)) . ' نفر', 'icon' => 'bi-people-fill'],
            ['label' => 'تعداد اعضای خانوار', 'value' => filled($selectedGuardian->children_in_house) ? number_format((int) $selectedGuardian->children_in_house) . ' نفر' : '-', 'icon' => 'bi-house-heart'],
            ['label' => 'تعداد مددجویان', 'value' => number_format((int) ($selectedGuardian->people_count ?? 0)) . ' نفر', 'icon' => 'bi-person-vcard'],
            ['label' => 'وضعیت مسکن', 'value' => $selectedGuardian->residence?->residenceStatus?->name ?: '-', 'icon' => 'bi-house-door'],
            ['label' => 'منطقه / ناحیه', 'value' => $selectedGuardian->residence?->district?->name ?: '-', 'icon' => 'bi-geo-alt'],
            ['label' => 'بومی شهر', 'value' => $localValue, 'icon' => 'bi-pin-map'],
            ['label' => 'آدرس منزل', 'value' => $selectedGuardian->residence?->address ?: '-', 'icon' => 'bi-signpost-2'],
        ];
        $detailSections = [
            [
                'title' => 'اطلاعات هویتی',
                'subtitle' => 'مشخصات اصلی سرپرست خانوار',
                'icon' => 'bi-person-vcard',
                'accent' => 'text-cyan-700 bg-cyan-50 ring-cyan-100',
                'metaAccent' => 'text-cyan-700 bg-cyan-50 ring-cyan-100',
                'items' => $identityItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
            [
                'title' => 'وضعیت اقتصادی',
                'subtitle' => 'شغل، درآمد، بیمه و دارایی',
                'icon' => 'bi-wallet2',
                'accent' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
                'metaAccent' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
                'items' => $economicItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
            [
                'title' => 'خانوار و مسکن',
                'subtitle' => 'ترکیب خانوار و وضعیت سکونت',
                'icon' => 'bi-house-fill',
                'accent' => 'text-amber-700 bg-amber-50 ring-amber-100',
                'metaAccent' => 'text-amber-700 bg-amber-50 ring-amber-100',
                'items' => $householdItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
        ];
    @endphp

    <div
        wire:key="guardian-modal"
        x-data="{
            open: @js($showGuardianModal),
            previousActiveElement: null,
            closing: false,
            scrollLockStyles: null,
            allowBackdropClose: false,
            init() {
                this.previousActiveElement = document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
                this.allowBackdropClose = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                this.lockScroll();
                this.$nextTick(() => {
                    (this.$refs.closeButton || this.$refs.dialog)?.focus({ preventScroll: true });
                });
            },
            destroy() {
                this.unlockScroll();
            },
            lockScroll() {
                if (this.scrollLockStyles) return;
                this.scrollLockStyles = {
                    bodyOverflow: document.body.style.overflow,
                    htmlOverflow: document.documentElement.style.overflow,
                };
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
            },
            unlockScroll() {
                if (! this.scrollLockStyles) return;
                document.body.style.overflow = this.scrollLockStyles.bodyOverflow;
                document.documentElement.style.overflow = this.scrollLockStyles.htmlOverflow;
                this.scrollLockStyles = null;
            },
            closeFromBackdrop() {
                if (! this.allowBackdropClose) return;
                this.close();
            },
            close() {
                if (this.closing) return;
                this.closing = true;
                this.open = false;
                setTimeout(() => {
                    Promise.resolve($wire.closeGuardianModal()).finally(() => {
                        this.unlockScroll();
                        this.$nextTick(() => {
                            if (this.previousActiveElement?.isConnected) {
                                this.previousActiveElement.focus({ preventScroll: true });
                            }
                        });
                    });
                }, 200);
            }
        }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 px-0 pb-0 pt-8 backdrop-blur-sm sm:items-center sm:p-4"
        @keydown.escape.window="close()"
        style="display: none;"
    >
        <div class="absolute inset-0" @click="closeFromBackdrop()"></div>

        <div
            x-ref="dialog"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 sm:h-auto sm:max-h-[90vh] sm:rounded-3xl"
            @click.stop
            tabindex="-1"
        >
            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        <span>پرونده سرپرست خانوار</span>
                    </div>
                    <h2 class="mt-2 truncate text-lg font-extrabold text-slate-900 sm:text-xl">
                        {{ $selectedGuardian->full_name ?: 'بدون نام' }}
                    </h2>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                        <span dir="ltr">کد خانوار: {{ $selectedGuardian->guardian_code ?: '-' }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span dir="ltr">{{ $selectedGuardian->national_code ?: '-' }}</span>
                    </div>
                </div>

                <button
                    x-ref="closeButton"
                    type="button"
                    @click="close()"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-2xl leading-none text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    aria-label="بستن"
                >
                    &times;
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-slate-50 p-3 sm:p-5">
                @foreach($detailSections as $section)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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
                                <div class="flex min-h-[4.75rem] items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                                        <i class="bi {{ $item['icon'] }} text-sm"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-semibold text-slate-500">{{ $item['label'] }}</p>
                                        <p class="mt-1 break-words text-sm font-bold leading-6 text-slate-900" @if(isset($item['dir'])) dir="{{ $item['dir'] }}" @endif>
                                            {{ $item['value'] }}
                                        </p>
                                        @if(isset($item['meta_label']))
                                            <div class="mt-2 inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $section['metaAccent'] }}">
                                                <span class="shrink-0 opacity-75">{{ $item['meta_label'] }}:</span>
                                                <span class="truncate">{{ $item['meta_value'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-slate-900">مددجویان تحت پوشش</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">فهرست مددجویان متعلق به این خانوار</p>
                        </div>
                        <span class="inline-flex items-center justify-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100">
                            {{ number_format($selectedGuardian->people->count()) }} نفر
                        </span>
                    </div>

                    @if($selectedGuardian->people->isNotEmpty())
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($selectedGuardian->people as $beneficiary)
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                    <p class="truncate text-sm font-bold text-slate-900">
                                        {{ trim(($beneficiary->first_name ?? '') . ' ' . ($beneficiary->last_name ?? '')) ?: 'بدون نام' }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                        <span dir="ltr">کد: {{ $beneficiary->person_code ?: '-' }}</span>
                                        <span dir="ltr">{{ $beneficiary->national_id ?: '-' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center text-xs text-slate-500">
                            مددجویی برای این خانوار ثبت نشده است.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
