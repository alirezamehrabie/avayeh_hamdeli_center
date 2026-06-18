<div
    class="space-y-6"
    dir="rtl"
    x-data="{
        isDirty: false,
        isSaving: false,
        confirmLeave() {
            if (this.isSaving || ! this.isDirty) {
                return true;
            }

            return confirm('تغییرات ذخیره نشده‌اند. آیا از خروج از فرم مطمئن هستید؟');
        },
        leaveForm() {
            if (! this.confirmLeave()) {
                return;
            }

            this.isDirty = false;
            this.$wire.backToList();
        },
    }"
    x-on:activity-save-failed.window="isSaving = false; isDirty = true"
    x-on:beforeunload.window="if (isDirty && ! isSaving) { $event.preventDefault(); $event.returnValue = ''; }"
>
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="min-h-[104px] border-b border-emerald-700/30 bg-[#059669] px-4 py-4 text-white sm:px-6 sm:py-6">
            <div class="flex flex-col gap-3 sm:gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-lg font-extrabold leading-tight text-white sm:mt-2 sm:text-2xl">{{ $editingActivityId ? 'ویرایش فعالیت' : 'تعریف فعالیت جدید' }}</h1>
                    <p class="mt-1 hidden max-w-3xl text-sm leading-6 text-emerald-50/90 sm:mt-2 sm:block">فعالیت‌ها برای رویدادها، جشن‌ها، اردوها و برنامه‌های گروهی ثبت می‌شوند.</p>
                </div>
                <button type="button" x-on:click="leaveForm()" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60">بازگشت به فهرست</button>
            </div>
        </div>

        <form wire:submit.prevent="save" x-on:input="isDirty = true" x-on:change="isDirty = true" x-on:jalali-picker-confirm="isDirty = true" x-on:submit="isSaving = true; isDirty = false" class="space-y-4 px-4 py-4 sm:px-6 lg:py-5">
            @if (session('activity-success'))
                <div role="status" aria-live="polite" class="flex items-center gap-3 rounded-2xl border border-emerald-300/70 bg-gradient-to-l from-emerald-600 via-emerald-500 to-green-500 px-4 py-3.5 text-sm font-medium text-emerald-50 shadow-lg shadow-emerald-900/10 ring-1 ring-emerald-100">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/18 text-white ring-1 ring-white/30">
                        <i class="bi bi-check2-circle text-lg leading-none"></i>
                    </span>
                    <span class="leading-6">{{ session('activity-success') }}</span>
                </div>
            @endif

            <fieldset wire:loading.attr="disabled" wire:target="save" class="space-y-4 disabled:pointer-events-none disabled:opacity-70">
                <section class="space-y-4 rounded-3xl border border-slate-200 bg-slate-50/80 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">اطلاعات اصلی</h2>
                            <p class="text-sm text-slate-500">نام، نوع، وضعیت و کد فعالیت</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold tracking-wide text-slate-600 shadow-sm">
                            {{ $this->previewActivityCode }}
                        </span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
{{--                        <div class="md:max-w-56">--}}
{{--                            <label class="mb-2 block text-sm font-bold text-slate-700">{{ $editingActivityId ? 'کد فعالیت' : 'کد پیشنهادی فعالیت' }}</label>--}}
{{--                            <div class="inline-flex min-w-36 max-w-full items-center rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm">--}}
{{--                                <span class="truncate">{{ $this->previewActivityCode }}</span>--}}
{{--                            </div>--}}
{{--                            @unless($editingActivityId)--}}
{{--                                <p class="mt-1 text-xs leading-5 text-slate-500">کد نهایی هنگام ذخیره قطعی می‌شود.</p>--}}
{{--                            @endunless--}}
{{--                        </div>--}}
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">نام فعالیت <span class="text-rose-500">*</span></label>
                            <input type="text" required wire:model="name" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">نوع فعالیت <span class="text-rose-500">*</span></label>
                            <select required wire:model="activityType" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                @foreach($typeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('activityType') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">دسته‌بندی / نوع فعالیت <span class="text-rose-500">*</span></label>
                            <select required wire:model="status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">مکان</label>
                            <div class="relative">
                                <input type="text" wire:model="location" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-11 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <i class="bi bi-geo-alt text-base"></i>
                                </span>
                            </div>
                            @error('location') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">زمان‌بندی و ظرفیت</h2>
                        <p class="text-sm text-slate-500">برای ثبت تاریخ از تقویم استفاده کنید و بعد از انتخاب، دکمه تأیید تقویم را بزنید.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div x-data="jalaliDateTimeField($wire.entangle('startsAt').live)">
                            <label class="mb-2 block text-sm font-bold text-slate-700">زمان شروع</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    x-ref="input"
                                    x-model="draft"
                                    x-on:change="syncFromInput()"
                                    x-on:blur="syncFromInput()"
                                    x-on:jalali-picker-open="handlePickerOpen()"
                                    x-on:jalali-picker-close="handlePickerClose()"
                                    x-on:jalali-picker-confirm="confirm()"
                                    readonly
                                    inputmode="none"
                                    autocomplete="off"
                                    data-jdp-readonly
                                    data-jdp
                                    data-jdp-time
                                    placeholder="۱۴۰۳/۰۱/۰۱ ۱۴:۳۰"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-11 text-sm text-slate-700 outline-none transition ltr:text-left focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                                >
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <i class="bi bi-calendar2-event text-base"></i>
                                </span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">قالب مجاز: 1403/01/01 یا 1403/01/01 14:30. انتخاب تقویم بدون تأیید ثبت نمی‌شود.</p>
                            @error('startsAt') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div x-data="jalaliDateTimeField($wire.entangle('endsAt').live)">
                            <label class="mb-2 block text-sm font-bold text-slate-700">زمان پایان</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    x-ref="input"
                                    x-model="draft"
                                    x-on:change="syncFromInput()"
                                    x-on:blur="syncFromInput()"
                                    x-on:jalali-picker-open="handlePickerOpen()"
                                    x-on:jalali-picker-close="handlePickerClose()"
                                    x-on:jalali-picker-confirm="confirm()"
                                    readonly
                                    inputmode="none"
                                    autocomplete="off"
                                    data-jdp-readonly
                                    data-jdp
                                    data-jdp-time
                                    placeholder="۱۴۰۳/۰۱/۰۱ ۱۸:۰۰"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pe-11 text-sm text-slate-700 outline-none transition ltr:text-left focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                                >
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <i class="bi bi-clock-history text-base"></i>
                                </span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">در صورت ثبت زمان پایان، زمان شروع هم باید وارد شود و پایان باید بعد از شروع باشد.</p>
                            @error('endsAt') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">ظرفیت</label>
                            <input type="number" min="1" wire:model="capacity" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                            <p class="mt-1 text-xs leading-5 text-slate-500">اگر محدودیتی وجود ندارد، این فیلد را خالی بگذارید.</p>
                            @error('capacity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">توضیحات و یادداشت‌ها</h2>
                        <p class="text-sm text-slate-500">جزئیات اجرایی و دلیل تغییر وضعیت را برای پیگیری‌های بعدی ثبت کنید.</p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label>
                            <textarea wire:model="description" rows="2" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 lg:min-h-[5.25rem]"></textarea>
                            @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت وضعیت</label>
                            <textarea wire:model="statusNotes" rows="2" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 lg:min-h-[5.25rem]"></textarea>
                            @error('statusNotes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <div class="sticky bottom-3 z-20 flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur-sm">
                    <button type="button" x-on:click="leaveForm()" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">انصراف</button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-violet-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:bg-violet-400">
                        <span wire:loading.remove wire:target="save">ذخیره فعالیت</span>
                        <span wire:loading.flex wire:target="save" class="items-center gap-2">
                            <i class="bi bi-arrow-repeat animate-spin text-base leading-none"></i>
                            در حال ذخیره...
                        </span>
                    </button>
                </div>
            </fieldset>
        </form>
    </div>
</div>
