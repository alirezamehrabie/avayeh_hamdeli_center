<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'landline_phone',
        'trusted_person_phone',
        'messenger_type',
        'messenger_number',
        'guardian_id'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
