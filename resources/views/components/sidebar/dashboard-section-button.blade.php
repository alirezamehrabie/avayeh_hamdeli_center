@props([
    'section',
    'active' => false,
])

<button
    type="button"
    wire:click="$dispatch('open-dashboard-section', { section: @js($section) })"
    {{ $attributes->class([
        'block w-full px-4 py-2 text-right text-sm',
        'rounded bg-indigo-800 text-white' => $active,
        'text-indigo-200 hover:text-white' => ! $active,
    ]) }}
>
    {{ $slot }}
</button>
