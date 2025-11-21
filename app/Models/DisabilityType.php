<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisabilityType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', // نام نوع معلولیت (مثلاً نابینا، ناشنوا، جسمی و حرکتی و ...)
    ];

    /**
     * هر نوع معلولیت، می‌تواند برای چندین فرد ثبت شود
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'disability_type_id');
    }
}
