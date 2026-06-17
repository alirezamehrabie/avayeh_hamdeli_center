@props([
    'subject',
    'subjectLabel',
    'subjectCode',
    'reasonInputId',
    'emptyStateMessage',
])

@if($showQrModal && $subject)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-xl overflow-hidden rounded-3xl border border-cyan-100 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 bg-cyan-700 px-6 py-5 text-white">
                <div>
                    <h2 class="text-xl font-extrabold">کارت QR {{ $subjectLabel }}</h2>
                    <p class="mt-1 text-sm text-white/85">{{ $subject->full_name }} - {{ $subjectCode }}</p>
                </div>
                <button type="button" wire:click="closeQrModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25" aria-label="بستن">&times;</button>
            </div>

            <div class="space-y-4 p-6">
                @if($this->selectedQrIdentity)
                    <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[240px_minmax(0,1fr)] sm:items-center">
                        <div class="flex h-60 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:w-60">
                            <div class="h-[220px] w-[220px] [&>svg]:block [&>svg]:h-full [&>svg]:w-full">
                                {!! $this->selectedQrIdentity->qr_svg !!}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-500">شناسه کارت</p>
                            <p class="mt-1 font-mono text-lg font-black text-slate-900">{{ $this->selectedQrIdentity->public_code }}</p>
                            <p class="mt-3 text-xs font-bold text-slate-500">نشانی اسکن احراز هویت‌شده</p>
                            <p class="mt-1 break-all rounded-xl bg-white px-3 py-2 text-xs text-slate-700">{{ $this->selectedQrIdentity->scan_url }}</p>
                            <p class="mt-3 text-xs text-slate-500">این QR فقط پس از ورود کارکنان قابل استفاده است و شامل اطلاعات شخصی نیست.</p>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $emptyStateMessage }}</div>
                @endif

                @if($issuedQrToken)
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        QR جدید صادر شد. نشانی اسکن نمایش‌داده‌شده برای چاپ به‌روزرسانی شده است.
                    </div>
                @endif

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-6 text-amber-900">
                    این کارت به عنوان کارت هویتی بلندمدت چاپ می‌شود. صدور مجدد یا ابطال فقط در شرایط کنترل‌شده، با دسترسی کامل و ثبت علت مجاز است.
                </div>

                @if($confirmingQrLifecycleAction)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-sm font-black text-rose-800">
                            {{ $qrLifecycleAction === 'reissue' ? 'تایید صدور مجدد QR' : 'تایید ابطال QR' }}
                        </p>
                        <label class="mt-3 block text-xs font-bold text-slate-700" for="{{ $reasonInputId }}">علت اقدام</label>
                        <textarea
                            id="{{ $reasonInputId }}"
                            wire:model.defer="qrLifecycleReason"
                            rows="3"
                            class="mt-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-4 focus:ring-rose-100"
                            placeholder="علت دقیق ابطال یا صدور مجدد کارت را ثبت کنید..."
                        ></textarea>
                        @error('qrLifecycleReason') <p class="mt-1 text-xs font-bold text-rose-700">{{ $message }}</p> @enderror
                        <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <button type="button" wire:click="cancelQrLifecycleAction" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700">انصراف</button>
                            <button type="button" wire:click="confirmQrLifecycleAction" class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-bold text-white">تایید نهایی</button>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    @can('full-access')
                        <button type="button" wire:click="requestQrLifecycleAction('revoke')" @disabled(!$this->selectedQrIdentity) class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 disabled:opacity-50">ابطال کنترل‌شده</button>
                        <button type="button" wire:click="requestQrLifecycleAction('reissue')" class="rounded-2xl bg-cyan-700 px-4 py-2 text-sm font-bold text-white">صدور مجدد کنترل‌شده</button>
                    @endcan
                    <button type="button" wire:click="closeQrModal" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700">بستن</button>
                </div>
            </div>
        </div>
    </div>
@endif
