<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeedsLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'need_level', 'evaluation_date', 'reviewer_name'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
