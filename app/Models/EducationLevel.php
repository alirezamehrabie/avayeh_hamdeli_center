<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    protected $table = 'education_levels';

    protected $fillable = ['name', 'sort_order'];

    /**
     * ارتباط یک-به-چند با مدل Education
     */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }
}
