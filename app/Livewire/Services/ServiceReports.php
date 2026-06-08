<?php

namespace App\Livewire\Services;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use App\Models\ServiceName;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Morilog\Jalali\CalendarUtils;

class ServiceReports extends Component
{
    public ?int $selectedServiceId = null;
    public string $search = '';
    public string $selectedStatus = 'all';
    public string $selectedCategory = 'all';
    public string $selectedType = 'all';
    public string $selectedServiceName = 'all';
    public string $displayMode = 'list';
    public string $deliverySearch = '';
    public string $selectedDeliveryEntryType = 'all';
    public ?int $editingDeliveryId = null;
    public bool $showEditDeliveryModal = false;
    public string $editRecipientName = '';
    public string $editNationalId = '';
    public string $editMobile = '';
    public string $editDeliveredQuantity = '';
    public string $editDeliveredAt = '';
    public string $editNotes = '';

    public function getFilteredDeliveriesProperty()
    {
        if (! $this->selectedService) {
            return collect();
        }

        $search = trim($this->deliverySearch);
        $entryType = $this->selectedDeliveryEntryType;

        $deliveries = $this->selectedService->deliveries->sortByDesc('delivered_at');

        if ($entryType !== 'all') {
            $deliveries = $deliveries->filter(function ($delivery) use ($entryType) {
                return match ($entryType) {
                    'manual' => ! $delivery->person && ! $delivery->guardian,
                    'individual' => (bool) $delivery->person,
                    'guardian' => ! $delivery->person && (bool) $delivery->guardian,
                    default => true,
                };
            });
        }

        if ($search === '') {
            return $deliveries;
        }

        return $deliveries->filter(function ($delivery) use ($search) {
            $name = trim($delivery->recipient_name ?? '');
            $nationalId = trim($delivery->recipient_national_id ?? '');
            $socialWorker = trim($delivery->socialWorker?->full_name ?? '');
            $creator = trim($delivery->creator?->full_name ?? $delivery->creator?->name ?? '');
            $notes = trim($delivery->notes ?? '');
            $mobile = trim($delivery->mobile ?? '');

            $nameMatch = stripos($name, $search) !== false;
            $nationalIdMatch = stripos($nationalId, $search) !== false;
            $socialWorkerMatch = stripos($socialWorker, $search) !== false;
            $creatorMatch = stripos($creator, $search) !== false;
            $notesMatch = stripos($notes, $search) !== false;
            $mobileMatch = stripos($mobile, $search) !== false;

            return $nameMatch || $nationalIdMatch || $socialWorkerMatch || $creatorMatch || $notesMatch || $mobileMatch;
        });
    }

    public function mount(?int $selectedServiceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedServiceId = $selectedServiceId;
    }

    public function openService(int $serviceId): void
    {
        $serviceExists = Service::query()->whereKey($serviceId)->exists();

        if (! $serviceExists) {
            return;
        }

        $this->selectedServiceId = $serviceId;
        $this->deliverySearch = '';
        $this->selectedDeliveryEntryType = 'all';
        $this->closeEditDeliveryModal();
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report', id: $serviceId);
    }

    public function backToServices(): void
    {
        $this->selectedServiceId = null;
        $this->deliverySearch = '';
        $this->selectedDeliveryEntryType = 'all';
        $this->closeEditDeliveryModal();
        $this->dispatch('open-dashboard-section', section: 'advanced-service-report');
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->with([
                'serviceName',
                'serviceCategory',
                'district',
                'socialWorkers',
                'deliveries.person.guardian',
                'deliveries.guardian',
                'deliveries.socialWorker',
                'deliveries.creator',
            ])
            ->find($this->selectedServiceId);
    }

    public function render()
    {
        $search = trim($this->search);

        $status = ($this->selectedStatus === 'all') ? null : $this->selectedStatus;
        $category = ($this->selectedCategory === 'all') ? null : $this->selectedCategory;
        $type = ($this->selectedType === 'all') ? null : $this->selectedType;
        $serviceName = ($this->selectedServiceName === 'all') ? null : $this->selectedServiceName;

        $categories = ServiceCategory::query()
            ->when($serviceName, fn ($q) => $q->whereHas('serviceName', fn ($sq) => $sq->where('name', $serviceName)))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $serviceNames = ServiceName::query()
            ->ordered()
            ->pluck('name')
            ->toArray();

        return view('livewire.services.service-reports', [
            'services' => Service::query()
                ->with(['serviceName', 'serviceCategory', 'district', 'socialWorkers', 'creator'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($category, fn ($q) => $q->whereHas('serviceCategory', fn ($q) => $q->where('name', $category)))
                ->when($type, fn ($q) => $q->where('service_type', $type))
                ->when($serviceName, fn ($q) => $q->whereHas('serviceName', fn ($q) => $q->where('name', $serviceName)))
                ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                    $inner
                        ->orWhere('service_code', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('serviceName', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('serviceCategory', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%'));
                }))
                ->latest()
                ->get(),
            'selectedService' => $this->selectedService,
            'filteredDeliveries' => $this->filteredDeliveries,
            'statusOptions' => Service::STATUS_OPTIONS,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::UNIT_OPTIONS,
            'categoryOptions' => $categories,
            'serviceNames' => $serviceNames,
            'displayMode' => $this->displayMode,
            'jalaliDateTime' => fn ($dateTime) => $dateTime ? Jalalian::fromDateTime($dateTime)->format('Y/m/d H:i') : '-',
        ]);
    }

    public function editDelivery(int $deliveryId): void
    {
        $delivery = $this->selectedService?->deliveries->firstWhere('id', $deliveryId);

        if (! $delivery) {
            return;
        }

        $this->editingDeliveryId = $delivery->id;
        $this->editRecipientName = (string) ($delivery->full_name ?? '');
        $this->editNationalId = (string) ($delivery->national_id ?? '');
        $this->editMobile = (string) ($delivery->mobile ?? '');
        $this->editDeliveredQuantity = $this->formatDecimal($delivery->delivered_quantity);
        $this->editDeliveredAt = $delivery->delivered_at
            ? Jalalian::fromDateTime($delivery->delivered_at)->format('Y/m/d')
            : Jalalian::fromDateTime(now())->format('Y/m/d');
        $this->editNotes = (string) ($delivery->notes ?? '');
        $this->showEditDeliveryModal = true;
        $this->resetValidation();
    }

    public function saveDeliveryEdits(): void
    {
        $service = $this->selectedService;
        abort_unless($service, 404);

        $delivery = ServiceDelivery::query()
            ->whereKey($this->editingDeliveryId)
            ->where('service_id', $service->id)
            ->first();

        abort_unless($delivery, 404);

        $validated = $this->validate($this->deliveryEditRules(), [], $this->deliveryEditAttributes());

        $newQuantity = (float) $validated['editDeliveredQuantity'];
        $otherDeliveredForService = max(0, (float) $service->quantity_delivered - (float) $delivery->delivered_quantity);

        if (($otherDeliveredForService + $newQuantity) > (float) $service->total_quantity) {
            throw ValidationException::withMessages([
                'editDeliveredQuantity' => 'مقدار تحویل‌شده نمی‌تواند از تعداد کل خدمت بیشتر شود.',
            ]);
        }

        if ($delivery->social_worker_id) {
            $allocated = $service->allocatedQuantityForWorker((int) $delivery->social_worker_id);

            if ($allocated > 0) {
                $otherDeliveredForWorker = max(0, $service->deliveredQuantityForWorker((int) $delivery->social_worker_id) - (float) $delivery->delivered_quantity);

                if (($otherDeliveredForWorker + $newQuantity) > $allocated) {
                    throw ValidationException::withMessages([
                        'editDeliveredQuantity' => 'مقدار جدید از سهمیه تخصیص‌یافته این مددکار بیشتر است.',
                    ]);
                }
            }
        }

        $payload = [
            'mobile' => trim($validated['editMobile']) !== '' ? trim($validated['editMobile']) : null,
            'delivered_quantity' => $newQuantity,
            'delivered_total_value' => (int) round($newQuantity * (int) $delivery->value_per_unit_snapshot),
            'delivered_at' => $this->jalaliToGregorian($validated['editDeliveredAt']),
            'notes' => trim($validated['editNotes']) !== '' ? trim($validated['editNotes']) : null,
        ];

        if ($this->isManualDelivery($delivery)) {
            $payload['full_name'] = trim($validated['editRecipientName']);
            $payload['national_id'] = trim($validated['editNationalId']);
        }

        $delivery->update($payload);

        $this->closeEditDeliveryModal();
    }

    public function closeEditDeliveryModal(): void
    {
        $this->editingDeliveryId = null;
        $this->showEditDeliveryModal = false;
        $this->editRecipientName = '';
        $this->editNationalId = '';
        $this->editMobile = '';
        $this->editDeliveredQuantity = '';
        $this->editDeliveredAt = '';
        $this->editNotes = '';
        $this->resetValidation();
    }

    public function deleteDelivery(int $deliveryId): void
    {
        $delivery = ServiceDelivery::query()
            ->whereKey($deliveryId)
            ->first();

        if ($delivery) {
            if ($this->editingDeliveryId === $delivery->id) {
                $this->closeEditDeliveryModal();
            }

            $delivery->delete();
        }
    }

    public function printReceipt(int $deliveryId): void
    {
        $this->dispatch('open-delivery-receipt', deliveryId: $deliveryId);
    }

    protected function deliveryEditRules(): array
    {
        return [
            'editRecipientName' => [
                $this->currentEditingDeliveryIsManual() ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'editNationalId' => [
                $this->currentEditingDeliveryIsManual() ? 'required' : 'nullable',
                'string',
                'max:20',
            ],
            'editMobile' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'editDeliveredQuantity' => ['required', 'numeric', 'min:0.01'],
            'editDeliveredAt' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidJalaliDate((string) $value)) {
                        $fail('تاریخ تحویل باید به فرمت شمسی معتبر مانند 1405/03/16 وارد شود.');
                    }
                },
            ],
            'editNotes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function deliveryEditAttributes(): array
    {
        return [
            'editRecipientName' => 'نام گیرنده',
            'editNationalId' => 'کد ملی گیرنده',
            'editMobile' => 'موبایل',
            'editDeliveredQuantity' => 'مقدار تحویل',
            'editDeliveredAt' => 'تاریخ تحویل',
            'editNotes' => 'توضیحات',
        ];
    }

    protected function currentEditingDeliveryIsManual(): bool
    {
        $delivery = $this->selectedService?->deliveries->firstWhere('id', $this->editingDeliveryId);

        return $delivery ? $this->isManualDelivery($delivery) : false;
    }

    protected function isManualDelivery(ServiceDelivery $delivery): bool
    {
        return ! $delivery->person_id && ! $delivery->guardian_id;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', trim($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }
}
