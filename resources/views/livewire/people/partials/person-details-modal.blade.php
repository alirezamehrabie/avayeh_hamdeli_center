@if($this->selectedPerson)
    @php
        $selectedPerson = $this->selectedPerson;
        $supportOrganization = $selectedPerson->supportCoverage?->organization;
        $showEditAction = method_exists($this, 'editPerson') && auth()->user()?->can('people-edit');
        $supportOrganizationName = $supportOrganization?->slug === 'other'
            ? ($selectedPerson->supportCoverage?->other_organization_name ?: ($supportOrganization?->name ?? '-'))
            : ($supportOrganization?->name ?? '-');
        $creatorName = $selectedPerson->creator?->full_name ?: $selectedPerson->creator?->name ?: 'نامشخص';
        $harmTypes = $selectedPerson->harmTypes->pluck('title')->filter()->implode('، ');
        $skills = $selectedPerson->skills->pluck('name')->filter()->implode('، ');
        $birthDateValue = $selectedPerson->formatted_birth_date ?? $selectedPerson->birth_date ?? '-';
        $ageBreakdown = null;
        if ($selectedPerson->birth_year && $selectedPerson->birth_month && $selectedPerson->birth_day) {
            try {
                $todayJalali = \App\Helpers\Morilog\Jalalian::now();
                $years = $todayJalali->getYear() - (int) $selectedPerson->birth_year;
                $months = $todayJalali->getMonth() - (int) $selectedPerson->birth_month;
                $days = $todayJalali->getDay() - (int) $selectedPerson->birth_day;

                if ($days < 0) {
                    $previousMonthDate = $todayJalali->subDays($todayJalali->getDay());
                    $days += $previousMonthDate->getDaysOf($previousMonthDate->getMonth());
                    $months--;
                }

                if ($months < 0) {
                    $months += 12;
                    $years--;
                }

                if ($years >= 0) {
                    $ageBreakdown = sprintf('%d سال، %d ماه و %d روز', $years, $months, $days);
                }
            } catch (\Throwable $e) {
                $ageBreakdown = null;
            }
        }
        if ($ageBreakdown) {
            $birthDateValue;
        }
        $reasonForNotStudying = match ($selectedPerson->education?->reason_for_not_studying) {
            'graduation' => 'فارغ التحصیلی',
            'dropped_out' => 'ترک تحصیل',
            'below_school_age' => 'زیر سن مدرسه',
            default => null,
        };
        $educationStatus = match (true) {
            !$selectedPerson->education => '-',
            $selectedPerson->education->is_studying => 'در حال تحصیل',
            filled($reasonForNotStudying) => $reasonForNotStudying,
            filled($selectedPerson->education->drop_reason) => 'ترک تحصیل - ' . $selectedPerson->education->drop_reason,
            default => 'در حال تحصیل نیست',
        };
        $educationLevelGrade = match (true) {
            !$selectedPerson->education => '-',
            $selectedPerson->education->is_studying => $selectedPerson->education->educationLevel?->name ?: '-',
            filled($selectedPerson->education->educationDegreeLevel?->title) => $selectedPerson->education->educationDegreeLevel->title,
            default => '-',
        };
        $employmentStatus = !$selectedPerson->education
            ? '-'
            : ($selectedPerson->education->works_alongside_study ? 'بله' : 'خیر');
        $guardianJob = collect([
            $selectedPerson->guardian?->occupation?->name,
            $selectedPerson->guardian?->jobType?->name,
        ])->filter()->implode(' - ');
        $guardianFullName = trim(collect([
            $selectedPerson->guardian?->first_name,
            $selectedPerson->guardian?->last_name,
        ])->filter()->implode(' '));
        $guardianStatus = $selectedPerson->familyStatus?->guardianRelationType?->title ?: '-';
        if ($guardianFullName !== '') {
            $guardianStatus .= ' - ' . $guardianFullName;
        }
        $disabilityValue = $selectedPerson->has_disability
            ? ($selectedPerson->disabilityType?->name ?: 'دارد')
            : 'ندارد';
        $disabilityDescription = $selectedPerson->has_disability
            ? ($selectedPerson->disability_description ?: '-')
            : '-';
        $identityItems = [
            ['label' => 'نام و نام خانوادگی', 'value' => $selectedPerson->full_name ?: '-', 'icon' => 'bi-person'],
            ['label' => 'نام پدر', 'value' => $selectedPerson->father_name ?: '-', 'icon' => 'bi-person-heart'],
            ['label' => 'کد ملی', 'value' => $selectedPerson->national_id ?: '-', 'icon' => 'bi-credit-card-2-front', 'dir' => 'ltr'],
            ['label' => 'تاریخ تولد', 'value' => $birthDateValue, 'icon' => 'bi-calendar3', 'age' => $ageBreakdown],
            ['label' => 'وضعیت سادات', 'value' => $selectedPerson->sadaat_status === 'sadaat' ? 'سادات' : 'عام', 'icon' => 'bi-patch-check'],
            ['label' => 'شماره موبایل', 'value' => $selectedPerson->phone_number ?: '-', 'icon' => 'bi-phone', 'dir' => 'ltr'],
        ];
        $educationWorkItems = [
            ['label' => 'وضعیت تحصیلی', 'value' => $educationStatus, 'icon' => 'bi-mortarboard', 'meta_label' => 'مقطع', 'meta_value' => $educationLevelGrade],
            ['label' => 'مهارت‌ها', 'value' => $skills ?: ($selectedPerson->skills_description ?: '-'), 'icon' => 'bi-stars'],
            ['label' => 'اشتغال مددجو', 'value' => $employmentStatus, 'icon' => 'bi-briefcase'],
        ];
        $householdGuardianItems = [
            ['label' => 'وضعیت سرپرست', 'value' => $guardianStatus, 'icon' => 'bi-person-badge'],
            ['label' => 'کد ملی سرپرست', 'value' => $selectedPerson->guardian?->national_code ?: '-', 'icon' => 'bi-credit-card', 'dir' => 'ltr'],
            ['label' => 'شغل سرپرست', 'value' => $guardianJob ?: '-', 'icon' => 'bi-tools'],
            ['label' => 'تعداد اعضای خانوار', 'value' => filled($selectedPerson->guardian?->children_in_house) ? number_format((int) $selectedPerson->guardian->children_in_house) . ' نفر' : '-', 'icon' => 'bi-people'],
            ['label' => 'آدرس منزل سرپرست', 'value' => $selectedPerson->guardian?->residence?->address ?: '-', 'icon' => 'bi-house-door'],
            ['label' => 'مددکار اختصاص‌یافته', 'value' => $selectedPerson->guardian?->socialWorker?->full_name ?: '-', 'icon' => 'bi-person-check'],
        ];
        $healthSupportItems = [
            ['label' => 'نوع آسیب', 'value' => $harmTypes ?: '-', 'icon' => 'bi-shield-exclamation'],
            ['label' => 'نوع بیماری / معلولیت', 'value' => $disabilityValue, 'icon' => 'bi-heart-pulse'],
            ['label' => 'توضیحات بیماری / معلولیت', 'value' => $disabilityDescription, 'icon' => 'bi-clipboard2-pulse'],
            ['label' => 'نهاد حامی', 'value' => $supportOrganizationName, 'icon' => 'bi-diagram-3'],
            ['label' => 'سطح نیاز', 'value' => $selectedPerson->needsLevel?->levelType?->title ?: '-', 'icon' => 'bi-bar-chart-steps'],
            ['label' => 'شرح وضعیت مددجو', 'value' => $selectedPerson->client_case_history ?: '-', 'icon' => 'bi-journal-text'],
        ];
        $detailSections = [
            [
                'title' => 'اطلاعات هویتی',
                'subtitle' => 'مشخصات اصلی مددجو',
                'icon' => 'bi-person-vcard',
                'accent' => 'text-cyan-700 bg-cyan-50 ring-cyan-100',
                'metaAccent' => 'text-cyan-700 bg-cyan-50 ring-cyan-100',
                'items' => $identityItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
            [
                'title' => 'تحصیل و مهارت',
                'subtitle' => 'وضعیت آموزشی، توانمندی و اشتغال',
                'icon' => 'bi-journal-check',
                'accent' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
                'metaAccent' => 'text-emerald-700 bg-emerald-50 ring-emerald-100',
                'items' => $educationWorkItems,
                'grid' => 'md:grid-cols-3',
            ],
            [
                'title' => 'خانوار و سرپرست',
                'subtitle' => 'اطلاعات سرپرست، خانوار و مددکار',
                'icon' => 'bi-people-fill',
                'accent' => 'text-amber-700 bg-amber-50 ring-amber-100',
                'metaAccent' => 'text-amber-700 bg-amber-50 ring-amber-100',
                'items' => $householdGuardianItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
            [
                'title' => 'سلامت و حمایت',
                'subtitle' => 'آسیب‌پذیری، نیاز و پوشش حمایتی',
                'icon' => 'bi-heart-pulse',
                'accent' => 'text-rose-700 bg-rose-50 ring-rose-100',
                'metaAccent' => 'text-rose-700 bg-rose-50 ring-rose-100',
                'items' => $healthSupportItems,
                'grid' => 'sm:grid-cols-2 xl:grid-cols-3',
            ],
        ];
        $personImages = collect([
            [
                'label' => 'عکس پروفایل',
                'url' => $selectedPerson->profile_photo ? asset($selectedPerson->profile_photo) : asset('images/no-image-profile.png?v=2'),
            ],
            [
                'label' => 'عکس شناسنامه',
                'url' => $selectedPerson->photo_birth_certificate ? asset($selectedPerson->photo_birth_certificate) : null,
            ],
            [
                'label' => 'عکس کارت ملی',
                'url' => $selectedPerson->photo_id_card ? asset($selectedPerson->photo_id_card) : null,
            ],
            [
                'label' => 'عکس کارت حمایتی',
                'url' => $selectedPerson->supportCoverage?->support_card_image ? asset($selectedPerson->supportCoverage->support_card_image) : null,
            ],
        ])->filter(fn ($image) => filled($image['url']))->values();
    @endphp

    <div
        wire:key="person-modal"
        x-data="{
            open: @js($showPersonModal),
            viewerOpen: false,
            viewerImages: @js($personImages),
            viewerIndex: 0,
            copiedField: null,
            previousActiveElement: null,
            closing: false,
            scrollLockStyles: null,
            allowBackdropClose: false,
            enableHistoryClose: false,
            historyStatePushed: false,
            closingFromPopstate: false,
            popstateHandler: null,
            init() {
                this.previousActiveElement = document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
                this.allowBackdropClose = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                this.enableHistoryClose = window.matchMedia('(max-width: 767px), (pointer: coarse)').matches;
                this.lockScroll();
                this.setupHistoryClose();

                this.$nextTick(() => {
                    (this.$refs.closeButton || this.$refs.dialog)?.focus({ preventScroll: true });
                });
            },
            destroy() {
                this.teardownHistoryClose();
                this.unlockScroll();
            },
            setupHistoryClose() {
                if (! this.enableHistoryClose || ! window.history?.pushState) return;

                window.history.pushState({ ...(window.history.state || {}), personDetailsModal: true }, '', window.location.href);
                this.historyStatePushed = true;

                this.popstateHandler = () => {
                    if (! this.historyStatePushed || this.closing) return;

                    if (this.viewerOpen) {
                        this.closeViewer();
                        window.history.pushState({ ...(window.history.state || {}), personDetailsModal: true }, '', window.location.href);
                        return;
                    }

                    this.closingFromPopstate = true;
                    this.close(true);
                };

                window.addEventListener('popstate', this.popstateHandler);
            },
            teardownHistoryClose() {
                if (! this.popstateHandler) return;

                window.removeEventListener('popstate', this.popstateHandler);
                this.popstateHandler = null;
            },
            lockScroll() {
                if (this.scrollLockStyles) return;

                this.scrollLockStyles = {
                    bodyOverflow: document.body.style.overflow,
                    bodyPosition: document.body.style.position,
                    bodyTop: document.body.style.top,
                    bodyWidth: document.body.style.width,
                    htmlOverflow: document.documentElement.style.overflow,
                    bodyPaddingRight: document.body.style.paddingRight,
                    scrollY: window.scrollY,
                };

                const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;

                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.top = `-${this.scrollLockStyles.scrollY}px`;
                document.body.style.width = '100%';

                if (scrollbarWidth > 0) {
                    document.body.style.paddingRight = `${scrollbarWidth}px`;
                }
            },
            unlockScroll() {
                if (! this.scrollLockStyles) return;

                document.body.style.overflow = this.scrollLockStyles.bodyOverflow;
                document.body.style.position = this.scrollLockStyles.bodyPosition;
                document.body.style.top = this.scrollLockStyles.bodyTop;
                document.body.style.width = this.scrollLockStyles.bodyWidth;
                document.documentElement.style.overflow = this.scrollLockStyles.htmlOverflow;
                document.body.style.paddingRight = this.scrollLockStyles.bodyPaddingRight;
                window.scrollTo(0, this.scrollLockStyles.scrollY);
                this.scrollLockStyles = null;
            },
            focusableElements(scope) {
                if (! scope) return [];

                return Array.from(scope.querySelectorAll([
                    'a[href]',
                    'button:not([disabled])',
                    'input:not([disabled])',
                    'select:not([disabled])',
                    'textarea:not([disabled])',
                    '[tabindex]:not([tabindex=\'-1\'])',
                ].join(','))).filter((element) => {
                    return element.offsetParent !== null || element === document.activeElement;
                });
            },
            trapFocus(event) {
                if (! this.open || this.closing) return;

                const scope = this.viewerOpen ? this.$refs.viewer : this.$refs.dialog;
                const focusable = this.focusableElements(scope);

                if (! focusable.length) {
                    event.preventDefault();
                    scope?.focus({ preventScroll: true });
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (! scope.contains(document.activeElement)) {
                    event.preventDefault();
                    first.focus({ preventScroll: true });
                    return;
                }

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus({ preventScroll: true });
                    return;
                }

                if (! event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus({ preventScroll: true });
                }
            },
            openViewer(index = 0) {
                if (! this.viewerImages.length) return;
                this.viewerIndex = index;
                this.viewerOpen = true;
                this.$nextTick(() => {
                    (this.$refs.viewerCloseButton || this.$refs.viewer)?.focus({ preventScroll: true });
                });
            },
            closeViewer() {
                this.viewerOpen = false;
                this.$nextTick(() => {
                    (this.$refs.dialog || this.$refs.closeButton)?.focus({ preventScroll: true });
                });
            },
            nextImage() {
                if (this.viewerImages.length < 2) return;
                this.viewerIndex = (this.viewerIndex + 1) % this.viewerImages.length;
            },
            previousImage() {
                if (this.viewerImages.length < 2) return;
                this.viewerIndex = (this.viewerIndex - 1 + this.viewerImages.length) % this.viewerImages.length;
            },
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
            closeFromBackdrop() {
                if (! this.allowBackdropClose || this.viewerOpen) return;

                this.close();
            },
            close(skipHistoryBack = false) {
                if (this.closing) return;

                if (this.historyStatePushed && ! this.closingFromPopstate && ! skipHistoryBack) {
                    this.historyStatePushed = false;

                    try {
                        window.history.back();
                    } catch (error) {
                        // The modal must still close even if browser history cannot be adjusted.
                    }
                }

                this.closing = true;
                this.historyStatePushed = false;
                this.closeViewer();
                this.open = false;
                setTimeout(() => {
                    Promise.resolve($wire.closePersonModal()).finally(() => {
                        this.unlockScroll();
                        this.$nextTick(() => {
                            if (this.previousActiveElement?.isConnected) {
                                this.previousActiveElement.focus({ preventScroll: true });
                            }
                        });
                    });
                }, 220);
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
        @keydown.escape.window="viewerOpen ? closeViewer() : close()"
        @keydown.tab="trapFocus($event)"
        @keydown.arrow-right.window="if (viewerOpen) nextImage()"
        @keydown.arrow-left.window="if (viewerOpen) previousImage()"
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
            class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 sm:h-auto sm:max-h-[90vh] sm:rounded-3xl"
            @click.stop
            tabindex="-1"
        >
            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 sm:px-5 sm:py-4">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span>پرونده مددجو</span>
                    </div>
                    <h2 class="mt-2 truncate text-lg font-extrabold text-slate-900 sm:text-xl">
                        {{ $selectedPerson->full_name ?: 'بدون نام' }}
                    </h2>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                        <span dir="ltr">{{ $selectedPerson->formatted_person_code ?: ($selectedPerson->person_code ?: '-') }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span dir="ltr">{{ $selectedPerson->national_id ?: '-' }}</span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if($showEditAction)
                        <button
                            type="button"
                            wire:click="editPerson({{ $selectedPerson->id }})"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                            aria-label="ویرایش"
                        >
                            <i class="bi bi-pencil-square text-base"></i>
                        </button>
                    @endif
                    <button
                        x-ref="closeButton"
                        type="button"
                        @click="close()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-2xl leading-none text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        aria-label="بستن"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <div class="grid min-h-0 flex-1 gap-4 overflow-y-auto bg-slate-50 p-3 sm:p-5 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-5">
                <aside class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:sticky lg:top-0 lg:self-start">
                    <div class="overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200" style="aspect-ratio: 4 / 5;">
                        <img
                            src="{{ $selectedPerson->profile_photo ? asset($selectedPerson->profile_photo) : asset('images/no-image-profile.png?v=2') }}"
                            alt="تصویر مددجو"
                            class="h-full w-full cursor-zoom-in object-cover transition duration-200 hover:scale-[1.02]"
                            @click="openViewer(0)"
                        >
                    </div>

                    <div class="mt-3 space-y-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">نام کامل</p>
                            <p class="mt-1 break-words text-base font-extrabold leading-7 text-slate-900">{{ $selectedPerson->full_name ?: '-' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[11px] font-semibold text-slate-500">کد مددجو</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900" dir="ltr">{{ $selectedPerson->formatted_person_code ?: ($selectedPerson->person_code ?: '-') }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[11px] font-semibold text-slate-500">سطح نیاز</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $selectedPerson->needsLevel?->levelType?->title ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($personImages->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-2 lg:grid-cols-3">
                            @foreach($personImages->take(4) as $imageIndex => $image)
                                <button
                                    type="button"
                                    class="group relative overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200"
                                    style="aspect-ratio: 1 / 1;"
                                    @click="openViewer({{ $imageIndex }})"
                                    aria-label="{{ $image['label'] }}"
                                >
                                    <img src="{{ $image['url'] }}" alt="{{ $image['label'] }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </aside>

                <section class="min-w-0 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-sm font-extrabold text-slate-900">اطلاعات پرونده</p>
                        <p class="mt-1 text-xs leading-6 text-slate-500">جزئیات فردی، خانوادگی، آموزشی و حمایتی مددجو</p>
                        <div class="mt-2 inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-medium text-slate-600">
                            <span class="text-slate-400">اپراتور ثبت:</span>
                            <span class="truncate text-slate-700">{{ $creatorName }}</span>
                        </div>
                    </div>

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
                                    @php
                                        $isAssignedSocialWorker = $item['label'] === 'مددکار اختصاص‌یافته';
                                    @endphp
                                    <div class="flex min-h-[4.75rem] items-start gap-3 rounded-xl border p-3 {{ $isAssignedSocialWorker ? 'border-cyan-200 bg-cyan-50/70 shadow-sm' : 'border-slate-100 bg-slate-50' }}">
                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $isAssignedSocialWorker ? 'bg-white text-cyan-700 ring-cyan-200' : 'bg-white text-slate-500 ring-slate-200' }}">
                                            <i class="bi {{ $item['icon'] }} text-sm"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold {{ $isAssignedSocialWorker ? 'text-cyan-700' : 'text-slate-500' }}">{{ $item['label'] }}</p>
                                            @if($item['label'] === 'کد ملی')
                                                <div class="mt-1 flex items-center gap-2">
                                                    <p class="min-w-0 break-words text-sm font-bold leading-6 text-slate-900" @if(isset($item['dir'])) dir="{{ $item['dir'] }}" @endif>
                                                        {{ $item['value'] }}
                                                    </p>
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        @click="copyText(@js($item['value']), 'national-id')"
                                                        aria-label="کپی کد ملی"
                                                    >
                                                        <i class="bi bi-copy text-xs" x-show="copiedField !== 'national-id'"></i>
                                                        <i class="bi bi-check2 text-sm text-emerald-600" x-show="copiedField === 'national-id'" style="display: none;"></i>
                                                    </button>
                                                    <span
                                                        x-show="copiedField === 'national-id'"
                                                        x-transition.opacity.duration.150ms
                                                        class="shrink-0 text-[11px] font-medium text-emerald-600"
                                                        style="display: none;"
                                                    >
                                                        کپی شد
                                                    </span>
                                                </div>
                                            @else
                                                <p class="mt-1 break-words text-sm font-bold leading-6 {{ $isAssignedSocialWorker ? 'text-cyan-950' : 'text-slate-900' }}" @if(isset($item['dir'])) dir="{{ $item['dir'] }}" @endif>
                                                    {{ $item['value'] }}
                                                </p>
                                            @endif
                                            @if(!empty($item['age']))
                                                <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $section['metaAccent'] }}">
                                                    {{ $item['age'] }}
                                                </span>
                                            @endif
                                            @if(isset($item['meta_label']))
                                                <div class="mt-2 inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $section['metaAccent'] }}">
                                                    <span class="shrink-0 opacity-75">{{ $item['meta_label'] }}:</span>
                                                    <span class="truncate">{{ $item['meta_value'] }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($item['note']))
                                                <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $section['metaAccent'] }}">
                                                    {{ $item['note'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            </div>

            <div
                x-ref="viewer"
                x-show="viewerOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 z-20 flex items-center justify-center bg-slate-950/90 p-3 sm:p-4"
                @click.self="closeViewer()"
                tabindex="-1"
                style="display: none;"
            >
                <button
                    type="button"
                    class="absolute left-3 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-white/10 text-2xl text-white shadow-lg transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40 sm:left-6"
                    @click.stop="previousImage()"
                    :disabled="viewerImages.length < 2"
                    aria-label="تصویر قبلی"
                >
                    &#8249;
                </button>

                <div class="relative flex w-full max-w-4xl flex-col items-center gap-3" @click.stop>
                    <button
                        x-ref="viewerCloseButton"
                        type="button"
                        class="absolute right-0 top-0 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-2xl leading-none text-white shadow-lg transition hover:bg-white/20"
                        @click="closeViewer()"
                        aria-label="بستن نمایشگر تصویر"
                    >
                        &times;
                    </button>

                    <template x-if="viewerImages[viewerIndex]">
                        <div class="flex w-full flex-col items-center gap-3 pt-8">
                            <img
                                :src="viewerImages[viewerIndex].url"
                                :alt="viewerImages[viewerIndex].label"
                                class="max-h-[72vh] w-auto max-w-full rounded-2xl bg-white object-contain shadow-2xl ring-1 ring-white/10"
                            >
                            <div class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white shadow-lg">
                                <span x-text="viewerImages[viewerIndex].label"></span>
                                <span class="mx-2 text-white/60">|</span>
                                <span x-text="`${viewerIndex + 1} / ${viewerImages.length}`"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <button
                    type="button"
                    class="absolute right-3 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-white/10 text-2xl text-white shadow-lg transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40 sm:right-6"
                    @click.stop="nextImage()"
                    :disabled="viewerImages.length < 2"
                    aria-label="تصویر بعدی"
                >
                    &#8250;
                </button>
            </div>
        </div>
    </div>
@endif
