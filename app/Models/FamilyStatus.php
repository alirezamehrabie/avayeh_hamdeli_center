<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'guardian_relation_type_id',
        'economic_decile',
        'remarried_parent',
        'children_from_previous_marriage',
        'has_parent_disability',
        'parent_disability_description',
    ];

    protected $casts = [
        'economic_decile' => 'integer',
        'has_parent_disability' => 'boolean',
    ];

    // تعریف رابطه با جدول Lookup
    public function guardianRelationType()
    {
        return $this->belongsTo(GuardianRelationType::class);
    }
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
