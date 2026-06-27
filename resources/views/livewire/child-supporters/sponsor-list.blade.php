<div id="sponsor-list-top" class="{{ $embedded ? '' : 'mx-auto max-w-5xl' }}" dir="rtl">
    <div class="space-y-4 pb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600">حامی کودک</p>
                    <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">لیست حامیان</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">فهرست حامیان ثبت‌شده، مبلغ واریزی ماهیانه و تعداد کودکان مددجوی اختصاص‌یافته.</p>
                </div>

                <div class="inline-flex w-fit items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">
                    {{ $this->persianNumber(number_format($sponsors->total())) }} حامی
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            @if (session()->has('success'))
                <div class="mb-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-3 grid gap-2 rounded-xl border border-slate-100 bg-slate-50/80 p-2.5 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_9rem] lg:items-end">
                <div>
                    <label for="sponsor-search" class="mb-1.5 block text-xs font-bold text-slate-500">جستجو</label>
                    <input
                        id="sponsor-search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="کد حامی، نام، نام خانوادگی یا موبایل"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                    >
                </div>

                <div>
                    <label for="sponsor-sort" class="mb-1.5 block text-xs font-bold text-slate-500">مرتب‌سازی</label>
                    <select
                        id="sponsor-sort"
                        wire:model.live="sort"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                    >
                        <option value="latest">جدیدترین</option>
                        <option value="name_asc">نام حامی</option>
                        <option value="donation_desc">بیشترین مبلغ</option>
                        <option value="donation_asc">کمترین مبلغ</option>
                        <option value="beneficiaries_desc">بیشترین مددجو</option>
                        <option value="beneficiaries_asc">کمترین مددجو</option>
                    </select>
                </div>

                <div>
                    <label for="sponsor-per-page" class="mb-1.5 block text-xs font-bold text-slate-500">نمایش</label>
                    <select
                        id="sponsor-per-page"
                        wire:model.live="perPage"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                    >
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $this->persianNumber($option) }} مورد</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mt-0 space-y-2 md:hidden">
                @forelse($sponsors as $sponsor)
                    <article wire:key="sponsor-row-{{ $sponsor->id }}" class="rounded-2xl border border-slate-200 bg-white p-3 transition hover:border-slate-300 hover:bg-slate-50/60 sm:p-4 md:grid md:grid-cols-[0.75fr_1fr_1fr_1fr_1.1fr_0.9fr_auto] md:items-center md:gap-3">
                        <div class="md:hidden">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-900">{{ trim(($sponsor->user?->first_name ?? '') . ' ' . ($sponsor->user?->last_name ?? '')) ?: '-' }}</p>
                                    <p class="mt-1 inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600" dir="ltr">{{ $sponsor->supporter_code ?: '-' }}</p>
                                </div>
                                <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">
                                    {{ $this->persianNumber((int) $sponsor->beneficiaries_count) }} نفر
                                </span>
                            </div>

                            <div class="mt-3 grid gap-2 text-sm text-slate-600">
                                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                    <span class="text-xs font-bold text-slate-400">موبایل</span>
                                    <span class="text-sm font-semibold text-slate-700">{{ $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                    <span class="text-xs font-bold text-slate-400">مبلغ ماهیانه</span>
                                    <span class="text-sm font-black text-emerald-700">{{ $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) }} ریال</span>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center gap-2">
                                <button type="button" title="جزئیات" wire:click="showDetails({{ $sponsor->id }})" wire:loading.attr="disabled" wire:target="showDetails({{ $sponsor->id }})" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border border-indigo-100 bg-indigo-50 px-3 text-sm font-black text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-70">
                                    <i class="bi bi-eye text-base" aria-hidden="true"></i>
                                    <span wire:loading.remove wire:target="showDetails({{ $sponsor->id }})">جزئیات</span>
                                    <span wire:loading wire:target="showDetails({{ $sponsor->id }})">...</span>
                                </button>
                                @if($embedded)
                                    <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'child-supporter-sponsor-edit', id: {{ $sponsor->id }} })" title="ویرایش" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                        <span class="sr-only">ویرایش</span>
                                        <i class="bi bi-pencil-square text-base" aria-hidden="true"></i>
                                    </button>
                                @else
                                    <a href="{{ route('admin.child-supporters.sponsor-registration', ['sponsor' => $sponsor->id]) }}" title="ویرایش" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                        <span class="sr-only">ویرایش</span>
                                        <i class="bi bi-pencil-square text-base" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="hidden items-center justify-between gap-3 md:block">
                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-black text-slate-600" dir="ltr">{{ $sponsor->supporter_code ?: '-' }}</span>
                        </div>

                        <div class="hidden md:block">
                            <span class="truncate text-sm font-bold text-slate-800">{{ $sponsor->user?->first_name ?: '-' }}</span>
                        </div>

                        <div class="hidden md:block">
                            <span class="truncate text-sm font-bold text-slate-800">{{ $sponsor->user?->last_name ?: '-' }}</span>
                        </div>

                        <div class="hidden md:block">
                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-700">{{ $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-') }}</span>
                        </div>

                        <div class="hidden md:block">
                            <div class="min-w-0 text-left md:text-right">
                                <span class="block text-sm font-black text-emerald-700">{{ $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) }} ریال</span>
                                <span class="mt-0.5 block truncate text-[11px] font-semibold leading-5 text-slate-500">{{ $this->donationAmountInTomanWords((int) $sponsor->monthly_donation_amount) }}</span>
                            </div>
                        </div>

                        <div class="hidden md:block">
                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-black text-slate-600">
                                {{ $this->persianNumber((int) $sponsor->beneficiaries_count) }} نفر
                            </span>
                        </div>

                        <div class="hidden gap-1 md:mt-0 md:flex">
                            <button type="button" title="جزئیات" wire:click="showDetails({{ $sponsor->id }})" wire:loading.attr="disabled" wire:target="showDetails({{ $sponsor->id }})" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-indigo-600 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-70">
                                <span class="sr-only">جزئیات</span>
                                <i class="bi bi-eye text-base" aria-hidden="true" wire:loading.remove wire:target="showDetails({{ $sponsor->id }})"></i>
                                <span wire:loading wire:target="showDetails({{ $sponsor->id }})">...</span>
                            </button>
                            @if($embedded)
                                <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'child-supporter-sponsor-edit', id: {{ $sponsor->id }} })" title="ویرایش" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                    <span class="sr-only">ویرایش</span>
                                    <i class="bi bi-pencil-square text-base" aria-hidden="true"></i>
                                </button>
                            @else
                                <a href="{{ route('admin.child-supporters.sponsor-registration', ['sponsor' => $sponsor->id]) }}" title="ویرایش" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                    <span class="sr-only">ویرایش</span>
                                    <i class="bi bi-pencil-square text-base" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                        <p class="text-sm font-bold text-slate-600">هنوز حامی‌ای ثبت نشده است.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-3 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white md:block">
                <table class="min-w-full table-fixed divide-y divide-slate-100">
                    <thead class="sticky top-0 z-10 bg-slate-50">
                        <tr>
                            <th scope="col" class="w-28 px-4 py-3 text-right text-xs font-black text-slate-500">کد حامی</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-black text-slate-500">نام حامی</th>
                            <th scope="col" class="w-36 px-4 py-3 text-right text-xs font-black text-slate-500">موبایل</th>
                            <th scope="col" class="w-48 px-4 py-3 text-left text-xs font-black text-slate-500">مبلغ ماهیانه</th>
                            <th scope="col" class="w-24 px-4 py-3 text-center text-xs font-black text-slate-500">مددجویان</th>
                            <th scope="col" class="w-24 px-4 py-3 text-center text-xs font-black text-slate-500">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($sponsors as $sponsor)
                            <tr wire:key="sponsor-table-row-{{ $sponsor->id }}" class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span class="text-sm font-bold text-slate-600" dir="ltr">{{ $sponsor->supporter_code ?: '-' }}</span>
                                </td>
                                <td class="min-w-0 px-4 py-3.5">
                                    <span class="block truncate text-sm font-bold text-slate-900">{{ trim(($sponsor->user?->first_name ?? '') . ' ' . ($sponsor->user?->last_name ?? '')) ?: '-' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span class="text-sm font-semibold text-slate-700" dir="ltr">{{ $this->persianNumber($sponsor->user?->mobile ?: $sponsor->user?->name ?: '-') }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-left">
                                    <div class="min-w-0" dir="rtl">
                                        <span class="block whitespace-nowrap text-sm font-black text-emerald-700">{{ $this->persianNumber(number_format((int) $sponsor->monthly_donation_amount)) }} ریال</span>
                                        <span class="mt-0.5 block truncate text-[11px] font-semibold leading-5 text-slate-500">{{ $this->donationAmountInTomanWords((int) $sponsor->monthly_donation_amount) }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                    <span class="text-sm font-black text-slate-700">
                                        {{ $this->persianNumber((int) $sponsor->beneficiaries_count) }} نفر
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <div class="flex justify-center gap-1">
                                        <button type="button" title="جزئیات" wire:click="showDetails({{ $sponsor->id }})" wire:loading.attr="disabled" wire:target="showDetails({{ $sponsor->id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-indigo-600 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-70">
                                            <span class="sr-only">جزئیات</span>
                                            <i class="bi bi-eye text-base" aria-hidden="true" wire:loading.remove wire:target="showDetails({{ $sponsor->id }})"></i>
                                            <span wire:loading wire:target="showDetails({{ $sponsor->id }})">...</span>
                                        </button>
                                        @if($embedded)
                                            <button type="button" wire:click="$dispatch('open-dashboard-section', { section: 'child-supporter-sponsor-edit', id: {{ $sponsor->id }} })" title="ویرایش" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                                <span class="sr-only">ویرایش</span>
                                                <i class="bi bi-pencil-square text-base" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('admin.child-supporters.sponsor-registration', ['sponsor' => $sponsor->id]) }}" title="ویرایش" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                                <span class="sr-only">ویرایش</span>
                                                <i class="bi bi-pencil-square text-base" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center">
                                    <p class="text-sm font-bold text-slate-600">هنوز حامی‌ای ثبت نشده است.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sponsors->hasPages())
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-col gap-1 text-xs font-bold text-slate-500 sm:text-sm">
                            <span>
                                نمایش {{ $this->persianNumber($sponsors->firstItem() ?? 0) }}
                                تا {{ $this->persianNumber($sponsors->lastItem() ?? 0) }}
                                از {{ $this->persianNumber($sponsors->total()) }} حامی
                            </span>
                            <span>
                                صفحه {{ $this->persianNumber($sponsors->currentPage()) }}
                                از {{ $this->persianNumber($sponsors->lastPage()) }}
                                • {{ $this->persianNumber($perPage) }} مورد در هر صفحه
                            </span>
                        </div>

                        <div class="text-xs font-bold text-slate-500">
                            جستجو، مرتب‌سازی و تعداد نمایش در آدرس صفحه حفظ می‌شود.
                        </div>
                    </div>

                    <div class="mt-3">
                        {{ $sponsors->onEachSide(1)->links('vendor.livewire.tailwind-mobile-persian', ['scrollTo' => '#sponsor-list-top']) }}
                    </div>
                </div>
            @endif
        </section>
    </div>

    @if($selectedSponsor)
        <div
            x-data="{
                open: true,
                lastFocused: null,
                focusables: [],
                refreshFocusables() {
                    this.focusables = Array.from(this.$el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex=&quot;-1&quot;])'))
                        .filter((element) => !element.hasAttribute('disabled'));
                },
                trap(event) {
                    this.refreshFocusables();
                    if (this.focusables.length === 0) {
                        return;
                    }

                    const first = this.focusables[0];
                    const last = this.focusables[this.focusables.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
                init() {
                    this.lastFocused = document.activeElement;
                    document.body.classList.add('overflow-hidden');

                    this.$nextTick(() => {
                        this.refreshFocusables();
                        this.focusables[0]?.focus();
                    });
                },
                destroy() {
                    document.body.classList.remove('overflow-hidden');
                    this.lastFocused?.focus?.();
                }
            }"
            x-init="init(); return () => destroy()"
            x-on:keydown.escape.window.prevent="$wire.closeDetails()"
            x-on:keydown.tab.prevent="trap($event)"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/35 p-2 sm:items-center sm:p-4"
            wire:click.self="closeDetails"
        >
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-4 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="selected-sponsor-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-indigo-600">جزئیات حامی</p>
                        <h2 id="selected-sponsor-title" class="mt-1 text-lg font-black text-slate-900">{{ $selectedSponsor['fullName'] }}</h2>
                    </div>
                    <button type="button" wire:click="closeDetails" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition hover:bg-slate-200">
                        <span class="sr-only">بستن</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 5L15 15M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                    <div class="mt-4 max-h-[70vh] space-y-4 overflow-y-auto pr-1">
                        <section>
                            <h3 class="text-xs font-black text-slate-400">اطلاعات حامی</h3>
                            <div class="mt-2 grid gap-3 sm:grid-cols-3">
                                <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="block text-xs font-bold text-slate-400">کد حامی</span>
                                    <span class="mt-1 block truncate text-sm font-black text-slate-700" dir="ltr">{{ $selectedSponsor['supporterCode'] ?? '-' }}</span>
                                </div>
                                <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="block text-xs font-bold text-slate-400">شماره موبایل</span>
                                    <span class="mt-1 block truncate text-sm font-black text-slate-800" dir="ltr">{{ $selectedSponsor['mobile'] }}</span>
                                </div>
                                <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="block text-xs font-bold text-slate-400">مبلغ ماهیانه</span>
                                    <span class="mt-1 block truncate text-sm font-black text-emerald-700">{{ $selectedSponsor['monthlyDonationAmount'] }}</span>
                                </div>
                            </div>
                            <p class="mt-2 truncate text-xs font-semibold leading-5 text-slate-500">{{ $selectedSponsor['monthlyDonationAmountInWords'] }}</p>
                        </section>

                        <section class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <h3 class="text-xs font-black text-slate-400">روش‌های یادآوری</h3>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse($selectedSponsor['reminderMethods'] as $method)
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $method }}</span>
                                    @empty
                                        <span class="text-sm font-semibold text-slate-500">-</span>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-black text-slate-400">مشخصات خاص کودک</h3>
                                <p class="mt-2 max-h-24 overflow-y-auto rounded-xl bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-700">{{ $selectedSponsor['childPreferences'] }}</p>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-xs font-black text-slate-400">مددجویان اختصاص‌یافته</h3>
                                <span class="text-xs font-black text-slate-600">
                                    {{ $this->persianNumber((int) ($selectedSponsor['beneficiariesCount'] ?? 0)) }} کودک مددجو
                                </span>
                            </div>

                            <div class="mt-3 space-y-2">
                                @forelse($selectedSponsor['beneficiaries'] ?? [] as $beneficiary)
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $beneficiary['full_name'] }}</p>
                                            <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiary['person_code'] }}</p>
                                        </div>
                                        <button type="button" wire:click="removeBeneficiaryFromSelectedSponsor({{ $beneficiary['id'] }})" class="shrink-0 rounded-md px-2 py-1 text-xs font-black text-rose-600 transition hover:bg-rose-50">حذف</button>
                                    </div>
                                @empty
                                    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-sm font-semibold text-slate-500">هیچ مددجویی اختصاص داده نشده است.</p>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <h3 class="text-xs font-black text-slate-500">افزودن مددجو</h3>
                            <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                                <input type="text" wire:model.blur="beneficiaryCode" dir="ltr" placeholder="کد مددجو" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-left text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                                <button type="button" wire:click="lookupBeneficiary" wire:loading.attr="disabled" wire:target="lookupBeneficiary" class="h-10 rounded-lg border border-indigo-100 bg-white px-3 text-xs font-black text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-70">
                                    <span wire:loading.remove wire:target="lookupBeneficiary">بررسی</span>
                                    <span wire:loading wire:target="lookupBeneficiary">در حال بررسی...</span>
                                </button>
                                <button type="button" wire:click="addBeneficiaryToSelectedSponsor" wire:loading.attr="disabled" wire:target="addBeneficiaryToSelectedSponsor" class="h-10 rounded-lg bg-teal-600 px-3 text-xs font-black text-white transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-70">
                                    <span wire:loading.remove wire:target="addBeneficiaryToSelectedSponsor">افزودن</span>
                                    <span wire:loading wire:target="addBeneficiaryToSelectedSponsor">در حال افزودن...</span>
                                </button>
                            </div>
                            @error('beneficiaryCode') <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror

                            @if($beneficiaryPreview)
                                <div class="mt-3 rounded-lg border border-slate-200 bg-white p-2.5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $beneficiaryPreview['full_name'] }}</p>
                                            <p class="text-xs font-semibold text-slate-500" dir="ltr">{{ $beneficiaryPreview['person_code'] }}</p>
                                        </div>
                                        <span class="shrink-0 text-xs font-bold text-slate-600">{{ $beneficiaryPreview['supporters_count'] }} حامی فعلی</span>
                                    </div>
                                    @if($beneficiaryPreview['supporters_count'] > 0)
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($beneficiaryPreview['supporters'] as $supporter)
                                                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $supporter['full_name'] }} - {{ $supporter['supporter_code'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    </div>
            </div>
        </div>
    @endif
</div>
