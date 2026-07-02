{{-- Shared assignment panel body: current operators + assign-new form. --}}
{{-- Expects $currentOperatorAssignments, $assignableOperators, $operatorSearch, $assignmentNotes in scope. --}}
<div>
    <h3 class="mb-2 text-sm font-black text-slate-900">اپراتورهای تخصیص‌یافته</h3>
    <div class="space-y-2">
        @forelse($currentOperatorAssignments as $assignment)
            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3" wire:key="assignment-{{ $assignment->id }}">
                <div>
                    <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-900">
                        <span>{{ trim(($assignment->operator->first_name ?? '') . ' ' . ($assignment->operator->last_name ?? '')) ?: $assignment->operator->name }}</span>
                        @if($assignment->operator?->access_level === \App\Models\User::ACCESS_LEVEL_ACTIVITY_OPERATOR)
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">اپراتور فعالیت</span>
                        @elseif($assignment->operator?->access_level === \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">اپراتور توزیع</span>
                        @endif
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

<div class="border-t border-slate-200 pt-4">
    <h3 class="mb-2 text-sm font-black text-slate-900">تخصیص اپراتور جدید</h3>
    <div class="grid gap-3">
        <input type="search" wire:model.live.debounce.300ms="operatorSearch" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="جستجوی نام اپراتور فعالیت یا توزیع">
        @error('operatorSearch') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

        <div class="space-y-2">
            @forelse($assignableOperators as $operator)
                <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-2.5" wire:key="assignable-{{ $operator->id }}">
                    <div>
                        <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-900">
                            <span>{{ trim(($operator->first_name ?? '') . ' ' . ($operator->last_name ?? '')) ?: $operator->name }}</span>
                            @if($operator->access_level === \App\Models\User::ACCESS_LEVEL_ACTIVITY_OPERATOR)
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">اپراتور فعالیت</span>
                            @elseif($operator->access_level === \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">اپراتور توزیع</span>
                            @endif
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
                            همه اپراتورهای فعالیت و توزیع یا تخصیص‌یافته‌اند یا هیچ اپراتوری تعریف نشده است.
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
