<?php

namespace App\Models;

use App\Support\OrganizationContext;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'current_organization_id',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'theme_preference',
        'bio',
        'avatar_path',
        'primary_goal',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_memberships')
            ->withPivot(['id', 'role', 'status', 'joined_at', 'last_active_at'])
            ->withTimestamps();
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function athleteProfiles(): HasMany
    {
        return $this->hasMany(AthleteProfile::class);
    }

    public function coachProfiles(): HasMany
    {
        return $this->hasMany(CoachProfile::class);
    }

    public function coachAssignments(): HasMany
    {
        return $this->hasMany(CoachAthleteAssignment::class, 'coach_id');
    }

    public function athleteAssignments(): HasMany
    {
        return $this->hasMany(CoachAthleteAssignment::class, 'athlete_id');
    }

    public function coachPrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class, 'coach_id');
    }

    public function athletePrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class, 'athlete_id');
    }

    public function programAssignments(): HasMany
    {
        return $this->hasMany(ProgramAssignment::class, 'athlete_id');
    }

    public function scheduledWorkouts(): HasMany
    {
        return $this->hasMany(ScheduledWorkout::class, 'athlete_id');
    }

    public function coachedScheduledWorkouts(): HasMany
    {
        return $this->hasMany(ScheduledWorkout::class, 'coach_id');
    }

    public function progressEntries(): HasMany
    {
        return $this->hasMany(ProgressEntry::class, 'athlete_id');
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class, 'athlete_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at', 'archived_at'])
            ->withTimestamps();
    }

    public function progressPhotos(): HasMany
    {
        return $this->hasMany(ProgressPhoto::class, 'athlete_id');
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class, 'athlete_id');
    }

    public function coachNotes(): HasMany
    {
        return $this->hasMany(CoachNote::class, 'athlete_id');
    }

    public function activeOrganizationMembership(?int $organizationId = null): ?OrganizationMembership
    {
        $organizationId ??= app(OrganizationContext::class)->id() ?: $this->current_organization_id;

        if (! $organizationId) {
            return null;
        }

        return $this->organizationMemberships()
            ->with('permissionOverrides')
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->first();
    }

    public function isPlatformOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isPlatformAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function isOwner(): bool
    {
        return $this->isPlatformOwner();
    }

    public function isAdmin(): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return in_array($this->activeOrganizationMembership()?->role, ['organization_owner', 'organization_admin'], true);
    }

    public function isCoach(): bool
    {
        if ($this->isPlatformAdmin()) {
            return false;
        }

        $membership = $this->activeOrganizationMembership();

        return $membership ? $membership->role === 'coach' : $this->role === 'coach';
    }

    public function isAthlete(): bool
    {
        if ($this->isPlatformAdmin()) {
            return false;
        }

        $membership = $this->activeOrganizationMembership();

        return $membership ? $membership->role === 'athlete' : $this->role === 'athlete';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isPlatformOwner()) {
            return true;
        }

        if ($this->isPlatformAdmin()) {
            return in_array($permission, PermissionCatalog::defaultsForRole($this->role), true)
                || $this->permissions()->where('permission', $permission)->exists();
        }

        $membership = $this->activeOrganizationMembership();

        if ($membership) {
            if ($membership->isOwner() && ! PermissionCatalog::isPlatformOnly($permission)) {
                return true;
            }

            $override = $membership->permissionOverrides->firstWhere('permission', $permission);

            if ($override) {
                return $override->allowed;
            }

            if (in_array($permission, PermissionCatalog::defaultsForOrganizationRole($membership->role), true)) {
                return true;
            }

            return false;
        }

        if (in_array($permission, PermissionCatalog::defaultsForRole($this->role), true)) {
            return true;
        }

        return $this->permissions()->where('permission', $permission)->exists();
    }

    /** @return list<string> */
    public function effectivePermissions(): array
    {
        $catalog = PermissionCatalog::all();

        if ($this->isPlatformOwner()) {
            return $catalog;
        }

        if ($this->isPlatformAdmin()) {
            $permissions = array_fill_keys(PermissionCatalog::defaultsForRole($this->role), true);

            foreach ($this->permissions()->pluck('permission') as $permission) {
                $permissions[$permission] = true;
            }

            return array_values(array_filter($catalog, fn (string $permission): bool => isset($permissions[$permission])));
        }

        $membership = $this->activeOrganizationMembership();

        if ($membership) {
            $defaults = $membership->isOwner()
                ? array_values(array_filter($catalog, fn (string $permission): bool => ! PermissionCatalog::isPlatformOnly($permission)))
                : PermissionCatalog::defaultsForOrganizationRole($membership->role);
            $permissions = array_fill_keys($defaults, true);

            if (! $membership->isOwner()) {
                foreach ($membership->permissionOverrides as $override) {
                    if ($override->allowed) {
                        $permissions[$override->permission] = true;
                    } else {
                        unset($permissions[$override->permission]);
                    }
                }
            }

            return array_values(array_filter($catalog, fn (string $permission): bool => isset($permissions[$permission])));
        }

        $permissions = array_fill_keys(PermissionCatalog::defaultsForRole($this->role), true);

        foreach ($this->permissions()->pluck('permission') as $permission) {
            $permissions[$permission] = true;
        }

        return array_values(array_filter($catalog, fn (string $permission): bool => isset($permissions[$permission])));
    }

    public function syncDefaultPermissions(): void
    {
        $defaults = PermissionCatalog::defaultsForRole($this->role);

        foreach ($defaults as $permission) {
            $this->permissions()->firstOrCreate(['permission' => $permission]);
        }
    }

    public function landingPath(): string
    {
        if ($this->isAdmin()) {
            return route('admin.dashboard');
        }

        return $this->isCoach() ? route('coach.home') : route('app.home');
    }
}
