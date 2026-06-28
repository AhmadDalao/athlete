<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Support\PermissionCatalog;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'stripe_customer_id',
        'primary_goal',
        'preferred_contact_method',
        'registration_channel',
        'position',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function deviceConnections(): HasMany
    {
        return $this->hasMany(DeviceConnection::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(MetricSnapshot::class);
    }

    public function athleteCheckIns(): HasMany
    {
        return $this->hasMany(AthleteCheckIn::class);
    }

    public function contactSubmissions(): HasMany
    {
        return $this->hasMany(ContactSubmission::class);
    }

    public function latestAthleteCheckIn(): HasOne
    {
        return $this->hasOne(AthleteCheckIn::class)->latestOfMany('logged_date');
    }

    public function trainingProgramsAsCoach(): HasMany
    {
        return $this->hasMany(TrainingProgram::class, 'coach_id');
    }

    public function trainingProgramsAsAthlete(): HasMany
    {
        return $this->hasMany(TrainingProgram::class, 'athlete_id');
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class, 'athlete_id');
    }

    public function workoutSetLogs(): HasMany
    {
        return $this->hasMany(WorkoutSetLog::class, 'athlete_id');
    }

    public function sentCoachAthleteMessages(): HasMany
    {
        return $this->hasMany(CoachAthleteMessage::class, 'sender_id');
    }

    public function receivedCoachAthleteMessages(): HasMany
    {
        return $this->hasMany(CoachAthleteMessage::class, 'recipient_id');
    }

    public function coachAssignments(): HasMany
    {
        return $this->hasMany(CoachAthleteAssignment::class, 'coach_id');
    }

    public function athleteAssignments(): HasMany
    {
        return $this->hasMany(CoachAthleteAssignment::class, 'athlete_id');
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'coach_athlete_assignments', 'coach_id', 'athlete_id')
            ->withPivot(['status', 'goal', 'notes', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'coach_athlete_assignments', 'athlete_id', 'coach_id')
            ->withPivot(['status', 'goal', 'notes', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function scopeRole(Builder $query, RoleName|string $role): Builder
    {
        $roleName = $role instanceof RoleName ? $role->value : $role;

        return $query->whereHas('roles', fn (Builder $builder) => $builder->where('name', $roleName));
    }

    public function hasRole(RoleName|string $role): bool
    {
        $roleName = $role instanceof RoleName ? $role->value : $role;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $roleName);
        }

        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole(RoleName::Owner)) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->isNotEmpty()) {
                return $this->permissions->contains('permission_key', $permission);
            }
        } elseif ($this->permissions()->exists()) {
            return $this->permissions()->where('permission_key', $permission)->exists();
        }

        if ($this->hasRole(RoleName::Admin)) {
            return in_array($permission, PermissionCatalog::defaultsForRole(RoleName::Admin), true);
        }

        return in_array($permission, PermissionCatalog::defaultsForRoles($this->roleNames()), true);
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->hasRole(RoleName::Owner)) {
            return PermissionCatalog::keys();
        }

        if ($this->relationLoaded('permissions')) {
            $explicit = $this->permissions->pluck('permission_key')->all();
        } else {
            $explicit = $this->permissions()->pluck('permission_key')->all();
        }

        if ($explicit !== []) {
            return collect(PermissionCatalog::keys())
                ->filter(fn (string $permission) => in_array($permission, $explicit, true))
                ->values()
                ->all();
        }

        return PermissionCatalog::defaultsForRoles($this->roleNames());
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function syncPermissions(array $permissions, ?self $actor = null): void
    {
        if ($this->hasRole(RoleName::Owner)) {
            $this->permissions()->delete();
            $this->unsetRelation('permissions');

            return;
        }

        $timestamp = now();
        $sanitized = PermissionCatalog::sanitize($permissions);

        $this->permissions()->delete();

        if ($sanitized !== []) {
            $this->permissions()->insert(
                collect($sanitized)
                    ->map(fn (string $permission) => [
                        'user_id' => $this->id,
                        'permission_key' => $permission,
                        'created_by_user_id' => $actor?->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])
                    ->all(),
            );
        }

        $this->unsetRelation('permissions');
    }

    public function assignRole(RoleName|string $role): void
    {
        $roleEnum = $role instanceof RoleName ? $role : RoleName::from($role);

        $roleModel = Role::query()->firstOrCreate(
            ['name' => $roleEnum->value],
            ['label' => $roleEnum->label()],
        );

        $this->roles()->syncWithoutDetaching([
            $roleModel->id => [
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->unsetRelation('roles');
    }

    /**
     * @param  array<int, RoleName|string>  $roles
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = collect($roles)
            ->map(fn (RoleName|string $role) => $role instanceof RoleName ? $role : RoleName::from($role))
            ->unique(fn (RoleName $role) => $role->value)
            ->map(function (RoleName $role) {
                return Role::query()->firstOrCreate(
                    ['name' => $role->value],
                    ['label' => $role->label()],
                )->id;
            })
            ->values();

        $timestamp = now();

        $this->roles()->sync(
            $roleIds
                ->mapWithKeys(fn (int $roleId) => [
                    $roleId => [
                        'assigned_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ],
                ])
                ->all(),
        );

        $this->unsetRelation('roles');
    }

    /**
     * @return list<string>
     */
    public function roleNames(): array
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('name')->all();
        }

        return $this->roles()->pluck('name')->all();
    }

    public function primaryRoleName(): ?string
    {
        /** @var Collection<int, Role> $roles */
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        return $roles
            ->sortByDesc(fn (Role $role) => RoleName::from($role->name)->priority())
            ->first()
            ?->name;
    }

    public function currentMembership(): ?Membership
    {
        /** @var Collection<int, Membership> $memberships */
        $memberships = $this->relationLoaded('memberships')
            ? $this->memberships
            : $this->memberships()->with('plan')->get();

        $currentMembership = $memberships
            ->filter(fn (Membership $membership) => $membership->status?->isCurrentish())
            ->sortByDesc(fn (Membership $membership) => $membership->effectiveEndDate()?->timestamp ?? 0)
            ->first();

        if ($currentMembership) {
            return $currentMembership;
        }

        return $memberships
            ->sortByDesc(fn (Membership $membership) => $membership->effectiveEndDate()?->timestamp ?? 0)
            ->first();
    }

    public function avatarUrl(): ?string
    {
        /** @var Collection<int, SocialAccount> $socialAccounts */
        $socialAccounts = $this->relationLoaded('socialAccounts')
            ? $this->socialAccounts
            : $this->socialAccounts()->get();

        return $socialAccounts
            ->pluck('provider_avatar')
            ->filter(fn ($avatar) => is_string($avatar) && $avatar !== '')
            ->first();
    }

    public function landingPath(): string
    {
        return match ($this->primaryRoleName()) {
            RoleName::Owner->value => '/admin/control-center',
            RoleName::Admin->value => '/admin/control-center',
            RoleName::Coach->value => '/roster',
            RoleName::Athlete->value => '/app',
            default => '/dashboard',
        };
    }
}
