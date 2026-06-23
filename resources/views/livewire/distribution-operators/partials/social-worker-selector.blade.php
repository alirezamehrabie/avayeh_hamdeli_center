@php
    $accent = $accent ?? 'cyan';
    $selectorId = $selectorId ?? 'social-worker-selector';
    $isEmerald = $accent === 'emerald';
    $accentClasses = $isEmerald
        ? [
            'hoverBorder' => 'hover:border-emerald-300 hover:bg-emerald-50/50 focus:border-emerald-500 focus:ring-emerald-500/10',
            'focus' => 'focus:border-emerald-500 focus:ring-emerald-500/10',
            'activeCard' => 'border-emerald-300 bg-emerald-50/80',
            'hoverCard' => 'hover:border-emerald-200 hover:bg-emerald-50/60 active:bg-emerald-50 focus:ring-emerald-500/10',
            'badge' => 'bg-emerald-50 text-emerald-700',
            'icon' => 'text-emerald-600',
        ]
        : [
            'hoverBorder' => 'hover:border-cyan-300 hover:bg-cyan-50/50 focus:border-cyan-500 focus:ring-cyan-500/10',
            'focus' => 'focus:border-cyan-500 focus:ring-cyan-500/10',
            'activeCard' => 'border-cyan-300 bg-cyan-50/80',
            'hoverCard' => 'hover:border-cyan-200 hover:bg-cyan-50/60 active:bg-cyan-50 focus:ring-cyan-500/10',
            'badge' => 'bg-cyan-50 text-cyan-700',
            'icon' => 'text-cyan-600',
        ];
@endphp

<div wire:key="{{ $selectorId }}-root">
    <label class="mb-2 block text-sm font-bold text-slate-700">مددکار</label>
    <div
        class="relative"
        x-data="{
            open: false,
            historyActive: false,
            search: @entangle('socialWorkerQuery').live,
            get isMobileSheet() {
                return window.matchMedia('(max-width: 639px)').matches;
            },
            openSelector() {
                this.open = true;
                $wire.set('showSocialWorkerSuggestions', true);
                this.pushSelectorHistory();
                this.$nextTick(() => this.$refs.workerSearch?.focus());
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
                $wire.selectSocialWorker(Number(workerId));
            },
            clear() {
                this.search = '';
                this.closeSelector();
                $wire.clearSocialWorkerSelection();
            },
        }"
        x-init="
            window.addEventListener('popstate', () => handleSelectorPopState());
            $watch('open', (value) => {
                document.documentElement.classList.toggle('overflow-hidden', value && isMobileSheet);
            });
        "
        x-on:keydown.escape.window="closeSelector()"
    >
        <button
            type="button"
            @click="toggleSelector()"
            :aria-expanded="open.toString()"
            aria-haspopup="dialog"
            class="flex min-h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right text-sm font-bold text-slate-700 shadow-sm transition focus:outline-none focus:ring-4 {{ $accentClasses['hoverBorder'] }}"
        >
            <span class="min-w-0">
                <span class="block truncate">
                    {{ $socialWorkerId ? $socialWorkerQuery : 'نام یا کد مددکار را جستجو کنید' }}
                </span>
                <span class="mt-0.5 block truncate text-xs font-semibold text-slate-500">
                    {{ $socialWorkerId ? 'مددکار برای تخصیص انتخاب شده است' : 'جستجو، بررسی ظرفیت پوشش و انتخاب سریع' }}
                </span>
            </span>
            <span class="ms-3 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                <i class="bi bi-search text-sm"></i>
            </span>
        </button>

        @if($socialWorkerQuery !== '')
            <button
                type="button"
                @click.stop="clear()"
                class="absolute left-3 top-3.5 inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                aria-label="پاک کردن مددکار"
            >
                <i class="bi bi-x-lg text-xs"></i>
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
                        @focus="$wire.set('showSocialWorkerSuggestions', true)"
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
                            @click="choose({{ (int) $worker['id'] }})"
                            class="group flex w-full items-center gap-2 rounded-xl border px-2.5 py-2 text-right transition focus:outline-none focus:ring-4 sm:px-3 {{ (int) $socialWorkerId === (int) $worker['id'] ? $accentClasses['activeCard'] : 'border-slate-200 bg-white' }} {{ $accentClasses['hoverCard'] }}"
                        >
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 {{ $accentClasses['icon'] }}">
                                <i class="bi bi-person-badge text-sm"></i>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex min-w-0 items-center gap-1.5">
                                    <span class="min-w-0 flex-1 truncate text-sm font-black leading-5 text-slate-900">{{ $worker['name'] }}</span>
                                    <span class="shrink-0 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-black leading-4 text-slate-600">کد {{ $worker['code'] }}</span>
                                    <span class="max-w-24 shrink-0 truncate rounded-md px-1.5 py-0.5 text-[10px] font-black leading-4 sm:max-w-32 {{ $accentClasses['badge'] }}">{{ $worker['district'] }}</span>
                                </span>

                                <span class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] font-bold leading-5 text-slate-500">
                                    <span class="truncate">موبایل: {{ $worker['mobile'] }}</span>
                                    <span class="text-slate-300">|</span>
                                    <span class="whitespace-nowrap">افراد: {{ number_format($worker['covered_people']) }}</span>
                                    <span class="whitespace-nowrap">خانوار: {{ number_format($worker['covered_households']) }}</span>
                                    <span class="whitespace-nowrap">کودک: {{ number_format($worker['covered_children']) }}</span>
                                    <span class="whitespace-nowrap text-amber-700">باز: {{ number_format($worker['open_allocations']) }}</span>
                                    <span class="min-w-0 truncate text-slate-600">حجم: {{ $worker['allocated_quantity_label'] }}</span>
                                </span>
                            </span>
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ (int) $socialWorkerId === (int) $worker['id'] ? 'border-current bg-white/70 ' . $accentClasses['icon'] : 'border-slate-200 text-slate-300 group-hover:border-slate-300 group-hover:text-slate-400' }}">
                                <i class="bi {{ (int) $socialWorkerId === (int) $worker['id'] ? 'bi-check-lg' : 'bi-chevron-left' }} text-[10px]"></i>
                            </span>
                        </button>
                    @endforeach
                @elseif(mb_strlen(trim($socialWorkerQuery)) >= 2)
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
                @if($socialWorkerId)
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
    @error('socialWorkerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>
