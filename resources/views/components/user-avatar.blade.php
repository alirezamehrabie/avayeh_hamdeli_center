@props([
    'user' => null,
    'size' => 'h-12 w-12',
])

@php
    $avatarUser = $user ?? auth()->user();
    $photoPath = $avatarUser?->profile_photo_path;
    $hasPhoto = filled($photoPath);
    $displayName = $avatarUser?->name ?? 'کاربر';
@endphp

<div {{ $attributes->merge(['class' => "relative flex {$size} items-center justify-center overflow-hidden rounded-full bg-gray-100"]) }}>
    {{-- آیکون پروفایل --}}
    <span class="flex h-full w-full items-center justify-center text-gray-400">
        <svg class="h-1/2 w-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
        </svg>
    </span>

    @if ($hasPhoto)
        <img
            src="{{ asset($photoPath) }}"
            alt="تصویر پروفایل {{ $displayName }}"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.remove();"
        >
    @endif
</div>
