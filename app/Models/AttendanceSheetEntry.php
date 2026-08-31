<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSheetEntry extends Model
{
    public const METHOD_OPTIONS = [
        'qr' => 'اسکن QR',
        'manual' => 'ثبت دستی',
    ];

    protected $fillable = [
        'attendance_sheet_id',
        'person_id',
        'qr_identity_id',
        'person_name',
        'person_code',
        'national_id',
        'checked_in_at',
        'check_in_method',
        'checked_in_by',
        'checked_out_at',
        'check_out_method',
        'checked_out_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_sheet_id' => 'integer',
            'person_id' => 'integer',
            'qr_identity_id' => 'integer',
            'checked_in_at' => 'datetime',
            'checked_in_by' => 'integer',
            'checked_out_at' => 'datetime',
            'checked_out_by' => 'integer',
        ];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function qrIdentity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class);
    }

    public function checkInRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkOutRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }
}
