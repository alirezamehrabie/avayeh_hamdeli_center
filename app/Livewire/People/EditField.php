<?php

namespace App\Livewire\People;

use App\Models\NeedLevelType;
use App\Models\NeedsLevel;
use App\Models\Person;
use App\Queries\People\PeopleIndexSearchQuery;
use Illuminate\Support\Collection;
use Livewire\Component;

class EditField extends Component
{
    public bool $embedded = false;

    public string $search = '';

    public ?int $selectedPersonId = null;

    public string $editorTitle = '';

    public ?string $needLevelId = null;

    public ?string $initialNeedLevelId = null;

    public ?string $flashMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('people-edit'), 403);
    }

    public function updatedSearch(): void
    {
        $this->flashMessage = null;
    }

    public function selectPerson(int $personId): void
    {
        $person = Person::query()->find($personId);
        abort_if(! $person, 404);

        $this->selectedPersonId = $person->id;
        $this->needLevelId = $person->needsLevel?->need_level_id !== null
            ? (string) $person->needsLevel->need_level_id
            : null;
        $this->initialNeedLevelId = $this->needLevelId;
        $this->flashMessage = null;

        // شیت پایین «سطح نیاز» در موبایل بلافاصله پس از انتخاب مددجو باز می‌شود.
        $this->dispatch('open-need-level-sheet');
    }

    public function resetSelection(): void
    {
        $this->selectedPersonId = null;
        $this->needLevelId = null;
        $this->initialNeedLevelId = null;
        $this->search = '';
        $this->flashMessage = null;

        $this->dispatch('focus-person-search');
    }

    public function backToFields(): void
    {
        $this->resetSelection();
        $this->dispatch('open-dashboard-section', section: 'people-edit-field');
    }

    public function save(): void
    {
        $person = $this->selectedPerson();
        abort_if(! $person, 404);

        $validated = $this->validate([
            'needLevelId' => ['required', 'integer', 'exists:need_level_types,id'],
        ], [
            'needLevelId.required' => 'انتخاب سطح نیاز الزامی است.',
            'needLevelId.exists' => 'سطح نیاز انتخابی نامعتبر است.',
        ]);

        NeedsLevel::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['need_level_id' => (int) $validated['needLevelId']],
        );

        $this->flashMessage = 'سطح نیاز برای «'.$person->full_name.'» با موفقیت ذخیره شد؛ صفحه برای مددجوی بعدی آماده است.';

        // بازگشت خودکار به حالت جستجو برای ثبت مددجوی بعدی.
        $this->selectedPersonId = null;
        $this->needLevelId = null;
        $this->initialNeedLevelId = null;
        $this->search = '';
        $this->dispatch('close-need-level-sheet');
        $this->dispatch('focus-person-search');
    }

    private function selectedPerson(): ?Person
    {
        if ($this->selectedPersonId === null) {
            return null;
        }

        return Person::query()
            ->with([
                'guardian:id,insurance_status,insurance_type_id',
                'guardian.insuranceType:id,name',
                'supportCoverage:id,person_id,support_organization_id,other_organization_name',
                'supportCoverage.organization:id,name,slug',
                'needsLevel:id,person_id,need_level_id',
                'needsLevel.levelType:id,code,title',
            ])
            ->find($this->selectedPersonId);
    }

    /**
     * انتخاب سطح معتبر و متفاوت از مقدار ذخیره‌شده؛ دکمه ثبت تا این شرط برقرار
     * نشود غیرفعال می‌ماند تا ثبت بی‌معنا یا ثبت «بی‌تغییری» ممکن نشود.
     */
    private function canSaveNeedLevel(): bool
    {
        return $this->selectedPersonId !== null
            && $this->needLevelId !== null
            && $this->needLevelId !== $this->initialNeedLevelId;
    }

    /**
     * @return Collection<int, Person>
     */
    private function searchResults(): Collection
    {
        $peopleSearch = app(PeopleIndexSearchQuery::class);
        $search = $peopleSearch->normalizeSearchTerm(trim($this->search));

        if ($search === '' || $peopleSearch->needsMoreInput($search, 'all')) {
            return collect();
        }

        $query = Person::query()
            ->select(['id', 'person_code', 'first_name', 'last_name', 'full_name', 'national_id'])
            ->latest('id');

        $peopleSearch->applyTo($query, $search);

        // یکی بیشتر از ظرفیت نمایش، تا مشخص شود نتایج بیشتری وجود دارد.
        return $query->limit(11)->get();
    }

    public function render()
    {
        $hits = $this->selectedPersonId === null ? $this->searchResults() : collect();

        return view('livewire.people.edit-field', [
            'person' => $this->selectedPerson(),
            'searchResults' => $hits->take(10),
            'searchTruncated' => $hits->count() > 10,
            'searchTooShort' => $this->selectedPersonId === null && $this->searchNeedsMoreInput(),
            'canSave' => $this->canSaveNeedLevel(),
            'levels' => NeedLevelType::query()->orderByDesc('severity_order')->get(),
        ]);
    }

    private function searchNeedsMoreInput(): bool
    {
        $peopleSearch = app(PeopleIndexSearchQuery::class);

        return $peopleSearch->needsMoreInput($peopleSearch->normalizeSearchTerm(trim($this->search)), 'all');
    }
}
