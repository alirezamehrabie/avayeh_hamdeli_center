<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ServiceBatchCreator extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $serviceBlocks = [];

    public string $socialWorkerSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $this->serviceBlocks = [$this->makeBlock()];
    }

    public function addBlock(): void
    {
        $this->serviceBlocks[] = $this->makeBlock();
    }

    public function removeBlock(int $index): void
    {
        if (count($this->serviceBlocks) === 1) {
            return;
        }

        unset($this->serviceBlocks[$index]);
        $this->serviceBlocks = array_values($this->serviceBlocks);
        $this->refreshPreviewCodes();
    }

    public function saveBatch(): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function () use ($validated): void {
            foreach ($validated['serviceBlocks'] as $index => $block) {
                $nameId = $this->resolveServiceNameId($block['service_name'] ?? null);
                $categoryId = $this->resolveServiceCategoryId($block['category'] ?? null);
                $serviceCode = Service::generateNextCode();

                $service = Service::query()->create([
                    'service_code' => $serviceCode,
                    'service_name_id' => $nameId,
                    'service_category_id' => $categoryId,
                    'service_type' => 'individual',
                    'description' => trim((string) $block['description']),
                    'total_quantity' => $block['total_quantity'],
                    'service_unit' => $block['unit'],
                    'value_per_unit' => 0,
                    'total_service_value' => 0,
                    'district_id' => null,
                    'distribution_start_date' => $this->jalaliToGregorian($block['date']),
                    'distribution_end_date' => $this->jalaliToGregorian($block['date']),
                    'priority' => null,
                    'status' => 'in_distribution',
                    'status_notes' => 'ایجادشده توسط اپراتور توزیع و تخصیص مستقیم به مددکار.',
                    'quantity_delivered' => 0,
                    'created_by' => auth()->id(),
                ]);

                $service->socialWorkers()->attach($block['social_worker_id'], [
                    'allocated_quantity' => $block['total_quantity'],
                ]);

                $this->serviceBlocks[$index]['service_id_preview'] = $serviceCode;
            }
        });

        $count = count($validated['serviceBlocks']);
        $this->resetValidation();
        $this->serviceBlocks = [$this->makeBlock()];

        session()->flash('success', "{$count} خدمت برای توزیع ثبت و به مددکاران تخصیص داده شد.");
    }

    public function getAvailableSocialWorkersProperty()
    {
        $query = SocialWorker::query()->select(['id', 'first_name', 'last_name', 'worker_code']);

        if (trim($this->socialWorkerSearch) !== '') {
            $query->autocompleteSearch($this->socialWorkerSearch);
        }

        return $query->orderBy('first_name')->orderBy('last_name')->limit(50)->get();
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-batch-creator', [
            'socialWorkers' => $this->availableSocialWorkers,
            'unitOptions' => Service::UNIT_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'serviceBlocks' => ['required', 'array', 'min:1'],
            'serviceBlocks.*.service_name' => ['nullable', 'string', 'max:255'],
            'serviceBlocks.*.category' => ['nullable', 'string', 'max:255'],
            'serviceBlocks.*.description' => ['required', 'string', 'max:5000'],
            'serviceBlocks.*.total_quantity' => ['required', 'numeric', 'min:0.01'],
            'serviceBlocks.*.unit' => ['required', Rule::in(array_keys(Service::UNIT_OPTIONS))],
            'serviceBlocks.*.date' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isValidJalaliDate((string) $value)) {
                    $fail('تاریخ واردشده معتبر نیست.');
                }
            }],
            'serviceBlocks.*.social_worker_id' => ['required', 'integer', 'exists:social_workers,id'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'serviceBlocks.*.service_name' => 'نام خدمت',
            'serviceBlocks.*.category' => 'دسته‌بندی',
            'serviceBlocks.*.description' => 'توضیحات',
            'serviceBlocks.*.total_quantity' => 'تعداد کل',
            'serviceBlocks.*.unit' => 'واحد',
            'serviceBlocks.*.date' => 'تاریخ',
            'serviceBlocks.*.social_worker_id' => 'مددکار',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeBlock(): array
    {
        return [
            'service_id_preview' => Service::generateNextCode(),
            'service_name' => '',
            'category' => '',
            'description' => '',
            'total_quantity' => '',
            'unit' => 'package',
            'date' => Jalalian::now()->format('Y/m/d'),
            'social_worker_id' => null,
        ];
    }

    protected function refreshPreviewCodes(): void
    {
        foreach (array_keys($this->serviceBlocks) as $index) {
            $this->serviceBlocks[$index]['service_id_preview'] = Service::generateNextCode();
        }
    }

    protected function resolveServiceNameId(?string $name): int
    {
        $normalized = trim((string) $name);

        if ($normalized === '') {
            $normalized = 'خدمت توزیعی';
        }

        return ServiceName::query()->firstOrCreate(
            ['name' => $normalized],
            ['created_by' => auth()->id()]
        )->id;
    }

    protected function resolveServiceCategoryId(?string $name): int
    {
        $normalized = trim((string) $name);

        if ($normalized === '') {
            $existingId = ServiceCategory::query()->orderBy('name')->value('id');

            if ($existingId) {
                return (int) $existingId;
            }

            $normalized = 'توزیع';
        }

        return ServiceCategory::query()->firstOrCreate(
            ['name' => $normalized],
            ['created_by' => auth()->id()]
        )->id;
    }

    protected function isValidJalaliDate(string $date): bool
    {
        $parts = explode('/', trim($date));

        if (count($parts) !== 3) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', $parts);

        return \App\Helpers\Morilog\CalendarUtils::isValidateJalaliDate($year, $month, $day);
    }

    protected function jalaliToGregorian(string $date): string
    {
        return Jalalian::fromFormat('Y/m/d', trim($date))->toCarbon()->toDateString();
    }
}
