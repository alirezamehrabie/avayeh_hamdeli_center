<?php

namespace App\Livewire\Guardians;

use App\Models\Guardian;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DeletedGuardians extends Component
{
    use WithPagination;

    public bool $embedded = false;
    public string $nationalIdSearch = '';
    public string $appliedNationalIdSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function searchByNationalId(): void
    {
        $this->appliedNationalIdSearch = preg_replace('/\D+/', '', trim($this->nationalIdSearch));
        $this->resetPage();
    }

    public function getDeletedGuardiansProperty()
    {
        $query = Guardian::onlyTrashed()
            ->withCount(['people' => fn ($query) => $query->onlyTrashed()])
            ->orderBy('deleted_at', 'desc');

        if ($this->appliedNationalIdSearch !== '') {
            $query->where('national_code', 'LIKE', '%' . $this->appliedNationalIdSearch . '%');
        }

        return $query->paginate(20);
    }

    public function restoreFamily(int $guardianId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $guardian = Guardian::onlyTrashed()->findOrFail($guardianId);
        $guardian->restoreFamily();
        $this->resetPage();

        session()->flash('success', 'سرپرست و همه مددجویان خانواده با همان کدهای قبلی بازیابی شدند.');
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        return view('livewire.guardians.deleted-guardians');
    }
}
