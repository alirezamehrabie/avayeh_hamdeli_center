@props([
    'category' => null,
    'path' => null,
    'url' => null,
    'name' => null,
    'sizeClass' => 'h-10 w-10',
    'roundedClass' => 'rounded-lg',
])

@php
    $imageUrl = $url
        ?: ($category?->image_url)
        ?: \App\Models\ServiceCategory::thumbnailUrl($path);

    $altText = $name ?: ($category?->name ?: 'تصویر دسته‌بندی');

    $frameClass = $attributes->class([
        'inline-block shrink-0 overflow-hidden bg-slate-100 ring-1 ring-slate-200',
        $roundedClass,
        $sizeClass,
    ]);
@endphp

@if ($imageUrl)
    <span {{ $frameClass }} x-data="{ broken: false }">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $altText }}"
            loading="lazy"
            x-show="!broken"
            x-on:error="broken = true"
            class="h-full w-full object-cover"
        >
        <span x-cloak x-show="broken" class="flex h-full w-full items-center justify-center text-slate-400" aria-hidden="true">
            <svg class="h-1/2 w-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7a2 2 0 0 1 2-2h3l1.2 1.5H18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                <circle cx="9" cy="10" r="1.2" fill="currentColor" />
                <path d="m7 17 3.2-3.2a1 1 0 0 1 1.4 0L14 16l1.2-1.2a1 1 0 0 1 1.4 0L18 16" />
            </svg>
        </span>
    </span>
@else
    <span {{ $frameClass }} aria-hidden="true">
        <span class="flex h-full w-full items-center justify-center bg-white text-slate-400">
            <svg class="h-1/2 w-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7a2 2 0 0 1 2-2h3l1.2 1.5H18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                <circle cx="9" cy="10" r="1.2" fill="currentColor" />
                <path d="m7 17 3.2-3.2a1 1 0 0 1 1.4 0L14 16l1.2-1.2a1 1 0 0 1 1.4 0L18 16" />
            </svg>
        </span>
    </span>
@endif
