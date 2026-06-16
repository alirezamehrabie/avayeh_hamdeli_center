<div>
    <div class="container mx-auto p-0">
        <div class="rounded-2xl border bg-gradient-to-br from-white via-rose-50/30 to-white p-6 shadow-sm sm:p-7" style="border-color: #f5d0e1;">
            <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-col gap-4 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">

                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-sm"
                             style="background: linear-gradient(to left, #9D174D, #BE185D);">
                            <i class="fa fa-users text-xl"></i>
                        </div>

                        <div>
                            <h1 class="text-xl font-extrabold text-slate-800 lg:text-2xl">لیست مددجویان</h1>
                            <p class="mt-1 text-sm text-slate-500">جستجو، مشاهده و مدیریت افراد ثبت‌شده در سامانه</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        @can('people-register')
                            <a  href="{{ route('admin.dashboard', ['section' => 'people-fast-create']) }}"
                               class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4"
                               style="background: linear-gradient(to left, #9D174D, #BE185D); --tw-ring-color: rgb(244 114 182 / 0.25);">
                                <i class="fa fa-bolt ml-2 text-sm"></i>
                                ثبت نام سریع
                            </a>

                            <a href="{{ route('admin.dashboard', ['section' => 'person-create']) }}"
                               class="inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4"
                               style="border-color: #f3d2df; color: #9D174D; background-color: #fff7fb; --tw-ring-color: rgb(244 114 182 / 0.18);">
                                <i class="fa fa-user-plus ml-2 text-sm"></i>
                                ثبت نام کامل
                            </a>
                        @endcan

                        <div class="min-w-[140px] rounded-xl border px-4 py-2.5 text-center shadow-sm"
                             style="border-color: #f3d2df; background: linear-gradient(180deg, #fffafc 0%, #ffffff 100%);">
                            <p class="text-xs font-semibold text-slate-500 py-2">تعداد مددجویان</p>
                            <p class="mt-1 text-xl font-extrabold" style="color: #9D174D;">
                                {{ number_format($this->people->total()) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>


            <div class="mb-6 rounded-2xl border bg-white/70 p-4 sm:p-5" style="border-color: #f5d0e1;">
                <label for="beneficiary-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سریع</label>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="beneficiary-search-field"
                        wire:model.live="searchField"
                        class="w-full rounded-2xl border bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:outline-none focus:ring-4"
                        style="border-color: #f5d0e1;"
                        aria-label="معیار جستجو"
                    >
                        <option value="all">همه فیلدها</option>
                        <option value="person_code">کد مددجو</option>
                        <option value="full_name">نام و نام خانوادگی</option>
                        <option value="first_name">نام</option>
                        <option value="last_name">نام خانوادگی</option>
                        <option value="national_id">کد ملی</option>
                        <option value="mother_national_id">کد ملی مادر</option>
                        <option value="father_national_id">کد ملی پدر</option>
                    </select>

                    <input
                        id="beneficiary-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4"
                        style="border-color: #f5d0e1;"
                        placeholder="عبارت جستجو را وارد کنید..."
                    >
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="text-white" style="background: linear-gradient(to left, #9D174D, #be185d);">
                            <tr>
                                <th class="px-5 py-4 text-center font-bold">ردیف</th>
                                <th class="px-5 py-4 text-center font-bold">کد مددجو</th>
                                <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                <th class="px-5 py-4 text-center font-bold">کد ملی</th>
                                <th class="px-5 py-4 text-center font-bold">تاریخ تولد</th>
                                <th class="px-5 py-4 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($this->people as $person)
                                <tr wire:key="person-row-{{ $person->id }}" wire:click="showPersonInfo({{ $person->id }})" class="cursor-pointer transition hover:bg-rose-50/70">
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $this->people->firstItem() + $loop->index }}</td>
                                    <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $person->person_code }}</td>
                                    <td class="px-5 py-4 text-right font-light text-slate-800">{{ $person->full_name }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->national_id }}</td>
                                    <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                    <td class="px-5 py-4 text-center">
                                        @php
                                            $trackingTooltip = '<div class="tracking-tooltip-wrap border-gray-400" dir="rtl">'
                                                . '<div class="tracking-tooltip-title my-2">رهگیری ثبت نام</div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> ایجادکننده </span><span class="value">' . e($person->creator?->name ?? 'مدیریت') . '</span></div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> زمان ایجاد </span><span class="value">' . e(optional($person->created_at)->format('Y/m/d H:i') ?? '-') . '</span></div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> آخرین ویرایش توسط </span><span class="value">' . e($person->updater?->name ?? $person->creator?->name ?? 'مدیریت') . '</span></div>'
                                                . '<div class="tracking-tooltip-row"><span class="label"> زمان آخرین ویرایش </span><span class="value">' . e(optional($person->updated_at)->format('Y/m/d H:i') ?? '-') . '</span></div>'
                                                . '</div>';
                                        @endphp
                                        <div class="flex items-center justify-center gap-2 whitespace-nowrap">
                                            <button
                                                type="button"
                                                onclick="event.stopPropagation()"
                                                class="js-tracking-tooltip inline-flex h-9 w-9 items-center justify-center rounded-full border bg-white text-slate-600 shadow-sm transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                                style="border-color: #f5d0e1;"
                                                aria-label="رهگیری ثبت"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-html="true"
                                                data-bs-custom-class="beneficiary-tracking-tooltip"
                                                data-bs-title="{{ $trackingTooltip }}"
                                                x-data="{}"
                                                x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', sanitize: false, delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                            >
                                                <i class="bi bi-clock-history"></i>
                                            </button>

                                                @can('people-edit')
                                                <button
                                                    wire:click.stop="editPerson({{ $person->id }})"
                                                    onclick="event.stopPropagation()"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                                    aria-label="ویرایش کامل"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-title="ویرایش کامل اطلاعات مددجو"
                                                    x-data="{}"
                                                    x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <button
                                                    wire:click.stop="quickEditPerson({{ $person->id }})"
                                                    onclick="event.stopPropagation()"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                                    aria-label="ویرایش سریع"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-title="ویرایش سریع اطلاعات کلیدی"
                                                    x-data="{}"
                                                    x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                                >
                                                    <i class="bi bi-lightning-charge"></i>
                                                </button>
                                            @endcan

                                            @can('people-edit')
                                                <button
                                                    wire:click.stop="openQrModal({{ $person->id }})"
                                                    onclick="event.stopPropagation()"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-cyan-200 bg-cyan-50 text-cyan-700 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-200"
                                                    aria-label="کارت QR"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-title="کارت QR"
                                                    x-data="{}"
                                                    x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                                >
                                                    <i class="bi bi-qr-code"></i>
                                                </button>
                                            @endcan

                                            @can('people-delete')
                                                <button
                                                    wire:click.stop="openDeleteModal({{ $person->id }})"
                                                    onclick="event.stopPropagation()"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                                    aria-label="حذف"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-title="انتقال به بلاک لیست"
                                                    x-data="{}"
                                                    x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">هیچ مددجویی ثبت نشده است.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $this->people->links() }}
            </div>
        </div>
    </div>

    @if($showDeleteModal && $deletingPerson)
        <div
            wire:key="person-delete-modal"
            x-data="{
                open: @js($showDeleteModal),
                close() {
                    this.open = false;
                    setTimeout(() => $wire.closeDeleteModal(), 220);
                }
            }"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @keydown.escape.window="close()"
            style="display: none;"
        >
            <div class="absolute inset-0" @click="close()"></div>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-rose-100 bg-white shadow-2xl"
                @click.stop
            >
                <div class="flex items-start justify-between gap-4 bg-gradient-to-l from-rose-600 to-pink-500 px-6 py-5 text-white">
                    <div>
                        <h2 class="text-xl font-extrabold">حذف مددجو و انتقال به بلاک لیست</h2>
                        <p class="mt-1 text-sm text-white/85">{{ $deletingPerson->full_name }}</p>
                    </div>
                    <button type="button" @click="close()" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25" aria-label="بستن">
                        &times;
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4 text-sm text-slate-700">
                        برای انتقال این مددجو به بلاک لیست، ثبت علت حذف الزامی است.
                    </div>

                    <div>
                        <label for="person-deletion-reason" class="mb-2 block text-sm font-semibold text-slate-700">علت حذف <span class="text-rose-600">*</span></label>
                        <textarea
                            id="person-deletion-reason"
                            wire:model.defer="deletionReason"
                            rows="4"
                            class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100"
                            placeholder="علت انتقال این مددجو به بلاک لیست را ثبت کنید..."
                        ></textarea>
                        @error('deletionReason') <span class="mt-2 block text-sm font-semibold text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" @click="close()" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            انصراف
                        </button>
                        <button type="button" wire:click="confirmDeletePerson" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                            حذف مددجو
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showQrModal && $this->qrPerson)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-cyan-100 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 bg-cyan-700 px-6 py-5 text-white">
                    <div>
                        <h2 class="text-xl font-extrabold">کارت QR مددجو</h2>
                        <p class="mt-1 text-sm text-white/85">{{ $this->qrPerson->full_name }} - {{ $this->qrPerson->person_code }}</p>
                    </div>
                    <button type="button" wire:click="closeQrModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25" aria-label="بستن">&times;</button>
                </div>

                <div class="space-y-4 p-6">
                    @if($this->selectedQrIdentity)
                        <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[240px_minmax(0,1fr)] sm:items-center">
                            <div class="flex h-60 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:w-60">
                                <div class="h-[220px] w-[220px] [&>svg]:block [&>svg]:h-full [&>svg]:w-full">
                                    {!! $this->selectedQrIdentity->qr_svg !!}
                                </div>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-500">شناسه کارت</p>
                                <p class="mt-1 font-mono text-lg font-black text-slate-900">{{ $this->selectedQrIdentity->public_code }}</p>
                                <p class="mt-3 text-xs font-bold text-slate-500">نشانی اسکن احراز هویت‌شده</p>
                                <p class="mt-1 break-all rounded-xl bg-white px-3 py-2 text-xs text-slate-700">{{ $this->selectedQrIdentity->scan_url }}</p>
                                <p class="mt-3 text-xs text-slate-500">این QR فقط پس از ورود کارکنان قابل استفاده است و شامل اطلاعات شخصی نیست.</p>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">برای این مددجو QR فعال وجود ندارد.</div>
                    @endif

                    @if($issuedQrToken)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            QR جدید صادر شد. نشانی اسکن نمایش‌داده‌شده برای چاپ به‌روزرسانی شده است.
                        </div>
                    @endif

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-6 text-amber-900">
                        این کارت به عنوان کارت هویتی بلندمدت چاپ می‌شود. صدور مجدد یا ابطال فقط در شرایط کنترل‌شده، با دسترسی کامل و ثبت علت مجاز است.
                    </div>

                    @if($confirmingQrLifecycleAction)
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <p class="text-sm font-black text-rose-800">
                                {{ $qrLifecycleAction === 'reissue' ? 'تایید صدور مجدد QR' : 'تایید ابطال QR' }}
                            </p>
                            <label class="mt-3 block text-xs font-bold text-slate-700" for="person-qr-lifecycle-reason">علت اقدام</label>
                            <textarea
                                id="person-qr-lifecycle-reason"
                                wire:model.defer="qrLifecycleReason"
                                rows="3"
                                class="mt-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100"
                                placeholder="علت دقیق ابطال یا صدور مجدد کارت را ثبت کنید..."
                            ></textarea>
                            @error('qrLifecycleReason') <p class="mt-1 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                            <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button type="button" wire:click="cancelQrLifecycleAction" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700">انصراف</button>
                                <button type="button" wire:click="confirmQrLifecycleAction" class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-bold text-white">تایید نهایی</button>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @can('full-access')
                            <button type="button" wire:click="requestQrLifecycleAction('revoke')" @disabled(!$this->selectedQrIdentity) class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 disabled:opacity-50">ابطال کنترل‌شده</button>
                            <button type="button" wire:click="requestQrLifecycleAction('reissue')" class="rounded-2xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">صدور مجدد کنترل‌شده</button>
                        @endcan
                        <button type="button" wire:click="closeQrModal" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700">بستن</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('livewire.people.partials.person-details-modal')
</div>

@push('styles')
    <style>
        .tooltip.beneficiary-tracking-tooltip .tooltip-inner {
            max-width: 21rem;
            min-width: 17rem;
            text-align: right;
            direction: rtl;
            border-radius: 0.95rem;
            border: 1px solid #fecdd3;
            background: linear-gradient(180deg, #fff 0%, #fffafc 100%);
            color: #334155;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
            padding: 0.7rem 0.8rem;
            font-size: 0.74rem;
            line-height: 1.5;
        }

        .tooltip.beneficiary-tracking-tooltip .tooltip-arrow::before {
            border-top-color: #fecdd3;
            border-bottom-color: #fecdd3;
            border-left-color: #fecdd3;
            border-right-color: #fecdd3;
        }

        .tooltip .tooltip-inner {
            border-radius: 0.75rem;
            font-size: 0.72rem;
            background: #fff;
            color: #334155;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
            padding: 0.42rem 0.62rem;
        }

        .tooltip .tooltip-arrow::before {
            border-top-color: #e2e8f0;
            border-bottom-color: #e2e8f0;
            border-left-color: #e2e8f0;
            border-right-color: #e2e8f0;
        }

        .tracking-tooltip-wrap {
            display: grid;
            gap: 0.42rem;
        }

        .tracking-tooltip-title {
            margin-bottom: 0.1rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid #ffe4e6;
            font-size: 0.78rem;
            font-weight: 800;
            color: #9f1239;
        }

        .tracking-tooltip-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: baseline;
            font-size: 0.73rem;
        }

        .tracking-tooltip-row .label {
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
        }

        .tracking-tooltip-row .value {
            color: #0f172a;
            font-weight: 700;
            text-align: left;
            direction: ltr;
        }
    </style>
@endpush
