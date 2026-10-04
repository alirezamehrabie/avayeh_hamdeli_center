<div>
    {{-- resources/views/livewire/people/deleted-people.blade.php --}}
    <div
        x-data="{
            showToast: false,
            toastMessage: '',
            toastTimer: null,
            openToast(message) {
                this.toastMessage = message;
                this.showToast = true;

                if (this.toastTimer) {
                    clearTimeout(this.toastTimer);
                }

                this.toastTimer = setTimeout(() => {
                    this.showToast = false;
                }, 3500);
            }
        }"
        x-on:people-block-list-toast.window="openToast($event.detail.message)"
    >
        @php
            $people = $this->people;
            $hasSearch = trim($search) !== '' || $searchField !== 'all';
            $searchNeedsMoreInput = $this->searchNeedsMoreInput();
            $deletedCountLabel = method_exists($people, 'total') ? number_format($people->total()) . ' مورد غیرفعال' : 'نتایج جستجو';
        @endphp

        <div class="container mx-auto p-0">
            {{-- هویت رنگی بخش: rose (مددجو) --}}
            <div class="rounded-2xl border border-rose-100/80 bg-gradient-to-br from-white via-rose-50/40 to-white p-3 shadow-sm sm:p-5">
                {{-- هدر صفحه و آمار --}}
                <div class="mb-3 flex flex-col gap-3 sm:mb-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-l from-rose-700 to-pink-700 text-white shadow-sm sm:flex">
                            <i class="bi bi-person-x-fill text-base"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl lg:text-2xl">بلاک‌لیست مددجویان (غیرفعال)</h1>
                                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100">
                                    {{ $deletedCountLabel }}
                                </span>
                            </div>
                            <p class="mt-1 hidden text-sm text-slate-500 sm:block">مشاهده مددجویان حذف‌شده و امکان بازیابی آنی نظارت با همان کد مددجو و سوابق قبلی</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-[minmax(0,1fr)_auto_auto] items-stretch gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end sm:gap-3">
                        <!-- کارت آمار مددجویان غیرفعال -->
                        <div class="rounded-xl border border-rose-100 bg-white/90 px-3 py-2 shadow-sm ring-1 ring-rose-50 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:rounded-2xl sm:px-4 sm:py-2.5">
                            <p class="truncate text-[10px] font-semibold text-slate-500 sm:text-xs">کل مددجویان غیرفعال</p>
                            <div class="mt-0.5 flex items-center justify-center gap-2 sm:mt-1 sm:gap-2.5" dir="ltr">
                                <span class="relative hidden h-2.5 w-2.5 sm:flex" aria-label="وضعیت غیرفعال">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-60"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 animate-pulse rounded-full bg-rose-500 shadow-sm shadow-rose-300"></span>
                                </span>
                                <span class="text-base font-extrabold tracking-tight text-rose-600 iranyekan-bold sm:text-lg">
                                    {{ number_format($totalDeletedPeople) }}
                                </span>
                            </div>
                        </div>

                        <!-- دکمه بروزرسانی -->
                        <button
                            type="button"
                            wire:click="refreshData"
                            wire:loading.attr="disabled"
                            wire:target="refreshData"
                            title="بروزرسانی داده‌ها"
                            class="group inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-100 bg-white text-rose-600 shadow-sm transition-all duration-200 hover:border-rose-300 hover:bg-rose-50 hover:shadow-md disabled:opacity-60 sm:h-11 sm:w-11 sm:rounded-2xl"
                        >
                            <svg class="h-4 w-4 stroke-[1.8] transition-transform duration-500 group-hover:rotate-180 sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                        </button>

                        <!-- بازگشت به لیست مددجویان فعال -->
                        @if($embedded)
                            <button
                                type="button"
                                wire:click="goToPeopleList"
                                class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-rose-100 sm:min-h-11 sm:px-4 sm:text-sm"
                            >
                                <i class="bi bi-people-fill ml-1.5 text-xs sm:text-sm"></i>
                                لیست مددجویان فعال
                            </button>
                        @else
                            <a
                                href="{{ route('admin.dashboard', ['section' => 'people-list']) }}"
                                class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-rose-100 sm:min-h-11 sm:px-4 sm:text-sm"
                            >
                                <i class="bi bi-people-fill ml-1.5 text-xs sm:text-sm"></i>
                                لیست مددجویان فعال
                            </a>
                        @endif
                    </div>
                </div>

                {{-- کادر جستجوی سریع --}}
                <div class="mb-4 rounded-2xl border border-rose-100/80 bg-white/80 p-2.5 sm:mb-5 sm:p-4">
                    <div class="flex items-center justify-between gap-3">
                        <label for="blocked-person-search" class="text-sm font-bold text-slate-700">جستجوی سریع در بلاک‌لیست</label>
                        @if($hasSearch)
                            <button
                                type="button"
                                wire:click="clearSearch"
                                wire:loading.attr="disabled"
                                wire:target="clearSearch"
                                class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-100 disabled:opacity-60"
                            >
                                پاک کردن جستجو
                            </button>
                        @endif
                    </div>

                    <div class="mt-2.5 space-y-2.5">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-rose-400">
                                <i class="bi bi-search text-sm"></i>
                            </span>
                            <input
                                id="blocked-person-search"
                                type="text"
                                wire:model.live.debounce.600ms="search"
                                wire:loading.attr="disabled"
                                wire:target="search,searchField"
                                class="w-full rounded-xl border border-rose-200/70 bg-white py-2.5 pr-9 pl-4 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100 sm:rounded-2xl sm:py-3"
                                placeholder="نام، کد ملی یا کد مددجو..."
                            >
                        </div>

                        {{-- انتخاب معیار جستجو در موبایل --}}
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 sm:hidden">
                            <span class="shrink-0 text-[11px] font-bold text-slate-500">در</span>
                            <select
                                id="blocked-person-search-field-mobile"
                                wire:model.change="searchField"
                                class="min-w-0 flex-1 bg-transparent text-xs font-bold text-slate-700 focus:outline-none"
                                aria-label="معیار جستجو"
                            >
                                @foreach($searchFieldLabels as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- انتخاب معیار جستجو در دسکتاپ --}}
                        <div class="hidden md:grid md:grid-cols-[minmax(180px,240px)_1fr] md:gap-3">
                            <select
                                id="blocked-person-search-field"
                                wire:model.change="searchField"
                                wire:loading.attr="disabled"
                                wire:target="search,searchField"
                                class="w-full rounded-2xl border border-rose-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100"
                                aria-label="معیار جستجو"
                            >
                                @foreach($searchFieldLabels as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                                @endforeach
                            </select>

                            <div class="flex items-center gap-2 rounded-2xl border border-dashed border-rose-200/70 bg-rose-50/40 px-4 py-3 text-sm font-semibold text-rose-800/80">
                                <i class="bi bi-info-circle shrink-0 text-base text-rose-400"></i>
                                برای سرعت بیشتر، جستجو با کد مددجو یا کد ملی دقیق‌تر است.
                            </div>
                        </div>
                    </div>

                    @if($searchNeedsMoreInput)
                        <div class="mt-2.5 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-800 sm:text-xs">
                            برای جستجوی متنی حداقل ۲ کاراکتر وارد کنید.
                        </div>
                    @endif

                    <div
                        wire:loading.flex
                        wire:target="search,searchField,clearSearch,previousPage,nextPage,gotoPage"
                        class="mt-2.5 items-center gap-2 rounded-xl border border-rose-100 bg-rose-50/70 px-3 py-2 text-[11px] font-semibold text-rose-700 sm:mt-3 sm:text-xs"
                    >
                        <span class="h-2 w-2 animate-pulse rounded-full bg-rose-600"></span>
                        در حال به‌روزرسانی لیست...
                    </div>
                </div>

                @if (session()->has('success'))
                    <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- حالت خالی بودن لیست --}}
                @if ($people->isEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm ring-1 ring-slate-100">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                            <i class="bi {{ $hasSearch ? 'bi-search' : 'bi-inbox' }} text-2xl"></i>
                        </div>
                        <h2 class="mt-4 text-base font-extrabold text-slate-800">
                            @if ($searchNeedsMoreInput)
                                برای شروع جستجو حداقل ۲ کاراکتر وارد کنید
                            @elseif ($hasSearch)
                                مددجویی مطابق با عبارت جستجو در بلاک‌لیست پیدا نشد
                            @else
                                هیچ مددجویی در بلاک‌لیست نیست
                            @endif
                        </h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                            @if ($searchNeedsMoreInput)
                                برای جلوگیری از کند شدن سیستم، جستجوی متنی با ورودی کوتاه اجرا نمی‌شود.
                            @elseif ($hasSearch)
                                عبارت جستجو یا معیار انتخاب‌شده را تغییر دهید، یا فیلترها را پاک کنید.
                            @else
                                مددجویانی که از سامانه حذف می‌شوند در این بخش فهرست می‌شوند و امکان بازیابی نظارت خواهند داشت.
                            @endif
                        </p>
                        @if ($hasSearch)
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
                    {{-- نمای موبایل: کارت‌های عمودی --}}
                    <div class="space-y-2.5 md:hidden">
                        @foreach ($people as $person)
                            <article wire:key="blocked-person-card-{{ $person->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
                                <div class="px-3 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $person->full_name ?: 'بدون نام' }}</h2>
                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $person->gender === 'female' ? 'bg-pink-500' : 'bg-sky-500' }}" aria-hidden="true"></span>
                                                    {{ $person->gender_label ?: 'نامشخص' }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 font-bold text-rose-700" dir="ltr">{{ $person->person_code }}</span>
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $person->national_id ?: '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-left">
                                            <p class="text-[10px] font-semibold text-slate-400">تاریخ حذف</p>
                                            <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-slate-700" dir="ltr">
                                                {{ $this->formatJalaliDate($person->deleted_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <dl class="mt-3 grid grid-cols-2 gap-2 text-right">
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-[10px] font-semibold text-slate-400">سرپرست</dt>
                                            <dd class="mt-0.5 truncate text-xs font-bold text-slate-700">
                                                {{ trim(($person->guardian?->first_name ?? '') . ' ' . ($person->guardian?->last_name ?? '')) ?: '—' }}
                                            </dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-[10px] font-semibold text-slate-400">تاریخ تولد</dt>
                                            <dd class="mt-0.5 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $person->birth_date ?? 'نامشخص' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-[10px] font-semibold text-slate-400">نام پدر</dt>
                                            <dd class="mt-0.5 truncate text-xs font-bold text-slate-700">{{ $person->father_name ?: '—' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-[10px] font-semibold text-slate-400">شماره تماس</dt>
                                            <dd class="mt-0.5 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $person->phone_number ?: '—' }}</dd>
                                        </div>
                                    </dl>

                                    {{-- علت حذف --}}
                                    @if (filled($person->deletion_reason))
                                        <div class="mt-3 rounded-xl border border-rose-200/70 bg-rose-50/60 p-2.5 text-xs leading-5 text-rose-950">
                                            <div class="flex items-center gap-1.5 font-bold text-rose-800">
                                                <i class="bi bi-info-circle-fill text-rose-500"></i>
                                                <span>علت حذف:</span>
                                            </div>
                                            <p class="mt-1 font-medium">{{ $person->deletion_reason }}</p>
                                        </div>
                                    @else
                                        <div class="mt-2.5 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-[11px] text-slate-400">
                                            علت حذف ثبت نشده است
                                        </div>
                                    @endif
                                </div>

                                {{-- دکمه اقدام بازیابی --}}
                                <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                                    <button
                                        type="button"
                                        wire:click="restoreSupervision({{ $person->id }})"
                                        wire:confirm="آیا از بازیابی نظارت این مددجو با همان کد قبلی مطمئن هستید؟"
                                        wire:loading.attr="disabled"
                                        wire:target="restoreSupervision({{ $person->id }})"
                                        class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <i class="bi bi-arrow-counterclockwise text-sm" wire:loading.remove wire:target="restoreSupervision({{ $person->id }})"></i>
                                        <span wire:loading.remove wire:target="restoreSupervision({{ $person->id }})">بازیابی نظارت مددجو</span>
                                        <span wire:loading wire:target="restoreSupervision({{ $person->id }})" class="inline-flex items-center gap-2">
                                            <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-emerald-600 border-t-transparent" role="status"></span>
                                            در حال بازیابی...
                                        </span>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- نمای دسکتاپ: جدول بهینه‌شده --}}
                    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 md:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-sm">
                                <thead class="bg-gradient-to-l from-rose-700 to-pink-700 text-white">
                                    <tr>
                                        <th class="w-16 whitespace-nowrap px-4 py-4 text-center font-bold">ردیف</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد مددجو</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد ملی</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-right font-bold">سرپرست</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">تاریخ تولد</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">تاریخ و زمان حذف</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-right font-bold">علت حذف</th>
                                        <th class="w-44 whitespace-nowrap px-5 py-4 text-center font-bold">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($people as $person)
                                        <tr wire:key="blocked-person-row-{{ $person->id }}" class="transition hover:bg-rose-50/60">
                                            <td class="px-4 py-4 text-center text-xs font-extrabold tabular-nums text-slate-400">
                                                {{ ($people->firstItem() ?? 1) + $loop->index }}
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <span class="inline-flex items-center rounded-lg bg-rose-50 px-2.5 py-1 font-mono text-xs font-bold text-rose-800 ring-1 ring-rose-200/70" dir="ltr">
                                                    {{ $person->person_code }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-extrabold text-slate-900">{{ $person->full_name ?: 'بدون نام' }}</span>
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $person->gender === 'female' ? 'bg-pink-500' : 'bg-sky-500' }}" aria-hidden="true"></span>
                                                        {{ $person->gender_label ?: 'نامشخص' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">
                                                {{ $person->national_id ?: '-' }}
                                            </td>
                                            <td class="px-5 py-4 text-right font-medium text-slate-700">
                                                {{ trim(($person->guardian?->first_name ?? '') . ' ' . ($person->guardian?->last_name ?? '')) ?: '—' }}
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">
                                                {{ $person->birth_date ?? 'نامشخص' }}
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">
                                                {{ $this->formatJalaliDate($person->deleted_at) }}
                                            </td>
                                            <td class="px-5 py-4 text-right text-xs">
                                                @if (filled($person->deletion_reason))
                                                    <div class="max-w-[220px] truncate rounded-lg border border-rose-100 bg-rose-50/80 px-2.5 py-1 font-medium text-rose-900" title="{{ $person->deletion_reason }}">
                                                        {{ $person->deletion_reason }}
                                                    </div>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <button
                                                    type="button"
                                                    wire:click="restoreSupervision({{ $person->id }})"
                                                    wire:confirm="آیا از بازیابی نظارت این مددجو با همان کد قبلی مطمئن هستید؟"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restoreSupervision({{ $person->id }})"
                                                    class="inline-flex min-h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
                                                    title="بازیابی نظارت مددجو با حفظ کد قبلی"
                                                >
                                                    <i class="bi bi-arrow-counterclockwise text-sm" wire:loading.remove wire:target="restoreSupervision({{ $person->id }})"></i>
                                                    <span wire:loading.remove wire:target="restoreSupervision({{ $person->id }})">بازیابی نظارت</span>
                                                    <span wire:loading wire:target="restoreSupervision({{ $person->id }})" class="inline-flex items-center gap-1.5">
                                                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-emerald-600 border-t-transparent" role="status"></span>
                                                        در حال ثبت...
                                                    </span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- صفحه‌بندی --}}
                    <div class="mt-4">
                        {{ $people->links('vendor.livewire.tailwind-mobile-persian') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- اعلان Toast --}}
        <div
            x-show="showToast"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-5 left-5 z-50 flex items-center gap-3 rounded-2xl border border-rose-200 bg-white/95 px-4 py-3 shadow-xl backdrop-blur ring-1 ring-rose-100"
            style="display: none;"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                <i class="bi bi-check2-circle text-base"></i>
            </div>
            <span class="text-xs font-bold text-slate-800" x-text="toastMessage"></span>
        </div>
    </div>
</div>
