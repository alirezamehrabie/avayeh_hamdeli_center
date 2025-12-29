<?php

namespace App\Livewire\SocialWorkers;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SocialWorker;
use App\Models\District;
use App\Models\Occupation;
use App\Models\AcademicLevel;
use App\Traits\SocialWorkerFormTrait;
use Illuminate\Support\Facades\DB;


class EditSocialWorker extends Component
{
    use WithFileUploads , SocialWorkerFormTrait;
    public SocialWorker $socialWorker;

    public function mount(SocialWorker $socialWorker)
    {
        $this->socialWorker = $socialWorker;
        $this->fill($socialWorker->toArray());

        // تبدیل تاریخ‌ها به فیلدهای جداگانه
        if ($socialWorker->birth_date_full) {
            [$this->birth_year, $this->birth_month, $this->birth_day] = explode('/', $socialWorker->birth_date_full);
        }
        if ($socialWorker->start_date_full) {
            [$this->start_year, $this->start_month, $this->start_day] = explode('/', $socialWorker->start_date_full);
        }
    }

    public function update()
    {
        $this->validate($this->getValidationRules($this->socialWorker->id));

        DB::beginTransaction();
        try {
            $photoPath = $this->photo ? $this->uploadPhoto() : $this->socialWorker->photo_path;

            $this->socialWorker->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'full_name' => trim("{$this->first_name} {$this->last_name}"),
                'id_number' => $this->id_number,
                'mobile' => $this->mobile,
                'photo_path' => $photoPath,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'birth_date_full' => $this->getBirthDateFull(),
                'start_day' => $this->start_day,
                'start_month' => $this->start_month,
                'start_year' => $this->start_year,
                'start_date_full' => $this->getStartDateFull(),
                'district_id' => $this->district_id,
                'occupation_id' => $this->occupation_id,
                'academic_level_id' => $this->academic_level_id,
                'family_members_count' => $this->family_members_count,
                'covered_people_count' => $this->covered_people_count,
                'covered_households_count' => $this->covered_households_count,
                'covered_children_count' => $this->covered_children_count,
                'substitute_first_name' => $this->substitute_first_name,
                'substitute_last_name' => $this->substitute_last_name,
                'substitute_mobile' => $this->substitute_mobile,
            ]);

            DB::commit();
            session()->flash('success', 'اطلاعات مددکار با موفقیت به‌روزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'خطا: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.social-workers.edit-social-worker', [
            'academicLevels' => AcademicLevel::orderBy('sort_order')->get(),
            'allDistricts' => District::orderBy('sort_order')->get(),
            'allOccupations' => Occupation::all(),
        ])->extends('layouts.app')->section('content');
    }
}
