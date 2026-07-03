<div class="space-y-4" dir="rtl">
    @php
        $selectedPerson = $this->selectedPerson;
        $searchResults = $this->searchResults;
        $serviceDeliveries = $this->serviceDeliveries;
        $activityAttendances = $this->activityAttendances;
        $timeline = $this->timeline;
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400">پرونده خدمات و فعالیت‌ها</p>
                <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">پرونده مددجو</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    نسخه اولیه این بخش، سوابق ثبت‌شده در دفتر خدمات، گیت‌های توزیع و حضور در فعالیت‌ها را به‌صورت خواندنی نمایش می‌دهد.
                </p>
            </div>

            @if($selectedPerson)
                <button
                    type="button"
                    wire:click="clearSelection"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                >
                    انتخاب مددجوی دیگر
                </button>
            @endif
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
            <label for="case-file-person-search" class="mb-2 block text-sm font-bold text-slate-700">جستجوی مددجو</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                    <i class="bi bi-search text-sm"></i>
                </span>
                <input
                    id="case-file-person-search"
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pr-9 pl-4 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="نام، کد ملی یا کد مددجو..."
                >
            </div>

            @if(trim($search) !== '' && $searchResults->isEmpty())
                <p class="mt-2 text-xs font-semibold text-slate-500">برای جستجوی متنی حداقل ۲ کاراکتر وارد کنید یا عبارت دقیق‌تری بنویسید.</p>
            @endif

            @if($searchResults->isNotEmpty())
                <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($searchResults as $person)
                        <button
                            type="button"
                            wire:click="selectPerson({{ $person->id }})"
                            class="rounded-xl border border-slate-200 bg-white p-3 text-right transition hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        >
                            <span class="block text-sm font-black text-slate-800">{{ $person->full_name ?: trim($person->first_name.' '.$person->last_name) }}</span>
                            <span class="mt-1 block text-xs text-slate-500">کد مددجو: {{ $person->person_code ?: '-' }}</span>
                            <span class="mt-1 block text-xs text-slate-500">کد ملی: {{ $person->national_id ?: '-' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if(! $selectedPerson)
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                <i class="bi bi-folder2-open text-2xl"></i>
            </div>
            <h2 class="mt-4 text-base font-black text-slate-800">برای مشاهده پرونده، یک مددجو را انتخاب کنید</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                پس از انتخاب، خلاصه هویتی، خدمات دریافتی، فعالیت‌ها و ارزش‌های مالی ثبت‌شده در سیستم نمایش داده می‌شود.
            </p>
        </section>
    @else
        <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">{{ $selectedPerson->full_name ?: trim($selectedPerson->first_name.' '.$selectedPerson->last_name) }}</h2>
                            <p class="mt-1 text-sm text-slate-500">کد مددجو: {{ $selectedPerson->person_code ?: '-' }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                            {{ $selectedPerson->created_at ? 'عضویت از '.$this->formatDate($selectedPerson->created_at) : 'تاریخ عضویت ثبت نشده' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">کد ملی</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->national_id ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">سن</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->age ? $selectedPerson->age.' سال' : '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">سرپرست</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->guardian?->full_name ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-400">مددکار</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedPerson->socialWorker ? trim($selectedPerson->socialWorker->first_name.' '.$selectedPerson->socialWorker->last_name) : '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900">خط زمانی پرونده</h2>
                            <p class="mt-1 text-sm text-slate-500">ترکیب خدمات تحویل‌شده و حضورهای ثبت‌شده در فعالیت‌ها</p>
                        </div>
                        <span class="text-xs font-bold text-slate-400">{{ number_format($timeline->count()) }} رکورد</span>
                    </div>

                    @if($timeline->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                            برای این مددجو هنوز خدمت یا فعالیتی در دفتر فعلی ثبت نشده است.
                        </div>
                    @else
                        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-xs font-black text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 text-right">تاریخ</th>
                                            <th class="px-4 py-3 text-right">نوع</th>
                                            <th class="px-4 py-3 text-right">عنوان</th>
                                            <th class="px-4 py-3 text-right">مقدار/روش</th>
                                            <th class="px-4 py-3 text-right">ارزش/فاکتور</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($timeline as $row)
                                            <tr>
                                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $this->formatDate($row['date']) }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex rounded-full {{ $row['type'] === 'service' ? 'bg-emerald-50 text-emerald-700' : 'bg-cyan-50 text-cyan-700' }} px-2.5 py-1 text-xs font-bold">
                                                        {{ $row['badge'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-bold text-slate-800">{{ $row['title'] }}</p>
                                                    @if($row['subtitle'])
                                                        <p class="mt-1 text-xs text-slate-500">{{ $row['subtitle'] }}</p>
                                                    @endif
                                                    @if(! empty($row['details']))
                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                            @foreach($row['details'] as $label => $value)
                                                                <span class="rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">{{ $label }}: {{ $value }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['quantity'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $row['value'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-black text-slate-900">خلاصه پرونده</h2>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2">
                            <span class="text-xs font-bold text-emerald-700">خدمات تحویل‌شده</span>
                            <span class="text-sm font-black text-emerald-900">{{ number_format($serviceDeliveries->count()) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-cyan-50 px-3 py-2">
                            <span class="text-xs font-bold text-cyan-700">حضور در فعالیت‌ها</span>
                            <span class="text-sm font-black text-cyan-900">{{ number_format($activityAttendances->count()) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 px-3 py-2">
                            <span class="text-xs font-bold text-amber-700">ارزش ثبت‌شده</span>
                            <span class="text-sm font-black text-amber-900">{{ number_format((int) $serviceDeliveries->sum('delivered_total_value')) }} ریال</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-black text-slate-800">یادداشت فاز بعد</h2>
                    <p class="mt-2 text-xs leading-6 text-slate-500">
                        در این نسخه فقط داده‌های موجود نمایش داده می‌شود. برای فاکتور رسمی، پیوست‌ها، یادداشت دستی مددکاری و ثبت سوابق خارج از چرخه توزیع، نیاز به طراحی جدول‌های اختصاصی پرونده داریم.
                    </p>
                </div>
            </aside>
        </section>
    @endif
</div>
