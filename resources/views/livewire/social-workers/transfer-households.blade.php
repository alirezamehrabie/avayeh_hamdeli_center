<div>
    @if($open)
        @php
            $isDeactivate = $intent === 'deactivate';
            $isSelective = ! $isDeactivate && $mode === 'selective';
            $selectedCount = count($selectedGuardianIds);
            $confirmDisabled = ! $socialWorkerId || ($isSelective && $selectedCount === 0);
            $accent = $isDeactivate ? 'rose' : 'cyan';
        @endphp

        <div
            class="fixed inset-0 z-[90] flex items-end justify-center bg-slate-900/50 px-0 py-0 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6"
            dir="rtl"
            wire:key="transfer-households-modal-{{ $sourceWorkerId }}"
        >
            <div class="absolute inset-0" wire:click="closeModal" aria-hidden="true"></div>

            <div
                x-data="{ guardianFilter: '' }"
                class="relative flex max-h-[92svh] w-full flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[88vh] sm:max-w-lg sm:rounded-3xl"
                role="dialog"
                aria-modal="true"
                aria-label="انتقال خانوارهای مددکار"
            >
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-extrabold text-slate-900">
                            {{ $isDeactivate ? 'انتقال خانوارها پیش از غیرفعال‌سازی' : 'انتقال خانوارهای مددکار' }}
                        </h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            @if($isDeactivate)
                                برای غیرفعال‌سازی این مددکار، ابتدا خانوارهای او را به مددکار دیگری بسپارید.
                            @else
                                خانوارهای این مددکار را به‌صورت کامل یا انتخابی به مددکار دیگری منتقل کنید.
                            @endif
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        aria-label="بستن"
                    >
                        <span class="text-xl leading-none">&times;</span>
                    </button>
                </div>

                {{-- Body --}}
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    {{-- Source worker --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-bold text-slate-400">مددکار مبدأ</p>
                        <div class="mt-1 flex items-center justify-between gap-3">
                            <p class="min-w-0 truncate text-sm font-extrabold text-slate-900">{{ $sourceWorkerName }}</p>
                            <span class="shrink-0 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-600" dir="ltr">کد {{ $sourceWorkerCode }}</span>
                        </div>
                        <p class="mt-1 text-[11px] font-bold text-slate-500">{{ $sourceHouseholdCount }} خانوار تحت پوشش</p>
                    </div>

                    {{-- Target worker picker --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-slate-700">مددکار مقصد</label>

                        @if($socialWorkerId)
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-cyan-200 bg-cyan-50/70 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-slate-900">{{ $selectedSocialWorkerDisplay }}</p>
                                    <p class="mt-0.5 text-[11px] font-bold text-cyan-700" dir="ltr">کد {{ $selectedSocialWorkerCode }}</p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearSocialWorkerSelection"
                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                                    aria-label="پاک کردن مددکار مقصد"
                                >
                                    <span class="text-lg leading-none">&times;</span>
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <input
                                    type="search"
                                    wire:model.live.debounce.300ms="socialWorkerQuery"
                                    x-on:focus="$wire.set('showSocialWorkerSuggestions', true)"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-10 py-3 text-sm text-slate-700 transition placeholder:text-xs placeholder:text-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                    placeholder="نام، کد مددکاری یا موبایل مددکار مقصد"
                                    autocomplete="off"
                                >
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                    <i class="bi bi-search text-sm"></i>
                                </span>

                                @if($socialWorkerSuggestions->isNotEmpty())
                                    <div class="mt-2 max-h-64 space-y-1.5 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
                                        @foreach($socialWorkerSuggestions as $worker)
                                            <button
                                                type="button"
                                                wire:key="target-worker-{{ $worker['id'] }}"
                                                wire:click="selectSocialWorker({{ (int) $worker['id'] }})"
                                                class="flex w-full items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-right transition hover:border-cyan-200 hover:bg-cyan-50/60 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                            >
                                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-cyan-600">
                                                    <i class="bi bi-person-badge text-sm"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="flex items-center gap-2">
                                                        <span class="min-w-0 flex-1 truncate text-sm font-black text-slate-900">{{ $worker['name'] }}</span>
                                                        <span class="shrink-0 text-[11px] font-bold text-slate-400">کد {{ $worker['code'] }}</span>
                                                    </span>
                                                    <span class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] font-bold text-slate-500">
                                                        <span class="truncate text-cyan-700">{{ $worker['district'] }}</span>
                                                        <span class="text-slate-300">•</span>
                                                        <span dir="ltr">{{ $worker['mobile'] }}</span>
                                                    </span>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif(mb_strlen(trim($socialWorkerQuery)) >= 2)
                                    <div class="mt-2 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-5 text-center text-xs font-bold text-slate-500">
                                        مددکاری با این جستجو پیدا نشد.
                                    </div>
                                @else
                                    <div class="mt-2 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-5 text-center text-xs font-bold text-slate-500">
                                        برای جستجو حداقل دو کاراکتر وارد کنید.
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Mode toggle (transfer only) --}}
                    @unless($isDeactivate)
                        <div>
                            <span class="mb-1.5 block text-sm font-bold text-slate-700">دامنهٔ انتقال</span>
                            <div class="grid grid-cols-2 gap-1 rounded-2xl border border-slate-200 bg-slate-50 p-1">
                                <button
                                    type="button"
                                    wire:click="setMode('all')"
                                    class="rounded-xl px-3 py-2 text-xs font-extrabold transition {{ $mode === 'all' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                                >
                                    همهٔ خانوارها ({{ $sourceHouseholdCount }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="setMode('selective')"
                                    class="rounded-xl px-3 py-2 text-xs font-extrabold transition {{ $mode === 'selective' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                                >
                                    انتخاب خانوارها
                                </button>
                            </div>
                        </div>
                    @endunless

                    {{-- Selective guardian picker --}}
                    @if($isSelective)
                        <div class="rounded-2xl border border-slate-200 bg-white">
                            <div class="flex flex-col gap-2 border-b border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="relative flex-1">
                                    <input
                                        type="search"
                                        x-model="guardianFilter"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-9 py-2.5 text-sm text-slate-700 transition placeholder:text-xs placeholder:text-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                        placeholder="جستجوی سرپرست بر اساس نام، کد ملی یا موبایل"
                                        autocomplete="off"
                                    >
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                        <i class="bi bi-search text-xs"></i>
                                    </span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <button type="button" wire:click="selectAllGuardians" class="rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1.5 text-[11px] font-bold text-cyan-700 transition hover:bg-cyan-100">انتخاب همه</button>
                                    <button type="button" wire:click="clearGuardianSelection" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-slate-50">پاک کردن</button>
                                </div>
                            </div>

                            <div class="max-h-64 space-y-1.5 overflow-y-auto p-2">
                                @forelse($sourceGuardians as $guardian)
                                    <label
                                        wire:key="transfer-guardian-{{ $guardian['id'] }}"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-cyan-200 hover:bg-cyan-50/40"
                                        x-show="guardianFilter === '' || @js(mb_strtolower($guardian['name'].' '.$guardian['national_code'].' '.$guardian['phone'])).includes(guardianFilter.toLowerCase().trim())"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $guardian['id'] }}"
                                            wire:model.live="selectedGuardianIds"
                                            class="h-4 w-4 shrink-0 rounded border-slate-300 text-cyan-600 focus:ring-cyan-200"
                                        >
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center justify-between gap-2">
                                                <span class="min-w-0 truncate text-xs font-extrabold text-slate-800">{{ $guardian['name'] }}</span>
                                                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $guardian['people_count'] }} نفر</span>
                                            </span>
                                            <span class="mt-1 flex flex-wrap gap-x-2 text-[10px] font-bold text-slate-500">
                                                <span dir="ltr">{{ $guardian['national_code'] }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span dir="ltr">{{ $guardian['phone'] }}</span>
                                            </span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-500">سرپرستی برای این مددکار ثبت نشده است.</p>
                                @endforelse
                            </div>

                            <div class="border-t border-slate-100 px-3 py-2 text-center text-[11px] font-bold text-cyan-700">
                                {{ $selectedCount }} از {{ $sourceHouseholdCount }} خانوار انتخاب شد
                            </div>
                        </div>
                    @endif

                    {{-- Summary --}}
                    <div class="rounded-2xl border px-4 py-3 text-xs font-bold {{ $isDeactivate ? 'border-rose-100 bg-rose-50 text-rose-700' : 'border-cyan-100 bg-cyan-50 text-cyan-800' }}">
                        @php
                            $affected = $isSelective ? $selectedCount : $sourceHouseholdCount;
                        @endphp
                        <div class="flex items-center gap-2">
                            <i class="bi {{ $isDeactivate ? 'bi-exclamation-triangle' : 'bi-arrow-left-right' }}"></i>
                            <span>
                                {{ $affected }} خانوار از «{{ $sourceWorkerName }}»
                                @if($socialWorkerId)
                                    به «{{ $selectedSocialWorkerDisplay }}»
                                @else
                                    به مددکار مقصد
                                @endif
                                منتقل می‌شود.
                            </span>
                        </div>
                        @if($isDeactivate)
                            <p class="mt-1.5 leading-5">پس از انتقال، مددکار مبدأ غیرفعال خواهد شد. مددجویان از طریق سرپرست خانوار به مددکار جدید سپرده می‌شوند.</p>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                        انصراف
                    </button>
                    <button
                        type="button"
                        wire:click="confirm"
                        wire:loading.attr="disabled"
                        wire:target="confirm"
                        @disabled($confirmDisabled)
                        class="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-2.5 text-sm font-extrabold text-white transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-50 {{ $isDeactivate ? 'bg-rose-600 hover:bg-rose-500 focus:ring-rose-100' : 'bg-cyan-600 hover:bg-cyan-500 focus:ring-cyan-100' }}"
                    >
                        <span wire:loading.remove wire:target="confirm">
                            {{ $isDeactivate ? 'انتقال و غیرفعال‌سازی' : 'انتقال خانوارها' }}
                        </span>
                        <span wire:loading wire:target="confirm">در حال انجام...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
