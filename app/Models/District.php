<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['name', 'sort_order'];

    /**
     * ارتباط یک-به-چند با Residence
     */
    public function residences(): HasMany
    {
        return $this->hasMany(Residence::class);
    }
}
