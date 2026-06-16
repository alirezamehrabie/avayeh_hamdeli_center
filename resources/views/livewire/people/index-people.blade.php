<div>
    <div class="container mx-auto p-0">
        @php
            $people = $this->people;
            $hasSearch = trim($search) !== '' || $searchField !== 'all';
        @endphp

        <div class="rounded-2xl border bg-gradient-to-br from-white via-rose-50/30 to-white p-3 shadow-sm sm:p-5" style="border-color: #f5d0e1;">
            <div class="mb-3 flex flex-col gap-3 sm:mb-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm sm:flex"
                         style="background: linear-gradient(to left, #9D174D, #BE185D);">
                        <i class="fa fa-users text-base"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl lg:text-2xl">لیست مددجویان</h1>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-extrabold"
                                  style="border-color: #f3d2df; color: #9D174D; background-color: #fff7fb;">
                                {{ number_format($people->total()) }} مددجو
                            </span>
                        </div>
                        <p class="mt-1 hidden text-sm text-slate-500 sm:block">جستجو، مشاهده و مدیریت افراد ثبت‌شده در سامانه</p>
                    </div>
                </div>

                @can('people-register')
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end">
                        <a  href="{{ route('admin.dashboard', ['section' => 'people-fast-create']) }}"
                           class="inline-flex min-h-10 items-center justify-center rounded-xl px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 sm:px-4 sm:text-sm"
                           style="background: linear-gradient(to left, #9D174D, #BE185D); --tw-ring-color: rgb(244 114 182 / 0.25);">
                            <i class="fa fa-bolt ml-2 text-xs sm:text-sm"></i>
                            ثبت سریع
                        </a>

                        <a href="{{ route('admin.dashboard', ['section' => 'person-create']) }}"
                           class="inline-flex min-h-10 items-center justify-center rounded-xl border px-3 py-2 text-xs font-bold shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 sm:px-4 sm:text-sm"
                           style="border-color: #f3d2df; color: #9D174D; background-color: #fff7fb; --tw-ring-color: rgb(244 114 182 / 0.18);">
                            <i class="fa fa-user-plus ml-2 text-xs sm:text-sm"></i>
                            ثبت کامل
                        </a>
                    </div>
                @endcan
            </div>

            <div class="mb-4 rounded-2xl border bg-white/70 p-3 sm:mb-5 sm:p-4" style="border-color: #f5d0e1;">
                <label for="beneficiary-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سریع</label>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="beneficiary-search-field"
                        wire:model.live="searchField"
                        class="w-full rounded-2xl border bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition focus:outline-none focus:ring-4 sm:py-3"
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
                        class="w-full rounded-2xl border bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4 sm:py-3"
                        style="border-color: #f5d0e1;"
                        placeholder="عبارت جستجو را وارد کنید..."
                    >
                </div>
                <div
                    wire:loading.flex
                    wire:target="search,searchField,clearSearch,previousPage,nextPage,gotoPage"
                    class="mt-3 items-center gap-2 rounded-xl border border-rose-100 bg-rose-50/70 px-3 py-2 text-xs font-semibold text-rose-700"
                >
                    <span class="h-2 w-2 animate-pulse rounded-full bg-rose-600"></span>
                    در حال به‌روزرسانی لیست...
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($people->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm ring-1 ring-slate-100">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                        <i class="bi {{ $hasSearch ? 'bi-search' : 'bi-people' }} text-2xl"></i>
                    </div>
                    <h2 class="mt-4 text-base font-extrabold text-slate-800">
                        {{ $hasSearch ? 'نتیجه‌ای برای جستجوی شما پیدا نشد' : 'هنوز مددجویی ثبت نشده است' }}
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        @if($hasSearch)
                            عبارت جستجو یا معیار انتخاب‌شده را تغییر دهید، یا فیلترها را پاک کنید.
                        @else
                            پس از ثبت اولین مددجو، اطلاعات اصلی و عملیات سریع در این بخش نمایش داده می‌شود.
                        @endif
                    </p>
                    @if($hasSearch)
                        <button
                            type="button"
                            wire:click="clearSearch"
                            wire:loading.attr="disabled"
                            wire:target="clearSearch"
                            class="mt-5 inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-bold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:opacity-60"
                        >
                            پاک کردن جستجو
                        </button>
                    @endif
                </div>
            @else
                <div class="space-y-3 md:hidden">
                    @foreach($people as $person)
                        <article wire:key="person-card-{{ $person->id }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-100">
                            <button
                                type="button"
                                wire:click="showPersonInfo({{ $person->id }})"
                                wire:loading.attr="disabled"
                                wire:target="showPersonInfo({{ $person->id }})"
                                class="block w-full rounded-xl text-right focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:opacity-60"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h2 class="truncate text-base font-extrabold text-slate-900">{{ $person->full_name ?: 'بدون نام' }}</h2>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1" dir="ltr">{{ $person->person_code }}</span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1" dir="ltr">{{ $person->national_id }}</span>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">
                                        #{{ $people->firstItem() + $loop->index }}
                                    </span>
                                </div>
                                <dl class="mt-4 grid grid-cols-2 gap-2 text-right">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <dt class="text-[11px] font-semibold text-slate-500">تاریخ تولد</dt>
                                        <dd class="mt-1 text-sm font-bold text-slate-800">{{ $person->birth_date ?? 'نامشخص' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <dt class="text-[11px] font-semibold text-slate-500">آخرین ویرایش</dt>
                                        <dd class="mt-1 text-sm font-bold text-slate-800">{{ optional($person->updated_at)->format('Y/m/d') ?? '-' }}</dd>
                                    </div>
                                </dl>
                            </button>

                            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                                <button
                                    type="button"
                                    wire:click="showRegistrationTracking({{ $person->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="showRegistrationTracking({{ $person->id }})"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-white text-cyan-700 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:opacity-60"
                                    aria-label="رهگیری ثبت"
                                >
                                    <i class="bi bi-file-earmark-check text-lg"></i>
                                </button>

                                <button
                                    type="button"
                                    wire:click="showPersonInfo({{ $person->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="showPersonInfo({{ $person->id }})"
                                    class="inline-flex min-h-11 flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:opacity-60"
                                >
                                    مشاهده
                                </button>

                                @canany(['people-edit', 'people-delete'])
                                    <div class="relative" x-data="{ open: false }" @click.stop @keydown.escape.window="open = false">
                                        <button
                                            type="button"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            aria-label="اقدامات بیشتر"
                                            aria-haspopup="menu"
                                            :aria-expanded="open.toString()"
                                            @click="open = ! open"
                                        >
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>

                                        <div
                                            x-show="open"
                                            x-transition.origin.top.right
                                            @click.away="open = false"
                                            class="absolute left-0 z-30 mt-2 w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 text-right shadow-xl ring-1 ring-slate-100"
                                            style="display: none;"
                                            role="menu"
                                        >
                                            @can('people-edit')
                                                <button
                                                    type="button"
                                                    wire:click="quickEditPerson({{ $person->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="quickEditPerson({{ $person->id }})"
                                                    class="flex w-full items-center gap-2 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-amber-50 hover:text-amber-700 focus:bg-amber-50 focus:outline-none disabled:opacity-60"
                                                    role="menuitem"
                                                    @click="open = false"
                                                >
                                                    <i class="bi bi-lightning-charge text-amber-600"></i>
                                                    ویرایش سریع
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="openQrModal({{ $person->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="openQrModal({{ $person->id }})"
                                                    class="flex w-full items-center gap-2 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-cyan-50 hover:text-cyan-700 focus:bg-cyan-50 focus:outline-none disabled:opacity-60"
                                                    role="menuitem"
                                                    @click="open = false"
                                                >
                                                    <i class="bi bi-qr-code text-cyan-600"></i>
                                                    کارت QR
                                                </button>
                                            @endcan

                                            @can('people-delete')
                                                <button
                                                    type="button"
                                                    wire:click="openDeleteModal({{ $person->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="openDeleteModal({{ $person->id }})"
                                                    class="flex w-full items-center gap-2 px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none disabled:opacity-60"
                                                    role="menuitem"
                                                    @click="open = false"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                    انتقال به بلاک لیست
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                @endcanany
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 md:block">
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
                                @foreach($people as $person)
                                    <tr wire:key="person-row-{{ $person->id }}" wire:click="showPersonInfo({{ $person->id }})" class="cursor-pointer transition hover:bg-rose-50/70">
                                        <td class="px-5 py-4 text-center font-light text-slate-700">{{ $people->firstItem() + $loop->index }}</td>
                                        <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $person->person_code }}</td>
                                        <td class="px-5 py-4 text-right font-light text-slate-800">{{ $person->full_name }}</td>
                                        <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->national_id }}</td>
                                        <td class="px-5 py-4 text-center font-light text-slate-700">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2 whitespace-nowrap">
                                                <button
                                                    type="button"
                                                    wire:click.stop="showRegistrationTracking({{ $person->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="showRegistrationTracking({{ $person->id }})"
                                                    onclick="event.stopPropagation()"
                                                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-white text-cyan-700 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:opacity-60"
                                                    aria-label="رهگیری ثبت"
                                                >
                                                    <i class="bi bi-file-earmark-check text-lg"></i>
                                                </button>

                                                @can('people-edit')
                                                    <button
                                                        wire:click.stop="editPerson({{ $person->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="editPerson({{ $person->id }})"
                                                        onclick="event.stopPropagation()"
                                                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white text-indigo-700 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:opacity-60"
                                                        aria-label="ویرایش کامل"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-title="ویرایش کامل اطلاعات مددجو"
                                                        x-data="{}"
                                                        x-init="if (window.bootstrap?.Tooltip) { const t = new window.bootstrap.Tooltip($el, { container: 'body', trigger: 'hover focus', delay: { show: 120, hide: 80 } }); $el.addEventListener('click', () => t.hide()); $el.addEventListener('mouseleave', () => t.hide()); }"
                                                    >
                                                        <i class="bi bi-pencil-fill text-base"></i>
                                                    </button>

                                                @endcan

                                                @canany(['people-edit', 'people-delete'])
                                                    <div class="relative" x-data="{ open: false }" @click.stop @keydown.escape.window="open = false">
                                                        <button
                                                            type="button"
                                                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                            aria-label="اقدامات بیشتر"
                                                            aria-haspopup="menu"
                                                            :aria-expanded="open.toString()"
                                                            @click="open = ! open"
                                                        >
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>

                                                        <div
                                                            x-show="open"
                                                            x-transition.origin.top.right
                                                            @click.away="open = false"
                                                            class="absolute left-0 z-30 mt-2 w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 text-right shadow-xl ring-1 ring-slate-100"
                                                            style="display: none;"
                                                            role="menu"
                                                        >
                                                            @can('people-edit')
                                                                <button
                                                                    type="button"
                                                                    wire:click="quickEditPerson({{ $person->id }})"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="quickEditPerson({{ $person->id }})"
                                                                    class="flex w-full items-center gap-2 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-amber-50 hover:text-amber-700 focus:bg-amber-50 focus:outline-none disabled:opacity-60"
                                                                    role="menuitem"
                                                                    @click="open = false"
                                                                >
                                                                    <i class="bi bi-lightning-charge text-amber-600"></i>
                                                                    ویرایش سریع
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    wire:click="openQrModal({{ $person->id }})"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="openQrModal({{ $person->id }})"
                                                                    class="flex w-full items-center gap-2 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-cyan-50 hover:text-cyan-700 focus:bg-cyan-50 focus:outline-none disabled:opacity-60"
                                                                    role="menuitem"
                                                                    @click="open = false"
                                                                >
                                                                    <i class="bi bi-qr-code text-cyan-600"></i>
                                                                    کارت QR
                                                                </button>
                                                            @endcan

                                                            @can('people-delete')
                                                                <button
                                                                    type="button"
                                                                    wire:click="openDeleteModal({{ $person->id }})"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="openDeleteModal({{ $person->id }})"
                                                                    class="flex w-full items-center gap-2 px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none disabled:opacity-60"
                                                                    role="menuitem"
                                                                    @click="open = false"
                                                                >
                                                                    <i class="bi bi-trash3"></i>
                                                                    انتقال به بلاک لیست
                                                                </button>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                @endcanany
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-3">
                {{ $people->links() }}
            </div>
        </div>
    </div>

    @if($showTrackingModal)
        @php
            $trackingPerson = $this->trackingPerson;
        @endphp

        @if($trackingPerson)
            <div
                wire:key="person-tracking-modal-{{ $trackingPerson->id }}"
                x-data="{
                    open: @js($showTrackingModal),
                    close() {
                        this.open = false;
                        setTimeout(() => $wire.closeTrackingModal(), 180);
                    }
                }"
                x-show="open"
                x-transition:enter="transition-opacity ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/35 p-0 sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="person-tracking-modal-title"
                @keydown.escape.window="close()"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="close()"></div>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-3 opacity-0 sm:translate-y-0"
                    class="relative w-full overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:max-w-lg sm:rounded-2xl"
                    @click.stop
                >
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-4 py-4 sm:px-5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 text-xs font-bold text-cyan-700">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700">
                                    <i class="bi bi-file-earmark-check"></i>
                                </span>
                                رهگیری ثبت نام
                            </div>
                            <h2 id="person-tracking-modal-title" class="mt-2 truncate text-base font-extrabold text-slate-900 sm:text-lg">
                                {{ $trackingPerson->full_name ?: 'مددجوی بدون نام' }}
                            </h2>
                            <p class="mt-1 text-xs font-bold text-slate-500" dir="ltr">
                                {{ $trackingPerson->person_code ?: '-' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="close()"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-2xl leading-none text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            aria-label="بستن"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="px-4 py-2 sm:px-5">
                        <dl class="divide-y divide-slate-100">
                            <div class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-3 py-3">
                                <dt class="text-xs font-bold text-slate-500">ایجادکننده</dt>
                                <dd class="min-w-0 break-words text-sm font-bold text-slate-900">
                                    {{ $trackingPerson->creator?->name ?? 'مدیریت' }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-3 py-3">
                                <dt class="text-xs font-bold text-slate-500">زمان ایجاد</dt>
                                <dd class="text-sm font-bold text-slate-900" dir="ltr">
                                    {{ optional($trackingPerson->created_at)->format('Y/m/d H:i') ?? '-' }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-3 py-3">
                                <dt class="text-xs font-bold text-slate-500">آخرین ویرایش</dt>
                                <dd class="min-w-0 break-words text-sm font-bold text-slate-900">
                                    {{ $trackingPerson->updater?->name ?? $trackingPerson->creator?->name ?? 'مدیریت' }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-3 py-3">
                                <dt class="text-xs font-bold text-slate-500">زمان ویرایش</dt>
                                <dd class="text-sm font-bold text-slate-900" dir="ltr">
                                    {{ optional($trackingPerson->updated_at)->format('Y/m/d H:i') ?? '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 px-4 py-3 sm:px-5">
                        <button
                            type="button"
                            @click="close()"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                            بستن
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

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
