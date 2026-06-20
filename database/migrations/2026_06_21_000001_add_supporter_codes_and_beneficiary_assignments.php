<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsor_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('sponsor_profiles', 'supporter_code')) {
                $table->string('supporter_code', 16)->nullable()->after('id');
            }
        });

        $this->backfillSupporterCodes();

        Schema::table('sponsor_profiles', function (Blueprint $table): void {
            if ($this->hasIndex('sponsor_profiles', 'sponsor_profiles_supporter_code_unique')) {
                return;
            }

            $table->unique('supporter_code');
        });

        if (! Schema::hasTable('person_sponsor_profile')) {
            Schema::create('person_sponsor_profile', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
                $table->foreignId('sponsor_profile_id')->constrained('sponsor_profiles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['person_id', 'sponsor_profile_id'], 'person_sponsor_profile_unique');
                $table->index('sponsor_profile_id', 'person_sponsor_profile_sponsor_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('person_sponsor_profile');

        Schema::table('sponsor_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('sponsor_profiles', 'supporter_code')) {
                $table->dropUnique('sponsor_profiles_supporter_code_unique');
                $table->dropColumn('supporter_code');
            }
        });
    }

    private function backfillSupporterCodes(): void
    {
        DB::table('sponsor_profiles')
            ->whereNull('supporter_code')
            ->orderBy('id')
            ->select(['id'])
            ->chunk(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    DB::table('sponsor_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'supporter_code' => sprintf('CS-%04d', (int) $profile->id),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $schema = Schema::getConnection()->getSchemaBuilder();

        if (! method_exists($schema, 'getIndexes')) {
            return false;
        }

        return collect($schema->getIndexes($table))
            ->contains(fn (array $existingIndex): bool => ($existingIndex['name'] ?? null) === $index);
    }
};
