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
                <div class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                    <button type="button" wire:click="switchTab('activity')" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'activity' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
                        <i class="bi bi-calendar-event"></i>
                        اطلاعات فعالیت
                    </button>
                    <button type="button" wire:click="switchTab('services')" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold transition {{ $activeTab === 'services' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
                        <i class="bi bi-box-seam"></i>
                        خدمات مرتبط
                        <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ count($activityServices) }}</span>
                    </button>
                </div>

                <div class="{{ $activeTab === 'activity' ? 'space-y-4' : 'hidden' }}">
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

                </div>

                <div class="{{ $activeTab === 'services' ? 'space-y-4' : 'hidden' }}">
                    <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">تعریف خدمات مرتبط با فعالیت</h2>
                                <p class="text-sm text-slate-500">حضور در فعالیت، تحویل این خدمات را برای مددجو ثبت می‌کند.</p>
                            </div>
                            <button type="button" wire:click="addActivityService" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                                <i class="bi bi-plus-lg"></i>
                                افزودن خدمت
                            </button>
                        </div>

                        @error('activityServices') <p class="text-sm font-bold text-rose-600">{{ $message }}</p> @enderror

                        @if(count($activityServices) === 0)
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                                <p class="text-sm font-bold text-slate-700">هنوز خدمتی برای این فعالیت تعریف نشده است.</p>
                                <button type="button" wire:click="addActivityService" class="mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                                    <i class="bi bi-plus-lg"></i>
                                    افزودن اولین خدمت
                                </button>
                            </div>
                        @endif

                        <div class="space-y-5">
                            @foreach($activityServices as $serviceIndex => $service)
                                <div wire:key="activity-service-{{ $serviceIndex }}" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-base font-black text-slate-800">خدمت {{ $serviceIndex + 1 }}</h3>
                                            <p class="text-xs text-slate-500">روش تحویل نهایی: تحویل در فعالیت</p>
                                        </div>
                                        @if(count($activityServices) > 1)
                                            <button type="button" wire:click="removeActivityService({{ $serviceIndex }})" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 transition hover:bg-rose-50">
                                                حذف خدمت
                                            </button>
                                        @endif
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">نام خدمت <span class="text-rose-500">*</span></label>
                                            <input type="text" list="activity-service-names" wire:model="activityServices.{{ $serviceIndex }}.serviceName" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                            @error("activityServices.$serviceIndex.serviceName") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">نوع خدمت <span class="text-rose-500">*</span></label>
                                            <select wire:model="activityServices.{{ $serviceIndex }}.serviceType" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                                @foreach($serviceTypeOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error("activityServices.$serviceIndex.serviceType") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">منطقه</label>
                                            <select wire:model="activityServices.{{ $serviceIndex }}.serviceDistrictId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                                <option value="">بدون منطقه</option>
                                                @foreach($districts as $district)
                                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("activityServices.$serviceIndex.serviceDistrictId") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">شروع توزیع <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="activityServices.{{ $serviceIndex }}.distributionStartDate" placeholder="1405/03/28" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition ltr:text-left focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                            @error("activityServices.$serviceIndex.distributionStartDate") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">پایان توزیع</label>
                                            <input type="text" wire:model="activityServices.{{ $serviceIndex }}.distributionEndDate" placeholder="1405/03/28" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition ltr:text-left focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                            @error("activityServices.$serviceIndex.distributionEndDate") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">وضعیت خدمت</label>
                                            <select wire:model="activityServices.{{ $serviceIndex }}.status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                                @foreach($serviceStatusOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error("activityServices.$serviceIndex.status") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">توضیحات خدمت</label>
                                            <textarea wire:model="activityServices.{{ $serviceIndex }}.description" rows="2" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"></textarea>
                                            @error("activityServices.$serviceIndex.description") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت وضعیت خدمت</label>
                                            <textarea wire:model="activityServices.{{ $serviceIndex }}.statusNotes" rows="2" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"></textarea>
                                            @error("activityServices.$serviceIndex.statusNotes") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <h4 class="text-sm font-black text-slate-800">دسته‌های خدمت</h4>
                                            <button type="button" wire:click="addActivityServiceCategory({{ $serviceIndex }})" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                                                افزودن دسته
                                            </button>
                                        </div>

                                        <div class="space-y-3">
                                            @foreach(($service['categories'] ?? []) as $categoryIndex => $category)
                                                <div wire:key="activity-service-{{ $serviceIndex }}-category-{{ $categoryIndex }}" class="grid gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3 md:grid-cols-[minmax(0,1.2fr)_120px_140px_140px_auto]">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-slate-600">نام دسته</label>
                                                        <input type="text" wire:model="activityServices.{{ $serviceIndex }}.categories.{{ $categoryIndex }}.name" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                                        @error("activityServices.$serviceIndex.categories.$categoryIndex.name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-slate-600">تعداد</label>
                                                        <input type="number" step="0.01" min="0.01" wire:model="activityServices.{{ $serviceIndex }}.categories.{{ $categoryIndex }}.quantity" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                                        @error("activityServices.$serviceIndex.categories.$categoryIndex.quantity") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-slate-600">واحد</label>
                                                        <select wire:model="activityServices.{{ $serviceIndex }}.categories.{{ $categoryIndex }}.unit" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                                            @foreach($unitOptions as $value => $label)
                                                                <option value="{{ $value }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error("activityServices.$serviceIndex.categories.$categoryIndex.unit") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-slate-600">ارزش واحد</label>
                                                        <input type="text" wire:model="activityServices.{{ $serviceIndex }}.categories.{{ $categoryIndex }}.value" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                                        @error("activityServices.$serviceIndex.categories.$categoryIndex.value") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                                    </div>
                                                    <div class="flex items-end">
                                                        <button type="button" wire:click="removeActivityServiceCategory({{ $serviceIndex }}, {{ $categoryIndex }})" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50" title="حذف دسته">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <datalist id="activity-service-names">
                    @foreach($serviceNames as $serviceName)
                        <option value="{{ $serviceName->name }}"></option>
                    @endforeach
                </datalist>

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
