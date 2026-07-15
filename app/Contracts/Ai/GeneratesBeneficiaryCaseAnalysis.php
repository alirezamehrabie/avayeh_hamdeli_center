<?php

namespace App\Contracts\Ai;

use App\Models\Person;
use Illuminate\Support\Collection;

interface GeneratesBeneficiaryCaseAnalysis
{
    /**
     * @return array{summary: string, reminders: list<array{title: string, category: string}>}
     */
    public function generate(
        Person $person,
        Collection $serviceDeliveries,
        Collection $activityAttendances,
        Collection $caseRecords,
        array $caseFileTotals,
    ): array;
}
