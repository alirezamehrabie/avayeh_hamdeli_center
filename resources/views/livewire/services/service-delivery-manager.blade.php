<div
    x-data="{ showQuotaSavedModal: false }"
    x-on:quota-saved.window="showQuotaSavedModal = true"
    class="space-y-6"
>
    <div class="overflow-hidden rounded-[32px] border border-emerald-100 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-emerald-500 via-teal-500 to-cyan-500 px-5 py-6 text-white sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
{{--                    <p class="text-sm font-semibold text-emerald-50">Manager Allocation</p>--}}
                    <h1 class="mt-2 text-2xl font-extrabold">تخصیص سهمیه خدمت به مددکاران</h1>
                    <p class="mt-2 max-w-3xl text-sm text-emerald-50/95">
                        خدمت موردنظر را انتخاب کنید، سپس مددکاران موردنیاز را اضافه کرده و سهمیه هر کدام را تنظیم کنید.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-4 py-6 sm:px-6">
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

            <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex-1">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label class="block text-sm font-bold text-slate-700">خدمت</label>
                            <span class="text-[11px] font-medium text-slate-400">شناسه / نام خدمت / دسته</span>
                        </div>
                        <select wire:model.live="selectedServiceId" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                            <option value="">انتخاب خدمت</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->code }} | {{ $service->serviceName?->name ?? '-' }} | {{ $service->categories->count() }} آیتم
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($this->selectedService)
                        <button
                            type="button"
                            wire:click="$set('selectedServiceId', null)"
                            class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700"
                        >
                            نمایش همه خدمات
                        </button>
                    @endif
                </div>

                <p class="mt-3 text-xs font-medium text-slate-500">
                    خدمت را انتخاب کنید، سهمیه‌ها را ثبت کنید و در پایان فقط خلاصه نهایی را ببینید.
                </p>

                @if($this->selectedService)
                    <div class="mt-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-cyan-50 px-3 py-1 text-[11px] font-bold text-cyan-700">خدمت انتخاب‌شده</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">{{ $this->selectedService->code }}</span>
                                </div>
                                <h2 class="mt-3 text-base font-black text-slate-800">{{ $this->selectedService->serviceName?->name ?? '-' }}</h2>
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium text-slate-500">
                                    <span>{{ \App\Models\Service::TYPE_OPTIONS[$this->selectedService->service_type] ?? '-' }}</span>
                                    <span class="hidden sm:inline text-slate-300">•</span>
                                    <span>{{ $selectedWorkers->count() }} مددکار</span>
                                    @if($this->selectedService->serviceCategory?->name)
                                        <span class="hidden sm:inline text-slate-300">•</span>
                                        <span>{{ $this->selectedService->categories->count() }} آیتم خدمت</span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[420px]">
                                <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold text-slate-500">کل</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ number_format((float) $this->selectedService->total_quantity, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold text-slate-500">تحویل</p>
                                    <p class="mt-1 text-sm font-black text-teal-700">{{ number_format((float) $this->selectedService->quantity_delivered, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold text-slate-500">تخصیص</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-cyan-50 px-3 py-2.5">
                                    <p class="text-[10px] font-semibold text-cyan-700">باقی سهمیه</p>
                                    <p class="mt-1 text-sm font-black text-cyan-800">{{ number_format($this->remainingAssignableQuantity, 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-3">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-xs font-black text-slate-700">آیتم‌های قابل تحویل</p>
                                <p class="text-[11px] font-bold text-cyan-700">
                                    موجودی کل: {{ number_format((float) $this->selectedService->remaining_stock_quantity, 2) }}
                                </p>
                            </div>
                            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($this->selectedService->categories->sortBy('sort_id') as $category)
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-black text-slate-800">{{ $category->name }}</p>
                                                <p class="mt-1 text-[10px] font-semibold text-slate-500">{{ $category->code }}</p>
                                            </div>
                                            <span class="rounded-full {{ (float) $category->quantity > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-2 py-1 text-[10px] font-bold">
                                                {{ (float) $category->quantity > 0 ? 'موجود' : 'ناموجود' }}
                                            </span>
                                        </div>
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-600">
                                            <span class="rounded-full bg-slate-50 px-2 py-1">موجودی: {{ number_format((float) $category->quantity, 2) }} {{ \App\Models\Service::unitOptions()[$category->unit] ?? $category->unit }}</span>
                                            <span class="rounded-full bg-slate-50 px-2 py-1">ارزش واحد: {{ number_format((int) $category->value) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if(! $this->selectedService)
                    <div class="mt-5">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-black text-slate-800">خدمات ثبت‌شده و تحویلی</h2>
                                <p class="mt-1 text-xs text-slate-500">روی هر کارت بزنید تا همان خدمت برای ویرایش باز شود.</p>
                            </div>
                            <div class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 shadow-sm">
                                {{ $services->count() }} خدمت
                            </div>
                        </div>

                        <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:grid lg:grid-cols-2 lg:overflow-visible lg:pb-0">
                            @forelse($services as $service)
                                <button
                                    type="button"
                                    wire:click="$set('selectedServiceId', {{ $service->id }})"
                                    class="min-w-[220px] max-w-[220px] snap-start shrink-0 overflow-hidden rounded-[26px] border border-slate-200 bg-white text-right shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200 sm:min-w-[236px] sm:max-w-[236px] lg:min-w-0 lg:max-w-none"
                                >
                                    <div class="flex items-start justify-between gap-3 px-4 pb-2 pt-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-800">{{ $service->serviceName?->name ?? '-' }}</p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-500">{{ $service->code }} - {{ \App\Models\Service::TYPE_OPTIONS[$service->service_type] ?? '-' }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-bold
                                            {{ $service->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($service->status === 'in_distribution' ? 'bg-amber-100 text-amber-700' : 'bg-cyan-100 text-cyan-700') }}">
                                            {{ $service->status === 'completed' ? 'تکمیل‌شده' : ($service->status === 'in_distribution' ? 'در حال توزیع' : 'آماده توزیع') }}
                                        </span>
                                    </div>

                                    <div class="px-4 pb-4">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                <p class="text-[10px] font-semibold text-slate-500">کل / تحویل</p>
                                                <p class="mt-1 text-sm font-black text-slate-800">
                                                    {{ number_format((float) $service->total_quantity, 2) }}
                                                    <span class="text-xs font-bold text-teal-700">/ {{ number_format((float) $service->quantity_delivered, 2) }}</span>
                                                </p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                <p class="text-[10px] font-semibold text-cyan-700">موجودی / سهمیه</p>
                                                <p class="mt-1 text-sm font-black text-cyan-800">{{ number_format((float) $service->remaining_stock_quantity, 2) }} / {{ number_format((float) $service->remaining_assignable_quantity, 2) }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-semibold text-slate-500">مددکار / تخصیص</p>
                                                <p class="mt-1 truncate text-xs font-bold text-slate-700">
                                                    {{ $service->socialWorkers->count() }} مددکار - {{ number_format((float) $service->allocated_quantity, 2) }}
                                                </p>
                                            </div>
                                            <div class="h-2.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 via-cyan-500 to-sky-500" style="width: {{ min(100, max(0, (float) $service->progress_percentage)) }}%"></div>
                                            </div>
                                        </div>

                                        <div class="mt-3 border-t border-slate-100 pt-3">
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @forelse($service->socialWorkers->take(4) as $socialWorker)
                                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">
                                                        {{ $socialWorker->full_name }}
                                                    </span>
                                                @empty
                                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500">
                                                        هنوز مددکاری تخصیص نشده
                                                    </span>
                                                @endforelse

                                                @if($service->socialWorkers->count() > 4)
                                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">
                                                        +{{ $service->socialWorkers->count() - 4 }} نفر دیگر
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">
                                    هنوز خدمتی برای نمایش در بخش توزیع ثبت نشده است.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

            @if($this->selectedService)
                <form wire:submit.prevent="saveAllocations" class="mt-6 space-y-5">
                    @if($this->showSavedSummary)
                        <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 p-4 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ $this->selectedService->code }} - {{ $this->selectedService->serviceName?->name ?? '-' }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="enableEditing"
                                    class="rounded-2xl border border-cyan-200 bg-white px-4 py-2 text-sm font-bold text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-50"
                                >
                                    ویرایش
                                </button>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-2xl bg-white/90 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-slate-500">Service</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ $this->selectedService->serviceName?->name ?? '-' }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/90 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-slate-500">Workers</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ $selectedWorkers->count() }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/90 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-slate-500">Assigned</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/90 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-slate-500">Remaining</p>
                                    <p class="mt-1 text-sm font-black text-cyan-700">{{ number_format($this->remainingAssignableQuantity, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-white/80 bg-white/80 px-3 py-3 text-xs font-medium text-slate-600">
                                مرحله تخصیص بسته شد. برای تغییر سهمیه‌ها فقط از دکمه ویرایش استفاده کنید.
                            </div>

                            <div class="mt-4 space-y-2">
                                @foreach($selectedWorkers as $worker)
                                    @php
                                        $summaryAllocated = (float) ($this->allocations[$worker->id] ?? 0);
                                        $summaryDelivered = $this->selectedService->deliveredQuantityForWorker($worker->id);
                                    @endphp
                                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-white/80 bg-white/85 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $worker->full_name }}</p>
                                            <p class="text-[11px] text-slate-500">Code: {{ $worker->worker_code }}</p>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-sm font-black text-slate-800">{{ number_format($summaryAllocated, 2) }}</p>
                                            <p class="text-[11px] text-teal-700">Delivered: {{ number_format($summaryDelivered, 2) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">مددکاران مسئول</h2>
                                    <p class="mt-1 text-sm text-slate-500">فقط مددکاران موردنیاز را اضافه کنید و سهمیه هر نفر را مشخص کنید.</p>
                                </div>

                                <div
                                    x-data="{ open: false }"
                                    @click.outside="open = false"
                                    class="relative w-full sm:max-w-md"
                                >
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="flex w-full items-center justify-between rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-right text-sm font-bold text-cyan-800 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-100/70"
                                    >
                                        <span>+ افزودن مددکار</span>
                                        <svg x-bind:class="open ? 'rotate-180' : ''" class="h-5 w-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div
                                        x-show="open"
                                        x-transition.opacity.duration.150ms
                                        style="display: none;"
                                        class="absolute right-0 z-20 mt-3 w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-cyan-100/60"
                                    >
                                        <div class="border-b border-slate-100 bg-slate-50 p-4">
                                            <label class="mb-2 block text-sm font-bold text-slate-700">جستجوی مددکار</label>
                                            <input
                                                type="text"
                                                wire:model.live.debounce.250ms="socialWorkerSearch"
                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                                placeholder="نام یا کد مددکاری"
                                            >
                                        </div>

                                        <div class="max-h-72 overflow-y-auto p-3">
                                            @forelse($availableSocialWorkers as $socialWorker)
                                                <button
                                                    type="button"
                                                    wire:click="addSocialWorker({{ $socialWorker->id }})"
                                                    @click="open = false"
                                                    class="mb-2 flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right transition hover:border-cyan-300 hover:bg-cyan-50"
                                                >
                                                    <span class="min-w-0">
                                                        <span class="block truncate text-sm font-bold text-slate-800">{{ $socialWorker->full_name }}</span>
                                                        <span class="mt-1 block text-xs text-slate-500">کد مددکار: {{ $socialWorker->worker_code }}</span>
                                                    </span>
                                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-cyan-700">افزودن</span>
                                                </button>
                                            @empty
                                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                                                    مددکار دیگری برای افزودن پیدا نشد.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @error('selectedWorkerIds')
                                <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                            @error('allocations_total')
                                <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-slate-500">مددکار انتخاب‌شده</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ $selectedWorkers->count() }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-slate-500">مجموع تخصیص فعلی</p>
                                    <p class="mt-1 text-sm font-black text-slate-800">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                                </div>
                                <div class="rounded-2xl border border-orange-200 bg-orange-50 px-3 py-3">
                                    <p class="text-[11px] font-semibold text-orange-700">قابل تخصیص</p>
                                    <p class="mt-1 text-sm font-black text-orange-800">{{ number_format($this->remainingAssignableQuantity, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                @forelse($selectedWorkers as $worker)
                                    @php
                                        $allocated = (float) ($this->allocations[$worker->id] ?? 0);
                                        $delivered = $this->selectedService->deliveredQuantityForWorker($worker->id);
                                        $remaining = max(0, $allocated - $delivered);
                                    @endphp

                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-3 shadow-sm">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-black text-slate-800">{{ $worker->full_name }}</p>
                                                        <p class="mt-0.5 text-[11px] font-semibold text-slate-500">کد مددکار: {{ $worker->worker_code }}</p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        wire:click="removeSocialWorker({{ $worker->id }})"
                                                        class="shrink-0 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 transition hover:bg-rose-100"
                                                    >
                                                        حذف
                                                    </button>
                                                </div>

                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700">
                                                        تخصیص: {{ number_format($allocated, 2) }}
                                                    </span>
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-teal-700">
                                                        تحویل: {{ number_format($delivered, 2) }}
                                                    </span>
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-cyan-700">
                                                        باقی مانده: {{ number_format($remaining, 2) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="w-full lg:w-[170px]">
                                                <label class="mb-1 block text-xs font-bold text-slate-700">مقدار سهمیه</label>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    wire:model.live.debounce.200ms="allocations.{{ $worker->id }}"
                                                    class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-center text-sm font-bold text-slate-800 shadow-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                                    placeholder="0"
                                                >
                                                @error('allocations.' . $worker->id)
                                                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                        هنوز مددکاری انتخاب نشده است. از دکمه <span class="font-bold text-cyan-700">+ Add Social Worker</span> استفاده کنید.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    @unless($this->showSavedSummary)
                        <div class="sticky bottom-3 z-10 rounded-3xl border border-cyan-200 bg-cyan-50/95 px-4 py-4 shadow-cyan-100 backdrop-blur sm:static sm:px-5 sm:shadow-none">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="grid gap-2 sm:grid-cols-3 lg:flex-1">
                                    <div class="rounded-2xl bg-white/90 px-3 py-2.5">
                                        <p class="text-[11px] font-semibold text-slate-500">وضعیت ثبت</p>
                                        <p class="mt-1 text-sm font-bold text-cyan-800">آماده ذخیره سهمیه</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/90 px-3 py-2.5">
                                        <p class="text-[11px] font-semibold text-slate-500">تخصیص فعلی</p>
                                        <p class="mt-1 text-sm font-black text-slate-800">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/90 px-3 py-2.5">
                                        <p class="text-[11px] font-semibold text-slate-500">باقی‌مانده</p>
                                        <p class="mt-1 text-sm font-black text-cyan-700">{{ number_format($this->remainingAssignableQuantity, 2) }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <p class="text-xs font-medium text-cyan-800 sm:max-w-[220px]">
                                        مجموع سهمیه‌ها باید کمتر یا مساوی تعداد کل خدمت باشد.
                                    </p>
                                    <button type="submit" wire:loading.attr="disabled" class="rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-70">
                                        ذخیره سهمیه‌ها
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endunless
                </form>
            @endif
        </div>
    </div>

    <div
        x-show="showQuotaSavedModal"
        x-transition.opacity.duration.200ms
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/25 px-4"
        style="display: none;"
    >
        <div
            @click.outside="showQuotaSavedModal = false"
            x-transition.scale.duration.200ms
            class="w-full max-w-sm rounded-[28px] border border-emerald-100 bg-white p-5 shadow-2xl shadow-emerald-100/60"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="relative flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
                        <span class="absolute inset-0 rounded-full border-4 border-emerald-100 animate-ping"></span>
                        <span class="relative inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500 text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    </div>
                    <div>
                        <p class="text-base font-black text-slate-800">عملیات موفق</p>
                        <p class="mt-1 text-xs text-slate-500">عملیات ذخیرۀ سهمیه انجام شد</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="showQuotaSavedModal = false"
                    class="rounded-full border border-slate-200 p-2 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                    aria-label="Close success modal"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <button
                type="button"
                @click="showQuotaSavedModal = false"
                class="mt-5 w-full rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-600"
            >
                بستن
            </button>
        </div>
    </div>
</div>
