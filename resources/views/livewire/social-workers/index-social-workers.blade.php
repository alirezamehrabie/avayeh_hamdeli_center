<div>
    {{-- resources/views/livewire/social-workers/index-social-workers.blade.php --}}
    <livewire:social-workers.transfer-households wire:key="social-worker-transfer-modal" />

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
        x-on:social-workers-toast.window="openToast($event.detail.message)"
    >
        @php
            $socialWorkers = $this->socialWorkers;
            $hasSearch = trim($search) !== '' || $searchField !== 'all';
            $searchNeedsMoreInput = $this->searchNeedsMoreInput();
            $searchFieldLabels = [
                'all' => 'همه فیلدها',
                'worker_code' => 'کد مددکاری',
                'full_name' => 'نام و نام خانوادگی',
                'first_name' => 'نام',
                'last_name' => 'نام خانوادگی',
                'national_id' => 'کد ملی',
                'mobile' => 'شماره موبایل',
            ];
            $workerCountLabel = method_exists($socialWorkers, 'total') ? number_format($socialWorkers->total()) . ' مددکار' : 'نتایج جستجو';
        @endphp

        <div class="container mx-auto p-0">
            {{-- هویت رنگی بخش: cyan / sky (مددکاران اجتماعی) --}}
            <div class="rounded-2xl border border-cyan-100/80 bg-gradient-to-br from-white via-cyan-50/30 to-white p-3 shadow-sm sm:p-5">
                {{-- هدر و آمار بالا --}}
                <div class="mb-3 flex flex-col gap-3 sm:mb-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-l from-cyan-600 via-sky-600 to-cyan-700 text-white shadow-sm sm:flex">
                            <i class="bi bi-person-badge-fill text-base"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg font-extrabold text-slate-800 sm:text-xl lg:text-2xl">لیست مددکاران اجتماعی</h1>
                                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-700 ring-1 ring-cyan-100">
                                    {{ $workerCountLabel }}
                                </span>
                            </div>
                            <p class="mt-1 hidden text-sm text-slate-500 sm:block">مدیریت اطلاعات مددکاران، راه‌های ارتباطی و نظارت بر پرونده‌های تحت پوشش</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] items-stretch gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end sm:gap-3">
                        <!-- کارت آمار مددکاران -->
                        <div class="rounded-xl border border-cyan-100 bg-white/90 px-3 py-2 shadow-sm ring-1 ring-cyan-50 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:rounded-2xl sm:px-4 sm:py-2.5">
                            <p class="truncate text-[10px] font-semibold text-slate-500 sm:text-xs">تعداد مددکاران</p>
                            <div class="mt-0.5 flex items-center justify-center gap-2 sm:mt-1 sm:gap-2.5" dir="ltr">
                                <span class="relative hidden h-2.5 w-2.5 sm:flex" aria-label="به‌روزرسانی زنده">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-60"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-500 shadow-sm shadow-cyan-300"></span>
                                </span>
                                <span class="text-base font-extrabold tracking-tight text-cyan-600 iranyekan-bold sm:text-lg">
                                    {{ number_format($totalSocialWorkers) }}
                                </span>
                            </div>
                        </div>

                        <!-- کارت آمار خانوارهای تخصیص‌یافته -->
                        <div class="rounded-xl border border-sky-100 bg-white/90 px-3 py-2 shadow-sm ring-1 ring-sky-50 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:rounded-2xl sm:px-4 sm:py-2.5">
                            <p class="truncate text-[10px] font-semibold text-slate-500 sm:text-xs">خانوارهای منتسب</p>
                            <div class="mt-0.5 flex items-center justify-center gap-2 sm:mt-1 sm:gap-2.5" dir="ltr">
                                <span class="relative hidden h-2.5 w-2.5 sm:flex" aria-label="تعداد خانوارها">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-60"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 animate-pulse rounded-full bg-sky-500 shadow-sm shadow-sky-300"></span>
                                </span>
                                <span class="text-base font-extrabold tracking-tight text-sky-600 iranyekan-bold sm:text-lg">
                                    {{ number_format($totalAssignedHouseholds) }}
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
                            class="group inline-flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-100 bg-white text-cyan-600 shadow-sm transition-all duration-200 hover:border-cyan-300 hover:bg-cyan-50 hover:shadow-md disabled:opacity-60 sm:h-11 sm:w-11 sm:rounded-2xl"
                        >
                            <svg class="h-4 w-4 stroke-[1.8] transition-transform duration-500 group-hover:rotate-180 sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                        </button>

                        <!-- دکمه ثبت مددکار جدید -->
                        @if($embedded)
                            <button
                                type="button"
                                wire:click="createSocialWorker"
                                wire:loading.attr="disabled"
                                wire:target="createSocialWorker"
                                class="col-span-3 inline-flex min-h-10 items-center justify-center whitespace-nowrap rounded-xl bg-gradient-to-l from-cyan-600 via-sky-600 to-cyan-700 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-200 disabled:cursor-not-allowed disabled:opacity-60 sm:col-auto sm:px-4 sm:text-sm"
                            >
                                <i class="bi bi-person-plus-fill ml-1.5 text-xs sm:text-sm"></i>
                                <span wire:loading.remove wire:target="createSocialWorker">ثبت مددکار جدید</span>
                                <span wire:loading wire:target="createSocialWorker">در حال باز کردن...</span>
                            </button>
                        @else
                            <a
                                href="{{ route('social-workers.create') }}"
                                class="col-span-3 inline-flex min-h-10 items-center justify-center whitespace-nowrap rounded-xl bg-gradient-to-l from-cyan-600 via-sky-600 to-cyan-700 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-200 sm:col-auto sm:px-4 sm:text-sm"
                            >
                                <i class="bi bi-person-plus-fill ml-1.5 text-xs sm:text-sm"></i>
                                ثبت مددکار جدید
                            </a>
                        @endif
                    </div>
                </div>

                {{-- کادر جستجوی سریع --}}
                <div class="mb-4 rounded-2xl border border-cyan-100/80 bg-white/80 p-2.5 sm:mb-5 sm:p-4">
                    <div class="flex items-center justify-between gap-3">
                        <label for="social-worker-search" class="text-sm font-bold text-slate-700">جستجوی سریع مددکاران</label>
                        @if($hasSearch)
                            <button
                                type="button"
                                wire:click="clearSearch"
                                wire:loading.attr="disabled"
                                wire:target="clearSearch"
                                class="inline-flex items-center justify-center rounded-lg border border-cyan-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-cyan-700 transition hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:opacity-60"
                            >
                                پاک کردن جستجو
                            </button>
                        @endif
                    </div>

                    <div class="mt-2.5 space-y-2.5">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <i class="bi bi-search text-sm"></i>
                            </span>
                            <input
                                id="social-worker-search"
                                type="text"
                                wire:model.live.debounce.600ms="search"
                                wire:loading.attr="disabled"
                                wire:target="search,searchField"
                                class="w-full rounded-xl border border-cyan-200/70 bg-white py-2.5 pr-9 pl-4 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 sm:rounded-2xl sm:py-3"
                                placeholder="نام، کد ملی، شماره موبایل یا کد مددکاری..."
                            >
                        </div>

                        {{-- انتخاب فیلتر در موبایل --}}
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 sm:hidden">
                            <span class="shrink-0 text-[11px] font-bold text-slate-500">در</span>
                            <select
                                id="social-worker-search-field-mobile"
                                wire:model.change="searchField"
                                class="min-w-0 flex-1 bg-transparent text-xs font-bold text-slate-700 focus:outline-none"
                                aria-label="معیار جستجو"
                            >
                                @foreach($searchFieldLabels as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- انتخاب فیلتر در دسکتاپ --}}
                        <div class="hidden md:grid md:grid-cols-[minmax(180px,240px)_1fr] md:gap-3">
                            <select
                                id="social-worker-search-field"
                                wire:model.change="searchField"
                                wire:loading.attr="disabled"
                                wire:target="search,searchField"
                                class="w-full rounded-2xl border border-cyan-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                aria-label="معیار جستجو"
                            >
                                @foreach($searchFieldLabels as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                                @endforeach
                            </select>

                            <div class="flex items-center gap-2 rounded-2xl border border-dashed border-cyan-200/70 bg-cyan-50/40 px-4 py-3 text-sm font-semibold text-cyan-800/80">
                                <i class="bi bi-info-circle shrink-0 text-base text-cyan-500"></i>
                                برای سرعت بیشتر، جستجو با کد مددکاری یا کد ملی دقیق‌تر است.
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
                        class="mt-2.5 items-center gap-2 rounded-xl border border-cyan-100 bg-cyan-50/70 px-3 py-2 text-[11px] font-semibold text-cyan-700 sm:mt-3 sm:text-xs"
                    >
                        <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-600"></span>
                        در حال به‌روزرسانی لیست...
                    </div>
                    @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                @if (session()->has('success'))
                    <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- حالت خالی بودن لیست --}}
                @if($socialWorkers->isEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm ring-1 ring-slate-100">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                            <i class="bi {{ $hasSearch ? 'bi-search' : 'bi-person-badge' }} text-2xl"></i>
                        </div>
                        <h2 class="mt-4 text-base font-extrabold text-slate-800">
                            @if($searchNeedsMoreInput)
                                برای شروع جستجو حداقل ۲ کاراکتر وارد کنید
                            @else
                                {{ $hasSearch ? 'نتیجه‌ای برای جستجوی شما پیدا نشد' : 'هنوز مددکاری ثبت نشده است' }}
                            @endif
                        </h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                            @if($searchNeedsMoreInput)
                                برای جلوگیری از کند شدن سیستم، جستجوی متنی با ورودی کوتاه اجرا نمی‌شود.
                            @elseif($hasSearch)
                                عبارت جستجو یا معیار انتخاب‌شده را تغییر دهید، یا فیلترها را پاک کنید.
                            @else
                                پس از ثبت اولین مددکار، اطلاعات اصلی و عملیات سریع در این بخش نمایش داده می‌شود.
                            @endif
                        </p>
                        @if($hasSearch)
                            <button
                                type="button"
                                wire:click="clearSearch"
                                wire:loading.attr="disabled"
                                wire:target="clearSearch"
                                class="mt-5 inline-flex items-center justify-center rounded-2xl border border-cyan-200 bg-cyan-50 px-5 py-3 text-sm font-bold text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 focus:outline-none focus:ring-4 focus:ring-cyan-100 disabled:opacity-60"
                            >
                                پاک کردن جستجو
                            </button>
                        @endif
                    </div>
                @else
                    {{-- نمایش کارتی در حالت موبایل --}}
                    <div class="space-y-2.5 md:hidden">
                        @foreach($socialWorkers as $worker)
                            <article wire:key="social-worker-card-{{ $worker->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
                                <button
                                    type="button"
                                    wire:click="toggleSocialWorker({{ $worker->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleSocialWorker({{ $worker->id }})"
                                    class="block w-full px-3 py-3 text-right focus:outline-none focus:ring-4 focus:ring-cyan-100 disabled:opacity-60"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</h2>
                                            </div>
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500">
                                                <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 font-bold text-cyan-700" dir="ltr">{{ $worker->worker_code ?: '-' }}</span>
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $worker->national_id ?: '-' }}</span>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-left">
                                            <p class="text-[10px] font-semibold text-slate-400">تحت پوشش</p>
                                            <p class="mt-0.5 text-xs font-extrabold text-slate-800">{{ $this->getCoveredCountForWorker($worker) }} نفر</p>
                                        </div>
                                    </div>

                                    <dl class="mt-3 grid grid-cols-2 gap-2 text-right">
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-[10px] font-semibold text-slate-400">شماره موبایل</dt>
                                            <dd class="mt-0.5 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $worker->mobile ?: '-' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2">
                                            <dt class="text-[10px] font-semibold text-slate-400">وضعیت جزئیات</dt>
                                            <dd class="mt-0.5 text-xs font-bold text-slate-700">{{ $expandedSocialWorkerId === $worker->id ? 'باز شده' : 'بسته' }}</dd>
                                        </div>
                                    </dl>
                                </button>

                                <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <button
                                            type="button"
                                            wire:click.stop="toggleSocialWorker({{ $worker->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleSocialWorker({{ $worker->id }})"
                                            class="inline-flex min-h-9 flex-1 items-center justify-center rounded-xl border border-cyan-200 bg-cyan-50 px-3 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60"
                                        >
                                            <span wire:loading.remove wire:target="toggleSocialWorker({{ $worker->id }})">
                                                {{ $expandedSocialWorkerId === $worker->id ? 'بستن پرونده‌ها' : 'مشاهده پرونده‌ها' }}
                                            </span>
                                            <span wire:loading wire:target="toggleSocialWorker({{ $worker->id }})">...</span>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click.stop="transferHouseholds({{ $worker->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="transferHouseholds({{ $worker->id }})"
                                            class="inline-flex min-h-9 flex-1 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 text-xs font-bold text-sky-700 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-100 disabled:cursor-wait disabled:opacity-60"
                                        >
                                            انتقال خانوار
                                        </button>

                                        {{-- اکشن شیت منوی بیشتر در موبایل --}}
                                        <div
                                            class="relative"
                                            x-data="{
                                                open: false,
                                                historyPushed: false,
                                                openSheet() {
                                                    if (this.open) return;
                                                    this.open = true;
                                                    window.history.pushState({ socialWorkerActionSheet: {{ $worker->id }} }, '', window.location.href);
                                                    this.historyPushed = true;
                                                },
                                                closeSheet(fromPopState = false) {
                                                    if (! this.open) return;
                                                    this.open = false;
                                                    if (this.historyPushed) {
                                                        this.historyPushed = false;
                                                        if (! fromPopState) {
                                                            window.history.back();
                                                        }
                                                    }
                                                },
                                            }"
                                            @click.stop
                                            @keydown.escape.window="closeSheet()"
                                            @popstate.window="closeSheet(true)"
                                        >
                                            <button
                                                type="button"
                                                @click="open ? closeSheet() : openSheet()"
                                                class="inline-flex min-h-9 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                                aria-label="اقدامات بیشتر"
                                                aria-haspopup="dialog"
                                                :aria-expanded="open.toString()"
                                            >
                                                <i class="bi bi-three-dots text-sm"></i>
                                            </button>

                                            <!-- پس‌زمینه محو -->
                                            <div
                                                x-show="open"
                                                x-transition.opacity
                                                class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-[1px]"
                                                style="display: none;"
                                                @click="closeSheet()"
                                                aria-hidden="true"
                                            ></div>

                                            <!-- شیت کشویی پایین -->
                                            <div
                                                x-show="open"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="translate-y-6 opacity-0"
                                                x-transition:enter-end="translate-y-0 opacity-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="translate-y-0 opacity-100"
                                                x-transition:leave-end="translate-y-6 opacity-0"
                                                class="fixed inset-x-0 bottom-0 z-50 rounded-t-3xl border border-slate-200 bg-white px-4 pb-[calc(env(safe-area-inset-bottom)+1.25rem)] pt-3 shadow-2xl"
                                                style="display: none;"
                                                role="dialog"
                                                aria-modal="true"
                                                aria-label="اقدامات کارت مددکار"
                                                @click.stop
                                            >
                                                <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-200"></div>
                                                <div class="mb-3 border-b border-slate-100 pb-3 text-right">
                                                    <p class="truncate text-sm font-extrabold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</p>
                                                    <p class="mt-1 text-[11px] font-semibold text-slate-500" dir="ltr">{{ $worker->worker_code ?: '-' }}</p>
                                                </div>

                                                <div class="space-y-2">
                                                    @if($embedded)
                                                        <button
                                                            type="button"
                                                            @click="closeSheet()"
                                                            wire:click.stop="editSocialWorker({{ $worker->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="editSocialWorker({{ $worker->id }})"
                                                            class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-right text-sm font-bold text-sky-800 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-100 disabled:cursor-wait disabled:opacity-60"
                                                        >
                                                            <i class="bi bi-pencil-square text-base"></i>
                                                            <span wire:loading.remove wire:target="editSocialWorker({{ $worker->id }})">ویرایش مشخصات</span>
                                                            <span wire:loading wire:target="editSocialWorker({{ $worker->id }})">در حال باز کردن...</span>
                                                        </button>
                                                    @else
                                                        <a
                                                            href="{{ route('social-workers.edit', $worker) }}"
                                                            @click.stop="closeSheet()"
                                                            class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-right text-sm font-bold text-sky-800 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-100"
                                                        >
                                                            <i class="bi bi-pencil-square text-base"></i>
                                                            ویرایش مشخصات
                                                        </a>
                                                    @endif

                                                    <button
                                                        type="button"
                                                        @click="closeSheet()"
                                                        wire:click.stop="transferHouseholds({{ $worker->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="transferHouseholds({{ $worker->id }})"
                                                        class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-cyan-100 bg-cyan-50/70 px-4 py-3 text-right text-sm font-bold text-cyan-800 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60"
                                                    >
                                                        <i class="bi bi-arrow-left-right text-base"></i>
                                                        <span wire:loading.remove wire:target="transferHouseholds({{ $worker->id }})">انتقال خانوارها</span>
                                                        <span wire:loading wire:target="transferHouseholds({{ $worker->id }})">در حال باز کردن...</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click="closeSheet()"
                                                        wire:click.stop="deleteSocialWorker({{ $worker->id }})"
                                                        wire:confirm="آیا از غیرفعال‌سازی این مددکار مطمئن هستید؟"
                                                        wire:loading.attr="disabled"
                                                        wire:target="deleteSocialWorker({{ $worker->id }})"
                                                        class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50/70 px-4 py-3 text-right text-sm font-bold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-100 disabled:cursor-wait disabled:opacity-60"
                                                    >
                                                        <i class="bi bi-trash3 text-base"></i>
                                                        <span wire:loading.remove wire:target="deleteSocialWorker({{ $worker->id }})">غیرفعال‌سازی مددکار</span>
                                                        <span wire:loading wire:target="deleteSocialWorker({{ $worker->id }})">در حال ثبت...</span>
                                                    </button>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                    @click="closeSheet()"
                                                >
                                                    بستن
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- پانل بازشونده موبایل --}}
                                @if($expandedSocialWorkerId === $worker->id)
                                    @php
                                        $guardians = $this->getGuardiansForWorker($worker->id);
                                        $visibleGuardians = $this->getVisibleGuardiansForWorker($worker->id);
                                        $guardianLimit = $this->getVisibleGuardianLimitForWorker($worker->id);
                                        $coveredDetailsLoaded = $this->hasLoadedCoveredDetailsForWorker($worker->id);
                                        $coveredDetails = $this->getCoveredDetailsForWorker($worker->id);
                                        $visibleCoveredDetails = $this->getVisibleCoveredDetailsForWorker($worker->id);
                                        $coveredDetailLimit = $this->getVisibleCoveredDetailLimitForWorker($worker->id);
                                    @endphp
                                    <div
                                        x-data="{ show: false, activePanel: 'guardians' }"
                                        x-init="$nextTick(() => show = true)"
                                        x-show="show"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                                        class="space-y-3 border-t border-cyan-100 bg-cyan-50/40 p-3"
                                        wire:init="loadCoveredDetailsForWorker({{ $worker->id }})"
                                    >
                                        <div class="grid grid-cols-2 gap-2 rounded-xl border border-cyan-200/70 bg-white p-1">
                                            <button
                                                type="button"
                                                @click="activePanel = 'guardians'"
                                                :class="activePanel === 'guardians' ? 'bg-cyan-50 text-cyan-800 shadow-sm' : 'text-slate-500 hover:bg-slate-50'"
                                                class="rounded-lg px-3 py-2 text-xs font-extrabold transition"
                                            >
                                                سرپرستان ({{ count($guardians) }})
                                            </button>
                                            <button
                                                type="button"
                                                @click="activePanel = 'coverage'"
                                                :class="activePanel === 'coverage' ? 'bg-emerald-50 text-emerald-800 shadow-sm' : 'text-slate-500 hover:bg-slate-50'"
                                                class="rounded-lg px-3 py-2 text-xs font-extrabold transition"
                                            >
                                                آمار تحت پوشش ({{ $this->getCoveredCountForWorker($worker) }})
                                            </button>
                                        </div>

                                        <section x-show="activePanel === 'guardians'" class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-sm">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <h3 class="text-xs font-extrabold text-slate-800">سرپرستان تحت پوشش</h3>
                                                <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[10px] font-bold text-cyan-800">{{ count($guardians) }} سرپرست</span>
                                            </div>
                                            <div class="space-y-2">
                                                @forelse($visibleGuardians as $guardian)
                                                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <p class="min-w-0 truncate text-xs font-extrabold text-slate-900">{{ trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')) ?: '-' }}</p>
                                                            <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-700 shadow-sm">{{ $guardian['people_count'] }} مددجو</span>
                                                        </div>
                                                        <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-bold text-slate-500">
                                                            <span class="rounded-full bg-white px-2 py-0.5" dir="ltr">{{ $guardian['national_code'] ?: '-' }}</span>
                                                            <span class="rounded-full bg-white px-2 py-0.5" dir="ltr">{{ $guardian['guardian_phone_number'] ?: '-' }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">سرپرستی برای این مددکار ثبت نشده است.</p>
                                                @endforelse

                                                @if(count($guardians) > $guardianLimit)
                                                    <button
                                                        type="button"
                                                        wire:click="showMoreGuardians({{ $worker->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="showMoreGuardians({{ $worker->id }})"
                                                        class="w-full rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60"
                                                    >
                                                        <span wire:loading.remove wire:target="showMoreGuardians({{ $worker->id }})">نمایش سرپرستان بیشتر ({{ count($guardians) - $guardianLimit }})</span>
                                                        <span wire:loading wire:target="showMoreGuardians({{ $worker->id }})">در حال نمایش...</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </section>

                                        <section x-show="activePanel === 'coverage'" class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-sm">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <h3 class="text-xs font-extrabold text-slate-800">جزئیات آمار تحت پوشش</h3>
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-800">{{ $this->getCoveredCountForWorker($worker) }} نفر</span>
                                            </div>

                                            @if(! $coveredDetailsLoaded)
                                                <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">در حال بارگذاری جزئیات...</p>
                                            @else
                                                <div class="space-y-2">
                                                    @forelse($visibleCoveredDetails as $detail)
                                                        @php
                                                            $details = $visibleCoveredDetails;
                                                            $currentGuardianGroup = $detail['guardian_group'] ?? '-';
                                                            $previousGuardianGroup = $loop->index > 0 ? ($details[$loop->index - 1]['guardian_group'] ?? '-') : null;
                                                            $isNewSourceGroup = $loop->first || $currentGuardianGroup !== $previousGuardianGroup;
                                                        @endphp
                                                        @if($isNewSourceGroup)
                                                            <p class="rounded-lg bg-cyan-50 px-3 py-1.5 text-[10px] font-extrabold text-cyan-800">سرپرست مشترک: {{ $currentGuardianGroup }}</p>
                                                        @endif
                                                        <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2">
                                                            <div class="flex items-start justify-between gap-3">
                                                                <p class="min-w-0 truncate text-xs font-extrabold text-slate-800">{{ $detail['name'] ?: '-' }}</p>
                                                                <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700">{{ $detail['role_label'] }}</span>
                                                            </div>
                                                            <p class="mt-1 text-[10px] font-bold text-slate-500" dir="ltr">{{ $detail['national_id'] }}</p>
                                                            <p class="mt-2 text-[10px] leading-5 text-slate-600">{{ implode('، ', $detail['sources']) }}</p>
                                                        </div>
                                                    @empty
                                                        <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">موردی برای نمایش ثبت نشده است.</p>
                                                    @endforelse

                                                    @if(count($coveredDetails) > $coveredDetailLimit)
                                                        <button
                                                            type="button"
                                                            wire:click="showMoreCoveredDetails({{ $worker->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="showMoreCoveredDetails({{ $worker->id }})"
                                                            class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:cursor-wait disabled:opacity-60"
                                                        >
                                                            <span wire:loading.remove wire:target="showMoreCoveredDetails({{ $worker->id }})">نمایش جزئیات بیشتر ({{ count($coveredDetails) - $coveredDetailLimit }})</span>
                                                            <span wire:loading wire:target="showMoreCoveredDetails({{ $worker->id }})">در حال نمایش...</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </section>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    {{-- نمایش جدولی در دسکتاپ --}}
                    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 md:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-sm">
                                <thead class="bg-gradient-to-l from-cyan-600 via-sky-600 to-cyan-700 text-white">
                                    <tr>
                                        <th class="w-16 whitespace-nowrap px-4 py-4 text-center font-bold">ردیف</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد مددکاری</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">کد ملی</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">شماره موبایل</th>
                                        <th class="whitespace-nowrap px-5 py-4 text-center font-bold">آمار تحت پوشش</th>
                                        <th class="w-48 whitespace-nowrap px-5 py-4 text-center font-bold">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($socialWorkers as $worker)
                                        <tr
                                            wire:key="social-worker-row-{{ $worker->id }}"
                                            wire:click="toggleSocialWorker({{ $worker->id }})"
                                            class="cursor-pointer transition hover:bg-cyan-50/60"
                                        >
                                            <td class="px-4 py-4 text-center text-xs font-extrabold tabular-nums text-slate-400">
                                                {{ ($socialWorkers->firstItem() ?? 1) + $loop->index }}
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <span class="inline-flex items-center rounded-lg bg-cyan-50 px-2.5 py-1 font-mono text-xs font-bold text-cyan-800 ring-1 ring-cyan-200/70" dir="ltr">
                                                    {{ $worker->worker_code ?: '-' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="font-extrabold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</div>
                                            </td>
                                            <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">
                                                {{ $worker->national_id ?: '-' }}
                                            </td>
                                            <td class="px-5 py-4 text-center font-mono text-xs tabular-nums text-slate-600" dir="ltr">
                                                {{ $worker->mobile ?: '-' }}
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <span class="font-extrabold text-slate-800">{{ $this->getCoveredCountForWorker($worker) }}</span>
                                                <span class="text-xs text-slate-500">نفر</span>
                                            </td>
                                            <td class="px-5 py-4 text-center" onclick="event.stopPropagation()">
                                                <div class="flex items-center justify-center gap-1.5 whitespace-nowrap">
                                                    {{-- دکمه ویرایش --}}
                                                    @if($embedded)
                                                        <button
                                                            type="button"
                                                            wire:click.stop="editSocialWorker({{ $worker->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="editSocialWorker({{ $worker->id }})"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-200 disabled:opacity-60"
                                                            title="ویرایش مشخصات"
                                                            aria-label="ویرایش مشخصات"
                                                        >
                                                            <i class="bi bi-pencil-square text-sm"></i>
                                                        </button>
                                                    @else
                                                        <a
                                                            href="{{ route('social-workers.edit', $worker) }}"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-200"
                                                            title="ویرایش مشخصات"
                                                            aria-label="ویرایش مشخصات"
                                                        >
                                                            <i class="bi bi-pencil-square text-sm"></i>
                                                        </a>
                                                    @endif

                                                    {{-- دکمه انتقال خانوارها --}}
                                                    <button
                                                        type="button"
                                                        wire:click.stop="transferHouseholds({{ $worker->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="transferHouseholds({{ $worker->id }})"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-200 bg-cyan-50 text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-cyan-200 disabled:opacity-60"
                                                        title="انتقال خانوارها"
                                                        aria-label="انتقال خانوارها"
                                                    >
                                                        <i class="bi bi-arrow-left-right text-sm"></i>
                                                    </button>

                                                    {{-- دکمه مشاهده پرونده‌ها / جزئیات --}}
                                                    <button
                                                        type="button"
                                                        wire:click.stop="toggleSocialWorker({{ $worker->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="toggleSocialWorker({{ $worker->id }})"
                                                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-700 transition hover:border-cyan-300 hover:bg-cyan-50 hover:text-cyan-800 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-cyan-200 disabled:cursor-wait disabled:opacity-60"
                                                        title="{{ $expandedSocialWorkerId === $worker->id ? 'بستن پرونده‌ها' : 'مشاهده پرونده‌ها' }}"
                                                    >
                                                        <span wire:loading.remove wire:target="toggleSocialWorker({{ $worker->id }})">
                                                            {{ $expandedSocialWorkerId === $worker->id ? 'بستن' : 'پرونده‌ها' }}
                                                        </span>
                                                        <span wire:loading wire:target="toggleSocialWorker({{ $worker->id }})">...</span>
                                                        <i class="bi {{ $expandedSocialWorkerId === $worker->id ? 'bi-chevron-up' : 'bi-chevron-down' }} text-xs"></i>
                                                    </button>

                                                    {{-- دکمه غیرفعال‌سازی --}}
                                                    <button
                                                        type="button"
                                                        wire:click.stop="deleteSocialWorker({{ $worker->id }})"
                                                        wire:confirm="آیا از غیرفعال‌سازی این مددکار مطمئن هستید؟"
                                                        wire:loading.attr="disabled"
                                                        wire:target="deleteSocialWorker({{ $worker->id }})"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-200 disabled:opacity-60"
                                                        title="غیرفعال‌سازی مددکار"
                                                        aria-label="غیرفعال‌سازی مددکار"
                                                    >
                                                        <i class="bi bi-trash3 text-sm"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- پانل آکاردئونی دسکتاپ --}}
                                        @if($expandedSocialWorkerId === $worker->id)
                                            @php
                                                $guardians = $this->getGuardiansForWorker($worker->id);
                                                $visibleGuardians = $this->getVisibleGuardiansForWorker($worker->id);
                                                $guardianLimit = $this->getVisibleGuardianLimitForWorker($worker->id);
                                                $coveredDetailsLoaded = $this->hasLoadedCoveredDetailsForWorker($worker->id);
                                                $coveredDetails = $this->getCoveredDetailsForWorker($worker->id);
                                                $visibleCoveredDetails = $this->getVisibleCoveredDetailsForWorker($worker->id);
                                                $coveredDetailLimit = $this->getVisibleCoveredDetailLimitForWorker($worker->id);
                                            @endphp
                                            <tr class="bg-cyan-50/40" wire:key="social-worker-panel-{{ $worker->id }}">
                                                <td colspan="7" class="px-5 py-4">
                                                    <div
                                                        x-data="{ show: false, activePanel: 'guardians' }"
                                                        x-init="$nextTick(() => show = true)"
                                                        x-show="show"
                                                        x-transition:enter="transition ease-out duration-300"
                                                        x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                        x-transition:leave="transition ease-in duration-200"
                                                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                        x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                                                        class="rounded-2xl border border-cyan-100 bg-white p-4 shadow-sm ring-1 ring-cyan-50"
                                                        wire:init="loadCoveredDetailsForWorker({{ $worker->id }})"
                                                    >
                                                        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                            <div class="flex items-center gap-2">
                                                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
                                                                    <i class="bi bi-folder2-open text-sm"></i>
                                                                </div>
                                                                <h2 class="text-sm font-extrabold text-slate-800">
                                                                    جزئیات پرونده‌ها و آمار تحت پوشش: {{ $worker->full_name ?: 'بدون نام' }}
                                                                </h2>
                                                            </div>

                                                            <div class="inline-grid grid-cols-2 gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
                                                                <button
                                                                    type="button"
                                                                    @click="activePanel = 'guardians'"
                                                                    :class="activePanel === 'guardians' ? 'bg-white text-cyan-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                                                    class="rounded-lg px-4 py-2 text-xs font-extrabold transition"
                                                                >
                                                                    سرپرستان ({{ count($guardians) }})
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    @click="activePanel = 'coverage'"
                                                                    :class="activePanel === 'coverage' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                                                    class="rounded-lg px-4 py-2 text-xs font-extrabold transition"
                                                                >
                                                                    آمار تحت پوشش ({{ $this->getCoveredCountForWorker($worker) }})
                                                                </button>
                                                            </div>
                                                        </div>

                                                        {{-- تب سرپرستان --}}
                                                        <div x-show="activePanel === 'guardians'" class="overflow-x-auto rounded-xl border border-slate-200">
                                                            <table class="min-w-full border-collapse text-xs">
                                                                <thead class="bg-slate-50 text-slate-700">
                                                                    <tr>
                                                                        <th class="w-14 px-4 py-3 text-center font-bold">ردیف</th>
                                                                        <th class="px-4 py-3 text-center font-bold">کد ملی سرپرست</th>
                                                                        <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی سرپرست</th>
                                                                        <th class="px-4 py-3 text-center font-bold">شماره موبایل</th>
                                                                        <th class="px-4 py-3 text-center font-bold">مددجویان تحت نظارت</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    @forelse ($visibleGuardians as $guardian)
                                                                        <tr class="transition hover:bg-cyan-50/30">
                                                                            <td class="px-4 py-3 text-center font-medium text-slate-400 tabular-nums">{{ $loop->iteration }}</td>
                                                                            <td class="px-4 py-3 text-center font-mono font-medium text-slate-700" dir="ltr">{{ $guardian['national_code'] ?: '-' }}</td>
                                                                            <td class="px-4 py-3 text-right font-bold text-slate-800">{{ trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')) ?: '-' }}</td>
                                                                            <td class="px-4 py-3 text-center font-mono text-slate-600" dir="ltr">{{ $guardian['guardian_phone_number'] ?: '-' }}</td>
                                                                            <td class="px-4 py-3 text-center font-bold text-slate-700">
                                                                                <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-0.5 text-xs text-cyan-800 ring-1 ring-cyan-200">
                                                                                    {{ $guardian['people_count'] }} مددجو
                                                                                </span>
                                                                            </td>
                                                                        </tr>
                                                                    @empty
                                                                        <tr>
                                                                            <td colspan="5" class="px-4 py-6 text-center text-xs font-bold text-slate-500">
                                                                                سرپرستی برای این مددکار ثبت نشده است.
                                                                            </td>
                                                                        </tr>
                                                                    @endforelse
                                                                    @if(count($guardians) > $guardianLimit)
                                                                        <tr>
                                                                            <td colspan="5" class="bg-slate-50/50 px-4 py-3 text-center">
                                                                                <button
                                                                                    type="button"
                                                                                    wire:click="showMoreGuardians({{ $worker->id }})"
                                                                                    wire:loading.attr="disabled"
                                                                                    wire:target="showMoreGuardians({{ $worker->id }})"
                                                                                    class="inline-flex items-center justify-center rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60"
                                                                                >
                                                                                    <span wire:loading.remove wire:target="showMoreGuardians({{ $worker->id }})">نمایش سرپرستان بیشتر ({{ count($guardians) - $guardianLimit }})</span>
                                                                                    <span wire:loading wire:target="showMoreGuardians({{ $worker->id }})">در حال بارگذاری...</span>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        {{-- تب آمار تحت پوشش --}}
                                                        <div x-show="activePanel === 'coverage'" class="mt-1">
                                                            <div class="mb-3 flex items-center justify-between">
                                                                <h3 class="text-xs font-extrabold text-slate-800">جزئیات افراد مؤثر در آمار تحت پوشش</h3>
                                                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                                                    {{ $this->getCoveredCountForWorker($worker) }} نفر (بر پایه کد ملی)
                                                                </span>
                                                            </div>

                                                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                                                <table class="min-w-full border-collapse text-xs">
                                                                    <thead class="bg-slate-50 text-slate-700">
                                                                        <tr>
                                                                            <th class="w-14 px-4 py-3 text-center font-bold">ردیف</th>
                                                                            <th class="px-4 py-3 text-center font-bold">کد ملی</th>
                                                                            <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی</th>
                                                                            <th class="px-4 py-3 text-center font-bold">دسته</th>
                                                                            <th class="px-4 py-3 text-right font-bold">منبع ثبت</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-slate-100">
                                                                        @if(! $coveredDetailsLoaded)
                                                                            <tr>
                                                                                <td colspan="5" class="px-4 py-6 text-center text-xs font-bold text-slate-500">
                                                                                    در حال بارگذاری جزئیات...
                                                                                </td>
                                                                            </tr>
                                                                        @else
                                                                            @forelse ($visibleCoveredDetails as $detail)
                                                                                @php
                                                                                    $details = $visibleCoveredDetails;
                                                                                    $currentGuardianGroup = $detail['guardian_group'] ?? '-';
                                                                                    $previousGuardianGroup = $loop->index > 0 ? ($details[$loop->index - 1]['guardian_group'] ?? '-') : null;
                                                                                    $isNewSourceGroup = $loop->first || $currentGuardianGroup !== $previousGuardianGroup;
                                                                                @endphp
                                                                                @if($isNewSourceGroup)
                                                                                    <tr class="bg-cyan-50/80">
                                                                                        <td colspan="5" class="px-4 py-2.5 text-right text-[11px] font-extrabold text-cyan-900">
                                                                                            سرپرست مشترک: {{ $currentGuardianGroup }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                                <tr class="transition hover:bg-slate-50">
                                                                                    <td class="px-4 py-3 text-center font-medium text-slate-400 tabular-nums">{{ $loop->iteration }}</td>
                                                                                    <td class="px-4 py-3 text-center font-mono font-medium text-slate-700" dir="ltr">{{ $detail['national_id'] }}</td>
                                                                                    <td class="px-4 py-3 text-right font-bold text-slate-800">{{ $detail['name'] ?: '-' }}</td>
                                                                                    <td class="px-4 py-3 text-center">
                                                                                        <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] font-bold text-sky-800">
                                                                                            {{ $detail['role_label'] }}
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right text-slate-600">{{ implode('، ', $detail['sources']) }}</td>
                                                                                </tr>
                                                                            @empty
                                                                                <tr>
                                                                                    <td colspan="5" class="px-4 py-6 text-center text-xs font-bold text-slate-500">
                                                                                        موردی برای نمایش ثبت نشده است.
                                                                                    </td>
                                                                                </tr>
                                                                            @endforelse

                                                                            @if(count($coveredDetails) > $coveredDetailLimit)
                                                                                <tr>
                                                                                    <td colspan="5" class="bg-slate-50/50 px-4 py-3 text-center">
                                                                                        <button
                                                                                            type="button"
                                                                                            wire:click="showMoreCoveredDetails({{ $worker->id }})"
                                                                                            wire:loading.attr="disabled"
                                                                                            wire:target="showMoreCoveredDetails({{ $worker->id }})"
                                                                                            class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:cursor-wait disabled:opacity-60"
                                                                                        >
                                                                                            <span wire:loading.remove wire:target="showMoreCoveredDetails({{ $worker->id }})">نمایش جزئیات بیشتر ({{ count($coveredDetails) - $coveredDetailLimit }})</span>
                                                                                            <span wire:loading wire:target="showMoreCoveredDetails({{ $worker->id }})">در حال بارگذاری...</span>
                                                                                        </button>
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endif
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- صفحه‌بندی --}}
                    <div class="mt-4">
                        {{ $socialWorkers->links('vendor.livewire.tailwind-mobile-persian') }}
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
            class="fixed bottom-5 left-5 z-50 flex items-center gap-3 rounded-2xl border border-cyan-200 bg-white/95 px-4 py-3 shadow-xl backdrop-blur ring-1 ring-cyan-100"
            style="display: none;"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600">
                <i class="bi bi-check2-circle text-base"></i>
            </div>
            <span class="text-xs font-bold text-slate-800" x-text="toastMessage"></span>
        </div>
    </div>
</div>
