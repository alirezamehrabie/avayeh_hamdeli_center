<div
    x-data="{
        sidebarOpen: window.innerWidth >= 1024,
        reminderDrawerOpen: false,
        resizeHandler: null,
        sectionLoading: false,
        sectionLoaderVisible: false,
        sectionLoaderTimer: null,
        beginSectionLoading() {
            this.sectionLoading = true;
            window.clearTimeout(this.sectionLoaderTimer);
            // صفحه بارگذاری فقط برای بارگذاری‌های محسوس (>۲۵۰ms) ظاهر می‌شود تا از پرش بصری جلوگیری شود.
            this.sectionLoaderTimer = window.setTimeout(() => {
                if (this.sectionLoading) {
                    this.sectionLoaderVisible = true;
                }
            }, 250);
        },
        endSectionLoading() {
            this.sectionLoading = false;
            window.clearTimeout(this.sectionLoaderTimer);
            this.sectionLoaderTimer = null;
            this.sectionLoaderVisible = false;
        },
        initSidebar() {
            this.syncSidebar();
            this.resizeHandler = () => this.syncSidebar();
            window.addEventListener('resize', this.resizeHandler);
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            this.syncSidebarFocus();
        },
        closeSidebarOnMobile() {
            if (window.innerWidth < 1024 && this.sidebarOpen) {
                this.sidebarOpen = false;
                this.restoreSidebarToggleFocus();
            }
        },
        openReminderDrawer() {
            this.reminderDrawerOpen = true;
            this.$nextTick(() => this.$refs.reminderPanel?.focus({ preventScroll: true }));
        },
        closeReminderDrawer() {
            if (!this.reminderDrawerOpen) {
                return;
            }

            this.reminderDrawerOpen = false;
            this.$nextTick(() => this.$refs.reminderToggle?.focus({ preventScroll: true }));
        },
        syncSidebar() {
            if (window.innerWidth >= 1024) {
                this.sidebarOpen = true;
            }
        },
        syncSidebarFocus() {
            if (this.sidebarOpen && window.innerWidth < 1024) {
                this.$nextTick(() => this.$refs.sidebarPanel?.focus({ preventScroll: true }));
            } else if (!this.sidebarOpen) {
                this.restoreSidebarToggleFocus();
            }
        },
        restoreSidebarToggleFocus() {
            this.$nextTick(() => this.$refs.sidebarToggle?.focus({ preventScroll: true }));
        },
        trapSidebarFocus(event) {
            if (!this.sidebarOpen || window.innerWidth >= 1024) {
                return;
            }

            const panel = this.$refs.sidebarPanel;

            if (!panel) {
                return;
            }

            const focusable = Array.from(panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\'-1\'])'))
                .filter((element) => element.offsetParent !== null);

            if (focusable.length === 0) {
                event.preventDefault();
                panel.focus({ preventScroll: true });

                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (!panel.contains(document.activeElement)) {
                event.preventDefault();
                first.focus({ preventScroll: true });

                return;
            }

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus({ preventScroll: true });
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus({ preventScroll: true });
            }
        },
        destroy() {
            if (this.resizeHandler) {
                window.removeEventListener('resize', this.resizeHandler);
                this.resizeHandler = null;
            }
        }
    }"
    x-init="initSidebar()"
    @open-dashboard-section.window="closeSidebarOnMobile(); closeReminderDrawer(); beginSectionLoading()"
    @dashboard-section-rendered.window="endSectionLoading()"
    @sidebar-request-failed.window="endSectionLoading()"
    @keydown.escape.window="closeSidebarOnMobile(); closeReminderDrawer()"
    @keydown.tab.window="trapSidebarFocus($event)"
    class="flex h-full overflow-hidden"
    dir="rtl"
>
    <livewire:admin.dashboard-sidebar :active-section="$activeSection" :key="'dashboard-sidebar'" />

    <div
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @click="closeSidebarOnMobile()"
        class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"
        style="display: none;"
    ></div>

    <div
        x-show="reminderDrawerOpen"
        x-transition.opacity.duration.200ms
        @click="closeReminderDrawer()"
        class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-sm"
        style="display: none;"
    ></div>

    @php
        $openReminders = $reminders->where('is_done', false);
        $doneReminders = $reminders->where('is_done', true);
        $reminderProgressPercent = $reminders->isEmpty() ? 0 : (int) round($doneReminders->count() * 100 / $reminders->count());
    @endphp

    <aside
        x-show="reminderDrawerOpen"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 -translate-x-6"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-6"
        x-ref="reminderPanel"
        tabindex="-1"
        class="fixed inset-y-0 left-0 z-50 flex w-full max-w-md flex-col border-r border-slate-200 bg-white shadow-2xl"
        style="display: none;"
    >
        {{-- سربرگ: هماهنگ با چیپ آیکون کارت «یادآوری‌ها» در نمای کلی --}}
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100/80 text-amber-600 ring-1 ring-amber-200/60">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 00-12 0v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-[0.16em] text-slate-400">فضای شخصی</p>
                    <h2 class="mt-0.5 truncate text-lg font-bold text-slate-800">یادآوری‌های من</h2>
                </div>
            </div>
            <button
                type="button"
                @click="closeReminderDrawer()"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100"
                aria-label="بستن یادآوری‌ها"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            @if($reminders->isNotEmpty())
                {{-- نوار پیشرفت: درصد کارهای انجام‌شده --}}
                <div class="mb-4 rounded-2xl bg-slate-50/80 p-3 ring-1 ring-slate-200/60">
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <span class="font-semibold text-slate-500">پیشرفت کارها</span>
                        <span class="font-bold tabular-nums text-emerald-600">{{ number_format($doneReminders->count()) }} از {{ number_format($reminders->count()) }} انجام شده</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200/70" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $reminderProgressPercent }}">
                        <div class="h-full rounded-full bg-gradient-to-l from-emerald-500 to-emerald-400 transition-all duration-500" style="width: {{ $reminderProgressPercent }}%;"></div>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="addReminder" class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-3.5">
                <div>
                    <label for="dashboard-reminder-title" class="mb-1 block text-xs font-semibold text-slate-500">متن یادآوری</label>
                    <input
                        id="dashboard-reminder-title"
                        type="text"
                        wire:model.defer="newReminderTitle"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 transition focus:border-indigo-300 focus:bg-white focus:ring focus:ring-indigo-100"
                        placeholder="مثلاً: پیگیری پرونده خانوار ..."
                    >
                    @error('newReminderTitle')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="dashboard-reminder-category" class="mb-1 block text-xs font-semibold text-slate-500">دسته‌بندی</label>
                    <select
                        id="dashboard-reminder-category"
                        wire:model.defer="newReminderCategory"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 transition focus:border-indigo-300 focus:bg-white focus:ring focus:ring-indigo-100"
                    >
                        @foreach($reminderCategories as $categoryKey => $categoryLabel)
                            <option value="{{ $categoryKey }}">{{ $categoryLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    افزودن یادآوری
                </button>
            </form>

            {{-- لیست: بازها اول و سپس انجام‌شده‌ها (مرتب‌سازی از سمت کامپوننت) --}}
            <div class="mt-5">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-bold text-slate-600">یادآوری‌های باز</p>
                    @if($openReminders->isNotEmpty())
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold tabular-nums text-amber-700 ring-1 ring-amber-100">{{ number_format($openReminders->count()) }} مورد</span>
                    @endif
                </div>

                <div class="mt-2 space-y-2">
                    @forelse($openReminders as $reminder)
                        @include('livewire.admin.dashboard.partials.reminder-row', ['reminder' => $reminder])
                    @empty
                        @if($reminders->isEmpty())
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center">
                                <p class="text-sm font-semibold text-slate-500">هنوز یادآوری شخصی ثبت نکرده‌اید</p>
                                <p class="mt-1 text-xs text-slate-400">با فرم بالا اولین یادآوری خود را اضافه کنید.</p>
                            </div>
                        @else
                            <div class="rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 p-6 text-center">
                                <p class="text-sm font-semibold text-emerald-700">همۀ یادآوری‌هایتان انجام شده است</p>
                                <p class="mt-1 text-xs text-emerald-600/80">آفرین! می‌توانید یادآوری جدید اضافه کنید.</p>
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>

            @if($doneReminders->isNotEmpty())
                <div class="mt-6">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold text-slate-600">انجام‌شده‌ها</p>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold tabular-nums text-emerald-700 ring-1 ring-emerald-100">{{ number_format($doneReminders->count()) }} مورد</span>
                    </div>
                    <div class="mt-2 space-y-2">
                        @foreach($doneReminders as $reminder)
                            @include('livewire.admin.dashboard.partials.reminder-row', ['reminder' => $reminder])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </aside>

    <div class="flex min-w-0 min-h-0 flex-1 w-full flex-col overflow-y-auto">
        @include('layouts.partials.header')

        <main class="relative min-h-0 px-2 py-4 lg:px-4" :aria-busy="sectionLoading ? 'true' : 'false'">
            {{-- صفحه بارگذاری برنددار: با المان واضح و مرتبط با سایت (لوگو + حلقه چرخان) و هماهنگ با تم سایدبار.
                 با fixed دقیقاً قدِ viewportِ ناحیه محتوا (سمت چپ سایدبار) را پر می‌کند؛ m-auto هم وسط‌چین می‌کند
                 و هم در بخش‌های خیلی بلند/کوتاه از بریده‌شدن یا خارج‌شدن اسپینر از دید جلوگیری می‌کند. --}}
            <div
                x-show="sectionLoaderVisible"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-y-0 left-0 right-0 z-40 flex overflow-y-auto bg-gradient-to-br from-indigo-900 via-indigo-900 to-indigo-950 text-white lg:right-64"
            >
                <div class="m-auto flex flex-col items-center gap-4 px-6 py-10 text-center sm:gap-5">
                    {{-- لوگوی سایت داخل حلقه چرخان --}}
                    <div class="relative flex h-20 w-20 shrink-0 items-center justify-center sm:h-24 sm:w-24">
                        <span class="absolute inset-0 animate-spin rounded-full border-2 border-indigo-500/30 border-t-indigo-200" aria-hidden="true"></span>
                        <span class="absolute inset-[6px] animate-spin rounded-full border-2 border-purple-400/20 border-b-purple-200/70 [animation-duration:1.8s]" style="animation-direction: reverse;" aria-hidden="true"></span>
                        <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl opacity-90 shadow-lg shadow-indigo-500/20 sm:h-16 sm:w-16">
                            <img src="{{ asset('images/logo-wh.webp') }}" width="150" height="150" alt="لوگوی آوای همدلی" class="h-full w-full object-cover">
                        </div>
                    </div>

                    {{-- نام برند، همان استایل سایدبار --}}
                    <div>
                        <p class="mb-1 text-[11px] font-light tracking-wide text-indigo-100/80">مرکز نیکوکاری تخصصی کودکان</p>
                        <h2 class="bg-gradient-to-l from-indigo-200 via-purple-100 to-indigo-200 bg-clip-text text-2xl font-black text-transparent">آوای همدلی</h2>
                    </div>

                    {{-- متن و نقطه‌های بارگذاری --}}
                    <div class="flex items-center gap-2 text-sm text-indigo-100">
                        <span>در حال بارگذاری</span>
                        <x-sidebar.loading-dots class="text-indigo-200" />
                    </div>
                </div>
            </div>

            <div class="container mx-auto min-h-0">
                @switch($activeSection)
                    @case('people-fast-create')
                        <livewire:people.fast-create-person :person="$editingPerson" :embedded="true" :key="'people-fast-create-'.($editingPerson?->id ?? 'new')" />
                        @break

                    @case('people-list')
                        <livewire:people.index-people :embedded="true" :key="'people-list'" />
                        @break

                    @case('people-incomplete-cases')
                        <livewire:people.incomplete-cases-queue :embedded="true" :key="'people-incomplete-cases'" />
                        @break

                    @case('people-block-list')
                        <livewire:people.deleted-people :embedded="true" :key="'people-block-list'" />
                        @break

                    @case('beneficiary-case-file')
                        <livewire:people.beneficiary-case-file :person-id="$caseFilePersonId" :key="'beneficiary-case-file-'.($caseFilePersonId ?? 'search')" />
                        @break

                    @case('people-edit-field')
                        <livewire:people.edit-field-index :key="'people-edit-field'" />
                        @break

                    @case('people-edit-field-need-level')
                        <livewire:people.edit-field :embedded="true" editor-title="ویرایش سطح نیازمندی" :key="'people-edit-field-need-level'" />
                        @break

                    @case('person-create')
                        <livewire:people.create-person mode="create" :embedded="true" :key="'person-create'" />
                        @break

                    @case('person-edit')
                        @if($editingPerson)
                            <livewire:people.create-person mode="edit" :person="$editingPerson" :embedded="true" :key="'person-edit-'.$editingPerson->id" />
                        @else
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <p class="text-red-600 mb-4">مددجوی انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('people-list')" class="btn btn-primary">بازگشت به لیست مددجویان</button>
                            </div>
                        @endif
                        @break

                    @case('social-workers-list')
                        <livewire:social-workers.index-social-workers :embedded="true" :key="'social-workers-list'" />
                        @break

                    @case('social-workers-block-list')
                        <livewire:social-workers.deleted-social-workers :embedded="true" :key="'social-workers-block-list'" />
                        @break

                    @case('social-worker-create')
                        <livewire:social-workers.create-social-worker :embedded="true" :key="'social-worker-create'" />
                        @break

                    @case('social-worker-edit')
                        @if($editingSocialWorker)
                            <livewire:social-workers.edit-social-worker :social-worker="$editingSocialWorker" :embedded="true" :key="'social-worker-edit-'.$editingSocialWorker->id" />
                        @else
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <p class="text-red-600 mb-4">مددکار انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('social-workers-list')" class="btn btn-primary">بازگشت به لیست مددکاران</button>
                            </div>
                        @endif
                        @break

                    @case('social-worker-attendance-monitor')
                        <livewire:admin.attendance-monitor :embedded="true" :key="'social-worker-attendance-monitor'" />
                        @break

                    @case('guardians-list')
                        <livewire:guardians.index-guardians :embedded="true" :key="'guardians-list'" />
                        @break

                    @case('guardians-block-list')
                        <livewire:guardians.deleted-guardians :embedded="true" :key="'guardians-block-list'" />
                        @break

                    @case('guardian-edit')
                        @if($editingGuardian)
                            <livewire:guardians.edit-guardian :guardian="$editingGuardian" :embedded="true" :key="'guardian-edit-'.$editingGuardian->id" />
                        @else
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <p class="text-red-600 mb-4">سرپرست انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('guardians-list')" class="btn btn-primary">بازگشت به لیست سرپرستان</button>
                            </div>
                        @endif
                     @break


                    @case('advanced-reports')
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">گزارش پیشرفته</h1>
                            <p class="text-gray-600 mb-6">گزارش گیری پیشرفته مرکز تخصصی کودکان آوای همدلی</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                @can('full-access')
                                <button type="button" wire:click="selectSection('advanced-beneficiary-report')" class="group relative block w-full text-right overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                    <span class="absolute inset-y-0 right-0 w-1 bg-indigo-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-indigo-600 mb-2">Advanced Report</p>
                                            <h2 class="text-base font-bold text-gray-800">گزارش پیشرفته مددجویان</h2>
                                            <p class="text-xs text-gray-500 mt-2">تحلیل جامع اطلاعات مددجویان و شاخص‌های کلیدی</p>
                                        </div>
                                        <div class="rounded-lg bg-indigo-100 p-2 text-indigo-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                                @endcan

                                <button type="button" wire:click="selectSection('advanced-operator-report')" class="group relative text-right overflow-hidden rounded-xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                    <span class="absolute inset-y-0 right-0 w-1 bg-cyan-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-cyan-600 mb-2">Operator Report</p>
                                            <h2 class="text-base font-bold text-gray-800">گزارش عملکرد اپراتورها</h2>
                                            <p class="text-xs text-gray-500 mt-2">تحلیل ثبت، ویرایش، حذف و روند فعالیت هر کاربر سیستم</p>
                                        </div>
                                        <div class="rounded-lg bg-cyan-100 p-2 text-cyan-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm3-6h.01M12 15h.01M16 15h.01M8 11h.01M12 11h.01M16 11h.01"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                <button type="button" wire:click="selectSection('advanced-supervisor-report')" class="group relative text-right overflow-hidden rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                    <span class="absolute inset-y-0 right-0 w-1 bg-emerald-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-emerald-600 mb-2">Advanced Report</p>
                                            <h2 class="text-base font-bold text-gray-800">گزارش پیشرفته سرپرستان</h2>
                                            <p class="text-xs text-gray-500 mt-2">بررسی وضعیت سرپرستان، پوشش خانوار و روند عملکرد</p>
                                        </div>
                                        <div class="rounded-lg bg-emerald-100 p-2 text-emerald-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5m10 0v-4a3 3 0 00-3-3H10a3 3 0 00-3 3v4m10 0H7m8-13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                <button type="button" class="group relative text-right overflow-hidden rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-violet-100">
                                    <span class="absolute inset-y-0 right-0 w-1 bg-violet-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-violet-600 mb-2">Advanced Report</p>
                                            <h2 class="text-base font-bold text-gray-800">گزارش پیشرفته مددکاران</h2>
                                            <p class="text-xs text-gray-500 mt-2">نمایش تخصصی عملکرد مددکاران و وضعیت پرونده‌ها</p>
                                        </div>
                                        <div class="rounded-lg bg-violet-100 p-2 text-violet-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.567-3 3.5S10.343 15 12 15s3-1.567 3-3.5S13.657 8 12 8zm0 0V5m0 10v4m7-7h-3M8 12H5m11.364 4.95l-2.121-2.121M9.757 9.757L7.636 7.636m8.728 0l-2.121 2.121M9.757 14.243l-2.121 2.121"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                @can('full-access')
                                <button type="button" wire:click="selectSection('advanced-gate-report')" class="group relative text-right overflow-hidden rounded-xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-rose-100">
                                    <span class="absolute inset-y-0 right-0 w-1 bg-rose-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-rose-600 mb-2">Advanced Report</p>
                                            <h2 class="text-base font-bold text-gray-800">گزارش گیت‌های توزیع</h2>
                                            <p class="text-xs text-gray-500 mt-2">پیگیری ورود، تحویل و خروج مددجویان و خانوارها در ایستگاه‌های توزیع</p>
                                        </div>
                                        <div class="rounded-lg bg-rose-100 p-2 text-rose-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                                @endcan
                            </div>
                        </div>
                        @break

                    @case('advanced-beneficiary-report')
                        <livewire:people.advanced-filter-builder :key="'advanced-beneficiary-report'" />
                        @break

                    @case('advanced-service-report')
                        <livewire:services.service-reports
                            :selected-service-id="$serviceReportServiceId"
                            :delivery-channel="$serviceReportChannel"
                            :key="'advanced-service-report-'.($serviceReportServiceId ?? 'list').'-'.($serviceReportChannel ?? 'pick')"
                        />
                        @break

                    @case('advanced-operator-report')
                        <livewire:admin.operator-report :key="'advanced-operator-report'" />
                        @break

                    @case('advanced-supervisor-report')
                        <livewire:guardians.advanced-guardian-report :key="'advanced-supervisor-report'" />
                        @break

                    @case('advanced-social-worker-report')
                        <livewire:social-workers.advanced-social-worker-report :key="'advanced-social-worker-report'" />
                        @break

                    @case('advanced-gate-report')
                        <livewire:admin.gate-report :key="'advanced-gate-report'" />
                        @break

                    @case('advanced-gate-technical-report')
                        <livewire:admin.gate-technical-report
                            :service-id="$sectionContextId"
                            :key="'advanced-gate-technical-report-'.($sectionContextId ?? 'none')"
                        />
                        @break

                    @case('service-definition')
                        <livewire:services.service-definition :service-id="$editingServiceId" :key="'service-definition-' . ($editingServiceId ?? 'new')" />
                        @break

                    @case('service-list')
                        <livewire:services.service-list :key="'service-list'" />
                        @break

                    @case('service-delivery')
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">تحویل خدمات</h1>
                            <p class="text-gray-600 mb-6">ابتدا مشخص کنید خدمت به چه کسی تحویل داده می‌شود، سپس فرآیند مربوطه را ادامه دهید.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    wire:click="selectSection('service-delivery-social-worker')"
                                    aria-label="تحویل خدمت به مددکار اجتماعی"
                                    class="group relative block w-full text-right overflow-hidden rounded-xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                >
                                    <span class="absolute inset-y-0 right-0 w-1 bg-cyan-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-cyan-600 mb-2">تحویل خدمت</p>
                                            <h2 class="text-base font-bold text-gray-800">تحویل خدمت به مددکار اجتماعی</h2>
                                            <p class="text-xs text-gray-500 mt-2">تخصیص سهمیه خدمات به مددکاران اجتماعی برای توزیع</p>
                                        </div>
                                        <div class="rounded-lg bg-cyan-100 p-2 text-cyan-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-cyan-600 transition group-hover:gap-2">
                                        ورود به بخش
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                        </svg>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="selectSection('service-delivery-beneficiary')"
                                    aria-label="تحویل مستقیم خدمت به مددجو / سرپرست"
                                    class="group relative block w-full text-right overflow-hidden rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                >
                                    <span class="absolute inset-y-0 right-0 w-1 bg-emerald-500"></span>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-semibold text-emerald-600 mb-2">تحویل خدمت</p>
                                            <h2 class="text-base font-bold text-gray-800">تحویل مستقیم خدمت به مددجو / سرپرست</h2>
                                            <p class="text-xs text-gray-500 mt-2">ثبت مستقیم و نهایی تحویل خدمت برای مددجو یا سرپرست خانوار</p>
                                        </div>
                                        <div class="rounded-lg bg-emerald-100 p-2 text-emerald-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 transition group-hover:gap-2">
                                        ورود به بخش
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                        @break

                    @case('service-delivery-social-worker')
                        <div class="mb-4">
                            <button
                                type="button"
                                wire:click="selectSection('service-delivery')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-100"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                                بازگشت به انتخاب نوع تحویل
                            </button>
                        </div>
                        <livewire:services.service-delivery-manager :key="'service-delivery-social-worker'" />
                        @break

                    @case('service-delivery-beneficiary')
                        <div class="mb-4">
                            <button
                                type="button"
                                wire:click="selectSection('service-delivery')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-100"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                                بازگشت به انتخاب نوع تحویل
                            </button>
                        </div>
                        <livewire:services.beneficiary-service-delivery :key="'service-delivery-beneficiary'" />
                        @break

                    @case('service-management')
                        <livewire:services.service-management :key="'service-management'" />
                        @break

                    @case('service-archive')
                        <livewire:services.service-archive :key="'service-archive'" />
                        @break

                    @case('activity-definition')
                        <livewire:activities.activity-definition :activity-id="$editingActivityId" :key="'activity-definition-' . ($editingActivityId ?? 'new')" />
                        @break

                    @case('activity-list')
                        <livewire:activities.activity-list :key="'activity-list'" />
                        @break


                    @case('activity-scanner')
                        @if($scanningActivityId)
                            <livewire:activities.activity-scanner :activity-id="$scanningActivityId" :key="'activity-scanner-' . $scanningActivityId" />
                        @else
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <p class="text-red-600 mb-4">فعالیت انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('activity-list')" class="btn btn-primary">بازگشت به لیست فعالیت‌ها</button>
                            </div>
                        @endif
                        @break

                    @case('activity-operator-assignments')
                        <livewire:admin.activity-operator-assignment :key="'activity-operator-assignments'" />
                        @break

                    @case('notifications-center')
                        <livewire:admin.notifications.notification-center :key="'notifications-center'" />
                        @break

                    @case('notifications-settings')
                        <livewire:admin.notifications.notification-settings :key="'notifications-settings'" />
                        @break

                    @case('system-settings-user-definition')
                        <livewire:admin.user-management :key="'system-settings-user-definition'" />
                        @break

                    @case('system-settings-user-list')
                        <livewire:admin.user-management :key="'system-settings-user-list'" :show-deleted-users="$showDeletedUsers" :list-only="true" />
                        @break

                    @case('system-settings-user-account')
                        <livewire:admin.user-account :key="'system-settings-user-account'" />
                        @break


                    @case('child-supporter-sponsor-registration')
                        <livewire:child-supporters.sponsor-registration :embedded="true" :key="'child-supporter-sponsor-registration'" />
                        @break

                    @case('child-supporter-sponsor-edit')
                        @if($editingSponsor)
                            <livewire:child-supporters.sponsor-registration :embedded="true" :sponsor-id="$editingSponsor->id" :key="'child-supporter-sponsor-edit-'.$editingSponsor->id" />
                        @else
                            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                                <p class="mb-4 text-red-600">حامی انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('child-supporter-sponsor-list')" class="btn btn-primary">بازگشت به لیست حامیان</button>
                            </div>
                        @endif
                        @break

                    @case('child-supporter-sponsor-list')
                        <livewire:child-supporters.sponsor-list :embedded="true" :key="'child-supporter-sponsor-list'" />
                        @break

                    @case('special-features-id-card-scanner')
                        <livewire:admin.id-card-scanner :key="'special-features-id-card-scanner'" />
                        @break

                    @case('special-features-print-client-card')
                        <livewire:admin.print-client-card :key="'special-features-print-client-card'" />
                        @break

                    @default
                        <div>
                            {{-- سربرگ بخش نمای کلی: هماهنگ با نوار رنگی کارت‌های آمار و میان‌برها --}}
                            <div class="mb-3 flex items-center gap-2.5 sm:mb-4">
                                <span class="h-7 w-1 shrink-0 rounded-full bg-gradient-to-b from-indigo-500 via-indigo-400 to-indigo-200 sm:h-8" aria-hidden="true"></span>
                                <h1 class="min-w-0 truncate text-base font-extrabold tracking-tight text-slate-800 sm:text-lg">خلاصه وضعیت مرکز نیکوکاری</h1>
                                <span class="ms-auto hidden shrink-0 text-[11px] font-medium text-slate-400 sm:block">نمای کلی پرونده‌ها و نیروی انسانی</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4 lg:gap-4">
                                <livewire:admin.dashboard.stat-card
                                    title="کل اعضای مرکز"
                                    caption="آمار تجمیعی"
                                    :value="$totalCenterMembers"
                                    suffix="نفر"
                                    color="indigo"
                                    icon="M2.25 12l8.954-8.955c.44-.44 1.152-.44 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>

                                <livewire:admin.dashboard.stat-card
                                    title="کودک"
                                    caption="مددجویان"
                                    :value="$totalPeople"
                                    suffix="نفر"
                                    color="sky"
                                    :badges="[
                                        ['label' => 'دختر', 'value' => $femaleCount, 'color' => 'rose'],
                                        ['label' => 'پسر', 'value' => $maleCount, 'color' => 'sky'],
                                    ]"
                                    icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="سرپرست فعال"
                                    caption="خانوار تحت پوشش"
                                    :value="$guardianCount"
                                    suffix="خانوار"
                                    color="emerald"
                                    icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="مددکار فعال"
                                    caption="مشغول به خدمت"
                                    :value="$totalSocialWorkers"
                                    suffix="نفر"
                                    color="violet"
                                    icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm sm:p-5">
                                @php
                                    /**
                                     * پالت رنگ میان‌برها به‌صورت رشته‌های کامل و ثابت نوشته شده است تا
                                     * Tailwind آن‌ها را در بیلد اسکن کند (هماهنگ با پالت کارت‌های آمار).
                                     */
                                    $shortcutPalettes = [
                                        'sky' => [
                                            'wash' => 'bg-gradient-to-bl from-sky-50/80 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-sky-500 via-sky-400 to-sky-200',
                                            'icon' => 'bg-sky-100/80 text-sky-600 ring-sky-200/60',
                                            'hover' => 'hover:ring-sky-200 focus:ring-sky-100',
                                            'hoverText' => 'group-hover:text-sky-700',
                                        ],
                                        'cyan' => [
                                            'wash' => 'bg-gradient-to-bl from-cyan-50/80 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-cyan-500 via-cyan-400 to-cyan-200',
                                            'icon' => 'bg-cyan-100/80 text-cyan-600 ring-cyan-200/60',
                                            'hover' => 'hover:ring-cyan-200 focus:ring-cyan-100',
                                            'hoverText' => 'group-hover:text-cyan-700',
                                        ],
                                        'amber' => [
                                            'wash' => 'bg-gradient-to-bl from-amber-50/80 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-amber-500 via-amber-400 to-amber-200',
                                            'icon' => 'bg-amber-100/80 text-amber-600 ring-amber-200/60',
                                            'hover' => 'hover:ring-amber-200 focus:ring-amber-100',
                                            'hoverText' => 'group-hover:text-amber-700',
                                        ],
                                        'rose' => [
                                            'wash' => 'bg-gradient-to-bl from-rose-50/80 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-rose-500 via-rose-400 to-rose-200',
                                            'icon' => 'bg-rose-100/80 text-rose-600 ring-rose-200/60',
                                            'hover' => 'hover:ring-rose-200 focus:ring-rose-100',
                                            'hoverText' => 'group-hover:text-rose-700',
                                        ],
                                        'emerald' => [
                                            'wash' => 'bg-gradient-to-bl from-emerald-50/80 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-emerald-500 via-emerald-400 to-emerald-200',
                                            'icon' => 'bg-emerald-100/80 text-emerald-600 ring-emerald-200/60',
                                            'hover' => 'hover:ring-emerald-200 focus:ring-emerald-100',
                                            'hoverText' => 'group-hover:text-emerald-700',
                                        ],
                                        'indigo' => [
                                            'wash' => 'bg-gradient-to-bl from-indigo-50/80 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-indigo-500 via-indigo-400 to-indigo-200',
                                            'icon' => 'bg-indigo-100/80 text-indigo-600 ring-indigo-200/60',
                                            'hover' => 'hover:ring-indigo-200 focus:ring-indigo-100',
                                            'hoverText' => 'group-hover:text-indigo-700',
                                        ],
                                    ];

                                    $quickAccessShortcuts = [
                                        [
                                            'label' => 'ثبت مددجو',
                                            'caption' => 'ثبت سریع',
                                            'section' => 'people-fast-create',
                                            'color' => 'sky',
                                            'gate' => null,
                                            'icon' => 'M12 4v16m8-8H4',
                                        ],
                                        [
                                            'label' => 'تحویل خدمت',
                                            'caption' => 'عملیات روزانه',
                                            'section' => 'service-delivery',
                                            'color' => 'cyan',
                                            'gate' => null,
                                            'icon' => 'M4 7h16M7 12h10M9 17h6',
                                        ],
                                        [
                                            'label' => 'ثبت فعالیت',
                                            'caption' => 'تعریف برنامه',
                                            'section' => 'activity-definition',
                                            'color' => 'amber',
                                            'gate' => 'full-access',
                                            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                        ],
                                        [
                                            'label' => 'ثبت حامی',
                                            'caption' => 'حامی کودک',
                                            'section' => 'child-supporter-sponsor-registration',
                                            'color' => 'rose',
                                            'gate' => 'full-access',
                                            'icon' => 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z',
                                        ],
                                        [
                                            'label' => 'اسکن کارت',
                                            'caption' => 'شناسایی سریع',
                                            'section' => 'special-features-id-card-scanner',
                                            'color' => 'emerald',
                                            'gate' => null,
                                            'icon' => 'M4 7V6a2 2 0 012-2h1M20 7V6a2 2 0 00-2-2h-1M4 17v1a2 2 0 002 2h1M20 17v1a2 2 0 01-2 2h-1M7 12h10',
                                        ],
                                        [
                                            'label' => 'گزارش پیشرفته',
                                            'caption' => 'تحلیل مددجویان',
                                            'section' => 'advanced-beneficiary-report',
                                            'color' => 'indigo',
                                            'gate' => null,
                                            'icon' => 'M9 17v-6m4 6V7m4 10v-3M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                                        ],
                                    ];
                                @endphp

                                {{-- سربرگ: هماهنگ با الگوی نوار رنگی سربرگ بخش‌ها --}}
                                <div class="flex items-center gap-2.5">
                                    <span class="h-6 w-1 shrink-0 rounded-full bg-gradient-to-b from-indigo-500 via-indigo-400 to-indigo-200" aria-hidden="true"></span>
                                    <h2 class="text-sm font-bold text-slate-800 sm:text-base">دسترسی سریع</h2>
                                    <span class="ms-auto hidden shrink-0 text-[11px] font-medium text-slate-400 sm:block">میان‌برهای عملیاتی روزانه</span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-6 sm:gap-3">
                                    @foreach($quickAccessShortcuts as $shortcut)
                                        @if(empty($shortcut['gate']) || auth()->user()->can($shortcut['gate']))
                                            @php $palette = $shortcutPalettes[$shortcut['color']]; @endphp
                                            <button
                                                type="button"
                                                wire:click="selectSection('{{ $shortcut['section'] }}')"
                                                aria-label="{{ $shortcut['label'] }} — {{ $shortcut['caption'] }}"
                                                class="group relative flex h-full items-center gap-2.5 overflow-hidden rounded-2xl p-3 text-right shadow-sm ring-1 ring-slate-200/70 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 {{ $palette['wash'] }} {{ $palette['hover'] }}"
                                            >
                                                {{-- نوار رنگی عمودی ابتدای کارت (سمت راست در RTL) --}}
                                                <span class="absolute inset-y-0 right-0 w-1 {{ $palette['bar'] }}" aria-hidden="true"></span>

                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-transform duration-300 group-hover:scale-105 {{ $palette['icon'] }}">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $shortcut['icon'] }}"></path>
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-xs font-bold text-slate-800 transition-colors duration-300 sm:text-sm {{ $palette['hoverText'] }}">{{ $shortcut['label'] }}</span>
                                                    <span class="mt-0.5 block truncate text-[10px] font-medium text-slate-400 sm:text-[11px]">{{ $shortcut['caption'] }}</span>
                                                </span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
                                <div class="xl:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                                    @php
                                        $birthMonthMax = max(1, (int) $birthMonthChart->max('count'));
                                        $birthMonthPeakMonth = $birthMonthTotal > 0 ? ($birthMonthChart->sortByDesc('count')->first()['month'] ?? null) : null;
                                    @endphp

                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100/80 text-indigo-600 ring-1 ring-indigo-200/60">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                                </svg>
                                            </span>
                                            <div>
                                                <h2 class="text-sm font-bold text-slate-800 sm:text-base">تعداد مددجویان بر اساس ماه تولد</h2>
                                                <p class="mt-0.5 text-[11px] text-slate-400">توزیع تولدها در ۱۲ ماه شمسی</p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 flex-col items-end gap-1">
                                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold tabular-nums text-indigo-700 ring-1 ring-indigo-100">
                                                {{ number_format($birthMonthTotal) }} مددجو با ماه تولد
                                            </span>
                                            @if($birthMonthUnknown > 0)
                                                <span class="text-[10px] font-medium text-slate-400">+ {{ number_format($birthMonthUnknown) }} بدون ثبت ماه تولد</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($birthMonthTotal === 0)
                                        <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
                                            <p class="text-sm font-medium text-slate-500">هنوز مددجویی با ماه تولد ثبت‌شده وجود ندارد.</p>
                                            <p class="mt-1 text-xs text-slate-400">با تکمیل تاریخ تولد در پرونده‌ها، این نمودار فعال می‌شود.</p>
                                        </div>
                                    @else
                                        {{-- نمودار ستونی (دسکتاپ): ماه اوج با گرادیان پررنگ‌تر و تولتیپ سهم در هاور --}}
                                        <div class="mt-5 hidden md:block">
                                            <div class="relative flex h-60 items-end gap-2 rounded-t-xl border-b-2 border-slate-200 bg-gradient-to-b from-slate-50/80 to-white px-4">
                                                <div class="pointer-events-none absolute inset-x-4 inset-y-0 flex flex-col justify-between" aria-hidden="true">
                                                    <span class="border-t border-dashed border-slate-200"></span>
                                                    <span class="border-t border-dashed border-slate-200"></span>
                                                    <span class="border-t border-dashed border-slate-200"></span>
                                                </div>
                                                @foreach($birthMonthChart as $monthData)
                                                    @php
                                                        $isPeakMonth = $monthData['month'] === $birthMonthPeakMonth;
                                                        $monthShare = $birthMonthTotal > 0 ? (int) round($monthData['count'] * 100 / $birthMonthTotal) : 0;
                                                        $barHeight = $monthData['count'] > 0 ? max(5, (int) round($monthData['count'] * 100 / $birthMonthMax)) : 2;
                                                    @endphp
                                                    <div class="group/bar relative flex h-full min-w-0 flex-1 cursor-default flex-col items-center justify-end">
                                                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-800 px-2 py-1 text-[10px] font-bold text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover/bar:opacity-100">
                                                            {{ number_format($monthData['count']) }} نفر — {{ $monthShare }}٪
                                                        </div>
                                                        <p class="mb-1.5 text-[11px] font-bold tabular-nums {{ $isPeakMonth ? 'text-indigo-700' : ($monthData['count'] > 0 ? 'text-slate-500' : 'text-slate-300') }}">{{ number_format($monthData['count']) }}</p>
                                                        <div
                                                            class="w-full max-w-[34px] rounded-t-lg transition-all duration-300 {{ $isPeakMonth ? 'bg-gradient-to-t from-indigo-700 via-indigo-500 to-indigo-300 shadow-[0_-8px_18px_-12px_rgba(79,70,229,0.65)]' : 'bg-gradient-to-t from-indigo-400/90 via-indigo-300/70 to-indigo-200/50 group-hover/bar:from-indigo-600 group-hover/bar:via-indigo-500 group-hover/bar:to-indigo-300' }}"
                                                            style="height: {{ $barHeight }}%;"
                                                        ></div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-2 flex gap-2 px-4">
                                                @foreach($birthMonthChart as $monthData)
                                                    <div class="flex min-w-0 flex-1 justify-center">
                                                        <span class="truncate text-[10px] {{ $monthData['month'] === $birthMonthPeakMonth ? 'font-bold text-indigo-700' : 'font-medium text-slate-500' }}">{{ $monthData['label'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- لیست افقی (موبایل): بدون اسکرول افقی، خوانا در ۳۶۰px --}}
                                        <div class="mt-4 space-y-2 md:hidden">
                                            @foreach($birthMonthChart as $monthData)
                                                @php
                                                    $isPeakMonth = $monthData['month'] === $birthMonthPeakMonth;
                                                    $lineWidth = $monthData['count'] > 0 ? max(10, (int) round($monthData['count'] * 100 / $birthMonthMax)) : 0;
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                    <span class="w-14 shrink-0 text-[11px] {{ $isPeakMonth ? 'font-bold text-indigo-700' : 'font-medium text-slate-500' }}">{{ $monthData['label'] }}</span>
                                                    <div class="h-5 flex-1 overflow-hidden rounded-lg bg-slate-100">
                                                        <div class="h-full rounded-lg {{ $isPeakMonth ? 'bg-gradient-to-l from-indigo-700 to-indigo-400' : 'bg-gradient-to-l from-indigo-400/80 to-indigo-300/60' }}" style="width: {{ $lineWidth }}%;"></div>
                                                    </div>
                                                    <span class="w-9 shrink-0 text-end text-[11px] font-bold tabular-nums {{ $monthData['count'] > 0 ? 'text-slate-700' : 'text-slate-300' }}">{{ number_format($monthData['count']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100/80 text-amber-600 ring-1 ring-amber-200/60">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 00-12 0v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                                            </svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h2 class="truncate text-sm font-bold text-slate-800 sm:text-base">یادآوری‌ها</h2>
                                            <p class="mt-0.5 truncate text-[11px] text-slate-400">فضای شخصی — کارهای روزمرۀ شما</p>
                                        </div>
                                        <button
                                            type="button"
                                            x-ref="reminderToggle"
                                            @click="openReminderDrawer()"
                                            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-bold text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                        >
                                            مدیریت
                                        </button>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-2">
                                        <div class="rounded-xl bg-amber-50/80 px-3 py-2.5 ring-1 ring-amber-100">
                                            <p class="flex items-center gap-1.5 text-[11px] font-medium text-amber-700">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400" aria-hidden="true"></span>
                                                باز
                                            </p>
                                            <p class="mt-1 text-xl font-bold leading-none tabular-nums text-slate-800">{{ number_format($openReminders->count()) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-emerald-50/80 px-3 py-2.5 ring-1 ring-emerald-100">
                                            <p class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-700">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400" aria-hidden="true"></span>
                                                انجام‌شده
                                            </p>
                                            <p class="mt-1 text-xl font-bold leading-none tabular-nums text-slate-800">{{ number_format($doneReminders->count()) }}</p>
                                        </div>
                                    </div>

                                    {{-- پیش‌نمایش سه یادآوری باز: امکان تیک‌زدن مستقیم بدون باز‌کردن پنل --}}
                                    <div class="mt-4 flex-1 space-y-2">
                                        @forelse($openReminders->take(3) as $reminder)
                                            <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/70 px-2.5 py-2 transition hover:border-amber-200 hover:bg-amber-50/50">
                                                <button
                                                    type="button"
                                                    wire:click="toggleReminder({{ $reminder->id }})"
                                                    aria-label="انجام شد: {{ $reminder->title }}"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full focus:outline-none focus:ring-4 focus:ring-amber-100"
                                                >
                                                    <span class="h-4 w-4 rounded-full border-2 border-amber-400 bg-white transition hover:border-amber-500"></span>
                                                </button>
                                                <p class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700">{{ $reminder->title }}</p>
                                                <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-slate-200/70">{{ $reminderCategories[$reminder->category] ?? $reminder->category }}</span>
                                            </div>
                                        @empty
                                            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-5 text-center">
                                                <p class="text-xs font-semibold text-slate-500">{{ $reminders->isEmpty() ? 'هنوز یادآوری شخصی ثبت نکرده‌اید' : 'همۀ یادآوری‌هایتان انجام شده است' }}</p>
                                                <p class="mt-1 text-[11px] text-slate-400">با دکمۀ «مدیریت» یادآوری جدید اضافه کنید.</p>
                                            </div>
                                        @endforelse

                                        @if($openReminders->count() > 3)
                                            <p class="text-center text-[11px] font-medium text-slate-400">و {{ number_format($openReminders->count() - 3) }} مورد دیگر در «مدیریت یادآوری‌ها»</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- نبض عملیات مرکز: سنجه‌های زنده توزیع، موجودی و حضور (فقط full-access) --}}
                            @if (! empty($opsPulse))
                                @php
                                    /**
                                     * پالت رنگ کارت‌های نبض به‌صورت رشته‌های کامل و ثابت نوشته شده است تا
                                     * Tailwind آن‌ها را در بیلد اسکن کند (الگوی پالت دسترسی سریع).
                                     * هویت رنگی: rose=معوقه، cyan=تحویل، amber=موجودی، violet=حضور.
                                     */
                                    $pulsePalettes = [
                                        'rose' => [
                                            'wash' => 'bg-gradient-to-bl from-rose-50/70 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-rose-500 via-rose-400 to-rose-200',
                                            'icon' => 'bg-rose-100/80 text-rose-600 ring-rose-200/60',
                                            'hover' => 'hover:ring-rose-200 focus:ring-rose-100',
                                            'hoverText' => 'group-hover:text-rose-700',
                                            'link' => 'text-rose-700',
                                        ],
                                        'cyan' => [
                                            'wash' => 'bg-gradient-to-bl from-cyan-50/70 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-cyan-500 via-cyan-400 to-cyan-200',
                                            'icon' => 'bg-cyan-100/80 text-cyan-600 ring-cyan-200/60',
                                            'hover' => 'hover:ring-cyan-200 focus:ring-cyan-100',
                                            'hoverText' => 'group-hover:text-cyan-700',
                                            'link' => 'text-cyan-700',
                                        ],
                                        'amber' => [
                                            'wash' => 'bg-gradient-to-bl from-amber-50/70 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-amber-500 via-amber-400 to-amber-200',
                                            'icon' => 'bg-amber-100/80 text-amber-600 ring-amber-200/60',
                                            'hover' => 'hover:ring-amber-200 focus:ring-amber-100',
                                            'hoverText' => 'group-hover:text-amber-700',
                                            'link' => 'text-amber-700',
                                        ],
                                        'violet' => [
                                            'wash' => 'bg-gradient-to-bl from-violet-50/70 via-white to-white',
                                            'bar' => 'bg-gradient-to-b from-violet-500 via-violet-400 to-violet-200',
                                            'icon' => 'bg-violet-100/80 text-violet-600 ring-violet-200/60',
                                            'hover' => 'hover:ring-violet-200 focus:ring-violet-100',
                                            'hoverText' => 'group-hover:text-violet-700',
                                            'link' => 'text-violet-700',
                                        ],
                                    ];

                                    /**
                                     * چیپ وضعیت فوتر کارت: alert برای سنجه‌های نیازمند اقدام،
                                     * ok برای وضعیت پایدار، live برای سنجۀ زنده حاضران.
                                     */
                                    $pulseTones = [
                                        'alert' => 'bg-rose-50 text-rose-700 ring-rose-200/70',
                                        'ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
                                        'neutral' => 'bg-slate-100 text-slate-600 ring-slate-200/70',
                                        'live' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
                                    ];
                                @endphp

                                <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                                    {{-- سربرگ: الگوی نوار رنگی بخش‌ها؛ هویت rose چون محور این بخش «توجه لازم» است --}}
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="h-7 w-1 shrink-0 rounded-full bg-gradient-to-b from-rose-500 via-rose-400 to-rose-200" aria-hidden="true"></span>
                                            <div class="min-w-0">
                                                <h2 class="truncate text-sm font-bold text-slate-800 sm:text-base">نبض عملیات مرکز</h2>
                                                <p class="mt-0.5 truncate text-[11px] text-slate-400">سنجه‌های لحظه‌ای توزیع، موجودی و حضور</p>
                                            </div>
                                        </div>
                                        <span class="hidden shrink-0 text-[11px] font-medium text-slate-400 sm:block">با لمس هر کارت، بخش مرتبط باز می‌شود</span>
                                    </div>

                                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        @foreach ($opsPulse as $pulseItem)
                                            @php $pulse = $pulsePalettes[$pulseItem['color']]; @endphp
                                            <button
                                                type="button"
                                                wire:click="selectSection('{{ $pulseItem['section'] }}')"
                                                aria-label="{{ $pulseItem['label'] }} — {{ number_format($pulseItem['value']) }} {{ $pulseItem['unit'] }} — ورود به بخش مرتبط"
                                                class="group relative flex h-full flex-col overflow-hidden rounded-2xl p-4 text-right shadow-sm ring-1 ring-slate-200/70 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 {{ $pulse['wash'] }} {{ $pulse['hover'] }}"
                                            >
                                                {{-- نوار رنگی عمودی ابتدای کارت (سمت راست در RTL) --}}
                                                <span class="absolute inset-y-0 right-0 w-1 {{ $pulse['bar'] }}" aria-hidden="true"></span>

                                                <span class="flex items-start gap-2.5">
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-transform duration-300 group-hover:scale-105 {{ $pulse['icon'] }}">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $pulseItem['icon'] }}"></path>
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-xs font-bold leading-snug text-slate-700 transition-colors duration-300 {{ $pulse['hoverText'] }}">{{ $pulseItem['label'] }}</span>
                                                        <span class="mt-0.5 block truncate text-[10px] font-medium text-slate-400">{{ $pulseItem['caption'] }}</span>
                                                    </span>
                                                </span>

                                                {{-- مقدار اصلی: الگوی عدد درشت tabular با واحد کوچک --}}
                                                <span class="mt-3.5 flex items-end justify-between gap-2">
                                                    <span class="whitespace-nowrap text-[26px] font-extrabold leading-none tracking-tight tabular-nums text-slate-900">{{ number_format($pulseItem['value']) }}</span>
                                                    <span class="pb-1 text-[10px] font-medium text-slate-400">{{ $pulseItem['unit'] }}</span>
                                                </span>

                                                {{-- چیپ‌های خنثی ریزجزئیات: خوانا روی هر پس‌زمینه‌ای، هماهنگ با بَج کارت‌های آمار --}}
                                                @if (! empty($pulseItem['badges']))
                                                    <span class="mt-2.5 flex flex-wrap gap-1.5">
                                                        @foreach ($pulseItem['badges'] as $badge)
                                                            @php
                                                                $badgeDot = match ($badge['dot'] ?? 'slate') {
                                                                    'rose' => 'bg-rose-400',
                                                                    'amber' => 'bg-amber-400',
                                                                    default => 'bg-slate-300',
                                                                };
                                                            @endphp
                                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-500 shadow-sm ring-1 ring-slate-200/70">
                                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $badgeDot }}" aria-hidden="true"></span>
                                                                <span>{{ $badge['label'] }}</span>
                                                                <span class="font-bold tabular-nums text-slate-800">{{ number_format($badge['value']) }}</span>
                                                            </span>
                                                        @endforeach
                                                    </span>
                                                @endif

                                                {{-- فوتر: چیپ وضعیت + راهنمای ورود به بخش --}}
                                                <span class="mt-auto flex items-center justify-between gap-2 border-t border-slate-200/60 pt-3">
                                                    <span class="inline-flex min-w-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $pulseTones[$pulseItem['tone']] }}">
                                                        @if ($pulseItem['live'] ?? false)
                                                            <span class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
                                                        @endif
                                                        <span class="truncate">{{ $pulseItem['status'] }}</span>
                                                    </span>
                                                    <span class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold {{ $pulse['link'] }}">
                                                        مشاهده
                                                        <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                                                        </svg>
                                                    </span>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                                {{-- سربرگ: الگوی نوار رنگی بخش‌ها؛ هویت رنگی sky هماهنگ با کارت آمار «کودک» --}}
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <span class="h-7 w-1 shrink-0 rounded-full bg-gradient-to-b from-sky-500 via-sky-400 to-sky-200" aria-hidden="true"></span>
                                        <div class="min-w-0">
                                            <h2 class="truncate text-sm font-bold text-slate-800 sm:text-base">آخرین مددجویان ثبت‌شده</h2>
                                            <p class="mt-0.5 truncate text-[11px] text-slate-400">بررسی سریع ۸ پروندۀ اخیر برای تکمیل و پیگیری مددکار</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="selectSection('people-list')"
                                        class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3.5 text-xs font-bold text-sky-700 transition hover:bg-sky-100 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                    >
                                        مشاهده همه مددجویان
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    @forelse($latestPeople as $person)
                                        @php
                                            $socialWorker = $person->guardian?->socialWorker;
                                            $socialWorkerName = $socialWorker
                                                ? trim(implode(' ', array_filter([$socialWorker->first_name, $socialWorker->last_name])))
                                                : null;
                                            $personFullName = trim(implode(' ', array_filter([$person->first_name, $person->last_name])));
                                            $personDisplayTitle = $personFullName !== '' ? $personFullName : 'مددجوی بدون نام';
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="selectSection('person-edit', {{ $person->id }})"
                                            aria-label="مشاهده پروندۀ {{ $personDisplayTitle }}"
                                            class="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-gradient-to-bl from-sky-50/70 via-white to-white p-4 text-right shadow-sm ring-1 ring-slate-200/70 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:ring-sky-200 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                        >
                                            {{-- نوار رنگی عمودی ابتدای کارت (سمت راست در RTL) --}}
                                            <span class="absolute inset-y-0 right-0 w-1 bg-gradient-to-b from-sky-500 via-sky-400 to-sky-200" aria-hidden="true"></span>

                                            <span class="flex items-start gap-2.5">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100/80 text-sky-600 ring-1 ring-sky-200/60 transition-transform duration-300 group-hover:scale-105">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.9 17.9 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-bold text-slate-800 transition-colors duration-300 group-hover:text-sky-700">{{ $personDisplayTitle }}</span>
                                                    <span class="mt-0.5 block truncate text-[10px] font-medium text-slate-400">کد {{ $person->person_code ?? '-' }}</span>
                                                </span>
                                            </span>

                                            {{-- چیپ‌های خنثی: خوانا روی هر پس‌زمینه‌ای، هماهنگ با بَج کارت‌های آمار --}}
                                            <span class="mt-3 flex flex-wrap gap-1.5">
                                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-500 shadow-sm ring-1 ring-slate-200/70">
                                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $person->gender === 'female' ? 'bg-rose-400' : 'bg-sky-400' }}" aria-hidden="true"></span>
                                                    {{ $person->gender_label ?: 'نامشخص' }}
                                                </span>
                                                @if($person->age !== null)
                                                    <span class="inline-flex items-center rounded-lg bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-500 shadow-sm ring-1 ring-slate-200/70">
                                                        {{ number_format($person->age) }} ساله
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center rounded-lg bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-500 shadow-sm ring-1 ring-slate-200/70">
                                                    خانوار {{ $person->guardian?->guardian_code ?? '-' }}
                                                </span>
                                            </span>

                                            {{-- اطلاعات تشخیص هویت --}}
                                            <span class="mt-3 block space-y-1.5 border-t border-slate-200/60 pt-3 text-[11px]">
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="shrink-0 text-slate-400">کد ملی</span>
                                                    <span class="truncate font-mono text-slate-700">{{ $person->national_id ?: '-' }}</span>
                                                </span>
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="shrink-0 text-slate-400">نام پدر</span>
                                                    <span class="truncate text-slate-700">{{ $person->father_name ?: '-' }}</span>
                                                </span>
                                            </span>

                                            {{-- فوتر: وضعیت پیگیری + راهنمای ورود به پرونده --}}
                                            <span class="mt-auto flex items-center justify-between gap-2 border-t border-slate-200/60 pt-3">
                                                <span class="inline-flex min-w-0 items-center gap-1.5 text-[11px] font-bold {{ $socialWorkerName ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $socialWorkerName ? 'bg-emerald-400' : 'bg-amber-400' }}" aria-hidden="true"></span>
                                                    <span class="truncate">{{ $socialWorkerName ? 'مددکار: '.$socialWorkerName : 'نیازمند پیگیری' }}</span>
                                                </span>
                                                <span class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-sky-700">
                                                    پرونده
                                                    <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                                                    </svg>
                                                </span>
                                            </span>
                                        </button>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center sm:col-span-2 xl:col-span-4">
                                            <p class="text-sm font-semibold text-slate-500">هنوز مددجویی ثبت نشده است.</p>
                                            <p class="mt-1 text-xs text-slate-400">پس از ثبت اولین پرونده، آخرین ثبت‌ها در این بخش نمایش داده می‌شوند.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                @endswitch
            </div>
        </main>
    </div>
</div>
