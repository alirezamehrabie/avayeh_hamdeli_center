@php
    // طیف رنگ از «پایین» (سرد/آرام) به «بحرانی» (گرم/هشدار) — کلاس‌ها کامل و ثابت
    // نوشته شده‌اند تا Tailwind آن‌ها را در بیلد حذف نکند.
    $levelTones = [
        'E' => ['strip' => 'bg-gradient-to-l from-emerald-400/80 to-emerald-200/30', 'dot' => 'bg-gradient-to-br from-emerald-400 to-emerald-600', 'glass' => 'bg-emerald-50/30', 'selected' => 'peer-checked:border-emerald-400/70 peer-checked:bg-emerald-50/70 peer-checked:ring-emerald-300/40 peer-checked:shadow-emerald-200/35', 'pill' => 'bg-emerald-600', 'caption' => 'کمترین اولویت رسیدگی'],
        'D' => ['strip' => 'bg-gradient-to-l from-lime-400/80 to-lime-200/30', 'dot' => 'bg-gradient-to-br from-lime-400 to-lime-600', 'glass' => 'bg-lime-50/30', 'selected' => 'peer-checked:border-lime-400/70 peer-checked:bg-lime-50/70 peer-checked:ring-lime-300/40 peer-checked:shadow-lime-200/35', 'pill' => 'bg-lime-600', 'caption' => 'اولویت پایین'],
        'C' => ['strip' => 'bg-gradient-to-l from-amber-400/80 to-amber-200/30', 'dot' => 'bg-gradient-to-br from-amber-400 to-amber-600', 'glass' => 'bg-amber-50/30', 'selected' => 'peer-checked:border-amber-400/70 peer-checked:bg-amber-50/70 peer-checked:ring-amber-300/40 peer-checked:shadow-amber-200/35', 'pill' => 'bg-amber-600', 'caption' => 'اولویت متوسط'],
        'B' => ['strip' => 'bg-gradient-to-l from-orange-500/80 to-orange-300/30', 'dot' => 'bg-gradient-to-br from-orange-400 to-orange-600', 'glass' => 'bg-orange-50/30', 'selected' => 'peer-checked:border-orange-400/70 peer-checked:bg-orange-50/70 peer-checked:ring-orange-300/40 peer-checked:shadow-orange-200/35', 'pill' => 'bg-orange-600', 'caption' => 'اولویت بالا'],
        'A' => ['strip' => 'bg-gradient-to-l from-rose-500/80 to-rose-300/30', 'dot' => 'bg-gradient-to-br from-rose-500 to-rose-700', 'glass' => 'bg-rose-50/30', 'selected' => 'peer-checked:border-rose-500/70 peer-checked:bg-rose-50/70 peer-checked:ring-rose-300/40 peer-checked:shadow-rose-200/35', 'pill' => 'bg-rose-700', 'caption' => 'وضعیت بحرانی و فوری'],
    ];
@endphp

<div class="space-y-4"
     x-data="{ needSheetOpen: false }"
     x-on:open-need-level-sheet.window="needSheetOpen = true"
     x-on:close-need-level-sheet.window="needSheetOpen = false">
    {{-- ═══ انتخاب مددجو ═══ --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1">ویرایش فیلد</h1>
        <p class="text-sm text-gray-500 mb-5">برای ویرایش یک فیلد، ابتدا مددجو را با کد ملی، کد مددجویی یا نام و نام خانوادگی پیدا و انتخاب کنید.</p>

        @if($person)
            @php
                $guardian = $person->guardian;
                $insuranceName = ($guardian?->insurance_status && $guardian?->insuranceType?->name)
                    ? $guardian->insuranceType->name
                    : null;

                $coverage = $person->supportCoverage;
                $coverageOrg = $coverage?->organization;
                $coverageName = match (true) {
                    $coverage === null => null,
                    $coverageOrg?->slug === 'other' => $coverage->other_organization_name ?: ($coverageOrg?->name ?? null),
                    default => $coverageOrg?->name ?? null,
                };
            @endphp
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-3.5 py-3 sm:px-5 sm:py-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fa fa-user"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-800">
                            {{ $person->full_name }}
                            @if($person->father_name)
                                <span class="mr-1 text-xs font-medium text-gray-500">(نام پدر: {{ $person->father_name }})</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500">
                            کد مددجویی: {{ $person->person_code ?? '—' }}
                            <span class="mx-2 text-gray-300">|</span>
                            کد ملی: {{ $person->national_id ?? '—' }}
                        </p>
                        @if($insuranceName || $coverageName)
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                @if($insuranceName)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                        <i class="fa fa-shield"></i>
                                        بیمه: {{ $insuranceName }}
                                    </span>
                                @endif
                                @if($coverageName)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700">
                                        <i class="fa fa-building"></i>
                                        تحت پوشش: {{ $coverageName }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="needSheetOpen = true"
                            class="inline-flex lg:hidden items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition active:scale-[0.98]">
                        <i class="fa fa-signal"></i>
                        سطح نیاز
                    </button>
                    <button type="button" wire:click="resetSelection"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                        <i class="fa fa-search"></i>
                        تغییر مددجو
                    </button>
                </div>
            </div>
        @else
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-gray-400">
                    <i class="fa fa-search"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" autocomplete="off"
                       placeholder="کد ملی، کد مددجویی یا نام و نام خانوادگی…"
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pr-11 pl-4 text-sm focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:outline-none transition"/>
            </div>

            @if(trim($search) !== '')
                <div class="mt-3 overflow-hidden rounded-xl border border-gray-100">
                    @forelse($searchResults as $result)
                        <button type="button" wire:click="selectPerson({{ $result->id }})"
                                class="flex w-full items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 py-3 text-right transition last:border-b-0 hover:bg-indigo-50">
                            <span class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                    <i class="fa fa-user text-xs"></i>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-gray-800">{{ $result->full_name }}</span>
                                    <span class="block text-xs text-gray-500">کد ملی: {{ $result->national_id ?? '—' }}</span>
                                </span>
                            </span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                {{ $result->person_code ?? '—' }}
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                            <i class="fa fa-info-circle ml-1"></i>
                            مددجویی با این مشخصات یافت نشد. برای جستجوی نام حداقل ۲ نویسه وارد کنید.
                        </div>
                    @endforelse
                </div>
            @endif
        @endif

        @if($flashMessage)
            <div class="mt-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                <i class="fa fa-check-circle"></i>
                {{ $flashMessage }}
            </div>
        @endif
    </div>

    {{-- ═══ ویرایش سطح نیاز — دسکتاپ ═══ --}}
    @if($person)
        <div class="hidden lg:block bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800">سطح نیاز</h2>
                <span class="text-xs text-gray-400">تنها همین فیلد در این صفحه قابل ویرایش است</span>
            </div>
            <p class="text-sm text-gray-500 mb-5">مقدار «سطح نیاز» برای «{{ $person->full_name }}» را از طیف پایین تا بحرانی انتخاب کنید:</p>

            @include('livewire.people.partials.need-level-cards', ['listLayout' => false, 'radioName' => 'need_level_grid'])

            @error('needLevelId')
                <p class="mt-3 text-sm font-semibold text-rose-600"><i class="fa fa-exclamation-circle ml-1"></i>{{ $message }}</p>
            @enderror

            <div class="mt-6 flex items-center gap-3">
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-500 active:scale-[0.98] disabled:opacity-60">
                    <span wire:loading.remove wire:target="save"><i class="fa fa-floppy-o"></i> ذخیره تغییرات</span>
                    <span wire:loading wire:target="save">در حال ذخیره…</span>
                </button>
                <span class="text-xs text-gray-400">با ذخیره، فقط رکورد «سطح نیاز» این مددجو به‌روزرسانی می‌شود.</span>
            </div>
        </div>

        {{-- ═══ شیت پایین «سطح نیاز» — موبایل ═══ --}}
        <div class="lg:hidden" wire:key="need-level-sheet">
            {{-- پس‌زمینه تیره --}}
            <div x-show="needSheetOpen" x-cloak x-transition.opacity.duration.200ms
                 @click="needSheetOpen = false"
                 class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-[2px]"
                 style="display: none;"></div>

            {{-- خود شیت --}}
            <div x-show="needSheetOpen" x-cloak
                 x-transition:enter="transform transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full"
                 class="fixed inset-x-0 bottom-0 z-50 flex max-h-[88dvh] flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl"
                 style="display: none;">

                {{-- هدر جمع‌وجور: اطلاعات مددجو در نهایتاً دو خط --}}
                <div class="shrink-0 border-b border-gray-100 bg-white/95 px-4 pb-3 pt-2 backdrop-blur">
                    <div class="mx-auto mb-2 h-1.5 w-12 rounded-full bg-gray-300" aria-hidden="true"></div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-gray-800">
                                {{ $person->full_name }}
                                @if($person->father_name)
                                    <span class="text-[11px] font-medium text-gray-500">(نام پدر: {{ $person->father_name }})</span>
                                @endif
                            </p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-500">
                                کد مددجویی: {{ $person->person_code ?? '—' }}
                                <span class="mx-1 text-gray-300">·</span>کد ملی: {{ $person->national_id ?? '—' }}
                                @if($insuranceName)
                                    <span class="mx-1 text-gray-300">·</span>بیمه: {{ $insuranceName }}
                                @endif
                                @if($coverageName)
                                    <span class="mx-1 text-gray-300">·</span>تحت پوشش: {{ $coverageName }}
                                @endif
                            </p>
                        </div>
                        <button type="button" @click="needSheetOpen = false"
                                class="-mr-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition active:scale-95"
                                aria-label="بستن">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @if($flashMessage)
                        <div class="mt-2 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700">
                            <i class="fa fa-check-circle"></i>
                            <span class="truncate">{{ $flashMessage }}</span>
                        </div>
                    @endif
                </div>

                {{-- بدنه اسکرول‌شونده: کارت‌های طیف نیاز --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                    <p class="mb-1 text-xs font-bold text-gray-700">تعیین سطح نیاز</p>
                    <p class="mb-3 text-[11px] text-gray-400">از طیف پایین تا بحرانی انتخاب کنید:</p>

                    @include('livewire.people.partials.need-level-cards', ['listLayout' => true, 'radioName' => 'need_level_sheet'])

                    @error('needLevelId')
                        <p class="mt-3 text-xs font-semibold text-rose-600"><i class="fa fa-exclamation-circle ml-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- دکمه ثبت چسبان در ته شیت --}}
                <div class="shrink-0 border-t border-gray-100 bg-white/95 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur">
                    <button type="button" wire:click="save" wire:loading.attr="disabled"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-extrabold text-white shadow-md transition hover:bg-indigo-500 active:scale-[0.99] disabled:opacity-60">
                        <span wire:loading.remove wire:target="save"><i class="fa fa-floppy-o"></i> ثبت سطح نیاز</span>
                        <span wire:loading wire:target="save">در حال ذخیره…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
