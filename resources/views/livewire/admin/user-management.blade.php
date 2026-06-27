<div class="bg-slate-50/80" x-data="{ statsModalOpen: false }" x-on:keydown.escape.window="statsModalOpen = false">
    <div class="{{ $listOnly ? 'w-full p-0' : 'mx-auto w-full max-w-full p-0' }}">
        <div class="w-full max-w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_20px_80px_-30px_rgba(15,23,42,0.35)] sm:rounded-[2rem] {{ $listOnly ? 'flex min-h-0 flex-col' : '' }}">
            <div class="border-b border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 px-3 py-4 text-white sm:px-5 sm:py-5 lg:px-6 lg:py-3.5">
                <div class="flex min-w-0 flex-col gap-4 sm:gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0 max-w-3xl">
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-black tracking-tight text-white lg:text-[1.75rem]">
                                {{ $listOnly ? 'لیست کاربران' : 'تعریف کاربر' }}
                            </h1>
                        </div>
                        <p class="mt-1.5 max-w-2xl text-xs leading-5 text-slate-200/90 sm:text-sm sm:leading-6 lg:max-w-xl">
                            {{ $listOnly ? 'مشاهده، جستجو و فیلتر کاربران با همان ظاهر فعلی سیستم' : 'ایجاد و تعریف حساب کاربری جدید با نقش و دسترسی مشخص' }}
                        </p>
                    </div>
                    <div class="flex w-full min-w-0 items-stretch gap-2 sm:items-center lg:w-auto lg:min-w-[18rem] lg:justify-end">
                        <div class="flex min-h-12 min-w-0 flex-1 items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/5 px-3 py-2 backdrop-blur lg:flex-none lg:min-w-52">
                            <div class="min-w-0">
                                <span class="block truncate text-[10px] font-semibold text-slate-300">خلاصه کاربران</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-400">فعال و غیرفعال</span>
                            </div>
                            <div class="shrink-0 text-left">
                                <span class="block text-lg font-black leading-5 text-white">{{ $userStats['total'] }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            x-on:click="statsModalOpen = true"
                            class="inline-flex h-auto min-h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/10 text-white transition hover:bg-white/15 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/20"
                            aria-label="نمایش آمار نقش‌های کاربران"
                            title="آمار نقش‌ها"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 19V5" />
                                <path d="M4 19h16" />
                                <path d="M8 16v-5" />
                                <path d="M12 16V8" />
                                <path d="M16 16v-9" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-5 px-3 py-4 sm:px-5 sm:py-5 lg:px-8 {{ $listOnly ? 'min-h-0' : '' }}">
                @if (session()->has('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(! $listOnly && $isManager && $hasPendingRequests)
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/80 p-3 shadow-sm sm:p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-black text-indigo-900">درخواست‌های در انتظار تایید</h2>
                                <p class="mt-1 text-xs text-indigo-700">درخواست‌های مدیریتی که هنوز بررسی نشده‌اند.</p>
                            </div>
                        </div>
                        <div class="grid gap-2.5 xl:grid-cols-2">
                            @foreach($pendingRequests as $pending)
                                <div class="rounded-2xl border border-indigo-100 bg-white p-3 shadow-sm sm:p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="space-y-1 text-sm text-slate-700">
                                            <div class="font-bold text-slate-900">
                                                {{ $pending->action_type === 'delete_user' ? 'حذف کاربر' : ($pending->action_type === 'downgrade_admin' ? 'تنزیل به کاربر عادی' : 'ارتقا به ادمین') }}
                                            </div>
                                            <div class="text-xs text-slate-500">هدف: {{ $pendingUserNames[$pending->target_user_id] ?? ('#'.$pending->target_user_id) }}</div>
                                            <div class="text-xs text-slate-500">درخواست‌دهنده: {{ $pendingUserNames[$pending->requested_by_user_id] ?? ('#'.$pending->requested_by_user_id) }}</div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="approveRequest({{ $pending->id }})" class="min-h-10 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">تایید</button>
                                            <button type="button" wire:click="rejectRequest({{ $pending->id }})" class="min-h-10 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">رد</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @unless($listOnly || $viewingDeletedUsers)
                <form wire:submit.prevent="createUser" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-3 shadow-sm sm:rounded-3xl sm:p-4">
                    <div class="mb-3 flex flex-col gap-2 border-b border-slate-200 pb-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">ایجاد کاربر جدید</h2>
                            <p class="mt-1 text-sm text-slate-600">فرآیند ساخت کاربر را در سه مرحله شفاف و قابل مدیریت انجام دهید.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-3">
                            @foreach ([
                                ['step' => '01', 'title' => 'نقش پایه', 'desc' => 'انتخاب نقش اصلی کاربر'],
                                ['step' => '02', 'title' => 'اطلاعات کاربر', 'desc' => 'تکمیل مشخصات و امنیت حساب'],
                                ['step' => '03', 'title' => 'دسترسی‌ها', 'desc' => 'تنظیم دسترسی‌های وابسته به نقش'],
                            ] as $step)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-[11px] font-black tracking-[0.2em] text-indigo-600">{{ $step['step'] }}</div>
                                    <div class="mt-1 text-sm font-black text-slate-900">{{ $step['title'] }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $step['desc'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-black text-slate-900">مرحله ۱ – انتخاب نقش پایه</h3>
                                    <p class="mt-1 text-sm text-slate-500">نقش پایه رفتار پیش‌فرض فرم و دسترسی‌های قابل انتخاب را تعیین می‌کند.</p>
                                </div>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">نقش پایه</span>
                            </div>

                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                                @foreach ($roleDefinitions as $roleValue => $roleMeta)
                                    <label class="min-w-0 cursor-pointer">
                                        <input
                                            type="radio"
                                            class="peer sr-only"
                                            wire:model.live="access_level"
                                            value="{{ $roleValue }}"
                                            @disabled(($roleMeta['disabled'] ?? false) || ($roleValue === 'admin' && ! $actorCanCreateAdmin))
                                        >
                                        <div class="relative flex h-full min-h-32 flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4 text-right transition duration-200 ease-out peer-checked:border-indigo-400 peer-checked:bg-indigo-50 peer-checked:shadow-sm peer-checked:ring-4 peer-checked:ring-indigo-100 peer-disabled:cursor-not-allowed peer-disabled:opacity-50">
                                            <div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="text-sm font-black text-slate-900">{{ $roleMeta['label'] }}</span>
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-400 peer-checked:border-indigo-500 peer-checked:text-indigo-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.414-1.42l2.293 2.294 6.543-6.544a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $roleMeta['description'] }}</p>
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                @foreach(($roleMeta['recommended_permissions'] ?? []) as $permissionKey)
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                                        {{ $permissionOptions[$permissionKey] ?? $permissionKey }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('access_level') <span class="mt-2 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                        </section>

                        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-black text-slate-900">مرحله ۲ – اطلاعات کاربر</h3>
                                    <p class="mt-1 text-sm text-slate-500">مشخصات هویتی و اطلاعات ورود را کامل کنید.</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">اطلاعات کاربر</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">نام</label>
                                    <input type="text" wire:model.blur="first_name" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm leading-5 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                    @error('first_name') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">نام خانوادگی</label>
                                    <input type="text" wire:model.blur="last_name" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm leading-5 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                    @error('last_name') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">نام کاربری</label>
                                    <input type="text" wire:model.blur="username" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm leading-5 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                    @error('username') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-1 xl:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">رمز عبور</label>
                                    <div x-data="{ showPassword: false }" class="relative">
                                        <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="password" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 text-sm leading-5 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                        <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600" aria-label="نمایش یا مخفی کردن رمز عبور">
                                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    @error('password') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-1 xl:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">تکرار رمز عبور</label>
                                    <div x-data="{ showPassword: false }" class="relative">
                                        <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="password_confirmation" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 text-sm leading-5 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                        <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600" aria-label="نمایش یا مخفی کردن تکرار رمز">
                                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-900">مرحله ۳ – مدیریت پویا و نقش‌محور دسترسی‌ها</h3>
                                    <p class="mt-1 text-sm text-slate-500">دسترسی‌های این بخش در لحظه و متناسب با نقش پایه به‌روزرسانی می‌شوند.</p>
                                </div>
                                <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                                    <div class="font-black">{{ $roleDefinitions[$access_level]['label'] ?? 'بدون نقش' }}</div>
                                    <div class="mt-1 text-xs text-indigo-700">هر تغییر در نقش پایه، فهرست دسترسی‌های این بخش را بازچینش می‌کند.</div>
                                </div>
                            </div>

                            @if(count($availablePermissionOptions) > 0)
                                <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-3">
                                    @foreach($availablePermissionOptions as $permissionKey => $permissionLabel)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" value="{{ $permissionKey }}" wire:model.live="permissions" class="peer sr-only">
                                            <div class="flex min-h-24 flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4 transition peer-checked:border-indigo-300 peer-checked:bg-indigo-50 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="text-sm font-bold text-slate-900">{{ $permissionLabel }}</div>
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-400 peer-checked:border-indigo-500 peer-checked:text-indigo-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.414-1.42l2.293 2.294 6.543-6.544a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="mt-3 text-xs text-slate-500">
                                                    @if($access_level === \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
                                                        دسترسی عملیاتی برای یکی از گیت‌های فرآیند توزیع.
                                                    @elseif($permissionKey === \App\Models\User::PERMISSION_FULL_ACCESS)
                                                        دسترسی کامل برای مدیریت همه قابلیت‌های سیستم.
                                                    @else
                                                        دسترسی قابل توسعه برای این نقش پایه.
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                    <div class="text-sm font-bold text-slate-800">برای این نقش هنوز دسترسی قابل انتخابی تعریف نشده است.</div>
                                    <div class="mt-1 text-xs text-slate-500">ساختار نقش‌محور آماده توسعه برای دسترسی‌های آینده باقی می‌ماند.</div>
                                </div>
                            @endif

                            @error('permissions') <span class="mt-2 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                            @error('permissions.*') <span class="mt-2 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                        </section>

                        <div class="flex flex-col gap-3 rounded-3xl border border-emerald-200 bg-emerald-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-black text-emerald-900">آماده ثبت کاربر جدید</div>
                                <div class="mt-1 text-xs text-emerald-700">نقش انتخاب‌شده: {{ $roleDefinitions[$access_level]['label'] ?? '-' }} • دسترسی‌های انتخاب‌شده: {{ count($permissions) }}</div>
                            </div>
                            <button
                                type="submit"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-emerald-800/40 bg-gradient-to-b from-emerald-700 to-emerald-800 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-emerald-600 hover:to-emerald-700 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-200"
                                aria-label="ثبت کاربر"
                                title="ثبت کاربر"
                            >
                                <i class="bi bi-person-plus text-base leading-none" aria-hidden="true"></i>
                                <span>ثبت کاربر</span>
                            </button>
                        </div>
                    </div>
                </form>
                @endunless

                @if($listOnly)
                <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-3xl sm:p-4">
                    <div class="mb-4 flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">فهرست کاربران</h2>
                            <p class="mt-1 text-sm text-slate-600">جستجو، فیلتر و تغییر نقش‌های سریع. ویرایش‌های جزئی در یک مودال جدا انجام می‌شود.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.user-definition') }}" class="inline-flex items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $viewingDeletedUsers ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' : 'border-indigo-200 bg-indigo-50 text-indigo-700' }}">
                                کاربران فعال
                            </a>
                            <a href="{{ route('admin.user-list.deleted') }}" class="inline-flex items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $viewingDeletedUsers ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">
                                کاربران غیرفعال
                            </a>
                            @unless($viewingDeletedUsers)
                        <button type="button" wire:click="clearUserFilters" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            پاک کردن فیلترها
                        </button>
                            @endunless
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">جستجو</label>
                            <input type="search" wire:model.live.debounce.300ms="search" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100" placeholder="نام، نام کاربری یا ایمیل">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">نقش</label>
                            <select wire:model.live="roleFilter" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="all">همه نقش‌ها</option>
                                <option value="manager">مدیریت</option>
                                <option value="admin">ادمین</option>
                                <option value="regular_user">کاربر عادی</option>
                                <option value="child_supporter">حامی کودک</option>
                                <option value="social_worker">مددکار</option>
                                <option value="distribution_operator">اپراتور توزیع</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">وضعیت</label>
                            <select wire:model.live="statusFilter" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="all">همه وضعیت‌ها</option>
                                <option value="admin">ادمین / مدیر</option>
                                <option value="regular">کاربر غیرادمین</option>
                                <option value="protected">حساب محافظت‌شده</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">دسترسی</label>
                            <select wire:model.live="permissionFilter" class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <option value="all">همه دسترسی‌ها</option>
                                @foreach($permissionOptions as $permissionKey => $permissionLabel)
                                    <option value="{{ $permissionKey }}">{{ $permissionLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse($users as $user)
                            @php
                                $roleLabel = $user->access_level === 'manager' ? 'مدیریت' : ($user->access_level === 'admin' ? 'ادمین' : ($user->access_level === 'social_worker' ? 'مددکار' : ($user->access_level === \App\Models\User::ACCESS_LEVEL_CHILD_SUPPORTER ? 'حامی کودک' : ($user->access_level === 'distribution_operator' ? 'اپراتور توزیع' : 'کاربر عادی'))));
                                $roleClasses = $user->access_level === 'manager' ? 'bg-indigo-100 text-indigo-700 ring-indigo-200' : ($user->access_level === 'admin' ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : ($user->access_level === 'social_worker' ? 'bg-cyan-100 text-cyan-700 ring-cyan-200' : ($user->access_level === \App\Models\User::ACCESS_LEVEL_CHILD_SUPPORTER ? 'bg-teal-100 text-teal-700 ring-teal-200' : ($user->access_level === 'distribution_operator' ? 'bg-violet-100 text-violet-700 ring-violet-200' : 'bg-slate-100 text-slate-700 ring-slate-200'))));
                                $isCurrentUser = auth()->id() === $user->id;
                                $isProtected = $user->isProtectedManagerAccount();
                                $hasPendingAction = ($pendingActionMap[$user->id]['delete_user'] ?? false);
                                $permissionSummary = collect($user->permission_labels)->take(1)->implode('، ');
                                $permissionCount = count($user->permission_labels);
                            @endphp
                            <article wire:key="user-card-{{ $user->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                                <div class="px-3 py-3 sm:px-4">
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
                                            <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $user->name }}</p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $roleClasses }}">{{ $roleLabel }}</span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] text-slate-500">
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5">ایجاد: {{ $user->created_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($user->created_at)->format('Y/m/d') : '-' }}</span>
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5">دسترسی: {{ $permissionCount > 0 ? ($permissionSummary !== '' ? $permissionSummary : '۱ مورد') : 'ندارد' }}</span>
                                    </div>

                                    @if($viewingDeletedUsers)
                                        <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] text-slate-500">
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-rose-700">حذف‌شده: {{ $user->deleted_at ? \App\Helpers\Morilog\Jalalian::fromDateTime($user->deleted_at)->format('Y/m/d') : '-' }}</span>
                                        </div>
                                    @endif

                                    @if($hasPendingAction)
                                        <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11px] font-semibold text-amber-800">
                                            در انتظار تایید مدیریتی
                                        </div>
                                    @endif

                                    @if($viewingDeletedUsers)
                                        <div class="mt-2 rounded-xl border border-rose-100 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700">
                                            رکورد کاربر حذف شده برای حسابرسی نگه داشته شد.
                                        </div>
                                    @else
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        @if(! $isCurrentUser)
                                            @if(! $isProtected)
                                                <button type="button" wire:click="openEditModal({{ $user->id }})" title="ویرایش" aria-label="ویرایش" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>
                                                    <span>ویرایش</span>
                                                </button>

                                                @if(!($pendingActionMap[$user->id]['delete_user'] ?? false))
                                                    <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="آیا از حذف این کاربر مطمئن هستید؟" title="حذف" aria-label="حذف" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-rose-200 bg-white/80 text-rose-600 border border-slate-200 transition duration-150 ease-out hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:translate-y-px">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M4 7h16" />
                                                            <path d="M9 7V5.8c0-.99.8-1.8 1.8-1.8h2.4c.99 0 1.8.8 1.8 1.8V7" />
                                                            <path d="M7 7.5 7.8 19c.08.98.89 1.75 1.88 1.75h4.64c.99 0 1.8-.77 1.88-1.75L17 7.5" />
                                                            <path d="M10 10.5v5" />
                                                            <path d="M14 10.5v5" />
                                                        </svg>
                                                        <span class="sr-only">حذف</span>
                                                    </button>
                                                @endif
                                            @else
                                                <span class="rounded-xl bg-amber-50 px-2.5 py-1.5 text-[11px] font-semibold text-amber-700">حساب مدیر اصلی محافظت شده است.</span>
                                            @endif
                                        @else
                                            <span class="rounded-xl bg-slate-100 px-2.5 py-1.5 text-[11px] font-semibold text-slate-500">حساب فعلی</span>
                                        @endif
                                    </div>
                                    @endif
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
                @endif
            </div>
        </div>
    </div>

    <div
        x-cloak
        x-show="statsModalOpen"
        x-transition.opacity.duration.150ms
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 px-3 py-3 backdrop-blur-sm sm:items-center sm:px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-role-stats-title"
    >
        <div class="absolute inset-0" x-on:click="statsModalOpen = false" aria-hidden="true"></div>

        <div
            x-show="statsModalOpen"
            x-transition.scale.origin.bottom.duration.150ms
            class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-5">
                <div>
                    <h2 id="user-role-stats-title" class="text-base font-black text-slate-900">آمار نقش‌های کاربران</h2>
                    <p class="mt-1 text-xs text-slate-500">نمای خلاصه از توزیع نقش‌ها و وضعیت کاربران سیستم.</p>
                </div>

                <button
                    type="button"
                    x-on:click="statsModalOpen = false"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-200"
                    aria-label="بستن آمار نقش‌ها"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-2 px-4 py-4 sm:grid-cols-2 sm:px-5">
                @foreach ([
                    ['label' => 'کل کاربران', 'value' => $userStats['total'], 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
                    ['label' => 'ادمین', 'value' => $userStats['admins'], 'class' => 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
                    ['label' => 'اپراتور توزیع', 'value' => $userStats['distributionOperators'], 'class' => 'bg-cyan-50 text-cyan-700 ring-cyan-200'],
                    ['label' => 'مددکار', 'value' => $userStats['socialWorkers'], 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                    ['label' => 'حامی کودک', 'value' => $userStats['childSupporters'], 'class' => 'bg-teal-50 text-teal-700 ring-teal-200'],
                    ['label' => 'کاربر عادی', 'value' => $userStats['regular'], 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
                    ['label' => 'غیرفعال', 'value' => $userStats['deleted'], 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
                ] as $stat)
                    <div class="flex items-center justify-between rounded-2xl px-3 py-3 ring-1 {{ $stat['class'] }}">
                        <span class="text-xs font-bold">{{ $stat['label'] }}</span>
                        <span class="text-lg font-black">{{ $stat['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($showEditModal && $editing_user_id)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 px-2 py-2 backdrop-blur-sm sm:items-center sm:px-4 sm:py-6" wire:keydown.escape.window="cancelEditingUser">
            <div class="flex w-full max-w-none flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl max-h-[92vh] sm:max-w-4xl sm:rounded-3xl sm:max-h-[90vh]">
                <div class="border-b border-slate-200 px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">ویرایش کاربر</h2>
                            <p class="mt-1 text-sm text-slate-600">به‌روزرسانی نقش، اطلاعات حساب و دسترسی‌های وابسته به نقش با همان الگوی استاندارد.</p>
                        </div>
                        <button type="button" wire:click="cancelEditingUser" aria-label="بستن" title="بستن" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18" />
                                <path d="M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit.prevent="updateUser" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
                        <div class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-3">
                            @foreach ([
                                ['step' => '01', 'title' => 'نقش پایه', 'desc' => 'بازتعریف نقش اصلی کاربر'],
                                ['step' => '02', 'title' => 'اطلاعات کاربر', 'desc' => 'اصلاح مشخصات و امنیت حساب'],
                                ['step' => '03', 'title' => 'دسترسی‌ها', 'desc' => 'به‌روزرسانی دسترسی‌های وابسته'],
                            ] as $step)
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="text-[11px] font-black tracking-[0.2em] text-amber-600">{{ $step['step'] }}</div>
                                    <div class="mt-1 text-sm font-black text-slate-900">{{ $step['title'] }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $step['desc'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-black text-slate-900">مرحله ۱ – انتخاب نقش پایه</h3>
                                    <p class="mt-1 text-sm text-slate-500">تغییر نقش پایه، دسترسی‌های مرحله سوم را بلافاصله به‌روزرسانی می‌کند.</p>
                                </div>
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">نقش پایه</span>
                            </div>

                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                                @foreach ($roleDefinitions as $roleValue => $roleMeta)
                                    <label class="min-w-0 cursor-pointer">
                                        <input
                                            type="radio"
                                            class="peer sr-only"
                                            wire:model.live="edit_access_level"
                                            value="{{ $roleValue }}"
                                            @disabled(($roleMeta['disabled'] ?? false) || ($roleValue === 'admin' && ! $actorCanCreateAdmin))
                                        >
                                        <div class="relative flex h-full min-h-32 flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4 text-right transition duration-200 ease-out peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:shadow-sm peer-checked:ring-4 peer-checked:ring-amber-100 peer-disabled:cursor-not-allowed peer-disabled:opacity-50">
                                            <div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="text-sm font-black text-slate-900">{{ $roleMeta['label'] }}</span>
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-400 peer-checked:border-amber-500 peer-checked:text-amber-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.414-1.42l2.293 2.294 6.543-6.544a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $roleMeta['description'] }}</p>
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                @foreach(($roleMeta['recommended_permissions'] ?? []) as $permissionKey)
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                                        {{ $permissionOptions[$permissionKey] ?? $permissionKey }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('edit_access_level') <span class="mt-2 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                        </section>

                        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-black text-slate-900">مرحله ۲ – اطلاعات کاربر</h3>
                                    <p class="mt-1 text-sm text-slate-500">نام، نام کاربری و در صورت نیاز رمز عبور را ویرایش کنید.</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">اطلاعات کاربر</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">نام</label>
                                    <input type="text" wire:model.blur="edit_first_name" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm leading-5 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                                    @error('edit_first_name') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">نام خانوادگی</label>
                                    <input type="text" wire:model.blur="edit_last_name" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm leading-5 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                                    @error('edit_last_name') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">نام کاربری</label>
                                    <input type="text" wire:model.blur="edit_username" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 text-sm leading-5 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                                    @error('edit_username') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-2 xl:col-span-4">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">رمز عبور فعلی</label>
                                    <div x-data="{ showPassword: false }" class="relative">
                                        <input x-bind:type="showPassword ? 'text' : 'password'" value="{{ $edit_current_password !== '' ? $edit_current_password : 'رمز عبور قبلی در سیستم قابل نمایش نیست' }}" readonly class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 pl-3 text-sm leading-5 text-slate-700 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                                        <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600" aria-label="نمایش یا مخفی کردن رمز عبور فعلی">
                                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    @if($edit_current_password === '')
                                        <p class="mt-1 text-[11px] text-slate-500">برای کاربران قدیمی فقط رمزهای ثبت‌شده یا تغییر یافته از این بخش قابل نمایش هستند.</p>
                                    @endif
                                </div>
                                <div class="sm:col-span-1 xl:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">رمز عبور جدید</label>
                                    <div x-data="{ showPassword: false }" class="relative">
                                        <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="edit_password" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 text-sm leading-5 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100" placeholder="در صورت نیاز تغییر دهید">
                                        <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600">
                                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    @error('edit_password') <span class="mt-1 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-1 xl:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">تکرار رمز عبور جدید</label>
                                    <div x-data="{ showPassword: false }" class="relative">
                                        <input x-bind:type="showPassword ? 'text' : 'password'" wire:model.blur="edit_password_confirmation" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 text-sm leading-5 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100" placeholder="تکرار رمز عبور جدید">
                                        <button type="button" x-on:click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600">
                                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.294 5.225M15 12a3 3 0 00-4.243-4.243M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-900">مرحله ۳ – مدیریت پویا و نقش‌محور دسترسی‌ها</h3>
                                    <p class="mt-1 text-sm text-slate-500">این بخش فقط دسترسی‌های معتبر برای نقش انتخاب‌شده را نمایش می‌دهد.</p>
                                </div>
                                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    <div class="font-black">{{ $roleDefinitions[$edit_access_level]['label'] ?? 'بدون نقش' }}</div>
                                    <div class="mt-1 text-xs text-amber-700">با تغییر نقش پایه، دسترسی‌های ناسازگار حذف و گزینه‌های معتبر جایگزین می‌شوند.</div>
                                </div>
                            </div>

                            @if(count($availableEditPermissionOptions) > 0)
                                <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-3">
                                    @foreach($availableEditPermissionOptions as $permissionKey => $permissionLabel)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" value="{{ $permissionKey }}" wire:model.live="edit_permissions" class="peer sr-only">
                                            <div class="flex min-h-24 flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4 transition peer-checked:border-amber-300 peer-checked:bg-amber-50 peer-checked:ring-4 peer-checked:ring-amber-100">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="text-sm font-bold text-slate-900">{{ $permissionLabel }}</div>
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-400 peer-checked:border-amber-500 peer-checked:text-amber-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.414-1.42l2.293 2.294 6.543-6.544a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="mt-3 text-xs text-slate-500">
                                                    @if($edit_access_level === \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
                                                        دسترسی عملیاتی برای یکی از گیت‌های فرآیند توزیع.
                                                    @elseif($permissionKey === \App\Models\User::PERMISSION_FULL_ACCESS)
                                                        دسترسی کامل برای مدیریت کل سیستم.
                                                    @else
                                                        دسترسی قابل توسعه و کنترل‌شده برای این نقش.
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                    <div class="text-sm font-bold text-slate-800">برای این نقش، دسترسی قابل انتخابی تعریف نشده است.</div>
                                    <div class="mt-1 text-xs text-slate-500">زیرساخت این بخش برای اضافه شدن دسترسی‌های آینده آماده است.</div>
                                </div>
                            @endif

                            @error('edit_permissions') <span class="mt-2 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                            @error('edit_permissions.*') <span class="mt-2 block text-[11px] text-red-600">{{ $message }}</span> @enderror
                        </section>
                    </div>

                    <div class="shrink-0 border-t border-slate-200 bg-white px-4 py-4 sm:px-6">
                        <div class="flex flex-col gap-3 rounded-3xl border border-amber-200 bg-amber-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-black text-amber-900">آماده ذخیره تغییرات</div>
                                <div class="mt-1 text-xs text-amber-700">نقش انتخاب‌شده: {{ $roleDefinitions[$edit_access_level]['label'] ?? '-' }} • دسترسی‌های انتخاب‌شده: {{ count($edit_permissions) }}</div>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100 sm:w-auto">
                                    ذخیره تغییرات
                                </button>
                                <button type="button" wire:click="cancelEditingUser" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:w-auto">
                                    انصراف
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
