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
        'education_level_id',
        'drop_reason',
        'works_alongside_study',
        'monthly_income',
    ];

    protected $casts = [
        'is_studying' => 'boolean',
        'works_alongside_study' => 'boolean',
        'monthly_income' => 'integer',
    ];

    /**
     * رابطه با Person
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * رابطه با سطح تحصیلات
     */
    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }
}
