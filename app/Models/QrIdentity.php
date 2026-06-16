<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrIdentity extends Model
{
    public const SUBJECT_PERSON = 'person';
    public const SUBJECT_GUARDIAN = 'guardian';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_REPLACED = 'replaced';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'token_hash',
        'token_encrypted',
        'public_code',
        'status',
        'issued_at',
        'revoked_at',
        'last_scanned_at',
        'created_by',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'token_encrypted' => 'encrypted',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_scanned_at' => 'datetime',
            'created_by' => 'integer',
            'revoked_by' => 'integer',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'subject_type', 'subject_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scans()
    {
        return $this->hasMany(QrIdentityScanLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getScanUrlAttribute(): ?string
    {
        $token = $this->token_encrypted;

        return $token ? route('qr-identities.resolve', ['token' => $token]) : null;
    }

    public function getQrSvgAttribute(): ?string
    {
        $scanUrl = $this->scan_url;

        if (! $scanUrl) {
            return null;
        }

        return (string) QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($scanUrl);
    }
}
