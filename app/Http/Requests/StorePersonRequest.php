<?php

namespace App\Http\Requests;
use App\Models\AccountOwnerRelation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Everyone can try to create a person for now
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $otherRelationId = AccountOwnerRelation::where('name', 'سایر')->value('id');

        return [
            // Section 1: Person Info
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => 'required|string|digits:10|unique:people,national_id',
            'birth_day' => 'required|integer|min:1|max:31',
            'birth_month' => ['required', 'integer', 'min:1', 'max:12'],
            'birth_year' => 'required|integer|min:1300|max:1500',
            'father_name' => 'nullable|string|max:255',
            'father_national_id' => 'nullable|string|digits:10',
            'mother_national_id' => 'nullable|string|digits:10',
            'gender' => ['required', Rule::in(['مرد', 'زن'])],
            'sadaat_status'      => ['required', Rule::in(['سادات','عام'])],
            'sadaat_relation_id' => ['nullable','exists:sadaat_relations,id','required_if:sadaat_status,سادات'],

            'role' => ['required', Rule::in(['فرزند', 'سرپرست'])],
            'social_worker_id' => 'required|exists:social_workers,id',
            'photo_id_card' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'photo_birth_certificate' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'has_disability' => 'required|boolean',
            'disability_type_id' => 'required_if:has_disability,1|integer|exists:disability_types,id',
            'disability_description' => 'nullable|string|max:1000',
            'skills' => 'nullable|array',
            'skills.*' => ['integer', 'exists:skills,id',],
            'skills_description' => 'nullable|string|max:1000',
            'photo_live_capture' => 'nullable|string', // base64 image string



            // Section 2: Family Status
            'parents_alive' => 'nullable|string|max:255',
            'death_year' => 'nullable|numeric|digits:4',
            'death_reason' => 'nullable|string',
            'children_from_previous_marriage' => 'nullable|integer|min:0',
            'has_parent_disability' => 'nullable|boolean',
            'parent_disability_description' => 'nullable|required_if:has_parent_disability,1|string|max:1000',
            // اضافه کردن قانون برای guardian_relation
            'guardian_relation_type_id' => ['nullable', 'exists:guardian_relation_types,id'],

            'economic_decile' => [
                'nullable',
                Rule::in(['1','2','3','4','5','6','7','8','9','10']),
            ],
            'living_parents' => ['nullable', Rule::in(['پدر', 'مادر', 'هر دو (پدر و مادر)'])],
            'deceased_parent' => ['nullable', Rule::in(['پدر', 'مادر', 'هر دو (پدر و مادر)'])],
            'divorced_parent' => ['nullable', Rule::in(['پدر', 'مادر', 'هر دو (پدر و مادر)'])],
            'remarried_parent' => ['nullable', Rule::in(['پدر', 'مادر', 'هر دو (پدر و مادر)'])],

            // Section 3: Guardian Info
            'guardian_birth_date' => 'nullable|date',
            'occupation_id' => 'required|exists:occupations,id',
            'job_type_id' => ['nullable', 'exists:job_types,id'],
            'guardian_phone_number' => 'nullable|string|max:20',
            'children_count' => 'nullable|integer|min:0',
            'children_in_house' => 'nullable|integer|min:0',
            'insurance_status' => ['required', 'boolean'],
            'insurance_type_id' => ['nullable', 'required_if:insurance_status,1', 'exists:insurance_types,id'],
            'divorced_child_at_home' => ['nullable', Rule::in(['ندارد', 'پسر', 'دختر', 'پسر / دختر'])],
            'average_income' => 'nullable|numeric|min:0',
            'any_family_employed' => 'required|boolean',
            'has_vehicle' => 'required|boolean',
            'vehicle_type_id' => ['nullable', 'required_if:has_vehicle,1', 'exists:vehicle_types,id'],


            // Section 4: Residence & Contact
            'residence_status_id' => 'required|exists:residence_status_types,id',
            'is_local_to_city' => 'required|boolean',
            'deposit_amount' => 'nullable|numeric|min:0',
            'monthly_rent' => 'nullable|numeric|min:0',
            'residence_duration_years' => 'nullable|integer|min:0',
            'address'      => 'required|string',
            'district_id'  => 'nullable|exists:districts,id',
            'personal_phone' => 'nullable|string|max:20',
            'landline_phone' => 'nullable|string|max:20',
            'trusted_person_phone' => 'nullable|string|max:20',
            'messenger_type' => 'nullable|string|max:255',
            'messenger_number' => 'nullable|string|max:255',

            // Section 5: Financial, Education & Support
            'has_own_account' => 'required|boolean',
            'account_owner_relation_id' => 'nullable|exists:account_owner_relations,id',
            'other_account_owner_relation' => [
                'nullable',
                'string',
                'max:255',
                // اگر ID انتخاب شده برابر با ID "سایر" بود، این فیلد اجباری است
                Rule::requiredIf(function () use ($otherRelationId) {
                    return request('account_owner_relation_id') == $otherRelationId;
                }),
            ],
            'bank_id' => 'nullable|exists:banks,id',
            'card_number' => 'nullable|digits:16|numeric', // 16 digits + 3 spaces
            'sheba_number' => 'nullable|string|max:26',
            'subsidy_card_number' => 'nullable|digits:16|numeric',
            'subsidy_sheba_number' => 'nullable|string|max:26',
            'is_studying' => 'required|boolean',
            'school_name' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'education_level_id'  => 'nullable|exists:education_levels,id',
            'drop_reason' => 'nullable|string',
            'works_alongside_study' => 'required|boolean',
            'monthly_income' => 'nullable|numeric|min:0',
            'talent_description' => 'nullable|string',
            'organization_type' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'coverage_start_date' => 'nullable|date',
            'support_card_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Section 6: Needs Level
            'need_level_id' => 'nullable|exists:need_level_types,id',
//            'evaluation_date' => 'required|date',
//            'reviewer_name' => 'required|string|max:255',
        ];
    }

    protected function withValidator($validator)
    {
        $validator->sometimes('sadaat_relation_id', ['required'], function ($input) {
            return $input->sadaat_status === 'سادات';
        });
    }

}
