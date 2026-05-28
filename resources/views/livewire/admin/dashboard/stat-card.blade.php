<div>
    <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 bg-white px-3 py-4 shadow-sm">
        <div class="flex min-w-0 items-center">
            <div class="ml-1 rounded-lg bg-{{ $color }}-100 p-3 text-{{ $color }}-600">
                <!-- در اینجا از آیکون‌های SVG استفاده کنید یا FontAwesome -->
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="mb-2 text-sm font-medium text-gray-500">{{ $title }}</p>
                <p class="text-md font-bold text-gray-800">{{ is_numeric($value) ? number_format($value) : $value }}{{ $suffix ? ' ' . $suffix : '' }}</p>
            </div>
        </div>

        @if(!empty($badges))
            <div class="flex flex-wrap justify-end gap-2">
                @foreach($badges as $badge)
                    @php
                        $badgeColor = $badge['color'] ?? 'slate';
                        $badgeLabel = $badge['label'] ?? '';
                        $badgeValue = $badge['value'] ?? '';
                    @endphp
                    <div class="inline-flex items-center gap-2 rounded-full border border-{{ $badgeColor }}-100 bg-{{ $badgeColor }}-50 px-3 py-1 text-xs font-semibold text-{{ $badgeColor }}-700">
                        <span>{{ $badgeLabel }}</span>
                        <span class="rounded-full bg-white/80 px-2 py-0.5 text-{{ $badgeColor }}-800">
                            {{ is_numeric($badgeValue) ? number_format($badgeValue) : $badgeValue }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
