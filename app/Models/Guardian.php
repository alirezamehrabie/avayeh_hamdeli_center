<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'children_count', 'children_in_house', 'any_family_employed', 'birth_date',
        'occupation_id', 'job_type_id', 'insurance_status', 'other_insurance', 'divorced_child_at_home',
        'average_income', 'has_vehicle', 'vehicle_type', 'guardian_phone_number'
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

    // ارتباط به Person
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
