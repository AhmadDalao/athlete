<?php

namespace App\Models;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class DeviceConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'public_id',
        'provider',
        'status',
        'auth_type',
        'external_user_id',
        'ingest_key',
        'ingest_key_last_four',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'granted_scopes',
        'provider_account_payload',
        'last_synced_at',
    ];

    protected $hidden = [
        'ingest_key',
        'access_token',
        'refresh_token',
        'provider_account_payload',
    ];

    protected function casts(): array
    {
        return [
            'provider' => DeviceProvider::class,
            'status' => DeviceConnectionStatus::class,
            'ingest_key' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'granted_scopes' => 'array',
            'provider_account_payload' => 'array',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $connection): void {
            if (! $connection->public_id) {
                $connection->public_id = (string) Str::uuid();
            }

            if (! $connection->ingest_key && $connection->auth_type !== 'oauth') {
                $connection->regenerateIngestKey();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function metricIngests(): HasMany
    {
        return $this->hasMany(DeviceMetricIngest::class);
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(MetricSnapshot::class);
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(MetricSnapshot::class)->latestOfMany('metric_date');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function regenerateIngestKey(?string $plainKey = null): string
    {
        $plainKey ??= 'thl_'.Str::lower(Str::random(32));

        $this->ingest_key = $plainKey;
        $this->ingest_key_last_four = substr($plainKey, -4);

        return $plainKey;
    }

    public function matchesIngestKey(?string $plainKey): bool
    {
        return is_string($plainKey) && $plainKey !== '' && hash_equals((string) $this->ingest_key, $plainKey);
    }

    public function usesOauth(): bool
    {
        return $this->auth_type === 'oauth';
    }

    public function tokenExpiresSoon(int $bufferSeconds = 300): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->lte(now()->addSeconds($bufferSeconds));
    }
}
