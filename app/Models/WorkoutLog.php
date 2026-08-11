<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'training_session_id',
        'program_assignment_id',
        'scheduled_workout_id',
        'athlete_id',
        'status',
        'duration_minutes',
        'rpe',
        'set_logs',
        'completed_at',
        'notes',
        'sync_version',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'set_logs' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProgramAssignment::class, 'program_assignment_id');
    }

    public function scheduledWorkout(): BelongsTo
    {
        return $this->belongsTo(ScheduledWorkout::class);
    }

    public function setLogs(): HasMany
    {
        return $this->hasMany(WorkoutSetLog::class)->orderBy('exercise_index')->orderBy('set_number');
    }
}
