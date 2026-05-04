<div>
    <div class="flex items-center py-4 px-2 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-3 ml-1 bg-{{ $color }}-100 text-{{ $color }}-600 rounded-lg">
            <!-- در اینجا از آیکون‌های SVG استفاده کنید یا FontAwesome -->
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="text-md font-bold text-gray-800">{{ is_numeric($value) ? number_format($value) : $value }}{{ $suffix ? ' ' . $suffix : '' }}</p>
        </div>
    </div>
</div>
