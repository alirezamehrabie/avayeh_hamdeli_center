@php
    // طیف رنگ از «پایین» (سرد/آرام) به «بحرانی» (گرم/هشدار) — کلاس‌ها کامل و ثابت
    // نوشته شده‌اند تا Tailwind آن‌ها را در بیلد حذف نکند.
    $levelTones = [
        'E' => ['strip' => 'bg-gradient-to-l from-emerald-400/80 to-emerald-200/30', 'dot' => 'bg-gradient-to-br from-emerald-400 to-emerald-600', 'glass' => 'bg-emerald-50/30', 'selected' => 'peer-checked:border-emerald-400/70 peer-checked:bg-emerald-50/70 peer-checked:ring-emerald-300/40 peer-checked:shadow-emerald-200/35', 'pill' => 'bg-emerald-600', 'caption' => 'کمترین اولویت رسیدگی'],
        'D' => ['strip' => 'bg-gradient-to-l from-lime-400/80 to-lime-200/30', 'dot' => 'bg-gradient-to-br from-lime-400 to-lime-600', 'glass' => 'bg-lime-50/30', 'selected' => 'peer-checked:border-lime-400/70 peer-checked:bg-lime-50/70 peer-checked:ring-lime-300/40 peer-checked:shadow-lime-200/35', 'pill' => 'bg-lime-600', 'caption' => 'اولویت پایین'],
        'C' => ['strip' => 'bg-gradient-to-l from-amber-400/80 to-amber-200/30', 'dot' => 'bg-gradient-to-br from-amber-400 to-amber-600', 'glass' => 'bg-amber-50/30', 'selected' => 'peer-checked:border-amber-400/70 peer-checked:bg-amber-50/70 peer-checked:ring-amber-300/40 peer-checked:shadow-amber-200/35', 'pill' => 'bg-amber-600', 'caption' => 'اولویت متوسط'],
        'B' => ['strip' => 'bg-gradient-to-l from-orange-500/80 to-orange-300/30', 'dot' => 'bg-gradient-to-br from-orange-400 to-orange-600', 'glass' => 'bg-orange-50/30', 'selected' => 'peer-checked:border-orange-400/70 peer-checked:bg-orange-50/70 peer-checked:ring-orange-300/40 peer-checked:shadow-orange-200/35', 'pill' => 'bg-orange-600', 'caption' => 'اولویت بالا'],
        'A' => ['strip' => 'bg-gradient-to-l from-rose-500/80 to-rose-300/30', 'dot' => 'bg-gradient-to-br from-rose-500 to-rose-700', 'glass' => 'bg-rose-50/30', 'selected' => 'peer-checked:border-rose-400/70 peer-checked:bg-rose-50/70 peer-checked:ring-rose-300/40 peer-checked:shadow-rose-200/35', 'pill' => 'bg-rose-700', 'caption' => 'وضعیت بحرانی و فوری'],
    ];
@endphp

<div class="space-y-4"
     x-data="{
         needSheetOpen: false,
         focusSearch() {
             this.$nextTick(() => { const input = this.$refs.personSearch; if (input) input.focus(); });
         },
         init() {
             // اپراتور دسکتاپ ثبت گروهی را بلافاصله با تایپ شروع می‌کند؛ در موبایل فوکوس خودکار کیبورد را باز می‌کند و مزاحم است.
             @if(! $person)
                 if (window.innerWidth >= 1024) {
                     this.focusSearch();
                 }
             @endif
         },
     }"
     x-on:open-need-level-sheet.window="needSheetOpen = true"
     x-on:close-need-level-sheet.window="needSheetOpen = false"
     x-on:focus-person-search.window="focusSearch()"
     x-on:keydown.escape.window="needSheetOpen = false">
    {{-- ═══ انتخاب مددجو ═══ --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $editorTitle !== '' ? $editorTitle : 'ویرایش فیلد' }}</h1>
            @if($editorTitle !== '')
                <button type="button" wire:click="backToFields"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">
                    <i class="bi bi-grid"></i>
                    فهرست فیلدها
                </button>
            @endif
        </div>
        <p class="text-sm text-gray-500 mb-5">
            @if($person)
                مددجوی انتخابی را بررسی و سطح نیاز او را ثبت کنید.
            @else
                برای ویرایش «سطح نیازمندی»، مددجو را با کد ملی، کد مددجویی یا نام و نام خانوادگی پیدا و انتخاب کنید.
            @endif
        </p>

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

                $personPhoto = $person->profile_photo
                    ? asset($person->profile_photo)
                    : asset('images/no-image-profile.png?v=2');

                // مقدار ذخیره‌شدۀ «سطح نیاز» تا اپراتور بداند دارد چه چیزی را عوض می‌کند.
                $currentLevel = $person->needsLevel?->levelType;
                $currentLevelTone = $currentLevel ? ($levelTones[$currentLevel->code] ?? null) : null;
                $levelChipClass = $currentLevelTone
                    ? $currentLevelTone['pill'].' text-white'
                    : 'border border-gray-200 bg-white text-gray-500';
            @endphp
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-3.5 py-3 sm:px-5 sm:py-4">
                <div class="flex items-start gap-3">
                    <img src="{{ $personPhoto }}" alt="تصویر مددجو"
                         class="h-11 w-11 shrink-0 rounded-full bg-indigo-100 object-cover shadow-sm ring-2 ring-white"/>
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
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $levelChipClass }}">
                                <i class="bi bi-speedometer2"></i>
                                @if($currentLevel)
                                    سطح نیاز فعلی: {{ $currentLevel->code }}
                                    <span class="opacity-80">({{ $currentLevel->title }})</span>
                                @else
                                    سطح نیاز: ثبت‌نشده
                                @endif
                            </span>
                            @if($insuranceName)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                    <i class="bi bi-shield-check"></i>
                                    بیمه: {{ $insuranceName }}
                                </span>
                            @endif
                            @if($coverageName)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700">
                                    <i class="bi bi-building"></i>
                                    تحت پوشش: {{ $coverageName }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="needSheetOpen = true"
                            class="inline-flex lg:hidden items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition active:scale-[0.98]">
                        <i class="bi bi-speedometer2"></i>
                        سطح نیاز@if($currentLevel) · {{ $currentLevel->code }}@endif
                    </button>
                    <button type="button" wire:click="resetSelection"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                        <i class="bi bi-search"></i>
                        تغییر مددجو
                    </button>
                </div>
            </div>
        @else
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-gray-400">
                    <i class="bi bi-search"></i>
                </span>
                <span wire:loading wire:target="search"
                      class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-indigo-500">
                    <i class="bi bi-arrow-repeat inline-block animate-spin"></i>
                </span>
                <input type="text" x-ref="personSearch" wire:model.live.debounce.300ms="search" autocomplete="off"
                       aria-label="جستجوی مددجو" inputmode="search" enterkeyhint="search"
                       placeholder="کد ملی، کد مددجویی یا نام و نام خانوادگی…"
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pr-11 pl-11 text-sm focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:outline-none transition"/>
            </div>

            @if($searchTooShort)
                <p class="mt-3 text-xs text-gray-500">
                    <i class="bi bi-info-circle ml-1 text-gray-400"></i>
                    برای جستجوی نام دستِ‌کم ۲ نویسه وارد کنید؛ کد ملی یا کد مددجویی را می‌توانید کامل تایپ کنید.
                </p>
            @elseif(trim($search) !== '')
                <div class="mt-3 overflow-hidden rounded-xl border border-gray-100">
                    @forelse($searchResults as $result)
                        <button type="button" wire:click="selectPerson({{ $result->id }})"
                                class="flex w-full items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 py-3 text-right transition last:border-b-0 hover:bg-indigo-50">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                    <i class="bi bi-person text-sm"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold text-gray-800">{{ $result->full_name }}</span>
                                    <span class="block truncate text-xs text-gray-500">کد ملی: {{ $result->national_id ?? '—' }}</span>
                                </span>
                            </span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                {{ $result->person_code ?? '—' }}
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                            <i class="bi bi-question-circle ml-1"></i>
                            مددجویی با این مشخصات یافت نشد. املای نام یا کد را دوباره بررسی کنید.
                        </div>
                    @endforelse

                    @if($searchTruncated)
                        <div class="bg-gray-50 px-4 py-2 text-center text-[11px] text-gray-500">
                            تنها ۱۰ نتیجه نخست نمایش داده می‌شود؛ عبارت دقیق‌تری وارد کنید.
                        </div>
                    @endif
                </div>
            @endif
        @endif

        @if($flashMessage)
            <div class="mt-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                <i class="bi bi-check-circle-fill"></i>
                {{ $flashMessage }}
            </div>
        @endif
    </div>

    {{-- ═══ ویرایش سطح نیاز — دسکتاپ ═══ --}}
    @if($person)
        <div class="hidden lg:block bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800">سطح نیاز</h2>
                <span class="text-xs text-gray-400">طیف از پایین (E) تا بحرانی (A)</span>
            </div>
            <p class="text-sm text-gray-500 mb-5">سطح نیاز «{{ $person->full_name }}» را انتخاب کنید:</p>

            @include('livewire.people.partials.need-level-cards', ['listLayout' => false, 'radioName' => 'need_level_grid'])

            @error('needLevelId')
                <p class="mt-3 text-sm font-semibold text-rose-600"><i class="bi bi-exclamation-circle ml-1"></i>{{ $message }}</p>
            @enderror

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="save" @disabled(! $canSave) wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-500 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-gray-300 disabled:shadow-none">
                    <span wire:loading.remove wire:target="save"><i class="bi bi-save"></i> ذخیره تغییرات</span>
                    <span wire:loading wire:target="save">در حال ذخیره…</span>
                </button>
                <span @class([
                    'text-xs',
                    'text-gray-400' => $canSave,
                    'text-amber-600 font-semibold' => ! $canSave,
                ])>
                    @if($needLevelId === null)
                        برای ذخیره، ابتدا یک سطح را انتخاب کنید.
                    @elseif(! $canSave)
                        مقدار انتخابی با سطح نیاز فعلی یکسان است.
                    @else
                        فقط رکورد «سطح نیاز» همین مددجو به‌روزرسانی می‌شود.
                    @endif
                </span>
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
                 role="dialog" aria-modal="true" aria-label="تعیین سطح نیاز"
                 class="fixed inset-x-0 bottom-0 z-50 flex max-h-[88dvh] flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl"
                 style="display: none;">

                {{-- هدر جمع‌وجور: اطلاعات مددجو در نهایتاً دو خط --}}
                <div class="shrink-0 border-b border-gray-100 bg-white/95 px-4 pb-3 pt-2 backdrop-blur">
                    <div class="mx-auto mb-2 h-1.5 w-12 rounded-full bg-gray-300" aria-hidden="true"></div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <img src="{{ $personPhoto }}" alt="تصویر مددجو"
                                 class="h-11 w-11 shrink-0 rounded-full bg-gray-100 object-cover shadow-sm ring-2 ring-indigo-100"/>
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
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-500">
                                    <span class="font-bold {{ $currentLevel ? 'text-indigo-700' : 'text-gray-500' }}">
                                        سطح نیاز فعلی: {{ $currentLevel?->code ?? 'ثبت‌نشده' }}
                                    </span>
                                    @if($insuranceName || $coverageName)
                                        <span class="mx-1 text-gray-300">·</span>
                                        @if($insuranceName)
                                            <span class="font-semibold text-sky-700">بیمه: {{ $insuranceName }}</span>
                                        @endif
                                        @if($insuranceName && $coverageName)
                                            <span class="mx-1 text-gray-300">·</span>
                                        @endif
                                        @if($coverageName)
                                            <span class="font-semibold text-violet-700">تحت پوشش: {{ $coverageName }}</span>
                                        @endif
                                    @endif
                                </p>
                            </div>
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
                            <i class="bi bi-check-circle-fill"></i>
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
                        <p class="mt-3 text-xs font-semibold text-rose-600"><i class="bi bi-exclamation-circle ml-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- دکمه ثبت چسبان در ته شیت --}}
                <div class="shrink-0 border-t border-gray-100 bg-white/95 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur">
                    @if(! $canSave)
                        <p class="mb-2 text-center text-[11px] font-semibold text-amber-600">
                            @if($needLevelId === null)
                                برای ثبت، ابتدا یک سطح را انتخاب کنید.
                            @else
                                مقدار انتخابی با سطح نیاز فعلی یکسان است.
                            @endif
                        </p>
                    @endif
                    <button type="button" wire:click="save" @disabled(! $canSave) wire:loading.attr="disabled" wire:target="save"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-extrabold text-white shadow-md transition hover:bg-indigo-500 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-gray-300 disabled:shadow-none">
                        <span wire:loading.remove wire:target="save"><i class="bi bi-save"></i> ثبت سطح نیاز</span>
                        <span wire:loading wire:target="save">در حال ذخیره…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
