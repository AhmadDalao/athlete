<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSetLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_log_id',
        'training_session_id',
        'athlete_id',
        'exercise_index',
        'exercise_name',
        'set_number',
        'target_reps',
        'target_load',
        'target_rest_seconds',
        'actual_reps',
        'actual_load',
        'actual_rpe',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'exercise_index' => 'integer',
            'set_number' => 'integer',
            'target_rest_seconds' => 'integer',
            'actual_rpe' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
