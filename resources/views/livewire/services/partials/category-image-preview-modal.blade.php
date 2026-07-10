<div
    x-show="categoryImagesOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/35 px-4 py-6 backdrop-blur-sm"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="service-category-images-title"
    x-on:keydown.escape.window="categoryImagesOpen = false"
>
    <div class="absolute inset-0" @click="categoryImagesOpen = false"></div>

    <div
        @click.stop
        class="relative w-full max-w-4xl overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl shadow-slate-950/20"
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/80 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 id="service-category-images-title" class="text-lg font-black text-slate-900">تصاویر دسته‌بندی‌ها</h3>
                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-black text-sky-800 ring-1 ring-sky-200">{{ count($this->categoryImagePreviews) }} مورد</span>
                </div>
                <p class="mt-1 text-sm font-medium text-slate-500">پیش‌نمایش سریع تصاویر ثبت‌شده برای دسته‌های این خدمت</p>
            </div>

            <button
                type="button"
                @click="categoryImagesOpen = false"
                class="rounded-full border border-slate-200 bg-white p-2 text-slate-400 shadow-sm transition hover:bg-slate-50 hover:text-slate-700"
                aria-label="بستن"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <div class="max-h-[78vh] overflow-y-auto px-4 py-5 sm:px-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($this->categoryImagePreviews as $item)
                    <section x-data="{ imageAvailable: @js($item['is_available']) }" class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm shadow-slate-950/5">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-black text-slate-800">{{ $item['name'] }}</p>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">تصویر</span>
                        </div>

                        <div class="p-3">
                            <template x-if="imageAvailable">
                                <div class="overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200/70">
                                    <img
                                        src="{{ $item['image_url'] ?? '' }}"
                                        alt="تصویر {{ $item['name'] }}"
                                        class="h-56 w-full object-cover"
                                        loading="lazy"
                                        x-on:error="imageAvailable = false"
                                    >
                                </div>
                            </template>

                            <template x-if="!imageAvailable">
                                <div class="flex h-56 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 text-center">
                                    <div>
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-slate-500">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-9-8h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-bold text-slate-600">فایل تصویر در دسترس نیست</p>
                                        <p class="mt-1 text-xs font-medium text-slate-400">ممکن است فایل حذف شده باشد یا بارگذاری آن با خطا روبه‌رو شود.</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
