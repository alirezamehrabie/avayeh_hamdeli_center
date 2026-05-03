<?php

namespace App\Livewire\People;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class FastCreatePerson extends Component
{
    public bool $embedded = false;
    public ?Person $person = null;

    // Required fields for fast registration
    public $first_name;
    public $last_name;
    public $national_id;
    public $birth_day;
    public $birth_month;
    public $birth_year;
    public $gender;
    public $father_name;
    public $father_national_id;
    public $mother_national_id;
    public $role = 'child';

    public function mount(?Person $person = null): void
    {
        if (!$person?->exists) {
            return;
        }

        $this->person = $person;
        $this->first_name = $person->first_name;
        $this->last_name = $person->last_name;
        $this->national_id = $person->national_id;
        $this->birth_day = $person->birth_day;
        $this->birth_month = $person->birth_month;
        $this->birth_year = $person->birth_year;
        $this->gender = $person->gender;
        $this->father_name = $person->father_name;
        $this->father_national_id = $person->father_national_id;
        $this->mother_national_id = $person->mother_national_id;
        $this->role = $person->role ?: 'child';
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                'string',
                'size:10',
                Rule::unique('people', 'national_id')->ignore($this->person?->id),
            ],
            'birth_day' => 'required|integer|min:1|max:31',
            'birth_month' => 'required|integer|min:1|max:12',
            'birth_year' => 'required|integer|min:1300|max:1420',
            'gender' => 'required|in:male,female',
            'father_name' => 'nullable|string|max:100',
            'father_national_id' => 'nullable|digits:10',
            'mother_national_id' => 'nullable|digits:10',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'لطفاً نام را وارد کنید',
            'last_name.required' => 'لطفاً نام خانوادگی را وارد کنید',
            'national_id.required' => 'لطفاً کد ملی را وارد کنید',
            'national_id.unique' => 'این کد ملی قبلاً ثبت شده است',
            'birth_day.required' => 'لطفاً روز تولد را انتخاب کنید',
            'birth_month.required' => 'لطفاً ماه تولد را انتخاب کنید',
            'birth_year.required' => 'لطفاً سال تولد را انتخاب کنید',
            'gender.required' => 'لطفاً جنسیت را انتخاب کنید',
            'gender.in' => 'جنسیت باید مرد یا زن باشد',
            'father_name.max' => 'نام پدر نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'father_national_id.digits' => 'کد ملی پدر باید 10 رقم باشد',
            'mother_national_id.digits' => 'کد ملی مادر باید 10 رقم باشد',
        ];
    }

    public function save()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $data = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_id' => $this->national_id,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'gender' => $this->gender,
                'father_name' => $this->father_name,
                'father_national_id' => $this->father_national_id,
                'mother_national_id' => $this->mother_national_id,
                'role' => $this->role,
            ];

            if ($this->person) {
                $this->person->update($data);
                $successMessage = 'ویرایش سریع مددجو با موفقیت انجام شد.';
            } else {
                Person::create($data + ['person_code' => Person::generateUniqueCode()]);
                $successMessage = 'اطلاعات فرد به صورت سریع ثبت شد. اکنون می‌توانید اطلاعات کامل این فرد را ویرایش کنید.';
            }

            DB::commit();

            session()->flash('success', $successMessage);

            if ($this->embedded) {
                $this->dispatch('open-dashboard-section', section: 'people-list');
                return;
            }

            if (!$this->person) {
                $this->reset();
            }

            return redirect()->route('people.index');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Fast Create Person Error: ' . $e->getMessage());
            session()->flash('error', 'خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید.');
        }
    }

    public function render()
    {
        return view('livewire.people.fast-create-person');
    }
}
