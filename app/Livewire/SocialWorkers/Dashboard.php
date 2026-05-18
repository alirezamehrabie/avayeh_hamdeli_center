<?php

namespace App\Livewire\SocialWorkers;

use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.social-worker')]
class Dashboard extends Component
{
    public ?int $selectedServiceId = null;
    public array $recipientEntries = [];
    public string $deliveredAt = '';
    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-social-worker-panel'), 403);
        $this->recipientEntries = [$this->blankEntry()];
    }

    public function updatedSelectedServiceId(): void
    {
        $this->recipientEntries = [$this->blankEntry()];
        $this->resetValidation();
    }

    public function addRecipientField(): void
    {
        $this->recipientEntries[] = $this->blankEntry();
    }

    public function removeRecipientField(int $index): void
    {
        unset($this->recipientEntries[$index]);
        $this->recipientEntries = array_values($this->recipientEntries);

        if ($this->recipientEntries === []) {
            $this->recipientEntries = [$this->blankEntry()];
        }
    }

    public function updatedRecipientEntries($value, string $key): void
    {
        if (! str_ends_with($key, '.national_id')) {
            return;
        }

        [$index] = explode('.', $key);
        $index = (int) $index;
        $nationalId = trim((string) ($this->recipientEntries[$index]['national_id'] ?? ''));
        $this->recipientEntries[$index]['resolved_name'] = '';
        $this->recipientEntries[$index]['resolved_meta'] = '';
        $this->recipientEntries[$index]['person_id'] = null;
        $this->recipientEntries[$index]['guardian_id'] = null;

        if ($nationalId === '' || ! $this->selectedService) {
            return;
        }

        if ($this->selectedService->service_type === 'family') {
            $guardian = Guardian::query()
                ->withCount('people')
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->where('national_code', $nationalId)
                ->first();

            if ($guardian) {
                $this->recipientEntries[$index]['guardian_id'] = $guardian->id;
                $this->recipientEntries[$index]['resolved_name'] = trim(($guardian->first_name ?? '') . ' ' . ($guardian->last_name ?? '')) ?: '-';
                $this->recipientEntries[$index]['resolved_meta'] = $guardian->people_count . ' نفر تحت تکفل';
            }

            return;
        }

        $person = Person::query()
            ->where('national_id', $nationalId)
            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
            ->first();

        if ($person) {
            $this->recipientEntries[$index]['person_id'] = $person->id;
            $this->recipientEntries[$index]['resolved_name'] = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: '-';
            $this->recipientEntries[$index]['resolved_meta'] = 'مددجو';
        }
    }

    public function saveDelivery(): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $service = $this->selectedService;
        abort_unless($service, 404);

        $totalToDeliver = collect($validated['recipientEntries'])->sum(fn ($entry) => (float) $entry['quantity']);

        if ($totalToDeliver > $this->remainingAllocationForCurrentWorker()) {
            $this->addError('recipientEntries', 'جمع مقادیر ثبت‌شده از سهمیه تخصیص‌یافته شما بیشتر است.');
            return;
        }

        DB::transaction(function () use ($service, $validated): void {
            foreach ($validated['recipientEntries'] as $entry) {
                $personId = null;
                $guardianId = null;

                if ($service->service_type === 'family') {
                    $guardian = Guardian::query()
                        ->where('social_worker_id', $this->currentSocialWorkerId())
                        ->where('national_code', trim((string) $entry['national_id']))
                        ->firstOrFail();
                    $guardianId = $guardian->id;
                } else {
                    $person = Person::query()
                        ->where('national_id', trim((string) $entry['national_id']))
                        ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
                        ->firstOrFail();
                    $personId = $person->id;
                }

                ServiceDelivery::query()->create([
                    'service_id' => $service->id,
                    'social_worker_id' => $this->currentSocialWorkerId(),
                    'person_id' => $personId,
                    'guardian_id' => $guardianId,
                    'delivered_quantity' => $entry['quantity'],
                    'value_per_unit_snapshot' => $service->value_per_unit,
                    'delivered_total_value' => (int) round((float) $entry['quantity'] * $service->value_per_unit),
                    'delivered_at' => $validated['deliveredAt'],
                    'notes' => $validated['notes'] ?: null,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        session()->flash('success', 'تحویل خدمات برای گیرندگان ثبت شد.');
        $this->recipientEntries = [$this->blankEntry()];
        $this->reset(['deliveredAt', 'notes']);
    }

    public function getAssignedServicesProperty()
    {
        return Service::query()
            ->with(['serviceName', 'serviceCategory', 'socialWorkers'])
            ->whereHas('socialWorkers', function (Builder $query) {
                $query->where('social_workers.id', $this->currentSocialWorkerId())
                    ->where('service_social_worker.allocated_quantity', '>', 0);
            })
            ->whereIn('status', ['approved', 'in_distribution', 'completed'])
            ->latest()
            ->get();
    }

    public function getSelectedServiceProperty(): ?Service
    {
        if (! $this->selectedServiceId) {
            return null;
        }

        return Service::query()
            ->with(['serviceName', 'serviceCategory', 'socialWorkers'])
            ->whereHas('socialWorkers', function (Builder $query) {
                $query->where('social_workers.id', $this->currentSocialWorkerId())
                    ->where('service_social_worker.allocated_quantity', '>', 0);
            })
            ->find($this->selectedServiceId);
    }

    public function getCurrentAllocationProperty(): float
    {
        $service = $this->selectedService;

        return $service ? $service->allocatedQuantityForWorker($this->currentSocialWorkerId()) : 0;
    }

    public function getCurrentDeliveredProperty(): float
    {
        $service = $this->selectedService;

        return $service ? $service->deliveredQuantityForWorker($this->currentSocialWorkerId()) : 0;
    }

    public function getCurrentRemainingAllocationProperty(): float
    {
        return $this->remainingAllocationForCurrentWorker();
    }

    public function render()
    {
        return view('livewire.social-workers.dashboard', [
            'deliveries' => ServiceDelivery::query()
                ->with(['service.serviceName', 'person', 'guardian'])
                ->where('social_worker_id', $this->currentSocialWorkerId())
                ->latest('delivered_at')
                ->latest('id')
                ->take(25)
                ->get(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'selectedServiceId' => ['required', 'integer', Rule::in($this->assignedServices->pluck('id')->all())],
            'recipientEntries' => ['required', 'array', 'min:1'],
            'recipientEntries.*.national_id' => [
                'required',
                'digits:10',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $service = $this->selectedService;
                    if (! $service) {
                        return;
                    }

                    if ($service->service_type === 'family') {
                        $exists = Guardian::query()
                            ->where('social_worker_id', $this->currentSocialWorkerId())
                            ->where('national_code', trim((string) $value))
                            ->exists();
                    } else {
                        $exists = Person::query()
                            ->where('national_id', trim((string) $value))
                            ->whereHas('guardian', fn (Builder $query) => $query->where('social_worker_id', $this->currentSocialWorkerId()))
                            ->exists();
                    }

                    if (! $exists) {
                        $fail('کد ملی واردشده برای این خدمت و مددکار معتبر نیست.');
                    }
                },
            ],
            'recipientEntries.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'deliveredAt' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'selectedServiceId' => 'خدمت',
            'recipientEntries.*.national_id' => 'کد ملی گیرنده',
            'recipientEntries.*.quantity' => 'مقدار',
            'deliveredAt' => 'تاریخ تحویل',
            'notes' => 'یادداشت',
        ];
    }

    protected function currentSocialWorkerId(): int
    {
        return (int) auth()->user()->social_worker_id;
    }

    protected function blankEntry(): array
    {
        return [
            'national_id' => '',
            'quantity' => '',
            'resolved_name' => '',
            'resolved_meta' => '',
            'person_id' => null,
            'guardian_id' => null,
        ];
    }

    protected function remainingAllocationForCurrentWorker(): float
    {
        $service = $this->selectedService;

        return $service ? $service->remainingAllocationForWorker($this->currentSocialWorkerId()) : 0;
    }
}
