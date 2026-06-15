<?php

namespace App\Models;

use App\Enums\WorkoutCompletionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completion_status' => WorkoutCompletionStatus::class,
            'performed_at' => 'datetime',
            'duration_minutes' => 'integer',
            'exertion_rating' => 'integer',
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
}
