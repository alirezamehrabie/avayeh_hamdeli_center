<div class="mx-auto max-w-[1680px] space-y-6 px-4 2xl:max-w-[1760px]">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-teal-600 via-cyan-600 to-sky-700 px-4 py-4 text-white sm:px-6 sm:py-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between sm:gap-5">
                <div>
                    <h1 class="text-lg font-extrabold leading-tight sm:mt-2 sm:text-2xl">افزودن خدمت / پویش</h1>
                    <p class="mt-1 hidden max-w-3xl text-sm text-cyan-50/90 sm:mt-2 sm:block">
                        یک خدمت والد بسازید و برای آن چند دسته با مقدار، واحد و ارزش مستقل تعریف کنید.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 sm:gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white/95 backdrop-blur">
                        <span class="text-xs text-cyan-100">تعداد کل</span>
                        <span class="font-semibold">{{ number_format($this->totalQuantity, 2) }}</span>
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white/95 backdrop-blur">
                        <span class="text-xs text-cyan-100">ارزش کل</span>
                        <span class="font-semibold">{{ number_format($this->totalServiceValue) }} ریال</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="px-6 py-6 xl:px-8 2xl:px-10">
            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit.prevent="save" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_360px] 2xl:grid-cols-[minmax(0,1.35fr)_400px] 2xl:gap-8">
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-5">
                                    <div>
                                    <h2 class="text-lg font-bold text-slate-800">اطلاعات پایه خدمت</h2>
                                    <p class="text-sm text-slate-500">نام، نوع و وضعیت خدمت</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold tracking-wide text-slate-600 shadow-sm">
                                        {{ $this->previewServiceCode }}
                                    </span>
                                </div>
                                @if($editingServiceId)
                                    <button type="button" wire:click="startNewService" class="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                                        خدمت جدید
                                    </button>
                                @endif
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">

                                <div class="md:col-span-1">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">نام خدمت / پویش</label>
                                    <div
                                        x-data="{
                                            open: false,
                                            activeIndex: -1,
                                            selectedId: @entangle('selectedServiceNameId').live,
                                            serviceName: @entangle('serviceName').live,
                                            serviceNames: @js($serviceNames->map(fn ($serviceName) => [
                                                'id' => $serviceName->id,
                                                'name' => $serviceName->name,
                                            ])->values()),
                                            filterText: @entangle('serviceName').live,
                                            get filteredServiceNames() {
                                                const query = this.filterText.trim().toLowerCase();

                                                if (!query) {
                                                    return this.serviceNames;
                                                }

                                                return this.serviceNames.filter((item) => item.name.toLowerCase().includes(query));
                                            },
                                            selectServiceName(item) {
                                                this.selectedId = item.id;
                                                this.serviceName = item.name;
                                                this.filterText = item.name;
                                                this.open = false;
                                            },
                                            clearServiceName() {
                                                this.selectedId = null;
                                                this.serviceName = '';
                                                this.filterText = '';
                                                this.open = false;
                                            }
                                        }"
                                        x-on:click.outside="open = false"
                                        x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                        class="relative"
                                    >
                                        <input
                                            type="text"
                                            x-model="filterText"
                                            x-on:focus="open = true"
                                            x-on:input="selectedId = null; serviceName = filterText"
                                            x-on:keydown.escape="open = false"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-20 text-sm text-slate-700"
                                            placeholder="مثال: سفره ام‌البنین (س)"
                                            autocomplete="off"
                                        >
                                        <button
                                            type="button"
                                            tabindex="-1"
                                            x-cloak
                                            x-show="filterText"
                                            x-on:click.stop.prevent="clearServiceName()"
                                            class="absolute inset-y-0 end-8 flex items-center px-2 text-slate-400 transition hover:text-rose-600"
                                            aria-label="پاک کردن نام خدمت"
                                        >
                                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                                <path d="M5.5 5.5L14.5 14.5M14.5 5.5L5.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            tabindex="-1"
                                            x-on:click.stop.prevent="open = !open"
                                            class="absolute inset-y-0 end-0 flex items-center px-3 text-slate-400"
                                            aria-label="باز کردن فهرست نام خدمات"
                                        >
                                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            x-transition.origin.top.left
                                            class="absolute z-30 mt-2 max-h-60 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                        >
                                            <div class="border-b border-slate-100 px-3 py-2 text-xs font-semibold text-slate-400">
                                                برای ثبت مورد جدید تایپ کنید
                                            </div>
                                            <div class="max-h-56 overflow-y-auto py-1">
                                                <template x-for="item in filteredServiceNames" :key="item.id">
                                                    <button
                                                        type="button"
                                                        x-on:click="selectServiceName(item)"
                                                        class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        <span x-text="item.name" class="font-medium"></span>
                                                        <span class="text-xs text-slate-400" x-show="selectedId === item.id">انتخاب شده</span>
                                                    </button>
                                                </template>
                                                <div x-show="filteredServiceNames.length === 0" class="px-4 py-3 text-sm text-slate-500">
                                                    موردی پیدا نشد. می‌توانید همین نام را به عنوان مورد جدید ثبت کنید.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @error('serviceName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-1">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
                                    <div
                                        x-data="{
                                            open: false,
                                            serviceType: @entangle('serviceType').live,
                                            typeOptions: @js($typeOptions),
                                            get options() {
                                                return Object.entries(this.typeOptions).map(([value, label]) => ({ value, label }));
                                            },
                                            get selectedLabel() {
                                                return this.typeOptions[this.serviceType] ?? 'انتخاب نوع خدمت';
                                            },
                                            selectServiceType(value) {
                                                this.serviceType = value;
                                                this.open = false;
                                            }
                                        }"
                                        x-on:click.outside="open = false"
                                        x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                        class="relative"
                                    >
                                        <button
                                            type="button"
                                            x-on:click.stop.prevent="open = !open"
                                            x-on:keydown.escape="open = false"
                                            class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                            aria-haspopup="listbox"
                                            x-bind:aria-expanded="open.toString()"
                                        >
                                            <span x-text="selectedLabel" class="font-medium"></span>
                                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="{ 'rotate-180': open }">
                                                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            x-transition.origin.top.left
                                            class="absolute z-30 mt-2 max-h-60 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                            role="listbox"
                                        >
                                            <div class="max-h-56 overflow-y-auto py-1">
                                                <template x-for="item in options" :key="item.value">
                                                    <button
                                                        type="button"
                                                        x-on:click="selectServiceType(item.value)"
                                                        class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                        role="option"
                                                        x-bind:aria-selected="serviceType === item.value"
                                                    >
                                                        <span x-text="item.label" class="font-medium"></span>
                                                        <span class="text-xs text-slate-400" x-show="serviceType === item.value">انتخاب شده</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    @error('serviceType') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>

                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات خدمت</label>
                                <textarea wire:model.blur="description" rows="2" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40">
                            <div class="mb-4 flex flex-col gap-1 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h2 class="text-base font-bold text-slate-800">دسته‌های خدمت</h2>
                                    <p class="mt-0.5 text-xs text-slate-500">برای هر دسته مقدار، واحد و ارزش واحد را ثبت کنید</p>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                    {{ count($categories) }} دسته
                                </span>
                            </div>

                            <div class="space-y-3">
                                @foreach($categories as $index => $category)
                                    <div
                                        x-data="{
                                            quantity: @entangle('categories.' . $index . '.quantity').live,
                                            value: @entangle('categories.' . $index . '.value').live,
                                            numberValue(raw) {
                                                const value = Number.parseFloat(String(raw ?? '').replace(/,/g, ''));

                                                return Number.isFinite(value) ? value : 0;
                                            },
                                            formatGrouped(raw) {
                                                return String(raw ?? '')
                                                    .replace(/[^\d]/g, '')
                                                    .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                            },
                                            formatValueInput(event) {
                                                const formatted = this.formatGrouped(event.target.value);

                                                event.target.value = formatted;
                                                this.value = formatted;
                                            },
                                            get lineTotal() {
                                                return this.numberValue(this.quantity) * this.numberValue(this.value);
                                            },
                                            get hasLineTotal() {
                                                return this.numberValue(this.quantity) > 0 && this.numberValue(this.value) > 0;
                                            },
                                            get formattedLineTotal() {
                                                return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 }).format(Math.round(this.lineTotal));
                                            }
                                        }"
                                        class="rounded-2xl border border-slate-200 bg-white p-3.5 transition-colors focus-within:border-cyan-200 focus-within:bg-cyan-50/20"
                                    >
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <div class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-500 ring-1 ring-inset ring-slate-200">
                                                {{ $category['code'] ?: 'CAT-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) }}
                                            </div>
                                            @if(count($categories) > 1)
                                                <button type="button" wire:click="removeCategory({{ $index }})" class="rounded-full px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                                    حذف
                                                </button>
                                            @endif
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">نام دسته</label>
                                                <div
                                                    x-data="{
                                                        open: false,
                                                        selectedServiceNameId: @entangle('selectedServiceNameId').live,
                                                        categoryName: @entangle('categories.' . $index . '.name').live,
                                                        categories: @entangle('categories').live,
                                                        currentIndex: {{ $index }},
                                                        categoryTemplates: @js($categoryTemplates->map(fn ($template) => [
                                                            'id' => $template->id,
                                                            'serviceNameId' => $template->service_name_id,
                                                            'name' => $template->name,
                                                        ])->values()),
                                                        filterText: @entangle('categories.' . $index . '.name').live,
                                                        get filteredCategoryTemplates() {
                                                            const serviceNameId = Number(this.selectedServiceNameId);
                                                            const query = this.filterText.trim().toLowerCase();

                                                            if (!serviceNameId) {
                                                                return [];
                                                            }

                                                            return this.categoryTemplates.filter((item) => {
                                                                const belongsToSelectedService = Number(item.serviceNameId) === serviceNameId;
                                                                const matchesQuery = !query || item.name.toLowerCase().includes(query);
                                                                const alreadySelected = this.categories.some((category, index) => {
                                                                    if (Number(index) === Number(this.currentIndex)) {
                                                                        return false;
                                                                    }

                                                                    return (category.name || '').trim().toLowerCase() === item.name.trim().toLowerCase();
                                                                });

                                                                return belongsToSelectedService && matchesQuery && !alreadySelected;
                                                            });
                                                        },
                                                        selectCategoryTemplate(item) {
                                                            this.categoryName = item.name;
                                                            this.filterText = item.name;
                                                            this.open = false;
                                                        },
                                                        clearCategoryName() {
                                                            this.categoryName = '';
                                                            this.filterText = '';
                                                            this.open = false;
                                                        }
                                                    }"
                                                    x-on:click.outside="open = false"
                                                    x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                                    class="relative"
                                                >
                                                    <input
                                                        type="text"
                                                        x-model="filterText"
                                                        x-on:focus="open = true"
                                                        x-on:input="categoryName = filterText"
                                                        x-on:keydown.escape="open = false"
                                                        class="h-[50px] w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-20 text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                        placeholder="مثال: چلو کباب کوبیده"
                                                        autocomplete="off"
                                                    >
                                                    <button
                                                        type="button"
                                                        tabindex="-1"
                                                        x-cloak
                                                        x-show="filterText"
                                                        x-on:click.stop.prevent="clearCategoryName()"
                                                        class="absolute inset-y-0 end-8 flex items-center px-2 text-slate-400 transition hover:text-rose-600"
                                                        aria-label="پاک کردن نام دسته"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                                            <path d="M5.5 5.5L14.5 14.5M14.5 5.5L5.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        tabindex="-1"
                                                        x-on:click.stop.prevent="open = !open"
                                                        class="absolute inset-y-0 end-0 flex items-center px-3 text-slate-400"
                                                        aria-label="باز کردن فهرست دسته‌های خدمت"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': open }">
                                                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </button>

                                                    <div
                                                        x-cloak
                                                        x-show="open"
                                                        x-transition.origin.top.left
                                                        class="absolute z-30 mt-2 max-h-60 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                                    >
                                                        <div class="border-b border-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                                                            دسته‌های مرتبط با نام خدمت انتخاب‌شده
                                                        </div>
                                                        <div class="max-h-56 overflow-y-auto py-1">
                                                            <template x-for="item in filteredCategoryTemplates" :key="item.id">
                                                                <button
                                                                    type="button"
                                                                    x-on:click="selectCategoryTemplate(item)"
                                                                    class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                                >
                                                                    <span x-text="item.name" class="font-medium"></span>
                                                                    <span class="text-xs text-slate-400" x-show="categoryName === item.name">انتخاب شده</span>
                                                                </button>
                                                            </template>
                                                            <div x-show="!selectedServiceNameId" class="px-4 py-3 text-sm text-slate-500">
                                                                ابتدا نام خدمت را انتخاب کنید تا دسته‌های مرتبط نمایش داده شوند.
                                                            </div>
                                                            <div x-show="selectedServiceNameId && filteredCategoryTemplates.length === 0" class="px-4 py-3 text-sm text-slate-500">
                                                                دسته‌ای برای این نام خدمت پیدا نشد. می‌توانید همین نام را به عنوان دسته جدید ثبت کنید.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @error("categories.$index.name") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">تعداد / مقدار</label>
                                                <input type="number" min="0.01" step="0.01" x-model="quantity" class="h-[50px] w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100" placeholder="0">
                                                @error("categories.$index.quantity") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">واحد</label>
                                                <div
                                                    x-data="{
                                                        open: false,
                                                        dropUp: false,
                                                        menuMaxHeight: 224,
                                                        unit: @entangle('categories.' . $index . '.unit').live,
                                                        unitOptions: @js($unitOptions),
                                                        get options() {
                                                            return Object.entries(this.unitOptions).map(([value, label]) => ({ value, label }));
                                                        },
                                                        get selectedLabel() {
                                                            return this.unitOptions[this.unit] ?? 'انتخاب واحد';
                                                        },
                                                        toggleUnitDropdown() {
                                                            this.open = !this.open;

                                                            if (this.open) {
                                                                this.$nextTick(() => this.positionUnitDropdown());
                                                            }
                                                        },
                                                        positionUnitDropdown() {
                                                            const trigger = this.$refs.unitTrigger;

                                                            if (!trigger) {
                                                                return;
                                                            }

                                                            const rect = trigger.getBoundingClientRect();
                                                            const gap = 8;
                                                            const viewportPadding = 16;
                                                            const preferredHeight = 224;
                                                            const spaceBelow = window.innerHeight - rect.bottom - gap - viewportPadding;
                                                            const spaceAbove = rect.top - gap - viewportPadding;

                                                            this.dropUp = spaceBelow < preferredHeight && spaceAbove > spaceBelow;
                                                            const availableSpace = this.dropUp ? spaceAbove : spaceBelow;

                                                            this.menuMaxHeight = Math.max(0, Math.min(preferredHeight, availableSpace));
                                                        },
                                                        activeIndex: -1,
                                                        closeUnitDropdown() {
                                                            this.open = false;
                                                            this.activeIndex = -1;
                                                        },
                                                        openUnitDropdown() {
                                                            this.open = true;
                                                            this.activeIndex = Math.max(this.options.findIndex((item) => item.value === this.unit), 0);
                                                            this.$nextTick(() => this.positionUnitDropdown());
                                                        },
                                                        moveUnitSelection(step) {
                                                            if (!this.open) {
                                                                this.openUnitDropdown();
                                                                return;
                                                            }

                                                            const count = this.options.length;

                                                            if (count === 0) {
                                                                return;
                                                            }

                                                            const startIndex = this.activeIndex >= 0
                                                                ? this.activeIndex
                                                                : Math.max(this.options.findIndex((item) => item.value === this.unit), 0);

                                                            this.activeIndex = (startIndex + step + count) % count;
                                                        },
                                                        selectActiveUnit() {
                                                            if (this.activeIndex < 0 || this.activeIndex >= this.options.length) {
                                                                return;
                                                            }

                                                            this.selectUnit(this.options[this.activeIndex].value);
                                                        },
                                                        selectUnit(value) {
                                                            this.unit = value;
                                                            this.closeUnitDropdown();
                                                        }
                                                    }"
                                                    x-on:click.outside="closeUnitDropdown()"
                                                    x-on:focusout="if (! $el.contains($event.relatedTarget)) closeUnitDropdown()"
                                                    x-on:resize.window="open && positionUnitDropdown()"
                                                    x-on:scroll.window.throttle.100ms="open && positionUnitDropdown()"
                                                    class="relative"
                                                >
                                                    <button
                                                        x-ref="unitTrigger"
                                                        type="button"
                                                        x-on:click.stop.prevent="open ? closeUnitDropdown() : openUnitDropdown()"
                                                        x-on:keydown.down.prevent="moveUnitSelection(1)"
                                                        x-on:keydown.up.prevent="moveUnitSelection(-1)"
                                                        x-on:keydown.enter.prevent="open ? selectActiveUnit() : openUnitDropdown()"
                                                        x-on:keydown.escape="closeUnitDropdown()"
                                                        class="flex h-[50px] w-full items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                        aria-haspopup="listbox"
                                                        x-bind:aria-expanded="open.toString()"
                                                    >
                                                        <span x-text="selectedLabel" class="font-medium"></span>
                                                        <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="{ 'rotate-180': open }">
                                                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </button>

                                                    <div
                                                        x-cloak
                                                        x-show="open"
                                                        x-transition
                                                        x-bind:class="dropUp ? 'bottom-full mb-2' : 'top-full mt-2'"
                                                        x-bind:style="`max-height: ${menuMaxHeight}px`"
                                                        class="absolute z-30 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                                        role="listbox"
                                                    >
                                                        <div
                                                            class="overflow-y-auto py-1"
                                                            x-bind:style="`max-height: ${menuMaxHeight}px`"
                                                        >
                                                            <template x-for="(item, index) in options" :key="item.value">
                                                                <button
                                                                    type="button"
                                                                    x-on:click="selectUnit(item.value)"
                                                                    x-bind:class="{ 'bg-slate-50': activeIndex === index }"
                                                                    class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                                    role="option"
                                                                    x-bind:aria-selected="unit === item.value"
                                                                >
                                                                    <span x-text="item.label" class="font-medium"></span>
                                                                    <span class="text-xs text-slate-400" x-show="unit === item.value">انتخاب شده</span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                                @error("categories.$index.unit") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">ارزش واحد</label>
                                                <input type="text" inputmode="numeric" x-model="value" x-on:input="formatValueInput($event)" x-init="value = formatGrouped(value)" class="h-[50px] w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 transition ltr:text-left focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100" placeholder="0">
                                                <p x-cloak x-show="hasLineTotal" class="mt-1.5 text-xs font-medium text-slate-400">
                                                    جمع این دسته:
                                                    <span class="font-semibold text-slate-500" x-text="formattedLineTotal"></span>
                                                    ریال
                                                </p>
                                                @error("categories.$index.value") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="sticky bottom-4 z-20 mt-5 flex justify-end">
                                <button type="button" wire:click="addCategory" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-teal-700">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0">
                                        <path d="M10 4.5V15.5M4.5 10H15.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                    افزودن دسته
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40">
                            <h2 class="text-base font-bold text-slate-800">زمان‌بندی و وضعیت</h2>
                            <div class="mt-4 grid gap-3">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">منطقه خدمت</label>
                                    <div
                                        x-data="{
                                            open: false,
                                            selectedDistrictId: @entangle('serviceDistrictId').live,
                                            filterText: '',
                                            districts: @js($districts->map(fn ($district) => [
                                                'id' => $district->id,
                                                'name' => $district->name,
                                            ])->values()),
                                            get selectedLabel() {
                                                const selected = this.districts.find((item) => Number(item.id) === Number(this.selectedDistrictId));

                                                return selected ? selected.name : 'بدون منطقه مشخص';
                                            },
                                            get filteredDistricts() {
                                                const query = this.filterText.trim().toLowerCase();

                                                if (!query || query === this.selectedLabel.toLowerCase()) {
                                                    return this.districts;
                                                }

                                                return this.districts.filter((item) => item.name.toLowerCase().includes(query));
                                            },
                                            selectDistrict(item) {
                                                this.selectedDistrictId = item.id;
                                                this.filterText = item.name;
                                                this.open = false;
                                            },
                                            clearDistrict() {
                                                this.selectedDistrictId = null;
                                                this.filterText = '';
                                                this.open = false;
                                            }
                                        }"
                                        x-init="
                                            filterText = selectedLabel;
                                            $watch('selectedDistrictId', () => filterText = selectedLabel);
                                        "
                                        x-on:click.outside="open = false"
                                        x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                        class="relative"
                                    >
                                        <input
                                            type="text"
                                            x-model="filterText"
                                            x-on:focus="open = true"
                                            x-on:input="selectedDistrictId = null"
                                            x-on:keydown.escape="open = false"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-20 text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                            placeholder="نام منطقه را جستجو کنید"
                                            autocomplete="off"
                                        >
                                        <button
                                            type="button"
                                            tabindex="-1"
                                            x-cloak
                                            x-show="selectedDistrictId || filterText"
                                            x-on:click.stop.prevent="clearDistrict()"
                                            class="absolute inset-y-0 end-8 flex items-center px-2 text-slate-400 transition hover:text-rose-600"
                                            aria-label="پاک کردن منطقه خدمت"
                                        >
                                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                                <path d="M5.5 5.5L14.5 14.5M14.5 5.5L5.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            tabindex="-1"
                                            x-on:click.stop.prevent="open = !open; filterText = selectedDistrictId ? selectedLabel : filterText"
                                            class="absolute inset-y-0 end-0 flex items-center px-3 text-slate-400"
                                            aria-label="باز کردن فهرست مناطق"
                                        >
                                            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': open }">
                                                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            x-transition.origin.top.left
                                            class="absolute z-30 mt-2 max-h-60 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                        >
                                            <button
                                                type="button"
                                                x-on:click="clearDistrict()"
                                                class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                            >
                                                <span class="font-medium">بدون منطقه مشخص</span>
                                                <span class="text-xs text-slate-400" x-show="!selectedDistrictId">انتخاب شده</span>
                                            </button>
                                            <div class="max-h-52 overflow-y-auto py-1">
                                                <template x-for="item in filteredDistricts" :key="item.id">
                                                    <button
                                                        type="button"
                                                        x-on:click="selectDistrict(item)"
                                                        class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        <span x-text="item.name" class="font-medium"></span>
                                                        <span class="text-xs text-slate-400" x-show="Number(selectedDistrictId) === Number(item.id)">انتخاب شده</span>
                                                    </button>
                                                </template>
                                                <div x-show="filteredDistricts.length === 0" class="px-4 py-3 text-sm text-slate-500">
                                                    منطقه‌ای پیدا نشد.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @error('serviceDistrictId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">شروع توزیع</label>
                                        <input
                                            type="text"
                                            wire:model.blur="distributionStartDate"
                                            inputmode="numeric"
                                            dir="ltr"
                                            placeholder="1405/03/23"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">پایان توزیع</label>
                                        <input
                                            type="text"
                                            wire:model.blur="distributionEndDate"
                                            inputmode="numeric"
                                            dir="ltr"
                                            placeholder="1405/03/30"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                        >
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">اولویت</label>
                                        <div
                                            x-data="{
                                                open: false,
                                                priority: @entangle('priority').live,
                                                priorityOptions: @js($priorityOptions),
                                                get options() {
                                                    return Object.entries(this.priorityOptions).map(([value, label]) => ({ value, label }));
                                                },
                                                get selectedLabel() {
                                                    return this.priorityOptions[this.priority] ?? 'بدون اولویت';
                                                },
                                                selectPriority(value) {
                                                    this.priority = value;
                                                    this.open = false;
                                                }
                                            }"
                                            x-on:click.outside="open = false"
                                            x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                            class="relative"
                                        >
                                            <button
                                                type="button"
                                                x-on:click.stop.prevent="open = !open"
                                                x-on:keydown.escape="open = false"
                                                class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-right text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                aria-haspopup="listbox"
                                                x-bind:aria-expanded="open.toString()"
                                            >
                                                <span x-text="selectedLabel" class="font-medium"></span>
                                                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="{ 'rotate-180': open }">
                                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>

                                            <div
                                                x-cloak
                                                x-show="open"
                                                x-transition.origin.top.left
                                                class="absolute z-30 mt-2 max-h-60 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                                role="listbox"
                                            >
                                                <button
                                                    type="button"
                                                    x-on:click="selectPriority('')"
                                                    class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="font-medium">بدون اولویت</span>
                                                    <span class="text-xs text-slate-400" x-show="priority === ''">انتخاب شده</span>
                                                </button>
                                                <div class="max-h-52 overflow-y-auto py-1">
                                                    <template x-for="item in options" :key="item.value">
                                                        <button
                                                            type="button"
                                                            x-on:click="selectPriority(item.value)"
                                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                            role="option"
                                                            x-bind:aria-selected="priority === item.value"
                                                        >
                                                            <span x-text="item.label" class="font-medium"></span>
                                                            <span class="text-xs text-slate-400" x-show="priority === item.value">انتخاب شده</span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">وضعیت</label>
                                        <div
                                            x-data="{
                                                open: false,
                                                status: @entangle('status').live,
                                                statusOptions: @js($statusOptions),
                                                get options() {
                                                    return Object.entries(this.statusOptions).map(([value, label]) => ({ value, label }));
                                                },
                                                get selectedLabel() {
                                                    return this.statusOptions[this.status] ?? 'انتخاب وضعیت';
                                                },
                                                selectStatus(value) {
                                                    this.status = value;
                                                    this.open = false;
                                                }
                                            }"
                                            x-on:click.outside="open = false"
                                            x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                            class="relative"
                                        >
                                            <button
                                                type="button"
                                                x-on:click.stop.prevent="open = !open"
                                                x-on:keydown.escape="open = false"
                                                class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-right text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                aria-haspopup="listbox"
                                                x-bind:aria-expanded="open.toString()"
                                            >
                                                <span x-text="selectedLabel" class="font-medium"></span>
                                                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="{ 'rotate-180': open }">
                                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>

                                            <div
                                                x-cloak
                                                x-show="open"
                                                x-transition.origin.top.left
                                                class="absolute z-30 mt-2 max-h-60 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                                role="listbox"
                                            >
                                                <div class="max-h-56 overflow-y-auto py-1">
                                                    <template x-for="item in options" :key="item.value">
                                                        <button
                                                            type="button"
                                                            x-on:click="selectStatus(item.value)"
                                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-right text-sm text-slate-700 transition hover:bg-slate-50"
                                                            role="option"
                                                            x-bind:aria-selected="status === item.value"
                                                        >
                                                            <span x-text="item.label" class="font-medium"></span>
                                                            <span class="text-xs text-slate-400" x-show="status === item.value">انتخاب شده</span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت وضعیت</label>
                                    <textarea wire:model.blur="statusNotes" rows="3" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm leading-6 text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"></textarea>
                                </div>
                            </div>
                        </div>

                        @php
                            $categoryCount = count($categories);
                            $averageUnitValue = $categoryCount > 0
                                ? collect($categories)->avg(fn (array $category) => (int) preg_replace('/\D+/', '', (string) ($category['value'] ?? 0)))
                                : 0;
                            $activeUnits = collect($categories)
                                ->pluck('unit')
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp

                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-bold text-slate-800">خلاصه مالی</h2>
                                    <p class="mt-0.5 text-xs text-slate-500">جمع‌بندی فشرده مقدار و ارزش دسته‌ها</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                    {{ $categoryCount }} دسته
                                </span>
                            </div>

                            <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/70">
                                <div class="flex items-center justify-between gap-4 px-3 py-2.5">
                                    <span class="text-xs font-medium text-slate-500">جمع مقدار دسته‌ها</span>
                                    <span class="text-sm font-black text-slate-800">{{ number_format($this->totalQuantity, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-4 px-3 py-2.5">
                                    <span class="text-xs font-medium text-slate-500">ارزش کل</span>
                                    <span class="text-sm font-black text-teal-700">{{ number_format($this->totalServiceValue) }} ریال</span>
                                </div>
                                <div class="flex items-center justify-between gap-4 px-3 py-2.5">
                                    <span class="text-xs font-medium text-slate-500">میانگین ارزش واحد</span>
                                    <span class="text-sm font-bold text-slate-700">{{ number_format((int) $averageUnitValue) }} ریال</span>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @forelse($activeUnits as $unit)
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                                        {{ $unitOptions[$unit] ?? $unit }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400">هنوز واحدی انتخاب نشده است.</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="inline-flex items-center gap-2 rounded-2xl border border-emerald-700/10 bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm shadow-emerald-900/10 transition duration-200 ease-out hover:-translate-y-0.5 hover:from-emerald-500 hover:to-teal-500 hover:shadow-md hover:shadow-emerald-900/15 focus:outline-none focus:ring-4 focus:ring-emerald-200 active:translate-y-0 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-80 disabled:hover:translate-y-0 disabled:hover:shadow-sm"
                            >
                                <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-2">
                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0" aria-hidden="true">
                                        <path d="M4.5 10.5L8.5 14.5L15.5 6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 2.5C5.86 2.5 2.5 5.86 2.5 10C2.5 14.14 5.86 17.5 10 17.5C14.14 17.5 17.5 14.14 17.5 10C17.5 5.86 14.14 2.5 10 2.5Z" stroke="currentColor" stroke-width="1.4" opacity="0.7"/>
                                    </svg>
                                    <span>ثبت نهایی خدمت</span>
                                </span>
                                <span wire:loading.flex wire:target="save" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
                                        <path class="opacity-90" d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                    </svg>
                                    <span>در حال ثبت نهایی</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
