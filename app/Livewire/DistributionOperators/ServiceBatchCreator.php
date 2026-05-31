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
use Livewire\Component;

class ServiceBatchCreator extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $serviceBlocks = [];

    public ?int $activeSocialWorkerSearchIndex = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        $this->serviceBlocks = [$this->makeBlock()];
        $this->refreshPreviewCodes();
    }

    public function addBlock(): void
    {
        $this->serviceBlocks[] = $this->makeBlock();
        $this->refreshPreviewCodes();
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

    public function saveBatch(): void
    {
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function () use ($validated): void {
            foreach ($validated['serviceBlocks'] as $index => $block) {
                $nameId = $this->resolveServiceNameId($block['service_name'] ?? null);
                $categoryId = $this->resolveServiceCategoryId($block['category_id'] ?? null);
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
        $this->refreshPreviewCodes();

        session()->flash('success', "{$count} خدمت برای توزیع ثبت و به مددکاران تخصیص داده شد.");
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-batch-creator', [
            'categoryOptions' => ServiceCategory::query()->orderBy('name')->get(['id', 'name']),
            'socialWorkerSuggestions' => $this->getSocialWorkerSuggestions(),
            'unitOptions' => Service::UNIT_OPTIONS,
        ]);
    }

    protected function rules(): array
    {
        return [
            'serviceBlocks' => ['required', 'array', 'min:1'],
            'serviceBlocks.*.service_name' => ['nullable', 'string', 'max:255'],
            'serviceBlocks.*.category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
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
            'serviceBlocks.*.category_id' => 'دسته‌بندی',
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
            'service_id_preview' => '',
            'service_name' => '',
            'category_id' => null,
            'description' => '',
            'total_quantity' => '',
            'unit' => 'package',
            'date' => Jalalian::now()->format('Y/m/d'),
            'social_worker_id' => null,
            'social_worker_query' => '',
        ];
    }

    protected function refreshPreviewCodes(): void
    {
        $lastId = (int) Service::query()->max('id');

        foreach (array_keys($this->serviceBlocks) as $index) {
            $this->serviceBlocks[$index]['service_id_preview'] = 'SRV-' . str_pad((string) ($lastId + $index + 1), 5, '0', STR_PAD_LEFT);
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

    protected function resolveServiceCategoryId(?int $categoryId): int
    {
        if ($categoryId) {
            return (int) ServiceCategory::query()->findOrFail($categoryId)->id;
        }

        return (int) ServiceCategory::query()
            ->where('name', 'Undefined')
            ->value('id');
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
