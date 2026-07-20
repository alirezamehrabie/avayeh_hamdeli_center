<?php

namespace App\Livewire\People;

use App\Models\Contact;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Services\People\BeneficiaryCompletenessCatalog;
use App\Services\People\BeneficiaryCompletenessEvaluator;
use App\Services\People\IncompleteCasesQueueCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorInstance;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class IncompleteCasesQueue extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public int $perPage = 12;

    public string $search = '';

    public string $selectedReason = 'all';

    public string $selectedSeverity = 'all';

    public string $selectedSort = 'newest';

    protected ?array $scanResultsCache = null;

    protected ?LengthAwarePaginator $incompletePeoplePaginatorCache = null;

    protected array $incompleteStateCache = [];

    protected ?array $catalogFieldsCache = null;

    protected ?array $reasonOptionsCache = null;

    protected ?Collection $personContactsCache = null;

    protected ?Collection $guardianContactsCache = null;

    protected ?Collection $socialWorkersCache = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function updatingSelectedReason(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedSeverity(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedSort(): void
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
        $this->search = '';
        $this->selectedReason = 'all';
        $this->selectedSeverity = 'all';
        $this->selectedSort = 'newest';
        $this->resetPage();
    }

    public function getIncompletePeopleProperty(): LengthAwarePaginator
    {
        if ($this->incompletePeoplePaginatorCache instanceof LengthAwarePaginator) {
            return $this->incompletePeoplePaginatorCache;
        }

        $scanResults = $this->scanResults();
        $page = Paginator::resolveCurrentPage() ?: 1;
        $total = count($scanResults['filtered_ids']);
        $pageIds = array_values(array_slice(
            $scanResults['filtered_ids'],
            max(0, ($page - 1) * $this->perPage),
            $this->perPage
        ));
        $items = $this->loadPeopleForIds($pageIds);

        return $this->incompletePeoplePaginatorCache = new LengthAwarePaginatorInstance(
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
        $scanResults = $this->scanResults();

        return [
            'incomplete_count' => $scanResults['incomplete_count'],
            'filtered_count' => count($scanResults['filtered_ids']),
            'reason_counts' => $scanResults['reason_counts'],
            'severity_counts' => $scanResults['severity_counts'],
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

    public function nextStepFor(Person $person): array
    {
        return app(BeneficiaryCompletenessEvaluator::class)->nextStep(
            $this->buildIncompleteState($person)['reasons']
        );
    }

    public function reasonOptions(): array
    {
        return ['all' => 'همه موارد'] + $this->catalogFields()
            ->mapWithKeys(fn (array $field): array => [
                $field['key'] => $field['section_label'].' - '.$field['label'],
            ])
            ->all();
    }

    public function severityOptions(): array
    {
        return [
            'all' => 'همه شدت‌ها',
            'critical' => 'بحرانی',
            'high' => 'زیاد',
            'medium' => 'متوسط',
            'low' => 'کم',
        ];
    }

    public function sortOptions(): array
    {
        return [
            'newest' => 'جدیدترین ثبت',
            'severity' => 'بالاترین شدت',
            'missing_count' => 'بیشترین نقص',
            'oldest' => 'قدیمی‌ترین ثبت',
        ];
    }

    protected function scanResults(): array
    {
        if ($this->scanResultsCache !== null) {
            return $this->scanResultsCache;
        }

        return $this->scanResultsCache = Cache::remember(
            $this->scanCacheKey(),
            now()->addMinutes(5),
            fn (): array => $this->buildScanResults()
        );
    }

    protected function buildScanResults(): array
    {
        $reasonCounts = array_fill_keys($this->catalogFieldKeys(), 0);

        $severityCounts = array_fill_keys(
            array_diff(array_keys($this->severityOptions()), ['all']),
            0
        );

        $incompleteCount = 0;
        $filteredReferences = [];

        Person::query()
            ->select($this->personColumns())
            ->with($this->scanRelations())
            ->orderBy('id')
            ->chunkById(200, function (EloquentCollection $people) use (&$filteredReferences, &$incompleteCount, &$reasonCounts, &$severityCounts): void {
                $this->primeContactCachesForPeople($people);

                foreach ($people as $person) {
                    $state = $this->buildIncompleteState($person);

                    if (count($state['reasons']) === 0) {
                        unset($this->incompleteStateCache[$person->id]);

                        continue;
                    }

                    $incompleteCount++;

                    foreach (array_keys($state['reasons']) as $reasonKey) {
                        $reasonCounts[$reasonKey]++;
                    }

                    $severityCounts[$state['severity']['key']]++;

                    if ($this->matchesSelectedFilters($person, $state)) {
                        $filteredReferences[] = [
                            'id' => $person->id,
                            'sort' => $this->sortReferenceFor($person, $state),
                        ];
                    }
                }

                $this->flushContactCaches();
            });

        usort($filteredReferences, fn (array $left, array $right): int => $this->compareSortReferences($left['sort'], $right['sort']));

        return $this->scanResultsCache = [
            'incomplete_count' => $incompleteCount,
            'reason_counts' => $reasonCounts,
            'severity_counts' => $severityCounts,
            'filtered_ids' => array_column($filteredReferences, 'id'),
        ];
    }

    protected function buildIncompleteState(Person $person): array
    {
        if (array_key_exists($person->id, $this->incompleteStateCache)) {
            return $this->incompleteStateCache[$person->id];
        }

        $person = $this->hydrateCompletenessContext($person);
        $evaluator = app(BeneficiaryCompletenessEvaluator::class);

        return $this->incompleteStateCache[$person->id] = $evaluator->evaluate(
            $person,
            $this->catalogFieldsArray(),
            $this->personContactsCache,
            $this->guardianContactsCache,
        );
    }

    protected function matchesSelectedFilters(Person $person, array $state): bool
    {
        if (! $this->matchesSearch($person)) {
            return false;
        }

        if ($this->selectedReason !== 'all' && ! array_key_exists($this->selectedReason, $state['reasons'])) {
            return false;
        }

        if ($this->selectedSeverity !== 'all' && $state['severity']['key'] !== $this->selectedSeverity) {
            return false;
        }

        return true;
    }

    protected function matchesSearch(Person $person): bool
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return true;
        }

        $personName = Person::normalizeSearchText((string) ($person->full_name ?: trim($person->first_name.' '.$person->last_name)));
        $guardianName = Person::normalizeSearchText(trim((string) ($person->guardian?->first_name.' '.$person->guardian?->last_name)));
        $socialWorkerName = $this->normalizedSocialWorkerName($person->guardian?->social_worker_id);

        if (ctype_digit($search)) {
            return $this->matchesNumericIdentifier((string) $person->person_code, $search, 5)
                || $this->matchesNumericIdentifier((string) $person->national_id, $search, 10)
                || $this->matchesNumericIdentifier((string) ($person->guardian?->national_code ?? ''), $search, 10);
        }

        return $this->matchesNormalizedText($personName, $search)
            || $this->matchesNormalizedText($guardianName, $search)
            || $this->matchesNormalizedText($socialWorkerName, $search);
    }

    protected function normalizedSearch(): string
    {
        return Person::normalizeSearchText($this->search);
    }

    protected function matchesNumericIdentifier(string $value, string $search, int $exactLength): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return strlen($search) >= $exactLength
            ? $value === $search
            : str_starts_with($value, $search);
    }

    protected function matchesNormalizedText(string $value, string $search): bool
    {
        if ($value === '') {
            return false;
        }

        return str_starts_with($value, $search)
            || (mb_strlen($search) >= 3 && str_contains($value, $search));
    }

    protected function catalogFields(): Collection
    {
        return collect($this->catalogFieldsArray());
    }

    protected function catalogFieldsArray(): array
    {
        if ($this->catalogFieldsCache !== null) {
            return $this->catalogFieldsCache;
        }

        return $this->catalogFieldsCache = app(BeneficiaryCompletenessCatalog::class)->fields();
    }

    protected function catalogFieldKeys(): array
    {
        return array_column($this->catalogFieldsArray(), 'key');
    }

    protected function loadPeopleForIds(array $personIds): EloquentCollection
    {
        if ($personIds === []) {
            return new EloquentCollection;
        }

        $people = Person::query()
            ->select($this->personColumns())
            ->with($this->pageRelations())
            ->whereIn('id', $personIds)
            ->get()
            ->keyBy('id');

        $this->primeContactCachesForPeople($people);
        $this->syncSocialWorkerRelations($people);

        return new EloquentCollection(
            collect($personIds)
                ->map(fn (int $id) => $people->get($id))
                ->filter()
                ->values()
                ->all()
        );
    }

    protected function personSortKey(Person $person): string
    {
        return sprintf(
            '%010d-%010d',
            $person->created_at?->getTimestamp() ?? 0,
            $person->id
        );
    }

    protected function sortReferenceFor(Person $person, array $state): array
    {
        $severityRank = match ($state['severity']['key'] ?? 'low') {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            default => 1,
        };

        return match ($this->selectedSort) {
            'severity' => [
                'priority' => $severityRank,
                'secondary' => count($state['reasons']),
                'timestamp' => $person->created_at?->getTimestamp() ?? 0,
                'id' => $person->id,
            ],
            'missing_count' => [
                'priority' => count($state['reasons']),
                'secondary' => $severityRank,
                'timestamp' => $person->created_at?->getTimestamp() ?? 0,
                'id' => $person->id,
            ],
            'oldest' => [
                'priority' => -1 * ($person->created_at?->getTimestamp() ?? 0),
                'secondary' => -1 * $person->id,
                'timestamp' => 0,
                'id' => 0,
            ],
            default => [
                'priority' => $person->created_at?->getTimestamp() ?? 0,
                'secondary' => $person->id,
                'timestamp' => 0,
                'id' => 0,
            ],
        };
    }

    protected function compareSortReferences(array $left, array $right): int
    {
        foreach (['priority', 'secondary', 'timestamp', 'id'] as $key) {
            $comparison = ($right[$key] ?? 0) <=> ($left[$key] ?? 0);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    protected function scanCacheKey(): string
    {
        return implode(':', [
            'people-incomplete-cases',
            $this->normalizedSearch(),
            $this->selectedReason,
            $this->selectedSeverity,
            $this->selectedSort,
            app(IncompleteCasesQueueCache::class)->currentVersion(),
        ]);
    }

    protected function personColumns(): array
    {
        return [
            'id',
            'person_code',
            'first_name',
            'last_name',
            'full_name',
            'guardian_id',
            'national_id',
            'shenasnameh_serial',
            'shenasnameh_series_number',
            'shenasnameh_series_letter',
            'birth_day',
            'birth_month',
            'birth_year',
            'father_name',
            'father_national_id',
            'mother_national_id',
            'phone_number',
            'gender',
            'role',
            'sadaat_status',
            'sadaat_relation_id',
            'has_disability',
            'disability_type_id',
            'disability_description',
            'photo_id_card',
            'photo_birth_certificate',
            'profile_photo',
            'client_case_history',
            'created_at',
        ];
    }

    protected function scanRelations(): array
    {
        return [
            'guardian:id,social_worker_id,national_code,first_name,last_name,guardian_phone_number,insurance_status,insurance_type_id',
            'guardian.residence:id,guardian_id,residence_status_id,district_id,address',
            'education:id,person_id,is_studying,reason_for_not_studying,education_degree',
            'familyStatus:id,person_id,guardian_relation_type_id,has_parent_disability,parent_disability_description',
            'harmTypes:id',
            'residenceContact:id,person_id,residence_status_id,district_id,address',
            'supportCoverage:id,person_id,support_organization_id,description,other_organization_name,support_card_image',
            'supportCoverage.organization:id,slug',
            'needsLevel:id,person_id,need_level_id',
        ];
    }

    protected function pageRelations(): array
    {
        return array_merge(
            $this->scanRelations(),
            ['guardian.socialWorker:id,first_name,last_name']
        );
    }

    protected function hydrateCompletenessContext(Person $person): Person
    {
        $person->loadMissing($this->pageRelations());

        if (
            ! ($this->personContactsCache?->has($person->id) ?? false)
            && ! ($this->guardianContactsCache?->has($person->guardian_id) ?? false)
        ) {
            $this->primeContactCachesForPeople(collect([$person]));
        }

        $this->syncSocialWorkerRelations(collect([$person]));

        return $person;
    }

    protected function primeContactCachesForPeople(Collection $people): void
    {
        $personIds = $people->pluck('id')->filter()->values();
        $guardianIds = $people->pluck('guardian_id')->filter()->unique()->values();
        $socialWorkerIds = $people
            ->pluck('guardian.social_worker_id')
            ->filter()
            ->unique()
            ->values();

        $this->personContactsCache = $personIds->isEmpty()
            ? collect()
            : Contact::query()
                ->select(['id', 'person_id', 'guardian_id', 'landline_phone', 'trusted_person_phone'])
                ->whereIn('person_id', $personIds)
                ->get()
                ->keyBy('person_id');

        $this->guardianContactsCache = $guardianIds->isEmpty()
            ? collect()
            : Contact::query()
                ->select(['id', 'person_id', 'guardian_id', 'landline_phone', 'trusted_person_phone'])
                ->whereIn('guardian_id', $guardianIds)
                ->get()
                ->keyBy('guardian_id');

        $this->socialWorkersCache = $socialWorkerIds->isEmpty()
            ? collect()
            : SocialWorker::withoutGlobalScope('active')
                ->select(['id', 'first_name', 'last_name'])
                ->whereIn('id', $socialWorkerIds)
                ->get()
                ->keyBy('id');
    }

    protected function syncSocialWorkerRelations(Collection $people): void
    {
        foreach ($people as $person) {
            $guardian = $person->guardian;

            if (! $guardian || ! $guardian->social_worker_id) {
                continue;
            }

            $worker = $this->socialWorkersCache?->get($guardian->social_worker_id);

            if ($worker instanceof SocialWorker) {
                $guardian->setRelation('socialWorker', $worker);
            }
        }
    }

    protected function flushContactCaches(): void
    {
        $this->personContactsCache = null;
        $this->guardianContactsCache = null;
        $this->socialWorkersCache = null;
    }

    protected function normalizedSocialWorkerName(?int $socialWorkerId): string
    {
        if (! $socialWorkerId) {
            return '';
        }

        $worker = $this->socialWorkersCache?->get($socialWorkerId);

        if (! $worker) {
            return '';
        }

        return Person::normalizeSearchText(trim((string) ($worker->first_name.' '.$worker->last_name)));
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.incomplete-cases-queue');
    }
}
