<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Residence extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'residence_status_id',
        'district_id',
        'is_local_to_city',
        'deposit_amount',
        'monthly_rent',
        'residence_duration_years',
        'address',
        'guardian_id'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function residenceStatus(): BelongsTo
    {
        return $this->belongsTo(ResidenceStatusType::class, 'residence_status_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
