<div class="space-y-4">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-slate-800 via-slate-700 to-slate-800 px-5 py-4 text-white">
            <h1 class="text-2xl font-extrabold">آرشیو خدمات</h1>
            <p class="mt-1.5 text-xs text-slate-100/90">خدمت‌ها، نام‌ها و دسته‌بندی‌های حذف‌شده برای بازیابی امن و نگهداری سوابق تاریخی در این بخش باقی می‌مانند.</p>
        </div>

        <div class="space-y-4 px-4 py-4">
            @if (session()->has('archive-success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    {{ session('archive-success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-800">موارد حذف‌شده</h2>
                        <p class="text-xs text-slate-500">بازیابی فقط زمانی انجام می‌شود که با رکوردهای فعال تداخل نداشته باشد.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">
                        {{ $archivedServices->count() + $archivedServiceNames->count() + $archivedServiceCategories->count() }} مورد
                    </span>
                </div>

                <div class="grid gap-4 xl:grid-cols-3">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-700">خدمت‌ها</h3>
                            <span class="text-[11px] font-semibold text-slate-400">{{ $archivedServices->count() }} مورد</span>
                        </div>

                        <div class="space-y-1.5">
                            @forelse($archivedServices as $item)
                                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $item->serviceName?->name ?: $item->name ?: $item->code }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                            <span dir="ltr">{{ $item->code }}</span>
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ $item->categories_count }} دسته
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ $item->deliveries_count }} تحویل
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ optional($item->deleted_at)->format('Y-m-d H:i') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="openRestoreServiceConfirmation({{ $item->id }})" class="shrink-0 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100">
                                        بازیابی
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-5 text-center text-xs text-slate-400">
                                    خدمت حذف‌شده‌ای وجود ندارد.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-700">نام‌های خدمت</h3>
                            <span class="text-[11px] font-semibold text-slate-400">{{ $archivedServiceNames->count() }} مورد</span>
                        </div>

                        <div class="space-y-1.5">
                            @forelse($archivedServiceNames as $item)
                                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $item->name }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                            {{ $item->services_count }} خدمت
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ $item->category_templates_count }} دسته
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ optional($item->deleted_at)->format('Y-m-d H:i') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="openRestoreServiceNameConfirmation({{ $item->id }})" class="shrink-0 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100">
                                        بازیابی
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-5 text-center text-xs text-slate-400">
                                    نام خدمت حذف‌شده‌ای وجود ندارد.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-700">دسته‌بندی‌های خدمت</h3>
                            <span class="text-[11px] font-semibold text-slate-400">{{ $archivedServiceCategories->count() }} مورد</span>
                        </div>

                        <div class="space-y-1.5">
                            @forelse($archivedServiceCategories as $item)
                                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $item->name }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                            {{ $item->serviceName?->name ?? 'نام خدمت حذف‌شده' }}
                                            <span class="mx-1 text-slate-300">•</span>
                                            {{ optional($item->deleted_at)->format('Y-m-d H:i') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="openRestoreCategoryConfirmation({{ $item->id }})" class="shrink-0 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100">
                                        بازیابی
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-5 text-center text-xs text-slate-400">
                                    دسته‌بندی حذف‌شده‌ای وجود ندارد.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
