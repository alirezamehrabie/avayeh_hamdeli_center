<div class="space-y-4">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-indigo-700 via-sky-700 to-cyan-700 px-5 py-4 text-white">
            <h1 class="text-xl font-extrabold">مدیریت خدمات</h1>
            <p class="mt-1.5 text-xs text-sky-50/90">در این بخش نام خدمت، دسته‌بندی‌ها و واحدهای قابل استفاده را تعریف و ویرایش کنید.</p>
        </div>

        <div class="space-y-4 px-4 py-4">
            @if (session()->has('management-success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    {{ session('management-success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                    <p class="font-bold">لطفاً خطاهای فرم را بررسی کنید.</p>
                </div>
            @endif

            <div class="grid gap-4 xl:grid-cols-3">
                <section class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <div class="mb-3 flex items-center justify-between gap-2.5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">نام خدمت</h2>
                            <p class="text-xs text-slate-500">ثبت و ویرایش سرویس‌های اصلی</p>
                        </div>
                        @if($editingServiceNameId)
                            <button type="button" wire:click="resetServiceNameForm" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <div class="mb-2.5">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                            <input
                                type="text"
                                wire:model.live.debounce.200ms="serviceNameSearch"
                                placeholder="نام خدمت را جستجو کنید ..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-100/80 py-2.5 pl-10 pr-3.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                            >
                        </div>
                    </div>

                    <form wire:submit.prevent="saveServiceName" class="space-y-2.5">
                        <input type="text" wire:model.blur="serviceName" placeholder="مثال سفرۀ ام‌البنین (س)" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('serviceName') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-xl bg-indigo-700 px-3.5 py-2.5 text-sm font-bold text-white">
                            {{ $editingServiceNameId ? 'به‌روزرسانی نام خدمت' : 'ثبت نام خدمت' }}
                        </button>
                    </form>

                    <div
                        x-data="{
                            showFade: false,
                            updateFade() {
                                const el = this.$refs.scroller;
                                this.showFade = !!el && (el.scrollHeight - el.clientHeight - el.scrollTop > 8);
                            }
                        }"
                        x-init="$nextTick(() => updateFade())"
                        @resize.window="updateFade()"
                        class="relative mt-5"
                    >
                        <div
                            x-ref="scroller"
                            @scroll="updateFade()"
                            class="max-h-[22rem] space-y-1.5 overflow-y-auto scroll-smooth pb-12 pr-1"
                        >
                            @foreach($serviceNames as $item)
                                <button type="button" wire:click="editServiceName({{ $item->id }})" class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-right transition hover:border-indigo-300">
                                    <span class="text-sm font-semibold text-slate-800">{{ $item->name }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $item->categories_count }} دسته</span>
                                </button>
                            @endforeach
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-slate-50/95 via-slate-50/80 to-slate-50/0"
                        ></div>
                    </div>
                </section>

                <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-2.5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">دسته‌بندی خدمت</h2>
                            <p class="text-xs text-slate-500">برای هر نام خدمت، دسته‌بندی‌های وابسته تعریف کنید</p>
                        </div>
                        @if($editingCategoryId)
                            <button type="button" wire:click="resetCategoryForm" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <form wire:submit.prevent="saveCategory" class="space-y-2.5">
                        <select wire:model.live="selectedServiceNameId" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                            <option value="">انتخاب نام خدمت</option>
                            @foreach($serviceNames as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedServiceNameId') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <input type="text" wire:model.blur="categoryName" placeholder="مثلاً غذای گرم" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('categoryName') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-xl bg-sky-700 px-3.5 py-2.5 text-sm font-bold text-white">
                            {{ $editingCategoryId ? 'به‌روزرسانی دسته‌بندی' : 'ثبت دسته‌بندی' }}
                        </button>
                    </form>

                    <div
                        x-data="{
                            showFade: false,
                            updateFade() {
                                const el = this.$refs.scroller;
                                this.showFade = !!el && (el.scrollHeight - el.clientHeight - el.scrollTop > 8);
                            }
                        }"
                        x-init="$nextTick(() => updateFade())"
                        @resize.window="updateFade()"
                        class="relative mt-5"
                    >
                        <div
                            x-ref="scroller"
                            @scroll="updateFade()"
                            class="max-h-[22rem] space-y-1.5 overflow-y-auto scroll-smooth pb-12 pr-1"
                        >
                            @forelse($serviceCategories as $item)
                                <button type="button" wire:click="editCategory({{ $item->id }})" class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-right transition hover:border-sky-300">
                                    <span class="text-sm font-semibold text-slate-800">{{ $item->name }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $item->serviceName?->name }}</span>
                                </button>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 px-3.5 py-5 text-center text-xs text-slate-500">برای این خدمت هنوز دسته‌بندی ثبت نشده است.</div>
                            @endforelse
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-white via-white/85 to-white/0"
                        ></div>
                    </div>
                </section>

                <section class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <div class="mb-3 flex items-center justify-between gap-2.5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">واحد خدمت</h2>
                            <p class="text-xs text-slate-500">واحدهای قابل انتخاب برای خدمات را مدیریت کنید</p>
                        </div>
                        @if($editingUnitId)
                            <button type="button" wire:click="resetUnitForm" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <form wire:submit.prevent="saveUnit" class="space-y-2.5">
                        <input type="text" wire:model.blur="unitLabel" placeholder="مثلاً کیلوگرم" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('unitLabel') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <input type="text" wire:model.blur="unitKey" placeholder="کلید اختیاری، مثل kilogram" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                        @error('unitKey') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-xl bg-cyan-700 px-3.5 py-2.5 text-sm font-bold text-white">
                            {{ $editingUnitId ? 'به‌روزرسانی واحد' : 'ثبت واحد' }}
                        </button>
                    </form>

                    <div
                        x-data="{
                            showFade: false,
                            updateFade() {
                                const el = this.$refs.scroller;
                                this.showFade = !!el && (el.scrollHeight - el.clientHeight - el.scrollTop > 8);
                            }
                        }"
                        x-init="$nextTick(() => updateFade())"
                        @resize.window="updateFade()"
                        class="relative mt-5"
                    >
                        <div
                            x-ref="scroller"
                            @scroll="updateFade()"
                            class="max-h-[22rem] space-y-1.5 overflow-y-auto scroll-smooth pb-12 pr-1"
                        >
                            @foreach($serviceUnits as $item)
                                <button type="button" wire:click="editUnit({{ $item->id }})" class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-right transition hover:border-cyan-300">
                                    <span class="text-sm font-semibold text-slate-800">{{ $item->label }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $item->key }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 rounded-b-2xl bg-gradient-to-t from-slate-50/95 via-slate-50/80 to-slate-50/0"
                        ></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
