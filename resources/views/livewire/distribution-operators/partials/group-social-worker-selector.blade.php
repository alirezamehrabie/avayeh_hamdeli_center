@php
    $groupIndex = $groupIndex ?? 0;
    $group = $group ?? [];
    $selectorId = 'group-social-worker-selector-' . $groupIndex;
    $groupWorkerId = (int) ($group['social_worker_id'] ?? 0);
    $groupWorkerCode = (string) ($group['worker_code'] ?? '');
    $groupWorkerDisplay = (string) ($group['worker_display'] ?? '');
    $groupWorkerSearch = (string) ($group['worker_search'] ?? '');
    $accentClasses = [
        'hoverBorder' => 'hover:border-emerald-300 hover:bg-emerald-50/50 focus:border-emerald-500 focus:ring-emerald-500/10',
        'focus' => 'focus:border-emerald-500 focus:ring-emerald-500/10',
        'activeCard' => 'border-emerald-300 bg-emerald-50/80',
        'hoverCard' => 'hover:border-emerald-200 hover:bg-emerald-50/60 active:bg-emerald-50 focus:ring-emerald-500/10',
        'meta' => 'text-emerald-700',
        'icon' => 'text-emerald-600',
    ];
@endphp

<div wire:key="{{ $selectorId }}-root">
    <label class="mb-2 block text-sm font-bold text-slate-700">مددکار</label>
    <div
        class="relative"
        x-data="{
            open: false,
            historyActive: false,
            popStateHandler: null,
            mediaQuery: null,
            mediaQueryHandler: null,
            viewportSyncHandler: null,
            scrollLocked: false,
            viewportHeight: window.innerHeight,
            viewportOffsetTop: 0,
            search: @entangle('miscWorkerGroups.' . $groupIndex . '.worker_search').live,
            get isMobileSheet() {
                return this.mediaQuery?.matches ?? window.matchMedia('(max-width: 639px)').matches;
            },
            get keyboardInset() {
                return Math.max(0, window.innerHeight - (this.viewportHeight + this.viewportOffsetTop));
            },
            get mobileSheetStyle() {
                if (! this.isMobileSheet) {
                    return '';
                }
                const maxHeight = Math.max(280, this.viewportHeight - 16);
                const minHeight = Math.min(maxHeight, Math.max(240, this.viewportHeight * 0.45));
                return `transform: translateY(-${this.keyboardInset}px); max-height: ${maxHeight}px; min-height: ${minHeight}px;`;
            },
            init() {
                this.mediaQuery = window.matchMedia('(max-width: 639px)');
                this.popStateHandler = () => this.handleSelectorPopState();
                this.mediaQueryHandler = () => this.syncPageScrollLock(this.open);
                this.viewportSyncHandler = () => this.syncViewport();

                window.addEventListener('popstate', this.popStateHandler);
                window.addEventListener('resize', this.viewportSyncHandler);
                if (this.mediaQuery.addEventListener) {
                    this.mediaQuery.addEventListener('change', this.mediaQueryHandler);
                } else {
                    this.mediaQuery.addListener?.(this.mediaQueryHandler);
                }

                if (window.visualViewport) {
                    window.visualViewport.addEventListener('resize', this.viewportSyncHandler);
                    window.visualViewport.addEventListener('scroll', this.viewportSyncHandler);
                }

                this.syncViewport();

                this.$watch('open', (value) => this.syncPageScrollLock(value));
            },
            syncViewport() {
                if (window.visualViewport) {
                    this.viewportHeight = window.visualViewport.height;
                    this.viewportOffsetTop = window.visualViewport.offsetTop;
                    return;
                }
                this.viewportHeight = window.innerHeight;
                this.viewportOffsetTop = 0;
            },
            destroy() {
                if (this.popStateHandler) {
                    window.removeEventListener('popstate', this.popStateHandler);
                }

                if (this.viewportSyncHandler) {
                    window.removeEventListener('resize', this.viewportSyncHandler);
                }

                if (this.mediaQueryHandler) {
                    if (this.mediaQuery?.removeEventListener) {
                        this.mediaQuery.removeEventListener('change', this.mediaQueryHandler);
                    } else {
                        this.mediaQuery?.removeListener?.(this.mediaQueryHandler);
                    }
                }

                if (window.visualViewport && this.viewportSyncHandler) {
                    window.visualViewport.removeEventListener('resize', this.viewportSyncHandler);
                    window.visualViewport.removeEventListener('scroll', this.viewportSyncHandler);
                }

                this.releasePageScrollLock();
                this.historyActive = false;
            },
            syncPageScrollLock(shouldLock) {
                if (shouldLock && this.isMobileSheet) {
                    this.lockPageScroll();
                    return;
                }

                this.releasePageScrollLock();
            },
            lockPageScroll() {
                if (this.scrollLocked) {
                    return;
                }

                const currentLocks = Number(document.documentElement.dataset.distributionOperatorSheetLocks || 0);

                document.documentElement.dataset.distributionOperatorSheetLocks = String(currentLocks + 1);
                document.documentElement.classList.add('overflow-hidden');
                this.scrollLocked = true;
            },
            releasePageScrollLock() {
                if (! this.scrollLocked) {
                    return;
                }

                const currentLocks = Number(document.documentElement.dataset.distributionOperatorSheetLocks || 0);
                const remainingLocks = Math.max(0, currentLocks - 1);

                if (remainingLocks > 0) {
                    document.documentElement.dataset.distributionOperatorSheetLocks = String(remainingLocks);
                } else {
                    delete document.documentElement.dataset.distributionOperatorSheetLocks;
                    document.documentElement.classList.remove('overflow-hidden');
                }

                this.scrollLocked = false;
            },
            openSelector() {
                this.open = true;
                this.syncViewport();
                $wire.openGroupWorkerSearch({{ $groupIndex }});
                this.focusWorkerSearch();
                this.pushSelectorHistory();
            },
            autoOpenForNewGroup(event) {
                // Direct-trigger flow: when this exact group was just added and
                // no worker is picked yet, open the selector so the operator lands
                // straight in search (bottom sheet on mobile, dropdown on desktop).
                if (event.detail?.index !== {{ $groupIndex }} || this.open) {
                    return;
                }

                if (@js((bool) $groupWorkerId)) {
                    return;
                }

                this.$nextTick(() => this.openSelector());
            },
            focusWorkerSearch() {
                if (this.isMobileSheet) {
                    return;
                }
                this.$nextTick(() => {
                    this.$refs.workerSearch?.focus({ preventScroll: true });
                });
            },
            closeSelector({ syncHistory = true } = {}) {
                const shouldRestoreHistory = syncHistory && this.historyActive;
                this.open = false;

                if (shouldRestoreHistory) {
                    this.historyActive = false;
                    window.history.back();
                } else if (! syncHistory) {
                    this.historyActive = false;
                }
            },
            toggleSelector() {
                this.open ? this.closeSelector() : this.openSelector();
            },
            pushSelectorHistory() {
                if (! this.isMobileSheet || this.historyActive) {
                    return;
                }

                try {
                    window.history.pushState({
                        ...(window.history.state || {}),
                        distributionOperatorSocialWorkerSelector: '{{ $selectorId }}',
                    }, '', window.location.href);
                    this.historyActive = true;
                } catch (error) {
                    this.historyActive = false;
                }
            },
            handleSelectorPopState() {
                if (! this.open) {
                    this.historyActive = false;
                    return;
                }

                this.closeSelector({ syncHistory: false });
            },
            choose(workerId) {
                this.closeSelector();
                // Keep this group as the single expanded section; siblings collapse.
                window.dispatchEvent(new CustomEvent('worker-group-expand', { detail: { index: {{ $groupIndex }} } }));
                $wire.selectGroupWorker({{ $groupIndex }}, Number(workerId));
            },
            clear() {
                this.search = '';
                this.closeSelector();
                $wire.clearGroupWorker({{ $groupIndex }});
            },
        }"
        x-on:keydown.escape.window="closeSelector()"
        x-on:worker-group-added.window="autoOpenForNewGroup($event)"
    >
        <button
            type="button"
            @click="toggleSelector()"
            :aria-expanded="open.toString()"
            aria-haspopup="dialog"
            class="flex min-h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white py-3 ps-4 {{ $groupWorkerId ? 'pe-16' : 'pe-4' }} text-right shadow-sm transition focus:outline-none focus:ring-4 {{ $accentClasses['hoverBorder'] }}"
        >
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[10px] font-medium text-slate-400">
                    {{ $groupWorkerId ? 'کد پرسنلی: ' . $groupWorkerCode : 'نام یا کد مددکار را جستجو کنید' }}
                </span>
                @if($groupWorkerId)
                    <span class="mt-1 block truncate text-sm font-bold text-slate-900">
                        {{ $groupWorkerDisplay }}
                    </span>
                @else
                    <span class="mt-1 block truncate text-xs font-semibold text-slate-500">
                        جستجو بر اساس نام، کد، منطقه یا موبایل
                    </span>
                @endif
            </span>
            @if(! $groupWorkerId)
                <span class="ms-3 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                    <i class="bi bi-search text-sm"></i>
                </span>
            @endif
        </button>

        @if($groupWorkerId)
            <button
                type="button"
                @click.stop="clear()"
                class="absolute left-2 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                aria-label="پاک کردن مددکار"
            >
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        @endif

        <div
            x-show="open"
            x-transition.opacity.duration.150ms
            @click="closeSelector()"
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm sm:hidden"
            style="display: none;"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-80 sm:-translate-y-2 sm:opacity-0"
            @click.outside="closeSelector()"
            :style="isMobileSheet ? mobileSheetStyle : ''"
            class="fixed inset-x-0 bottom-0 z-50 flex max-h-[86svh] min-h-[48svh] flex-col rounded-t-3xl border border-slate-200 bg-slate-50 p-3 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] transition-[transform,max-height] duration-100 ease-out sm:absolute sm:inset-auto sm:mt-2 sm:min-h-0 sm:max-h-[30rem] sm:w-full sm:rounded-2xl sm:p-2 sm:shadow-xl sm:transition-none"
            dir="rtl"
            role="dialog"
            aria-modal="true"
            aria-label="انتخاب مددکار"
            style="display: none;"
        >
            <!-- Mobile drag handle pill -->
            <div class="mx-auto mb-2.5 h-1.5 w-12 shrink-0 rounded-full bg-slate-300 sm:hidden" aria-hidden="true"></div>

            <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-200/70 pb-3 sm:hidden">
                <div class="min-w-0">
                    <h3 class="text-sm font-black text-slate-800">انتخاب مددکار</h3>
                    <p class="mt-1 truncate text-xs text-slate-500">
                        {{ $socialWorkerSuggestions->isNotEmpty() ? $socialWorkerSuggestions->count() . ' مددکار پیدا شد' : 'نام، کد یا موبایل را وارد کنید' }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeSelector()"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200 active:scale-95"
                    aria-label="بستن انتخاب مددکار"
                >
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <div wire:ignore.self class="sticky top-0 z-10 bg-slate-50 pb-2">
                <div class="relative">
                    <input
                        type="search"
                        x-ref="workerSearch"
                        x-model.debounce.250ms="search"
                        inputmode="search"
                        enterkeyhint="search"
                        @focus="$wire.openGroupWorkerSearch({{ $groupIndex }})"
                        class="min-h-12 w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-3 text-sm text-slate-700 shadow-sm transition placeholder:text-xs placeholder:text-slate-400 focus:outline-none focus:ring-4 {{ $accentClasses['focus'] }}"
                        placeholder="نام، کد مددکار یا موبایل"
                        autocomplete="off"
                    >
                    <!-- Loading Indicator -->
                    <span
                        wire:loading.delay
                        wire:target="miscWorkerGroups.{{ $groupIndex }}.worker_search, openGroupWorkerSearch"
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 {{ $accentClasses['icon'] }}"
                    >
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </span>
                    <!-- Clear search button -->
                    <button
                        type="button"
                        x-show="search && search.length > 0"
                        wire:loading.remove
                        wire:target="miscWorkerGroups.{{ $groupIndex }}.worker_search, openGroupWorkerSearch"
                        @click="search = ''; $refs.workerSearch?.focus()"
                        class="absolute left-2.5 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-300 active:scale-95"
                        aria-label="پاک کردن متن جستجو"
                    >
                        <i class="bi bi-x text-sm leading-none"></i>
                    </button>
                    <!-- Search Icon (when idle and empty) -->
                    <i
                        x-show="! search || search.length === 0"
                        wire:loading.remove
                        wire:target="miscWorkerGroups.{{ $groupIndex }}.worker_search, openGroupWorkerSearch"
                        class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                    ></i>
                </div>
            </div>

            <div class="min-h-0 flex-1 space-y-1.5 overflow-y-auto overscroll-contain pb-[calc(env(safe-area-inset-bottom)+0.5rem)] sm:pb-0">
                @if($socialWorkerSuggestions->isNotEmpty())
                    @foreach($socialWorkerSuggestions as $worker)
                        <button
                            type="button"
                            @if(!($worker['duplicate'] ?? false))
                                @click="choose({{ (int) $worker['id'] }})"
                            @endif
                            @disabled($worker['duplicate'] ?? false)
                            aria-disabled="{{ ($worker['duplicate'] ?? false) ? 'true' : 'false' }}"
                            class="group flex w-full items-center gap-2.5 rounded-xl border px-3 py-2.5 text-right transition focus:outline-none focus:ring-4 {{ ($worker['duplicate'] ?? false) ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-80 disabled:pointer-events-none' : (($groupWorkerId === (int) $worker['id'] ? $accentClasses['activeCard'] : 'border-slate-200 bg-white') . ' ' . $accentClasses['hoverCard']) }}"
                        >
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ ($worker['duplicate'] ?? false) ? 'bg-white text-slate-400' : 'bg-slate-50 ' . $accentClasses['icon'] }}">
                                <i class="bi {{ ($worker['duplicate'] ?? false) ? 'bi-person-dash' : 'bi-person-badge' }} text-sm"></i>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="min-w-0 flex-1 truncate text-sm font-black leading-5 {{ ($worker['duplicate'] ?? false) ? 'text-slate-500' : 'text-slate-900' }}">{{ $worker['name'] }}</span>
                                    <span class="shrink-0 text-[11px] font-bold text-slate-400">کد {{ $worker['code'] }}</span>
                                </span>

                                <span class="mt-1 flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-bold leading-5 text-slate-500">
                                    <span class="truncate {{ ($worker['duplicate'] ?? false) ? 'text-slate-400' : $accentClasses['meta'] }}">{{ $worker['district'] }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="truncate dir-ltr">{{ $worker['mobile'] }}</span>
                                    @if($worker['duplicate'] ?? false)
                                        <span class="w-full rounded-full bg-white px-2 py-0.5 text-[10px] font-black text-slate-500 ring-1 ring-slate-200 sm:w-auto">
                                            قبلاً برای این خدمت انتخاب شده است
                                        </span>
                                    @endif
                                </span>
                            </span>
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ ($worker['duplicate'] ?? false) ? 'border-slate-200 bg-white text-slate-400' : ($groupWorkerId === (int) $worker['id'] ? 'border-current bg-white/70 ' . $accentClasses['icon'] : 'border-slate-200 text-slate-300 group-hover:border-slate-300 group-hover:text-slate-400') }}">
                                <i class="bi {{ ($worker['duplicate'] ?? false) ? 'bi-lock' : ($groupWorkerId === (int) $worker['id'] ? 'bi-check-lg' : 'bi-chevron-left') }} text-[10px]"></i>
                            </span>
                        </button>
                    @endforeach
                @elseif(mb_strlen(trim($groupWorkerSearch)) >= 2)
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-7 text-center">
                        <i class="bi bi-search text-xl text-slate-300"></i>
                        <p class="mt-2 text-xs font-black text-slate-500">مددکاری با این جستجو پیدا نشد.</p>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-7 text-center">
                        <i class="bi bi-person-lines-fill text-xl text-slate-300"></i>
                        <p class="mt-2 text-xs font-black text-slate-500">برای جستجو حداقل دو کاراکتر وارد کنید.</p>
                    </div>
                @endif
            </div>

            <div class="mt-2 flex items-center justify-between gap-2 border-t border-slate-200/70 pt-2">
                @if($groupWorkerId)
                    <button
                        type="button"
                        @click="clear()"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                    >
                        پاک کردن انتخاب
                    </button>
                @endif
                <span class="ms-auto text-[11px] font-bold text-slate-400">حداکثر ۱۰ نتیجه نمایش داده می‌شود</span>
            </div>
        </div>
    </div>
    @error("miscWorkerGroups.$groupIndex.social_worker_id") <p data-validation-error class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>
