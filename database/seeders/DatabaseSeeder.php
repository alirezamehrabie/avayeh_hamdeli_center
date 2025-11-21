<?php

namespace Database\Seeders;

use App\Models\GuardianRelationType;
use App\Models\ResidenceStatusType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SocialWorkerSeeder::class,
            EducationLevelSeeder::class,
            ResidenceStatusTypeSeeder::class,
            NeedLevelTypeSeeder::class,
            DistrictSeeder::class,
            OccupationSeeder::class,
            SadaatRelationsSeeder::class,
            SkillsTableSeeder::class,
            GuardianRelationTypeSeeder::class,
            DisabilityTypeSeeder::class,
        ]);
    }
}
