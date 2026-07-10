<?php

namespace App\Services\People;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IncompleteCasesQueueCache
{
    private const VERSION_CACHE_KEY = 'people-incomplete-cases:version';

    public function currentVersion(): string
    {
        $version = Cache::get(self::VERSION_CACHE_KEY);

        if (is_string($version) && $version !== '') {
            return $version;
        }

        $version = (string) Str::uuid();

        Cache::forever(self::VERSION_CACHE_KEY, $version);

        return $version;
    }

    public function bump(): string
    {
        $version = (string) Str::uuid();

        Cache::forever(self::VERSION_CACHE_KEY, $version);

        return $version;
    }
}
