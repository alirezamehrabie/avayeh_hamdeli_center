<div
    x-data="{ saved: false }"
    x-on:quota-saved.window="saved = true; setTimeout(() => saved = false, 2600)"
    class="space-y-6"
>
    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-900 px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-cyan-200">Service Delivery</p>
                    <h1 class="mt-2 text-2xl font-black">تخصیص سهمیه خدمات به مددکاران</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        در این بخش فقط سهمیه مددکاران برای هر آیتم خدمت مشخص می‌شود. تعریف خدمت و مصرف/تحویل نهایی در جریان‌های جداگانه مدیریت می‌شوند.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[360px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-3 py-2">
                        <p class="text-[11px] text-slate-300">خدمات</p>
                        <p class="mt-1 text-base font-black">{{ $services->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-3 py-2">
                        <p class="text-[11px] text-slate-300">مددکار</p>
                        <p class="mt-1 text-base font-black">{{ $selectedWorkers->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-cyan-500/20 px-3 py-2">
                        <p class="text-[11px] text-cyan-100">سهمیه</p>
                        <p class="mt-1 text-base font-black">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-5 sm:px-6">
            <div
                x-show="saved"
                x-transition.opacity.duration.200ms
                x-cloak
                class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"
            >
                سهمیه‌ها با موفقیت ذخیره شدند.
            </div>

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

            <div class="grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
                <aside class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="mb-2 block text-sm font-black text-slate-800">انتخاب خدمت / پویش</label>
                        <select
                            wire:model.live="selectedServiceId"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                        >
                            <option value="">انتخاب کنید</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->code }} - {{ $service->name ?: ($service->serviceName?->name ?? '-') }}
                                </option>
                            @endforeach
                        </select>
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
                                                            class="mb-2 flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right transition hover:border-cyan-300 hover:bg-cyan-50"
                                                        >
                                                            <span class="min-w-0">
                                                                <span class="block truncate text-sm font-black text-slate-800">{{ $socialWorker->full_name }}</span>
                                                                <span class="mt-1 block text-xs font-bold text-slate-500">کد مددکاری: {{ $socialWorker->worker_code }}</span>
                                                            </span>
                                                            <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-cyan-700">افزودن</span>
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
                                                            Quantity allocation for {{ $worker->full_name }} / {{ $category->name }}
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

                            <div class="sticky bottom-3 z-10 rounded-3xl border border-cyan-200 bg-cyan-50/95 p-4 shadow-lg shadow-cyan-100 backdrop-blur sm:static sm:shadow-none">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[540px]">
                                        <div class="rounded-2xl bg-white px-3 py-2">
                                            <p class="text-[11px] font-bold text-slate-500">مددکاران</p>
                                            <p class="mt-1 text-sm font-black text-slate-900">{{ $selectedWorkers->count() }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white px-3 py-2">
                                            <p class="text-[11px] font-bold text-slate-500">تخصیص کل</p>
                                            <p class="mt-1 text-sm font-black text-slate-900">{{ number_format($this->currentAllocatedTotal, 2) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white px-3 py-2">
                                            <p class="text-[11px] font-bold text-cyan-700">باقی قابل تخصیص</p>
                                            <p class="mt-1 text-sm font-black text-cyan-800">{{ number_format($this->remainingAssignableQuantity, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        @if($showSavedSummary)
                                            <button
                                                type="button"
                                                wire:click="enableEditing"
                                                class="rounded-2xl border border-cyan-300 bg-white px-5 py-3 text-sm font-black text-cyan-700 transition hover:bg-cyan-50"
                                            >
                                                ادامه ویرایش
                                            </button>
                                        @endif
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            class="rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-70"
                                        >
                                            ذخیره سهمیه‌ها
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
                                            <h3 class="text-lg font-black text-slate-900">Confirm quota allocation</h3>
                                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                                Review worker and item totals before replacing this service's saved quotas.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="cancelSaveConfirmation"
                                            class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-600 transition hover:bg-slate-50"
                                        >
                                            Close
                                        </button>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <p class="mb-2 text-sm font-black text-slate-800">Worker totals</p>
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
                                            <p class="mb-2 text-sm font-black text-slate-800">Item totals</p>
                                            <div class="max-h-56 space-y-2 overflow-y-auto">
                                                @foreach($this->selectedServiceCategories as $category)
                                                    <div class="rounded-xl bg-white px-3 py-2 text-sm">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="truncate font-bold text-slate-700">{{ $category->name }}</span>
                                                            <span class="font-black text-cyan-700">{{ number_format($this->allocationForCategory((int) $category->id), 2) }}</span>
                                                        </div>
                                                        <p class="mt-1 text-[11px] font-bold text-slate-400">
                                                            Defined capacity: {{ number_format((float) $category->quantity, 2) }}
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
                                            Back to edit
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="confirmSaveAllocations"
                                            wire:loading.attr="disabled"
                                            class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70"
                                        >
                                            Confirm final save
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
