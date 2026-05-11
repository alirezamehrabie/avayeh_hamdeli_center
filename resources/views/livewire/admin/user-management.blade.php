<div class="container mx-auto p-4">
    <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-slate-800">مدیریت کاربران</h1>
            <p class="mt-1 text-sm text-slate-500">ایجاد کاربر جدید با نام کاربری و رمز عبور</p>
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

        <form wire:submit.prevent="createUser" class="grid gap-4 md:grid-cols-4">
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

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">رمز عبور</label>
                <input
                    type="password"
                    wire:model.blur="password"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="حداقل 8 کاراکتر"
                >
                @error('password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">تکرار رمز عبور</label>
                <input
                    type="password"
                    wire:model.blur="password_confirmation"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    placeholder="تکرار رمز عبور"
                >
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
                </select>
                @error('access_level') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
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
                    <th class="px-4 py-3 text-right font-bold">نام کاربری</th>
                    <th class="px-4 py-3 text-right font-bold">ایمیل سیستمی</th>
                    <th class="px-4 py-3 text-center font-bold">سطح دسترسی</th>
                    <th class="px-4 py-3 text-center font-bold">تاریخ ایجاد</th>
                    <th class="px-4 py-3 text-center font-bold">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $user->access_level === 'manager' ? 'bg-indigo-100 text-indigo-700' : '' }}
                                {{ $user->access_level === 'admin' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $user->access_level === 'regular_user' ? 'bg-slate-100 text-slate-600' : '' }}">
                                {{ $user->access_level === 'manager' ? 'Manager' : ($user->access_level === 'admin' ? 'Admin' : 'Regular User') }}
                            </span>
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
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">کاربری ثبت نشده است.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
