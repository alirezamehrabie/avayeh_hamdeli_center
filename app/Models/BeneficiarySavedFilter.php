<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiarySavedFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'criteria_version',
        'name',
        'filters',
        'global_search',
        'visible_columns',
    ];

    protected $casts = [
        'criteria_version' => 'integer',
        'filters' => 'array',
        'visible_columns' => 'array',
    ];
}
