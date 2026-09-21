<div class="space-y-4">
    <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-sm border border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-800">اسلایدر بنرهای لندینگ</h2>
            <p class="mt-1 text-sm text-gray-500">تصاویر بنر اسلایدی صفحه نخست؛ ترتیب نمایش با شماره ترتیب است.</p>
        </div>
        <a href="{{ route('landing.preview') }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
            پیش‌نمایش لندینگ
        </a>
    </div>

    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-right font-bold">بنر</th>
                    <th class="px-4 py-3 text-right font-bold">متن جایگزین</th>
                    <th class="px-4 py-3 text-center font-bold">ترتیب</th>
                    <th class="px-4 py-3 text-center font-bold">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($banners as $banner)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($banner->image_url)
                                    <img src="{{ $banner->image_url }}" alt="" class="h-14 w-24 rounded-lg object-cover border border-gray-100" loading="lazy">
                                @endif
                                <span class="text-xs text-gray-500">{{ $banner->image_path }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $banner->alt_text }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $banner->sort_id }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($banner->active_status)
                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">فعال</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500">غیرفعال</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">هنوز بنری ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500">ویرایش، ترتیب‌دهی و آپلود تصاویر در فازهای بعدی همین بخش فعال می‌شود.</p>
</div>
