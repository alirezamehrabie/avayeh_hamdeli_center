<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-teal-600 via-cyan-600 to-sky-700 px-6 py-6 text-white">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="mt-2 text-2xl font-extrabold">تعریف و مدیریت خدمات</h1>
                    <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                        یک خدمت والد بسازید و برای آن چند دسته با مقدار، واحد و ارزش مستقل تعریف کنید.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs text-cyan-100">شناسه خدمت</p>
                        <p class="mt-1 text-lg font-bold tracking-wide">{{ $this->previewServiceCode }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs text-cyan-100">تعداد کل</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($this->totalQuantity, 2) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs text-cyan-100">ارزش کل</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($this->totalServiceValue) }} ریال</p>
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
                                    <p class="text-sm text-slate-500">کد، نام، نوع و وضعیت خدمت والد</p>
                                </div>
                                @if($editingServiceId)
                                    <button type="button" wire:click="startNewService" class="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
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

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-bold text-slate-700">نام خدمت</label>
                                <input type="text" wire:model.blur="serviceName" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="مثال: سفره ام البنین">
                                @error('serviceName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات خدمت</label>
                                <textarea wire:model.blur="description" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">دسته‌های خدمت</h2>
                                    <p class="text-sm text-slate-500">برای هر دسته مقدار، واحد و ارزش واحد را ثبت کنید</p>
                                </div>
                                <button type="button" wire:click="addCategory" class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-bold text-cyan-700">
                                    افزودن دسته
                                </button>
                            </div>

                            <div class="space-y-4">
                                @foreach($categories as $index => $category)
                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="mb-3 flex items-center justify-between">
                                            <div class="text-sm font-bold text-slate-700">
                                                {{ $category['code'] ?: 'CAT-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) }}
                                            </div>
                                            @if(count($categories) > 1)
                                                <button type="button" wire:click="removeCategory({{ $index }})" class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">
                                                    حذف
                                                </button>
                                            @endif
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">نام دسته</label>
                                                <input type="text" wire:model.blur="categories.{{ $index }}.name" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="مثال: چلو کباب کوبیده">
                                                @error("categories.$index.name") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">واحد</label>
                                                <select wire:model="categories.{{ $index }}.unit" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                                    @foreach($unitOptions as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error("categories.$index.unit") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">تعداد</label>
                                                <input type="number" min="0.01" step="0.01" wire:model.live="categories.{{ $index }}.quantity" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="0">
                                                @error("categories.$index.quantity") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-slate-700">ارزش واحد</label>
                                                <input type="number" min="0" step="1" wire:model.live="categories.{{ $index }}.value" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="0">
                                                @error("categories.$index.value") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
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
