@props(['label' => 'در حال بارگذاری'])

{{--
    Dot Spinner سبک برای نمایش وضعیت بارگذاری در گزینه‌های منوی سایدبار.
    رنگ نقطه‌ها از رنگ متن والد (bg-current) ارث‌بری می‌شود تا با تم سایدبار هماهنگ بماند.
--}}
<span
    {{ $attributes->class(['inline-flex shrink-0 items-center gap-1']) }}
    role="status"
    aria-live="polite"
>
    <span class="sr-only">{{ $label }}</span>
    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-current [animation-delay:-0.3s]" aria-hidden="true"></span>
    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-current [animation-delay:-0.15s]" aria-hidden="true"></span>
    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-current" aria-hidden="true"></span>
</span>
