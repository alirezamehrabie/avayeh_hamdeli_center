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
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-black text-slate-800">فهرست خدمات</h2>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $services->count() }} خدمت</span>
        </div>

        <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
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
                            <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">تخصیص از خدمت</span>
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

                    <div class="mt-auto pt-4">
                        @if($isMisc)
                            <a href="{{ route('distribution-operator.edit-service', $service->id) }}" class="inline-flex items-center justify-center rounded-2xl border border-violet-200 bg-white px-4 py-2 text-sm font-bold text-violet-700 transition hover:bg-violet-50">
                                ویرایش خدمت
                            </a>
                        @elseif($operatorAllocations->isNotEmpty())
                            <a href="{{ route('distribution-operator.edit-allocations', $service->id) }}" class="inline-flex items-center justify-center rounded-2xl border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50">
                                ویرایش تخصیص‌ها
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    هنوز خدمتی توسط این اپراتور ثبت نشده است.
                </div>
            @endforelse
        </div>
    </div>
</div>
