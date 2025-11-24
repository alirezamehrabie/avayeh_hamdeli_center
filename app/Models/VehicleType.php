<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{

    protected $fillable = [
        'name',
    ];


    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }
}
