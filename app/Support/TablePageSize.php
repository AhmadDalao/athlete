<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class TablePageSize
{
    public const DEFAULT = '10';

    public const OPTIONS = ['10', '25', '50', '100', 'all'];

    public static function queryValue(Request $request, string $key = 'per_page', string $default = self::DEFAULT): string
    {
        return self::normalize($request->string($key)->value(), $default);
    }

    public static function resolve(Request $request, Builder $query, string $key = 'per_page', string $default = self::DEFAULT): int
    {
        return self::resolveValue(self::queryValue($request, $key, $default), $query, $default);
    }

    public static function resolveValue(?string $value, Builder $query, string $default = self::DEFAULT): int
    {
        $value = self::normalize($value, $default);

        if ($value === 'all') {
            return max(1, (clone $query)->count());
        }

        return (int) $value;
    }

    public static function normalize(?string $value, string $default = self::DEFAULT): string
    {
        $fallback = in_array($default, self::OPTIONS, true) ? $default : self::DEFAULT;
        $value = strtolower(trim((string) $value));

        return in_array($value, self::OPTIONS, true) ? $value : $fallback;
    }
}
