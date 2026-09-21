<div class="space-y-4">
    <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-sm border border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-800">کارت‌های رگال خدمات لندینگ</h2>
            <p class="mt-1 text-sm text-gray-500">کارت‌های خدمات در دو ردیف رگال صفحه نخست؛ ترتیب با دکمه‌های بالا/پایین در همان ردیف.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openCreateForm"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-500">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                افزودن کارت
            </button>
            <a href="{{ route('landing.preview') }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                پیش‌نمایش لندینگ
            </a>
        </div>
    </div>

    @if (session()->has('landing-success'))
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800">
            {{ session('landing-success') }}
        </div>
    @endif

    @if($showCreateForm || $editingCardId)
        <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
            <h3 class="mb-4 text-base font-bold text-gray-800">
                {{ $editingCardId ? 'ویرایش کارت' : 'کارت جدید' }}
            </h3>

            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <span class="mb-1 block text-sm font-bold text-gray-700">منبع تصویر</span>
                    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                        <button type="button" wire:click="$set('imageMode', 'existing')"
                                class="rounded-md px-4 py-1.5 text-sm font-bold transition {{ $imageMode === 'existing' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            فایل‌های موجود
                        </button>
                        <button type="button" wire:click="$set('imageMode', 'upload')"
                                class="rounded-md px-4 py-1.5 text-sm font-bold transition {{ $imageMode === 'upload' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            آپلود تصویر جدید
                        </button>
                    </div>
                </div>

                @if($imageMode === 'existing')
                    <div>
                        <label for="card-image-path" class="mb-1 block text-sm font-bold text-gray-700">تصویر کارت</label>
                        <select id="card-image-path" wire:model="imagePath" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— انتخاب تصویر —</option>
                            @foreach($availableImages as $image)
                                <option value="{{ $image }}">{{ $image }}</option>
                            @endforeach
                        </select>
                        @error('imagePath')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        @if($imagePath && \App\Support\Landing\LandingImageCatalog::exists($imagePath))
                            <img src="{{ asset($imagePath) }}" alt="پیش‌نمایش" class="mt-3 h-20 w-20 rounded-lg border border-gray-100 object-cover" loading="lazy">
                        @endif
                    </div>
                @else
                    <div>
                        <label for="card-image-upload" class="mb-1 block text-sm font-bold text-gray-700">فایل تصویر</label>
                        <input type="file" id="card-image-upload" wire:model="imageUpload" accept=".jpg,.jpeg,.png,.webp"
                               class="block w-full text-sm text-gray-700 file:ml-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('imageUpload')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">JPG، PNG یا WebP — حداکثر ۶ مگابایت؛ خروجی WebP بهینه‌سازی‌شده (سقف ۵۱۲KB و ابعاد ۱۶۰۰px، با حفظ شفافیت) ذخیره می‌شود.</p>
                        @if($imageUpload && method_exists($imageUpload, 'temporaryUrl'))
                            @php $uploadPreviewUrl = null; try { $uploadPreviewUrl = $imageUpload->temporaryUrl(); } catch (\Throwable) { $uploadPreviewUrl = null; } @endphp
                            @if($uploadPreviewUrl)
                                <img src="{{ $uploadPreviewUrl }}" alt="پیش‌نمایش آپلود" class="mt-3 h-20 w-20 rounded-lg border border-gray-100 object-cover">
                            @endif
                        @endif
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="card-title" class="mb-1 block text-sm font-bold text-gray-700">عنوان کارت</label>
                        <input type="text" id="card-title" wire:model="title"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="مثلاً هزینه تحصیل">
                        @error('title')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="card-rail-row" class="mb-1 block text-sm font-bold text-gray-700">ردیف رگال</label>
                        <select id="card-rail-row" wire:model="railRow" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1">ردیف یک</option>
                            <option value="2">ردیف دو</option>
                        </select>
                        @error('railRow')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
                        ذخیره
                    </button>
                    <button type="button" wire:click="cancelForm" class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-bold text-gray-600 transition hover:bg-gray-200">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    @endif

    @foreach(\App\Models\LandingServiceCard::RAIL_ROWS as $railRow)
        @php $railCards = $cards[$railRow] ?? collect(); @endphp
        <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-4 py-3 text-sm font-bold text-gray-700">
                رگال خدمات، ردیف {{ $railRow === 1 ? 'یک' : 'دو' }}
                <span class="mr-2 text-xs font-semibold text-gray-400">({{ $railCards->count() }} کارت)</span>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-bold">کارت</th>
                        <th class="px-4 py-3 text-right font-bold">عنوان</th>
                        <th class="px-4 py-3 text-center font-bold">ترتیب</th>
                        <th class="px-4 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-4 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($railCards as $index => $card)
                        <tr class="{{ $card->active_status ? '' : 'bg-gray-50 opacity-60' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($card->image_url)
                                        <img src="{{ $card->image_url }}" alt="" class="h-12 w-12 rounded-lg object-cover border border-gray-100" loading="lazy">
                                    @endif
                                    <span class="text-xs text-gray-500" dir="ltr">{{ $card->image_path }}</span>
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
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" wire:click="edit({{ $card->id }})" title="ویرایش"
                                            class="rounded-lg p-2 text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" wire:confirm="از تغییر وضعیت کارت «{{ $card->title }}» مطمئن هستید؟" wire:click="toggleActive({{ $card->id }})"
                                            title="{{ $card->active_status ? 'غیرفعال کردن' : 'فعال کردن' }}"
                                            class="rounded-lg p-2 transition {{ $card->active_status ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                        <i class="bi {{ $card->active_status ? 'bi-toggle-on' : 'bi-toggle-off' }}" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" wire:click="moveUp({{ $card->id }})" title="انتقال بالا" {{ $index === 0 ? 'disabled' : '' }}
                                            class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-30">
                                        <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" wire:click="moveDown({{ $card->id }})" title="انتقال پایین" {{ $index === $railCards->count() - 1 ? 'disabled' : '' }}
                                            class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-30">
                                        <i class="bi bi-arrow-down" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">کارتی در این ردیف ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <p class="text-xs text-gray-500">غیرفعال‌سازی به‌جای حذف انجام می‌شود تا در محیط واقعی، رکورد و تصویرش همیشه قابل بازگشت بماند.</p>
</div>
