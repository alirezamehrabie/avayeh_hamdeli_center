<div class="space-y-5">
    @if (session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-bold">لطفاً خطاهای فرم را بررسی کنید.</p>
            <ul class="mt-2 list-disc space-y-1 pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl border border-violet-100 bg-violet-50/70 p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-violet-700">ثبت مستقیم خدمات در حال توزیع</p>
                <h2 class="mt-1 text-xl font-black text-slate-800">تعریف خدمت و تخصیص فوری به مددکار</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">هر کارت یک خدمت مستقل است. با دکمه `+` چند خدمت را در یک نوبت ثبت کنید.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    wire:click="addBlock"
                    class="inline-flex h-12 items-center justify-center rounded-2xl bg-violet-600 px-5 text-base font-black text-white shadow-sm transition hover:bg-violet-700"
                >
                    +
                </button>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="saveBatch" class="space-y-4">
        @foreach($serviceBlocks as $index => $block)
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold text-violet-700">بلوک خدمت {{ $loop->iteration }}</p>
                        <h3 class="mt-1 text-base font-black text-slate-800">شناسه: {{ $block['service_id_preview'] }}</h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700">در حال توزیع</span>
                        @if(count($serviceBlocks) > 1)
                            <button
                                type="button"
                                wire:click="removeBlock({{ $index }})"
                                class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
                            >
                                حذف
                            </button>
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">نام خدمت</label>
                        <input
                            type="text"
                            wire:model.blur="serviceBlocks.{{ $index }}.service_name"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="اختیاری"
                        >
                        @error("serviceBlocks.$index.service_name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">دسته‌بندی</label>
                        <select
                            wire:model="serviceBlocks.{{ $index }}.category_id"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                        >
                            <option value="">انتخاب نشده</option>
                            @foreach($categoryOptions as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error("serviceBlocks.$index.category_id") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">مقدار <span class="text-xs fw-light text-gray-500 px-1">(بر اساس واحد)</span> </label>
                        <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            wire:model.blur="serviceBlocks.{{ $index }}.total_quantity"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="مثال: 25"
                        >
                        @error("serviceBlocks.$index.total_quantity") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">واحد</label>
                        <select
                            wire:model="serviceBlocks.{{ $index }}.unit"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                        >
                            @foreach($unitOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error("serviceBlocks.$index.unit") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>


                    <div class="sm:col-span-2 xl:col-span-2">
                        <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
                        <textarea
                            rows="3"
                            wire:model.blur="serviceBlocks.{{ $index }}.description"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="شرح خدمت و توضیحات لازم"
                        ></textarea>
                        @error("serviceBlocks.$index.description") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>


                    <div class="relative sm:col-span-2 xl:col-span-1">
                        <label class="mb-2 block text-sm font-bold text-slate-700">مددکار فعال</label>
                        <div class="relative">
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="serviceBlocks.{{ $index }}.social_worker_query"
                                wire:focus="activateSocialWorkerSearch({{ $index }})"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pl-12 text-sm text-slate-700"
                                placeholder="نام مددکار را تایپ کنید"
                                autocomplete="off"
                            >
                            @if(!empty($block['social_worker_query']))
                                <button
                                    type="button"
                                    wire:click="clearSocialWorkerSelection({{ $index }})"
                                    class="absolute inset-y-0 left-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-rose-600"
                                    aria-label="پاک کردن مددکار"
                                >
                                    ×
                                </button>
                            @endif
                        </div>
                        @if(
                            mb_strlen(trim((string) ($block['social_worker_query'] ?? ''))) >= 2
                            && $activeSocialWorkerSearchIndex === $index
                        )
                            <div class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg">
                                @forelse(($socialWorkerSuggestions[$index] ?? collect()) as $worker)
                                    <button
                                        type="button"
                                        wire:click="selectSocialWorker({{ $index }}, {{ $worker->id }})"
                                        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right text-sm text-slate-700 transition hover:bg-violet-50"
                                    >
                                        <span class="font-semibold">{{ $worker->full_name }}</span>
                                        <span class="text-xs text-slate-500">کد {{ $worker->worker_code }}</span>
                                    </button>
                                @empty
                                    <div class="px-4 py-3 text-sm text-slate-500">مددکاری یافت نشد.</div>
                                @endforelse
                            </div>
                        @endif
                        @error("serviceBlocks.$index.social_worker_id") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ ثبت</label>
                        <input
                            type="text"
                            wire:model.blur="serviceBlocks.{{ $index }}.date"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                            placeholder="1404/01/01"
                        >
                        @error("serviceBlocks.$index.date") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>
        @endforeach

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
            <button
                type="button"
                wire:click="addBlock"
                class="inline-flex items-center justify-center rounded-2xl border border-violet-200 bg-white px-5 py-3 text-sm font-bold text-violet-700 transition hover:bg-violet-50"
            >
                + افزودن بلوک خدمت
            </button>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800"
            >
                ثبت همه خدمات
            </button>
        </div>
    </form>
</div>
