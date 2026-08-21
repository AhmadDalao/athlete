<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramAssignment extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'training_program_id', 'athlete_id', 'assigned_by', 'status', 'starts_on', 'ends_on', 'timezone', 'notes', 'published_at'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'published_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scheduledWorkouts(): HasMany
    {
        return $this->hasMany(ScheduledWorkout::class)->orderBy('scheduled_for');
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    /** @return array{completed: int, total: int, percent: int} */
    public function completionStats(): array
    {
        $workouts = $this->relationLoaded('scheduledWorkouts')
            ? $this->scheduledWorkouts
            : $this->scheduledWorkouts()->with('logs')->get();
        $completed = $workouts->filter(fn (ScheduledWorkout $workout): bool => $workout->status === 'completed' || $workout->logs->contains('status', 'completed')
        )->count();
        $total = $workouts->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }
}
