<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Occupation extends Model
{
    protected $fillable = ['name', 'sort_order'];

    /**
     * ارتباط یک به چند با جدول Guardians
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }
}
