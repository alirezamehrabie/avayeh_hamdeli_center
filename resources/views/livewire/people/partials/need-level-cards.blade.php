{{--
    $levels, $levelTones, $needLevelId از والدین تزریق می‌شوند.
    $listLayout: true برای شیت موبایل (ردیفی) | false برای گرید دسکتاپ
    $radioName: نام رادیوی متفاوت برای هر چیدمان تا گروه‌های رادیوی DOM با هم تداخل نکنند.
--}}
@php
    $listLayout = $listLayout ?? false;
    $radioName = $radioName ?? 'need_level_id';
    $layoutKey = $listLayout ? 'list' : 'grid';
@endphp

<div class="{{ $listLayout ? 'space-y-2' : 'grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5' }}">
    @foreach($levels as $level)
        @php $tone = $levelTones[$level->code] ?? $levelTones['C']; @endphp
        @php $isSelected = $needLevelId !== null && (int) $needLevelId === $level->id; @endphp
        <label class="relative block cursor-pointer" wire:key="need-level-{{ $layoutKey }}-{{ $level->id }}">
            {{-- وضعیت انتخاب با CSS خالص (peer-checked) نمایش داده می‌شود تا بازخورد آنی باشد و به round-trip سرور وابسته نباشد --}}
            <input type="radio" name="{{ $radioName }}" value="{{ $level->id }}" class="peer sr-only"
                   wire:model.live="needLevelId" @checked($isSelected)/>

            @if($listLayout)
                <div class="relative flex items-center gap-3 overflow-hidden rounded-2xl border border-white/70 py-2.5 pr-4 pl-12 shadow-sm shadow-slate-400/10 ring-1 ring-white/40 backdrop-blur-md transition-all duration-200
                    {{ $tone['glass'] }}
                    hover:bg-white/60 hover:shadow-md
                    peer-checked:shadow-md peer-checked:ring-4 {{ $tone['selected'] }}
                    peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-500">
                    <span class="absolute inset-y-0 right-0 w-1.5 {{ $tone['pill'] }}" aria-hidden="true"></span>

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-black text-white shadow-sm ring-2 ring-white/60 {{ $tone['dot'] }}">
                        {{ $level->code }}
                    </span>
                    <span class="min-w-0 flex-1 text-right">
                        <span class="block truncate text-[13px] font-extrabold text-gray-800">{{ $level->title }}</span>
                        <span class="block truncate text-[10px] font-medium text-gray-500">{{ $tone['caption'] }}</span>
                    </span>
                </div>

                {{-- تیک انتخاب باید خواهرِ مستقیم input باشد تا peer-checked روی آن اعمال شود --}}
                <span class="pointer-events-none absolute left-3 top-1/2 z-10 hidden h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-white shadow-md ring-1 ring-white/50 peer-checked:inline-flex {{ $tone['pill'] }}">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </span>
            @else
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
            @endif
        </label>
    @endforeach
</div>
