<div class="space-y-6" dir="rtl">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">چاپ کارت مددجو</h1>
        <p class="text-gray-600 mb-6">مددجویان مورد نظر را جستجو و به لیست چاپ اضافه کنید، سپس مستقیماً چاپ کنید یا خروجی دریافت کنید.</p>

        @if(session()->has('success'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session()->has('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Printer Connection Settings Panel --}}
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50/80 overflow-hidden">
            <button
                type="button"
                wire:click="togglePrinterSettings"
                class="flex w-full items-center justify-between px-5 py-4 text-right transition hover:bg-slate-100"
            >
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span class="text-base font-semibold text-slate-800">تنظیمات اتصال پرینتر</span>
                </div>
                <svg class="h-5 w-5 text-slate-400 transition-transform {{ $showPrinterSettings ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            @if($showPrinterSettings)
                <div class="border-t border-slate-200 bg-white px-5 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">نوع اتصال</label>
                            <select
                                wire:model.live="printerConnection"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                            >
                                <option value="network">شبکه (TCP/IP)</option>
                                <option value="usb">USB (Windows Share)</option>
                            </select>
                        </div>

                        @if($printerConnection === 'network')
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">آدرس IP پرینتر</label>
                                <input
                                    type="text"
                                    wire:model.live.debounce.500ms="printerHost"
                                    placeholder="مثال: 192.168.1.100"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                    dir="ltr"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">پورت</label>
                                <input
                                    type="number"
                                    wire:model.live.debounce.500ms="printerPort"
                                    placeholder="9100"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                    dir="ltr"
                                >
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">نام پرینتر اشتراکی</label>
                                <input
                                    type="text"
                                    wire:model.live.debounce.500ms="printerUsbName"
                                    placeholder="مثال: TSC_TTP-244_Pro"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                    dir="ltr"
                                >
                                <p class="mt-1 text-xs text-slate-500">نام پرینتر را از Devices & Printers ویندوز وارد کنید.</p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="printTestLabel"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            چاپ برچسب تست
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Advanced Label Layout Configuration Panel --}}
        <div class="mb-6 rounded-xl border border-violet-200 bg-violet-50/50 overflow-hidden">
            <button
                type="button"
                wire:click="toggleAdvancedLayout"
                class="flex w-full items-center justify-between px-5 py-4 text-right transition hover:bg-violet-100/50"
            >
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    <span class="text-base font-semibold text-violet-900">تنظیمات پیشرفته چیدمان برچسب</span>
                </div>
                <svg class="h-5 w-5 text-violet-400 transition-transform {{ $showAdvancedLayout ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            @if($showAdvancedLayout)
                <div class="border-t border-violet-200 bg-white px-5 py-5 space-y-6">

                    {{-- Paper & Label Dimensions --}}
                    <div>
                        <h3 class="text-sm font-bold text-violet-900 mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 4h4M4 4l5 5m11-1V4m0 4h-4m4-4l-5 5M4 16v4m0-4h4m-4 4l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                            ابعاد کاغذ و برچسب
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">عرض کاغذ (mm)</label>
                                <input type="number" step="0.5" min="0" max="300" wire:model.live.debounce.500ms="paperWidthMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">عرض برچسب (mm)</label>
                                <input type="number" step="0.5" min="10" max="200" wire:model.live.debounce.500ms="labelWidthMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">ارتفاع برچسب (mm)</label>
                                <input type="number" step="0.5" min="10" max="200" wire:model.live.debounce.500ms="labelHeightMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">فاصله بین برچسب (mm)</label>
                                <input type="number" step="0.5" min="0" max="20" wire:model.live.debounce.500ms="gapMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">فاصله از لبه رول (mm)</label>
                                <input type="number" step="0.5" min="0" max="20" wire:model.live.debounce.500ms="edgeMarginMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">فاصله از بالا (mm)</label>
                                <input type="number" step="0.5" min="0" max="20" wire:model.live.debounce.500ms="topMarginMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">فاصله از پایین (mm)</label>
                                <input type="number" step="0.5" min="0" max="20" wire:model.live.debounce.500ms="bottomMarginMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">رزولوشن (DPI)</label>
                                <select wire:model.live="dpi" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100">
                                    <option value="203">203 DPI</option>
                                    <option value="300">300 DPI</option>
                                    <option value="600">600 DPI</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">تعداد ستون</label>
                                <select wire:model.live="columns" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100">
                                    <option value="1">۱ ستون</option>
                                    <option value="2">۲ ستون</option>
                                    <option value="3">۳ ستون</option>
                                    <option value="4">۴ ستون</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">حالت چیدمان</label>
                                <select wire:model.live="layoutMode" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100">
                                    <option value="vertical">عمودی</option>
                                    <option value="horizontal">افقی</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- QR Code Settings --}}
                    <div>
                        <h3 class="text-sm font-bold text-violet-900 mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            تنظیمات کد QR
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">اندازه QR (dots)</label>
                                <input type="number" step="10" min="50" max="500" wire:model.live.debounce.500ms="qrSizeDots" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">بزرگ‌نمایی (Magnification)</label>
                                <input type="number" min="1" max="10" wire:model.live.debounce.500ms="qrMagnification" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">سطح تصحیح خطا</label>
                                <select wire:model.live="qrErrorCorrection" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100">
                                    <option value="L">L (7%)</option>
                                    <option value="M">M (15%)</option>
                                    <option value="Q">Q (25%)</option>
                                    <option value="H">H (30%)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Text Settings --}}
                    <div>
                        <h3 class="text-sm font-bold text-violet-900 mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                            تنظیمات متن کد مددجو
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">اندازه فونت (dots)</label>
                                <input type="number" min="8" max="120" wire:model.live.debounce.500ms="textFontSize" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">فاصله از پایین (dots)</label>
                                <input type="number" min="0" max="500" wire:model.live.debounce.500ms="textBottomOffsetDots" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                        </div>
                    </div>

                    {{-- Rotation & Actions --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-violet-100">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" wire:model.live="rotate180" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <span class="text-sm font-medium text-slate-700">چرخش ۱۸۰ درجه (معکوس)</span>
                        </label>

                        <button
                            type="button"
                            wire:click="resetLayoutDefaults"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-100"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            بازگشت به پیش‌فرض
                        </button>
                    </div>

                    {{-- Live Calculated Summary --}}
                    <div class="rounded-lg bg-violet-50 border border-violet-100 px-4 py-3">
                        <p class="text-xs text-violet-700 leading-relaxed">
                            <strong>خلاصه محاسبات:</strong>
                            عرض کاغذ = {{ $paperWidthMm }} mm
                            | برچسب = {{ $labelWidthMm }}×{{ $labelHeightMm }} mm
                            | فاصله ستون = {{ $gapMm }} mm
                            | حالت = {{ $layoutMode === 'horizontal' ? 'افقی' : 'عمودی' }}
                            | تعداد برچسب برای {{ count($this->printList) }} مددجو = {{ intdiv(count($this->printList) + max(1, $columns) - 1, max(1, $columns)) }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Search Section --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
                    <h2 class="text-base font-semibold text-slate-800 mb-4">جستجوی مددجو</h2>

                    <div class="flex gap-2 mb-3">
                        <select
                            wire:model.live="searchField"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                        >
                            <option value="all">همه فیلدها</option>
                            <option value="national_id">کد ملی</option>
                            <option value="person_code">کد مددجو</option>
                        </select>

                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="نام، کد ملی یا کد مددجو..."
                            class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                        >
                    </div>

                    @if($this->showSearchResults && count($this->searchResults) > 0)
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                            @foreach($this->searchResults as $result)
                                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5 last:border-b-0 hover:bg-slate-50 transition">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-800">{{ $result['full_name'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            کد ملی: {{ $result['national_id'] }} | کد مددجو: {{ $result['person_code'] }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="addToPrintList({{ $result['id'] }})"
                                        class="mr-3 inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                    >
                                        افزودن
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @elseif($this->showSearchResults && count($this->searchResults) === 0)
                        <div class="rounded-lg border border-dashed border-slate-200 bg-white p-4 text-center text-sm text-slate-500">
                            نتیجه‌ای یافت نشد.
                        </div>
                    @endif
                </div>

                {{-- Social Worker Selector --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
                    <h2 class="text-base font-semibold text-slate-800 mb-4">افزودن مددجویان یک مددکار</h2>

                    @if($selectedSocialWorkerId && $this->selectedSocialWorker)
                        <div class="mb-3 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-emerald-800">{{ $this->selectedSocialWorker->first_name }} {{ $this->selectedSocialWorker->last_name }}</p>
                                <p class="text-xs text-emerald-600">کد مددکار: {{ $this->selectedSocialWorker->worker_code }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="clearSocialWorkerSelection"
                                class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                تغییر مددکار
                            </button>
                        </div>

                        <button
                            type="button"
                            wire:click="addSocialWorkerClientsToPrintList"
                            wire:loading.attr="disabled"
                            wire:target="addSocialWorkerClientsToPrintList"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:opacity-60"
                        >
                            <svg wire:loading wire:target="addSocialWorkerClientsToPrintList" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            افزودن تمام مددجویان این مددکار به لیست چاپ
                        </button>
                    @else
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="socialWorkerSearch"
                            placeholder="نام یا کد مددکار را جستجو کنید..."
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                        >

                        @if(!empty($socialWorkerOptions))
                            <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                                @foreach($socialWorkerOptions as $sw)
                                    <button
                                        type="button"
                                        wire:click="selectSocialWorker({{ $sw['id'] }})"
                                        class="flex w-full items-center justify-between border-b border-slate-100 px-3 py-2.5 text-right last:border-b-0 hover:bg-slate-50 transition"
                                    >
                                        <span class="text-sm font-medium text-slate-800">{{ $sw['name'] }}</span>
                                        <span class="text-xs text-slate-500">کد: {{ $sw['code'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif(mb_strlen(trim($socialWorkerSearch)) >= 2 && empty($socialWorkerOptions))
                            <div class="mt-2 rounded-lg border border-dashed border-slate-200 bg-white p-3 text-center text-sm text-slate-500">
                                مددکاری یافت نشد.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Print List Section --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-800">
                            لیست چاپ
                            <span class="mr-2 inline-flex items-center justify-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700">
                                {{ count($this->printList) }}
                            </span>
                        </h2>

                        @if(count($this->printList) > 0)
                            <button
                                type="button"
                                wire:click="clearPrintList"
                                wire:confirm="آیا مطمئن هستید که می‌خواهید لیست چاپ را پاک کنید؟"
                                class="text-xs font-medium text-red-500 transition hover:text-red-700"
                            >
                                پاک کردن همه
                            </button>
                        @endif
                    </div>

                    @if(count($this->printList) > 0)
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                            @foreach($this->printList as $index => $item)
                                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5 last:border-b-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-800">{{ $item['full_name'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            کد ملی: {{ $item['national_id'] }} | کد مددجو: {{ $item['person_code'] }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeFromPrintList({{ $index }})"
                                        class="mr-3 inline-flex shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100"
                                    >
                                        حذف
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        {{-- Preview & Direct Print Buttons --}}
                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                wire:click="togglePreview"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-sm font-bold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                {{ $showPreview ? 'بستن پیش‌نمایش' : 'پیش‌نمایش' }}
                            </button>

                            <button
                                type="button"
                                wire:click="printDirectly"
                                wire:loading.attr="disabled"
                                @disabled(! $showPreview)
                                wire:target="printDirectly"
                                wire:confirm="آیا مطمئن هستید که می‌خواهید {{ count($this->printList) }} کارت را مستقیماً به پرینتر ارسال کنید؟"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-100 disabled:opacity-60"
                            >
                                <svg wire:loading wire:target="printDirectly" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg wire:loading.remove wire:target="printDirectly" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                چاپ مستقیم ({{ count($this->printList) }} کارت)
                            </button>
                        </div>

                        {{-- Export Buttons --}}
                        <div class="mt-3 flex gap-2">
                            <button
                                type="button"
                                wire:click="exportToCsv"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                دانلود CSV
                            </button>

                            <button
                                type="button"
                                wire:click="exportToExcel"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                دانلود Excel
                            </button>
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-200 bg-white p-8 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            <p class="mt-3 text-sm font-medium text-slate-500">لیست چاپ خالی است</p>
                            <p class="mt-1 text-xs text-slate-400">مددجویان مورد نظر را از بخش جستجو اضافه کنید.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Label Preview Modal --}}
        @if($showPreview && count($this->printList) > 0)
            <div class="mt-6 rounded-xl border border-indigo-200 bg-white shadow-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50 px-5 py-3">
                    <h3 class="text-sm font-bold text-indigo-900">
                        پیش‌نمایش برچسب‌ها
                        ({{ count($this->printList) }} کارت - {{ intdiv(count($this->printList) + max(1, $columns) - 1, max(1, $columns)) }} برچسب)
                    </h3>
                    <button
                        type="button"
                        wire:click="togglePreview"
                        class="inline-flex items-center justify-center rounded-lg p-1.5 text-indigo-500 transition hover:bg-indigo-100 hover:text-indigo-700"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-5">
                    <p class="mb-4 text-xs text-slate-500">
                        هر ردیف معادل یک برچسب فیزیکی {{ $labelWidthMm }}×{{ $labelHeightMm }} میلی‌متری با {{ $columns }} ستون است.
                        <span class="font-semibold text-slate-700">حالت: {{ $layoutMode === 'horizontal' ? 'افقی' : 'عمودی' }}</span>
                        @if($rotate180)
                            <span class="font-semibold text-amber-600">⚠ چرخش ۱۸۰ درجه فعال است.</span>
                        @endif
                    </p>

                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                        @foreach(array_chunk($this->previewItems, max(1, $columns)) as $labelIndex => $row)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="mb-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">برچسب {{ $labelIndex + 1 }}</p>
                                <div class="grid gap-3" style="grid-template-columns: repeat({{ max(1, $columns) }}, minmax(0, 1fr));">
                                    @foreach($row as $item)
                                        <div class="flex {{ $layoutMode === 'horizontal' ? 'flex-row gap-3' : 'flex-col' }} items-center justify-center rounded-md border border-dashed border-slate-300 bg-white p-3" style="min-height: 120px;">
                                            <div class="{{ $layoutMode === 'horizontal' ? 'shrink-0' : 'mb-2' }} [&>svg]:block [&>svg]:mx-auto [&>svg]:w-full [&>svg]:h-full" style="width: {{ min(80, max(40, $qrSizeDots * 25.4 / max(1, $dpi) * 2)) }}px; height: {{ min(80, max(40, $qrSizeDots * 25.4 / max(1, $dpi) * 2)) }}px;">
                                                {!! $item['qr_svg'] !!}
                                            </div>
                                            <p class="{{ $layoutMode === 'horizontal' ? 'text-right' : 'text-center' }} text-xs font-bold text-slate-800 font-mono tracking-wide" style="font-size: {{ min(14, max(8, $textFontSize / 2)) }}px;">{{ $item['person_code'] }}</p>
                                        </div>
                                    @endforeach

                                    @for($i = count($row); $i < max(1, $columns); $i++)
                                        <div class="flex items-center justify-center rounded-md border border-dashed border-slate-200 bg-slate-50/50 p-3" style="min-height: 120px;">
                                            <span class="text-xs text-slate-300">خالی</span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/80 p-4">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-blue-800">راهنمای چاپ مستقیم با ZPL</p>
                    <p class="mt-1 text-xs leading-5 text-blue-700">
                        دکمه «چاپ مستقیم» کدهای QR و کد مددجو را به صورت ZPL تولید کرده و مستقیماً به پرینتر TSC ارسال می‌کند.
                        تنظیمات چیدمان از پنل «تنظیمات پیشرفته» قابل تنظیم دقیق هستند.
                        ابتدا تنظیمات پرینتر را بررسی کنید و با «چاپ برچسب تست» از اتصال و چیدمان مطمئن شوید.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
