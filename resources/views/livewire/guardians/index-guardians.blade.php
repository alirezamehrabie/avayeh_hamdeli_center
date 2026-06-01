<div
    wire:init="refreshStatsOnLoad"
    x-data="{
        showToast: false,
        toastMessage: '',
        toastTimer: null,
        openToast(message) {
            this.toastMessage = message;
            this.showToast = true;

            if (this.toastTimer) {
                clearTimeout(this.toastTimer);
            }

            this.toastTimer = setTimeout(() => {
                this.showToast = false;
            }, 3500);
        }
    }"
    x-on:guardian-stats-refreshed.window="openToast($event.detail.message)"
>
    <div class="container mx-auto p-0">
        <div
            class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/30 to-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">لیست سرپرستان</h1>
                    <p class="mt-1 text-sm text-slate-500">مشاهده سرپرستان و مددجویان تحت نظارت هر سرپرست</p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Stat Card -->
                    <div class="rounded-2xl border border-emerald-100 bg-white/90 px-5 py-3 shadow-sm ring-1 ring-emerald-50 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                        <p class="text-xs font-semibold text-slate-500">تعداد سرپرستان</p>
                        <div class="mt-1 flex items-center justify-center gap-3" dir="ltr">
                            <span class="relative flex h-3 w-3" aria-label="به‌روزرسانی زنده">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-3 w-3 animate-pulse rounded-full bg-emerald-500 shadow-sm shadow-emerald-300"></span>
                            </span>
                            <span class="text-xl font-extrabold tracking-tight text-emerald-600 iranyekan-bold">
                                {{ number_format($totalGuardians) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <!-- Details Button -->
                        <button
                            type="button"
                            wire:click="showHouseholdSizeDetails"
                            title="مشاهده جزئیات"
                            class="group inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-violet-100 bg-white text-violet-600 shadow-sm transition-all duration-200 hover:border-violet-300 hover:bg-violet-50 hover:shadow-md"
                        >
                            <svg class="h-5 w-5 stroke-[1.8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z"/>
                            </svg>
                        </button>

                        <!-- Refresh Button -->
                        <button
                            type="button"
                            wire:click="refreshStats"
                            title="بروزرسانی داده‌ها"
                            class="group inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-sky-100 bg-white text-sky-600 shadow-sm transition-all duration-200 hover:border-sky-300 hover:bg-sky-50 hover:shadow-md"
                        >
                            <svg class="h-5 w-5 stroke-[1.8] transition-transform duration-500 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                        </button>
                    </div>
                </div>


            </div>

            @if (session()->has('success'))
                <div
                    class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-5">
                <label for="guardian-search" class="mb-2 block text-sm font-semibold text-slate-700">جستجوی سریع
                    سرپرستان</label>
                <div class="grid gap-3 md:grid-cols-[minmax(180px,240px)_1fr]">
                    <select
                        id="guardian-search-field"
                        wire:model.live="searchField"
                        class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                        aria-label="معیار جستجو"
                    >
                        <option value="all">همه فیلدها</option>
                        <option value="national_code">کد ملی سرپرست</option>
                        <option value="full_name">نام و نام خانوادگی</option>
                        <option value="mobile">موبایل</option>
                    </select>

                    <div class="relative">
                        <input
                            id="guardian-search"
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            class="w-full rounded-2xl border border-amber-200 bg-white px-10 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            placeholder="عبارت جستجو را وارد کنید..."
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-amber-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35m1.1-5.4a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                            </svg>
                        </span>
                    </div>
                </div>
                @error('search') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-gradient-to-l from-amber-500 to-yellow-400 text-white">
                        <tr>
                            <th class="w-14 px-3 py-4 text-center font-bold">ردیف</th>
                            <th class="px-5 py-4 text-center font-bold">کد ملی سرپرست</th>
                            <th class="px-5 py-4 text-right font-bold">نام و نام خانوادگی</th>
                            <th class="px-5 py-4 text-center font-bold">موبایل</th>
                            <th class="px-5 py-4 text-center font-bold">تعداد مددجویان تحت پوشش</th>
                            <th class="px-5 py-4 text-center font-bold">تعداد نفرات خانواده</th>
                            <th class="w-40 px-3 py-4 text-center font-bold">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($this->guardians as $guardian)
                            <tr wire:key="guardian-row-{{ $guardian->id }}"
                                wire:click="toggleGuardian({{ $guardian->id }})"
                                class="cursor-pointer transition hover:bg-amber-50/70">
                                <td class="px-3 py-4 text-center text-xs font-extrabold text-slate-500">{{ ($this->guardians->firstItem() ?? 1) + $loop->index }}</td>
                                <td class="px-5 py-4 text-center font-medium text-slate-700">{{ $guardian->national_code }}</td>
                                <td class="px-5 py-4 text-right font-light text-slate-800">{{ trim($guardian->first_name . ' ' . $guardian->last_name) }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-700">{{ $guardian->guardian_phone_number ?? '-' }}</td>
                                <td class="px-5 py-4 text-center font-light text-slate-800">{{ $guardian->people_count }}
                                    نفر
                                </td>
                                <td class="px-5 py-4 text-center font-light text-slate-800">{{ (int) ($guardian->children_in_house ?? 0) }}
                                    نفر
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" wire:click.stop="editGuardian({{ $guardian->id }})"
                                                onclick="event.stopPropagation()"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100"
                                                title="ویرایش" aria-label="ویرایش">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536A4 4 0 019.707 17.707L7 18l.293-2.707A4 4 0 018.464 12.536z"/>
                                            </svg>
                                        </button>
                                        <button type="button" wire:click.stop="toggleGuardian({{ $guardian->id }})"
                                                onclick="event.stopPropagation()"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
                                                title="{{ $expandedGuardianId === $guardian->id ? 'بستن' : 'مشاهده مددجویان' }}"
                                                aria-label="{{ $expandedGuardianId === $guardian->id ? 'بستن' : 'مشاهده مددجویان' }}">
                                            @if ($expandedGuardianId === $guardian->id)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            @endif
                                        </button>
                                        <button type="button" wire:click.stop="showHouseholdInfo({{ $guardian->id }})"
                                                onclick="event.stopPropagation()"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100"
                                                title="اطلاعات خانوار" aria-label="اطلاعات خانوار">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                        <button type="button" wire:click.stop="openDeleteModal({{ $guardian->id }})"
                                                onclick="event.stopPropagation()"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                                                title="حذف خانوار" aria-label="حذف خانوار">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0l1 12h6l1-12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            @if($expandedGuardianId === $guardian->id)
                                <tr class="bg-amber-50/40" wire:key="guardian-panel-{{ $guardian->id }}">
                                    <td colspan="7" class="px-5 py-4">
                                        <div
                                            x-data="{ show: false }"
                                            x-init="$nextTick(() => show = true)"
                                            x-show="show"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                                            class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm"
                                        >
                                            <div class="mb-3 flex items-center justify-between">
                                                <h2 class="text-sm font-bold text-slate-700">مددجویان مرتبط
                                                    با {{ trim($guardian->first_name . ' ' . $guardian->last_name) }}</h2>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $guardian->people_count }} مددجوی تحت پوشش</span>
                                                    <span
                                                        class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">{{ (int) ($guardian->children_in_house ?? 0) }} نفرات خانواده</span>
                                                </div>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-50 text-slate-600">
                                                    <tr>
                                                        <th class="px-4 py-3 text-center font-bold">کد مددجو</th>
                                                        <th class="px-4 py-3 text-center font-bold">کد ملی مددجو</th>
                                                        <th class="px-4 py-3 text-right font-bold">نام و نام خانوادگی
                                                        </th>
                                                        <th class="px-4 py-3 text-right font-bold">نام پدر</th>
                                                        <th class="px-4 py-3 text-center font-bold">تاریخ تولد</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                    @forelse($guardian->people as $person)
                                                        <tr class="transition hover:bg-slate-50">
                                                            <td class="px-4 py-3 text-center font-medium text-slate-700">{{ $person->person_code }}</td>
                                                            <td class="px-4 py-3 text-center text-slate-600">{{ $person->national_id }}</td>
                                                            <td class="px-4 py-3 text-right text-slate-700">{{ $person->full_name }}</td>
                                                            <td class="px-4 py-3 text-right text-slate-600">{{ $person->father_name ?? '-' }}</td>
                                                            <td class="px-4 py-3 text-center text-slate-600">{{ $person->birth_date ?? 'نامشخص' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5"
                                                                class="px-4 py-6 text-center text-slate-500">مددجویی
                                                                برای این سرپرست ثبت نشده است.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-5 py-10 text-center text-slate-500">{{ $search ? 'سرپرستی مطابق جستجو پیدا نشد.' : 'هنوز سرپرستی ثبت نشده است.' }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                {{ $this->guardians->links() }}
            </div>
        </div>
    </div>

    <div
        x-cloak
        x-show="showToast"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-y-4 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        class="pointer-events-none fixed bottom-6 right-6 z-[70] w-full max-w-sm px-4"
        style="display: none;"
    >
        <div
            class="pointer-events-auto overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-2xl ring-1 ring-emerald-100">
            <div class="flex items-start gap-3 bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-3 text-white">
                <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-extrabold">به‌روزرسانی آمار</p>
                    <p class="mt-1 text-sm text-white/90" x-text="toastMessage"></p>
                </div>
                <button type="button" @click="showToast = false"
                        class="rounded-full bg-white/10 p-1 text-white transition hover:bg-white/20" aria-label="بستن">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="h-1.5 w-full overflow-hidden bg-emerald-100">
                <div class="h-full bg-emerald-500"
                     style="animation: guardian-toast-progress 3.5s linear forwards;"></div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes guardian-toast-progress {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }
    </style>

    @if($showDeleteModal && $deletingGuardian)
        <div
            wire:key="guardian-delete-modal"
            x-data="{
                open: @js($showDeleteModal),
                close() {
                    this.open = false;
                    setTimeout(() => $wire.closeDeleteModal(), 220);
                }
            }"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @keydown.escape.window="close()"
            style="display: none;"
        >
            <div class="absolute inset-0" @click="close()"></div>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-rose-100 bg-white shadow-2xl"
                @click.stop
            >
                <div
                    class="flex items-start justify-between gap-4 bg-gradient-to-l from-rose-600 to-pink-500 px-6 py-5 text-white">
                    <div>
                        <h2 class="text-xl font-extrabold">حذف خانوار و انتقال به بلاک لیست</h2>
                        <p class="mt-1 text-sm text-white/85">{{ trim($deletingGuardian->first_name . ' ' . $deletingGuardian->last_name) }}</p>
                    </div>
                    <button type="button" @click="close()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25"
                            aria-label="بستن">
                        &times;
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4 text-sm text-slate-700">
                        با این عملیات، سرپرست و <span
                            class="font-extrabold text-rose-700">{{ $deletingGuardian->people_count }}</span> مددجوی
                        مرتبط با این خانواده به‌صورت یک‌جا به بلاک لیست منتقل می‌شوند.
                    </div>

                    <div>
                        <label for="guardian-deletion-reason" class="mb-2 block text-sm font-semibold text-slate-700">علت
                            حذف خانوار <span class="text-rose-600">*</span></label>
                        <textarea
                            id="guardian-deletion-reason"
                            wire:model.defer="deletionReason"
                            rows="4"
                            class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100"
                            placeholder="علت انتقال این خانواده به بلاک لیست را ثبت کنید..."
                        ></textarea>
                        @error('deletionReason') <span
                            class="mt-2 block text-sm font-semibold text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" @click="close()"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            انصراف
                        </button>
                        <button type="button" wire:click="deleteGuardianFamily"
                                class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                            حذف کل خانوار
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($this->selectedGuardian)
        @php
            $selectedGuardian = $this->selectedGuardian;
            $extraHouseholdMembers = is_array($selectedGuardian->extra_household_members ?? null)
                ? $selectedGuardian->extra_household_members
                : [];
            $compositionFormula = $selectedGuardian->household_composition_formula;
            $extraHouseholdMembersCount = (int) ($compositionFormula['non_beneficiaries'] ?? count($extraHouseholdMembers));
            $childrenCount = (int) ($compositionFormula['beneficiaries'] ?? $selectedGuardian->children_count ?? $selectedGuardian->people_count ?? 0);
            $childrenInHouse = (int) ($compositionFormula['final_residents'] ?? $selectedGuardian->children_in_house ?? 0);
            $childrenFromPreviousMarriageApplied = (int) ($compositionFormula['previous_marriage_members'] ?? 0);
            $motherResidentCount = (int) ($compositionFormula['mother'] ?? 0);
            $vehicleOwnershipLabels = [
                'personal' => 'شخصی',
                'company' => 'شراکتی',
                'rented' => 'استیجاری',
            ];
        @endphp

        <div
            wire:key="household-modal"
            x-data="{
            open: @js($showHouseholdModal),
            close() {
                this.open = false;
                setTimeout(() => $wire.closeHouseholdModal(), 220);
            }
        }"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @keydown.escape.window="close()"
            style="display: none;"
        >
            <div class="absolute inset-0" @click="close()"></div>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full max-w-4xl overflow-hidden rounded-3xl border border-amber-100 bg-white shadow-2xl"
                @click.stop
            >
                <div
                    class="flex items-start justify-between gap-4 bg-gradient-to-l from-amber-500 to-yellow-400 px-6 py-5 text-white">
                    <div>
                        <h2 class="text-xl font-extrabold">اطلاعات خانوار</h2>
                        <p class="mt-1 text-sm text-white/85">{{ trim($selectedGuardian->first_name . ' ' . $selectedGuardian->last_name) }}</p>
                    </div>
                    <button type="button" @click="close()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25"
                            aria-label="بستن">
                        &times;
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">کد ملی سرپرست</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->national_code ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">مددکار اختصاص‌یافته</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->socialWorker?->full_name ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">نام سرپرست</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->first_name ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">نام خانوادگی سرپرست</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->last_name ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">تاریخ تولد سرپرست</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->guardian_formatted_birth_date ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">شماره موبایل سرپرست</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->guardian_phone_number ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">دهک اقتصادی خانوار</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->economic_decile ? 'دهک ' . $selectedGuardian->economic_decile : '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">شغل سرپرست</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->occupation?->name ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">وضعیت بیمه و نوع بیمه</p>
                            <p class="mt-1 font-bold text-slate-800">
                                {{ $selectedGuardian->insurance_status ? 'دارد' : 'ندارد' }}
                                @if($selectedGuardian->insurance_status && $selectedGuardian->insuranceType)
                                    - {{ $selectedGuardian->insuranceType->name }}
                                @endif
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">وضعیت مالکیت وسیله نقلیه و نوع وسیله</p>
                            <p class="mt-1 font-bold text-slate-800">
                                {{ $selectedGuardian->has_vehicle ? 'دارد' : 'ندارد' }}
                                @if($selectedGuardian->has_vehicle)
                                    @if($selectedGuardian->vehicleType)
                                        - {{ $selectedGuardian->vehicleType->name }}
                                    @endif
                                    @if($selectedGuardian->vehicle_ownership_type)
                                        ({{ $vehicleOwnershipLabels[$selectedGuardian->vehicle_ownership_type] ?? $selectedGuardian->vehicle_ownership_type }}
                                        )
                                    @endif
                                @endif
                            </p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 md:col-span-2">
                            <p class="text-xs font-semibold text-emerald-700">متوسط درآمد ماهیانه</p>
                            <p class="mt-1 text-xl font-extrabold text-emerald-700"
                               dir="rtl">{{ $selectedGuardian->average_income ? number_format($selectedGuardian->average_income) : '-' }}
                                ریال </p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">وضعیت سکونت</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->residence?->residenceStatus?->name ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold text-slate-500">محدوده سکونت</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->residence?->district?->name ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 md:col-span-2">
                            <p class="text-xs font-semibold text-slate-500">آدرس کامل</p>
                            <p class="mt-1 font-bold text-slate-800">{{ $selectedGuardian->residence?->address ?? '-' }}</p>
                        </div>

                        <div class="rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4 md:col-span-2">
                            <div class="mb-3">
                                <p class="text-sm font-bold text-cyan-800">ترکیب اعضای خانوار</p>
                                <p class="mt-1 text-xs text-slate-600">این بخش مبنای عدد «فرزندان ساکن در منزل» را شفاف
                                    نشان می‌دهد.</p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-5">
                                <div class="rounded-xl border border-cyan-100 bg-white p-3">
                                    <p class="text-[11px] font-semibold text-slate-500">تحت پوشش مرکز</p>
                                    <p class="mt-1 text-lg font-extrabold text-slate-800">{{ $childrenCount }}</p>
                                </div>
                                <div class="rounded-xl border border-violet-100 bg-white p-3">
                                    <p class="text-[11px] font-semibold text-slate-500">ازدواج قبلی</p>
                                    <p class="mt-1 text-lg font-extrabold text-violet-700">{{ $childrenFromPreviousMarriageApplied }}</p>
                                </div>
                                <div class="rounded-xl border border-emerald-100 bg-white p-3">
                                    <p class="text-[11px] font-semibold text-slate-500">افراد غیرمددجو</p>
                                    <p class="mt-1 text-lg font-extrabold text-emerald-700">{{ $extraHouseholdMembersCount }}</p>
                                </div>
                                <div class="rounded-xl border border-rose-100 bg-white p-3">
                                    <p class="text-[11px] font-semibold text-slate-500">مادر</p>
                                    <p class="mt-1 text-lg font-extrabold text-rose-700">{{ $motherResidentCount }}</p>
                                </div>
                                <div class="rounded-xl border border-amber-100 bg-white p-3">
                                    <p class="text-[11px] font-semibold text-slate-500">ساکن در منزل (نهایی)</p>
                                    <p class="mt-1 text-lg font-extrabold text-amber-700">{{ $childrenInHouse }}</p>
                                </div>
                            </div>

                            <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700">
                                <span class="font-semibold">فرمول:</span>
                                <span class="ms-1">{{ $childrenCount }}</span>
                                <span class="mx-1">+</span>
                                <span>{{ $childrenFromPreviousMarriageApplied }}</span>
                                <span class="mx-1">+</span>
                                <span>{{ $extraHouseholdMembersCount }}</span>
                                <span class="mx-1">+</span>
                                <span>{{ $motherResidentCount }}</span>
                                <span class="mx-1">=</span>
                                <span class="font-extrabold text-slate-900">{{ $childrenInHouse }}</span>
                            </div>

                            <div class="mt-3 rounded-xl border border-cyan-100 bg-white p-3">
                                <p class="mb-2 text-xs font-semibold text-slate-600">شرح افراد غیرمددجو ساکن در منزل</p>
                                @if($extraHouseholdMembersCount > 0)
                                    <div class="grid gap-2 md:grid-cols-3">
                                        @foreach($extraHouseholdMembers as $member)
                                            <div
                                                class="rounded-lg border border-slate-100 bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                                                {{ $member['description'] ?? '-' }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-slate-500">برای این خانوار فرد غیرمددجو ثبت نشده است.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showHouseholdSizeModal)
        <div
            x-data="{
                open: @js($showHouseholdSizeModal),
                close() {
                    this.open = false;
                    setTimeout(() => $wire.closeHouseholdSizeModal(), 220);
                }
            }"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @keydown.escape.window="close()"
            style="display: none;"
        >
            <div class="absolute inset-0" @click="close()"></div>
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-2xl"
                @click.stop
            >
                <div
                    class="flex items-start justify-between gap-4 bg-gradient-to-l from-violet-600 to-fuchsia-500 px-6 py-5 text-white">
                    <div>
                        <h2 class="text-xl font-extrabold">جزئیات خانوارها بر اساس اندازه</h2>
                        <p class="mt-1 text-sm text-white/85">تعداد خانوارها در هر اندازه خانوار</p>
                    </div>
                    <button type="button" @click="close()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25"
                            aria-label="بستن">
                        &times;
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto p-6">
                    <div class="mb-4 flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                        <button type="button" wire:click="setHouseholdStatsTab('household_size')"
                                class="flex-1 rounded-xl px-4 py-2 text-sm font-semibold transition {{ $householdStatsTab === 'household_size' ? 'bg-white text-violet-700 shadow-sm' : 'text-slate-600' }}">
                            بر اساس اندازه خانوار
                        </button>
                        <button type="button" wire:click="setHouseholdStatsTab('coverage_count')"
                                class="flex-1 rounded-xl px-4 py-2 text-sm font-semibold transition {{ $householdStatsTab === 'coverage_count' ? 'bg-white text-violet-700 shadow-sm' : 'text-slate-600' }}">
                            بر اساس مددجویان تحت پوشش
                        </button>
                    </div>

                    <div class="space-y-3">
                        @if($householdStatsTab === 'household_size')
                            @forelse($householdSizeStats as $stat)
                                <div class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50">
                                    <button
                                        type="button"
                                        wire:click="toggleHouseholdSize({{ $stat['household_size'] }})"
                                        class="flex w-full items-center justify-between px-4 py-3 text-right transition hover:bg-slate-100"
                                    >
                                        <span class="font-semibold text-slate-700">خانواده {{ $stat['household_size'] }} نفره</span>
                                        <span class="font-bold text-violet-700">{{ number_format($stat['households_count']) }} مورد</span>
                                    </button>

                                    @if($expandedHouseholdSize === $stat['household_size'])
                                        <div class="border-t border-slate-200 bg-white px-4 py-3">
                                            @if(!empty($stat['national_codes']))
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($stat['national_codes'] as $nationalCode)
                                                        <span
                                                            class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $nationalCode }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-slate-500">کد ملی ثبت نشده است.</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div
                                    class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-center text-slate-500">
                                    داده‌ای برای نمایش وجود ندارد.
                                </div>
                            @endforelse
                        @else
                            @forelse($coverageCountStats as $stat)
                                <div class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50">
                                    <button
                                        type="button"
                                        wire:click="toggleCoverageCount({{ $stat['coverage_count'] }})"
                                        class="flex w-full items-center justify-between px-4 py-3 text-right transition hover:bg-slate-100"
                                    >
                                        <span class="font-semibold text-slate-700">خانواده‌هایی با {{ $stat['coverage_count'] }} مددجوی تحت پوشش</span>
                                        <span class="font-bold text-violet-700">{{ number_format($stat['households_count']) }} مورد</span>
                                    </button>

                                    @if($expandedCoverageCount === $stat['coverage_count'])
                                        <div class="border-t border-slate-200 bg-white px-4 py-3">
                                            @if(!empty($stat['national_codes']))
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($stat['national_codes'] as $nationalCode)
                                                        <span
                                                            class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $nationalCode }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-slate-500">کد ملی ثبت نشده است.</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div
                                    class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-center text-slate-500">
                                    داده‌ای برای نمایش وجود ندارد.
                                </div>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
