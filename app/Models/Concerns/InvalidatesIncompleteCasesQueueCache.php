<?php

namespace App\Models\Concerns;

use App\Services\People\IncompleteCasesQueueCache;
use Illuminate\Database\Eloquent\SoftDeletes;

trait InvalidatesIncompleteCasesQueueCache
{
    public static function bootInvalidatesIncompleteCasesQueueCache(): void
    {
        $invalidate = static function (): void {
            app(IncompleteCasesQueueCache::class)->bump();
        };

        static::saved($invalidate);
        static::deleted($invalidate);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored($invalidate);
        }
    }
}
