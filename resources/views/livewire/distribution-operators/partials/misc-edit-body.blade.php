<div
    x-data="{
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
    x-on:worker-group-added.window="scrollToNewGroup($event)"
    class="space-y-5 p-4 sm:p-5"
>
    {{-- Service type --}}
    <div>
        <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
        <div
            x-data="{
                serviceType: @entangle('miscServiceType').live,
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
                    this.serviceType = value;
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
            @endphp

            <div
                data-worker-group-index="{{ $gi }}"
                wire:key="worker-group-{{ $group['uid'] ?? $gi }}"
                class="overflow-hidden rounded-2xl border {{ $groupLocked ? 'border-slate-200 bg-slate-50/70' : 'border-emerald-200 bg-white shadow-sm' }}"
            >
                @if($groupLocked)
                    {{-- Locked summary --}}
                    <div class="flex items-center gap-3 px-3.5 py-3 sm:px-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-slate-400 ring-1 ring-slate-200">
                            <i class="bi bi-lock-fill text-sm"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="min-w-0 truncate text-sm font-black text-slate-800">{{ $groupWorkerDisplay ?: 'مددکار' }}</span>
                                <span class="shrink-0 text-[11px] font-bold text-slate-400">کد {{ $groupWorkerCode ?: '-' }}</span>
                            </div>
                            <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-500">
                                {{ $groupCategoryCount }} دسته‌بندی @if($groupCategoryPreview !== '')<span class="text-slate-300">·</span> {{ $groupCategoryPreview }}@endif
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="toggleGroupLock({{ $gi }})"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <i class="bi bi-pencil-square"></i>
                            ویرایش
                        </button>
                    </div>
                @else
                    {{-- Editable --}}
                    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-emerald-50/40 px-3.5 py-2.5 sm:px-4">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-black text-emerald-700">{{ $gi + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm font-black text-slate-800">
                            {{ $groupWorkerId ? ($groupWorkerDisplay ?: 'مددکار') : 'مددکار جدید' }}
                        </span>
                        @if($groupIsExisting)
                            <button
                                type="button"
                                wire:click="toggleGroupLock({{ $gi }})"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-slate-50"
                                aria-label="قفل‌کردن این مددکار"
                            >
                                <i class="bi bi-lock"></i>
                                قفل
                            </button>
                        @endif
                        @if(count($miscWorkerGroups) > 1)
                            <button
                                type="button"
                                wire:click="removeWorkerGroup({{ $gi }})"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg p-1.5 text-rose-400 transition hover:bg-rose-50 hover:text-rose-600"
                                aria-label="حذف این مددکار"
                            >
                                <i class="bi bi-trash text-sm"></i>
                            </button>
                        @endif
                    </div>

                    <div class="space-y-3 p-3.5 sm:p-4">
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
                                    @endphp
                                    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm shadow-slate-200/50 sm:p-3.5">
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
                                                        modelPath: 'miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.name',
                                                        assignedCategoryKeys: @js($assignedCategoryKeysForCurrentWorker),
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
                                                            document.documentElement.classList.add('overflow-hidden');
                                                            this.pushSheetHistory();
                                                        },
                                                        closeSheet({ syncHistory = true } = {}) {
                                                            const shouldRestoreHistory = syncHistory && this.historyActive;
                                                            this.sheetOpen = false;
                                                            document.documentElement.classList.remove('overflow-hidden');

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
                                                        pick(name) {
                                                            if (this.isAlreadyAssigned(name)) {
                                                                return;
                                                            }

                                                            this.directEntry = false;
                                                            $wire.set(this.modelPath, name);
                                                            this.closeSheet();
                                                        },
                                                        createNew() {
                                                            this.directEntry = true;
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
                                                    x-init="window.addEventListener('popstate', () => handleSheetPopState())"
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
                                                    @error("miscWorkerGroups.$gi.categories.$ci.name") <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror

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
                                                                        @foreach($categoryNameSuggestions as $name)
                                                                            <button
                                                                                type="button"
                                                                                data-name="{{ $name }}"
                                                                                @click="pick($el.dataset.name)"
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
                                                                                    <span class="block truncate text-sm font-bold">{{ $name }}</span>
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
                                                <div class="grid grid-cols-[minmax(0,1fr)_8.5rem] overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition focus-within:border-emerald-300 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
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
                                                        wire:model="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.unit"
                                                        class="border-0 border-r border-slate-200 bg-white/70 px-2.5 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-0"
                                                    >
                                                        @foreach($unitOptions as $value => $label)
                                                            <option value="{{ $value }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error("miscWorkerGroups.$gi.categories.$ci.quantity") <p class="text-[11px] text-rose-600">{{ $message }}</p> @enderror
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
                @error('date') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
            <textarea rows="3" wire:model.blur="miscDescription" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
        </div>
    </div>
</div>
