<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateByLatestId('family_statuses', 'person_id');
        $this->deduplicateByLatestId('support_coverages', 'person_id');
        $this->deduplicateByLatestId('needs_levels', 'person_id');
        $this->deduplicateByLatestId('residences', 'guardian_id');
        $this->deduplicateByLatestId('contacts', 'guardian_id');
        $this->deduplicateByLatestId('bank_infos', 'person_id');
        $this->deduplicateByLatestId('bank_infos', 'guardian_id');

        Schema::table('family_statuses', function (Blueprint $table) {
            $table->unique('person_id', 'uq_family_statuses_person_id');
        });

        Schema::table('support_coverages', function (Blueprint $table) {
            $table->unique('person_id', 'uq_support_coverages_person_id');
        });

        Schema::table('needs_levels', function (Blueprint $table) {
            $table->unique('person_id', 'uq_needs_levels_person_id');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->unique('guardian_id', 'uq_residences_guardian_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unique('guardian_id', 'uq_contacts_guardian_id');
        });

        Schema::table('bank_infos', function (Blueprint $table) {
            $table->unique('person_id', 'uq_bank_infos_person_id');
            $table->unique('guardian_id', 'uq_bank_infos_guardian_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            $table->dropUnique('uq_bank_infos_person_id');
            $table->dropUnique('uq_bank_infos_guardian_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('uq_contacts_guardian_id');
        });

        Schema::table('residences', function (Blueprint $table) {
            $table->dropUnique('uq_residences_guardian_id');
        });

        Schema::table('needs_levels', function (Blueprint $table) {
            $table->dropUnique('uq_needs_levels_person_id');
        });

        Schema::table('support_coverages', function (Blueprint $table) {
            $table->dropUnique('uq_support_coverages_person_id');
        });

        Schema::table('family_statuses', function (Blueprint $table) {
            $table->dropUnique('uq_family_statuses_person_id');
        });
    }

    private function deduplicateByLatestId(string $table, string $column): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                DELETE t1
                FROM {$table} t1
                INNER JOIN {$table} t2
                    ON t1.{$column} = t2.{$column}
                    AND t1.id < t2.id
                WHERE t1.{$column} IS NOT NULL
            ");
            return;
        }

        $duplicateValues = DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($column);

        foreach ($duplicateValues as $value) {
            $keepId = DB::table($table)
                ->where($column, $value)
                ->max('id');

            DB::table($table)
                ->where($column, $value)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }
};
