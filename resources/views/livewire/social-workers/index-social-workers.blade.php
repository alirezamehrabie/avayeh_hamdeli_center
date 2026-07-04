<div>
    {{-- resources/views/livewire/social-workers/index-social-workers.blade.php --}}
    <livewire:social-workers.transfer-households wire:key="social-worker-transfer-modal" />
    @php
        $socialWorkers = $this->socialWorkers;
        $searchNeedsMoreInput = $this->searchNeedsMoreInput();
        $searchFieldLabels = [
            'all' => 'همه فیلدها',
            'worker_code' => 'کد مددکاری',
            'full_name' => 'نام و نام خانوادگی',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'national_id' => 'کد ملی',
            'mobile' => 'موبایل',
        ];
    @endphp

    <div class="container mx-auto p-0">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">لیست مددکاران اجتماعی</h1>
                    <p class="mt-1 text-sm text-slate-500">مدیریت اطلاعات مددکاران، کد مددکاری و راه‌های ارتباطی</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-5 py-3">
                        <p class="text-xs font-semibold text-slate-500">تعداد مددکاران</p>
                        <div class="mt-1 flex items-center justify-center gap-3" dir="ltr">
                            <span class="text-xl font-extrabold tracking-tight iranyekan-bold" style="color: #1d9dcf;">{{ number_format($totalSocialWorkers) }}</span>
                        </div>
                    </div>

                @if($embedded)
                    <button type="button" wire:click="createSocialWorker" wire:loading.attr="disabled" wire:target="createSocialWorker" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:opacity-60" style="background-color: #53BEEA;">
                        <span wire:loading.remove wire:target="createSocialWorker">ثبت مددکار جدید</span>
                        <span wire:loading wire:target="createSocialWorker">در حال باز کردن...</span>
                    </button>
                @else
                    <a href="{{ route('social-workers.create') }}" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-cyan-100" style="background-color: #53BEEA;">
                        ثبت مددکار جدید
                    </a>
                @endif
                </div>
            </div>

            <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <label for="social-worker-search" class="block text-sm font-semibold text-slate-700">جستجوی سریع</label>
                    @if($search !== '')
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                            <span>فیلتر فعال: {{ $searchFieldLabels[$searchField] ?? 'همه فیلدها' }}</span>
                            <button type="button" wire:click="clearSearch" wire:loading.attr="disabled" wire:target="clearSearch" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60">
                                پاک کردن جستجو
                            </button>
                        </div>
                    @endif
                </div>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="social-worker-search-field"
                        wire:model.live="searchField"
                        wire:loading.attr="disabled"
                        wire:target="search,searchField"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                        aria-label="معیار جستجو"
                    >
                        <option value="all">همه فیلدها</option>
                        <option value="worker_code">کد مددکاری</option>
                        <option value="full_name">نام و نام خانوادگی</option>
                        <option value="first_name">نام</option>
                        <option value="last_name">نام خانوادگی</option>
                        <option value="national_id">کد ملی</option>
                        <option value="mobile">موبایل</option>
                    </select>

                    <div class="relative">
                        <input
                            id="social-worker-search"
                            type="text"
                            wire:model.live.debounce.600ms="search"
                            wire:loading.attr="disabled"
                            wire:target="search,searchField"
                            class="w-full rounded-lg border border-slate-300 bg-white px-10 py-3 text-sm text-slate-700 transition placeholder:text-slate-400 focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                            placeholder="عبارت جستجو را وارد کنید..."
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center" style="color: #53BEEA;">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-5.4a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                            </svg>
                        </span>
                    </div>
                </div>
                @if($searchNeedsMoreInput)
                    <div class="mt-2.5 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-800 sm:text-xs">
                        برای جستجوی متنی حداقل ۲ کاراکتر وارد کنید.
                    </div>
                @endif
                <div
                    wire:loading.flex
                    wire:target="search,searchField,clearSearch,previousPage,nextPage,gotoPage"
                    class="mt-2.5 items-center gap-2 rounded-lg border border-cyan-100 bg-cyan-50/70 px-3 py-2 text-[11px] font-semibold text-cyan-700 sm:text-xs"
                >
                    <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-600"></span>
                    در حال به‌روزرسانی لیست...
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            @if (session()->has('success'))
                <div class="mb-5 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="space-y-3 md:hidden">
                @forelse ($socialWorkers as $worker)
                    <article wire:key="social-worker-card-{{ $worker->id }}" class="relative overflow-visible rounded-lg border border-slate-200 bg-white">
                        <button
                            type="button"
                            wire:click="toggleSocialWorker({{ $worker->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleSocialWorker({{ $worker->id }})"
                            class="block w-full px-4 py-4 text-right transition hover:bg-cyan-50/50 focus:outline-none focus:ring-4 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-70"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</h2>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-500">
                                        <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-cyan-700" dir="ltr">{{ $worker->worker_code ?: '-' }}</span>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{{ $worker->national_id ?: '-' }}</span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-left">
                                    <p class="text-[10px] font-semibold text-slate-400">تحت پوشش</p>
                                    <p class="mt-0.5 text-xs font-extrabold text-slate-800">{{ $this->getCoveredCountForWorker($worker) }} نفر</p>
                                </div>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-2 text-right">
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <dt class="text-[10px] font-bold text-slate-400">موبایل</dt>
                                    <dd class="mt-1 truncate text-xs font-bold text-slate-700" dir="ltr">{{ $worker->mobile ?: '-' }}</dd>
                                </div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <dt class="text-[10px] font-bold text-slate-400">وضعیت</dt>
                                    <dd class="mt-1 text-xs font-bold text-slate-700">{{ $expandedSocialWorkerId === $worker->id ? 'باز شده' : 'بسته' }}</dd>
                                </div>
                            </dl>
                        </button>

                        <div class="border-t border-slate-100 bg-slate-50/70 px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <button type="button" wire:click.stop="toggleSocialWorker({{ $worker->id }})" wire:loading.attr="disabled" wire:target="toggleSocialWorker({{ $worker->id }})" class="inline-flex min-h-9 flex-1 items-center justify-center rounded-lg border border-cyan-200 bg-cyan-50 px-3 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60">
                                    <span wire:loading.remove wire:target="toggleSocialWorker({{ $worker->id }})">{{ $expandedSocialWorkerId === $worker->id ? 'بستن' : 'جزئیات' }}</span>
                                    <span wire:loading wire:target="toggleSocialWorker({{ $worker->id }})">...</span>
                                </button>

                                <div
                                    class="relative"
                                    x-data="{
                                        open: false,
                                        historyPushed: false,
                                        openSheet() {
                                            if (this.open) {
                                                return;
                                            }

                                            this.open = true;
                                            window.history.pushState({ socialWorkerActionSheet: {{ $worker->id }} }, '', window.location.href);
                                            this.historyPushed = true;
                                        },
                                        closeSheet(fromPopState = false) {
                                            if (! this.open) {
                                                return;
                                            }

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
                                        class="inline-flex min-h-9 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                        aria-label="عملیات مددکار"
                                        aria-haspopup="dialog"
                                        :aria-expanded="open.toString()"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75h.01M12 12h.01M12 17.25h.01"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        x-transition.opacity
                                        class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-[1px]"
                                        style="display: none;"
                                        @click="closeSheet()"
                                        aria-hidden="true"
                                    ></div>

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
                                            <button type="button" @click="closeSheet()" wire:click.stop="editSocialWorker({{ $worker->id }})" wire:loading.attr="disabled" wire:target="editSocialWorker({{ $worker->id }})" class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-cyan-100 bg-cyan-50/70 px-4 py-3 text-right text-sm font-bold text-cyan-800 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60">
                                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM19.5 7.125 16.875 4.5" />
                                                </svg>
                                                <span wire:loading.remove wire:target="editSocialWorker({{ $worker->id }})">ویرایش</span>
                                                <span wire:loading wire:target="editSocialWorker({{ $worker->id }})">در حال باز کردن...</span>
                                            </button>
                                        @else
                                            <a href="{{ route('social-workers.edit', $worker) }}" @click.stop="closeSheet()" class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-cyan-100 bg-cyan-50/70 px-4 py-3 text-right text-sm font-bold text-cyan-800 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM19.5 7.125 16.875 4.5" />
                                                </svg>
                                                ویرایش
                                            </a>
                                        @endif
                                        <button type="button" @click="closeSheet()" wire:click.stop="transferHouseholds({{ $worker->id }})" wire:loading.attr="disabled" wire:target="transferHouseholds({{ $worker->id }})" class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-cyan-100 bg-cyan-50/70 px-4 py-3 text-right text-sm font-bold text-cyan-800 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60">
                                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                            </svg>
                                            <span wire:loading.remove wire:target="transferHouseholds({{ $worker->id }})">انتقال خانوارها</span>
                                            <span wire:loading wire:target="transferHouseholds({{ $worker->id }})">در حال باز کردن...</span>
                                        </button>
                                        <button type="button" @click="closeSheet()" wire:click.stop="deleteSocialWorker({{ $worker->id }})" wire:confirm="آیا از غیرفعال‌سازی این مددکار مطمئن هستید؟" wire:loading.attr="disabled" wire:target="deleteSocialWorker({{ $worker->id }})" class="flex min-h-12 w-full items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50/70 px-4 py-3 text-right text-sm font-bold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-100 disabled:cursor-wait disabled:opacity-60">
                                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.956L4.772 5.79m14.456 0A48.108 48.108 0 0 0 12 5.25c-2.43 0-4.817.18-7.228.54m14.456 0L18.16 19.673M4.772 5.79c.34-.059.68-.114 1.022-.166m0 0A48.11 48.11 0 0 1 12 5.25m-6.206.374L5.25 4.5A2.25 2.25 0 0 1 7.5 2.25h9A2.25 2.25 0 0 1 18.75 4.5l-.544 1.124" />
                                            </svg>
                                            <span wire:loading.remove wire:target="deleteSocialWorker({{ $worker->id }})">غیرفعال</span>
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
                            <div class="space-y-3 border-t border-slate-100 bg-slate-50 p-3" wire:init="loadCoveredDetailsForWorker({{ $worker->id }})" x-data="{ activePanel: 'guardians' }">
                                <div class="grid grid-cols-2 gap-2 rounded-lg border border-slate-200 bg-white p-1">
                                    <button type="button" @click="activePanel = 'guardians'" :class="activePanel === 'guardians' ? 'bg-cyan-50 text-cyan-700' : 'text-slate-500 hover:bg-slate-50'" class="rounded-md px-3 py-2 text-xs font-extrabold transition">
                                        سرپرستان
                                    </button>
                                    <button type="button" @click="activePanel = 'coverage'" :class="activePanel === 'coverage' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:bg-slate-50'" class="rounded-md px-3 py-2 text-xs font-extrabold transition">
                                        آمار تحت پوشش
                                    </button>
                                </div>

                                <section x-show="activePanel === 'guardians'" class="rounded-lg border border-slate-200 bg-white p-3">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h3 class="text-xs font-extrabold text-slate-700">سرپرستان تحت پوشش</h3>
                                        <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[10px] font-bold text-cyan-700">{{ count($guardians) }} سرپرست</span>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse($visibleGuardians as $guardian)
                                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="min-w-0 truncate text-xs font-extrabold text-slate-800">{{ trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')) ?: '-' }}</p>
                                                    <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $guardian['people_count'] }} نفر</span>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-bold text-slate-500">
                                                    <span class="rounded-full bg-white px-2 py-0.5" dir="ltr">{{ $guardian['national_code'] ?: '-' }}</span>
                                                    <span class="rounded-full bg-white px-2 py-0.5" dir="ltr">{{ $guardian['guardian_phone_number'] ?: '-' }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="rounded-lg bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">سرپرستی برای این مددکار ثبت نشده است.</p>
                                        @endforelse
                                        @if(count($guardians) > $guardianLimit)
                                            <button type="button" wire:click="showMoreGuardians({{ $worker->id }})" wire:loading.attr="disabled" wire:target="showMoreGuardians({{ $worker->id }})" class="w-full rounded-lg border border-cyan-100 bg-cyan-50 px-3 py-2 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60">
                                                <span wire:loading.remove wire:target="showMoreGuardians({{ $worker->id }})">نمایش سرپرستان بیشتر ({{ count($guardians) - $guardianLimit }})</span>
                                                <span wire:loading wire:target="showMoreGuardians({{ $worker->id }})">در حال نمایش...</span>
                                            </button>
                                        @endif
                                    </div>
                                </section>

                                <section x-show="activePanel === 'coverage'" class="rounded-lg border border-slate-200 bg-white p-3">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h3 class="text-xs font-extrabold text-slate-700">جزئیات آمار تحت پوشش</h3>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-700">{{ $this->getCoveredCountForWorker($worker) }} نفر</span>
                                    </div>

                                    @if(! $coveredDetailsLoaded)
                                        <p class="rounded-lg bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">در حال بارگذاری جزئیات...</p>
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
                                                    <p class="rounded-lg bg-cyan-50 px-3 py-2 text-[10px] font-extrabold text-cyan-800">سرپرست مشترک: {{ $currentGuardianGroup }}</p>
                                                @endif
                                                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="min-w-0 truncate text-xs font-extrabold text-slate-800">{{ $detail['name'] ?: '-' }}</p>
                                                        <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700">{{ $detail['role_label'] }}</span>
                                                    </div>
                                                    <p class="mt-1 text-[10px] font-bold text-slate-500" dir="ltr">{{ $detail['national_id'] }}</p>
                                                    <p class="mt-2 text-[10px] leading-5 text-slate-600">{{ implode('، ', $detail['sources']) }}</p>
                                                </div>
                                            @empty
                                                <p class="rounded-lg bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">موردی برای نمایش ثبت نشده است.</p>
                                            @endforelse
                                            @if(count($coveredDetails) > $coveredDetailLimit)
                                                <button type="button" wire:click="showMoreCoveredDetails({{ $worker->id }})" wire:loading.attr="disabled" wire:target="showMoreCoveredDetails({{ $worker->id }})" class="w-full rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:cursor-wait disabled:opacity-60">
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
                @empty
                    <div class="rounded-lg border border-slate-200 bg-white px-5 py-8 text-center text-sm font-bold text-slate-500">
                        @if($search)
                            نتیجه‌ای برای این جستجو پیدا نشد.
                        @else
                            هنوز مددکاری ثبت نشده است.
                        @endif
                    </div>
                @endforelse
            </div>

            <div class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-5 py-4 text-center font-bold">کد مددکاری</th>
                            <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                            <th class="px-5 py-4 text-center font-bold">کد ملی</th>
                            <th class="px-5 py-4 text-center font-bold">موبایل</th>
                            <th class="px-5 py-4 text-center font-bold">آمار تحت پوشش</th>
                            <th class="px-5 py-4 text-center font-bold">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($socialWorkers as $worker)
                            <tr
                                wire:key="social-worker-{{ $worker->id }}"
                                class="transition hover:bg-slate-50"
                            >
                                <td class="px-5 py-4 text-center">
                                    <span class="font-bold text-slate-800" dir="ltr">{{ $worker->worker_code ?: '-' }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="font-bold text-slate-900">{{ $worker->full_name ?: 'بدون نام' }}</div>
                                </td>
                                <td class="px-5 py-4 text-center font-medium text-slate-600" dir="ltr">{{ $worker->national_id ?: '-' }}</td>
                                <td class="px-5 py-4 text-center font-medium text-slate-600" dir="ltr">{{ $worker->mobile ?: '-' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="font-bold text-slate-800">{{ $this->getCoveredCountForWorker($worker) }}</span>
                                    <span class="text-xs text-slate-500">نفر</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="toggleSocialWorker({{ $worker->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleSocialWorker({{ $worker->id }})"
                                            x-data="{ hovered: false }"
                                            @mouseenter="hovered = true"
                                            @mouseleave="hovered = false"
                                            :class="hovered && !$wire.loading ? 'scale-105' : ''"
                                            class="group relative inline-flex items-center gap-2 rounded-xl border border-cyan-200/80 bg-gradient-to-br from-cyan-50 to-cyan-100/50 px-4 py-2.5 text-xs font-semibold text-cyan-700 transition-all duration-300 ease-out hover:border-cyan-300 hover:from-cyan-100 hover:to-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-300 focus:ring-offset-1 disabled:cursor-wait disabled:opacity-60 disabled:scale-100"
                                        >
                                            @if($expandedSocialWorkerId === $worker->id)
                                                <svg class="h-5 w-5 shrink-0 transition-all duration-500 ease-out group-hover:text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                                </svg>
                                            @else
                                                <svg class="h-5 w-5 shrink-0 transition-all duration-500 ease-out group-hover:text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                                </svg>
                                            @endif
                                            <span wire:loading.remove wire:target="toggleSocialWorker({{ $worker->id }})" class="transition-colors duration-300 group-hover:text-cyan-800">
                                                {{ $expandedSocialWorkerId === $worker->id ? 'بستن' : 'جزئیات' }}
                                            </span>
                                            <span wire:loading wire:target="toggleSocialWorker({{ $worker->id }})" class="inline-flex items-center gap-1">
                                                <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-cyan-600"></span>
                                            </span>
                                        </button>
                                        <div class="relative" x-data="{ open: false }">
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                                aria-label="عملیات مددکار"
                                                aria-haspopup="dialog"
                                                :aria-expanded="open.toString()"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75h.01M12 12h.01M12 17.25h.01"/>
                                                </svg>
                                            </button>

                                            <div
                                                x-show="open"
                                                x-transition.opacity
                                                @click="open = false"
                                                class="fixed inset-0 z-30"
                                                style="display: none;"
                                                aria-hidden="true"
                                            ></div>

                                            <div
                                                x-show="open"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                @click.stop
                                                class="absolute left-0 top-full z-40 mt-1 min-w-48 rounded-lg border border-slate-200 bg-white shadow-lg"
                                                style="display: none;"
                                                role="menu"
                                            >
                                                @if($embedded)
                                                    <button
                                                        type="button"
                                                        wire:click.stop="editSocialWorker({{ $worker->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="editSocialWorker({{ $worker->id }})"
                                                        @click="open = false"
                                                        class="flex w-full items-center gap-3 px-4 py-3 text-right text-sm font-bold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 first:rounded-t-lg disabled:opacity-60"
                                                        role="menuitem"
                                                    >
                                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM19.5 7.125 16.875 4.5" />
                                                        </svg>
                                                        <span wire:loading.remove wire:target="editSocialWorker({{ $worker->id }})">ویرایش</span>
                                                        <span wire:loading wire:target="editSocialWorker({{ $worker->id }})">...</span>
                                                    </button>
                                                @else
                                                    <a
                                                        href="{{ route('social-workers.edit', $worker) }}"
                                                        wire:click.stop
                                                        @click="open = false"
                                                        class="flex w-full items-center gap-3 px-4 py-3 text-right text-sm font-bold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 first:rounded-t-lg"
                                                        role="menuitem"
                                                    >
                                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM19.5 7.125 16.875 4.5" />
                                                        </svg>
                                                        ویرایش
                                                    </a>
                                                @endif
                                                <button
                                                    type="button"
                                                    wire:click.stop="transferHouseholds({{ $worker->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="transferHouseholds({{ $worker->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-3 px-4 py-3 text-right text-sm font-bold text-cyan-800 transition hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:opacity-60"
                                                    role="menuitem"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                                    </svg>
                                                    <span wire:loading.remove wire:target="transferHouseholds({{ $worker->id }})">انتقال خانوارها</span>
                                                    <span wire:loading wire:target="transferHouseholds({{ $worker->id }})">...</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click.stop="deleteSocialWorker({{ $worker->id }})"
                                                    wire:confirm="آیا از غیرفعال‌سازی این مددکار مطمئن هستید؟"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteSocialWorker({{ $worker->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-3 px-4 py-3 text-right text-sm font-bold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-100 last:rounded-b-lg disabled:opacity-60"
                                                    role="menuitem"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.956L4.772 5.79m14.456 0A48.108 48.108 0 0 0 12 5.25c-2.43 0-4.817.18-7.228.54m14.456 0L18.16 19.673M4.772 5.79c.34-.059.68-.114 1.022-.166m0 0A48.11 48.11 0 0 1 12 5.25m-6.206.374L5.25 4.5A2.25 2.25 0 0 1 7.5 2.25h9A2.25 2.25 0 0 1 18.75 4.5l-.544 1.124" />
                                                    </svg>
                                                    <span wire:loading.remove wire:target="deleteSocialWorker({{ $worker->id }})">غیرفعال‌سازی</span>
                                                    <span wire:loading wire:target="deleteSocialWorker({{ $worker->id }})">...</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
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
                                <tr class="bg-slate-50" wire:key="social-worker-panel-{{ $worker->id }}">
                                    <td colspan="6" class="px-5 py-4">
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
                                            class="rounded-lg border border-slate-200 bg-white p-4"
                                            wire:init="loadCoveredDetailsForWorker({{ $worker->id }})"
                                        >
                                            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                <h2 class="text-sm font-bold text-slate-700">جزئیات {{ $worker->full_name ?: 'بدون نام' }}</h2>
                                                <div class="inline-grid grid-cols-2 gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
                                                    <button type="button" @click="activePanel = 'guardians'" :class="activePanel === 'guardians' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-md px-4 py-2 text-xs font-bold transition">
                                                        سرپرستان ({{ count($guardians) }})
                                                    </button>
                                                    <button type="button" @click="activePanel = 'coverage'" :class="activePanel === 'coverage' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-md px-4 py-2 text-xs font-bold transition">
                                                        آمار تحت پوشش ({{ $this->getCoveredCountForWorker($worker) }})
                                                    </button>
                                                </div>
                                            </div>

                                            <div x-show="activePanel === 'guardians'" class="overflow-x-auto">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-50 text-slate-600">
                                                        <tr>
                                                            <th class="px-4 py-3 text-center font-bold">ردیف</th>
                                                            <th class="px-4 py-3 text-center font-bold">کد ملی سرپرست</th>
                                                            <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی سرپرست</th>
                                                            <th class="px-4 py-3 text-center font-bold">موبایل سرپرست</th>
                                                            <th class="px-4 py-3 text-center font-bold">تعداد مددجویان تحت سرپرستی</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @forelse ($visibleGuardians as $guardian)
                                                            <tr class="transition hover:bg-slate-50">
                                                                <td class="px-4 py-3 text-center font-medium text-slate-700">{{ $loop->iteration }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-600" dir="ltr">{{ $guardian['national_code'] ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-right text-slate-700">{{ trim(($guardian['first_name'] ?? '') . ' ' . ($guardian['last_name'] ?? '')) ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-600" dir="ltr">{{ $guardian['guardian_phone_number'] ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-600">{{ $guardian['people_count'] }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">سرپرستی برای این مددکار ثبت نشده است.</td>
                                                            </tr>
                                                        @endforelse
                                                        @if(count($guardians) > $guardianLimit)
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-3 text-center">
                                                                    <button type="button" wire:click="showMoreGuardians({{ $worker->id }})" wire:loading.attr="disabled" wire:target="showMoreGuardians({{ $worker->id }})" class="inline-flex items-center justify-center rounded-lg border border-cyan-100 bg-cyan-50 px-4 py-2 text-xs font-bold text-cyan-700 transition hover:bg-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-100 disabled:cursor-wait disabled:opacity-60">
                                                                        <span wire:loading.remove wire:target="showMoreGuardians({{ $worker->id }})">نمایش سرپرستان بیشتر ({{ count($guardians) - $guardianLimit }})</span>
                                                                        <span wire:loading wire:target="showMoreGuardians({{ $worker->id }})">در حال نمایش...</span>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div x-show="activePanel === 'coverage'" class="mt-1">
                                            <div class="mb-3 flex items-center justify-between">
                                                <h2 class="text-sm font-bold text-slate-700">جزئیات افراد مؤثر در آمار تحت پوشش</h2>
                                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    {{ $this->getCoveredCountForWorker($worker) }} نفر (بر پایه کد ملی)
                                                </span>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-50 text-slate-600">
                                                        <tr>
                                                            <th class="px-4 py-3 text-center font-bold">ردیف</th>
                                                            <th class="px-4 py-3 text-center font-bold">کد ملی</th>
                                                            <th class="px-4 py-3 text-right font-bold">نام</th>
                                                            <th class="px-4 py-3 text-center font-bold">دسته</th>
                                                            <th class="px-4 py-3 text-right font-bold">منبع ثبت</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @if(! $coveredDetailsLoaded)
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
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
                                                                <tr class="bg-cyan-50/70">
                                                                    <td colspan="5" class="px-4 py-2.5 text-right text-[11px] font-bold text-cyan-800">
                                                                        سرپرست مشترک: {{ $currentGuardianGroup }}
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                            <tr class="transition hover:bg-slate-50">
                                                                <td class="px-4 py-3 text-center font-medium text-slate-700">{{ $loop->iteration }}</td>
                                                                <td class="px-4 py-3 text-center text-slate-700" dir="ltr">{{ $detail['national_id'] }}</td>
                                                                <td class="px-4 py-3 text-right text-slate-700">{{ $detail['name'] ?: '-' }}</td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                                                        {{ $detail['role_label'] }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-right text-slate-600">{{ implode('، ', $detail['sources']) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                                                    موردی برای نمایش ثبت نشده است.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                        @if(count($coveredDetails) > $coveredDetailLimit)
                                                            <tr>
                                                                <td colspan="5" class="px-4 py-3 text-center">
                                                                    <button type="button" wire:click="showMoreCoveredDetails({{ $worker->id }})" wire:loading.attr="disabled" wire:target="showMoreCoveredDetails({{ $worker->id }})" class="inline-flex items-center justify-center rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:cursor-wait disabled:opacity-60">
                                                                        <span wire:loading.remove wire:target="showMoreCoveredDetails({{ $worker->id }})">نمایش جزئیات بیشتر ({{ count($coveredDetails) - $coveredDetailLimit }})</span>
                                                                        <span wire:loading wire:target="showMoreCoveredDetails({{ $worker->id }})">در حال نمایش...</span>
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
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                    @if($search)
                                        نتیجه‌ای برای این جستجو پیدا نشد.
                                    @else
                                        هنوز مددکاری ثبت نشده است.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $socialWorkers->links('vendor.livewire.tailwind-mobile-persian') }}
            </div>
        </div>
    </div>
</div>
