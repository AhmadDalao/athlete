<?php

namespace App\Models;

use App\Support\PlatformSettingCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class PlatformSetting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, ?string $fallback = null): ?string
    {
        if (! Schema::hasTable('platform_settings')) {
            return $fallback;
        }

        try {
            return cache()->remember("platform_setting_{$key}", 60, fn () => static::query()->where('key', $key)->value('value') ?? $fallback);
        } catch (QueryException) {
            return $fallback;
        }
    }

    public static function put(string $key, ?string $value, string $group = 'general'): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        cache()->forget("platform_setting_{$key}");
        cache()->forget('platform_settings_public_map');
    }

    public static function publicMap(): array
    {
        return cache()->remember('platform_settings_public_map', 60, function (): array {
            $defaults = PlatformSettingCatalog::defaults();
            $stored = Schema::hasTable('platform_settings')
                ? static::query()->whereIn('key', array_keys($defaults))->pluck('value', 'key')->all()
                : [];

            return PlatformSettingCatalog::normalizeForForm(array_replace($defaults, $stored));
        });
    }

    public static function enabled(string $key, bool $fallback = false): bool
    {
        return filter_var(static::get($key, $fallback ? '1' : '0'), FILTER_VALIDATE_BOOL);
    }
}
