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
                                <option value="usb">پرینتر ویندوز / محلی</option>
                                <option value="bridge">پرینتر ویندوزِ همین رایانه (سایت روی هاست اصلی)</option>
                                <option value="browser">چاپ از طریق مرورگر</option>
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
                        @elseif($printerConnection === 'bridge')
                            <div class="md:col-span-2">
                                <div x-data="printBridgeSettings" class="rounded-lg border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                                                :class="{ 'bg-slate-400 animate-pulse': status === 'checking', 'bg-emerald-500': status === 'online', 'bg-red-500': status === 'offline' }"
                                            ></span>
                                            <span class="text-sm font-medium text-slate-700" x-text="statusText()"></span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="refreshStatus()" class="text-xs font-semibold text-indigo-600 transition hover:text-indigo-800">
                                                بررسی اتصال
                                            </button>
                                            <button
                                                type="button"
                                                @click="scanPrinters()"
                                                :disabled="scanning"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:opacity-60"
                                            >
                                                <svg :class="{ 'animate-spin': scanning }" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                شناسایی پرینترهای ویندوز
                                            </button>
                                        </div>
                                    </div>

                                    <div x-show="printers.length > 0">
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">پرینتر شناسایی‌شده روی همین رایانه</label>
                                        <select
                                            @change="selectPrinter($event.target.value)"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                            dir="ltr"
                                        >
                                            <option value="">انتخاب پرینتر</option>
                                            <template x-for="p in printers" :key="p.name">
                                                <option :value="p.name" x-text="(p.is_default ? p.name + ' (پیش‌فرض ویندوز)' : p.name)" :selected="selectedPrinter === p.name"></option>
                                            </template>
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500">
                                            انتخاب شما فقط برای همین رایانه ذخیره می‌شود؛ هر سیستم چاپ خودش را دارد.
                                            <span x-show="selectedPrinter !== ''" class="font-mono text-slate-600" x-text="'پرینتر فعال: ' + selectedPrinter"></span>
                                        </p>
                                    </div>

                                    <p x-show="scanError !== ''" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" x-text="scanError"></p>

                                    <p x-show="status === 'offline'" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
                                        برای چاپ مستقیم از طریق دامنه اصلی، برنامه کوچک «پل چاپ» باید روی همین رایانه (ویندوز) اجرا شود.
                                        پوشه <span class="font-mono">print-bridge</span> پروژه را روی این سیستم کپی کنید و فایل
                                        <span class="font-mono">start-bridge.bat</span> را اجرا نمایید؛ سپس «بررسی اتصال» را بزنید.
                                        این برنامه فقط از داخل همین رایانه قابل دسترسی است.
                                    </p>

                                    <details class="text-xs text-slate-600">
                                        <summary class="cursor-pointer select-none font-semibold text-slate-500 hover:text-slate-700">تنظیمات پیشرفته اتصال (آدرس و توکن)</summary>
                                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block font-medium text-slate-600 mb-1">آدرس برنامه پل چاپ</label>
                                                <input
                                                    type="text"
                                                    x-model="urlInput"
                                                    @change="persistConnection(); refreshStatus()"
                                                    placeholder="http://127.0.0.1:9235"
                                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                                    dir="ltr"
                                                >
                                            </div>
                                            <div>
                                                <label class="block font-medium text-slate-600 mb-1">توکن امنیتی (اختیاری)</label>
                                                <input
                                                    type="text"
                                                    x-model="tokenInput"
                                                    @change="persistConnection(); refreshStatus()"
                                                    placeholder="اگر در bridge token.txt تنظیم شده باشد"
                                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                                    dir="ltr"
                                                >
                                            </div>
                                        </div>
                                    </details>

                                    <div>
                                        <button
                                            type="button"
                                            @click="testPrint()"
                                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            چاپ برچسب تست
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div>
                                @if($printerConnection === 'usb')
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-sm font-medium text-slate-700">پرینتر شناسایی‌شده روی همین سیستم</label>
                                        <button
                                            type="button"
                                            wire:click="refreshDetectedLocalPrinters"
                                            class="text-xs font-semibold text-indigo-600 transition hover:text-indigo-800"
                                        >
                                            به‌روزرسانی
                                        </button>
                                    </div>

                                    @if(!empty($detectedLocalPrinters))
                                        <select
                                            wire:model.live="printerUsbName"
                                            class="mb-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                            dir="ltr"
                                        >
                                            <option value="">انتخاب پرینتر</option>
                                            @foreach($detectedLocalPrinters as $printerName)
                                                <option value="{{ $printerName }}">{{ $printerName }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    <input
                                        type="text"
                                        wire:model.live.debounce.500ms="printerUsbName"
                                        placeholder="مثال: TSC_TTP-244_Pro"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                        dir="ltr"
                                    >
                                    <p class="mt-1 text-xs text-slate-500">اگر پرینتر در فهرست نیست، نام آن را دستی وارد کنید.</p>
                                    @if(! $canDetectLocalPrinters)
                                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
                                            سایت روی این هاست به پرینترهای ویندوزِ رایانهٔ شما دسترسی ندارد؛ شناسایی خودکار فقط وقتی کار می‌کند که سایت روی همان سیستم ویندوزی اجرا شود.
                                            برای چاپ از پرینترِ همین رایانه در حالت دامنهٔ اصلی، گزینهٔ «پرینتر ویندوزِ همین رایانه (سایت روی هاست اصلی)» را انتخاب کنید.
                                        </p>
                                    @endif
                                @else
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600">
                                        چاپ از طریق مرورگر، پرینترهای نصب‌شده روی همین دستگاه را در پنجره چاپ سیستم نشان می‌دهد.
                                    </div>
                                @endif
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
                            {{ $printerConnection === 'browser' ? 'باز کردن چاپ تست' : 'چاپ برچسب تست' }}
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
                                <select wire:model.live="columns" disabled class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 opacity-70 focus:border-violet-300 focus:ring focus:ring-violet-100">
                                    <option value="1">۱ ستون</option>
                                    <option value="2">۲ ستون</option>
                                    <option value="3">۳ ستون</option>
                                    <option value="4">۴ ستون</option>
                                </select>
                                <p class="mt-1 text-[10px] text-slate-400">برای این رول، مقدار ثابت ۲ است.</p>
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
                                <label class="block text-xs font-medium text-slate-600 mb-1">فاصله QR و متن (mm)</label>
                                <input type="number" step="0.5" min="-20" max="20" wire:model.live.debounce.500ms="qrTextGapMm" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">چرخش متن (درجه)</label>
                                <select wire:model.live="qrTextRotationDeg" class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100">
                                    <option value="0">۰ درجه</option>
                                    <option value="90">۹۰ درجه</option>
                                    <option value="180">۱۸۰ درجه</option>
                                    <option value="270">۲۷۰ درجه</option>
                                </select>
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

                    {{-- Save / Restore Layout Settings --}}
                    <div class="rounded-lg border border-violet-100 bg-violet-50 px-4 py-4" x-data>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-bold text-violet-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                            ذخیره و بازیابی تنظیمات چیدمان
                        </h3>
                        <p class="mb-3 text-xs leading-relaxed text-violet-700">
                            مقادیر فعلی این بخش و ویرایشگر بصری (ابعاد، فاصله‌ها، اندازه فونت و ...) در یک فایل ذخیره می‌شود؛
                            پس از هر بارگذاری مجدد صفحه یا تغییر رایانه، همان فایل را وارد کنید تا همه تنظیمات به‌صورت خودکار بازگردند.
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="exportLayoutSettings"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                دریافت فایل تنظیمات (JSON)
                            </button>

                            <button
                                type="button"
                                @click="$refs.layoutImportPicker.click()"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-800"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"></path>
                                </svg>
                                انتخاب فایل تنظیمات
                            </button>
                            {{-- Hidden on purpose: opened programmatically so the browser never scrolls to it --}}
                            <input
                                type="file"
                                accept=".json,application/json"
                                wire:model="layoutImportFile"
                                x-ref="layoutImportPicker"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                            >

                            <button
                                type="button"
                                wire:click="importLayoutSettings"
                                @disabled(! $layoutImportFile)
                                wire:loading.attr="disabled"
                                wire:target="importLayoutSettings"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-100 disabled:opacity-60"
                            >
                                <svg wire:loading.remove wire:target="importLayoutSettings" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <svg wire:loading wire:target="importLayoutSettings" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                بارگذاری تنظیمات از فایل
                            </button>

                            <span wire:loading wire:target="layoutImportFile" class="text-xs font-medium text-slate-500">در حال آماده‌سازی فایل...</span>
                        </div>

                        @if($layoutImportFile)
                            <p class="mt-2 text-xs text-slate-600">
                                فایل انتخاب‌شده:
                                <span class="font-mono font-semibold text-slate-800">{{ $layoutImportFile->getClientOriginalName() }}</span>
                            </p>
                        @endif
                    </div>

                    {{-- Live Calculated Summary --}}
                    <div class="rounded-lg bg-violet-50 border border-violet-100 px-4 py-3">
                        <p class="text-xs text-violet-700 leading-relaxed">
                            <strong>خلاصه محاسبات:</strong>
                            عرض کاغذ = {{ $paperWidthMm }} mm
                            | هر ستون = {{ $labelWidthMm }}×{{ $labelHeightMm }} mm
                            | دو ستون در هر ردیف
                            | فاصله ستون = {{ $gapMm }} mm
                            | حالت = {{ $layoutMode === 'horizontal' ? 'افقی' : 'عمودی' }}
                            | تعداد ردیف برای {{ count($this->printList) }} مددجو = {{ intdiv(count($this->printList) + 1, 2) }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Visual Label Editor --}}
        <div class="mb-6 rounded-xl border border-fuchsia-200 bg-fuchsia-50/50 overflow-hidden">
            <button
                type="button"
                wire:click="toggleVisualEditor"
                class="flex w-full items-center justify-between px-5 py-4 text-right transition hover:bg-fuchsia-100/50"
            >
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                    <span class="text-base font-semibold text-fuchsia-900">ویرایشگر بصری چیدمان برچسب</span>
                </div>
                <svg class="h-5 w-5 text-fuchsia-400 transition-transform {{ $showVisualEditor ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            @if($showVisualEditor)
                @php
                    $editorScale = 4;
                @endphp
                <div class="border-t border-fuchsia-200 bg-white px-5 py-5" x-data="labelEditor({
                    labelWidthMm: @entangle('labelWidthMm'),
                    labelHeightMm: @entangle('labelHeightMm'),
                    qrSizeDots: @entangle('qrSizeDots'),
                    qrTextGapMm: @entangle('qrTextGapMm'),
                    edgeMarginMm: @entangle('edgeMarginMm'),
                    topMarginMm: @entangle('topMarginMm'),
                    bottomMarginMm: @entangle('bottomMarginMm'),
                    textFontSize: @entangle('textFontSize'),
                    textBottomOffsetDots: @entangle('textBottomOffsetDots'),
                    qrTextRotationDeg: @entangle('qrTextRotationDeg'),
                    layoutMode: @entangle('layoutMode'),
                    dpi: @entangle('dpi'),
                    rotate180: @entangle('rotate180'),
                })">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-fuchsia-700 leading-relaxed">
                            المان‌ها را با ماوس بکشید یا از اسلایدرها و ورودی‌های عددی استفاده کنید. تغییرات فوراً در تنظیمات چیدمان اعمال می‌شوند.
                        </p>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-medium text-slate-600">بزرگ‌نمایی:</label>
                            <select x-model.number="scale" class="rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-fuchsia-300 focus:ring focus:ring-fuchsia-100">
                                <option value="2">2×</option>
                                <option value="3">3×</option>
                                <option value="4">4×</option>
                                <option value="5">5×</option>
                                <option value="6">6×</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-6">
                        {{-- Canvas Area --}}
                        <div class="flex-1 flex flex-col items-center">
                            <div
                                class="relative bg-white border-2 border-fuchsia-300 shadow-md select-none overflow-hidden cursor-crosshair"
                                :style="`width: ${labelWidthMm * scale}px; height: ${labelHeightMm * scale}px;`"
                                @mousedown="onCanvasMouseDown($event)"
                                @mousemove="onCanvasMouseMove($event)"
                                @mouseup="onCanvasMouseUp()"
                                @mouseleave="onCanvasMouseUp()"
                            >
                                {{-- Grid lines --}}
                                <template x-for="gx in Math.floor(labelWidthMm / 5)" :key="'gx'+gx">
                                    <div class="absolute top-0 bottom-0 border-l border-fuchsia-100 pointer-events-none" :style="`left: ${gx * 5 * scale}px;`"></div>
                                </template>
                                <template x-for="gy in Math.floor(labelHeightMm / 5)" :key="'gy'+gy">
                                    <div class="absolute left-0 right-0 border-t border-fuchsia-100 pointer-events-none" :style="`top: ${gy * 5 * scale}px;`"></div>
                                </template>

                                {{-- Margin guides --}}
                                <div class="absolute border border-dashed border-blue-300/50 pointer-events-none" :style="`top: ${topMarginMm * scale}px; left: ${edgeMarginMm * scale}px; right: ${edgeMarginMm * scale}px; bottom: ${bottomMarginMm * scale}px;`"></div>

                                {{-- QR Code element --}}
                                <div
                                    class="absolute flex items-center justify-center bg-violet-100/60 border border-violet-400 cursor-move hover:bg-violet-200/70 transition-colors"
                                    :class="{ 'ring-2 ring-violet-500 z-10': dragging === 'qr' }"
                                    :style="qrStyle()"
                                    @mousedown.stop="startDrag('qr', $event)"
                                >
                                    <span class="font-bold text-violet-700 pointer-events-none" :style="`font-size: ${Math.max(6, scale * 1.5)}px;`">QR</span>
                                </div>

                                {{-- Text element --}}
                                <div
                                    class="absolute flex items-center justify-center bg-amber-100/60 border border-amber-400 cursor-move hover:bg-amber-200/70 transition-colors"
                                    :class="{ 'ring-2 ring-amber-500 z-10': dragging === 'text' }"
                                    :style="textStyle()"
                                    @mousedown.stop="startDrag('text', $event)"
                                >
                                    <span class="font-mono font-bold text-amber-800 pointer-events-none whitespace-nowrap" :style="`font-size: ${Math.max(6, fontSizePx())}px; transform: rotate(${qrTextRotationDeg}deg);`">کد مددجو</span>
                                </div>

                                {{-- Dimension labels --}}
                                <div class="absolute -top-5 left-0 text-[9px] text-slate-400 pointer-events-none" :style="`width: ${labelWidthMm * scale}px; text-align: center;`">
                                    <span x-text="labelWidthMm + ' mm'"></span>
                                </div>
                                <div class="absolute -left-12 top-0 text-[9px] text-slate-400 pointer-events-none" :style="`height: ${labelHeightMm * scale}px; display: flex; align-items: center; writing-mode: vertical-rl;`">
                                    <span x-text="labelHeightMm + ' mm'"></span>
                                </div>
                            </div>

                            {{-- Position readout --}}
                            <div class="mt-3 flex gap-4 text-[10px] text-slate-500">
                                <span>موقعیت QR: <span class="font-mono text-violet-600" x-text="`X=${qrPos.x.toFixed(1)} Y=${qrPos.y.toFixed(1)} mm`"></span></span>
                                <span>موقعیت متن: <span class="font-mono text-amber-600" x-text="`X=${textPos.x.toFixed(1)} Y=${textPos.y.toFixed(1)} mm`"></span></span>
                                <span x-show="dragging" class="text-fuchsia-600 font-semibold">در حال جابجایی...</span>
                            </div>
                        </div>

                        {{-- Controls Panel --}}
                        <div class="w-full lg:w-72 space-y-4">
                            {{-- QR Code Controls --}}
                            <div class="rounded-lg border border-violet-200 bg-violet-50/50 p-3">
                                <h4 class="text-xs font-bold text-violet-800 mb-2 flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded bg-violet-400"></div>
                                    کد QR
                                </h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[10px] font-medium text-slate-600 mb-0.5">اندازه (dots): <span class="font-mono text-violet-700" x-text="qrSizeDots"></span></label>
                                        <input type="range" min="50" max="500" step="10" x-model.number="qrSizeDots" class="w-full h-1.5 bg-violet-200 rounded-lg appearance-none cursor-pointer accent-violet-600">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">X (mm)</label>
                                            <input type="number" step="0.5" min="0" :max="labelWidthMm" x-model.number="qrPos.x" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">Y (mm)</label>
                                            <input type="number" step="0.5" min="0" :max="labelHeightMm" x-model.number="qrPos.y" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-violet-300 focus:ring focus:ring-violet-100" dir="ltr">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Text Controls --}}
                            <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-3">
                                <h4 class="text-xs font-bold text-amber-800 mb-2 flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded bg-amber-400"></div>
                                    متن کد مددجو
                                </h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[10px] font-medium text-slate-600 mb-0.5">اندازه فونت (dots): <span class="font-mono text-amber-700" x-text="textFontSize"></span></label>
                                        <input type="range" min="8" max="120" step="1" x-model.number="textFontSize" class="w-full h-1.5 bg-amber-200 rounded-lg appearance-none cursor-pointer accent-amber-600">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">X (mm)</label>
                                            <input type="number" step="0.5" x-model.number="textPos.x" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-amber-300 focus:ring focus:ring-amber-100" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">Y (mm)</label>
                                            <input type="number" step="0.5" x-model.number="textPos.y" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-amber-300 focus:ring focus:ring-amber-100" dir="ltr">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-slate-600 mb-0.5">چرخش متن</label>
                                        <select x-model.number="qrTextRotationDeg" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-amber-300 focus:ring focus:ring-amber-100">
                                            <option value="0">۰ درجه</option>
                                            <option value="90">۹۰ درجه</option>
                                            <option value="180">۱۸۰ درجه</option>
                                            <option value="270">۲۷۰ درجه</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Spacing Controls --}}
                            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                <h4 class="text-xs font-bold text-slate-700 mb-2">فاصله‌ها و حاشیه‌ها</h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[10px] font-medium text-slate-600 mb-0.5">فاصله QR تا متن (mm): <span class="font-mono" x-text="qrTextGapMm"></span></label>
                                        <input type="range" min="-20" max="20" step="0.5" x-model.number="qrTextGapMm" class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-600">
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">بالا (mm)</label>
                                            <input type="number" step="0.5" min="0" max="20" x-model.number="topMarginMm" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-slate-300 focus:ring focus:ring-slate-100" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">پایین (mm)</label>
                                            <input type="number" step="0.5" min="0" max="20" x-model.number="bottomMarginMm" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-slate-300 focus:ring focus:ring-slate-100" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">لبه (mm)</label>
                                            <input type="number" step="0.5" min="0" max="20" x-model.number="edgeMarginMm" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs text-slate-700 focus:border-slate-300 focus:ring focus:ring-slate-100" dir="ltr">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Layout Mode & Rotation --}}
                            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-medium text-slate-700">حالت چیدمان</label>
                                    <select x-model="layoutMode" class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-fuchsia-300 focus:ring focus:ring-fuchsia-100">
                                        <option value="horizontal">افقی</option>
                                        <option value="vertical">عمودی</option>
                                    </select>
                                </div>
                                <label class="mt-2 flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" x-model="rotate180" class="rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">
                                    <span class="text-xs font-medium text-slate-700">چرخش ۱۸۰ درجه</span>
                                </label>
                            </div>
                        </div>
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

                {{-- Registration Date Batch Add --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
                    <h2 class="text-base font-semibold text-slate-800 mb-1">افزودن مددجویان ثبت‌شده در یک روز مشخص</h2>
                    <p class="mb-4 text-xs text-slate-500">تاریخ‌هایی را که مددجو ثبت شده‌اند انتخاب و یک‌جا به لیست چاپ اضافه کنید.</p>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                        <div>
                            <input
                                type="text"
                                wire:model.live.debounce.500ms="registrationDate"
                                data-jdp
                                data-jdp-only-date
                                placeholder="مثال: 1403/01/15"
                                inputmode="numeric"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:ring focus:ring-indigo-100"
                                dir="ltr"
                            >

                            @if($this->registrationDate !== '' && $preview = $this->registrationDatePreview)
                                <p class="mt-2 text-xs {{ $preview['valid'] ? 'text-slate-500' : 'text-red-500' }}">
                                    @if($preview['valid'])
                                        {{ $preview['count'] }} مددجو در این تاریخ ثبت شده‌اند.
                                    @else
                                        تاریخ وارد شده معتبر نیست.
                                    @endif
                                </p>
                            @endif
                        </div>

                        <button
                            type="button"
                            wire:click="addRegistrationDateClientsToPrintList"
                            wire:loading.attr="disabled"
                            wire:target="addRegistrationDateClientsToPrintList"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:opacity-60"
                        >
                            <svg wire:loading wire:target="addRegistrationDateClientsToPrintList" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            افزودن به لیست چاپ
                        </button>
                    </div>
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
                                @disabled($printerConnection !== 'browser' && ! $showPreview)
                                wire:target="printDirectly"
                                wire:confirm="{{ $printerConnection === 'browser' ? 'آیا می‌خواهید پنجره چاپ سیستم باز شود؟' : 'آیا مطمئن هستید که می‌خواهید ' . count($this->printList) . ' کارت را مستقیماً به پرینتر ارسال کنید؟' }}"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-100 disabled:opacity-60"
                            >
                                <svg wire:loading wire:target="printDirectly" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg wire:loading.remove wire:target="printDirectly" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                {{ $printerConnection === 'browser' ? 'چاپ با مرورگر' : 'چاپ مستقیم' }} ({{ count($this->printList) }} کارت)
                            </button>
                        </div>

                        {{-- Download Print File Button --}}
                        <div class="mt-3">
                            <button
                                type="button"
                                wire:click="downloadPrintFile"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                دانلود فایل چاپ ({{ strtoupper(config('label-printer.printer.language', 'tspl')) }})
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

        {{-- Accurate Label Preview --}}
        @if($showPreview && count($this->printList) > 0)
            @php
                $qrSizeMm = $qrSizeDots * 25.4 / max(1, $dpi);
                $fontSizePt = $textFontSize * 72 / max(1, $dpi);
            @endphp
            <div class="mt-6 rounded-xl border border-indigo-200 bg-white shadow-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50 px-5 py-3">
                    <h3 class="text-sm font-bold text-indigo-900">
                        پیش‌نمایش دقیق برچسب‌ها
                        ({{ count($this->printList) }} کارت - {{ intdiv(count($this->printList) + 1, 2) }} ردیف)
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
                    <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3">
                        <p class="text-xs text-blue-800 leading-relaxed">
                            <strong>پیش‌نمایش واقعی:</strong> ابعاد زیر دقیقاً مطابق اندازه واقعی برچسب روی کاغذ {{ $paperWidthMm }} میلی‌متری است.
                            هر برچسب {{ $labelWidthMm }}×{{ $labelHeightMm }} میلی‌متر | فاصله بین دو برچسب {{ $gapMm }} میلی‌متر |
                            حاشیه از لبه {{ $edgeMarginMm }} میلی‌متر | QR Code ≈ {{ number_format($qrSizeMm, 1) }} میلی‌متر |
                            فاصله QR و متن {{ $qrTextGapMm }} میلی‌متر |
                            چرخش متن {{ $qrTextRotationDeg }} درجه |
                            فونت متن ≈ {{ number_format($fontSizePt, 1) }} پوینت
                            <span class="font-semibold">| حالت: {{ $layoutMode === 'horizontal' ? 'افقی' : 'عمودی' }}</span>
                            @if($rotate180)
                                <span class="font-semibold text-amber-600">| ⚠ چرخش ۱۸۰° فعال</span>
                            @endif
                        </p>
                    </div>

                    <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
                        @foreach(array_chunk($this->previewItems, 2) as $labelIndex => $row)
                            <div>
                                <p class="mb-1.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                    ردیف {{ $labelIndex + 1 }}
                                </p>
                                <div class="inline-block rounded border border-slate-300 bg-white shadow-sm" style="width: {{ $paperWidthMm }}mm; padding: 0;">
                                    <div class="flex" style="gap: {{ $gapMm }}mm; padding: 0 {{ $edgeMarginMm }}mm;">
                                        @foreach($row as $item)
                                            <div
                                                class="relative border border-dashed border-slate-300 bg-white overflow-hidden"
                                                style="width: {{ $labelWidthMm }}mm; height: {{ $labelHeightMm }}mm; @if($rotate180) transform: rotate(180deg); @endif"
                                            >
                                                @if($layoutMode === 'vertical')
                                                    <div class="absolute flex items-center justify-center" style="top: {{ $topMarginMm }}mm; left: 0; right: 0; height: {{ $qrSizeMm }}mm;">
                                                        <div style="width: {{ $qrSizeMm }}mm; height: {{ $qrSizeMm }}mm;" class="[&>svg]:block [&>svg]:w-full [&>svg]:h-full">
                                                            {!! $item['qr_svg'] !!}
                                                        </div>
                                                    </div>
                                                    <div class="absolute flex items-center justify-center" style="bottom: {{ $bottomMarginMm }}mm; left: 0; right: 0;">
                                                        <span class="font-mono font-bold text-slate-900 whitespace-nowrap" style="font-size: {{ $fontSizePt }}pt;">{{ $item['person_code'] }}</span>
                                                    </div>
                                                @else
                                                    @php
                                                        $isLandscape = in_array($qrTextRotationDeg, [90, 270]);
                                                        $textBlockWidthMm = $isLandscape ? ($labelHeightMm - $topMarginMm - $bottomMarginMm) : ($labelWidthMm - $qrSizeMm - $qrTextGapMm - $edgeMarginMm);
                                                        $textBlockHeightMm = $isLandscape ? ($qrSizeMm + $qrTextGapMm) : ($labelHeightMm - $topMarginMm - $bottomMarginMm);
                                                    @endphp
                                                    <div class="absolute flex items-start" style="top: {{ $topMarginMm }}mm; left: {{ $edgeMarginMm }}mm;">
                                                        <div style="width: {{ $qrSizeMm }}mm; height: {{ $qrSizeMm }}mm;" class="shrink-0 [&>svg]:block [&>svg]:w-full [&>svg]:h-full">
                                                            {!! $item['qr_svg'] !!}
                                                        </div>
                                                    </div>
                                                    <div class="absolute flex items-center justify-center" style="left: {{ $edgeMarginMm + $qrSizeMm + $qrTextGapMm }}mm; width: {{ $textBlockWidthMm }}mm; @if($isLandscape) top: {{ $topMarginMm }}mm; height: {{ $textBlockHeightMm }}mm; @else top: 0; bottom: {{ $bottomMarginMm }}mm; @endif">
                                                        <span class="inline-block origin-center font-mono font-bold text-slate-900 whitespace-nowrap" style="font-size: {{ $fontSizePt }}pt; transform: rotate({{ $qrTextRotationDeg }}deg); transform-origin: center;">
                                                            {{ $item['person_code'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                        @for($i = count($row); $i < 2; $i++)
                                            <div
                                                class="border border-dashed border-slate-200 bg-slate-50/50 flex items-center justify-center"
                                                style="width: {{ $labelWidthMm }}mm; height: {{ $labelHeightMm }}mm;"
                                            >
                                                <span class="text-[8px] text-slate-300">خالی</span>
                                            </div>
                                        @endfor
                                    </div>
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
                    <p class="text-sm font-semibold text-blue-800">راهنمای چاپ</p>
                    <p class="mt-1 text-xs leading-5 text-blue-700">
                        دکمه «چاپ مستقیم» برچسب‌ها را با زبان {{ strtoupper(config('label-printer.printer.language', 'tspl')) }} تولید و به پرینتر ارسال می‌کند.
                        دکمه «دانلود فایل چاپ» همان فایل را برای چاپ دستی با نرم‌افزار TSC دانلود می‌کند.
                        پیش‌نمایش بالا ابعاد واقعی برچسب ({{ $labelWidthMm }}×{{ $labelHeightMm }} میلی‌متر) را روی کاغذ {{ $paperWidthMm }} میلی‌متری شبیه‌سازی می‌کند.
                        اگر حالت «چاپ از طریق مرورگر» را انتخاب کنید، پنجره چاپ سیستم باز می‌شود.
                        ابتدا با «چاپ برچسب تست» از اتصال و چیدمان مطمئن شوید.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
