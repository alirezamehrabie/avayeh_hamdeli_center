<div class="relative" x-data="{ bellOpen: false }" @keydown.escape.window="bellOpen = false" wire:poll.60s>
    <button
        type="button"
        @click="bellOpen = !bellOpen"
        :aria-expanded="bellOpen.toString()"
        aria-haspopup="true"
        aria-label="اعلان‌ها{{ $unreadCount > 0 ? ' - '.$unreadCount.' اعلان خوانده‌نشده' : '' }}"
        class="relative inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-100"
    >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>

        @if($unreadCount > 0)
            <span class="absolute -top-1.5 -left-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow ring-2 ring-white">
                {{ $unreadCount > 99 ? '۹۹+' : \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }}
            </span>
        @endif
    </button>

    <div
        x-show="bellOpen"
        x-cloak
        @click.outside="bellOpen = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute left-0 z-50 mt-2 w-80 origin-top-left overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl sm:w-96"
        role="menu"
        aria-label="فهرست اعلان‌های اخیر"
    >
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/70 px-4 py-3">
            <span class="text-sm font-bold text-gray-800">اعلان‌ها</span>
            @if($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs font-semibold text-indigo-600 transition hover:text-indigo-800 focus:outline-none focus:underline">
                    خواندن همه
                </button>
            @endif
        </div>

        <div class="max-h-80 divide-y divide-gray-50 overflow-y-auto">
            @forelse($recentNotifications as $notification)
                <div class="flex items-start gap-3 px-4 py-3 transition hover:bg-indigo-50/40 {{ $notification->isRead() ? '' : 'bg-indigo-50/60' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->isRead() ? 'bg-gray-200' : 'bg-indigo-500' }}"></span>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-gray-800">{{ $notification->title }}</p>
                        <p class="mt-0.5 line-clamp-2 text-[11px] leading-5 text-gray-600">{{ $notification->message }}</p>
                        <div class="mt-1 flex items-center gap-2 text-[10px] text-gray-400">
                            <span>{{ \App\Helpers\Morilog\Jalalian::fromDateTime($notification->created_at)->format('Y/m/d H:i') }}</span>
                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 font-semibold text-gray-500">{{ $notification->event_label }}</span>
                        </div>
                    </div>

                    @unless($notification->isRead())
                        <button type="button" wire:click="markAsRead({{ $notification->id }})" class="shrink-0 text-[10px] font-semibold text-indigo-500 transition hover:text-indigo-700 focus:outline-none focus:underline" aria-label="علامت‌گذاری به‌عنوان خوانده‌شده">
                            خواندم
                        </button>
                    @endunless
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <p class="mt-2 text-xs text-gray-500">اعلان جدیدی وجود ندارد.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-gray-100 bg-gray-50/70 px-4 py-2.5 text-center">
            <button type="button" @click="bellOpen = false" wire:click="openNotificationCenter" class="text-xs font-bold text-indigo-600 transition hover:text-indigo-800 focus:outline-none focus:underline">
                مشاهده همه اعلان‌ها
            </button>
        </div>
    </div>
</div>
