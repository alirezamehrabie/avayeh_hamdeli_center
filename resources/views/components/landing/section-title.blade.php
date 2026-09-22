@props([
    'heading' => '',
    'id' => null,
])

<div dir="rtl" {{ $attributes->class(['flex w-full items-center gap-3 sm:gap-4', $id ? '' : 'text-center']) }}>
    <span class="h-px min-w-0 flex-1 bg-slate-200" aria-hidden="true"></span>
    @if ($id)
        <h2 id="{{ $id }}" class="shrink-0 rounded-full bg-slate-100 px-4 py-1 text-xs font-bold leading-5 text-slate-600 sm:px-5 sm:text-[13px] sm:leading-6">
            {{ $heading }}
        </h2>
    @else
        <span class="shrink-0 rounded-full bg-slate-100 px-4 py-1 text-xs font-bold leading-5 text-slate-600 sm:px-5 sm:text-[13px] sm:leading-6">
            {{ $heading }}
        </span>
    @endif
    <span class="h-px min-w-0 flex-1 bg-slate-200" aria-hidden="true"></span>
</div>
