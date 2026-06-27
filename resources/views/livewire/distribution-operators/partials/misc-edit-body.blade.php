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

                        <div class="rounded-xl border border-slate-200 bg-white">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-black text-slate-800">دسته‌بندی‌ها</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $groupCategoryCount }}</span>
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach($groupCategories as $ci => $category)
                                    <div class="p-3 sm:p-3.5">
                                        <div class="flex items-start gap-2.5">
                                            <span class="mt-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-black text-emerald-700">{{ $ci + 1 }}</span>
                                            <div class="min-w-0 flex-1 space-y-2.5">
                                                <input
                                                    type="text"
                                                    list="misc-category-names"
                                                    wire:model.blur="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.name"
                                                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                                                    placeholder="نام دسته‌بندی (مثال: بسته غذایی)"
                                                    autocomplete="off"
                                                >
                                                @error("miscWorkerGroups.$gi.categories.$ci.name") <p class="text-[11px] text-rose-600">{{ $message }}</p> @enderror

                                                <div class="grid grid-cols-2 gap-2">
                                                    <input
                                                        type="number"
                                                        min="0.01"
                                                        step="0.01"
                                                        inputmode="decimal"
                                                        wire:model.blur="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.quantity"
                                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                                                        placeholder="مقدار"
                                                    >
                                                    <select
                                                        wire:model="miscWorkerGroups.{{ $gi }}.categories.{{ $ci }}.unit"
                                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                                                    >
                                                        @foreach($unitOptions as $value => $label)
                                                            <option value="{{ $value }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error("miscWorkerGroups.$gi.categories.$ci.quantity") <p class="text-[11px] text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            @if($groupCategoryCount > 1)
                                                <button
                                                    type="button"
                                                    wire:click="removeGroupCategory({{ $gi }}, {{ $ci }})"
                                                    class="mt-1 shrink-0 rounded-lg p-1.5 text-rose-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                    aria-label="حذف دسته‌بندی {{ $ci + 1 }}"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                <button
                                    type="button"
                                    wire:click="addGroupCategory({{ $gi }})"
                                    class="flex w-full items-center justify-center gap-2 px-3 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50"
                                >
                                    <span class="text-lg leading-none">+</span>
                                    افزودن دسته‌بندی
                                </button>
                            </div>
                        </div>
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

    <datalist id="misc-category-names">
        @foreach($categoryNameSuggestions as $name)
            <option value="{{ $name }}"></option>
        @endforeach
    </datalist>
</div>
