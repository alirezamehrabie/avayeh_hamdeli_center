@php
    /**
     * Shared trigger that opens the service categories modal.
     *
     * Expects: $service, $unitOptions
     * Optional: $variant ('chip' | 'chip-white' | 'header'), $label
     */
    $variant = $variant ?? 'chip';
    $label = $label ?? $service->categories->count().' مورد';

    $classes = match ($variant) {
        'header' => 'inline-flex items-center gap-1 rounded-full border border-white/20 bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-white transition hover:bg-white/20',
        'chip-white' => 'mt-1.5 inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700',
        default => 'inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700',
    };
@endphp

<button
    type="button"
    @click.stop="categoryTitle = @js($service->serviceName?->name ?: 'خدمت'); categories = @js($service->categories->map(fn ($category) => [
        'name' => $category->name,
        'quantity' => $this->formatQuantityForUnit($category->quantity, (string) $category->unit),
        'unit' => $unitOptions[$category->unit] ?? ($category->unit ?? '-'),
        'value' => (int) $category->value ? number_format((int) $category->value) : null,
    ])->values()); categoriesOpen = true"
    class="{{ $classes }}"
>
    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h10M4 17h7"/>
    </svg>
    <span>{{ $label }}</span>
</button>
