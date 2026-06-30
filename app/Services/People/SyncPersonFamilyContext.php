<?php

namespace App\Services\People;

use App\Models\FamilyStatus;
use App\Models\Guardian;
use App\Models\Person;

class SyncPersonFamilyContext
{
    /**
     * @param  array<int|string|null>  $selectedHarmTypes
     * @param  array<string, mixed>  $familyStatus
     */
    public function handle(
        Person $person,
        ?Guardian $guardian,
        array $selectedHarmTypes,
        array $familyStatus,
    ): void {
        $this->syncFamilyStatusAcrossSiblings($person, $familyStatus);
        $this->syncHarmTypesWithFamilyPropagation($person, $selectedHarmTypes);
        $this->refreshGuardianHousehold($guardian);
    }

    /**
     * @param  array<string, mixed>  $familyStatus
     */
    public function syncSiblingFamilyStatuses(Person $person, array $familyStatus, ?Guardian $guardian = null): void
    {
        $this->syncFamilyStatusAcrossSiblings($person, $familyStatus);
        $this->refreshGuardianHousehold($guardian);
    }

    /**
     * @param  array<int|string|null>  $selectedHarmTypes
     */
    public function syncHarmTypesWithFamilyPropagation(Person $person, array $selectedHarmTypes): void
    {
        $this->syncPersonHarmTypesWithFamilyPropagation($person, $selectedHarmTypes);
    }

    public function refreshGuardianHousehold(?Guardian $guardian): void
    {
        if ($guardian) {
            $guardian->refreshChildrenInHouse();
        }
    }

    /**
     * @param  array<int|string|null>  $selectedHarmTypes
     */
    private function syncPersonHarmTypesWithFamilyPropagation(Person $person, array $selectedHarmTypes): void
    {
        $selectedHarmTypeIds = collect($selectedHarmTypes)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $person->harmTypes()->sync($selectedHarmTypeIds);

        $fatherId = trim((string) $person->father_national_id);
        $motherId = trim((string) $person->mother_national_id);

        if ($fatherId === '' && $motherId === '') {
            return;
        }

        $harmRules = [
            1 => ['father_national_id' => $fatherId],
            2 => ['mother_national_id' => $motherId],
            5 => ['father_national_id' => $fatherId, 'mother_national_id' => $motherId],
            7 => ['father_national_id' => $fatherId, 'mother_national_id' => $motherId],
        ];

        foreach ($harmRules as $harmId => $conditions) {
            if (collect($conditions)->contains(fn ($value) => empty($value))) {
                continue;
            }

            $targetSiblings = Person::query()
                ->where('id', '!=', $person->id)
                ->where($conditions)
                ->get(['id']);

            if ($targetSiblings->isEmpty()) {
                continue;
            }

            foreach ($targetSiblings as $sibling) {
                if (in_array($harmId, $selectedHarmTypeIds, true)) {
                    $sibling->harmTypes()->syncWithoutDetaching([$harmId]);
                } else {
                    $sibling->harmTypes()->detach([$harmId]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $familyStatus
     */
    private function syncFamilyStatusAcrossSiblings(Person $person, array $familyStatus): void
    {
        $fatherNationalId = trim((string) $person->father_national_id);
        $motherNationalId = trim((string) $person->mother_national_id);

        if ($fatherNationalId === '' || $motherNationalId === '') {
            return;
        }

        $siblings = Person::query()
            ->where('id', '!=', $person->id)
            ->where('father_national_id', $fatherNationalId)
            ->where('mother_national_id', $motherNationalId)
            ->get(['id']);

        foreach ($siblings as $sibling) {
            FamilyStatus::updateOrCreate(
                ['person_id' => $sibling->id],
                $familyStatus
            );
        }
    }
}
