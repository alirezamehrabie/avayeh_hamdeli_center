<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',

        // فیلدهای جدید تاریخ تولد شمسی
        'guardian_birth_day',
        'guardian_birth_month',
        'guardian_birth_year',
        'guardian_birth_date_full',


        'occupation_id',
        'job_type_id',
        'children_count',
        'children_in_house',
        'guardian_phone_number',
        'any_family_employed',
        'birth_date',
        'insurance_status',
        'insurance_type_id',
        'divorced_child_at_home',
        'average_income',
        'has_vehicle',
        'vehicle_type_id',

    ];

    protected $casts = [
        'guardian_birth_day' => 'integer',
        'guardian_birth_month' => 'integer',
        'guardian_birth_year' => 'integer',
        'children_count' => 'integer',
        'children_in_house' => 'integer',
        'insurance_status' => 'boolean',
        'any_family_employed' => 'boolean',
        'has_vehicle' => 'boolean',
        'average_income' => 'integer',
    ];

    /**
     * ارتباط به جدول Occupations
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function insuranceType()
    {
        return $this->belongsTo(InsuranceType::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }
    // ارتباط به Person
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
