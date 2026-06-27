<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ServiceDelivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'service_category_id',
        'gate_entry_assignment_id',
        'activity_attendance_id',
        'delivery_channel',
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
            'gate_entry_assignment_id' => 'integer',
            'activity_attendance_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $delivery): void {
            $delivery->assertServiceCategoryBelongsToService();
        });

        static::created(function (self $delivery): void {
            $delivery->service?->refreshDeliveryProgress();
        });

        static::updated(function (self $delivery): void {
            $delivery->service?->refreshDeliveryProgress();
        });

        static::deleted(function (self $delivery): void {
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

    public function activityAttendance(): BelongsTo
    {
        return $this->belongsTo(ActivityAttendance::class);
    }

    public function gateEntryAssignment(): BelongsTo
    {
        return $this->belongsTo(GateEntryAssignment::class);
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


    protected function assertServiceCategoryBelongsToService(): void
    {
        $serviceId = (int) $this->service_id;
        $categoryId = (int) $this->service_category_id;

        if ($serviceId <= 0 || $categoryId <= 0) {
            throw ValidationException::withMessages([
                'service_category_id' => 'انتخاب دسته‌بندی خدمت الزامی است.',
            ]);
        }

        $categoryBelongsToService = ServiceCategory::query()
            ->whereKey($categoryId)
            ->where('service_id', $serviceId)
            ->exists();

        if (! $categoryBelongsToService) {
            throw ValidationException::withMessages([
                'service_category_id' => 'دسته‌بندی انتخاب‌شده متعلق به این خدمت نیست.',
            ]);
        }
    }
}
