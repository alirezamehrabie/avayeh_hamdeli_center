@props([
    'details' => [],
    'buttonLabel' => 'جزئیات',
    'popoverTitle' => 'جزئیات رکورد',
])

@php
    $entries = collect($details)
        ->filter(fn ($value): bool => trim((string) $value) !== '')
        ->all();
@endphp

@if(count($entries) > 0)
    <div
        class="relative inline-flex"
        x-data="{
            open: false,
            styles: '',
            openTimer: null,
            closeTimer: null,
            show() {
                clearTimeout(this.openTimer);
                clearTimeout(this.closeTimer);

                if (this.open) {
                    return;
                }

                this.place();
                this.open = true;
            },
            hide() {
                clearTimeout(this.openTimer);
                clearTimeout(this.closeTimer);
                this.open = false;
            },
            scheduleShow() {
                clearTimeout(this.openTimer);
                clearTimeout(this.closeTimer);
                this.openTimer = setTimeout(() => this.show(), 120);
            },
            scheduleHide() {
                clearTimeout(this.openTimer);
                clearTimeout(this.closeTimer);
                this.closeTimer = setTimeout(() => this.hide(), 150);
            },
            toggle() {
                this.open ? this.hide() : this.show();
            },
            place() {
                const trigger = this.$refs.trigger;
                const panel = this.$refs.panel;

                if (!trigger || !panel) {
                    return;
                }

                const padding = 12;
                const gap = 8;
                const triggerRect = trigger.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                let top = triggerRect.bottom + gap;

                if (top + panelRect.height > window.innerHeight - padding) {
                    const above = triggerRect.top - gap - panelRect.height;
                    top = above > padding ? above : Math.max(padding, window.innerHeight - padding - panelRect.height);
                }

                const maxLeft = Math.max(padding, window.innerWidth - padding - panelRect.width);
                const left = Math.max(padding, Math.min(triggerRect.right - panelRect.width, maxLeft));

                this.styles = `top: ${Math.round(top)}px; left: ${Math.round(left)}px;`;
            },
        }"
        x-on:mouseenter="scheduleShow()"
        x-on:mouseleave="scheduleHide()"
        x-on:click.outside="hide()"
        x-on:keydown.escape.window="hide()"
        x-on:scroll.window.capture="if (open) place()"
        x-on:resize.window.debounce.100ms="if (open) place()"
    >
        <button
            type="button"
            x-ref="trigger"
            x-on:click.stop="toggle()"
            x-on:focus="show()"
            x-bind:aria-expanded="open.toString()"
            aria-haspopup="dialog"
            title="نمایش جزئیات با هاور یا لمس"
            class="group inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-500 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100"
        >
            <i class="bi bi-info-circle text-[13px]"></i>
            <span>{{ $buttonLabel }}</span>
            <span class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-slate-100 px-1 text-[9px] font-black text-slate-500 transition group-hover:bg-indigo-100 group-hover:text-indigo-600">{{ count($entries) }}</span>
        </button>

        <div
            x-ref="panel"
            x-bind:class="open ? 'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 translate-y-1'"
            x-bind:style="styles"
            x-bind:inert="!open"
            x-bind:aria-hidden="(!open).toString()"
            role="dialog"
            aria-label="{{ $popoverTitle }}"
            class="fixed z-50 w-80 max-w-[calc(100vw-1.5rem)] rounded-2xl border border-slate-200 bg-white text-right shadow-xl shadow-slate-900/15 transition duration-150 ease-out"
        >
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3 py-2">
                <p class="text-[11px] font-black text-slate-600">{{ $popoverTitle }}</p>
                <button
                    type="button"
                    x-on:click="hide()"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none"
                    aria-label="بستن جزئیات"
                >
                    <i class="bi bi-x-lg text-[10px]"></i>
                </button>
            </div>

            <div class="max-h-72 overflow-y-auto p-1.5">
                @foreach($entries as $label => $value)
                    <div class="rounded-lg px-2 py-1.5 text-[11px] leading-5 even:bg-slate-50/70">
                        <span class="font-bold text-slate-400">{{ $label }}:</span>
                        <span class="font-bold text-slate-700">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
