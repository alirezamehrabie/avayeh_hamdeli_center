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
