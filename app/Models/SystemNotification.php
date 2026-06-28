<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id',
        'target_type',
        'target_role',
        'target_user_id',
        'title',
        'body',
        'action_label',
        'action_url',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(SystemNotificationRead::class);
    }

    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $builder): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roles = $user->roleNames();

        return $query
            ->currentlyVisible()
            ->where(function (Builder $builder) use ($roles, $user): void {
                $builder
                    ->where('target_type', 'all')
                    ->orWhere(function (Builder $roleQuery) use ($roles): void {
                        $roleQuery
                            ->where('target_type', 'role')
                            ->whereIn('target_role', $roles);
                    })
                    ->orWhere(function (Builder $userQuery) use ($user): void {
                        $userQuery
                            ->where('target_type', 'user')
                            ->where('target_user_id', $user->id);
                    });
            });
    }

    public function readBy(User $user): bool
    {
        return $this->reads->contains('user_id', $user->id);
    }
}
