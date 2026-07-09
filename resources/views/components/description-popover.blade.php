@props([
    'text' => null,
    'emptyLabel' => '-',
    'buttonLabel' => 'نمایش توضیحات',
    'popoverTitle' => 'توضیحات',
])

@php
    $description = trim((string) $text);
    $hasDescription = $description !== '';
@endphp

@if($hasDescription)
    <div
        x-data="{
            open: false,
            placement: 'bottom',
            panelStyles: '',
            toggle() {
                if (this.open) {
                    this.open = false;

                    return;
                }

                this.open = true;
                this.$nextTick(() => this.positionPopover());
            },
            positionPopover() {
                const trigger = this.$refs.trigger;
                const panel = this.$refs.panel;

                if (! trigger || ! panel) {
                    return;
                }

                const gap = 8;
                const viewportPadding = 16;
                const triggerRect = trigger.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                const spaceBelow = window.innerHeight - triggerRect.bottom - viewportPadding;
                const spaceAbove = triggerRect.top - viewportPadding;
                const shouldOpenTop = spaceBelow < Math.min(panelRect.height, 240) && spaceAbove > spaceBelow;
                const availableHeight = Math.max(120, shouldOpenTop ? spaceAbove - gap : spaceBelow - gap);

                this.placement = shouldOpenTop ? 'top' : 'bottom';
                this.panelStyles = `max-height: ${Math.floor(availableHeight)}px;`;
            },
        }"
        class="relative inline-flex"
    >
        <button
            type="button"
            x-ref="trigger"
            x-on:click.stop="toggle()"
            x-on:keydown.escape.stop="open = false"
            x-bind:aria-expanded="open.toString()"
            aria-haspopup="dialog"
            aria-label="{{ $buttonLabel }}"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 7h.01" />
                <path d="M10.75 10.25h1.25V17h1.25" />
                <path d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10Z" />
            </svg>
        </button>

        <div
            x-show="open"
            x-ref="panel"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window.stop="open = false"
            x-on:resize.window.debounce.100ms="if (open) positionPopover()"
            x-bind:style="panelStyles"
            style="display: none;"
            x-bind:class="placement === 'top' ? 'bottom-full mb-2 origin-bottom-right' : 'top-full mt-2 origin-top-right'"
            class="absolute right-0 z-30 w-72 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 text-right shadow-xl shadow-slate-900/10"
            role="dialog"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold text-slate-500">{{ $popoverTitle }}</p>
                <button
                    type="button"
                    x-on:click="open = false"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    aria-label="بستن توضیحات"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <div class="mt-2 overflow-y-auto pr-1 text-xs leading-6 text-slate-600 whitespace-pre-line" x-bind:style="panelStyles">
                {{ $description }}
            </div>
        </div>
    </div>
@else
    <span class="text-sm text-slate-300">{{ $emptyLabel }}</span>
@endif
