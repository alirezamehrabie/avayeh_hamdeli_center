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
        'economic_decile',
        'living_parents',
        'deceased_parent',
        'death_year',
        'death_reason',
        'divorced_parent',
        'remarried_parent',
        'children_from_previous_marriage',
        'has_parent_disability',
        'parent_disability_description',
    ];

    protected $casts = [
        'economic_decile' => 'integer',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
