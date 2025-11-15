<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidenceStatusType extends Model
{
    protected $table = 'residence_status_types';

    protected $fillable = ['name', 'sort_order'];

    public function residences(): HasMany
    {
        return $this->hasMany(Residence::class, 'residence_status_id');
    }
}
