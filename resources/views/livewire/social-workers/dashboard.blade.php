<div class="space-y-6">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-sky-700 via-cyan-700 to-teal-600 px-6 py-6 text-white">
            <h1 class="text-2xl font-extrabold">ثبت تحویل خدمت</h1>
            <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                برای خدمت فردی، کد ملی هر مددجو را وارد کنید. برای خدمت خانوار، کد ملی سرپرست را وارد کنید. کنار هر کد ملی، مقدار تحویل را مشخص کنید.
            </p>
        </div>

        <div class="px-6 py-6">
            @if (session()->has('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
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

            <form wire:submit.prevent="saveDelivery" class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <label class="mb-2 block text-sm font-bold text-slate-700">خدمت تخصیص‌یافته</label>
                        <select wire:model.live="selectedServiceId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                            <option value="">انتخاب خدمت</option>
                            @foreach($this->assignedServices as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->service_code }} - {{ $service->serviceName?->name }} ({{ number_format($service->remainingAllocationForWorker(auth()->user()->social_worker_id), 2) }} باقی‌مانده برای شما)
                                </option>
                            @endforeach
                        </select>
                        @error('selectedServiceId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">گیرندگان خدمت</h2>
                                <p class="mt-1 text-sm text-slate-500">کد ملی و مقدار تحویلی را برای هر گیرنده وارد کنید.</p>
                            </div>
                            <button type="button" wire:click="addRecipientField" class="inline-flex items-center rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-bold text-cyan-700">
                                + افزودن گیرنده
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach($recipientEntries as $index => $entry)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_160px_auto] md:items-start">
                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">کد ملی</label>
                                            <input type="text" maxlength="10" wire:model.live.debounce.300ms="recipientEntries.{{ $index }}.national_id" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="کد ملی را وارد کنید">
                                            @error('recipientEntries.' . $index . '.national_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                            @if($entry['resolved_name'])
                                                <div class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                                    <p class="font-bold">{{ $entry['resolved_name'] }}</p>
                                                    @if($entry['resolved_meta'])
                                                        <p class="mt-1 text-xs text-emerald-700">{{ $entry['resolved_meta'] }}</p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-slate-700">مقدار</label>
                                            <input type="number" min="0.01" step="0.01" wire:model.blur="recipientEntries.{{ $index }}.quantity" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" placeholder="0">
                                            @error('recipientEntries.' . $index . '.quantity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="pt-8">
                                            @if(count($recipientEntries) > 1)
                                                <button type="button" wire:click="removeRecipientField({{ $index }})" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                                                    حذف
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">تاریخ تحویل</label>
                                <input type="date" wire:model="deliveredAt" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                @error('deliveredAt') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">یادداشت</label>
                                <textarea wire:model.blur="notes" rows="3" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"></textarea>
                                @error('notes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-slate-950 p-5 text-white">
                        <h2 class="text-lg font-bold">سهمیه شما</h2>
                        @if($this->selectedService)
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                    <p class="text-slate-300">خدمت</p>
                                    <p class="mt-1 font-bold">{{ $this->selectedService->serviceName?->name }}</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <p class="text-slate-300">سهمیه تخصیص‌یافته</p>
                                        <p class="mt-1 font-bold">{{ number_format($this->currentAllocation, 2) }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                        <p class="text-slate-300">تحویل‌شده توسط شما</p>
                                        <p class="mt-1 font-bold">{{ number_format($this->currentDelivered, 2) }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 sm:col-span-2">
                                        <p class="text-slate-300">باقی‌مانده سهمیه شما</p>
                                        <p class="mt-1 text-lg font-black">{{ number_format($this->currentRemainingAllocation, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="mt-4 text-sm text-slate-300">ابتدا یک خدمت تخصیص‌یافته را انتخاب کنید.</p>
                        @endif
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
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
                            <td class="px-4 py-4 text-center font-bold text-emerald-700">{{ number_format($delivery->delivered_total_value) }} IRR</td>
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
