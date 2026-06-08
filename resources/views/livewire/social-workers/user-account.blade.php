<div class="container mx-auto max-w-5xl p-4">
    <div class="space-y-4">
        @if (session()->has('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <label for="profile-photo-input" class="group relative block h-16 w-16 cursor-pointer overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                        @if($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="Profile" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-lg font-bold text-slate-600">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <span class="absolute inset-x-0 bottom-0 bg-slate-900/60 px-1 py-0.5 text-center text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100">تغییر</span>
                    </label>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">حساب کاربری</h1>
                        <p class="text-xs text-slate-500">پروفایل شخصی، نام کاربری، ایمیل و رمز عبور</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input id="profile-photo-input" type="file" wire:model="newProfilePhoto" accept="image/*" class="hidden">
                    <label for="profile-photo-input" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">انتخاب تصویر</label>
                    <span wire:loading wire:target="newProfilePhoto" class="text-xs font-semibold text-amber-600">در حال آپلود...</span>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">برای تغییر عکس، روی تصویر پروفایل یا دکمه انتخاب تصویر کلیک کنید. ذخیره به‌صورت خودکار انجام می‌شود.</p>
            @error('newProfilePhoto') <span class="mt-2 block text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">اطلاعات حساب</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"><span class="text-slate-500">شناسه</span><span class="font-semibold text-slate-700">{{ $user->id }}</span></div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"><span class="text-slate-500">نام کاربری فعلی</span><span class="font-semibold text-slate-700">{{ $user->name }}</span></div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"><span class="text-slate-500">سطح دسترسی</span><span class="font-semibold text-slate-700">{{ $user->access_level ?? ($user->is_admin ? 'admin' : 'regular_user') }}</span></div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">تغییر ایمیل سیستمی</h2>
                <form wire:submit.prevent="updateEmail" class="mt-3 space-y-2">
                    <input type="email" wire:model.blur="email" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="example@domain.com">
                    @error('email') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
                    <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">ذخیره ایمیل</button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-800">تغییر نام کاربری</h2>
                    @if(!$isEditingUsername)
                        <button type="button" wire:click="startUsernameEdit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">ویرایش</button>
                    @endif
                </div>

                <form wire:submit.prevent="updateUsername" class="space-y-2">
                    <input
                        type="text"
                        wire:model.blur="username"
                        @disabled(!$isEditingUsername)
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 disabled:bg-slate-100 disabled:text-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    >
                    @error('username') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror

                    @if($isEditingUsername)
                        <div class="flex items-center gap-2">
                            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">تایید و ذخیره</button>
                            <button type="button" wire:click="cancelUsernameEdit" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">انصراف</button>
                        </div>
                    @endif
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">تغییر رمز عبور</h2>
                <form wire:submit.prevent="updatePassword" class="mt-3 space-y-2">
                    <input type="password" wire:model.blur="current_password" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="رمز عبور فعلی">
                    @error('current_password') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror

                    <input type="password" wire:model.blur="new_password" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="رمز عبور جدید">
                    @error('new_password') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror

                    <input type="password" wire:model.blur="new_password_confirmation" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="تکرار رمز عبور جدید">

                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">ذخیره رمز عبور</button>
                </form>
            </div>
        </div>
    </div>
</div>
