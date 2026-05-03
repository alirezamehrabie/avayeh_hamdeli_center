<div>
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50" dir="rtl">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
            <div>
                <img class="w-56 mx-auto" src="{{ asset('/images/logo-sm.png') }}" alt="image">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    ورود به پنل مدیریت
                </h2>
                <p class="mt-3 text-center text-sm text-gray-600">
                    مرکز نیکوکاری تخصصی کودکان آوای همدلی
                </p>
            </div>
            <form class="mt-8 space-y-6" wire:submit.prevent="login">
                <div class="rounded-md -space-y-px">
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">نام کاربری</label>
                        <input wire:model="email" id="email" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">رمز عبور</label>
                        <input wire:model="password" id="password" type="password" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input wire:model="remember" id="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember-me" class="mr-2 block text-sm text-gray-900">
                            مرا به خاطر بسپار
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <span wire:loading.remove>ورود به سیستم</span>
                        <span wire:loading>در حال بررسی...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
