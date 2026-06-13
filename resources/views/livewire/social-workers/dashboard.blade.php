<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-sky-700  to-indigo-600 px-6 py-4 text-white">
            <h1 class="text-2xl font-extrabold">ثبت تحویل خدمت</h1>
            <p class="mt-1 max-w-3xl text-xs text-cyan-50/90">
                تحویل خدمات توسط مددکاران
            </p>
        </div>

        <div class="px-3 py-6">
            <form wire:submit.prevent="saveDelivery"
                  class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
                <div class="space-y-4">
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
                        selectedText = '{{ $service->code }} - {{ $service->serviceName?->name }}';
                        open         = false;
                        $wire.set('selectedServiceId', '{{ $service->id }}')
                    "
                                        :class="{ 'bg-blue-50': selected === '{{ $service->id }}' }"
                                        class="flex w-full flex-col gap-0.5 border-t border-slate-100 px-4 py-3.5
                           text-right transition hover:bg-blue-50 active:bg-blue-100"
                                    >
                                        {{-- Row 1: Code & Name --}}
                                        <span class="text-sm font-semibold text-slate-800">
                        {{ $service->code }} — {{ $service->serviceName?->name }}
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
        {{ $this->selectedService->code }}
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


                    <div class="relative rounded-3xl border border-slate-100 bg-white p-4 shadow-sm {{ !$this->selectedService ? 'opacity-60' : '' }}">
                        @if(!$this->selectedService)
                            <button type="button" wire:click="requireServiceSelection"
                                    class="absolute inset-0 z-10 cursor-not-allowed rounded-3xl"
                                    aria-label="Please select a service first"></button>
                        @endif

                        <!-- Header Section -->
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-800 md:text-lg">گیرندگان خدمت</h2>
                                <p class="mt-0.5 text-[11px] leading-relaxed text-slate-500 md:text-xs">کد ملی را وارد کرده و مقدار را مشخص کنید.</p>
                            </div>
                            <button type="button" wire:click="addRecipientField"
                                    @disabled(!$this->selectedService)
                                    class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-cyan-600 px-3 py-2 text-xs font-bold text-white shadow-sm shadow-cyan-200 active:scale-95 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                افزودن
                            </button>
                        </div>

                        <!-- Recipients List -->
                        <div class="space-y-3">
                            @foreach($recipientEntries as $index => $entry)
                                <div class="relative rounded-2xl border border-slate-100 bg-slate-50/50 p-3 md:p-4 transition-all">

                                    <!-- Remove Button (Top Left for Mobile) -->
                                    @if(count($recipientEntries) > 1)
                                        <button type="button" wire:click="removeRecipientField({{ $index }})"
                                                @disabled(!$this->selectedService)
                                                class="absolute -left-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full border border-rose-100 bg-white text-rose-500 shadow-sm hover:bg-rose-50 md:left-2 md:top-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    @endif

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                                        <!-- National ID Input -->
                                        <div class="md:col-span-8">
                                            <label class="mb-1.5 block text-[11px] font-bold text-slate-500 mr-1">کد ملی یا نام</label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    maxlength="10"
                                                    wire:model.live.debounce.300ms="recipientEntries.{{ $index }}.national_id"
                                                    wire:focus="setActiveRecipientSearch({{ $index }})"
                                                    @disabled(!$this->selectedService)
                                                    class="w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all placeholder:text-slate-400"
                                                    placeholder="درج و جستجوی مددجو / سرپرست"
                                                    autocomplete="off"
                                                >

                                                <!-- Search Suggestions -->
                                                @if(!empty($this->recipientSuggestions[$index]) && $this->activeRecipientSearchIndex === $index)
                                                    <div class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5">
                                                        @foreach($this->recipientSuggestions[$index] as $suggestion)
                                                            <button type="button"
                                                                    wire:click="selectRecipientSuggestion({{ $index }}, '{{ $this->selectedService?->service_type === 'family' ? 'guardian' : 'person' }}', {{ $suggestion->id }})"
                                                                    class="flex w-full items-center justify-between gap-3 border-b border-slate-50 px-4 py-3 text-right hover:bg-slate-50 last:border-b-0"
                                                            >
                                            <span class="block">
                                                <span class="block text-sm font-bold text-slate-800">{{ trim(($suggestion->first_name ?? '') . ' ' . ($suggestion->last_name ?? '')) ?: '-' }}</span>
                                                <span class="text-[10px] text-slate-400">{{ $this->selectedService?->service_type === 'family' ? 'سرپرست' : 'مددجو' }}</span>
                                            </span>
                                                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-mono font-bold text-slate-600">
                                                {{ $this->selectedService?->service_type === 'family' ? $suggestion->national_code : $suggestion->national_id }}
                                            </span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Quantity Input -->
                                        <div class="md:col-span-4">
                                            <label class="mb-1.5 block text-[11px] font-bold text-slate-500 mr-1">مقدار تحویلی</label>
                                            <input type="number" min="0.01" step="0.01"
                                                   wire:model.blur="recipientEntries.{{ $index }}.quantity"
                                                   @disabled(!$this->selectedService)
                                                   class="w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm font-bold text-slate-700 shadow-sm focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all placeholder:text-slate-300"
                                                   placeholder="0">
                                        </div>
                                    </div>

                                    <!-- Errors -->
                                    @error('recipientEntries.' . $index . '.national_id') <p class="mt-1 text-[10px] font-bold text-rose-500 mr-1">{{ $message }}</p> @enderror
                                    @error('recipientEntries.' . $index . '.quantity') <p class="mt-1 text-[10px] font-bold text-rose-500 mr-1">{{ $message }}</p> @enderror

                                    <!-- Unregistered User Fields -->
                                    @if($entry['is_unregistered'] ?? false)
                                        <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50/50 p-3">
                                            <p class="mb-2 text-[11px] font-bold text-amber-700">{{ $entry['not_found_notice'] ?: 'فرد در سیستم یافت نشد.' }}</p>
                                            <div class="grid grid-cols-1 gap-2">
                                                <input type="text" wire:model.blur="recipientEntries.{{ $index }}.full_name" class="rounded-lg border-slate-200 bg-white px-3 py-2 text-xs" placeholder="نام کامل">
                                                <input type="tel" inputmode="numeric" pattern="[0-9]*" oninput="this.value=this.value.replace(/[^0-9]/g,'')" wire:model.blur="recipientEntries.{{ $index }}.mobile" class="rounded-lg border-slate-200 bg-white px-3 py-2 text-xs" placeholder="موبایل" maxlength="11">
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Resolved Info (Result) -->
                                    @if($entry['resolved_name'])
                                        <div class="mt-3 grid grid-cols-2 gap-2 rounded-xl border border-emerald-100 bg-emerald-50/40 p-2.5">
                                            <div class="flex flex-col">
                                                <span class="text-[9px] font-bold text-emerald-600">نام و خانوادگی</span>
                                                <span class="text-xs font-extrabold text-emerald-900">{{ $entry['resolved_name'] }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[9px] font-bold text-emerald-600">اعضای خانواده</span>
                                                <span class="text-xs font-extrabold text-emerald-900">{{ $entry['family_members_count'] ?? 0 }} نفر</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>


                    <div class="relative rounded-3xl border border-slate-200 bg-white p-4 {{ !$this->selectedService ? 'opacity-60' : '' }}">
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

</div>
