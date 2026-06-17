<div class="space-y-4" dir="rtl">
    @php
        $badgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'scheduled' => 'bg-sky-100 text-sky-700',
            'ongoing' => 'bg-amber-100 text-amber-700',
            'closed' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-violet-600 via-fuchsia-600 to-rose-600 px-5 py-4 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold">مدیریت فعالیت‌ها</h1>
                    <p class="mt-1 text-xs text-violet-50/90">فعالیت‌ها را ایجاد، ویرایش و در چرخه برگزاری مدیریت کنید.</p>
                </div>
                <button type="button" wire:click="createActivity" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">افزودن فعالیت</button>
            </div>
        </div>

        <div class="space-y-4 p-4">
            @if (session('activity-success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('activity-success') }}</div>
            @endif

            @error('status')
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
            @enderror
            @error('starts_at')
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
            @enderror

            <div class="grid gap-3 rounded-[26px] border border-slate-200 bg-slate-50/80 p-3 lg:grid-cols-6">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو کد، نام، مکان یا ثبت‌کننده" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm lg:col-span-2 focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                <select wire:model.live="statusFilter" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                    <option value="all">همه وضعیت‌ها</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="typeFilter" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                    <option value="all">همه نوع‌ها</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" wire:model.live.debounce.500ms="startsFrom" placeholder="از تاریخ 1403/01/01" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                <div class="flex gap-2">
                    <input type="text" wire:model.live.debounce.500ms="startsUntil" placeholder="تا تاریخ" class="min-w-0 flex-1 rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                    <button type="button" wire:click="resetFilters" class="rounded-2xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 hover:bg-slate-50">پاک</button>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-3">
                    @forelse($activities as $activity)
                        @php
                            $creator = $activity->creator;
                            $creatorName = $creator?->full_name ?: $creator?->name ?: 'نامشخص';
                        @endphp
                        <article class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm transition hover:border-violet-200 hover:shadow-md">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $activity->code }}</span>
                                        <span class="rounded-full px-3 py-1 text-[11px] font-bold {{ $badgeClasses[$activity->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusOptions[$activity->status] ?? $activity->status }}</span>
                                        <span class="rounded-full bg-violet-50 px-3 py-1 text-[11px] font-bold text-violet-700">{{ $typeOptions[$activity->activity_type] ?? $activity->activity_type }}</span>
                                    </div>
                                    <h2 class="truncate text-base font-black text-slate-800">{{ $activity->name }}</h2>
                                    <div class="mt-2 grid gap-2 text-xs text-slate-500 md:grid-cols-4">
                                        <span>شروع: {{ $this->formatJalaliDateTime($activity->starts_at) }}</span>
                                        <span>پایان: {{ $this->formatJalaliDateTime($activity->ends_at) }}</span>
                                        <span>مکان: {{ $activity->location ?: '-' }}</span>
                                        <span>حضور: {{ $activity->attendances_count }} نفر</span>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-400">ثبت‌کننده: {{ $creatorName }}</p>
                                </div>
                                <div class="flex shrink-0 items-center justify-end gap-2">
                                    <button type="button" wire:click="selectActivity({{ $activity->id }})" class="rounded-full border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100">جزئیات</button>
                                    <button type="button" wire:click="editActivity({{ $activity->id }})" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100">ویرایش</button>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm font-semibold text-slate-500">فعالیتی با این فیلترها یافت نشد.</div>
                    @endforelse
                </div>

                <aside class="rounded-[28px] border border-slate-200 bg-slate-50/80 p-4">
                    @if($selectedActivity)
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-400">جزئیات فعالیت</p>
                                    <h2 class="mt-1 text-lg font-black text-slate-800">{{ $selectedActivity->name }}</h2>
                                </div>
                                <button type="button" wire:click="clearSelectedActivity" class="text-xs font-bold text-slate-400 hover:text-slate-600">بستن</button>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-2xl bg-white p-3"><span class="block text-slate-400">کد</span><strong>{{ $selectedActivity->code }}</strong></div>
                                <div class="rounded-2xl bg-white p-3"><span class="block text-slate-400">وضعیت</span><strong>{{ $statusOptions[$selectedActivity->status] ?? $selectedActivity->status }}</strong></div>
                                <div class="rounded-2xl bg-white p-3"><span class="block text-slate-400">ظرفیت</span><strong>{{ $selectedActivity->capacity ?: 'نامحدود' }}</strong></div>
                                <div class="rounded-2xl bg-white p-3"><span class="block text-slate-400">کل حضور</span><strong>{{ $selectedActivity->attendances_count }}</strong></div>
                                <div class="rounded-2xl bg-white p-3"><span class="block text-slate-400">حاضر</span><strong>{{ $selectedActivity->present_attendances_count }}</strong></div>
                                <div class="rounded-2xl bg-white p-3"><span class="block text-slate-400">غایب</span><strong>{{ $selectedActivity->absent_attendances_count }}</strong></div>
                            </div>

                            <div class="rounded-2xl bg-white p-3 text-xs leading-6 text-slate-600">
                                <p><strong>شروع:</strong> {{ $this->formatJalaliDateTime($selectedActivity->starts_at) }}</p>
                                <p><strong>پایان:</strong> {{ $this->formatJalaliDateTime($selectedActivity->ends_at) }}</p>
                                <p><strong>مکان:</strong> {{ $selectedActivity->location ?: '-' }}</p>
                                <p><strong>توضیحات:</strong> {{ $selectedActivity->description ?: 'ثبت نشده' }}</p>
                                <p><strong>یادداشت وضعیت:</strong> {{ $selectedActivity->status_notes ?: 'ثبت نشده' }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-500">یادداشت تغییر وضعیت</label>
                                <textarea wire:model="transitionNotes" rows="2" class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100"></textarea>
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

                            <div class="rounded-2xl border border-dashed border-violet-200 bg-violet-50 p-3 text-xs text-violet-700">
                                جایگاه اتصال آینده: اسکن QR، آمار حضور و گزارش فعالیت.
                            </div>
                        </div>
                    @else
                        <div class="flex min-h-72 items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm font-semibold text-slate-400">برای مشاهده جزئیات و مدیریت چرخه، یک فعالیت را انتخاب کنید.</div>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</div>
