<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class ServiceList extends Component
{
    public ?int $editingServiceId = null;

    public string $serviceName = '';

    public ?int $selectedServiceCategoryId = null;

    public string $description = '';

    public string $totalQuantity = '';

    public string $serviceUnit = 'package';

    public string $distributionDate = '';

    public ?int $socialWorkerId = null;

    public string $socialWorkerQuery = '';

    public bool $showSocialWorkerSuggestions = false;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);
    }

    public function startEditing(int $serviceId): void
    {
        $service = $this->operatorServicesQuery()->with('socialWorkers')->findOrFail($serviceId);
        abort_unless(auth()->user()?->can('view-distribution-operator-service', $service), 403);

        $this->editingServiceId = $service->id;
        $this->serviceName = (string) ($service->serviceName?->name ?? '');
        $this->selectedServiceCategoryId = $service->service_category_id;
        $this->description = (string) $service->description;
        $this->totalQuantity = $this->formatDecimal($service->total_quantity);
        $this->serviceUnit = $service->service_unit;
        $this->distributionDate = Jalalian::fromDateTime($service->distribution_start_date)->format('Y/m/d');

        $worker = $service->socialWorkers->first();
        $this->socialWorkerId = $worker?->id;
        $this->socialWorkerQuery = $worker ? trim($worker->full_name . ' - کد ' . $worker->worker_code) : '';
        $this->showSocialWorkerSuggestions = false;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->reset([
            'editingServiceId',
            'serviceName',
            'selectedServiceCategoryId',
            'description',
            'totalQuantity',
            'serviceUnit',
            'distributionDate',
            'socialWorkerId',
            'socialWorkerQuery',
            'showSocialWorkerSuggestions',
        ]);

        $this->serviceUnit = 'package';
        $this->resetValidation();
    }

    public function updatedSocialWorkerQuery(): void
    {
        $this->socialWorkerId = null;
        $this->showSocialWorkerSuggestions = mb_strlen(trim($this->socialWorkerQuery)) >= 2;
    }

    public function activateSocialWorkerSearch(): void
    {
        $this->showSocialWorkerSuggestions = mb_strlen(trim($this->socialWorkerQuery)) >= 2;
    }

    public function selectSocialWorker(int $socialWorkerId): void
    {
        $worker = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->findOrFail($socialWorkerId);

        $this->socialWorkerId = $worker->id;
        $this->socialWorkerQuery = trim($worker->full_name . ' - کد ' . $worker->worker_code);
        $this->showSocialWorkerSuggestions = false;
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->socialWorkerId = null;
        $this->socialWorkerQuery = '';
        $this->showSocialWorkerSuggestions = false;
    }

    public function updateService(): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        $service = $this->operatorServicesQuery()->with('socialWorkers')->findOrFail($this->editingServiceId);
        abort_unless(auth()->user()?->can('view-distribution-operator-service', $service), 403);

        DB::transaction(function () use ($service, $validated): void {
            $service->update([
                'service_name_id' => $this->resolveServiceNameId($validated['serviceName']),
                'service_category_id' => $validated['selectedServiceCategoryId'] ?: $this->undefinedCategoryId(),
                'description' => trim($validated['description']),
                'total_quantity' => $validated['totalQuantity'],
                'service_unit' => $validated['serviceUnit'],
                'distribution_start_date' => $this->jalaliToGregorian($validated['distributionDate']),
                'distribution_end_date' => $this->jalaliToGregorian($validated['distributionDate']),
            ]);

            $service->socialWorkers()->sync([
                $validated['socialWorkerId'] => ['allocated_quantity' => $validated['totalQuantity']],
            ]);
        });

        session()->flash('success', 'خدمت با موفقیت به‌روزرسانی شد.');
        $this->cancelEditing();
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-list', [
            'services' => $this->operatorServicesQuery()
                ->with(['serviceName', 'serviceCategory', 'socialWorkers'])
                ->latest()
                ->get(),
            'serviceCategories' => ServiceCategory::query()->orderBy('name')->get(['id', 'name']),
            'socialWorkerSuggestions' => $this->socialWorkerSuggestions(),
            'unitOptions' => Service::UNIT_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'serviceName' => ['required', 'string', 'max:255'],
            'selectedServiceCategoryId' => ['nullable', 'integer', 'exists:service_categories,id'],
            'description' => ['required', 'string', 'max:5000'],
            'totalQuantity' => ['required', 'numeric', 'min:0.01'],
            'serviceUnit' => ['required', Rule::in(array_keys(Service::UNIT_OPTIONS))],
            'distributionDate' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $parts = explode('/', trim((string) $value));

                if (count($parts) !== 3) {
                    $fail('تاریخ واردشده معتبر نیست.');

                    return;
                }

                [$year, $month, $day] = array_map('intval', $parts);

                if (! \App\Helpers\Morilog\CalendarUtils::isValidateJalaliDate($year, $month, $day)) {
                    $fail('تاریخ واردشده معتبر نیست.');
                }
            }],
            'socialWorkerId' => ['required', 'integer', 'exists:social_workers,id'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'serviceName' => 'نام خدمت',
            'selectedServiceCategoryId' => 'دسته‌بندی',
            'description' => 'توضیحات',
            'totalQuantity' => 'تعداد کل',
            'serviceUnit' => 'واحد',
            'distributionDate' => 'تاریخ',
            'socialWorkerId' => 'مددکار',
        ];
    }

    protected function operatorServicesQuery(): Builder
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        return Service::query()
            ->where('created_by', auth()->id())
            ->whereHas('creator', function (Builder $query): void {
                $query->where('access_level', \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
            });
    }

    protected function undefinedCategoryId(): int
    {
        return (int) ServiceCategory::query()->where('name', 'Undefined')->value('id');
    }

    protected function resolveServiceNameId(string $name): int
    {
        return ServiceName::query()->firstOrCreate(
            ['name' => trim($name)],
            ['created_by' => auth()->id()]
        )->id;
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    protected function socialWorkerSuggestions()
    {
        $query = trim($this->socialWorkerQuery);

        if (! $this->showSocialWorkerSuggestions || mb_strlen($query) < 2) {
            return collect();
        }

        return SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->where(function (Builder $workerQuery) use ($query): void {
                $workerQuery->where('first_name', 'like', $query . '%')
                    ->orWhere('last_name', 'like', $query . '%')
                    ->orWhere('worker_code', 'like', $query . '%')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query . '%'])
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%' . $query . '%']);
            })
            ->orderBy('worker_code')
            ->limit(6)
            ->get();
    }
}
