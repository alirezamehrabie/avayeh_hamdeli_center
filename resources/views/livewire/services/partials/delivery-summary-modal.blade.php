{{-- Worker-performance summary: bottom sheet on phones (same overlay/backdrop behavior as the
     subcategories sheet on this page), centered dialog on desktop. The backdrop blocks wheel
     and touch scrolling so the page behind stays put; the sheet body owns one scroll region
     and the recipients list gets its own inner scroll (overscroll-contain) so the sheet never
     grows past its max height. Each worker is an accordion row (first one opens automatically)
     to keep the whole list compact. --}}
<div
    x-cloak
    x-show="workersOpen"
    @click="workersOpen = false"
    @wheel.prevent
    @touchmove.prevent
    x-transition:enter="transition-opacity ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 !mt-0 bg-slate-950/40 lg:backdrop-blur-sm"
></div>

<div
    x-cloak
    @keydown.escape.window="workersOpen = false"
    :class="workersOpen
        ? 'translate-y-0 lg:scale-100 lg:opacity-100'
        : 'translate-y-full lg:translate-y-0 lg:scale-95 lg:opacity-0 lg:pointer-events-none'"
    class="fixed inset-x-0 bottom-0 z-50 flex max-h-[88svh] min-h-0 flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl transition duration-300 ease-out lg:inset-0 lg:!m-auto lg:h-fit lg:max-h-[82vh] lg:w-full lg:max-w-2xl lg:rounded-[28px] lg:border lg:border-slate-200/80"
>
    <div class="flex-none bg-gradient-to-l from-slate-50 via-white to-cyan-50 px-4 pb-3.5 pt-3 sm:px-5">
        <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-200 lg:hidden"></div>
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-sky-600 text-white shadow-sm shadow-cyan-900/15">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="shrink-0 text-base font-black text-slate-900 sm:text-lg">عملکرد مددکاران</h3>
                        <span class="shrink-0 rounded-full bg-cyan-100 px-2.5 py-0.5 text-[11px] font-black text-cyan-800 ring-1 ring-cyan-200" x-text="workersSummary?.code"></span>
                    </div>
                    <p class="mt-0.5 truncate text-xs font-semibold text-slate-500 sm:text-sm">
                        <span x-text="workersSummary?.service"></span>
                        <span class="mx-1.5 text-slate-300">|</span>
                        <span class="text-slate-400" x-text="`${workersSummary?.workers?.length ?? 0} مددکار`"></span>
                    </p>
                </div>
            </div>
            <button
                type="button"
                @click="workersOpen = false"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-700"
                aria-label="بستن"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div
        data-modal-scroll
        class="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-[max(1.25rem,env(safe-area-inset-bottom))]"
        x-data="{ openWorker: null }"
        x-init="$watch('workersOpen', (open) => { if (open) openWorker = workersSummary?.workers?.[0]?.id ?? null; })"
    >
        <template x-if="workersSummary?.workers?.length">
            <div class="divide-y divide-slate-100">
                <template x-for="worker in workersSummary.workers" :key="worker.id">
                    <article>
                        <button
                            type="button"
                            @click="openWorker = openWorker === worker.id ? null : worker.id"
                            :aria-expanded="openWorker === worker.id ? 'true' : 'false'"
                            class="flex min-h-16 w-full items-center gap-3 px-4 py-3 text-start transition hover:bg-slate-50 focus:outline-none focus-visible:bg-cyan-50/60 sm:px-5"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-sky-600 text-sm font-black text-white" x-text="worker.initials"></span>
                            <span class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm font-black text-slate-900" x-text="worker.name"></span>
                                    <span class="shrink-0 text-[11px] font-bold text-slate-400" x-text="`${worker.recipients?.length ?? 0} گیرنده`"></span>
                                </span>
                                <span class="flex items-center gap-2.5">
                                    <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100">
                                        <span class="block h-full rounded-full bg-gradient-to-l from-cyan-500 via-sky-500 to-emerald-400" :style="`width: ${worker.progress}%`"></span>
                                    </span>
                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black ring-1"
                                        :class="worker.progress >= 100 ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : (worker.progress > 0 ? 'bg-cyan-50 text-cyan-700 ring-cyan-100' : 'bg-amber-50 text-amber-700 ring-amber-100')"
                                        x-text="`${worker.progress}%`"
                                    ></span>
                                </span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="openWorker === worker.id ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="openWorker === worker.id" x-collapse.duration.250ms class="px-4 pb-4 sm:px-5">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-bold text-slate-500">
                                <span>تخصیص: <b class="text-slate-800" x-text="worker.allocated"></b></span>
                                <span>تحویل: <b class="text-emerald-700" x-text="worker.delivered"></b></span>
                                <span>باقی‌مانده: <b class="text-amber-700" x-text="worker.remaining"></b></span>
                                <span class="text-slate-200">|</span>
                                <span x-text="worker.code"></span>
                                <span class="text-slate-400" x-text="worker.mobile"></span>
                            </div>

                            <div class="mt-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-black text-slate-700">تفکیک بر اساس واحد خدمت</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="`${worker.categories.length} مورد`"></span>
                                </div>
                                <ul class="mt-1 divide-y divide-slate-100">
                                    <template x-for="(category, index) in worker.categories" :key="`${worker.id}-${category.name}-${index}`">
                                        <li class="py-2.5">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="truncate text-xs font-bold text-slate-800" x-text="category.name"></p>
                                                <span class="shrink-0 text-[11px] font-black" :class="category.progress >= 100 ? 'text-emerald-700' : 'text-slate-500'">
                                                    <span x-text="category.delivered"></span>
                                                    <span class="text-slate-300">/</span>
                                                    <span x-text="category.allocated"></span>
                                                    <span class="text-slate-400" x-text="category.unit"></span>
                                                </span>
                                            </div>
                                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full" :class="category.progress >= 100 ? 'bg-emerald-500' : 'bg-cyan-500'" :style="`width: ${category.progress}%`"></div>
                                            </div>
                                            <p class="mt-1 text-[11px] font-bold text-slate-400">
                                                <span>باقی‌مانده: <b class="text-amber-700" x-text="category.remaining"></b></span>
                                                <span class="mx-1.5 text-slate-200">|</span>
                                                <span x-text="`${category.progress}%`"></span>
                                            </p>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <div class="mt-3 border-t border-slate-100 pt-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-black text-slate-700">گیرندگان خدمت</p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1"
                                        :class="worker.recipients?.length ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-500 ring-slate-200'"
                                        x-text="`${worker.recipients?.length ?? 0} گیرنده`"
                                    ></span>
                                </div>

                                <template x-if="worker.recipients?.length">
                                    <ul class="mt-1 max-h-56 divide-y divide-slate-100 overflow-y-auto overscroll-contain pe-1">
                                        <template x-for="(recipient, index) in worker.recipients" :key="`${worker.id}-${recipient.national_id}-${index}`">
                                            <li class="flex items-center justify-between gap-3 py-2.5">
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <p class="truncate text-xs font-black text-slate-800" x-text="recipient.name"></p>
                                                        <span
                                                            class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1"
                                                            :class="recipient.type === 'مددجو' ? 'bg-sky-50 text-sky-700 ring-sky-100' : (recipient.type === 'سرپرست خانوار' ? 'bg-indigo-50 text-indigo-700 ring-indigo-100' : 'bg-slate-100 text-slate-500 ring-slate-200')"
                                                            x-text="recipient.type"
                                                        ></span>
                                                    </div>
                                                    <p class="mt-0.5 truncate text-[11px] font-medium text-slate-400">
                                                        <span>کد ملی: <b class="font-bold text-slate-600" x-text="recipient.national_id"></b></span>
                                                        <span class="mx-1 text-slate-200">|</span>
                                                        <span x-text="`${recipient.deliveries_count} ثبت`"></span>
                                                    </p>
                                                </div>
                                                <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700 ring-1 ring-emerald-100" x-text="`تحویل ${recipient.delivered}`"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </template>

                                <template x-if="!worker.recipients?.length">
                                    <p class="mt-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-3 py-3 text-center text-[11px] font-semibold text-slate-400">
                                        هنوز تحویلی برای این مددکار ثبت نشده است.
                                    </p>
                                </template>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </template>

        <template x-if="!(workersSummary?.workers?.length)">
            <div class="px-4 py-12 text-center sm:px-6">
                <p class="text-sm font-bold text-slate-600">برای این خدمت هنوز مددکاری تخصیص داده نشده است.</p>
                <p class="mt-1 text-xs text-slate-400">پس از تخصیص سهمیه، وضعیت مصرف، ظرفیت باقی‌مانده و گیرندگان خدمت در همین بخش نمایش داده می‌شود.</p>
            </div>
        </template>
    </div>
</div>
