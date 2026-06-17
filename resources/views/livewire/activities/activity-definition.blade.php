<div class="space-y-4" dir="rtl">
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-violet-600 via-fuchsia-600 to-rose-600 px-5 py-4 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold">{{ $editingActivityId ? 'ویرایش فعالیت' : 'تعریف فعالیت جدید' }}</h1>
                    <p class="mt-1 text-xs text-violet-50/90">فعالیت‌ها برای رویدادها، جشن‌ها، اردوها و برنامه‌های گروهی ثبت می‌شوند.</p>
                </div>
                <button type="button" wire:click="backToList" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">بازگشت به فهرست</button>
            </div>
        </div>

        <form wire:submit.prevent="save" class="space-y-5 p-5">
            @if (session('activity-success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('activity-success') }}</div>
            @endif

            @error('status')
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
            @enderror

            @if($detailsLocked)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    این فعالیت در حال برگزاری است؛ فقط مکان، زمان پایان و یادداشت وضعیت قابل ویرایش هستند.
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">کد فعالیت</label>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-700">{{ $this->previewActivityCode }}</div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">وضعیت</label>
                    <select wire:model="status" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">نوع فعالیت</label>
                    <select wire:model="activityType" @disabled($detailsLocked) class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400">
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('activityType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">نام فعالیت</label>
                    <input type="text" wire:model="name" @disabled($detailsLocked) class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">مکان</label>
                    <input type="text" wire:model="location" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                    @error('location') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">زمان شروع</label>
                    <input type="text" wire:model="startsAt" @disabled($detailsLocked) placeholder="1403/01/01 14:30" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm ltr:text-left focus:border-violet-300 focus:ring-4 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400">
                    @error('startsAt') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">زمان پایان</label>
                    <input type="text" wire:model="endsAt" placeholder="1403/01/01 18:00" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                    @error('endsAt') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-500">ظرفیت</label>
                    <input type="number" min="1" wire:model="capacity" @disabled($detailsLocked) class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400">
                    @error('capacity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">توضیحات</label>
                <textarea wire:model="description" rows="4" @disabled($detailsLocked) class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400"></textarea>
                @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-500">یادداشت وضعیت</label>
                <textarea wire:model="statusNotes" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100"></textarea>
                @error('statusNotes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" wire:click="backToList" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">انصراف</button>
                <button type="submit" class="rounded-full bg-violet-600 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700">ذخیره فعالیت</button>
            </div>
        </form>
    </div>
</div>
