<div class="space-y-4">
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-bold">لطفا خطاهای فرم را بررسی کنید.</p>
            <ul class="mt-2 list-disc space-y-1 pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/70 p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">{{ $isEditing ? 'ویرایش خدمت متفرقه' : 'تخصیص خدمت و ایجاد خدمت متفرقه' }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        برای خدمات آماده، فقط از موجودی تأییدشده به مددکار تخصیص دهید. برای موارد خارج از فهرست، یک خدمت متفرقه جدید ایجاد و همان‌جا تخصیص داده می‌شود.
                    </p>
                </div>

                @if($isEditing)
                    <button
                        type="button"
                        wire:click="cancelEditing"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        انصراف
                    </button>
                @endif
            </div>
        </div>

        @if(!$isEditing)
            <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                <label class="group relative flex cursor-pointer gap-3 rounded-2xl border p-4 transition focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 {{ $mode === 'predefined' ? 'border-cyan-500 bg-cyan-50 shadow-sm ring-1 ring-cyan-200' : 'border-slate-200 bg-white hover:border-cyan-300 hover:bg-cyan-50/40' }}">
                    <input type="radio" value="predefined" wire:model.live="mode" class="sr-only">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $mode === 'predefined' ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-cyan-100 group-hover:text-cyan-700' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-black text-slate-900">تخصیص خدمت موجود</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">انتخاب خدمت تأییدشده و تخصیص مقدار از موجودی دسته‌بندی‌های آن.</span>
                    </span>
                    <span class="absolute left-4 top-4 h-3 w-3 rounded-full {{ $mode === 'predefined' ? 'bg-cyan-600' : 'bg-slate-200' }}"></span>
                </label>

                <label class="group relative flex cursor-pointer gap-3 rounded-2xl border p-4 transition focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2 {{ $mode === 'misc' ? 'border-emerald-500 bg-emerald-50 shadow-sm ring-1 ring-emerald-200' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/40' }}">
                    <input type="radio" value="misc" wire:model.live="mode" class="sr-only">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $mode === 'misc' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-emerald-100 group-hover:text-emerald-700' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-black text-slate-900">ایجاد خدمت متفرقه</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">ثبت {{ $nextMiscName }} برای موارد خارج از فهرست و تخصیص آن به مددکار.</span>
                    </span>
                    <span class="absolute left-4 top-4 h-3 w-3 rounded-full {{ $mode === 'misc' ? 'bg-emerald-600' : 'bg-slate-200' }}"></span>
                </label>
            </div>
        @endif
    </div>

    <form wire:submit.prevent="saveBatch" class="space-y-4">
        @if($mode === 'predefined' && !$isEditing)
            <section class="overflow-visible rounded-2xl border border-cyan-100 bg-white shadow-sm">
                <div class="border-b border-cyan-100 bg-cyan-50/60 px-4 py-3 sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-white">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7.5h16M7 12h10M9 16.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-black text-slate-900">تخصیص خدمت موجود</h2>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">ابتدا خدمت و مددکار را انتخاب کنید، سپس مقدار هر دسته‌بندی را وارد کنید.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">خدمت موجود برای تخصیص</label>
                        <select
                            wire:model.live="selectedServiceId"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                        >
                            <option value="">یک خدمت تأییدشده را انتخاب کنید</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->code }} - {{ $service->name ?: ($service->serviceName?->name ?? 'بدون عنوان') }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedServiceId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="relative">
                        <label class="mb-2 block text-sm font-bold text-slate-700">مددکار</label>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="socialWorkerQuery"
                            wire:focus="$set('showSocialWorkerSuggestions', true)"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="نام یا کد مددکار"
                            autocomplete="off"
                        >
                        @if($socialWorkerQuery !== '')
                            <button type="button" wire:click="clearSocialWorkerSelection" class="absolute left-3 top-10 text-slate-400 hover:text-rose-600">×</button>
                        @endif
                        @error('socialWorkerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                        @if($showSocialWorkerSuggestions && mb_strlen(trim($socialWorkerQuery)) >= 2)
                            <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg">
                                @forelse($socialWorkerSuggestions as $worker)
                                    <button
                                        type="button"
                                        wire:click="selectSocialWorker({{ $worker->id }})"
                                        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right text-sm text-slate-700 transition hover:bg-cyan-50"
                                    >
                                        <span class="font-semibold">{{ $worker->full_name }}</span>
                                        <span class="text-xs text-slate-500">کد {{ $worker->worker_code }}</span>
                                    </button>
                                @empty
                                    <div class="px-4 py-3 text-sm text-slate-500">مددکاری یافت نشد.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                @if($selectedService)
                    <div class="mx-4 mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:mx-5 sm:mb-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-base font-black text-slate-900">{{ $selectedService->name ?: ($selectedService->serviceName?->name ?? 'خدمت انتخاب‌شده') }}</h2>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $selectedService->code }} - موجودی کل: {{ number_format((float) $selectedService->total_quantity, 2) }}</p>
                            </div>
                            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800">فقط تخصیص موجودی</span>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($selectedServiceCategories as $category)
                                @php
                                    $allocatedPreview = $this->predefinedAllocationForCategory((int) $category->id);
                                    $assignableQuantity = $this->predefinedAssignableForCategory((int) $category->id);
                                    $remainingPreview = $this->predefinedRemainingForCategory((int) $category->id);
                                    $isOverAllocated = $allocatedPreview > $assignableQuantity;
                                @endphp
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900">{{ $category->name }}</p>
                                            <p class="mt-1 text-xs font-bold text-slate-500">موجودی: {{ number_format((float) $category->quantity, 2) }}</p>
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                            {{ $unitOptions[$category->unit] ?? $category->unit }}
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        max="{{ $assignableQuantity }}"
                                        wire:model.live.debounce.250ms="predefinedAllocations.{{ $category->id }}"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-sm font-black text-slate-900"
                                        placeholder="0"
                                    >
                                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl {{ $isOverAllocated ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }} px-3 py-2">
                                        <span class="text-xs font-bold">مانده پس از تخصیص</span>
                                        <span class="text-sm font-black">{{ number_format($remainingPreview, 2) }}</span>
                                    </div>
                                    @error('predefinedAllocations.' . $category->id) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @else
            <section class="overflow-visible rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50/60 px-4 py-3 sm:px-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-base font-black text-slate-900">{{ $isEditing ? 'ویرایش خدمت متفرقه' : 'ایجاد ' . $nextMiscName }}</h2>
                                <p class="mt-0.5 text-xs leading-5 text-slate-500">دسته‌بندی‌ها را وارد کنید؛ مجموع آن‌ها موجودی اولیه و مقدار تخصیص به مددکار می‌شود.</p>
                            </div>
                        </div>
                        <span class="self-start rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 sm:self-auto">ایجاد و تخصیص</span>
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت</label>
                        <select wire:model="miscServiceType" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <label class="mb-2 block text-sm font-bold text-slate-700">مددکار</label>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="socialWorkerQuery"
                            wire:focus="$set('showSocialWorkerSuggestions', true)"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="نام یا کد مددکار"
                            autocomplete="off"
                        >
                        @if($showSocialWorkerSuggestions && mb_strlen(trim($socialWorkerQuery)) >= 2)
                            <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg">
                                @forelse($socialWorkerSuggestions as $worker)
                                    <button type="button" wire:click="selectSocialWorker({{ $worker->id }})" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right text-sm text-slate-700 transition hover:bg-emerald-50">
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

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" min="1" max="31" wire:model.blur="dateDay" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="روز">
                            <input type="number" min="1" max="12" wire:model.blur="dateMonth" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="ماه">
                            <input type="number" min="1300" max="1600" wire:model.blur="dateYear" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="سال">
                        </div>
                    </div>

                    <div class="sm:col-span-2 xl:col-span-4">
                        <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
                        <textarea rows="3" wire:model.blur="miscDescription" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                    </div>
                </div>

                <div class="space-y-3 px-4 pb-4 sm:px-5">
                    @foreach($miscCategories as $index => $category)
                        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[minmax(0,1fr)_160px_160px] xl:grid-cols-[minmax(0,1fr)_160px_160px_auto]">
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600">نام دسته‌بندی</label>
                                <input type="text" wire:model.blur="miscCategories.{{ $index }}.name" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="مثال: بسته غذایی">
                                @error("miscCategories.$index.name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600">مقدار</label>
                                <input type="number" min="0.01" step="0.01" wire:model.blur="miscCategories.{{ $index }}.quantity" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="0">
                                @error("miscCategories.$index.quantity") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600">واحد</label>
                                <select wire:model="miscCategories.{{ $index }}.unit" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                                    @foreach($unitOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end md:col-span-2 xl:col-span-1">
                                <button type="button" wire:click="removeCategory({{ $index }})" class="w-full rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-bold text-rose-700 transition hover:bg-rose-50 xl:w-auto">
                                    حذف
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-4 pb-5 sm:px-5">
                    <button
                        type="button"
                        wire:click="addCategory"
                        class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 sm:w-auto"
                    >
                        + افزودن دسته‌بندی
                    </button>
                </div>
            </section>
        @endif

        <div class="flex justify-end">
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800 sm:w-auto"
            >
                {{ $mode === 'predefined' && !$isEditing ? 'ثبت تخصیص خدمت موجود' : 'ثبت و تخصیص خدمت متفرقه' }}
            </button>
        </div>
    </form>
</div>
