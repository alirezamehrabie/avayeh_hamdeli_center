@php
    /**
     * Social worker popover with registration/delivery metadata for one delivery.
     *
     * Expects: $socialWorkerName, $deliveredDate, $createdDate
     * Optional: $updatedDate, $operatorName, $updaterName
     */
    $updatedDate = $updatedDate ?? null;
    $operatorName = $operatorName ?? null;
    $updaterName = $updaterName ?? null;
@endphp

<div
    x-data="{
        open: false,
        timer: null,
        style: '',
        position() {
            const r = $refs.trigger.getBoundingClientRect();
            const width = 256;
            let left = r.left + r.width / 2 - width / 2;
            left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
            let top = r.bottom + 8;
            this.style = `position:fixed; top:${top}px; left:${left}px; width:${width}px;`;
        },
        show() { clearTimeout(this.timer); this.position(); this.open = true; },
        hide() { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = false, 200); },
    }"
    @keydown.escape.window="open = false"
    class="relative inline-block max-w-[180px] align-middle"
>
    <button
        type="button"
        x-ref="trigger"
        @mouseenter="show()"
        @mouseleave="hide()"
        @focus="show()"
        @blur="hide()"
        @click="open ? open = false : show()"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="dialog"
        aria-label="جزئیات ثبت مددکار: {{ $socialWorkerName }}"
        class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200"
    >
        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-black text-indigo-700">
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </span>
        <span class="truncate">{{ $socialWorkerName }}</span>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            @mouseenter="show()"
            @mouseleave="hide()"
            @click.outside="open = false"
            :style="style"
            role="dialog"
            aria-label="جزئیات ثبت و تحویل"
            class="z-50 rounded-2xl border border-slate-200 bg-white p-3 text-right shadow-2xl"
            style="display: none;"
        >
            <div class="flex items-center gap-2 border-b border-dashed border-slate-200 pb-2">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] text-slate-400">مددکار</p>
                    <p class="truncate text-sm font-black text-slate-900">{{ $socialWorkerName }}</p>
                </div>
            </div>

            <dl class="mt-2 space-y-1.5 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-slate-400">تاریخ تحویل</dt>
                    <dd class="font-bold text-slate-700">{{ $deliveredDate }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-slate-400">تاریخ ثبت</dt>
                    <dd class="font-bold text-slate-700">{{ $createdDate }}</dd>
                </div>
                @if($updatedDate)
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">آخرین ویرایش</dt>
                        <dd class="font-bold text-slate-700">{{ $updatedDate }}</dd>
                    </div>
                @endif
                @if($operatorName)
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">ثبت‌کننده</dt>
                        <dd class="truncate font-bold text-slate-700">{{ $operatorName }}</dd>
                    </div>
                @endif
                @if($updaterName && $updaterName !== $operatorName)
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">ویرایش‌کننده</dt>
                        <dd class="truncate font-bold text-slate-700">{{ $updaterName }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </template>
</div>
