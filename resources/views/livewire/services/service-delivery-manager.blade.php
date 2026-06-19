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
        <div class="bg-gradient-to-l from-indigo-700 via-sky-700 to-cyan-700 px-5 py-4 text-white">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-3">
                <div class="min-w-0">
                    <h1 class="mt-1 truncate text-2xl font-extrabold leading-7 text-white">مدیریت سهمیه خدمات</h1>
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
                        <span class="font-black text-white">{{ number_format($this->currentAllocatedTotal, 2) }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-4 px-4 py-4">
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

            <div class="grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
                <aside class="space-y-4">
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

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="text-sm font-black text-slate-800">خدمات آماده تخصیص</h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $services->count() }}</span>
                        </div>

                        <div class="max-h-[520px] space-y-2 overflow-y-auto pr-1">
                            @forelse($services as $service)
                                @php
                                    $allocated = (float) $service->allocated_quantity;
                                    $total = (float) $service->total_quantity;
                                    $percent = $total > 0 ? min(100, ($allocated / $total) * 100) : 0;
                                @endphp

                                <button
                                    type="button"
                                    wire:click="$set('selectedServiceId', {{ $service->id }})"
                                    class="w-full rounded-2xl border px-4 py-3 text-right transition hover:border-cyan-300 hover:bg-cyan-50/60 {{ (int) $selectedServiceId === (int) $service->id ? 'border-cyan-400 bg-cyan-50' : 'border-slate-200 bg-white' }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-800">{{ $service->name ?: ($service->serviceName?->name ?? '-') }}</p>
                                            <p class="mt-1 text-[11px] font-bold text-slate-500">{{ $service->code }} - {{ $service->categories->count() }} آیتم</p>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                            {{ number_format($allocated, 2) }}
                                        </span>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
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
                </aside>

                <main class="min-w-0">
                    @if(! $this->selectedService)
                        <div class="flex min-h-[520px] items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
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
                            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $service->code }}</span>
                                            <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">{{ \App\Models\Service::TYPE_OPTIONS[$service->service_type] ?? '-' }}</span>
                                        </div>
                                        <h2 class="mt-3 text-xl font-black text-slate-900">{{ $service->name ?: ($service->serviceName?->name ?? '-') }}</h2>
                                        @if($service->description)
                                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $service->description }}</p>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[520px]">
                                        <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
                                            <p class="text-[11px] font-bold text-slate-500">ظرفیت کل</p>
                                            <p class="mt-1 text-sm font-black text-slate-900">{{ number_format((float) $service->total_quantity, 2) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
                                            <p class="text-[11px] font-bold text-slate-500">آیتم‌ها</p>
                                            <p class="mt-1 text-sm font-black text-slate-900">{{ $this->selectedServiceCategories->count() }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-cyan-50 px-3 py-2.5">
                                            <p class="text-[11px] font-bold text-cyan-700">تخصیص فعلی</p>
                                            <p class="mt-1 text-sm font-black text-cyan-800">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-emerald-50 px-3 py-2.5">
                                            <p class="text-[11px] font-bold text-emerald-700">باقی‌مانده</p>
                                            <p class="mt-1 text-sm font-black text-emerald-800">{{ number_format($this->remainingAssignableQuantity, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                                    <div>
                                        <h2 class="text-base font-black text-slate-900">ساختار خدمت</h2>
                                        <p class="mt-1 text-sm text-slate-500">این اطلاعات فقط خواندنی است و از تعریف خدمت می‌آید.</p>
                                    </div>
                                    <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                        نام خدمت → آیتم‌ها → مقدار قابل تخصیص
                                    </span>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($this->selectedServiceCategories as $category)
                                        @php($allocated = $this->allocationForCategory((int) $category->id))
                                        @php($remaining = $this->remainingAssignableForCategory((int) $category->id))
                                        @php($categoryTotal = (float) $category->quantity)
                                        @php($categoryPercent = $categoryTotal > 0 ? min(100, ($allocated / $categoryTotal) * 100) : 0)

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
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
                                                    <p class="mt-1 text-xs font-black text-slate-900">{{ number_format($categoryTotal, 2) }}</p>
                                                </div>
                                                <div class="rounded-xl bg-white px-2 py-2">
                                                    <p class="text-[10px] font-bold text-cyan-700">تخصیص</p>
                                                    <p class="mt-1 text-xs font-black text-cyan-800">{{ number_format($allocated, 2) }}</p>
                                                </div>
                                                <div class="rounded-xl bg-white px-2 py-2">
                                                    <p class="text-[10px] font-bold text-emerald-700">باقی</p>
                                                    <p class="mt-1 text-xs font-black text-emerald-800">{{ number_format($remaining, 2) }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white">
                                                <div class="h-full rounded-full bg-cyan-500" style="width: {{ $categoryPercent }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <h2 class="text-base font-black text-slate-900">تخصیص سهمیه مددکاران</h2>
                                        <p class="mt-1 text-sm text-slate-500">برای هر مددکار، مقدار سهمیه هر آیتم را وارد کنید. این مقدار مصرف نیست.</p>
                                    </div>

                                    <div class="relative w-full lg:max-w-sm">
                                        <input
                                            type="text"
                                            wire:model.live.debounce.250ms="socialWorkerSearch"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                            placeholder="جستجوی مددکار با نام یا کد"
                                        >

                                        @if($socialWorkerSearch !== '')
                                            <div class="absolute right-0 z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                                                <div class="max-h-72 overflow-y-auto p-2">
                                                    @forelse($availableSocialWorkers as $socialWorker)
                                                        <button
                                                            type="button"
                                                            wire:click="addSocialWorker({{ $socialWorker->id }})"
                                                            class="mb-2 flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right transition hover:border-cyan-300 hover:bg-cyan-50 relative isolate"
                                                        >
                                                            <span aria-hidden="true" class="pointer-events-none absolute -inset-1 rounded-xl bg-[conic-gradient(from_0deg,rgba(34,211,238,0),rgba(34,211,238,0.18),rgba(16,185,129,0.12),rgba(59,130,246,0.18),rgba(34,211,238,0))] opacity-70 blur-md motion-safe:animate-[spin_8s_linear_infinite]"></span>
                                                            <span aria-hidden="true" class="pointer-events-none absolute inset-[1px] rounded-[11px] bg-slate-50/85 transition group-hover:bg-cyan-50/70"></span>
                                                            <span class="min-w-0">
                                                                <span class="relative z-10 block truncate text-sm font-black text-slate-800">{{ $socialWorker->full_name }}</span>
                                                                <span class="relative z-10 mt-1 block text-xs font-bold text-slate-500">کد مددکاری: {{ $socialWorker->worker_code }}</span>
                                                            </span>
                                                            <span class="relative z-10 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-cyan-700 shadow-sm shadow-cyan-500/10">افزودن</span>
                                                        </button>
                                                    @empty
                                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800">
                                                            مددکاری پیدا نشد.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @forelse($selectedWorkers as $worker)
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-black text-slate-900">{{ $worker->full_name }}</p>
                                                    <p class="mt-1 text-xs font-bold text-slate-500">کد مددکاری: {{ $worker->worker_code }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-cyan-700">
                                                        مجموع: {{ number_format($this->allocationForWorker((int) $worker->id), 2) }}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        wire:click="removeSocialWorker({{ $worker->id }})"
                                                        class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
                                                    >
                                                        حذف
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                                @foreach($this->selectedServiceCategories as $category)
                                                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                        <div class="mb-2 flex items-start justify-between gap-2">
                                                            <div class="min-w-0">
                                                                <p class="truncate text-xs font-black text-slate-900">{{ $category->name }}</p>
                                                                <p class="mt-1 text-[10px] font-bold text-slate-500">ظرفیت باقی: {{ number_format($this->remainingAssignableForCategory((int) $category->id), 2) }}</p>
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
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                                            مددکار را جستجو و اضافه کنید تا ماتریس سهمیه نمایش داده شود.
                                        </div>
                                    @endforelse
                                </div>
                            </section>

                            <div class="sticky bottom-3 z-10 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur-sm sm:static">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-600">
                                        <span class="rounded-full bg-slate-100 px-3 py-1">
                                            مددکاران: <span class="font-black text-slate-900">{{ $selectedWorkers->count() }}</span>
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-3 py-1">
                                            تخصیص کل: <span class="font-black text-slate-900">{{ number_format($this->currentAllocatedTotal, 2) }}</span>
                                        </span>
                                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-cyan-700">
                                            باقی قابل تخصیص: <span class="font-black text-cyan-800">{{ number_format($this->remainingAssignableQuantity, 2) }}</span>
                                        </span>
                                    </div>

                                    <div class="flex flex-col gap-2 sm:flex-row">
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
                            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
                                <div class="w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 class="text-lg font-black text-slate-900">تایید نهایی تخصیص سهمیه</h3>
                                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                                پیش از ثبت نهایی، مجموع سهمیه هر مددکار و هر آیتم را بررسی کنید. با تایید این مرحله، سهمیه‌های ذخیره‌شده این خدمت با مقادیر جدید جایگزین می‌شوند.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="cancelSaveConfirmation"
                                            class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-600 transition hover:bg-slate-50"
                                        >
                                            بستن
                                        </button>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <p class="mb-2 text-sm font-black text-slate-800">جمع سهمیه مددکاران</p>
                                            <div class="max-h-56 space-y-2 overflow-y-auto">
                                                @foreach($selectedWorkers as $worker)
                                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 text-sm">
                                                        <span class="truncate font-bold text-slate-700">{{ $worker->full_name }}</span>
                                                        <span class="font-black text-cyan-700">{{ number_format($this->allocationForWorker((int) $worker->id), 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <p class="mb-2 text-sm font-black text-slate-800">جمع سهمیه آیتم‌ها</p>
                                            <div class="max-h-56 space-y-2 overflow-y-auto">
                                                @foreach($this->selectedServiceCategories as $category)
                                                    <div class="rounded-xl bg-white px-3 py-2 text-sm">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="truncate font-bold text-slate-700">{{ $category->name }}</span>
                                                            <span class="font-black text-cyan-700">{{ number_format($this->allocationForCategory((int) $category->id), 2) }}</span>
                                                        </div>
                                                        <p class="mt-1 text-[11px] font-bold text-slate-400">
                                                            ظرفیت تعریف‌شده: {{ number_format((float) $category->quantity, 2) }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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
