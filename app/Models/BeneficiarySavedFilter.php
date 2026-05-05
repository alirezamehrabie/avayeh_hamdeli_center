<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiarySavedFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'global_search',
        'visible_columns',
    ];

    protected $casts = [
        'filters' => 'array',
        'visible_columns' => 'array',
    ];
}
