@php
    /** @var \App\Models\DashboardReminder $reminder */
    /** @var array<string, string> $reminderCategories */
    $isDone = (bool) $reminder->is_done;
@endphp

<div class="group flex items-center gap-2.5 rounded-2xl border p-3 transition-all duration-200 {{ $isDone ? 'border-slate-200/80 bg-slate-50/70 hover:border-slate-300' : 'border-amber-200/70 bg-amber-50/40 hover:border-amber-300/80' }}">
    {{-- چک‌باکس دایره‌ای: ناحیهٔ لمسی ۴۰px با حلقهٔ فوکوس --}}
    <button
        type="button"
        wire:click="toggleReminder({{ $reminder->id }})"
        aria-label="{{ $isDone ? 'بازکردن یادآوری' : 'علامت‌زدن به‌عنوان انجام‌شده' }}: {{ $reminder->title }}"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full focus:outline-none focus:ring-4 {{ $isDone ? 'focus:ring-emerald-100' : 'focus:ring-amber-100' }}"
    >
        <span class="flex h-5 w-5 items-center justify-center rounded-full border-2 transition-all duration-200 {{ $isDone ? 'border-emerald-500 bg-emerald-500' : 'border-amber-400 bg-white group-hover:border-amber-500' }}">
            @if($isDone)
                <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
            @endif
        </span>
    </button>

    {{-- کل متن هم قابل‌کلیک برای تغییر وضعیت (ناحیهٔ لمسی بزرگ‌تر) --}}
    <button
        type="button"
        wire:click="toggleReminder({{ $reminder->id }})"
        class="min-w-0 flex-1 rounded-lg text-right focus:outline-none"
    >
        <p class="text-sm {{ $isDone ? 'font-medium text-slate-400 line-through' : 'font-semibold text-slate-800' }}">{{ $reminder->title }}</p>
        <p class="mt-1.5">
            <span class="inline-flex items-center rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-slate-200/70">{{ $reminderCategories[$reminder->category] ?? $reminder->category }}</span>
        </p>
    </button>

    <button
        type="button"
        wire:click="deleteReminder({{ $reminder->id }})"
        aria-label="حذف یادآوری: {{ $reminder->title }}"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-300 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-4 focus:ring-rose-100"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
        </svg>
    </button>
</div>
