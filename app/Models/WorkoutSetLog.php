<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSetLog extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'workout_log_id', 'scheduled_workout_id', 'training_session_exercise_id', 'athlete_id', 'exercise_index', 'exercise_name', 'set_number', 'target_reps', 'target_load', 'target_rest_seconds', 'actual_reps', 'actual_load', 'actual_rpe', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return ['actual_reps' => 'decimal:2', 'actual_load' => 'decimal:2', 'completed_at' => 'datetime'];
    }

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }
}
