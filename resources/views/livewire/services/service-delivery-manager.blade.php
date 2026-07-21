<div
    x-data="{
        successOpen: false,
        successMessage: 'سهمیه‌ها با موفقیت برای مددکاران ثبت شد.',
        dropdownOpen: false,
        dropdownQuery: '',
        selectedServiceId: @entangle('selectedServiceId').live,
        serviceOptions: @js($services->map(fn ($service) => [
            'id' => (int) $service->id,
            'name' => $service->name ?: ($service->serviceName?->name ?? '-'),
            'items' => (int) $service->categories->count(),
        ])->values()),
        get selectedServiceOption() {
            return this.serviceOptions.find((service) => Number(service.id) === Number(this.selectedServiceId)) ?? null;
        },
        get filteredServiceOptions() {
            const query = this.dropdownQuery.trim().toLowerCase();

            if (! query) {
                return this.serviceOptions.slice(0, 80);
            }

            return this.serviceOptions
                .filter((service) => {
                    return this.serviceOptionSearchText(service).toLowerCase().includes(query);
                })
                .slice(0, 80);
        },
        serviceOptionTitle(service) {
            return service.name || 'خدمت بدون عنوان';
        },
        serviceOptionMeta(service) {
            return `شناسه ${service.id} · ${service.items} آیتم`;
        },
        serviceOptionSearchText(service) {
            return [
                service.id,
                service.name,
                service.items,
                this.serviceOptionTitle(service),
                this.serviceOptionMeta(service),
            ].join(' ');
        },
        openDropdown() {
            this.dropdownOpen = true;
            this.$nextTick(() => this.$refs.serviceSearch?.focus());
        },
        closeDropdown() {
            this.dropdownOpen = false;
            this.dropdownQuery = '';
        },
        selectService(serviceId) {
            this.selectedServiceId = serviceId;
            this.closeDropdown();
        },
        openSuccess(message) {
            this.successMessage = message || this.successMessage;
            this.successOpen = true;
        },
        closeSuccess() {
            this.successOpen = false;
        },
    }"
    x-on:quota-assigned-success.window="openSuccess($event.detail?.message)"
    class="space-y-4"
>
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-indigo-700 via-sky-700 to-cyan-700 px-4 py-3.5 text-white sm:px-5 sm:py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-3">
                <div class="min-w-0">
                    <h1 class="mt-1 truncate text-xl font-extrabold leading-7 text-white sm:text-2xl">مدیریت سهمیه خدمات</h1>
                    <p class="mt-1.5 hidden max-w-2xl text-xs leading-6 text-sky-50/90 lg:block">
                        در این بخش فقط سهمیه مددکاران برای هر آیتم خدمت یا پویش مشخص می‌شود.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 sm:justify-end">
                    <span class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/12 px-2 py-1 text-[10px] font-semibold text-sky-50/90 shadow-sm shadow-sky-950/10 backdrop-blur">
                        <svg viewBox="0 0 24 24" fill="none" class="h-3.5 w-3.5" aria-hidden="true">
                            <path d="M4 7.5h16M7 4v7m10-7v7M6 20h12a2 2 0 0 0 2-2V9H4v9a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>خدمات</span>
                        <span class="font-black text-white">{{ $services->count() }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/12 px-2 py-1 text-[10px] font-semibold text-sky-50/90 shadow-sm shadow-sky-950/10 backdrop-blur">
                        <svg viewBox="0 0 24 24" fill="none" class="h-3.5 w-3.5" aria-hidden="true">
                            <path d="M16 19a4 4 0 0 0-8 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="10" r="4" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                        <span>مددکار</span>
                        <span class="font-black text-white">{{ $selectedWorkers->count() }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/12 px-2 py-1 text-[10px] font-semibold text-sky-50/90 shadow-sm shadow-sky-950/10 backdrop-blur">
                        <svg viewBox="0 0 24 24" fill="none" class="h-3.5 w-3.5" aria-hidden="true">
                            <path d="M12 3v18M7.5 7.5h6.75a2.75 2.75 0 1 1 0 5.5H9.75a2.75 2.75 0 1 0 0 5.5H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>سهمیه</span>
                        <span class="font-black text-white">{{ \App\Models\Service::formatQuantityForUnit($this->currentAllocatedTotal, null) }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-4 px-3 py-3 sm:px-4 sm:py-4">
            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-bold">لطفا خطاهای فرم را بررسی کنید.</p>
                    <ul class="mt-2 list-disc space-y-1 pr-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                x-show="successOpen"
                x-transition.opacity.duration.180ms
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 px-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="quota-success-title"
                x-on:click.self="closeSuccess()"
                x-on:keydown.escape.window="closeSuccess()"
            >
                <div class="w-full max-w-md rounded-3xl border border-emerald-100 bg-white p-6 shadow-2xl shadow-emerald-100/40">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                <path d="M20 7L10 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-600">موفقیت</p>
                            <h3 id="quota-success-title" class="mt-1 text-lg font-black text-slate-900">
                                <span class="block">ثبت سهمیه انجام شد</span>
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500" x-text="successMessage"></p>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button
                            type="button"
                            x-on:click="closeSuccess()"
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            تایید
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[340px_minmax(0,1fr)] xl:gap-5">
                <aside class="space-y-3 xl:sticky xl:top-4 xl:self-start">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="mb-2 block text-sm font-black text-slate-800">انتخاب خدمت / پویش</label>
                        <div
                            class="relative"
                            x-on:click.outside="closeDropdown()"
                            x-on:keydown.escape.window="closeDropdown()"
                        >
                            <button
                                type="button"
                                x-on:click="dropdownOpen ? closeDropdown() : openDropdown()"
                                x-bind:class="{ 'service-selector-highlight service-selector-highlight--2xl': ! selectedServiceId }"
                                class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right text-slate-700 shadow-sm transition hover:border-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                            >
                                <span class="min-w-0 text-right">
                                    <span class="block truncate text-sm font-bold text-slate-900" x-text="selectedServiceOption ? serviceOptionTitle(selectedServiceOption) : 'انتخاب کنید'"></span>
                                    <span class="mt-0.5 block truncate text-xs font-medium text-slate-500" x-show="selectedServiceOption" x-text="selectedServiceOption ? serviceOptionMeta(selectedServiceOption) : ''"></span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="dropdownOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                x-show="dropdownOpen"
                                x-transition.opacity.duration.120ms
                                x-cloak
                                class="absolute right-0 z-30 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div class="border-b border-slate-100 p-3">
                                    <input
                                        x-ref="serviceSearch"
                                        x-model.debounce.150ms="dropdownQuery"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                        placeholder="جستجو با شناسه، نام یا تعداد آیتم"
                                    >
                                </div>

                                <div class="max-h-72 overflow-y-auto p-2">
                                    <template x-if="filteredServiceOptions.length">
                                        <div class="space-y-2">
                                            <template x-for="service in filteredServiceOptions" :key="service.id">
                                                <button
                                                    type="button"
                                                    x-on:click="selectService(service.id)"
                                                    class="w-full rounded-2xl border px-3 py-3 text-right transition"
                                                    x-bind:class="Number(selectedServiceId) === Number(service.id) ? 'border-cyan-400 bg-cyan-50 shadow-sm' : 'border-slate-200 bg-white hover:border-cyan-300 hover:bg-cyan-50/60'"
                                                >
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <span class="block truncate text-sm font-bold text-slate-900" x-text="serviceOptionTitle(service)"></span>
                                                            <span class="mt-0.5 block truncate text-xs font-medium text-slate-500" x-text="serviceOptionMeta(service)"></span>
                                                        </div>

                                                        <span
                                                            class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                                            x-bind:class="Number(selectedServiceId) === Number(service.id) ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-600'"
                                                            x-text="`#${service.id}`"
                                                        ></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="! filteredServiceOptions.length">
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800">
                                            موردی پیدا نشد.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-3 sm:p-4"
                        x-data="{ serviceListOpen: false }"
                    >
                        <button
                            type="button"
                            x-on:click="serviceListOpen = ! serviceListOpen"
                            x-bind:aria-expanded="serviceListOpen.toString()"
                            aria-controls="assignable-service-list"
                            class="flex w-full items-center justify-between gap-3 rounded-xl text-right focus:outline-none focus:ring-2 focus:ring-cyan-100"
                        >
                            <span class="flex items-center gap-2">
                                <span class="text-sm font-black text-slate-800">خدمات آماده تخصیص</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $services->count() }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="serviceListOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="assignable-service-list" x-show="serviceListOpen" x-collapse.duration.250ms x-cloak>
                            <div class="mt-3 max-h-[300px] space-y-2 overflow-y-auto overscroll-contain pl-1 xl:max-h-[420px]">
                                @forelse($services as $service)
                                    @php
                                        $allocated = (float) $service->allocated_quantity;
                                        $total = (float) $service->total_quantity;
                                        $percent = $total > 0 ? min(100, ($allocated / $total) * 100) : 0;
                                    @endphp

                                    <button
                                        type="button"
                                        wire:key="assignable-service-{{ $service->id }}"
                                        wire:click="$set('selectedServiceId', {{ $service->id }})"
                                        class="w-full rounded-xl border px-3 py-2.5 text-right transition hover:border-cyan-300 hover:bg-cyan-50/60 {{ (int) $selectedServiceId === (int) $service->id ? 'border-cyan-400 bg-cyan-50' : 'border-slate-200 bg-white' }}"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-black text-slate-800">{{ $service->name ?: ($service->serviceName?->name ?? '-') }}</p>
                                                <p class="mt-0.5 text-[11px] font-bold text-slate-500">{{ $service->code }} - {{ $service->categories->count() }} آیتم</p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                                                {{ \App\Models\Service::formatQuantityForUnit($allocated, null) }}
                                            </span>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-cyan-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </button>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                        هنوز خدمتی برای تخصیص وجود ندارد.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="min-w-0">
                    @if(! $this->selectedService)
                        <div class="flex min-h-[280px] items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center xl:min-h-[520px]">
                            <div>
                                <p class="text-lg font-black text-slate-800">یک خدمت را انتخاب کنید</p>
                                <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
                                    پس از انتخاب، ساختار خدمت شامل آیتم‌ها و ظرفیت هر آیتم نمایش داده می‌شود و می‌توانید سهمیه مددکاران را ثبت کنید.
                                </p>
                            </div>
                        </div>
                    @else
                        @php($service = $this->selectedService)

                        <form wire:submit.prevent="requestSaveConfirmation" class="space-y-5">
                            <section class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                                <div class="flex flex-col gap-3 sm:gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 sm:px-3 sm:py-1 sm:text-xs">{{ $service->code }}</span>
                                            <span class="rounded-full bg-cyan-50 px-2 py-0.5 text-[10px] font-bold text-cyan-700 sm:px-3 sm:py-1 sm:text-xs">{{ \App\Models\Service::TYPE_OPTIONS[$service->service_type] ?? '-' }}</span>
                                        </div>
                                        <h2 class="mt-2 text-base font-black leading-6 text-slate-900 sm:mt-3 sm:text-xl sm:leading-7">{{ $service->name ?: ($service->serviceName?->name ?? '-') }}</h2>
                                        @if($service->description)
                                            <p class="mt-2 hidden max-w-3xl text-sm leading-6 text-slate-500 sm:block">{{ $service->description }}</p>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-4 gap-1.5 sm:gap-2 lg:w-auto lg:shrink-0">
                                        <div class="rounded-xl bg-slate-50 px-1.5 py-2 text-center sm:rounded-2xl sm:px-3 sm:py-2.5 sm:text-right">
                                            <p class="text-[10px] font-bold leading-4 text-slate-500 sm:text-[11px]">ظرفیت کل</p>
                                            <p class="mt-0.5 text-xs font-black text-slate-900 sm:mt-1 sm:text-sm">{{ \App\Models\Service::formatQuantityForUnit((float) $service->total_quantity, null) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-1.5 py-2 text-center sm:rounded-2xl sm:px-3 sm:py-2.5 sm:text-right">
                                            <p class="text-[10px] font-bold leading-4 text-slate-500 sm:text-[11px]">آیتم‌ها</p>
                                            <p class="mt-0.5 text-xs font-black text-slate-900 sm:mt-1 sm:text-sm">{{ $this->selectedServiceCategories->count() }}</p>
                                        </div>
                                        <div class="rounded-xl bg-cyan-50 px-1.5 py-2 text-center sm:rounded-2xl sm:px-3 sm:py-2.5 sm:text-right">
                                            <p class="text-[10px] font-bold leading-4 text-cyan-700 sm:text-[11px]">تخصیص فعلی</p>
                                            <p class="mt-0.5 text-xs font-black text-cyan-800 sm:mt-1 sm:text-sm">{{ \App\Models\Service::formatQuantityForUnit($this->currentAllocatedTotal, null) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-emerald-50 px-1.5 py-2 text-center sm:rounded-2xl sm:px-3 sm:py-2.5 sm:text-right">
                                            <p class="text-[10px] font-bold leading-4 text-emerald-700 sm:text-[11px]">باقی‌مانده</p>
                                            <p class="mt-0.5 text-xs font-black text-emerald-800 sm:mt-1 sm:text-sm">{{ \App\Models\Service::formatQuantityForUnit($this->remainingAssignableQuantity, null) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section
                                class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4"
                                x-data="{ structureOpen: false }"
                                wire:key="service-structure-{{ $service->id }}"
                            >
                                <button
                                    type="button"
                                    x-on:click="structureOpen = ! structureOpen"
                                    x-bind:aria-expanded="structureOpen.toString()"
                                    aria-controls="service-structure-panel"
                                    class="flex w-full items-center justify-between gap-3 rounded-xl text-right focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                >
                                    <span class="min-w-0">
                                        <span class="block text-base font-black text-slate-900">ساختار خدمت</span>
                                        <span class="mt-1 block text-xs text-slate-500 sm:text-sm">این اطلاعات فقط خواندنی است و از تعریف خدمت می‌آید.</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-2">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $this->selectedServiceCategories->count() }} آیتم</span>
                                        <svg class="h-4 w-4 text-slate-400 transition" x-bind:class="structureOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <div id="service-structure-panel" x-show="structureOpen" x-collapse.duration.250ms x-cloak>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach($this->selectedServiceCategories as $category)
                                            @php($allocated = $this->allocationForCategory((int) $category->id))
                                            @php($remaining = $this->remainingAssignableForCategory((int) $category->id))
                                            @php($categoryTotal = (float) $category->quantity)
                                            @php($categoryPercent = $categoryTotal > 0 ? min(100, ($allocated / $categoryTotal) * 100) : 0)

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3" wire:key="structure-category-{{ $category->id }}">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-black text-slate-900">{{ $category->name }}</p>
                                                        <p class="mt-1 text-[11px] font-bold text-slate-500">{{ $category->code }}</p>
                                                    </div>
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                                        {{ \App\Models\Service::unitOptions()[$category->unit] ?? $category->unit }}
                                                    </span>
                                                </div>
                                                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                                    <div class="rounded-xl bg-white px-2 py-2">
                                                        <p class="text-[10px] font-bold text-slate-500">تعریف‌شده</p>
                                                        <p class="mt-1 text-xs font-black text-slate-900">{{ \App\Models\Service::formatQuantityForUnit($categoryTotal, (string) $category->unit) }}</p>
                                                    </div>
                                                    <div class="rounded-xl bg-white px-2 py-2">
                                                        <p class="text-[10px] font-bold text-cyan-700">تخصیص</p>
                                                        <p class="mt-1 text-xs font-black text-cyan-800">{{ \App\Models\Service::formatQuantityForUnit($allocated, (string) $category->unit) }}</p>
                                                    </div>
                                                    <div class="rounded-xl bg-white px-2 py-2">
                                                        <p class="text-[10px] font-bold text-emerald-700">باقی</p>
                                                        <p class="mt-1 text-xs font-black text-emerald-800">{{ \App\Models\Service::formatQuantityForUnit($remaining, (string) $category->unit) }}</p>
                                                    </div>
                                                </div>
                                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-white">
                                                    <div class="h-full rounded-full bg-cyan-500" style="width: {{ $categoryPercent }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <h2 class="text-base font-black text-slate-900">تخصیص سهمیه مددکاران</h2>
                                        <p class="mt-1 text-sm text-slate-500">برای هر مددکار، مقدار سهمیه هر آیتم را وارد کنید. این مقدار مصرف نیست.</p>
                                    </div>

                                    <div
                                        x-data="{
                                            socialWorkerOpen: false,
                                            activeSocialWorkerIndex: -1,
                                            socialWorkerSearchValue: @entangle('socialWorkerSearch').live,
                                            get socialWorkerOptions() {
                                                return Array.from(this.$refs.socialWorkerList?.querySelectorAll('[data-social-worker-option]') ?? []);
                                            },
                                            get hasEnoughSearchText() {
                                                return (this.socialWorkerSearchValue || '').trim().length >= 2;
                                            },
                                            openSocialWorkerDropdown() {
                                                this.socialWorkerOpen = true;
                                                this.activeSocialWorkerIndex = this.socialWorkerOptions.length ? 0 : -1;
                                            },
                                            closeSocialWorkerDropdown() {
                                                this.socialWorkerOpen = false;
                                                this.activeSocialWorkerIndex = -1;
                                            },
                                            clearSocialWorkerSearch() {
                                                this.socialWorkerSearchValue = '';
                                                this.closeSocialWorkerDropdown();
                                                this.$nextTick(() => this.$refs.socialWorkerSearch?.focus());
                                            },
                                            setActiveSocialWorker(index) {
                                                const options = this.socialWorkerOptions;

                                                if (! options.length) {
                                                    this.activeSocialWorkerIndex = -1;
                                                    return;
                                                }

                                                this.activeSocialWorkerIndex = Math.max(0, Math.min(index, options.length - 1));
                                                this.$nextTick(() => options[this.activeSocialWorkerIndex]?.scrollIntoView({ block: 'nearest' }));
                                            },
                                            moveSocialWorkerActive(step) {
                                                const options = this.socialWorkerOptions;

                                                if (! options.length) {
                                                    return;
                                                }

                                                if (! this.socialWorkerOpen) {
                                                    this.openSocialWorkerDropdown();
                                                    return;
                                                }

                                                const nextIndex = this.activeSocialWorkerIndex < 0
                                                    ? 0
                                                    : (this.activeSocialWorkerIndex + step + options.length) % options.length;

                                                this.setActiveSocialWorker(nextIndex);
                                            },
                                            chooseActiveSocialWorker() {
                                                if (! this.socialWorkerOpen) {
                                                    this.openSocialWorkerDropdown();
                                                    return;
                                                }

                                                this.socialWorkerOptions[this.activeSocialWorkerIndex]?.click();
                                            },
                                            selectSocialWorkerOption(workerId) {
                                                $wire.addSocialWorker(workerId);
                                                this.closeSocialWorkerDropdown();
                                                this.$nextTick(() => this.$refs.socialWorkerSearch?.focus());
                                            },
                                        }"
                                        x-on:click.outside="closeSocialWorkerDropdown()"
                                        x-on:keydown.escape.window="closeSocialWorkerDropdown()"
                                        x-effect="
                                            if (hasEnoughSearchText && document.activeElement === $refs.socialWorkerSearch) {
                                                socialWorkerOpen = true;
                                            }

                                            if (! hasEnoughSearchText) {
                                                closeSocialWorkerDropdown();
                                            }
                                        "
                                        class="relative w-full lg:max-w-md"
                                    >
                                        <label class="sr-only" for="social-worker-search">جستجوی مددکار</label>
                                        <input
                                            id="social-worker-search"
                                            x-ref="socialWorkerSearch"
                                            type="text"
                                            wire:model.live.debounce.250ms="socialWorkerSearch"
                                            x-on:focus="if (hasEnoughSearchText) openSocialWorkerDropdown()"
                                            x-on:keydown.arrow-down.prevent="moveSocialWorkerActive(1)"
                                            x-on:keydown.arrow-up.prevent="moveSocialWorkerActive(-1)"
                                            x-on:keydown.enter.prevent="chooseActiveSocialWorker()"
                                            x-on:keydown.home.prevent="setActiveSocialWorker(0)"
                                            x-on:keydown.end.prevent="setActiveSocialWorker(socialWorkerOptions.length - 1)"
                                            role="combobox"
                                            aria-autocomplete="list"
                                            aria-controls="social-worker-search-results"
                                            x-bind:aria-expanded="socialWorkerOpen.toString()"
                                            x-bind:aria-activedescendant="activeSocialWorkerIndex >= 0 ? `social-worker-option-${activeSocialWorkerIndex}` : null"
                                            autocomplete="off"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-3.5 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-sky-400 focus:bg-white focus:ring-2 focus:ring-sky-100"
                                            placeholder="جستجوی مددکار با نام یا کد"
                                        >
                                        <button
                                            type="button"
                                            x-show="(socialWorkerSearchValue || '').length"
                                            x-cloak
                                            x-on:click="clearSocialWorkerSearch()"
                                            class="absolute left-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-100"
                                            aria-label="پاک کردن جستجو"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 0 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div
                                            x-show="socialWorkerOpen"
                                            x-transition.opacity.duration.100ms
                                            x-cloak
                                            class="fixed inset-x-3 bottom-20 z-30 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/20 sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:mt-2 sm:w-full sm:rounded-xl sm:shadow-xl"
                                        >
                                            <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 sm:hidden">
                                                <div class="mx-auto mb-2 h-1 w-10 rounded-full bg-slate-300"></div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-xs font-bold text-slate-600">
                                                        @if(mb_strlen(trim($socialWorkerSearch)) < 2)
                                                            جستجوی مددکار
                                                        @else
                                                            نتایج جستجو
                                                        @endif
                                                    </p>
                                                    <button
                                                        type="button"
                                                        x-on:click="closeSocialWorkerDropdown()"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-100"
                                                        aria-label="بستن نتایج جستجو"
                                                    >
                                                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 0 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <div
                                                id="social-worker-search-results"
                                                x-ref="socialWorkerList"
                                                role="listbox"
                                                aria-label="نتایج جستجوی مددکار"
                                                class="max-h-[min(20rem,52vh)] overscroll-contain overflow-y-auto p-2 scroll-py-2"
                                            >
                                                @if(mb_strlen(trim($socialWorkerSearch)) >= 2)
                                                    <div class="space-y-2">
                                                        @forelse($availableSocialWorkers as $socialWorker)
                                                            <button
                                                                type="button"
                                                                id="social-worker-option-{{ $loop->index }}"
                                                                data-social-worker-option
                                                                role="option"
                                                                wire:key="service-delivery-social-worker-option-{{ $socialWorker->id }}"
                                                                x-on:mouseenter="setActiveSocialWorker({{ $loop->index }})"
                                                                x-on:click="selectSocialWorkerOption({{ $socialWorker->id }})"
                                                                x-bind:aria-selected="activeSocialWorkerIndex === {{ $loop->index }}"
                                                                class="group flex min-h-[3.25rem] w-full items-center justify-between gap-3 rounded-xl border px-3 py-3 text-right transition focus:outline-none focus:ring-2 focus:ring-sky-100"
                                                                x-bind:class="activeSocialWorkerIndex === {{ $loop->index }} ? 'border-sky-300 bg-sky-50 shadow-sm' : 'border-slate-200 bg-white hover:border-sky-200 hover:bg-slate-50'"
                                                            >
                                                                <span
                                                                    class="h-9 w-1 shrink-0 rounded-full transition"
                                                                    x-bind:class="activeSocialWorkerIndex === {{ $loop->index }} ? 'bg-sky-500' : 'bg-slate-200 group-hover:bg-sky-300'"
                                                                    aria-hidden="true"
                                                                ></span>
                                                                <span class="flex min-w-0 flex-1 items-center gap-3">
                                                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-black text-slate-600 transition group-hover:border-sky-200 group-hover:bg-white group-hover:text-sky-700">
                                                                        {{ mb_substr($socialWorker->full_name, 0, 1) }}
                                                                    </span>
                                                                    <span class="min-w-0">
                                                                        <span class="block truncate text-sm font-black text-slate-900">{{ $socialWorker->full_name }}</span>
                                                                        <span class="mt-1 block truncate text-xs font-semibold text-slate-500">کد مددکاری: {{ $socialWorker->worker_code }}</span>
                                                                    </span>
                                                                </span>
                                                                <span
                                                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition"
                                                                    x-bind:class="activeSocialWorkerIndex === {{ $loop->index }} ? 'border-sky-200 bg-white text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-500 group-hover:border-sky-200 group-hover:text-sky-700'"
                                                                >
                                                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                                                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                                                                    </svg>
                                                                    <span>افزودن</span>
                                                                </span>
                                                            </button>
                                                        @empty
                                                            <div class="flex min-h-[3.25rem] items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800" role="status">
                                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/80 text-amber-600">
                                                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.36 9.86l2.64 2.64a.75.75 0 1 0 1.06-1.06l-2.64-2.64A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </span>
                                                                <span class="font-bold">مددکاری پیدا نشد.</span>
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                @else
                                                    <div class="flex min-h-[3.25rem] items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600" role="status">
                                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400">
                                                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.36 9.86l2.64 2.64a.75.75 0 1 0 1.06-1.06l-2.64-2.64A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                        <span class="font-semibold">نام، نام خانوادگی یا کد مددکاری را وارد کنید.</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @forelse($selectedWorkers as $worker)
                                        <div
                                            class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
                                            wire:key="selected-worker-{{ $worker->id }}"
                                            x-data="{ workerOpen: false }"
                                        >
                                            <div class="flex items-center justify-between gap-2">
                                                <button
                                                    type="button"
                                                    x-on:click="workerOpen = ! workerOpen"
                                                    x-bind:aria-expanded="workerOpen.toString()"
                                                    aria-controls="worker-allocation-panel-{{ $worker->id }}"
                                                    class="flex min-w-0 flex-1 items-center gap-2 rounded-xl text-right focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                                >
                                                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="workerOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="min-w-0">
                                                        <span class="block truncate text-sm font-black text-slate-900">{{ $worker->full_name }}</span>
                                                        <span class="mt-0.5 block text-xs font-bold text-slate-500">کد مددکاری: {{ $worker->worker_code }}</span>
                                                    </span>
                                                </button>
                                                <div class="flex shrink-0 items-center gap-2">
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-cyan-700">
                                                        مجموع: {{ \App\Models\Service::formatQuantityForUnit($this->allocationForWorker((int) $worker->id), null) }}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        x-data="{
                                                            confirmRemoveWorker() {
                                                                window.dispatchEvent(new CustomEvent('open-notification-modal', {
                                                                    detail: {
                                                                        config: {
                                                                            type: 'warning',
                                                                            icon: 'warning',
                                                                            title: 'حذف مددکار از تخصیص',
                                                                            message: @js('آیا از حذف «'.$worker->full_name.'» (کد مددکاری: '.$worker->worker_code.') از فهرست تخصیص این خدمت مطمئن هستید؟'."\n".'سهمیه‌های واردشده برای این مددکار پاک می‌شود و پس از ذخیره نهایی، این عملیات ممکن است غیرقابل بازگشت باشد.'),
                                                                            buttons: [
                                                                                {
                                                                                    label: 'تایید حذف',
                                                                                    action: 'event',
                                                                                    event: 'confirm-social-worker-remove',
                                                                                    payload: { workerId: {{ $worker->id }} },
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
                                                                }));
                                                            },
                                                        }"
                                                        x-on:click="confirmRemoveWorker()"
                                                        wire:loading.attr="disabled"
                                                        wire:target="removeSocialWorkerConfirmed"
                                                        class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        <svg wire:loading wire:target="removeSocialWorkerConfirmed" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
                                                            <path class="opacity-90" d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                                        </svg>
                                                        <span wire:loading.remove wire:target="removeSocialWorkerConfirmed">حذف</span>
                                                        <span wire:loading wire:target="removeSocialWorkerConfirmed">در حال حذف...</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="worker-allocation-panel-{{ $worker->id }}" x-show="workerOpen" x-collapse.duration.250ms x-cloak>
                                                <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                    @foreach($this->selectedServiceCategories as $category)
                                                        <div class="rounded-2xl border border-slate-200 bg-white p-3" wire:key="allocation-cell-{{ $worker->id }}-{{ $category->id }}">
                                                            <div class="mb-2 flex items-start justify-between gap-2">
                                                                <div class="min-w-0">
                                                                    <p class="truncate text-xs font-black text-slate-900">{{ $category->name }}</p>
                                                                    <p class="mt-1 text-[10px] font-bold text-slate-500">ظرفیت باقی: {{ \App\Models\Service::formatQuantityForUnit($this->remainingAssignableForCategory((int) $category->id), (string) $category->unit) }}</p>
                                                                </div>
                                                                <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">
                                                                    {{ \App\Models\Service::unitOptions()[$category->unit] ?? $category->unit }}
                                                                </span>
                                                            </div>
                                                            <label class="sr-only" for="allocation-{{ $worker->id }}-{{ $category->id }}">
                                                                مقدار سهمیه {{ $worker->full_name }} برای {{ $category->name }}
                                                            </label>
                                                            <input
                                                                id="allocation-{{ $worker->id }}-{{ $category->id }}"
                                                                type="text"
                                                                inputmode="decimal"
                                                                pattern="^\d+(\.\d{1,2})?$"
                                                                autocomplete="off"
                                                                wire:model.live.debounce.250ms="allocations.{{ $worker->id }}.{{ $category->id }}"
                                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-sm font-black text-slate-900 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                                placeholder="0"
                                                            >
                                                            @error('allocations.' . $worker->id . '.' . $category->id)
                                                                <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                                            مددکار را جستجو و اضافه کنید تا ماتریس سهمیه نمایش داده شود.
                                        </div>
                                    @endforelse
                                </div>
                            </section>

                            <div class="sticky bottom-3 z-10 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg shadow-slate-900/5 backdrop-blur-sm sm:p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-slate-600 sm:gap-2">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 sm:px-3">
                                            مددکاران: <span class="font-black text-slate-900">{{ $selectedWorkers->count() }}</span>
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 sm:px-3">
                                            تخصیص کل: <span class="font-black text-slate-900">{{ \App\Models\Service::formatQuantityForUnit($this->currentAllocatedTotal, null) }}</span>
                                        </span>
                                        <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-cyan-700 sm:px-3">
                                            باقی قابل تخصیص: <span class="font-black text-cyan-800">{{ \App\Models\Service::formatQuantityForUnit($this->remainingAssignableQuantity, null) }}</span>
                                        </span>
                                    </div>

                                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                        @if($showSavedSummary)
                                            <button
                                                type="button"
                                                wire:click="enableEditing"
                                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                                            >
                                                ادامه ویرایش
                                            </button>
                                        @endif
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="requestSaveConfirmation"
                                            class="group inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition duration-200 hover:bg-cyan-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70"
                                        >
                                            <svg wire:loading wire:target="requestSaveConfirmation" class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
                                                <path class="opacity-90" d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="requestSaveConfirmation">ذخیره سهمیه‌ها</span>
                                            <span wire:loading wire:target="requestSaveConfirmation">در حال ذخیره...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        @if($confirmingAllocationSave)
                            <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-0 sm:items-center sm:px-4 sm:py-6">
                                <div class="flex max-h-[92dvh] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[85dvh] sm:rounded-3xl">
                                    <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                                        <div>
                                            <h3 class="text-lg font-black text-slate-900">تایید نهایی تخصیص سهمیه</h3>
                                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                                پیش از ثبت نهایی، مجموع سهمیه هر مددکار و هر آیتم را بررسی کنید. با تایید این مرحله، سهمیه‌های ذخیره‌شده این خدمت با مقادیر جدید جایگزین می‌شوند.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="cancelSaveConfirmation"
                                            class="w-fit rounded-full border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-600 transition hover:bg-slate-50"
                                        >
                                            بستن
                                        </button>
                                    </div>

                                    <div class="grid gap-3 overflow-y-auto overscroll-contain p-4 sm:p-5 md:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <p class="mb-2 text-sm font-black text-slate-800">جمع سهمیه مددکاران</p>
                                            <div class="max-h-56 space-y-2 overflow-y-auto">
                                                @foreach($selectedWorkers as $worker)
                                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 text-sm" wire:key="confirm-worker-{{ $worker->id }}">
                                                        <span class="truncate font-bold text-slate-700">{{ $worker->full_name }}</span>
                                                        <span class="font-black text-cyan-700">{{ \App\Models\Service::formatQuantityForUnit($this->allocationForWorker((int) $worker->id), null) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <p class="mb-2 text-sm font-black text-slate-800">جمع سهمیه آیتم‌ها</p>
                                            <div class="max-h-56 space-y-2 overflow-y-auto">
                                                @foreach($this->selectedServiceCategories as $category)
                                                    <div class="rounded-xl bg-white px-3 py-2 text-sm" wire:key="confirm-category-{{ $category->id }}">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="truncate font-bold text-slate-700">{{ $category->name }}</span>
                                                            <span class="font-black text-cyan-700">{{ \App\Models\Service::formatQuantityForUnit($this->allocationForCategory((int) $category->id), (string) $category->unit) }}</span>
                                                        </div>
                                                        <p class="mt-1 text-[11px] font-bold text-slate-400">
                                                            ظرفیت تعریف‌شده: {{ \App\Models\Service::formatQuantityForUnit((float) $category->quantity, (string) $category->unit) }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 p-4 sm:flex-row sm:justify-end sm:p-5">
                                        <button
                                            type="button"
                                            wire:click="cancelSaveConfirmation"
                                            class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
                                        >
                                            بازگشت به ویرایش
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="confirmSaveAllocations"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmSaveAllocations"
                                            class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm transition duration-200 hover:bg-emerald-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70"
                                        >
                                            <svg wire:loading wire:target="confirmSaveAllocations" class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
                                                <path class="opacity-90" d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="confirmSaveAllocations">تایید و ذخیره نهایی</span>
                                            <span wire:loading wire:target="confirmSaveAllocations">در حال ثبت نهایی...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </main>
            </div>
        </div>
    </div>
</div>
