<?php

namespace App\Http\Controllers;

// Form Request for Validation
use App\Http\Requests\StorePersonRequest;
use App\Models\GuardianRelationType;
use App\Models\VehicleType;
use App\Models\Bank;
use App\Models\Person;
use App\Models\Skill;
use App\Models\SocialWorker;
use App\Models\FamilyStatus;
use App\Models\Guardian;
use App\Models\Residence;
use App\Models\BankInfo;
use App\Models\Education;
use App\Models\SupportCoverage;
use App\Models\NeedsLevel;
use App\Models\NeedLevelType;
use App\Models\SadaatRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PersonController extends Controller
{
    /**
     * Show the form for creating a new person.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $socialWorkers    = SocialWorker::all();
        $skills = Skill::all();
        $person = new Person();
        $sadaatRelations  = SadaatRelation::orderBy('sort_order')->get();
        $guardianRelationTypes = GuardianRelationType::all();
        $jobTypes = \App\Models\JobType::all();
        $insuranceTypes = \App\Models\InsuranceType::all();
        $vehicleTypes = VehicleType::all();
        $accountRelations = \App\Models\AccountOwnerRelation::all();
        $needLevelTypes = NeedLevelType::all();
        $banks = Bank::all();

        $deciles = [
            '1'  => 'دهک یک',
            '2'  => 'دهک دو',
            '3'  => 'دهک سه',
            '4'  => 'دهک چهار',
            '5'  => 'دهک پنج',
            '6'  => 'دهک شش',
            '7'  => 'دهک هفت',
            '8'  => 'دهک هشت',
            '9'  => 'دهک نه',
            '10' => 'دهک ده',
        ];

        return view('people.create', compact(
            'deciles',
            'socialWorkers',
            'sadaatRelations',
            'skills',
            'person',
            'guardianRelationTypes',
            'jobTypes',
            'insuranceTypes',
            'vehicleTypes',
            'accountRelations',
            'banks',
            'needLevelTypes'
        ));
    }

    /**
     * Store a newly created person and all related data in storage.
     *
     * @param  \App\Http\Requests\StorePersonRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePersonRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            // Handle file uploads
            $paths = [];
            $fileFields = ['photo_id_card', 'photo_birth_certificate', 'support_card_image'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $paths[$field] = $request->file($field)->store('uploads/' . $field, 'public');
                } else {
                    $paths[$field] = null;
                }
            }

            // 1. Create Person
            $person = Person::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'national_id' => $validatedData['national_id'],
                'birth_day'    => $validatedData['birth_day'],
                'birth_month'  => $validatedData['birth_month'],
                'birth_year'   => $validatedData['birth_year'],
                'father_name' => $validatedData['father_name'],
                'father_national_id' => $validatedData['father_national_id'],
                'mother_national_id' => $validatedData['mother_national_id'],
                'gender' => $validatedData['gender'],
                'role' => $validatedData['role'],
                'sadaat_status' => $validatedData['sadaat_status'],
                'sadaat_relation_id'   => $validatedData['sadaat_relation_id'] ?? null,
//                'skills' => $validatedData['skills'] ?? [],
                'skills_description' => $validatedData['skills_description'] ?? null,
                'social_worker_id' => $validatedData['social_worker_id'],
                'photo_id_card' => $paths['photo_id_card'],
                'photo_birth_certificate' => $paths['photo_birth_certificate'],
                'has_disability' => $request->boolean('has_disability'),
                'disability_type_id' => $validatedData['disability_type_id'] ?? null,
                'disability_description' => $validatedData['disability_description'] ?? null,
            ]);



            // 2. Create FamilyStatus
            FamilyStatus::create(['person_id' => $person->id] + [
                    'guardian_relation_type_id' => $request->input('guardian_relation_type_id'),
                    'economic_decile' => $validatedData['economic_decile'] ?? null,
                    'living_parents' => $validatedData['living_parents'] ?? null,
                    'deceased_parent' => $validatedData['deceased_parent'] ?? null,
                    'death_year' => $validatedData['death_year'] ?? null,
                    'death_reason' => $validatedData['death_reason'] ?? null,
                    'divorced_parent' => $validatedData['divorced_parent'] ?? null,
                    'remarried_parent' => $validatedData['remarried_parent'] ?? null,
                    'children_from_previous_marriage' => $validatedData['children_from_previous_marriage'] ?? null,
                    'has_parent_disability' => $request->boolean('has_parent_disability'),
                    'parent_disability_description' => $validatedData['parent_disability_description'] ?? null,
                ]);


            // 3. Create GuardianInfo
            Guardian::create(['person_id' => $person->id] + $request->only([
                    'occupation_id' => $request->input('occupation_id'),
                    'guardian_birth_date',
                    'job_type_id' => $request->input('job_type_id'),
                    'guardian_phone_number', 'children_count', 'children_in_house',
                    'insurance_status',
                    'insurance_type_id',
                    'divorced_child_at_home',
                    'average_income',
                    'any_family_employed',
                    'has_vehicle',
                    'vehicle_type'
                ]));

            // 4. Create ResidenceContact
            Residence::create(['person_id' => $person->id] + $request->only([
                    'residence_status_id' => $request->residence_status_id,
                    'district_id'          => $request->district_id,
                    'is_local_to_city'     => $request->is_local_to_city,
                    'deposit_amount'       => $request->deposit_amount,
                    'monthly_rent'         => $request->monthly_rent,
                    'residence_duration_years' => $request->residence_duration_years,
                    'address'              => $request->address,
                    'personal_phone',
                    'landline_phone',
                    'trusted_person_phone',
                    'messenger_type',
                    'messenger_number'
                ]));

            // 5. Create BankInfo
            BankInfo::create(['person_id' => $person->id] + $request->only([
                    'has_own_account', 'account_holder_relation', 'bank_id', 'card_number',
                    'sheba_number', 'subsidy_card_number', 'subsidy_sheba_number'
                ]));

            // 6. Create EducationInfo
            Education::create(['person_id' => $person->id] + $request->only([
                    'is_studying', 'school_name', 'major', 'education_level_id', 'drop_reason',
                    'works_alongside_study', 'monthly_income', 'talent_description'
                ]));

            // 7. Create SupportCoverage
            SupportCoverage::create([
                'person_id' => $person->id,
                'organization_type' => $validatedData['organization_type'] ?? null,
                'organization_name' => $validatedData['organization_name'] ?? null,
                'coverage_start_date' => $validatedData['coverage_start_date'] ?? null,
                'support_card_image' => $paths['support_card_image']
            ]);

            // 8. Create NeedsLevel
            NeedsLevel::create([
                'person_id' => $person->id] + $request->only([
                    'need_level_id'   => $request->input('need_level_id'), // دریافت شناسه از فرم
                    'evaluation_date' => now(),
                    'reviewer_name'   => 'کاربر ارشد',   // ثبت پیش‌فرض نام کاربر
                ]));

            // Note: HealthInfo (9) and SkillsInfo (10) are not in the form yet.


            $skillIds = $request->input('skills', []);
            $person->skills()->sync($skillIds);

            DB::commit();

            return redirect()->route('people.create')->with('success', 'اطلاعات مددجو با موفقیت ثبت شد.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing person: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'خطایی در هنگام ثبت اطلاعات رخ داد. لطفاً دوباره تلاش کنید.')
                ->withInput();
        }

    }


    public function edit(Person $person)
    {
//        $socialWorkers = SocialWorker::all();
//        $skills = Skill::all();
//        return view('people.edit', compact('person','socialWorkers','skills'));
    }
}
