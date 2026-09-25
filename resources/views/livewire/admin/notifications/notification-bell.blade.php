<div class="relative" x-data="{ bellOpen: false }" @keydown.escape.window="bellOpen = false" wire:poll.60s>
    {{-- Trigger Button --}}
    <button
        type="button"
        @click="bellOpen = !bellOpen"
        :aria-expanded="bellOpen.toString()"
        aria-haspopup="true"
        aria-label="اعلان‌ها{{ $unreadCount > 0 ? ' - '.$unreadCount.' اعلان خوانده‌نشده' : '' }}"
        class="group relative inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200/80 bg-white/90 text-slate-600 shadow-xs backdrop-blur-md transition-all duration-200 hover:border-indigo-300 hover:bg-gradient-to-b hover:from-indigo-50/50 hover:to-white hover:text-indigo-600 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-500/15 active:scale-95"
        :class="{ 'border-indigo-300 bg-indigo-50/60 text-indigo-600 ring-4 ring-indigo-500/15 shadow-md': bellOpen }"
    >
        <span class="relative flex items-center justify-center transition-transform duration-300 group-hover:scale-110">
            <svg class="h-5 w-5 transition-transform duration-300 group-hover:rotate-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </span>

        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-60"></span>
                <span class="relative inline-flex h-4 min-w-[1.15rem] items-center justify-center rounded-full bg-gradient-to-tr from-rose-600 to-rose-500 px-1 text-[10px] font-extrabold text-white shadow-md shadow-rose-500/30 ring-2 ring-white">
                    {{ $unreadCount > 99 ? '۹۹+' : \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }}
                </span>
            </span>
        @endif
    </button>

    <div
        x-show="bellOpen"
        x-cloak
        @click.outside="bellOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        class="absolute left-0 z-50 mt-3 w-80 origin-top-left overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-2xl shadow-indigo-950/10 ring-1 ring-black/5 sm:w-96"
        role="menu"
        aria-label="فهرست اعلان‌های اخیر"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100/80 bg-gradient-to-r from-slate-50 via-indigo-50/20 to-slate-50 px-4 py-3.5">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-xs shadow-indigo-500/20">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </span>
                <span class="text-xs font-bold text-slate-800">اعلان‌ها</span>
                @if($unreadCount > 0)
                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-600">
                        {{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }} جدید
                    </span>
                @endif
            </div>

            @if($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="group inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                >
                    <svg class="h-3.5 w-3.5 transition group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    <span>خواندن همه</span>
                </button>
            @endif
        </div>

        {{-- Notifications List --}}
        <div class="max-h-84 divide-y divide-slate-100 overflow-y-auto overscroll-contain">
            @forelse($recentNotifications as $notification)
                <div class="group relative flex items-start gap-3 p-3.5 transition-colors duration-200 {{ $notification->isRead() ? 'hover:bg-slate-50/80' : 'bg-indigo-50/40 hover:bg-indigo-50/70' }}">
                    <div class="mt-1 flex h-2 w-2 shrink-0 items-center justify-center">
                        @if(! $notification->isRead())
                            <span class="h-2 w-2 rounded-full bg-indigo-600 ring-4 ring-indigo-100"></span>
                        @else
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold {{ $notification->isRead() ? 'text-slate-700' : 'text-slate-900' }}">
                            {{ $notification->title }}
                        </p>
                        <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-600">
                            {{ $notification->message }}
                        </p>
                        <div class="mt-2 flex items-center gap-2 text-[10px] text-slate-400">
                            <span class="inline-flex items-center gap-1 font-mono">
                                <svg class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                {{ \App\Helpers\Morilog\Jalalian::fromDateTime($notification->created_at)->format('Y/m/d H:i') }}
                            </span>
                            <span class="rounded-md border border-slate-200/60 bg-white px-1.5 py-0.5 font-medium text-slate-600 shadow-2xs">
                                {{ $notification->event_label }}
                            </span>
                        </div>
                    </div>

                    @unless($notification->isRead())
                        <button
                            type="button"
                            wire:click="markAsRead({{ $notification->id }})"
                            class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-indigo-100 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                            title="علامت‌گذاری به‌عنوان خوانده‌شده"
                            aria-label="علامت‌گذاری به‌عنوان خوانده‌شده"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </button>
                    @endunless
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-600">اعلانی در دسترس نیست</p>
                    <p class="mt-0.5 text-[11px] text-slate-400">تمام پیام‌ها و رویدادهای مهم اینجا نمایش داده می‌شوند.</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-2.5 text-center">
            <button
                type="button"
                @click="bellOpen = false"
                wire:click="openNotificationCenter"
                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl py-1.5 text-xs font-bold text-indigo-600 transition hover:bg-indigo-50/80 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-300"
            >
                <span>مشاهده همه در مرکز اعلان‌ها</span>
                <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>
