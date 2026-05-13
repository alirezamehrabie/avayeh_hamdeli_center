<?php

namespace App\Livewire\Guardians;

use App\Models\AccountOwnerRelation;
use App\Models\Bank;
use App\Models\BankInfo;
use App\Models\Contact;
use App\Models\District;
use App\Models\Guardian;
use App\Models\InsuranceType;
use App\Models\JobType;
use App\Models\Occupation;
use App\Models\Residence;
use App\Models\ResidenceStatusType;
use App\Models\SocialWorker;
use App\Models\VehicleType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EditGuardian extends Component
{
    public Guardian $guardian;
    public bool $embedded = false;

    public $social_worker_id;
    public $national_code;
    public $first_name;
    public $last_name;
    public $guardian_phone_number;
    public $guardian_birth_day;
    public $guardian_birth_month;
    public $guardian_birth_year;
    public $children_in_house;
    public $economic_decile;
    public $occupation_id;
    public $job_type_id;
    public $insurance_status = false;
    public $insurance_type_id;
    public $divorced_child_at_home = 'none';
    public $average_income;
    public $any_family_employed = false;
    public $any_family_employed_description;
    public $has_vehicle = false;
    public $vehicle_type_id;
    public $vehicle_ownership_type;
    public $extra_household_members = [];

    public $residence_status_id;
    public $district_id;
    public $is_local_to_city = true;
    public $deposit_amount;
    public $monthly_rent;
    public $residence_duration_years;
    public $address;

    public $landline_phone;
    public $trusted_person_phone;
    public $messenger_type;
    public $messenger_number;

    public $bank_id;
    public $card_number;
    public $sheba_number;
    public $subsidy_card_number;
    public $subsidy_sheba_number;
    public $account_owner_relation_id;
    public $other_account_owner_relation;

    public $socialWorkers;
    public $occupations;
    public $jobTypes;
    public $insuranceTypes;
    public $vehicleTypes;
    public $residenceStatusTypes;
    public $districts;
    public $banks;
    public $accountRelations;

    public function mount(Guardian $guardian): void
    {
        $this->guardian = $guardian->load(['residence', 'contact', 'bankInfo', 'people:id,guardian_id,first_name,last_name,person_code']);

        $this->socialWorkers = SocialWorker::query()->orderBy('first_name')->get();
        $this->occupations = Occupation::query()->orderBy('name')->get();
        $this->jobTypes = JobType::query()->orderBy('name')->get();
        $this->insuranceTypes = InsuranceType::query()->orderBy('name')->get();
        $this->vehicleTypes = VehicleType::query()->orderBy('name')->get();
        $this->residenceStatusTypes = ResidenceStatusType::query()->orderBy('name')->get();
        $this->districts = District::query()->orderBy('name')->get();
        $this->banks = Bank::query()->orderBy('name')->get();
        $this->accountRelations = AccountOwnerRelation::query()->orderBy('name')->get();

        $this->social_worker_id = $guardian->social_worker_id;
        $this->national_code = $guardian->national_code;
        $this->first_name = $guardian->first_name;
        $this->last_name = $guardian->last_name;
        $this->guardian_phone_number = $guardian->guardian_phone_number;
        $this->guardian_birth_day = $guardian->guardian_birth_day;
        $this->guardian_birth_month = $guardian->guardian_birth_month;
        $this->guardian_birth_year = $guardian->guardian_birth_year;
        $this->children_in_house = $guardian->children_in_house;
        $this->economic_decile = $guardian->economic_decile;
        $this->occupation_id = $guardian->occupation_id;
        $this->job_type_id = $guardian->job_type_id;
        $this->insurance_status = (bool) $guardian->insurance_status;
        $this->insurance_type_id = $guardian->insurance_type_id;
        $this->divorced_child_at_home = $guardian->divorced_child_at_home ?: 'none';
        $this->average_income = $guardian->average_income;
        $this->any_family_employed = (bool) $guardian->any_family_employed;
        $this->any_family_employed_description = $guardian->any_family_employed_description;
        $this->has_vehicle = (bool) $guardian->has_vehicle;
        $this->vehicle_type_id = $guardian->vehicle_type_id;
        $this->vehicle_ownership_type = $guardian->vehicle_ownership_type;
        $this->extra_household_members = is_array($guardian->extra_household_members) ? $guardian->extra_household_members : [];

        $residence = $guardian->residence;
        if ($residence) {
            $this->residence_status_id = $residence->residence_status_id;
            $this->district_id = $residence->district_id;
            $this->is_local_to_city = (bool) $residence->is_local_to_city;
            $this->deposit_amount = $residence->deposit_amount;
            $this->monthly_rent = $residence->monthly_rent;
            $this->residence_duration_years = $residence->residence_duration_years;
            $this->address = $residence->address;
        }

        $contact = $guardian->contact;
        if ($contact) {
            $this->landline_phone = $contact->landline_phone;
            $this->trusted_person_phone = $contact->trusted_person_phone;
            $this->messenger_type = $contact->messenger_type;
            $this->messenger_number = $contact->messenger_number;
        }

        $bankInfo = $guardian->bankInfo;
        if ($bankInfo) {
            $this->bank_id = $bankInfo->bank_id;
            $this->card_number = $bankInfo->card_number;
            $this->sheba_number = $bankInfo->sheba_number;
            $this->subsidy_card_number = $bankInfo->subsidy_card_number;
            $this->subsidy_sheba_number = $bankInfo->subsidy_sheba_number;
            $this->account_owner_relation_id = $bankInfo->account_owner_relation_id;
            $this->other_account_owner_relation = $bankInfo->other_account_owner_relation;
        }
    }

    protected function rules(): array
    {
        return [
            'social_worker_id' => 'nullable|exists:social_workers,id',
            'national_code' => ['nullable', 'digits:10', Rule::unique('guardians', 'national_code')->ignore($this->guardian->id)],
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'guardian_phone_number' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'guardian_birth_year' => 'nullable|integer|between:1300,1450',
            'guardian_birth_month' => 'nullable|integer|between:1,12',
            'guardian_birth_day' => ['nullable', 'integer', 'between:1,31', function ($attribute, $value, $fail) {
                if (!$value || !$this->guardian_birth_month) {
                    return;
                }
                if ($this->guardian_birth_month > 6 && $this->guardian_birth_month < 12 && $value > 30) {
                    $fail('برای نیمه دوم سال، روز نمی‌تواند بیشتر از ۳۰ باشد.');
                }
                if ($this->guardian_birth_month == 12 && $value > 29) {
                    $fail('برای اسفند، روز نمی‌تواند بیشتر از ۲۹ باشد.');
                }
            }],
            'children_in_house' => 'nullable|integer|min:0',
            'economic_decile' => 'nullable|integer|min:1|max:10',
            'occupation_id' => 'nullable|exists:occupations,id',
            'job_type_id' => 'nullable|exists:job_types,id',
            'insurance_status' => 'nullable|boolean',
            'insurance_type_id' => 'nullable|required_if:insurance_status,1|exists:insurance_types,id',
            'divorced_child_at_home' => 'nullable|in:none,son,daughter,both',
            'average_income' => 'nullable|integer|min:0',
            'any_family_employed' => 'nullable|boolean',
            'any_family_employed_description' => 'nullable|required_if:any_family_employed,1|string|max:500',
            'has_vehicle' => 'nullable|boolean',
            'vehicle_type_id' => 'nullable|required_if:has_vehicle,1|exists:vehicle_types,id',
            'vehicle_ownership_type' => 'nullable|required_if:has_vehicle,1|in:personal,company,rented',
            'residence_status_id' => 'nullable|exists:residence_status_types,id',
            'district_id' => 'nullable|exists:districts,id',
            'is_local_to_city' => 'nullable|boolean',
            'deposit_amount' => 'nullable|integer|min:0',
            'monthly_rent' => 'nullable|integer|min:0',
            'residence_duration_years' => 'nullable|integer|min:0',
            'address' => 'nullable|string|max:1000',
            'landline_phone' => ['nullable', 'regex:/^0[1-9]{2}[0-9]{8}$/'],
            'trusted_person_phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'messenger_type' => 'nullable|string|max:70',
            'messenger_number' => ['nullable', 'required_with:messenger_type', 'regex:/^09[0-9]{9}$/'],
            'bank_id' => 'nullable|exists:banks,id',
            'card_number' => 'nullable|digits:16',
            'sheba_number' => ['nullable', 'size:26', 'regex:/^IR[0-9]{24}$/i'],
            'subsidy_card_number' => 'nullable|digits:16',
            'subsidy_sheba_number' => 'nullable|string|max:26',
            'account_owner_relation_id' => 'nullable|exists:account_owner_relations,id',
            'other_account_owner_relation' => 'nullable|string|max:255',
        ];
    }

    public function updatedInsuranceStatus($value): void
    {
        if (!(bool) $value) {
            $this->insurance_type_id = null;
        }
    }

    public function updatedHasVehicle($value): void
    {
        if (!(bool) $value) {
            $this->vehicle_type_id = null;
            $this->vehicle_ownership_type = null;
        }
    }

    private function normalizeNullableFields(): void
    {
        foreach ([
            'social_worker_id',
            'occupation_id',
            'job_type_id',
            'insurance_type_id',
            'average_income',
            'vehicle_type_id',
            'residence_status_id',
            'district_id',
            'deposit_amount',
            'monthly_rent',
            'residence_duration_years',
            'bank_id',
            'account_owner_relation_id',
        ] as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
        }
    }

    private function toNullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    public function save(): mixed
    {
        $this->normalizeNullableFields();
        $this->validate();

        DB::transaction(function () {
            $guardianBirthDateFull = null;
            if ($this->guardian_birth_year && $this->guardian_birth_month && $this->guardian_birth_day) {
                $guardianBirthDateFull = sprintf('%04d/%02d/%02d', $this->guardian_birth_year, $this->guardian_birth_month, $this->guardian_birth_day);
            }

            $this->guardian->update([
                'social_worker_id' => $this->social_worker_id,
                'national_code' => $this->national_code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'guardian_phone_number' => $this->guardian_phone_number,
                'guardian_birth_day' => $this->guardian_birth_day,
                'guardian_birth_month' => $this->guardian_birth_month,
                'guardian_birth_year' => $this->guardian_birth_year,
                'guardian_birth_date_full' => $guardianBirthDateFull,
                'children_in_house' => $this->children_in_house,
                'economic_decile' => $this->economic_decile,
                'occupation_id' => $this->occupation_id,
                'job_type_id' => $this->job_type_id,
                'insurance_status' => (bool) $this->insurance_status,
                'insurance_type_id' => (bool) $this->insurance_status ? $this->insurance_type_id : null,
                'divorced_child_at_home' => $this->divorced_child_at_home ?: 'none',
                'average_income' => $this->toNullableInt($this->average_income),
                'any_family_employed' => (bool) $this->any_family_employed,
                'any_family_employed_description' => (bool) $this->any_family_employed ? $this->any_family_employed_description : null,
                'has_vehicle' => (bool) $this->has_vehicle,
                'vehicle_type_id' => (bool) $this->has_vehicle ? $this->vehicle_type_id : null,
                'vehicle_ownership_type' => (bool) $this->has_vehicle ? $this->vehicle_ownership_type : null,
            ]);

            Residence::updateOrCreate(
                ['guardian_id' => $this->guardian->id],
                [
                    'person_id' => $this->guardian->residence?->person_id,
                    'residence_status_id' => $this->residence_status_id,
                    'district_id' => $this->district_id,
                    'is_local_to_city' => (bool) $this->is_local_to_city,
                    'deposit_amount' => $this->toNullableInt($this->deposit_amount),
                    'monthly_rent' => $this->toNullableInt($this->monthly_rent),
                    'residence_duration_years' => $this->toNullableInt($this->residence_duration_years),
                    'address' => $this->address,
                ]
            );

            Contact::updateOrCreate(
                ['guardian_id' => $this->guardian->id],
                [
                    'person_id' => $this->guardian->contact?->person_id,
                    'landline_phone' => $this->landline_phone,
                    'trusted_person_phone' => $this->trusted_person_phone,
                    'messenger_type' => $this->messenger_type,
                    'messenger_number' => $this->messenger_number,
                ]
            );

            BankInfo::updateOrCreate(
                ['guardian_id' => $this->guardian->id],
                [
                    'person_id' => null,
                    'has_own_account' => false,
                    'bank_id' => $this->bank_id,
                    'card_number' => $this->card_number,
                    'sheba_number' => $this->sheba_number,
                    'subsidy_card_number' => $this->subsidy_card_number,
                    'subsidy_sheba_number' => $this->subsidy_sheba_number,
                    'account_owner_relation_id' => $this->account_owner_relation_id,
                    'other_account_owner_relation' => $this->other_account_owner_relation,
                ]
            );
        });

        session()->flash('success', 'اطلاعات سرپرست با موفقیت به‌روزرسانی شد.');

        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'guardians-list');
            return null;
        }

        return redirect()->route('guardians.index');
    }

    public function backToList(): mixed
    {
        if ($this->embedded) {
            $this->dispatch('open-dashboard-section', section: 'guardians-list');
            return null;
        }

        return redirect()->route('guardians.index');
    }

    public function render()
    {
        return view('livewire.guardians.edit-guardian');
    }
}
