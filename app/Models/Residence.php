<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Residence extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'residence_status', 'is_local_to_city', 'deposit_amount', 'monthly_rent',
        'residence_duration_years', 'address', 'district'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
