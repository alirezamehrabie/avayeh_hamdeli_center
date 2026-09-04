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

    public function test_command_folds_alef_variants_into_normalized_and_compact_columns(): void
    {
        $person = Person::query()->create([
            'person_code' => 'P830003',
            'national_id' => '8300010003',
            'first_name' => 'آرمان',
            'last_name' => 'أحمدی',
        ]);

        DB::table('people')
            ->where('id', $person->id)
            ->update([
                'normalized_first_name' => null,
                'normalized_last_name' => null,
                'normalized_full_name' => null,
                'compact_first_name' => null,
                'compact_last_name' => null,
                'compact_full_name' => null,
            ]);

        $this->artisan('people:backfill-normalized-search', ['--chunk' => 1])
            ->assertSuccessful();

        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'normalized_first_name' => 'ارمان',
            'normalized_last_name' => 'احمدی',
            'normalized_full_name' => 'ارمان احمدی',
            'compact_first_name' => 'ارمان',
            'compact_last_name' => 'احمدی',
            'compact_full_name' => 'ارماناحمدی',
        ]);
    }

    public function test_command_keeps_column_values_aligned_with_multiple_rows_per_chunk(): void
    {
        $first = Person::query()->create([
            'person_code' => 'P830004',
            'national_id' => '8300010004',
            'first_name' => 'آرمان',
            'last_name' => 'موسوی',
        ]);
        $second = Person::query()->create([
            'person_code' => 'P830005',
            'national_id' => '8300010005',
            'first_name' => 'ارسلان',
            'last_name' => 'رحیمی',
        ]);
        $third = Person::query()->create([
            'person_code' => 'P830006',
            'national_id' => '8300010006',
            'first_name' => 'امید',
            'last_name' => 'زمانی فروشانی',
        ]);

        DB::table('people')->update([
            'normalized_first_name' => null,
            'normalized_last_name' => null,
            'normalized_full_name' => null,
            'compact_first_name' => null,
            'compact_last_name' => null,
            'compact_full_name' => null,
        ]);

        $this->artisan('people:backfill-normalized-search', ['--chunk' => 2])
            ->assertSuccessful();

        $this->assertDatabaseHas('people', [
            'id' => $first->id,
            'normalized_first_name' => 'ارمان',
            'normalized_last_name' => 'موسوی',
            'normalized_full_name' => 'ارمان موسوی',
            'compact_first_name' => 'ارمان',
            'compact_last_name' => 'موسوی',
            'compact_full_name' => 'ارمانموسوی',
        ]);
        $this->assertDatabaseHas('people', [
            'id' => $second->id,
            'normalized_first_name' => 'ارسلان',
            'normalized_last_name' => 'رحیمی',
            'normalized_full_name' => 'ارسلان رحیمی',
            'compact_first_name' => 'ارسلان',
            'compact_last_name' => 'رحیمی',
            'compact_full_name' => 'ارسلانرحیمی',
        ]);
        $this->assertDatabaseHas('people', [
            'id' => $third->id,
            'normalized_first_name' => 'امید',
            'normalized_last_name' => 'زمانی فروشانی',
            'normalized_full_name' => 'امید زمانی فروشانی',
            'compact_first_name' => 'امید',
            'compact_last_name' => 'زمانیفروشانی',
            'compact_full_name' => 'امیدزمانیفروشانی',
        ]);
    }
}
