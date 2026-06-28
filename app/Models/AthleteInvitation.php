<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AthleteInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'coach_id',
        'invited_by_user_id',
        'accepted_user_id',
        'name',
        'email',
        'phone',
        'goal',
        'notes',
        'token_hash',
        'status',
        'expires_at',
        'accepted_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return array{plain:string,hash:string}
     */
    public static function tokenPair(): array
    {
        $plain = Str::random(48);

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain),
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->where(function (Builder $nested): void {
                $nested->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_PENDING
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function refreshToken(int $expiryDays): string
    {
        $token = self::tokenPair();

        $this->forceFill([
            'token_hash' => $token['hash'],
            'status' => self::STATUS_PENDING,
            'expires_at' => now()->addDays(max(1, $expiryDays)),
            'cancelled_at' => null,
        ])->save();

        return $token['plain'];
    }
}
