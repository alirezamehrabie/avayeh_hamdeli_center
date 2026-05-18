<?php

namespace App\Livewire\SocialWorkers;

use App\Traits\SocialWorkerFormTrait;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SocialWorker;
use App\Models\District;
use App\Models\Occupation;
use App\Models\AcademicLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
#[AllowDynamicProperties]
#[Layout('layouts.app')]
class CreateSocialWorker extends Component
{
    use WithFileUploads, SocialWorkerFormTrait;

    public bool $embedded = false;

    public function save()
    {
        $this->validate(array_merge($this->getValidationRules(), [
            'account_username' => ['required', 'string', 'max:100', 'unique:users,name'],
            'account_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]));

        try {
            DB::beginTransaction();
            $birthDateFull = $this->getBirthDateFull();
            $startDateFull = $this->getStartDateFull();
            $photoPath = $this->uploadPhoto();

            // ۳. ترکیب تاریخ‌ها برای ستون‌های Full
            $birthDateFull = $this->birth_year ? "{$this->birth_year}/{$this->birth_month}/{$this->birth_day}" : null;
            $startDateFull = $this->start_year ? "{$this->start_year}/{$this->start_month}/{$this->start_day}" : null;

            // ۴. ذخیره در دیتابیس
            $socialWorker = SocialWorker::create([
                'worker_code' => SocialWorker::generateNextWorkerCode(),
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_id' => $this->national_id,
                'id_number' => $this->id_number,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'birth_date_full' => $birthDateFull,
                'district_id' => $this->district_id,
                'occupation_id' => $this->occupation_id,
                'academic_level_id' => $this->academic_level_id,
                'mobile' => $this->mobile,
                'family_members_count' => $this->family_members_count,
                'photo_path' => $photoPath,
                'start_day' => $this->start_day,
                'start_month' => $this->start_month,
                'start_year' => $this->start_year,
                'start_date_full' => $startDateFull,
                'covered_people_count' => $this->covered_people_count,
                'covered_households_count' => $this->covered_households_count,
                'covered_children_count' => $this->covered_children_count,
                'substitute_first_name' => $this->substitute_first_name,
                'substitute_last_name' => $this->substitute_last_name,
                'substitute_mobile' => $this->substitute_mobile,
            ]);

            $username = mb_strtolower(trim((string) $this->account_username));

            User::create([
                'name' => $username,
                'first_name' => trim((string) $this->first_name),
                'last_name' => trim((string) $this->last_name),
                'email' => $username . '@local.system',
                'password' => $this->account_password,
                'access_level' => User::ACCESS_LEVEL_REGULAR,
                'is_admin' => false,
                'permissions' => [],
                'social_worker_id' => $socialWorker->id,
            ]);

            DB::commit();
            session()->flash('success', 'مددکار با موفقیت ثبت شد.');
            if ($this->embedded) {
                $this->dispatch('open-dashboard-section', section: 'social-workers-list');
                return;
            }

            $this->reset();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'خطا: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.social-workers.create-social-worker', [
            'academicLevels' => AcademicLevel::orderBy('sort_order')->get(),
            'allDistricts' => District::orderBy('sort_order')->get(),
            'allOccupations' => Occupation::all(),
        ]);
    }
}
