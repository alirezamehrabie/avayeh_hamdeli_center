@props([
    'section',
    'active' => false,
    'badge' => null,
])

<button
    type="button"
    wire:click="$dispatch('open-dashboard-section', { section: @js($section) })"
    {{ $attributes->class([
        'flex w-full items-center justify-between gap-2 px-4 py-2 text-right text-sm',
        'rounded bg-indigo-800 text-white' => $active,
        'text-indigo-200 hover:text-white' => ! $active,
    ]) }}
>
    <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>

    @if(! is_null($badge) && (int) $badge > 0)
        <span class="inline-flex h-[1.125rem] min-w-[1.125rem] shrink-0 items-center justify-center rounded-full bg-rose-500/90 px-1.5 text-[10px] font-bold leading-none text-white">
            {{ (int) $badge > 99 ? '۹۹+' : \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) (int) $badge) }}
        </span>
    @endif
</button>

