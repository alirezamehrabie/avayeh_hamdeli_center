<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrIdentityScanLog extends Model
{
    protected $fillable = [
        'qr_identity_id',
        'subject_type',
        'subject_id',
        'user_id',
        'outcome',
        'context',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'qr_identity_id' => 'integer',
            'subject_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function qrIdentity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
