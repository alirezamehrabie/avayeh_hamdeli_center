<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HarmType extends Model
{
    protected $fillable = ['title'];

    public function people()
    {
        return $this->belongsToMany(Person::class, 'harm_type_person');
    }
}
