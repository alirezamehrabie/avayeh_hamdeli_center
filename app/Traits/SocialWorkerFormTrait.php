<?php

namespace App\Traits;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait SocialWorkerFormTrait
{
    // --- فیلدهای اطلاعات فردی ---
    public $first_name;
    public $last_name;
    public $national_id;
    public $id_number;
    public $birth_day;
    public $birth_month;
    public $birth_year;
    public $mobile;
    public $education;
    public $occupation;
    public $family_members_count = 0;
    /** @var TemporaryUploadedFile|null */
    public $photo;


    // --- فیلدهای حرفه‌ای ---
    public $start_day;
    public $start_month;
    public $start_year;
    public $covered_area;

    // --- فیلدهای آماری ---
    public $covered_people_count = 0;
    public $covered_households_count = 0;
    public $covered_children_count = 0;

    // --- فیلدهای همکار علی‌البدل ---
    public $substitute_first_name;
    public $substitute_last_name;
    public $substitute_mobile;
    public $district_id;
    public $occupation_id;
    public $academic_level_id;
    public $existingPhoto;

    public $canEditNationalId = false;


    protected function getValidationRules($ignoreId = null)
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('social_workers', 'national_id')->ignore($ignoreId),
            ],
            'id_number' => 'nullable|string',
            'mobile' => [
                'required',
                'regex:/^09[0-9]{9}$/',
                Rule::unique('social_workers', 'mobile')->ignore($ignoreId),
            ],
            'birth_year' => 'nullable|integer|between:1320,1410',
            'birth_month' => 'nullable|integer|between:1,12',
            'birth_day' => 'nullable|integer|between:1,31',
            'start_year' => 'nullable|integer|between:1370,1450',
            'start_month' => 'nullable|integer|between:1,12',
            'start_day' => 'nullable|integer|between:1,31',
            'district_id' => 'nullable|exists:districts,id',
            'occupation_id' => 'nullable|exists:occupations,id',
            'academic_level_id' => 'nullable|exists:academic_levels,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'substitute_first_name' => 'nullable|string|max:100',
            'substitute_last_name' => 'nullable|string|max:100',
            'substitute_mobile' => ['nullable', 'regex:/^09[0-9]{9}$/'],
        ];
    }


    /**
     * ترکیب تاریخ تولد
     *
     * @return string|null
     */
    public function getBirthDateFull(): ?string
    {
        if (!$this->birth_year || !$this->birth_month || !$this->birth_day) {
            return null;
        }
        return "{$this->birth_year}/{$this->birth_month}/{$this->birth_day}";
    }

    /**
     * ترکیب تاریخ شروع
     *
     * @return string|null
     */
    public function getStartDateFull(): ?string
    {
        if (!$this->start_year || !$this->start_month || !$this->start_day) {
            return null;
        }
        return "{$this->start_year}/{$this->start_month}/{$this->start_day}";
    }

    /**
     * آپلود عکس
     *
     * @return string|null
     */
    public function uploadPhoto(): ?string
    {
        if ($this->photo instanceof TemporaryUploadedFile) {
            return $this->photo->store('social_workers_photos', 'public');
        }
        return null;
    }
}
