<div
    x-data="{
        sidebarOpen: window.innerWidth >= 1024,
        reminderDrawerOpen: false,
        resizeHandler: null,
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
    @open-dashboard-section.window="closeSidebarOnMobile(); closeReminderDrawer()"
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
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <p class="text-xs font-semibold tracking-[0.16em] text-slate-400">فضای شخصی</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-800">یادآوری‌های من</h2>
                <p class="mt-1 text-sm text-slate-500">مدیریت کارهای شخصی بدون شلوغ‌کردن داشبورد اصلی</p>
            </div>
            <button
                type="button"
                @click="closeReminderDrawer()"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100"
                aria-label="بستن یادآوری‌ها"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            <form wire:submit.prevent="addReminder" class="space-y-3">
                <div>
                    <label for="dashboard-reminder-title" class="mb-1 block text-xs font-semibold text-slate-500">متن یادآوری</label>
                    <input
                        id="dashboard-reminder-title"
                        type="text"
                        wire:model.defer="newReminderTitle"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                        placeholder="یادآوری جدید ثبت کنید..."
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
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                    >
                        <option value="today_tasks">کارهای امروز</option>
                        <option value="pending_approvals">موارد در انتظار تایید</option>
                        <option value="contract_deadlines">سررسید قراردادها</option>
                        <option value="required_reports">گزارش‌های مورد نیاز</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                >
                    افزودن یادآوری
                </button>
            </form>

            <div class="mt-6 space-y-2">
                @forelse($reminders as $reminder)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" wire:click="toggleReminder({{ $reminder->id }})" class="flex-1 text-right">
                                <p class="text-sm font-medium {{ $reminder->is_done ? 'text-slate-400 line-through' : 'text-slate-700' }}">
                                    {{ $reminder->title }}
                                </p>
                                <p class="mt-1 text-[11px] {{ $reminder->is_done ? 'text-slate-400' : 'text-indigo-600' }}">
                                    {{
                                        match($reminder->category) {
                                            'today_tasks' => 'کارهای امروز',
                                            'pending_approvals' => 'در انتظار تایید',
                                            'contract_deadlines' => 'سررسید قرارداد',
                                            'required_reports' => 'گزارش مورد نیاز',
                                        }
                                    }}
                                </p>
                            </button>

                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $reminder->is_done ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $reminder->is_done ? 'انجام شد' : 'باز' }}
                                </span>
                                <button type="button" wire:click="deleteReminder({{ $reminder->id }})" class="text-xs font-medium text-red-500 transition hover:text-red-700">
                                    حذف
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
                        هنوز یادآوری شخصی ثبت نشده است.
                    </div>
                @endforelse
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 min-h-0 flex-1 w-full flex-col overflow-y-auto">
        @include('layouts.partials.header')

        <main class="min-h-0 px-2 py-4 lg:px-4">
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
                            </div>
                        </div>
                        @break

                    @case('advanced-beneficiary-report')
                        <livewire:people.advanced-filter-builder :key="'advanced-beneficiary-report'" />
                        @break

                    @case('advanced-service-report')
                        <livewire:services.service-reports :selected-service-id="$serviceReportServiceId" :key="'advanced-service-report-'.($serviceReportServiceId ?? 'list')" />
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

                    @case('service-definition')
                        <livewire:services.service-definition :service-id="$editingServiceId" :key="'service-definition-' . ($editingServiceId ?? 'new')" />
                        @break

                    @case('service-list')
                        <livewire:services.service-list :key="'service-list'" />
                        @break

                    @case('service-delivery')
                        <livewire:services.service-delivery-manager :key="'service-delivery'" />

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


                    @default
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-6">خلاصه وضعیت مرکز نیکوکاری</h1>

                            <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-4">
                                <livewire:admin.dashboard.stat-card
                                    title="کل اعضای مرکز"
                                    :value="$totalCenterMembers . ' نفر '"
                                    color="blue"
                                    icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="کودک"
                                    :value="$totalPeople . ' نفر '"
                                    color="blue"
                                    :badges="[
                                        ['label' => 'دختر', 'value' => $femaleCount, 'color' => 'rose'],
                                        ['label' => 'پسر', 'value' => $maleCount, 'color' => 'sky'],
                                    ]"
                                    icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="سرپرست فعال"
                                    :value="$guardianCount  . ' خانوار '"
                                    color="blue"
                                    icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="مددکار فعال"
                                    :value="$totalSocialWorkers  . ' نفر '"
                                    color="blue"
                                    icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </div>

                            @php
                                $maxBirthMonthCount = max(1, $birthMonthChart->max('count') ?? 1);
                            @endphp

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold tracking-[0.16em] text-slate-400">دسترسی سریع</p>
                                        <h2 class="mt-1 text-lg font-semibold text-slate-800">اقدام‌های اصلی</h2>
                                    </div>
                                    <p class="text-sm text-slate-500">میانبر بخش‌های پرتکرار برای اجرای سریع کارهای روزانه</p>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-6">
                                    <button
                                        type="button"
                                        wire:click="selectSection('people-fast-create')"
                                        class="group inline-flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-right transition duration-200 hover:border-sky-200 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                    >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">ثبت مددجو</span>
                                            <span class="mt-1 block text-[11px] text-slate-500">ثبت سریع</span>
                                        </span>
                                        <span class="mr-3 rounded-lg bg-sky-100 p-2 text-sky-700 transition group-hover:bg-sky-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="selectSection('service-delivery')"
                                        class="group inline-flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-right transition duration-200 hover:border-cyan-200 hover:bg-cyan-50 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                    >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">تحویل خدمت</span>
                                            <span class="mt-1 block text-[11px] text-slate-500">عملیات روزانه</span>
                                        </span>
                                        <span class="mr-3 rounded-lg bg-cyan-100 p-2 text-cyan-700 transition group-hover:bg-cyan-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M7 12h10M9 17h6"></path>
                                            </svg>
                                        </span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="selectSection('activity-definition')"
                                        class="group inline-flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-right transition duration-200 hover:border-amber-200 hover:bg-amber-50 focus:outline-none focus:ring-4 focus:ring-amber-100"
                                    >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">ثبت فعالیت</span>
                                            <span class="mt-1 block text-[11px] text-slate-500">تعریف برنامه</span>
                                        </span>
                                        <span class="mr-3 rounded-lg bg-amber-100 p-2 text-amber-700 transition group-hover:bg-amber-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="selectSection('child-supporter-sponsor-registration')"
                                        class="group inline-flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-right transition duration-200 hover:border-rose-200 hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100"
                                    >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">ثبت حامی</span>
                                            <span class="mt-1 block text-[11px] text-slate-500">حامی کودک</span>
                                        </span>
                                        <span class="mr-3 rounded-lg bg-rose-100 p-2 text-rose-700 transition group-hover:bg-rose-200">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                            </svg>
                                        </span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="selectSection('special-features-id-card-scanner')"
                                        class="group inline-flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-right transition duration-200 hover:border-emerald-200 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                    >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">اسکن کارت</span>
                                            <span class="mt-1 block text-[11px] text-slate-500">شناسایی سریع</span>
                                        </span>
                                        <span class="mr-3 rounded-lg bg-emerald-100 p-2 text-emerald-700 transition group-hover:bg-emerald-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7V6a2 2 0 012-2h1M20 7V6a2 2 0 00-2-2h-1M4 17v1a2 2 0 002 2h1M20 17v1a2 2 0 01-2 2h-1M7 12h10"></path>
                                            </svg>
                                        </span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="selectSection('advanced-beneficiary-report')"
                                        class="group inline-flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-right transition duration-200 hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    >
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">گزارش پیشرفته</span>
                                            <span class="mt-1 block text-[11px] text-slate-500">تحلیل مددجویان</span>
                                        </span>
                                        <span class="mr-3 rounded-lg bg-indigo-100 p-2 text-indigo-700 transition group-hover:bg-indigo-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-6m4 6V7m4 10v-3M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            @php
                                $pendingReminderCount = $reminders->where('is_done', false)->count();
                                $completedReminderCount = $reminders->where('is_done', true)->count();
                            @endphp

                            <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
                                <div class="xl:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="flex items-center justify-between mb-5">
                                        <h2 class="text-lg font-semibold text-gray-800">تعداد مددجویان بر اساس ماه تولد</h2>
                                        <span class="text-xs text-gray-500">فروردین تا اسفند</span>
                                    </div>

                                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/30 p-4">
                                        <div class="h-72 flex items-end gap-1 md:gap-2 pb-2 overflow-x-auto xl:overflow-x-hidden">
                                        @foreach($birthMonthChart as $monthData)
                                            @php
                                                $heightPercentage = round(($monthData['count'] / $maxBirthMonthCount) * 100, 2);
                                            @endphp
                                                <div class="min-w-[44px] xl:min-w-0 xl:flex-1 flex flex-col items-center justify-end h-full">
                                                    <p class="mb-2 text-xs font-bold text-indigo-700">{{ number_format($monthData['count']) }}</p>
                                                    <div
                                                        class="w-8 md:w-9 xl:w-full rounded-t-md bg-gradient-to-t from-indigo-600 via-indigo-500 to-indigo-400 shadow-[0_10px_24px_-18px_rgba(99,102,241,0.45)] transition-all duration-300"
                                                        style="height: {{ max($heightPercentage, $monthData['count'] > 0 ? 4 : 0) }}%;"
                                                        title="{{ $monthData['count'] }} نفر"
                                                    ></div>
                                                    <p class="mt-2 text-[11px] text-gray-700 text-center whitespace-nowrap">{{ $monthData['label'] }}</p>
                                                </div>
                                        @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] font-semibold tracking-[0.14em] text-slate-400">فضای شخصی</p>
                                            <h2 class="mt-1 text-sm font-semibold text-slate-800">یادآوری‌ها</h2>
                                        </div>

                                        <button
                                            type="button"
                                            x-ref="reminderToggle"
                                            @click="openReminderDrawer()"
                                            class="inline-flex h-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                        >
                                            مدیریت
                                        </button>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-2">
                                        <div class="rounded-xl bg-amber-50 px-3 py-2">
                                            <p class="text-[11px] text-amber-700">باز</p>
                                            <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($pendingReminderCount) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-emerald-50 px-3 py-2">
                                            <p class="text-[11px] text-emerald-700">انجام‌شده</p>
                                            <p class="mt-1 text-base font-semibold text-slate-800">{{ number_format($completedReminderCount) }}</p>
                                        </div>
                                    </div>

                                    <p class="mt-4 text-xs leading-6 text-slate-500">
                                        یادآوری‌های شخصی از بدنه اصلی داشبورد جدا شده‌اند تا صفحه خلوت بماند.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-8 overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800">آخرین مددجویان ثبت شده</h2>
                                        <p class="mt-1 text-sm text-gray-500">نمایی سریع از ثبت‌های جدید برای بررسی، تکمیل پرونده و ارجاع به بخش جزئیات.</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="selectSection('people-list')"
                                        class="inline-flex items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    >
                                        مشاهده همه مددجویان
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    @forelse($latestPeople as $person)
                                        @php
                                            $socialWorker = $person->guardian?->socialWorker;
                                            $socialWorkerName = $socialWorker
                                                ? trim(implode(' ', array_filter([$socialWorker->first_name, $socialWorker->last_name])))
                                                : null;
                                            $personFullName = trim(implode(' ', array_filter([$person->first_name, $person->last_name])));
                                        @endphp

                                        <article class="group flex h-full flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white hover:shadow-md">
                                            <div>
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <h3 class="truncate text-sm font-semibold text-slate-800 sm:text-base">
                                                            {{ $personFullName !== '' ? $personFullName : 'مددجوی بدون نام' }}
                                                        </h3>
                                                        <p class="mt-1 text-[11px] text-slate-400">
                                                            ثبت جدید مددجو
                                                        </p>
                                                    </div>
                                                    <span class="inline-flex shrink-0 items-center rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-700">
                                                        {{ $person->person_code ?? '-' }}
                                                    </span>
                                                </div>

                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">
                                                        خانوار {{ $person->guardian?->guardian_code ?? '-' }}
                                                    </span>
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $socialWorkerName ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                                        {{ $socialWorkerName ?: 'بدون مددکار' }}
                                                    </span>
                                                </div>

                                                <dl class="mt-4 space-y-2">
                                                    <div class="flex items-center justify-between gap-3 text-xs">
                                                        <dt class="text-slate-400">کد ملی</dt>
                                                        <dd class="truncate font-mono text-slate-700">{{ $person->national_id ?: '-' }}</dd>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-3 text-xs">
                                                        <dt class="text-slate-400">نام پدر</dt>
                                                        <dd class="truncate text-slate-700">{{ $person->father_name ?: '-' }}</dd>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-3 text-xs">
                                                        <dt class="text-slate-400">جنسیت</dt>
                                                        <dd class="text-slate-700">{{ $person->gender_label ?: '-' }}</dd>
                                                    </div>
                                                </dl>
                                            </div>

                                            <div class="mt-4 flex items-center justify-between border-t border-slate-200/80 pt-3">
                                                <span class="text-[11px] font-medium {{ $socialWorkerName ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    {{ $socialWorkerName ? 'ارجاع شده' : 'نیازمند پیگیری' }}
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="selectSection('person-edit', {{ $person->id }})"
                                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                                >
                                                    مشاهده پرونده
                                                </button>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center sm:col-span-2 xl:col-span-4">
                                            <p class="text-sm font-medium text-gray-500">هنوز مددجویی ثبت نشده است.</p>
                                            <p class="mt-1 text-xs text-gray-400">پس از ثبت اولین پرونده، آخرین ثبت‌ها در این بخش نمایش داده می‌شوند.</p>
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
