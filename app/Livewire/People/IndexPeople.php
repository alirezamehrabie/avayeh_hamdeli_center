<?php

namespace App\Livewire\People;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Services\QrIdentityService;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexPeople extends Component
{
    use WithPagination;

    public $search = '';
    public string $searchField = 'all';
    public bool $embedded = false;
    public ?int $selectedPersonId = null;
    public bool $showPersonModal = false;
    public bool $showDeleteModal = false;
    public bool $showQrModal = false;
    public string $deletionReason = '';
    public ?int $deletingPersonId = null;
    public ?int $qrPersonId = null;
    public ?string $issuedQrToken = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-people'), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->resetPage();
    }

    public function getPeopleProperty()
    {
        $query = Person::with(['creator:id,name', 'updater:id,name'])->orderBy('created_at', 'desc');

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
                : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

            match ($this->searchField) {
                'person_code' => $query->where('person_code', 'LIKE', "%{$search}%"),
                'full_name' => $query->whereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]),
                'first_name' => $query->where('first_name', 'LIKE', "%{$search}%"),
                'last_name' => $query->where('last_name', 'LIKE', "%{$search}%"),
                'national_id' => $query->where('national_id', 'LIKE', "%{$search}%"),
                'mother_national_id' => $query->where('mother_national_id', 'LIKE', "%{$search}%"),
                'father_national_id' => $query->where('father_national_id', 'LIKE', "%{$search}%"),
                default => $query->where(function ($q) use ($search, $fullNameExpression) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('national_id', 'LIKE', "%{$search}%")
                        ->orWhere('mother_national_id', 'LIKE', "%{$search}%")
                        ->orWhere('father_national_id', 'LIKE', "%{$search}%")
                        ->orWhere('person_code', 'LIKE', "%{$search}%")
                        ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
                }),
            };
        }

        return $query->paginate(20);
    }

    public function editPerson(Person $person)
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'person-edit', id: $person->id);
            return;
        }

        return redirect()->route('people.form', [
            'mode' => 'edit',
            'person' => $person->id
        ]);
    }

    public function quickEditPerson(Person $person)
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'people-fast-create', id: $person->id);
            return;
        }

        return redirect()->route('people.fast-create', ['person' => $person->id]);
    }

    public function showPersonInfo(int $personId): void
    {
        $this->selectedPersonId = $personId;
        $this->showPersonModal = true;
    }

    public function closePersonModal(): void
    {
        $this->showPersonModal = false;
        $this->selectedPersonId = null;
    }

    public function openQrModal(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        $person = Person::query()->findOrFail($personId);
        $qrIdentityService = app(QrIdentityService::class);
        $qrIdentityService->ensureActiveFor($person, auth()->id());

        $this->qrPersonId = $person->id;
        $this->issuedQrToken = null;
        $this->showQrModal = true;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->qrPersonId = null;
        $this->issuedQrToken = null;
    }

    public function reissueQrCode(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        $person = Person::query()->findOrFail($this->qrPersonId);
        $qrIdentityService = app(QrIdentityService::class);
        $issued = $qrIdentityService->replaceFor($person, auth()->id());

        $this->issuedQrToken = $issued['token'];
        session()->flash('success', 'QR مددجو با موفقیت دوباره صادر شد.');
    }

    public function revokeQrCode(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-edit'), 403);

        $identity = $this->selectedQrIdentity;

        if ($identity) {
            $qrIdentityService = app(QrIdentityService::class);
            $qrIdentityService->revoke($identity, auth()->id());
        }

        $this->issuedQrToken = null;
        session()->flash('success', 'QR مددجو با موفقیت ابطال شد.');
    }

    public function getSelectedQrIdentityProperty(): ?QrIdentity
    {
        if (! $this->qrPersonId) {
            return null;
        }

        return QrIdentity::query()
            ->where('subject_type', QrIdentity::SUBJECT_PERSON)
            ->where('subject_id', $this->qrPersonId)
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    public function getQrPersonProperty(): ?Person
    {
        return $this->qrPersonId ? Person::query()->find($this->qrPersonId) : null;
    }

    public function getSelectedPersonProperty(): ?Person
    {
        if (!$this->selectedPersonId) {
            return null;
        }

        return Person::with([
            'guardian.occupation',
            'guardian.jobType',
            'guardian.residence',
            'guardian.socialWorker',
            'education.educationLevel',
            'education.educationDegreeLevel',
            'supportCoverage.organization',
            'disabilityType',
            'familyStatus.guardianRelationType',
            'skills',
            'harmTypes',
            'needsLevel.levelType',
        ])->find($this->selectedPersonId);
    }

    public function deletePerson(Person $person): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $person->forceFill([
            'deletion_reason' => $this->deletionReason,
        ])->saveQuietly();
        $person->delete();
        $this->resetPage();

        session()->flash('success', 'مددجو با موفقیت به بلاک لیست منتقل شد.');
    }

    public function openDeleteModal(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $this->deletingPersonId = Person::query()->findOrFail($personId)->id;
        $this->deletionReason = '';
        $this->resetValidation('deletionReason');
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPersonId = null;
        $this->deletionReason = '';
        $this->resetValidation('deletionReason');
    }

    public function confirmDeletePerson(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('people-delete'), 403);

        $validated = $this->validate([
            'deletionReason' => ['required', 'string', 'max:1000'],
        ], [
            'deletionReason.required' => 'ثبت علت حذف الزامی است.',
        ]);

        $person = Person::query()->findOrFail($this->deletingPersonId);

        $this->deletionReason = $validated['deletionReason'];
        $this->deletePerson($person);
        $this->closeDeleteModal();
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-people'), 403);

        return view('livewire.people.index-people', [
            'deletingPerson' => $this->deletingPersonId
                ? Person::query()->find($this->deletingPersonId)
                : null,
        ]);
    }
}
