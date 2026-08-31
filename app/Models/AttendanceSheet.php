<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSheet extends Model
{
    /**
     * Scan mode is a per-session UI choice on the attendance screen, not sheet
     * state: the same sheet is switched between ورود and خروج as needed.
     */
    public const MODE_IN = 'in';

    public const MODE_OUT = 'out';

    public const MODE_OPTIONS = [
        self::MODE_IN => 'ورود',
        self::MODE_OUT => 'خروج',
    ];

    protected $fillable = [
        'social_worker_id',
        'name',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'social_worker_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function socialWorker(): BelongsTo
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AttendanceSheetEntry::class);
    }
}
