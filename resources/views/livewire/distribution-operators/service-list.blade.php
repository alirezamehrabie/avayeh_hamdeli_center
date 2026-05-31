<div class="space-y-6">
    <div class="rounded-3xl border border-violet-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-violet-700">فهرست خدمات اپراتور</p>
                <h1 class="mt-2 text-2xl font-black text-slate-800">خدمات ثبت‌شده توسط شما</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">در این بخش فقط خدمات ایجادشده توسط حساب فعلی نمایش داده می‌شوند و ویرایش به فیلدهای مجاز محدود است.</p>
            </div>
            <a href="{{ route('distribution-operator.define-service') }}" class="inline-flex items-center justify-center rounded-2xl bg-violet-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-violet-700">
                تعریف خدمت جدید
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($editingServiceId)
        <form wire:submit.prevent="updateService" class="rounded-3xl border border-violet-100 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-violet-700">ویرایش خدمت</p>
                    <h2 class="mt-1 text-xl font-black text-slate-800">فقط فیلدهای مجاز اپراتور توزیع</h2>
                </div>
                <button type="button" wire:click="cancelEditing" class="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    انصراف
                </button>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">نام خدمت</label>
                    <input type="text" wire:model.blur="serviceName" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="نام خدمت را وارد کنید">
                    @error('serviceName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">دسته‌بندی</label>
                    <select wire:model="selectedServiceCategoryId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                        <option value="">Undefined</option>
                        @foreach($serviceCategories as $serviceCategory)
                            <option value="{{ $serviceCategory->id }}">{{ $serviceCategory->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedServiceCategoryId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">تعداد کل</label>
                    <input type="number" min="0.01" step="0.01" wire:model.blur="totalQuantity" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                    @error('totalQuantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">واحد</label>
                    <select wire:model="serviceUnit" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                        @foreach($unitOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('serviceUnit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
                    <textarea rows="3" wire:model.blur="description" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ (جلالی)</label>
                    <input type="text" wire:model.blur="distributionDate" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="1404/01/01">
                    @error('distributionDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="relative">
                    <label class="mb-2 block text-sm font-bold text-slate-700">مددکار فعال</label>
                    <div class="relative">
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="socialWorkerQuery"
                            wire:focus="activateSocialWorkerSearch"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pl-12 text-sm text-slate-700"
                            placeholder="حداقل 2 کاراکتر وارد کنید"
                            autocomplete="off"
                        >
                        @if($socialWorkerQuery !== '')
                            <button type="button" wire:click="clearSocialWorkerSelection" class="absolute inset-y-0 left-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-rose-600">×</button>
                        @endif
                    </div>
                    @if($showSocialWorkerSuggestions && mb_strlen(trim($socialWorkerQuery)) >= 2)
                        <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg">
                            @forelse($socialWorkerSuggestions as $worker)
                                <button type="button" wire:click="selectSocialWorker({{ $worker->id }})" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right text-sm text-slate-700 transition hover:bg-violet-50">
                                    <span class="font-semibold">{{ $worker->full_name }}</span>
                                    <span class="text-xs text-slate-500">کد {{ $worker->worker_code }}</span>
                                </button>
                            @empty
                                <div class="px-4 py-3 text-sm text-slate-500">مددکاری یافت نشد.</div>
                            @endforelse
                        </div>
                    @endif
                    @error('socialWorkerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white transition hover:bg-slate-800">
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-black text-slate-800">فهرست خدمات</h2>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $services->count() }} خدمت</span>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($services as $service)
                <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-violet-700">{{ $service->service_code }}</p>
                            <h3 class="mt-1 text-base font-black text-slate-800">{{ $service->serviceName?->name ?? '—' }}</h3>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">در حال توزیع</span>
                    </div>

                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p><span class="font-bold text-slate-800">دسته‌بندی:</span> {{ $service->serviceCategory?->name ?? 'Undefined' }}</p>
                        <p><span class="font-bold text-slate-800">تعداد:</span> {{ number_format((float) $service->total_quantity, 2) }} {{ $unitOptions[$service->service_unit] ?? $service->service_unit }}</p>
                        <p><span class="font-bold text-slate-800">مددکار:</span> {{ $service->socialWorkers->first()?->full_name ?? '—' }}</p>
                        <p><span class="font-bold text-slate-800">تاریخ:</span> {{ \App\Helpers\Morilog\Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d') }}</p>
                    </div>

                    <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">{{ $service->description }}</p>

                    <div class="mt-4">
                        <button type="button" wire:click="startEditing({{ $service->id }})" class="inline-flex items-center justify-center rounded-2xl border border-violet-200 bg-white px-4 py-2 text-sm font-bold text-violet-700 transition hover:bg-violet-50">
                            ویرایش خدمت
                        </button>
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
