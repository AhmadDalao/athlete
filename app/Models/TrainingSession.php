<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'training_program_id',
        'program_phase_id',
        'title',
        'focus',
        'scheduled_on',
        'status',
        'exercises',
        'coach_notes',
        'media_url',
        'day_offset',
        'sort_order',
        'estimated_minutes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'exercises' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProgramPhase::class, 'program_phase_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function prescribedExercises(): HasMany
    {
        return $this->hasMany(TrainingSessionExercise::class)->orderBy('sort_order');
    }

    public function scheduledWorkouts(): HasMany
    {
        return $this->hasMany(ScheduledWorkout::class);
    }

    public function exerciseSummary(): string
    {
        $items = $this->relationLoaded('prescribedExercises') && $this->prescribedExercises->isNotEmpty()
            ? $this->prescribedExercises->map(fn (TrainingSessionExercise $exercise): array => [
                'name' => $exercise->name,
                'sets' => $exercise->target_sets,
                'reps' => $exercise->target_reps,
            ])
            : collect($this->exercises ?? []);

        if ($items->isEmpty()) {
            return 'No exercises yet';
        }

        return $items->take(2)->map(function (array $exercise): string {
            $sets = $exercise['sets'] ?? '-';
            $reps = $exercise['reps'] ?? '-';

            return trim(($exercise['name'] ?? 'Exercise').' '.$sets.'x'.$reps);
        })->implode(', ');
    }

    public function mediaCount(): int
    {
        $exerciseMedia = $this->relationLoaded('prescribedExercises')
            ? $this->prescribedExercises->whereNotNull('media_url')->count()
            : collect($this->exercises ?? [])->filter(
                fn (array $exercise): bool => filled($exercise['media_url'] ?? null)
            )->count();

        return (filled($this->media_url) ? 1 : 0) + $exerciseMedia;
    }

    public function hasMedia(): bool
    {
        return $this->mediaCount() > 0;
    }
}
