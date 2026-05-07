<?php

namespace App\Livewire\People;

use AllowDynamicProperties;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use App\Models\Person;
use App\Models\FamilyStatus;
use App\Models\Guardian;

// اطمینان حاصل کنید که این مدل import شده است
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
use App\Models\HarmType;
use App\Models\GuardianRelationType;
use App\Models\Occupation;
use App\Models\JobType;
use App\Models\InsuranceType;
use App\Models\VehicleType;
use App\Models\ResidenceStatusType;
use App\Models\District;
use App\Models\AccountOwnerRelation;
use App\Models\SupportOrganization;
use App\Models\Bank;
use App\Models\EducationLevel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;


// برای استفاده از Rule::requiredIf
use App\Helpers\Carbon\Carbon;

// برای تبدیل تاریخ شمسی به میلادی
#[AllowDynamicProperties]
#[Layout('layouts.app')] // این خط را اضافه کنید
class CreatePerson extends Component
{
    use WithFileUploads;
    public $is_submitted = false;
    public $show_step_error_badges = false;

    // Wizard state properties
    public $current_step = 1;
    public $total_steps = 10;
    public $completed_steps = [];
    public $wizard_steps = [
        1 => 'اطلاعات فردی',
        2 => 'مهارت‌ها و استعدادها',
        3 => 'اطلاعات معلولیت',
        4 => 'مدارک شناسایی',
        5 => 'وضعیت تحصیلی',
        6 => 'وضعیت خانوادگی',
        7 => 'اطلاعات سرپرست و معیشت',
        8 => 'اطلاعات بانکی',
        9 => 'وضعیت سکونت و تماس',
        10 => 'سطح نیازمندی و پوشش حمایتی',
    ];

    // --- 1. تعریف متغیرهای اطلاعات فردی مددجو ---
    public $first_name;

    public $last_name;
    public $national_id;

    public $person_id = null; // اضافه کردن این خط در کنار سایر public ها

    public $birth_day;
    public $birth_month;
    public $birth_year;

    public $father_name;
    public $father_national_id;
    public $mother_national_id;
    public $phone_number;
    public $gender;

    // سادات (پیش‌فرض عام)
    public $role = 'child'; // مقدار پیش‌فرض
    public $sadaat_status = 'general'; // مقدار پیش‌فرض
    public $sadaat_relation_id;
    public $social_worker_id;

    // فایل‌ها
    public $photo_id_card;
    public $photo_birth_certificate;


    public $profile_photo;          // برای آپلود فایل (Temporary Uploaded File)
    public $captured_photo_base64; // برای ذخیره رشته تصویر دوربین (Base64)

    public $existing_photo_id_card = null;       // مسیر تصویر کارت ملی ذخیره شده
    public $existing_photo_birth_certificate = null; // (اختیاری) برای شناسنامه هم
    public $existing_profile_photo = null;           // (اختیاری) برای عکس پروفایل هم

    public $captured_id_card_base64;          // برای تصویر دوربین کارت ملی
    public $captured_support_card_base64;
    public $captured_birth_certificate_base64;
    // معلولیت (پیش‌فرض 0 یا رشته '0' برای رادیو)
    public $has_disability = 0;
    public $disability_type_id;
    public $disability_description;
    public $skills_description;

    public $harm_types = [];        // لیست IDهایی که کاربر انتخاب می‌کند
    public $allHarmTypes;           // جهت نمایش در فرم
    public bool $showFatherSuggestions = false;

    // مهارت‌ها
    public $skills = []; // آرایه برای ذخیره شناسه مهارت‌های انتخاب شده (تیک خورده)

    // --- 2. تعریف متغیرهای وضعیت خانوادگی ---
    public $guardian_relation_type_id;
    public $economic_decile;
    public $remarried_parent = 'none';
    public $children_from_previous_marriage;

    public $has_parent_disability = false; // مقدار پیش‌فرض Boolean
    public $parent_disability_description;

    // --- 3. تعریف متغیرهای اطلاعات سرپرست ---
    public $guardian_national_code; // کد ملی سرپرست برای جستجو
    public $guardian_first_name;    // نام سرپرست
    public $guardian_last_name;     // نام خانوادگی سرپرست
    // تاریخ تولد سرپرست
    public $guardian_birth_day;
    public $guardian_birth_month;
    public $guardian_birth_year;

    public $occupation_id;
    public $guardian_exists_in_db = false;
    public $current_guardian_id = null;
    public $is_programmatic_guardian_lookup = false;

    // سایر متغیرهای فرم که در مراحل بعدی استفاده می‌شوند
    public $job_type_id;
    public $guardian_phone_number;
    public $children_count;
    public $children_in_house;
    public $extra_household_members = [];
    public $new_extra_household_member_description = '';
    public $insurance_status = '0';
    public $insurance_type_id;
    public $divorced_child_at_home = 'none';
    public $average_income;
    public $any_family_employed = '0';
    public $any_family_employed_description;

    public $has_vehicle = '0';
    public $vehicle_type_id;
    public $vehicle_ownership_type = 'personal';

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
    public $has_own_account; // پیش‌فرض بله
    public $account_owner_relation_id;
    public $other_account_owner_relation; // توضیح نسبت سایر
    public $bank_id;
    public $card_number = '';
    public $sheba_number = '';
    public $subsidy_card_number = '';
    public $subsidy_sheba_number = '';

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
    public $support_organizations; // برای نگه داشتن لیست در listBox
    public $support_organization_id; // برای ذخیره ID انتخاب شده
    public $other_organization_name; // برای ذخیره نام خیریه دستی
    public $coverage_start_day;
    public $coverage_start_month;
    public $coverage_start_year;
    public $support_card_image;

    public $need_level_id;

    public $sadaatRelations;
    public $allSkills;
    public $disabilityTypes;
    public $socialWorkers;

    public $allow_social_worker_edit = false;
    public $guardianRelationTypes;
    public $occupations;
    public $deciles;
    public $jobTypes; // <--- این و موارد بعدی باید public تعریف شوند
    public $insuranceTypes;
    public $vehicleTypes;
    public $residenceStatusTypes;
    public $districts;
    public $accountRelations;
    public $banks;
    public $educationLevels;
    public $needLevelTypes;

    public ?Person $person = null;
    public string $mode;
    public bool $embedded = false;

    public function mount(string $mode, ?Person $person = null, bool $embedded = false)
    {
        $this->person = $person;
        $this->mode = $mode;
        $this->embedded = $embedded;
        $this->current_step = 1;
        $this->completed_steps = [];

        $this->guardian = Guardian::with('bankInfo')->where('national_code', $this->guardian_national_code)->first();
        $this->sadaatRelations = SadaatRelation::orderBy('sort_order')->get();
        $this->allSkills = Skill::all();
        $this->disabilityTypes = DisabilityType::all();
        $this->socialWorkers = SocialWorker::all();
        $this->guardianRelationTypes = GuardianRelationType::all();
        $this->occupations = Occupation::all();
        $this->jobTypes = JobType::all(); // اضافه شده به mount
        $this->insuranceTypes = InsuranceType::all(); // اضافه شده به mount
        $this->vehicleTypes = VehicleType::all(); // اضافه شده به mount
        $this->residenceStatusTypes = ResidenceStatusType::all(); // اضافه شده به mount
        $this->districts = District::all(); // اضافه شده به mount
        $this->accountRelations = AccountOwnerRelation::all(); // اضافه شده به mount
        $this->banks = Bank::all(); // اضافه شده به mount
        $this->educationLevels = EducationLevel::orderBy('sort_order')->get(); // اضافه شده به mount
        $this->needLevelTypes = NeedLevelType::all(); // اضافه شده به mount
        $this->allHarmTypes = HarmType::all();
        $this->support_organizations = SupportOrganization::all();


        // آرایه دهک‌ها (داده‌های ثابت)
        $this->deciles = [
            '1' => 'دهک ۱', '2' => 'دهک ۲', '3' => 'دهک ۳', '4' => 'دهک ۴', '5' => 'دهک ۵',
            '6' => 'دهک ۶', '7' => 'دهک ۷', '8' => 'دهک ۸', '9' => 'دهک ۹', '10' => 'دهک ۱۰',
        ];



        if ($this->mode == "edit" && $person) {
            $this->person_id = $person->id;
            $this->first_name = $this->person->first_name;
            $this->last_name = $this->person->last_name;
            $this->national_id = $this->person->national_id;
            $this->birth_day = $this->person->birth_day;
            $this->birth_month = $this->person->birth_month;
            $this->birth_year = $this->person->birth_year;
            $this->birth_date_full = $this->person->birth_date_full;
            $this->father_name = $this->person->father_name;
            $this->father_national_id = $this->person->father_national_id;
            $this->mother_national_id = $this->person->mother_national_id;
            $this->phone_number = $this->person->phone_number;
            $this->gender = $this->person->gender;
            $this->role = $this->person->role;
            $this->sadaat_status = $this->person->sadaat_status;
            $this->sadaat_relation_id = $this->person->sadaat_relation_id;
            $this->has_disability = (int)$this->person->has_disability;
            if($this->has_disability) {
                $this->disability_type_id = $this->person->disability_type_id;
                $this->disability_description = $this->person->disability_description;
            }
            $this->skills_description = $this->person->skills_description;

            $this->existing_photo_id_card = $this->person->photo_id_card;
            $this->existing_photo_birth_certificate = $this->person->photo_birth_certificate;
            $this->existing_profile_photo = $this->person->profile_photo;


            // Education data (with null check)
            if ($this->person->education) {
                $this->is_studying = $this->person->education->is_studying ? '1' : '0';
                $this->school_name = $this->person->education->school_name ?? '';
                $this->education_level_id = $this->person->education->education_level_id;
                $this->major = $this->person->education->major ?? '';
                $this->drop_reason = $this->person->education->drop_reason ?? '';
                $this->works_alongside_study = $this->person->education->works_alongside_study ? '1' : '0';
                $this->monthly_income = $this->person->education->monthly_income ?? '';
            }

            // Family status data (with null check)
            if ($this->person->familyStatus) {
                $this->guardian_relation_type_id = $this->person->familyStatus->guardian_relation_type_id;
                $this->remarried_parent = $this->person->familyStatus->remarried_parent;
                $this->children_from_previous_marriage = $this->person->familyStatus->children_from_previous_marriage;
                $this->has_parent_disability = (bool)$this->person->familyStatus->has_parent_disability;
                $this->parent_disability_description = $this->person->familyStatus->parent_disability_description;
            }


            // Guardian data (with null check)
            if ($this->person->guardian) {
                $this->guardian_national_code = $this->person->guardian->national_code;
                $this->_populateGuardianFieldsFromModel($this->person->guardian);
            }

            // Harm types (with null check)
            if ($this->person->harmTypes) {
                $this->harm_types = $this->person->harmTypes->pluck('id')->map(fn($id) => (string)$id)->toArray();
            }

            // Skills (with null check)
            if ($this->person->skills) {
                $this->skills = $this->person->skills->pluck('id')->toArray();
            }

            if ($this->person->bankInfo)
            {
                $this->has_own_account = (bool) $this->person->bankInfo->has_own_account;
                $this->updateBankInfoFromGuardian();
            }
            else
            {
                $this->has_own_account = 0; // مقدار پیش‌فرض
                $this->account_owner_relation_id = 2;
                $this->updateBankInfoFromGuardian();
            }



            // Support coverage (with null check)
            if ($this->person->supportCoverage) {
                $this->support_organization_id = $this->person->supportCoverage->support_organization_id;
                $this->other_organization_name = $this->person->supportCoverage->other_organization_name;
                $this->coverage_start_day = $this->person->supportCoverage->coverage_start_day;
                $this->coverage_start_month = $this->person->supportCoverage->coverage_start_month;
                $this->coverage_start_year = $this->person->supportCoverage->coverage_start_year;

            }


            if($this->person->needsLevel){
                $this->need_level_id = $this->person->needsLevel->need_level_id;
            }
        }
    }

    public function updatedCardNumber($value)
    {
        $this->subsidy_card_number = $value;
    }

    public function updatedShebaNumber($value)
    {
        $this->subsidy_sheba_number = $value;
    }

    public function updatedNationalId($value)
    {
        // اعتبارسنجی فقط همین فیلد
        $this->validateOnly('national_id', [
            'national_id' => ['nullable', 'digits:10'],
        ]);
    }

    public function updatedFatherNationalId($value): void
    {
        $this->showFatherSuggestions = true;
        $fatherNationalId = preg_replace('/\D+/', '', trim((string)$value));
        $this->father_national_id = $fatherNationalId;

        if (strlen($fatherNationalId) !== 10) {
            $this->resetValidation('father_national_id');
            return;
        }

        $this->validateOnly('father_national_id', [
            'father_national_id' => ['nullable', 'digits:10'],
        ]);

        $query = Person::query()
            ->where('father_national_id', $fatherNationalId)
            ->whereNotNull('father_name')
            ->where('father_name', '!=', '');

        if (!empty($this->person?->id)) {
            $query->where('id', '!=', $this->person->id);
        }

        $existingFatherName = $query->orderByDesc('id')->value('father_name');

        if ($existingFatherName) {
            $this->father_name = trim((string)$existingFatherName);
        }
    }

    public function getFatherSuggestionsProperty()
    {
        $search = trim((string)$this->father_national_id);
        if ($search === '' || strlen($search) < 4) {
            return collect();
        }

        $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(father_name, '')"
            : "COALESCE(father_name, '')";

        $query = Person::query()
            ->select(['father_national_id', 'father_name'])
            ->whereNotNull('father_national_id')
            ->where('father_national_id', '!=', '')
            ->whereNotNull('father_name')
            ->where('father_name', '!=', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search, $fullNameExpression) {
                $q->where('father_national_id', 'LIKE', "%{$search}%")
                    ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
            })->orderByRaw('CASE WHEN father_national_id = ? THEN 0 ELSE 1 END', [$search]);
        }

        return $query->orderByDesc('id')
            ->limit(6)
            ->get()
            ->unique('father_national_id')
            ->values();
    }

    public function selectFatherFromSuggestions(string $fatherNationalId): void
    {
        $normalizedFatherNationalId = trim($fatherNationalId);
        if ($normalizedFatherNationalId === '') {
            return;
        }

        $fatherRecord = Person::query()
            ->select(['father_national_id', 'father_name'])
            ->where('father_national_id', $normalizedFatherNationalId)
            ->whereNotNull('father_name')
            ->where('father_name', '!=', '')
            ->orderByDesc('id')
            ->first();

        if (!$fatherRecord) {
            return;
        }

        $this->father_national_id = $fatherRecord->father_national_id;
        $this->father_name = $fatherRecord->father_name;
        $this->showFatherSuggestions = false;
        $this->resetValidation('father_national_id');
    }

    public function getGuardianSuggestionsProperty()
    {
        $search = trim((string) $this->guardian_national_code);
        $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
            : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

        $query = Guardian::query()
            ->select(['id', 'national_code', 'first_name', 'last_name'])
            ->whereNotNull('national_code')
            ->where('national_code', '!=', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search, $fullNameExpression) {
                $q->where('national_code', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
            })->orderByRaw('CASE WHEN national_code = ? THEN 0 ELSE 1 END', [$search]);
        }

        return $query->orderByDesc('id')
            ->limit(15)
            ->get()
            ->unique('national_code')
            ->values();
    }

    public function selectGuardianFromSuggestions(int $guardianId): void
    {
        $guardian = Guardian::query()
            ->select(['id', 'national_code'])
            ->find($guardianId);

        if (!$guardian || empty($guardian->national_code)) {
            return;
        }

        $this->guardian_national_code = $guardian->national_code;
        $this->updatedGuardianNationalCode($guardian->national_code);
        $this->dispatch('guardian-selected');
    }

    public function updatedGuardianNationalCode($value)
    {
        // در اینجا اعتبارسنجی دقیق کد ملی (regex) را انجام نمی‌دهیم تا فقط در زمان ذخیره بررسی شود.
        // هدف این متد صرفاً بررسی وجود کد ملی در دیتابیس و پر کردن فیلدها است.
        // resetValidation را برای پاک کردن پیام‌های خطای مربوط به نام و نام خانوادگی نگه می‌داریم.
        $this->resetValidation([
            'guardian_first_name',
            'guardian_last_name',
            'guardian_national_code',
            'guardian_birth_day',
            'guardian_birth_month',
            'guardian_birth_year',
            'guardian_phone_number',
            'occupation_id',

        ]);

        $national_code = trim($value);

        // سناریو 1: کاربر فیلد را خالی کرده است.
        if (empty($national_code)) {
            $this->resetAllGuardianFields(); // تمام فیلدهای سرپرست (از جمله کد ملی) را پاک می‌کند.
            return; // از ادامه اجرای متد جلوگیری می‌کند.
        }

        // سناریو 2: کد ملی وارد شده هنوز کامل نیست (کمتر از 10 رقم است) یا طول آن نامعتبر است.
        if (strlen($national_code) !== 10) {
            // در این حالت، کد ملی که کاربر در حال تایپ آن است، نباید پاک شود.
            // اما فیلدهای وابسته به جستجو در دیتابیس باید پاک شوند.
            // همچنین اگر programmatic نباشد، نام و نام خانوادگی نیز باید پاک شوند.

            // ابتدا فیلدهای وابسته (غیراز کد ملی، نام و نام خانوادگی) را پاک می‌کنیم.
            $this->resetDependentGuardianFields();

            // اگر این یک کپی برنامه‌نویسی از پدر نباشد، نام و نام خانوادگی را هم پاک می‌کنیم.
            // (اگر programmatic باشد، این مقادیر باید حفظ شوند.)
            if (!$this->is_programmatic_guardian_lookup) {
                $this->guardian_first_name = null;
                $this->guardian_last_name = null;
            }
            // کد ملی وارد شده توسط کاربر را دست‌نخورده باقی می‌گذاریم.
            return; // از ادامه اجرای متد جلوگیری می‌کند.

            $this->updateBankInfoFromGuardian();
        }


        // --- اگر به این مرحله رسیدیم، یعنی national_code دقیقاً 10 رقمی و معتبر است. ---
        // سناریو 3: کد ملی 10 رقمی و کامل است. جستجو در دیتابیس را انجام می‌دهیم.
        $guardian = Guardian::with('bankInfo')->where('national_code', $national_code)->first();

        if ($guardian) {
            // سرپرست در دیتابیس یافت شد: تمام اطلاعات را از مدل پر می‌کنیم.
            $this->_populateGuardianFieldsFromModel($guardian);
            $this->is_programmatic_guardian_lookup = false; // چون از دیتابیس یافت شده، دیگر programmatic نیست.
        } else {
            // سرپرست در دیتابیس یافت نشد.
            // فیلدهای وابسته را پاک می‌کنیم.
            $this->resetDependentGuardianFields();

            // اگر این یک کپی برنامه‌نویسی (از اطلاعات پدر) بوده است و سرپرست یافت نشد:
            // نام و نام خانوادگی (که از پدر/مددجو کپی شده‌اند) حفظ می‌شوند.
            if ($this->is_programmatic_guardian_lookup) {
                // نیازی به انجام کاری برای guardian_first_name و guardian_last_name نیست،
                // زیرا قبلاً در updatedGuardianRelationTypeId تنظیم شده‌اند و نباید پاک شوند.
            } else {
                // اگر کاربر دستی کد ملی وارد کرده و یافت نشده است:
                // نام و نام خانوادگی را پاک می‌کنیم.
                $this->guardian_first_name = null;
                $this->guardian_last_name = null;
            }
        }

        $this->updateBankInfoFromGuardian();

    }

    private function updateBankInfoFromGuardian()
    {
        // فقط اگر "حساب شخصی ندارد" انتخاب شده باشد
        if ($this->has_own_account == '0' && !empty($this->guardian_national_code)) {
            $guardian = Guardian::where('national_code', $this->guardian_national_code)->first();

            if ($guardian && $guardian->bankInfo) {
                $this->bank_id = $guardian->bankInfo->bank_id;
                $this->card_number = $guardian->bankInfo->card_number;
                $this->sheba_number = $guardian->bankInfo->sheba_number;
                $this->subsidy_card_number = $guardian->bankInfo->subsidy_card_number;
                $this->subsidy_sheba_number = $guardian->bankInfo->subsidy_sheba_number;
            } else {
                // اگر سرپرست جدید است یا اطلاعات بانکی ندارد، فیلدها را خالی کنیم
                $this->resetBankInfoFields();
            }
        }
    }

    private function resetBankInfoFields()
    {
        $this->bank_id = null;
        $this->card_number = null;
        $this->sheba_number = null;
        $this->subsidy_card_number = null;
        $this->subsidy_sheba_number = null;
    }


    public function updatedGuardianRelationTypeId($value)
    {
        // ریست کردن اعتبارسنجی‌ها برای فیلدهای سرپرست تا ارورهای قدیمی پاک شوند
        $this->resetValidation([
            'guardian_national_code',
            'guardian_first_name',
            'guardian_last_name'
        ]);

        if (is_numeric($value) && $value > 0) {
            $relationType = \App\Models\GuardianRelationType::find($value);

            if ($relationType) {
                // منطق برای پدر
                if ($relationType->title === 'پدر') {
                    $this->guardian_national_code = $this->father_national_id;
                    $this->guardian_first_name = $this->father_name;
                    $this->guardian_last_name = $this->last_name;

                    if (strlen(trim($this->father_national_id)) === 10) {
                        $this->is_programmatic_guardian_lookup = true;
                        $this->updatedGuardianNationalCode($this->father_national_id);
                        $this->is_programmatic_guardian_lookup = false;
                    } else {
                        $this->resetDependentGuardianFields();
                        $this->is_programmatic_guardian_lookup = false;
                    }

                } // منطق جدید برای مادر
                elseif ($relationType->title === 'مادر') {
                    // کد ملی مادر و نام مادر را کپی می‌کنیم
                    $this->guardian_national_code = $this->mother_national_id;

                    if (strlen(trim($this->mother_national_id)) === 10) {
                        $this->is_programmatic_guardian_lookup = true;
                        $this->updatedGuardianNationalCode($this->mother_national_id);
                        $this->is_programmatic_guardian_lookup = false;
                    } else {
                        $this->resetDependentGuardianFields();
                        $this->is_programmatic_guardian_lookup = false;
                    }
                } // برای سایر نسبت‌ها
                else {
                    // اگر نسبت انتخاب شده "پدر" یا "مادر" نیست، تمام فیلدهای سرپرست را ریست می‌کنیم
                    $this->resetAllGuardianFields();
                }
            } else {
                $this->resetAllGuardianFields();
            }
        } else {
            // اگر value معتبر نباشد، تمام فیلدهای سرپرست را ریست می‌کنیم
            $this->resetAllGuardianFields();
        }
    }

    protected function resetDependentGuardianFields()
    {
        $this->current_guardian_id = null;
        $this->guardian_exists_in_db = false;
        $this->guardian_phone_number = null;
        $this->guardian_birth_day = null;
        $this->guardian_birth_month = null;
        $this->guardian_birth_year = null;
        $this->occupation_id = null;
        $this->job_type_id = null;
        $this->children_count = null;
        $this->economic_decile = null;
        $this->children_in_house = null;
        $this->extra_household_members = [];
        $this->new_extra_household_member_description = '';
        $this->insurance_status = '0';
        $this->insurance_type_id = null;
        $this->divorced_child_at_home = null;
        $this->average_income = null;
        $this->any_family_employed = '0';
        $this->has_vehicle = '0';
        $this->vehicle_type_id = null;
        $this->social_worker_id = null;
        $this->allow_social_worker_edit = false;

        $this->residence_status_id = null;
        $this->district_id = null;
        $this->is_local_to_city = null;
        $this->deposit_amount = null;
        $this->monthly_rent = null;
        $this->residence_duration_years = null;
        $this->address = null;

        $this->landline_phone = null;
        $this->trusted_person_phone = null;
        $this->messenger_type = null;
        $this->messenger_number = null;


        if ($this->has_own_account == '0') {
            $this->resetBankInfoFields();
        }
    }

    protected function resetAllGuardianFields()
    {
        $this->guardian_national_code = null;
        $this->guardian_first_name = null;
        $this->guardian_last_name = null;
        $this->resetDependentGuardianFields(); // پاک کردن فیلدهای وابسته
        $this->is_programmatic_guardian_lookup = false; // ریست کردن پرچم
        // اگر حساب شخصی ندارد، اطلاعات بانکی را هم ریست کنیم
        if ($this->has_own_account == '0') {
            $this->resetBankInfoFields();
        }
    }


    private function _populateGuardianFieldsFromModel(\App\Models\Guardian $guardian)
    {
        $this->guardian_first_name = $guardian->first_name;
        $this->guardian_last_name = $guardian->last_name;
        $this->current_guardian_id = $guardian->id;
        $this->guardian_exists_in_db = true;

        $this->guardian_phone_number = $guardian->guardian_phone_number;
        $this->guardian_birth_day = $guardian->guardian_birth_day;
        $this->guardian_birth_month = $guardian->guardian_birth_month;
        $this->guardian_birth_year = $guardian->guardian_birth_year;
        $this->occupation_id = $guardian->occupation_id;
        $this->job_type_id = $guardian->job_type_id;
        $this->children_count = $guardian->children_count;
        $this->economic_decile = $guardian->economic_decile;
        $this->children_in_house = $guardian->children_in_house;
        $this->extra_household_members = is_array($guardian->extra_household_members) ? $guardian->extra_household_members : [];
        $this->insurance_type_id = $guardian->insurance_type_id;
        $this->divorced_child_at_home = $guardian->divorced_child_at_home;
        $this->any_family_employed_description = $guardian->any_family_employed_description;
        $this->average_income = $guardian->average_income;
        $this->vehicle_type_id = $guardian->vehicle_type_id;
        $this->vehicle_ownership_type = $guardian->vehicle_ownership_type;

        // فیلدهای بولی (Boolean) یا فیلدهای مورد استفاده در رادیو باتن باید به رشته تبدیل شوند
        $this->insurance_status = (bool)$guardian->insurance_status;
        $this->any_family_employed = (bool)$guardian->any_family_employed;
        $this->has_vehicle = (bool)$guardian->has_vehicle;
        $this->social_worker_id = $guardian->social_worker_id;
        $this->allow_change_social_worker = false; // همیشه بعد از جستجو قفل بماند

        // ۲. واکشی اطلاعات سکونت (Auto-fill)
        $residence = $guardian->residence; // استفاده از رابطه تعریف شده
        if ($residence) {
            $this->residence_status_id = $residence->residence_status_id;
            $this->district_id = $residence->district_id;
            $this->is_local_to_city = $residence->is_local_to_city ? '1' : '0';
            $this->deposit_amount = $residence->deposit_amount;
            $this->monthly_rent = $residence->monthly_rent;
            $this->residence_duration_years = $residence->residence_duration_years;
            $this->address = $residence->address;
        }

        // ۳. واکشی اطلاعات تماس (Auto-fill)
        $contact = $guardian->contact;
        if ($contact) {
            $this->landline_phone = $contact->landline_phone;
            $this->trusted_person_phone = $contact->trusted_person_phone;
            $this->personal_phone = $contact->personal_phone; // اگر نیاز است
            $this->messenger_type = $contact->messenger_type;
            $this->messenger_number = $contact->messenger_number;
        }

        // واکشی اطلاعات بانکی مددجو
        if ($this->person && $this->person->bankInfo) {
            // تشخیص has_own_account از روی account_owner_relation_id
            // فرض: ID=1 یعنی خود مددجو، ID=2 یعنی سرپرست
            $this->has_own_account = ($this->person->bankInfo->account_owner_relation_id == 1) ? '1' : '0';
            $this->account_owner_relation_id = $this->person->bankInfo->account_owner_relation_id;

            // در هر دو حالت، اطلاعات بانکی را از person->bankInfo می‌گیریم
            $this->bank_id = $this->person->bankInfo->bank_id;
            $this->card_number = $this->person->bankInfo->card_number;
            $this->sheba_number = $this->person->bankInfo->sheba_number;
            $this->subsidy_card_number = $this->person->bankInfo->subsidy_card_number;
            $this->subsidy_sheba_number = $this->person->bankInfo->subsidy_sheba_number;
            $this->other_account_owner_relation = $this->person->bankInfo->other_account_owner_relation;
        } elseif ($this->has_own_account == '0' && ($guardianBankInfo = $guardian->bankInfo()->first())) {
            // اگر مددجو اطلاعات بانکی ندارد ولی سرپرست دارد، از سرپرست استفاده می‌کنیم
            $this->has_own_account = '0';
            $this->account_owner_relation_id = 2; // سرپرست
            $this->bank_id = $guardianBankInfo->bank_id;
            $this->card_number = $guardianBankInfo->card_number;
            $this->sheba_number = $guardianBankInfo->sheba_number;
            $this->subsidy_card_number = $guardianBankInfo->subsidy_card_number;
            $this->subsidy_sheba_number = $guardianBankInfo->subsidy_sheba_number;
        }

        $this->recalculateChildrenInHouseRealtime();
    }


    public function updatedAnyFamilyEmployed($value)
    {
        if ($value == '0') {
            $this->any_family_employed_description = null;
        }
    }

    public function updatedChildrenFromPreviousMarriage($value): void
    {
        $this->children_from_previous_marriage = is_numeric($value) ? (int)$value : 0;
        $this->recalculateChildrenInHouseRealtime();
    }

    public function updatedRemarriedParent(): void
    {
        if (trim((string)$this->remarried_parent) === 'none') {
            $this->children_from_previous_marriage = 0;
        }

        $this->recalculateChildrenInHouseRealtime();
    }

    public function updatedChildrenCount($value): void
    {
        $this->children_count = is_numeric($value) ? (int)$value : 0;
        $this->recalculateChildrenInHouseRealtime();
    }

    public function addExtraHouseholdMember(): void
    {
        $description = trim((string)$this->new_extra_household_member_description);
        if ($description === '') {
            return;
        }

        $this->extra_household_members[] = ['description' => $description];
        $this->new_extra_household_member_description = '';
        $this->recalculateChildrenInHouseRealtime();
    }

    public function removeExtraHouseholdMember(int $index): void
    {
        if (!isset($this->extra_household_members[$index])) {
            return;
        }

        unset($this->extra_household_members[$index]);
        $this->extra_household_members = array_values($this->extra_household_members);
        $this->recalculateChildrenInHouseRealtime();
    }

    /**
     * وقتی وضعیت "حساب شخصی دارد" تغییر می‌کند
     */
    public function updatedHasOwnAccount($value)
    {
        if ($value == '1') {
            // ✅ حساب شخصی → مالک حساب = شخص مددجو
            $this->account_owner_relation_id = 1;
            $this->resetBankInfoFields();
        } else {
            // ✅ حساب شخصی ندارد → مالک حساب = سرپرست قانونی
            $this->account_owner_relation_id = 2;
            // آپدیت خودکار اطلاعات بانکی از سرپرست فعلی
            $this->updateBankInfoFromGuardian();
        }

        if ($value == '0') { // اگر "خیر" انتخاب شود
            $guardian = Guardian::where('national_code', $this->guardian_national_code)->first();

            if ($guardian && $guardian->bankInfo) {
                $this->bank_id = $guardian->bankInfo->bank_id;
                $this->card_number = $guardian->bankInfo->card_number;
                $this->sheba_number = $guardian->bankInfo->sheba_number;
                $this->subsidy_card_number = $guardian->bankInfo->subsidy_card_number;
                $this->subsidy_sheba_number = $guardian->bankInfo->subsidy_sheba_number;
            }
        } else { // اگر "بله" انتخاب شود
            $this->bank_id = null;
            $this->card_number = null;
            $this->sheba_number = null;
            $this->subsidy_card_number = null;
            $this->subsidy_sheba_number = null;
        }
    }

    // متدهای لایو وایر برای ریست کردن فیلدهای وابسته (مانند disabled/enabled)
    public function updatedHasDisability($value)
    {
        if ($value == '0') {
            $this->disability_type_id = null;
            $this->disability_description = null;
        }
    }


    public function updatedInsuranceStatus($value)
    {
        if ($value == '0') {
            $this->insurance_type_id = null;
        }
    }

    public function updatedIsStudying($value)
    {
        if ($value == '0') {
            $this->school_name = null;
            $this->major = null;
            $this->education_level_id = null;
            // drop_reason و works_alongside_study ممکن است مستقل باشند
        }
    }

    public function updatedHasParentDisability($value)
    {
        if ($value == '0') {
            $this->parent_disability_description = null;
        }
    }

    public function updatedHasVehicle($value)
    {
        if ($value == '0') {
            $this->vehicle_type_id = null;
            $this->vehicle_ownership_type = null;
        }
    }

    protected function rules()
    {
        return [
            // --- بخش 1: اطلاعات فردی مددجو ---
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('people', 'national_id')->ignore($this->person_id)
            ],
            'birth_day' => ['required', 'integer', 'between:1,31', function ($attribute, $value, $fail) {
                if ($this->birth_month > 6 && $value > 30) $fail('برای نیمه دوم سال، روز نمی‌تواند ۳۱ باشد.');
                if ($this->birth_month == 12 && $value > 29) $fail('برای اسفند، روز نمی‌تواند بیشتر از ۲۹ باشد.');
            }],
            'birth_month' => 'required|integer|between:1,12',
            'birth_year' => 'required|integer|between:1300,1450',
            'father_name' => 'required|string|max:100',
            'father_national_id' => 'nullable|digits:10',
            'mother_national_id' => 'nullable|digits:10',
            'phone_number' => 'nullable|string|max:11',
            'gender' => 'required|in:male,female',
            'role' => 'required|in:child,guardian',

            // سادات: فقط اگر وضعیت "سادات" باشد، نسبت اجباری است
            'sadaat_status' => 'nullable|in:general,sadaat',
            'sadaat_relation_id' => 'required_if:sadaat_status,sadaat|nullable|exists:sadaat_relations,id',

            // --- بخش 2 و 3: سرپرست ---
            'guardian_relation_type_id' => 'nullable|exists:guardian_relation_types,id',
            'social_worker_id' => 'nullable|exists:social_workers,id',
            'remarried_parent' => 'nullable|in:none,father,mother,both',
            'children_from_previous_marriage' => 'nullable|integer|min:0',
            'has_parent_disability' => 'nullable|boolean',
            'parent_disability_description' => 'required_if:has_parent_disability,1|nullable|string|max:500',


            // وضعیت معلولیت
            'has_disability' => 'nullable|in:0,1',
            'disability_type_id' => [
                'nullable',
                Rule::requiredIf($this->has_disability == '1'),
                'exists:disability_types,id'
            ],
            'disability_description' => 'nullable|string|max:500',

            // مهارت‌ها و آسیب‌ها
            'skills' => 'nullable|array',
            'skills.*' => 'exists:skills,id', // بررسی وجود هر مهارت در دیتابیس
            'skills_description' => 'nullable|string|max:500',
            'harm_types' => 'nullable|array',
            'harm_types.*' => 'exists:harm_types,id',

            // فایل‌ها (با رعایت پسوند و حجم)
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'photo_id_card' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'photo_birth_certificate' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',


            'guardian_national_code' => [
                'nullable', 'string', 'digits:10',
                // اعتبارسنجی یونیک بودن کد ملی سرپرست (اگر جدید است)
                Rule::unique('guardians', 'national_code')->ignore($this->current_guardian_id)
            ],

// فیلدهای زیر اگر سرپرست در دیتابیس وجود داشته باشد (guardian_exists_in_db == true) می‌تواند nullable باشد
            'guardian_first_name' => [$this->guardian_exists_in_db ? 'nullable' : 'nullable', 'string', 'max:100'],
            'guardian_last_name' => [$this->guardian_exists_in_db ? 'nullable' : 'nullable', 'string', 'max:100'],
            'guardian_phone_number' => [$this->guardian_exists_in_db ? 'nullable' : 'nullable', 'regex:/^09[0-9]{9}$/'],

// اعتبارسنجی تاریخ تولد سرپرست
            'guardian_birth_year' => [$this->guardian_exists_in_db ? 'nullable' : 'nullable', 'integer', 'between:1300,1450'],
            'guardian_birth_month' => [$this->guardian_exists_in_db ? 'nullable' : 'nullable', 'integer', 'between:1,12'],
            'guardian_birth_day' => [
                $this->guardian_exists_in_db ? 'nullable' : 'nullable',
                'integer',
                'between:1,31',
                function ($attribute, $value, $fail) {
                    if (!$this->guardian_exists_in_db) {
                        if ($this->guardian_birth_month > 6 && $this->guardian_birth_month < 12 && $value > 30) $fail('روز نمی‌تواند بیش از ۳۰ باشد.');
                        if ($this->guardian_birth_month == 12 && $value > 29) $fail('اسفند نمی‌تواند بیش از ۲۹ روز باشد.');
                    }
                }
            ],
            'occupation_id' => 'nullable|exists:occupations,id',
            'job_type_id' => 'nullable|exists:job_types,id',
            'children_in_house' => 'nullable|integer|min:0',
            'economic_decile' => 'nullable|integer|min:1|max:10',
            'insurance_status' => 'nullable|boolean',
            'insurance_type_id' => 'required_if:insurance_status,1|nullable|exists:insurance_types,id',
            'divorced_child_at_home' => 'nullable|in:none,1,2,3,more',
            'average_income' => 'nullable|numeric|min:0',

// اشتغال سایر اعضای خانواده
            'any_family_employed' => 'nullable|boolean',
            'any_family_employed_description' => 'required_if:any_family_employed,1|nullable|string|max:500',

// خودرو
            'has_vehicle' => 'nullable|boolean',
            'vehicle_type_id' => 'required_if:has_vehicle,1|nullable|exists:vehicle_types,id',
            'vehicle_ownership_type' => 'required_if:has_vehicle,1|nullable|in:personal,company,rented',


            'residence_status_id' => 'nullable|exists:residence_status_types,id',
            'district_id' => 'nullable|exists:districts,id',
            'is_local_to_city' => 'nullable|boolean',
            'deposit_amount' => 'nullable|integer|min:0',
            'monthly_rent' => 'nullable|integer|min:0',
            'residence_duration_years' => 'nullable|integer|min:0',
            'address' => 'nullable|string|max:1000',
            'landline_phone' => ['nullable', 'regex:/^0[1-9]{2}[0-9]{8}$/'], // تلفن ثابت با کد شهر
            'trusted_person_phone' => ['nullable', 'regex:/^09[0-9]{9}$/'], // تلفن فرد مورد اعتماد
            'messenger_type' => 'nullable|string|max:70',
            'messenger_number' => ['nullable', 'required_with:messenger_type', 'regex:/^09[0-9]{9}$/'],

            // --- بخش 5: مالی و بانکی (اصلاح مهم) ---
            'has_own_account' => 'nullable|in:0,1',
            'account_owner_relation_id' => 'nullable|exists:account_owner_relations,id',
            'bank_id' => 'nullable|exists:banks,id',
            'card_number' => 'nullable|string|digits:16',
            'sheba_number' => ['nullable', 'string', 'size:26', 'regex:/^IR[0-9]{24}$/i'],
            'subsidy_card_number' => 'nullable|string|digits:16',
            'subsidy_sheba_number' => 'nullable|string|max:26',

            'is_studying' => 'nullable|boolean',
            'education_level_id' => 'nullable|exists:education_levels,id',
            'school_name' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'drop_reason' => 'nullable|string|max:1000',
            'works_alongside_study' => 'nullable|boolean',
            'monthly_income' => 'nullable|integer|min:0',


            'support_organization_id' => 'nullable|exists:support_organizations,id',
            'other_organization_name' => [
                Rule::requiredIf(fn() => $this->isOtherOrganization()),
                'nullable', 'string', 'max:255'
            ],
            'coverage_start_day' => 'nullable|integer|between:1,31',
            'coverage_start_month' => 'nullable|integer|between:1,12',
            'coverage_start_year' => 'nullable|integer|between:1300,1450',
            'need_level_id' => 'nullable|exists:need_level_types,id',
        ];
    }

    private function isOtherOrganization()
    {
        return $this->support_organization_id == $this->support_organizations->where('slug', 'other')->first()?->id;
    }

    // --- متد Save (ذخیره نهایی) ---
    public function save()
    {
        $this->is_submitted = true;
        try {
            $validatedData = $this->validate();
            $this->show_step_error_badges = false;
        } catch (ValidationException $e) {
            $this->show_step_error_badges = true;
            throw $e;
        }

        DB::beginTransaction();
        try {
            // ۱. پردازش تمام تصاویر
            $profilePhotoPath = $this->processImage($this->profile_photo, $this->captured_photo_base64, 'profile_photos');
            $idCardPath = $this->processImage($this->photo_id_card, $this->captured_id_card_base64, 'photo_id_card');
            $birthCertPath = $this->processImage($this->photo_birth_certificate, $this->captured_birth_certificate_base64, 'photo_birth_certificate');
            $supportCardPath = $this->processImage($this->support_card_image, $this->captured_support_card_base64, 'support_card_image');

            $guardianBirthDateFull = null;
            if ($this->guardian_birth_year && $this->guardian_birth_month && $this->guardian_birth_day) {
                $guardianBirthDateFull = sprintf('%04d/%02d/%02d', $this->guardian_birth_year, $this->guardian_birth_month, $this->guardian_birth_day);
            }

            $coverageDateFull = null;
            if ($this->coverage_start_year && $this->coverage_start_month && $this->coverage_start_day) {
                $coverageDateFull = sprintf('%04d/%02d/%02d', $this->coverage_start_year, $this->coverage_start_month, $this->coverage_start_day);
            }

            // ========================================
            // حالت ویرایش (Edit Mode)
            // ========================================
            if ($this->mode == "edit") {

                // --- 3. مدیریت سرپرست (ایجاد یا به‌روزرسانی) ---
                $guardianInstance = null;
                $oldGuardianId = $this->person->guardian_id;

                if ($this->guardian_exists_in_db && $this->current_guardian_id) {
                    // سرپرست موجود در دیتابیس
                    $guardianInstance = Guardian::find($this->current_guardian_id);
                    if (!$guardianInstance) {
                        throw new \Exception("سرپرست با شناسه {$this->current_guardian_id} یافت نشد.");
                    }
                } else {
                    // ایجاد سرپرست جدید
                    $guardianInstance = Guardian::create([
                        'social_worker_id' => $this->social_worker_id,
                        'guardian_code' => Guardian::generateNextGuardianCode(),
                        'national_code' => $this->guardian_national_code,
                        'first_name' => $this->guardian_first_name,
                        'last_name' => $this->guardian_last_name,
                        'guardian_birth_day' => $this->guardian_birth_day,
                        'guardian_birth_month' => $this->guardian_birth_month,
                        'guardian_birth_year' => $this->guardian_birth_year,
                        'guardian_birth_date_full' => $guardianBirthDateFull,
                        'children_count' => 0,
                        'economic_decile' => $this->economic_decile ?: null,
                    ]);
                }

                // به‌روزرسانی اطلاعات سرپرست
                $guardianInstance->update([
                    'social_worker_id' => $this->social_worker_id,
                    'occupation_id' => $this->occupation_id,
                    'job_type_id' => $this->job_type_id ?: null,
                    'guardian_phone_number' => $this->guardian_phone_number,
                    'children_in_house' => (int)$this->children_in_house,
                    'extra_household_members' => $this->extra_household_members,
                    'insurance_status' => (bool)$this->insurance_status,
                    'insurance_type_id' => ((bool)$this->insurance_status) ? $this->insurance_type_id : null,
                    'divorced_child_at_home' => $this->divorced_child_at_home ?: 'none',
                    'average_income' => (int)$this->average_income,
                    'any_family_employed' => (bool)$this->any_family_employed,
                    'any_family_employed_description' => ((bool)$this->any_family_employed) ? $this->any_family_employed_description : null,
                    'has_vehicle' => (bool)$this->has_vehicle,
                    'vehicle_type_id' => ((bool)$this->has_vehicle) ? $this->vehicle_type_id : null,
                    'vehicle_ownership_type' => ((bool)$this->has_vehicle) ? $this->vehicle_ownership_type : null,
                ]);

                // مدیریت تغییر سرپرست (کاهش/افزایش children_count)
                if ($oldGuardianId != $guardianInstance->id) {
                    // کاهش تعداد فرزندان سرپرست قبلی
                    if ($oldGuardianId) {
                        $oldGuardian = Guardian::find($oldGuardianId);
                        if ($oldGuardian && $oldGuardian->children_count > 0) {
                            $oldGuardian->decrement('children_count');
                        }
                    }
                    // افزایش تعداد فرزندان سرپرست جدید
                    $guardianInstance->increment('children_count');
                }

                // --- 4. به‌روزرسانی اطلاعات مددجو (Person) ---
                $personUpdateData = [
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'national_id' => $this->national_id,
                    'birth_day' => $this->birth_day,
                    'birth_month' => $this->birth_month,
                    'birth_year' => $this->birth_year,
                    'father_name' => $this->father_name,
                    'father_national_id' => $this->father_national_id,
                    'mother_national_id' => $this->mother_national_id,
                    'phone_number' => $this->phone_number,
                    'gender' => $this->gender,
                    'role' => $this->role,
                    'sadaat_status' => $this->sadaat_status,
                    'sadaat_relation_id' => ($this->sadaat_status === 'sadaat') ? $this->sadaat_relation_id : null,
                    'skills_description' => $this->skills_description,
                    'has_disability' => (bool)$this->has_disability,
                    'disability_type_id' => ((bool)$this->has_disability) ? $this->disability_type_id : null,
                    'disability_description' => ((bool)$this->has_disability) ? $this->disability_description : null,
                    'guardian_id' => $guardianInstance->id,
                ];

                // فقط در صورت آپلود تصویر جدید، مسیر را به‌روز کن
                if ($profilePhotoPath) {
                    $personUpdateData['profile_photo'] = $profilePhotoPath;
                }
                if ($idCardPath) {
                    $personUpdateData['photo_id_card'] = $idCardPath;
                }
                if ($birthCertPath) {
                    $personUpdateData['photo_birth_certificate'] = $birthCertPath;
                }

                $this->person->update($personUpdateData);

                // --- 5. به‌روزرسانی وضعیت خانوادگی (FamilyStatus) ---
                $this->person->familyStatus()->updateOrCreate(
                    ['person_id' => $this->person->id],
                    [
                        'guardian_relation_type_id' => $this->guardian_relation_type_id,
                        'remarried_parent' => $this->remarried_parent,
                        'children_from_previous_marriage' => (int)$this->children_from_previous_marriage,
                        'has_parent_disability' => (bool)$this->has_parent_disability,
                        'parent_disability_description' => ((bool)$this->has_parent_disability) ? $this->parent_disability_description : null,
                    ]
                );
                $this->syncChildrenInHouseForParentGuardian($guardianInstance);
                $this->syncFamilyStatusAcrossSiblings($this->person);

                // --- 6. به‌روزرسانی اطلاعات سکونت (Residence) ---
                \App\Models\Residence::updateOrCreate(
                    ['guardian_id' => $guardianInstance->id],
                    [
                        'person_id' => $this->person->id,
                        'residence_status_id' => $this->residence_status_id,
                        'district_id' => $this->district_id ?: null,
                        'is_local_to_city' => (bool)$this->is_local_to_city,
                        'deposit_amount' => (int)$this->deposit_amount,
                        'monthly_rent' => (int)$this->monthly_rent,
                        'residence_duration_years' => (int)$this->residence_duration_years,
                        'address' => $this->address,
                    ]
                );

                // --- 7. به‌روزرسانی اطلاعات تماس (Contact) ---
                \App\Models\Contact::updateOrCreate(
                    ['guardian_id' => $guardianInstance->id],
                    [
                        'person_id' => $this->person->id,
                        'landline_phone' => $this->landline_phone,
                        'trusted_person_phone' => $this->trusted_person_phone,
                        'messenger_type' => $this->messenger_type,
                        'messenger_number' => $this->messenger_number,
                    ]
                );

                // --- 8. به‌روزرسانی اطلاعات بانکی (BankInfo) ---
                if ($this->has_own_account == '1') {
                    // حساب شخصی مددجو
                    BankInfo::updateOrCreate(
                        ['person_id' => $this->person->id],
                        [
                            'guardian_id' => null,
                            'bank_id' => $this->bank_id,
                            'card_number' => $this->card_number,
                            'sheba_number' => $this->sheba_number,
                            'subsidy_card_number' => $this->subsidy_card_number,
                            'subsidy_sheba_number' => $this->subsidy_sheba_number,
                            'account_owner_relation_id' => $this->account_owner_relation_id,
                            'other_account_owner_relation' => $this->other_account_owner_relation,
                        ]
                    );
                } else {
                    // حساب سرپرست
                    // ابتدا اطلاعات بانکی مددجو را حذف کن (اگر وجود داشت)
                    BankInfo::where('person_id', $this->person->id)->delete();

                    BankInfo::updateOrCreate(
                        ['guardian_id' => $guardianInstance->id],
                        [
                            'person_id' => null,
                            'bank_id' => $this->bank_id,
                            'card_number' => $this->card_number,
                            'sheba_number' => $this->sheba_number,
                            'subsidy_card_number' => $this->subsidy_card_number,
                            'subsidy_sheba_number' => $this->subsidy_sheba_number,
                            'account_owner_relation_id' => $this->account_owner_relation_id,
                            'other_account_owner_relation' => $this->other_account_owner_relation,
                        ]
                    );
                }

                // --- 9. به‌روزرسانی اطلاعات تحصیلی (Education) ---
                $this->person->education()->updateOrCreate(
                    ['person_id' => $this->person->id],
                    [
                        'is_studying' => (bool)$this->is_studying,
                        'school_name' => $this->school_name,
                        'major' => $this->major,
                        'education_level_id' => $this->education_level_id ?: null,
                        'drop_reason' => $this->drop_reason,
                        'works_alongside_study' => (bool)$this->works_alongside_study,
                        'monthly_income' => (int)$this->monthly_income,
                    ]
                );

                // --- 10. به‌روزرسانی پوشش حمایتی (SupportCoverage) ---
                $supportCoverageData = [
                    'support_organization_id' => $this->support_organization_id,
                    'other_organization_name' => $this->other_organization_name,
                    'coverage_start_day' => $this->coverage_start_day ?: null,
                    'coverage_start_month' => $this->coverage_start_month ?: null,
                    'coverage_start_year' => $this->coverage_start_year ?: null,
                    'coverage_start_date' => $coverageDateFull,
                ];

                if ($supportCardPath) {
                    $supportCoverageData['support_card_image'] = $supportCardPath;
                }

                $this->person->supportCoverage()->updateOrCreate(
                    ['person_id' => $this->person->id],
                    $supportCoverageData
                );

                // --- 11. به‌روزرسانی سطح نیاز (NeedsLevel) ---
                $this->person->needsLevel()->updateOrCreate(
                    ['person_id' => $this->person->id],
                    [
                        'need_level_id' => $this->need_level_id,
                        'evaluation_date' => now(),
                        'reviewer_name' => auth()->user()->name ?? 'سیستم',
                    ]
                );

                // --- 12. همگام‌سازی مهارت‌ها و آسیب‌ها ---
                $this->person->skills()->sync($this->skills ?? []);
                $this->syncPersonHarmTypesWithFamilyPropagation($this->person, $this->harm_types ?? []);

                DB::commit();

                // به‌روزرسانی آمار مددکار اجتماعی
                if ($this->social_worker_id) {
                    $socialWorker = \App\Models\SocialWorker::find($this->social_worker_id);
                    if ($socialWorker) {
                        $socialWorker->refreshStatistics();
                    }
                }

                session()->flash('success', 'اطلاعات مددجو با موفقیت به‌روزرسانی شد.');

                if ($this->embedded) {
                    $this->dispatch('open-dashboard-section', section: 'people-list');
                    return;
                }

                return redirect()->route('people.form', [
                    'mode' => 'edit',
                    'person' => $this->person->id
                ]);

            }
            // ========================================
            // حالت ایجاد (Create Mode) - کد موجود شما
            // ========================================
            else {
                $isNewPerson = true;
                $guardian_id_to_assign = null;
                $guardianInstance = null;

                if ($this->guardian_exists_in_db && $this->current_guardian_id) {
                    $guardianInstance = Guardian::find($this->current_guardian_id);

                    if (!$guardianInstance) {
                        throw new \Exception("Existing guardian with ID {$this->current_guardian_id} not found.");
                    }
                } else {
                    $guardianInstance = Guardian::create([
                        'social_worker_id' => $this->social_worker_id,
                        'guardian_code' => Guardian::generateNextGuardianCode(),
                        'national_code' => $this->guardian_national_code,
                        'first_name' => $this->guardian_first_name,
                        'last_name' => $this->guardian_last_name,
                        'guardian_birth_day' => $this->guardian_birth_day,
                        'guardian_birth_month' => $this->guardian_birth_month,
                        'guardian_birth_year' => $this->guardian_birth_year,
                        'guardian_birth_date_full' => $guardianBirthDateFull,
                        'children_count' => 0,
                        'economic_decile' => $this->economic_decile ?: null,
                    ]);
                }
                $guardian_id_to_assign = $guardianInstance->id;

                if ($guardianInstance) {
                    $guardianInstance->update([
                        'social_worker_id' => $this->social_worker_id,
                        'occupation_id' => $this->occupation_id,
                        'job_type_id' => $this->job_type_id ?: null,
                        'guardian_phone_number' => $this->guardian_phone_number,
                        'children_in_house' => (int)$this->children_in_house,
                        'extra_household_members' => $this->extra_household_members,
                        'insurance_status' => (bool)$this->insurance_status,
                        'insurance_type_id' => ((bool)$this->insurance_status) ? $this->insurance_type_id : null,
                        'divorced_child_at_home' => $this->divorced_child_at_home ?: 'none',
                        'average_income' => (int)$this->average_income,
                        'any_family_employed' => (bool)$this->any_family_employed,
                        'any_family_employed_description' => ((bool)$this->any_family_employed) ? $this->any_family_employed_description : null,
                        'has_vehicle' => (bool)$this->has_vehicle,
                        'vehicle_type_id' => ((bool)$this->has_vehicle) ? $this->vehicle_type_id : null,
                        'vehicle_ownership_type' => ((bool)$this->has_vehicle) ? $this->vehicle_ownership_type : null,
                    ]);
                } else {
                    throw new \Exception("Failed to retrieve or create guardian instance for ID: " . ($this->current_guardian_id ?? 'new'));
                }

                // جدول 1: Person
                $person = Person::create([
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'national_id' => $this->national_id,
                    'birth_day' => $this->birth_day,
                    'birth_month' => $this->birth_month,
                    'birth_year' => $this->birth_year,
                    'father_name' => $this->father_name,
                    'father_national_id' => $this->father_national_id,
                    'mother_national_id' => $this->mother_national_id,
                    'phone_number' => $this->phone_number,
                    'gender' => $this->gender,
                    'role' => $this->role,
                    'sadaat_status' => $this->sadaat_status,
                    'sadaat_relation_id' => ($this->sadaat_status === 'sadaat') ? $this->sadaat_relation_id : null,
                    'skills_description' => $this->skills_description,
                    'profile_photo' => $profilePhotoPath,
                    'photo_id_card' => $idCardPath,
                    'photo_birth_certificate' => $birthCertPath,
                    'has_disability' => (bool)$this->has_disability,
                    'disability_type_id' => ((bool)$this->has_disability) ? $this->disability_type_id : null,
                    'disability_description' => ((bool)$this->has_disability) ? $this->disability_description : null,
                    'guardian_id' => $guardian_id_to_assign,
                ]);

                if ($isNewPerson) {
                    $guardianInstance->increment('children_count');
                }

                // جدول 2: FamilyStatus
                FamilyStatus::create([
                    'person_id' => $person->id,
                    'guardian_relation_type_id' => $this->guardian_relation_type_id,
                    'remarried_parent' => $this->remarried_parent,
                    'children_from_previous_marriage' => (int)$this->children_from_previous_marriage,
                    'has_parent_disability' => (bool)$this->has_parent_disability,
                    'parent_disability_description' => ((bool)$this->has_parent_disability) ? $this->parent_disability_description : null,
                ]);
                $this->syncChildrenInHouseForParentGuardian($guardianInstance);
                $this->syncFamilyStatusAcrossSiblings($person);

                // جدول 3: Residence
                \App\Models\Residence::updateOrCreate(
                    ['guardian_id' => $guardianInstance->id],
                    [
                        'person_id' => $person->id,
                        'residence_status_id' => $this->residence_status_id,
                        'district_id' => $this->district_id ?: null,
                        'is_local_to_city' => (bool)$this->is_local_to_city,
                        'deposit_amount' => (int)$this->deposit_amount,
                        'monthly_rent' => (int)$this->monthly_rent,
                        'residence_duration_years' => (int)$this->residence_duration_years,
                        'address' => $this->address,
                    ]
                );

                // جدول 4: Contact
                \App\Models\Contact::updateOrCreate(
                    ['guardian_id' => $guardianInstance->id],
                    [
                        'person_id' => $person->id,
                        'landline_phone' => $this->landline_phone,
                        'trusted_person_phone' => $this->trusted_person_phone,
                        'messenger_type' => $this->messenger_type,
                        'messenger_number' => $this->messenger_number,
                    ]
                );

                // جدول 5: BankInfo
                if ($this->has_own_account == '1') {
                    BankInfo::create([
                        'person_id' => $person->id,
                        'bank_id' => $this->bank_id,
                        'card_number' => $this->card_number,
                        'sheba_number' => $this->sheba_number,
                        'subsidy_card_number' => $this->subsidy_card_number,
                        'subsidy_sheba_number' => $this->subsidy_sheba_number,
                        'account_owner_relation_id' => $this->account_owner_relation_id,
                        'other_account_owner_relation' => $this->other_account_owner_relation,
                    ]);
                } else {
                    BankInfo::updateOrCreate(
                        ['guardian_id' => $guardianInstance->id],
                        [
                            'bank_id' => $this->bank_id,
                            'card_number' => $this->card_number,
                            'sheba_number' => $this->sheba_number,
                            'subsidy_card_number' => $this->subsidy_card_number,
                            'subsidy_sheba_number' => $this->subsidy_sheba_number,
                            'account_owner_relation_id' => $this->account_owner_relation_id,
                            'other_account_owner_relation' => $this->other_account_owner_relation,
                        ]
                    );
                }

                // جدول 6: Education
                Education::create([
                    'person_id' => $person->id,
                    'is_studying' => (bool)$this->is_studying,
                    'school_name' => $this->school_name,
                    'major' => $this->major,
                    'education_level_id' => $this->education_level_id ?: null,
                    'drop_reason' => $this->drop_reason,
                    'works_alongside_study' => (bool)$this->works_alongside_study,
                    'monthly_income' => (int)$this->monthly_income,
                ]);

                // جدول 7: SupportCoverage
                SupportCoverage::create([
                    'person_id' => $person->id,
                    'support_organization_id' => $this->support_organization_id,
                    'other_organization_name' => $this->other_organization_name,
                    'coverage_start_day' => $this->coverage_start_day ?: null,
                    'coverage_start_month' => $this->coverage_start_month ?: null,
                    'coverage_start_year' => $this->coverage_start_year ?: null,
                    'coverage_start_date' => $coverageDateFull,
                    'support_card_image' => $supportCardPath,
                ]);

                // جدول 8: NeedsLevel
                NeedsLevel::create([
                    'person_id' => $person->id,
                    'need_level_id' => $this->need_level_id,
                    'evaluation_date' => now(),
                    'reviewer_name' => auth()->user()->name ?? 'سیستم',
                ]);

                // Sync مهارت‌ها و آسیب‌ها
                if (!empty($this->skills)) {
                    $person->skills()->sync($this->skills);
                }

                if (!empty($this->harm_types)) {
                    $this->syncPersonHarmTypesWithFamilyPropagation($person, $this->harm_types);
                } else {
                    $this->syncPersonHarmTypesWithFamilyPropagation($person, []);
                }

                DB::commit();

                if ($this->social_worker_id) {
                    $socialWorker = \App\Models\SocialWorker::find($this->social_worker_id);
                    if ($socialWorker) {
                        $socialWorker->refreshStatistics();
                    }
                }

                session()->flash('success', 'اطلاعات مددجو با موفقیت ثبت و ذخیره شد.');

                if ($this->embedded) {
                    $this->dispatch('open-dashboard-section', section: 'people-list');
                    return;
                }

                $this->reset();

                return redirect()->route('people.form', [
                    'mode' => 'create'
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Create/Edit Person Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', 'خطا در ثبت اطلاعات. لطفاً ورودی‌ها را بررسی کنید و در صورت تکرار، با پشتیبانی تماس بگیرید.');
            // throw $e; // برای دیباگ در محیط توسعه
        }
    }


    /**
     * متد کمکی برای مدیریت هوشمند تصاویر (آپلود یا دوربین)
     */
    private function processImage($uploadedFile, $base64Data, $subFolder, $personCode = null)
    {
        // ساخت مسیر نهایی: uploads/subFolder/personCode/
        $folderPath = 'uploads/' . $subFolder;
        if ($personCode) {
            $folderPath .= '/' . $personCode;
        }

        // ۱. اولویت با تصویر دوربین (Base64)
        if (!empty($base64Data)) {
            try {
                // استخراج داده‌های اصلی تصویر
                $image_parts = explode(";base64,", $base64Data);
                if (count($image_parts) < 2) return null;

                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1] ?? 'png';
                $image_base64 = base64_decode($image_parts[1]);

                // نام‌گذاری فایل
                $fileName = uniqid() . '_' . time() . '.' . $image_type;
                $finalPath = $folderPath . '/' . $fileName;

                // ذخیره فیزیکی در Storage (دیسک public)
                \Illuminate\Support\Facades\Storage::disk('public')->put($finalPath, $image_base64);

                return $finalPath;
            } catch (\Exception $e) {
                \Log::error("خطا در پردازش تصویر دوربین ($subFolder): " . $e->getMessage());
                return null;
            }
        }

        // ۲. اگر دوربین نبود، استفاده از فایل آپلود شده
        if ($uploadedFile) {
            return $uploadedFile->store($folderPath, 'public');
        }

        return null;
    }

    private function syncPersonHarmTypesWithFamilyPropagation(Person $person, array $selectedHarmTypes): void
    {
        $selectedHarmTypeIds = collect($selectedHarmTypes)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value) => (int)$value)
            ->unique()
            ->values()
            ->all();

        $person->harmTypes()->sync($selectedHarmTypeIds);

        // آسیب‌های مرتبط با والدین که باید بین فرزندان با والد مشترک همگام شوند
        $parentRelatedHarmTypeIds = [1, 2, 5, 7]; // فوت پدر، فوت مادر، زندانی والدین، فرزند طلاق
        $harmTypesToPropagate = array_values(array_intersect($selectedHarmTypeIds, $parentRelatedHarmTypeIds));
        $harmTypesToRemove = array_values(array_diff($parentRelatedHarmTypeIds, $harmTypesToPropagate));

        $fatherNationalId = trim((string)$person->father_national_id);
        $motherNationalId = trim((string)$person->mother_national_id);

        if ($fatherNationalId === '' && $motherNationalId === '') {
            return;
        }

        $siblingsQuery = Person::query()->where('id', '!=', $person->id);
        $siblingsQuery->where(function ($query) use ($fatherNationalId, $motherNationalId) {
            if ($fatherNationalId !== '') {
                $query->orWhere('father_national_id', $fatherNationalId);
            }
            if ($motherNationalId !== '') {
                $query->orWhere('mother_national_id', $motherNationalId);
            }
        });

        $siblings = $siblingsQuery->get(['id']);

        foreach ($siblings as $sibling) {
            if (!empty($harmTypesToPropagate)) {
                $sibling->harmTypes()->syncWithoutDetaching($harmTypesToPropagate);
            }

            if (!empty($harmTypesToRemove)) {
                $sibling->harmTypes()->detach($harmTypesToRemove);
            }
        }
    }

    private function syncFamilyStatusAcrossSiblings(Person $person): void
    {
        $fatherNationalId = trim((string)$person->father_national_id);
        $motherNationalId = trim((string)$person->mother_national_id);

        // برای جلوگیری از به‌روزرسانی اشتباه، هم‌گام‌سازی فقط وقتی انجام می‌شود
        // که هر دو کد ملی والدین موجود باشند (خواهر/برادر واقعی با والدین یکسان).
        if ($fatherNationalId === '' || $motherNationalId === '') {
            return;
        }

        $siblings = Person::query()
            ->where('id', '!=', $person->id)
            ->where('father_national_id', $fatherNationalId)
            ->where('mother_national_id', $motherNationalId)
            ->get(['id']);

        foreach ($siblings as $sibling) {
            FamilyStatus::updateOrCreate(
                ['person_id' => $sibling->id],
                [
                    'remarried_parent' => $this->remarried_parent ?: null,
                    'children_from_previous_marriage' => (int)($this->children_from_previous_marriage ?? 0),
                    'has_parent_disability' => (bool)$this->has_parent_disability,
                    'parent_disability_description' => (bool)$this->has_parent_disability
                        ? $this->parent_disability_description
                        : null,
                ]
            );
        }
    }

    private function syncChildrenInHouseForParentGuardian(Guardian $guardian): void
    {
        if (!$this->isParentGuardianWithRelevantRemarriage()) {
            return;
        }

        $childrenFromPreviousMarriage = (int)($this->children_from_previous_marriage ?? 0);
        $childrenCount = (int)($guardian->children_count ?? 0);
        $extraHouseholdCount = count($this->extra_household_members ?? []);

        $guardian->update([
            'children_in_house' => $childrenCount + $childrenFromPreviousMarriage + $extraHouseholdCount,
        ]);
    }

    private function recalculateChildrenInHouseRealtime(): void
    {
        $childrenCount = $this->getBaseChildrenCount();
        $childrenFromPreviousMarriage = $this->getAppliedChildrenFromPreviousMarriage();
        $extraHouseholdCount = $this->getExtraHouseholdCount();

        if (!$this->isParentGuardianWithRelevantRemarriage()) {
            $this->children_in_house = $childrenCount + $extraHouseholdCount;
            return;
        }

        $this->children_in_house = $childrenCount + $childrenFromPreviousMarriage + $extraHouseholdCount;
    }

    public function getChildrenInHouseFormulaProperty(): array
    {
        return [
            'children_count' => $this->getBaseChildrenCount(),
            'children_from_previous_marriage' => $this->getAppliedChildrenFromPreviousMarriage(),
            'extra_household_members' => $this->getExtraHouseholdCount(),
        ];
    }

    private function isParentGuardianWithRelevantRemarriage(): bool
    {
        if (!$this->guardian_relation_type_id) {
            return false;
        }

        static $relationTypeTitleCache = [];
        $relationTypeId = (int)$this->guardian_relation_type_id;

        if (!array_key_exists($relationTypeId, $relationTypeTitleCache)) {
            $relationTypeTitleCache[$relationTypeId] = GuardianRelationType::query()
                ->where('id', $relationTypeId)
                ->value('title');
        }

        $relationTypeTitle = trim((string)($relationTypeTitleCache[$relationTypeId] ?? ''));
        $isFatherGuardian = $relationTypeTitle === 'پدر';
        $isMotherGuardian = $relationTypeTitle === 'مادر';

        if (!$isFatherGuardian && !$isMotherGuardian) {
            return false;
        }

        $remarriedParent = strtolower(trim((string)$this->remarried_parent));

        return ($isFatherGuardian && in_array($remarriedParent, ['father', 'both'], true))
            || ($isMotherGuardian && in_array($remarriedParent, ['mother', 'both'], true));
    }

    private function getBaseChildrenCount(): int
    {
        return (int)($this->children_count ?? 0);
    }

    private function getExtraHouseholdCount(): int
    {
        return count($this->extra_household_members ?? []);
    }

    private function getAppliedChildrenFromPreviousMarriage(): int
    {
        if (!$this->isParentGuardianWithRelevantRemarriage()) {
            return 0;
        }

        return max(0, (int)($this->children_from_previous_marriage ?? 0));
    }


    // Wizard navigation methods
    public function nextStep()
    {
        $this->validateCurrentStep();
        $this->markCurrentStepCompleted();

        if ($this->current_step < $this->total_steps) {
            $this->current_step++;
        }
    }

    public function previousStep()
    {
        if ($this->current_step > 1) {
            $this->current_step--;
        }
    }

    public function goToStep($step)
    {
        $step = (int) $step;

        if ($step < 1 || $step > $this->total_steps) {
            return;
        }

        $this->current_step = $step;
    }

    public function skipStep()
    {
        // Skip without validation, but still mark as completed
        $this->markCurrentStepCompleted();

        if ($this->current_step < $this->total_steps) {
            $this->current_step++;
        }
    }

    public function getWizardProgressProperty()
    {
        return ($this->current_step / $this->total_steps) * 100;
    }

    private function validateCurrentStep()
    {
        $rules = $this->rulesForStep($this->current_step);
        if (!empty($rules)) {
            $this->validate($rules);
        }
    }

    private function markCurrentStepCompleted()
    {
        if (!in_array($this->current_step, $this->completed_steps)) {
            $this->completed_steps[] = $this->current_step;
        }
    }

    private function rulesForStep($step)
    {
        switch ($step) {
            case 1: // Personal Information
                return [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'national_id' => 'required|digits:10|unique:people,national_id,' . $this->person_id,
                    'birth_day' => 'required|integer|min:1|max:31',
                    'birth_month' => 'required|integer|min:1|max:12',
                    'birth_year' => 'required|integer|min:1300|max:1420',
                    'father_name' => 'required|string|max:100',
                    'gender' => 'required|in:male,female',
                    'role' => 'required|in:child,guardian',
                ];

            case 2: // Skills and Talents
                return []; // Optional fields

            case 3: // Disability Information
                return [
                    'has_disability' => 'required|in:0,1',
                    'disability_type_id' => Rule::requiredIf($this->has_disability == 1),
                ];

            case 4: // Identity Documents
                return []; // Optional uploads

            case 5: // Education Status
                return [
                    'is_studying' => 'required|in:0,1',
                ];

            case 6: // Family Status
                return []; // Optional fields

            case 7: // Guardian and Livelihood
                return [
                    'guardian_national_code' => 'required|digits:10',
                ];

            case 8: // Banking Information
                return []; // Optional fields

            case 9: // Housing and Contact
                return [
                    'residence_status_id' => 'required|exists:residence_status_types,id',
                    'district_id' => 'required|exists:districts,id',
                ];

            case 10: // Support Needs and Final
                return []; // Optional fields

            default:
                return [];
        }
    }

    public function getStepIncompleteCountsProperty(): array
    {
        $counts = [];

        for ($step = 1; $step <= (int) $this->total_steps; $step++) {
            $rules = $this->rulesForStep($step);
            if (empty($rules)) {
                $counts[$step] = 0;
                continue;
            }

            $validator = Validator::make($this->all(), $rules);
            $counts[$step] = count($validator->errors()->keys());
        }

        return $counts;
    }

    public function render()
    {
        return view('livewire.people.create-person');
    }
}
