<div class="space-y-4">
    <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-sm border border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-800">کارت‌های رگال خدمات لندینگ</h2>
            <p class="mt-1 text-sm text-gray-500">کارت‌های خدمات در دو ردیف رگال صفحه نخست.</p>
        </div>
        <a href="{{ route('landing.preview') }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
            پیش‌نمایش لندینگ
        </a>
    </div>

    @foreach(\App\Models\LandingServiceCard::RAIL_ROWS as $railRow)
        <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-4 py-3 text-sm font-bold text-gray-700">
                رگال خدمات، ردیف {{ $railRow === 1 ? 'یک' : 'دو' }}
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-bold">کارت</th>
                        <th class="px-4 py-3 text-right font-bold">عنوان</th>
                        <th class="px-4 py-3 text-center font-bold">ترتیب</th>
                        <th class="px-4 py-3 text-center font-bold">وضعیت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($cards[$railRow] ?? [] as $card)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($card->image_url)
                                        <img src="{{ $card->image_url }}" alt="" class="h-12 w-12 rounded-lg object-cover border border-gray-100" loading="lazy">
                                    @endif
                                    <span class="text-xs text-gray-500">{{ $card->image_path }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $card->title }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $card->sort_id }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($card->active_status)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">فعال</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500">غیرفعال</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">کارتی در این ردیف ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <p class="text-xs text-gray-500">ویرایش، ترتیب‌دهی و آپلود تصاویر در فازهای بعدی همین بخش فعال می‌شود.</p>
</div>
