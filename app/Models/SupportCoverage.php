<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'organization_type', 'organization_name', 'support_card_image',
        'coverage_start_date', 'active_status'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
