<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduledWorkout extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'program_assignment_id', 'training_session_id', 'athlete_id', 'coach_id', 'scheduled_for', 'status', 'coach_notes', 'athlete_notes'];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProgramAssignment::class, 'program_assignment_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function executionLog(): HasOne
    {
        return $this->hasOne(WorkoutLog::class)->latestOfMany();
    }
}
