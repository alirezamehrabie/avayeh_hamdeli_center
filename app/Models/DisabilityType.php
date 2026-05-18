<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Person;

class DisabilityType extends Model
{
    use HasFactory;

    protected $fillable = ['id','name'];

    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'disability_type_id');
    }
}
