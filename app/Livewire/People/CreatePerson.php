<?php

namespace App\Livewire\People;
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

// برای استفاده از Rule::requiredIf
use Carbon\Carbon;

// برای تبدیل تاریخ شمسی به میلادی

class CreatePerson extends Component
{
    use WithFileUploads;

    public $is_submitted = false;
    // --- 1. تعریف متغیرهای اطلاعات فردی مددجو ---
    public $first_name;

    public $last_name;
    public $national_id;


    public $birth_day;
    public $birth_month;
    public $birth_year;

    public $father_name;
    public $father_national_id;
    public $mother_national_id;
    public $phone_number;
    public $gender;

    // سادات (پیش‌فرض عام)
    public $role = 'فرزند'; // مقدار پیش‌فرض
    public $sadaat_status = 'عام'; // مقدار پیش‌فرض
    public $sadaat_relation_id;
    public $social_worker_id;

    // فایل‌ها
    public $photo_id_card;
    public $photo_birth_certificate;

    public $profile_photo;          // برای آپلود فایل (Temporary Uploaded File)
    public $captured_photo_base64; // برای ذخیره رشته تصویر دوربین (Base64)
    // معلولیت (پیش‌فرض 0 یا رشته '0' برای رادیو)
    public $has_disability = '0';
    public $disability_type_id;
    public $disability_description;
    public $skills_description;

    public $harm_types = [];        // لیست IDهایی که کاربر انتخاب می‌کند
    public $allHarmTypes;           // جهت نمایش در فرم

    // مهارت‌ها
    public $skills = []; // آرایه برای ذخیره شناسه مهارت‌های انتخاب شده (تیک خورده)

    // --- 2. تعریف متغیرهای وضعیت خانوادگی ---
    public $guardian_relation_type_id;
    public $economic_decile;

    public $divorced_parent;
    public $remarried_parent;
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
    public $guardian_exists_in_db = false; // وضعیت وجود سرپرست در دیتابیس
    public $current_guardian_id = null; // ID سرپرست موجود در دیتابیس
    // --- پراپرتی جدید برای مدیریت منطق کپی از فیلدهای پدر ---
    public $is_programmatic_guardian_lookup = false;

    // سایر متغیرهای فرم که در مراحل بعدی استفاده می‌شوند
    public $job_type_id;
    public $guardian_phone_number;
    public $children_count;
    public $children_in_house;
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

    public function mount()
    {
        // --- 1. بارگذاری تمام داده‌های lookup (فقط یک بار) ---
        // این داده‌ها در طول عمر کامپوننت ثابت می‌مانند و نیازی به بارگذاری مجدد در هر رندر نیست.
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
    }

    public function updatedCardNumber($value)
    {
        $this->subsidy_card_number = $value;
    }
    public function updatedShebaNumber($value)
    {
        $this->subsidy_sheba_number = $value;
    }
    public function updatedNationalId($value){
        // اعتبارسنجی فقط همین فیلد
        $this->validateOnly('national_id', [
            'national_id' => ['nullable', 'digits:10'],
        ]);
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
        }

        // --- اگر به این مرحله رسیدیم، یعنی national_code دقیقاً 10 رقمی و معتبر است. ---
        // سناریو 3: کد ملی 10 رقمی و کامل است. جستجو در دیتابیس را انجام می‌دهیم.
        $guardian = Guardian::where('national_code', $national_code)->first();

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

            if ($relationType && $relationType->title === 'پدر') {
                // مرحله 1: کد ملی پدر، نام پدر و نام خانوادگی مددجو را کپی می‌کنیم.
                $this->guardian_national_code = $this->father_national_id;
                $this->guardian_first_name = $this->father_name;
                $this->guardian_last_name = $this->last_name;

                // مرحله 2: بررسی می‌کنیم که آیا کد ملی پدر ۱۰ رقمی است تا جستجوی برنامه‌نویسی انجام شود.
                if (strlen(trim($this->father_national_id)) === 10) {
                    $this->is_programmatic_guardian_lookup = true;
                    // مرحله 3: متد updatedGuardianNationalCode را فراخوانی می‌کنیم تا جستجو و پر کردن سایر فیلدهای سرپرست انجام شود.
                    $this->updatedGuardianNationalCode($this->father_national_id);
                    $this->is_programmatic_guardian_lookup = false; // بلافاصله پرچم را ریست می‌کنیم.
                } else {
                    // اگر کد ملی پدر ۱۰ رقمی نبود، فقط نام‌ها و کد ملی (ناقص/نادرست) کپی می‌شوند.
                    // بقیه فیلدهای سرپرست باید پاک شوند.
                    $this->resetDependentGuardianFields();
                    $this->is_programmatic_guardian_lookup = false; // این یک جستجوی موفق programmatic نیست.
                }

            } else {
                // اگر نسبت انتخاب شده "پدر" نیست یا معتبر نیست، تمام فیلدهای سرپرست را ریست می‌کنیم.
                $this->resetAllGuardianFields();
            }
        } else {
            // اگر value معتبر نباشد (مثلا کاربر گزینه "— انتخاب کنید —" را انتخاب کرده باشد)،
            // تمام فیلدهای سرپرست را ریست می‌کنیم.
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
        $this->children_in_house = null;
        $this->insurance_status = '0';
        $this->insurance_type_id = null;
        $this->divorced_child_at_home = null;
        $this->average_income = null;
        $this->any_family_employed = '0';
        $this->has_vehicle = '0';
        $this->vehicle_type_id = null;
    }

    protected function resetAllGuardianFields()
    {
        $this->guardian_national_code = null;
        $this->guardian_first_name = null;
        $this->guardian_last_name = null;
        $this->resetDependentGuardianFields(); // پاک کردن فیلدهای وابسته
        $this->is_programmatic_guardian_lookup = false; // ریست کردن پرچم
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
        $this->children_in_house = $guardian->children_in_house;
        $this->insurance_type_id = $guardian->insurance_type_id;
        $this->divorced_child_at_home = $guardian->divorced_child_at_home;
        $this->average_income = $guardian->average_income;
        $this->vehicle_type_id = $guardian->vehicle_type_id;

        // فیلدهای بولی (Boolean) یا فیلدهای مورد استفاده در رادیو باتن باید به رشته تبدیل شوند
        $this->insurance_status = (string)$guardian->insurance_status;
        $this->any_family_employed = (string)$guardian->any_family_employed;
        $this->has_vehicle = (string)$guardian->has_vehicle;
    }


    public function updatedAnyFamilyEmployed($value)
    {
        if ($value == '0') {
            $this->any_family_employed_description = null;
        }
    }

    /**
     * وقتی وضعیت "حساب شخصی دارد" تغییر می‌کند
     */
    public function updatedHasOwnAccount($value)
    {
        if ($value == '1') {
            // ✅ حساب شخصی → مالک حساب = شخص مددجو
            $this->account_owner_relation_id = 1;
        } else {
            // ✅ حساب شخصی ندارد → مالک حساب = سرپرست قانونی
            $this->account_owner_relation_id = 2;
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


    // --- متد Save (ذخیره نهایی) ---
    public function save()
    {
        $isNewPerson = true;
        $this->is_submitted = true;

        // 1. اعتبارسنجی کامل و جامع (Validation)
        // این بخش تضمین می‌کند داده‌های ورودی تمیز و استاندارد هستند
        $this->validate([
            // --- بخش 1: اطلاعات فردی مددجو ---
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => 'required|string|digits:10|unique:people,national_id',
            'birth_day' => 'required|integer|between:1,31',
            'birth_month' => 'required|integer|between:1,12',
            'birth_year' => 'required|integer|between:1300,1450',
            'father_name' => 'nullable|string|max:255',
            'father_national_id' => 'nullable|string|digits:10',
            'mother_national_id' => 'nullable|string|digits:10',
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|in:مرد,زن',
            'role' => 'required|in:فرزند,سرپرست', // با توجه به سناریو، معمولا "فرزند" است
            'social_worker_id' => 'required|exists:social_workers,id',

            'sadaat_status' => 'required|in:عام,سادات',
            'sadaat_relation_id' => 'nullable|required_if:sadaat_status,سادات|exists:sadaat_relations,id',

            'has_disability' => 'required|boolean',
            'disability_type_id' => 'nullable|required_if:has_disability,1|exists:disability_types,id',
            'disability_description' => 'nullable|string|max:1000',

            'harm_types' => 'nullable|array',
            'harm_types.*' => 'integer|exists:harm_types,id',

            'skills_description' => 'nullable|string|max:1000',
            'skills' => 'nullable|array', // مهارت‌ها یک آرایه از ID ها هستند

            'photo_id_card' => 'nullable|image|max:2048', // حداکثر 2 مگابایت
            'photo_birth_certificate' => 'nullable|image|max:2048',
            'support_card_image' => 'nullable|image|max:2048',
            'profile_photo' => 'nullable|image|max:2048', // حداکثر ۲ مگابایت

            // --- بخش 2: وضعیت خانوادگی ---
            'guardian_relation_type_id' => 'required|exists:guardian_relation_types,id', // نسبت سرپرست با مددجو همیشه باید مشخص باشد
            'economic_decile' => 'nullable|integer|between:1,10',
            'divorced_parent' => 'nullable|in:none,divorced',
            'remarried_parent' => 'nullable|in:none,father,mother,both',
            'children_from_previous_marriage' => 'nullable|integer|min:0',
            'has_parent_disability' => 'nullable|boolean',
            'parent_disability_description' => 'nullable|string|max:1000',

            // --- بخش 3: اطلاعات سرپرست و معیشت ---
            // کد ملی سرپرست همیشه مورد نیاز است و باید در جدول Guardian یونیک باشد
            'guardian_national_code' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('guardians', 'national_code')->ignore($this->current_guardian_id),
            ],
            // نام، نام خانوادگی و تاریخ تولد سرپرست فقط زمانی الزامی است که سرپرست جدید باشد
            'guardian_first_name' => Rule::requiredIf($this->guardian_exists_in_db === false) . '|string|max:255',
            'guardian_last_name' => Rule::requiredIf($this->guardian_exists_in_db === false) . '|string|max:255',
            'guardian_birth_day' => Rule::requiredIf($this->guardian_exists_in_db === false) . '|integer|between:1,31',
            'guardian_birth_month' => Rule::requiredIf($this->guardian_exists_in_db === false) . '|integer|between:1,12',
            'guardian_birth_year' => Rule::requiredIf($this->guardian_exists_in_db === false) . '|integer|between:1300,1450',

            'occupation_id' => 'nullable|exists:occupations,id',
            'job_type_id' => 'nullable|exists:job_types,id',
            'guardian_phone_number' => 'nullable|string|max:20',
            'children_in_house' => 'nullable|integer|min:0',
            'insurance_status' => 'nullable|boolean',
            'insurance_type_id' => 'nullable|required_if:insurance_status,1|exists:insurance_types,id',
            'divorced_child_at_home' => 'nullable|string|in:none,boy,girl,both',
            'average_income' => 'nullable|integer|min:0',
            'any_family_employed' => 'required|boolean',
            'any_family_employed_description' => 'nullable|required_if:any_family_employed,1|string|max:1000',
            'has_vehicle' => 'required|boolean',
            'vehicle_type_id' => 'nullable|required_if:has_vehicle,1|exists:vehicle_types,id',
            'vehicle_ownership_type' => 'nullable|required_if:has_vehicle,1|in:personal,company,rented',

            // --- بخش 4: اطلاعات سکونت و تماس ---
            'residence_status_id' => 'required|exists:residence_status_types,id',
            'district_id' => 'nullable|exists:districts,id',
            'is_local_to_city' => 'required|boolean',
            'deposit_amount' => 'nullable|integer|min:0',
            'monthly_rent' => 'nullable|integer|min:0',
            'residence_duration_years' => 'nullable|integer|min:0',
            'address' => 'nullable|string|max:1000',
            'personal_phone' => 'nullable   |string|max:20',
            'landline_phone' => 'nullable|string|max:20',
            'trusted_person_phone' => 'nullable|string|max:20',
            'messenger_type' => 'nullable|string|max:50',
            'messenger_number' => 'nullable|string|max:50',

            // --- بخش 5: مالی و تحصیلی ---
            'has_own_account' => ['required', 'in:0,1'],
            // اگر حساب شخصی ندارد، حتماً باید نسبت صاحب حساب مشخص شود
            'account_owner_relation_id' => ['nullable', Rule::in([1, 2])],
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

            // --- بخش 6: سطح نیاز و پوشش حمایتی ---
            'support_organization_id' => 'nullable|exists:support_organizations,id',
            'other_organization_name' => 'required_if:support_organization_id,' .
                ($this->support_organizations->where('slug', 'other')->first()?->id ?? '0') .
                '|nullable|string|max:255',
            'coverage_start_day' => 'nullable|integer|between:1,31',
            'coverage_start_month' => 'nullable|integer|between:1,12',
            'coverage_start_year' => 'nullable|integer|between:1300,1450',
            'need_level_id' => 'required|exists:need_level_types,id',
        ]);

// پردازش تصویر پروفایل
        $profilePhotoPath = null;

        if ($this->captured_photo_base64) {
            // اولویت با عکس دوربین: تبدیل Base64 به فایل
            $image = $this->captured_photo_base64;
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $fileName = 'profile_' . uniqid() . '.png';
            $profilePhotoPath = 'uploads/profile_photos/' . $fileName;

            \Storage::disk('public')->put($profilePhotoPath, base64_decode($image));
        } elseif ($this->profile_photo) {
            // حالت دوم: اگر عکسی گرفته نشده، فایل آپلود شده را ذخیره کن
            $profilePhotoPath = $this->profile_photo->store('uploads/profile_photos', 'public');
        }


        DB::beginTransaction(); // شروع تراکنش دیتابیس

        try {
            // --- 1. مدیریت آپلود فایل‌ها ---
            $paths = [
                'photo_id_card' => $this->photo_id_card ? $this->photo_id_card->store('uploads/photo_id_card', 'public') : null,
                'photo_birth_certificate' => $this->photo_birth_certificate ? $this->photo_birth_certificate->store('uploads/photo_birth_certificate', 'public') : null,
                'support_card_image' => $this->support_card_image ? $this->support_card_image->store('uploads/support_card_image', 'public') : null,
                ];


            // --- 2. آماده‌سازی تاریخ‌های کامل شمسی ---
            $personBirthDateFull = sprintf('%04d/%02d/%02d', $this->birth_year, $this->birth_month, $this->birth_day);

            $guardianBirthDateFull = null;
            if ($this->guardian_birth_year && $this->guardian_birth_month && $this->guardian_birth_day) {
                $guardianBirthDateFull = sprintf('%04d/%02d/%02d', $this->guardian_birth_year, $this->guardian_birth_month, $this->guardian_birth_day);
            }

            $coverageDateFull = null;
            if ($this->coverage_start_year && $this->coverage_start_month && $this->coverage_start_day) {
                $coverageDateFull = sprintf('%04d/%02d/%02d', $this->coverage_start_year, $this->coverage_start_month, $this->coverage_start_day);
            }

            // --- 3. مدیریت سرپرست (ایجاد یا به‌روزرسانی) ---
            $guardian_id_to_assign = null;
            $guardianInstance = null; // برای نگهداری مدل Guardian

            if ($this->guardian_exists_in_db && $this->current_guardian_id) {
                // اگر سرپرست در دیتابیس موجود است، آن را بازیابی می‌کنیم
                $guardianInstance = Guardian::find($this->current_guardian_id);
                if (!$guardianInstance) {
                    throw new \Exception("Existing guardian with ID {$this->current_guardian_id} not found.");
                }
                // اطلاعات شناسایی (نام، کد ملی، تاریخ تولد) برای سرپرست موجود فرض بر ثابت بودن دارد
                // اگر نیاز به بروزرسانی این فیلدها باشد، باید اینجا اضافه شود:
                // $guardianInstance->update([
                //     'first_name' => $this->guardian_first_name,
                //     'last_name' => $this->guardian_last_name,
                //     // ...
                // ]);
            } else {
                // اگر سرپرست جدید است، آن را ایجاد می‌کنیم
                $guardianInstance = Guardian::create([
                    'guardian_code' => Guardian::generateNextGuardianCode(),
                    'national_code' => $this->guardian_national_code,
                    'first_name' => $this->guardian_first_name,
                    'last_name' => $this->guardian_last_name,
                    'guardian_birth_day' => $this->guardian_birth_day,
                    'guardian_birth_month' => $this->guardian_birth_month,
                    'guardian_birth_year' => $this->guardian_birth_year,
                    'guardian_birth_date_full' => $guardianBirthDateFull,
                    'children_count' => 0,
                ]);
            }
            $guardian_id_to_assign = $guardianInstance->id;

            // اطلاعات معیشت و سایر جزئیات سرپرست را روی Guardian Instance به‌روز می‌کنیم.
            // این بخش برای سرپرست جدید (که تازه ساخته شده) و سرپرست موجود (که از دیتابیس بازیابی شده) مشترک است.
            if ($guardianInstance) {
                $guardianInstance->update([
                    'occupation_id' => $this->occupation_id,
                    'job_type_id' => $this->job_type_id ?: null,
                    'guardian_phone_number' => $this->guardian_phone_number,
                    'children_in_house' => (int)$this->children_in_house,
                    'insurance_status' => (bool)$this->insurance_status, // تبدیل به boolean
                    'insurance_type_id' => ((bool)$this->insurance_status) ? $this->insurance_type_id : null,
                    'divorced_child_at_home' => $this->divorced_child_at_home ?: 'none',
                    'average_income' => (int)$this->average_income,
                    'any_family_employed' => (bool)$this->any_family_employed,
                    'any_family_employed_description' => ((bool)$this->any_family_employed) ? $this->any_family_employed_description : null,
                    'has_vehicle' => (bool)$this->has_vehicle, // تبدیل به boolean
                    'vehicle_type_id' => ((bool)$this->has_vehicle) ? $this->vehicle_type_id : null,
                    'vehicle_ownership_type' => ((bool)$this->has_vehicle) ? $this->vehicle_ownership_type : null,
                ]);
            } else {
                // اگر به هر دلیلی Guardian Instance ایجاد یا بازیابی نشد، خطا صادر می‌کنیم
                throw new \Exception("Failed to retrieve or create guardian instance for ID: " . ($this->current_guardian_id ?? 'new'));
            }


            // --- 4. ذخیره در جداول دیگر ---

            // جدول 1: Person - اطلاعات فردی مددجو
            $person = Person::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_id' => $this->national_id,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'birth_date_full' => $personBirthDateFull,
                'father_name' => $this->father_name,
                'father_national_id' => $this->father_national_id,
                'mother_national_id' => $this->mother_national_id,
                'phone_number' => $this->phone_number,
                'gender' => $this->gender,
                'role' => $this->role,
                'sadaat_status' => $this->sadaat_status,
                'sadaat_relation_id' => ($this->sadaat_status === 'سادات') ? $this->sadaat_relation_id : null,
                'skills_description' => $this->skills_description,
                'social_worker_id' => $this->social_worker_id,
                'photo_id_card' => $paths['photo_id_card'],
                'photo_birth_certificate' => $paths['photo_birth_certificate'],
                'profile_photo' => $profilePhotoPath,
                'has_disability' => (bool)$this->has_disability, // تبدیل به boolean
                'disability_type_id' => ((bool)$this->has_disability) ? $this->disability_type_id : null,
                'disability_description' => ((bool)$this->has_disability) ? $this->disability_description : null,
                'guardian_id' => $guardian_id_to_assign, // اختصاص Guardian ID به Person
            ]);

            if ($isNewPerson) {
                $guardianInstance->increment('children_count');
            }

            // جدول 2: FamilyStatus - وضعیت خانوادگی مددجو
            FamilyStatus::create([
                'person_id' => $person->id,
                'guardian_relation_type_id' => $this->guardian_relation_type_id,
                'economic_decile' => $this->economic_decile ?: null,
                'divorced_parent' => $this->divorced_parent,
                'remarried_parent' => $this->remarried_parent,
                'children_from_previous_marriage' => (int)$this->children_from_previous_marriage,
                'has_parent_disability' => (bool)$this->has_parent_disability, // تبدیل به boolean
                'parent_disability_description' => ((bool)$this->has_parent_disability) ? $this->parent_disability_description : null,
            ]);

            // جدول 3: Residence - اطلاعات سکونت و تماس
            Residence::create([
                'person_id' => $person->id,
                'residence_status_id' => $this->residence_status_id,
                'district_id' => $this->district_id ?: null,
                'is_local_to_city' => (bool)$this->is_local_to_city, // تبدیل به boolean
                'deposit_amount' => (int)$this->deposit_amount,
                'monthly_rent' => (int)$this->monthly_rent,
                'residence_duration_years' => (int)$this->residence_duration_years,
                'address' => $this->address,
                'personal_phone' => $this->personal_phone,
                'landline_phone' => $this->landline_phone,
                'trusted_person_phone' => $this->trusted_person_phone,
                'messenger_type' => $this->messenger_type,
                'messenger_number' => $this->messenger_number,
            ]);

            // جدول 4: BankInfo - اطلاعات بانکی
            BankInfo::create([
                'person_id' => $person->id,
                'has_own_account' => (bool)$this->has_own_account, // تبدیل به boolean
                'account_owner_relation_id' => $this->account_owner_relation_id ?: null,
                'bank_id' => $this->bank_id ?: null,
                'card_number' => $this->card_number,
                'sheba_number' => $this->sheba_number,
                'subsidy_card_number' => $this->subsidy_card_number,
                'subsidy_sheba_number' => $this->subsidy_sheba_number,
            ]);

            // جدول 5: Education - اطلاعات تحصیلی
            Education::create([
                'person_id' => $person->id,
                'is_studying' => (bool)$this->is_studying, // تبدیل به boolean
                'school_name' => $this->school_name,
                'major' => $this->major,
                'education_level_id' => $this->education_level_id ?: null,
                'drop_reason' => $this->drop_reason,
                'works_alongside_study' => (bool)$this->works_alongside_study, // تبدیل به boolean
                'monthly_income' => (int)$this->monthly_income,
            ]);

            // جدول 6: SupportCoverage - اطلاعات پوشش حمایتی
            SupportCoverage::create([
                'person_id' => $person->id,
                'support_organization_id' => $this->support_organization_id,
                'other_organization_name' => $this->other_organization_name,
                'coverage_start_day' => $this->coverage_start_day ?: null,
                'coverage_start_month' => $this->coverage_start_month ?: null,
                'coverage_start_year' => $this->coverage_start_year ?: null,
                'coverage_start_date' => $coverageDateFull,
                'support_card_image' => $paths['support_card_image'],
            ]);

            // جدول 7: NeedsLevel - سطح نیاز تشخیص داده شده
            NeedsLevel::create([
                'person_id' => $person->id,
                'need_level_id' => $this->need_level_id,
                'evaluation_date' => now(),
                'reviewer_name' => auth()->user()->name ?? 'سیستم', // نام کاربر فعلی یا 'سیستم'
            ]);

            // Sync کردن مهارت‌ها (اتصال Person به Skills از طریق جدول واسط)
            if (!empty($this->skills)) {
                $person->skills()->sync($this->skills);
            }

            // اتصال نوع آسیب‌ها (Many-to-Many)
            if (!empty($this->harm_types)) {
                $person->harmTypes()->sync($this->harm_types);
            }

            DB::commit(); // اتمام موفقیت‌آمیز تراکنش

            session()->flash('success', 'اطلاعات مددجو با موفقیت ثبت و ذخیره شد.');
            $this->reset(); // ریست کردن تمام فیلدهای فرم پس از ذخیره موفق

            return redirect()->route('people.create'); // ریدایرکت به همین صفحه برای ثبت مددجوی جدید

        } catch (\Exception $e) {
            DB::rollBack(); // در صورت بروز خطا، تراکنش را برمی‌گردانیم
            \Log::error('Create Person Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]); // لاگ خطا با جزئیات بیشتر
            session()->flash('error', 'خطا در ثبت اطلاعات. لطفاً ورودی‌ها را بررسی کنید و در صورت تکرار، با پشتیبانی تماس بگیرید.');
            // در صورت نیاز به مشاهده خطای کامل در محیط توسعه، می‌توانید خط زیر را فعال کنید:
            // throw $e;
        }
    }

    public function render()
    {
        return view('livewire.people.create-person')
            ->extends('layouts.app')
            ->section('content');
    }
}
