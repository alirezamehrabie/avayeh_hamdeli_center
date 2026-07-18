<div class="space-y-6">
    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">مدیریت اعلان‌ها</h1>
                <p class="mt-1 text-sm text-gray-500">
                    مشخص کنید برای کدام رویدادهای سیستمی اعلان دریافت کنید و اعلان‌ها به کدام نقش‌ها یا کاربران محدود شوند.
                </p>
            </div>

            <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'notifications-center' })"
                    class="inline-flex items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                مرکز اعلان‌ها
            </button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-4">
        @php
            $groupedEvents = collect($events)->groupBy('group', preserveKeys: true);
        @endphp

        @foreach($groupedEvents as $groupLabel => $groupEvents)
            <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50/60 px-5 py-3">
                    <h2 class="text-sm font-bold text-gray-700">{{ $groupLabel }}</h2>
                </div>

                <div class="divide-y divide-gray-50">
                    @foreach($groupEvents as $eventKey => $event)
                        @php $formKey = \App\Livewire\Admin\Notifications\NotificationSettings::formKey($eventKey); @endphp
                        <div class="px-5 py-4" wire:key="event-{{ $formKey }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-gray-800">{{ $event['label'] }}</h3>
                                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-400" dir="ltr">{{ $eventKey }}</code>
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-gray-500">{{ $event['description'] }}</p>
                                </div>

                                {{-- Enable toggle --}}
                                <label class="relative inline-flex cursor-pointer items-center gap-2">
                                    <span class="text-xs font-semibold {{ ($preferences[$formKey]['enabled'] ?? false) ? 'text-emerald-600' : 'text-gray-400' }}">
                                        {{ ($preferences[$formKey]['enabled'] ?? false) ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                    {{-- ورودی روی خود کنترل قرار می‌گیرد (نه sr-only) تا فوکوس مجدد Livewire باعث اسکرول ناخواسته صفحه نشود. --}}
                                    <input type="checkbox" wire:model.live="preferences.{{ $formKey }}.enabled" class="peer absolute inset-0 h-full w-full cursor-pointer appearance-none rounded-lg focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-100">
                                    <span class="relative h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-emerald-500 after:absolute after:right-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:-translate-x-5"></span>
                                </label>
                            </div>

                            @if(($preferences[$formKey]['enabled'] ?? false) && $event['supports_targeting'])
                                <div class="mt-4 rounded-xl border border-indigo-50 bg-indigo-50/40 p-4">
                                    <fieldset>
                                        <legend class="mb-2 text-xs font-bold text-gray-600">اعلان برای:</legend>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($targetTypeLabels as $targetType => $targetLabel)
                                                <label class="relative inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ ($preferences[$formKey]['target_type'] ?? 'all') === $targetType ? 'border-indigo-300 bg-indigo-100 text-indigo-700' : 'border-gray-200 bg-white text-gray-500 hover:border-indigo-200' }}">
                                                    <input type="radio" wire:model.live="preferences.{{ $formKey }}.target_type" value="{{ $targetType }}" class="absolute inset-0 h-full w-full cursor-pointer appearance-none rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">
                                                    {{ $targetLabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    @if(($preferences[$formKey]['target_type'] ?? 'all') === 'roles')
                                        <fieldset class="mt-3">
                                            <legend class="mb-2 text-xs font-semibold text-gray-600">نقش‌های موردنظر:</legend>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($event['targetable_roles'] as $role)
                                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-indigo-200 has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                                                        <input type="checkbox" wire:model.live="preferences.{{ $formKey }}.target_roles" value="{{ $role }}" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-300">
                                                        {{ $roleLabels[$role] ?? $role }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    @endif

                                    @if(($preferences[$formKey]['target_type'] ?? 'all') === 'users')
                                        <fieldset class="mt-3">
                                            <legend class="mb-2 text-xs font-semibold text-gray-600">کاربران موردنظر:</legend>
                                            <div class="max-h-56 space-y-3 overflow-y-auto rounded-lg border border-gray-100 bg-white p-3">
                                                @php $hasSelectableUsers = false; @endphp
                                                @foreach($event['targetable_roles'] as $role)
                                                    @continue(! $selectableUsers->has($role) || $selectableUsers[$role]->isEmpty())
                                                    @php $hasSelectableUsers = true; @endphp
                                                    <div>
                                                        <p class="mb-1.5 text-[10px] font-bold text-gray-400">{{ $roleLabels[$role] ?? $role }}</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach($selectableUsers[$role] as $selectableUser)
                                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-indigo-200 has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                                                                    <input type="checkbox" wire:model.live="preferences.{{ $formKey }}.target_user_ids" value="{{ $selectableUser->id }}" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-300">
                                                                    {{ $selectableUser->full_name ?: $selectableUser->name }}
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @unless($hasSelectableUsers)
                                                    <p class="text-xs text-gray-400">کاربری با نقش‌های قابل انتخاب یافت نشد.</p>
                                                @endunless
                                            </div>
                                        </fieldset>
                                    @endif
                                </div>
                            @endif

                            @error('preferences.'.$formKey)
                                <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <span wire:loading wire:target="save" class="text-xs text-gray-400">در حال ذخیره…</span>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:opacity-60">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                ذخیره تنظیمات
            </button>
        </div>
    </form>
</div>
