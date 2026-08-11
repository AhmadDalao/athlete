<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationMembership extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'role', 'status', 'joined_at', 'last_active_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'last_active_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(MembershipPermissionOverride::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'organization_owner';
    }
}
