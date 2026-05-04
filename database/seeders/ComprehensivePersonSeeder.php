<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{AcademicLevel,
    Person,
    FamilyStatus,
    Guardian,
    Residence,
    Contact,
    BankInfo,
    Education,
    SupportCoverage,
    NeedsLevel,
    SocialWorker,
    SadaatRelation,
    DisabilityType,
    Skill,
    HarmType,
    GuardianRelationType,
    Occupation,
    JobType,
    InsuranceType,
    VehicleType,
    ResidenceStatusType,
    District,
    AccountOwnerRelation,
    SupportOrganization,
    Bank,
    EducationLevel,
    NeedLevelType
};
use Faker\Factory;

class ComprehensivePersonSeeder extends Seeder
{
    private const SOCIAL_WORKER_COUNT = 10;
    private const HOUSEHOLD_COUNT = 30;
    private const MIN_CHILDREN_PER_HOUSEHOLD = 1;
    private const MAX_CHILDREN_PER_HOUSEHOLD = 6;

    public function run(): void
    {
        $faker = Factory::create('fa_IR');

        // ۱. اطمینان از وجود شمارنده کد سرپرستی (طبق متد generateNextGuardianCode در مدل)
        if (DB::table('guardian_code_counter')->count() === 0) {
            DB::table('guardian_code_counter')->insert(['last_code' => 1000]);
        }

        // ۲. پر کردن جداول پایه (Lookup Tables) در صورت خالی بودن
        $this->seedLookupTables();

        // ۳. ایجاد ۵ مددکار اجتماعی
        $socialWorkers = [];
        for ($i = 0; $i < self::SOCIAL_WORKER_COUNT; $i++) {

            $firstName = $faker->firstName();
            $lastName = $faker->lastName();

            // مقادیر تصادفی برای تاریخ تولد
            $bDay = $faker->numberBetween(1, 30);
            $bMonth = $faker->numberBetween(1, 12);
            $bYear = $faker->numberBetween(1350, 1375);
            $birthDateFull = sprintf('%04d/%02d/%02d', $bYear, $bMonth, $bDay);

            // مقادیر تصادفی برای تاریخ شروع همکاری
            $sDay = $faker->numberBetween(1, 30);
            $sMonth = $faker->numberBetween(1, 12);
            $sYear = $faker->numberBetween(1395, 1402);
            $startDateFull = sprintf('%04d/%02d/%02d', $sYear, $sMonth, $sDay);
            $socialWorkers[] = SocialWorker::create([
                'worker_code' => $faker->numberBetween(10, 99),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'national_id' => $faker->unique()->numerify('##########'),
                'id_number' => $faker->numerify('####'),


                // فیلدهای تفکیک شده و کامل تولد
                'birth_day' => $bDay,
                'birth_month' => $bMonth,
                'birth_year' => $bYear,
                'birth_date_full' => $birthDateFull,

                'mobile' => $faker->phoneNumber(),


                // فیلدهای تفکیک شده و کامل شروع همکاری
                'start_day'       => $sDay,
                'start_month'     => $sMonth,
                'start_year'      => $sYear,
                'start_date_full' => $startDateFull,

                'occupation_id' => Occupation::inRandomOrder()->first()->id,
                'district_id' => District::inRandomOrder()->first()->id,
                'academic_level_id' => AcademicLevel::inRandomOrder()->first()->id,

                'substitute_first_name' => $faker->optional(1)->firstName(),
                'substitute_last_name'  => $faker->optional(1)->lastName(),
                'substitute_mobile'     => $faker->optional(1)->phoneNumber(),

                'photo_path' => null,

            ]);
        }

        // ۴. ایجاد ۲۰ پرونده کامل (سرپرست + مددجو + مخلفات)
        for ($i = 0; $i < self::HOUSEHOLD_COUNT; $i++) {
            $sw = $faker->randomElement($socialWorkers);

            // ۱. تولید مقادیر تصادفی برای تاریخ تولد
            $gYear = $faker->numberBetween(1340, 1370);
            $gMonth = $faker->numberBetween(1, 12);
            $gDay = $faker->numberBetween(1, 30);

            // ۲. ترکیب مقادیر برای فیلد full (با فرمت YYYY/MM/DD)
            $gFullDate = sprintf('%04d/%02d/%02d', $gYear, $gMonth, $gDay);

            // ابتدا وضعیت‌های منطقی را تعیین می‌کنیم
            $isInsured = $faker->boolean(70); // ۷۰ درصد احتمال دارد بیمه باشد
            $hasVehicle = $faker->boolean(90); // ۳۰ درصد احتمال دارد خودرو داشته باشد
            $hasEmployedFamily = $faker->boolean(50);

            // الف) ایجاد سرپرست (Guardian)
            $guardian = Guardian::create([
                'social_worker_id' => $sw->id,
                'guardian_code' => Guardian::generateNextGuardianCode(),
                'national_code' => $faker->unique()->numerify('##########'),
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                // مقادیر تفکیک شده
                'guardian_birth_year' => $gYear,
                'guardian_birth_month' => $gMonth,
                'guardian_birth_day' => $gDay,

                // مقدار ترکیبی (که درخواست کرده بودید)
                'guardian_birth_date_full' => $gFullDate,


                'occupation_id' => Occupation::inRandomOrder()->first()->id,
                'job_type_id' => JobType::inRandomOrder()->first()->id,
                'guardian_phone_number' => '09' . $faker->numerify('#########'),
                'children_count' => 0, // در ادامه آپدیت می‌شود
                'children_in_house' => rand(1, 6),
                // --- منطق شرطی بیمه ---
                'insurance_status' => $isInsured,
                'insurance_type_id' => $isInsured ? InsuranceType::inRandomOrder()->first()->id : null,
                'average_income' => $faker->numberBetween(5, 30) * 1000000,
                'economic_decile' =>  $faker->numberBetween(1, 10),
                'any_family_employed' => $hasEmployedFamily,
                'any_family_employed_description' => $hasEmployedFamily
                    ? $faker->randomElement(['فرزند بزرگ خانواده در کارگاه نجاری', 'همسر در خانه خیاطی می‌کند', 'یکی از فرزندان شاگرد مغازه است'])
                    : null,
                // --- منطق شرطی خودرو ---
                'has_vehicle' => $hasVehicle,
                'vehicle_type_id' => $hasVehicle ? VehicleType::inRandomOrder()->first()->id : null,
                'vehicle_ownership_type' => $hasVehicle ? $faker->randomElement(['personal', 'company' , 'rented']) : null,
            ]);

            // ب) اطلاعات سکونت و تماس (متصل به سرپرست)
            Residence::create([
                'guardian_id' => $guardian->id,
                'residence_status_id' => ResidenceStatusType::inRandomOrder()->first()->id,
                'district_id' => District::inRandomOrder()->first()->id,
                'is_local_to_city' => true,
                'deposit_amount' => $faker->numberBetween(50, 500) * 1000000,
                'monthly_rent' => $faker->numberBetween(2, 15) * 1000000,
                'address' => $faker->address(),
            ]);

            Contact::create([
                'guardian_id' => $guardian->id,
                'landline_phone' => $faker->numerify('021########'),
                'messenger_type' => $faker->randomElement(['ایتا', 'واتس‌اپ']),
                'messenger_number' => $guardian->guardian_phone_number,
                'trusted_person_phone' => $faker->numerify('091########'),
            ]);

            // ج) ایجاد ۱ تا ۳ مددجو (Person) برای هر سرپرست
            $childCount = rand(self::MIN_CHILDREN_PER_HOUSEHOLD, self::MAX_CHILDREN_PER_HOUSEHOLD);
            for ($j = 0; $j < $childCount; $j++) {
                $person = Person::create([
                    'guardian_id' => $guardian->id,
                    'first_name' => $faker->firstName(),
                    'last_name' => $guardian->last_name,
                    'national_id' => $faker->unique()->numerify('##########'),
                    'father_name' => $guardian->first_name,
                    'birth_day' => $faker->numberBetween(1, 30),
                    'birth_month' => $faker->numberBetween(1, 12),
                    'birth_year' => $faker->numberBetween(1385, 1402),
                    'father_national_id' => $faker->numerify('##########'),
                    'mother_national_id' => $faker->numerify('##########'),
                    'phone_number' => $faker->numerify('0913#######'),
                    'sadaat_status' => $faker->randomElement(['sadaat','general']),
                    'has_disability' => $faker->randomElement(['0','1']),
                    'disability_type_id' => $faker->randomElement([DisabilityType::inRandomOrder()->first()->id]),
                    'disability_description' => $faker->text(200),
                    'skills_description' => $faker->text(200),
                    'sadaat_relation_id' => $faker->randomElement(SadaatRelation::pluck('id')->toArray()),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'role' => 'child',

                ]);

                // د) وضعیت خانوادگی مددجو
                FamilyStatus::create([
                    'person_id' => $person->id,
                    'guardian_relation_type_id' => GuardianRelationType::inRandomOrder()->first()->id,
                    'remarried_parent' => $faker->randomElement(['both', 'mother', 'father', 'none']),
                    'children_from_previous_marriage' => $faker->numberBetween(0,10),
                    'has_parent_disability' => $faker->randomElement([0,1]),

                ]);

                // ه) اطلاعات تحصیلی
                Education::create([
                    'person_id' => $person->id,
                    'is_studying' => $faker->randomElement([0,1]),
                    'education_level_id' => EducationLevel::inRandomOrder()->first()->id,
                    'school_name' => 'مدرسه ' . $faker->firstName(),
                    'major' => 'رشته ' . $faker->lastName(),
                    'drop_reason' => $faker->text(100),
                    'works_alongside_study' => $faker->randomElement([0,1]),
                    'monthly_income' => $faker->numberBetween(50, 500) * 1000000,
                ]);

                // و) اطلاعات بانکی (شبیه‌سازی منطق فرم: یا برای مددجو یا برای سرپرست)
                $hasOwnAccount = $faker->boolean();
                BankInfo::create([
                    'person_id' => $hasOwnAccount ? $person->id : null,
                    'guardian_id' => $hasOwnAccount ? null : $guardian->id,
                    'bank_id' => Bank::inRandomOrder()->first()->id,
                    'has_own_account' => $hasOwnAccount ? 1 : 0,
                    'account_owner_relation_id' => $hasOwnAccount ? '1' : '2',
                    'card_number' => $faker->numerify('################'),
                    'sheba_number' => 'IR' . $faker->numerify('########################'),
                ]);

                // ز) پوشش حمایتی و سطح نیاز
                SupportCoverage::create([
                    'person_id' => $person->id,
                    'support_organization_id' => SupportOrganization::inRandomOrder()->first()->id,
                    'other_organization_name' => $faker->company(),
                    'coverage_start_year' => $faker->numberBetween(1300, 1405),
                    'coverage_start_month' => $faker->numberBetween(1, 12),
                    'coverage_start_day' => $faker->numberBetween(1, 31),
                ]);

                NeedsLevel::create([
                    'person_id' => $person->id,
                    'need_level_id' => NeedLevelType::inRandomOrder()->first()->id,
                    'evaluation_date' => now(),
                    'reviewer_name' => 'سیستم تست',
                ]);

                // ح) مهارت‌ها و آسیب‌ها (Many-to-Many)
                $person->skills()->sync(Skill::inRandomOrder()->limit(2)->pluck('id'));
                $person->harmTypes()->sync(HarmType::inRandomOrder()->limit(1)->pluck('id'));
            }

            // بروزرسانی تعداد فرزندان در جدول سرپرست
            $guardian->update(['children_count' => $childCount]);
        }

        // Recalculate the counters shown for each social worker after all seeded records exist.
        foreach ($socialWorkers as $socialWorker) {
            $socialWorker->refreshStatistics();
        }
    }

    private function seedLookupTables()
    {
        // ایجاد داده‌های اولیه برای جداولی که کلید خارجی هستند
        if (Occupation::count() == 0) Occupation::create(['title' => 'آزاد']);
        if (JobType::count() == 0) JobType::create(['title' => 'روز مزد']);
        if (InsuranceType::count() == 0) InsuranceType::create(['title' => 'سلامت']);
        if (VehicleType::count() == 0) VehicleType::create(['title' => 'موتورسیکلت']);
        if (ResidenceStatusType::count() == 0) ResidenceStatusType::create(['title' => 'استیجاری']);
        if (District::count() == 0) District::create(['name' => 'منطقه ۱']);
        if (Bank::count() == 0) Bank::create(['name' => 'بانک ملی']);
        if (EducationLevel::count() == 0) EducationLevel::create(['title' => 'هفتم', 'sort_order' => 7]);
        if (NeedLevelType::count() == 0) NeedLevelType::create(['title' => 'بسیار زیاد']);
        if (GuardianRelationType::count() == 0) {
            foreach (['پدر', 'مادر', 'پدربزرگ', 'سایر'] as $t) GuardianRelationType::create(['title' => $t]);
        }
        if (Skill::count() == 0) Skill::create(['title' => 'قالیبافی']);
        if (HarmType::count() == 0) HarmType::create(['title' => 'بیماری خاص']);
        if (SupportOrganization::count() == 0) SupportOrganization::create(['name' => 'کمیته امداد']);
        if (AccountOwnerRelation::count() == 0) AccountOwnerRelation::create(['title' => 'خودش']);
    }
}
