<div class="space-y-4">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-indigo-700 via-sky-700 to-cyan-700 px-5 py-4 text-white">
            <h1 class="text-2xl font-extrabold">مدیریت خدمات</h1>
            <p class="mt-1.5 text-xs text-sky-50/90">در این بخش نام خدمت، دسته‌بندی‌ها و واحدهای قابل استفاده را تعریف و ویرایش کنید.</p>
        </div>

        <div class="space-y-4 px-4 py-4">
            @if (session()->has('management-success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    {{ session('management-success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                    <p class="font-bold">لطفاً خطاهای فرم را بررسی کنید.</p>
                </div>
            @endif

            @php
                $selectedServiceName = $allServiceNames->firstWhere('id', (int) $selectedServiceNameId);
            @endphp

            <div class="grid gap-4 xl:grid-cols-3">
                <section class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <div class="mb-3 flex items-center justify-between gap-2.5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">نام خدمت</h2>
                            <p class="text-xs text-slate-500">ثبت و ویرایش سرویس‌های اصلی</p>
                        </div>
                        @if($editingServiceNameId)
                            <button type="button" wire:click="resetServiceNameForm" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <div class="mb-2.5">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                            <input
                                type="text"
                                wire:model.live.debounce.200ms="serviceNameSearch"
                                placeholder="نام خدمت را جستجو کنید ..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-100/80 py-2.5 pl-10 pr-3.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                            >
                        </div>
                    </div>

                    <form
                        x-data="{
                            openServiceNameConfirm() {
                                const name = this.$refs.serviceNameInput?.value?.trim() || '';

                                if (!name) {
                                    return;
                                }

                                window.dispatchEvent(new CustomEvent('open-notification-modal', {
                                    detail: {
                                        config: {
                                            type: 'Warning',
                                            title: 'تأیید نام خدمت',
                                            message: `آیا مطمئن هستید که می‌خواهید ${name} را اضافه یا ویرایش کنید؟`,
                                            buttons: [
                                                {
                                                    label: 'تائید',
                                                    action: 'event',
                                                    event: 'confirm-service-name-save',
                                                    payload: { serviceNameInput: name },
                                                    variant: 'primary',
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
                            }
                        }"
                        @submit.prevent="openServiceNameConfirm()"
                        class="space-y-2.5"
                    >
                        <input type="text" wire:model.live="serviceName" x-ref="serviceNameInput" placeholder="مثال سفره ام‌البنین (س)" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('serviceName') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button
                            type="submit"
                            @class([
                                'w-full rounded-xl px-3.5 py-2.5 text-sm font-bold text-white transition',
                                'bg-indigo-700 hover:bg-indigo-600 shadow-sm' => ! $editingServiceNameId,
                                'bg-amber-600 hover:bg-amber-500 shadow-sm ring-1 ring-amber-300' => (bool) $editingServiceNameId,
                            ])
                        >
                            {{ $editingServiceNameId ? 'به‌روزرسانی نام خدمت' : 'ثبت نام خدمت' }}
                        </button>
                    </form>

                    <div
                        x-data="{
                            showFade: false,
                            updateFade() {
                                const el = this.$refs.scroller;
                                this.showFade = !!el && (el.scrollHeight - el.clientHeight - el.scrollTop > 8);
                            }
                        }"
                        x-init="$nextTick(() => updateFade())"
                        @resize.window="updateFade()"
                        class="relative mt-5"
                    >
                        <div
                            x-ref="scroller"
                            @scroll="updateFade()"
                            class="max-h-[22rem] space-y-1.5 overflow-y-auto scroll-smooth pb-12 pr-1"
                        >
                            @foreach($serviceNames as $item)
                                <div
                                    @class([
                                        'group relative flex w-full items-center gap-2 rounded-xl border bg-white p-1.5 text-right transition',
                                        'border-slate-300 bg-slate-100/80' => (int) $selectedServiceNameId === (int) $item->id,
                                        'border-slate-200 hover:border-indigo-300' => (int) $selectedServiceNameId !== (int) $item->id,
                                    ])
                                >
                                    <button type="button" wire:click="selectServiceName({{ $item->id }})" class="min-w-0 flex-1 rounded-lg px-2 py-1.5 text-right transition hover:bg-indigo-50/70">
                                        <span class="block truncate text-sm font-semibold text-slate-800">{{ $item->name }}</span>
                                        <span class="mt-0.5 flex min-w-0 items-center gap-1.5 truncate text-[11px] text-slate-500">
                                            <span>{{ $item->category_templates_count }} دسته</span>
                                            @if((int) $selectedServiceNameId === (int) $item->id)
                                                <span class="h-1 w-1 shrink-0 rounded-full bg-indigo-300"></span>
                                                <span class="shrink-0 text-[10px] font-semibold text-indigo-600">فعال</span>
                                            @endif
                                        </span>
                                    </button>
                                    <div x-data="{ open: false }" x-on:click.outside="open = false" class="relative flex w-[78px] shrink-0 items-center justify-end gap-1">
                                        <button type="button" wire:click="editServiceName({{ $item->id }})" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-indigo-200 hover:bg-white hover:text-indigo-700" aria-label="ویرایش نام خدمت {{ $item->name }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M4 20h4l10.5-10.5a1.5 1.5 0 0 0 0-2.1l-1.9-1.9a1.5 1.5 0 0 0-2.1 0L4 16v4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                                <path d="M13.5 6.5l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click.stop="open = !open"
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-transparent bg-transparent text-slate-400 transition hover:border-slate-200 hover:bg-white hover:text-slate-700"
                                            aria-label="گزینه‌های نام خدمت {{ $item->name }}"
                                            aria-haspopup="menu"
                                            x-bind:aria-expanded="open.toString()"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8" />
                                                <circle cx="12" cy="12" r="1.8" />
                                                <circle cx="12" cy="19" r="1.8" />
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            x-transition.origin.top.left
                                            class="absolute left-0 top-11 z-40 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1 shadow-xl"
                                            role="menu"
                                        >
                                            <button
                                                type="button"
                                                wire:click="openArchiveServiceNameConfirmation({{ $item->id }})"
                                                x-on:click="open = false"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-right text-sm font-bold text-rose-600 transition hover:bg-rose-50"
                                                role="menuitem"
                                            >
                                                <span>حذف از فهرست</span>
                                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M6 7h12M10 11v6M14 11v6M9 7l1-2h4l1 2M8 7l.5 12h7L16 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-slate-50/95 via-slate-50/80 to-slate-50/0"
                        ></div>
                    </div>
                </section>

                <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-2.5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">دسته‌بندی خدمت</h2>
                            <p class="text-xs text-slate-500">برای هر نام خدمت، دسته‌بندی‌های وابسته تعریف کنید</p>
                        </div>
                        @if($editingCategoryId)
                            <button type="button" wire:click="resetCategoryForm" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <div class="mb-3 rounded-2xl border border-sky-100 bg-sky-50/70 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-sky-700">خدمت انتخاب‌شده</p>
                        <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-extrabold text-slate-800">{{ $selectedServiceName?->name ?? 'هنوز انتخاب نشده' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $selectedServiceName ? $selectedServiceName->category_templates_count . ' دسته ثبت‌شده' : 'برای تعریف دسته ابتدا نام خدمت را انتخاب کنید.' }}
                                </p>
                            </div>
                            @if($selectedServiceName)
                                <button type="button" wire:click="editServiceName({{ $selectedServiceName->id }})" class="w-full rounded-xl border border-sky-200 bg-white px-3 py-2 text-xs font-bold text-sky-700 transition hover:border-sky-300 sm:w-auto">
                                    ویرایش نام خدمت
                                </button>
                            @endif
                        </div>
                    </div>

                    <form wire:submit.prevent="saveCategory" class="space-y-2.5">
                        <div
                            x-data="{
                                open: false,
                                selectedId: @entangle('selectedServiceNameId').live,
                                serviceNames: @js($allServiceNames->map(fn ($serviceName) => [
                                    'id' => $serviceName->id,
                                    'name' => $serviceName->name,
                                    'count' => $serviceName->category_templates_count,
                                ])->values()),
                                query: '',
                                isTyping: false,
                                init() {
                                    this.syncQuery();

                                    this.$watch('selectedId', () => {
                                        if (!this.isTyping) {
                                            this.syncQuery();
                                        }
                                    });
                                },
                                get filteredServiceNames() {
                                    const term = this.query.trim().toLowerCase();

                                    if (!term) {
                                        return this.serviceNames;
                                    }

                                    return this.serviceNames.filter((item) => item.name.toLowerCase().includes(term));
                                },
                                get selectedServiceName() {
                                    return this.serviceNames.find((item) => Number(item.id) === Number(this.selectedId)) || null;
                                },
                                syncQuery() {
                                    this.query = this.selectedServiceName?.name || '';
                                },
                                selectServiceName(item) {
                                    this.isTyping = false;
                                    this.selectedId = item.id;
                                    this.query = item.name;
                                    this.open = false;
                                },
                                searchServiceName() {
                                    this.isTyping = true;
                                    this.selectedId = null;
                                    this.open = true;
                                    this.$nextTick(() => this.isTyping = false);
                                },
                                clearServiceName() {
                                    this.isTyping = false;
                                    this.selectedId = null;
                                    this.query = '';
                                    this.open = false;
                                }
                            }"
                            x-on:click.outside="open = false"
                            class="relative"
                        >
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">انتخاب نام خدمت</label>
                            <input
                                type="text"
                                x-model="query"
                                x-on:focus="open = true"
                                x-on:input="searchServiceName()"
                                x-on:keydown.escape="open = false"
                                placeholder="جستجو یا انتخاب نام خدمت"
                                autocomplete="off"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pe-20 text-sm text-slate-700 outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                            >
                            <button
                                type="button"
                                x-cloak
                                x-show="query"
                                x-on:click.stop.prevent="clearServiceName()"
                                class="absolute bottom-0 end-8 flex h-[42px] items-center px-2 text-slate-400 transition hover:text-rose-600"
                                aria-label="پاک کردن نام خدمت"
                            >
                                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                    <path d="M5.5 5.5L14.5 14.5M14.5 5.5L5.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                x-on:click.stop.prevent="open = !open"
                                class="absolute bottom-0 end-0 flex h-[42px] items-center px-3 text-slate-400"
                                aria-label="باز کردن فهرست نام خدمات"
                            >
                                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': open }">
                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top.left
                                class="absolute z-30 mt-2 max-h-72 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div class="border-b border-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                                    انتخاب یک خدمت، فهرست دسته‌های همان خدمت را نمایش می‌دهد.
                                </div>
                                <div class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="item in filteredServiceNames" :key="item.id">
                                        <button
                                            type="button"
                                            x-on:click="selectServiceName(item)"
                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-sky-50"
                                        >
                                            <span x-text="item.name" class="min-w-0 truncate font-medium"></span>
                                            <span class="flex shrink-0 items-center gap-2 text-xs text-slate-400">
                                                <span x-text="`${item.count} دسته`"></span>
                                                <span x-show="Number(selectedId) === Number(item.id)" class="font-bold text-sky-700">انتخاب شده</span>
                                            </span>
                                        </button>
                                    </template>
                                    <div x-show="filteredServiceNames.length === 0" class="px-4 py-3 text-sm text-slate-500">
                                        نام خدمتی با این عبارت پیدا نشد.
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('selectedServiceNameId') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-600">نام دسته‌بندی</label>
                            <input type="text" wire:model.blur="categoryName" placeholder="مثلاً غذای گرم" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100">
                        </div>
                        @error('categoryName') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-xl bg-sky-700 px-3.5 py-2.5 text-sm font-bold text-white">
                            {{ $editingCategoryId ? 'به‌روزرسانی دسته‌بندی' : 'ثبت دسته‌بندی' }}
                        </button>
                    </form>

                    <div
                        x-data="{
                            showFade: false,
                            updateFade() {
                                const el = this.$refs.scroller;
                                this.showFade = !!el && (el.scrollHeight - el.clientHeight - el.scrollTop > 8);
                            }
                        }"
                        x-init="$nextTick(() => updateFade())"
                        @resize.window="updateFade()"
                        class="relative mt-5"
                    >
                        <div
                            x-ref="scroller"
                            @scroll="updateFade()"
                            class="max-h-[22rem] space-y-1.5 overflow-y-auto scroll-smooth pb-12 pr-1"
                        >
                            @forelse($serviceCategories as $item)
                                <div
                                    @class([
                                        'relative flex w-full items-center gap-2 rounded-xl border bg-slate-50 p-1.5 text-right transition',
                                        'border-slate-300 bg-white' => (int) $editingCategoryId === (int) $item->id,
                                        'border-slate-200 hover:border-sky-300' => (int) $editingCategoryId !== (int) $item->id,
                                    ])
                                >
                                    <div class="min-w-0 flex-1 rounded-lg px-2 py-1.5">
                                        <span class="block truncate text-sm font-semibold text-slate-800">{{ $item->name }}</span>
                                        <span class="mt-0.5 flex min-w-0 items-center gap-1.5 truncate text-[11px] text-slate-500">
                                            <span>{{ $item->serviceName?->name }}</span>
                                            @if((int) $editingCategoryId === (int) $item->id)
                                                <span class="h-1 w-1 shrink-0 rounded-full bg-amber-300"></span>
                                                <span class="shrink-0 text-[10px] font-semibold text-amber-600">ویرایش</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div x-data="{ open: false }" x-on:click.outside="open = false" class="relative flex w-[78px] shrink-0 items-center justify-end gap-1">
                                        <button type="button" wire:click="editCategory({{ $item->id }})" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:border-sky-200 hover:text-sky-700" aria-label="ویرایش دسته‌بندی {{ $item->name }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M4 20h4l10.5-10.5a1.5 1.5 0 0 0 0-2.1l-1.9-1.9a1.5 1.5 0 0 0-2.1 0L4 16v4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                                <path d="M13.5 6.5l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click.stop="open = !open"
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-transparent bg-transparent text-slate-400 transition hover:border-slate-200 hover:bg-white hover:text-slate-700"
                                            aria-label="گزینه‌های دسته‌بندی {{ $item->name }}"
                                            aria-haspopup="menu"
                                            x-bind:aria-expanded="open.toString()"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8" />
                                                <circle cx="12" cy="12" r="1.8" />
                                                <circle cx="12" cy="19" r="1.8" />
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            x-transition.origin.top.left
                                            class="absolute left-0 top-11 z-40 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1 shadow-xl"
                                            role="menu"
                                        >
                                            <button
                                                type="button"
                                                wire:click="openDeleteCategoryConfirmation({{ $item->id }})"
                                                x-on:click="open = false"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-right text-sm font-bold text-rose-600 transition hover:bg-rose-50"
                                                role="menuitem"
                                            >
                                                <span>حذف دسته‌بندی</span>
                                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M6 7h12M10 11v6M14 11v6M9 7l1-2h4l1 2M8 7l.5 12h7L16 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 px-3.5 py-5 text-center text-xs text-slate-500">برای این خدمت هنوز دسته‌بندی ثبت نشده است.</div>
                            @endforelse
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-white via-white/85 to-white/0"
                        ></div>
                    </div>
                </section>

                <section class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <div class="mb-3 flex items-center justify-between gap-2.5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">واحد خدمت</h2>
                            <p class="text-xs text-slate-500">واحدهای قابل انتخاب برای خدمات را مدیریت کنید</p>
                        </div>
                        @if($editingUnitId)
                            <button type="button" wire:click="resetUnitForm" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <form wire:submit.prevent="saveUnit" class="space-y-2.5">
                        <input type="text" wire:model.blur="unitLabel" placeholder="مثلاً کیلوگرم" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('unitLabel') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <input type="text" wire:model.blur="unitKey" placeholder="کلید اختیاری، مثل kilogram" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('unitKey') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-xl bg-cyan-700 px-3.5 py-2.5 text-sm font-bold text-white">
                            {{ $editingUnitId ? 'به‌روزرسانی واحد' : 'ثبت واحد' }}
                        </button>
                    </form>

                    <div
                        x-data="{
                            showFade: false,
                            updateFade() {
                                const el = this.$refs.scroller;
                                this.showFade = !!el && (el.scrollHeight - el.clientHeight - el.scrollTop > 8);
                            }
                        }"
                        x-init="$nextTick(() => updateFade())"
                        @resize.window="updateFade()"
                        class="relative mt-5"
                    >
                        <div
                            x-ref="scroller"
                            @scroll="updateFade()"
                            class="max-h-[22rem] space-y-1.5 overflow-y-auto scroll-smooth pb-12 pr-1"
                        >
                            @foreach($serviceUnits as $item)
                                <button type="button" wire:click="editUnit({{ $item->id }})" class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-right transition hover:border-cyan-300">
                                    <span class="text-sm font-semibold text-slate-800">{{ $item->label }}</span>
                                    <span class="flex items-center gap-1.5">
                                        <span class="text-[11px] text-slate-500">{{ $item->key }}</span>
                                        <svg class="h-3.5 w-3.5 text-slate-300 transition group-hover:text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 20h4l10.5-10.5a1.5 1.5 0 0 0 0-2.1l-1.9-1.9a1.5 1.5 0 0 0-2.1 0L4 16v4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                            <path d="M13.5 6.5l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-slate-50/95 via-slate-50/80 to-slate-50/0"
                        ></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
