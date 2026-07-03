<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryCaseRecord extends Model
{
    public const TYPE_NOTE = 'note';

    public const TYPE_SERVICE = 'service';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_FOLLOW_UP = 'follow_up';

    public const TYPE_OPTIONS = [
        self::TYPE_NOTE => 'یادداشت پرونده',
        self::TYPE_SERVICE => 'خدمت خارج از چرخه',
        self::TYPE_INVOICE => 'فاکتور/هزینه',
        self::TYPE_FOLLOW_UP => 'پیگیری',
    ];

    protected $fillable = [
        'person_id',
        'created_by',
        'record_type',
        'title',
        'description',
        'recorded_at',
        'amount',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'person_id' => 'integer',
            'created_by' => 'integer',
            'recorded_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRecordTypeLabelAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->record_type] ?? $this->record_type;
    }
}
