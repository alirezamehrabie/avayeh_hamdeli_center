<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicLevel extends Model
{
    protected $fillable = ['title', 'sort_order'];

    // رابطه با مددکاران
    public function socialWorkers(): HasMany
    {
        return $this->hasMany(SocialWorker::class, 'academic_level_id');
    }

}
