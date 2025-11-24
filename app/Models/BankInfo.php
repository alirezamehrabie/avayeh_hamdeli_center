<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'bank_id',
        'has_own_account',
        'account_owner_relation_id',
        'other_account_owner_relation',
        'bank_name',
        'card_number',
        'sheba_number',
        'subsidy_card_number',
        'subsidy_sheba_number',
    ];


    public function accountOwnerRelation()
    {
        return $this->belongsTo(AccountOwnerRelation::class);
    }
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }


    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }


}


