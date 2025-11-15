<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SadaatRelation extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'sadaat_relation_id');
    }
}
