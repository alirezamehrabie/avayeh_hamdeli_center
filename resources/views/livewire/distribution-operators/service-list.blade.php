<div class="space-y-6">
    <div class="rounded-3xl border border-violet-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="mt-2 text-2xl font-black text-slate-800">خدمات ثبت‌شده <span class="text-violet-400">اپراتور توزیع</span></h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">خدمات ایجادشده (متفرقه) و خدمات تخصیص‌یافته توسط حساب فعلی در این بخش نمایش داده می‌شوند.</p>
            </div>
            <a href="{{ route('distribution-operator.define-service') }}" class="inline-flex items-center justify-center rounded-2xl bg-violet-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-violet-700">
                تخصیص یا ایجاد خدمت متفرقه
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-800">فهرست خدمات</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">خدمات بر اساس حالت ثبت، در دو دسته جدا نمایش داده می‌شوند.</p>
                </div>
                <span wire:loading.class="opacity-60" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $services->count() }} خدمت</span>
            </div>

            <div class="grid gap-2 rounded-2xl bg-slate-100 p-1 sm:grid-cols-2" role="tablist" aria-label="فیلتر نوع خدمات">
                <button
                    type="button"
                    wire:click="switchTab('campaigns')"
                    wire:loading.attr="disabled"
                    role="tab"
                    aria-selected="{{ $activeTab === 'campaigns' ? 'true' : 'false' }}"
                    class="flex min-h-16 items-center justify-between gap-3 rounded-xl px-4 py-3 text-right transition {{ $activeTab === 'campaigns' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-white/70 hover:text-slate-800' }}"
                >
                    <span>
                        <span class="block text-sm font-black">لیست کمپین‌ها</span>
                        <span class="mt-1 block text-xs font-semibold opacity-75">انتخاب از خدمات/کمپین</span>
                    </span>
                    <span class="shrink-0 rounded-full {{ $activeTab === 'campaigns' ? 'bg-blue-100 text-blue-700' : 'bg-white text-slate-500' }} px-3 py-1 text-xs font-black">
                        {{ $counts['campaigns'] ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="switchTab('misc')"
                    wire:loading.attr="disabled"
                    role="tab"
                    aria-selected="{{ $activeTab === 'misc' ? 'true' : 'false' }}"
                    class="flex min-h-16 items-center justify-between gap-3 rounded-xl px-4 py-3 text-right transition {{ $activeTab === 'misc' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-white/70 hover:text-slate-800' }}"
                >
                    <span>
                        <span class="block text-sm font-black">لیست متفرقه</span>
                        <span class="mt-1 block text-xs font-semibold opacity-75">تعریف انتخاب جدید</span>
                    </span>
                    <span class="shrink-0 rounded-full {{ $activeTab === 'misc' ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-500' }} px-3 py-1 text-xs font-black">
                        {{ $counts['misc'] ?? 0 }}
                    </span>
                </button>
            </div>
        </div>

        <div wire:loading.class="opacity-60" wire:target="switchTab" class="grid gap-4 p-4 transition-opacity md:grid-cols-2 xl:grid-cols-3">
            @forelse($services as $service)
                @php
                    $isMisc = (int) ($service->created_by ?? 0) === (int) auth()->id();
                    $operatorAllocations = $service->workerAllocations
                        ->filter(fn ($a) => (int) $a->assigned_by_user_id === (int) auth()->id());
                @endphp

                <div class="flex flex-col rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-violet-700">{{ $service->code }}</p>
                            <h3 class="mt-1 text-base font-black text-slate-800">{{ $service->serviceName?->name ?? '—' }}</h3>
                        </div>
                        @if($isMisc)
                            <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">تعریف جدید</span>
                        @else
                            <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">کمپین</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p><span class="font-bold text-slate-800">دسته‌بندی:</span> {{ $service->serviceCategory?->name ?? 'نامشخص' }}</p>
                        <p><span class="font-bold text-slate-800">تعداد:</span> {{ number_format((float) $service->total_quantity, 2) }} {{ $unitOptions[$service->service_unit] ?? ($service->service_unit ?? '-') }}</p>

                        @if($isMisc)
                            <p><span class="font-bold text-slate-800">مددکار:</span> {{ $service->socialWorkers->first()?->full_name ?? '—' }}</p>
                        @else
                            <div>
                                <span class="font-bold text-slate-800">مددکاران تخصیص‌یافته:</span>
                                <ul class="mt-1 space-y-1">
                                    @forelse($operatorAllocations as $allocation)
                                        <li class="flex items-center gap-2">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400"></span>
                                            <span>{{ $allocation->socialWorker?->full_name ?? '—' }}</span>
                                            <span class="text-xs text-slate-400">({{ number_format((float) $allocation->allocated_quantity, 2) }})</span>
                                        </li>
                                    @empty
                                        <li>—</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endif

                        <p><span class="font-bold text-slate-800">تاریخ:</span> {{ \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d') }}</p>
                    </div>

                    <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">{{ $service->description }}</p>

                    <div class="mt-auto border-t border-slate-200 pt-4">
                        @if($isMisc)
                            <a href="{{ route('distribution-operator.edit-service', $service->id) }}" class="inline-flex items-center justify-center rounded-2xl border border-violet-200 bg-white px-4 py-2 text-sm font-bold text-violet-700 transition hover:bg-violet-50">
                                ویرایش خدمت
                            </a>
                        @elseif($operatorAllocations->isNotEmpty())
                            <a href="{{ route('distribution-operator.edit-allocations', $service->id) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 active:bg-blue-800 sm:w-auto">
                                مدیریت تخصیص‌ها
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    @if($activeTab === 'misc')
                        هنوز خدمت متفرقه‌ای توسط این اپراتور تعریف نشده است.
                    @else
                        هنوز خدمتی از لیست کمپین‌ها به این اپراتور تخصیص داده نشده است.
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>
