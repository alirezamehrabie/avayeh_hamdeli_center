<?php

namespace App\Livewire\DistributionOperators;

use App\Helpers\Morilog\Jalalian;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryTemplate;
use App\Models\ServiceName;
use App\Models\SocialWorker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ServiceBatchCreator extends Component
{
    protected const DEFAULT_SERVICE_NAME = 'متفرقه';

    protected const DEFAULT_SERVICE_CATEGORY = 'سایر (متفرقه)';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $serviceBlocks = [];

    public ?int $activeSocialWorkerSearchIndex = null;

    public ?int $editingServiceId = null;

    public function mount(?int $editingServiceId = null): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $this->editingServiceId = $editingServiceId;
        $this->serviceBlocks = $this->editingServiceId
            ? [$this->makeBlockFromService($this->resolveEditableService($this->editingServiceId))]
            : [$this->makeBlock()];
        $this->synchronizeBlockDates();
        $this->refreshPreviewCodes();
    }

    public function addBlock(): void
    {
        if ($this->editingServiceId) {
            return;
        }

        $this->serviceBlocks[] = $this->makeBlock();
        $this->synchronizeBlockDates();
        $this->refreshPreviewCodes();
    }

    public function removeBlock(int $index): void
    {
        if ($this->editingServiceId) {
            return;
        }

        if (count($this->serviceBlocks) === 1) {
            return;
        }

        unset($this->serviceBlocks[$index]);
        $this->serviceBlocks = array_values($this->serviceBlocks);
        $this->refreshPreviewCodes();
    }

    public function updatedServiceBlocks(mixed $value, string $name): void
    {
        $parts = explode('.', $name);

        if (count($parts) < 3) {
            return;
        }

        $index = (int) $parts[1];
        $field = $parts[2];

        if (! isset($this->serviceBlocks[$index])) {
            return;
        }

        if ($field === 'social_worker_query') {
            $this->serviceBlocks[$index]['social_worker_query'] = trim((string) $value);
            $this->serviceBlocks[$index]['social_worker_id'] = null;
            $this->activeSocialWorkerSearchIndex = $index;

            if ($this->serviceBlocks[$index]['social_worker_query'] === '') {
                $this->serviceBlocks[$index]['social_worker_id'] = null;
            }
        }

        if (in_array($field, ['date_day', 'date_month', 'date_year'], true)) {
            $this->serviceBlocks[$index]['date'] = $this->buildJalaliDateFromParts($this->serviceBlocks[$index]);
        }
    }

    public function activateSocialWorkerSearch(int $index): void
    {
        $this->activeSocialWorkerSearchIndex = $index;
    }

    public function selectSocialWorker(int $index, int $socialWorkerId): void
    {
        $worker = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->findOrFail($socialWorkerId);

        $this->serviceBlocks[$index]['social_worker_id'] = $worker->id;
        $this->serviceBlocks[$index]['social_worker_query'] = trim($worker->full_name . ' - کد ' . $worker->worker_code);
        $this->activeSocialWorkerSearchIndex = null;
    }

    public function clearSocialWorkerSelection(int $index): void
    {
        if (! isset($this->serviceBlocks[$index])) {
            return;
        }

        $this->serviceBlocks[$index]['social_worker_id'] = null;
        $this->serviceBlocks[$index]['social_worker_query'] = '';
        $this->activeSocialWorkerSearchIndex = $index;
    }

    public function saveBatch()
    {
        $this->synchronizeBlockDates();
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $defaultServiceName = $this->resolveDefaultServiceName();
        $defaultServiceCategory = $this->resolveDefaultServiceCategory($defaultServiceName->id);

        if ($this->editingServiceId) {
            $service = $this->resolveEditableService($this->editingServiceId);
            $block = $validated['serviceBlocks'][0];

            DB::transaction(function () use ($service, $block, $defaultServiceName, $defaultServiceCategory): void {
                $service->update([
                    'service_name_id' => $defaultServiceName->id,
                    'service_type' => $block['service_type'],
                    'description' => $this->buildServiceDescription(
                        $block['service_name'] ?? null,
                        $block['description'] ?? null
                    ),
                    'total_quantity' => $block['total_quantity'],
                    'distribution_start_date' => $this->jalaliToGregorian($block['date']),
                    'distribution_end_date' => $this->jalaliToGregorian($block['date']),
                ]);

                $category = $service->categories()->ordered()->first() ?? $service->categories()->create([
                    'service_name_id' => $defaultServiceName->id,
                    'name' => trim((string) ($block['service_name'] ?? '')) !== '' ? trim((string) $block['service_name']) : $defaultServiceCategory->name,
                    'quantity' => (float) $block['total_quantity'],
                    'unit' => $block['unit'],
                    'value' => 0,
                    'sort_id' => 1,
                    'created_by' => $service->created_by ?? auth()->id(),
                ]);

                $category->fill([
                    'service_name_id' => $defaultServiceName->id,
                    'name' => trim((string) ($block['service_name'] ?? '')) !== '' ? trim((string) $block['service_name']) : $category->name,
                    'quantity' => (float) $block['total_quantity'],
                    'unit' => $block['unit'],
                ])->save();

                $service->socialWorkers()->sync([
                    $block['social_worker_id'] => ['allocated_quantity' => $block['total_quantity']],
                ]);

                $service->refreshFinancialTotals();
            });

            session()->flash('success', 'خدمت با موفقیت به‌روزرسانی شد.');

            return redirect()->route('distribution-operator.service-list');
        }

        DB::transaction(function () use ($validated, $defaultServiceName, $defaultServiceCategory): void {
            foreach ($validated['serviceBlocks'] as $index => $block) {
                $serviceCode = Service::generateNextCode();

                $service = Service::query()->create([
                    'code' => $serviceCode,
                    'service_name_id' => $defaultServiceName->id,
                    'service_type' => $block['service_type'],
                    'description' => $this->buildServiceDescription(
                        $block['service_name'] ?? null,
                        $block['description'] ?? null
                    ),
                    'total_quantity' => $block['total_quantity'],
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

                $service->categories()->create([
                    'service_name_id' => $defaultServiceName->id,
                    'name' => trim((string) ($block['service_name'] ?? '')) !== '' ? trim((string) $block['service_name']) : $defaultServiceCategory->name,
                    'quantity' => (float) $block['total_quantity'],
                    'unit' => $block['unit'],
                    'value' => 0,
                    'sort_id' => 1,
                    'created_by' => auth()->id(),
                ]);

                $service->socialWorkers()->attach($block['social_worker_id'], [
                    'allocated_quantity' => $block['total_quantity'],
                ]);

                $service->refreshFinancialTotals();
                $this->serviceBlocks[$index]['service_id_preview'] = $serviceCode;
            }
        });

        $count = count($validated['serviceBlocks']);
        $this->resetValidation();
        $this->serviceBlocks = [$this->makeBlock()];
        $this->synchronizeBlockDates();
        $this->refreshPreviewCodes();

        session()->flash('success', "{$count} خدمت برای توزیع ثبت و به مددکاران تخصیص داده شد.");

        return redirect()->route('distribution-operator.service-list');
    }

    public function cancelEditing()
    {
        if (! $this->editingServiceId) {
            return;
        }

        return redirect()->route('distribution-operator.service-list');
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-batch-creator', [
            'socialWorkerSuggestions' => $this->getSocialWorkerSuggestions(),
            'isEditing' => $this->editingServiceId !== null,
            'typeOptions' => Service::TYPE_OPTIONS,
            'unitOptions' => Service::unitOptions(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'serviceBlocks' => ['required', 'array', 'min:1'],
            'serviceBlocks.*.service_name' => ['nullable', 'string', 'max:255'],
            'serviceBlocks.*.service_type' => ['required', Rule::in(array_keys(Service::TYPE_OPTIONS))],
            'serviceBlocks.*.description' => ['nullable', 'string', 'max:5000'],
            'serviceBlocks.*.total_quantity' => ['required', 'numeric', 'min:0.01'],
            'serviceBlocks.*.unit' => ['required', Rule::in(Service::unitKeys())],
            'serviceBlocks.*.date_day' => ['required', 'integer', 'min:1', 'max:31'],
            'serviceBlocks.*.date_month' => ['required', 'integer', 'min:1', 'max:12'],
            'serviceBlocks.*.date_year' => ['required', 'integer', 'min:1300', 'max:1600'],
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
            'serviceBlocks.*.service_type' => 'نوع خدمت',
            'serviceBlocks.*.description' => 'توضیحات',
            'serviceBlocks.*.total_quantity' => 'تعداد کل',
            'serviceBlocks.*.unit' => 'واحد',
            'serviceBlocks.*.date_day' => 'روز',
            'serviceBlocks.*.date_month' => 'ماه',
            'serviceBlocks.*.date_year' => 'سال',
            'serviceBlocks.*.date' => 'تاریخ',
            'serviceBlocks.*.social_worker_id' => 'مددکار',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeBlock(): array
    {
        $today = Jalalian::now();

        return [
            'service_id_preview' => '',
            'service_name' => '',
            'service_type' => 'individual',
            'description' => '',
            'total_quantity' => '',
            'unit' => 'package',
            'date_day' => (string) $today->getDay(),
            'date_month' => (string) $today->getMonth(),
            'date_year' => (string) $today->getYear(),
            'date' => $today->format('Y/m/d'),
            'social_worker_id' => null,
            'social_worker_query' => '',
        ];
    }

    protected function makeBlockFromService(Service $service): array
    {
        [$serviceTitle, $serviceDescription] = $this->splitServiceDescription((string) $service->description);
        $worker = $service->socialWorkers()->select(['social_workers.id', 'first_name', 'last_name', 'worker_code'])->first();
        $jalaliDate = Jalalian::fromDateTime($service->distribution_start_date);

        return [
            'service_id_preview' => (string) $service->code,
            'service_name' => $serviceTitle,
            'service_type' => (string) $service->service_type,
            'description' => $serviceDescription,
            'total_quantity' => $this->formatDecimal($service->total_quantity),
            'unit' => (string) ($service->serviceCategory?->unit ?? $service->categories()->ordered()->value('unit') ?? array_key_first(Service::unitOptions()) ?? 'package'),
            'date_day' => (string) $jalaliDate->getDay(),
            'date_month' => (string) $jalaliDate->getMonth(),
            'date_year' => (string) $jalaliDate->getYear(),
            'date' => $jalaliDate->format('Y/m/d'),
            'social_worker_id' => $worker?->id,
            'social_worker_query' => $worker ? trim($worker->full_name . ' - کد ' . $worker->worker_code) : '',
        ];
    }

    protected function synchronizeBlockDates(): void
    {
        foreach (array_keys($this->serviceBlocks) as $index) {
            $this->serviceBlocks[$index]['date'] = $this->buildJalaliDateFromParts($this->serviceBlocks[$index]);
        }
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function buildJalaliDateFromParts(array $block): string
    {
        $year = str_pad(trim((string) ($block['date_year'] ?? '')), 4, '0', STR_PAD_LEFT);
        $month = str_pad(trim((string) ($block['date_month'] ?? '')), 2, '0', STR_PAD_LEFT);
        $day = str_pad(trim((string) ($block['date_day'] ?? '')), 2, '0', STR_PAD_LEFT);

        return implode('/', [$year, $month, $day]);
    }

    protected function refreshPreviewCodes(): void
    {
        if ($this->editingServiceId) {
            return;
        }

        $lastId = (int) Service::query()->max('id');

        foreach (array_keys($this->serviceBlocks) as $index) {
            $this->serviceBlocks[$index]['service_id_preview'] = 'SN-' . str_pad((string) ($lastId + $index + 1), 5, '0', STR_PAD_LEFT);
        }
    }

    protected function resolveDefaultServiceName(): ServiceName
    {
        $serviceName = ServiceName::query()
            ->where('name', self::DEFAULT_SERVICE_NAME)
            ->first();

        if ($serviceName) {
            return $serviceName;
        }

        throw ValidationException::withMessages([
            'serviceBlocks' => 'نام خدمت پیش‌فرض اپراتور تنظیم نشده است. لطفاً با مدیر سیستم تماس بگیرید.',
        ]);
    }

    protected function resolveDefaultServiceCategory(int $serviceNameId): ServiceCategoryTemplate
    {
        $serviceCategory = ServiceCategoryTemplate::query()
            ->where('service_name_id', $serviceNameId)
            ->where('name', self::DEFAULT_SERVICE_CATEGORY)
            ->first();

        if ($serviceCategory) {
            return $serviceCategory;
        }

        throw ValidationException::withMessages([
            'serviceBlocks' => 'دسته‌بندی پیش‌فرض اپراتور تنظیم نشده است. لطفاً با مدیر سیستم تماس بگیرید.',
        ]);
    }

    protected function buildServiceDescription(?string $serviceTitle, ?string $description): string
    {
        $parts = array_filter([
            trim((string) $serviceTitle),
            trim((string) $description),
        ], fn (?string $value): bool => $value !== null && $value !== '');

        return implode(PHP_EOL . PHP_EOL, $parts);
    }

    /**
     * @return array{0:string,1:string}
     */
    protected function splitServiceDescription(string $description): array
    {
        $parts = preg_split("/\R{2,}/u", trim($description), 2) ?: [];

        if (count($parts) === 0) {
            return ['', ''];
        }

        if (count($parts) === 1) {
            return ['', (string) $parts[0]];
        }

        return [(string) $parts[0], (string) $parts[1]];
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

    protected function resolveEditableService(int $serviceId): Service
    {
        $service = Service::query()
            ->with('socialWorkers')
            ->where('created_by', auth()->id())
            ->findOrFail($serviceId);

        abort_unless(auth()->user()?->can('view-distribution-operator-service', $service), 403);

        return $service;
    }

    protected function formatDecimal(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return number_format($number, 2, '.', '');
    }

    /**
     * @return array<int, \Illuminate\Support\Collection<int, SocialWorker>>
     */
    protected function getSocialWorkerSuggestions(): array
    {
        $suggestions = [];

        foreach ($this->serviceBlocks as $index => $block) {
            if ($this->activeSocialWorkerSearchIndex !== $index) {
                $suggestions[$index] = collect();
                continue;
            }

            $query = trim((string) ($block['social_worker_query'] ?? ''));

            if (mb_strlen($query) < 2) {
                $suggestions[$index] = collect();
                continue;
            }

            $suggestions[$index] = SocialWorker::query()
                ->select(['id', 'first_name', 'last_name', 'worker_code'])
                ->where(function (Builder $workerQuery) use ($query): void {
                    $workerQuery->where('first_name', 'like', $query . '%')
                        ->orWhere('last_name', 'like', $query . '%')
                        ->orWhere('worker_code', 'like', $query . '%')
                        ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", [$query . '%'])
                        ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ['%' . $query . '%']);
                })
                ->orderByRaw(
                    "CASE
                        WHEN first_name LIKE ? THEN 1
                        WHEN last_name LIKE ? THEN 2
                        WHEN worker_code LIKE ? THEN 3
                        WHEN CONCAT_WS(' ', first_name, last_name) LIKE ? THEN 4
                        ELSE 5
                    END",
                    [$query . '%', $query . '%', $query . '%', $query . '%']
                )
                ->orderBy('worker_code')
                ->limit(6)
                ->get();
        }

        return $suggestions;
    }
}
