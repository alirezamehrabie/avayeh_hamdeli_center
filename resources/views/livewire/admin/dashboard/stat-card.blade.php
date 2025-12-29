<div>
    <div class="flex items-center p-4 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-3 ml-4 bg-{{ $color }}-100 text-{{ $color }}-600 rounded-lg">
            <!-- در اینجا از آیکون‌های SVG استفاده کنید یا FontAwesome -->
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
            </svg>
        </div>
        <div>
            <p class="mb-1 text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($value) }}</p>
        </div>
    </div>
</div>
