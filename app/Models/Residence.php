<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Residence extends Model
{
    use HasFactory;

    public const MONEY_SCALE = 100;

    protected $fillable = [
        'person_id',
        'residence_status_id',
        'district_id',
        'is_local_to_city',
        'deposit_amount',
        'monthly_rent',
        'residence_duration_years',
        'address',
        'guardian_id'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function residenceStatus(): BelongsTo
    {
        return $this->belongsTo(ResidenceStatusType::class, 'residence_status_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    protected function depositAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->fromStoredMoney($value),
            set: fn ($value) => $this->toStoredMoney($value),
        );
    }

    protected function monthlyRent(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->fromStoredMoney($value),
            set: fn ($value) => $this->toStoredMoney($value),
        );
    }

    private function toStoredMoney(mixed $value): ?int
    {
        return self::toStoredMoneyAmount($value);
    }

    private function fromStoredMoney(mixed $value): ?int
    {
        return self::fromStoredMoneyAmount($value);
    }

    public static function toStoredMoneyAmount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return intdiv((int) $value, self::MONEY_SCALE);
    }

    public static function fromStoredMoneyAmount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value * self::MONEY_SCALE;
    }
}
