<div class="space-y-6">
    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">مرکز اعلان‌ها</h1>
                <p class="mt-1 text-sm text-gray-500">
                    اعلان‌های سیستمی بر اساس تنظیمات شما نمایش داده می‌شوند.
                    @if($unreadCount > 0)
                        <span class="mr-1 inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600">
                            {{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }} خوانده‌نشده
                        </span>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'notifications-settings' })"
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.22.61.77 1.02 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                    تنظیمات اعلان‌ها
                </button>

                @if($unreadCount > 0)
                    <button type="button" wire:click="markAllAsRead"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                        خواندن همه
                    </button>
                @endif

                <button type="button"
                        @click="if (confirm('اعلان‌های خوانده‌شده حذف شوند؟')) $wire.deleteReadNotifications()"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-100 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 shadow-sm transition hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-100">
                    حذف خوانده‌شده‌ها
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="notif-status-filter" class="mb-1 block text-xs font-semibold text-gray-600">وضعیت</label>
                <select id="notif-status-filter" wire:model.live="statusFilter" class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-300 focus:ring-indigo-200">
                    <option value="all">همه</option>
                    <option value="unread">خوانده‌نشده</option>
                    <option value="read">خوانده‌شده</option>
                </select>
            </div>

            <div>
                <label for="notif-event-filter" class="mb-1 block text-xs font-semibold text-gray-600">نوع رویداد</label>
                <select id="notif-event-filter" wire:model.live="eventFilter" class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-300 focus:ring-indigo-200">
                    <option value="">همه رویدادها</option>
                    @foreach($eventOptions as $eventKey => $eventLabel)
                        <option value="{{ $eventKey }}">{{ $eventLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="notif-date-from" class="mb-1 block text-xs font-semibold text-gray-600">از تاریخ</label>
                <input id="notif-date-from" type="text" dir="ltr" placeholder="1405/01/01" wire:model.live.debounce.500ms="dateFrom"
                       class="w-full rounded-xl border-gray-200 text-center text-sm focus:border-indigo-300 focus:ring-indigo-200">
            </div>

            <div>
                <label for="notif-date-to" class="mb-1 block text-xs font-semibold text-gray-600">تا تاریخ</label>
                <input id="notif-date-to" type="text" dir="ltr" placeholder="1405/12/29" wire:model.live.debounce.500ms="dateTo"
                       class="w-full rounded-xl border-gray-200 text-center text-sm focus:border-indigo-300 focus:ring-indigo-200">
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100">
                    حذف فیلترها
                </button>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="rounded-xl border border-gray-100 bg-white shadow-sm" wire:loading.class="opacity-60" wire:target="statusFilter,eventFilter,dateFrom,dateTo,clearFilters">
        <div class="divide-y divide-gray-50">
            @forelse($notifications as $notification)
                <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-indigo-50/30 sm:flex-row sm:items-start {{ $notification->isRead() ? '' : 'bg-indigo-50/50' }}">
                    <span class="mt-2 hidden h-2.5 w-2.5 shrink-0 rounded-full sm:block {{ $notification->isRead() ? 'bg-gray-200' : 'bg-indigo-500' }}" aria-hidden="true"></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-800">{{ $notification->title }}</h2>
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-600">{{ $notification->event_label }}</span>
                            @unless($notification->isRead())
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">جدید</span>
                            @endunless
                        </div>

                        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $notification->message }}</p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                {{ \App\Helpers\Morilog\Jalalian::fromDateTime($notification->created_at)->format('l Y/m/d ساعت H:i') }}
                            </span>

                            @if($notification->actor_name)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    {{ $notification->actor_name }}
                                    @if($notification->actor_role_label)
                                        <span class="text-gray-400">({{ $notification->actor_role_label }})</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 self-end sm:self-start">
                        @if($notification->subject_type === 'service' && $notification->subject_id)
                            <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'advanced-service-report', id: {{ $notification->subject_id }} })"
                                    class="rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                مشاهده خدمت
                            </button>
                        @endif

                        @unless($notification->isRead())
                            <button type="button" wire:click="markAsRead({{ $notification->id }})"
                                    class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-600 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                خواندم
                            </button>
                        @endunless

                        <button type="button"
                                @click="if (confirm('این اعلان حذف شود؟')) $wire.deleteNotification({{ $notification->id }})"
                                class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-500 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                aria-label="حذف اعلان">
                            حذف
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-14 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <p class="mt-3 text-sm font-semibold text-gray-500">اعلانی یافت نشد.</p>
                    <p class="mt-1 text-xs text-gray-400">
                        برای دریافت اعلان، ابتدا رویدادهای موردنظر را در
                        <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'notifications-settings' })" class="font-bold text-indigo-500 hover:text-indigo-700 focus:outline-none focus:underline">تنظیمات اعلان‌ها</button>
                        فعال کنید.
                    </p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
