<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-sky-700 via-cyan-700 to-teal-600 px-6 py-6 text-white">
            <h1 class="text-2xl font-extrabold">ثبت تحویل خدمت</h1>
            <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                برای خدمت فردی، کد ملی هر مددجو را وارد کنید. برای خدمت خانوار، کد ملی سرپرست را وارد کنید. کنار هر کد
                ملی، مقدار تحویل را مشخص کنید.
            </p>
        </div>

        <div class="px-6 py-6">
            @if (session()->has('success'))
                <div
                    class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-bold">لطفاً خطاهای فرم را بررسی کنید.</p>
                    <ul class="mt-2 list-disc space-y-1 pr-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form wire:submit.prevent="saveDelivery"
                  class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-3.5">

                        <label class="mb-3 block text-sm font-bold text-slate-700">
                            خدمت تخصیص‌یافته
                        </label>

                        <div
                            class="relative"
                            x-data="{
                            open: false,
                            selected: null,
                            selectedText: 'انتخاب خدمت'
                                        }"
                        >
                            {{-- Trigger Button --}}
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex w-full items-center justify-between rounded-2xl border border-slate-300
                                   bg-white px-4 py-3.5 text-right text-sm text-slate-700 shadow-sm
                                   transition active:scale-[0.98]"
                            >
                                <span class="truncate" x-text="selectedText"></span>

                                <svg
                                    class="ms-2 h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                @click.outside="open = false"
                                class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto overscroll-contain
                                rounded-2xl border border-slate-200 bg-white shadow-xl"
                                style="display: none;"
                            >
                                {{-- Default Option --}}
                                <button
                                    type="button"
                                    @click="
                                    selected     = '';
                                    selectedText = 'انتخاب خدمت';
                                    open         = false;
                                    $wire.set('selectedServiceId', '')
                                "
                                    class="flex w-full items-center px-4 py-3.5 text-right text-sm text-slate-400
                                    transition hover:bg-slate-50 active:bg-slate-100"
                                >
                                    انتخاب خدمت
                                </button>

                                {{-- Service Options --}}
                                @foreach ($this->assignedServices as $service)

                                    @php
                                        $remaining = number_format(
                                            $service->remainingAllocationForWorker(
                                                auth()->user()->social_worker_id
                                            ),
                                            2
                                        );
                                        $serviceTypeLabel = $service->service_type === 'family' ? 'خانوادگی' : 'شخصی';
                                    @endphp

                                    <button
                                        type="button"
                                        @click="
                        selected     = '{{ $service->id }}';
                        selectedText = '{{ $service->service_code }} - {{ $service->serviceName?->name }}';
                        open         = false;
                        $wire.set('selectedServiceId', '{{ $service->id }}')
                    "
                                        :class="{ 'bg-blue-50': selected === '{{ $service->id }}' }"
                                        class="flex w-full flex-col gap-0.5 border-t border-slate-100 px-4 py-3.5
                           text-right transition hover:bg-blue-50 active:bg-blue-100"
                                    >
                                        {{-- Row 1: Code & Name --}}
                                        <span class="text-sm font-semibold text-slate-800">
                        {{ $service->service_code }} — {{ $service->serviceName?->name }}
                    </span>

                                        <span class="mt-1 text-xs text-slate-500">
                        {{ $service->description ?: 'بدون توضیحات' }}
                    </span>

                                        {{-- Row 3: Remaining Balance --}}
                                        <span
                                            class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        مانده: {{ $remaining }} - {{$serviceTypeLabel}}
                    </span>
                                    </button>

                                @endforeach
                            </div>
                        </div>

                        {{-- Hidden Input for wire:model --}}
                        <input type="hidden" wire:model="selectedServiceId">

                        {{-- Validation Error --}}
                        @error('selectedServiceId')
                        <p class="mt-2 flex items-center gap-1 text-sm text-rose-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">

                        <h2 class="text-sm font-semibold text-slate-500">سهمیه شما</h2>

                        @if($this->selectedService)

                            <div class="mt-3 flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-slate-700">
                                        {{ $this->selectedService->serviceName?->name }}
                                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500">
        {{ $this->selectedService->service_code }}
    </span>
                                    </p>



                                    <p class="mt-1 text-xs text-slate-400"> نوع: {{$this->selectedServiceTypeLabel }}</p>
                                </div>
                            </div>

                            @if($this->selectedService->description)
                                <p class="mt-2.5 rounded-full text-center border border-slate-50 p-2 text-xs text-slate-500">
                                    {{ $this->selectedService->description }}
                                </p>
                            @endif

                            <div class="mt-3 grid grid-cols-3 divide-x divide-x-reverse divide-slate-100">

                                <div class="px-3 text-center first:pr-0 last:pl-0">
                                    <p class="text-[11px] text-slate-400">تخصیص‌یافته</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-700">
                                        {{ number_format($this->currentAllocation, 2) }}
                                    </p>
                                </div>

                                <div class="px-3 text-center">
                                    <p class="text-[11px] text-slate-400">تحویل‌شده</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-700">
                                        {{ number_format($this->currentDelivered, 2) }}
                                    </p>
                                </div>

                                <div class="px-3 text-center first:pr-0 last:pl-0">
                                    <p class="text-[11px] text-slate-400">باقی‌مانده</p>
                                    <p class="mt-0.5 text-sm font-bold text-emerald-600">
                                        {{ number_format($this->currentRemainingAllocation, 2) }}
                                    </p>
                                </div>

                            </div>



                        @else
                            <p class="mt-3 text-sm text-slate-400">ابتدا یک خدمت انتخاب کنید.</p>
                        @endif

                    </div>

                    @if($serviceSelectionWarning !== '')
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                            {{ $serviceSelectionWarning }}
                        </div>
                    @endif


                    <div class="relative rounded-3xl border border-slate-200 bg-white p-3.5 {{ !$this->selectedService ? 'opacity-60' : '' }}">
                        @if(!$this->selectedService)
                            <button type="button" wire:click="requireServiceSelection"
                                    class="absolute inset-0 z-10 cursor-not-allowed rounded-3xl"
                                    aria-label="Please select a service first"></button>
                        @endif
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">گیرندگان خدمت</h2>
                                <p class="mt-1 text-sm text-slate-500">کد ملی گیرنده را وارد کنید یا نام او را جستجو
                                    کنید، سپس مقدار تحویلی را مشخص کنید.</p>
                            </div>
                            <button type="button" wire:click="addRecipientField"
                                    @disabled(!$this->selectedService)
                                    class="inline-flex items-center rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-bold text-cyan-700">
                                + افزودن گیرنده
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach($recipientEntries as $index => $entry)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_160px_auto] md:items-start">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">کد ملی</label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    maxlength="10"
                                                    wire:model.live.debounce.300ms="recipientEntries.{{ $index }}.national_id"
                                                    wire:focus="setActiveRecipientSearch({{ $index }})"
                                                    @disabled(!$this->selectedService)
                                                    @readonly(!$this->selectedService)
                                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                                    placeholder="{{ $this->selectedService?->service_type === 'family' ? 'کد ملی یا نام و نام خانوادگی سرپرست' : 'کد ملی یا نام و نام خانوادگی مددجو' }}"
                                                    autocomplete="off"
                                                >
                                                @if(!empty($this->recipientSuggestions[$index]) && $this->activeRecipientSearchIndex === $index)
                                                    <div class="absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl">
                                                        @foreach($this->recipientSuggestions[$index] as $suggestion)
                                                            <button
                                                                type="button"
                                                                wire:click="selectRecipientSuggestion({{ $index }}, '{{ $this->selectedService?->service_type === 'family' ? 'guardian' : 'person' }}', {{ $suggestion->id }})"
                                                                class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-right transition hover:bg-cyan-50 last:border-b-0"
                                                            >
                                                                <span>
                                                                    <span class="block text-sm font-bold text-slate-800">
                                                                        {{ trim(($suggestion->first_name ?? '') . ' ' . ($suggestion->last_name ?? '')) ?: '-' }}
                                                                    </span>
                                                                    <span class="mt-1 block text-xs text-slate-500">
                                                                        {{ $this->selectedService?->service_type === 'family' ? 'سرپرست' : 'مددجو' }}
                                                                    </span>
                                                                </span>
                                                                <span class="text-xs font-semibold text-cyan-700">
                                                                    {{ $this->selectedService?->service_type === 'family' ? $suggestion->national_code : $suggestion->national_id }}
                                                                </span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            @error('recipientEntries.' . $index . '.national_id') <p
                                                class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            @if($entry['is_unregistered'] ?? false)
                                                <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                                    {{ $entry['not_found_notice'] ?: 'فردی در سیستم یافت نشد.' }}
                                                </div>

                                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-2 block text-sm font-bold text-slate-700">نام و نام خانوادگی</label>
                                                        <input type="text"
                                                               wire:model.blur="recipientEntries.{{ $index }}.full_name"
                                                               @disabled(!$this->selectedService)
                                                               @readonly(!$this->selectedService)
                                                               class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                                               placeholder="نام و نام خانوادگی را وارد کنید">
                                                        @error('recipientEntries.' . $index . '.full_name') <p
                                                            class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-sm font-bold text-slate-700">موبایل</label>
                                                        <input type="text"
                                                               wire:model.blur="recipientEntries.{{ $index }}.mobile"
                                                               @disabled(!$this->selectedService)
                                                               @readonly(!$this->selectedService)
                                                               class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                                               MAXLENGTH="11"
                                                               placeholder="اختیاری">
                                                        @error('recipientEntries.' . $index . '.mobile') <p
                                                            class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                                    </div>
                                                </div>
                                            @endif
                                            @if($entry['resolved_name'])
                                                <div
                                                    class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                                    <div class="grid gap-2 sm:grid-cols-2">
                                                        <div>
                                                            <p class="text-xs font-semibold text-emerald-700">نام و نام خانوادگی</p>
                                                            <p class="mt-1 font-bold">{{ $entry['resolved_name'] }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-emerald-700">کد ملی</p>
                                                            <p class="mt-1 font-bold">{{ $entry['national_id'] ?: '-' }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-emerald-700">تعداد افراد تحت پوشش</p>
                                                            <p class="mt-1 font-bold">{{ $entry['covered_dependents_count'] ?? 0 }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-emerald-700">تعداد اعضای خانواده</p>
                                                            <p class="mt-1 font-bold">{{ $entry['family_members_count'] ?? 0 }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">مقدار</label>
                                            <input type="number" min="0.01" step="0.01"
                                                   wire:model.blur="recipientEntries.{{ $index }}.quantity"
                                                   @disabled(!$this->selectedService)
                                                   @readonly(!$this->selectedService)
                                                   class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                                   placeholder="0">
                                            @error('recipientEntries.' . $index . '.quantity') <p
                                                class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="pt-8">
                                            @if(count($recipientEntries) > 1)
                                                <button type="button" wire:click="removeRecipientField({{ $index }})"
                                                        @disabled(!$this->selectedService)
                                                        class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                                                    حذف
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative rounded-3xl border border-slate-200 bg-white p-5 {{ !$this->selectedService ? 'opacity-60' : '' }}">
                        @if(!$this->selectedService)
                            <button type="button" wire:click="requireServiceSelection"
                                    class="absolute inset-0 z-10 cursor-not-allowed rounded-3xl"
                                    aria-label="Please select a service first"></button>
                        @endif
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ تحویل</label>
                                <input type="text" dir="ltr" inputmode="numeric" wire:model="deliveredAt"
                                       @disabled(!$this->selectedService)
                                       @readonly(!$this->selectedService)
                                       class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                <p class="mt-1 text-xs text-slate-500">فرمت: 1405/03/16</p>
                                @error('deliveredAt') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت</label>
                                <textarea wire:model.blur="notes" rows="3"
                                          @disabled(!$this->selectedService)
                                          @readonly(!$this->selectedService)
                                          class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                                @error('notes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- Submit Button --}}
                    <button type="submit"
                            @disabled(!$this->selectedService)
                            class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition active:scale-[0.98] hover:bg-emerald-500">
                        ثبت تحویل
                    </button>

                </div>

            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-xl font-black text-slate-800">سوابق تحویل شما</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950 text-white">
                <tr>
                    <th class="px-4 py-4 text-right font-bold">خدمت</th>
                    <th class="px-4 py-4 text-right font-bold">گیرنده</th>
                    <th class="px-4 py-4 text-center font-bold">کد ملی</th>
                    <th class="px-4 py-4 text-center font-bold">مقدار</th>
                    <th class="px-4 py-4 text-center font-bold">ارزش</th>
                    <th class="px-4 py-4 text-center font-bold">تاریخ</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($deliveries as $delivery)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-4 text-slate-700">
                            <p class="font-bold">{{ $delivery->service?->service_code }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $delivery->service?->serviceName?->name }}</p>
                        </td>
                        <td class="px-4 py-4 text-slate-700">{{ $delivery->recipient_name }}</td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ $delivery->recipient_national_id }}</td>
                        <td class="px-4 py-4 text-center font-bold text-slate-800">{{ number_format((float) $delivery->delivered_quantity, 2) }}</td>
                        <td class="px-4 py-4 text-center font-bold text-emerald-700">{{ number_format($delivery->delivered_total_value) }}
                            IRR
                        </td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ optional($delivery->delivered_at)->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">هنوز تحویلی ثبت نکرده‌اید.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
