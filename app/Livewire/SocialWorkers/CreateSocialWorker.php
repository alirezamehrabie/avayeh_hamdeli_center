<?php

namespace App\Livewire\SocialWorkers;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\SocialWorker;
use App\Models\District;
use App\Models\Occupation;
use Illuminate\Support\Facades\DB;

class CreateSocialWorker extends Component
{
    use WithFileUploads;

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
    public $photo; // برای آپلود عکس

    // --- فیلدهای حرفه‌ای ---
    public $start_day;
    public $start_month;
    public $start_year;
    public $covered_area;

    // --- فیلدهای آماری (پیش‌فرض 0) ---
    public $covered_people_count = 0;
    public $covered_households_count = 0;
    public $covered_children_count = 0;

    // --- فیلدهای همکار علی‌البدل ---
    public $substitute_first_name;
    public $substitute_last_name;
    public $substitute_mobile;


    public $district_id;
    public $allDistricts;

    public $occupation_id;    // برای ذخیره انتخاب کاربر
    public $allOccupations;   // برای نمایش در لیست‌باکس


    public function mount()
    {
        // ۲. بارگذاری مناطق مشابه فرم مددجویان
        $this->allDistricts = District::orderBy('sort_order')->get();
        $this->allOccupations = Occupation::all();
    }

    /**
     * قوانین اعتبارسنجی
     */
    protected function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => 'required|string|digits:10|unique:social_workers,national_id',
            'mobile' => 'required|string|max:11',
            'birth_year' => 'nullable|integer|between:1300,1400',
            'start_year' => 'nullable|integer|between:1370,1450',
            'district_id' => 'nullable|exists:districts,id',
            'occupation_id' => 'nullable|exists:occupations,id',
            'photo' => 'nullable|image|max:1024', // حداکثر 1 مگابایت

        ];
    }

    public function save()
    {
        $this->validate();

        try {
            DB::beginTransaction();
            $nextWorkerCode = SocialWorker::generateNextWorkerCode();

            $fullName = trim($this->first_name . ' ' . $this->last_name);

            // ۱. مدیریت آپلود عکس
            $photoPath = $this->photo ? $this->photo->store('social_workers_photos', 'public') : null;

            // ۲. تولید خودکار کد مددکاری (موقت - در مراحل بعد پیشرفته‌تر می‌شود)
            $lastWorker = SocialWorker::orderBy('worker_code', 'desc')->first();
            $nextCode = $lastWorker ? ($lastWorker->worker_code + 1) : 10;

            // ۳. ترکیب تاریخ‌ها برای ستون‌های Full
            $birthDateFull = $this->birth_year ? "{$this->birth_year}/{$this->birth_month}/{$this->birth_day}" : null;
            $startDateFull = $this->start_year ? "{$this->start_year}/{$this->start_month}/{$this->start_day}" : null;

            // ۴. ذخیره در دیتابیس
            SocialWorker::create([
                'worker_code' => $nextWorkerCode,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'full_name'   => $fullName, // ذخیره مستقیم در دیتابیس
                'national_id' => $this->national_id,
                'id_number' => $this->id_number,
                'birth_day' => $this->birth_day,
                'birth_month' => $this->birth_month,
                'birth_year' => $this->birth_year,
                'birth_date_full' => $birthDateFull,
                'district_id' => $this->district_id,
                'occupation_id' => $this->occupation_id,
                'mobile' => $this->mobile,
                'education' => $this->education,
                'occupation' => $this->occupation,
                'family_members_count' => $this->family_members_count,
                'photo_path' => $photoPath,
                'start_day' => $this->start_day,
                'start_month' => $this->start_month,
                'start_year' => $this->start_year,
                'start_date_full' => $startDateFull,
                'covered_area' => $this->covered_area,
                'covered_people_count' => $this->covered_people_count,
                'covered_households_count' => $this->covered_households_count,
                'covered_children_count' => $this->covered_children_count,
                'substitute_first_name' => $this->substitute_first_name,
                'substitute_last_name' => $this->substitute_last_name,
                'substitute_mobile' => $this->substitute_mobile,
            ]);

            DB::commit();

            session()->flash('success', 'اطلاعات مددکار با موفقیت در سیستم ثبت شد.');
            // ریست کردن تمام فیلدها برای ثبت مجدد (بدون ریدایرکت)
            // ریست کردن تمام فیلدها برای ثبت مجدد (بدون ریدایرکت)
            $this->reset([
                'first_name', 'last_name', 'national_id', 'id_number',
                'birth_day', 'birth_month', 'birth_year', 'mobile',
                'education', 'occupation', 'family_members_count', 'photo',
                'start_day', 'start_month', 'start_year', 'covered_area',
                'covered_people_count', 'covered_households_count', 'covered_children_count',
                'substitute_first_name', 'substitute_last_name', 'substitute_mobile'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'خطایی رخ داد: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.social-workers.create-social-worker')
            ->extends('layouts.app')
            ->section('content');
    }
}
