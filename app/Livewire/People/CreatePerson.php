<?php

namespace App\Livewire\People;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use App\Models\Person;
use App\Models\FamilyStatus;
use App\Models\Guardian;
use App\Models\Residence;
use App\Models\BankInfo;
use App\Models\Education;
use App\Models\SupportCoverage;
use App\Models\NeedsLevel;
use App\Models\NeedLevelType;
use App\Models\SocialWorker;
use App\Models\SadaatRelation;
use App\Models\DisabilityType;
use App\Models\Skill;
use App\Models\GuardianRelationType;
use App\Models\Occupation;
use App\Models\JobType;
use App\Models\InsuranceType;
use App\Models\VehicleType;
use App\Models\ResidenceStatusType;
use App\Models\District;
use App\Models\AccountOwnerRelation;
use App\Models\Bank;
use App\Models\EducationLevel;

class CreatePerson extends Component
{
    use WithFileUploads;

    // --- 1. تعریف متغیرهای اطلاعات فردی ---
    public $first_name;
    public $last_name;
    public $national_id;

    // تاریخ تولد (۳ بخش مجزا)
    public $birth_day;
    public $birth_month;
    public $birth_year;

    public $father_name;
    public $father_national_id;
    public $mother_national_id;
    public $gender;

    // سادات (پیش‌فرض عام)
    public $role = 'فرزند'; // مقدار پیش‌فرض
    public $sadaat_status = 'عام'; // مقدار پیش‌فرض
    public $sadaat_relation_id;
    public $social_worker_id;

    // فایل‌ها
    public $photo_id_card;
    public $photo_birth_certificate;

    // معلولیت (پیش‌فرض 0 یا رشته '0' برای رادیو)
    public $has_disability = '0';
    public $disability_type_id;
    public $disability_description;
    public $skills_description;


    // مهارت‌ها
    public $skills = []; // آرایه برای ذخیره شناسه مهارت‌های انتخاب شده (تیک خورده)




    // --- 2. تعریف متغیرهای وضعیت خانوادگی ---
    public $guardian_relation_type_id;
    public $economic_decile;
    public $living_parents;
    public $deceased_parent;

    public $death_year;
    public $death_reason;

    public $divorced_parent;
    public $remarried_parent;
    public $children_from_previous_marriage;

    public $has_parent_disability;
    public $parent_disability_description;

    // --- 3. تعریف متغیرهای اطلاعات سرپرست ---
    // تاریخ تولد سرپرست
    public $guardian_birth_day;
    public $guardian_birth_month;
    public $guardian_birth_year;
    public $occupation_id;
    public $job_type_id;
    public $guardian_phone_number;
    public $children_count;
    public $children_in_house;
    public $insurance_status = '0';
    public $insurance_type_id;

    public $divorced_child_at_home; // متن یا عدد

    public $average_income;
    public $any_family_employed;

    public $has_vehicle = '0';
    public $vehicle_type_id;

    // --- 4. تعریف متغیرهای سکونت و تماس ---
    public $residence_status_id;
    public $district_id;
    public $is_local_to_city = '1';
    public $deposit_amount;
    public $monthly_rent;
    public $residence_duration_years;

    public $address;
    public $personal_phone;
    public $landline_phone;
    public $trusted_person_phone;
    public $messenger_type;
    public $messenger_number;



    // --- 5. متغیرهای مالی، تحصیلی و حمایتی ---

    // بانک
    public $has_own_account = '0'; // پیش‌فرض خیر
    public $account_owner_relation_id;
    public $other_account_owner_relation; // توضیح نسبت سایر
    public $bank_id;
    public $card_number;
    public $sheba_number;
    public $subsidy_card_number;
    public $subsidy_sheba_number;

    // تحصیل
    public $is_studying = '0'; // پیش‌فرض خیر
    public $school_name;
    public $major;
    public $education_level_id;
    public $drop_reason;
    public $works_alongside_study = '0';
    public $monthly_income;

    // حمایت
    public $organization_type;
    public $coverage_start_day;
    public $coverage_start_month;
    public $coverage_start_year;
    public $support_card_image;

    public $need_level_id;

    // --- هوک‌ها (Hooks) برای منطق خودکار ---

    /**
     * وقتی وضعیت "حساب شخصی دارد" تغییر می‌کند
     */
    public function updatedHasOwnAccount($value)
    {
        if ($value == '1') {
            // فرض می‌کنیم در دیتابیس آیدی رابطه 'خود مددجو' برابر 1 است
            // یا می‌توانید کوئری بزنید: AccountOwnerRelation::where('name', 'مددجو')->first()->id
            $this->account_owner_relation_id = 1;
        } else {
            $this->account_owner_relation_id = null;
        }
    }


    // --- متد Save (ذخیره نهایی) ---
    public function save()
    {
        // 1. اعتبارسنجی کامل و جامع (Validation)
        // این بخش تضمین می‌کند داده‌های ورودی تمیز و استاندارد هستند
        $this->validate([
            // --- بخش 1: اطلاعات فردی ---
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => 'required|string|digits:10|unique:people,national_id',
            'birth_day' => 'required|integer|between:1,31',
            'birth_month' => 'required|integer|between:1,12',
            'birth_year' => 'required|integer|between:1300,1450',
            'father_name' => 'required|string|max:255',
            'father_national_id' => 'nullable|string|digits:10',
            'mother_national_id' => 'nullable|string|digits:10',
            'gender' => 'required|in:مرد,زن',
            'role' => 'required|in:فرزند,سرپرست',
            'social_worker_id' => 'required|exists:social_workers,id',

            'sadaat_status' => 'required|in:عام,سادات',
            'sadaat_relation_id' => 'nullable|required_if:sadaat_status,سادات|exists:sadaat_relations,id',

            'has_disability' => 'required|boolean',
            'disability_type_id' => 'nullable|required_if:has_disability,1|exists:disability_types,id',
            'disability_description' => 'nullable|string|max:1000',

            'skills_description' => 'nullable|string|max:1000',
            'skills' => 'nullable|array',

            'photo_id_card' => 'nullable|image|max:2048',
            'photo_birth_certificate' => 'nullable|image|max:2048',
            'support_card_image' => 'nullable|image|max:2048',

            // --- بخش 2: وضعیت خانوادگی ---
            'guardian_relation_type_id' => 'nullable|exists:guardian_relation_types,id',
            'economic_decile' => 'nullable|integer|between:1,10',
            'living_parents' => 'nullable|in:both_alive,father_dead,mother_dead,both_dead',
            'divorced_parent' => 'nullable|in:none,divorced',
            'death_year' => 'nullable|integer|digits:4',
            'death_reason' => 'nullable|string|max:255',
            'remarried_parent' => 'nullable|in:none,father,mother,both',
            'children_from_previous_marriage' => 'nullable|integer|min:0',
            'has_parent_disability' => 'nullable|boolean',
            'parent_disability_description' => 'nullable|string|max:1000',

            // --- بخش 3: اطلاعات سرپرست ---
            'guardian_birth_day' => 'nullable|integer|between:1,31',
            'guardian_birth_month' => 'nullable|integer|between:1,12',
            'guardian_birth_year' => 'nullable|integer|between:1300,1450',
            'occupation_id' => 'required|exists:occupations,id',
            'job_type_id' => 'nullable|exists:job_types,id',
            'guardian_phone_number' => 'nullable|string|max:20',
            'children_count' => 'nullable|integer|min:0',
            'children_in_house' => 'nullable|integer|min:0',
            'insurance_status' => 'required|boolean',
            'insurance_type_id' => 'nullable|required_if:insurance_status,1|exists:insurance_types,id',
            'divorced_child_at_home' => 'nullable|string|in:ندارد,پسر,دختر,پسر / دختر',
            'average_income' => 'nullable|integer|min:0',
            'any_family_employed' => 'required|boolean',
            'has_vehicle' => 'required|boolean',
            'vehicle_type_id' => 'nullable|required_if:has_vehicle,1|exists:vehicle_types,id',

            // --- بخش 4: سکونت و تماس ---
            'residence_status_id' => 'required|exists:residence_status_types,id',
            'district_id' => 'nullable|exists:districts,id',
            'is_local_to_city' => 'required|boolean',
            'deposit_amount' => 'nullable|integer|min:0',
            'monthly_rent' => 'nullable|integer|min:0',
            'residence_duration_years' => 'nullable|integer|min:0',
            'address' => 'required|string|max:1000',
            'personal_phone' => 'required|string|max:20',
            'landline_phone' => 'nullable|string|max:20',
            'trusted_person_phone' => 'nullable|string|max:20',
            'messenger_type' => 'nullable|string|max:50',
            'messenger_number' => 'nullable|string|max:50',

            // --- بخش 5: مالی و تحصیلی ---
            'has_own_account' => 'required|boolean',
            'account_owner_relation_id' => 'nullable|exists:account_owner_relations,id',
            'bank_id' => 'nullable|exists:banks,id',
            'card_number' => 'nullable|string|digits:16',
            'sheba_number' => 'nullable|string|max:26',
            'subsidy_card_number' => 'nullable|string|digits:16',
            'subsidy_sheba_number' => 'nullable|string|max:26',

            'is_studying' => 'required|boolean',
            'school_name' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'education_level_id' => 'nullable|exists:education_levels,id',
            'drop_reason' => 'nullable|string|max:1000',
            'works_alongside_study' => 'required|boolean',
            'monthly_income' => 'nullable|integer|min:0',

            // --- بخش 6: سطح نیاز و پوشش ---
            'organization_type' => 'nullable|string|max:255',
            'coverage_start_day' => 'nullable|integer|between:1,31',
            'coverage_start_month' => 'nullable|integer|between:1,12',
            'coverage_start_year' => 'nullable|integer|between:1300,1450',
            'need_level_id' => 'required|exists:need_level_types,id',
        ]);

        DB::beginTransaction();

        try {
            // --- 1. مدیریت آپلود فایل‌ها ---
            $paths = [
                'photo_id_card' => $this->photo_id_card ? $this->photo_id_card->store('uploads/photo_id_card', 'public') : null,
                'photo_birth_certificate' => $this->photo_birth_certificate ? $this->photo_birth_certificate->store('uploads/photo_birth_certificate', 'public') : null,
                'support_card_image' => $this->support_card_image ? $this->support_card_image->store('uploads/support_card_image', 'public') : null,
            ];

            // --- 2. آماده‌سازی تاریخ‌ها ---
            $birthDateFull = sprintf('%04d/%02d/%02d', $this->birth_year, $this->birth_month, $this->birth_day);

            $guardianBirthDateFull = null;
            if ($this->guardian_birth_year && $this->guardian_birth_month && $this->guardian_birth_day) {
                $guardianBirthDateFull = sprintf('%04d/%02d/%02d', $this->guardian_birth_year, $this->guardian_birth_month, $this->guardian_birth_day);
            }

            $coverageDateFull = null;
            if ($this->coverage_start_year && $this->coverage_start_month && $this->coverage_start_day) {
                $coverageDateFull = sprintf('%04d/%02d/%02d', $this->coverage_start_year, $this->coverage_start_month, $this->coverage_start_day);
            }

            // --- 3. ذخیره در جداول ---

            // جدول 1: Person
            $person = Person::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_id' => $this->national_id,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'birth_date_full' => $birthDateFull,
                'father_name' => $this->father_name,
                'father_national_id' => $this->father_national_id, // خودکار null می‌شود اگر خالی باشد
                'mother_national_id' => $this->mother_national_id,
                'gender' => $this->gender,
                'role' => $this->role,
                'sadaat_status' => $this->sadaat_status,
                // فقط اگر سادات باشد مقدار ذخیره شود
                'sadaat_relation_id' => ($this->sadaat_status === 'سادات') ? $this->sadaat_relation_id : null,
                'skills_description' => $this->skills_description,
                'social_worker_id' => $this->social_worker_id,
                'photo_id_card' => $paths['photo_id_card'],
                'photo_birth_certificate' => $paths['photo_birth_certificate'],
                'has_disability' => $this->has_disability,
                // فقط اگر معلولیت دارد ذخیره شود
                'disability_type_id' => ($this->has_disability == '1') ? $this->disability_type_id : null,
                'disability_description' => ($this->has_disability == '1') ? $this->disability_description : null,
            ]);

            // جدول 2: FamilyStatus
            FamilyStatus::create([
                'person_id' => $person->id,
                // استفاده از ?: null برای اطمینان از اینکه رشته خالی تبدیل به NULL شود (برای Foreign Key)
                'guardian_relation_type_id' => $this->guardian_relation_type_id ?: null,
                'economic_decile' => $this->economic_decile ?: null,
                'living_parents' => $this->living_parents,
                'deceased_parent' => $this->deceased_parent,
                'death_year' => $this->death_year,
                'death_reason' => $this->death_reason,
                'divorced_parent' => $this->divorced_parent,
                'remarried_parent' => $this->remarried_parent,
                // تبدیل صریح به int برای اینکه مقادیر خالی 0 ذخیره شوند
                'children_from_previous_marriage' => (int) $this->children_from_previous_marriage,
                'has_parent_disability' => $this->has_parent_disability,
                'parent_disability_description' => ($this->has_parent_disability == '1') ? $this->parent_disability_description : null,
            ]);

            // جدول 3: Guardian
            Guardian::create([
                'person_id' => $person->id,
                'guardian_birth_day' => $this->guardian_birth_day ?: null,
                'guardian_birth_month' => $this->guardian_birth_month ?: null,
                'guardian_birth_year' => $this->guardian_birth_year ?: null,
                'guardian_birth_date_full' => $guardianBirthDateFull,
                'occupation_id' => $this->occupation_id,
                'job_type_id' => $this->job_type_id ?: null,
                'guardian_phone_number' => $this->guardian_phone_number,
                // مقادیر عددی پیش‌فرض 0
                'children_count' => (int) $this->children_count,
                'children_in_house' => (int) $this->children_in_house,
                'insurance_status' => $this->insurance_status,
                'insurance_type_id' => ($this->insurance_status == '1') ? $this->insurance_type_id : null,
                'divorced_child_at_home' => $this->divorced_child_at_home ?: 'ندارد',
                'average_income' => (int) $this->average_income,
                'any_family_employed' => $this->any_family_employed,
                'has_vehicle' => $this->has_vehicle,
                'vehicle_type_id' => ($this->has_vehicle == '1') ? $this->vehicle_type_id : null,
            ]);

            // جدول 4: Residence
            Residence::create([
                'person_id' => $person->id,
                'residence_status_id' => $this->residence_status_id,
                'district_id' => $this->district_id ?: null,
                'is_local_to_city' => $this->is_local_to_city,
                'deposit_amount' => (int) $this->deposit_amount,
                'monthly_rent' => (int) $this->monthly_rent,
                'residence_duration_years' => (int) $this->residence_duration_years,
                'address' => $this->address,
                'personal_phone' => $this->personal_phone,
                'landline_phone' => $this->landline_phone,
                'trusted_person_phone' => $this->trusted_person_phone,
                'messenger_type' => $this->messenger_type,
                'messenger_number' => $this->messenger_number,
            ]);

            // جدول 5: BankInfo
            BankInfo::create([
                'person_id' => $person->id,
                'has_own_account' => $this->has_own_account,
                'account_owner_relation_id' => $this->account_owner_relation_id ?: null,
                'bank_id' => $this->bank_id ?: null,
                'card_number' => $this->card_number,
                'sheba_number' => $this->sheba_number,
                'subsidy_card_number' => $this->subsidy_card_number,
                'subsidy_sheba_number' => $this->subsidy_sheba_number,
            ]);

            // جدول 6: Education
            Education::create([
                'person_id' => $person->id,
                'is_studying' => $this->is_studying,
                'school_name' => $this->school_name,
                'major' => $this->major,
                'education_level_id' => $this->education_level_id ?: null,
                'drop_reason' => $this->drop_reason,
                'works_alongside_study' => $this->works_alongside_study,
                'monthly_income' => (int) $this->monthly_income,
            ]);

            // جدول 7: SupportCoverage
            SupportCoverage::create([
                'person_id' => $person->id,
                'organization_type' => $this->organization_type,
                'coverage_start_day' => $this->coverage_start_day ?: null,
                'coverage_start_month' => $this->coverage_start_month ?: null,
                'coverage_start_year' => $this->coverage_start_year ?: null,
                'coverage_start_date' => $coverageDateFull,
                'support_card_image' => $paths['support_card_image'],
            ]);

            // جدول 8: NeedsLevel
            NeedsLevel::create([
                'person_id' => $person->id,
                'need_level_id' => $this->need_level_id,
                'evaluation_date' => now(),
                'reviewer_name' => auth()->user()->name ?? 'سیستم',
            ]);


            if (!empty($this->skills)) {
                $person->skills()->sync($this->skills);
            }

            DB::commit();

            session()->flash('success', 'اطلاعات مددجو با موفقیت ثبت و ذخیره شد.');
            return redirect()->route('people.create');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Create Person Error: ' . $e->getMessage());
            // نمایش پیام خطای ساده به کاربر (جزئیات فنی را فقط لاگ می‌کنیم)
            session()->flash('error', 'خطا در ثبت اطلاعات. لطفاً ورودی‌ها را بررسی کنید.');
        }
    }

    public function render()
    {
        // دریافت اطلاعات مورد نیاز برای دراپ‌داون‌ها
        $socialWorkers = SocialWorker::all();
        $sadaatRelations = SadaatRelation::orderBy('sort_order')->get();
        $disabilityTypes = DisabilityType::all();
        $guardianRelationTypes = GuardianRelationType::all();
        $occupations = Occupation::all();
        $jobTypes = JobType::all();
        $insuranceTypes = InsuranceType::all();
        $vehicleTypes = VehicleType::all();
        $residenceStatusTypes = ResidenceStatusType::all();
        $districts = District::all();
        $accountRelations = AccountOwnerRelation::all();
        $banks = Bank::all();
        $educationLevels = EducationLevel::orderBy('sort_order')->get();
        $needLevelTypes = NeedLevelType::all();

        // آرایه دهک‌ها برای سادگی
        $deciles = [
            '1' => 'دهک یک', '2' => 'دهک دو', '3' => 'دهک سه', '4' => 'دهک چهار',
            '5' => 'دهک پنج', '6' => 'دهک شش', '7' => 'دهک هفت', '8' => 'دهک هشت',
            '9' => 'دهک نه', '10' => 'دهک ده',
        ];

        // لیست مهارت‌ها (برای سینک کردن در انتها)
        $allSkills = \App\Models\Skill::all();

        return view('livewire.people.create-person', compact(
            'socialWorkers',
            'sadaatRelations',
            'disabilityTypes','guardianRelationTypes', 'deciles',
            'occupations', 'jobTypes', 'insuranceTypes', 'vehicleTypes',
            'residenceStatusTypes', 'districts', 'accountRelations', 'banks', 'educationLevels'
            , 'needLevelTypes', 'allSkills'
        ))
            ->extends('layouts.app')
            ->section('content');
    }
}
