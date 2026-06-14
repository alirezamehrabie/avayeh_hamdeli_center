<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
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

        <div class="px-6 py-6">
            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-bold">لطفاً خطاهای فرم را بررسی کنید.</p>
                    <ul class="mt-2 list-disc space-y-1 pr-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form wire:submit.prevent="save" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.8fr)]">
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
                                <textarea wire:model.blur="description" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
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
                                                    class="relative"
                                                >
                                                    <input
                                                        type="text"
                                                        x-model="filterText"
                                                        x-on:focus="open = true"
                                                        x-on:input="categoryName = filterText"
                                                        x-on:keydown.escape="open = false"
                                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-20 text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                        placeholder="مثال: چلو کباب کوبیده"
                                                        autocomplete="off"
                                                    >
                                                    <button
                                                        type="button"
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
                                                <label class="mb-2 block text-sm font-bold text-slate-700">واحد</label>
                                                <div
                                                    x-data="{
                                                        open: false,
                                                        unit: @entangle('categories.' . $index . '.unit').live,
                                                        unitOptions: @js($unitOptions),
                                                        get options() {
                                                            return Object.entries(this.unitOptions).map(([value, label]) => ({ value, label }));
                                                        },
                                                        get selectedLabel() {
                                                            return this.unitOptions[this.unit] ?? 'انتخاب واحد';
                                                        },
                                                        selectUnit(value) {
                                                            this.unit = value;
                                                            this.open = false;
                                                        }
                                                    }"
                                                    x-on:click.outside="open = false"
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
                                                                    x-on:click="selectUnit(item.value)"
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
                                                <label class="mb-2 block text-sm font-bold text-slate-700">تعداد</label>
                                                <input type="number" min="0.01" step="0.01" x-model="quantity" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100" placeholder="0">
                                                @error("categories.$index.quantity") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">ارزش واحد</label>
                                                <input type="text" inputmode="numeric" x-model="value" x-on:input="formatValueInput($event)" x-init="value = formatGrouped(value)" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 transition ltr:text-left focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100" placeholder="0">
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

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <h2 class="text-lg font-bold text-slate-800">زمان‌بندی و وضعیت</h2>
                            <div class="mt-4 grid gap-3">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">منطقه خدمت</label>
                                    <select wire:model="serviceDistrictId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        <option value="">بدون منطقه مشخص</option>
                                        @foreach($districts as $district)
                                            <option value="{{ $district->id }}">{{ $district->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">شروع توزیع (شمسی)</label>
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
                                        <label class="mb-2 block text-sm font-bold text-slate-700">پایان توزیع (شمسی، اختیاری)</label>
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
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">اولویت</label>
                                    <select wire:model="priority" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        <option value="">بدون اولویت</option>
                                        @foreach($priorityOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">وضعیت</label>
                                    <select wire:model="status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        @foreach($statusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت وضعیت</label>
                                    <textarea wire:model.blur="statusNotes" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-cyan-100 bg-cyan-50 p-5">
                            <h2 class="text-lg font-bold text-cyan-900">خلاصه مالی</h2>
                            <div class="mt-4 grid gap-3">
                                <div class="rounded-2xl bg-white px-4 py-3">
                                    <p class="text-xs text-slate-500">جمع مقدار دسته‌ها</p>
                                    <p class="mt-1 text-lg font-black text-slate-800">{{ number_format($this->totalQuantity, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white px-4 py-3">
                                    <p class="text-xs text-slate-500">ارزش کل</p>
                                    <p class="mt-1 text-lg font-black text-cyan-800">{{ number_format($this->totalServiceValue) }} ریال</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15">
                                {{ $editingServiceId ? 'به‌روزرسانی خدمت' : 'ثبت خدمت جدید' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
