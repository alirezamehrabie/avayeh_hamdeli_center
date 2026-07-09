<div
    x-show="workersOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/35 px-4 py-6 backdrop-blur-md"
    style="display: none;"
>
    <div
        @click.outside="workersOpen = false"
        x-transition.scale.origin.center
        class="w-full max-w-5xl overflow-hidden rounded-[32px] border border-white/70 bg-white/95 shadow-2xl shadow-slate-950/20"
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 bg-gradient-to-l from-slate-50 via-white to-cyan-50 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-black text-slate-900">عملکرد مددکاران</h3>
                    <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[11px] font-black text-cyan-800 ring-1 ring-cyan-200" x-text="workersSummary?.code"></span>
                </div>
                <p class="mt-1 truncate text-sm font-semibold text-slate-500" x-text="workersSummary?.service"></p>
            </div>

            <button type="button" @click="workersOpen = false" class="rounded-full border border-slate-200 bg-white p-2 text-slate-400 shadow-sm transition hover:bg-slate-50 hover:text-slate-700" aria-label="بستن">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <div class="max-h-[78vh] overflow-y-auto px-4 py-5 sm:px-6">
            <template x-if="workersSummary?.workers?.length">
                <div class="grid gap-4 lg:grid-cols-2">
                    <template x-for="worker in workersSummary.workers" :key="worker.id">
                        <section class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm shadow-slate-950/5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-slate-800 text-sm font-black text-white shadow-lg shadow-cyan-900/15" x-text="worker.initials"></div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-900" x-text="worker.name"></p>
                                        <p class="mt-0.5 truncate text-xs font-medium text-slate-400">
                                            <span x-text="worker.code"></span>
                                            <span class="mx-1 text-slate-300">|</span>
                                            <span x-text="worker.mobile"></span>
                                        </p>
                                    </div>
                                </div>

                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-black text-white" x-text="`${worker.progress}%`"></span>
                            </div>

                            <div class="mt-4">
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200/70">
                                    <div class="h-full rounded-full bg-gradient-to-l from-cyan-500 via-sky-500 to-emerald-400 transition-all duration-700" :style="`width: ${worker.progress}%`"></div>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-2xl bg-slate-50 px-2 py-2">
                                        <p class="text-[10px] font-semibold text-slate-400">تخصیص</p>
                                        <p class="mt-1 text-xs font-black text-slate-800" x-text="worker.allocated"></p>
                                    </div>
                                    <div class="rounded-2xl bg-emerald-50 px-2 py-2">
                                        <p class="text-[10px] font-semibold text-emerald-600/70">تحویل</p>
                                        <p class="mt-1 text-xs font-black text-emerald-800" x-text="worker.delivered"></p>
                                    </div>
                                    <div class="rounded-2xl bg-amber-50 px-2 py-2">
                                        <p class="text-[10px] font-semibold text-amber-700/70">باقی‌مانده</p>
                                        <p class="mt-1 text-xs font-black text-amber-900" x-text="worker.remaining"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-black text-slate-700">تفکیک بر اساس واحد خدمت</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="`${worker.categories.length} مورد`"></span>
                                </div>

                                <template x-for="(category, index) in worker.categories" :key="`${worker.id}-${category.name}-${index}`">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="truncate text-xs font-black text-slate-800" x-text="category.name"></p>
                                            <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200" x-text="category.unit"></span>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                                            <div class="h-full rounded-full bg-slate-800 transition-all duration-700" :style="`width: ${category.progress}%`"></div>
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-bold text-slate-500">
                                            <span>تحویل: <b class="text-emerald-700" x-text="category.delivered"></b></span>
                                            <span>باقی‌مانده: <b class="text-amber-700" x-text="category.remaining"></b></span>
                                            <span>تخصیص: <b class="text-slate-800" x-text="category.allocated"></b></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-black text-slate-700">گیرندگان خدمت</p>
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100" x-text="`${worker.recipients?.length ?? 0} گیرنده`"></span>
                                </div>

                                <template x-if="worker.recipients?.length">
                                    <div class="max-h-44 space-y-2 overflow-y-auto pe-1">
                                        <template x-for="(recipient, index) in worker.recipients" :key="`${worker.id}-${recipient.national_id}-${index}`">
                                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 px-3 py-2.5">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="truncate text-xs font-black text-slate-800" x-text="recipient.name"></p>
                                                    <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100" x-text="recipient.type"></span>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-bold text-slate-500">
                                                    <span>کد ملی: <b class="text-slate-800" x-text="recipient.national_id"></b></span>
                                                    <span>تحویل: <b class="text-emerald-700" x-text="recipient.delivered"></b></span>
                                                    <span>تعداد ثبت: <b class="text-slate-800" x-text="recipient.deliveries_count"></b></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!(worker.recipients?.length)">
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-xs font-semibold text-slate-500">
                                        هنوز تحویلی برای این مددکار ثبت نشده است.
                                    </div>
                                </template>
                            </div>
                        </section>
                    </template>
                </div>
            </template>

            <template x-if="!(workersSummary?.workers?.length)">
                <div class="rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center">
                    <p class="text-sm font-bold text-slate-600">برای این خدمت هنوز مددکاری تخصیص داده نشده است.</p>
                    <p class="mt-1 text-xs text-slate-400">پس از تخصیص سهمیه، وضعیت مصرف، ظرفیت باقی‌مانده و گیرندگان خدمت در همین بخش نمایش داده می‌شود.</p>
                </div>
            </template>
        </div>
    </div>
</div>
