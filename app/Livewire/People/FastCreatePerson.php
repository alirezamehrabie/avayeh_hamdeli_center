<?php

namespace App\Livewire\People;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class FastCreatePerson extends Component
{
    // Required fields for fast registration
    public $first_name;
    public $last_name;
    public $national_id;
    public $birth_day;
    public $birth_month;
    public $birth_year;
    public $gender;

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => 'required|string|size:10|unique:people,national_id',
            'birth_day' => 'required|integer|min:1|max:31',
            'birth_month' => 'required|integer|min:1|max:12',
            'birth_year' => 'required|integer|min:1300|max:1420',
            'gender' => 'required|in:male,female',
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
        ];
    }

    public function save()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $person = Person::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_id' => $this->national_id,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'gender' => $this->gender,
                'person_code' => Person::generateUniqueCode(),
            ]);

            DB::commit();

            session()->flash('success', 'اطلاعات فرد به صورت سریع ثبت شد. اکنون می‌توانید اطلاعات کامل این فرد را ویرایش کنید.');
            
            // Reset form
            $this->reset();

            return redirect()->route('people.fast-create');
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
