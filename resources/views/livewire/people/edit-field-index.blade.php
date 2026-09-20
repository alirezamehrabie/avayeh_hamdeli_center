@php
    $persianNumber = fn (int $value) => \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $value);
@endphp

<div class="space-y-4">
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1">ویرایش فیلد</h1>
                <p class="text-sm text-gray-500">فیلدی را که می‌خواهید ویرایش کنید انتخاب کنید؛ پس از آن می‌توانید مددجو را جستجو و مقدار فیلد را ثبت کنید.</p>
            </div>
            @if($needLevelRecords > 0)
                <span class="shrink-0 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600">
                    <i class="bi bi-database ml-1 text-gray-400"></i>
                    {{ $persianNumber($needLevelRecords) }} رکورد سطح نیاز ثبت‌شده
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($fields as $field)
            <button type="button"
                    wire:click="$dispatch('open-dashboard-section', { section: @js($field['section']) })"
                    class="group flex flex-col rounded-xl border border-gray-100 bg-white p-4 text-right shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-100 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                <div class="flex items-center justify-between gap-3">
                    <span class="flex min-w-0 items-center gap-2.5">
                        <i class="{{ $field['icon'] }} shrink-0 text-2xl {{ $field['iconClass'] }}" aria-hidden="true"></i>
                        <span class="truncate text-base font-bold text-gray-800">{{ $field['title'] }}</span>
                    </span>
                    @if(! empty($field['badge']))
                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $field['badgeClass'] }}">
                            {{ $field['badge'] }}
                        </span>
                    @endif
                </div>

                <div class="mt-3.5 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($field['chips'] ?? [] as $chip)
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-bold text-gray-600">
                                <span class="h-2 w-2 rounded-full {{ $chip['dot'] }}"></span>
                                {{ $chip['label'] }}
                            </span>
                        @endforeach
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-sm font-bold text-indigo-700 transition group-hover:gap-2.5">
                        ورود ویرایش
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </span>
                </div>
            </button>
        @endforeach
    </div>
</div>
