<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityAttendance extends Model
{
    public const STATUS_OPTIONS = [
        'present' => 'حاضر',
        'late' => 'با تأخیر',
        'absent' => 'غایب',
        'excused' => 'موجه',
    ];

    public const METHOD_OPTIONS = [
        'qr' => 'اسکن QR',
        'manual' => 'ثبت دستی',
    ];

    protected $fillable = [
        'activity_id',
        'person_id',
        'qr_identity_id',
        'status',
        'registration_method',
        'checked_in_at',
        'checked_out_at',
        'check_out_method',
        'checked_out_by',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'person_id' => 'integer',
            'qr_identity_id' => 'integer',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'checked_out_by' => 'integer',
            'recorded_by' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function qrIdentity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function checkOutRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function serviceDeliveries(): HasMany
    {
        return $this->hasMany(ServiceDelivery::class);
    }
}
