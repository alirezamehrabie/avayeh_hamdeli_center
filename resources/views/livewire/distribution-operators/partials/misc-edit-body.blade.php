@php
    $initialExpandedIndex = $activeWorkerGroupIndex ?? (count($miscWorkerGroups) ? array_key_last($miscWorkerGroups) : null);

    // Worker-group indexes that currently carry a validation error, so a collapsed
    // header can flag itself instead of hiding the problem inside the accordion (Item 2).
    $groupErrorIndexes = collect($errors->keys())
        ->map(fn ($key) => preg_match('/^miscWorkerGroups\.(\d+)(\.|$)/', $key, $matches) ? (int) $matches[1] : null)
        ->reject(fn ($index) => $index === null)
        ->unique()
        ->values()
        ->all();

    // How many rows (across all worker groups) reference each category, keyed by
    // normalized name. A count >= 2 means the category is shared, so a unit edit
    // must propagate live; otherwise the unit commits on blur like the quantity,
    // avoiding a server round-trip per change (Item 5).
    $sharedCategoryNameCounts = [];
    foreach ($miscWorkerGroups as $sharedScanGroup) {
        foreach (($sharedScanGroup['categories'] ?? []) as $sharedScanCategory) {
            $sharedScanKey = \App\Models\ServiceCategory::normalizeName((string) ($sharedScanCategory['name'] ?? ''));

            if ($sharedScanKey === '') {
                continue;
            }

            $sharedCategoryNameCounts[$sharedScanKey] = ($sharedCategoryNameCounts[$sharedScanKey] ?? 0) + 1;
        }
    }
@endphp
<div
    x-data="{
        expandedIndex: @js($initialExpandedIndex),
        pendingErrorExpand: false,
        init() {
            // After a failed save, open the first worker group that carries an
            // error so its inline messages aren't hidden behind the accordion.
            if (window.Livewire) {
                Livewire.hook('morph.updated', () => {
                    if (! this.pendingErrorExpand) {
                        return;
                    }

                    this.pendingErrorExpand = false;
                    this.expandFirstErrorGroup();
                });
            }
        },
        expandFirstErrorGroup() {
            this.$nextTick(() => {
                const groups = this.$el.querySelectorAll('[data-worker-group-index]');

                for (const group of groups) {
                    if (! group.querySelector('[data-validation-error]')) {
                        continue;
                    }

                    const index = Number(group.dataset.workerGroupIndex);

                    if (Number.isInteger(index)) {
                        this.expandedIndex = index;
                        this.focusGroup(index);
                    }

                    break;
                }
            });
        },
        isExpanded(index) {
            return this.expandedIndex === index;
        },
        toggleGroup(index) {
            this.expandedIndex = this.expandedIndex === index ? null : index;

            if (this.expandedIndex === index) {
                this.focusGroup(index);
            }
        },
        expandGroup(index) {
            if (! Number.isInteger(index)) {
                return;
            }

            this.expandedIndex = index;
            this.focusGroup(index);
        },
        focusGroup(index) {
            // Bring the freshly-expanded card into view, but only when it is not
            // already comfortably on screen — avoids disorienting jumps.
            this.$nextTick(() => {
                const group = this.$el.querySelector(`[data-worker-group-index='${index}']`);

                if (! group) {
                    return;
                }

                const rect = group.getBoundingClientRect();
                const margin = 24;
                const fullyVisible = rect.top >= margin && rect.bottom <= (window.innerHeight - margin);

                if (! fullyVisible) {
                    group.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        },
        scrollToNewGroup(event) {
            const index = event.detail?.index;

            this.$nextTick(() => {
                const selector = Number.isInteger(index)
                    ? `[data-worker-group-index='${index}']`
                    : '[data-worker-group-index]:last-of-type';
                const group = this.$el.querySelector(selector);

                if (! group) {
                    return;
                }

                group.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }"
    x-on:worker-group-added.window="if (Number.isInteger($event.detail?.index)) { expandedIndex = $event.detail.index; } scrollToNewGroup($event)"
    x-on:worker-group-expand.window="expandGroup($event.detail?.index)"
    x-on:misc-worker-save-attempt.window="pendingErrorExpand = true"
    class="space-y-5 p-4 sm:p-5"
>
    {{-- Service type --}}
    <div>
        <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
        <div
            x-data="{
                serviceType: @entangle('miscServiceType').live,
                isEditing: @js($isEditing ?? false),
                get options() {
                    return [
                        {
                            value: 'individual',
                            title: 'شخصی',
                            subtitle: 'ثبت برای مددجو',
                            activeClass: 'border-rose-300 bg-rose-50/80 shadow-sm',
                            inactiveClass: 'border-slate-200 bg-white hover:border-rose-200 hover:bg-rose-50/40',
                            iconActiveClass: 'bg-rose-600 text-white',
                            iconInactiveClass: 'bg-rose-100 text-rose-700',
                            titleActiveClass: 'text-rose-950',
                            titleInactiveClass: 'text-slate-800',
                            subtitleActiveClass: 'text-rose-700',
                            subtitleInactiveClass: 'text-slate-500',
                            dotActiveClass: 'bg-rose-600',
                        },
                        {
                            value: 'family',
                            title: 'خانوادگی',
                            subtitle: 'ثبت برای سرپرست',
                            activeClass: 'border-amber-400 bg-amber-50 shadow-sm',
                            inactiveClass: 'border-slate-200 bg-white hover:border-amber-200 hover:bg-amber-50/40',
                            iconActiveClass: 'bg-amber-500 text-white',
                            iconInactiveClass: 'bg-amber-100 text-amber-700',
                            titleActiveClass: 'text-amber-950',
                            titleInactiveClass: 'text-slate-800',
                            subtitleActiveClass: 'text-amber-700',
                            subtitleInactiveClass: 'text-slate-500',
                            dotActiveClass: 'bg-amber-500',
                        },
                    ];
                },
                selectServiceType(value) {
                    if (value === this.serviceType) {
                        return;
                    }

                    if (! this.isEditing || ! this.serviceType) {
                        this.serviceType = value;
                        return;
                    }

                    const selected = this.options.find((item) => item.value === value);

                    window.dispatchEvent(new CustomEvent('open-notification-modal', {
                        detail: {
                            config: {
                                type: 'warning',
                                title: 'تغییر نوع خدمت',
                                message: `آیا از تغییر نوع خدمت به «${selected?.title || value}» مطمئن هستید؟`,
                                buttons: [
                                    {
                                        label: 'تأیید تغییر',
                                        action: 'event',
                                        event: 'confirm-misc-service-type-change',
                                        payload: { serviceType: value },
                                        variant: 'warning',
                                    },
                                    {
                                        label: 'انصراف',
                                        action: 'close',
                                        variant: 'secondary',
                                    },
                                ],
                            },
                        },
                    }));
                },
            }"
        >
            <div class="grid gap-2 sm:grid-cols-2">
                <template x-for="item in options" :key="item.value">
                    <button
                        type="button"
                        x-on:click="selectServiceType(item.value)"
                        class="group flex min-h-[56px] w-full items-center gap-2.5 rounded-2xl border px-3 py-2.5 text-right transition focus:outline-none focus:ring-4 focus:ring-emerald-100"
                        x-bind:class="serviceType === item.value ? item.activeClass : item.inactiveClass"
                        x-bind:aria-pressed="(serviceType === item.value).toString()"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition"
                            x-bind:class="serviceType === item.value ? item.iconActiveClass : item.iconInactiveClass"
                        >
                            <template x-if="item.value === 'individual'">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </template>
                            <template x-if="item.value === 'family'">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M9 11a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm6 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4 19a5 5 0 0 1 10 0M13 19a5 5 0 0 1 7 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span x-text="item.title" class="block text-sm font-black leading-5" x-bind:class="serviceType === item.value ? item.titleActiveClass : item.titleInactiveClass"></span>
                            <span x-text="item.subtitle" class="mt-0.5 block text-[11px] font-semibold leading-4" x-bind:class="serviceType === item.value ? item.subtitleActiveClass : item.subtitleInactiveClass"></span>
                        </span>
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full transition" x-bind:class="serviceType === item.value ? item.dotActiveClass : 'bg-slate-200'" aria-hidden="true"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Worker groups --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <p class="text-sm font-black text-slate-800">مددکاران و دسته‌بندی‌ها</p>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">{{ count($miscWorkerGroups) }} مددکار</span>
            </div>
        </div>

        @foreach($miscWorkerGroups as $gi => $group)
            @php
                $groupLocked = (bool) ($group['locked'] ?? false);
                $groupIsExisting = (bool) ($group['is_existing'] ?? false);
                $groupWorkerId = (int) ($group['social_worker_id'] ?? 0);
                $groupWorkerDisplay = (string) ($group['worker_display'] ?? '');
                $groupWorkerCode = (string) ($group['worker_code'] ?? '');
                $groupCategories = $group['categories'] ?? [];
                $groupCategoryCount = count($groupCategories);
                $groupCategoryPreview = collect($groupCategories)
                    ->pluck('name')
                    ->filter(fn ($name) => trim((string) $name) !== '')
                    ->take(4)
                    ->implode('، ');
                $groupHasError = in_array($gi, $groupErrorIndexes, true);
            @endphp

            <div
                data-worker-group-index="{{ $gi }}"
                data-validation-scope
                wire:key="worker-group-{{ $group['uid'] ?? $gi }}"
                class="overflow-hidden rounded-2xl border {{ $groupLocked ? 'border-slate-200 bg-slate-50/70' : 'border-emerald-200 bg-white shadow-sm' }}"
            >
                @if($groupLocked)
                    {{-- Done / collapsed summary --}}
                    <div class="flex items-center gap-3 px-3.5 py-3 sm:px-4" @if(! $groupHasError) title="ویرایش این مددکار تمام شده و جمع شده است؛ برای تغییر، دکمه ویرایش را بزنید" @endif>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $groupHasError ? 'bg-rose-50 text-rose-500 ring-rose-200' : 'bg-emerald-50 text-emerald-600 ring-emerald-100' }}">
                            <i class="bi {{ $groupHasError ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' }} text-sm"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="min-w-0 truncate text-sm font-black text-slate-800">{{ $groupWorkerDisplay ?: 'مددکار' }}</span>
                                <span class="shrink-0 text-[11px] font-bold text-slate-400">کد {{ $groupWorkerCode ?: '-' }}</span>
                            </div>
                            @if($groupHasError)
                                <p class="mt-0.5 truncate text-[11px] font-bold text-rose-600">
                                    این مددکار خطا دارد؛ برای اصلاح، ویرایش کنید
                                </p>
                            @else
                                <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-500">
                                    {{ $groupCategoryCount }} دسته‌بندی @if($groupCategoryPreview !== '')<span class="text-slate-300">·</span> {{ $groupCategoryPreview }}@endif
                                </p>
                            @endif
                        </div>
                        <button
                            type="button"
                            @click="expandGroup({{ $gi }})"
                            wire:click="toggleGroupLock({{ $gi }})"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <i class="bi bi-pencil-square"></i>
                            ویرایش
                        </button>
                    </div>
                @else
                    {{-- Editable --}}
                    <div class="flex items-center gap-2.5 border-b {{ $groupHasError ? 'border-rose-100 bg-rose-50/40' : 'border-slate-100 bg-emerald-50/40' }} px-3.5 py-2.5 sm:px-4">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-black {{ $groupHasError ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $gi + 1 }}</span>
                        <button
                            type="button"
                            @click="toggleGroup({{ $gi }})"
                            :aria-expanded="isExpanded({{ $gi }}).toString()"
                            class="flex min-w-0 flex-1 items-center gap-2 text-right"
                        >
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-black text-slate-800">
                                    {{ $groupWorkerId ? ($groupWorkerDisplay ?: 'مددکار') : 'مددکار جدید' }}
                                </span>
                                <span
                                    x-show="! isExpanded({{ $gi }})"
                                    class="mt-0.5 block truncate text-[11px] font-bold {{ $groupHasError ? 'text-rose-600' : 'text-slate-400' }}"
                                >
                                    @if($groupHasError)
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        این مددکار خطا دارد؛ برای اصلاح باز کنید
                                    @elseif($groupCategoryCount > 0)
                                        {{ $groupCategoryCount }} دسته‌بندی@if($groupCategoryPreview !== '')<span class="text-slate-300"> · </span>{{ $groupCategoryPreview }}@endif
                                    @else
                                        برای مشاهده و ویرایش، باز کنید
                                    @endif
                                </span>
                            </span>
                            @if($groupHasError)
                                <span x-show="! isExpanded({{ $gi }})" class="inline-flex h-2 w-2 shrink-0 rounded-full bg-rose-500" aria-hidden="true"></span>
                            @endif
                            <i
                                class="bi bi-chevron-down shrink-0 text-sm text-slate-400 transition-transform duration-200"
                                :class="isExpanded({{ $gi }}) ? 'rotate-180' : ''"
                            ></i>
                        </button>
                        @if($groupIsExisting)
                            <button
                                type="button"
                                @click.stop
                                wire:click="toggleGroupLock({{ $gi }})"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-500 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                                title="این مددکار جمع می‌شود تا اشتباهی تغییر نکند (ذخیره نهایی با تأیید خدمت انجام می‌شود)"
                                aria-label="اتمام ویرایش این مددکار و جمع‌کردن آن"
                            >
                                <i class="bi bi-check2"></i>
                                تمام شد
                            </button>
                        @endif
                        @if(count($miscWorkerGroups) > 1)
                            <button
                                type="button"
                                x-on:click.stop="
                                    window.dispatchEvent(new CustomEvent('open-notification-modal', {
                                        detail: {
                                            config: {
                                                type: 'warning',
                                                title: 'حذف مددکار',
                                                message: 'آیا از حذف این مددکار و دسته‌بندی‌های ثبت‌شده برای او مطمئن هستید؟',
                                                buttons: [
                                                    {
                                                        label: 'حذف',
                                                        action: 'event',
                                                        event: 'confirm-misc-worker-group-delete',
                                                        payload: { index: {{ $gi }} },
                                                        variant: 'danger',
                                                    },
                                                    {
                                                        label: 'انصراف',
                                                        action: 'close',
                                                        variant: 'secondary',
                                                    },
                                                ],
                                            },
                                        },
                                    }))
                                "
                                class="inline-flex shrink-0 items-center justify-center rounded-lg p-1.5 text-rose-400 transition hover:bg-rose-50 hover:text-rose-600"
                                aria-label="حذف این مددکار"
                            >
                                <i class="bi bi-trash text-sm"></i>
                            </button>
                        @endif
                    </div>

                    <div
                        x-show="isExpanded({{ $gi }})"
                        x-collapse
                        @if($gi !== $initialExpandedIndex) style="display: none;" @endif
                        class="space-y-3 p-3.5 sm:p-4"
                    >
                        @include('livewire.distribution-operators.partials.group-social-worker-selector', [
                            'groupIndex' => $gi,
                            'group' => $group,
                        ])

                        @if(! $groupWorkerId)
                        {{-- Categories stay locked until a helper is selected (helper-first flow). --}}
                        <div class="flex items-center gap-2.5 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-3.5 py-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 ring-1 ring-slate-200">
                                <i class="bi bi-lock text-sm"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-500">دسته‌بندی‌ها</p>
                                <p class="mt-0.5 text-[11px] font-bold leading-5 text-slate-400">ابتدا مددکار را انتخاب کنید تا افزودن دسته‌بندی فعال شود.</p>
                            </div>
                        </div>
                        @else
                        <div class="rounded-xl border border-slate-200 bg-white">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-black text-slate-800">دسته‌بندی‌ها</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $groupCategoryCount }}</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 bg-slate-50/50 p-2.5">
                                @foreach($groupCategories as $ci => $category)
                                    @php
                                        $assignedCategoryKeysForCurrentWorker = collect($groupCategories)
                                            ->except($ci)
                                            ->map(fn ($categoryRow) => mb_strtolower(trim((string) ($categoryRow['name'] ?? ''))))
                                            ->filter()
                                            ->unique()
                                            ->values()
                                            ->all();

                                        $normalizedCategoryName = \App\Models\ServiceCategory::normalizeName((string) ($category['name'] ?? ''));
                                        $isUnitLocked = in_array((int) ($category['id'] ?? 0), $usedCategoryLock['ids'] ?? [], true)
                                            || isset(($usedCategoryLock['names'] ?? [])[$normalizedCategoryName]);
                                        // Shared across worker groups => unit edits must sync live; otherwise defer to blur.
                                        $isSharedCategoryRow = ($sharedCategoryNameCounts[$normalizedCategoryName] ?? 0) >= 2;
                                    @endphp
                                    <div data-validation-scope class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm shadow-slate-200/50 sm:p-3.5">
                                        <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-2.5">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-black text-emerald-700">{{ $ci + 1 }}</span>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black text-slate-700">دسته‌بندی {{ $ci + 1 }}</p>
                                                    <p class="mt-0.5 text-[10px] font-bold text-slate-400">نام، مقدار و واحد این ردیف</p>
                                                </div>
                                            </div>
                                            @if($groupCategoryCount > 1)
                                                <button
                                                    type="button"
                                                    x-on:click="
                                                        window.dispatchEvent(new CustomEvent('open-notification-modal', {
                                                            detail: {
                                                                config: {
                                                                    type: 'warning',
                                                                    title: 'حذف دسته‌بندی',
                                                                    message: 'آیا از حذف این دسته‌بندی برای مددکار انتخاب‌شده مطمئن هستید؟',
                                                                    buttons: [
                                                                        {
                                                                            label: 'حذف',
                                                                            action: 'event',
                                                                            event: 'confirm-misc-worker-category-delete',
                                                                            payload: { groupIndex: {{ $gi }}, categoryIndex: {{ $ci }} },
                                                                            variant: 'danger',
                                                                        },
                                                                        {
                                                                            label: 'انصراف',
                                                                            action: 'close',
                                                                            variant: 'secondary',
                                                                        },
                                                                    ],
                                                                },
                                                            },
                                                        }))
                                                    "
                                                    class="shrink-0 rounded-xl border border-rose-100 bg-rose-50 px-2.5 py-1.5 text-[11px] font-black text-rose-500 transition hover:border-rose-200 hover:bg-rose-100 hover:text-rose-700"
                                                    aria-label="حذف دسته‌بندی {{ $ci + 1 }}"
                                                >
                                                    حذف
                                                </button>
                                            @endif
                                        </div>

                                        <div class="space-y-3">
                                            <div class="space-y-1.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <label class="block text-[11px] font-black text-slate-500">نام دسته‌بندی</label>
                                                    @if(!empty($categoryNameSuggestions))
                                                        <span class="text-[10px] font-bold text-emerald-600">برای انتخاب لمس کنید</span>
                                                    @endif
                                                </div>
                                                <div
                                                    x-data="{
                                                        sheetOpen: false,
                                                        historyActive: false,
                                                        directEntry: false,
                                                        popStateHandler: null,
                                                        scrollLocked: false,
                                                        modelPath: 'miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.name',
                                                        modelIdPath: 'miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.id',
                                                        modelUnitPath: 'miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.unit',
                                                        unitKeys: @js(\App\Models\Service::unitKeys()),
                                                        assignedCategoryKeys: @js($assignedCategoryKeysForCurrentWorker),
                                                        init() {
                                                            this.popStateHandler = () => this.handleSheetPopState();
                                                            window.addEventListener('popstate', this.popStateHandler);
                                                        },
                                                        destroy() {
                                                            if (this.popStateHandler) {
                                                                window.removeEventListener('popstate', this.popStateHandler);
                                                            }

                                                            this.releasePageScrollLock();
                                                            this.historyActive = false;
                                                        },
                                                        lockPageScroll() {
                                                            if (this.scrollLocked) {
                                                                return;
                                                            }

                                                            const currentLocks = Number(document.documentElement.dataset.distributionOperatorSheetLocks || 0);

                                                            document.documentElement.dataset.distributionOperatorSheetLocks = String(currentLocks + 1);
                                                            document.documentElement.classList.add('overflow-hidden');
                                                            this.scrollLocked = true;
                                                        },
                                                        releasePageScrollLock() {
                                                            if (! this.scrollLocked) {
                                                                return;
                                                            }

                                                            const currentLocks = Number(document.documentElement.dataset.distributionOperatorSheetLocks || 0);
                                                            const remainingLocks = Math.max(0, currentLocks - 1);

                                                            if (remainingLocks > 0) {
                                                                document.documentElement.dataset.distributionOperatorSheetLocks = String(remainingLocks);
                                                            } else {
                                                                delete document.documentElement.dataset.distributionOperatorSheetLocks;
                                                                document.documentElement.classList.remove('overflow-hidden');
                                                            }

                                                            this.scrollLocked = false;
                                                        },
                                                        normalizeCategoryName(name) {
                                                            return String(name || '').trim().toLowerCase();
                                                        },
                                                        isAlreadyAssigned(name) {
                                                            return this.assignedCategoryKeys.includes(this.normalizeCategoryName(name));
                                                        },
                                                        openSheet() {
                                                            if (this.sheetOpen) {
                                                                return;
                                                            }

                                                            this.directEntry = false;
                                                            this.sheetOpen = true;
                                                            this.lockPageScroll();
                                                            this.pushSheetHistory();
                                                        },
                                                        closeSheet({ syncHistory = true } = {}) {
                                                            const shouldRestoreHistory = syncHistory && this.historyActive;
                                                            this.sheetOpen = false;
                                                            this.releasePageScrollLock();

                                                            if (shouldRestoreHistory) {
                                                                this.historyActive = false;
                                                                window.history.back();
                                                            } else if (! syncHistory) {
                                                                this.historyActive = false;
                                                            }
                                                        },
                                                        pushSheetHistory() {
                                                            if (this.historyActive || ! window.matchMedia('(max-width: 639px)').matches) {
                                                                return;
                                                            }

                                                            try {
                                                                window.history.pushState({
                                                                    ...(window.history.state || {}),
                                                                    miscCategorySheet: '{{ $gi }}-{{ $ci }}',
                                                                }, '', window.location.href);
                                                                this.historyActive = true;
                                                            } catch (error) {
                                                                this.historyActive = false;
                                                            }
                                                        },
                                                        handleSheetPopState() {
                                                            if (! this.sheetOpen) {
                                                                this.historyActive = false;
                                                                return;
                                                            }

                                                            this.closeSheet({ syncHistory: false });
                                                        },
                                                        pick(category) {
                                                            const categoryId = Number(category?.id || 0);
                                                            const categoryName = String(category?.name || '');
                                                            const categoryUnit = String(category?.unit || '');

                                                            if (this.isAlreadyAssigned(categoryName)) {
                                                                return;
                                                            }

                                                            this.directEntry = false;
                                                            $wire.set(this.modelIdPath, categoryId > 0 ? categoryId : null);
                                                            if (categoryUnit && this.unitKeys.includes(categoryUnit)) {
                                                                $wire.set(this.modelUnitPath, categoryUnit);
                                                            }
                                                            $wire.set(this.modelPath, categoryName);
                                                            this.closeSheet();
                                                        },
                                                        createNew() {
                                                            this.directEntry = true;
                                                            $wire.set(this.modelIdPath, null);
                                                            this.closeSheet();
                                                            this.$nextTick(() => this.$refs.nameInput?.focus({ preventScroll: true }));
                                                        },
                                                        handleNameFieldInteraction(event) {
                                                            if (this.directEntry) {
                                                                return;
                                                            }

                                                            event.preventDefault();
                                                            this.$refs.nameInput?.blur();
                                                            this.openSheet();
                                                        },
                                                    }"
                                                    x-on:keydown.escape.window="closeSheet()"
                                                >
                                                    <div class="relative">
                                                        @if(!empty($categoryNameSuggestions))
                                                            <span
                                                                x-show="! directEntry"
                                                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-emerald-600"
                                                                aria-hidden="true"
                                                            >

                                                            </span>
                                                        @endif
                                                        <input
                                                            x-ref="nameInput"
                                                            type="text"
                                                            wire:model.blur="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.name"
                                                            @if(!empty($categoryNameSuggestions))
                                                                x-bind:readonly="! directEntry"
                                                                @click="handleNameFieldInteraction($event)"
                                                                @focus="handleNameFieldInteraction($event)"
                                                                x-bind:class="{ 'cursor-pointer caret-transparent': ! directEntry }"
                                                                aria-haspopup="dialog"
                                                            @endif
                                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 {{ !empty($categoryNameSuggestions) ? 'pl-10 pr-11' : '' }} text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                                                            placeholder="نام دسته‌بندی"
                                                            autocomplete="off"
                                                        >
                                                        <input type="hidden" wire:model="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.id">
                                                        @if(!empty($categoryNameSuggestions))
                                                            <button
                                                                type="button"
                                                                @click="openSheet()"
                                                                class="absolute inset-y-0 left-0 my-1 ml-1 inline-flex items-center justify-center rounded-md px-2 text-emerald-700 transition hover:bg-emerald-50"
                                                                aria-label="انتخاب از دسته‌بندی‌های قبلی"
                                                            >
                                                                <i class="bi bi-chevron-down text-sm"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    @if(!empty($categoryNameSuggestions))
                                                        <p x-show="! directEntry" class="mt-1 text-[10px] font-bold text-slate-400">
                                                            دسته‌بندی مورد نظر را از لیست انتخاب کنید
                                                        </p>
                                                        <p x-show="directEntry" class="mt-1 text-[10px] font-bold text-emerald-600">
                                                            ثبت دسته‌بندی جدید فعال شد، نام را تایپ کنید
                                                        </p>
                                                    @endif
                                                    @error("miscWorkerGroups.$gi.categories.$ci.name") <p data-validation-error class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror

                                                    @if(!empty($categoryNameSuggestions))
                                                        <template x-teleport="body">
                                                            <div
                                                                x-show="sheetOpen"
                                                                x-transition.opacity.duration.150ms
                                                                @click="closeSheet()"
                                                                class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-950/50 backdrop-blur-sm sm:items-center sm:p-4"
                                                                style="display: none;"
                                                                dir="rtl"
                                                            >
                                                                <div
                                                                    @click.stop
                                                                    x-show="sheetOpen"
                                                                    x-transition:enter="transition ease-out duration-200"
                                                                    x-transition:enter-start="translate-y-full opacity-80 sm:translate-y-0 sm:scale-95 sm:opacity-0"
                                                                    x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                                                                    x-transition:leave="transition ease-in duration-150"
                                                                    x-transition:leave-start="translate-y-0 opacity-100"
                                                                    x-transition:leave-end="translate-y-full opacity-80"
                                                                    class="flex max-h-[82svh] w-full max-w-md flex-col rounded-t-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[80vh] sm:rounded-3xl"
                                                                    role="dialog"
                                                                    aria-modal="true"
                                                                    aria-label="انتخاب دسته‌بندی"
                                                                >
                                                                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                                                                        <div class="min-w-0">
                                                                            <h3 class="text-sm font-black text-slate-900">دسته‌بندی‌های قبلی</h3>
                                                                            <p class="mt-0.5 text-[11px] font-semibold text-slate-500">یک مورد را انتخاب کنید یا دسته‌بندی جدید بسازید</p>
                                                                        </div>
                                                                        <button type="button" @click="closeSheet()" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50" aria-label="بستن">
                                                                            <i class="bi bi-x-lg text-sm"></i>
                                                                        </button>
                                                                    </div>

                                                                    <div class="border-b border-slate-100 p-3">
                                                                        <button
                                                                            type="button"
                                                                            @click="createNew()"
                                                                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/60 px-3 py-3 text-sm font-black text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50"
                                                                        >
                                                                            <i class="bi bi-plus-lg text-base"></i>
                                                                            دسته‌بندی جدید
                                                                        </button>
                                                                    </div>

                                                                    <div class="min-h-0 flex-1 space-y-1.5 overflow-y-auto p-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)]">
                                                                        @foreach($categoryNameSuggestions as $suggestion)
                                                                            <button
                                                                                type="button"
                                                                                data-name="{{ $suggestion['name'] }}"
                                                                                data-id="{{ $suggestion['id'] }}"
                                                                                data-unit="{{ $suggestion['unit'] ?? '' }}"
                                                                                @click="pick({ id: Number($el.dataset.id || 0), name: $el.dataset.name, unit: $el.dataset.unit })"
                                                                                x-bind:disabled="isAlreadyAssigned($el.dataset.name)"
                                                                                x-bind:aria-disabled="isAlreadyAssigned($el.dataset.name).toString()"
                                                                                x-bind:class="isAlreadyAssigned($el.dataset.name)
                                                                                    ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-75'
                                                                                    : 'border-slate-200 bg-white text-slate-800 hover:border-emerald-200 hover:bg-emerald-50/60 active:bg-emerald-50'"
                                                                                class="flex w-full items-center gap-2.5 rounded-xl border px-3 py-2.5 text-right transition disabled:pointer-events-none"
                                                                            >
                                                                                <span
                                                                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                                                                                    x-bind:class="isAlreadyAssigned($el.closest('button').dataset.name) ? 'bg-white text-slate-400' : 'bg-slate-50 text-emerald-600'"
                                                                                >
                                                                                    <i class="bi text-sm" x-bind:class="isAlreadyAssigned($el.closest('button').dataset.name) ? 'bi-lock' : 'bi-tag'"></i>
                                                                                </span>
                                                                                <span class="min-w-0 flex-1">
                                                                                    <span class="block truncate text-sm font-bold">{{ $suggestion['name'] }}</span>
                                                                                    <span x-show="isAlreadyAssigned($el.closest('button').dataset.name)" class="mt-0.5 block text-[10px] font-bold text-slate-400">
                                                                                        قبلاً برای این مددکار ثبت شده است
                                                                                    </span>
                                                                                </span>
                                                                                <i
                                                                                    class="bi text-[11px]"
                                                                                    x-bind:class="isAlreadyAssigned($el.closest('button').dataset.name) ? 'bi-check2 text-slate-400' : 'bi-chevron-left text-slate-300'"
                                                                                ></i>
                                                                            </button>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="space-y-1.5">
                                                <label class="block text-[11px] font-black text-slate-500">مقدار خدمت</label>
                                                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-stretch overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition focus-within:border-emerald-300 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
                                                    <input
                                                        type="number"
                                                        min="0.01"
                                                        step="0.01"
                                                        inputmode="decimal"
                                                        wire:model.blur="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.quantity"
                                                        class="min-w-0 border-0 bg-transparent px-3 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                                                        placeholder="مقدار"
                                                    >
                                                    <select
                                                        wire:model{{ $isSharedCategoryRow ? '.live' : '.blur' }}="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.unit"
                                                        @disabled($isUnitLocked)
                                                        @if($isUnitLocked) title="این دسته‌بندی استفاده شده است و واحد آن قابل تغییر نیست" @endif
                                                        class="w-full min-w-[4.5rem] max-w-[6rem] border-0 border-r border-slate-200 px-2 py-2 text-center text-xs font-bold outline-none focus:ring-0 {{ $isUnitLocked ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white/70 text-slate-600' }}"
                                                    >
                                                        @foreach($unitOptions as $value => $label)
                                                            <option value="{{ $value }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @if($isUnitLocked)
                                                    <p class="flex items-center gap-1 text-[10px] font-bold text-slate-400">
                                                        <i class="bi bi-lock-fill"></i>
                                                        واحد این دسته‌بندی به‌دلیل ثبت تحویل یا ورود قابل تغییر نیست
                                                    </p>
                                                @endif
                                                @error("miscWorkerGroups.$gi.categories.$ci.quantity") <p data-validation-error class="text-[11px] text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <button
                                    type="button"
                                    wire:click="addGroupCategory({{ $gi }})"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-emerald-200 bg-white px-3 py-2.5 text-sm font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-50"
                                >
                                    <span class="text-lg leading-none">+</span>
                                    افزودن دسته‌بندی
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

        <button
            type="button"
            wire:click="addWorkerGroup"
            class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/50 px-3 py-3 text-sm font-black text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50"
        >
            <i class="bi bi-person-plus text-base"></i>
            افزودن مددکار
        </button>
    </div>

    {{-- Date & description --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <div x-data="jalaliDateTimeField($wire.entangle('date').live)">
                <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
                <div class="relative">
                    <input
                        type="text"
                        x-ref="input"
                        x-model="draft"
                        x-on:change="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                        x-on:blur="syncFromInput(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                        x-on:jalali-picker-open="handlePickerOpen()"
                        x-on:jalali-picker-close="handlePickerClose()"
                        x-on:jalali-picker-confirm="confirm(); draft = (draft || '').split(' ')[0]; committedValue = draft; $refs.input.value = draft; model = draft"
                        readonly
                        inputmode="none"
                        autocomplete="off"
                        data-jdp-readonly
                        data-jdp
                        placeholder="1405/04/03"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pe-11 text-sm font-medium text-slate-700 outline-none transition ltr:text-left focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                    >
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i class="bi bi-calendar2-event text-base"></i>
                    </span>
                </div>
                <p class="mt-1 text-xs leading-5 text-slate-500">تاریخ با تقویم شمسی ثبت می‌شود</p>
                @error('date') <p data-validation-error class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
            <textarea rows="3" wire:model.blur="miscDescription" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
        </div>
    </div>
</div>
