<?php

namespace App\Models;

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
    }

    public static function publicMap(): array
    {
        return [
            'app_name' => static::get('app_name', 'Throughline'),
            'tagline' => static::get('tagline', 'Coach performance OS'),
            'support_email' => static::get('support_email', 'admin@throughline.test'),
            'invite_expiry_days' => static::get('invite_expiry_days', '7'),
        ];
    }
}
