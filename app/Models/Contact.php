<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'personal_phone', 'landline_phone', 'trusted_person_phone',
        'messenger_type', 'messenger_number'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
