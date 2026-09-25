{{-- بج شمارش پیام‌های خوانده‌نشده در سایدبار؛ به‌صورت مستقل هر ۶۰ ثانیه بازخوانی می‌شود. --}}
<span wire:poll.60s class="contents">
    @if($unreadCount > 0)
        <span class="mr-2 inline-flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-rose-500/90 px-1.5 text-[10px] font-bold leading-none text-white shadow-sm shadow-rose-900/30"
              aria-label="{{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }} پیام خوانده‌نشده">
            {{ $unreadCount > 99 ? '۹۹+' : \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }}
        </span>
    @endif
</span>
