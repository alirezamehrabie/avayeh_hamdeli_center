<?php

namespace App\Livewire\SocialWorkers;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SocialWorker;
use App\Models\District;
use App\Models\Occupation;
use App\Models\AcademicLevel;
use App\Models\User;
use App\Traits\SocialWorkerFormTrait;
use Illuminate\Support\Facades\DB;

#[AllowDynamicProperties]
#[Layout('layouts.app')]
class EditSocialWorker extends Component
{
    use WithFileUploads , SocialWorkerFormTrait;
    public SocialWorker $socialWorker;
    public bool $embedded = false;

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

        $this->existingPhoto = $socialWorker->photo_path;
        $this->account_username = (string) optional($socialWorker->user)->name;
    }

    public function update()
    {
        $this->validate(array_merge($this->getValidationRules($this->socialWorker->id), [
            'account_username' => ['required', 'string', 'max:100', 'unique:users,name,' . optional($this->socialWorker->user)->id],
            'account_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]));

        DB::beginTransaction();
        try {
            $photoPath = $this->photo ? $this->uploadPhoto() : $this->socialWorker->photo_path;

            $this->socialWorker->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_id' => $this->national_id,
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

            $username = mb_strtolower(trim((string) $this->account_username));
            $userPayload = [
                'name' => $username,
                'first_name' => trim((string) $this->first_name),
                'last_name' => trim((string) $this->last_name),
                'email' => $username . '@local.system',
                'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
                'is_admin' => false,
                'permissions' => [],
                'social_worker_id' => $this->socialWorker->id,
            ];

            if (filled($this->account_password)) {
                $userPayload['password'] = $this->account_password;
            }

            $this->socialWorker->user()->updateOrCreate(
                ['social_worker_id' => $this->socialWorker->id],
                $userPayload
            );

            DB::commit();
            session()->flash('success', 'اطلاعات مددکار با موفقیت به‌روزرسانی شد.');
            if ($this->embedded) {
                $this->dispatch('open-dashboard-section', section: 'social-workers-list');
            }
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
        ]);
    }
}
