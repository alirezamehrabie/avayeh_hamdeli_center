@php
    use App\Helpers\Morilog\Jalalian;
    use App\Models\MandatoryAnnouncement;
@endphp

<div class="space-y-3.5 sm:space-y-6 px-1 sm:px-4 pb-16" dir="rtl">
    <!-- Header: Compact on Mobile, Full on Desktop -->
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
            <span class="flex h-8 w-8 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/30">
                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </span>
            <div class="min-w-0">
                <h1 class="text-lg font-black tracking-tight text-slate-900 sm:text-2xl truncate">صندوق پیام‌ها</h1>
                <p class="hidden sm:block text-xs font-semibold text-slate-500 truncate">
                    آرشیو رسمی ابلاغیه‌ها و پیام‌های ارسالی مدیریت با ثبت دقیق تاریخ و سوابق تأیید شما
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button
                type="button"
                wire:click="$refresh"
                wire:loading.attr="disabled"
                class="inline-flex h-8 sm:min-h-[42px] items-center justify-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-2.5 sm:px-3.5 py-1 text-xs font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 disabled:opacity-60"
                title="به‌روزرسانی پیام‌ها"
            >
                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-indigo-600 transition-transform" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span class="hidden sm:inline">به‌روزرسانی</span>
            </button>
            <span class="hidden sm:inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm">
                <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-slate-500">امروز:</span>
                <span class="tabular-nums font-black text-slate-900">{{ Jalalian::now()->format('l، d F Y') }}</span>
            </span>
        </div>
    </div>

    <!-- KPI Statistics Grid (Shown on Desktop, integrated into filter pills on Mobile) -->
    <div class="hidden sm:grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Received -->
        <div class="group flex items-center justify-between rounded-2xl border border-indigo-100 bg-gradient-to-b from-indigo-50/30 via-white to-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 truncate">کل ابلاغیه‌ها</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-indigo-700" dir="ltr">
                    {{ number_format($totalCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <!-- Card 2: Unread / Pending -->
        <div class="group flex items-center justify-between rounded-2xl border border-amber-100 bg-gradient-to-b from-amber-50/30 via-white to-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 truncate">خوانده‌نشده / نیازمند اقدام</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-amber-600" dir="ltr">
                    {{ number_format($unreadCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/25">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Card 3: Read & Acknowledged -->
        <div class="group flex items-center justify-between rounded-2xl border border-emerald-100 bg-gradient-to-b from-emerald-50/30 via-white to-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 truncate">مطالعه و تأیید شده</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-emerald-600" dir="ltr">
                    {{ number_format($readCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/25">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Card 4: Urgent Announcements -->
        <div class="group flex items-center justify-between rounded-2xl border border-rose-100 bg-gradient-to-b from-rose-50/30 via-white to-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-rose-200 hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 truncate">اولویت فوری</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-rose-600" dir="ltr">
                    {{ number_format($urgentCount) }}
                </p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-md shadow-rose-600/25">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filters and Search: Ultra-compact on Mobile, Rich on Desktop -->
    <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-2.5 sm:p-4 shadow-xs sm:shadow-sm">
        <div class="flex flex-col gap-2 sm:gap-3 lg:flex-row lg:items-center lg:justify-between">
            <!-- Row 1: Search Input & Compact Priority Select -->
            <div class="flex items-center gap-2 flex-1">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 sm:pr-3 text-slate-400">
                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="جستجو در پیام‌ها..."
                        class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 py-1.5 sm:py-2.5 pr-8 sm:pr-9 pl-7 sm:pl-8 text-xs font-semibold text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 sm:focus:ring-4 focus:ring-indigo-100"
                    >
                    @if($search !== '')
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 left-0 flex items-center pl-2.5 sm:pl-3 text-slate-400 hover:text-slate-600"
                            title="پاک کردن جستجو"
                        >
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                <!-- Priority Select -->
                <select
                    wire:model.live="priorityFilter"
                    class="shrink-0 w-28 sm:w-auto rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-2 sm:px-3 py-1.5 sm:py-2 text-[11px] sm:text-xs font-bold text-slate-700 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 sm:focus:ring-4 focus:ring-indigo-100"
                >
                    <option value="all">همه اولویت‌ها</option>
                    <option value="{{ MandatoryAnnouncement::PRIORITY_URGENT }}">فوری</option>
                    <option value="{{ MandatoryAnnouncement::PRIORITY_IMPORTANT }}">مهم</option>
                    <option value="{{ MandatoryAnnouncement::PRIORITY_NORMAL }}">عادی</option>
                </select>
            </div>

            <!-- Row 2: Status Tabs (Full-width Segmented on Mobile, Inline on Desktop) -->
            <div class="inline-flex w-full lg:w-auto items-center justify-between sm:justify-start rounded-lg sm:rounded-xl bg-slate-100 p-0.5 sm:p-1 text-[11px] sm:text-xs">
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'all')"
                    class="flex-1 lg:flex-initial text-center rounded-md sm:rounded-lg px-2 sm:px-3 py-1 sm:py-1.5 font-bold transition-all {{ $statusFilter === 'all' ? 'bg-white text-indigo-700 shadow-xs sm:shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    <span>همه</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] tabular-nums {{ $statusFilter === 'all' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-200 text-slate-600' }}">{{ $totalCount }}</span>
                </button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'unread')"
                    class="flex-1 lg:flex-initial text-center rounded-md sm:rounded-lg px-2 sm:px-3 py-1 sm:py-1.5 font-bold transition-all {{ $statusFilter === 'unread' ? 'bg-white text-amber-700 shadow-xs sm:shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    <span>خوانده‌نشده</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] tabular-nums {{ $statusFilter === 'unread' ? 'bg-amber-100 text-amber-800' : ($unreadCount > 0 ? 'bg-rose-500 text-white font-black' : 'bg-slate-200 text-slate-600') }}">{{ $unreadCount }}</span>
                </button>
                <button
                    type="button"
                    wire:click="$set('statusFilter', 'read')"
                    class="flex-1 lg:flex-initial text-center rounded-md sm:rounded-lg px-2 sm:px-3 py-1 sm:py-1.5 font-bold transition-all {{ $statusFilter === 'read' ? 'bg-white text-emerald-700 shadow-xs sm:shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    <span>مطالعه‌شده</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] tabular-nums {{ $statusFilter === 'read' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $readCount }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Announcements List -->
    <div class="space-y-3">
        @forelse($recipients as $recipient)
            @php
                $announcement = $recipient->announcement;
                $isRead = $recipient->isRead();
                $priority = $announcement->priority ?? MandatoryAnnouncement::PRIORITY_NORMAL;

                $priorityBadge = match($priority) {
                    MandatoryAnnouncement::PRIORITY_URGENT => [
                        'class' => 'bg-rose-50 text-rose-700 ring-rose-200',
                        'dot' => 'bg-rose-500 animate-pulse',
                        'label' => 'فوری و اضطراری',
                    ],
                    MandatoryAnnouncement::PRIORITY_IMPORTANT => [
                        'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'dot' => 'bg-amber-500',
                        'label' => 'مهم',
                    ],
                    default => [
                        'class' => 'bg-blue-50 text-blue-700 ring-blue-200',
                        'dot' => 'bg-blue-500',
                        'label' => 'عادی',
                    ],
                };

                $jalaliCreatedAt = $announcement->created_at ? Jalalian::fromDateTime($announcement->created_at)->format('l، d F Y - H:i') : 'نامشخص';
                $jalaliReadAt = $recipient->read_at ? Jalalian::fromDateTime($recipient->read_at)->format('l، d F Y - H:i') : null;
                $creatorDisplayName = $announcement->creator?->first_name ? ($announcement->creator->first_name . ' ' . $announcement->creator->last_name) : ($announcement->creator?->name ?: 'مدیریت مرکز');
            @endphp

            <div class="group relative overflow-hidden rounded-2xl border transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $isRead ? 'border-slate-200 bg-white' : 'border-indigo-200 bg-gradient-to-r from-indigo-50/40 via-white to-white shadow-sm' }}">
                <div class="p-3.5 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <!-- Left/Main Info -->
                        <div class="min-w-0 flex-1 space-y-2">
                            <!-- Badges Row -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Priority Badge -->
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 {{ $priorityBadge['class'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $priorityBadge['dot'] }}"></span>
                                    <span>{{ $priorityBadge['label'] }}</span>
                                </span>

                                <!-- Read Status Badge -->
                                @if($isRead)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                        <svg class="h-3 w-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>مطالعه و تأیید شده</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-black text-amber-800 ring-1 ring-amber-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        <span>خوانده‌نشده / نیازمند تأیید</span>
                                    </span>
                                @endif

                                @if($announcement->is_blocking)
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                        ابلاغیه رسمی الزامی
                                    </span>
                                @endif
                            </div>

                            <!-- Title -->
                            <h2 class="text-base font-black text-slate-900 sm:text-lg">
                                {{ $announcement->title }}
                            </h2>

                            <!-- Excerpt -->
                            <p class="text-xs font-normal text-slate-600 line-clamp-2 leading-relaxed sm:text-sm">
                                {{ Str::limit(strip_tags($announcement->body), 180) }}
                            </p>

                            <!-- Metadata Row (Dates & Timestamps) -->
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3 pt-1 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>فرستنده:</span>
                                    <strong class="font-bold text-slate-800">{{ $creatorDisplayName }}</strong>
                                </span>

                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>زمان ارسال:</span>
                                    <strong class="font-bold tabular-nums text-slate-800">{{ $jalaliCreatedAt }}</strong>
                                </span>

                                @if($isRead && $jalaliReadAt)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50/70 px-2 py-0.5 text-emerald-800 ring-1 ring-emerald-200/60">
                                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>زمان تأیید:</span>
                                        <strong class="font-bold tabular-nums text-emerald-900">{{ $jalaliReadAt }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Right/Action Button -->
                        <div class="flex flex-col sm:flex-row shrink-0 items-stretch sm:items-center gap-2 pt-2 sm:pt-0 sm:self-center">
                            @if(! $isRead)
                                <button
                                    type="button"
                                    wire:click="acknowledgeAnnouncement({{ $recipient->id }})"
                                    class="inline-flex w-full sm:w-auto min-h-[40px] items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-emerald-600/20 transition hover:bg-emerald-700 active:scale-95"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>تأیید مطالعه</span>
                                </button>
                            @endif

                            <button
                                type="button"
                                wire:click="viewDetails({{ $recipient->id }})"
                                class="inline-flex w-full sm:w-auto min-h-[40px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50/40 hover:text-indigo-700 active:scale-95"
                            >
                                <span>مشاهده متن کامل</span>
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-4 shadow-inner">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </span>
                <h3 class="text-base font-black text-slate-900">هیچ پیامی یافت نشد</h3>
                <p class="mt-1 text-xs text-slate-500">
                    @if($search !== '' || $statusFilter !== 'all' || $priorityFilter !== 'all')
                        با فیلترها و عبارت جستجوی انتخاب‌شده ابلاغیه‌ای یافت نشد. می‌توانید فیلترها را ریست کنید.
                    @else
                        در حال حاضر هیچ پیام یا ابلاغیه‌ای برای شما ثبت نشده است. پیام‌های جدید بلافاصله پس از ارسال در این قسمت قرار می‌گیرند.
                    @endif
                </p>
                @if($search !== '' || $statusFilter !== 'all' || $priorityFilter !== 'all')
                    <div class="mt-4">
                        <button
                            type="button"
                            wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('priorityFilter', 'all')"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100"
                        >
                            <span>پاک کردن فیلترها و نمایش همه</span>
                        </button>
                    </div>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($recipients->hasPages())
        <div class="mt-6">
            {{ $recipients->links() }}
        </div>
    @endif

    <!-- Announcement Detail Reading Modal (Bottom Sheet on Mobile, Centered Modal on Desktop) -->
    @if($showDetailModal && $selectedRecipient)
        @php
            $modalAnnouncement = $selectedRecipient->announcement;
            $modalIsRead = $selectedRecipient->isRead();
            $modalPriority = $modalAnnouncement->priority ?? MandatoryAnnouncement::PRIORITY_NORMAL;
            $modalPriorityLabel = match($modalPriority) {
                MandatoryAnnouncement::PRIORITY_URGENT => 'فوری و اضطراری',
                MandatoryAnnouncement::PRIORITY_IMPORTANT => 'مهم',
                default => 'عادی',
            };
            $modalPriorityColor = match($modalPriority) {
                MandatoryAnnouncement::PRIORITY_URGENT => 'bg-rose-50 text-rose-700 ring-rose-200/80',
                MandatoryAnnouncement::PRIORITY_IMPORTANT => 'bg-amber-50 text-amber-700 ring-amber-200/80',
                default => 'bg-blue-50 text-blue-700 ring-blue-200/80',
            };
            $modalPriorityDot = match($modalPriority) {
                MandatoryAnnouncement::PRIORITY_URGENT => 'bg-rose-500 animate-pulse',
                MandatoryAnnouncement::PRIORITY_IMPORTANT => 'bg-amber-500',
                default => 'bg-blue-500',
            };
            $modalCreatedAt = $modalAnnouncement->created_at ? Jalalian::fromDateTime($modalAnnouncement->created_at)->format('l، d F Y - H:i') : 'نامشخص';
            $modalReadAt = $selectedRecipient->read_at ? Jalalian::fromDateTime($selectedRecipient->read_at)->format('l، d F Y - H:i') : null;
            $modalCreator = $modalAnnouncement->creator?->first_name ? ($modalAnnouncement->creator->first_name . ' ' . $modalAnnouncement->creator->last_name) : ($modalAnnouncement->creator?->name ?: 'مدیریت مرکز');
        @endphp

        <div
            x-data="{ show: true }"
            @keydown.escape.window="$wire.closeDetailModal()"
            class="fixed inset-0 z-50 flex flex-col justify-end sm:justify-center sm:items-center sm:p-4 lg:p-6"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div
                wire:click="closeDetailModal"
                x-show="show"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            ></div>

            <!-- Modal / Bottom Sheet Container -->
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95 sm:opacity-0"
                x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
                x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95 sm:opacity-0"
                class="relative flex flex-col w-full max-h-[88vh] sm:max-h-[85vh] sm:max-w-2xl lg:max-w-3xl overflow-hidden rounded-t-[28px] sm:rounded-3xl border border-slate-200/90 bg-white shadow-2xl transition-all"
            >
                <!-- Mobile Drag/Grab Handle Pill -->
                <div class="flex justify-center pt-2.5 pb-1 sm:hidden shrink-0">
                    <div class="h-1.5 w-12 rounded-full bg-slate-300"></div>
                </div>

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-6 sm:py-4 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 sm:py-1 text-[11px] sm:text-xs font-bold ring-1 {{ $modalPriorityColor }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $modalPriorityDot }}"></span>
                            <span>{{ $modalPriorityLabel }}</span>
                        </span>

                        @if($modalIsRead)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 sm:py-1 text-[11px] sm:text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                <svg class="h-3 w-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>تأیید شده</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 sm:py-1 text-[11px] sm:text-xs font-bold text-amber-800 ring-1 ring-amber-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                <span>نیازمند تأیید</span>
                            </span>
                        @endif

                        @if($modalAnnouncement->is_blocking)
                            <span class="hidden xs:inline-flex rounded-md bg-slate-200/70 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                                الزامی
                            </span>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="closeDetailModal"
                        class="inline-flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-700 active:scale-95"
                        title="بستن"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Scrollable Body Content -->
                <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5 space-y-4 sm:space-y-5">
                    <!-- Title -->
                    <div>
                        <h2 class="text-base font-black text-slate-900 sm:text-xl leading-snug">
                            {{ $modalAnnouncement->title }}
                        </h2>
                    </div>

                    <!-- Metadata Grid: 2 Columns on Mobile & Desktop -->
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 sm:p-4 text-xs">
                        <div class="space-y-1">
                            <span class="text-slate-500 font-semibold block text-[11px]">فرستنده:</span>
                            <p class="font-bold text-slate-900 truncate">{{ $modalCreator }}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-slate-500 font-semibold block text-[11px]">تاریخ و ساعت ارسال:</span>
                            <p class="font-bold text-slate-900 tabular-nums truncate">{{ $modalCreatedAt }}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-slate-500 font-semibold block text-[11px]">وضعیت مطالعه:</span>
                            @if($modalIsRead)
                                <p class="font-bold text-emerald-700 flex items-center gap-1 truncate">
                                    <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>تأیید شده</span>
                                </p>
                            @else
                                <p class="font-bold text-amber-700 truncate">در انتظار تأیید</p>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <span class="text-slate-500 font-semibold block text-[11px]">زمان ثبت تأیید شما:</span>
                            <p class="font-bold text-slate-900 tabular-nums truncate">
                                {{ $modalReadAt ?: 'هنوز ثبت نشده است' }}
                            </p>
                        </div>
                    </div>

                    <!-- Message Body Container -->
                    <div class="rounded-2xl border border-indigo-100/80 bg-gradient-to-br from-indigo-50/20 via-white to-white p-4 sm:p-5 shadow-xs">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-700 mb-2.5">
                            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>متن کامل ابلاغیه:</span>
                        </div>
                        <div class="border-r-3 border-indigo-500 pr-3 sm:pr-4 text-xs sm:text-sm text-slate-800 leading-relaxed font-normal whitespace-pre-wrap text-justify">
                            {{ $modalAnnouncement->body }}
                        </div>
                    </div>
                </div>

                <!-- Sticky Action Footer (Thumb-friendly on Mobile) -->
                <div class="border-t border-slate-100 bg-white/95 backdrop-blur-sm px-4 py-3 sm:px-6 sm:py-4 shrink-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        @if(! $modalIsRead)
                            <button
                                type="button"
                                wire:click="acknowledgeAnnouncement({{ $selectedRecipient->id }})"
                                class="inline-flex w-full sm:w-auto min-h-[44px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-600/25 transition hover:bg-emerald-700 active:scale-95"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>متوجه شدم، مطالعه کردم و تأیید می‌کنم</span>
                            </button>
                        @else
                            <div class="flex items-center gap-1.5 text-xs text-emerald-800 font-bold bg-emerald-50 rounded-xl px-3 py-2 border border-emerald-200/80">
                                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>این پیام در سوابق کاربری شما به صورت رسمی تأیید گردیده است.</span>
                            </div>
                        @endif

                        <button
                            type="button"
                            wire:click="closeDetailModal"
                            class="inline-flex w-full sm:w-auto min-h-[40px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 active:scale-95"
                        >
                            بستن
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
