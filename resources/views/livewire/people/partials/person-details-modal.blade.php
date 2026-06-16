@if($this->selectedPerson)
    @php
        $selectedPerson = $this->selectedPerson;
        $supportOrganization = $selectedPerson->supportCoverage?->organization;
        $showEditAction = method_exists($this, 'editPerson') && auth()->user()?->can('people-edit');
        $supportOrganizationName = $supportOrganization?->slug === 'other'
            ? ($selectedPerson->supportCoverage?->other_organization_name ?: ($supportOrganization?->name ?? '-'))
            : ($supportOrganization?->name ?? '-');
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
            $selectedPerson->education->is_studying => trim('در حال تحصیل' . ($selectedPerson->education->educationLevel?->name ? ' - ' . $selectedPerson->education->educationLevel->name : '')),
            filled($reasonForNotStudying) => trim($reasonForNotStudying . ($selectedPerson->education->educationDegreeLevel?->title ? ' - ' . $selectedPerson->education->educationDegreeLevel->title : '')),
            filled($selectedPerson->education->drop_reason) => 'ترک تحصیل - ' . $selectedPerson->education->drop_reason,
            default => 'در حال تحصیل نیست',
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
        $detailItems = [
            ['label' => 'نام و نام خانوادگی', 'value' => $selectedPerson->full_name ?: '-'],
            ['label' => 'کد ملی', 'value' => $selectedPerson->national_id ?: '-'],
            ['label' => 'نام پدر', 'value' => $selectedPerson->father_name ?: '-'],
            'birthDateValue' => ['label' => 'تاریخ تولد', 'value' => $birthDateValue],
            ['label' => 'نوع آسیب', 'value' => $harmTypes ?: '-'],
            ['label' => 'وضعیت سادات', 'value' => $selectedPerson->sadaat_status === 'sadaat' ? 'سادات' : 'عام'],
            ['label' => 'شماره موبایل', 'value' => $selectedPerson->phone_number ?: '-'],
            ['label' => 'وضعیت تحصیلی', 'value' => $educationStatus],
            ['label' => 'مهارت‌ها', 'value' => $skills ?: ($selectedPerson->skills_description ?: '-')],
            ['label' => 'نهاد حامی', 'value' => $supportOrganizationName],
            ['label' => 'توضیحات نهاد حامی', 'value' => $selectedPerson->supportCoverage?->description ?: '-'],
            ['label' => 'نوع معلولیت', 'value' => $selectedPerson->has_disability ? (($selectedPerson->disabilityType?->name ?? '') . ($selectedPerson->disability_description ? ' - ' . $selectedPerson->disability_description : '')) : 'ندارد'],
            ['label' => 'وضعیت سرپرست', 'value' => $guardianStatus],
            ['label' => 'شغل سرپرست', 'value' => $guardianJob ?: '-'],
            ['label' => 'اشتغال مددجو', 'value' => $employmentStatus],
            ['label' => 'آدرس منزل سرپرست', 'value' => $selectedPerson->guardian?->residence?->address ?: '-'],
            ['label' => 'مددکار اختصاص‌یافته', 'value' => $selectedPerson->guardian?->socialWorker?->full_name ?: '-'],
            ['label' => 'سطح نیاز', 'value' => $selectedPerson->needsLevel?->levelType?->title ?: '-'],
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
            openViewer(index = 0) {
                if (! this.viewerImages.length) return;
                this.viewerIndex = index;
                this.viewerOpen = true;
            },
            closeViewer() {
                this.viewerOpen = false;
            },
            nextImage() {
                if (this.viewerImages.length < 2) return;
                this.viewerIndex = (this.viewerIndex + 1) % this.viewerImages.length;
            },
            previousImage() {
                if (this.viewerImages.length < 2) return;
                this.viewerIndex = (this.viewerIndex - 1 + this.viewerImages.length) % this.viewerImages.length;
            },
            close() {
                this.closeViewer();
                this.open = false;
                setTimeout(() => $wire.closePersonModal(), 220);
            }
        }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-md sm:items-center sm:p-4"
        @keydown.escape.window="viewerOpen ? closeViewer() : close()"
        @keydown.arrow-right.window="if (viewerOpen) nextImage()"
        @keydown.arrow-left.window="if (viewerOpen) previousImage()"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.18),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(15,23,42,0.22),transparent_36%)]" @click="close()"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-t-[2rem] border border-white/70 bg-white shadow-2xl shadow-slate-950/30 ring-1 ring-slate-950/5 sm:max-h-[90vh] sm:rounded-[2rem]"
            @click.stop
        >
            <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-slate-50 via-white to-transparent"></div>

            <div class="relative flex items-start justify-between gap-3 border-b border-slate-100 bg-white/90 px-4 py-4 backdrop-blur-xl sm:px-6 sm:py-5">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-black text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span>پرونده مددجو</span>
                    </div>
                    <h2 class="mt-3 truncate text-xl font-black tracking-tight text-slate-950 sm:text-2xl">
                        {{ $selectedPerson->full_name ?: 'بدون نام' }}
                    </h2>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500">
                        <span class="rounded-full bg-slate-100 px-3 py-1" dir="ltr">{{ $selectedPerson->formatted_person_code ?: ($selectedPerson->person_code ?: '-') }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1" dir="ltr">{{ $selectedPerson->national_id ?: '-' }}</span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if($showEditAction)
                        <button
                            type="button"
                            wire:click="editPerson({{ $selectedPerson->id }})"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                            aria-label="ویرایش"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.768-6.768a2.5 2.5 0 113.536 3.536L12.536 14.536a4 4 0 01-1.414.95L7 17l1.514-4.122a4 4 0 01.95-1.414z"/>
                            </svg>
                        </button>
                    @endif
                    <button
                        type="button"
                        @click="close()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-2xl leading-none text-slate-500 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        aria-label="بستن"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <div class="relative grid min-h-0 flex-1 overflow-y-auto bg-slate-50/60 p-4 sm:p-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:gap-6">
                <aside class="mb-4 rounded-[1.75rem] border border-white bg-white p-4 shadow-sm ring-1 ring-slate-950/5 lg:sticky lg:top-0 lg:mb-0 lg:self-start">
                    <div class="overflow-hidden rounded-[1.5rem] bg-slate-100 shadow-inner ring-1 ring-slate-200" style="aspect-ratio: 3 / 4;">
                        <img
                            src="{{ $selectedPerson->profile_photo ? asset($selectedPerson->profile_photo) : asset('images/no-image-profile.png?v=2') }}"
                            alt="تصویر مددجو"
                            class="h-full w-full cursor-zoom-in object-cover transition duration-300 hover:scale-105"
                            @click="openViewer(0)"
                        >
                    </div>

                    <div class="mt-4 space-y-3">
                        <div>
                            <p class="text-xs font-bold text-slate-400">نام کامل</p>
                            <p class="mt-1 text-base font-black text-slate-950">{{ $selectedPerson->full_name ?: '-' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-[11px] font-bold text-slate-400">کد مددجو</p>
                                <p class="mt-1 truncate text-sm font-black text-slate-900" dir="ltr">{{ $selectedPerson->formatted_person_code ?: ($selectedPerson->person_code ?: '-') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-[11px] font-bold text-slate-400">سطح نیاز</p>
                                <p class="mt-1 truncate text-sm font-black text-slate-900">{{ $selectedPerson->needsLevel?->levelType?->title ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($personImages->count() > 1)
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            @foreach($personImages->take(4) as $imageIndex => $image)
                                <button
                                    type="button"
                                    class="group relative overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200"
                                    style="aspect-ratio: 1 / 1;"
                                    @click="openViewer({{ $imageIndex }})"
                                    aria-label="{{ $image['label'] }}"
                                >
                                    <img src="{{ $image['url'] }}" alt="{{ $image['label'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </aside>

                <section class="min-w-0">
                    <div class="mb-4 rounded-[1.5rem] border border-cyan-100 bg-gradient-to-l from-cyan-50 via-white to-white p-4 shadow-sm">
                        <p class="text-sm font-black text-slate-900">اطلاعات پرونده</p>
                        <p class="mt-1 text-xs leading-6 text-slate-500">جزئیات فردی، خانوادگی، آموزشی و حمایتی مددجو در یک نمای خلاصه و قابل مرور.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($detailItems as $key => $item)

                        @switch($key)
                            @case('birthDateValue')
                                <div class="group rounded-[1.35rem] border border-white bg-white p-4 shadow-sm ring-1 ring-slate-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                                        {{ $item['label'] }}
                                    </p>
                                    <div class="mt-2 flex flex-col items-start gap-2">
                                        <p class="break-words text-sm font-black leading-7 text-slate-900">
                                            {{ $item['value'] }}
                                        </p>
                                        @if($ageBreakdown)
                                            <div class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700 ring-1 ring-cyan-100">
                                                {{ $ageBreakdown }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @break


                            @default
                                <div class="group rounded-[1.35rem] border border-white bg-white p-4 shadow-sm ring-1 ring-slate-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">
                                        {{ $item['label'] }}
                                    </p>

                                    <p class="mt-2 break-words text-sm font-black leading-7 text-slate-900">
                                        {{ $item['value'] }}
                                    </p>
                                </div>
                        @endswitch

                    @endforeach

                    </div>
                </section>
                </div>

            <div
                x-show="viewerOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 z-20 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-sm"
                @click.self="closeViewer()"
                style="display: none;"
            >
                <button
                    type="button"
                    class="absolute left-3 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/10 text-2xl text-white shadow-lg backdrop-blur transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40 sm:left-6"
                    @click.stop="previousImage()"
                    :disabled="viewerImages.length < 2"
                    aria-label="تصویر قبلی"
                >
                    &#8249;
                </button>

                <div class="relative flex w-full max-w-4xl flex-col items-center gap-4" @click.stop>
                    <button
                        type="button"
                        class="absolute right-0 top-0 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/10 text-2xl leading-none text-white shadow-lg backdrop-blur transition hover:bg-white/20"
                        @click="closeViewer()"
                        aria-label="بستن نمایشگر تصویر"
                    >
                        &times;
                    </button>

                    <template x-if="viewerImages[viewerIndex]">
                        <div class="flex w-full flex-col items-center gap-4 pt-8">
                            <img
                                :src="viewerImages[viewerIndex].url"
                                :alt="viewerImages[viewerIndex].label"
                                class="max-h-[70vh] w-auto max-w-full rounded-[1.5rem] bg-white object-contain shadow-2xl ring-1 ring-white/10"
                            >
                            <div class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white shadow-lg backdrop-blur">
                                <span x-text="viewerImages[viewerIndex].label"></span>
                                <span class="mx-2 text-white/60">|</span>
                                <span x-text="`${viewerIndex + 1} / ${viewerImages.length}`"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <button
                    type="button"
                    class="absolute right-3 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/10 text-2xl text-white shadow-lg backdrop-blur transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40 sm:right-6"
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
