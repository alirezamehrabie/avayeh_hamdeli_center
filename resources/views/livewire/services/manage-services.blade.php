<div class="space-y-6">
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
                                    <label class="mb-2 block text-sm font-bold text-slate-700">نام خدمت</label>
                                    <select wire:model.live="selectedServiceNameId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                        <option value="">انتخاب نام خدمت</option>
                                        @foreach($serviceNames as $serviceName)
                                            <option value="{{ $serviceName->id }}" @selected((string) $selectedServiceNameId === (string) $serviceName->id)>{{ $serviceName->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedServiceNameId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">دسته‌بندی خدمت</label>
                                    <select
                                        wire:model.live="selectedServiceCategoryId"
                                        wire:key="service-category-{{ $selectedServiceNameId ?? 'none' }}"
                                        @disabled(blank($selectedServiceNameId) || $serviceCategories->isEmpty())
                                        wire:loading.attr="disabled"
                                        wire:target="selectedServiceNameId"
                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                    >
                                        <option value="">
                                            @if(blank($selectedServiceNameId))
                                                ابتدا نام خدمت را انتخاب کنید
                                            @elseif($serviceCategories->isEmpty())
                                                دسته‌بندی برای این خدمت تعریف نشده است
                                            @else
                                                انتخاب دسته‌بندی
                                            @endif
                                        </option>
                                        @foreach($serviceCategories as $serviceCategory)
                                            <option value="{{ $serviceCategory->id }}">{{ $serviceCategory->name }}</option>
                                        @endforeach
                                    </select>
                                    <p wire:loading wire:target="selectedServiceNameId" class="mt-1 text-xs text-slate-500">در حال بارگذاری دسته‌بندی‌ها...</p>
                                    @error('selectedServiceCategoryId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
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
                                    <p class="text-xs font-semibold text-slate-500">تعداد تحویل‌شده توسط مددکاران</p>
                                    <p class="mt-2 text-lg font-black text-slate-800">{{ $editingServiceId ? number_format($this->deliveredQuantity, 2) : '0' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">این مقدار از روی تحویل‌های ثبت‌شده محاسبه می‌شود.</p>
                                </div>

                                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-cyan-700">تعداد باقی‌مانده</p>
                                    <p class="mt-2 text-lg font-black text-cyan-900">{{ number_format($this->remainingQuantity, 2) }}</p>
                                </div>

                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-emerald-700">درصد پیشرفت</p>
                                            <p class="mt-2 text-lg font-black text-emerald-900">{{ number_format($this->progressPercentage, 2) }}%</p>
                                        </div>
                                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-emerald-100">
                                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $this->progressPercentage }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-500">ایجادکننده</p>
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

</div>
