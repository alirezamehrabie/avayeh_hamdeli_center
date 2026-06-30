<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    public const STATUS_OPTIONS = [
        'draft' => 'پیش نویس',
        'ongoing' => 'آماده برگزاری',
        'closed' => 'بسته شده',
        'cancelled' => 'لغو شده',
    ];

    public const TYPE_OPTIONS = [
        'celebration' => 'جشن',
        'camp' => 'اردو',
        'workshop' => 'کارگاه',
        'ceremony' => 'مراسم',
        'group_activity' => 'فعالیت گروهی',
        'educational_class' => 'کلاس آموزشی',
        'elite_program_boys' => 'طرح نخبگان (پسران)',
        'elite_program_girls' => 'طرح نخبگان (دختران)',
    ];

    protected $fillable = [
        'code',
        'name',
        'activity_type',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'capacity',
        'status',
        'status_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'created_by' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            if (blank($activity->code)) {
                $activity->code = static::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $lastSequence = (int) static::withTrashed()
            ->where('code', 'like', 'ACT-%')
            ->selectRaw(static::codeSequenceExpression().' as sequence')
            ->value('sequence');

        if ($lastSequence <= 0) {
            $lastSequence = (int) static::withTrashed()
                ->selectRaw('MAX(id) as sequence')
                ->value('sequence');
        }

        return 'ACT-'.str_pad((string) ($lastSequence + 1), 5, '0', STR_PAD_LEFT);
    }

    protected static function codeSequenceExpression(): string
    {
        return match (static::query()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => 'MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED))',
            'pgsql' => 'MAX(CAST(SUBSTRING(code FROM 5) AS INTEGER))',
            'sqlsrv' => 'MAX(CAST(SUBSTRING(code, 5, 8000) AS INTEGER))',
            default => 'MAX(CAST(SUBSTR(code, 5) AS INTEGER))',
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'activity_attendances')
            ->withPivot([
                'qr_identity_id',
                'status',
                'registration_method',
                'checked_in_at',
                'checked_out_at',
                'notes',
                'recorded_by',
            ])
            ->withTimestamps();
    }
}
