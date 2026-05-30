<div class="container mx-auto p-4">
    <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-slate-800">مدیریت کاربران</h1>
            <p class="mt-1 text-sm text-slate-500">ایجاد کاربر جدید و تخصیص سطح دسترسی ماژول‌ها برای ثبت، ویرایش یا حذف مددجو</p>
        </div>

        @if (session()->has('success'))
            <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($isManager)
            <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                <h2 class="mb-3 text-sm font-bold text-indigo-800">درخواست‌های در انتظار تایید مدیریت</h2>
                @if($pendingRequests->isEmpty())
                    <p class="text-xs text-indigo-700">درخواستی در انتظار تایید وجود ندارد.</p>
                @else
                    <div class="space-y-2">
                        @foreach($pendingRequests as $pending)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-indigo-100 bg-white px-3 py-2 text-xs">
                                <div class="text-slate-700">
                                    <span class="font-semibold">
                                        {{ $pending->action_type === 'delete_user'
                                            ? 'حذف کاربر ادمین'
                                            : ($pending->action_type === 'downgrade_admin' ? 'تنزل ادمین به Regular User' : 'ارتقا به Admin') }}
                                    </span>
                                    <span class="mx-1 text-slate-400">|</span>
                                    هدف: <span class="font-semibold">{{ $pendingUserNames[$pending->target_user_id] ?? ('#'.$pending->target_user_id) }}</span>
                                    <span class="mx-1 text-slate-400">|</span>
                                    درخواست‌دهنده: <span class="font-semibold">{{ $pendingUserNames[$pending->requested_by_user_id] ?? ('#'.$pending->requested_by_user_id) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="approveRequest({{ $pending->id }})" class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 font-semibold text-emerald-700 hover:bg-emerald-100">
                                        تایید
                                    </button>
                                    <button type="button" wire:click="rejectRequest({{ $pending->id }})" class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 font-semibold text-rose-700 hover:bg-rose-100">
                                        رد
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if($editing_user_id)
            <form
                wire:submit.prevent="updateUser"
                x-data="{}"
                x-on:scroll-to-user-edit.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                class="mb-6 grid gap-4 rounded-2xl border border-amber-200 bg-amber-50/40 p-5 md:grid-cols-4"
            >
                <div class="md:col-span-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">ویرایش کاربر</h2>
                        <p class="mt-1 text-sm text-slate-500">نام، نام خانوادگی، نام کاربری، سطح دسترسی و رمز عبور کاربر را در همین صفحه تغییر دهید.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="cancelEditingUser"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        انصراف
                    </button>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">نام</label>
                    <input
                        type="text"
                        wire:model.blur="edit_first_name"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                    >
                    @error('edit_first_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">نام خانوادگی</label>
                    <input
                        type="text"
                        wire:model.blur="edit_last_name"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                    >
                    @error('edit_last_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">نام کاربری</label>
                    <input
                        type="text"
                        wire:model.blur="edit_username"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                    >
                    @error('edit_username') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">سطح دسترسی</label>
                    <select
                        wire:model="edit_access_level"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                    >
                        <option value="manager" disabled>مدیریت (محافظت‌شده)</option>
                        <option value="admin" @disabled(!$actorCanCreateAdmin)>ادمین</option>
                        <option value="regular_user">کاربر عادی</option>
                        <option value="social_worker">مددکار (سیستمی)</option>
                        <option value="distribution_operator">اپراتور توزیع</option>
                    </select>
                    @error('edit_access_level') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div x-data="{ showPassword: false }">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">رمز عبور جدید</label>
                    <div class="relative">
                        <input
                            x-bind:type="showPassword ? 'text' : 'password'"
                            wire:model.blur="edit_password"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pl-10 text-sm text-slate-700 focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            placeholder="در صورت نیاز تغییر دهید"
                        >
                        <button
                            type="button"
                            x-on:click="showPassword = !showPassword"
                            class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600"
                            x-bind:aria-label="showPassword ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور'"
                        >
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('edit_password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2" x-data="{ showPassword: false }">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">تکرار رمز عبور جدید</label>
                    <div class="relative">
                        <input
                            x-bind:type="showPassword ? 'text' : 'password'"
                            wire:model.blur="edit_password_confirmation"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pl-10 text-sm text-slate-700 focus:border-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            placeholder="تکرار رمز عبور جدید"
                        >
                        <button
                            type="button"
                            x-on:click="showPassword = !showPassword"
                            class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600"
                            x-bind:aria-label="showPassword ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور'"
                        >
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="md:col-span-4 flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100"
                    >
                        ذخیره تغییرات
                    </button>
                    <span class="text-xs text-slate-500">اگر رمز عبور را خالی بگذارید، رمز فعلی حفظ می‌شود.</span>
                </div>
            </form>
        @endif

        <form wire:submit.prevent="createUser" class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">نام</label>
                <input
                    type="text"
                    wire:model.blur="first_name"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="مثال: علی"
                >
                @error('first_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">نام خانوادگی</label>
                <input
                    type="text"
                    wire:model.blur="last_name"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="مثال: رضایی"
                >
                @error('last_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">نام کاربری</label>
                <input
                    type="text"
                    wire:model.blur="username"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="مثال: admin01"
                >
                @error('username') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div x-data="{ showPassword: false }">
                <label class="mb-2 block text-sm font-semibold text-slate-700">رمز عبور</label>
                <div class="relative">
                    <input
                        x-bind:type="showPassword ? 'text' : 'password'"
                        wire:model.blur="password"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pl-10 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        placeholder="حداقل 8 کاراکتر"
                    >
                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600"
                        x-bind:aria-label="showPassword ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور'"
                    >
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                @error('password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div x-data="{ showPassword: false }">
                <label class="mb-2 block text-sm font-semibold text-slate-700">تکرار رمز عبور</label>
                <div class="relative">
                    <input
                        x-bind:type="showPassword ? 'text' : 'password'"
                        wire:model.blur="password_confirmation"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pl-10 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        placeholder="تکرار رمز عبور"
                    >
                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        class="absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600"
                        x-bind:aria-label="showPassword ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور'"
                    >
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">سطح دسترسی</label>
                <select
                    wire:model="access_level"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                >
                    <option value="manager" disabled>مدیریت (محافظت‌شده)</option>
                    <option value="admin" @disabled(!$actorCanCreateAdmin)>ادمین</option>
                    <option value="regular_user">کاربر عادی</option>
                    <option value="distribution_operator">اپراتور توزیع</option>
                </select>
                @error('access_level') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-4">
                <label class="mb-2 block text-sm font-semibold text-slate-700">دسترسی‌های قابل تخصیص</label>
                <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
                    @foreach($permissionOptions as $permissionKey => $permissionLabel)
                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                value="{{ $permissionKey }}"
                                wire:model="permissions"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span>{{ $permissionLabel }}</span>
                        </label>
                    @endforeach
                </div>
                @error('permissions') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                @error('permissions.*') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-4">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                >
                    ایجاد کاربر
                </button>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full border-collapse text-sm">
                <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-center font-bold">#</th>
                    <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی</th>
                    <th class="px-4 py-3 text-right font-bold">نام کاربری</th>
                    <th class="px-4 py-3 text-right font-bold">ایمیل سیستمی</th>
                    <th class="px-4 py-3 text-center font-bold">سطح دسترسی</th>
                    <th class="px-4 py-3 text-right font-bold">دسترسی‌های فعال</th>
                    <th class="px-4 py-3 text-center font-bold">تاریخ ایجاد</th>
                    <th class="px-4 py-3 text-center font-bold">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $user->full_name ?: '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $user->access_level === 'manager' ? 'bg-indigo-100 text-indigo-700' : '' }}
                                {{ $user->access_level === 'admin' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $user->access_level === 'regular_user' ? 'bg-slate-100 text-slate-600' : '' }}
                                {{ $user->access_level === 'social_worker' ? 'bg-cyan-100 text-cyan-700' : '' }}
                                {{ $user->access_level === 'distribution_operator' ? 'bg-violet-100 text-violet-700' : '' }}">
                                {{ $user->access_level === 'manager' ? 'Manager' : ($user->access_level === 'admin' ? 'Admin' : ($user->access_level === 'social_worker' ? 'Social Worker' : ($user->access_level === 'distribution_operator' ? 'Distribution Operator' : 'Regular User'))) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                @foreach($permissionOptions as $permissionKey => $permissionLabel)
                                    @php($enabled = $user->hasPermission($permissionKey))
                                    <button
                                        type="button"
                                        wire:click="toggleUserPermission({{ $user->id }}, '{{ $permissionKey }}')"
                                        @disabled(auth()->id() === $user->id || $user->isProtectedManagerAccount())
                                        class="rounded-full border px-2.5 py-1 text-[11px] font-semibold transition
                                            {{ $enabled ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}
                                            disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {{ $permissionLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $user->created_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($user->created_at)->format('Y/m/d H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if(auth()->id() !== $user->id)
                                    @if(!$user->isProtectedManagerAccount())
                                        @if(($pendingActionMap[$user->id]['downgrade_admin'] ?? false) || ($pendingActionMap[$user->id]['delete_user'] ?? false) || ($pendingActionMap[$user->id]['promote_to_admin'] ?? false))
                                            <span class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                                درخواست در انتظار تایید مدیر
                                            </span>
                                        @endif

                                        @if($user->access_level !== 'admin' && !($pendingActionMap[$user->id]['promote_to_admin'] ?? false))
                                            <button type="button" wire:click="setAccessLevel({{ $user->id }}, 'admin')" class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                تغییر به ادمین
                                            </button>
                                        @endif

                                        @if($user->access_level !== 'regular_user' && !($pendingActionMap[$user->id]['downgrade_admin'] ?? false))
                                            <button type="button" wire:click="setAccessLevel({{ $user->id }}, 'regular_user')" class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                                                تغییر به کاربر عادی
                                            </button>
                                        @endif

                                        @if($user->access_level !== 'distribution_operator')
                                            <button type="button" wire:click="setAccessLevel({{ $user->id }}, 'distribution_operator')" class="inline-flex items-center justify-center rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100">
                                                تغییر به اپراتور توزیع
                                            </button>
                                        @endif

                                        <button type="button" wire:click="startEditingUser({{ $user->id }})" class="inline-flex items-center justify-center rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                                            ویرایش
                                        </button>
                                    @endif

                                    @if(!$user->isProtectedManagerAccount() && !($pendingActionMap[$user->id]['delete_user'] ?? false))
                                        <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="آیا از حذف این کاربر مطمئن هستید؟" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                            حذف
                                        </button>
                                    @elseif($user->isProtectedManagerAccount())
                                        <span class="text-xs font-semibold text-indigo-600">(مدیر) محافظت‌شده</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">حساب فعلی</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">کاربری ثبت نشده است.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
