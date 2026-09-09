{{-- Collapsible gate-report list: the slot renders the list's table(s); rows
     beyond the initial limit are hidden client-side by the caller with
     x-show="listOpen". When $hiddenCount > 0 this adds a white bottom fade
     (only while collapsed) plus an expand/collapse toggle with a down arrow —
     toggling never hits the server. When everything fits, the slot renders
     untouched and no controls appear. --}}
@props([
    'hiddenCount' => 0,
])

@php $collapsible = (int) $hiddenCount > 0; @endphp

@if (! $collapsible)
    <div {{ $attributes }}>
        {{ $slot }}
    </div>
@else
    <div x-data="{ listOpen: false }">
        <div {{ $attributes->merge(['class' => 'relative']) }}>
            {{ $slot }}

            {{-- White gradient fade hinting there is more content below. --}}
            <div
                x-show="!listOpen"
                x-transition.duration.250ms
                class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-white via-white/90 to-transparent"
            ></div>
        </div>

        <button
            type="button"
            @click="listOpen = !listOpen"
            :aria-expanded="listOpen ? 'true' : 'false'"
            class="flex w-full items-center justify-center gap-1.5 border-t border-slate-100 bg-white px-4 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
        >
            <span x-text="listOpen ? 'بستن فهرست' : 'نمایش {{ number_format((int) $hiddenCount) }} مورد دیگر'">نمایش {{ number_format((int) $hiddenCount) }} مورد دیگر</span>
            <svg class="h-4 w-4 shrink-0 transition-transform duration-200" :class="listOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>
@endif
