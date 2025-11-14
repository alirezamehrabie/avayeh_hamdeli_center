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
        'occupation', 'job_type', 'insurance_status', 'other_insurance', 'divorced_child_at_home',
        'average_income', 'has_vehicle', 'vehicle_type', 'guardian_phone_number'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
