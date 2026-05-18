<div x-data="{ detailsOpen: false, details: null }" class="space-y-6">
    @php
        $badgeClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'approved' => 'bg-emerald-100 text-emerald-700',
            'in_distribution' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-sky-100 text-sky-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-teal-600 via-cyan-600 to-sky-700 px-6 py-6 text-white">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
{{--                    <p class="text-sm font-semibold text-cyan-100">بخش مدیریت</p>--}}
                    <h1 class="mt-2 text-2xl font-extrabold">تعریف و مدیریت خدمات</h1>
                    <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                        خدمت جدید را با جزئیات لازم تعریف کرده و به مددکاران تخصیص دهید.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs text-cyan-100">شناسه خدمت</p>
                        <p class="mt-1 text-lg font-bold tracking-wide">{{ $this->previewServiceCode }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs text-cyan-100">ارزش کل</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($this->totalServiceValue) }} ریال</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs text-cyan-100">پیشرفت</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($this->progressPercentage, 2) }}%</p>
                    </div>
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
                        <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">اطلاعات پایه خدمت</h2>
                                    <p class="text-sm text-slate-500">شناسه، نام، دسته‌بندی، نوع و شرح خدمت</p>
                                </div>
                                @if($editingServiceId)
                                    <button type="button" wire:click="startNewService" class="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400">
                                        خدمت جدید
                                    </button>
                                @endif
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">شناسه خدمت</label>
                                    <input type="text" value="{{ $this->previewServiceCode }}" disabled class="w-full rounded-2xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
                                    <select wire:model="serviceType" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        @foreach($typeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <div class="mb-2 flex items-center justify-between">
                                        <label class="block text-sm font-bold text-slate-700">نام خدمت</label>
                                        @if($serviceNameMode === 'existing')
                                            <button type="button" wire:click="useNewServiceName" class="text-sm font-semibold text-cyan-700">+ افزودن نام جدید</button>
                                        @else
                                            <button type="button" wire:click="useExistingServiceName" class="text-sm font-semibold text-slate-500">بازگشت به فهرست</button>
                                        @endif
                                    </div>

                                    @if($serviceNameMode === 'existing')
                                        <select wire:model="selectedServiceNameId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                            <option value="">انتخاب نام خدمت</option>
                                            @foreach($serviceNames as $serviceName)
                                                <option value="{{ $serviceName->id }}">{{ $serviceName->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('selectedServiceNameId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    @else
                                        <input type="text" wire:model.blur="newServiceName" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="مثال: بسته ارزاق ماهانه">
                                        @error('newServiceName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    @endif
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between">
                                        <label class="block text-sm font-bold text-slate-700">دسته‌بندی خدمت</label>
                                        @if($serviceCategoryMode === 'existing')
                                            <button type="button" wire:click="useNewServiceCategory" class="text-sm font-semibold text-cyan-700">+ افزودن دسته‌بندی</button>
                                        @else
                                            <button type="button" wire:click="useExistingServiceCategory" class="text-sm font-semibold text-slate-500">بازگشت به فهرست</button>
                                        @endif
                                    </div>

                                    @if($serviceCategoryMode === 'existing')
                                        <select wire:model="selectedServiceCategoryId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                            <option value="">انتخاب دسته‌بندی</option>
                                            @foreach($serviceCategories as $serviceCategory)
                                                <option value="{{ $serviceCategory->id }}">{{ $serviceCategory->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('selectedServiceCategoryId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    @else
                                        <input type="text" wire:model.blur="newServiceCategory" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="مثال: Livelihood">
                                        @error('newServiceCategory') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات خدمت</label>
                                <textarea wire:model.blur="description" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="جزئیات خدمت، هدف و نکات اجرایی را وارد کنید."></textarea>
                                @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <h2 class="text-lg font-bold text-slate-800">مقدار و ارزش مالی</h2>
                            <p class="mt-1 text-sm text-slate-500">فقط چهار فیلد اصلی برای ثبت مقدار و ارزش خدمت</p>

                            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-[1.1fr_0.9fr]">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">تعداد کل</label>
                                    <input type="number" min="0.01" step="0.01" wire:model.live="totalQuantity" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="0">
                                    @error('totalQuantity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">واحد خدمت</label>
                                        <select wire:model="serviceUnit" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                            @foreach($unitOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('serviceUnit') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-2 block text-sm font-bold text-slate-700">ارزش هر واحد (ریال)</label>
                                        <input type="number" step="1"  min="0" wire:model.live="valuePerUnit" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="0">
                                        @error('valuePerUnit') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-cyan-100 bg-slate-50 p-4">
                                    <p class="text-xs font-semibold text-slate-500">خلاصه مالی خدمت</p>
                                    <div class="mt-3 space-y-3">
                                        <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-3">
                                            <span class="text-sm font-medium text-slate-500">تعداد x ارزش واحد</span>
                                            <span class="text-sm font-black text-slate-800">{{ number_format($this->normalizeDecimal($totalQuantity ?? 0), 2) }} × {{ number_format((int) ($valuePerUnit ?: 0)) }}</span>
                                        </div>
                                        <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-4 text-center">
                                            <p class="text-xs font-semibold text-cyan-700">ارزش کل خدمت</p>
                                            <p class="mt-2 text-xl font-black text-cyan-900">{{ number_format($this->totalServiceValue) }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs text-slate-500">ارزش کل به صورت خودکار از روی مقدار و ارزش هر واحد محاسبه می‌شود.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">برنامه توزیع و وضعیت خدمت</h2>
                                    <p class="mt-1 text-sm text-slate-500">زمان‌بندی، منطقه و وضعیت نهایی خدمت در یک چیدمان فشرده</p>
                                </div>
                                <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                    {{ $statusOptions[$status] ?? 'وضعیت' }}
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 lg:grid-cols-[1.05fr_0.95fr]">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">منطقه خدمت</label>
                                        <select wire:model="serviceDistrictId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                            <option value="">بدون منطقه مشخص</option>
                                            @foreach($districts as $district)
                                                <option value="{{ $district->id }}">{{ $district->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('serviceDistrictId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">اولویت</label>
                                        <select wire:model="priority" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                            <option value="">بدون اولویت</option>
                                            @foreach($priorityOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('priority') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">شروع توزیع</label>
                                        <input type="date" wire:model="distributionStartDate" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        @error('distributionStartDate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">پایان توزیع</label>
                                        <input type="date" wire:model="distributionEndDate" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        @error('distributionEndDate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid gap-3">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-slate-700">وضعیت خدمت</label>
                                        <select wire:model="status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('status') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                        <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت وضعیت</label>
                                        <textarea wire:model.blur="statusNotes" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="مثلاً خدمت آماده توزیع در بازه مشخص"></textarea>
                                        @error('statusNotes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <h2 class="text-lg font-bold text-slate-800">شاخص‌های محاسباتی</h2>
                            <p class="mt-1 text-sm text-slate-500">این مقادیر بر پایه تعریف خدمت و فرآیند توزیع محاسبه می‌شوند.</p>

                            <div class="mt-4 grid gap-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-500">14. تعداد تحویل‌شده توسط مددکاران</p>
                                    <p class="mt-2 text-lg font-black text-slate-800">{{ $editingServiceId ? number_format($this->deliveredQuantity, 2) : '0' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">این مقدار از روی تحویل‌های ثبت‌شده محاسبه می‌شود.</p>
                                </div>

                                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-cyan-700">15. تعداد باقی‌مانده</p>
                                    <p class="mt-2 text-lg font-black text-cyan-900">{{ number_format($this->remainingQuantity, 2) }}</p>
                                </div>

                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-emerald-700">16. درصد پیشرفت</p>
                                            <p class="mt-2 text-lg font-black text-emerald-900">{{ number_format($this->progressPercentage, 2) }}%</p>
                                        </div>
                                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-emerald-100">
                                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $this->progressPercentage }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-500">17. ایجادکننده</p>
                                    <p class="mt-2 text-sm font-bold text-slate-800">{{ auth()->user()->full_name ?: auth()->user()->name }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            @if($editingServiceId)
                                <button type="button" wire:click="startNewService" class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-400">
                                    انصراف از ویرایش
                                </button>
                            @endif
                            <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800">
                                {{ $editingServiceId ? 'به‌روزرسانی خدمت' : 'ثبت خدمت جدید' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-800">فهرست خدمات تعریف‌شده</h2>
                <p class="text-sm text-slate-500">نمایی جمع‌وجور از خدمات تعریف‌شده با جزئیات کامل در پنجره جداگانه</p>
            </div>
            <div class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">
                {{ $services->count() }} خدمت ثبت شده
            </div>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($services as $service)
                <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 transition hover:border-cyan-200 hover:bg-cyan-50/40">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500">شناسه</p>
                            <p class="mt-1 text-sm font-black text-slate-800">{{ $service->service_code }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $badgeClasses[$service->status] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ $statusOptions[$service->status] ?? $service->status }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">نام خدمت</p>
                            <p class="mt-1 text-base font-black text-slate-800">{{ $service->serviceName?->name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500">دسته‌بندی</p>
                            <p class="mt-1 text-sm font-bold text-slate-700">{{ $service->serviceCategory?->name ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white px-3 py-3">
                            <p class="text-[11px] font-semibold text-slate-500">ارزش کل</p>
                            <p class="mt-1 text-sm font-black text-slate-800">{{ number_format($service->total_service_value) }} ریال</p>
                        </div>
                        <div class="rounded-2xl bg-white px-3 py-3">
                            <p class="text-[11px] font-semibold text-slate-500">تعداد مددکار</p>
                            <p class="mt-1 text-sm font-black text-slate-800">{{ $service->socialWorkers->count() }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="editService({{ $service->id }})"
                            class="flex-1 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-cyan-400 hover:text-cyan-700"
                        >
                            ویرایش
                        </button>
                        <button
                            type="button"
                            @click="details = @js([
                                'code' => $service->service_code,
                                'name' => $service->serviceName?->name ?: '-',
                                'category' => $service->serviceCategory?->name ?: '-',
                                'type' => $typeOptions[$service->service_type] ?? $service->service_type,
                                'status' => $statusOptions[$service->status] ?? $service->status,
                                'priority' => $service->priority ? ($priorityOptions[$service->priority] ?? $service->priority) : 'بدون اولویت',
                                'quantity' => number_format((float) $service->total_quantity, 2) . ' ' . ($unitOptions[$service->service_unit] ?? $service->service_unit),
                                'delivered' => number_format((float) $service->quantity_delivered, 2),
                                'remaining' => number_format($service->remaining_quantity, 2),
                                'value' => number_format($service->total_service_value) . ' ریال',
                                'district' => $service->district?->name ?: 'بدون منطقه',
                                'start' => optional($service->distribution_start_date)->format('Y-m-d') ?: '-',
                                'end' => optional($service->distribution_end_date)->format('Y-m-d') ?: '-',
                                'creator' => $service->creator?->full_name ?: $service->creator?->name ?: '-',
                                'description' => $service->description ?: 'توضیحی ثبت نشده است.',
                                'status_notes' => $service->status_notes ?: 'یادداشتی ثبت نشده است.',
                                'workers_count' => $service->socialWorkers->count(),
                            ]); detailsOpen = true"
                            class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-bold text-cyan-700 transition hover:bg-cyan-100"
                        >
                            جزئیات
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-slate-500">
                    هنوز خدمتی تعریف نشده است.
                </div>
            @endforelse
        </div>
    </div>

    <div
        x-show="detailsOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 px-4"
        style="display: none;"
    >
        <div @click.outside="detailsOpen = false" class="w-full max-w-2xl rounded-[30px] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-lg font-black text-slate-800">جزئیات خدمت</h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="details?.code"></p>
                </div>
                <button type="button" @click="detailsOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto px-5 py-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">نام</p><p class="mt-1 font-bold text-slate-800" x-text="details?.name"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">دسته‌بندی</p><p class="mt-1 font-bold text-slate-800" x-text="details?.category"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">نوع</p><p class="mt-1 font-bold text-slate-800" x-text="details?.type"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">وضعیت / اولویت</p><p class="mt-1 font-bold text-slate-800"><span x-text="details?.status"></span> - <span x-text="details?.priority"></span></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">مقدار کل</p><p class="mt-1 font-bold text-slate-800" x-text="details?.quantity"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">ارزش کل</p><p class="mt-1 font-bold text-slate-800" x-text="details?.value"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">تحویل / باقی‌مانده</p><p class="mt-1 font-bold text-slate-800"><span x-text="details?.delivered"></span> / <span x-text="details?.remaining"></span></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">تعداد مددکار</p><p class="mt-1 font-bold text-slate-800" x-text="details?.workers_count"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">منطقه</p><p class="mt-1 font-bold text-slate-800" x-text="details?.district"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">شروع / پایان</p><p class="mt-1 font-bold text-slate-800"><span x-text="details?.start"></span> - <span x-text="details?.end"></span></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2"><p class="text-xs text-slate-500">توضیحات خدمت</p><p class="mt-1 font-bold text-slate-800" x-text="details?.description"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2"><p class="text-xs text-slate-500">یادداشت وضعیت</p><p class="mt-1 font-bold text-slate-800" x-text="details?.status_notes"></p></div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2"><p class="text-xs text-slate-500">ایجادکننده</p><p class="mt-1 font-bold text-slate-800" x-text="details?.creator"></p></div>
                </div>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                <button type="button" @click="detailsOpen = false" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>
