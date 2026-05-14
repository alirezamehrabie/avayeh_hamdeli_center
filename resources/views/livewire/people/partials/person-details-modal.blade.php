@if($this->selectedPerson)
    @php
        $selectedPerson = $this->selectedPerson;
        $supportOrganization = $selectedPerson->supportCoverage?->organization;
        $supportOrganizationName = $supportOrganization?->slug === 'other'
            ? ($selectedPerson->supportCoverage?->other_organization_name ?: ($supportOrganization?->name ?? '-'))
            : ($supportOrganization?->name ?? '-');
        $harmTypes = $selectedPerson->harmTypes->pluck('title')->filter()->implode('، ');
        $skills = $selectedPerson->skills->pluck('name')->filter()->implode('، ');
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
            ['label' => 'تاریخ تولد', 'value' => $selectedPerson->formatted_birth_date ?? $selectedPerson->birth_date ?? '-'],
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
    @endphp

    <div
        wire:key="person-modal"
        x-data="{
            open: @js($showPersonModal),
            close() {
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
            class="relative w-full max-w-5xl overflow-hidden rounded-3xl border shadow-2xl"
            style="border-color: #f5d0e1; background: linear-gradient(180deg, #ffffff 0%, #fff8fb 100%);"
            @click.stop
        >
            <div class="flex items-start justify-between gap-4 px-6 py-4 text-white" style="background: linear-gradient(to left, #9D174D, #BE185D);">
                <div class="flex items-center gap-4">
                    <div class="shrink-0 overflow-hidden rounded-2xl border-2 border-white/60 bg-white/20 shadow-sm" style="width: 120px; height: 140px; aspect-ratio: 3 / 4;">
                        <img
                            src="{{ $selectedPerson->profile_photo ? asset($selectedPerson->profile_photo) : asset('images/no-image.png') }}"
                            alt="تصویر مددجو"
                            class="h-full w-full object-cover"
                        >
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold">اطلاعات مددجو</h2>
                        <p class="mt-1 text-sm text-white/85">{{ $selectedPerson->full_name ?: 'بدون نام' }}</p>
                    </div>
                </div>
                <button type="button" @click="close()" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25" aria-label="بستن">
                    &times;
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($detailItems as $item)
                        <div class="rounded-2xl border border-slate-100 bg-white/90 p-4 shadow-sm">
                            <p class="text-xs font-semibold text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-1 font-bold leading-7 text-slate-800">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
