@php
    $isCheckOut = ($mode ?? 'in') === \App\Models\AttendanceSheet::MODE_OUT;
@endphp
<div class="space-y-4 px-2 pb-16 sm:px-4" dir="rtl">
    @if(! $sheet)
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="rounded-t-3xl bg-gradient-to-l from-indigo-600 via-indigo-500 to-violet-500 px-4 py-5 text-white">
                <h1 class="text-xl font-black leading-7">حضور و غیاب</h1>
                <p class="mt-1 text-xs font-semibold text-indigo-50">
                    یک نام برای حضور و غیاب امروز بنویسید و دکمه ساخت را بزنید.
                </p>
            </div>

            <form wire:submit="createSheet" class="space-y-3 p-4">
                <label for="attendance-new-sheet-name" class="block text-sm font-extrabold text-slate-800">
                    نام حضور و غیاب
                </label>
                <input
                    id="attendance-new-sheet-name"
                    type="text"
                    wire:model="newSheetName"
                    placeholder="مثلاً: کلاس قرآن پنجشنبه"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-base font-bold text-slate-800 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                >
                @error('newSheetName')
                    <p class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $message }}</p>
                @enderror
                <p class="text-xs font-semibold text-slate-500">نام تکراری نمی‌شود؛ اگر قبلاً همین نام را ساخته‌اید، آن را از فهرست پایین باز کنید.</p>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-indigo-600 px-4 py-4 text-base font-black text-white shadow-lg shadow-indigo-700/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                >
                    ساخت و شروع اسکن ورود
                </button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-extrabold text-slate-800">حضور و غیاب‌های شما</h2>
            <div class="mt-3 space-y-2">
                @forelse($sheets as $item)
                    <button
                        type="button"
                        wire:click="openSheet({{ $item->id }})"
                        class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-right transition hover:border-indigo-300 hover:bg-indigo-50"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-base font-black text-slate-800">{{ $item->name }}</span>
                            <span class="mt-1 block text-xs font-bold text-slate-500">
                                ورود: {{ $item->checked_in_count }} نفر · خروج: {{ $item->checked_out_count }} نفر
                            </span>
                        </span>
                        <span class="shrink-0 rounded-full bg-indigo-50 px-3 py-1.5 text-[11px] font-black text-indigo-700">
                            باز کردن
                        </span>
                    </button>
                @empty
                    <p class="rounded-2xl bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-500">
                        هنوز حضور و غیابی نساخته‌اید.
                    </p>
                @endforelse
            </div>
        </div>
    @else
        @include('livewire.social-workers.partials.attendance-sheet-panel', [
            'sheet' => $sheet,
            'entries' => $entries,
            'checkedInCount' => $checkedInCount,
            'checkedOutCount' => $checkedOutCount,
            'manualCandidates' => $manualCandidates,
            'selectedPerson' => $selectedPerson,
            'isCheckOut' => $isCheckOut,
        ])
    @endif
</div>
