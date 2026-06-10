<div class="min-h-screen bg-slate-50/60">
    <div class="container mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_20px_80px_-30px_rgba(15,23,42,0.35)]">
            <div class="border-b border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-5 py-6 text-white sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-300">System Settings</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">مدیریت کاربران</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                            نمای سبک و سریع برای بررسی کاربران، جستجو، فیلتر و انجام تغییرات مدیریتی بدون شلوغی بصری.
                        </p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-slate-300">کل کاربران</div>
                            <div class="mt-1 text-2xl font-black">{{ $userStats['total'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-slate-300">ادمین‌ها</div>
                            <div class="mt-1 text-2xl font-black">{{ $userStats['admins'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-slate-300">کاربران عادی</div>
                            <div class="mt-1 text-2xl font-black">{{ $userStats['regular'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-amber-400/20 bg-amber-300/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-amber-100">حساب محافظت‌شده</div>
                            <div class="mt-1 text-2xl font-black text-amber-100">{{ $userStats['protected'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6 px-4 py-5 sm:px-6 lg:px-8">
                @if (session()->has('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if($isManager)
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/80 p-4 shadow-sm">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-black text-indigo-900">درخواست‌های در انتظار تایید</h2>
                                <p class="mt-1 text-xs text-indigo-700">درخواست‌های مدیریتی که هنوز بررسی نشده‌اند.</p>
                            </div>
                        </div>

                        @if($pendingRequests->isEmpty())
                            <p class="text-sm text-indigo-700">درخواستی در انتظار تایید وجود ندارد.</p>
                        @else
                            <div class="grid gap-3 xl:grid-cols-2">
                                @foreach($pendingRequests as $pending)
                                    <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="space-y-1 text-sm text-slate-700">
                                                <div class="font-bold text-slate-900">
                                                    {{ $pending->action_type === 'delete_user' ? 'حذف کاربر' : ($pending->action_type === 'downgrade_admin' ? 'تنزیل به کاربر عادی' : 'ارتقا به ادمین') }}
                                                </div>
                                                <div class="text-xs text-slate-500">هدف: {{ $pendingUserNames[$pending->target_user_id] ?? ('#'.$pending->target_user_id) }}</div>
                                                <div class="text-xs text-slate-500">درخواست‌دهنده: {{ $pendingUserNames[$pending->requested_by_user_id] ?? ('#'.$pending->requested_by_user_id) }}</div>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" wire:click="approveRequest({{ $pending->id }})" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">تایید</button>
                                                <button type="button" wire:click="rejectRequest({{ $pending->id }})" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">رد</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <form wire:submit.prevent="createUser" class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5 shadow-sm">
                    <div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">ایجاد کاربر جدید</h2>
                            <p class="mt-1 text-sm text-slate-600">فرم سبک برای ساخت حساب جدید و تعیین سطح دسترسی.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">نام</label>
                            <input type="text" wire:model.blur="first_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="مثال: علی">
                            @error('first_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">نام خانوادگی</label>
                            <input type="text" wire:model.blur="last_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="مثال: رضایی">
                            @error('last_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">نام کاربری</label>
                            <input type="text" wire:model.blur="username" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="مثال: admin01">
                            @error('username') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">سطح دسترسی</label>
                            <select wire:model="access_level" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="manager" disabled>مدیریت (محافظت‌شده)</option>
                                <option value="admin" @disabled(!$actorCanCreateAdmin)>ادمین</option>
                                <option value="regular_user">کاربر عادی</option>
                                <option value="distribution_operator">اپراتور توزیع</option>
                            </select>
                            @error('access_level') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div x-data="{ showPassword: false }">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">رمز عبور</label>
                            <div class="relative">
                                <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="password" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pl-10 text-sm focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="حداقل 8 کاراکتر">
                                <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600">
                                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                </button>
                            </div>
                            @error('password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div x-data="{ showPassword: false }">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">تکرار رمز عبور</label>
                            <div class="relative">
                                <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="password_confirmation" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pl-10 text-sm focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="تکرار رمز عبور">
                                <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600">
                                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-2 xl:col-span-4">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">دسترسی‌های قابل تخصیص</label>
                            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-4">
                                @foreach($permissionOptions as $permissionKey => $permissionLabel)
                                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                        <input type="checkbox" value="{{ $permissionKey }}" wire:model="permissions" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $permissionLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('permissions') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            @error('permissions.*') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2 xl:col-span-4">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                ایجاد کاربر
                            </button>
                        </div>
                    </div>
                </form>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">فهرست کاربران</h2>
                            <p class="mt-1 text-sm text-slate-600">جستجو، فیلتر و تغییر نقش‌های سریع. ویرایش‌های جزئی در یک مودال جدا انجام می‌شود.</p>
                        </div>
                        <button type="button" wire:click="clearUserFilters" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            پاک کردن فیلترها
                        </button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">جستجو</label>
                            <input type="search" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="نام، نام کاربری یا ایمیل">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">نقش</label>
                            <select wire:model.live="roleFilter" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="all">همه نقش‌ها</option>
                                <option value="manager">مدیریت</option>
                                <option value="admin">ادمین</option>
                                <option value="regular_user">کاربر عادی</option>
                                <option value="social_worker">مددکار</option>
                                <option value="distribution_operator">اپراتور توزیع</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">وضعیت</label>
                            <select wire:model.live="statusFilter" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="all">همه وضعیت‌ها</option>
                                <option value="admin">ادمین / مدیر</option>
                                <option value="regular">کاربر غیرادمین</option>
                                <option value="protected">حساب محافظت‌شده</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">دسترسی</label>
                            <select wire:model.live="permissionFilter" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="all">همه دسترسی‌ها</option>
                                @foreach($permissionOptions as $permissionKey => $permissionLabel)
                                    <option value="{{ $permissionKey }}">{{ $permissionLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse($users as $user)
                            @php
                                $roleLabel = $user->access_level === 'manager' ? 'مدیریت' : ($user->access_level === 'admin' ? 'ادمین' : ($user->access_level === 'social_worker' ? 'مددکار' : ($user->access_level === 'distribution_operator' ? 'اپراتور توزیع' : 'کاربر عادی')));
                                $roleClasses = $user->access_level === 'manager' ? 'bg-indigo-100 text-indigo-700 ring-indigo-200' : ($user->access_level === 'admin' ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : ($user->access_level === 'social_worker' ? 'bg-cyan-100 text-cyan-700 ring-cyan-200' : ($user->access_level === 'distribution_operator' ? 'bg-violet-100 text-violet-700 ring-violet-200' : 'bg-slate-100 text-slate-700 ring-slate-200')));
                                $isCurrentUser = auth()->id() === $user->id;
                                $isProtected = $user->isProtectedManagerAccount();
                                $hasPendingAction = ($pendingActionMap[$user->id]['downgrade_admin'] ?? false) || ($pendingActionMap[$user->id]['delete_user'] ?? false) || ($pendingActionMap[$user->id]['promote_to_admin'] ?? false);
                                $permissionSummary = collect($user->permission_labels)->take(1)->implode('، ');
                                $permissionCount = count($user->permission_labels);
                            @endphp
                            <article wire:key="user-card-{{ $user->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                                <div class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <h3 class="truncate text-sm font-black text-slate-900">{{ $user->full_name ?: 'بدون نام' }}</h3>
                                                @if($isCurrentUser)
                                                    <span class="rounded-full bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold text-white">فعلی</span>
                                                @endif
                                                @if($isProtected)
                                                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">محافظت‌شده</span>
                                                @endif
                                            </div>
                                            <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $user->name }} · {{ $user->email }}</p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $roleClasses }}">{{ $roleLabel }}</span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] text-slate-500">
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5">ایجاد: {{ $user->created_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($user->created_at)->format('Y/m/d') : '-' }}</span>
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5">دسترسی: {{ $permissionCount > 0 ? ($permissionSummary !== '' ? $permissionSummary : '۱ مورد') : 'ندارد' }}</span>
                                    </div>

                                    @if($hasPendingAction)
                                        <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11px] font-semibold text-amber-800">
                                            در انتظار تایید مدیریتی
                                        </div>
                                    @endif

                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @if(! $isCurrentUser)
                                            @if(! $isProtected)
                                                <button type="button" wire:click="openEditModal({{ $user->id }})" title="ویرایش" aria-label="ویرایش" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>
                                                </button>

                                                @if($user->access_level !== 'admin' && !($pendingActionMap[$user->id]['promote_to_admin'] ?? false))
                                                    <button type="button" wire:click="setAccessLevel({{ $user->id }}, 'admin')" title="ارتقا به ادمین" aria-label="ارتقا به ادمین" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if($user->access_level !== 'regular_user' && !($pendingActionMap[$user->id]['downgrade_admin'] ?? false))
                                                    <button type="button" wire:click="setAccessLevel({{ $user->id }}, 'regular_user')" title="تبدیل به کاربر عادی" aria-label="تبدیل به کاربر عادی" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if($user->access_level !== 'distribution_operator')
                                                    <button type="button" wire:click="setAccessLevel({{ $user->id }}, 'distribution_operator')" title="تنظیم به اپراتور توزیع" aria-label="تنظیم به اپراتور توزیع" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-violet-200 bg-violet-50 text-violet-700 transition hover:bg-violet-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16M12 4l8 8-8 8" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if(!($pendingActionMap[$user->id]['delete_user'] ?? false))
                                                    <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="آیا از حذف این کاربر مطمئن هستید؟" title="حذف" aria-label="حذف" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 14h6l1-14" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            @else
                                                <span class="rounded-xl bg-amber-50 px-2.5 py-1.5 text-[11px] font-semibold text-amber-700">حساب مدیر اصلی محافظت شده است.</span>
                                            @endif
                                        @else
                                            <span class="rounded-xl bg-slate-100 px-2.5 py-1.5 text-[11px] font-semibold text-slate-500">حساب فعلی</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                                <div class="text-lg font-bold text-slate-900">کاربری مطابق فیلترها پیدا نشد</div>
                                <p class="mt-2 text-sm text-slate-600">جستجو یا فیلترها را تغییر دهید، یا فیلترها را پاک کنید.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showEditModal && $editing_user_id)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm" wire:keydown.escape.window="cancelEditingUser">
            <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">ویرایش کاربر</h2>
                            <p class="mt-1 text-sm text-slate-600">ویرایش سریع نام، نام کاربری، رمز عبور و سطح دسترسی.</p>
                        </div>
                        <button type="button" wire:click="cancelEditingUser" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                            بستن
                        </button>
                    </div>
                </div>

                <form wire:submit.prevent="updateUser" class="space-y-4 px-5 py-5 sm:px-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">نام</label>
                            <input type="text" wire:model.blur="edit_first_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                            @error('edit_first_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">نام خانوادگی</label>
                            <input type="text" wire:model.blur="edit_last_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                            @error('edit_last_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">نام کاربری</label>
                            <input type="text" wire:model.blur="edit_username" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                            @error('edit_username') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">سطح دسترسی</label>
                            <select wire:model="edit_access_level" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                                <option value="manager" disabled>مدیریت (محافظت‌شده)</option>
                                <option value="admin" @disabled(!$actorCanCreateAdmin)>ادمین</option>
                                <option value="regular_user">کاربر عادی</option>
                                <option value="social_worker">مددکار</option>
                                <option value="distribution_operator">اپراتور توزیع</option>
                            </select>
                            @error('edit_access_level') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div x-data="{ showPassword: false }" class="md:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">رمز عبور جدید</label>
                            <div class="relative">
                                <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="edit_password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 pl-10 text-sm focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100" placeholder="در صورت نیاز تغییر دهید">
                                <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600">
                                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                </button>
                            </div>
                            @error('edit_password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div x-data="{ showPassword: false }" class="md:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">تکرار رمز عبور جدید</label>
                            <div class="relative">
                                <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="edit_password_confirmation" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 pl-10 text-sm focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100" placeholder="تکرار رمز عبور جدید">
                                <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600">
                                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100">
                            ذخیره تغییرات
                        </button>
                        <button type="button" wire:click="cancelEditingUser" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            انصراف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
