<div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all duration-300 hover:shadow-lg hover:ring-gray-200 hover:-translate-y-0.5">

    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-{{ $color }}-50 opacity-60 blur-2xl transition-all duration-500 group-hover:scale-150 group-hover:opacity-80"></div>

    <div class="relative flex items-start justify-between gap-2">

        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-{{ $color }}-50 text-{{ $color }}-500 transition-transform duration-300 group-hover:scale-110">

            <span class="absolute inset-0 rounded-xl bg-{{ $color }}-400/20 animate-ping opacity-0 group-hover:opacity-100" style="animation-duration: 1.5s;"></span>
            <svg class="relative h-5 w-5 transition-transform duration-300 group-hover:rotate-[-8deg]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.0" d="{{ $icon }}"></path>
            </svg>
        </div>

        {{-- Badges --}}
        @if(!empty($badges))
            <div class="flex flex-wrap justify-end gap-1.5">
                @foreach($badges as $badge)
                    @php
                        $badgeColor = $badge['color'] ?? 'slate';
                        $badgeLabel = $badge['label'] ?? '';
                        $badgeValue = $badge['value'] ?? '';
                    @endphp
                    <div class="inline-flex items-center gap-1 rounded-lg bg-{{ $badgeColor }}-50 px-2 py-1 text-[11px] font-medium text-{{ $badgeColor }}-600 transition-transform duration-200 hover:scale-105">
                        <span class="opacity-75">{{ $badgeLabel }}</span>
                        <span class="font-semibold text-{{ $badgeColor }}-700">
                            {{ is_numeric($badgeValue) ? number_format($badgeValue) : $badgeValue }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    <div class="relative mt-4">
        <p class="text-xl font-bold tracking-tight text-gray-900 transition-all duration-300 group-hover:text-{{ $color }}-700">
            {{ is_numeric($value) ? number_format($value) : $value }}
            @if($suffix)
                <span class="text-sm font-medium text-gray-400">{{ $suffix }}</span>
            @endif
        </p>
        <p class="mt-0.5 text-xs font-medium text-gray-400">{{ $title }}</p>
    </div>

    <div class="absolute bottom-0 left-0 h-0.5 w-0 bg-gradient-to-r from-{{ $color }}-400 via-{{ $color }}-300 to-transparent transition-all duration-500 group-hover:w-full"></div>

</div>
