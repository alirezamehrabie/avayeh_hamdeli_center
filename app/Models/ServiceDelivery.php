<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ServiceDelivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'service_category_id',
        'social_worker_id',
        'person_id',
        'guardian_id',
        'national_id',
        'full_name',
        'mobile',
        'delivered_quantity',
        'value_per_unit_snapshot',
        'delivered_total_value',
        'delivered_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'delivered_quantity' => 'decimal:2',
            'value_per_unit_snapshot' => 'integer',
            'delivered_total_value' => 'integer',
            'delivered_at' => 'date',
            'created_by' => 'integer',
            'service_category_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $delivery): void {
            $delivery->adjustCategoryQuantity(-1 * (float) $delivery->delivered_quantity);
            $delivery->service?->refreshDeliveryProgress();
        });

        static::updated(function (self $delivery): void {
            $originalQuantity = (float) $delivery->getOriginal('delivered_quantity');
            $originalCategoryId = (int) $delivery->getOriginal('service_category_id');

            if ($delivery->wasChanged('service_category_id') && $originalCategoryId > 0) {
                $delivery->adjustCategoryQuantity($originalQuantity, $originalCategoryId);
                $delivery->adjustCategoryQuantity(-1 * (float) $delivery->delivered_quantity);
            } elseif ($delivery->wasChanged('delivered_quantity')) {
                $delivery->adjustCategoryQuantity($originalQuantity);
                $delivery->adjustCategoryQuantity(-1 * (float) $delivery->delivered_quantity);
            }

            $delivery->service?->refreshDeliveryProgress();
        });

        static::deleted(function (self $delivery): void {
            $delivery->adjustCategoryQuantity((float) $delivery->delivered_quantity);
            $delivery->service?->refreshDeliveryProgress();
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class)->withTrashed();
    }

    public function socialWorker(): BelongsTo
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRecipientNameAttribute(): string
    {
        $storedName = trim((string) ($this->full_name ?? ''));

        if ($storedName !== '') {
            return $storedName;
        }

        if ($this->person) {
            return trim(implode(' ', array_filter([$this->person->first_name, $this->person->last_name]))) ?: '-';
        }

        if ($this->guardian) {
            return trim(implode(' ', array_filter([$this->guardian->first_name, $this->guardian->last_name]))) ?: '-';
        }

        return '-';
    }

    public function getRecipientNationalIdAttribute(): string
    {
        if ($this->person) {
            return (string) ($this->person->national_id ?? '-');
        }

        if ($this->guardian) {
            return (string) ($this->guardian->national_code ?? '-');
        }

        return (string) ($this->national_id ?: '-');
    }

    public static function attachToPerson(Person $person): void
    {
        static::query()
            ->whereHas('serviceCategory.service', fn ($query) => $query->where('service_type', 'individual'))
            ->whereNull('person_id')
            ->whereNull('guardian_id')
            ->where('national_id', (string) $person->national_id)
            ->update([
                'person_id' => $person->id,
                'guardian_id' => null,
                'full_name' => trim(implode(' ', array_filter([$person->first_name, $person->last_name]))) ?: '-',
            ]);
    }

    public static function attachToGuardian(Guardian $guardian): void
    {
        static::query()
            ->whereHas('serviceCategory.service', fn ($query) => $query->where('service_type', 'family'))
            ->whereNull('person_id')
            ->whereNull('guardian_id')
            ->where('national_id', (string) $guardian->national_code)
            ->update([
                'guardian_id' => $guardian->id,
                'person_id' => null,
                'full_name' => $guardian->full_name !== '' ? $guardian->full_name : '-',
                'mobile' => $guardian->guardian_phone_number ?: null,
            ]);
    }

    protected function adjustCategoryQuantity(float $delta, ?int $categoryId = null): void
    {
        $targetCategoryId = $categoryId ?: (int) $this->service_category_id;

        if ($targetCategoryId <= 0 || $delta === 0.0) {
            return;
        }

        DB::transaction(function () use ($targetCategoryId, $delta): void {
            $category = ServiceCategory::query()->lockForUpdate()->find($targetCategoryId);

            if (! $category) {
                return;
            }

            $nextQuantity = max(0, (float) $category->quantity + $delta);
            $category->forceFill(['quantity' => $nextQuantity])->saveQuietly();
            $category->service?->refreshFinancialTotals();
        });
    }
}
