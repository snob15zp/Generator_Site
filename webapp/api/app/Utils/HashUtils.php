<?php

namespace App\Utils;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class HashUtils
{
    public static function encode($data): string
    {
        return Hashids::encode($data);
    }

    public static function decode($hash)
    {
        return Hashids::decode($hash)[0];
    }

    public static function decodeHashes(array $hashes): Collection
    {
        return collect($hashes)->map(function ($hash) {
            return self::decode($hash);
        });
    }
}
