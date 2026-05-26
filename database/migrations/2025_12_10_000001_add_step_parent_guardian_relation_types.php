<?php

use App\Models\GuardianRelationType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            GuardianRelationType::TITLE_STEPFATHER,
            GuardianRelationType::TITLE_STEPMOTHER,
        ] as $title) {
            GuardianRelationType::query()->firstOrCreate(['title' => $title]);
        }
    }

    public function down(): void
    {
        GuardianRelationType::query()
            ->whereIn('title', [
                GuardianRelationType::TITLE_STEPFATHER,
                GuardianRelationType::TITLE_STEPMOTHER,
            ])
            ->delete();
    }
};
