@php
    /**
     * پالت رنگ به‌صورت رشته‌های کامل و ثابت نوشته شده است تا Tailwind
     * آن‌ها را در بیلد اسکن کند (کلاس‌های داینامیک مثل bg-{{ $color }}-50 ساخته نمی‌شوند).
     */
    $palette = match ($color) {
        'indigo' => [
            'card' => 'ring-indigo-100 hover:ring-indigo-200',
            'wash' => 'bg-gradient-to-bl from-indigo-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-indigo-500 via-indigo-400 to-indigo-200',
            'icon' => 'bg-indigo-100/80 text-indigo-600 ring-indigo-200/60',
            'hoverText' => 'group-hover:text-indigo-700',
        ],
        'sky' => [
            'card' => 'ring-sky-100 hover:ring-sky-200',
            'wash' => 'bg-gradient-to-bl from-sky-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-sky-500 via-sky-400 to-sky-200',
            'icon' => 'bg-sky-100/80 text-sky-600 ring-sky-200/60',
            'hoverText' => 'group-hover:text-sky-700',
        ],
        'emerald' => [
            'card' => 'ring-emerald-100 hover:ring-emerald-200',
            'wash' => 'bg-gradient-to-bl from-emerald-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-emerald-500 via-emerald-400 to-emerald-200',
            'icon' => 'bg-emerald-100/80 text-emerald-600 ring-emerald-200/60',
            'hoverText' => 'group-hover:text-emerald-700',
        ],
        'violet' => [
            'card' => 'ring-violet-100 hover:ring-violet-200',
            'wash' => 'bg-gradient-to-bl from-violet-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-violet-500 via-violet-400 to-violet-200',
            'icon' => 'bg-violet-100/80 text-violet-600 ring-violet-200/60',
            'hoverText' => 'group-hover:text-violet-700',
        ],
        'amber' => [
            'card' => 'ring-amber-100 hover:ring-amber-200',
            'wash' => 'bg-gradient-to-bl from-amber-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-amber-500 via-amber-400 to-amber-200',
            'icon' => 'bg-amber-100/80 text-amber-600 ring-amber-200/60',
            'hoverText' => 'group-hover:text-amber-700',
        ],
        'rose' => [
            'card' => 'ring-rose-100 hover:ring-rose-200',
            'wash' => 'bg-gradient-to-bl from-rose-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-rose-500 via-rose-400 to-rose-200',
            'icon' => 'bg-rose-100/80 text-rose-600 ring-rose-200/60',
            'hoverText' => 'group-hover:text-rose-700',
        ],
        default => [
            'card' => 'ring-slate-100 hover:ring-slate-200',
            'wash' => 'bg-gradient-to-bl from-slate-50/80 via-white to-white',
            'bar' => 'bg-gradient-to-b from-slate-500 via-slate-400 to-slate-200',
            'icon' => 'bg-slate-100/80 text-slate-600 ring-slate-200/60',
            'hoverText' => 'group-hover:text-slate-700',
        ],
    };
@endphp

<div class="group relative flex h-full flex-col overflow-hidden rounded-2xl p-3.5 shadow-sm ring-1 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:p-4 {{ $palette['card'] }} {{ $palette['wash'] }}">
    {{-- نوار رنگی عمودی ابتدای کارت (سمت راست در RTL) --}}
    <span class="absolute inset-y-0 right-0 w-1 {{ $palette['bar'] }}" aria-hidden="true"></span>

    {{-- سربرگ: چیپ آیکون + عنوان و توضیح --}}
    <div class="flex items-center gap-2.5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 transition-transform duration-300 group-hover:scale-105 sm:h-10 sm:w-10 {{ $palette['icon'] }}">
            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"></path>
            </svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-xs font-bold text-slate-700 sm:text-sm">{{ $title }}</span>
            @if (!empty($caption))
                <span class="mt-0.5 block truncate text-[10px] font-medium leading-tight text-slate-400 sm:text-[11px]">{{ $caption }}</span>
            @endif
        </span>
    </div>

    {{-- مقدار اصلی --}}
    <p class="mt-3 whitespace-nowrap pt-1 text-[22px] font-bold leading-none tracking-tight text-slate-900 tabular-nums transition-colors duration-300 sm:mt-4 sm:text-3xl {{ $palette['hoverText'] }}">
        {{ is_numeric($value) ? number_format($value) : $value }}
        @if (!empty($suffix))
            <span class="ms-1.5 text-[11px] font-medium tracking-normal text-slate-400 sm:text-xs">{{ $suffix }}</span>
        @endif
    </p>

    {{-- ریزجزئیات (مثل دختر/پسر) به‌صورت چیپ خنثی که روی هر رنگ پس‌زمینه‌ای خوانا می‌ماند --}}
    @if (!empty($badges))
        <div class="mt-2.5 flex flex-wrap gap-1.5">
            @foreach ($badges as $badge)
                @php
                    $dotClass = match ($badge['color'] ?? 'slate') {
                        'rose' => 'bg-rose-400',
                        'sky' => 'bg-sky-400',
                        'emerald' => 'bg-emerald-400',
                        'amber' => 'bg-amber-400',
                        'indigo' => 'bg-indigo-400',
                        'violet' => 'bg-violet-400',
                        default => 'bg-slate-300',
                    };
                    $badgeValue = $badge['value'] ?? '';
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-500 shadow-sm ring-1 ring-slate-200/70 sm:text-[11px]">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
                    <span>{{ $badge['label'] ?? '' }}</span>
                    <span class="font-bold tabular-nums text-slate-800">{{ is_numeric($badgeValue) ? number_format($badgeValue) : $badgeValue }}</span>
                </span>
            @endforeach
        </div>
    @endif
</div>
