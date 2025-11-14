<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'has_own_account', 'account_holder_relation', 'card_number', 'sheba_number',
        'subsidy_card_number', 'subsidy_sheba_number', 'bank_name'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
