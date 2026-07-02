<div class="space-y-4" dir="rtl">
    @php
        $badgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'ongoing' => 'bg-amber-100 text-amber-700',
            'closed' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="min-h-[104px] border-b border-blue-700/30 bg-[#2563EB] px-4 py-4 text-white sm:px-6 sm:py-6">
            <div class="flex flex-col gap-3 sm:gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-lg font-extrabold leading-tight text-white sm:mt-2 sm:text-2xl">مدیریت فعالیت‌ها</h1>
                    <p class="mt-1 hidden max-w-3xl text-sm leading-6 text-blue-50/90 sm:mt-2 sm:block">فعالیت‌ها را ایجاد، ویرایش و در چرخه برگزاری مدیریت کنید.</p>
                </div>
                @can('full-access')
                    <button type="button" wire:click="createActivity" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60">افزودن فعالیت</button>
                @endcan
            </div>
        </div>

        <div class="space-y-4 p-4">
            @if (session('activity-success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('activity-success') }}</div>
            @endif

            @error('status')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror
            @error('starts_at')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror
            @error('startsFrom')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror
            @error('startsUntil')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
            @enderror

            <div class="space-y-3 rounded-2xl border border-slate-100 bg-slate-50/40 p-3">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو کد، نام، مکان یا ثبت‌کننده" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <i class="bi bi-search text-sm"></i>
                    </span>
                </div>

                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <select wire:model.live="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        <option value="all">همه وضعیت‌ها</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="typeFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        <option value="all">همه نوع‌ها</option>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-semibold text-slate-600">بازه زمانی:</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" wire:click="applyDatePreset('today')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">امروز</button>
                            <button type="button" wire:click="applyDatePreset('week')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">این هفته</button>
                            <button type="button" wire:click="applyDatePreset('month')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">این ماه</button>
                            <button type="button" wire:click="applyDatePreset('30days')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">30 روز</button>
                            <button type="button" wire:click="applyDatePreset('all')" class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-slate-300">همه</button>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">تاریخ شروع <span class="text-slate-400">(مثال: 1403/01/15)</span></label>
                            <input type="text" wire:model.live.debounce.500ms="startsFrom" placeholder="1403/01/15" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100" data-jdp>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">تاریخ پایان <span class="text-slate-400">(مثال: 1403/01/20)</span></label>
                            <input type="text" wire:model.live.debounce.500ms="startsUntil" placeholder="1403/01/20" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100" data-jdp>
                        </div>
                    </div>

                    @if($errors->has('startsFrom') || $errors->has('startsUntil'))
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                            @error('startsFrom'){{ $message }}@enderror
                            @error('startsUntil'){{ $message }}@enderror
                        </div>
                    @endif
                </div>

                <div class="flex gap-2">
                    <button type="button" wire:click="resetFilters" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                        <i class="bi bi-arrow-clockwise me-1.5 text-xs"></i>
                        ریست کردن
                    </button>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-3">
                    @forelse($activities as $activity)
                        @php
                            $creator = $activity->creator;
                            $creatorName = $creator?->full_name ?: $creator?->name ?: 'نامشخص';
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-violet-200 hover:bg-slate-50/40">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h2 class="min-w-0 flex-1 truncate text-base font-bold text-slate-900">{{ $activity->name }}</h2>
                                        <span class="inline-flex max-w-28 shrink-0 items-center truncate rounded-full px-2 py-0.5 text-[10px] font-bold leading-5 ring-1 ring-inset ring-black/5 sm:max-w-none sm:px-3 sm:py-1 sm:text-[11px] {{ $badgeClasses[$activity->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusOptions[$activity->status] ?? $activity->status }}</span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                        <span class="font-medium text-slate-600">{{ $this->formatJalaliDateTime($activity->starts_at) }}</span>
                                        <span class="text-slate-300">|</span>
                                        <span>{{ $activity->location ?: '-' }}</span>
                                        <span class="text-slate-300">|</span>
                                        <span>{{ $activity->attendances_count }} نفر</span>
                                    </div>

                                    @php
                                        $capacityInfo = $this->getCapacityInfo($activity);
                                        $capacityPercentage = $capacityInfo['percentage'];
                                        $capacityClass = $capacityInfo['class'];
                                        $capacityText = $capacityInfo['text'];
                                        $isUnlimited = $capacityInfo['isUnlimited'];
                                    @endphp

                                    @if(!$isUnlimited)
                                        <div class="mt-3 w-full space-y-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] font-medium text-slate-600">ظرفیت:</span>
                                                <span class="text-[11px] font-bold {{ $capacityClass }}">{{ $capacityText }}</span>
                                            </div>
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full transition-all duration-300 {{ $capacityInfo['barClass'] }}" style="width: {{ min(100, $capacityPercentage) }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-3">
                                            <span class="text-[11px] font-medium text-slate-500">ظرفیت: نامحدود</span>
                                        </div>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 font-medium text-slate-500">{{ $activity->code }}</span>
                                        <span class="rounded-lg bg-violet-50 px-2.5 py-1 font-medium text-violet-700">{{ $typeOptions[$activity->activity_type] ?? $activity->activity_type }}</span>
                                        <span>پایان: {{ $this->formatJalaliDateTime($activity->ends_at) }}</span>
                                        <span>ثبت‌کننده: {{ $creatorName }}</span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center justify-end gap-2 lg:pt-1">
                                    @if($activity->status === 'ongoing')
                                        <button type="button" wire:click="openScanner({{ $activity->id }})" class="inline-flex items-center gap-1.5 rounded-full bg-cyan-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-cyan-700">
                                            <i class="bi bi-qr-code text-sm"></i>
                                            ثبت حضور
                                        </button>
                                    @else
                                        <button type="button" wire:click="selectActivity({{ $activity->id }})" class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-xs font-bold text-violet-700 transition hover:bg-violet-100">
                                            <i class="bi bi-info-circle text-sm"></i>
                                            جزئیات
                                        </button>
                                    @endif

                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                            <i class="bi bi-list text-lg font-bold"></i>
                                        </button>

                                        <div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 z-10 mt-2 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg" style="display: none;">
                                            @if($activity->status === 'ongoing')
                                                <button type="button" wire:click="selectActivity({{ $activity->id }})" class="w-full px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                                    <i class="bi bi-info-circle me-2 text-slate-500"></i>
                                                    جزئیات و مدیریت
                                                </button>
                                                <div class="border-t border-slate-100"></div>
                                            @endif

                                            @can('full-access')
                                                <button type="button" wire:click="editActivity({{ $activity->id }})" class="w-full px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                                    <i class="bi bi-pencil me-2 text-slate-500"></i>
                                                    ویرایش فعالیت
                                                </button>
                                                <button type="button" wire:click="openOperatorAssignment({{ $activity->id }})" class="w-full px-4 py-2.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                                    <i class="bi bi-person-badge me-2 text-slate-500"></i>
                                                    تخصیص اپراتور
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm font-medium text-slate-500">فعالیتی با این فیلترها یافت نشد.</div>
                    @endforelse

                    @if($activities->hasPages())
                        <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3">
                            {{ $activities->links('vendor.livewire.tailwind-mobile-persian') }}
                        </div>
                    @endif
                </div>

                <aside class="{{ $selectedActivity ? 'order-first xl:order-none' : 'hidden xl:block' }} rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                    @if($selectedActivity)
                        <div class="space-y-4">
                            <div class="border-b border-slate-200 pb-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-400">جزئیات فعالیت</p>
                                        <h2 class="mt-1 truncate text-lg font-bold text-slate-900">{{ $selectedActivity->name }}</h2>
                                    </div>
                                    <button type="button" wire:click="clearSelectedActivity" class="shrink-0 text-xs font-semibold text-slate-400 hover:text-slate-600">بستن</button>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <h3 class="text-xs font-semibold text-slate-500">خلاصه</h3>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                                    <div><span class="block text-slate-400">کد</span><strong class="mt-0.5 block text-sm font-semibold text-slate-800">{{ $selectedActivity->code }}</strong></div>
                                    <div><span class="block text-slate-400">وضعیت</span><strong class="mt-0.5 block text-sm font-semibold text-slate-800">{{ $statusOptions[$selectedActivity->status] ?? $selectedActivity->status }}</strong></div>
                                    <div><span class="block text-slate-400">ظرفیت</span><strong class="mt-0.5 block text-sm font-semibold text-slate-800">{{ $selectedActivity->capacity ?: 'نامحدود' }}</strong></div>
                                    <div><span class="block text-slate-400">کل حضور</span><strong class="mt-0.5 block text-sm font-semibold text-slate-800">{{ $selectedActivity->attendances_count }}</strong></div>
                                    <div><span class="block text-slate-400">درصد ظرفیت</span><strong class="mt-0.5 block text-sm font-semibold text-slate-800">{{ $selectedActivity->capacity ? min(100, round(($selectedActivity->present_attendances_count / max(1, $selectedActivity->capacity)) * 100)) . '%' : '-' }}</strong></div>
                                    <div><span class="block text-slate-400">حاضر / غایب</span><strong class="mt-0.5 block text-sm font-semibold text-slate-800">{{ $selectedActivity->present_attendances_count }} / {{ $selectedActivity->absent_attendances_count }}</strong></div>
                                </div>
                            </div>

                            <div class="space-y-2 border-t border-slate-200 pt-4 text-xs leading-6 text-slate-600">
                                <h3 class="text-xs font-semibold text-slate-500">اطلاعات برگزاری</h3>
                                <p><strong class="text-slate-700">شروع:</strong> {{ $this->formatJalaliDateTime($selectedActivity->starts_at) }}</p>
                                <p><strong class="text-slate-700">پایان:</strong> {{ $this->formatJalaliDateTime($selectedActivity->ends_at) }}</p>
                                <p><strong class="text-slate-700">مکان:</strong> {{ $selectedActivity->location ?: '-' }}</p>
                                <p><strong class="text-slate-700">توضیحات:</strong> {{ $selectedActivity->description ?: 'ثبت نشده' }}</p>
                                <p><strong class="text-slate-700">یادداشت وضعیت:</strong> {{ $selectedActivity->status_notes ?: 'ثبت نشده' }}</p>
                            </div>

                            @can('full-access')
                                <div class="space-y-2 border-t border-slate-200 pt-4">
                                    <label class="block text-xs font-semibold text-slate-500">یادداشت تغییر وضعیت</label>
                                    <textarea wire:model="transitionNotes" rows="2" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100"></textarea>
                                    <div class="flex flex-wrap gap-2">
                                        @forelse($this->allowedTransitionTargets($selectedActivity) as $targetStatus)
                                            <button type="button" wire:click="transitionActivity({{ $selectedActivity->id }}, '{{ $targetStatus }}')" class="rounded-full bg-slate-800 px-3 py-2 text-xs font-bold text-white hover:bg-slate-900">
                                                تغییر به {{ $statusOptions[$targetStatus] ?? $targetStatus }}
                                            </button>
                                        @empty
                                            <span class="text-xs font-semibold text-slate-400">تغییر وضعیت دیگری مجاز نیست.</span>
                                        @endforelse
                                    </div>
                                </div>
                            @endcan

                            <div class="space-y-2 border-t border-slate-200 pt-4">
                                <div class="flex flex-wrap gap-2">
                                    @if($selectedActivity->status === 'ongoing')
                                        <button type="button" wire:click="openScanner({{ $selectedActivity->id }})" class="inline-flex items-center gap-1.5 rounded-full bg-cyan-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-cyan-700">
                                            <i class="bi bi-qr-code text-sm"></i>
                                            ثبت حضور
                                        </button>
                                    @endif
                                    <button type="button" wire:click="exportAttendances" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        <i class="bi bi-file-earmark-spreadsheet text-sm"></i>
                                        خروجی اکسل
                                    </button>
                                </div>
                                @can('full-access')
                                    <button type="button" wire:click="openOperatorAssignment({{ $selectedActivity->id }})" class="w-full inline-flex items-center justify-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">
                                        <i class="bi bi-person-badge text-sm"></i>
                                        تخصیص اپراتور
                                    </button>
                                @endcan
                            </div>

                            <div class="space-y-3 border-t border-slate-200 pt-4">
                                <div class="mb-3 flex flex-col gap-2">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-800">فهرست شرکت‌کنندگان</h3>
                                        <p class="mt-1 text-[11px] font-medium text-slate-400">نمایش حداکثر {{ $attendanceDisplayLimit }} حضور آخر؛ برای فهرست کامل از خروجی اکسل استفاده کنید.</p>
                                    </div>
                                    <input type="text" wire:model.live.debounce.300ms="attendanceSearch" placeholder="جستجو مددجو یا ثبت‌کننده" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select wire:model.live="attendanceMethodFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                            <option value="all">همه روش‌ها</option>
                                            @foreach($attendanceMethodOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="attendanceStatusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                            <option value="all">همه وضعیت‌ها</option>
                                            @foreach($attendanceStatusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                                    @forelse($filteredAttendances as $attendance)
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                            <p class="font-bold text-slate-800">{{ $attendance->person?->full_name ?: '-' }}</p>
                                            <p>{{ $attendance->person?->person_code ?: '-' }} · {{ $attendance->person?->national_id ?: '-' }}</p>
                                            <p>روش: {{ $attendanceMethodOptions[$attendance->registration_method] ?? $attendance->registration_method }} · وضعیت: {{ $attendanceStatusOptions[$attendance->status] ?? $attendance->status }}</p>
                                            <p>زمان: {{ $this->formatJalaliDateTime($attendance->checked_in_at) }} · ثبت‌کننده: {{ $attendance->recorder?->full_name ?: $attendance->recorder?->name ?: '-' }}</p>
                                        </div>
                                    @empty
                                        <p class="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400">هنوز حضوری برای این فعالیت ثبت نشده است.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex min-h-72 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm font-medium text-slate-400">برای مشاهده جزئیات و مدیریت چرخه، یک فعالیت را انتخاب کنید.</div>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    @can('full-access')
        @if($assigningOperatorActivityId)
            <div
                x-data
                x-on:close-activity-operator-assignment-modal.window="$wire.closeOperatorAssignment()"
                x-on:keydown.escape.window="$wire.closeOperatorAssignment()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div
                    x-on:click.outside="$wire.closeOperatorAssignment()"
                    class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl"
                >
                    <livewire:admin.activity-operator-assignment
                        :activity-id="$assigningOperatorActivityId"
                        :key="'activity-operator-assignment-modal-' . $assigningOperatorActivityId"
                    />
                </div>
            </div>
        @endif
    @endcan
</div>

<script>
document.addEventListener('livewire:navigated', initializeDatePickers);
document.addEventListener('DOMContentLoaded', initializeDatePickers);

function initializeDatePickers() {
    const datepickers = document.querySelectorAll('[data-jdp]');
    datepickers.forEach(element => {
        if (!element._jdpInitialized) {
            // Prevent manual time input by blocking non-date characters
            element.addEventListener('input', function(e) {
                // Allow only digits, forward slashes (for date format)
                this.value = this.value.replace(/[^\d/]/g, '');
                // Limit to YYYY/MM/DD format
                if (this.value.length > 10) {
                    this.value = this.value.substring(0, 10);
                }
            });

            new jalaliDatepicker.JalaliDatePicker(element, {
                minDate: new Date(1900, 0, 1),
                maxDate: new Date(),
                closeAfterSelect: true,
                format: 'yyyy/MM/dd',
                altFieldFormat: 'yyyy/MM/dd',
                buttons: false,
                showCloseButton: true,
                timePicker: false,
                timePickerSeconds: false,
            });
            element._jdpInitialized = true;
        }
    });
}
</script>
