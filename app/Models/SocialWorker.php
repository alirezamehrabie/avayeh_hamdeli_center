<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'national_id',
        'role',
    ];

    /**
     * Accessor for full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function people()
    {
        return $this->hasMany(Person::class);
    }
}
