@props([
    'type',
    'textClass' => 'text-slate-700',
    'iconWrapClass' => 'bg-slate-100 ring-slate-200/80 text-slate-500',
])

@php
    $meta = \App\Models\Service::typeDisplayMeta($type);
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium', $textClass]) }}>
    <span class="{{ $iconWrapClass }} inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full ring-1 ring-inset" aria-hidden="true">
        @if($meta['icon'] === 'family')
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 18v-1a3 3 0 0 0-3-3h-2a3 3 0 0 0-3 3v1" />
                <path d="M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                <path d="M18.5 18v-.5a2.5 2.5 0 0 0-1.5-2.29" />
                <path d="M7 15.21A2.5 2.5 0 0 0 5.5 17.5v.5" />
                <path d="M16.5 6.5a2.5 2.5 0 0 1 0 5" />
                <path d="M7.5 6.5a2.5 2.5 0 0 0 0 5" />
            </svg>
        @else
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                <path d="M6.5 19a5.5 5.5 0 0 1 11 0" />
            </svg>
        @endif
    </span>
    <span>{{ $meta['label'] }}</span>
</span>
