<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900">تخصیص اپراتور فعالیت</h1>
        <p class="mt-1 text-sm text-slate-600">فعالیت مورد نظر را انتخاب کنید و اپراتورهای مسئول ثبت حضور و غیاب آن را مشخص کنید.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

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

                    <!-- Currently assigned operators -->
                    <div>
                        <h3 class="mb-2 text-sm font-black text-slate-900">اپراتورهای تخصیص‌یافته</h3>
                        <div class="space-y-2">
                            @forelse($currentOperatorAssignments as $assignment)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3" wire:key="assignment-{{ $assignment->id }}">
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">
                                            {{ trim(($assignment->operator->first_name ?? '') . ' ' . ($assignment->operator->last_name ?? '')) ?: $assignment->operator->name }}
                                        </p>
                                        <p class="text-xs text-slate-600">
                                            تخصیص توسط
                                            {{ trim(($assignment->assignedBy->first_name ?? '') . ' ' . ($assignment->assignedBy->last_name ?? '')) ?: $assignment->assignedBy?->name }}
                                            در {{ $assignment->assigned_at?->format('Y/m/d H:i') }}
                                        </p>
                                        @if($assignment->notes)
                                            <p class="mt-1 text-xs text-slate-500">یادداشت: {{ $assignment->notes }}</p>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeAssignment({{ $assignment->id }})"
                                        wire:confirm="آیا از حذف این تخصیص اطمینان دارید؟"
                                        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                    >
                                        حذف
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                    <p class="text-sm text-slate-600">هنوز اپراتوری به این فعالیت تخصیص داده نشده است.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Assign new operator -->
                    <div class="border-t border-slate-200 pt-4">
                        <h3 class="mb-2 text-sm font-black text-slate-900">تخصیص اپراتور جدید</h3>
                        <div class="grid gap-3">
                            <input type="search" wire:model.live.debounce.300ms="operatorSearch" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="جستجوی نام اپراتور فعالیت">
                            @error('operatorSearch') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                            <div class="space-y-2">
                                @forelse($assignableOperators as $operator)
                                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-2.5" wire:key="assignable-{{ $operator->id }}">
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">
                                                {{ trim(($operator->first_name ?? '') . ' ' . ($operator->last_name ?? '')) ?: $operator->name }}
                                            </p>
                                            <p class="text-xs text-slate-600">{{ $operator->name }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="assignOperator({{ $operator->id }})"
                                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700"
                                        >
                                            تخصیص
                                        </button>
                                    </div>
                                @empty
                                    <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                        <p class="text-sm text-slate-600">
                                            @if(trim($operatorSearch) !== '')
                                                اپراتوری با این مشخصات یافت نشد.
                                            @else
                                                همه اپراتورهای فعالیت یا تخصیص‌یافته‌اند یا هیچ اپراتور فعالیتی تعریف نشده است.
                                            @endif
                                        </p>
                                    </div>
                                @endforelse
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">یادداشت (اختیاری، برای تخصیص بعدی)</label>
                                <textarea wire:model="assignmentNotes" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="مثلاً: مسئول شیفت صبح"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                    <p class="text-sm text-slate-600">یک فعالیت را از فهرست سمت راست انتخاب کنید تا اپراتورهای آن را مدیریت کنید.</p>
                </div>
            @endif
        </div>
    </div>
</div>
