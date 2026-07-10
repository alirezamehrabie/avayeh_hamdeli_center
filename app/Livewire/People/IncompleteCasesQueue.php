<?php

namespace App\Livewire\People;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class IncompleteCasesQueue extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public int $perPage = 12;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function getIncompletePeopleProperty(): LengthAwarePaginator
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
            ->paginate($this->perPage);
    }

    public function getSummaryProperty(): array
    {
        $people = Person::query()
            ->select([
                'id',
                'national_id',
                'phone_number',
                'guardian_id',
            ])
            ->with([
                'guardian:id,guardian_phone_number,social_worker_id',
                'residenceContact:id,person_id',
                'supportCoverage:id,person_id',
                'needsLevel:id,person_id',
            ])
            ->get();

        $reasonCounts = [
            'missing_guardian' => 0,
            'missing_social_worker' => 0,
            'missing_national_id' => 0,
            'missing_contact_path' => 0,
            'missing_residence' => 0,
            'missing_support_coverage' => 0,
            'missing_needs_level' => 0,
        ];

        $incompleteCount = 0;

        foreach ($people as $person) {
            $reasons = $this->buildIncompleteReasons($person);

            if ($reasons === []) {
                continue;
            }

            $incompleteCount++;

            foreach (array_keys($reasons) as $reasonKey) {
                $reasonCounts[$reasonKey]++;
            }
        }

        return [
            'incomplete_count' => $incompleteCount,
            'reason_counts' => $reasonCounts,
        ];
    }

    public function reasonsFor(Person $person): Collection
    {
        return collect($this->buildIncompleteReasons($person))->values();
    }

    protected function buildIncompleteReasons(Person $person): array
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

        if (! $person->relationLoaded('residenceContact')) {
            $person->loadMissing('residenceContact');
        }

        if (! $person->residenceContact) {
            $reasons['missing_residence'] = 'اطلاعات سکونت ثبت نشده است.';
        }

        if (! $person->relationLoaded('supportCoverage')) {
            $person->loadMissing('supportCoverage');
        }

        if (! $person->supportCoverage) {
            $reasons['missing_support_coverage'] = 'وضعیت پوشش حمایتی ثبت نشده است.';
        }

        if (! $person->relationLoaded('needsLevel')) {
            $person->loadMissing('needsLevel');
        }

        if (! $person->needsLevel) {
            $reasons['missing_needs_level'] = 'سطح نیازمندی ثبت نشده است.';
        }

        return $reasons;
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        return view('livewire.people.incomplete-cases-queue');
    }
}
