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

<div class="space-y-4">
    {{-- ═══ انتخاب مددجو ═══ --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">ویرایش فیلد</h1>
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
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-5 py-4">
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
                <button type="button" wire:click="resetSelection"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                    <i class="fa fa-search"></i>
                    تغییر مددجو
                </button>
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

    {{-- ═══ ویرایش سطح نیاز ═══ --}}
    @if($person)
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800">سطح نیاز</h2>
                <span class="text-xs text-gray-400">تنها همین فیلد در این صفحه قابل ویرایش است</span>
            </div>
            <p class="text-sm text-gray-500 mb-5">مقدار «سطح نیاز» برای «{{ $person->full_name }}» را از طیف پایین تا بحرانی انتخاب کنید:</p>

            <div class="bg-white">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach($levels as $level)
                        @php $tone = $levelTones[$level->code] ?? $levelTones['C']; @endphp
                        @php $isSelected = $needLevelId !== null && (int) $needLevelId === $level->id; @endphp
                        <label class="relative block cursor-pointer">
                            {{-- وضعیت انتخاب با CSS خالص (peer-checked) نمایش داده می‌شود تا بازخورد آنی باشد و به round-trip سرور وابسته نباشد --}}
                            <input type="radio" name="need_level_id" value="{{ $level->id }}" class="peer sr-only"
                                   wire:model.live="needLevelId" @checked($isSelected)/>

                            <div class="relative h-full overflow-hidden rounded-2xl border border-white/70 shadow-sm shadow-slate-400/10 ring-1 ring-white/40 backdrop-blur-md transition-all duration-200
                                {{ $tone['glass'] }}
                                hover:-translate-y-1 hover:bg-white/60 hover:shadow-md
                                peer-checked:-translate-y-1 peer-checked:scale-[1.03] peer-checked:shadow-md peer-checked:ring-4 {{ $tone['selected'] }}
                                peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-500">
                                <span class="absolute inset-x-0 top-0 h-1.5 {{ $tone['strip'] }}" aria-hidden="true"></span>

                                <div class="flex flex-col items-center px-3 pb-10 pt-6 text-center">
                                    <span class="mb-2 flex h-11 w-11 items-center justify-center rounded-full text-sm font-black text-white shadow-md ring-2 ring-white/60 {{ $tone['dot'] }}">
                                        {{ $level->code }}
                                    </span>
                                    <span class="text-sm font-extrabold text-gray-800">{{ $level->title }}</span>
                                    <span class="mt-1 text-[11px] font-medium text-gray-500">{{ $tone['caption'] }}</span>
                                </div>
                            </div>

                            {{-- برچسب «انتخاب‌شده» باید خواهرِ مستقیم input باشد تا peer-checked روی آن اعمال شود --}}
                            <span class="pointer-events-none absolute inset-x-0 bottom-2 z-10 mx-auto hidden h-6 w-max items-center justify-center gap-1.5 rounded-full px-3 text-[11px] font-extrabold text-white shadow-md ring-1 ring-white/50 peer-checked:inline-flex {{ $tone['pill'] }}">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                انتخاب‌شده
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

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
    @endif
</div>
