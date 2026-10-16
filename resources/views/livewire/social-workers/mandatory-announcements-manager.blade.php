<div class="mx-auto max-w-7xl space-y-6">
    {{-- هدر صفحه و تب‌ها --}}
    <div class="rounded-2xl border border-cyan-100 bg-gradient-to-br from-white via-cyan-50/20 to-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-600 to-sky-700 text-white shadow-md shadow-cyan-200/50">
                    <i class="fa fa-bullhorn text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800 sm:text-2xl">بخشنامه‌ها و پیام‌های الزامی مدیریت</h1>
                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                        ابلاغ دستورالعمل‌های مسدودکننده، اطلاعیه‌های مهم به مددکاران اجتماعی و رهگیری دقیق تاییدیه و لاگ مطالعه (Audit Log)
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="switchTab('list')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition sm:text-sm {{ $activeTab === 'list' ? 'bg-cyan-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <i class="fa fa-list"></i>
                    <span>لیست و تاریخچه بخشنامه‌ها</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'list' ? 'bg-cyan-700 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $announcements->total() }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="switchTab('create')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition sm:text-sm {{ $activeTab === 'create' ? 'bg-cyan-600 text-white shadow-sm' : 'border border-cyan-300 bg-cyan-50 text-cyan-800 hover:bg-cyan-100' }}"
                >
                    <i class="fa fa-plus-circle"></i>
                    <span>ثبت بخشنامه جدید</span>
                </button>
            </div>
        </div>
    </div>

    {{-- پیام‌های سیستمی --}}
    @if (session()->has('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-emerald-800 shadow-sm">
            <i class="fa fa-check-circle text-lg text-emerald-600"></i>
            <span class="text-sm font-bold">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-rose-800 shadow-sm">
            <i class="fa fa-exclamation-triangle text-lg text-rose-600"></i>
            <span class="text-sm font-bold">{{ session('error') }}</span>
        </div>
    @endif

    {{-- تب اول: لیست بخشنامه‌ها --}}
    @if ($activeTab === 'list')
        <div class="space-y-4">
            {{-- فیلترها و جستجو --}}
            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-md">
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="search"
                        placeholder="جستجو در عنوان یا متن بخشنامه‌ها..."
                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2.5 pl-4 pr-10 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                    />
                    <i class="fa fa-search absolute right-3.5 top-3.5 text-slate-400"></i>
                    @if($search !== '')
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute left-3 top-3 text-slate-400 hover:text-slate-600"
                        >
                            <i class="fa fa-times-circle"></i>
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <label class="whitespace-nowrap text-xs font-bold text-slate-500 sm:text-sm">اولویت:</label>
                    <select
                        wire:model.live="priorityFilter"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100 sm:text-sm"
                    >
                        <option value="all">همه اولویت‌ها</option>
                        <option value="{{ \App\Models\MandatoryAnnouncement::PRIORITY_NORMAL }}">عادی</option>
                        <option value="{{ \App\Models\MandatoryAnnouncement::PRIORITY_IMPORTANT }}">مهم</option>
                        <option value="{{ \App\Models\MandatoryAnnouncement::PRIORITY_URGENT }}">بسیار فوری / اضطراری</option>
                    </select>
                </div>
            </div>

            {{-- لیست کارت‌ها --}}
            @if ($announcements->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fa fa-bullhorn text-2xl"></i>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-700">هیچ بخشنامه‌ای یافت نشد</h3>
                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                        @if ($search !== '' || $priorityFilter !== 'all')
                            با توجه به فیلترهای جستجو موردی پیدا نشد. می‌توانید فیلترها را پاک کنید.
                        @else
                            تاکنون بخشنامه یا پیام الزامی ثبت نشده است. می‌توانید با کلیک روی «ثبت بخشنامه جدید» اقدام کنید.
                        @endif
                    </p>
                    @if ($search === '' && $priorityFilter === 'all')
                        <button
                            type="button"
                            wire:click="switchTab('create')"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white shadow transition hover:bg-cyan-700 sm:text-sm"
                        >
                            <i class="fa fa-plus-circle"></i>
                            ثبت اولین بخشنامه
                        </button>
                    @endif
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($announcements as $announcement)
                        @php
                            $totalRecipients = $announcement->recipients_count;
                            $readCount = $announcement->read_recipients_count;
                            $readPercent = $totalRecipients > 0 ? (int) round(($readCount / $totalRecipients) * 100) : 0;
                            $allRead = $totalRecipients > 0 && $readCount === $totalRecipients;
                        @endphp
                        <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-cyan-300 hover:shadow-md sm:p-5">
                            {{-- نوار رنگی کنار کارت بر اساس اولویت --}}
                            <span class="absolute inset-y-0 right-0 w-1.5 {{ match($announcement->priority) {
                                \App\Models\MandatoryAnnouncement::PRIORITY_NORMAL => 'bg-blue-500',
                                \App\Models\MandatoryAnnouncement::PRIORITY_IMPORTANT => 'bg-amber-500',
                                \App\Models\MandatoryAnnouncement::PRIORITY_URGENT => 'bg-rose-500',
                                default => 'bg-amber-500'
                            } }}"></span>

                            <div class="flex flex-col gap-4 pr-3 sm:pr-4">
                                {{-- هدر کارت: بج‌ها و برچسب‌ها --}}
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        {{-- بج اولویت --}}
                                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-extrabold {{ $announcement->priorityBadgeClasses() }}">
                                            <i class="fa {{ match($announcement->priority) {
                                                \App\Models\MandatoryAnnouncement::PRIORITY_NORMAL => 'fa-info-circle',
                                                \App\Models\MandatoryAnnouncement::PRIORITY_IMPORTANT => 'fa-exclamation-triangle',
                                                \App\Models\MandatoryAnnouncement::PRIORITY_URGENT => 'fa-bell fa-spin',
                                                default => 'fa-info-circle'
                                            } }}"></i>
                                            اولویت: {{ $announcement->priorityLabel() }}
                                        </span>

                                        {{-- بج مسدودسازی --}}
                                        @if ($announcement->is_blocking)
                                            <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">
                                                <i class="fa fa-lock"></i>
                                                مسدودکننده پنل
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-600">
                                                <i class="fa fa-unlock"></i>
                                                اطلاعیه عادی
                                            </span>
                                        @endif

                                        {{-- بج دامنه مخاطبان --}}
                                        @if ($announcement->target_type === \App\Models\MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS)
                                            <span class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                                <i class="fa fa-users"></i>
                                                همه مددکاران اجتماعی
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700">
                                                <i class="fa fa-user-check"></i>
                                                مددکاران منتخب ({{ $totalRecipients }} نفر)
                                            </span>
                                        @endif

                                        {{-- زمان مطالعه الزامی --}}
                                        @if ($announcement->min_reading_seconds > 0)
                                            <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                                <i class="fa fa-clock-o"></i>
                                                {{ $announcement->min_reading_seconds }} ثانیه مطالعه الزامی
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-xs text-slate-400">
                                        <i class="fa fa-calendar-alt ml-1"></i>
                                        ثبت شده در: {{ $this->formatJalaliDate($announcement->created_at) }}
                                    </div>
                                </div>

                                {{-- عنوان و متن بخشنامه --}}
                                <div>
                                    <h2 class="text-base font-extrabold text-slate-800 sm:text-lg">
                                        {{ $announcement->title }}
                                    </h2>
                                    <p class="mt-2 line-clamp-3 whitespace-pre-line text-xs leading-relaxed text-slate-600 sm:text-sm">
                                        {{ $announcement->body }}
                                    </p>
                                </div>

                                {{-- پیشرفت مشاهده و اکشن‌ها --}}
                                <div class="flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                                    {{-- وضعیت تایید و درصد پیشرفت --}}
                                    <div class="flex items-center gap-3 sm:max-w-md flex-1">
                                        <div class="w-full">
                                            <div class="mb-1 flex items-center justify-between text-xs font-bold">
                                                <span class="{{ $allRead ? 'text-emerald-700' : 'text-slate-600' }}">
                                                    وضعیت مشاهده: {{ $readCount }} از {{ $totalRecipients }} مددکار
                                                    @if($allRead)
                                                        <i class="fa fa-check-double text-emerald-600 mr-1"></i>
                                                    @endif
                                                </span>
                                                <span class="{{ $allRead ? 'text-emerald-700' : 'text-cyan-700' }}">
                                                    {{ $readPercent }}%
                                                </span>
                                            </div>
                                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                                <div
                                                    class="h-full rounded-full transition-all duration-500 {{ $allRead ? 'bg-emerald-500' : 'bg-cyan-500' }}"
                                                    style="width: {{ $readPercent }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- دکمه‌ها --}}
                                    <div class="flex items-center gap-2 self-end sm:self-center">
                                        <button
                                            type="button"
                                            wire:click="openAuditModal({{ $announcement->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-cyan-200 bg-cyan-50 px-3.5 py-2 text-xs font-bold text-cyan-800 transition hover:bg-cyan-100"
                                            title="مشاهده فهرست مددکاران، زمان دقیق تایید و ردپای IP"
                                        >
                                            <i class="fa fa-shield-alt text-cyan-600"></i>
                                            <span>رهگیری و لاگ مشاهده (Audit Log)</span>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="deleteAnnouncement({{ $announcement->id }})"
                                            wire:confirm="آیا از حذف این بخشنامه و تمامی تاییدیه‌های آن اطمینان دارید؟ این عمل غیرقابل برگشت است."
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
                                            title="حذف بخشنامه"
                                        >
                                            <i class="fa fa-trash"></i>
                                            <span>حذف</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- صفحه‌بندی --}}
                    <div class="mt-4">
                        {{ $announcements->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- تب دوم: فرم ایجاد بخشنامه جدید --}}
    @if ($activeTab === 'create')
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-800 sm:text-xl">ثبت و ابلاغ بخشنامه جدید به مددکاران</h2>
                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                        این بخشنامه در زمان ورود مددکار به پنل خود به صورت خودکار و مسدودکننده نمایش داده خواهد شد.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="switchTab('list')"
                    class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600"
                >
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>

            <form wire:submit.prevent="saveAnnouncement" class="space-y-6">
                {{-- عنوان بخشنامه --}}
                <div>
                    <label class="mb-2 block text-xs font-bold text-slate-700 sm:text-sm">
                        عنوان بخشنامه / پیام مدیریت <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="title"
                        placeholder="مثال: دستورالعمل اجرایی توزیع بسته‌های حمایتی زمستانه"
                        class="w-full rounded-xl border {{ $errors->has('title') ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-cyan-500 focus:ring-cyan-100' }} px-4 py-3 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:ring-4"
                    />
                    @error('title')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- سطح اولویت --}}
                <div>
                    <label class="mb-2 block text-xs font-bold text-slate-700 sm:text-sm">
                        سطح اولویت بخشنامه <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition {{ $priority === \App\Models\MandatoryAnnouncement::PRIORITY_NORMAL ? 'border-blue-400 bg-blue-50/50 ring-2 ring-blue-200' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input
                                type="radio"
                                wire:model.live="priority"
                                value="{{ \App\Models\MandatoryAnnouncement::PRIORITY_NORMAL }}"
                                class="text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span class="block text-xs font-extrabold text-blue-900 sm:text-sm">عادی</span>
                                <span class="text-[11px] text-blue-600">اطلاع‌رسانی عمومی و اخبار اداری</span>
                            </div>
                        </label>

                        <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition {{ $priority === \App\Models\MandatoryAnnouncement::PRIORITY_IMPORTANT ? 'border-amber-400 bg-amber-50/50 ring-2 ring-amber-200' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input
                                type="radio"
                                wire:model.live="priority"
                                value="{{ \App\Models\MandatoryAnnouncement::PRIORITY_IMPORTANT }}"
                                class="text-amber-600 focus:ring-amber-500"
                            />
                            <div>
                                <span class="block text-xs font-extrabold text-amber-900 sm:text-sm">مهم (پیش‌فرض)</span>
                                <span class="text-[11px] text-amber-700">دستورالعمل‌ها و تغییر روال‌های کاری</span>
                            </div>
                        </label>

                        <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition {{ $priority === \App\Models\MandatoryAnnouncement::PRIORITY_URGENT ? 'border-rose-400 bg-rose-50/50 ring-2 ring-rose-200' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input
                                type="radio"
                                wire:model.live="priority"
                                value="{{ \App\Models\MandatoryAnnouncement::PRIORITY_URGENT }}"
                                class="text-rose-600 focus:ring-rose-500"
                            />
                            <div>
                                <span class="block text-xs font-extrabold text-rose-900 sm:text-sm">بسیار فوری / اضطراری</span>
                                <span class="text-[11px] text-rose-600">هشدارهای امنیتی یا تغییرات سریع و حیاتی</span>
                            </div>
                        </label>
                    </div>
                    @error('priority')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- متن پیام --}}
                <div>
                    <label class="mb-2 block text-xs font-bold text-slate-700 sm:text-sm">
                        متن بخشنامه / توضیحات کامل <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        wire:model="body"
                        rows="6"
                        placeholder="متن کامل بخشنامه یا دستورالعمل مدیریتی را با دقت وارد فرمایید..."
                        class="w-full rounded-xl border {{ $errors->has('body') ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-cyan-500 focus:ring-cyan-100' }} p-4 text-sm leading-relaxed text-slate-800 placeholder-slate-400 outline-none transition focus:ring-4"
                    ></textarea>
                    @error('body')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- دامنه مخاطبان --}}
                <div>
                    <label class="mb-2 block text-xs font-bold text-slate-700 sm:text-sm">
                        دامنه ارسال مخاطبان <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition {{ $targetType === \App\Models\MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS ? 'border-cyan-400 bg-cyan-50/50 ring-2 ring-cyan-200' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input
                                type="radio"
                                wire:model.live="targetType"
                                value="{{ \App\Models\MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS }}"
                                class="text-cyan-600 focus:ring-cyan-500"
                            />
                            <div>
                                <span class="block text-xs font-extrabold text-slate-800 sm:text-sm">ارسال به تمامی مددکاران اجتماعی</span>
                                <span class="text-[11px] text-slate-500">تمامی مددکاران دارای دسترسی پنل این پیام را دریافت می‌کنند</span>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition {{ $targetType === \App\Models\MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS ? 'border-cyan-400 bg-cyan-50/50 ring-2 ring-cyan-200' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input
                                type="radio"
                                wire:model.live="targetType"
                                value="{{ \App\Models\MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS }}"
                                class="text-cyan-600 focus:ring-cyan-500"
                            />
                            <div>
                                <span class="block text-xs font-extrabold text-slate-800 sm:text-sm">انتخاب مددکاران خاص</span>
                                <span class="text-[11px] text-slate-500">فقط مددکاران انتخابی موظف به مشاهده و تایید خواهند بود</span>
                            </div>
                        </label>
                    </div>

                    {{-- پنل انتخاب مددکاران خاص --}}
                    @if ($targetType === \App\Models\MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS)
                        <div class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50/30 p-4">
                            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-700 sm:text-sm">مددکاران مدنظر را انتخاب کنید:</span>
                                    <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-extrabold text-cyan-800">
                                        {{ count($selectedSocialWorkerIds) }} نفر انتخاب شده
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="selectAllSocialWorkers"
                                        class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-cyan-700 shadow-sm border border-cyan-200 hover:bg-cyan-50"
                                    >
                                        انتخاب همه
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="deselectAllSocialWorkers"
                                        class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-slate-600 shadow-sm border border-slate-200 hover:bg-slate-50"
                                    >
                                        لغو انتخاب
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input
                                    type="text"
                                    wire:model.live.debounce.250ms="workerSearch"
                                    placeholder="جستجوی نام یا کد مددکار..."
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 placeholder-slate-400 outline-none transition focus:border-cyan-500 sm:text-sm"
                                />
                            </div>

                            <div class="max-h-60 overflow-y-auto space-y-1.5 rounded-xl border border-slate-200 bg-white p-3">
                                @forelse ($availableSocialWorkers as $worker)
                                    @php
                                        $isSelected = in_array($worker->id, $selectedSocialWorkerIds, true);
                                    @endphp
                                    <label class="flex cursor-pointer items-center justify-between rounded-lg p-2 transition {{ $isSelected ? 'bg-cyan-50 text-cyan-900 font-bold' : 'hover:bg-slate-50 text-slate-700' }}">
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                value="{{ $worker->id }}"
                                                wire:click="toggleSocialWorkerSelection({{ $worker->id }})"
                                                @checked($isSelected)
                                                class="rounded text-cyan-600 focus:ring-cyan-500"
                                            />
                                            <span class="text-xs sm:text-sm">{{ $worker->first_name }} {{ $worker->last_name }}</span>
                                            <span class="text-[11px] text-slate-400">کد: {{ $worker->worker_code ?? '—' }}</span>
                                        </div>
                                        <span class="text-[11px] text-slate-400">{{ $worker->mobile ?? '' }}</span>
                                    </label>
                                @empty
                                    <p class="py-4 text-center text-xs text-slate-400">مددکاری با این مشخصات یافت نشد.</p>
                                @endforelse
                            </div>
                            @error('selectedSocialWorkerIds')
                                <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                {{-- تنظیمات مسدودسازی و شمارشگر مطالعه --}}
                <div class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:grid-cols-2">
                    <div>
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model="isBlocking"
                                class="mt-1 rounded text-cyan-600 focus:ring-cyan-500"
                            />
                            <div>
                                <span class="block text-xs font-extrabold text-slate-800 sm:text-sm">مسدودسازی پنل مددکار تا تایید کامل</span>
                                <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                    در صورت فعال بودن، تا زمانی که مددکار دکمه «مشاهده و تایید» را نزند، قادر به انجام هیچ عملی در پنل نخواهد بود.
                                </span>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700 sm:text-sm">
                            حداقل زمان مطالعه الزامی (ثانیه)
                        </label>
                        <p class="mb-2 text-[11px] text-slate-500">
                            در طول این مدت دکمه تایید غیرفعال خواهد بود تا مطمئن شوید پیام خوانده شده است (0 یعنی بدون تاخیر).
                        </p>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                wire:model="minReadingSeconds"
                                min="0"
                                max="300"
                                class="w-32 rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-sm font-bold text-slate-800 outline-none transition focus:border-cyan-500"
                            />
                            <span class="text-xs text-slate-500">ثانیه</span>

                            <div class="flex items-center gap-1 mr-2">
                                <button type="button" wire:click="$set('minReadingSeconds', 0)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-50">0</button>
                                <button type="button" wire:click="$set('minReadingSeconds', 5)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-50">5 ثانیه</button>
                                <button type="button" wire:click="$set('minReadingSeconds', 10)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-50">10 ثانیه</button>
                                <button type="button" wire:click="$set('minReadingSeconds', 30)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-50">30 ثانیه</button>
                            </div>
                        </div>
                        @error('minReadingSeconds')
                            <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- دکمه‌های فرم --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        wire:click="switchTab('list')"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 sm:text-sm"
                    >
                        انصراف و بازگشت
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-cyan-200/50 transition hover:bg-cyan-700 disabled:opacity-50 sm:text-sm"
                    >
                        <span wire:loading.remove>
                            <i class="fa fa-paper-plane ml-1"></i>
                            ارسال و ابلاغ بخشنامه
                        </span>
                        <span wire:loading class="inline-flex items-center gap-1.5">
                            <i class="fa fa-spinner fa-spin"></i>
                            در حال ثبت و ارسال...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- مودال Audit Log (ردپای مشاهده و تاییدیه مخاطبان) --}}
    @if ($showAuditModal && $auditAnnouncement)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- بک‌دراپ تاریک --}}
            <div
                wire:click="closeAuditModal"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            ></div>

            {{-- کانتینر مودال --}}
            <div class="relative z-10 w-full max-w-4xl max-h-[90vh] flex flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
                {{-- هدر مودال --}}
                <div class="border-b border-slate-100 bg-slate-50/70 p-4 sm:p-5 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $auditAnnouncement->priorityBadgeClasses() }}">
                                {{ $auditAnnouncement->priorityLabel() }}
                            </span>
                            <h3 class="text-base font-extrabold text-slate-800 sm:text-lg">
                                گزارش و ردپای مشاهده (Audit Log)
                            </h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 font-bold">
                            بخشنامه: <span class="text-slate-700">{{ $auditAnnouncement->title }}</span>
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeAuditModal"
                        class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:bg-white hover:text-slate-600 transition"
                    >
                        <i class="fa fa-times text-lg"></i>
                    </button>
                </div>

                {{-- خلاصه وضعیت آماری --}}
                <div class="grid grid-cols-3 border-b border-slate-100 bg-white text-center divide-x divide-x-reverse divide-slate-100 p-3">
                    <div>
                        <span class="block text-xs text-slate-500 font-bold">کل مخاطبان</span>
                        <span class="text-base font-extrabold text-slate-800">{{ $auditAnnouncement->recipients_count }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-emerald-600 font-bold">تایید کرده‌اند</span>
                        <span class="text-base font-extrabold text-emerald-700">{{ $auditAnnouncement->read_recipients_count }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-amber-600 font-bold">در انتظار مشاهده</span>
                        <span class="text-base font-extrabold text-amber-700">{{ $auditAnnouncement->recipients_count - $auditAnnouncement->read_recipients_count }}</span>
                    </div>
                </div>

                {{-- فیلترهای درون مودال --}}
                <div class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/50 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-xs">
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="auditSearch"
                            placeholder="جستجوی مددکار..."
                            class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-3 pr-8 text-xs text-slate-800 placeholder-slate-400 outline-none transition focus:border-cyan-500 sm:text-sm"
                        />
                        <i class="fa fa-search absolute right-3 top-3 text-xs text-slate-400"></i>
                    </div>

                    <div class="flex items-center gap-1.5 self-end sm:self-auto">
                        <button
                            type="button"
                            wire:click="$set('auditStatusFilter', 'all')"
                            class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $auditStatusFilter === 'all' ? 'bg-cyan-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}"
                        >
                            همه
                        </button>
                        <button
                            type="button"
                            wire:click="$set('auditStatusFilter', 'read')"
                            class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $auditStatusFilter === 'read' ? 'bg-emerald-600 text-white' : 'bg-white text-emerald-700 border border-emerald-200' }}"
                        >
                            تایید شده
                        </button>
                        <button
                            type="button"
                            wire:click="$set('auditStatusFilter', 'unread')"
                            class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $auditStatusFilter === 'unread' ? 'bg-amber-600 text-white' : 'bg-white text-amber-700 border border-amber-200' }}"
                        >
                            در انتظار
                        </button>
                    </div>
                </div>

                {{-- لیست ردپای گیرندگان --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    @forelse ($auditRecipients as $recipient)
                        @php
                            $worker = $recipient->socialWorker;
                            $user = $recipient->user;
                            $hasRead = !is_null($recipient->read_at);
                        @endphp
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border {{ $hasRead ? 'border-emerald-100 bg-emerald-50/20' : 'border-amber-100 bg-amber-50/20' }} p-3 transition">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $hasRead ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    <i class="fa {{ $hasRead ? 'fa-check' : 'fa-clock-o' }}"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-extrabold text-slate-800 sm:text-sm">
                                            @if($worker)
                                                {{ $worker->first_name }} {{ $worker->last_name }}
                                            @else
                                                {{ $user?->name ?? 'کاربر نامشخص' }}
                                            @endif
                                        </span>
                                        @if($worker?->worker_code)
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">
                                                کد: {{ $worker->worker_code }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        ایمیل: {{ $user?->email ?? '—' }} | تلفن: {{ $worker?->mobile ?? '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-right sm:text-left self-end sm:self-center">
                                @if ($hasRead)
                                    <span class="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-700">
                                        <i class="fa fa-calendar-check text-emerald-600"></i>
                                        تایید در: {{ $this->formatJalaliDate($recipient->read_at, 'Y/m/d - H:i:s') }}
                                    </span>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5" dir="ltr">
                                        IP: {{ $recipient->ip_address ?? '127.0.0.1' }}
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">
                                        <i class="fa fa-hourglass-half"></i>
                                        در انتظار مشاهده و تایید
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-slate-400">
                            موردی در لیست گیرندگان این بخشنامه یافت نشد.
                        </div>
                    @endforelse
                </div>

                {{-- فوتر مودال --}}
                <div class="border-t border-slate-100 bg-slate-50 p-3 sm:px-5 flex items-center justify-end">
                    <button
                        type="button"
                        wire:click="closeAuditModal"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition sm:text-sm"
                    >
                        بستن
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
