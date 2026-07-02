<div class="space-y-6">
    @unless($lockedActivityId)
        <div>
            <h1 class="text-2xl font-black text-slate-900">تخصیص اپراتور فعالیت</h1>
            <p class="mt-1 text-sm text-slate-600">فعالیت مورد نظر را انتخاب کنید و اپراتورهای مسئول ثبت حضور و غیاب آن را مشخص کنید.</p>
        </div>
    @endunless

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($lockedActivityId)
        {{-- Embedded mode: opened from the Activity Management page for a single, pre-selected activity. --}}
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4">
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-black text-slate-900">{{ $selectedActivity?->name ?? 'تخصیص اپراتور فعالیت' }}</h2>
                    @if($selectedActivity)
                        <p class="mt-1 text-xs text-slate-600">
                            {{ $selectedActivity->code }} •
                            {{ $statusOptions[$selectedActivity->status] ?? $selectedActivity->status }} •
                            {{ $selectedActivity->attendances_count }} حضور ثبت‌شده
                        </p>
                    @endif
                </div>
                <button type="button" wire:click="$dispatch('close-activity-operator-assignment-modal')" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                    بستن
                </button>
            </div>

            @if($selectedActivity)
                @include('livewire.admin._activity-operator-assignment-panel')
            @else
                <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                    <p class="text-sm text-slate-600">فعالیت مورد نظر یافت نشد.</p>
                </div>
            @endif
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-5">
            <!-- Activities list -->
            <div class="lg:col-span-2 space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-3">
                        <input type="search" wire:model.live.debounce.300ms="search" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="نام، کد یا محل فعالیت">
                        <select wire:model.live="statusFilter" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            <option value="all">همه وضعیت‌ها</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    @forelse($activities as $activity)
                        <button
                            type="button"
                            wire:click="selectActivity({{ $activity->id }})"
                            wire:key="activity-row-{{ $activity->id }}"
                            class="flex w-full items-center justify-between rounded-2xl border-2 bg-white px-4 py-3 text-right shadow-sm transition {{ $selectedActivity?->id === $activity->id ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}"
                        >
                            <div>
                                <p class="font-bold text-slate-900">{{ $activity->name }}</p>
                                <p class="text-xs text-slate-600">{{ $activity->code }} • {{ $statusOptions[$activity->status] ?? $activity->status }}</p>
                            </div>
                            <div class="text-left">
                                <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700">
                                    {{ $activity->operators_count }} اپراتور
                                </span>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                            <p class="text-sm text-slate-600">فعالیتی یافت نشد.</p>
                        </div>
                    @endforelse
                </div>

                @if($activities->hasPages())
                    <div>{{ $activities->links() }}</div>
                @endif
            </div>

            <!-- Assignment panel -->
            <div class="lg:col-span-3">
                @if($selectedActivity)
                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <h2 class="text-lg font-black text-slate-900">{{ $selectedActivity->name }}</h2>
                                <p class="mt-1 text-xs text-slate-600">
                                    {{ $selectedActivity->code }} •
                                    {{ $statusOptions[$selectedActivity->status] ?? $selectedActivity->status }} •
                                    {{ $selectedActivity->attendances_count }} حضور ثبت‌شده
                                </p>
                            </div>
                            <button type="button" wire:click="clearSelectedActivity" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                بستن
                            </button>
                        </div>

                        @include('livewire.admin._activity-operator-assignment-panel')
                    </div>
                @else
                    <div class="flex h-full items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                        <p class="text-sm text-slate-600">یک فعالیت را از فهرست سمت راست انتخاب کنید تا اپراتورهای آن را مدیریت کنید.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
