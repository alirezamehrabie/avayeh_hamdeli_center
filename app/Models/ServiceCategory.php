<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use SoftDeletes;

    public const NORMALIZED_NAME_COLUMN = 'normalized_name';

    protected $fillable = [
        'service_name_id',
        'service_id',
        'code',
        'name',
        'image_path',
        'quantity',
        'unit',
        'value',
        'sort_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'value' => 'integer',
            'created_by' => 'integer',
            'service_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (\Illuminate\Support\Facades\Schema::hasColumn($category->getTable(), self::NORMALIZED_NAME_COLUMN)) {
                $category->{self::NORMALIZED_NAME_COLUMN} = static::normalizeName((string) $category->name);
            }
        });

        static::creating(function (self $category): void {
            if (blank($category->service_name_id) && $category->service?->service_name_id) {
                $category->service_name_id = $category->service->service_name_id;
            }

            if (blank($category->sort_id)) {
                $category->sort_id = static::nextSortId((int) $category->service_id);
            }

            if (blank($category->code)) {
                $category->code = static::generateNextCode((int) $category->service_id);
            }
        });

        static::saved(function (self $category): void {
            $category->service?->refreshFinancialTotals();
            $category->syncDeliveryValues();
        });

        static::deleted(function (self $category): void {
            $category->service?->refreshFinancialTotals();
            $category->service?->syncDeliveryValues();
        });

        static::forceDeleting(function (self $category): void {
            if (app()->isProduction()) {
                throw new \RuntimeException('Service categories cannot be permanently deleted in production.');
            }
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN sort_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('sort_id')
            ->orderByDesc('id');
    }

    public static function normalizeName(string $name): string
    {
        $normalized = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة'], ['ی', 'ی', 'ک', 'ه', 'ه'], $name);
        $normalized = str_replace(["\u{200C}", "\u{200D}", "\u{FEFF}", "\u{00A0}"], ' ', $normalized);

        return preg_replace('/[\p{Z}\s]+/u', ' ', trim($normalized)) ?? '';
    }

    public static function generateNextCode(int $serviceId): string
    {
        $service = Service::query()->find($serviceId);

        if (! $service) {
            return 'SN-00000-CAT001';
        }

        $nextIndex = (int) static::query()
            ->where('service_id', $serviceId)
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(code, "CAT", -1) AS UNSIGNED)) as sequence')
            ->value('sequence');

        if ($nextIndex <= 0) {
            $nextIndex = (int) static::query()
                ->where('service_id', $serviceId)
                ->count();
        }

        return $service->code.'-CAT'.str_pad((string) ($nextIndex + 1), 3, '0', STR_PAD_LEFT);
    }

    public static function nextSortId(int $serviceId): int
    {
        $nextSortId = (int) static::query()
            ->where('service_id', $serviceId)
            ->max('sort_id');

        return $nextSortId > 0 ? $nextSortId + 1 : 1;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ServiceDelivery::class);
    }

    public function getUnitLabelAttribute(): string
    {
        $unitKey = $this->attributes['unit'] ?? '';

        if (! $unitKey) {
            return '';
        }

        $unitOptions = Service::unitOptions();

        return $unitOptions[$unitKey] ?? (string) $unitKey;
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->quantity;
    }

    public function recalculateServiceTotals(): void
    {
        $this->service?->refreshFinancialTotals();
    }

    public function syncDeliveryValues(): void
    {
        $valuePerUnit = (int) ($this->value ?? 0);

        $this->deliveries()
            ->get()
            ->each(function (ServiceDelivery $delivery) use ($valuePerUnit): void {
                $delivery->forceFill([
                    'value_per_unit_snapshot' => $valuePerUnit,
                    'delivered_total_value' => (int) round((float) $delivery->delivered_quantity * $valuePerUnit),
                ])->saveQuietly();
            });
    }
}
