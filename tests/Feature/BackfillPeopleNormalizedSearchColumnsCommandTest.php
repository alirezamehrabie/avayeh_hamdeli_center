<?php

namespace Tests\Feature;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillPeopleNormalizedSearchColumnsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_backfills_missing_normalized_search_columns(): void
    {
        $person = Person::query()->create([
            'person_code' => 'P830001',
            'national_id' => '8300010001',
            'first_name' => 'علي',
            'last_name' => 'رضايي',
        ]);

        DB::table('people')
            ->where('id', $person->id)
            ->update([
                'normalized_first_name' => null,
                'normalized_last_name' => null,
                'normalized_full_name' => null,
            ]);

        $this->artisan('people:backfill-normalized-search', ['--chunk' => 1])
            ->assertSuccessful()
            ->expectsOutput('Normalized search columns backfilled for 1 people missing normalized search columns.');

        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'normalized_first_name' => 'علی',
            'normalized_last_name' => 'رضایی',
            'normalized_full_name' => 'علی رضایی',
            'compact_first_name' => 'علی',
            'compact_last_name' => 'رضایی',
            'compact_full_name' => 'علیرضایی',
        ]);
    }

    public function test_command_can_recompute_all_rows_when_forced(): void
    {
        $person = Person::query()->create([
            'person_code' => 'P830002',
            'national_id' => '8300010002',
            'first_name' => 'محمد',
            'last_name' => 'کریمی',
        ]);

        DB::table('people')
            ->where('id', $person->id)
            ->update([
                'normalized_first_name' => 'stale',
                'normalized_last_name' => 'data',
                'normalized_full_name' => 'stale data',
            ]);

        $this->artisan('people:backfill-normalized-search', ['--force-all' => true, '--chunk' => 1])
            ->assertSuccessful()
            ->expectsOutput('Normalized search columns backfilled for 1 people.');

        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'normalized_first_name' => 'محمد',
            'normalized_last_name' => 'کریمی',
            'normalized_full_name' => 'محمد کریمی',
        ]);
    }
}
