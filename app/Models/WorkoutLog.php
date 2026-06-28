<?php

namespace App\Models;

use App\Enums\WorkoutCompletionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'athlete_id',
        'completion_status',
        'performed_at',
        'duration_minutes',
        'exertion_rating',
        'energy_score',
        'soreness_score',
        'stress_score',
        'sleep_quality_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completion_status' => WorkoutCompletionStatus::class,
            'performed_at' => 'datetime',
            'duration_minutes' => 'integer',
            'exertion_rating' => 'integer',
            'energy_score' => 'integer',
            'soreness_score' => 'integer',
            'stress_score' => 'integer',
            'sleep_quality_score' => 'integer',
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

    public function setLogs(): HasMany
    {
        return $this->hasMany(WorkoutSetLog::class)
            ->orderBy('exercise_index')
            ->orderBy('set_number');
    }
}
