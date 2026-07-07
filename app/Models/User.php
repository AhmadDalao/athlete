<?php

namespace App\Models;

use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'bio',
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

    public function progressEntries(): HasMany
    {
        return $this->hasMany(ProgressEntry::class, 'athlete_id');
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class, 'athlete_id');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function isCoach(): bool
    {
        return $this->role === 'coach';
    }

    public function isAthlete(): bool
    {
        return $this->role === 'athlete';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        if ($permission === 'coach.access') {
            return $this->isCoach() || $this->permissions()->where('permission', $permission)->exists();
        }

        if ($permission === 'athlete.access') {
            return $this->isAthlete() || $this->permissions()->where('permission', $permission)->exists();
        }

        return $this->permissions()->where('permission', $permission)->exists();
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
        return match ($this->role) {
            'owner', 'admin' => route('admin.dashboard'),
            'coach' => route('coach.home'),
            default => route('app.home'),
        };
    }
}
