<?php

namespace App\Livewire\People;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorInstance;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class IncompleteCasesQueue extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public int $perPage = 12;

    public string $selectedReason = 'all';

    public string $selectedSeverity = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function updatingSelectedReason(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedSeverity(): void
    {
        $this->resetPage();
    }

    public function selectReason(string $reason): void
    {
        $this->selectedReason = array_key_exists($reason, $this->reasonOptions()) ? $reason : 'all';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->selectedReason = 'all';
        $this->selectedSeverity = 'all';
        $this->resetPage();
    }

    public function getIncompletePeopleProperty(): LengthAwarePaginator
    {
        $filteredPeople = $this->filteredIncompletePeopleCollection();
        $page = Paginator::resolveCurrentPage() ?: 1;
        $total = $filteredPeople->count();
        $items = $filteredPeople->forPage($page, $this->perPage)->values();

        return new LengthAwarePaginatorInstance(
            $items,
            $total,
            $this->perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function getSummaryProperty(): array
    {
        $reasonCounts = array_fill_keys(array_keys($this->reasonOptions()), 0);
        unset($reasonCounts['all']);

        $severityCounts = array_fill_keys(array_keys($this->severityOptions()), 0);
        unset($severityCounts['all']);

        foreach ($this->baseIncompletePeopleCollection() as $person) {
            $state = $this->buildIncompleteState($person);

            foreach (array_keys($state['reasons']) as $reasonKey) {
                $reasonCounts[$reasonKey]++;
            }

            $severityCounts[$state['severity']['key']]++;
        }

        return [
            'incomplete_count' => array_sum($severityCounts),
            'filtered_count' => $this->filteredIncompletePeopleCollection()->count(),
            'reason_counts' => $reasonCounts,
            'severity_counts' => $severityCounts,
        ];
    }

    public function reasonsFor(Person $person): Collection
    {
        return collect($this->buildIncompleteState($person)['reasons'])->values();
    }

    public function severityFor(Person $person): array
    {
        return $this->buildIncompleteState($person)['severity'];
    }

    public function reasonOptions(): array
    {
        return [
            'all' => 'همه موارد',
            'missing_guardian' => 'بدون سرپرست',
            'missing_social_worker' => 'بدون مددکار',
            'missing_national_id' => 'بدون کد ملی',
            'missing_contact_path' => 'بدون مسیر تماس',
            'missing_residence' => 'بدون سکونت',
            'missing_support_coverage' => 'بدون پوشش حمایتی',
            'missing_needs_level' => 'بدون سطح نیاز',
        ];
    }

    public function severityOptions(): array
    {
        return [
            'all' => 'همه شدت‌ها',
            'critical' => 'بحرانی',
            'high' => 'زیاد',
            'medium' => 'متوسط',
        ];
    }

    protected function filteredIncompletePeopleCollection(): Collection
    {
        return $this->baseIncompletePeopleCollection()
            ->filter(function (Person $person): bool {
                $state = $this->buildIncompleteState($person);

                if ($this->selectedReason !== 'all' && ! array_key_exists($this->selectedReason, $state['reasons'])) {
                    return false;
                }

                if ($this->selectedSeverity !== 'all' && $state['severity']['key'] !== $this->selectedSeverity) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    protected function baseIncompletePeopleCollection(): Collection
    {
        return Person::query()
            ->select([
                'id',
                'person_code',
                'first_name',
                'last_name',
                'full_name',
                'national_id',
                'phone_number',
                'guardian_id',
                'created_at',
            ])
            ->with([
                'guardian:id,first_name,last_name,guardian_phone_number,social_worker_id',
                'guardian.socialWorker:id,first_name,last_name',
                'residenceContact:id,person_id',
                'supportCoverage:id,person_id',
                'needsLevel:id,person_id',
            ])
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('guardian_id')
                    ->orWhereNull('national_id')
                    ->orWhere(function (Builder $phoneQuery): void {
                        $phoneQuery
                            ->where(function (Builder $personPhoneQuery): void {
                                $personPhoneQuery
                                    ->whereNull('phone_number')
                                    ->orWhere('phone_number', '');
                            })
                            ->where(function (Builder $guardianPhoneQuery): void {
                                $guardianPhoneQuery
                                    ->whereDoesntHave('guardian')
                                    ->orWhereHas('guardian', function (Builder $guardianQuery): void {
                                        $guardianQuery
                                            ->whereNull('guardian_phone_number')
                                            ->orWhere('guardian_phone_number', '');
                                    });
                            });
                    })
                    ->orWhereDoesntHave('residenceContact')
                    ->orWhereDoesntHave('supportCoverage')
                    ->orWhereDoesntHave('needsLevel')
                    ->orWhere(function (Builder $guardianQuery): void {
                        $guardianQuery
                            ->whereNotNull('guardian_id')
                            ->whereHas('guardian', function (Builder $assignedGuardianQuery): void {
                                $assignedGuardianQuery->whereNull('social_worker_id');
                            });
                    });
            })
            ->latest('created_at')
            ->latest('id')
            ->get();
    }

    protected function buildIncompleteState(Person $person): array
    {
        $guardian = $person->guardian;
        $reasons = [];

        if (! $person->guardian_id || ! $guardian) {
            $reasons['missing_guardian'] = 'سرپرست برای این مددجو ثبت نشده است.';
        }

        if ($guardian && ! $guardian->social_worker_id) {
            $reasons['missing_social_worker'] = 'برای سرپرست این پرونده مددکار تعیین نشده است.';
        }

        if (blank($person->national_id)) {
            $reasons['missing_national_id'] = 'کد ملی مددجو ثبت نشده است.';
        }

        if (blank($person->phone_number) && blank($guardian?->guardian_phone_number)) {
            $reasons['missing_contact_path'] = 'هیچ شماره تماس مستقیمی برای مددجو یا سرپرست ثبت نشده است.';
        }

        if (! $person->residenceContact) {
            $reasons['missing_residence'] = 'اطلاعات سکونت ثبت نشده است.';
        }

        if (! $person->supportCoverage) {
            $reasons['missing_support_coverage'] = 'وضعیت پوشش حمایتی ثبت نشده است.';
        }

        if (! $person->needsLevel) {
            $reasons['missing_needs_level'] = 'سطح نیازمندی ثبت نشده است.';
        }

        return [
            'reasons' => $reasons,
            'severity' => $this->buildSeverity($reasons),
        ];
    }

    protected function buildSeverity(array $reasons): array
    {
        $reasonCount = count($reasons);

        if (isset($reasons['missing_guardian']) || isset($reasons['missing_social_worker']) || $reasonCount >= 4) {
            return [
                'key' => 'critical',
                'label' => 'بحرانی',
                'classes' => 'border-rose-200 bg-rose-50 text-rose-800',
            ];
        }

        if ($reasonCount >= 2) {
            return [
                'key' => 'high',
                'label' => 'زیاد',
                'classes' => 'border-amber-200 bg-amber-50 text-amber-800',
            ];
        }

        return [
            'key' => 'medium',
            'label' => 'متوسط',
            'classes' => 'border-sky-200 bg-sky-50 text-sky-800',
        ];
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.incomplete-cases-queue');
    }
}
