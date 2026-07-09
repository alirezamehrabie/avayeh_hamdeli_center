@php
    $label = $label ?? null;
    $compact = $compact ?? false;
    $minimal = $minimal ?? false;
@endphp

<button
    type="button"
    @click.stop="openWorkers(@js($this->socialWorkerSummary($service, $unitOptions)))"
    class="{{ $compact
        ? 'inline-flex items-center gap-1.5 rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[11px] '.($minimal ? 'font-medium' : 'font-black').' text-cyan-800 shadow-sm shadow-cyan-950/5 transition hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-white hover:text-cyan-900 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-100 active:translate-y-0'
        : 'group mt-1 inline-flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs '.($minimal ? 'font-medium' : 'font-black').' text-cyan-800 shadow-sm shadow-cyan-950/5 transition hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-white hover:text-cyan-900 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-cyan-100 active:translate-y-0'
    }}"
    title="مشاهده عملکرد مددکاران و گیرندگان خدمت"
    aria-label="مشاهده عملکرد مددکاران و گیرندگان خدمت"
>
    <span class="{{ $compact
        ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-cyan-600 text-[10px] '.($minimal ? 'font-semibold' : 'font-black').' text-white'
        : 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-cyan-600 text-[10px] '.($minimal ? 'font-semibold' : 'font-black').' text-white transition group-hover:scale-110'
    }}">
        {{ $service->uniqueSocialWorkersCount() }}
    </span>
    <span>{{ $label ?? 'نفر' }}</span>
</button>
