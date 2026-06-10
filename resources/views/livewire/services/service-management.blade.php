<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-indigo-700 via-sky-700 to-cyan-700 px-6 py-6 text-white">
            <h1 class="text-2xl font-extrabold">مدیریت خدمات</h1>
            <p class="mt-2 text-sm text-sky-50/90">در این بخش نام خدمت، دسته‌بندی‌ها و واحدهای قابل استفاده را تعریف و ویرایش کنید.</p>
        </div>

        <div class="space-y-6 px-6 py-6">
            @if (session()->has('management-success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('management-success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-bold">لطفاً خطاهای فرم را بررسی کنید.</p>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-3">
                <section class="flex flex-col rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">نام خدمت</h2>
                            <p class="text-sm text-slate-500">ثبت و ویرایش سرویس‌های اصلی</p>
                        </div>
                        @if($editingServiceNameId)
                            <button type="button" wire:click="resetServiceNameForm" class="rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <form wire:submit.prevent="saveServiceName" class="space-y-3">
                        <input type="text" wire:model.blur="serviceName" placeholder="مثلاً ارزاق" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                        @error('serviceName') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-2xl bg-indigo-700 px-4 py-3 text-sm font-bold text-white">
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
                            class="max-h-[28rem] space-y-2 overflow-y-auto scroll-smooth pr-1"
                        >
                            @foreach($serviceNames as $item)
                                <button type="button" wire:click="editServiceName({{ $item->id }})" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right transition hover:border-indigo-300">
                                    <span class="font-semibold text-slate-800">{{ $item->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $item->categories_count }} دسته</span>
                                </button>
                            @endforeach
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-12 rounded-b-3xl bg-gradient-to-t from-slate-50/95 via-slate-50/80 to-slate-50/0"
                        ></div>
                    </div>
                </section>

                <section class="flex flex-col rounded-3xl border border-slate-200 bg-white p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">دسته‌بندی خدمت</h2>
                            <p class="text-sm text-slate-500">برای هر نام خدمت، دسته‌بندی‌های وابسته تعریف کنید</p>
                        </div>
                        @if($editingCategoryId)
                            <button type="button" wire:click="resetCategoryForm" class="rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <form wire:submit.prevent="saveCategory" class="space-y-3">
                        <select wire:model.live="selectedServiceNameId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                            <option value="">انتخاب نام خدمت</option>
                            @foreach($serviceNames as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedServiceNameId') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <input type="text" wire:model.blur="categoryName" placeholder="مثلاً غذای گرم" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                        @error('categoryName') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-2xl bg-sky-700 px-4 py-3 text-sm font-bold text-white">
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
                            class="max-h-[28rem] space-y-2 overflow-y-auto scroll-smooth pr-1"
                        >
                            @forelse($serviceCategories as $item)
                                <button type="button" wire:click="editCategory({{ $item->id }})" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right transition hover:border-sky-300">
                                    <span class="font-semibold text-slate-800">{{ $item->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $item->serviceName?->name }}</span>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">برای این خدمت هنوز دسته‌بندی ثبت نشده است.</div>
                            @endforelse
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-12 rounded-b-3xl bg-gradient-to-t from-white via-white/85 to-white/0"
                        ></div>
                    </div>
                </section>

                <section class="flex flex-col rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">واحد خدمت</h2>
                            <p class="text-sm text-slate-500">واحدهای قابل انتخاب برای خدمات را مدیریت کنید</p>
                        </div>
                        @if($editingUnitId)
                            <button type="button" wire:click="resetUnitForm" class="rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">جدید</button>
                        @endif
                    </div>

                    <form wire:submit.prevent="saveUnit" class="space-y-3">
                        <input type="text" wire:model.blur="unitLabel" placeholder="مثلاً کیلوگرم" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                        @error('unitLabel') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <input type="text" wire:model.blur="unitKey" placeholder="کلید اختیاری، مثل kilogram" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                        @error('unitKey') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button type="submit" class="w-full rounded-2xl bg-cyan-700 px-4 py-3 text-sm font-bold text-white">
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
                            class="max-h-[28rem] space-y-2 overflow-y-auto scroll-smooth pr-1"
                        >
                            @foreach($serviceUnits as $item)
                                <button type="button" wire:click="editUnit({{ $item->id }})" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right transition hover:border-cyan-300">
                                    <span class="font-semibold text-slate-800">{{ $item->label }}</span>
                                    <span class="text-xs text-slate-500">{{ $item->key }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div
                            x-cloak
                            x-show="showFade"
                            x-transition.opacity.duration.200ms
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-12 rounded-b-3xl bg-gradient-to-t from-slate-50/95 via-slate-50/80 to-slate-50/0"
                        ></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
