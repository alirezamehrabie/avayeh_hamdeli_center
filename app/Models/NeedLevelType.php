<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NeedLevelType extends Model
{
    protected $table = 'need_level_types';

    protected $fillable = [
        'code',
        'title',
        'severity_order',
    ];

    /**
     * ارتباط با جدول نیازمندی‌ها (NeedsLevel)
     */
    public function needsLevels(): HasMany
    {
        return $this->hasMany(NeedsLevel::class, 'need_level_id');
    }
}
