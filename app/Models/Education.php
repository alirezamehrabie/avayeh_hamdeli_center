<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Education extends Model
{
    protected $table = 'educations';

    protected $fillable = [
        'person_id',
        'is_studying',
        'school_name',
        'major',
        'education_level',
        'drop_reason',
        'works_alongside_study',
        'monthly_income',
        'talent_description',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
