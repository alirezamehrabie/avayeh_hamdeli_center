@php
    $groupIndex = $groupIndex ?? 0;
    $group = $group ?? [];
    $selectorId = 'predefined-group-social-worker-selector-' . $groupIndex;
    $groupWorkerId = (int) ($group['social_worker_id'] ?? 0);
    $groupWorkerCode = (string) ($group['worker_code'] ?? '');
    $groupWorkerDisplay = (string) ($group['worker_display'] ?? '');
    $groupWorkerSearch = (string) ($group['worker_search'] ?? '');
    // Animated red gradient border cues the user that picking the first social
    // worker is the required next action; it clears once that worker is chosen.
    $guideSelection = $groupIndex === 0 && $groupWorkerId === 0;
    $accentClasses = [
        'hoverBorder' => 'hover:border-cyan-300 hover:bg-cyan-50/50 focus:border-cyan-500 focus:ring-cyan-500/10',
        'focus' => 'focus:border-cyan-500 focus:ring-cyan-500/10',
        'activeCard' => 'border-cyan-300 bg-cyan-50/80',
        'hoverCard' => 'hover:border-cyan-200 hover:bg-cyan-50/60 active:bg-cyan-50 focus:ring-cyan-500/10',
        'meta' => 'text-cyan-700',
        'icon' => 'text-cyan-600',
    ];
@endphp

@once
    <style>
        @keyframes distributionOperatorGuideBorder {
            0% { background-position: 0 0, 0% 50%; }
            100% { background-position: 0 0, 300% 50%; }
        }
        .distribution-operator-guide-border {
            border: 2px solid transparent !important;
            background:
                linear-gradient(#ffffff, #ffffff) padding-box,
                linear-gradient(90deg, #ea2e34, #ff9eac, #ffe0e3, #fb7185, #f43f5e) border-box;
            background-size: 100% 100%, 300% 100%;
            animation: distributionOperatorGuideBorder 2.0s linear infinite;
        }
        @keyframes distributionOperatorGuideZoom {
            0% { transform: scale(0.96); }
            55% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
        .distribution-operator-guide-zoom {
            /* One-shot reveal so the control draws the eye without a distracting
               continuous pulse; transform-only keeps it off the layout flow. */
            animation: distributionOperatorGuideZoom 0.6s ease-out 1;
            transform-origin: center;
        }
        @media (prefers-reduced-motion: reduce) {
            .distribution-operator-guide-border { animation: none; }
            .distribution-operator-guide-zoom { animation: none; }
        }
    </style>
@endonce

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
            scrollLocked: false,
            guideSelection: @js($guideSelection),
            search: @entangle('predefinedWorkerGroups.' . $groupIndex . '.worker_search').live,
            get isMobileSheet() {
                return this.mediaQuery?.matches ?? window.matchMedia('(max-width: 639px)').matches;
            },
            init() {
                this.mediaQuery = window.matchMedia('(max-width: 639px)');
                this.popStateHandler = () => this.handleSelectorPopState();
                this.mediaQueryHandler = () => this.syncPageScrollLock(this.open);

                window.addEventListener('popstate', this.popStateHandler);
                if (this.mediaQuery.addEventListener) {
                    this.mediaQuery.addEventListener('change', this.mediaQueryHandler);
                } else {
                    this.mediaQuery.addListener?.(this.mediaQueryHandler);
                }

                this.$watch('open', (value) => this.syncPageScrollLock(value));

                // When the first-worker section first appears (a service was just
                // picked and no worker is chosen yet), gently bring it into view and
                // move keyboard focus to the control that needs the user's action.
                if (this.guideSelection) {
                    this.$nextTick(() => this.runSelectionGuide());
                }
            },
            runSelectionGuide() {
                const section = this.$el.parentElement ?? this.$el;
                const rect = section.getBoundingClientRect();
                const margin = 24;
                const fullyVisible = rect.top >= margin && rect.bottom <= (window.innerHeight - margin);

                if (! fullyVisible) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // Focus without a second scroll jump; wait for the smooth scroll to
                // settle first when we actually had to move the viewport.
                window.setTimeout(
                    () => this.$refs.trigger?.focus({ preventScroll: true }),
                    fullyVisible ? 0 : 320
                );
            },
            destroy() {
                if (this.popStateHandler) {
                    window.removeEventListener('popstate', this.popStateHandler);
                }

                if (this.mediaQueryHandler) {
                    if (this.mediaQuery?.removeEventListener) {
                        this.mediaQuery.removeEventListener('change', this.mediaQueryHandler);
                    } else {
                        this.mediaQuery?.removeListener?.(this.mediaQueryHandler);
                    }
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
                $wire.openPredefinedGroupWorkerSearch({{ $groupIndex }});
                this.focusWorkerSearch();
                this.pushSelectorHistory();
            },
            autoOpenForNewGroup(event) {
                // When this exact group was just added and still has no worker,
                // drop the operator straight into search (bottom sheet on mobile).
                if (event.detail?.index !== {{ $groupIndex }} || this.open) {
                    return;
                }

                if (@js((bool) $groupWorkerId)) {
                    return;
                }

                this.$nextTick(() => this.openSelector());
            },
            focusWorkerSearch() {
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
                this.guideSelection = false;
                this.closeSelector();
                $wire.selectPredefinedGroupWorker({{ $groupIndex }}, Number(workerId));
            },
            clear() {
                this.search = '';
                this.closeSelector();
                $wire.clearPredefinedGroupWorker({{ $groupIndex }});
            },
        }"
        x-on:keydown.escape.window="closeSelector()"
        x-on:predefined-worker-group-added.window="autoOpenForNewGroup($event)"
    >
        <button
            type="button"
            x-ref="trigger"
            @click="guideSelection = false; toggleSelector()"
            :aria-expanded="open.toString()"
            aria-haspopup="dialog"
            x-bind:class="guideSelection ? 'distribution-operator-guide-border distribution-operator-guide-zoom bg-white' : 'border-slate-300 bg-white {{ $accentClasses['hoverBorder'] }}'"
            class="flex min-h-12 w-full items-center justify-between rounded-2xl border py-3 ps-4 {{ $groupWorkerId ? 'pe-16' : 'pe-4' }} text-right shadow-sm transition focus:outline-none focus:ring-4"
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
            class="fixed inset-x-0 bottom-0 z-50 flex max-h-[86svh] min-h-[48svh] flex-col rounded-t-3xl border border-slate-200 bg-slate-50 p-3 shadow-[0_-18px_45px_rgba(15,23,42,0.22)] sm:absolute sm:inset-auto sm:mt-2 sm:min-h-0 sm:max-h-[30rem] sm:w-full sm:rounded-2xl sm:p-2 sm:shadow-xl"
            dir="rtl"
            role="dialog"
            aria-modal="true"
            aria-label="انتخاب مددکار"
            style="display: none;"
        >
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
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    aria-label="بستن انتخاب مددکار"
                >
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <div class="sticky top-0 z-10 bg-slate-50 pb-2">
                <div class="relative">
                    <input
                        type="search"
                        x-ref="workerSearch"
                        x-model.debounce.250ms="search"
                        @focus="$wire.openPredefinedGroupWorkerSearch({{ $groupIndex }})"
                        class="min-h-12 w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-3 text-sm text-slate-700 shadow-sm transition placeholder:text-xs placeholder:text-slate-400 focus:outline-none focus:ring-4 {{ $accentClasses['focus'] }}"
                        placeholder="نام، کد مددکار یا موبایل"
                        autocomplete="off"
                    >
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
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
    @error("predefinedWorkerGroups.$groupIndex.social_worker_id") <p data-validation-error class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>
