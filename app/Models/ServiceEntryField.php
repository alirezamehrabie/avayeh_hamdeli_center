<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEntryField extends Model
{
    public const TYPE_NUMBER = 'number';

    public const TYPE_TEXT = 'text';

    public const TYPE_EDUCATION_LEVEL = 'education_level';

    public const TYPE_OPTIONS = [
        self::TYPE_NUMBER => 'عدد',
        self::TYPE_TEXT => 'متن',
        self::TYPE_EDUCATION_LEVEL => 'مقطع تحصیلی',
    ];

    protected $fillable = [
        'service_id',
        'title',
        'type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }
}
