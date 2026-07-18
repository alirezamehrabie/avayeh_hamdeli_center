@php
    /**
     * Edit/delete actions for a single delivery record.
     *
     * Expects: $delivery
     * Optional: $size ('sm' | 'md') — md gives larger, touch-friendly targets
     */
    $size = $size ?? 'sm';
    $buttonPadding = $size === 'md' ? 'p-2.5' : 'p-1.5';
    $iconSize = $size === 'md' ? 'h-5 w-5' : 'h-4 w-4';
@endphp

<div class="inline-flex items-center justify-center gap-1.5">
    {{-- Edit --}}
    <button
        type="button"
        wire:click="editDelivery({{ $delivery->id }})"
        class="rounded-lg border border-sky-200 bg-sky-50 {{ $buttonPadding }} text-sky-600 transition hover:bg-sky-100 hover:text-sky-700"
        title="ویرایش"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </button>
    {{-- Delete --}}
    <button
        type="button"
        wire:click="deleteDelivery({{ $delivery->id }})"
        onclick="return confirm('آیا از حذف این رکورد اطمینان دارید؟')"
        class="rounded-lg border border-rose-200 bg-rose-50 {{ $buttonPadding }} text-rose-600 transition hover:bg-rose-100 hover:text-rose-700"
        title="حذف"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    </button>
</div>
