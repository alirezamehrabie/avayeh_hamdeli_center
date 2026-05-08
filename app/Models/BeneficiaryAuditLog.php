<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiaryAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'user_id',
        'action',
        'changed_fields',
        'before_values',
        'after_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'before_values' => 'array',
        'after_values' => 'array',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
