<div class="group relative flex h-full min-h-[104px] overflow-hidden rounded-2xl bg-white p-3 shadow-sm ring-1 ring-gray-100 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-gray-200 sm:min-h-0 sm:p-4">
    <div class="absolute -right-6 -top-6 hidden h-24 w-24 rounded-full bg-{{ $color }}-50 opacity-60 blur-2xl transition-all duration-500 group-hover:scale-150 group-hover:opacity-80 sm:block"></div>

    <div class="relative flex w-full flex-col justify-between gap-2">
        <div class="flex items-start justify-between gap-2">
            <div class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-{{ $color }}-50 text-{{ $color }}-500 transition-transform duration-300 group-hover:scale-105 sm:h-10 sm:w-10 sm:rounded-xl">
                <span class="absolute inset-0 rounded-lg bg-{{ $color }}-400/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100 sm:rounded-xl"></span>
                <svg class="relative h-3.5 w-3.5 transition-transform duration-300 group-hover:rotate-[-4deg] sm:h-5 sm:w-5 sm:group-hover:rotate-[-6deg]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.0" d="{{ $icon }}"></path>
                </svg>
            </div>

            @if(!empty($badges))
                <div class="hidden flex-wrap justify-end gap-1.5 sm:flex">
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

        <div class="space-y-0.5">
            <p class="text-[20px] font-bold leading-none tracking-tight text-gray-900 transition-all duration-300 group-hover:text-{{ $color }}-700 sm:text-xl">
                {{ is_numeric($value) ? number_format($value) : $value }}
                @if($suffix)
                    <span class="text-[11px] font-medium text-gray-400 sm:text-sm">{{ $suffix }}</span>
                @endif
            </p>
            <p class="text-[11px] font-medium leading-snug text-gray-400 sm:text-xs">{{ $title }}</p>
        </div>

        @if(!empty($badges))
            <div class="flex flex-wrap gap-1 sm:hidden">
                @foreach($badges as $badge)
                    @php
                        $badgeColor = $badge['color'] ?? 'slate';
                        $badgeLabel = $badge['label'] ?? '';
                        $badgeValue = $badge['value'] ?? '';
                    @endphp
                    <div class="inline-flex items-center gap-1 rounded-md bg-{{ $badgeColor }}-50 px-1.5 py-0.5 text-[10px] font-medium text-{{ $badgeColor }}-700">
                        <span>{{ $badgeLabel }}</span>
                        <span>{{ is_numeric($badgeValue) ? number_format($badgeValue) : $badgeValue }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="absolute bottom-0 left-0 h-0.5 w-0 bg-gradient-to-r from-{{ $color }}-400 via-{{ $color }}-300 to-transparent transition-all duration-500 group-hover:w-full"></div>
</div>
